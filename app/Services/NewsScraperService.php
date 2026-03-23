<?php

namespace App\Services;

use App\Models\News;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class NewsScraperService
{
    private const KEYWORDS = [
        'ราคาน้ำมัน', 'น้ำมันแพง', 'น้ำมันขึ้นราคา', 'น้ำมันลดราคา',
        'ดีเซล', 'เบนซิน', 'วิกฤตพลังงาน', 'ปั๊มน้ำมัน',
        'พลังงาน', 'OPEC', 'น้ำท่วม', 'ภัยพิบัติ',
        'fuel price thailand', 'oil crisis',
    ];

    /**
     * Run full scrape from all sources.
     */
    public function scrapeAll(): int
    {
        $total = 0;

        // Clean old news first
        News::cleanupOld();

        // Scrape from multiple sources
        $total += $this->scrapeGoogleNews();
        $total += $this->scrapeBingNews();
        $total += $this->scrapeThaiRSS();

        Log::info("News scraper completed: {$total} new articles");

        return $total;
    }

    /**
     * Scrape from Google News RSS.
     */
    private function scrapeGoogleNews(): int
    {
        $count = 0;

        foreach (['ราคาน้ำมัน วันนี้', 'วิกฤตพลังงาน ไทย', 'น้ำมัน ขึ้นราคา'] as $query) {
            try {
                $url = 'https://news.google.com/rss/search?q=' . urlencode($query) . '&hl=th&gl=TH&ceid=TH:th';
                $response = Http::timeout(15)->get($url);

                if (!$response->ok()) continue;

                $xml = simplexml_load_string($response->body());
                if (!$xml || !isset($xml->channel->item)) continue;

                foreach ($xml->channel->item as $item) {
                    $title = strip_tags((string) $item->title);
                    $link = (string) $item->link;
                    $pubDate = (string) $item->pubDate;
                    $source = (string) ($item->source ?? 'Google News');
                    $description = strip_tags((string) ($item->description ?? ''));

                    // Clean Google redirect URLs
                    if (Str::contains($link, 'news.google.com')) {
                        // Keep it as-is, Google News links redirect
                    }

                    $hash = md5($title . $link);

                    if (News::where('hash', $hash)->exists()) continue;

                    News::create([
                        'title'        => Str::limit($title, 497),
                        'summary'      => Str::limit($description, 500),
                        'source_url'   => $link,
                        'source_name'  => Str::limit($source, 97),
                        'category'     => $this->detectCategory($title . ' ' . $description),
                        'hash'         => $hash,
                        'published_at' => $pubDate ? \Carbon\Carbon::parse($pubDate) : now(),
                    ]);

                    $count++;
                    if ($count >= 5) break; // Max 5 per query
                }
            } catch (\Exception $e) {
                Log::warning("Google News scrape failed for '{$query}'", ['error' => $e->getMessage()]);
            }
        }

        return $count;
    }

    /**
     * Scrape from Bing News.
     */
    private function scrapeBingNews(): int
    {
        $count = 0;

        try {
            $url = 'https://www.bing.com/news/search?q=' . urlencode('ราคาน้ำมัน ไทย วันนี้') . '&format=rss';
            $response = Http::timeout(15)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 ThaiHelp News Bot'])
                ->get($url);

            if (!$response->ok()) return 0;

            $xml = @simplexml_load_string($response->body());
            if (!$xml || !isset($xml->channel->item)) return 0;

            foreach ($xml->channel->item as $item) {
                $title = strip_tags((string) $item->title);
                $link = (string) $item->link;
                $description = strip_tags((string) ($item->description ?? ''));

                $hash = md5($title . $link);
                if (News::where('hash', $hash)->exists()) continue;

                News::create([
                    'title'        => Str::limit($title, 497),
                    'summary'      => Str::limit($description, 500),
                    'source_url'   => $link,
                    'source_name'  => 'Bing News',
                    'category'     => $this->detectCategory($title . ' ' . $description),
                    'hash'         => $hash,
                    'published_at' => now(),
                ]);

                $count++;
                if ($count >= 5) break;
            }
        } catch (\Exception $e) {
            Log::warning('Bing News scrape failed', ['error' => $e->getMessage()]);
        }

        return $count;
    }

    /**
     * Scrape from Thai RSS feeds.
     */
    private function scrapeThaiRSS(): int
    {
        $count = 0;

        $feeds = [
            'https://www.thairath.co.th/rss/economy' => 'ไทยรัฐ',
            'https://www.prachachat.net/feed' => 'ประชาชาติ',
            'https://www.bangkokbiznews.com/rss' => 'กรุงเทพธุรกิจ',
        ];

        foreach ($feeds as $feedUrl => $sourceName) {
            try {
                $response = Http::timeout(15)
                    ->withHeaders(['User-Agent' => 'Mozilla/5.0 ThaiHelp News Bot'])
                    ->get($feedUrl);

                if (!$response->ok()) continue;

                $xml = @simplexml_load_string($response->body());
                if (!$xml) continue;

                $items = $xml->channel->item ?? $xml->entry ?? [];

                foreach ($items as $item) {
                    $title = strip_tags((string) ($item->title ?? ''));
                    $link = (string) ($item->link ?? $item->link['href'] ?? '');
                    $description = strip_tags((string) ($item->description ?? $item->summary ?? ''));

                    // Filter: only fuel/energy/crisis related
                    if (!$this->isRelevant($title . ' ' . $description)) continue;

                    $hash = md5($title . $link);
                    if (News::where('hash', $hash)->exists()) continue;

                    // Try to extract image
                    $imageUrl = null;
                    $content = (string) ($item->children('media', true)->thumbnail ?? '');
                    if ($content) {
                        $imageUrl = $content;
                    }

                    News::create([
                        'title'        => Str::limit($title, 497),
                        'summary'      => Str::limit($description, 500),
                        'source_url'   => $link,
                        'source_name'  => $sourceName,
                        'image_url'    => $imageUrl,
                        'category'     => $this->detectCategory($title . ' ' . $description),
                        'hash'         => $hash,
                        'published_at' => isset($item->pubDate) ? \Carbon\Carbon::parse((string) $item->pubDate) : now(),
                    ]);

                    $count++;
                    if ($count >= 3) break; // Max 3 per feed
                }
            } catch (\Exception $e) {
                Log::warning("RSS scrape failed for {$sourceName}", ['error' => $e->getMessage()]);
            }
        }

        return $count;
    }

    /**
     * Check if content is relevant to our keywords.
     */
    private function isRelevant(string $text): bool
    {
        $text = mb_strtolower($text);
        foreach (self::KEYWORDS as $keyword) {
            if (mb_strpos($text, mb_strtolower($keyword)) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Detect news category from content.
     */
    private function detectCategory(string $text): string
    {
        $text = mb_strtolower($text);

        if (Str::contains($text, ['วิกฤต', 'ภัยพิบัติ', 'น้ำท่วม', 'crisis', 'emergency'])) {
            return 'crisis';
        }
        if (Str::contains($text, ['น้ำมัน', 'ดีเซล', 'เบนซิน', 'fuel', 'oil', 'ราคา', 'พลังงาน'])) {
            return 'fuel';
        }
        return 'general';
    }
}
