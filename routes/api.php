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
    ->middleware('throttle:3,1');

Route::post('/incidents/{incident}/vote', [IncidentController::class, 'vote'])
    ->middleware('throttle:10,1');

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
    ->middleware('throttle:3,1');

Route::post('/stations/report/{report}/confirm', [StationController::class, 'apiConfirm'])
    ->middleware('throttle:10,1');

// Fuel Prices (official daily prices)
Route::get('/fuel-prices', [StationController::class, 'apiFuelPrices'])
    ->middleware('throttle:30,1');

// News feed
Route::get('/news', function () {
    $news = \App\Models\News::recent()
        ->orderByDesc('published_at')
        ->limit(15)
        ->get();

    // If no news yet, trigger first scrape
    if ($news->isEmpty()) {
        try {
            app(\App\Services\NewsScraperService::class)->scrapeAll();
            $news = \App\Models\News::recent()
                ->orderByDesc('published_at')
                ->limit(15)
                ->get();
        } catch (\Exception $e) {
            // Silent fail
        }
    }

    return response()->json([
        'success' => true,
        'data' => $news,
        'count' => $news->count(),
    ]);
})->middleware('throttle:20,1');

// Chat
Route::post('/chat', [ChatController::class, 'apiChat'])
    ->middleware('throttle:10,1');

// Voice Command
Route::post('/voice-command', [VoiceCommandController::class, 'process'])
    ->middleware('throttle:15,1');

// Text-to-Speech (server-side Thai female voice)
Route::post('/tts', [\App\Http\Controllers\TtsController::class, 'synthesize'])
    ->middleware('throttle:30,1');

// My reports (auth required)
Route::middleware('auth')->group(function () {
    Route::get('/my-reports', function (\Illuminate\Http\Request $request) {
        $incidents = $request->user()->incidents()->with('votes')->latest()->get()
            ->map(fn($i) => array_merge($i->toArray(), ['type' => 'incident']));
        $stations = $request->user()->stationReports()->with('fuelReports')->latest()->get()
            ->map(fn($s) => array_merge($s->toArray(), ['type' => 'station']));
        return response()->json([
            'success' => true,
            'data' => $incidents->concat($stations)->sortByDesc('created_at')->values(),
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

// Track user activity (heartbeat)
Route::post('/heartbeat', function (\Illuminate\Http\Request $request) {
    if ($request->user()) {
        $request->user()->update(['last_active_at' => now()]);
    }
    return response()->json(['ok' => true]);
})->middleware(['auth', 'throttle:10,1']);

// Discord Bot Interactions
Route::post('/discord/interactions', [\App\Http\Controllers\DiscordInteractionController::class, 'handle']);
