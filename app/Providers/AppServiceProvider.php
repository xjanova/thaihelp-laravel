<?php

namespace App\Providers;

use App\Services\GooglePlacesService;
use App\Services\GroqAIService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(GooglePlacesService::class, function ($app) {
            return new GooglePlacesService();
        });

        $this->app->singleton(GroqAIService::class, function ($app) {
            return new GroqAIService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
