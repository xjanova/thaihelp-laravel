<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use App\Models\SiteSetting;
use App\Services\ApiKeyPool;
use App\Services\EVChargingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TripPlannerController extends Controller
{
    public function index()
    {
        return view('pages.trip-planner');
    }

    /**
     * API: Plan a trip — returns route + stations + incidents + EV chargers along the way.
     */
    public function plan(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'origin_lat' => ['required', 'numeric'],
            'origin_lng' => ['required', 'numeric'],
            'dest_lat' => ['required', 'numeric'],
            'dest_lng' => ['required', 'numeric'],
            'vehicle_type' => ['nullable', 'string', 'in:car,ev,motorcycle'],
            'fuel_type' => ['nullable', 'string'],
        ]);

        $originLat = $validated['origin_lat'];
        $originLng = $validated['origin_lng'];
        $destLat = $validated['dest_lat'];
        $destLng = $validated['dest_lng'];
        $vehicleType = $validated['vehicle_type'] ?? 'car';

        try {
            // Get route via Google Directions API
            $route = $this->getRoute($originLat, $originLng, $destLat, $destLng);

            // Get waypoints along the route (every ~50km)
            $waypoints = $this->getRouteWaypoints($route);

            // Find fuel stations along route
            $fuelStations = $this->getFuelStationsAlongRoute($waypoints);

            // Find EV chargers along route (if EV)
            $evChargers = [];
            if ($vehicleType === 'ev') {
                $evChargers = $this->getEVChargersAlongRoute($waypoints);
            }

            // Find active incidents along route
            $incidents = $this->getIncidentsAlongRoute($waypoints);

            // Find danger zones along route
            $dangerZones = Incident::active()
                ->where('is_danger_zone', true)
                ->get(['id', 'title', 'category', 'severity', 'latitude', 'longitude', 'danger_radius_km']);

            // Calculate trip summary — ensure distance is never 0
            $distanceKm = $route['distance_km'] ?? 0;
            $durationMin = $route['duration_min'] ?? 0;

            // If distance is still 0 (API returned no distance), compute via Haversine
            if ($distanceKm <= 0) {
                $straightLine = $this->haversine($originLat, $originLng, $destLat, $destLng);
                $distanceKm = round($straightLine * 1.35, 1);
                $durationMin = max(1, round($distanceKm / 60 * 60));
                $route['distance_km'] = $distanceKm;
                $route['duration_min'] = $durationMin;
                $route['is_fallback'] = true;
            }

            $summary = [
                'distance_km' => $distanceKm,
                'duration_min' => $durationMin,
                'fuel_stations_count' => count($fuelStations),
                'ev_chargers_count' => count($evChargers),
                'incidents_count' => count($incidents),
                'danger_zones_count' => $dangerZones->count(),
                'has_warnings' => count($incidents) > 0 || $dangerZones->count() > 0,
            ];

            // น้องหญิงสรุปการเดินทาง
            $yingSummary = $this->buildYingSummary($summary, $incidents, $dangerZones);

            return response()->json([
                'success' => true,
                'data' => [
                    'route' => $route,
                    'summary' => $summary,
                    'fuel_stations' => $fuelStations,
                    'ev_chargers' => $evChargers,
                    'incidents' => $incidents,
                    'danger_zones' => $dangerZones,
                    'ying_summary' => $yingSummary,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Trip plan failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถวางแผนเส้นทางได้ กรุณาลองใหม่',
            ], 500);
        }
    }

    /**
     * Get route from Google Directions API.
     */
    private function getRoute(float $oLat, float $oLng, float $dLat, float $dLng): array
    {
        $apiKey = ApiKeyPool::getKey('google_maps')
            ?: SiteSetting::get('google_maps_api_key')
            ?: config('services.google_maps.api_key');
        if (!$apiKey) {
            return $this->fallbackRoute($oLat, $oLng, $dLat, $dLng);
        }

        try {
            $response = Http::timeout(10)->get('https://maps.googleapis.com/maps/api/directions/json', [
                'origin' => "{$oLat},{$oLng}",
                'destination' => "{$dLat},{$dLng}",
                'key' => $apiKey,
                'language' => 'th',
                'alternatives' => true,
            ]);

            $data = $response->json();
            if (($data['status'] ?? '') !== 'OK') {
                return $this->fallbackRoute($oLat, $oLng, $dLat, $dLng);
            }

            $route = $data['routes'][0] ?? [];
            $leg = $route['legs'][0] ?? [];

            return [
                'polyline' => $route['overview_polyline']['points'] ?? null,
                'distance_km' => round(($leg['distance']['value'] ?? 0) / 1000, 1),
                'duration_min' => round(($leg['duration']['value'] ?? 0) / 60),
                'start_address' => $leg['start_address'] ?? '',
                'end_address' => $leg['end_address'] ?? '',
                'steps' => array_map(fn($s) => [
                    'instruction' => strip_tags($s['html_instructions'] ?? ''),
                    'distance' => $s['distance']['text'] ?? '',
                    'duration' => $s['duration']['text'] ?? '',
                    'lat' => $s['end_location']['lat'] ?? 0,
                    'lng' => $s['end_location']['lng'] ?? 0,
                ], $leg['steps'] ?? []),
                'alternatives' => count($data['routes'] ?? []) - 1,
            ];
        } catch (\Exception $e) {
            return $this->fallbackRoute($oLat, $oLng, $dLat, $dLng);
        }
    }

    /**
     * Fallback: straight-line distance when no API key.
     */
    private function fallbackRoute(float $oLat, float $oLng, float $dLat, float $dLng): array
    {
        $straightLineDist = $this->haversine($oLat, $oLng, $dLat, $dLng);
        // Road distance is typically 1.3-1.4x straight-line distance
        $roadDist = round($straightLineDist * 1.35, 1);
        $durationMin = max(1, round($roadDist / 60 * 60)); // ~60 km/h average

        Log::debug('Trip fallback route', [
            'origin' => [$oLat, $oLng],
            'dest' => [$dLat, $dLng],
            'straight_km' => $straightLineDist,
            'road_km' => $roadDist,
        ]);

        return [
            'polyline' => null,
            'distance_km' => $roadDist,
            'duration_min' => $durationMin,
            'start_address' => "{$oLat}, {$oLng}",
            'end_address' => "{$dLat}, {$dLng}",
            'steps' => [],
            'alternatives' => 0,
            'is_fallback' => true,
        ];
    }

    /**
     * Get waypoints every ~30km along route.
     */
    private function getRouteWaypoints(array $route): array
    {
        $points = [];

        if (!empty($route['steps'])) {
            foreach ($route['steps'] as $step) {
                $points[] = ['lat' => $step['lat'], 'lng' => $step['lng']];
            }
        }

        // If no steps, interpolate between origin and destination
        if (empty($points)) {
            $points[] = [
                'lat' => (float) request('origin_lat'),
                'lng' => (float) request('origin_lng'),
            ];
            $points[] = [
                'lat' => (float) request('dest_lat'),
                'lng' => (float) request('dest_lng'),
            ];
        }

        // Sample every ~30km
        $sampled = [$points[0]];
        $lastSampled = $points[0];
        foreach ($points as $p) {
            $dist = $this->haversine($lastSampled['lat'], $lastSampled['lng'], $p['lat'], $p['lng']);
            if ($dist >= 30) {
                $sampled[] = $p;
                $lastSampled = $p;
            }
        }
        $sampled[] = end($points);

        return $sampled;
    }

    /**
     * Find fuel stations near route waypoints.
     */
    private function getFuelStationsAlongRoute(array $waypoints): array
    {
        $stations = [];
        $seenIds = [];

        foreach ($waypoints as $wp) {
            $nearby = \App\Models\StationReport::whereRaw('(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) < 5', [
                    $wp['lat'], $wp['lng'], $wp['lat'],
                ])
                ->with('fuelReports')
                ->limit(5)
                ->get();

            foreach ($nearby as $s) {
                if (in_array($s->id, $seenIds)) continue;
                $seenIds[] = $s->id;
                $stations[] = $s;
            }
        }

        return array_slice($stations, 0, 30);
    }

    /**
     * Find EV chargers along route.
     */
    private function getEVChargersAlongRoute(array $waypoints): array
    {
        $chargers = [];
        $seenIds = [];
        $evService = app(EVChargingService::class);

        foreach ($waypoints as $wp) {
            $nearby = $evService->getNearby($wp['lat'], $wp['lng'], 10, 10);
            foreach ($nearby as $c) {
                $id = $c['id'] ?? md5($c['latitude'] . $c['longitude']);
                if (in_array($id, $seenIds)) continue;
                $seenIds[] = $id;
                $chargers[] = $c;
            }
        }

        return array_slice($chargers, 0, 30);
    }

    /**
     * Find active incidents along route.
     */
    private function getIncidentsAlongRoute(array $waypoints): array
    {
        $incidents = [];
        $seenIds = [];

        foreach ($waypoints as $wp) {
            $nearby = Incident::active()
                ->withinRadius($wp['lat'], $wp['lng'], 5)
                ->limit(10)
                ->get();

            foreach ($nearby as $i) {
                if (in_array($i->id, $seenIds)) continue;
                $seenIds[] = $i->id;
                $incidents[] = $i;
            }
        }

        return $incidents;
    }

    /**
     * น้องหญิงสรุปการเดินทาง
     */
    private function buildYingSummary(array $summary, array $incidents, $dangerZones): string
    {
        $parts = [];
        $parts[] = "🗺️ ระยะทาง {$summary['distance_km']} กม. ใช้เวลาประมาณ {$summary['duration_min']} นาทีค่ะ";

        if ($summary['fuel_stations_count'] > 0) {
            $parts[] = "⛽ มีปั๊มน้ำมัน {$summary['fuel_stations_count']} แห่งตลอดเส้นทาง";
        }

        if ($summary['ev_chargers_count'] > 0) {
            $parts[] = "🔌 มีสถานีชาร์จ EV {$summary['ev_chargers_count']} แห่ง";
        }

        if (count($incidents) > 0) {
            $parts[] = "⚠️ ระวังนะคะ มีเหตุการณ์ " . count($incidents) . " จุดบนเส้นทาง";
            foreach (array_slice($incidents, 0, 3) as $i) {
                $emoji = Incident::CATEGORY_EMOJI[$i->category] ?? '⚠️';
                $parts[] = "  {$emoji} {$i->title}";
            }
        }

        if ($dangerZones->count() > 0) {
            $parts[] = "🔴 มีพื้นที่อันตราย {$dangerZones->count()} จุด หลีกเลี่ยงถ้าเป็นไปได้ค่ะ";
        }

        if (!$summary['has_warnings']) {
            $parts[] = "✅ เส้นทางปลอดภัยดีค่ะ เดินทางดีนะคะ~";
        }

        $parts[] = "— น้องหญิง 💕";

        return implode("\n", $parts);
    }

    /**
     * Haversine distance in km.
     */
    private function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $r = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return $r * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
