<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Hier kommen geplante Tasks rein.
     */
    protected function schedule(Schedule $schedule): void
    {
        // 🔁 Täglich um 02:30 Uhr Europe/Rome
        $schedule->command('faelligkeit:recalc-all')
            ->dailyAt('02:30')
            ->timezone('Europe/Rome')
            ->onOneServer()          // bei mehreren Servern nur einmal
            ->withoutOverlapping()   // Schutz gegen Überschneidung
            ->runInBackground()      // blockiert nicht andere Jobs
            ->sendOutputTo(storage_path('logs/faelligkeit_cron.log')); // Logdatei
    }

    /**
     * Optional: Commands automatisch laden (Standard).
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
