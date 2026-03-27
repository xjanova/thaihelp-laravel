<?php

namespace App\Services;

use App\Models\YingTrainingData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class YingTrainingService
{
    /**
     * Collect a conversation pair for potential training.
     */
    public function collect(
        ?int $userId,
        string $userMessage,
        string $assistantMessage,
        ?string $systemPrompt = null,
        ?string $category = null,
        array $context = []
    ): ?YingTrainingData {
        if (!$this->isEnabled()) return null;

        // Skip very short or command-only responses
        if (mb_strlen($userMessage) < 3 || mb_strlen($assistantMessage) < 10) {
            return null;
        }

        // Auto-categorize if not provided
        if (!$category) {
            $category = $this->detectCategory($userMessage);
        }

        try {
            return YingTrainingData::create([
                'user_id' => $userId,
                'system_prompt' => $systemPrompt,
                'user_message' => $userMessage,
                'assistant_message' => $this->cleanForTraining($assistantMessage),
                'context_data' => $context ?: null,
                'category' => $category,
                'status' => 'pending',
            ]);
        } catch (\Exception $e) {
            Log::warning('Training data collection failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Export approved training data as JSONL (HuggingFace chat format).
     */
    public function exportJsonl(int $minQuality = 3): string
    {
        $data = YingTrainingData::exportable($minQuality)->get();
        $lines = [];

        foreach ($data as $item) {
            $lines[] = json_encode($item->toTrainingFormat(), JSON_UNESCAPED_UNICODE);
        }

        $content = implode("\n", $lines);
        $filename = 'ying_training_' . now()->format('Ymd_His') . '.jsonl';
        Storage::disk('local')->put("training/{$filename}", $content);

        // Mark as exported
        YingTrainingData::exportable($minQuality)->update([
            'status' => 'exported',
            'exported_at' => now(),
        ]);

        return storage_path("app/training/{$filename}");
    }

    /**
     * Push training data to HuggingFace Hub.
     */
    public function pushToHuggingFace(): array
    {
        $repo = $this->getConfig('huggingface_repo');
        $rawToken = $this->getConfig('huggingface_token');
        try {
            $token = $rawToken ? decrypt($rawToken) : null;
        } catch (\Exception $e) {
            $token = $rawToken; // fallback for legacy plain text
        }

        if (!$repo || !$token) {
            return ['success' => false, 'error' => 'HuggingFace repo or token not configured'];
        }

        // Export to JSONL first
        $minQuality = (int) $this->getConfig('training_min_quality', 3);
        $count = YingTrainingData::exportable($minQuality)->count();

        if ($count === 0) {
            return ['success' => false, 'error' => 'No approved training data to export'];
        }

        $filePath = $this->exportJsonl($minQuality);
        $content = file_get_contents($filePath);

        try {
            // Upload to HuggingFace Hub via API
            $response = Http::withToken($token)
                ->timeout(30)
                ->withBody($content, 'application/octet-stream')
                ->put("https://huggingface.co/api/datasets/{$repo}/upload/main/train.jsonl");

            if ($response->successful()) {
                // Update exported records
                YingTrainingData::where('status', 'exported')
                    ->whereNull('exported_to')
                    ->update(['exported_to' => $repo]);

                return [
                    'success' => true,
                    'exported_count' => $count,
                    'repo' => $repo,
                    'file' => 'train.jsonl',
                ];
            }

            return ['success' => false, 'error' => 'HuggingFace API error: ' . $response->status()];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get training statistics.
     */
    public function getStats(): array
    {
        return [
            'total' => YingTrainingData::count(),
            'pending' => YingTrainingData::pending()->count(),
            'approved' => YingTrainingData::approved()->count(),
            'exported' => YingTrainingData::where('status', 'exported')->count(),
            'rejected' => YingTrainingData::where('status', 'rejected')->count(),
            'by_category' => YingTrainingData::select('category', DB::raw('count(*) as count'))
                ->groupBy('category')->pluck('count', 'category')->toArray(),
            'avg_quality' => round(YingTrainingData::approved()->avg('quality_score') ?? 0, 1),
        ];
    }

    /**
     * Auto-detect conversation category.
     */
    private function detectCategory(string $message): string
    {
        $lower = mb_strtolower($message);

        $categories = [
            'navigation' => ['นำทาง', 'ไป', 'เส้นทาง', 'ถนน', 'ทาง', 'route'],
            'fuel' => ['น้ำมัน', 'เติม', 'ดีเซล', 'แก๊สโซฮอล์', 'ราคา', 'fuel'],
            'station' => ['ปั๊ม', 'สถานี', 'station', 'ptt', 'shell', 'bangchak'],
            'incident' => ['อุบัติเหตุ', 'น้ำท่วม', 'ถนนปิด', 'อันตราย', 'แจ้ง'],
            'weather' => ['อากาศ', 'ฝน', 'ร้อน', 'pm2.5', 'หนาว'],
            'hospital' => ['โรงพยาบาล', 'ฉุกเฉิน', 'เจ็บ', 'ป่วย'],
            'trip' => ['เดินทาง', 'วางแผน', 'trip', 'ท่องเที่ยว'],
            'greeting' => ['สวัสดี', 'หวัดดี', 'ดีจ้า', 'hello'],
        ];

        foreach ($categories as $cat => $keywords) {
            foreach ($keywords as $kw) {
                if (str_contains($lower, $kw)) return $cat;
            }
        }

        return 'general';
    }

    /**
     * Clean assistant message for training (remove command tags).
     */
    private function cleanForTraining(string $message): string
    {
        return preg_replace('/\[(?:NAVIGATE|FUEL_REPORT|INCIDENT_REPORT|OPEN_\w+|CALL_SOS|PLAY_VIDEO)[^\]]*\]/', '', $message);
    }

    private function isEnabled(): bool
    {
        return (bool) ($this->getConfig('auto_collect_training') ?? true);
    }

    private static array $_configCache = [];

    private function getConfig(string $key, $default = null)
    {
        if (!isset(self::$_configCache[$key])) {
            self::$_configCache[$key] = DB::table('ying_learning_config')->where('key', $key)->value('value');
        }
        return self::$_configCache[$key] ?? $default;
    }
}
