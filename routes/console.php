<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Backup otomatis dinonaktifkan — Admin menjalankan backup manual dari UI
// Schedule::command('backup:run --only-db')->dailyAt('02:00');
// Schedule::command('backup:clean')->dailyAt('01:30');
