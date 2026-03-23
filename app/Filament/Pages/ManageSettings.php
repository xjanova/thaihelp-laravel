<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use Filament\Forms;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;

class ManageSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'ตั้งค่าระบบ';

    protected static ?string $title = 'ตั้งค่าระบบ';

    protected static ?string $slug = 'settings';

    protected static ?int $navigationSort = 100;

    protected static string $view = 'filament.pages.manage-settings';

    public ?array $data = [];

    /**
     * All setting keys with their defaults and groups.
     */
    protected function settingDefinitions(): array
    {
        return [
            // General
            'site_name'              => ['default' => 'ThaiHelp', 'group' => 'general'],
            'site_description'       => ['default' => '', 'group' => 'general'],
            'setup_completed'        => ['default' => false, 'group' => 'general'],

            // Map
            'default_map_lat'        => ['default' => '13.7563', 'group' => 'map'],
            'default_map_lng'        => ['default' => '100.5018', 'group' => 'map'],
            'default_map_zoom'       => ['default' => '12', 'group' => 'map'],

            // API Keys
            'google_maps_api_key'    => ['default' => '', 'group' => 'api'],
            'groq_api_key'           => ['default' => '', 'group' => 'api'],

            // Google OAuth
            'google_client_id'       => ['default' => '', 'group' => 'oauth'],
            'google_client_secret'   => ['default' => '', 'group' => 'oauth'],
            'google_redirect_uri'    => ['default' => '', 'group' => 'oauth'],

            // LINE Login
            'line_channel_id'        => ['default' => '', 'group' => 'line'],
            'line_channel_secret'    => ['default' => '', 'group' => 'line'],
            'line_redirect_uri'      => ['default' => '', 'group' => 'line'],

            // Feature Toggles
            'enable_voice_assistant'   => ['default' => true, 'group' => 'features'],
            'enable_incident_reports'  => ['default' => true, 'group' => 'features'],
            'enable_fuel_reports'      => ['default' => true, 'group' => 'features'],
            'incident_expire_hours'    => ['default' => 4, 'group' => 'features'],
            'max_upload_size_mb'       => ['default' => 5, 'group' => 'features'],
        ];
    }

    public function mount(): void
    {
        $values = [];

        foreach ($this->settingDefinitions() as $key => $meta) {
            $raw = SiteSetting::get($key, $meta['default']);

            // Cast booleans for toggles
            if (is_bool($meta['default'])) {
                $values[$key] = filter_var($raw, FILTER_VALIDATE_BOOLEAN);
            } else {
                $values[$key] = $raw;
            }
        }

        $this->form->fill($values);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Settings')
                    ->tabs([
                        $this->generalTab(),
                        $this->mapTab(),
                        $this->apiKeysTab(),
                        $this->googleOAuthTab(),
                        $this->lineLoginTab(),
                        $this->featureTogglesTab(),
                    ])
                    ->columnSpanFull()
                    ->persistTabInQueryString(),
            ])
            ->statePath('data');
    }

    // ──────────────────────────────────────────────
    //  Tab definitions
    // ──────────────────────────────────────────────

    protected function generalTab(): Tab
    {
        return Tab::make('ทั่วไป')
            ->label('ทั่วไป (General)')
            ->icon('heroicon-o-globe-alt')
            ->schema([
                Section::make('General Settings')
                    ->description('ตั้งค่าพื้นฐานของเว็บไซต์')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        TextInput::make('site_name')
                            ->label('ชื่อเว็บไซต์ (Site Name)')
                            ->placeholder('ThaiHelp')
                            ->default('ThaiHelp')
                            ->required()
                            ->maxLength(255),

                        Textarea::make('site_description')
                            ->label('คำอธิบายเว็บไซต์ (Site Description)')
                            ->placeholder('ระบบช่วยเหลือคนไทยในต่างแดน')
                            ->rows(3)
                            ->maxLength(1000),

                        Toggle::make('setup_completed')
                            ->label('การตั้งค่าเสร็จสมบูรณ์ (Setup Completed)')
                            ->helperText('เปิดเมื่อตั้งค่าระบบเสร็จเรียบร้อยแล้ว')
                            ->default(false),
                    ]),
            ]);
    }

    protected function mapTab(): Tab
    {
        return Tab::make('แผนที่')
            ->label('แผนที่ (Map)')
            ->icon('heroicon-o-map-pin')
            ->schema([
                Section::make('Map Settings')
                    ->description('ตั้งค่าตำแหน่งเริ่มต้นของแผนที่')
                    ->icon('heroicon-o-map')
                    ->columns(3)
                    ->schema([
                        TextInput::make('default_map_lat')
                            ->label('ละติจูด (Latitude)')
                            ->placeholder('13.7563')
                            ->default('13.7563')
                            ->numeric()
                            ->required(),

                        TextInput::make('default_map_lng')
                            ->label('ลองจิจูด (Longitude)')
                            ->placeholder('100.5018')
                            ->default('100.5018')
                            ->numeric()
                            ->required(),

                        TextInput::make('default_map_zoom')
                            ->label('ระดับซูม (Zoom Level)')
                            ->placeholder('12')
                            ->default('12')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(20)
                            ->required(),
                    ]),
            ]);
    }

    protected function apiKeysTab(): Tab
    {
        return Tab::make('API Keys')
            ->label('API Keys')
            ->icon('heroicon-o-key')
            ->schema([
                Section::make('API Keys')
                    ->description('คีย์สำหรับเชื่อมต่อบริการภายนอก')
                    ->icon('heroicon-o-lock-closed')
                    ->schema([
                        TextInput::make('google_maps_api_key')
                            ->label('Google Maps API Key')
                            ->placeholder('Google Maps API Key')
                            ->password()
                            ->revealable()
                            ->maxLength(255)
                            ->helperText('ใช้สำหรับแสดงแผนที่และค้นหาสถานที่'),

                        TextInput::make('groq_api_key')
                            ->label('Groq AI API Key')
                            ->placeholder('Groq AI API Key')
                            ->password()
                            ->revealable()
                            ->maxLength(255)
                            ->helperText('ใช้สำหรับระบบผู้ช่วยเสียง AI (น้องหญิง)'),
                    ]),
            ]);
    }

    protected function googleOAuthTab(): Tab
    {
        $callbackUrl = url('/auth/google/callback');

        return Tab::make('Google OAuth')
            ->label('Google OAuth')
            ->icon('heroicon-o-shield-check')
            ->schema([
                Section::make('Google OAuth Settings')
                    ->description('ตั้งค่าการเข้าสู่ระบบด้วย Google')
                    ->icon('heroicon-o-user-circle')
                    ->schema([
                        TextInput::make('google_client_id')
                            ->label('Client ID')
                            ->placeholder('xxxxx.apps.googleusercontent.com')
                            ->maxLength(255),

                        TextInput::make('google_client_secret')
                            ->label('Client Secret')
                            ->password()
                            ->revealable()
                            ->maxLength(255),

                        TextInput::make('google_redirect_uri')
                            ->label('Redirect URI')
                            ->placeholder($callbackUrl)
                            ->helperText("Callback URL สำหรับตั้งค่าใน Google Console: {$callbackUrl}")
                            ->maxLength(500),
                    ]),
            ]);
    }

    protected function lineLoginTab(): Tab
    {
        $lineCallbackUrl = url('/auth/line/callback');

        return Tab::make('LINE Login')
            ->label('LINE Login')
            ->icon('heroicon-o-chat-bubble-left-right')
            ->schema([
                Section::make('LINE Login Settings')
                    ->description('ตั้งค่าการเข้าสู่ระบบด้วย LINE')
                    ->icon('heroicon-o-chat-bubble-oval-left')
                    ->schema([
                        TextInput::make('line_channel_id')
                            ->label('Channel ID')
                            ->placeholder('LINE Channel ID')
                            ->maxLength(255),

                        TextInput::make('line_channel_secret')
                            ->label('Channel Secret')
                            ->password()
                            ->revealable()
                            ->maxLength(255),

                        TextInput::make('line_redirect_uri')
                            ->label('Redirect URI')
                            ->placeholder($lineCallbackUrl)
                            ->helperText("Callback URL สำหรับตั้งค่าใน LINE Developers Console: {$lineCallbackUrl}")
                            ->maxLength(500),
                    ]),
            ]);
    }

    protected function featureTogglesTab(): Tab
    {
        return Tab::make('ฟีเจอร์')
            ->label('ฟีเจอร์ (Features)')
            ->icon('heroicon-o-adjustments-horizontal')
            ->schema([
                Section::make('Feature Toggles')
                    ->description('เปิด/ปิดฟีเจอร์ต่าง ๆ ของระบบ')
                    ->icon('heroicon-o-bolt')
                    ->columns(2)
                    ->schema([
                        Toggle::make('enable_voice_assistant')
                            ->label('ผู้ช่วยเสียง (Voice Assistant)')
                            ->helperText('เปิดใช้งานน้องหญิง AI')
                            ->default(true),

                        Toggle::make('enable_incident_reports')
                            ->label('รายงานเหตุการณ์ (Incident Reports)')
                            ->helperText('เปิดใช้งานระบบรายงานเหตุการณ์')
                            ->default(true),

                        Toggle::make('enable_fuel_reports')
                            ->label('รายงานน้ำมัน (Fuel Reports)')
                            ->helperText('เปิดใช้งานระบบรายงานราคาน้ำมัน')
                            ->default(true),
                    ]),

                Section::make('Limits')
                    ->description('ตั้งค่าขีดจำกัดต่าง ๆ')
                    ->icon('heroicon-o-clock')
                    ->columns(2)
                    ->schema([
                        TextInput::make('incident_expire_hours')
                            ->label('ชั่วโมงหมดอายุเหตุการณ์ (Incident Expire Hours)')
                            ->numeric()
                            ->default(4)
                            ->minValue(1)
                            ->maxValue(168)
                            ->suffix('ชั่วโมง')
                            ->helperText('เหตุการณ์จะหมดอายุหลังจากเวลาที่กำหนด'),

                        TextInput::make('max_upload_size_mb')
                            ->label('ขนาดไฟล์สูงสุด (Max Upload Size)')
                            ->numeric()
                            ->default(5)
                            ->minValue(1)
                            ->maxValue(50)
                            ->suffix('MB')
                            ->helperText('ขนาดไฟล์สูงสุดที่อนุญาตให้อัปโหลด'),
                    ]),
            ]);
    }

    // ──────────────────────────────────────────────
    //  Save
    // ──────────────────────────────────────────────

    public function save(): void
    {
        $data = $this->form->getState();

        foreach ($this->settingDefinitions() as $key => $meta) {
            $value = $data[$key] ?? $meta['default'];

            // Store booleans as "1"/"0" strings
            if (is_bool($value)) {
                $value = $value ? '1' : '0';
            }

            SiteSetting::set($key, (string) $value, $meta['group']);
        }

        // Clear config cache so new API keys take effect immediately
        try {
            Artisan::call('config:clear');
        } catch (\Throwable $e) {
            // Silently continue — cache clear is best-effort
        }

        Notification::make()
            ->title('บันทึกสำเร็จ')
            ->body('ตั้งค่าระบบถูกบันทึกเรียบร้อยแล้ว')
            ->success()
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            \Filament\Actions\Action::make('save')
                ->label('บันทึกการตั้งค่า (Save Settings)')
                ->submit('save'),
        ];
    }
}
