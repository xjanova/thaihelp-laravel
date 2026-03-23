<?php

namespace App\Services;

use App\Models\FuelReport;
use App\Models\StationReport;

class DemoDataService
{
    /**
     * Thai gas station brand templates for generating demo data.
     */
    private const BRANDS = [
        'PTT Station', 'Shell', 'Bangchak', 'Esso', 'Caltex',
        'Susco', 'PT', 'Cosmo', 'พีที',
    ];

    private const ROAD_NAMES = [
        'ถนนหลัก', 'ซอย 5', 'ถนนมิตรภาพ', 'ถนนพหลโยธิน', 'ถนนสุขุมวิท',
        'ถนนเพชรเกษม', 'ถนนรามอินทรา', 'ถนนลาดพร้าว', 'ถนนวิภาวดี',
        'ถนนรัชดาภิเษก', 'ซอยรามคำแหง', 'ถนนศรีนครินทร์', 'ถนนบางนา-ตราด',
    ];

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
        'ที่เติมลมเสีย ซ่อมอยู่',
        'มีที่ล้างรถด้วย เปิดถึง 2 ทุ่ม',
        'WiFi ฟรี นั่งพักได้',
        'ราคาถูกกว่าที่อื่น 50 สตางค์',
    ];

    /**
     * Today's base fuel prices (updated concept — in production, fetch from API).
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
     * Check if demo data is needed near user's location.
     * If no real reports exist within radius, generate demo stations.
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
            return; // Real data exists, no need for demo
        }

        // Check if demo data already exists nearby
        $demoCount = StationReport::where('is_demo', true)
            ->where('created_at', '>=', now()->subHours(4))
            ->whereRaw('(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) < ?', [
                $lat, $lng, $lat, $radiusKm,
            ])
            ->count();

        if ($demoCount >= 3) {
            return; // Enough demo data already
        }

        // Generate demo stations around this location
        $this->generateDemoStations($lat, $lng);
    }

    /**
     * Generate 5-8 demo gas stations around a GPS location.
     */
    public function generateDemoStations(float $centerLat, float $centerLng): void
    {
        $count = rand(5, 8);
        $basePrices = $this->basePrices();

        for ($i = 0; $i < $count; $i++) {
            // Random offset within ~5km radius
            $latOffset = (rand(-5000, 5000) / 100000);
            $lngOffset = (rand(-5000, 5000) / 100000);
            $stationLat = $centerLat + $latOffset;
            $stationLng = $centerLng + $lngOffset;

            $brand = self::BRANDS[array_rand(self::BRANDS)];
            $road = self::ROAD_NAMES[array_rand(self::ROAD_NAMES)];
            $name = "{$brand} {$road}";

            $report = StationReport::create([
                'place_id'           => 'demo_' . md5($name . $stationLat . now()->timestamp . $i),
                'station_name'       => $name,
                'reporter_name'      => self::REPORTER_NAMES[array_rand(self::REPORTER_NAMES)],
                'note'               => self::NOTES[array_rand(self::NOTES)],
                'latitude'           => $stationLat,
                'longitude'          => $stationLng,
                'is_demo'            => true,
                'is_verified'        => true,
                'confirmation_count' => rand(2, 12),
                'confirmed_ips'      => json_encode(['demo-auto']),
                'created_at'         => now()->subMinutes(rand(5, 180)),
            ]);

            // Generate fuel reports with regional price variation
            $priceVariation = (rand(-100, 100) / 100); // ±1.00 baht variation
            $fuelTypes = $this->randomFuelTypes();

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
    }

    /**
     * Pick random fuel types for a station (3-6 types).
     */
    private function randomFuelTypes(): array
    {
        $allTypes = ['gasohol95', 'gasohol91', 'e20', 'diesel', 'diesel_b7'];
        $extraTypes = ['e85', 'premium_diesel', 'ngv', 'lpg'];

        // Always include gasohol95 and diesel
        $types = ['gasohol95', 'diesel'];

        // Add 1-3 more from common types
        $remaining = array_diff($allTypes, $types);
        shuffle($remaining);
        $addCount = rand(1, min(3, count($remaining)));
        $types = array_merge($types, array_slice($remaining, 0, $addCount));

        // 30% chance to add an extra type
        if (rand(1, 100) <= 30) {
            $types[] = $extraTypes[array_rand($extraTypes)];
        }

        return array_unique($types);
    }

    /**
     * Random fuel status with realistic distribution.
     */
    private function randomStatus(): string
    {
        $roll = rand(1, 100);
        if ($roll <= 65) return 'available';    // 65% available
        if ($roll <= 80) return 'low';          // 15% low
        if ($roll <= 95) return 'empty';        // 15% empty
        return 'unknown';                        // 5% unknown
    }

    /**
     * Clean up old demo data (older than 4 hours).
     */
    public static function cleanupOldDemo(): int
    {
        $old = StationReport::where('is_demo', true)
            ->where('created_at', '<', now()->subHours(4))
            ->get();

        $count = $old->count();
        foreach ($old as $report) {
            $report->fuelReports()->delete();
            $report->delete();
        }

        return $count;
    }
}
