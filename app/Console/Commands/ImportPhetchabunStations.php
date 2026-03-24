<?php

namespace App\Console\Commands;

use App\Models\StationReport;
use App\Models\FuelReport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Import Phetchabun fuel station data from official government report.
 * Source: สำนักงานพลังงานจังหวัดเพชรบูรณ์ (Google Apps Script)
 * Data from: 24 มี.ค. 69
 */
class ImportPhetchabunStations extends Command
{
    protected $signature = 'import:phetchabun-stations {--dry-run : Show what would be imported without saving}';
    protected $description = 'Import Phetchabun province gas station data with coordinates';

    // Phetchabun station data from government report (24 มี.ค. 2569)
    // Coordinates are approximate center of each area
    private array $stations = [
        [
            'name' => 'ปตท แคมป์สน ขาเข้า',
            'brand' => 'PTT',
            'area' => 'เขาค้อ',
            'lat' => 16.5422,
            'lng' => 101.0528,
            'diesel' => 'available',
            'benzin' => 'available',
            'note' => 'รายงานเมื่อ 24 มี.ค. 69 เวลา 10.52 น.',
        ],
        [
            'name' => 'พีที ป่าคา',
            'brand' => 'PT',
            'area' => 'เขาค้อ',
            'lat' => 16.5380,
            'lng' => 101.0610,
            'diesel' => 'empty',
            'benzin' => 'low',
            'note' => 'รายงานเมื่อ 24 มี.ค. 69 เวลา 11.30 น.',
        ],
        [
            'name' => 'ปตท แคมป์สน ขาออก',
            'brand' => 'PTT',
            'area' => 'เขาค้อ',
            'lat' => 16.5418,
            'lng' => 101.0535,
            'diesel' => 'available',
            'benzin' => 'available',
            'note' => 'รายงานเมื่อ 24 มี.ค. 69 เวลา 10.52 น.',
        ],
        [
            'name' => 'พีที แคมป์สน',
            'brand' => 'PT',
            'area' => 'เขาค้อ',
            'lat' => 16.5430,
            'lng' => 101.0520,
            'diesel' => 'empty',
            'benzin' => 'available',
            'note' => 'รายงานเมื่อ 24 มี.ค. 69 เวลา 10.10 น.',
        ],
        [
            'name' => 'ปตท ชนแดน',
            'brand' => 'PTT',
            'area' => 'ชนแดน',
            'lat' => 15.8573,
            'lng' => 100.8697,
            'diesel' => 'empty',
            'benzin' => 'available',
            'note' => 'รายงานเมื่อ 24 มี.ค. 69 เวลา 09.49 น.',
        ],
        [
            'name' => 'พีที ชนแดน',
            'brand' => 'PT',
            'area' => 'ชนแดน',
            'lat' => 15.8560,
            'lng' => 100.8710,
            'diesel' => 'empty',
            'benzin' => 'available',
            'note' => 'รายงานเมื่อ 24 มี.ค. 69 เวลา 10.07 น.',
        ],
        [
            'name' => 'บางจาก ชนแดน',
            'brand' => 'Bangchak',
            'area' => 'ชนแดน',
            'lat' => 15.8590,
            'lng' => 100.8685,
            'diesel' => 'available',
            'benzin' => 'available',
            'note' => 'รายงานเมื่อ 24 มี.ค. 69 เวลา 09.55 น.',
        ],
        [
            'name' => 'บางจาก กรป กลาง วังโป่ง',
            'brand' => 'Bangchak',
            'area' => 'วังโป่ง',
            'lat' => 15.8990,
            'lng' => 100.9960,
            'diesel' => 'empty',
            'benzin' => 'low',
            'note' => 'รายงานเมื่อ 23 มี.ค. 69 เวลา 09.39 น.',
        ],
    ];

    public function handle(): int
    {
        $this->info('📊 นำเข้าข้อมูลปั๊มน้ำมันจังหวัดเพชรบูรณ์');
        $this->info('แหล่งข้อมูล: สำนักงานพลังงานจังหวัดเพชรบูรณ์');
        $this->newLine();

        $isDryRun = $this->option('dry-run');
        $imported = 0;
        $skipped = 0;

        // Try to refine coordinates using Google Maps Geocoding
        $googleKey = config('services.google_maps.api_key');

        foreach ($this->stations as $station) {
            $this->line("⛽ {$station['name']} ({$station['area']})");
            $this->line("   ดีเซล: {$station['diesel']} | เบนซิน: {$station['benzin']}");

            // Try to get better coordinates from Google
            $lat = $station['lat'];
            $lng = $station['lng'];

            if ($googleKey) {
                try {
                    $query = "ปั๊ม{$station['brand']} {$station['name']} {$station['area']} เพชรบูรณ์";
                    $geoRes = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
                        'address' => $query,
                        'key' => $googleKey,
                        'language' => 'th',
                        'region' => 'th',
                    ]);

                    if ($geoRes->successful()) {
                        $results = $geoRes->json('results', []);
                        if (!empty($results)) {
                            $location = $results[0]['geometry']['location'] ?? null;
                            if ($location) {
                                $lat = $location['lat'];
                                $lng = $location['lng'];
                                $this->line("   📍 พิกัดจาก Google: {$lat}, {$lng}");
                            }
                        }
                    }
                } catch (\Exception $e) {
                    // Use default coordinates
                    $this->warn("   ⚠️ ใช้พิกัดเริ่มต้น (Geocode ล้มเหลว)");
                }
            }

            // Check if station already exists (by name + area proximity)
            $existing = StationReport::where('station_name', 'like', "%{$station['name']}%")
                ->whereBetween('latitude', [$lat - 0.01, $lat + 0.01])
                ->whereBetween('longitude', [$lng - 0.01, $lng + 0.01])
                ->first();

            if ($existing) {
                $this->warn("   ⏭️ มีอยู่แล้ว — ข้าม");
                $skipped++;
                continue;
            }

            if ($isDryRun) {
                $this->info("   🔍 [DRY RUN] จะสร้างที่ ({$lat}, {$lng})");
                $imported++;
                continue;
            }

            // Create station report
            $report = StationReport::create([
                'place_id' => 'gov_phetchabun_' . md5($station['name']),
                'station_name' => $station['name'],
                'brand' => $station['brand'],
                'latitude' => $lat,
                'longitude' => $lng,
                'note' => $station['note'],
                'reporter_name' => 'สำนักงานพลังงานจังหวัดเพชรบูรณ์',
                'is_verified' => true,
                'confirmation_count' => 3, // Government source = trusted
            ]);

            // Create fuel reports
            FuelReport::create([
                'station_report_id' => $report->id,
                'fuel_type' => 'diesel',
                'status' => $station['diesel'],
            ]);

            FuelReport::create([
                'station_report_id' => $report->id,
                'fuel_type' => 'gasohol95',
                'status' => $station['benzin'],
            ]);

            $this->info("   ✅ สร้างสำเร็จ (ID: {$report->id})");
            $imported++;
        }

        $this->newLine();
        $this->info("📊 สรุป: นำเข้า {$imported} ปั๊ม, ข้าม {$skipped} ปั๊ม");

        if ($isDryRun) {
            $this->warn('🔍 นี่เป็น DRY RUN — ไม่มีข้อมูลถูกบันทึก');
            $this->info('ลบ --dry-run เพื่อนำเข้าจริง');
        }

        return Command::SUCCESS;
    }
}
