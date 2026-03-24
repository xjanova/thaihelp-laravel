<?php

namespace App\Services;

use App\Models\BreakingNews;
use App\Models\HospitalReport;
use App\Models\Incident;
use App\Models\SiteSetting;
use App\Models\StationReport;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * สร้าง context ที่รวมข้อมูลรอบตัวผู้ใช้ ให้น้องหญิงรู้
 * Smart loading: โหลดเฉพาะข้อมูลที่เกี่ยวข้องกับคำถาม
 */
class YingContextBuilder
{
    // Keyword groups for smart context loading
    private const KEYWORDS = [
        'stations'  => ['ปั๊ม', 'น้ำมัน', 'เติม', 'diesel', 'ดีเซล', 'แก๊ส', 'benzin', '95', '91', 'e20', 'lpg', 'ngv', 'เชลล์', 'shell', 'ptt', 'บางจาก', 'esso', 'caltex', 'susco'],
        'incidents' => ['อุบัติเหตุ', 'น้ำท่วม', 'ถนน', 'ปิด', 'จุดตรวจ', 'ก่อสร้าง', 'เหตุ', 'ระวัง', 'อันตราย', 'ไฟไหม้'],
        'hospitals' => ['โรงพยาบาล', 'รพ', 'คลินิก', 'ER', 'เตียง', 'ICU', 'เจ็บ', 'ป่วย', 'ฉุกเฉิน', 'หมอ'],
        'weather'   => ['อากาศ', 'ฝน', 'PM', 'pm2.5', 'หมอก', 'ร้อน', 'หนาว', 'พายุ'],
        'fuel'      => ['ราคา', 'แพง', 'ถูก', 'ขึ้น', 'ลด'],
    ];

    /**
     * สร้าง context string โดยโหลดเฉพาะข้อมูลที่เกี่ยวข้อง
     */
    public function build(float $lat, float $lng, ?int $userId = null, string $lastMessage = ''): string
    {
        $parts = [];
        $needs = $this->detectNeeds($lastMessage);
        $radiusKm = (int) (SiteSetting::get('search_radius_km') ?: 10);
        $radiusKm = min($radiusKm, 30); // Hard cap at 30km

        // Always load: breaking news (tiny) + stations (most common need)
        $parts[] = $this->buildBreakingNewsContext();

        if ($needs['stations'] || $needs['default']) {
            $parts[] = $this->buildStationContext($lat, $lng, $radiusKm);
        }

        if ($needs['incidents'] || $needs['default']) {
            $parts[] = $this->buildIncidentContext($lat, $lng, min($radiusKm * 2, 30));
        }

        if ($needs['fuel'] || $needs['stations']) {
            $parts[] = $this->buildFuelPriceContext();
        }

        if ($needs['hospitals']) {
            $parts[] = $this->buildHospitalContext($lat, $lng, $radiusKm);
        }

        if ($needs['weather']) {
            $parts[] = $this->buildWeatherContext($lat, $lng);
        }

        // Stats: always (1 line)
        $parts[] = $this->buildStatsContext();

        // User context if logged in
        if ($userId) {
            $parts[] = $this->buildUserContext($userId);
        }

        return implode("\n", array_filter($parts));
    }

    /**
     * Detect which contexts are needed based on user's message.
     */
    private function detectNeeds(string $message): array
    {
        $msg = mb_strtolower($message);
        $needs = [
            'stations' => false,
            'incidents' => false,
            'hospitals' => false,
            'weather' => false,
            'fuel' => false,
            'default' => true, // stations + incidents if nothing specific matched
        ];

        foreach (self::KEYWORDS as $group => $keywords) {
            foreach ($keywords as $kw) {
                if (mb_strpos($msg, mb_strtolower($kw)) !== false) {
                    $needs[$group] = true;
                    $needs['default'] = false;
                    break;
                }
            }
        }

        return $needs;
    }

    /**
     * Build station context using Google Places cache + DB reports.
     * Uses bounding box pre-filter for performance.
     */
    private function buildStationContext(float $lat, float $lng, int $radiusKm): string
    {
        try {
            // Try to use cached Google Places data first (from /stations page)
            $cacheKey = 'places_nearby_' . round($lat, 2) . '_' . round($lng, 2);
            $googleStations = Cache::get($cacheKey, []);

            // Also get DB station reports with bounding box pre-filter
            $latDelta = $radiusKm / 111.0; // ~111km per degree latitude
            $lngDelta = $radiusKm / (111.0 * cos(deg2rad($lat)));

            $dbReports = StationReport::with('fuelReports')
                ->where('is_demo', false)
                ->whereBetween('latitude', [$lat - $latDelta, $lat + $latDelta])
                ->whereBetween('longitude', [$lng - $lngDelta, $lng + $lngDelta])
                ->where('created_at', '>=', now()->subHours(12))
                ->latest()
                ->limit(15)
                ->get();

            // Merge: Google Places + DB reports
            $stations = [];

            // Add Google Places stations (if cached)
            foreach (array_slice($googleStations, 0, 10) as $gs) {
                $dist = $this->haversine($lat, $lng, $gs['lat'] ?? 0, $gs['lng'] ?? 0);
                if ($dist > $radiusKm) continue;

                $placeId = $gs['place_id'] ?? '';
                $dbReport = $dbReports->firstWhere('place_id', $placeId);

                $fuels = '';
                if ($dbReport && $dbReport->fuelReports->isNotEmpty()) {
                    $fuels = $dbReport->fuelReports->map(fn($f) =>
                        "{$f->fuel_type}:{$f->status}" . ($f->price ? "({$f->price}บ.)" : '')
                    )->implode(', ');
                }

                $stations[] = [
                    'name' => $gs['name'] ?? 'ไม่ทราบชื่อ',
                    'brand' => $gs['brand'] ?? '',
                    'dist' => $dist,
                    'lat' => $gs['lat'] ?? 0,
                    'lng' => $gs['lng'] ?? 0,
                    'fuels' => $fuels,
                    'place_id' => $placeId,
                ];
            }

            // Add DB-only reports not already included
            $existingPlaceIds = array_column($stations, 'place_id');
            foreach ($dbReports as $r) {
                if (in_array($r->place_id, $existingPlaceIds)) continue;

                $dist = $this->haversine($lat, $lng, $r->latitude, $r->longitude);
                if ($dist > $radiusKm) continue;

                $fuels = $r->fuelReports->map(fn($f) =>
                    "{$f->fuel_type}:{$f->status}" . ($f->price ? "({$f->price}บ.)" : '')
                )->implode(', ');

                $stations[] = [
                    'name' => $r->station_name,
                    'brand' => $r->brand ?? '',
                    'dist' => $dist,
                    'lat' => $r->latitude,
                    'lng' => $r->longitude,
                    'fuels' => $fuels,
                    'place_id' => $r->place_id,
                ];
            }

            if (empty($stations)) return '';

            // Sort by distance
            usort($stations, fn($a, $b) => $a['dist'] <=> $b['dist']);

            $lines = ["═══ ปั๊มน้ำมันใกล้ผู้ใช้ (รัศมี {$radiusKm} กม.) ═══"];
            foreach (array_slice($stations, 0, 10) as $i => $s) {
                $num = $i + 1;
                $distStr = $this->formatDistance($s['dist']);
                $brand = $s['brand'] ? " ({$s['brand']})" : '';
                $fuelInfo = $s['fuels'] ? " | {$s['fuels']}" : '';
                $lines[] = "{$num}. {$s['name']}{$brand} — {$distStr}{$fuelInfo}";
                $lines[] = "   พิกัด: {$s['lat']},{$s['lng']}";
            }
            return implode("\n", $lines);
        } catch (\Exception $e) {
            Log::warning('buildStationContext failed', ['error' => $e->getMessage()]);
            return '';
        }
    }

    private function buildIncidentContext(float $lat, float $lng, int $radiusKm): string
    {
        try {
            // Bounding box pre-filter
            $latDelta = $radiusKm / 111.0;
            $lngDelta = $radiusKm / (111.0 * cos(deg2rad($lat)));

            $incidents = Incident::active()
                ->where('is_demo', false)
                ->whereBetween('latitude', [$lat - $latDelta, $lat + $latDelta])
                ->whereBetween('longitude', [$lng - $lngDelta, $lng + $lngDelta])
                ->latest()
                ->limit(10)
                ->get()
                ->filter(fn($i) => $this->haversine($lat, $lng, $i->latitude, $i->longitude) <= $radiusKm);

            if ($incidents->isEmpty()) return "\n═══ เหตุการณ์ใกล้ตัว ═══\nไม่มีเหตุการณ์ในรัศมี {$radiusKm} กม. ✅";

            $labels = Incident::CATEGORY_LABELS;
            $sevLabels = Incident::SEVERITY_LABELS;
            $lines = ["\n═══ เหตุการณ์ใกล้ตัว ═══"];
            foreach ($incidents as $i) {
                $cat = $labels[$i->category] ?? $i->category;
                $sev = $sevLabels[$i->severity ?? 'medium'] ?? 'ปานกลาง';
                $dist = $this->haversine($lat, $lng, $i->latitude, $i->longitude);
                $distStr = $this->formatDistance($dist);
                $danger = $i->is_danger_zone ? ' 🚫อันตราย' : '';
                $lines[] = "- [{$sev}] {$cat}: {$i->title} — {$distStr} (ยืนยัน {$i->confirmation_count} คน){$danger}";
                $lines[] = "  พิกัด: {$i->latitude},{$i->longitude}";
            }
            return implode("\n", $lines);
        } catch (\Exception $e) {
            return '';
        }
    }

    private function buildBreakingNewsContext(): string
    {
        try {
            $news = BreakingNews::active()->latest()->limit(3)->get();
            if ($news->isEmpty()) return '';

            $lines = ["═══ ข่าวด่วน ═══"];
            foreach ($news as $n) {
                $lines[] = "🔴 {$n->title} — {$n->reporter_count} คนรายงาน";
            }
            return implode("\n", $lines);
        } catch (\Exception $e) {
            return '';
        }
    }

    private function buildFuelPriceContext(): string
    {
        try {
            $prices = Cache::get('fuel_prices_today', []);
            if (empty($prices)) {
                $prices = app(FuelPriceService::class)->getTodayPrices();
            }
            if (empty($prices)) return '';

            $lines = ["\n═══ ราคาน้ำมันวันนี้ ═══"];
            foreach ($prices as $type => $data) {
                $lines[] = "- {$data['name']}: {$data['price']} บ./ลิตร";
            }
            return implode("\n", $lines);
        } catch (\Exception $e) {
            return '';
        }
    }

    private function buildWeatherContext(float $lat, float $lng): string
    {
        try {
            $extService = app(ExternalDataService::class);
            $weather = $extService->getWeather($lat, $lng);
            $aqi = $extService->getAirQuality($lat, $lng);

            $lines = ["\n═══ สภาพอากาศ ═══"];
            if (!empty($weather['current'])) {
                $w = $weather['current'];
                $lines[] = "{$w['icon']} {$w['description']} {$w['temp']}°C (รู้สึก {$w['feels_like']}°C) ความชื้น {$w['humidity']}%";
            }
            if (!empty($aqi['aqi'])) {
                $lines[] = "💨 AQI: {$aqi['aqi']} ({$aqi['label_th']})" . ($aqi['pm25'] ? " PM2.5: {$aqi['pm25']}" : '');
            }
            return implode("\n", $lines);
        } catch (\Exception $e) {
            return '';
        }
    }

    private function buildHospitalContext(float $lat, float $lng, int $radiusKm): string
    {
        try {
            $latDelta = $radiusKm / 111.0;
            $lngDelta = $radiusKm / (111.0 * cos(deg2rad($lat)));

            $hospitals = HospitalReport::whereBetween('latitude', [$lat - $latDelta, $lat + $latDelta])
                ->whereBetween('longitude', [$lng - $lngDelta, $lng + $lngDelta])
                ->where('created_at', '>=', now()->subHours(12))
                ->latest()
                ->limit(5)
                ->get();

            if ($hospitals->isEmpty()) return '';

            $statusLabels = HospitalReport::ER_STATUS_LABELS;
            $lines = ["\n═══ สถานพยาบาลใกล้ตัว ═══"];
            foreach ($hospitals as $h) {
                $er = $statusLabels[$h->er_status] ?? 'ไม่ทราบ';
                $dist = $this->haversine($lat, $lng, $h->latitude, $h->longitude);
                $distStr = $this->formatDistance($dist);
                $beds = $h->available_beds !== null ? "เตียงว่าง {$h->available_beds}" : '';
                $lines[] = "🏥 {$h->hospital_name} — {$distStr} | ER: {$er} {$beds}";
                if ($h->phone) $lines[] = "   โทร: {$h->phone} | พิกัด: {$h->latitude},{$h->longitude}";
            }
            return implode("\n", $lines);
        } catch (\Exception $e) {
            return '';
        }
    }

    private function buildStatsContext(): string
    {
        try {
            $totalReports = Incident::where('is_demo', false)->count();
            $activeNow = Incident::active()->where('is_demo', false)->count();
            return "\n═══ สถิติ ═══\nรายงานทั้งหมด: {$totalReports} | กำลังเกิด: {$activeNow}";
        } catch (\Exception $e) {
            return '';
        }
    }

    private function buildUserContext(int $userId): string
    {
        try {
            $user = \App\Models\User::find($userId);
            if (!$user) return '';

            $starInfo = $user->getStarLevel();
            $starLevel = is_array($starInfo) ? $starInfo : ['name' => 'สมาชิกใหม่', 'icon' => '⭐'];

            return "\n═══ ผู้ใช้ ═══\nชื่อ: " . ($user->nickname ?? $user->name) . " | {$starLevel['icon']} {$starLevel['name']} | คะแนน: {$user->reputation_score}";
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Format distance in human-readable Thai.
     */
    private function formatDistance(float $km): string
    {
        if ($km < 0.1) {
            return round($km * 1000) . ' เมตร';
        }
        if ($km < 1) {
            return round($km * 1000, -1) . ' เมตร'; // round to nearest 10m
        }
        if ($km < 10) {
            return round($km, 1) . ' กม.';
        }
        return round($km) . ' กม.';
    }

    private function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $r = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return $r * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
