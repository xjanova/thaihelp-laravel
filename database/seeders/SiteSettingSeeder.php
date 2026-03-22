<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SiteSettingSeeder extends Seeder
{
    /**
     * Seed the site_settings table with default values.
     */
    public function run(): void
    {
        $settings = [
            ['key' => 'site_name', 'value' => 'ThaiHelp'],
            ['key' => 'site_description', 'value' => 'ชุมชนช่วยเหลือนักเดินทาง'],
            ['key' => 'setup_completed', 'value' => 'false'],
            ['key' => 'incident_expire_hours', 'value' => '4'],
            ['key' => 'report_expire_hours', 'value' => '6'],
            ['key' => 'max_upload_size_mb', 'value' => '5'],
            ['key' => 'enable_voice_assistant', 'value' => 'true'],
            ['key' => 'enable_incident_reports', 'value' => 'true'],
            ['key' => 'enable_fuel_reports', 'value' => 'true'],
            ['key' => 'default_map_lat', 'value' => '13.7563'],
            ['key' => 'default_map_lng', 'value' => '100.5018'],
            ['key' => 'default_map_zoom', 'value' => '12'],
        ];

        foreach ($settings as $setting) {
            DB::table('site_settings')->updateOrInsert(
                ['key' => $setting['key']],
                ['value' => $setting['value']]
            );
        }
    }
}
