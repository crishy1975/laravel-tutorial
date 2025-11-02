<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FaelligkeitsService;

class RecalcFaelligkeitAll extends Command
{
    /**
     * Konsolenbefehl (php artisan faelligkeit:recalc-all)
     */
    protected $signature = 'faelligkeit:recalc-all';

    /**
     * Beschreibung für "php artisan list"
     */
    protected $description = 'Berechnet Fälligkeit für alle aktiven Monate neu und aktualisiert das Flag pro Gebäude.';

    /**
     * Ausführung: ruft unseren Service im Batch-Modus auf.
     */
    public function handle(FaelligkeitsService $svc): int
    {
        $this->info('Starte Fälligkeits-Neuberechnung…');

        // Der Service soll eine Gesamtzahl zurückgeben (berechnete Gebäude)
        $processed = $svc->recalcForAll();

        $this->info("Fertig. Neuberechnet: {$processed} Gebäude.");

        return self::SUCCESS;
    }
}
