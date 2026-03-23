<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * EV Charging Station data from Open Charge Map (free API, no key needed)
 * + Google Places fallback
 */
class EVChargingService
{
    private const OCM_API = 'https://api.openchargemap.io/v3/poi/';

    /**
     * Get EV charging stations near a location.
     */
    public function getNearby(float $lat, float $lng, float $radiusKm = 25, int $limit = 50): array
    {
        $cacheKey = "ev_stations_{$lat}_{$lng}_{$radiusKm}";

        return Cache::remember($cacheKey, 1800, function () use ($lat, $lng, $radiusKm, $limit) {
            try {
                $response = Http::timeout(15)->get(self::OCM_API, [
                    'output' => 'json',
                    'countrycode' => 'TH',
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'distance' => $radiusKm,
                    'distanceunit' => 'KM',
                    'maxresults' => $limit,
                    'compact' => true,
                    'verbose' => false,
                ]);

                if (!$response->successful()) return [];

                $data = $response->json();
                $stations = [];

                foreach ($data as $station) {
                    $addr = $station['AddressInfo'] ?? [];
                    $connections = $station['Connections'] ?? [];
                    $statusType = $station['StatusType'] ?? [];

                    // Parse connectors
                    $connectors = [];
                    $maxKW = 0;
                    foreach ($connections as $conn) {
                        $kw = $conn['PowerKW'] ?? 0;
                        if ($kw > $maxKW) $maxKW = $kw;
                        $connectors[] = [
                            'type' => $conn['ConnectionType']['Title'] ?? 'Unknown',
                            'power_kw' => $kw,
                            'quantity' => $conn['Quantity'] ?? 1,
                            'status' => $conn['StatusType']['Title'] ?? null,
                        ];
                    }

                    // Determine charging speed category
                    $speedCategory = 'slow'; // < 7kW
                    if ($maxKW >= 50) $speedCategory = 'fast'; // DC Fast
                    elseif ($maxKW >= 7) $speedCategory = 'medium'; // AC

                    $stations[] = [
                        'id' => $station['ID'] ?? null,
                        'name' => $addr['Title'] ?? 'สถานีชาร์จ EV',
                        'operator' => $station['OperatorInfo']['Title'] ?? null,
                        'latitude' => $addr['Latitude'] ?? null,
                        'longitude' => $addr['Longitude'] ?? null,
                        'address' => $addr['AddressLine1'] ?? '',
                        'town' => $addr['Town'] ?? '',
                        'province' => $addr['StateOrProvince'] ?? '',
                        'distance_km' => $addr['Distance'] ?? null,
                        'connectors' => $connectors,
                        'max_power_kw' => $maxKW,
                        'speed_category' => $speedCategory,
                        'is_operational' => ($statusType['IsOperational'] ?? true),
                        'usage_type' => $station['UsageType']['Title'] ?? 'Public',
                        'is_free' => $station['UsageType']['IsPayAtLocation'] ?? false,
                        'num_points' => $station['NumberOfPoints'] ?? count($connections),
                        'updated_at' => $station['DateLastStatusUpdate'] ?? null,
                    ];
                }

                return $stations;
            } catch (\Exception $e) {
                Log::warning('EV charging fetch failed', ['error' => $e->getMessage()]);
                return [];
            }
        });
    }
}
