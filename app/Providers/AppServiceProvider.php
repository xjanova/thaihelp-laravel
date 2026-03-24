<?php

namespace App\Providers;

use App\Models\SiteSetting;
use App\Services\GooglePlacesService;
use App\Services\GroqAIService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;

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
        // Register LINE socialite provider
        Event::listen(SocialiteWasCalled::class, function (SocialiteWasCalled $socialiteWasCalled) {
            $socialiteWasCalled->extendSocialite('line', \SocialiteProviders\Line\Provider::class);
        });

        // Smart rate limiters: auth users get more, anon gets less
        $this->configureRateLimiting();

        // Override config with DB settings for OAuth providers
        $this->overrideConfigFromDatabase();
    }

    /**
     * Configure smart rate limiting — auth users get higher limits.
     */
    private function configureRateLimiting(): void
    {
        // Chat: 6/min anon, 15/min auth
        RateLimiter::for('chat', function (Request $request) {
            return $request->user()
                ? Limit::perMinute(15)->by('user_' . $request->user()->id)
                : Limit::perMinute(6)->by('ip_' . $request->ip());
        });

        // TTS: 20/min anon, 60/min auth
        RateLimiter::for('tts', function (Request $request) {
            return $request->user()
                ? Limit::perMinute(60)->by('user_' . $request->user()->id)
                : Limit::perMinute(20)->by('ip_' . $request->ip());
        });

        // Report submit: 3/min (prevent spam reports)
        RateLimiter::for('report', function (Request $request) {
            return Limit::perMinute(3)->by(
                $request->user() ? 'user_' . $request->user()->id : 'ip_' . $request->ip()
            );
        });

        // General API: 60/min anon, 120/min auth
        RateLimiter::for('api', function (Request $request) {
            return $request->user()
                ? Limit::perMinute(120)->by('user_' . $request->user()->id)
                : Limit::perMinute(60)->by('ip_' . $request->ip());
        });
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
