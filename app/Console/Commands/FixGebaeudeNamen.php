<?php

namespace App\Console\Commands;

use App\Models\Gebaeude;
use App\Models\Adresse;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Füllt fehlende Gebäude-Daten mit Daten vom Rechnungsempfänger
 * 
 * Verwendung:
 *   php artisan import:fix-gebaeude           # Alle ohne Name fixen
 *   php artisan import:fix-gebaeude --dry-run # Nur anzeigen was passieren würde
 */
class FixGebaeudeNamen extends Command
{
    protected $signature = 'import:fix-gebaeude 
                            {--dry-run : Nur anzeigen, nicht ändern}
                            {--force : Auch Gebäude mit Namen überschreiben}';

    protected $description = 'Füllt fehlende Gebäude-Namen und Adressen vom Rechnungsempfänger';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info('╔══════════════════════════════════════════════════════════╗');
        $this->info('║     GEBÄUDE-DATEN VOM RECHNUNGSEMPFÄNGER ÜBERNEHMEN     ║');
        $this->info('╚══════════════════════════════════════════════════════════╝');
        $this->newLine();

        if ($dryRun) {
            $this->warn('🔸 DRY-RUN MODUS - Es werden keine Daten geändert!');
            $this->newLine();
        }

        // Gebäude ohne Namen finden
        $query = Gebaeude::query()
            ->whereNotNull('rechnungsempfaenger_id');

        if (!$force) {
            $query->where(function ($q) {
                $q->whereNull('gebaeude_name')
                  ->orWhere('gebaeude_name', '')
                  ->orWhere('gebaeude_name', '?');
            });
        }

        $gebaeude = $query->get();

        $this->info("📊 Gefunden: {$gebaeude->count()} Gebäude " . ($force ? '(alle)' : 'ohne Namen'));
        $this->newLine();

        if ($gebaeude->isEmpty()) {
            $this->info('✅ Keine Gebäude zu aktualisieren.');
            return Command::SUCCESS;
        }

        $updated = 0;
        $skipped = 0;
        $errors = 0;

        $this->output->progressStart($gebaeude->count());

        foreach ($gebaeude as $geb) {
            try {
                $result = $this->fixGebaeude($geb, $dryRun);
                
                if ($result) {
                    $updated++;
                } else {
                    $skipped++;
                }
            } catch (\Exception $e) {
                $errors++;
                $this->newLine();
                $this->error("   Fehler bei Gebäude #{$geb->id}: {$e->getMessage()}");
            }

            $this->output->progressAdvance();
        }

        $this->output->progressFinish();
        $this->newLine();

        // Zusammenfassung
        $this->info('╔══════════════════════════════════════════════════════════╗');
        $this->info('║                    ZUSAMMENFASSUNG                       ║');
        $this->info('╚══════════════════════════════════════════════════════════╝');
        $this->newLine();

        $this->table(
            ['Aktion', 'Anzahl'],
            [
                ['✅ Aktualisiert', $updated],
                ['⏭️  Übersprungen', $skipped],
                ['❌ Fehler', $errors],
            ]
        );

        if ($dryRun) {
            $this->newLine();
            $this->warn('🔸 DRY-RUN - Führe ohne --dry-run aus um Änderungen zu speichern.');
        }

        return Command::SUCCESS;
    }

    /**
     * Einzelnes Gebäude fixen
     */
    protected function fixGebaeude(Gebaeude $geb, bool $dryRun): bool
    {
        // Rechnungsempfänger laden
        $re = Adresse::find($geb->rechnungsempfaenger_id);

        if (!$re) {
            return false;
        }

        $changes = [];

        // Name übernehmen wenn leer
        if (empty($geb->gebaeude_name) || $geb->gebaeude_name === '?') {
            $changes['gebaeude_name'] = $re->name;
        }

        // Straße übernehmen wenn leer
        if (empty($geb->strasse)) {
            $changes['strasse'] = $re->strasse;
        }

        // Hausnummer übernehmen wenn leer
        if (empty($geb->hausnummer)) {
            $changes['hausnummer'] = $re->hausnummer;
        }

        // PLZ übernehmen wenn leer
        if (empty($geb->plz)) {
            $changes['plz'] = $re->plz;
        }

        // Wohnort übernehmen wenn leer
        if (empty($geb->wohnort)) {
            $changes['wohnort'] = $re->wohnort;
        }

        // Land übernehmen wenn leer
        if (empty($geb->land)) {
            $changes['land'] = $re->land ?: 'IT';
        }

        // Keine Änderungen nötig?
        if (empty($changes)) {
            return false;
        }

        // Änderungen anwenden
        if (!$dryRun) {
            $geb->update($changes);
        }

        return true;
    }
}