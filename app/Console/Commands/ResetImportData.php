<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Löscht alle importierten Daten für einen Neu-Import
 * 
 * Verwendung:
 *   php artisan import:reset              # Interaktiv mit Bestätigung
 *   php artisan import:reset --force      # Ohne Bestätigung
 *   php artisan import:reset --dry-run    # Nur anzeigen was gelöscht würde
 */
class ResetImportData extends Command
{
    protected $signature = 'import:reset 
                            {--force : Ohne Bestätigung ausführen}
                            {--dry-run : Nur anzeigen was gelöscht würde}
                            {--keep-settings : Preis-Aufschläge und Fattura-Profile behalten}';

    protected $description = 'Löscht alle importierten Daten (Adressen, Gebäude, Rechnungen, etc.) für einen Neu-Import';

    /**
     * Tabellen in der Reihenfolge in der sie gelöscht werden müssen
     * (wegen Foreign Key Constraints - Kinder zuerst!)
     */
    protected array $tables = [
        // 1. Abhängige Tabellen zuerst (keine Foreign Keys auf andere)
        'bank_buchungen',           // Referenziert rechnungen
        'rechnung_positionen',      // Referenziert rechnungen, artikel_gebaeude
        'rechnungen',               // Referenziert gebaeude, adressen
        'timelines',                // Referenziert gebaeude
        'tourgebaeude',             // Pivot: touren <-> gebaeude
        'artikel_gebaeude',         // Referenziert gebaeude
        'gebaeude_aufschlaege',     // Referenziert gebaeude
        'gebaeude',                 // Referenziert adressen
        'adressen',                 // Basis-Tabelle
    ];

    /**
     * Optionale Tabellen (Settings) - nur mit --keep-settings=false
     */
    protected array $settingsTables = [
        'preis_aufschlaege',
        'fattura_profile',
    ];

    public function handle(): int
    {
        $this->newLine();
        $this->components->warn('⚠️  ACHTUNG: Dieses Kommando löscht ALLE importierten Daten!');
        $this->newLine();

        $isDryRun = $this->option('dry-run');
        $keepSettings = $this->option('keep-settings');

        // Tabellen sammeln
        $tablesToDelete = $this->tables;
        if (!$keepSettings) {
            $tablesToDelete = array_merge($tablesToDelete, $this->settingsTables);
        }

        // Statistik anzeigen
        $this->showStatistics($tablesToDelete);

        if ($isDryRun) {
            $this->components->info('🔍 Dry-Run Modus - keine Daten wurden gelöscht.');
            return Command::SUCCESS;
        }

        // Bestätigung (außer --force)
        if (!$this->option('force')) {
            if (!$this->components->confirm('Wirklich ALLE Daten unwiderruflich löschen?', false)) {
                $this->components->info('Abgebrochen.');
                return Command::SUCCESS;
            }

            // Doppelte Bestätigung
            $confirm = $this->ask('Zur Bestätigung "LÖSCHEN" eingeben');
            if ($confirm !== 'LÖSCHEN') {
                $this->components->info('Abgebrochen.');
                return Command::SUCCESS;
            }
        }

        // Löschen
        $this->newLine();
        $this->components->info('🗑️  Lösche Daten...');

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            foreach ($tablesToDelete as $table) {
                if (Schema::hasTable($table)) {
                    $count = DB::table($table)->count();
                    DB::table($table)->truncate();
                    $this->components->twoColumnDetail($table, "<fg=red>$count Einträge gelöscht</>");
                }
            }

            // Auto-Increment zurücksetzen
            $this->resetAutoIncrements($tablesToDelete);

        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        $this->newLine();
        $this->components->success('✅ Alle Daten wurden gelöscht. Bereit für Neu-Import!');

        $this->newLine();
        $this->components->info('Nächste Schritte:');
        $this->line('  1. XML-Dateien bereitstellen');
        $this->line('  2. php artisan import:access --all');
        $this->newLine();

        return Command::SUCCESS;
    }

    /**
     * Zeigt Statistik der zu löschenden Daten
     */
    protected function showStatistics(array $tables): void
    {
        $this->components->info('📊 Aktuelle Datenstände:');
        $this->newLine();

        $total = 0;
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                $count = DB::table($table)->count();
                $total += $count;

                $color = $count > 0 ? 'yellow' : 'gray';
                $this->components->twoColumnDetail(
                    $table,
                    "<fg=$color>" . number_format($count, 0, ',', '.') . " Einträge</>"
                );
            } else {
                $this->components->twoColumnDetail($table, '<fg=gray>Tabelle existiert nicht</>');
            }
        }

        $this->newLine();
        $this->components->twoColumnDetail(
            '<fg=white;options=bold>GESAMT</>',
            '<fg=red;options=bold>' . number_format($total, 0, ',', '.') . ' Einträge</>'
        );
        $this->newLine();
    }

    /**
     * Setzt Auto-Increment auf 1 zurück
     */
    protected function resetAutoIncrements(array $tables): void
    {
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                try {
                    DB::statement("ALTER TABLE `$table` AUTO_INCREMENT = 1");
                } catch (\Exception $e) {
                    // Ignorieren falls Tabelle keinen Auto-Increment hat
                }
            }
        }
    }
}
