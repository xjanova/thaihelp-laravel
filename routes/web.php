<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\IncidentController;
use App\Http\Controllers\SetupController;
use App\Http\Controllers\StationController;
use Illuminate\Support\Facades\Route;

// First-time setup (rate limited for security)
Route::get('/setup', [SetupController::class, 'index'])->name('setup');
Route::post('/setup/migrate', [SetupController::class, 'migrate'])->name('setup.migrate')->middleware('throttle:3,10');
Route::post('/setup/configure', [SetupController::class, 'configure'])->name('setup.configure')->middleware('throttle:3,10');

// Home
Route::get('/', [HomeController::class, 'index'])->name('home');

// Auth - Guest only
Route::middleware('guest')->group(function () {
    Route::get('/login', fn () => view('pages.login'))->name('login');
    Route::post('/login', [AuthController::class, 'loginNickname'])->name('login.nickname');
});

// Auth - Social
Route::get('/auth/google', [AuthController::class, 'redirectGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [AuthController::class, 'callbackGoogle'])->name('auth.google.callback');
Route::get('/auth/line', [AuthController::class, 'redirectLine'])->name('auth.line');
Route::get('/auth/line/callback', [AuthController::class, 'callbackLine'])->name('auth.line.callback');

// Auth - Logout
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Stations
Route::get('/stations', [StationController::class, 'index'])->name('stations');

// Report - Anyone can report (GPS required, stars only for members)
Route::get('/report', [IncidentController::class, 'create'])->name('report');

// Stats
Route::get('/stats', [App\Http\Controllers\StatsController::class, 'index'])->name('stats');

// Trip Planner
Route::get('/trip', [App\Http\Controllers\TripPlannerController::class, 'index'])->name('trip');

// Hospitals
Route::get('/hospitals', [App\Http\Controllers\HospitalController::class, 'index'])->name('hospitals');

// Fuel Prices
Route::get('/fuel-prices', function () {
    return view('pages.fuel-prices');
})->name('fuel-prices');

// Legal & Info pages
Route::get('/credits', fn() => view('pages.credits'))->name('credits');
Route::get('/privacy', fn() => view('pages.privacy'))->name('privacy');
Route::get('/terms', fn() => view('pages.terms'))->name('terms');

// Chat
Route::get('/chat', [ChatController::class, 'index'])->name('chat');

// My Reports - Auth required
Route::get('/my-reports', function () {
    return view('pages.my-reports');
})->middleware('auth')->name('my-reports');

// Offline
Route::get('/offline', fn () => view('pages.offline'))->name('offline');
