<?php

namespace App\Services;

use App\Models\SiteSetting;
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
     * Get API key from pool (round-robin) or fallback to single key.
     */
    private function getApiKey(): string
    {
        return ApiKeyPool::getKey('groq', 'groq.api_key') ?: '';
    }

    /**
     * Send a chat request to the Groq API.
     * Uses API key pool for load balancing.
     */
    public function chat(array $messages): string
    {
        $systemPrompt = <<<'PROMPT'
คุณคือ "น้องหญิง" ผู้ช่วย AI สุดน่ารักของแอป ThaiHelp
บุคลิก: เด็กสาวไทยอายุ 18 น่ารัก ร่าเริง พูดจาน่าหยิก ใช้คำลงท้าย "ค่ะ" "นะคะ" "จ้า"
ความสามารถ: เชี่ยวชาญเรื่องถนน ปั๊มน้ำมัน เส้นทาง การเดินทางในไทย

กฎสำคัญ:
1. ตอบสั้นกระชับ ไม่เกิน 2-3 ประโยค
2. ใช้อิโมจิบ้างให้น่ารัก แต่ไม่มากเกิน
3. จำบทสนทนาได้ รู้ว่ากำลังคุยเรื่องอะไร
4. เมื่อผู้ใช้แจ้งสถานะน้ำมัน ให้ถามยืนยันแล้วใส่ข้อมูลในรูปแบบ:
   [FUEL_REPORT:{"brand":"ชื่อปั๊ม","fuel_type":"ประเภท","status":"สถานะ"}]
   โดย fuel_type: gasohol95, gasohol91, diesel, diesel_b7, e20, e85, ngv, lpg
   โดย status: available, low, empty
5. เมื่อผู้ใช้ถามหาปั๊ม ให้แนะนำและถามว่าต้องการนำทางไหม
6. พูดเหมือนเด็กสาวจริงๆ ไม่เป็นทางการ
PROMPT;

        $allMessages = array_merge(
            [['role' => 'system', 'content' => $systemPrompt]],
            $messages
        );

        // Try up to 3 keys from the pool
        $maxRetries = 3;
        $lastError = null;

        for ($attempt = 0; $attempt < $maxRetries; $attempt++) {
            $apiKey = $this->getApiKey();

            if (empty($apiKey)) {
                return 'ขอโทษค่ะ ยังไม่ได้ตั้งค่า API Key นะคะ';
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

                // Rate limited — mark key and try next
                if ($response->status() === 429) {
                    ApiKeyPool::markRateLimited('groq', $apiKey, 60);
                    Log::warning('Groq API rate limited, rotating key', ['attempt' => $attempt + 1]);
                    $lastError = 'rate_limited';
                    continue;
                }

                // Auth error — mark key failed
                if ($response->status() === 401 || $response->status() === 403) {
                    ApiKeyPool::markFailed('groq', $apiKey);
                    Log::error('Groq API auth failed, key disabled', ['attempt' => $attempt + 1]);
                    $lastError = 'auth_failed';
                    continue;
                }

                if ($response->failed()) {
                    Log::error('Groq API request failed', [
                        'status' => $response->status(),
                        'body' => substr($response->body(), 0, 200),
                    ]);
                    return 'ขอโทษค่ะ ไม่สามารถตอบได้ในตอนนี้ 😢';
                }

                $data = $response->json();
                return $data['choices'][0]['message']['content'] ?? 'ขอโทษค่ะ ไม่สามารถตอบได้ในตอนนี้';

            } catch (\Exception $e) {
                Log::error('Groq API exception', ['message' => $e->getMessage(), 'attempt' => $attempt + 1]);
                ApiKeyPool::markFailed('groq', $apiKey);
                $lastError = $e->getMessage();
            }
        }

        return 'ขอโทษค่ะ ระบบ AI ไม่ว่างตอนนี้ ลองใหม่อีกทีนะคะ 🙏';
    }

    /**
     * Check if the Groq API is available.
     */
    public function isAvailable(): bool
    {
        return !empty($this->getApiKey());
    }
}
