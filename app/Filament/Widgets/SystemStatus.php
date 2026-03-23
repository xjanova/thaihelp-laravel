<?php

namespace App\Filament\Widgets;

use App\Models\SiteSetting;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class SystemStatus extends Widget
{
    protected static ?int $sort = 4;

    protected static string $view = 'filament.widgets.system-status';

    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        $checks = [];

        // Database connection
        try {
            DB::connection()->getPdo();
            $checks['database'] = [
                'label' => 'ฐานข้อมูล',
                'status' => true,
                'detail' => 'เชื่อมต่อสำเร็จ (' . config('database.default') . ')',
            ];
        } catch (\Exception $e) {
            $checks['database'] = [
                'label' => 'ฐานข้อมูล',
                'status' => false,
                'detail' => 'เชื่อมต่อไม่ได้',
            ];
        }

        // Setup completed
        $setupCompleted = SiteSetting::get('setup_completed', false);
        $checks['setup'] = [
            'label' => 'ตั้งค่าเริ่มต้น',
            'status' => (bool) $setupCompleted,
            'detail' => $setupCompleted ? 'ตั้งค่าเสร็จเรียบร้อย' : 'ยังไม่ได้ตั้งค่า',
        ];

        // Google Maps API Key
        $googleMapsKey = config('services.google_maps.key') ?: SiteSetting::get('google_maps_api_key');
        $checks['google_maps'] = [
            'label' => 'Google Maps API',
            'status' => ! empty($googleMapsKey),
            'detail' => ! empty($googleMapsKey) ? 'กำหนดค่าแล้ว' : 'ยังไม่ได้กำหนด API Key',
        ];

        // Groq API Key
        $groqKey = config('services.groq.api_key') ?: SiteSetting::get('groq_api_key');
        $checks['groq'] = [
            'label' => 'Groq AI API',
            'status' => ! empty($groqKey),
            'detail' => ! empty($groqKey) ? 'กำหนดค่าแล้ว' : 'ยังไม่ได้กำหนด API Key',
        ];

        // App environment
        $checks['environment'] = [
            'label' => 'สภาพแวดล้อม',
            'status' => app()->environment('production') || app()->environment('local'),
            'detail' => app()->environment(),
        ];

        return [
            'checks' => $checks,
        ];
    }
}
