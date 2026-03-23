<?php

namespace App\Providers;

use App\Models\SiteSetting;
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
        // Override config with DB settings for OAuth providers
        $this->overrideConfigFromDatabase();
    }

    /**
     * Override Laravel config values with settings from DB.
     * This allows admins to configure API keys from the admin panel.
     */
    private function overrideConfigFromDatabase(): void
    {
        try {
            // Google OAuth
            $googleClientId = SiteSetting::get('google_client_id');
            $googleClientSecret = SiteSetting::get('google_client_secret');
            $googleRedirectUri = SiteSetting::get('google_redirect_uri');

            if ($googleClientId) {
                config(['services.google.client_id' => $googleClientId]);
            }
            if ($googleClientSecret) {
                config(['services.google.client_secret' => $googleClientSecret]);
            }
            if ($googleRedirectUri) {
                config(['services.google.redirect' => $googleRedirectUri]);
            }

            // LINE OAuth
            $lineChannelId = SiteSetting::get('line_channel_id');
            $lineChannelSecret = SiteSetting::get('line_channel_secret');
            $lineRedirectUri = SiteSetting::get('line_redirect_uri');

            if ($lineChannelId) {
                config(['services.line.client_id' => $lineChannelId]);
            }
            if ($lineChannelSecret) {
                config(['services.line.client_secret' => $lineChannelSecret]);
            }
            if ($lineRedirectUri) {
                config(['services.line.redirect' => $lineRedirectUri]);
            }

            // Google Maps
            $googleMapsKey = SiteSetting::get('google_maps_api_key');
            if ($googleMapsKey) {
                config(['services.google_maps.api_key' => $googleMapsKey]);
            }

            // Groq AI
            $groqKey = SiteSetting::get('groq_api_key');
            if ($groqKey) {
                config(['services.groq.api_key' => $groqKey]);
            }
        } catch (\Exception $e) {
            // DB not ready yet, skip silently
        }
    }
}
