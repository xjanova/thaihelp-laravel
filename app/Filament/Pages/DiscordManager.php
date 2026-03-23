<?php

namespace App\Filament\Pages;

use App\Services\DiscordService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DiscordManager extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'Discord Bot';

    protected static ?string $title = 'จัดการ Discord Bot';

    protected static ?string $slug = 'discord';

    protected static ?int $navigationSort = 101;

    protected static string $view = 'filament.pages.discord-manager';

    public array $botInfo = [];
    public array $commands = [];
    public array $channels = [];
    public bool $loading = false;

    public function mount(): void
    {
        $this->loadBotInfo();
        $this->loadChannels();
    }

    // ── Helpers ──────────────────────────────────────────────

    protected function discord(): DiscordService
    {
        return app(DiscordService::class);
    }

    protected function token(): string
    {
        return config('services.discord.bot_token', '');
    }

    protected function appId(): string
    {
        return config('services.discord.application_id', '');
    }

    protected function guildId(): string
    {
        return config('services.discord.guild_id', '');
    }

    // ── Data Loaders ────────────────────────────────────────

    public function loadBotInfo(): void
    {
        if (empty($this->token())) {
            $this->botInfo = [];
            $this->commands = [];
            return;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bot ' . $this->token(),
            ])->get('https://discord.com/api/v10/users/@me');

            $this->botInfo = $response->successful() ? $response->json() : [];
        } catch (\Exception $e) {
            Log::error('Discord bot info fetch failed', ['error' => $e->getMessage()]);
            $this->botInfo = [];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bot ' . $this->token(),
            ])->get("https://discord.com/api/v10/applications/{$this->appId()}/commands");

            $this->commands = $response->successful() ? $response->json() : [];
        } catch (\Exception $e) {
            Log::error('Discord commands fetch failed', ['error' => $e->getMessage()]);
            $this->commands = [];
        }
    }

    public function loadChannels(): void
    {
        if (empty($this->token()) || empty($this->guildId())) {
            $this->channels = [];
            return;
        }

        try {
            $this->channels = $this->discord()->getGuildChannels($this->guildId());
        } catch (\Exception $e) {
            Log::error('Discord channels fetch failed', ['error' => $e->getMessage()]);
            $this->channels = [];
        }
    }

    // ── Status Helpers (used by the blade view) ─────────────

    public function getIsConfiguredProperty(): bool
    {
        return $this->discord()->isConfigured();
    }

    public function getConfigValuesProperty(): array
    {
        $webhook = config('services.discord.webhook_url', '');
        return [
            'bot_token' => !empty($this->token()),
            'application_id' => config('services.discord.application_id', ''),
            'public_key' => !empty(config('services.discord.public_key', '')),
            'guild_id' => config('services.discord.guild_id', ''),
            'notification_channel' => config('services.discord.notification_channel_id', ''),
            'admin_channel' => config('services.discord.admin_channel_id', ''),
            'webhook_url' => $webhook ? (substr($webhook, 0, 40) . '...') : '',
            'webhook_set' => !empty($webhook),
        ];
    }

    public function getTextChannelsProperty(): array
    {
        // type 0 = text channels
        return collect($this->channels)
            ->filter(fn ($ch) => ($ch['type'] ?? -1) === 0)
            ->values()
            ->toArray();
    }

    // ── Actions ─────────────────────────────────────────────

    public function sendTestMessage(): void
    {
        $channelId = config('services.discord.notification_channel_id');
        if (empty($channelId)) {
            Notification::make()->title('ไม่ได้ตั้งค่า Notification Channel')->danger()->send();
            return;
        }

        $ok = $this->discord()->sendChannelMessage($channelId, '', [[
            'title' => 'ทดสอบระบบ',
            'description' => 'ข้อความทดสอบจาก ThaiHelp Admin Panel',
            'color' => 0x3498DB,
            'timestamp' => now()->toIso8601String(),
            'footer' => ['text' => 'ThaiHelp Bot'],
        ]]);

        if ($ok) {
            Notification::make()->title('ส่งข้อความทดสอบสำเร็จ')->success()->send();
        } else {
            Notification::make()->title('ส่งข้อความไม่สำเร็จ')->danger()->send();
        }
    }

    public function sendTestAdminAlert(): void
    {
        $ok = $this->discord()->notifyAdminAlert(
            'ทดสอบ Admin Alert',
            'ข้อความทดสอบจาก ThaiHelp Admin Panel — ' . now()->format('d/m/Y H:i:s'),
        );

        if ($ok) {
            Notification::make()->title('ส่ง Admin Alert สำเร็จ')->success()->send();
        } else {
            Notification::make()->title('ส่ง Admin Alert ไม่สำเร็จ')->danger()->send();
        }
    }

    public function registerSlashCommands(): void
    {
        try {
            $result = $this->discord()->registerCommands();
            $count = is_array($result) ? count($result) : 0;
            Notification::make()
                ->title("ลงทะเบียน Slash Commands สำเร็จ ({$count} คำสั่ง)")
                ->success()
                ->send();
            $this->loadBotInfo();
        } catch (\Exception $e) {
            Notification::make()
                ->title('ลงทะเบียน Commands ไม่สำเร็จ')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function setInteractionsEndpoint(): void
    {
        $url = rtrim(config('app.url'), '/') . '/discord/interactions';

        try {
            $ok = $this->discord()->setInteractionsEndpoint($url);
            if ($ok) {
                Notification::make()
                    ->title('ตั้ง Interactions Endpoint สำเร็จ')
                    ->body($url)
                    ->success()
                    ->send();
            } else {
                Notification::make()->title('ตั้ง Endpoint ไม่สำเร็จ')->danger()->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('ตั้ง Endpoint ไม่สำเร็จ')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function createWebhook(): void
    {
        $channelId = config('services.discord.notification_channel_id');
        if (empty($channelId)) {
            Notification::make()->title('ไม่ได้ตั้งค่า Notification Channel')->danger()->send();
            return;
        }

        try {
            $url = $this->discord()->createWebhook($channelId);
            if ($url) {
                Notification::make()
                    ->title('สร้าง Webhook สำเร็จ')
                    ->body('Webhook URL: ' . substr($url, 0, 50) . '...')
                    ->success()
                    ->send();
            } else {
                Notification::make()->title('สร้าง Webhook ไม่สำเร็จ')->danger()->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('สร้าง Webhook ไม่สำเร็จ')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function refreshData(): void
    {
        $this->loadBotInfo();
        $this->loadChannels();
        Notification::make()->title('รีเฟรชข้อมูลสำเร็จ')->success()->send();
    }
}
