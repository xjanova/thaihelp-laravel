<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\IncidentController;
use App\Http\Controllers\SetupController;
use App\Http\Controllers\StationController;
use Illuminate\Support\Facades\Route;

// First-time setup (no middleware)
Route::get('/setup', [SetupController::class, 'index'])->name('setup');
Route::post('/setup/migrate', [SetupController::class, 'migrate'])->name('setup.migrate');
Route::post('/setup/configure', [SetupController::class, 'configure'])->name('setup.configure');

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

// Report - Auth required
Route::get('/report', [IncidentController::class, 'create'])->middleware('auth')->name('report');

// Chat
Route::get('/chat', [ChatController::class, 'index'])->name('chat');

// Offline
Route::get('/offline', fn () => view('pages.offline'))->name('offline');
