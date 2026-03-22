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
    ->middleware('throttle:5,1');

Route::post('/incidents/{incident}/vote', [IncidentController::class, 'vote'])
    ->middleware('throttle:10,1');

// Stations
Route::get('/stations', [StationController::class, 'apiSearch'])
    ->middleware('throttle:20,1');

Route::post('/stations/report', [StationController::class, 'apiReport'])
    ->middleware('throttle:5,1');

// Chat
Route::post('/chat', [ChatController::class, 'apiChat'])
    ->middleware('throttle:10,1');

// Voice Command
Route::post('/voice-command', [VoiceCommandController::class, 'process'])
    ->middleware('throttle:15,1');
