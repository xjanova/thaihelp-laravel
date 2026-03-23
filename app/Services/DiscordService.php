<?php

namespace App\Services;

use App\Models\FuelReport;
use App\Models\Incident;
use App\Models\StationReport;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DiscordService
{
    private const API = 'https://discord.com/api/v10';

    private const CATEGORY_COLORS = [
        'accident' => 0xFF0000,
        'flood' => 0x3498DB,
        'roadblock' => 0xE67E22,
        'checkpoint' => 0x9B59B6,
        'construction' => 0xF1C40F,
        'other' => 0x95A5A6,
    ];

    private const STATUS_EMOJI = [
        'available' => '🟢',
        'low' => '🟡',
        'empty' => '🔴',
        'unknown' => '⚪',
    ];

    private function token(): string
    {
        return config('services.discord.bot_token', '');
    }

    private function appId(): string
    {
        return config('services.discord.application_id', '');
    }

    private function notifyChannel(): string
    {
        return config('services.discord.notification_channel_id', '');
    }

    private function adminChannel(): string
    {
        return config('services.discord.admin_channel_id', '');
    }

    private function webhookUrl(): string
    {
        return config('services.discord.webhook_url', '');
    }

    public function isConfigured(): bool
    {
        return !empty($this->token()) && !empty($this->appId());
    }

    /**
     * Send message to webhook.
     */
    public function sendWebhook(string $content, array $embeds = []): bool
    {
        if (empty($this->webhookUrl())) {
            return false;
        }

        try {
            $payload = array_filter([
                'content' => $content ?: null,
                'embeds' => $embeds ?: null,
            ]);

            return Http::post($this->webhookUrl(), $payload)->successful();
        } catch (\Exception $e) {
            Log::error('Discord webhook failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Send message to channel via Bot API.
     */
    public function sendChannelMessage(string $channelId, string $content = '', array $embeds = []): bool
    {
        if (empty($this->token()) || empty($channelId)) {
            return false;
        }

        try {
            $payload = array_filter([
                'content' => $content ?: null,
                'embeds' => $embeds ?: null,
            ]);

            return Http::withHeaders([
                'Authorization' => "Bot {$this->token()}",
            ])->post(self::API . "/channels/{$channelId}/messages", $payload)->successful();
        } catch (\Exception $e) {
            Log::error('Discord message failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Register global slash commands.
     */
    public function registerCommands(): array
    {
        $commands = [
            [
                'name' => 'incident',
                'description' => 'แจ้งเหตุการณ์ผิดปกติบนท้องถนน',
                'options' => [
                    ['name' => 'category', 'description' => 'ประเภท', 'type' => 3, 'required' => true, 'choices' => [
                        ['name' => '🚗 อุบัติเหตุ', 'value' => 'accident'],
                        ['name' => '🌊 น้ำท่วม', 'value' => 'flood'],
                        ['name' => '🚧 ถนนปิด', 'value' => 'roadblock'],
                        ['name' => '👮 ด่านตรวจ', 'value' => 'checkpoint'],
                        ['name' => '🏗️ ก่อสร้าง', 'value' => 'construction'],
                        ['name' => '📌 อื่นๆ', 'value' => 'other'],
                    ]],
                    ['name' => 'title', 'description' => 'หัวข้อ', 'type' => 3, 'required' => true],
                    ['name' => 'description', 'description' => 'รายละเอียด', 'type' => 3, 'required' => false],
                ],
            ],
            [
                'name' => 'stations',
                'description' => 'ค้นหาปั๊มน้ำมันใกล้เคียง',
                'options' => [
                    ['name' => 'latitude', 'description' => 'ละติจูด (default: กรุงเทพ)', 'type' => 10, 'required' => false],
                    ['name' => 'longitude', 'description' => 'ลองจิจูด', 'type' => 10, 'required' => false],
                ],
            ],
            [
                'name' => 'fuel',
                'description' => 'เช็คสถานะน้ำมันจากรายงานชุมชน',
                'options' => [
                    ['name' => 'type', 'description' => 'ประเภทน้ำมัน', 'type' => 3, 'required' => false, 'choices' => [
                        ['name' => 'แก๊สโซฮอล์ 95', 'value' => 'gasohol95'],
                        ['name' => 'แก๊สโซฮอล์ 91', 'value' => 'gasohol91'],
                        ['name' => 'ดีเซล', 'value' => 'diesel'],
                        ['name' => 'ดีเซล B7', 'value' => 'diesel_b7'],
                        ['name' => 'E20', 'value' => 'e20'],
                        ['name' => 'NGV', 'value' => 'ngv'],
                        ['name' => 'LPG', 'value' => 'lpg'],
                    ]],
                ],
            ],
            [
                'name' => 'chat',
                'description' => 'คุยกับน้องหญิง AI ผู้ช่วยการเดินทาง',
                'options' => [
                    ['name' => 'message', 'description' => 'ข้อความ', 'type' => 3, 'required' => true],
                ],
            ],
            ['name' => 'help', 'description' => 'แสดงรายการคำสั่งทั้งหมดของ ThaiHelp Bot'],
            ['name' => 'status', 'description' => 'แสดงสถานะระบบ ThaiHelp'],
        ];

        $response = Http::withHeaders([
            'Authorization' => "Bot {$this->token()}",
        ])->put(self::API . "/applications/{$this->appId()}/commands", $commands);

        if ($response->failed()) {
            throw new \RuntimeException("Failed to register commands: {$response->body()}");
        }

        return $response->json();
    }

    /**
     * Set the interactions endpoint URL in Discord.
     */
    public function setInteractionsEndpoint(string $url): bool
    {
        $response = Http::withHeaders([
            'Authorization' => "Bot {$this->token()}",
        ])->patch(self::API . "/applications/{$this->appId()}", [
            'interactions_endpoint_url' => $url,
        ]);

        return $response->successful();
    }

    /**
     * Create a webhook in a channel.
     */
    public function createWebhook(string $channelId, string $name = 'ThaiHelp Bot'): ?string
    {
        $response = Http::withHeaders([
            'Authorization' => "Bot {$this->token()}",
        ])->post(self::API . "/channels/{$channelId}/webhooks", [
            'name' => $name,
        ]);

        if ($response->successful()) {
            return $response->json('url');
        }

        return null;
    }

    /**
     * Get guild channels.
     */
    public function getGuildChannels(string $guildId): array
    {
        $response = Http::withHeaders([
            'Authorization' => "Bot {$this->token()}",
        ])->get(self::API . "/guilds/{$guildId}/channels");

        return $response->successful() ? $response->json() : [];
    }

    /**
     * Create channels in a guild.
     */
    public function createChannel(string $guildId, string $name, int $type = 0, ?string $parentId = null): ?array
    {
        $payload = ['name' => $name, 'type' => $type];
        if ($parentId) {
            $payload['parent_id'] = $parentId;
        }

        $response = Http::withHeaders([
            'Authorization' => "Bot {$this->token()}",
        ])->post(self::API . "/guilds/{$guildId}/channels", $payload);

        return $response->successful() ? $response->json() : null;
    }

    /**
     * Notify new incident.
     */
    public function notifyNewIncident(Incident $incident): bool
    {
        if (!$this->isConfigured()) {
            return false;
        }

        $emoji = Incident::CATEGORY_EMOJI[$incident->category] ?? '⚠️';
        $label = Incident::CATEGORY_LABELS[$incident->category] ?? $incident->category;
        $color = self::CATEGORY_COLORS[$incident->category] ?? 0x95A5A6;

        $fields = [
            ['name' => '📋 ประเภท', 'value' => "{$emoji} {$label}", 'inline' => true],
        ];

        if ($incident->latitude && $incident->longitude) {
            $url = "https://www.google.com/maps?q={$incident->latitude},{$incident->longitude}";
            $fields[] = ['name' => '📍 พิกัด', 'value' => "[ดูแผนที่]({$url})", 'inline' => true];
        }

        $fields[] = ['name' => '🕐 เวลา', 'value' => $incident->created_at->format('d/m/Y H:i'), 'inline' => true];

        if ($incident->description) {
            $fields[] = ['name' => '📝 รายละเอียด', 'value' => mb_substr($incident->description, 0, 1024), 'inline' => false];
        }

        $embed = $this->embed("{$emoji} เหตุการณ์ใหม่: {$incident->title}", 'แจ้งเหตุการณ์ใหม่ในระบบ ThaiHelp', $color, $fields);

        $channelId = $this->notifyChannel();
        if ($channelId) {
            return $this->sendChannelMessage($channelId, '', [$embed]);
        }
        return $this->sendWebhook('', [$embed]);
    }

    /**
     * Notify new station report.
     */
    public function notifyNewStationReport(StationReport $report): bool
    {
        if (!$this->isConfigured()) {
            return false;
        }

        $report->loadMissing('fuelReports');

        $fuelLines = [];
        foreach ($report->fuelReports as $fuel) {
            $e = self::STATUS_EMOJI[$fuel->status] ?? '⚪';
            $fuelLines[] = "{$e} {$fuel->fuel_type}: {$fuel->status}" . ($fuel->price ? " ฿{$fuel->price}" : '');
        }

        $fields = [
            ['name' => '⛽ ปั๊ม', 'value' => $report->station_name ?: '-', 'inline' => true],
            ['name' => '🕐 เวลา', 'value' => $report->created_at->format('d/m/Y H:i'), 'inline' => true],
        ];

        if ($fuelLines) {
            $fields[] = ['name' => '⛽ สถานะน้ำมัน', 'value' => implode("\n", $fuelLines), 'inline' => false];
        }

        $embed = $this->embed("⛽ รายงานปั๊ม: {$report->station_name}", '', 0x2ECC71, $fields);

        $channelId = $this->notifyChannel();
        if ($channelId) {
            return $this->sendChannelMessage($channelId, '', [$embed]);
        }
        return $this->sendWebhook('', [$embed]);
    }

    /**
     * Admin alert.
     */
    public function notifyAdminAlert(string $title, string $message): bool
    {
        if (!$this->isConfigured()) {
            return false;
        }

        $embed = $this->embed("🔔 {$title}", $message, 0xE74C3C);
        $channelId = $this->adminChannel() ?: $this->notifyChannel();

        if ($channelId) {
            return $this->sendChannelMessage($channelId, '', [$embed]);
        }
        return $this->sendWebhook('', [$embed]);
    }

    /**
     * Build embed.
     */
    private function embed(string $title, string $desc = '', int $color = 0x3498DB, array $fields = []): array
    {
        return array_filter([
            'title' => $title,
            'description' => $desc ?: null,
            'color' => $color,
            'fields' => $fields ?: null,
            'timestamp' => now()->toIso8601String(),
            'footer' => ['text' => 'ThaiHelp Bot 🇹🇭'],
        ]);
    }
}
