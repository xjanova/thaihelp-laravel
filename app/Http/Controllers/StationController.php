<?php

namespace App\Http\Controllers;

use App\Models\FuelReport;
use App\Models\SiteSetting;
use App\Models\StationReport;
use App\Services\DemoDataService;
use App\Services\DiscordService;
use App\Services\FuelPriceService;
use App\Services\GooglePlacesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class StationController extends Controller
{
    /**
     * Show the stations map page.
     */
    public function index()
    {
        $googleMapsApiKey = SiteSetting::get('google_maps_api_key') ?: config('services.google_maps.api_key', '');

        return view('pages.stations', [
            'googleMapsApiKey' => $googleMapsApiKey,
        ]);
    }

    /**
     * API: Search nearby gas stations.
     */
    public function apiSearch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'radius' => ['nullable', 'integer', 'min:100', 'max:50000'],
        ]);

        try {
            $lat = $validated['lat'];
            $lng = $validated['lng'];
            $radius = $validated['radius'] ?? 5000;

            // Auto-generate demo data if nothing nearby (check once per location per 10 min)
            $demoKey = 'demo_check_' . round($lat, 2) . '_' . round($lng, 2);
            if (!Cache::has($demoKey)) {
                app(DemoDataService::class)->ensureDemoNearby($lat, $lng);
                DemoDataService::cleanupOldDemo();
                Cache::put($demoKey, true, 600); // 10 minutes
            }

            // Get stations from Google Places API
            $placesService = app(GooglePlacesService::class);
            $stations = $placesService->searchNearby($lat, $lng, $radius);

            // Get fuel reports from DB (last 6 hours)
            $placeIds = collect($stations)->pluck('place_id')->filter()->toArray();

            $recentReports = StationReport::whereIn('place_id', $placeIds)
                ->where('created_at', '>=', now()->subHours(6))
                ->with('fuelReports')
                ->latest()
                ->get()
                ->keyBy('place_id');

            // Also get demo reports (not limited to placeIds from Google)
            $demoReports = StationReport::where('is_demo', true)
                ->with('fuelReports')
                ->get();

            // Merge Google data with our fuel reports
            $merged = collect($stations)->map(function ($station) use ($recentReports) {
                $placeId = $station['place_id'] ?? null;
                $report = $placeId ? $recentReports->get($placeId) : null;

                return [
                    ...$station,
                    'fuel_reports' => $report?->fuelReports?->toArray() ?? [],
                    'last_report_at' => $report?->created_at?->toISOString(),
                    'reporter_name' => $report?->reporter_name,
                    'is_demo' => $report?->is_demo ?? false,
                    'is_verified' => $report?->is_verified ?? false,
                ];
            });

            // Add demo stations that aren't already in Google results
            $existingPlaceIds = $merged->pluck('place_id')->filter()->toArray();
            $demoStations = $demoReports->filter(fn($r) => !in_array($r->place_id, $existingPlaceIds))
                ->map(fn($r) => [
                    'place_id' => $r->place_id,
                    'name' => $r->station_name,
                    'vicinity' => $r->note,
                    'lat' => $r->latitude,
                    'lng' => $r->longitude,
                    'fuel_reports' => $r->fuelReports->toArray(),
                    'last_report_at' => $r->created_at->toISOString(),
                    'reporter_name' => $r->reporter_name,
                    'is_demo' => true,
                    'is_verified' => $r->is_verified,
                    'confirmation_count' => $r->confirmation_count,
                ]);

            $merged = $merged->concat($demoStations);

            return response()->json([
                'success' => true,
                'data' => $merged->values(),
            ]);
        } catch (\Exception $e) {
            Log::error('Station search failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถค้นหาปั๊มน้ำมันได้ กรุณาลองใหม่',
            ], 500);
        }
    }

    /**
     * API: Submit a fuel report for a station.
     */
    public function apiReport(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'placeId' => ['required', 'string', 'max:500'],
            'stationName' => ['required', 'string', 'max:255'],
            'reporterName' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:1000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'fuelReports' => ['required', 'array', 'min:1'],
            'fuelReports.*.fuel_type' => ['required', 'string', 'in:' . implode(',', FuelReport::FUEL_TYPES)],
            'fuelReports.*.status' => ['required', 'string', 'in:' . implode(',', FuelReport::STATUSES)],
            'fuelReports.*.price' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'facilities' => ['nullable', 'array'],
            'facilities.*' => ['string', 'in:' . implode(',', array_keys(StationReport::FACILITY_TYPES))],
        ]);

        try {
            // Auto-remove demo data for this station when real report comes in
            StationReport::replaceDemoWithReal($validated['placeId']);

            // Build facilities with status
            $facilities = [];
            if (!empty($validated['facilities'])) {
                foreach ($validated['facilities'] as $facility) {
                    $facilities[$facility] = ['status' => 'working'];
                }
            }

            $stationReport = StationReport::create([
                'place_id' => $validated['placeId'],
                'station_name' => $validated['stationName'],
                'user_id' => $request->user()?->id,
                'reporter_name' => $validated['reporterName'] ?? 'ไม่ระบุชื่อ',
                'note' => $validated['note'] ?? null,
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
                'is_demo' => false,
                'facilities' => !empty($facilities) ? $facilities : null,
            ]);

            foreach ($validated['fuelReports'] as $fuel) {
                $stationReport->fuelReports()->create([
                    'fuel_type' => $fuel['fuel_type'],
                    'status' => $fuel['status'],
                    'price' => $fuel['price'] ?? null,
                ]);
            }

            $stationReport->load('fuelReports');

            if ($request->user()) {
                $request->user()->incrementReports();
            }

            // Remove demo data for this station (real report replaces demo)
            if (!empty($validated['latitude']) && !empty($validated['longitude'])) {
                try {
                    $removed = DemoDataService::replaceDemoWithReal(
                        (float) $validated['latitude'],
                        (float) $validated['longitude']
                    );
                    if ($removed > 0) {
                        Log::info('Demo data replaced by real report', ['removed' => $removed]);
                    }
                } catch (\Exception $e) {
                    // Silent fail
                }
            }

            // Send Discord notification
            try {
                app(DiscordService::class)->notifyNewStationReport($stationReport);
            } catch (\Exception $e) {
                Log::warning('Discord notification failed', ['error' => $e->getMessage()]);
            }

            return response()->json([
                'success' => true,
                'message' => 'รายงานสำเร็จ ขอบคุณครับ!',
                'data' => $stationReport,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Station report failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถส่งรายงานได้ กรุณาลองใหม่',
            ], 500);
        }
    }

    /**
     * API: Confirm a station report.
     */
    public function apiConfirm(Request $request, StationReport $report): JsonResponse
    {
        $ip = $request->ip();

        if ($report->confirm($ip)) {
            if ($request->user()) {
                $request->user()->incrementConfirmations();
            }

            return response()->json([
                'success' => true,
                'message' => 'ยืนยันรายงานสำเร็จ ขอบคุณค่ะ!',
                'data' => [
                    'confirmation_count' => $report->confirmation_count,
                    'is_verified' => $report->is_verified,
                ],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'คุณได้ยืนยันรายงานนี้แล้ว',
        ], 409);
    }

    /**
     * API: Get today's official fuel prices.
     */
    public function apiFuelPrices(): JsonResponse
    {
        try {
            $prices = app(FuelPriceService::class)->getTodayPrices();

            return response()->json([
                'success' => true,
                'data' => $prices,
                'facility_types' => StationReport::FACILITY_TYPES,
            ]);
        } catch (\Exception $e) {
            Log::error('Fuel prices fetch failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถดึงราคาน้ำมันได้',
            ], 500);
        }
    }

    public function apiUpdate(Request $request, StationReport $report): JsonResponse
    {
        if (!$request->user() || $request->user()->id !== $report->user_id) {
            return response()->json(['success' => false, 'message' => 'ไม่มีสิทธิ์แก้ไข'], 403);
        }

        $validated = $request->validate([
            'station_name' => ['sometimes', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $report->update($validated);
        return response()->json(['success' => true, 'data' => $report->fresh()]);
    }

    public function apiDestroy(Request $request, StationReport $report): JsonResponse
    {
        if (!$request->user() || $request->user()->id !== $report->user_id) {
            return response()->json(['success' => false, 'message' => 'ไม่มีสิทธิ์ลบ'], 403);
        }

        $report->fuelReports()->delete();
        $report->delete();
        return response()->json(['success' => true, 'message' => 'ลบรายงานสำเร็จ']);
    }
}
