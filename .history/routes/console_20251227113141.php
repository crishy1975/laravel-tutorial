<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(\Illuminate\Foundation\Inspiring::quote());
})->purpose('Display an inspiring quote');

// Fälligkeiten täglich um 02:30
Schedule::command('faelligkeit:recalc-all')
    ->dailyAt('02:30')
    ->timezone('Europe/Rome');

// Backup alle 5 Minuten (Test) - später auf weekly ändern!
Schedule::command('backup:create --force')
    ->everyFiveMinutes()
    ->timezone('Europe/Rome');