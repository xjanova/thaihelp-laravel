<?php

namespace App\Http\Middleware;

use App\Models\SiteSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class CheckSetup
{
    /**
     * Redirect to setup wizard if first-time setup is not completed.
     * Skips check for setup routes, API routes, and asset routes.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip for setup routes, API, assets, and admin login
        if ($request->is('setup*', 'api/*', '_debugbar/*', 'livewire/*', 'admin/login*')) {
            return $next($request);
        }

        // Skip if request is for static files
        if ($request->is('*.js', '*.css', '*.ico', '*.png', '*.jpg', '*.svg', 'build/*', 'manifest.json', 'sw.js')) {
            return $next($request);
        }

        try {
            if (!Schema::hasTable('site_settings')) {
                return redirect('/setup');
            }

            $setupCompleted = SiteSetting::get('setup_completed');
            if ($setupCompleted !== 'true') {
                return redirect('/setup');
            }
        } catch (\Exception $e) {
            // DB not connected — redirect to setup
            return redirect('/setup');
        }

        return $next($request);
    }
}
