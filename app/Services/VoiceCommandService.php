<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class VoiceCommandService
{
    public function __construct(
        private GroqAIService $groqAI,
    ) {}

    /**
     * Process a voice command transcript and return action + reply.
     */
    public function process(
        string $transcript,
        ?int $stationCount = null,
        ?string $nearestStation = null,
        ?float $nearestDistance = null,
    ): array {
        if ($this->groqAI->isAvailable()) {
            return $this->processWithAI($transcript, $stationCount, $nearestStation, $nearestDistance);
        }

        return $this->detectAction($transcript);
    }

    /**
     * Process transcript using Groq AI for intelligent action detection.
     */
    private function processWithAI(
        string $transcript,
        ?int $stationCount,
        ?string $nearestStation,
        ?float $nearestDistance,
    ): array {
        $contextParts = [];
        if ($stationCount !== null) {
            $contextParts[] = "มีปั๊มน้ำมันใกล้เคียง {$stationCount} แห่ง";
        }
        if ($nearestStation !== null && $nearestDistance !== null) {
            $contextParts[] = "ปั๊มที่ใกล้ที่สุดคือ {$nearestStation} ห่าง {$nearestDistance} กม.";
        }
        $context = !empty($contextParts) ? implode(', ', $contextParts) : 'ไม่มีข้อมูลปั๊มใกล้เคียง';

        $systemPrompt = 'คุณเป็นระบบตรวจจับคำสั่งเสียงของแอป ThaiHelp '
            . 'จากข้อความที่ผู้ใช้พูด ให้ตรวจจับประเภทการกระทำและตอบกลับเป็นภาษาไทย '
            . 'ข้อมูลบริบท: ' . $context . ' '
            . 'ตอบเป็น JSON เท่านั้นในรูปแบบ: {"reply": "ข้อความตอบกลับ", "action": "ACTION_TYPE", "fuelType": null} '
            . 'ประเภท action ที่เป็นไปได้: FIND_STATION, FIND_DIESEL, FIND_GASOHOL, REPORT, INCIDENT, NAVIGATE, CHECK_PRICE, HELP, CHAT '
            . 'fuelType อาจเป็น: diesel, gasohol95, gasohol91, e20, e85, lpg หรือ null';

        $messages = [
            ['role' => 'user', 'content' => $transcript],
        ];

        try {
            $allMessages = array_merge(
                [['role' => 'system', 'content' => $systemPrompt]],
                $messages
            );

            $reply = $this->groqAI->chat($allMessages);

            // Try to parse JSON from the AI response
            $parsed = $this->parseAIResponse($reply);
            if ($parsed !== null) {
                return $parsed;
            }

            // If JSON parsing fails, return as chat with AI reply
            return [
                'reply' => $reply,
                'action' => 'CHAT',
                'fuelType' => null,
            ];
        } catch (\Exception $e) {
            Log::error('Voice command AI processing failed', ['message' => $e->getMessage()]);
            return $this->detectAction($transcript);
        }
    }

    /**
     * Parse the AI response JSON.
     */
    private function parseAIResponse(string $response): ?array
    {
        // Try to extract JSON from the response (AI might wrap it in markdown code blocks)
        $jsonString = $response;

        if (preg_match('/```(?:json)?\s*(\{.*?\})\s*```/s', $response, $matches)) {
            $jsonString = $matches[1];
        } elseif (preg_match('/(\{[^{}]*"reply"[^{}]*\})/s', $response, $matches)) {
            $jsonString = $matches[1];
        }

        $data = json_decode($jsonString, true);

        if (json_last_error() !== JSON_ERROR_NONE || !isset($data['reply'], $data['action'])) {
            return null;
        }

        return [
            'reply' => $data['reply'],
            'action' => $data['action'],
            'fuelType' => $data['fuelType'] ?? null,
        ];
    }

    /**
     * Detect action from transcript using keyword matching (fallback).
     */
    public function detectAction(string $transcript): array
    {
        $text = mb_strtolower(trim($transcript));

        // FIND_DIESEL
        if ($this->containsAny($text, ['ดีเซล', 'diesel', 'โซล่า'])) {
            return [
                'reply' => 'กำลังค้นหาปั๊มดีเซลใกล้เคียงให้นะคะ',
                'action' => 'FIND_DIESEL',
                'fuelType' => 'diesel',
            ];
        }

        // FIND_GASOHOL
        if ($this->containsAny($text, ['95', 'แก๊สโซฮอล์', 'เบนซิน', 'gasohol', 'แก๊สโซฮอล'])) {
            $fuelType = 'gasohol95';
            if (str_contains($text, '91')) {
                $fuelType = 'gasohol91';
            } elseif (str_contains($text, 'e20')) {
                $fuelType = 'e20';
            } elseif (str_contains($text, 'e85')) {
                $fuelType = 'e85';
            }

            return [
                'reply' => 'กำลังค้นหาปั๊มแก๊สโซฮอล์ใกล้เคียงให้นะคะ',
                'action' => 'FIND_GASOHOL',
                'fuelType' => $fuelType,
            ];
        }

        // FIND_STATION
        if ($this->containsAny($text, ['หาปั๊ม', 'ปั๊มน้ำมัน', 'ปั๊มใกล้', 'หาปั้ม', 'เติมน้ำมัน', 'gas station'])) {
            return [
                'reply' => 'กำลังค้นหาปั๊มน้ำมันใกล้เคียงให้นะคะ',
                'action' => 'FIND_STATION',
                'fuelType' => null,
            ];
        }

        // INCIDENT
        if ($this->containsAny($text, ['อุบัติเหตุ', 'น้ำท่วม', 'จุดตรวจ', 'ถนนปิด', 'accident'])) {
            return [
                'reply' => 'รับทราบค่ะ กำลังเปิดหน้ารายงานเหตุการณ์ให้นะคะ',
                'action' => 'INCIDENT',
                'fuelType' => null,
            ];
        }

        // REPORT
        if ($this->containsAny($text, ['รายงาน', 'แจ้ง', 'report'])) {
            return [
                'reply' => 'เปิดหน้ารายงานให้แล้วค่ะ กรุณาเลือกประเภทที่ต้องการแจ้งนะคะ',
                'action' => 'REPORT',
                'fuelType' => null,
            ];
        }

        // NAVIGATE
        if ($this->containsAny($text, ['นำทาง', 'ไป', 'พาไป', 'navigate', 'เส้นทาง'])) {
            return [
                'reply' => 'กำลังเปิดการนำทางให้นะคะ',
                'action' => 'NAVIGATE',
                'fuelType' => null,
            ];
        }

        // CHECK_PRICE
        if ($this->containsAny($text, ['ราคา', 'ราคาน้ำมัน', 'price', 'กี่บาท'])) {
            return [
                'reply' => 'กำลังเช็คราคาน้ำมันล่าสุดให้นะคะ',
                'action' => 'CHECK_PRICE',
                'fuelType' => null,
            ];
        }

        // HELP
        if ($this->containsAny($text, ['ช่วย', 'ทำอะไรได้', 'help', 'คำสั่ง', 'วิธีใช้'])) {
            return [
                'reply' => 'น้องหญิงช่วยได้หลายอย่างเลยค่ะ เช่น หาปั๊มน้ำมัน เช็คราคาน้ำมัน นำทาง รายงานเหตุการณ์ หรือจะคุยเล่นก็ได้นะคะ',
                'action' => 'HELP',
                'fuelType' => null,
            ];
        }

        // Default: CHAT
        return [
            'reply' => 'น้องหญิงรับฟังอยู่ค่ะ ลองบอกน้องหญิงว่าต้องการอะไรนะคะ เช่น "หาปั๊มน้ำมัน" หรือ "เช็คราคาน้ำมัน"',
            'action' => 'CHAT',
            'fuelType' => null,
        ];
    }

    /**
     * Check if text contains any of the given keywords.
     */
    private function containsAny(string $text, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if (str_contains($text, mb_strtolower($keyword))) {
                return true;
            }
        }

        return false;
    }
}
