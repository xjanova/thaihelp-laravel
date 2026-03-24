<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Scrape news every 5 hours
Schedule::command('news:scrape')->cron('0 */5 * * *');

// Clean up TTS cache files older than 3 days (prevent disk full)
Schedule::call(function () {
    $dir = storage_path('app/tts-cache');
    if (!is_dir($dir)) return;
    $deleted = 0;
    foreach (glob("{$dir}/*.mp3") as $file) {
        if (time() - filemtime($file) > 259200) { // 3 days
            @unlink($file);
            $deleted++;
        }
    }
    if ($deleted > 0) {
        \Illuminate\Support\Facades\Log::info("TTS cache cleanup: deleted {$deleted} files");
    }
})->daily();
