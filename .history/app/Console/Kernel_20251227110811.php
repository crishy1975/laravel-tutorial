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
        // Täglich um 02:30 Uhr Europe/Rome - Fälligkeiten aktualisieren
        $schedule->command('faelligkeit:recalc-all')
            ->dailyAt('02:30')
            ->timezone('Europe/Rome')
            ->onOneServer()          // bei mehreren Servern nur einmal
            ->withoutOverlapping()   // Schutz gegen Überschneidung
            ->runInBackground()      // blockiert nicht andere Jobs
            ->sendOutputTo(storage_path('logs/faelligkeit_cron.log'));
        
        // Wöchentlich Sonntag um 03:00 Uhr - Datenbank-Backup
        $schedule->command('backup:create')
            ->everyFiveMinutes()  // ← Für Test: alle 5 Minuten
            // ->weekly()         // ← Später: wöchentlich
            // ->sundays()        // ← Später: Sonntag
            // ->at('03:00')      // ← Später: um 03:00 Uhr

            ->timezone('Europe/Rome')
            ->onOneServer()
            ->withoutOverlapping()
            ->runInBackground()
            ->onSuccess(function () {
                \Illuminate\Support\Facades\Log::info('Wöchentliches Backup erfolgreich erstellt');
            })
            ->onFailure(function () {
                \Illuminate\Support\Facades\Log::error('Wöchentliches Backup fehlgeschlagen');
            })
            ->sendOutputTo(storage_path('logs/backup_cron.log'));
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
