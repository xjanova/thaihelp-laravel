<?php

namespace App\Console\Commands;

use App\Services\DiscordService;
use Illuminate\Console\Command;

class DiscordSetupCommand extends Command
{
    protected $signature = 'discord:setup
        {--token= : Bot token (or set DISCORD_BOT_TOKEN in .env)}
        {--app-id= : Application ID (or set DISCORD_APPLICATION_ID in .env)}
        {--public-key= : Public key (or set DISCORD_PUBLIC_KEY in .env)}
        {--guild-id= : Guild/Server ID (or set DISCORD_GUILD_ID in .env)}
        {--skip-channels : Skip channel creation}
        {--skip-commands : Skip slash command registration}
        {--skip-endpoint : Skip interactions endpoint setup}';

    protected $description = 'Complete Discord bot setup: channels, webhook, commands, endpoint — all in one command';

    public function handle(): int
    {
        $this->info('');
        $this->info('╔══════════════════════════════════════════╗');
        $this->info('║   🤖 ThaiHelp Discord Bot Setup         ║');
        $this->info('╚══════════════════════════════════════════╝');
        $this->info('');

        // 1. Resolve credentials
        $token = $this->option('token') ?: config('services.discord.bot_token');
        $appId = $this->option('app-id') ?: config('services.discord.application_id');
        $publicKey = $this->option('public-key') ?: config('services.discord.public_key');
        $guildId = $this->option('guild-id') ?: config('services.discord.guild_id');

        if (empty($token) || empty($appId)) {
            $this->error('Bot token and Application ID are required.');
            $this->line('Set them in .env:');
            $this->line('  DISCORD_BOT_TOKEN=your_token');
            $this->line('  DISCORD_APPLICATION_ID=your_app_id');
            $this->line('');
            $this->line('Or pass as options:');
            $this->line('  php artisan discord:setup --token=xxx --app-id=xxx');
            return 1;
        }

        // Write to .env if provided as options
        if ($this->option('token') || $this->option('app-id') || $this->option('public-key') || $this->option('guild-id')) {
            $this->writeEnvValues([
                'DISCORD_BOT_TOKEN' => $this->option('token'),
                'DISCORD_APPLICATION_ID' => $this->option('app-id'),
                'DISCORD_PUBLIC_KEY' => $this->option('public-key'),
                'DISCORD_GUILD_ID' => $this->option('guild-id'),
            ]);
            $this->info('✓ .env updated with Discord credentials');

            // Refresh config
            config(['services.discord.bot_token' => $token]);
            config(['services.discord.application_id' => $appId]);
            if ($publicKey) {
                config(['services.discord.public_key' => $publicKey]);
            }
            if ($guildId) {
                config(['services.discord.guild_id' => $guildId]);
            }
        }

        $discord = app(DiscordService::class);

        $this->info("App ID:    {$appId}");
        $this->info("Token:     " . substr($token, 0, 20) . '...');
        $this->info("Guild ID:  " . ($guildId ?: '(not set)'));
        $this->info('');

        // 2. Setup channels
        if (!$this->option('skip-channels') && $guildId) {
            $this->setupChannels($discord, $guildId);
        }

        // 3. Register slash commands
        if (!$this->option('skip-commands')) {
            $this->registerCommands($discord);
        }

        // 4. Set interactions endpoint
        if (!$this->option('skip-endpoint')) {
            $this->setupEndpoint($discord);
        }

        $this->info('');
        $this->info('╔══════════════════════════════════════════╗');
        $this->info('║   ✅ Discord Bot Setup Complete!         ║');
        $this->info('╚══════════════════════════════════════════╝');
        $this->info('');

        return 0;
    }

    private function setupChannels(DiscordService $discord, string $guildId): void
    {
        $this->info('► Setting up channels...');

        $channels = $discord->getGuildChannels($guildId);

        if (empty($channels)) {
            $this->warn('  Could not fetch guild channels. Check bot permissions.');
            return;
        }

        // Find or create category
        $categoryId = null;
        foreach ($channels as $ch) {
            if ($ch['type'] === 4 && stripos($ch['name'], 'thaihelp') !== false) {
                $categoryId = $ch['id'];
                $this->info("  ✓ Found category: {$ch['name']} ({$ch['id']})");
                break;
            }
        }

        if (!$categoryId) {
            $category = $discord->createChannel($guildId, 'ThaiHelp', 4);
            if ($category) {
                $categoryId = $category['id'];
                $this->info("  ✓ Created category: ThaiHelp ({$categoryId})");
            }
        }

        // Find or create notification channel
        $notifyChannelId = config('services.discord.notification_channel_id');
        if (empty($notifyChannelId)) {
            $existing = collect($channels)->firstWhere('name', 'แจ้งเตือน');
            if ($existing) {
                $notifyChannelId = $existing['id'];
            } else {
                $ch = $discord->createChannel($guildId, 'แจ้งเตือน', 0, $categoryId);
                if ($ch) {
                    $notifyChannelId = $ch['id'];
                }
            }
            if ($notifyChannelId) {
                $this->writeEnvValues(['DISCORD_NOTIFICATION_CHANNEL_ID' => $notifyChannelId]);
                config(['services.discord.notification_channel_id' => $notifyChannelId]);
                $this->info("  ✓ Notification channel: #{$notifyChannelId}");
            }
        } else {
            $this->info("  ✓ Notification channel already set: {$notifyChannelId}");
        }

        // Find or create admin channel
        $adminChannelId = config('services.discord.admin_channel_id');
        if (empty($adminChannelId)) {
            $existing = collect($channels)->firstWhere('name', 'admin-alerts');
            if ($existing) {
                $adminChannelId = $existing['id'];
            } else {
                $ch = $discord->createChannel($guildId, 'admin-alerts', 0, $categoryId);
                if ($ch) {
                    $adminChannelId = $ch['id'];
                }
            }
            if ($adminChannelId) {
                $this->writeEnvValues(['DISCORD_ADMIN_CHANNEL_ID' => $adminChannelId]);
                config(['services.discord.admin_channel_id' => $adminChannelId]);
                $this->info("  ✓ Admin channel: #{$adminChannelId}");
            }
        } else {
            $this->info("  ✓ Admin channel already set: {$adminChannelId}");
        }

        // Create webhook
        $webhookUrl = config('services.discord.webhook_url');
        if (empty($webhookUrl) && $notifyChannelId) {
            $webhookUrl = $discord->createWebhook($notifyChannelId);
            if ($webhookUrl) {
                $this->writeEnvValues(['DISCORD_WEBHOOK_URL' => $webhookUrl]);
                config(['services.discord.webhook_url' => $webhookUrl]);
                $this->info("  ✓ Webhook created");
            }
        } else {
            $this->info("  ✓ Webhook already set");
        }
    }

    private function registerCommands(DiscordService $discord): void
    {
        $this->info('► Registering slash commands...');

        try {
            $result = $discord->registerCommands();
            $count = is_array($result) ? count($result) : 0;
            $this->info("  ✓ Registered {$count} commands");

            if (is_array($result)) {
                foreach ($result as $cmd) {
                    $this->line("    /{$cmd['name']} — {$cmd['description']}");
                }
            }
        } catch (\Exception $e) {
            $this->error("  ✗ Failed: {$e->getMessage()}");
        }
    }

    private function setupEndpoint(DiscordService $discord): void
    {
        $appUrl = config('app.url', '');
        if (empty($appUrl) || str_contains($appUrl, 'localhost')) {
            $this->warn('  ⚠ APP_URL is localhost — skipping endpoint setup');
            $this->line('  Set APP_URL in .env to your production URL, then run:');
            $this->line('  php artisan discord:setup --skip-channels --skip-commands');
            return;
        }

        $endpoint = rtrim($appUrl, '/') . '/api/discord/interactions';
        $this->info("► Setting interactions endpoint: {$endpoint}");

        try {
            $ok = $discord->setInteractionsEndpoint($endpoint);
            if ($ok) {
                $this->info('  ✓ Endpoint configured');
            } else {
                $this->warn('  ⚠ Could not set endpoint (server must respond to PING)');
                $this->line("  Set manually in Discord Developer Portal → General Information:");
                $this->line("  {$endpoint}");
            }
        } catch (\Exception $e) {
            $this->warn("  ⚠ Endpoint setup failed: {$e->getMessage()}");
            $this->line("  Set manually: {$endpoint}");
        }
    }

    private function writeEnvValues(array $values): void
    {
        $envPath = base_path('.env');
        if (!file_exists($envPath)) {
            return;
        }

        $content = file_get_contents($envPath);

        foreach ($values as $key => $value) {
            if ($value === null) {
                continue;
            }

            if (preg_match("/^{$key}=.*/m", $content)) {
                $content = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $content);
            } else {
                $content .= "\n{$key}={$value}";
            }
        }

        file_put_contents($envPath, $content);
    }
}
