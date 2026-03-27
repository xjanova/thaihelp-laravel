<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GooglePlacesService
{
    /**
     * Density tiers: [min_density => [max_radius_km, max_pages, label]]
     * density = stations per km² from first page
     *
     * Bangkok center:  ~0.6 stations/km² → cap 5km, 2 pages (40 max)
     * Bangkok suburbs:  ~0.2 stations/km² → cap 10km, 3 pages (60 max)
     * Urban:           ~0.08 stations/km² → cap 20km, 3 pages (60 max)
     * Rural:           < 0.08              → cap 50km, 3 pages (60 max)
     */
    private const DENSITY_TIERS = [
        0.40 => ['max_radius' => 5000,  'max_pages' => 2, 'label' => 'very_dense'],
        0.15 => ['max_radius' => 10000, 'max_pages' => 3, 'label' => 'dense'],
        0.06 => ['max_radius' => 20000, 'max_pages' => 3, 'label' => 'moderate'],
        0.00 => ['max_radius' => 50000, 'max_pages' => 3, 'label' => 'sparse'],
    ];

    private function getApiKey(): string
    {
        return ApiKeyPool::getKey('google_maps', 'google_maps.api_key')
            ?: SiteSetting::get('google_maps_api_key')
            ?: config('services.google_maps.api_key', '');
    }

    public function getFrontendApiKey(): string
    {
        return $this->getApiKey();
    }

    /**
     * Progressive search: returns first page immediately with metadata.
     * Frontend calls back for additional pages using the returned page_token.
     */
    public function searchNearbyPaged(float $lat, float $lng, int $radius, ?string $keyword = null, ?string $pageToken = null): array
    {
        $radius = max(500, min(50000, $radius));

        // Page 2+ uses cached token
        if ($pageToken) {
            return $this->fetchNextPage($pageToken, $lat, $lng);
        }

        // Page 1: fresh search
        $cacheKey = "places_p1_" . round($lat, 4) . "_" . round($lng, 4) . "_{$radius}" . ($keyword ? "_" . md5($keyword) : '');

        $cached = Cache::get($cacheKey);
        if ($cached) {
            return $cached;
        }

        try {
            $params = [
                'location' => "{$lat},{$lng}",
                'radius' => $radius,
                'type' => 'gas_station',
                'language' => 'th',
                'key' => $this->getApiKey(),
            ];

            if ($keyword) {
                $params['keyword'] = $keyword;
            }

            $response = Http::timeout(8)->get('https://maps.googleapis.com/maps/api/place/nearbysearch/json', $params);

            if ($response->failed()) {
                return $this->emptyResult();
            }

            $data = $response->json();

            if (($data['status'] ?? '') !== 'OK') {
                return $this->emptyResult();
            }

            $results = $data['results'] ?? [];
            $stations = $this->parseResults($results, $lat, $lng);
            $nextToken = $data['next_page_token'] ?? null;

            // Calculate density from first page
            $density = $this->calculateDensity(count($results), $radius / 1000);
            $tier = $this->getDensityTier($density);
            $effectiveMaxRadius = $tier['max_radius'];
            $maxPages = $tier['max_pages'];

            // If user requested more than density allows, note it
            $radiusCapped = $radius > $effectiveMaxRadius;
            $effectiveRadius = min($radius, $effectiveMaxRadius);

            // Store next_page_token in cache for frontend to request
            $tokenKey = null;
            $hasMore = false;
            if ($nextToken && $maxPages > 1) {
                $tokenKey = 'gp_tok_' . bin2hex(random_bytes(8));
                Cache::put($tokenKey, [
                    'token' => $nextToken,
                    'lat' => $lat,
                    'lng' => $lng,
                    'page' => 2,
                    'max_pages' => $maxPages,
                ], now()->addMinutes(3)); // Google tokens expire quickly
                $hasMore = true;
            }

            $result = [
                'stations' => $stations,
                'total' => count($stations),
                'page' => 1,
                'has_more' => $hasMore,
                'page_token' => $tokenKey,
                'density' => round($density, 4),
                'density_label' => $tier['label'],
                'effective_radius' => $effectiveRadius,
                'radius_capped' => $radiusCapped,
                'max_pages' => $maxPages,
            ];

            Cache::put($cacheKey, $result, now()->addMinutes(5));

            return $result;
        } catch (\Exception $e) {
            Log::error('Google Places API exception', ['message' => $e->getMessage()]);
            return $this->emptyResult();
        }
    }

    /**
     * Fetch next page using stored token.
     */
    private function fetchNextPage(string $tokenKey, float $lat, float $lng): array
    {
        $tokenData = Cache::get($tokenKey);
        if (!$tokenData) {
            return $this->emptyResult('token_expired');
        }

        $currentPage = $tokenData['page'] ?? 2;
        $maxPages = $tokenData['max_pages'] ?? 3;

        try {
            $response = Http::timeout(6)->get('https://maps.googleapis.com/maps/api/place/nearbysearch/json', [
                'pagetoken' => $tokenData['token'],
                'key' => $this->getApiKey(),
            ]);

            if ($response->failed() || ($response->json('status') !== 'OK')) {
                // Token might not be ready yet — Google requires ~2s
                Cache::forget($tokenKey);
                return $this->emptyResult('page_failed');
            }

            $data = $response->json();
            $stations = $this->parseResults($data['results'] ?? [], $lat, $lng);
            $nextToken = $data['next_page_token'] ?? null;

            // Store next token if more pages allowed
            $hasMore = false;
            $newTokenKey = null;
            if ($nextToken && $currentPage < $maxPages) {
                $newTokenKey = 'gp_tok_' . bin2hex(random_bytes(8));
                Cache::put($newTokenKey, [
                    'token' => $nextToken,
                    'lat' => $lat,
                    'lng' => $lng,
                    'page' => $currentPage + 1,
                    'max_pages' => $maxPages,
                ], now()->addMinutes(3));
                $hasMore = true;
            }

            // Clean up used token
            Cache::forget($tokenKey);

            return [
                'stations' => $stations,
                'total' => count($stations),
                'page' => $currentPage,
                'has_more' => $hasMore,
                'page_token' => $newTokenKey,
            ];
        } catch (\Exception $e) {
            Cache::forget($tokenKey);
            return $this->emptyResult('error');
        }
    }

    /**
     * Calculate station density (stations per km²).
     */
    private function calculateDensity(int $stationCount, float $radiusKm): float
    {
        $area = M_PI * $radiusKm * $radiusKm;
        if ($area <= 0) return 0;
        return $stationCount / $area;
    }

    /**
     * Get density tier config.
     */
    private function getDensityTier(float $density): array
    {
        foreach (self::DENSITY_TIERS as $minDensity => $config) {
            if ($density >= $minDensity) {
                return $config;
            }
        }
        return self::DENSITY_TIERS[0.00];
    }

    private function emptyResult(string $reason = ''): array
    {
        return [
            'stations' => [],
            'total' => 0,
            'page' => 0,
            'has_more' => false,
            'page_token' => null,
            'reason' => $reason,
        ];
    }

    /**
     * Legacy method — kept for home page map which loads all at once.
     */
    public function searchNearby(float $lat, float $lng, int $radius = 10000, ?string $keyword = null): array
    {
        $result = $this->searchNearbyPaged($lat, $lng, $radius, $keyword);
        $allStations = $result['stations'] ?? [];

        // For legacy callers, auto-fetch all pages (with delay)
        $pageToken = $result['page_token'] ?? null;
        $page = 1;
        while ($pageToken && $page < 3) {
            usleep(2000000); // Google requires ~2s
            $nextResult = $this->fetchNextPage($pageToken, $lat, $lng);
            $allStations = array_merge($allStations, $nextResult['stations'] ?? []);
            $pageToken = $nextResult['page_token'] ?? null;
            $page++;
        }

        usort($allStations, fn ($a, $b) => $a['distance'] <=> $b['distance']);
        return $allStations;
    }

    /**
     * Parse API results into a structured array.
     */
    private function parseResults(array $results, float $originLat, float $originLng): array
    {
        $stations = [];

        foreach ($results as $place) {
            $placeLat = $place['geometry']['location']['lat'] ?? 0;
            $placeLng = $place['geometry']['location']['lng'] ?? 0;
            $name = $place['name'] ?? '';

            $stations[] = [
                'place_id' => $place['place_id'] ?? '',
                'name' => $name,
                'vicinity' => $place['vicinity'] ?? '',
                'latitude' => $placeLat,
                'longitude' => $placeLng,
                'rating' => $place['rating'] ?? null,
                'user_ratings_total' => $place['user_ratings_total'] ?? 0,
                'opening_hours' => $place['opening_hours']['open_now'] ?? null,
                'distance' => $this->calculateDistance($originLat, $originLng, $placeLat, $placeLng),
                'brand' => $this->detectBrand($name),
            ];
        }

        usort($stations, fn ($a, $b) => $a['distance'] <=> $b['distance']);

        return $stations;
    }

    /**
     * Detect gas station brand from name.
     */
    public function detectBrand(string $name): string
    {
        $brands = [
            'ptt' => ['ptt', 'ปตท', 'พีทีที'],
            'shell' => ['shell', 'เชลล์'],
            'bangchak' => ['bangchak', 'บางจาก'],
            'esso' => ['esso', 'เอสโซ'],
            'caltex' => ['caltex', 'คาลเท็กซ์'],
            'susco' => ['susco', 'ซัสโก้'],
            'pt' => ['พีที', 'พีทีแม็กซ์', 'ptmax', 'pt max'],
            'pure' => ['pure', 'เพียว'],
            'irpc' => ['irpc', 'ไออาร์พีซี'],
            'cosmo' => ['cosmo', 'คอสโม'],
        ];

        $lowerName = mb_strtolower($name);

        foreach ($brands as $brand => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($lowerName, mb_strtolower($keyword))) {
                    return $brand;
                }
            }
        }

        if (preg_match('/\bpt\b/i', $lowerName)) {
            return 'pt';
        }

        return 'other';
    }

    /**
     * Calculate distance between two coordinates using Haversine formula.
     */
    public function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($dLng / 2) * sin($dLng / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return round($earthRadius * $c, 1);
    }
}
