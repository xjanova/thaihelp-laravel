<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FuelPriceService
{
    /**
     * Get today's official fuel prices from PTT/EPPO.
     * Cached for 6 hours.
     */
    public function getTodayPrices(): array
    {
        return Cache::remember('fuel_prices_today', 60 * 60 * 6, function () {
            return $this->fetchFromEppo() ?: $this->fallbackPrices();
        });
    }

    /**
     * Fetch from EPPO (Energy Policy and Planning Office) API.
     * This is Thailand's official fuel price source.
     */
    private function fetchFromEppo(): ?array
    {
        try {
            $response = Http::timeout(10)
                ->get('https://orapiweb.eppo.go.th/api/oilprice/latest');

            if (!$response->ok()) {
                Log::warning('EPPO API failed', ['status' => $response->status()]);
                return null;
            }

            $data = $response->json();
            return $this->parseEppoData($data);
        } catch (\Exception $e) {
            Log::warning('EPPO API error', ['error' => $e->getMessage()]);

            // Try backup: PTT public price page
            return $this->fetchFromPttScrape();
        }
    }

    /**
     * Parse EPPO API response into our standard format.
     */
    private function parseEppoData(array $data): ?array
    {
        if (empty($data) || !isset($data['data'])) {
            return null;
        }

        $prices = [];
        $mapping = [
            'gasohol95'      => ['ULG95', 'Gasohol 95', 'แก๊สโซฮอล์ 95'],
            'gasohol91'      => ['ULG91', 'Gasohol 91', 'แก๊สโซฮอล์ 91'],
            'e20'            => ['E20', 'Gasohol E20', 'แก๊สโซฮอล์ E20'],
            'e85'            => ['E85', 'Gasohol E85', 'แก๊สโซฮอล์ E85'],
            'diesel'         => ['HSD', 'Diesel', 'ดีเซล'],
            'diesel_b7'      => ['B7', 'Diesel B7', 'ดีเซล B7'],
            'premium_diesel' => ['PHSD', 'Premium Diesel', 'ดีเซลพรีเมียม'],
        ];

        foreach ($data['data'] as $item) {
            $name = $item['name'] ?? $item['product_name'] ?? '';
            $price = $item['price'] ?? $item['retail_price'] ?? null;

            if (!$price) continue;

            foreach ($mapping as $key => $aliases) {
                foreach ($aliases as $alias) {
                    if (stripos($name, $alias) !== false) {
                        $prices[$key] = [
                            'price' => round((float) $price, 2),
                            'name' => $name,
                            'updated_at' => $item['date'] ?? now()->toDateString(),
                        ];
                        break 2;
                    }
                }
            }
        }

        return !empty($prices) ? $prices : null;
    }

    /**
     * Fallback: try to scrape PTT retail prices.
     */
    private function fetchFromPttScrape(): ?array
    {
        try {
            $response = Http::timeout(10)
                ->get('https://www.pttplc.com/th/Media-Center/Oil-Price.aspx');

            if (!$response->ok()) return null;

            // Extract prices from HTML (simplified pattern matching)
            $html = $response->body();
            $prices = [];

            $patterns = [
                'gasohol95' => '/แก๊สโซฮอล์\s*95.*?(\d+\.\d+)/s',
                'gasohol91' => '/แก๊สโซฮอล์\s*91.*?(\d+\.\d+)/s',
                'e20'       => '/E20.*?(\d+\.\d+)/s',
                'diesel'    => '/ดีเซล(?!.*พรีเมียม).*?(\d+\.\d+)/s',
            ];

            foreach ($patterns as $key => $pattern) {
                if (preg_match($pattern, $html, $m)) {
                    $prices[$key] = [
                        'price' => round((float) $m[1], 2),
                        'name' => $key,
                        'updated_at' => now()->toDateString(),
                    ];
                }
            }

            return !empty($prices) ? $prices : null;
        } catch (\Exception $e) {
            Log::warning('PTT scrape failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Fallback prices when all APIs fail.
     * Based on typical Thai fuel prices.
     */
    private function fallbackPrices(): array
    {
        return [
            'gasohol95'      => ['price' => 36.04, 'name' => 'แก๊สโซฮอล์ 95', 'updated_at' => now()->toDateString(), 'is_fallback' => true],
            'gasohol91'      => ['price' => 33.54, 'name' => 'แก๊สโซฮอล์ 91', 'updated_at' => now()->toDateString(), 'is_fallback' => true],
            'e20'            => ['price' => 32.04, 'name' => 'แก๊สโซฮอล์ E20', 'updated_at' => now()->toDateString(), 'is_fallback' => true],
            'e85'            => ['price' => 25.04, 'name' => 'แก๊สโซฮอล์ E85', 'updated_at' => now()->toDateString(), 'is_fallback' => true],
            'diesel'         => ['price' => 29.94, 'name' => 'ดีเซล', 'updated_at' => now()->toDateString(), 'is_fallback' => true],
            'diesel_b7'      => ['price' => 29.94, 'name' => 'ดีเซล B7', 'updated_at' => now()->toDateString(), 'is_fallback' => true],
            'premium_diesel' => ['price' => 34.94, 'name' => 'ดีเซลพรีเมียม', 'updated_at' => now()->toDateString(), 'is_fallback' => true],
            'ngv'            => ['price' => 18.59, 'name' => 'NGV', 'updated_at' => now()->toDateString(), 'is_fallback' => true],
            'lpg'            => ['price' => 23.47, 'name' => 'LPG', 'updated_at' => now()->toDateString(), 'is_fallback' => true],
        ];
    }

    /**
     * Force refresh the cache.
     */
    public function refresh(): array
    {
        Cache::forget('fuel_prices_today');
        return $this->getTodayPrices();
    }
}
