<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Abuse Protection Middleware — blocks bots, spam, and DDoS patterns.
 *
 * Layer 1: Known bot User-Agent blocking
 * Layer 2: Rapid-fire detection (too many requests too fast)
 * Layer 3: Suspicious pattern detection (no cookies, no referer, etc.)
 * Layer 4: Global rate limit (circuit breaker for extreme load)
 */
class AbuseProtection
{
    /** Blocked User-Agent patterns (case-insensitive) */
    private const BLOCKED_UA = [
        'python-requests', 'python-urllib', 'python-httpx',
        'curl/', 'wget/', 'httpie/',
        'scrapy', 'beautifulsoup',
        'go-http-client', 'java/', 'apache-httpclient',
        'postmanruntime', 'insomnia/',
        'sqlmap', 'nikto', 'nmap', 'masscan',
        'semrushbot', 'ahrefsbot', 'mj12bot', 'dotbot',
        'bytespider', 'petalbot', 'yandexbot',
        'headlesschrome', 'phantomjs', 'selenium',
    ];

    /** Allowed bots (search engines, monitoring) */
    private const ALLOWED_UA = [
        'googlebot', 'bingbot', 'uptimerobot', 'pingdom',
        'cloudflare-', 'facebookexternalhit', 'twitterbot',
    ];

    /** Rapid-fire threshold: max requests in 10 seconds */
    private const BURST_LIMIT = 20;
    private const BURST_WINDOW = 10; // seconds

    /** Global circuit breaker: max API requests per minute system-wide */
    private const GLOBAL_RPM_LIMIT = 5000;

    public function handle(Request $request, Closure $next): Response
    {
        // Wrap everything in try-catch — middleware must NEVER crash the app
        try {
            return $this->doHandle($request, $next);
        } catch (\Throwable $e) {
            // If abuse protection fails, let the request through (fail-open)
            Log::error('AbuseProtection error: ' . $e->getMessage());
            return $next($request);
        }
    }

    private function doHandle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();
        $ua = strtolower($request->userAgent() ?? '');

        // Layer 1: Block known malicious bots (allow search engines)
        if ($this->isBlockedBot($ua)) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        // Layer 2: Rapid-fire detection (per IP)
        $burstKey = "abuse_burst_{$ip}";
        $burstCount = (int) Cache::get($burstKey, 0);
        if ($burstCount >= self::BURST_LIMIT) {
            return response()->json([
                'error' => 'Too many requests. Please slow down.',
                'retry_after' => self::BURST_WINDOW,
            ], 429)->header('Retry-After', self::BURST_WINDOW);
        }
        Cache::put($burstKey, $burstCount + 1, self::BURST_WINDOW);

        // Layer 3: Suspicious request patterns (API endpoints only)
        if ($request->is('api/*') && !$request->is('api/heartbeat')) {
            $suspicionScore = $this->calculateSuspicionScore($request, $ua);
            if ($suspicionScore >= 5) {
                // Soft block: add delay instead of hard reject
                Log::info('AbuseProtection: suspicious request', [
                    'ip' => $ip,
                    'score' => $suspicionScore,
                    'ua' => substr($ua, 0, 80),
                ]);
                // Return 429 for very suspicious
                if ($suspicionScore >= 8) {
                    return response()->json(['error' => 'Suspicious activity detected'], 429);
                }
            }
        }

        // Layer 4: Global circuit breaker
        $globalKey = 'abuse_global_rpm';
        $globalCount = (int) Cache::get($globalKey, 0);
        if ($globalCount >= self::GLOBAL_RPM_LIMIT) {
            return response()->json([
                'error' => 'ระบบมีผู้ใช้เยอะมากค่ะ กรุณาลองใหม่อีกสักครู่นะคะ 🙏',
                'retry_after' => 30,
            ], 503)->header('Retry-After', 30);
        }
        Cache::put($globalKey, $globalCount + 1, 60);

        return $next($request);
    }

    private function isBlockedBot(string $ua): bool
    {
        if (empty($ua)) return false; // Don't block empty UA (some mobile browsers)

        // Check allowed first
        foreach (self::ALLOWED_UA as $allowed) {
            if (str_contains($ua, $allowed)) return false;
        }

        // Check blocked
        foreach (self::BLOCKED_UA as $blocked) {
            if (str_contains($ua, $blocked)) return true;
        }

        return false;
    }

    private function calculateSuspicionScore(Request $request, string $ua): int
    {
        $score = 0;

        // No User-Agent at all
        if (empty($ua)) $score += 2;

        // No Accept-Language header (real browsers always send this)
        if (!$request->header('Accept-Language')) $score += 2;

        // No Referer on POST requests (real forms have referer)
        if ($request->isMethod('POST') && !$request->header('Referer')) $score += 1;

        // No cookies at all (real users have session cookies)
        if (empty($request->cookies->all())) $score += 1;

        // Accept header doesn't include text/html or application/json
        $accept = $request->header('Accept', '');
        if (!str_contains($accept, 'json') && !str_contains($accept, 'html') && !str_contains($accept, '*/*')) {
            $score += 2;
        }

        // Very short or generic UA
        if (strlen($ua) < 20) $score += 1;

        return $score;
    }
}
