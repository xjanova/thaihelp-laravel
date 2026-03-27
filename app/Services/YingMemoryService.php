<?php

namespace App\Services;

use App\Models\YingMemory;
use App\Models\YingUserPattern;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class YingMemoryService
{
    /**
     * Store a memory from user interaction.
     */
    public function remember(?int $userId, ?string $sessionId, string $category, string $key, string $value, ?string $sourceMessage = null): ?YingMemory
    {
        if (!$this->isEnabled()) return null;

        try {
            // Check if similar memory exists — update instead of duplicate
            $existing = YingMemory::forUser($userId, $sessionId)
                ->where('category', $category)
                ->where('key', $key)
                ->first();

            if ($existing) {
                $existing->update([
                    'value' => $value,
                    'source_message' => $sourceMessage ?? $existing->source_message,
                ]);
                return $existing;
            }

            // Check max per user
            $maxPerUser = (int) $this->getConfig('memory_max_per_user', 50);
            $currentCount = YingMemory::forUser($userId, $sessionId)->count();
            if ($currentCount >= $maxPerUser) {
                // Remove least used memory
                YingMemory::forUser($userId, $sessionId)
                    ->orderBy('use_count')
                    ->orderBy('updated_at')
                    ->first()
                    ?->delete();
            }

            return YingMemory::create([
                'user_id' => $userId,
                'session_id' => $sessionId,
                'category' => $category,
                'key' => $key,
                'value' => $value,
                'source_message' => $sourceMessage,
            ]);
        } catch (\Exception $e) {
            Log::warning('YingMemory store failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Retrieve relevant memories for context injection.
     */
    public function recall(?int $userId, ?string $sessionId, ?string $topic = null, int $limit = 10): array
    {
        if (!$this->isEnabled()) return [];

        try {
            $query = YingMemory::forUser($userId, $sessionId)
                ->where('admin_approved', true)
                ->orderByDesc('use_count')
                ->orderByDesc('updated_at');

            if ($topic) {
                // Prioritize memories matching the topic
                $query->orderByRaw("CASE
                    WHEN `key` LIKE ? THEN 0
                    WHEN `value` LIKE ? THEN 1
                    ELSE 2
                END", ["%{$topic}%", "%{$topic}%"]);
            }

            $memories = $query->limit($limit)->get();

            // Mark as used
            foreach ($memories as $memory) {
                $memory->markUsed();
            }

            return $memories->toArray();
        } catch (\Exception $e) {
            Log::warning('YingMemory recall failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Build memory context string for system prompt injection.
     */
    public function buildMemoryContext(?int $userId, ?string $sessionId, ?string $userMessage = null): string
    {
        $memories = $this->recall($userId, $sessionId, $userMessage, 15);
        $patterns = $this->getPatterns($userId, $sessionId);

        if (empty($memories) && empty($patterns)) {
            return '';
        }

        $context = "\n\n=== ความจำเกี่ยวกับผู้ใช้คนนี้ ===\n";

        if (!empty($memories)) {
            $grouped = collect($memories)->groupBy('category');
            foreach ($grouped as $category => $items) {
                $catLabel = YingMemory::CATEGORIES[$category] ?? $category;
                $context .= "【{$catLabel}】\n";
                foreach ($items as $item) {
                    $context .= "- {$item['key']}: {$item['value']}\n";
                }
            }
        }

        if (!empty($patterns)) {
            $context .= "【พฤติกรรมที่เรียนรู้】\n";
            foreach ($patterns as $p) {
                $typeLabel = YingUserPattern::TYPES[$p['pattern_type']] ?? $p['pattern_type'];
                $context .= "- {$typeLabel}: " . json_encode($p['pattern_data'], JSON_UNESCAPED_UNICODE) . " (มั่นใจ {$p['confidence']})\n";
            }
        }

        $context .= "=== ใช้ข้อมูลข้างบนเพื่อตอบให้เหมาะกับผู้ใช้ ===\n";

        return $context;
    }

    /**
     * Parse user message for memory-worthy content.
     * Returns array of memories to store, or empty if nothing to learn.
     */
    public function extractMemories(string $message): array
    {
        $memories = [];
        $lower = mb_strtolower($message);

        // Nickname patterns
        if (preg_match('/(?:ชื่อ|เรียก(?:ว่า)?|เรียกฉัน(?:ว่า)?)\s*(.{1,30})/u', $lower, $m)) {
            $memories[] = ['category' => 'nickname', 'key' => 'user_nickname', 'value' => trim($m[1])];
        }

        // Vehicle/fuel preferences
        if (preg_match('/(?:รถ|ขับ|ใช้)\s*(เก๋ง|กระบะ|SUV|มอเตอร์ไซค์|รถจักรยานยนต์|รถบรรทุก|EV|ไฟฟ้า)/iu', $lower, $m)) {
            $memories[] = ['category' => 'vehicle', 'key' => 'vehicle_type', 'value' => trim($m[1])];
        }
        if (preg_match('/(?:เติม|ใช้น้ำมัน|เชื้อเพลิง)\s*(ดีเซล|แก๊สโซฮอล์\s*\d+|E20|E85|LPG|NGV|เบนซิน|91|95)/iu', $lower, $m)) {
            $memories[] = ['category' => 'preference', 'key' => 'preferred_fuel', 'value' => trim($m[1])];
        }

        // Brand preferences
        if (preg_match('/(?:ชอบ|ถูกใจ|เติมประจำ|ปั๊มประจำ)\s*(PTT|Shell|Bangchak|Esso|Caltex|PT|บางจาก|ปตท|เชลล์|เอสโซ)/iu', $lower, $m)) {
            $memories[] = ['category' => 'preference', 'key' => 'preferred_brand', 'value' => trim($m[1])];
        }

        // Home/work location
        if (preg_match('/(?:บ้าน(?:อยู่)?|อยู่(?:ที่|แถว)?)\s*(.{2,50})/u', $lower, $m)) {
            $val = trim($m[1]);
            if (mb_strlen($val) >= 2 && mb_strlen($val) <= 50) {
                $memories[] = ['category' => 'location', 'key' => 'home_area', 'value' => $val];
            }
        }
        if (preg_match('/(?:ทำงาน(?:ที่|แถว)?|ออฟฟิศ(?:อยู่)?)\s*(.{2,50})/u', $lower, $m)) {
            $memories[] = ['category' => 'location', 'key' => 'work_area', 'value' => trim($m[1])];
        }

        // Explicit teaching: "จำไว้ว่า..." / "หญิงจำไว้นะ..."
        if (preg_match('/(?:จำไว้(?:ว่า|นะ)?|จำ(?:ด้วย)?(?:นะ)?(?:ว่า)?)\s*(.{3,200})/u', $lower, $m)) {
            $memories[] = ['category' => 'fact', 'key' => 'user_taught_' . time(), 'value' => trim($m[1])];
        }

        // Corrections: "ไม่ใช่..." / "ผิดนะ..."
        if (preg_match('/(?:ไม่ใช่|ผิด(?:นะ|แล้ว)?|แก้ไข|ที่ถูกคือ)\s*(.{3,200})/u', $lower, $m)) {
            $memories[] = ['category' => 'correction', 'key' => 'correction_' . time(), 'value' => trim($m[1])];
        }

        return $memories;
    }

    /**
     * Get confident behavior patterns for a user.
     */
    private function getPatterns(?int $userId, ?string $sessionId): array
    {
        if (!$this->getConfig('behavior_tracking', true)) return [];

        return YingUserPattern::forUser($userId, $sessionId)
            ->confident(0.6)
            ->orderByDesc('confidence')
            ->limit(5)
            ->get()
            ->toArray();
    }

    /**
     * Track behavioral pattern from a message.
     */
    public function trackBehavior(?int $userId, ?string $sessionId, string $message, array $context = []): void
    {
        if (!$this->getConfig('behavior_tracking', true)) return;

        try {
            $lower = mb_strtolower($message);

            // Track fuel type mentions
            $fuelTypes = ['ดีเซล' => 'diesel', 'แก๊สโซฮอล์ 95' => 'gasohol95', 'แก๊สโซฮอล์ 91' => 'gasohol91', 'e20' => 'e20', 'e85' => 'e85', 'lpg' => 'lpg'];
            foreach ($fuelTypes as $thai => $eng) {
                if (str_contains($lower, mb_strtolower($thai)) || str_contains($lower, $eng)) {
                    YingUserPattern::record($userId, $sessionId, 'preferred_fuel', $eng, ['fuel' => $eng, 'last_mentioned' => now()->toISOString()]);
                }
            }

            // Track brand mentions
            $brands = ['ptt' => ['ptt', 'ปตท'], 'shell' => ['shell', 'เชลล์'], 'bangchak' => ['bangchak', 'บางจาก'], 'pt' => ['pt', 'พีที'], 'esso' => ['esso', 'เอสโซ']];
            foreach ($brands as $brand => $keywords) {
                foreach ($keywords as $kw) {
                    if (str_contains($lower, $kw)) {
                        YingUserPattern::record($userId, $sessionId, 'preferred_brand', $brand, ['brand' => $brand, 'last_mentioned' => now()->toISOString()]);
                        break;
                    }
                }
            }

            // Track time patterns
            $hour = now()->hour;
            $timeSlot = match (true) {
                $hour >= 5 && $hour < 9 => 'morning_commute',
                $hour >= 9 && $hour < 12 => 'morning_work',
                $hour >= 12 && $hour < 14 => 'lunch',
                $hour >= 14 && $hour < 17 => 'afternoon',
                $hour >= 17 && $hour < 20 => 'evening_commute',
                default => 'night',
            };
            YingUserPattern::record($userId, $sessionId, 'time_pattern', $timeSlot, ['hour' => $hour, 'day' => now()->dayName]);

        } catch (\Exception $e) {
            // Silent fail — tracking should never break chat
        }
    }

    private function isEnabled(): bool
    {
        return (bool) $this->getConfig('memory_enabled', true);
    }

    private function getConfig(string $key, $default = null)
    {
        return DB::table('ying_learning_config')->where('key', $key)->value('value') ?? $default;
    }
}
