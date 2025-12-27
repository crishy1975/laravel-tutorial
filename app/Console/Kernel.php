<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Fälligkeiten täglich
        $schedule->command('faelligkeit:recalc-all')
            ->dailyAt('02:30')
            ->timezone('Europe/Rome');

        // Backup alle 5 Minuten (Test)
        $schedule->command('backup:create --force')
            ->everyFiveMinutes()
            ->timezone('Europe/Rome');
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}