<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GroqAIService
{
    private string $model;

    public function __construct()
    {
        $this->model = config('services.groq.model', 'llama-3.3-70b-versatile');
    }

    /**
     * Check if any Groq API key is configured (without consuming a round-robin slot).
     */
    public function isAvailable(): bool
    {
        // Check pool first (doesn't rotate)
        $pool = ApiKeyPool::getPool('groq');
        foreach ($pool as $entry) {
            if (($entry['enabled'] ?? true) && !empty($entry['key'])) {
                return true;
            }
        }

        // Check fallback key
        $fallback = SiteSetting::get('groq_api_key') ?: config('services.groq.api_key');
        return !empty($fallback);
    }

    /**
     * Load system prompt from file, with DB override support.
     */
    private function getSystemPrompt(): string
    {
        // Allow admin to override via settings
        $custom = SiteSetting::get('ying_system_prompt');
        if (!empty($custom)) {
            return $custom;
        }

        // Load from file
        $path = resource_path('prompts/ying-system.txt');
        if (file_exists($path)) {
            return file_get_contents($path);
        }

        return 'คุณคือน้องหญิง ผู้ช่วย AI ของ ThaiHelp ตอบเป็นภาษาไทย สั้นกระชับ ลงท้ายด้วย ค่ะ/นะคะ';
    }

    /**
     * Collect all unique API keys (pool + fallback).
     * @return string[]
     */
    private function getAllKeys(): array
    {
        $keys = [];

        $pool = ApiKeyPool::getPool('groq');
        foreach ($pool as $entry) {
            if (($entry['enabled'] ?? true) && !empty($entry['key'])) {
                $keys[] = $entry['key'];
            }
        }

        $fallback = SiteSetting::get('groq_api_key') ?: config('services.groq.api_key');
        if (!empty($fallback) && !in_array($fallback, $keys)) {
            $keys[] = $fallback;
        }

        return $keys;
    }

    /**
     * Send a chat request to the Groq API.
     * Uses round-robin key rotation with automatic failover.
     */
    public function chat(array $messages, string $locationContext = ''): string
    {
        $systemPrompt = $this->getSystemPrompt();

        // Inject location context if available (cap at max chars to save tokens)
        if (!empty($locationContext)) {
            $maxCtx = (int) (SiteSetting::get('ying_max_context_chars') ?: 3000);
            $ctx = mb_substr($locationContext, 0, $maxCtx);
            $systemPrompt .= "\n\n═══ ข้อมูล LIVE รอบตัวผู้ใช้ ═══\n" . $ctx;
        }

        $allMessages = array_merge(
            [['role' => 'system', 'content' => $systemPrompt]],
            $messages
        );

        // Log prompt size
        $promptLen = mb_strlen($systemPrompt);
        $totalLen = array_sum(array_map(fn($m) => mb_strlen($m['content'] ?? ''), $allMessages));
        Log::debug('Groq request', ['prompt_chars' => $promptLen, 'total_chars' => $totalLen, 'messages' => count($allMessages)]);

        // Collect all keys and rotate
        $allKeys = $this->getAllKeys();
        if (empty($allKeys)) {
            return 'ขอโทษค่ะ ยังไม่ได้ตั้งค่า API Key นะคะ กรุณาแจ้ง Admin ค่ะ';
        }

        // Round-robin: start from next key index
        $keyCount = count($allKeys);
        // Atomic increment to prevent race conditions under concurrency
        $rrIndex = (int) Cache::increment('groq_rr_index');
        if ($rrIndex > 100000) Cache::put('groq_rr_index', 0, 3600); // prevent overflow

        $lastError = null;

        for ($j = 0; $j < $keyCount; $j++) {
            $apiKey = $allKeys[($rrIndex + $j) % $keyCount];

            // Skip rate-limited keys
            if (ApiKeyPool::isRateLimited('groq', $apiKey)) {
                $lastError = 'rate_limited';
                continue;
            }

            try {
                $response = Http::withToken($apiKey)
                    ->timeout(30)
                    ->post('https://api.groq.com/openai/v1/chat/completions', [
                        'model' => $this->model,
                        'messages' => $allMessages,
                        'temperature' => 0.7,
                        'max_tokens' => 1024,
                    ]);

                if ($response->status() === 429) {
                    ApiKeyPool::markRateLimited('groq', $apiKey, 60);
                    Log::warning('Groq 429', ['key' => $j]);
                    $lastError = 'rate_limited';
                    continue;
                }

                if ($response->status() === 401 || $response->status() === 403) {
                    ApiKeyPool::markFailed('groq', $apiKey);
                    Log::error('Groq auth failed', ['key' => $j, 'status' => $response->status()]);
                    $lastError = 'auth_failed';
                    continue;
                }

                if ($response->failed()) {
                    Log::error('Groq HTTP error', ['key' => $j, 'status' => $response->status()]);
                    $lastError = 'http_' . $response->status();
                    continue;
                }

                $data = $response->json();
                return $data['choices'][0]['message']['content'] ?? 'ขอโทษค่ะ AI ตอบกลับว่างเปล่าค่ะ';

            } catch (\Exception $e) {
                Log::error('Groq exception', ['msg' => $e->getMessage(), 'key' => $j]);
                ApiKeyPool::markFailed('groq', $apiKey);
                $lastError = $e->getMessage();
            }
        }

        Log::error('Groq: All keys exhausted', ['last_error' => $lastError, 'keys' => $keyCount]);

        return match ($lastError) {
            'rate_limited' => 'ขอโทษค่ะ มีคนใช้เยอะมากค่ะ ลองใหม่อีกสักครู่นะคะ 🙏',
            'auth_failed' => 'ขอโทษค่ะ API Key มีปัญหาค่ะ กรุณาแจ้ง Admin ค่ะ',
            default => 'ขอโทษค่ะ ระบบ AI ขัดข้องชั่วคราวค่ะ ลองใหม่อีกทีนะคะ 🙏',
        };
    }
}
