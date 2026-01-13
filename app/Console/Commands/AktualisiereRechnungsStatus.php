<?php
/**
 * ════════════════════════════════════════════════════════════════════════════
 * DATEI: AktualisiereRechnungsStatus.php
 * PFAD:  app/Console/Commands/AktualisiereRechnungsStatus.php
 * ════════════════════════════════════════════════════════════════════════════
 * 
 * Einmalig ausführen mit: php artisan rechnungen:status-aktualisieren
 */

namespace App\Console\Commands;

use App\Models\Eingangsrechnung;
use Illuminate\Console\Command;

class AktualisiereRechnungsStatus extends Command
{
    protected $signature = 'rechnungen:status-aktualisieren';
    
    protected $description = 'Aktualisiert den Status bestehender Rechnungen basierend auf der Zahlungsart (modalita_pagamento)';

    public function handle(): int
    {
        $this->info('Aktualisiere Rechnungsstatus basierend auf Zahlungsart...');
        $this->newLine();

        $rechnungen = Eingangsrechnung::whereNotNull('modalita_pagamento')->get();
        
        $updated = 0;
        $barKarte = 0;
        $bank = 0;

        foreach ($rechnungen as $rechnung) {
            $methode = Eingangsrechnung::MODALITA_TO_METHODE[$rechnung->modalita_pagamento] ?? 'bank';
            
            $neuerStatus = in_array($methode, ['bar', 'karte']) ? 'bezahlt' : 'offen';
            $alterStatus = $rechnung->status;
            
            // Nur aktualisieren wenn sich was ändert
            if ($alterStatus !== $neuerStatus || $rechnung->zahlungsmethode !== $methode) {
                $rechnung->zahlungsmethode = $methode;
                $rechnung->status = $neuerStatus;
                
                if ($neuerStatus === 'bezahlt') {
                    $rechnung->bezahlt_am = $rechnung->rechnungsdatum ?? now();
                    $barKarte++;
                } else {
                    $rechnung->bezahlt_am = null;
                    $bank++;
                }
                
                $rechnung->save();
                $updated++;
                
                $this->line("  [{$rechnung->rechnungsnummer}] {$alterStatus} → {$neuerStatus} ({$methode})");
            }
        }

        $this->newLine();
        $this->info("Fertig! {$updated} Rechnungen aktualisiert.");
        $this->line("  - Bar/Karte (bezahlt): {$barKarte}");
        $this->line("  - Bank (offen): {$bank}");

        return Command::SUCCESS;
    }
}
