<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(\Illuminate\Foundation\Inspiring::quote());
})->purpose('Display an inspiring quote');

// Fetch attendance for all active employees daily at midnight.
// Fetches yesterday's records so all punches for the day are complete.
Schedule::command('attendance:fetch')->dailyAt('00:00');

// Auto-acknowledge payslips not confirmed within 5 days of finalization.
Schedule::command('payroll:auto-acknowledge')->dailyAt('01:00');
Schedule::command('announcements:publish-scheduled')->everyMinute();

// Push reminders — both run every 5 minutes; each command handles its own time-window check.
// Time-in: fires in the 07:00–07:05 window.
// Clock-out: fires 15 min before each employee's shift end (fallback: 9:00 PM).
Schedule::command('notifications:time-in-reminders')->everyFiveMinutes();
Schedule::command('notifications:clock-out-reminders')->everyFiveMinutes();
