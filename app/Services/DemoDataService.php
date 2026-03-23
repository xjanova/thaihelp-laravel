<?php

namespace App\Services;

use App\Models\FuelReport;
use App\Models\StationReport;
use Illuminate\Support\Facades\Log;

class DemoDataService
{
    private const REPORTER_NAMES = [
        'คุณสมชาย', 'คุณนภา', 'คุณวิภา', 'คุณธนา', 'คุณกิตติ',
        'คุณแอน', 'คุณเอก', 'คุณพิม', 'คุณโจ้', 'คุณมาลี',
        'คุณต้อม', 'คุณนิด', 'คุณเบนซ์', 'คุณหนุ่ม', 'คุณปริญญา',
    ];

    private const NOTES = [
        'น้ำมันเต็มทุกหัวจ่าย คิวไม่ยาว',
        'คิว 5 คัน ประมาณ 10 นาที',
        'เปิด 24 ชม. ครบทุกชนิด',
        'ที่เติมลมใช้ได้ ฟรี',
        'ร้านสะดวกซื้อเปิดอยู่',
        'ห้องน้ำสะอาด มีร้านกาแฟ',
        'ดีเซลใกล้หมด รีบมา',
        'เพิ่งเติมถังมาใหม่เมื่อเช้า',
        'คิวยาวมาก 20+ คัน',
        'ปั๊มเงียบ เติมได้ทันที',
    ];

    /**
     * Base fuel prices (in production, fetch from API).
     */
    private function basePrices(): array
    {
        return [
            'gasohol95'      => 36.04,
            'gasohol91'      => 33.54,
            'e20'            => 32.04,
            'e85'            => 25.04,
            'diesel'         => 29.94,
            'diesel_b7'      => 29.94,
            'premium_diesel' => 34.94,
            'ngv'            => 18.59,
            'lpg'            => 23.47,
        ];
    }

    /**
     * Proximity threshold in km — if two coordinates are within this,
     * they are considered the same station.
     */
    private const SAME_STATION_RADIUS_KM = 0.15; // 150 meters

    /**
     * Check if demo data is needed near user's location.
     * Uses Google Places to find REAL stations nearby.
     */
    public function ensureDemoNearby(float $lat, float $lng, int $radiusKm = 10): void
    {
        // Check if real (non-demo) reports exist nearby (last 24h)
        $realCount = StationReport::where('is_demo', false)
            ->where('created_at', '>=', now()->subHours(24))
            ->whereRaw('(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) < ?', [
                $lat, $lng, $lat, $radiusKm,
            ])
            ->count();

        if ($realCount > 0) {
            return; // Real data exists, no demo needed
        }

        // Check if demo data already exists nearby (within 4 hours)
        $demoCount = StationReport::where('is_demo', true)
            ->where('created_at', '>=', now()->subHours(4))
            ->whereRaw('(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) < ?', [
                $lat, $lng, $lat, $radiusKm,
            ])
            ->count();

        if ($demoCount >= 3) {
            return; // Enough demo data
        }

        // Generate demo from REAL Google Places stations
        $this->generateFromRealStations($lat, $lng);
    }

    /**
     * Generate demo data using REAL gas station locations from Google Places API.
     * This ensures demo pins match actual station positions on the map.
     */
    public function generateFromRealStations(float $lat, float $lng): void
    {
        try {
            $placesService = app(GooglePlacesService::class);
            $stations = $placesService->searchNearby($lat, $lng, 5000); // 5km radius

            if (empty($stations)) {
                // Fallback: try wider radius
                $stations = $placesService->searchNearby($lat, $lng, 15000);
            }

            if (empty($stations)) {
                Log::info('DemoData: No Google Places stations found', compact('lat', 'lng'));
                return;
            }

            // Take up to 8 stations
            $stations = array_slice($stations, 0, 8);
            $basePrices = $this->basePrices();

            foreach ($stations as $station) {
                $stationLat = $station['lat'] ?? $station['latitude'] ?? null;
                $stationLng = $station['lng'] ?? $station['longitude'] ?? null;

                if (!$stationLat || !$stationLng) continue;

                $stationLat = (float) $stationLat;
                $stationLng = (float) $stationLng;
                $stationName = $station['name'] ?? 'ปั๊มน้ำมัน';
                $placeId = $station['place_id'] ?? ('demo_' . md5($stationName . $stationLat));
                $vicinity = $station['vicinity'] ?? '';

                // Check if demo for this exact station already exists
                $exists = StationReport::where('is_demo', true)
                    ->where('created_at', '>=', now()->subHours(4))
                    ->whereRaw('(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) < ?', [
                        $stationLat, $stationLng, $stationLat, self::SAME_STATION_RADIUS_KM,
                    ])
                    ->exists();

                if ($exists) continue;

                // Detect brand from name
                $brand = $this->detectBrand($stationName);

                $report = StationReport::create([
                    'place_id'           => 'demo_' . $placeId,
                    'station_name'       => $stationName,
                    'brand'              => $brand,
                    'reporter_name'      => self::REPORTER_NAMES[array_rand(self::REPORTER_NAMES)],
                    'note'               => self::NOTES[array_rand(self::NOTES)],
                    'latitude'           => $stationLat,  // EXACT coords from Google Places
                    'longitude'          => $stationLng,  // EXACT coords from Google Places
                    'is_demo'            => true,
                    'is_verified'        => true,
                    'confirmation_count' => rand(2, 12),
                    'confirmed_ips'      => ['demo-auto'],
                    'created_at'         => now()->subMinutes(rand(5, 180)),
                ]);

                // Generate fuel reports with brand-appropriate types
                $priceVariation = (rand(-100, 100) / 100);
                $fuelTypes = $this->fuelTypesForBrand($brand);

                foreach ($fuelTypes as $fuelType) {
                    $basePrice = $basePrices[$fuelType] ?? 30.00;
                    $status = $this->randomStatus();

                    FuelReport::create([
                        'report_id' => $report->id,
                        'fuel_type' => $fuelType,
                        'status'    => $status,
                        'price'     => $status !== 'empty' ? round($basePrice + $priceVariation, 2) : null,
                    ]);
                }
            }

            Log::info('DemoData: Generated from real stations', [
                'lat' => $lat, 'lng' => $lng, 'count' => count($stations),
            ]);
        } catch (\Exception $e) {
            Log::error('DemoData: Failed to generate', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Detect brand from station name.
     */
    private function detectBrand(string $name): string
    {
        $name = mb_strtolower($name);
        $brands = [
            'ptt' => 'PTT', 'ปตท' => 'PTT',
            'shell' => 'Shell', 'เชลล์' => 'Shell',
            'bangchak' => 'Bangchak', 'บางจาก' => 'Bangchak',
            'esso' => 'Esso', 'เอสโซ่' => 'Esso',
            'caltex' => 'Caltex', 'คาลเท็กซ์' => 'Caltex',
            'susco' => 'Susco', 'ซัสโก้' => 'Susco',
            'pt ' => 'PT', 'พีที' => 'PT',
        ];

        foreach ($brands as $keyword => $brand) {
            if (str_contains($name, $keyword)) {
                return $brand;
            }
        }

        return 'อื่นๆ';
    }

    /**
     * Fuel types appropriate for each brand.
     */
    private function fuelTypesForBrand(string $brand): array
    {
        $base = ['gasohol95', 'gasohol91', 'diesel'];

        return match ($brand) {
            'PTT' => array_merge($base, ['e20', 'diesel_b7', 'premium_diesel']),
            'Shell' => array_merge($base, ['e20', 'diesel_b7']),
            'Bangchak' => array_merge($base, ['e20', 'e85']),
            'Esso' => array_merge($base, ['diesel_b7']),
            'Caltex' => array_merge($base, ['diesel_b7']),
            default => $base,
        };
    }

    /**
     * Random fuel status with realistic distribution.
     */
    private function randomStatus(): string
    {
        $roll = rand(1, 100);
        if ($roll <= 65) return 'available';
        if ($roll <= 80) return 'low';
        if ($roll <= 95) return 'empty';
        return 'unknown';
    }

    /**
     * Check if coordinates match an existing station (within 150m).
     */
    public static function isSameStation(float $lat1, float $lng1, float $lat2, float $lng2): bool
    {
        $distance = 6371 * acos(
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            cos(deg2rad($lng2) - deg2rad($lng1)) +
            sin(deg2rad($lat1)) * sin(deg2rad($lat2))
        );

        return $distance < self::SAME_STATION_RADIUS_KM;
    }

    /**
     * When a real report comes in, remove demo data for the same station.
     */
    public static function replaceDemoWithReal(float $lat, float $lng): int
    {
        $demoIds = StationReport::where('is_demo', true)
            ->whereRaw('(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) < ?', [
                $lat, $lng, $lat, self::SAME_STATION_RADIUS_KM,
            ])
            ->pluck('id');

        if ($demoIds->isEmpty()) return 0;

        FuelReport::whereIn('report_id', $demoIds)->delete();
        return StationReport::whereIn('id', $demoIds)->delete();
    }

    /**
     * Clean up old demo data (older than 4 hours).
     */
    public static function cleanupOldDemo(): int
    {
        $oldIds = StationReport::where('is_demo', true)
            ->where('created_at', '<', now()->subHours(4))
            ->pluck('id');

        if ($oldIds->isEmpty()) return 0;

        FuelReport::whereIn('report_id', $oldIds)->delete();
        return StationReport::whereIn('id', $oldIds)->delete();
    }
}
