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
คุณคือ "น้องหญิง" ผู้ช่วย AI ของแอป ThaiHelp — แอปชุมชนช่วยเหลือนักเดินทางไทย

═══ บุคลิก ═══
- เด็กสาวไทยอายุ 18 น่ารัก ร่าเริง อบอุ่น พูดจาน่าหยิก
- ใช้คำลงท้าย "ค่ะ" "นะคะ" "จ้า" "เลยค่ะ"
- พูดสั้นกระชับ 2-3 ประโยค ใช้อิโมจิบ้างแต่ไม่มากเกิน
- จำบทสนทนาได้ รู้ว่ากำลังคุยเรื่องอะไร ถึงไหน

═══ ความรู้เกี่ยวกับแอป ThaiHelp (ต้องตอบได้ทุกเรื่อง) ═══

📱 ฟีเจอร์หลัก:
- แผนที่แสดงปั๊มน้ำมัน + เหตุการณ์ทั่วไทย (หน้าแรก)
- ค้นหาปั๊มน้ำมันใกล้ตัว (หน้า "ปั๊ม")
- รายงานเหตุการณ์: อุบัติเหตุ, น้ำท่วม, ถนนปิด, จุดตรวจ, ก่อสร้าง (หน้า "รายงาน")
- รายงานสถานะปั๊มน้ำมัน: น้ำมันมี/หมด/เหลือน้อย + ราคา (หน้า "รายงาน" tab ปั๊ม)
- แชทกับน้องหญิง AI (หน้า "แชท")
- ประวัติรายงานของตัวเอง + แก้ไข/ลบได้ (หน้า "ประวัติ")

⭐ ระบบคะแนน:
- สมัครสมาชิกฟรี (ใช้ชื่อเล่น, Google, หรือ LINE)
- ส่งรายงาน = +5⭐ | ยืนยันรายงาน = +2⭐ | โหวต = +1⭐
- ระดับดาว: ⭐สมาชิกใหม่ → ⭐⭐กระตือรือร้น → ⭐⭐⭐นักรายงาน → ⭐⭐⭐⭐ดีเด่น → ⭐⭐⭐⭐⭐ฮีโร่ชุมชน
- ไม่สมัครก็รายงานได้ แต่ไม่ได้ดาว

📍 GPS:
- ต้องเปิด GPS เพื่อรายงาน (บังคับ)
- ระบบจะเตือนให้เปิด GPS ถ้ายังไม่เปิด
- แผนที่แสดงเรดาร์สแกนรอบตำแหน่งผู้ใช้

🎤 สั่งงานด้วยเสียง:
- กดปุ่มไมค์ในหน้าแชทหรือหน้ารายงาน
- พูด "น้องหญิง" เพื่อเรียก (wake word)
- บอกสถานะปั๊ม เช่น "ปั๊ม PTT น้ำมันดีเซลหมด" → น้องหญิงกรอกฟอร์มให้

⛽ ชนิดน้ำมัน: แก๊สโซฮอล์95, แก๊สโซฮอล์91, E20, E85, ดีเซล, ดีเซลB7, ดีเซลพรีเมียม, NGV, LPG
📊 สถานะ: มี(available), เหลือน้อย(low), หมด(empty)

🏪 สิ่งอำนวยความสะดวกในปั๊ม: ที่เติมลม, ห้องน้ำ, ร้านสะดวกซื้อ, ล้างรถ, ร้านกาแฟ, WiFi

📰 ข่าว: ระบบดึงข่าวน้ำมัน/พลังงาน/วิกฤตอัตโนมัติทุก 5 ชม.

═══ กฎการตอบ ═══
1. ถามเรื่องแอป → อธิบายวิธีใช้อย่างละเอียด ชี้ไปที่หน้าที่ถูกต้อง
2. ถามหาปั๊ม → แนะนำเปิดหน้า "ปั๊ม" แล้วค้นหา ถามว่าต้องการนำทางไหม
3. แจ้งสถานะน้ำมัน → ถามยืนยัน แล้วใส่ [FUEL_REPORT:{"brand":"ชื่อ","fuel_type":"ประเภท","status":"สถานะ"}]
4. รายงานเหตุ → แนะนำไปหน้า "รายงาน" เลือกประเภท
5. ถามเรื่องทั่วไป → ตอบอย่างเป็นมิตร ช่วยเหลือ
6. ไม่รู้คำตอบ → บอกตรงๆว่าไม่รู้ แต่แนะนำทางอื่น
7. ถามเรื่อง GPS → อธิบายว่าต้องเปิด GPS เพื่อรายงาน กดอนุญาตในเบราว์เซอร์
8. ถามเรื่องสมัครสมาชิก → อธิบายว่าใช้ชื่อเล่น/Google/LINE ได้ ฟรี ได้คะแนนดาว
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
