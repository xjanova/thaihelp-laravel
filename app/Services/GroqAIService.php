<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GroqAIService
{
    private string $apiKey;
    private string $model;

    public function __construct()
    {
        $this->apiKey = SiteSetting::get('groq_api_key') ?: config('services.groq.api_key', '');
        $this->model = config('services.groq.model', 'llama-3.3-70b-versatile');
    }

    /**
     * Send a chat request to the Groq API.
     */
    public function chat(array $messages): string
    {
        $systemPrompt = 'คุณคือ "น้องหญิง" ผู้ช่วย AI สุดน่ารักของแอป ThaiHelp '
            . 'คุณเป็นสาวไทยใจดี พูดจาน่ารัก ใช้คำลงท้ายว่า "ค่ะ" หรือ "นะคะ" '
            . 'คุณมีความรู้เรื่องถนน เส้นทาง ปั๊มน้ำมัน และการเดินทางในประเทศไทยเป็นอย่างดี '
            . 'ตอบสั้นกระชับ เป็นมิตร และเป็นประโยชน์ค่ะ';

        $allMessages = array_merge(
            [['role' => 'system', 'content' => $systemPrompt]],
            $messages
        );

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(30)
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => $this->model,
                    'messages' => $allMessages,
                    'temperature' => 0.7,
                    'max_tokens' => 1024,
                ]);

            if ($response->failed()) {
                Log::error('Groq API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return 'ขอโทษค่ะ ไม่สามารถตอบได้ในตอนนี้';
            }

            $data = $response->json();

            return $data['choices'][0]['message']['content'] ?? 'ขอโทษค่ะ ไม่สามารถตอบได้ในตอนนี้';
        } catch (\Exception $e) {
            Log::error('Groq API exception', ['message' => $e->getMessage()]);
            return 'ขอโทษค่ะ ไม่สามารถตอบได้ในตอนนี้';
        }
    }

    /**
     * Check if the Groq API is available (API key configured).
     */
    public function isAvailable(): bool
    {
        return !empty($this->apiKey);
    }
}
