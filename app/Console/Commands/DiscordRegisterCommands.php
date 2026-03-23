<?php

namespace App\Console\Commands;

use App\Models\SiteSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class DiscordRegisterCommands extends Command
{
    protected $signature = 'discord:register';

    protected $description = 'Register ThaiHelp slash commands with Discord API';

    public function handle(): int
    {
        $botToken = SiteSetting::get('discord_bot_token');
        $appId = SiteSetting::get('discord_application_id');

        if (empty($botToken) || empty($appId)) {
            $this->error('Discord bot token or application ID not configured.');
            $this->info('Set discord_bot_token and discord_application_id in SiteSettings.');
            return self::FAILURE;
        }

        $commands = [
            [
                'name' => 'incident',
                'description' => 'แจ้งเหตุการณ์ผิดปกติบนท้องถนน',
                'options' => [
                    [
                        'name' => 'category',
                        'description' => 'ประเภท',
                        'type' => 3, // STRING
                        'required' => true,
                        'choices' => [
                            ['name' => '🚗 อุบัติเหตุ', 'value' => 'accident'],
                            ['name' => '🌊 น้ำท่วม', 'value' => 'flood'],
                            ['name' => '🚧 ถนนปิด', 'value' => 'roadblock'],
                            ['name' => '👮 ด่านตรวจ', 'value' => 'checkpoint'],
                            ['name' => '🏗️ ก่อสร้าง', 'value' => 'construction'],
                            ['name' => '📌 อื่นๆ', 'value' => 'other'],
                        ],
                    ],
                    [
                        'name' => 'title',
                        'description' => 'หัวข้อ',
                        'type' => 3,
                        'required' => true,
                    ],
                    [
                        'name' => 'description',
                        'description' => 'รายละเอียด',
                        'type' => 3,
                        'required' => false,
                    ],
                    [
                        'name' => 'latitude',
                        'description' => 'ละติจูด',
                        'type' => 10, // NUMBER
                        'required' => false,
                    ],
                    [
                        'name' => 'longitude',
                        'description' => 'ลองจิจูด',
                        'type' => 10,
                        'required' => false,
                    ],
                ],
            ],
            [
                'name' => 'stations',
                'description' => 'ค้นหาปั๊มน้ำมันใกล้เคียง',
                'options' => [
                    [
                        'name' => 'latitude',
                        'description' => 'ละติจูด (default: กรุงเทพ)',
                        'type' => 10,
                        'required' => false,
                    ],
                    [
                        'name' => 'longitude',
                        'description' => 'ลองจิจูด',
                        'type' => 10,
                        'required' => false,
                    ],
                    [
                        'name' => 'radius',
                        'description' => 'รัศมีค้นหา (กม.)',
                        'type' => 4, // INTEGER
                        'required' => false,
                    ],
                ],
            ],
            [
                'name' => 'fuel',
                'description' => 'เช็คสถานะน้ำมันจากรายงานชุมชน',
                'options' => [
                    [
                        'name' => 'type',
                        'description' => 'ประเภทน้ำมัน',
                        'type' => 3,
                        'required' => false,
                        'choices' => [
                            ['name' => 'แก๊สโซฮอล์ 95', 'value' => 'gasohol95'],
                            ['name' => 'แก๊สโซฮอล์ 91', 'value' => 'gasohol91'],
                            ['name' => 'ดีเซล', 'value' => 'diesel'],
                            ['name' => 'ดีเซล B7', 'value' => 'diesel_b7'],
                            ['name' => 'E20', 'value' => 'e20'],
                            ['name' => 'E85', 'value' => 'e85'],
                            ['name' => 'NGV', 'value' => 'ngv'],
                            ['name' => 'LPG', 'value' => 'lpg'],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'chat',
                'description' => 'คุยกับน้องหญิง AI ผู้ช่วยการเดินทาง',
                'options' => [
                    [
                        'name' => 'message',
                        'description' => 'ข้อความที่ต้องการถาม',
                        'type' => 3,
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'help',
                'description' => 'แสดงรายการคำสั่งทั้งหมดของ ThaiHelp Bot',
            ],
            [
                'name' => 'status',
                'description' => 'แสดงสถานะระบบ ThaiHelp',
            ],
        ];

        $url = "https://discord.com/api/v10/applications/{$appId}/commands";

        $this->info('Registering ' . count($commands) . ' slash commands with Discord...');
        $this->newLine();

        $success = 0;
        $failed = 0;

        foreach ($commands as $command) {
            $response = Http::withHeaders([
                'Authorization' => "Bot {$botToken}",
            ])->post($url, $command);

            if ($response->successful()) {
                $this->info("  ✅ /{$command['name']} - registered successfully");
                $success++;
            } else {
                $this->error("  ❌ /{$command['name']} - failed ({$response->status()})");
                $this->warn("     " . $response->body());
                $failed++;
            }
        }

        $this->newLine();
        $this->info("Done! {$success} succeeded, {$failed} failed.");

        if ($failed === 0) {
            $this->info('All commands registered. They may take up to 1 hour to appear globally.');
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
