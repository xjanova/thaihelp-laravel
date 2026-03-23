<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * ดึงข้อมูลจากแหล่งภายนอกฟรี (ไม่ต้อง API key ยกเว้น AQI)
 */
class ExternalDataService
{
    /**
     * ดึงข้อมูลทั้งหมดที่มี — cache ตาม TTL แต่ละประเภท
     */
    public function getAll(float $lat = 13.7563, float $lng = 100.5018): array
    {
        return [
            'earthquakes' => $this->getEarthquakes(),
            'weather' => $this->getWeather($lat, $lng),
            'air_quality' => $this->getAirQuality($lat, $lng),
            'flood_warnings' => $this->getFloodWarnings(),
            'traffic' => $this->getTrafficAlerts($lat, $lng),
        ];
    }

    /**
     * แผ่นดินไหว — USGS (ฟรี ไม่ต้อง API key)
     * แผ่นดินไหว M2.5+ ในรัศมี 2000km จากไทย, 7 วันย้อนหลัง
     */
    public function getEarthquakes(): array
    {
        return Cache::remember('ext_earthquakes', 900, function () { // 15 min
            try {
                $response = Http::timeout(10)->get('https://earthquake.usgs.gov/fdsnws/event/1/query', [
                    'format' => 'geojson',
                    'latitude' => 15.0,
                    'longitude' => 101.0,
                    'maxradiuskm' => 2000,
                    'minmagnitude' => 2.5,
                    'orderby' => 'time',
                    'limit' => 20,
                ]);

                if (!$response->successful()) return [];

                $data = $response->json();
                $results = [];

                foreach ($data['features'] ?? [] as $eq) {
                    $props = $eq['properties'] ?? [];
                    $coords = $eq['geometry']['coordinates'] ?? [];

                    $results[] = [
                        'type' => 'earthquake',
                        'title' => $props['title'] ?? 'แผ่นดินไหว',
                        'magnitude' => $props['mag'] ?? 0,
                        'latitude' => $coords[1] ?? 0,
                        'longitude' => $coords[0] ?? 0,
                        'depth_km' => $coords[2] ?? 0,
                        'time' => isset($props['time']) ? date('Y-m-d H:i:s', $props['time'] / 1000) : null,
                        'url' => $props['url'] ?? null,
                        'tsunami' => $props['tsunami'] ?? 0,
                    ];
                }

                return $results;
            } catch (\Exception $e) {
                Log::warning('USGS earthquake fetch failed', ['error' => $e->getMessage()]);
                return [];
            }
        });
    }

    /**
     * สภาพอากาศ — Open-Meteo (ฟรี ไม่ต้อง API key)
     */
    public function getWeather(float $lat, float $lng): array
    {
        $cacheKey = "ext_weather_{$lat}_{$lng}";
        return Cache::remember($cacheKey, 1800, function () use ($lat, $lng) { // 30 min
            try {
                $response = Http::timeout(10)->get('https://api.open-meteo.com/v1/forecast', [
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'current' => 'temperature_2m,relative_humidity_2m,apparent_temperature,precipitation,rain,weather_code,wind_speed_10m,wind_direction_10m',
                    'daily' => 'weather_code,temperature_2m_max,temperature_2m_min,precipitation_sum,precipitation_probability_max',
                    'timezone' => 'Asia/Bangkok',
                    'forecast_days' => 3,
                ]);

                if (!$response->successful()) return [];

                $data = $response->json();
                $current = $data['current'] ?? [];

                return [
                    'current' => [
                        'temp' => $current['temperature_2m'] ?? null,
                        'feels_like' => $current['apparent_temperature'] ?? null,
                        'humidity' => $current['relative_humidity_2m'] ?? null,
                        'rain' => $current['rain'] ?? 0,
                        'wind_speed' => $current['wind_speed_10m'] ?? null,
                        'wind_dir' => $current['wind_direction_10m'] ?? null,
                        'weather_code' => $current['weather_code'] ?? 0,
                        'description' => $this->weatherCodeToThai($current['weather_code'] ?? 0),
                        'icon' => $this->weatherCodeToIcon($current['weather_code'] ?? 0),
                    ],
                    'forecast' => array_map(function ($i) use ($data) {
                        $daily = $data['daily'] ?? [];
                        return [
                            'date' => $daily['time'][$i] ?? null,
                            'temp_max' => $daily['temperature_2m_max'][$i] ?? null,
                            'temp_min' => $daily['temperature_2m_min'][$i] ?? null,
                            'rain_sum' => $daily['precipitation_sum'][$i] ?? 0,
                            'rain_chance' => $daily['precipitation_probability_max'][$i] ?? 0,
                            'weather_code' => $daily['weather_code'][$i] ?? 0,
                            'icon' => $this->weatherCodeToIcon($daily['weather_code'][$i] ?? 0),
                        ];
                    }, range(0, min(2, count($data['daily']['time'] ?? []) - 1))),
                ];
            } catch (\Exception $e) {
                Log::warning('Weather fetch failed', ['error' => $e->getMessage()]);
                return [];
            }
        });
    }

    /**
     * คุณภาพอากาศ — WAQI (ฟรี token: demo)
     */
    public function getAirQuality(float $lat, float $lng): array
    {
        $cacheKey = "ext_aqi_{$lat}_{$lng}";
        return Cache::remember($cacheKey, 1800, function () use ($lat, $lng) { // 30 min
            try {
                // Use WAQI demo token (limited but free)
                $token = config('services.waqi.token', 'demo');
                $response = Http::timeout(10)->get("https://api.waqi.info/feed/geo:{$lat};{$lng}/", [
                    'token' => $token,
                ]);

                if (!$response->successful()) return [];

                $data = $response->json();
                if (($data['status'] ?? '') !== 'ok') return [];

                $d = $data['data'] ?? [];
                $aqi = $d['aqi'] ?? 0;

                return [
                    'aqi' => $aqi,
                    'level' => $this->aqiLevel($aqi),
                    'color' => $this->aqiColor($aqi),
                    'label_th' => $this->aqiLabelThai($aqi),
                    'station' => $d['city']['name'] ?? 'ไม่ทราบสถานี',
                    'pm25' => $d['iaqi']['pm25']['v'] ?? null,
                    'pm10' => $d['iaqi']['pm10']['v'] ?? null,
                    'o3' => $d['iaqi']['o3']['v'] ?? null,
                    'time' => $d['time']['s'] ?? null,
                ];
            } catch (\Exception $e) {
                Log::warning('AQI fetch failed', ['error' => $e->getMessage()]);
                return [];
            }
        });
    }

    /**
     * เตือนน้ำท่วม — Thai Open Data
     */
    public function getFloodWarnings(): array
    {
        return Cache::remember('ext_floods', 3600, function () { // 1 hour
            try {
                // Try Thai gov open data for flood warnings
                $response = Http::timeout(10)->get('https://data.tmd.go.th/api/WeatherWarning/v2/', [
                    'type' => 'json',
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $warnings = [];
                    foreach (($data['Warnings'] ?? []) as $w) {
                        $warnings[] = [
                            'type' => 'flood_warning',
                            'title' => $w['Title'] ?? 'เตือนภัย',
                            'description' => $w['Description'] ?? '',
                            'area' => $w['Area'] ?? '',
                            'severity' => $w['Severity'] ?? 'medium',
                            'issued_at' => $w['IssuedDate'] ?? null,
                        ];
                    }
                    return $warnings;
                }

                return [];
            } catch (\Exception $e) {
                // TMD API may not be available — return empty
                return [];
            }
        });
    }

    /**
     * Traffic alerts (placeholder — ใช้ข้อมูลจาก incidents ของเราเอง)
     */
    public function getTrafficAlerts(float $lat, float $lng): array
    {
        return Cache::remember("ext_traffic_{$lat}_{$lng}", 600, function () use ($lat, $lng) {
            // ดึงจาก incidents ของเราที่เป็น roadblock, accident, checkpoint
            try {
                $incidents = \App\Models\Incident::active()
                    ->whereIn('category', ['accident', 'roadblock', 'checkpoint', 'construction'])
                    ->withinRadius($lat, $lng, 30)
                    ->latest()
                    ->limit(20)
                    ->get(['id', 'category', 'title', 'latitude', 'longitude', 'severity', 'confirmation_count']);

                return $incidents->map(fn($i) => [
                    'type' => 'traffic',
                    'category' => $i->category,
                    'title' => $i->title,
                    'latitude' => $i->latitude,
                    'longitude' => $i->longitude,
                    'severity' => $i->severity,
                    'confirmations' => $i->confirmation_count,
                ])->toArray();
            } catch (\Exception $e) {
                return [];
            }
        });
    }

    // ─── Helper Methods ───

    private function weatherCodeToThai(int $code): string
    {
        return match (true) {
            $code === 0 => 'ฟ้าใส',
            $code <= 3 => 'มีเมฆบาง',
            $code <= 49 => 'มีหมอก',
            $code <= 59 => 'ฝนปรอยๆ',
            $code <= 69 => 'ฝนตก',
            $code <= 79 => 'หิมะ',
            $code <= 84 => 'ฝนตกหนัก',
            $code <= 94 => 'ฝนฟ้าคะนอง',
            $code <= 99 => 'พายุฝนฟ้าคะนอง',
            default => 'ไม่ทราบ',
        };
    }

    private function weatherCodeToIcon(int $code): string
    {
        return match (true) {
            $code === 0 => '☀️',
            $code <= 3 => '⛅',
            $code <= 49 => '🌫️',
            $code <= 59 => '🌦️',
            $code <= 69 => '🌧️',
            $code <= 79 => '❄️',
            $code <= 84 => '🌧️',
            $code <= 94 => '⛈️',
            $code <= 99 => '🌩️',
            default => '🌤️',
        };
    }

    private function aqiLevel(int $aqi): string
    {
        return match (true) {
            $aqi <= 50 => 'good',
            $aqi <= 100 => 'moderate',
            $aqi <= 150 => 'unhealthy_sensitive',
            $aqi <= 200 => 'unhealthy',
            $aqi <= 300 => 'very_unhealthy',
            default => 'hazardous',
        };
    }

    private function aqiColor(int $aqi): string
    {
        return match (true) {
            $aqi <= 50 => '#22c55e',
            $aqi <= 100 => '#eab308',
            $aqi <= 150 => '#f97316',
            $aqi <= 200 => '#ef4444',
            $aqi <= 300 => '#a855f7',
            default => '#991b1b',
        };
    }

    private function aqiLabelThai(int $aqi): string
    {
        return match (true) {
            $aqi <= 50 => 'ดี',
            $aqi <= 100 => 'ปานกลาง',
            $aqi <= 150 => 'ไม่ดีต่อกลุ่มเสี่ยง',
            $aqi <= 200 => 'ไม่ดีต่อสุขภาพ',
            $aqi <= 300 => 'อันตราย',
            default => 'อันตรายมาก',
        };
    }
}
