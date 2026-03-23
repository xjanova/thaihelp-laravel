<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class SiteSettingSeeder extends Seeder
{
    /**
     * Seed the site_settings table with default values.
     */
    public function run(): void
    {
        $settings = [
            'site_name' => 'ThaiHelp',
            'site_description' => 'ชุมชนช่วยเหลือนักเดินทาง',
            'setup_completed' => 'false',
            'incident_expire_hours' => '4',
            'report_expire_hours' => '6',
            'max_upload_size_mb' => '5',
            'enable_voice_assistant' => 'true',
            'enable_incident_reports' => 'true',
            'enable_fuel_reports' => 'true',
            'default_map_lat' => '13.7563',
            'default_map_lng' => '100.5018',
            'default_map_zoom' => '12',
        ];

        foreach ($settings as $key => $value) {
            SiteSetting::set($key, $value);
        }
    }
}
