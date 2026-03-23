<?php

namespace App\Services;

use App\Models\FuelReport;
use App\Models\StationReport;
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
     * Process a fuel report submitted via voice command.
     * Creates a StationReport + FuelReport from voice data.
     */
    public function processFuelReport(array $data): array
    {
        $latitude = $data['latitude'] ?? null;
        $longitude = $data['longitude'] ?? null;
        $fuelReport = $data['fuel_report'] ?? null;

        if (!$latitude || !$longitude || !$fuelReport) {
            return [
                'success' => false,
                'reply' => 'ข้อมูลไม่ครบค่ะ ต้องการตำแหน่งและข้อมูลน้ำมัน',
            ];
        }

        try {
            // Create a station report from voice
            $stationReport = StationReport::create([
                'place_id' => $fuelReport['place_id'] ?? 'voice_report_' . time(),
                'station_name' => $fuelReport['station_name'] ?? 'ปั๊มน้ำมัน (รายงานจากเสียง)',
                'reporter_name' => 'น้องหญิง Voice',
                'note' => $data['transcript'] ?? 'รายงานจากคำสั่งเสียง',
                'latitude' => $latitude,
                'longitude' => $longitude,
            ]);

            // Create fuel report entry
            $fuelType = $fuelReport['fuel_type'] ?? 'diesel';
            $fuelStatus = $fuelReport['status'] ?? 'available';

            // Validate against allowed values
            if (!in_array($fuelType, FuelReport::FUEL_TYPES)) {
                $fuelType = 'diesel';
            }
            if (!in_array($fuelStatus, FuelReport::STATUSES)) {
                $fuelStatus = 'available';
            }

            $stationReport->fuelReports()->create([
                'fuel_type' => $fuelType,
                'status' => $fuelStatus,
                'price' => $fuelReport['price'] ?? null,
            ]);

            return [
                'success' => true,
                'reply' => 'บันทึกรายงานน้ำมันเรียบร้อยแล้วค่ะ ขอบคุณที่ช่วยแจ้งนะคะ',
                'report_id' => $stationReport->id,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to process fuel report from voice', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            return [
                'success' => false,
                'reply' => 'ขอโทษค่ะ ไม่สามารถบันทึกรายงานได้ ลองใหม่อีกครั้งนะคะ',
            ];
        }
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
            . 'ประเภท action ที่เป็นไปได้: FIND_STATION, FIND_DIESEL, FIND_GASOHOL, REPORT, INCIDENT, NAVIGATE, CHECK_PRICE, FUEL_REPORT, HELP, CHAT '
            . 'fuelType อาจเป็น: diesel, gasohol95, gasohol91, e20, e85, lpg หรือ null '
            . 'ถ้าผู้ใช้รายงานสถานะน้ำมัน (เช่น "ปั๊มนี้น้ำมันหมด" หรือ "ดีเซลเหลือน้อย") ให้ตั้ง action เป็น FUEL_REPORT '
            . 'และเพิ่ม fuelStatus (available, low, empty) กับ fuelType ในผลลัพธ์ '
            . 'ตัวอย่าง: {"reply":"รับทราบค่ะ บันทึกว่าน้ำมันดีเซลหมดแล้ว","action":"FUEL_REPORT","fuelType":"diesel","fuelStatus":"empty"}';

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
            'fuelStatus' => $data['fuelStatus'] ?? null,
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

        // FUEL_REPORT
        if ($this->containsAny($text, ['น้ำมันหมด', 'หมดแล้ว', 'เหลือน้อย', 'น้ำมันเหลือ', 'มีน้ำมัน', 'เติมได้'])) {
            $fuelStatus = 'available';
            if ($this->containsAny($text, ['หมด', 'ไม่มี'])) {
                $fuelStatus = 'empty';
            } elseif ($this->containsAny($text, ['น้อย', 'เหลือน้อย'])) {
                $fuelStatus = 'low';
            }

            $fuelType = null;
            if ($this->containsAny($text, ['ดีเซล', 'diesel', 'โซล่า'])) {
                $fuelType = 'diesel';
            } elseif ($this->containsAny($text, ['95', 'แก๊สโซฮอล์'])) {
                $fuelType = 'gasohol95';
            } elseif ($this->containsAny($text, ['91'])) {
                $fuelType = 'gasohol91';
            } elseif ($this->containsAny($text, ['e20'])) {
                $fuelType = 'e20';
            } elseif ($this->containsAny($text, ['e85'])) {
                $fuelType = 'e85';
            }

            return [
                'reply' => 'รับทราบค่ะ บันทึกรายงานสถานะน้ำมันให้แล้วนะคะ ขอบคุณค่ะ',
                'action' => 'FUEL_REPORT',
                'fuelType' => $fuelType,
                'fuelStatus' => $fuelStatus,
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
