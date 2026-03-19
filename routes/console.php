<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(\Illuminate\Foundation\Inspiring::quote());
})->purpose('Display an inspiring quote');

// Fetch attendance for all active employees daily at midnight.
// Fetches yesterday's records so all punches for the day are complete.
Schedule::command('attendance:fetch')->dailyAt('00:00');
