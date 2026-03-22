<?php

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;

return [

    'name' => env('APP_NAME', 'ThaiHelp'),

    'env' => env('APP_ENV', 'production'),

    'debug' => (bool) env('APP_DEBUG', false),

    'url' => env('APP_URL', 'http://localhost'),

    'asset_url' => env('ASSET_URL'),

    'timezone' => 'Asia/Bangkok',

    'locale' => 'th',

    'fallback_locale' => 'en',

    'faker_locale' => 'th_TH',

    'key' => env('APP_KEY'),

    'cipher' => 'AES-256-CBC',

    'maintenance' => [
        'driver' => 'file',
    ],

    'providers' => ServiceProvider::defaultProviders()->merge([
        App\Providers\Filament\AdminPanelProvider::class,
    ])->toArray(),

    'aliases' => Facade::defaultAliases()->merge([
    ])->toArray(),

];
