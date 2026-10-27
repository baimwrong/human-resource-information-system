<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule daily attendance calculation or reminders
Schedule::command('hris:daily-attendance-check')->dailyAt('23:00');

// Provision annual leave balances at the start of each new year
Schedule::command('hris:initialize-leave-balances')->yearlyOn(1, 1, '00:01');

// Automated daily database backup and retention rotation
Schedule::command('hris:backup-db --retention-days=14')->dailyAt('02:00');
