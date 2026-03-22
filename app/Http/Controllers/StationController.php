<?php

namespace App\Http\Controllers;

use App\Models\FuelReport;
use App\Models\StationReport;
use App\Services\GooglePlacesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StationController extends Controller
{
    /**
     * Show the stations map page.
     */
    public function index()
    {
        return view('pages.stations', [
            'googleMapsApiKey' => config('services.google.maps_api_key'),
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

            // Get stations from Google Places API
            $placesService = app(GooglePlacesService::class);
            $stations = $placesService->nearbyStations($lat, $lng, $radius);

            // Get fuel reports from DB (last 6 hours)
            $placeIds = collect($stations)->pluck('place_id')->filter()->toArray();

            $recentReports = StationReport::whereIn('place_id', $placeIds)
                ->where('created_at', '>=', now()->subHours(6))
                ->with('fuelReports')
                ->latest()
                ->get()
                ->keyBy('place_id');

            // Merge Google data with our fuel reports
            $merged = collect($stations)->map(function ($station) use ($recentReports) {
                $placeId = $station['place_id'] ?? null;
                $report = $placeId ? $recentReports->get($placeId) : null;

                return [
                    ...$station,
                    'fuel_reports' => $report?->fuelReports?->toArray() ?? [],
                    'last_report_at' => $report?->created_at?->toISOString(),
                    'reporter_name' => $report?->reporter_name,
                ];
            });

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
        ]);

        try {
            $stationReport = StationReport::create([
                'place_id' => $validated['placeId'],
                'station_name' => $validated['stationName'],
                'user_id' => $request->user()?->id,
                'reporter_name' => $validated['reporterName'] ?? 'ไม่ระบุชื่อ',
                'note' => $validated['note'] ?? null,
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
            ]);

            foreach ($validated['fuelReports'] as $fuel) {
                $stationReport->fuelReports()->create([
                    'fuel_type' => $fuel['fuel_type'],
                    'status' => $fuel['status'],
                    'price' => $fuel['price'] ?? null,
                ]);
            }

            $stationReport->load('fuelReports');

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
}
