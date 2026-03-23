<?php

namespace App\Services;

use App\Models\BreakingNews;
use App\Models\HospitalReport;
use App\Models\Incident;
use App\Models\StationReport;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * สร้าง context ที่รวมข้อมูลทั้งหมดของระบบ ให้น้องหญิงรู้ทุกอย่าง
 */
class YingContextBuilder
{
    /**
     * สร้าง context string ที่ inject เข้า system prompt ของ Groq
     */
    public function build(float $lat, float $lng, ?int $userId = null): string
    {
        $parts = [];

        // 1. ปั๊มน้ำมันใกล้ตัว + สถานะน้ำมัน
        $parts[] = $this->buildStationContext($lat, $lng);

        // 2. เหตุการณ์ใกล้ตัว
        $parts[] = $this->buildIncidentContext($lat, $lng);

        // 3. ข่าวด่วน
        $parts[] = $this->buildBreakingNewsContext();

        // 4. ราคาน้ำมันวันนี้
        $parts[] = $this->buildFuelPriceContext();

        // 5. สภาพอากาศ + คุณภาพอากาศ
        $parts[] = $this->buildWeatherContext($lat, $lng);

        // 6. สถานพยาบาลใกล้ตัว
        $parts[] = $this->buildHospitalContext($lat, $lng);

        // 7. แผ่นดินไหวล่าสุด
        $parts[] = $this->buildEarthquakeContext();

        // 8. สถิติรวม
        $parts[] = $this->buildStatsContext();

        // 9. ข้อมูลผู้ใช้ (ถ้า login)
        if ($userId) {
            $parts[] = $this->buildUserContext($userId);
        }

        return implode("\n", array_filter($parts));
    }

    private function buildStationContext(float $lat, float $lng): string
    {
        try {
            $stations = StationReport::with('fuelReports')
                ->where('is_demo', false)
                ->whereRaw('(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) < 10', [$lat, $lng, $lat])
                ->latest()
                ->limit(15)
                ->get();

            if ($stations->isEmpty()) return '';

            $lines = ["═══ ปั๊มน้ำมันใกล้ผู้ใช้ (รัศมี 10 กม.) ═══"];
            foreach ($stations as $i => $s) {
                $num = $i + 1;
                $fuels = $s->fuelReports->map(fn($f) => "{$f->fuel_type}:{$f->status}" . ($f->price ? "({$f->price}บ.)" : ''))->implode(', ');
                $dist = round($this->haversine($lat, $lng, $s->latitude, $s->longitude), 1);
                $lines[] = "{$num}. {$s->station_name} ({$s->brand}) — {$dist} กม. | {$fuels}";
                $lines[] = "   พิกัด: {$s->latitude},{$s->longitude}";
            }
            return implode("\n", $lines);
        } catch (\Exception $e) {
            return '';
        }
    }

    private function buildIncidentContext(float $lat, float $lng): string
    {
        try {
            $incidents = Incident::active()
                ->where('is_demo', false)
                ->withinRadius($lat, $lng, 20)
                ->latest()
                ->limit(10)
                ->get();

            if ($incidents->isEmpty()) return "\n═══ เหตุการณ์ใกล้ตัว ═══\nไม่มีเหตุการณ์ในรัศมี 20 กม. ✅";

            $labels = Incident::CATEGORY_LABELS;
            $sevLabels = Incident::SEVERITY_LABELS;
            $lines = ["\n═══ เหตุการณ์ใกล้ตัว (รัศมี 20 กม.) ═══"];
            foreach ($incidents as $i) {
                $cat = $labels[$i->category] ?? $i->category;
                $sev = $sevLabels[$i->severity ?? 'medium'] ?? 'ปานกลาง';
                $dist = round($this->haversine($lat, $lng, $i->latitude, $i->longitude), 1);
                $confirm = $i->confirmation_count ?? 0;
                $danger = $i->is_danger_zone ? ' 🚫อันตราย' : '';
                $lines[] = "- [{$sev}] {$cat}: {$i->title} — {$dist} กม. (ยืนยัน {$confirm} คน){$danger}";
                if ($i->road_name) $lines[] = "  ถนน: {$i->road_name}";
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

            $lines = ["\n═══ ข่าวด่วน (น้องหญิงเขียน) ═══"];
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
                $label = $data['label'] ?? $type;
                $avg = $data['avg_price'] ?? '-';
                $lines[] = "- {$label}: เฉลี่ย {$avg} บาท/ลิตร";
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

            $lines = ["\n═══ สภาพอากาศ ณ ตำแหน่งผู้ใช้ ═══"];
            if (!empty($weather['current'])) {
                $w = $weather['current'];
                $lines[] = "{$w['icon']} {$w['description']} อุณหภูมิ {$w['temp']}°C (รู้สึก {$w['feels_like']}°C)";
                $lines[] = "ความชื้น {$w['humidity']}% ลม {$w['wind_speed']} km/h" . ($w['rain'] > 0 ? " ฝน {$w['rain']} mm" : '');
            }
            if (!empty($aqi['aqi'])) {
                $lines[] = "💨 คุณภาพอากาศ AQI: {$aqi['aqi']} ({$aqi['label_th']})" . ($aqi['pm25'] ? " PM2.5: {$aqi['pm25']}" : '');
            }
            return implode("\n", $lines);
        } catch (\Exception $e) {
            return '';
        }
    }

    private function buildHospitalContext(float $lat, float $lng): string
    {
        try {
            $hospitals = HospitalReport::withinRadius($lat, $lng, 15)->latest()->limit(5)->get();
            if ($hospitals->isEmpty()) return '';

            $statusLabels = HospitalReport::ER_STATUS_LABELS;
            $lines = ["\n═══ สถานพยาบาลใกล้ตัว ═══"];
            foreach ($hospitals as $h) {
                $er = $statusLabels[$h->er_status] ?? 'ไม่ทราบ';
                $beds = $h->available_beds !== null ? "เตียงว่าง {$h->available_beds}/{$h->total_beds}" : '';
                $icu = $h->icu_available !== null ? "ICU ว่าง {$h->icu_available}" : '';
                $dist = round($this->haversine($lat, $lng, $h->latitude, $h->longitude), 1);
                $lines[] = "🏥 {$h->hospital_name} — {$dist} กม. | ER: {$er} {$beds} {$icu}";
                if ($h->phone) $lines[] = "   โทร: {$h->phone}";
            }
            return implode("\n", $lines);
        } catch (\Exception $e) {
            return '';
        }
    }

    private function buildEarthquakeContext(): string
    {
        try {
            $quakes = Cache::get('ext_earthquakes', []);
            if (empty($quakes)) return '';

            $recent = array_filter($quakes, fn($q) => ($q['magnitude'] ?? 0) >= 4.0);
            if (empty($recent)) return '';

            $lines = ["\n═══ แผ่นดินไหวล่าสุด (M4.0+) ═══"];
            foreach (array_slice($recent, 0, 3) as $q) {
                $lines[] = "🫨 M{$q['magnitude']} — {$q['title']} ({$q['time']})";
            }
            return implode("\n", $lines);
        } catch (\Exception $e) {
            return '';
        }
    }

    private function buildStatsContext(): string
    {
        try {
            $totalReports = \App\Models\Incident::where('is_demo', false)->count();
            $totalStations = StationReport::where('is_demo', false)->count();
            $activeNow = \App\Models\Incident::active()->where('is_demo', false)->count();

            return "\n═══ สถิติ ThaiHelp ═══\nรายงานทั้งหมด: {$totalReports} | รายงานปั๊ม: {$totalStations} | เหตุการณ์ตอนนี้: {$activeNow}";
        } catch (\Exception $e) {
            return '';
        }
    }

    private function buildUserContext(int $userId): string
    {
        try {
            $user = \App\Models\User::find($userId);
            if (!$user) return '';

            $starLevel = \App\Models\Achievement::STAR_LEVELS[$user->star_level ?? 0] ?? ['name' => 'สมาชิกใหม่', 'icon' => '⭐'];

            return "\n═══ ข้อมูลผู้ใช้ที่กำลังคุย ═══"
                . "\nชื่อ: " . ($user->nickname ?? $user->name) . " | ระดับ: {$starLevel['icon']} {$starLevel['name']}"
                . "\nรายงาน: {$user->total_reports} ครั้ง | ยืนยัน: {$user->total_confirmations} ครั้ง | คะแนน: {$user->reputation_score}";
        } catch (\Exception $e) {
            return '';
        }
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
