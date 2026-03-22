<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GooglePlacesService
{
    private string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.google_maps.api_key', '');
    }

    /**
     * Search nearby gas stations using Google Places API.
     */
    public function searchNearby(float $lat, float $lng, int $radius = 10000): array
    {
        $radius = max(500, min(100000, $radius));

        $cacheKey = "places_nearby_{$lat}_{$lng}_{$radius}";

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($lat, $lng, $radius) {
            try {
                $response = Http::get('https://maps.googleapis.com/maps/api/place/nearbysearch/json', [
                    'location' => "{$lat},{$lng}",
                    'radius' => $radius,
                    'type' => 'gas_station',
                    'language' => 'th',
                    'key' => $this->apiKey,
                ]);

                if ($response->failed()) {
                    Log::error('Google Places API request failed', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                    return [];
                }

                $data = $response->json();

                if (($data['status'] ?? '') !== 'OK') {
                    Log::warning('Google Places API returned non-OK status', [
                        'status' => $data['status'] ?? 'unknown',
                        'error_message' => $data['error_message'] ?? '',
                    ]);
                    return [];
                }

                return $this->parseResults($data['results'] ?? [], $lat, $lng);
            } catch (\Exception $e) {
                Log::error('Google Places API exception', ['message' => $e->getMessage()]);
                return [];
            }
        });
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

        usort($stations, fn($a, $b) => $a['distance'] <=> $b['distance']);

        return $stations;
    }

    /**
     * Detect gas station brand from name.
     */
    public function detectBrand(string $name): string
    {
        $brands = [
            'PTT' => ['ptt', 'ปตท', 'พีทีที'],
            'Shell' => ['shell', 'เชลล์'],
            'Bangchak' => ['bangchak', 'บางจาก'],
            'Esso' => ['esso', 'เอสโซ่'],
            'Caltex' => ['caltex', 'คาลเท็กซ์'],
            'Susco' => ['susco', 'ซัสโก้'],
            'PT' => ['pt ', 'พีที'],
            'Cosmo' => ['cosmo', 'คอสโม'],
        ];

        $lowerName = mb_strtolower($name);

        foreach ($brands as $brand => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($lowerName, mb_strtolower($keyword))) {
                    return $brand;
                }
            }
        }

        return 'อื่นๆ';
    }

    /**
     * Calculate distance between two coordinates using Haversine formula.
     */
    public function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c, 1);
    }
}
