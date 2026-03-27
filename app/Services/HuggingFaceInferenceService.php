<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * เรียกใช้โมเดลที่ fine-tune แล้วผ่าน HuggingFace Inference API (ฟรี)
 *
 * Pipeline:
 * 1. เทรนบน Colab/Kaggle → ได้ LoRA adapter
 * 2. Merge adapter + base model → push ขึ้น HuggingFace Hub
 * 3. Service นี้เรียก HF Inference API ใช้โมเดลนั้น
 *
 * ข้อจำกัด HF Free Inference:
 * - โมเดลเล็ก (<3B) ใช้ได้เลย (serverless)
 * - โมเดลใหญ่ต้องรอ cold start 20-60 วินาที
 * - Rate limit ~300 req/hr (free), ~10,000 req/hr (PRO)
 */
class HuggingFaceInferenceService
{
    private ?string $modelRepo = null;
    private ?string $token = null;
    private ?string $inferenceEndpoint = null;

    private static array $_configCache = [];

    public function __construct()
    {
        $this->modelRepo = $this->getConfig('finetuned_model_repo');
        $this->inferenceEndpoint = $this->getConfig('inference_endpoint');

        $rawToken = $this->getConfig('huggingface_token');
        try {
            $this->token = $rawToken ? decrypt($rawToken) : null;
        } catch (\Exception $e) {
            $this->token = $rawToken;
        }
    }

    /**
     * ตรวจสอบว่ามีโมเดล fine-tune พร้อมใช้หรือไม่
     */
    public function isAvailable(): bool
    {
        // ต้องเปิดสวิตช์ use_finetuned_model + มี token + มี model/endpoint
        $enabled = $this->getConfig('use_finetuned_model', 'false') === 'true';
        return $enabled && !empty($this->token) && (!empty($this->modelRepo) || !empty($this->inferenceEndpoint));
    }

    /**
     * ส่ง chat ไปยังโมเดลที่ fine-tune แล้ว
     */
    public function chat(array $messages, string $systemPrompt = ''): ?string
    {
        if (!$this->isAvailable()) {
            return null;
        }

        // Build the full messages with system prompt
        $allMessages = [];
        if (!empty($systemPrompt)) {
            $allMessages[] = ['role' => 'system', 'content' => $systemPrompt];
        }
        $allMessages = array_merge($allMessages, $messages);

        try {
            // ลำดับ: 1) Custom endpoint  2) HF Inference API
            if (!empty($this->inferenceEndpoint)) {
                return $this->callCustomEndpoint($allMessages);
            }

            return $this->callHuggingFaceApi($allMessages);
        } catch (\Exception $e) {
            Log::warning('HuggingFace inference failed', [
                'error' => $e->getMessage(),
                'model' => $this->modelRepo,
            ]);
            return null;
        }
    }

    /**
     * เรียก HuggingFace Inference API (serverless, ฟรี)
     */
    private function callHuggingFaceApi(array $messages): ?string
    {
        $url = "https://api-inference.huggingface.co/models/{$this->modelRepo}/v1/chat/completions";

        $response = Http::withToken($this->token)
            ->timeout(60) // HF serverless อาจช้า (cold start)
            ->post($url, [
                'model' => $this->modelRepo,
                'messages' => $messages,
                'max_tokens' => 1024,
                'temperature' => 0.7,
                'stream' => false,
            ]);

        if ($response->status() === 503) {
            // Model loading — cold start
            Log::info('HF model loading (cold start)', ['model' => $this->modelRepo]);
            return null; // Fallback to Groq
        }

        if ($response->status() === 429) {
            Log::warning('HF rate limited');
            return null;
        }

        if ($response->failed()) {
            Log::warning('HF inference error', ['status' => $response->status(), 'body' => $response->body()]);
            return null;
        }

        $data = $response->json();
        return $data['choices'][0]['message']['content'] ?? null;
    }

    /**
     * เรียก Custom Inference Endpoint (HF Dedicated, Spaces, or self-hosted)
     * รองรับ OpenAI-compatible API format
     */
    private function callCustomEndpoint(array $messages): ?string
    {
        $url = rtrim($this->inferenceEndpoint, '/');
        if (!str_contains($url, '/chat/completions')) {
            $url .= '/v1/chat/completions';
        }

        $response = Http::withToken($this->token)
            ->timeout(30)
            ->post($url, [
                'messages' => $messages,
                'max_tokens' => 1024,
                'temperature' => 0.7,
            ]);

        if ($response->failed()) {
            Log::warning('Custom endpoint error', ['status' => $response->status()]);
            return null;
        }

        $data = $response->json();
        return $data['choices'][0]['message']['content'] ?? null;
    }

    /**
     * ตรวจสอบสถานะโมเดลบน HuggingFace
     */
    public function checkModelStatus(): array
    {
        if (!$this->modelRepo || !$this->token) {
            return ['status' => 'not_configured', 'message' => 'ยังไม่ได้ตั้งค่าโมเดล'];
        }

        try {
            // ดึงข้อมูลโมเดลจาก HF Hub API
            $response = Http::withToken($this->token)
                ->timeout(10)
                ->get("https://huggingface.co/api/models/{$this->modelRepo}");

            if ($response->status() === 404) {
                return ['status' => 'not_found', 'message' => "ไม่พบโมเดล {$this->modelRepo}"];
            }

            if ($response->failed()) {
                return ['status' => 'error', 'message' => 'ตรวจสอบไม่ได้: HTTP ' . $response->status()];
            }

            $data = $response->json();

            return [
                'status' => 'ready',
                'message' => 'โมเดลพร้อมใช้งาน',
                'model_id' => $data['modelId'] ?? $this->modelRepo,
                'pipeline_tag' => $data['pipeline_tag'] ?? 'unknown',
                'downloads' => $data['downloads'] ?? 0,
                'last_modified' => $data['lastModified'] ?? null,
            ];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * ทดสอบ inference ว่าโมเดลตอบได้
     */
    public function testInference(): array
    {
        $testMessages = [
            ['role' => 'user', 'content' => 'สวัสดีค่ะ ทดสอบระบบ'],
        ];

        $start = microtime(true);
        $reply = $this->chat($testMessages, 'คุณคือน้องหญิง ผู้ช่วย AI ของ ThaiHelp ตอบสั้น ๆ');
        $elapsed = round((microtime(true) - $start) * 1000);

        if ($reply) {
            return [
                'success' => true,
                'reply' => $reply,
                'latency_ms' => $elapsed,
                'message' => "ตอบได้ใน {$elapsed}ms",
            ];
        }

        return [
            'success' => false,
            'reply' => null,
            'latency_ms' => $elapsed,
            'message' => 'โมเดลยังไม่พร้อม หรือกำลัง cold start',
        ];
    }

    private function getConfig(string $key, $default = null)
    {
        if (!isset(self::$_configCache[$key])) {
            try {
                self::$_configCache[$key] = DB::table('ying_learning_config')->where('key', $key)->value('value');
            } catch (\Exception $e) {
                self::$_configCache[$key] = null;
            }
        }
        return self::$_configCache[$key] ?? $default;
    }
}
