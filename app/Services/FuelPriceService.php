<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FuelPriceService
{
    /** Standard fuel type mapping for all parsers */
    private const FUEL_MAP = [
        'gasohol95'      => ['ULG95', 'Gasohol 95', 'แก๊สโซฮอล์ 95', 'G95', 'เบนซิน 95', 'Super Power Gasohol 95'],
        'gasohol91'      => ['ULG91', 'Gasohol 91', 'แก๊สโซฮอล์ 91', 'G91', 'เบนซิน 91', 'Power Gasohol 91'],
        'e20'            => ['E20', 'Gasohol E20', 'แก๊สโซฮอล์ E20'],
        'e85'            => ['E85', 'Gasohol E85', 'แก๊สโซฮอล์ E85'],
        'diesel'         => ['HSD', 'Diesel', 'ดีเซล', 'Hi Diesel', 'ไฮดีเซล', 'Diesel B10'],
        'diesel_b7'      => ['B7', 'Diesel B7', 'ดีเซล B7'],
        'premium_diesel' => ['PHSD', 'Premium Diesel', 'ดีเซลพรีเมียม', 'V-Power Diesel', 'Hi Premium Diesel', 'ไฮพรีเมียมดีเซล'],
        'ngv'            => ['NGV', 'CNG'],
        'lpg'            => ['LPG', 'แอลพีจี'],
    ];

    /**
     * Get today's official fuel prices.
     * Tries multiple sources in order, cached for 6 hours.
     */
    public function getTodayPrices(): array
    {
        return Cache::remember('fuel_prices_today', 60 * 60 * 6, function () {
            $sources = [
                'Bangchak'      => fn () => $this->fetchFromBangchak(),
                'Motorist'      => fn () => $this->fetchFromMotorist(),
                'EPPO'          => fn () => $this->fetchFromEppo(),
                'PTT OR'        => fn () => $this->fetchFromPttOr(),
                'Shell'         => fn () => $this->fetchFromShell(),
                'PTT Scrape'    => fn () => $this->fetchFromPttScrape(),
                'GovChannel'    => fn () => $this->fetchFromGovChannel(),
            ];

            foreach ($sources as $name => $fetcher) {
                try {
                    $result = $fetcher();
                    if ($result && count($result) >= 3) {
                        Log::info("Fuel prices fetched from {$name}", ['count' => count($result)]);
                        // Tag source
                        foreach ($result as &$item) {
                            $item['source'] = $item['source'] ?? $name;
                        }
                        return $result;
                    }
                    if ($result) {
                        Log::info("Fuel prices from {$name} incomplete", ['count' => count($result)]);
                    }
                } catch (\Exception $e) {
                    Log::warning("Fuel source {$name} failed", ['error' => $e->getMessage()]);
                }
            }

            Log::error('All fuel price sources failed, using fallback');
            return $this->fallbackPrices();
        });
    }

    /**
     * 1. EPPO — สำนักงานนโยบายและแผนพลังงาน (official government)
     */
    private function fetchFromEppo(): ?array
    {
        $response = Http::timeout(10)
            ->get('https://orapiweb.eppo.go.th/api/oilprice/latest');

        if (!$response->ok()) return null;

        $data = $response->json();
        if (empty($data['data'])) return null;

        $prices = [];
        foreach ($data['data'] as $item) {
            $name = $item['name'] ?? $item['product_name'] ?? '';
            $price = $item['price'] ?? $item['retail_price'] ?? null;
            if (!$price || (float) $price <= 0) continue;

            $key = $this->matchFuelType($name);
            if ($key) {
                $prices[$key] = [
                    'price' => round((float) $price, 2),
                    'name' => $name,
                    'updated_at' => $item['date'] ?? now()->toDateString(),
                    'source' => 'สนพ.',
                ];
            }
        }

        return !empty($prices) ? $prices : null;
    }

    /**
     * 1. Bangchak — API ราคาน้ำมันบางจาก (primary, confirmed working)
     * Endpoint: https://www.bangchak.co.th/api/oilprice
     * Returns: {"code":200,"data":{"items":[{"OilName":"...","OilNameEng":"...","PriceToday":56.84,...}]}}
     */
    private function fetchFromBangchak(): ?array
    {
        $response = Http::timeout(10)
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 ThaiHelp/1.0',
                'Accept' => 'application/json',
            ])
            ->get('https://www.bangchak.co.th/api/oilprice');

        if (!$response->ok()) return null;

        $data = $response->json();
        if (empty($data['data']['items'])) return null;

        // Direct mapping from Bangchak OilNameEng to our fuel types
        // Bangchak returns premium variants (e.g. "Hi Premium 97 Gasohol 95") — skip those
        // in favor of the standard variants (e.g. "Gasohol 95 S EVO")
        $bangchakMap = [
            'Hi Premium Diesel'  => 'premium_diesel',
            'Hi Diesel'          => 'diesel',
            'Gasohol 95 S EVO'   => 'gasohol95',
            'Gasohol 91 S EVO'   => 'gasohol91',
            'Gasohol E20 S EVO'  => 'e20',
            'Gasohol E85 S EVO'  => 'e85',
        ];

        $prices = [];
        foreach ($data['data']['items'] as $item) {
            $nameEng = $item['OilNameEng'] ?? '';
            $nameTh = $item['OilName'] ?? '';
            $price = $item['PriceToday'] ?? null;

            if (!$price || (float) $price <= 0) continue;

            // Try direct mapping from English name first
            $key = null;
            foreach ($bangchakMap as $pattern => $fuelKey) {
                if (stripos($nameEng, $pattern) !== false) {
                    $key = $fuelKey;
                    break;
                }
            }

            // Fallback: try generic matchFuelType on both names
            if (!$key) {
                $key = $this->matchFuelType($nameEng) ?? $this->matchFuelType($nameTh);
            }

            // Only set if not already set (first match wins — standard variants listed first)
            if ($key && !isset($prices[$key])) {
                $prices[$key] = [
                    'price' => round((float) $price, 2),
                    'name' => $nameTh ?: $nameEng,
                    'updated_at' => now()->toDateString(),
                    'source' => 'บางจาก',
                ];
            }
        }

        return !empty($prices) ? $prices : null;
    }

    /**
     * 3. PTT OR (PTTOR) — API ราคาน้ำมันค้าปลีก ปตท.
     */
    private function fetchFromPttOr(): ?array
    {
        // PTT OR public retail price API
        $response = Http::timeout(10)
            ->withHeaders(['Accept' => 'application/json'])
            ->get('https://www.pttor.com/api/oilprice');

        if (!$response->ok()) return null;

        $data = $response->json();
        $items = $data['data'] ?? $data['products'] ?? $data;
        if (empty($items) || !is_array($items)) return null;

        $prices = [];
        foreach ($items as $item) {
            $name = $item['name'] ?? $item['product_name'] ?? $item['productName'] ?? '';
            $price = $item['price'] ?? $item['retailPrice'] ?? $item['sell_price'] ?? null;
            if (!$price || !$name || (float) $price <= 0) continue;

            $key = $this->matchFuelType($name);
            if ($key) {
                $prices[$key] = [
                    'price' => round((float) $price, 2),
                    'name' => $name,
                    'updated_at' => $item['date'] ?? $item['effective_date'] ?? now()->toDateString(),
                    'source' => 'PTT',
                ];
            }
        }

        return !empty($prices) ? $prices : null;
    }

    /**
     * 4. Shell Thailand — ราคาน้ำมัน Shell
     */
    private function fetchFromShell(): ?array
    {
        $response = Http::timeout(10)
            ->withHeaders([
                'Accept' => 'application/json',
                'User-Agent' => 'ThaiHelp/1.0',
            ])
            ->get('https://www.shell.co.th/th_th/motorists/shell-fuels/fuel-pricing/_jcr_content.price.json');

        if (!$response->ok()) return null;

        $data = $response->json();
        if (empty($data)) return null;

        // Shell returns various formats - try to parse
        $prices = [];
        $items = $data['prices'] ?? $data['products'] ?? $data;
        if (!is_array($items)) return null;

        foreach ($items as $item) {
            $name = $item['name'] ?? $item['product'] ?? $item['productName'] ?? '';
            $price = $item['price'] ?? $item['pump_price'] ?? $item['amount'] ?? null;

            // Shell sometimes nests: {name: "Shell V-Power", price: {amount: 48.34}}
            if (is_array($price)) {
                $price = $price['amount'] ?? $price['value'] ?? null;
            }
            if (!$price || !$name || (float) $price <= 0) continue;

            $key = $this->matchFuelType($name);
            if ($key) {
                $prices[$key] = [
                    'price' => round((float) $price, 2),
                    'name' => $name,
                    'updated_at' => $item['effectiveDate'] ?? now()->toDateString(),
                    'source' => 'Shell',
                ];
            }
        }

        return !empty($prices) ? $prices : null;
    }

    /**
     * 5. PTT Scrape — ดึงจากหน้าเว็บ PTT โดยตรง
     */
    private function fetchFromPttScrape(): ?array
    {
        $response = Http::timeout(15)
            ->withHeaders(['User-Agent' => 'Mozilla/5.0 ThaiHelp/1.0'])
            ->get('https://www.pttplc.com/th/Media-Center/Oil-Price.aspx');

        if (!$response->ok()) return null;

        $html = $response->body();
        if (empty($html)) return null;

        $prices = [];
        $patterns = [
            'gasohol95'      => '/แก๊สโซฮอล์\s*95[^0-9]*?(\d{2,3}\.\d{2})/s',
            'gasohol91'      => '/แก๊สโซฮอล์\s*91[^0-9]*?(\d{2,3}\.\d{2})/s',
            'e20'            => '/E20[^0-9]*?(\d{2,3}\.\d{2})/s',
            'e85'            => '/E85[^0-9]*?(\d{2,3}\.\d{2})/s',
            'diesel'         => '/ดีเซล(?!.*(?:พรีเมียม|B7))[^0-9]*?(\d{2,3}\.\d{2})/s',
            'diesel_b7'      => '/ดีเซล\s*B7[^0-9]*?(\d{2,3}\.\d{2})/s',
            'premium_diesel' => '/ดีเซลพรีเมียม[^0-9]*?(\d{2,3}\.\d{2})/s',
        ];

        foreach ($patterns as $key => $pattern) {
            if (preg_match($pattern, $html, $m)) {
                $p = (float) $m[1];
                if ($p > 10 && $p < 100) { // sanity check
                    $prices[$key] = [
                        'price' => round($p, 2),
                        'name' => $key,
                        'updated_at' => now()->toDateString(),
                        'source' => 'PTT',
                    ];
                }
            }
        }

        return !empty($prices) ? $prices : null;
    }

    /**
     * 6. GovChannel / data.go.th — ข้อมูลเปิดภาครัฐ ราคาน้ำมัน
     */
    private function fetchFromGovChannel(): ?array
    {
        // data.go.th open data - fuel price dataset
        $response = Http::timeout(10)
            ->get('https://data.go.th/api/3/action/datastore_search', [
                'resource_id' => 'f5c43c70-40e2-49e6-8e7b-3db8b0956798',
                'limit' => 20,
                'sort' => 'date desc',
            ]);

        if (!$response->ok()) return null;

        $data = $response->json();
        $records = $data['result']['records'] ?? [];
        if (empty($records)) return null;

        $prices = [];
        foreach ($records as $record) {
            $name = $record['product_name'] ?? $record['fuel_type'] ?? '';
            $price = $record['price'] ?? $record['retail_price'] ?? null;
            if (!$price || !$name || (float) $price <= 0) continue;

            $key = $this->matchFuelType($name);
            if ($key && !isset($prices[$key])) {
                $prices[$key] = [
                    'price' => round((float) $price, 2),
                    'name' => $name,
                    'updated_at' => $record['date'] ?? now()->toDateString(),
                    'source' => 'data.go.th',
                ];
            }
        }

        return !empty($prices) ? $prices : null;
    }

    /**
     * Motorist.co.th — aggregator with reliable HTML table of all brand prices.
     * Scrapes the PTT column (standard reference prices).
     */
    private function fetchFromMotorist(): ?array
    {
        $response = Http::timeout(15)
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'text/html',
            ])
            ->get('https://www.motorist.co.th/en/petrol-prices');

        if (!$response->ok()) return null;

        $html = $response->body();
        if (empty($html) || strlen($html) < 1000) return null;

        // Parse the fuel_comparison table — first data column is typically PTT prices
        // Pattern: <td>Fuel Name</td><td>฿XX.XX</td>...
        $prices = [];

        // Match rows: fuel name followed by first price (PTT column)
        $fuelPatterns = [
            'gasohol95'      => '/Gasohol\s*95(?!\s*Premium)[^฿]*?฿\s*(\d{2,3}\.\d{2})/si',
            'gasohol91'      => '/Gasohol\s*91[^฿]*?฿\s*(\d{2,3}\.\d{2})/si',
            'e20'            => '/Gasohol\s*E20[^฿]*?฿\s*(\d{2,3}\.\d{2})/si',
            'e85'            => '/Gasohol\s*E85[^฿]*?฿\s*(\d{2,3}\.\d{2})/si',
            'diesel'         => '/Diesel\s*B7(?!\s*Premium)[^฿]*?฿\s*(\d{2,3}\.\d{2})/si',
            'premium_diesel' => '/Diesel\s*B7\s*Premium[^฿]*?฿\s*(\d{2,3}\.\d{2})/si',
        ];

        foreach ($fuelPatterns as $key => $pattern) {
            if (preg_match($pattern, $html, $m)) {
                $p = (float) $m[1];
                if ($p > 10 && $p < 100) {
                    $prices[$key] = [
                        'price' => round($p, 2),
                        'name' => $this->fuelThaiName($key),
                        'updated_at' => now()->toDateString(),
                        'source' => 'Motorist',
                    ];
                }
            }
        }

        return !empty($prices) ? $prices : null;
    }

    /** Thai display names for fuel types */
    private function fuelThaiName(string $key): string
    {
        return match ($key) {
            'gasohol95'      => 'แก๊สโซฮอล์ 95',
            'gasohol91'      => 'แก๊สโซฮอล์ 91',
            'e20'            => 'แก๊สโซฮอล์ E20',
            'e85'            => 'แก๊สโซฮอล์ E85',
            'diesel'         => 'ดีเซล B7',
            'diesel_b7'      => 'ดีเซล B7',
            'premium_diesel' => 'ดีเซลพรีเมียม',
            'ngv'            => 'NGV',
            'lpg'            => 'LPG',
            default          => $key,
        };
    }

    /**
     * Match a fuel product name to our standard fuel type key.
     */
    private function matchFuelType(string $name): ?string
    {
        if (empty($name)) return null;

        // Match more specific types first to avoid false matches
        // e.g. "ดีเซล B7" should match diesel_b7, not diesel
        $orderedKeys = [
            'premium_diesel', 'diesel_b7', 'diesel',
            'gasohol95', 'gasohol91',
            'e85', 'e20',
            'ngv', 'lpg',
        ];

        foreach ($orderedKeys as $key) {
            foreach (self::FUEL_MAP[$key] as $alias) {
                if (stripos($name, $alias) !== false) {
                    return $key;
                }
            }
        }

        return null;
    }

    /**
     * Fallback prices when ALL sources fail.
     * Clearly marked as fallback so UI can warn users.
     */
    private function fallbackPrices(): array
    {
        $date = now()->toDateString();
        $fb = fn ($price, $name) => [
            'price' => $price,
            'name' => $name,
            'updated_at' => $date,
            'source' => 'fallback',
            'is_fallback' => true,
        ];

        // Updated 2026-03-26 — real prices after subsidy reduction (+6 THB)
        return [
            'gasohol95'      => $fb(41.05, 'แก๊สโซฮอล์ 95'),
            'gasohol91'      => $fb(40.68, 'แก๊สโซฮอล์ 91'),
            'e20'            => $fb(36.05, 'แก๊สโซฮอล์ E20'),
            'e85'            => $fb(32.79, 'แก๊สโซฮอล์ E85'),
            'diesel'         => $fb(38.94, 'ดีเซล B7'),
            'diesel_b7'      => $fb(38.94, 'ดีเซล B7'),
            'premium_diesel' => $fb(54.64, 'ดีเซลพรีเมียม'),
            'ngv'            => $fb(18.59, 'NGV'),
            'lpg'            => $fb(23.47, 'LPG'),
        ];
    }

    /**
     * Get price history for chart (last N days).
     */
    public function getPriceHistory(string $fuelType = 'diesel', int $days = 30): array
    {
        $cacheKey = "fuel_history_{$fuelType}_{$days}";

        return Cache::remember($cacheKey, 3600, function () use ($fuelType, $days) {
            // Try DB table first
            if (\Illuminate\Support\Facades\Schema::hasTable('fuel_prices')) {
                $prices = \App\Models\FuelPrice::where('fuel_type', $fuelType)
                    ->where('date', '>=', now()->subDays($days)->toDateString())
                    ->orderBy('date')
                    ->get();

                if ($prices->isNotEmpty()) {
                    return $prices->groupBy('date')->map(fn ($group, $date) => [
                        'date' => $date,
                        'avg_price' => round($group->avg('price'), 2),
                        'brands' => $group->pluck('price', 'brand')->toArray(),
                    ])->values()->toArray();
                }
            }

            // Try EPPO historical API
            $history = $this->fetchHistoryFromEppo($fuelType, $days);
            if ($history && count($history) > 5) {
                return $history;
            }

            // Last resort: generate from today's price (clearly marked)
            $todayPrices = $this->getTodayPrices();
            $basePrice = $todayPrices[$fuelType]['price'] ?? 30.0;
            $history = [];

            for ($i = $days - 1; $i >= 0; $i--) {
                $date = now()->subDays($i)->format('Y-m-d');
                $variation = $basePrice * (mt_rand(-200, 200) / 10000);
                $history[] = [
                    'date' => $date,
                    'avg_price' => round($basePrice + $variation, 2),
                    'is_estimated' => true,
                ];
            }

            return $history;
        });
    }

    /**
     * Try to get historical prices from EPPO.
     */
    private function fetchHistoryFromEppo(string $fuelType, int $days): ?array
    {
        try {
            $dateFrom = now()->subDays($days)->format('Y-m-d');
            $dateTo = now()->format('Y-m-d');

            $response = Http::timeout(15)
                ->get("https://orapiweb.eppo.go.th/api/oilprice/history", [
                    'from' => $dateFrom,
                    'to' => $dateTo,
                ]);

            if (!$response->ok()) return null;

            $data = $response->json();
            $records = $data['data'] ?? [];
            if (empty($records)) return null;

            $history = [];
            foreach ($records as $record) {
                $name = $record['name'] ?? $record['product_name'] ?? '';
                $key = $this->matchFuelType($name);
                if ($key !== $fuelType) continue;

                $price = $record['price'] ?? $record['retail_price'] ?? null;
                $date = $record['date'] ?? null;
                if (!$price || !$date) continue;

                $history[] = [
                    'date' => $date,
                    'avg_price' => round((float) $price, 2),
                ];
            }

            // Sort by date
            usort($history, fn ($a, $b) => strcmp($a['date'], $b['date']));

            return !empty($history) ? $history : null;
        } catch (\Exception $e) {
            Log::warning('EPPO history failed', ['error' => $e->getMessage()]);
            return null;
        }
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
