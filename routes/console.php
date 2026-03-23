<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Scrape news every 5 hours
Schedule::command('news:scrape')->cron('0 */5 * * *');
