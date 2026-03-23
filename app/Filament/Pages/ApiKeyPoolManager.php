<?php

namespace App\Filament\Pages;

use App\Services\ApiKeyPool;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ApiKeyPoolManager extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'API Key Pool';

    protected static ?string $title = 'API Key Pool';

    protected static ?string $slug = 'api-pool';

    protected static ?int $navigationSort = 102;

    protected static string $view = 'filament.pages.api-pool';

    public string $selectedService = 'groq';
    public array $pools = [];

    protected static array $services = [
        'groq' => 'Groq AI (น้องหญิง Chat)',
        'google_maps' => 'Google Maps API',
        'google_tts' => 'Google Cloud TTS',
    ];

    public function mount(): void
    {
        $this->loadPools();
    }

    public function loadPools(): void
    {
        $this->pools = [];
        foreach (self::$services as $key => $label) {
            $this->pools[$key] = [
                'label' => $label,
                'keys' => ApiKeyPool::getStats($key),
                'count' => count(ApiKeyPool::getPool($key)),
            ];
        }
    }

    public function getHeaderActions(): array
    {
        return [
            Action::make('addKey')
                ->label('เพิ่ม API Key')
                ->icon('heroicon-o-plus')
                ->color('success')
                ->form([
                    Select::make('service')
                        ->label('บริการ')
                        ->options(self::$services)
                        ->required(),

                    TextInput::make('key')
                        ->label('API Key')
                        ->required()
                        ->password()
                        ->revealable()
                        ->maxLength(500),

                    TextInput::make('label')
                        ->label('ชื่อ/ป้ายกำกับ')
                        ->placeholder('เช่น: Account 1, Project X')
                        ->maxLength(100),
                ])
                ->action(function (array $data): void {
                    ApiKeyPool::addKey(
                        $data['service'],
                        $data['key'],
                        $data['label'] ?? ''
                    );

                    $this->loadPools();

                    Notification::make()
                        ->title('เพิ่ม API Key สำเร็จ')
                        ->success()
                        ->send();
                }),
        ];
    }

    public function removeKey(string $service, string $key): void
    {
        ApiKeyPool::removeKey($service, $key);
        $this->loadPools();

        Notification::make()
            ->title('ลบ API Key สำเร็จ')
            ->success()
            ->send();
    }

    public function toggleKey(string $service, string $key, bool $enabled): void
    {
        $pool = ApiKeyPool::getPool($service);
        foreach ($pool as &$entry) {
            if ($entry['key'] === $key) {
                $entry['enabled'] = $enabled;
                break;
            }
        }
        ApiKeyPool::setPool($service, $pool);
        $this->loadPools();

        Notification::make()
            ->title($enabled ? 'เปิดใช้งาน Key' : 'ปิดใช้งาน Key')
            ->success()
            ->send();
    }
}
