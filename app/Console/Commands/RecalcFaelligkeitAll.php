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

        // Service aufrufen - korrekte Methode: aktualisiereAlle()
        $stats = $svc->aktualisiereAlle();

        $this->info("Fertig!");
        $this->table(
            ['Gesamt', 'Fällig', 'Nicht fällig', 'Geändert', 'Fehler', 'Dauer'],
            [[
                $stats['gesamt'],
                $stats['faellig'],
                $stats['nicht_faellig'],
                $stats['geaendert'],
                $stats['fehler'],
                $stats['dauer_sekunden'] . 's'
            ]]
        );

        return self::SUCCESS;
    }
}
