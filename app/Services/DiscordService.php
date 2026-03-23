<?php

namespace App\Services;

use App\Models\FuelReport;
use App\Models\Incident;
use App\Models\SiteSetting;
use App\Models\StationReport;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DiscordService
{
    private const DISCORD_API_BASE = 'https://discord.com/api/v10';

    private const CATEGORY_COLORS = [
        'accident' => 0xFF0000,    // Red
        'flood' => 0x3498DB,       // Blue
        'roadblock' => 0xE67E22,   // Orange
        'checkpoint' => 0x9B59B6,  // Purple
        'construction' => 0xF1C40F, // Yellow
        'other' => 0x95A5A6,      // Grey
    ];

    private const STATUS_EMOJI = [
        'available' => '🟢',
        'low' => '🟡',
        'empty' => '🔴',
        'unknown' => '⚪',
    ];

    /**
     * Send a message to the configured Discord webhook.
     */
    public function sendWebhook(string $content, array $embeds = []): bool
    {
        $webhookUrl = SiteSetting::get('discord_webhook_url');

        if (empty($webhookUrl)) {
            Log::warning('Discord webhook URL not configured');
            return false;
        }

        try {
            $payload = ['content' => $content];

            if (!empty($embeds)) {
                $payload['embeds'] = $embeds;
            }

            $response = Http::post($webhookUrl, $payload);

            if ($response->failed()) {
                Log::error('Discord webhook failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Discord webhook exception', ['message' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Send a message to a specific Discord channel via Bot API.
     */
    public function sendChannelMessage(string $channelId, string $content, array $embeds = []): bool
    {
        $botToken = SiteSetting::get('discord_bot_token');

        if (empty($botToken)) {
            Log::warning('Discord bot token not configured');
            return false;
        }

        try {
            $payload = ['content' => $content];

            if (!empty($embeds)) {
                $payload['embeds'] = $embeds;
            }

            $response = Http::withHeaders([
                'Authorization' => "Bot {$botToken}",
            ])->post(self::DISCORD_API_BASE . "/channels/{$channelId}/messages", $payload);

            if ($response->failed()) {
                Log::error('Discord channel message failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'channel_id' => $channelId,
                ]);
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Discord channel message exception', ['message' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Send a rich embed notification for a new incident.
     */
    public function notifyNewIncident(Incident $incident): bool
    {
        $category = $incident->category;
        $emoji = Incident::CATEGORY_EMOJI[$category] ?? '⚠️';
        $label = Incident::CATEGORY_LABELS[$category] ?? $category;
        $color = self::CATEGORY_COLORS[$category] ?? 0x95A5A6;

        $fields = [
            [
                'name' => '📋 ประเภท',
                'value' => "{$emoji} {$label}",
                'inline' => true,
            ],
        ];

        if ($incident->latitude && $incident->longitude) {
            $mapsUrl = "https://www.google.com/maps?q={$incident->latitude},{$incident->longitude}";
            $fields[] = [
                'name' => '📍 พิกัด',
                'value' => "[{$incident->latitude}, {$incident->longitude}]({$mapsUrl})",
                'inline' => true,
            ];
        }

        $fields[] = [
            'name' => '🕐 เวลา',
            'value' => $incident->created_at->format('d/m/Y H:i'),
            'inline' => true,
        ];

        if ($incident->description) {
            $fields[] = [
                'name' => '📝 รายละเอียด',
                'value' => mb_substr($incident->description, 0, 1024),
                'inline' => false,
            ];
        }

        $embed = $this->buildEmbed(
            title: "{$emoji} เหตุการณ์ใหม่: {$incident->title}",
            description: "มีการแจ้งเหตุการณ์ใหม่เข้ามาในระบบ ThaiHelp",
            color: $color,
            fields: $fields,
        );

        if ($incident->image_url) {
            $embed['image'] = ['url' => $incident->image_url];
        }

        $channelId = SiteSetting::get('discord_notification_channel_id');

        if ($channelId) {
            return $this->sendChannelMessage($channelId, '', [$embed]);
        }

        return $this->sendWebhook('', [$embed]);
    }

    /**
     * Send a rich embed notification for a new station/fuel report.
     */
    public function notifyNewStationReport(StationReport $report): bool
    {
        $report->loadMissing('fuelReports');

        $fuelLines = [];
        foreach ($report->fuelReports as $fuel) {
            $statusEmoji = self::STATUS_EMOJI[$fuel->status] ?? '⚪';
            $fuelLabel = FuelReport::FUEL_LABELS[$fuel->fuel_type] ?? $fuel->fuel_type;
            $statusLabel = FuelReport::STATUS_LABELS[$fuel->status] ?? $fuel->status;
            $pricePart = $fuel->price ? " - ฿{$fuel->price}" : '';
            $fuelLines[] = "{$statusEmoji} {$fuelLabel}: {$statusLabel}{$pricePart}";
        }

        $fields = [
            [
                'name' => '⛽ ปั๊ม',
                'value' => $report->station_name ?: 'ไม่ระบุ',
                'inline' => true,
            ],
        ];

        if ($report->latitude && $report->longitude) {
            $mapsUrl = "https://www.google.com/maps?q={$report->latitude},{$report->longitude}";
            $fields[] = [
                'name' => '📍 พิกัด',
                'value' => "[{$report->latitude}, {$report->longitude}]({$mapsUrl})",
                'inline' => true,
            ];
        }

        $fields[] = [
            'name' => '🕐 เวลา',
            'value' => $report->created_at->format('d/m/Y H:i'),
            'inline' => true,
        ];

        if (!empty($fuelLines)) {
            $fields[] = [
                'name' => '⛽ สถานะน้ำมัน',
                'value' => implode("\n", $fuelLines),
                'inline' => false,
            ];
        }

        if ($report->note) {
            $fields[] = [
                'name' => '💬 หมายเหตุ',
                'value' => mb_substr($report->note, 0, 1024),
                'inline' => false,
            ];
        }

        $embed = $this->buildEmbed(
            title: "⛽ รายงานปั๊มน้ำมันใหม่: {$report->station_name}",
            description: "มีรายงานสถานะน้ำมันเข้ามาใหม่",
            color: 0x2ECC71, // Green
            fields: $fields,
        );

        $channelId = SiteSetting::get('discord_notification_channel_id');

        if ($channelId) {
            return $this->sendChannelMessage($channelId, '', [$embed]);
        }

        return $this->sendWebhook('', [$embed]);
    }

    /**
     * Send an admin alert embed.
     */
    public function notifyAdminAlert(string $title, string $message): bool
    {
        $embed = $this->buildEmbed(
            title: "🔔 {$title}",
            description: $message,
            color: 0xE74C3C, // Red
        );

        $channelId = SiteSetting::get('discord_admin_channel_id');

        if ($channelId) {
            return $this->sendChannelMessage($channelId, '', [$embed]);
        }

        return $this->sendWebhook('', [$embed]);
    }

    /**
     * Build a standard ThaiHelp embed structure.
     */
    private function buildEmbed(
        string $title,
        string $description = '',
        int $color = 0x3498DB,
        array $fields = [],
    ): array {
        $siteUrl = config('app.url', 'https://thaihelp.app');
        $iconUrl = SiteSetting::get('site_icon_url', "{$siteUrl}/images/icon.png");

        $embed = [
            'title' => $title,
            'color' => $color,
            'timestamp' => now()->toIso8601String(),
            'footer' => [
                'text' => 'ThaiHelp Bot 🇹🇭 | ระบบแจ้งเหตุถนนไทย',
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

        return $embed;
    }
}
