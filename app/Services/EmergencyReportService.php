<?php

namespace App\Services;

use App\Models\Incident;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * น้องหญิง Emergency Reporter
 *
 * เมื่อเหตุการณ์ถึงระดับ critical หรือมีผู้ยืนยัน 3+ คน
 * น้องหญิงจะรายงานไปยังทุกช่องทางอัตโนมัติ
 */
class EmergencyReportService
{
    private const CRITICAL_THRESHOLD = 3; // จำนวนคนยืนยันก่อนแจ้งฉุกเฉิน
    private const COOLDOWN_MINUTES = 30; // ป้องกันแจ้งซ้ำ

    /**
     * ตรวจสอบและรายงานเหตุฉุกเฉินอัตโนมัติ
     */
    public function evaluate(Incident $incident): array
    {
        $actions = [];

        // เงื่อนไขที่ต้องรายงาน
        $shouldReport = false;
        $reason = '';

        if ($incident->severity === 'critical') {
            $shouldReport = true;
            $reason = 'ระดับวิกฤต';
        } elseif ($incident->has_injuries) {
            $shouldReport = true;
            $reason = 'มีผู้บาดเจ็บ';
        } elseif ($incident->confirmation_count >= self::CRITICAL_THRESHOLD && $incident->severity !== 'low') {
            $shouldReport = true;
            $reason = "{$incident->confirmation_count} คนยืนยัน";
        }

        if (!$shouldReport) {
            return $actions;
        }

        // เช็ค cooldown ป้องกันแจ้งซ้ำ
        $cacheKey = "emergency_reported_{$incident->id}";
        if (Cache::has($cacheKey)) {
            return ['already_reported' => true];
        }

        // สร้างข้อความรายงานจากน้องหญิง
        $message = $this->buildYingReport($incident, $reason);

        // 1. Discord Emergency Channel
        $discordResult = $this->sendToDiscord($incident, $message);
        if ($discordResult) $actions[] = 'discord';

        // 2. LINE Emergency Group
        $lineResult = $this->sendToLine($incident, $message);
        if ($lineResult) $actions[] = 'line';

        // 3. Webhook (สำหรับศูนย์กู้ภัยที่มี endpoint)
        $webhookResult = $this->sendToWebhook($incident, $message);
        if ($webhookResult) $actions[] = 'webhook';

        // Mark as reported
        Cache::put($cacheKey, true, now()->addMinutes(self::COOLDOWN_MINUTES));

        // Update incident
        $incident->update(['emergency_notified' => true]);

        Log::info('น้องหญิง emergency report sent', [
            'incident_id' => $incident->id,
            'reason' => $reason,
            'channels' => $actions,
        ]);

        return $actions;
    }

    /**
     * สร้างข้อความรายงานจากน้องหญิง
     */
    private function buildYingReport(Incident $incident, string $reason): array
    {
        $emoji = Incident::CATEGORY_EMOJI[$incident->category] ?? '⚠️';
        $catLabel = Incident::CATEGORY_LABELS[$incident->category] ?? $incident->category;
        $sevLabel = Incident::SEVERITY_LABELS[$incident->severity ?? 'medium'] ?? 'ปานกลาง';
        $time = now()->format('H:i น.');
        $date = now()->format('d/m/Y');

        $mapUrl = "https://www.google.com/maps?q={$incident->latitude},{$incident->longitude}";
        $appUrl = config('app.url', 'https://thaihelp.xman4289.com');

        // สร้าง text version
        $text = "🚨 น้องหญิงรายงานเหตุฉุกเฉิน 🚨\n"
            . "━━━━━━━━━━━━━━━━━\n"
            . "{$emoji} {$catLabel} — {$sevLabel}\n"
            . "📌 {$incident->title}\n"
            . ($incident->location_name ? "📍 {$incident->location_name}\n" : '')
            . ($incident->road_name ? "🛣️ {$incident->road_name}\n" : '')
            . ($incident->description ? "📝 {$incident->description}\n" : '')
            . ($incident->has_injuries ? "🚑 มีผู้บาดเจ็บ!\n" : '')
            . "━━━━━━━━━━━━━━━━━\n"
            . "👥 {$incident->confirmation_count} คนยืนยัน ({$reason})\n"
            . "🕐 {$time} {$date}\n"
            . "🗺️ พิกัด: {$mapUrl}\n"
            . "━━━━━━━━━━━━━━━━━\n"
            . "รายงานโดย: น้องหญิง AI — ThaiHelp\n"
            . "🌐 {$appUrl}";

        // สร้าง Discord embed
        $embed = [
            'title' => "{$emoji} {$catLabel} — {$incident->title}",
            'description' => $incident->description ?? 'ไม่มีรายละเอียดเพิ่มเติม',
            'color' => $this->severityToColor($incident->severity),
            'fields' => [
                ['name' => '📊 ระดับ', 'value' => $sevLabel, 'inline' => true],
                ['name' => '👥 ยืนยัน', 'value' => "{$incident->confirmation_count} คน", 'inline' => true],
                ['name' => '⏰ เวลา', 'value' => "{$time} {$date}", 'inline' => true],
            ],
            'footer' => [
                'text' => "รายงานโดย น้องหญิง AI — ThaiHelp | {$reason}",
                'icon_url' => "{$appUrl}/images/ying-avatar.png",
            ],
            'timestamp' => now()->toIso8601String(),
        ];

        if ($incident->location_name) {
            $embed['fields'][] = ['name' => '📍 สถานที่', 'value' => $incident->location_name, 'inline' => false];
        }
        if ($incident->road_name) {
            $embed['fields'][] = ['name' => '🛣️ ถนน', 'value' => $incident->road_name, 'inline' => true];
        }
        if ($incident->has_injuries) {
            $embed['fields'][] = ['name' => '🚑 ผู้บาดเจ็บ', 'value' => '⚠️ มีรายงานผู้บาดเจ็บ', 'inline' => false];
        }

        $embed['fields'][] = ['name' => '🗺️ แผนที่', 'value' => "[เปิด Google Maps]({$mapUrl})", 'inline' => false];

        // Photos
        if ($incident->image_url) {
            $embed['image'] = ['url' => $incident->image_url];
        }

        return [
            'text' => $text,
            'embed' => $embed,
            'incident' => $incident,
        ];
    }

    /**
     * ส่งไป Discord Emergency Channel
     */
    private function sendToDiscord(Incident $incident, array $message): bool
    {
        try {
            $webhookUrl = SiteSetting::get('discord_emergency_webhook');
            if (!$webhookUrl) {
                // Fallback: use main channel webhook
                $webhookUrl = SiteSetting::get('discord_webhook_url');
            }
            if (!$webhookUrl) return false;

            $payload = [
                'username' => 'น้องหญิง 🚨 Emergency',
                'avatar_url' => config('app.url', 'https://thaihelp.xman4289.com') . '/images/ying-avatar.png',
                'content' => $incident->has_injuries
                    ? '@everyone 🚨 **เหตุฉุกเฉิน — มีผู้บาดเจ็บ!**'
                    : ($incident->severity === 'critical' ? '@here 🚨 **เหตุฉุกเฉินระดับวิกฤต**' : ''),
                'embeds' => [$message['embed']],
            ];

            // Add emergency phone buttons
            if ($incident->has_injuries || $incident->severity === 'critical') {
                $payload['content'] .= "\n📞 **โทรแจ้ง:** `1669` (การแพทย์ฉุกเฉิน) | `191` (ตำรวจ) | `199` (ดับเพลิง)";
            }

            $response = Http::post($webhookUrl, $payload);
            return $response->successful();
        } catch (\Exception $e) {
            Log::warning('Discord emergency report failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * ส่งไป LINE Group (ศูนย์กู้ภัย)
     */
    private function sendToLine(Incident $incident, array $message): bool
    {
        try {
            $channelToken = SiteSetting::get('line_emergency_token');
            $groupId = SiteSetting::get('line_emergency_group_id');
            if (!$channelToken || !$groupId) return false;

            $mapUrl = "https://www.google.com/maps?q={$incident->latitude},{$incident->longitude}";

            $payload = [
                'to' => $groupId,
                'messages' => [
                    [
                        'type' => 'flex',
                        'altText' => "🚨 น้องหญิงรายงานเหตุฉุกเฉิน: {$incident->title}",
                        'contents' => [
                            'type' => 'bubble',
                            'size' => 'mega',
                            'header' => [
                                'type' => 'box',
                                'layout' => 'vertical',
                                'backgroundColor' => '#dc2626',
                                'contents' => [
                                    ['type' => 'text', 'text' => '🚨 เหตุฉุกเฉิน', 'color' => '#ffffff', 'size' => 'lg', 'weight' => 'bold'],
                                ],
                            ],
                            'body' => [
                                'type' => 'box',
                                'layout' => 'vertical',
                                'spacing' => 'md',
                                'contents' => [
                                    ['type' => 'text', 'text' => $incident->title, 'size' => 'lg', 'weight' => 'bold', 'wrap' => true],
                                    ['type' => 'text', 'text' => $incident->description ?? '-', 'size' => 'sm', 'color' => '#666666', 'wrap' => true],
                                    ['type' => 'separator'],
                                    ['type' => 'text', 'text' => "📍 " . ($incident->location_name ?? 'ดูแผนที่'), 'size' => 'sm', 'wrap' => true],
                                    ['type' => 'text', 'text' => "👥 {$incident->confirmation_count} คนยืนยัน", 'size' => 'sm'],
                                    ['type' => 'text', 'text' => "⏰ " . now()->format('H:i d/m/Y'), 'size' => 'xs', 'color' => '#999999'],
                                ],
                            ],
                            'footer' => [
                                'type' => 'box',
                                'layout' => 'vertical',
                                'spacing' => 'sm',
                                'contents' => [
                                    [
                                        'type' => 'button',
                                        'style' => 'primary',
                                        'color' => '#3b82f6',
                                        'action' => ['type' => 'uri', 'label' => '🗺️ เปิดแผนที่', 'uri' => $mapUrl],
                                    ],
                                    [
                                        'type' => 'button',
                                        'style' => 'secondary',
                                        'action' => ['type' => 'uri', 'label' => '📞 โทร 1669', 'uri' => 'tel:1669'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ];

            $response = Http::withToken($channelToken)
                ->post('https://api.line.me/v2/bot/message/push', $payload);

            return $response->successful();
        } catch (\Exception $e) {
            Log::warning('LINE emergency report failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * ส่งไป Webhook ภายนอก (ศูนย์กู้ภัยที่มี endpoint)
     */
    private function sendToWebhook(Incident $incident, array $message): bool
    {
        try {
            $webhookUrl = SiteSetting::get('emergency_webhook_url');
            if (!$webhookUrl) return false;

            $payload = [
                'source' => 'ThaiHelp',
                'reporter' => 'น้องหญิง AI',
                'type' => 'emergency_incident',
                'incident' => [
                    'id' => $incident->id,
                    'category' => $incident->category,
                    'category_label' => Incident::CATEGORY_LABELS[$incident->category] ?? $incident->category,
                    'title' => $incident->title,
                    'description' => $incident->description,
                    'severity' => $incident->severity,
                    'latitude' => $incident->latitude,
                    'longitude' => $incident->longitude,
                    'location_name' => $incident->location_name,
                    'road_name' => $incident->road_name,
                    'has_injuries' => $incident->has_injuries,
                    'confirmation_count' => $incident->confirmation_count,
                    'photos' => $incident->photos,
                    'image_url' => $incident->image_url,
                    'reported_at' => $incident->created_at->toIso8601String(),
                    'map_url' => "https://www.google.com/maps?q={$incident->latitude},{$incident->longitude}",
                ],
                'timestamp' => now()->toIso8601String(),
            ];

            $response = Http::timeout(10)->post($webhookUrl, $payload);
            return $response->successful();
        } catch (\Exception $e) {
            Log::warning('Emergency webhook failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Convert severity to Discord embed color
     */
    private function severityToColor(string $severity): int
    {
        return match ($severity) {
            'critical' => 0xdc2626,
            'high' => 0xf97316,
            'medium' => 0xeab308,
            'low' => 0x22c55e,
            default => 0xeab308,
        };
    }
}
