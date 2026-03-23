<?php

namespace App\Http\Controllers;

use App\Models\FuelReport;
use App\Models\Incident;
use App\Models\SiteSetting;
use App\Models\StationReport;
use App\Services\GooglePlacesService;
use App\Services\GroqAIService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DiscordInteractionController extends Controller
{
    /**
     * Handle incoming Discord interaction.
     */
    public function handle(Request $request): JsonResponse
    {
        // Verify Discord signature
        if (!$this->verifySignature($request)) {
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $type = $request->input('type');

        // Type 1: PING - respond with PONG
        if ($type === 1) {
            return response()->json(['type' => 1]);
        }

        // Type 2: APPLICATION_COMMAND
        if ($type === 2) {
            return $this->handleCommand($request);
        }

        return response()->json(['error' => 'Unknown interaction type'], 400);
    }

    /**
     * Verify Discord Ed25519 signature.
     */
    private function verifySignature(Request $request): bool
    {
        $publicKey = SiteSetting::get('discord_public_key');

        if (empty($publicKey)) {
            Log::warning('Discord public key not configured');
            return false;
        }

        $signature = $request->header('X-Signature-Ed25519');
        $timestamp = $request->header('X-Signature-Timestamp');

        if (empty($signature) || empty($timestamp)) {
            return false;
        }

        $body = $request->getContent();
        $message = $timestamp . $body;

        try {
            $binaryKey = sodium_hex2bin($publicKey);
            $binarySig = sodium_hex2bin($signature);

            return sodium_crypto_sign_verify_detached($binarySig, $message, $binaryKey);
        } catch (\Exception $e) {
            Log::error('Discord signature verification failed', ['message' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Route slash commands to their handlers.
     */
    private function handleCommand(Request $request): JsonResponse
    {
        $data = $request->input('data', []);
        $commandName = $data['name'] ?? '';
        $options = $this->parseOptions($data['options'] ?? []);

        $user = $request->input('member.user', $request->input('user', []));
        $username = $user['username'] ?? 'ผู้ใช้ Discord';

        return match ($commandName) {
            'incident' => $this->handleIncident($options, $username),
            'stations' => $this->handleStations($options),
            'fuel' => $this->handleFuel($options),
            'chat' => $this->handleChat($options, $username),
            'help' => $this->handleHelp(),
            'status' => $this->handleStatus(),
            default => $this->reply('ไม่รู้จักคำสั่งนี้ค่ะ ลอง `/help` ดูนะคะ'),
        };
    }

    /**
     * Parse slash command options into a key-value array.
     */
    private function parseOptions(array $options): array
    {
        $parsed = [];
        foreach ($options as $option) {
            $parsed[$option['name']] = $option['value'] ?? null;
        }
        return $parsed;
    }

    /**
     * /incident - Create a new incident from Discord.
     */
    private function handleIncident(array $options, string $username): JsonResponse
    {
        $category = $options['category'] ?? 'other';
        $title = $options['title'] ?? 'ไม่ระบุ';
        $description = $options['description'] ?? null;
        $latitude = $options['latitude'] ?? null;
        $longitude = $options['longitude'] ?? null;

        try {
            $incident = Incident::create([
                'category' => $category,
                'title' => $title,
                'description' => $description
                    ? "[Discord: {$username}] {$description}"
                    : "[แจ้งผ่าน Discord โดย {$username}]",
                'latitude' => $latitude,
                'longitude' => $longitude,
                'is_active' => true,
                'expires_at' => now()->addHours(24),
            ]);

            $emoji = Incident::CATEGORY_EMOJI[$category] ?? '⚠️';
            $label = Incident::CATEGORY_LABELS[$category] ?? $category;

            $fields = [
                ['name' => '📋 ประเภท', 'value' => "{$emoji} {$label}", 'inline' => true],
                ['name' => '🆔 ID', 'value' => "#{$incident->id}", 'inline' => true],
                ['name' => '👤 แจ้งโดย', 'value' => $username, 'inline' => true],
            ];

            if ($latitude && $longitude) {
                $mapsUrl = "https://www.google.com/maps?q={$latitude},{$longitude}";
                $fields[] = ['name' => '📍 พิกัด', 'value' => "[{$latitude}, {$longitude}]({$mapsUrl})", 'inline' => false];
            }

            if ($description) {
                $fields[] = ['name' => '📝 รายละเอียด', 'value' => mb_substr($description, 0, 1024), 'inline' => false];
            }

            return $this->replyEmbed(
                title: "✅ แจ้งเหตุสำเร็จ: {$title}",
                description: "เหตุการณ์ถูกบันทึกในระบบ ThaiHelp เรียบร้อยค่ะ",
                color: 0x2ECC71,
                fields: $fields,
            );
        } catch (\Exception $e) {
            Log::error('Discord incident creation failed', ['message' => $e->getMessage()]);
            return $this->reply('❌ เกิดข้อผิดพลาดในการแจ้งเหตุค่ะ กรุณาลองใหม่อีกครั้งนะคะ', ephemeral: true);
        }
    }

    /**
     * /stations - Search nearby gas stations.
     */
    private function handleStations(array $options): JsonResponse
    {
        $latitude = $options['latitude'] ?? 13.7563;
        $longitude = $options['longitude'] ?? 100.5018;
        $radius = ($options['radius'] ?? 5) * 1000; // Convert km to meters

        try {
            $placesService = app(GooglePlacesService::class);
            $stations = $placesService->searchNearby($latitude, $longitude, $radius);

            if (empty($stations)) {
                return $this->reply('🔍 ไม่พบปั๊มน้ำมันในบริเวณที่ค้นหาค่ะ ลองขยายรัศมีดูนะคะ');
            }

            $stationLines = [];
            $count = min(count($stations), 10);

            for ($i = 0; $i < $count; $i++) {
                $s = $stations[$i];
                $rating = $s['rating'] ? "⭐{$s['rating']}" : '';
                $open = $s['opening_hours'] === true ? '🟢' : ($s['opening_hours'] === false ? '🔴' : '⚪');
                $stationLines[] = "{$open} **{$s['name']}** ({$s['distance']} กม.) {$rating}\n┗ {$s['vicinity']}";
            }

            $mapsUrl = "https://www.google.com/maps?q={$latitude},{$longitude}";

            return $this->replyEmbed(
                title: "⛽ ปั๊มน้ำมันใกล้เคียง ({$count} แห่ง)",
                description: implode("\n\n", $stationLines),
                color: 0x2ECC71,
                fields: [
                    ['name' => '📍 ค้นหาจากพิกัด', 'value' => "[{$latitude}, {$longitude}]({$mapsUrl})", 'inline' => true],
                    ['name' => '📏 รัศมี', 'value' => ($radius / 1000) . ' กม.', 'inline' => true],
                ],
            );
        } catch (\Exception $e) {
            Log::error('Discord stations search failed', ['message' => $e->getMessage()]);
            return $this->reply('❌ ไม่สามารถค้นหาปั๊มน้ำมันได้ค่ะ กรุณาลองใหม่', ephemeral: true);
        }
    }

    /**
     * /fuel - Check community fuel status reports.
     */
    private function handleFuel(array $options): JsonResponse
    {
        $fuelType = $options['type'] ?? null;

        try {
            $query = FuelReport::with('stationReport')
                ->where('created_at', '>=', now()->subDays(7))
                ->orderByDesc('created_at');

            if ($fuelType) {
                $query->where('fuel_type', $fuelType);
            }

            $reports = $query->limit(15)->get();

            if ($reports->isEmpty()) {
                $typeName = $fuelType ? (FuelReport::FUEL_LABELS[$fuelType] ?? $fuelType) : 'ทุกประเภท';
                return $this->reply("🔍 ไม่มีรายงานน้ำมัน ({$typeName}) ในช่วง 7 วันที่ผ่านมาค่ะ");
            }

            $statusEmoji = [
                'available' => '🟢',
                'low' => '🟡',
                'empty' => '🔴',
                'unknown' => '⚪',
            ];

            $lines = [];
            foreach ($reports as $report) {
                $emoji = $statusEmoji[$report->status] ?? '⚪';
                $fuelLabel = FuelReport::FUEL_LABELS[$report->fuel_type] ?? $report->fuel_type;
                $statusLabel = FuelReport::STATUS_LABELS[$report->status] ?? $report->status;
                $stationName = $report->stationReport?->station_name ?? 'ไม่ระบุ';
                $price = $report->price ? " ฿{$report->price}" : '';
                $time = $report->created_at->diffForHumans();

                $lines[] = "{$emoji} **{$fuelLabel}**: {$statusLabel}{$price}\n┗ 📍 {$stationName} ({$time})";
            }

            $typeName = $fuelType ? (FuelReport::FUEL_LABELS[$fuelType] ?? $fuelType) : 'ทุกประเภท';

            return $this->replyEmbed(
                title: "⛽ สถานะน้ำมัน: {$typeName}",
                description: implode("\n\n", $lines),
                color: 0xF39C12,
                fields: [
                    ['name' => '📊 จำนวนรายงาน', 'value' => (string) $reports->count(), 'inline' => true],
                    ['name' => '📅 ช่วงเวลา', 'value' => '7 วันล่าสุด', 'inline' => true],
                ],
            );
        } catch (\Exception $e) {
            Log::error('Discord fuel check failed', ['message' => $e->getMessage()]);
            return $this->reply('❌ ไม่สามารถดึงข้อมูลน้ำมันได้ค่ะ', ephemeral: true);
        }
    }

    /**
     * /chat - Chat with the AI assistant.
     */
    private function handleChat(array $options, string $username): JsonResponse
    {
        $message = $options['message'] ?? '';

        if (empty($message)) {
            return $this->reply('กรุณาพิมพ์ข้อความที่ต้องการถามด้วยนะคะ 💬');
        }

        try {
            $groq = app(GroqAIService::class);

            if (!$groq->isAvailable()) {
                return $this->reply('❌ ระบบ AI ยังไม่พร้อมใช้งานค่ะ กรุณาตั้งค่า API Key ก่อนนะคะ', ephemeral: true);
            }

            $aiResponse = $groq->chat([
                ['role' => 'user', 'content' => $message],
            ]);

            return $this->replyEmbed(
                title: '💬 น้องหญิง AI ตอบ',
                description: mb_substr($aiResponse, 0, 4096),
                color: 0xE91E63,
                fields: [
                    ['name' => '❓ คำถามจาก', 'value' => "**{$username}**: {$message}", 'inline' => false],
                ],
            );
        } catch (\Exception $e) {
            Log::error('Discord chat failed', ['message' => $e->getMessage()]);
            return $this->reply('❌ น้องหญิงตอบไม่ได้ตอนนี้ค่ะ ลองใหม่อีกครั้งนะคะ', ephemeral: true);
        }
    }

    /**
     * /help - Show available commands.
     */
    private function handleHelp(): JsonResponse
    {
        $commands = [
            '`/incident` - 🚗 แจ้งเหตุการณ์ผิดปกติบนท้องถนน',
            '`/stations` - ⛽ ค้นหาปั๊มน้ำมันใกล้เคียง',
            '`/fuel` - 🛢️ เช็คสถานะน้ำมันจากรายงานชุมชน',
            '`/chat` - 💬 คุยกับน้องหญิง AI ผู้ช่วยการเดินทาง',
            '`/status` - 📊 แสดงสถานะระบบ ThaiHelp',
            '`/help` - ❓ แสดงรายการคำสั่งนี้',
        ];

        return $this->replyEmbed(
            title: '🇹🇭 ThaiHelp Bot - คำสั่งทั้งหมด',
            description: implode("\n\n", $commands),
            color: 0x3498DB,
            fields: [
                [
                    'name' => '🌐 เว็บไซต์',
                    'value' => config('app.url', 'https://thaihelp.app'),
                    'inline' => true,
                ],
                [
                    'name' => '💡 เกี่ยวกับ',
                    'value' => 'ระบบแจ้งเหตุถนนไทย & ค้นหาปั๊มน้ำมัน',
                    'inline' => true,
                ],
            ],
        );
    }

    /**
     * /status - Show system status.
     */
    private function handleStatus(): JsonResponse
    {
        try {
            $activeIncidents = Incident::active()->count();
            $totalIncidents = Incident::count();
            $totalReports = StationReport::count();
            $recentReports = StationReport::where('created_at', '>=', now()->subDay())->count();
            $fuelReports = FuelReport::where('created_at', '>=', now()->subDays(7))->count();

            return $this->replyEmbed(
                title: '📊 สถานะระบบ ThaiHelp',
                description: '🟢 ระบบทำงานปกติ',
                color: 0x2ECC71,
                fields: [
                    ['name' => '🚨 เหตุการณ์ที่ active', 'value' => (string) $activeIncidents, 'inline' => true],
                    ['name' => '📈 เหตุการณ์ทั้งหมด', 'value' => (string) $totalIncidents, 'inline' => true],
                    ['name' => '⛽ รายงานปั๊มทั้งหมด', 'value' => (string) $totalReports, 'inline' => true],
                    ['name' => '📅 รายงานวันนี้', 'value' => (string) $recentReports, 'inline' => true],
                    ['name' => '🛢️ รายงานน้ำมัน (7 วัน)', 'value' => (string) $fuelReports, 'inline' => true],
                    ['name' => '🕐 เวลาเซิร์ฟเวอร์', 'value' => now()->format('d/m/Y H:i:s'), 'inline' => true],
                ],
            );
        } catch (\Exception $e) {
            Log::error('Discord status check failed', ['message' => $e->getMessage()]);
            return $this->reply('❌ ไม่สามารถดึงสถานะระบบได้ค่ะ', ephemeral: true);
        }
    }

    /**
     * Return a simple text reply.
     */
    private function reply(string $content, bool $ephemeral = false): JsonResponse
    {
        return response()->json([
            'type' => 4, // CHANNEL_MESSAGE_WITH_SOURCE
            'data' => [
                'content' => $content,
                'flags' => $ephemeral ? 64 : 0,
            ],
        ]);
    }

    /**
     * Return a reply with a rich embed.
     */
    private function replyEmbed(
        string $title,
        string $description = '',
        int $color = 0x3498DB,
        array $fields = [],
        bool $ephemeral = false,
    ): JsonResponse {
        $siteUrl = config('app.url', 'https://thaihelp.app');
        $iconUrl = SiteSetting::get('site_icon_url', "{$siteUrl}/images/icon.png");

        $embed = [
            'title' => $title,
            'color' => $color,
            'timestamp' => now()->toIso8601String(),
            'footer' => [
                'text' => 'ThaiHelp Bot 🇹🇭',
                'icon_url' => $iconUrl,
            ],
        ];

        if ($description) {
            $embed['description'] = $description;
        }

        if (!empty($fields)) {
            $embed['fields'] = $fields;
        }

        $thumbnailUrl = SiteSetting::get('site_thumbnail_url', $iconUrl);
        if ($thumbnailUrl) {
            $embed['thumbnail'] = ['url' => $thumbnailUrl];
        }

        return response()->json([
            'type' => 4,
            'data' => [
                'embeds' => [$embed],
                'flags' => $ephemeral ? 64 : 0,
            ],
        ]);
    }
}
