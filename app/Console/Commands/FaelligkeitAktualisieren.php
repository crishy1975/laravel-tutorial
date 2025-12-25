<?php

namespace App\Console\Commands;

use App\Services\FaelligkeitsService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class FaelligkeitAktualisieren extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'faelligkeit:aktualisieren 
                            {--datum= : Stichtag im Format d.m.Y oder Y-m-d}
                            {--dry-run : Nur simulieren, nichts speichern}';

    /**
     * The console command description.
     */
    protected $description = 'Aktualisiert die Fälligkeits-Flags aller Gebäude';

    /**
     * Execute the console command.
     */
    public function handle(FaelligkeitsService $service): int
    {
        $stichtag = now();
        
        if ($datum = $this->option('datum')) {
            try {
                // Versuche verschiedene Formate
                if (str_contains($datum, '.')) {
                    $stichtag = Carbon::createFromFormat('d.m.Y', $datum)->startOfDay();
                } else {
                    $stichtag = Carbon::parse($datum)->startOfDay();
                }
            } catch (\Exception $e) {
                $this->error("Ungültiges Datum: {$datum}");
                return Command::FAILURE;
            }
        }
        
        $this->info("Stichtag: {$stichtag->format('d.m.Y')}");
        
        if ($this->option('dry-run')) {
            $this->warn('DRY-RUN Modus - es werden keine Änderungen gespeichert!');
            // TODO: Dry-run Implementierung
            return Command::SUCCESS;
        }
        
        $this->info('Aktualisiere Fälligkeiten...');
        
        $stats = $service->aktualisiereAlle($stichtag);
        
        $this->newLine();
        $this->table(
            ['Metrik', 'Anzahl'],
            [
                ['Gesamt geprüft', $stats['gesamt']],
                ['Fällig', $stats['faellig']],
                ['Nicht fällig', $stats['nicht_faellig']],
                ['Geändert', $stats['geaendert']],
                ['Fehler', $stats['fehler']],
            ]
        );
        
        if ($stats['fehler'] > 0) {
            $this->warn("Es gab {$stats['fehler']} Fehler. Siehe Log für Details.");
        }
        
        $this->info('Fertig!');
        
        return Command::SUCCESS;
    }
}
