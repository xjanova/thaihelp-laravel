<?php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\IncidentController;
use App\Http\Controllers\StationController;
use App\Http\Controllers\VoiceCommandController;
use Illuminate\Support\Facades\Route;

// Incidents
Route::get('/incidents', [IncidentController::class, 'apiIndex'])
    ->middleware('throttle:30,1');

Route::post('/incidents', [IncidentController::class, 'apiStore'])
    ->middleware('throttle:report');

Route::post('/incidents/{incident}/vote', [IncidentController::class, 'vote'])
    ->middleware('throttle:10,1');

// Note: PUT/DELETE /incidents defined in auth group below (line ~173)

Route::post('/incidents/{incident}/resolve', [IncidentController::class, 'resolve'])
    ->middleware(['auth', 'throttle:10,1']);

// External Data (แผ่นดินไหว, อากาศ, AQI, น้ำท่วม, จราจร)
Route::get('/external-data', function (\Illuminate\Http\Request $request) {
    $lat = $request->query('lat', 13.7563);
    $lng = $request->query('lng', 100.5018);
    $types = $request->query('types', 'all'); // all, earthquakes, weather, air_quality, flood_warnings, traffic

    $service = app(\App\Services\ExternalDataService::class);

    if ($types === 'all') {
        $data = $service->getAll((float) $lat, (float) $lng);
    } else {
        $data = [];
        foreach (explode(',', $types) as $type) {
            $type = trim($type);
            $data[$type] = match ($type) {
                'earthquakes' => $service->getEarthquakes(),
                'weather' => $service->getWeather((float) $lat, (float) $lng),
                'air_quality' => $service->getAirQuality((float) $lat, (float) $lng),
                'flood_warnings' => $service->getFloodWarnings(),
                'traffic' => $service->getTrafficAlerts((float) $lat, (float) $lng),
                default => [],
            };
        }
    }

    return response()->json(['success' => true, 'data' => $data]);
})->middleware('throttle:10,1');

// Trip Planner
Route::post('/trip/plan', [App\Http\Controllers\TripPlannerController::class, 'plan'])
    ->middleware('throttle:10,1');

// EV Charging Stations
Route::get('/ev-chargers', function (\Illuminate\Http\Request $request) {
    $lat = $request->query('lat', 13.7563);
    $lng = $request->query('lng', 100.5018);
    $radius = $request->query('radius', 25);
    $data = app(\App\Services\EVChargingService::class)->getNearby((float) $lat, (float) $lng, (float) $radius);
    return response()->json(['success' => true, 'data' => $data, 'count' => count($data)]);
})->middleware('throttle:20,1');

// Gamification
Route::get('/profile', [App\Http\Controllers\GamificationController::class, 'profile'])
    ->middleware(['auth', 'throttle:30,1']);
Route::get('/leaderboard', [App\Http\Controllers\GamificationController::class, 'leaderboard'])
    ->middleware('throttle:20,1');
Route::get('/challenges', [App\Http\Controllers\GamificationController::class, 'challenges'])
    ->middleware('throttle:20,1');

// Fuel Prices
Route::get('/fuel-prices', function () {
    $prices = app(\App\Services\FuelPriceService::class)->getTodayPrices();
    return response()->json(['success' => true, 'data' => $prices, 'date' => now()->toDateString()]);
})->middleware('throttle:20,1');

Route::get('/fuel-prices/history', function (\Illuminate\Http\Request $request) {
    $type = $request->query('type', 'diesel');
    $days = min((int) $request->query('days', 30), 90);
    $history = app(\App\Services\FuelPriceService::class)->getPriceHistory($type, $days);
    return response()->json(['success' => true, 'data' => $history]);
})->middleware('throttle:10,1');

// Hospitals
Route::get('/hospitals', [App\Http\Controllers\HospitalController::class, 'apiIndex'])->middleware('throttle:30,1');
Route::post('/hospitals', [App\Http\Controllers\HospitalController::class, 'apiStore'])->middleware('throttle:5,1');

// Stats
Route::get('/stats', [App\Http\Controllers\StatsController::class, 'apiStats'])
    ->middleware('throttle:20,1');

// Breaking News (auto-generated from 3+ similar reports)
Route::get('/breaking-news', function () {
    return response()->json([
        'success' => true,
        'data' => \App\Models\BreakingNews::active()->latest()->limit(10)->get(),
    ]);
})->middleware('throttle:30,1');

// Stations
Route::get('/stations', [StationController::class, 'apiSearch'])
    ->middleware('throttle:20,1');

Route::post('/stations/report', [StationController::class, 'apiReport'])
    ->middleware('throttle:report');

Route::post('/stations/report/{report}/confirm', [StationController::class, 'apiConfirm'])
    ->middleware('throttle:10,1');

// Note: /fuel-prices is defined above (lines 79-91) with FuelPriceService

// News feed
Route::get('/news', function () {
    $news = \App\Models\News::recent()
        ->orderByDesc('published_at')
        ->limit(15)
        ->get();

    // If no news, return empty — don't block the request with sync scraping
    // News scraping should happen via scheduled command (php artisan schedule:run)

    return response()->json([
        'success' => true,
        'data' => $news,
        'count' => $news->count(),
    ]);
})->middleware('throttle:20,1');

// Chat — smart rate limit: 15/min auth, 6/min anon
Route::post('/chat', [ChatController::class, 'apiChat'])
    ->middleware('throttle:chat');

// Voice Command
Route::post('/voice-command', [VoiceCommandController::class, 'process'])
    ->middleware('throttle:15,1');

// Text-to-Speech — smart rate limit: 60/min auth, 20/min anon
Route::match(['get', 'post'], '/tts', [\App\Http\Controllers\TtsController::class, 'synthesize'])
    ->middleware('throttle:tts');

// My reports (auth required)
Route::middleware('auth')->group(function () {
    Route::get('/my-reports', function (\Illuminate\Http\Request $request) {
        $limit = min((int) $request->query('limit', 20), 50);
        $incidents = $request->user()->incidents()->with('votes')->latest()->limit($limit)->get()
            ->map(fn($i) => array_merge($i->toArray(), ['type' => 'incident']));
        $stations = $request->user()->stationReports()->with('fuelReports')->latest()->limit($limit)->get()
            ->map(fn($s) => array_merge($s->toArray(), ['type' => 'station']));
        return response()->json([
            'success' => true,
            'data' => $incidents->concat($stations)->sortByDesc('created_at')->values()->take($limit),
        ]);
    });

    Route::put('/incidents/{incident}', [\App\Http\Controllers\IncidentController::class, 'apiUpdate']);
    Route::delete('/incidents/{incident}', [\App\Http\Controllers\IncidentController::class, 'apiDestroy']);

    Route::put('/stations/{report}', [\App\Http\Controllers\StationController::class, 'apiUpdate']);
    Route::delete('/stations/{report}', [\App\Http\Controllers\StationController::class, 'apiDestroy']);

    Route::get('/user/profile', function (\Illuminate\Http\Request $request) {
        $user = $request->user();
        $starLevel = $user->getStarLevel();
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'nickname' => $user->nickname ?? $user->name,
                'avatar' => $user->avatar_url,
                'reputation' => $user->reputation_score ?? 0,
                'reports_count' => $user->total_reports ?? 0,
                'confirmations_count' => $user->total_confirmations ?? 0,
                'star_level' => $starLevel['level'] ?? 1,
                'star_title' => $starLevel['title'] ?? 'สมาชิกใหม่',
                'stars' => $starLevel['stars'] ?? '⭐',
                'created_at' => $user->created_at->toISOString(),
            ],
        ]);
    });
});

// PWA installation tracking
Route::post('/pwa/installed', function (\Illuminate\Http\Request $request) {
    $validated = $request->validate([
        'device_type' => ['nullable', 'string', 'in:ios,android,desktop'],
    ]);

    if ($request->user()) {
        $request->user()->update([
            'pwa_installed' => true,
            'pwa_installed_at' => now(),
            'device_type' => $validated['device_type'] ?? 'desktop',
        ]);
    }

    return response()->json(['success' => true]);
})->middleware('throttle:5,1');

// Track user activity (heartbeat) — cached to avoid DB write storm
// Only writes to DB once every 5 minutes per user
Route::post('/heartbeat', function (\Illuminate\Http\Request $request) {
    if ($request->user()) {
        $userId = $request->user()->id;
        $cacheKey = "heartbeat_{$userId}";
        if (!\Illuminate\Support\Facades\Cache::has($cacheKey)) {
            $request->user()->update(['last_active_at' => now()]);
            \Illuminate\Support\Facades\Cache::put($cacheKey, true, 300); // 5 min
        }
    }
    return response()->json(['ok' => true]);
})->middleware(['auth', 'throttle:6,1']);

// Discord Bot Interactions
Route::post('/discord/interactions', [\App\Http\Controllers\DiscordInteractionController::class, 'handle'])
    ->middleware('throttle:60,1');
