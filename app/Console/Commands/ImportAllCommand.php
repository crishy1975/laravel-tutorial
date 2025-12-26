<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

/**
 * Master-Import: Führt alle Imports in der richtigen Reihenfolge aus
 * 
 * Verwendung:
 *   php artisan import:all                    # Alles importieren
 *   php artisan import:all --dry-run          # Nur simulieren
 *   php artisan import:all --skip-timeline    # Timeline überspringen
 *   php artisan import:all --skip-rechnungen  # Rechnungen überspringen
 */
class ImportAllCommand extends Command
{
    protected $signature = 'import:all 
                            {--dry-run : Nur simulieren, nichts speichern}
                            {--force : Bestehende Einträge überschreiben}
                            {--skip-timeline : Timeline-Import überspringen}
                            {--skip-rechnungen : Rechnungen-Import überspringen}
                            {--skip-paoloweb : PaoloWeb-Import überspringen}
                            {--min-jahr=2024 : Minimum Jahr für Timeline}
                            {--path= : Pfad zum Import-Ordner (Standard: storage/import)}';

    protected $description = 'Führt alle Imports in der richtigen Reihenfolge aus';

    protected array $stats = [
        'success' => [],
        'skipped' => [],
        'failed'  => [],
    ];

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');
        $path = $this->option('path') ?: storage_path('import');
        $minJahr = $this->option('min-jahr');

        // Header
        $this->newLine();
        $this->info('╔══════════════════════════════════════════════════════════╗');
        $this->info('║            MASTER-IMPORT: ALLE DATEN                     ║');
        $this->info('╚══════════════════════════════════════════════════════════╝');
        $this->newLine();

        if ($dryRun) {
            $this->warn('🔸 DRY-RUN MODUS - Es werden keine Daten gespeichert!');
            $this->newLine();
        }

        $this->info("📁 Import-Pfad: {$path}");
        $this->newLine();

        // Prüfen ob Pfad existiert
        if (!is_dir($path)) {
            $this->error("❌ Import-Ordner nicht gefunden: {$path}");
            return Command::FAILURE;
        }

        // Verfügbare Dateien anzeigen
        $this->info('📋 Verfügbare Dateien:');
        $files = [
            'Adresse.xml'          => 'Adressen',
            'Gebaeude.xml'         => 'Gebäude',
            'Artikel.xml'          => 'Artikel',
            'FatturaPA.xml'        => 'Rechnungen',
            'DatumAusfuehrung.xml' => 'Timeline',
            'paoloWeb.xml'         => 'PaoloWeb',
        ];
        
        foreach ($files as $file => $label) {
            $exists = file_exists("{$path}/{$file}");
            $status = $exists ? '✅' : '❌';
            $this->line("   {$status} {$label} ({$file})");
        }
        $this->newLine();

        $startTime = microtime(true);

        // ═══════════════════════════════════════════════════════════
        // 1. ACCESS-IMPORT (Adressen, Gebäude, Artikel)
        // ═══════════════════════════════════════════════════════════
        $this->runStep('1/5', 'Access-Daten (Adressen, Gebäude, Artikel)', function () use ($dryRun, $force, $path) {
            $options = [
                '--all'  => true,
                '--path' => $path,
            ];
            
            if ($dryRun) $options['--dry-run'] = true;
            if ($force) $options['--force'] = true;

            return Artisan::call('import:access', $options, $this->output);
        });

        // ═══════════════════════════════════════════════════════════
        // 2. RECHNUNGEN-IMPORT
        // ═══════════════════════════════════════════════════════════
        if (!$this->option('skip-rechnungen')) {
            $this->runStep('2/5', 'Rechnungen (FatturaPA)', function () use ($dryRun, $force, $path) {
                $file = "{$path}/FatturaPA.xml";
                
                if (!file_exists($file)) {
                    $this->warn("   ⚠️  FatturaPA.xml nicht gefunden - übersprungen");
                    return -1; // Skip
                }

                $options = ['file' => $file];
                if ($dryRun) $options['--dry-run'] = true;
                if ($force) $options['--force'] = true;

                return Artisan::call('import:rechnungen', $options, $this->output);
            });
        } else {
            $this->stats['skipped'][] = 'Rechnungen (--skip-rechnungen)';
            $this->line('   ⏭️  Rechnungen übersprungen (--skip-rechnungen)');
        }

        // ═══════════════════════════════════════════════════════════
        // 3. TIMELINE-IMPORT
        // ═══════════════════════════════════════════════════════════
        if (!$this->option('skip-timeline')) {
            $this->runStep('3/5', 'Timeline (Reinigungen)', function () use ($dryRun, $force, $path, $minJahr) {
                $file = "{$path}/DatumAusfuehrung.xml";
                
                if (!file_exists($file)) {
                    $this->warn("   ⚠️  DatumAusfuehrung.xml nicht gefunden - übersprungen");
                    return -1; // Skip
                }

                $options = [
                    'file' => $file,
                    '--min-jahr' => $minJahr,
                ];
                if ($dryRun) $options['--dry-run'] = true;
                if ($force) $options['--force'] = true;

                return Artisan::call('import:timeline', $options, $this->output);
            });
        } else {
            $this->stats['skipped'][] = 'Timeline (--skip-timeline)';
            $this->line('   ⏭️  Timeline übersprungen (--skip-timeline)');
        }

        // ═══════════════════════════════════════════════════════════
        // 4. PAOLOWEB-IMPORT
        // ═══════════════════════════════════════════════════════════
        if (!$this->option('skip-paoloweb')) {
            $this->runStep('4/5', 'PaoloWeb', function () use ($dryRun, $path) {
                $file = "{$path}/paoloWeb.xml";
                
                if (!file_exists($file)) {
                    $this->warn("   ⚠️  paoloWeb.xml nicht gefunden - übersprungen");
                    return -1; // Skip
                }

                $options = [];
                if ($dryRun) $options['--dry-run'] = true;

                return Artisan::call('import:paoloweb', $options, $this->output);
            });
        } else {
            $this->stats['skipped'][] = 'PaoloWeb (--skip-paoloweb)';
            $this->line('   ⏭️  PaoloWeb übersprungen (--skip-paoloweb)');
        }

        // ═══════════════════════════════════════════════════════════
        // 5. FIX GEBÄUDE-NAMEN
        // ═══════════════════════════════════════════════════════════
        $this->runStep('5/5', 'Gebäude-Namen fixen', function () use ($dryRun) {
            $options = [];
            if ($dryRun) $options['--dry-run'] = true;

            return Artisan::call('import:fix-gebaeude', $options, $this->output);
        });

        // ═══════════════════════════════════════════════════════════
        // ZUSAMMENFASSUNG
        // ═══════════════════════════════════════════════════════════
        $duration = round(microtime(true) - $startTime, 2);
        
        $this->newLine();
        $this->info('╔══════════════════════════════════════════════════════════╗');
        $this->info('║                    ZUSAMMENFASSUNG                       ║');
        $this->info('╚══════════════════════════════════════════════════════════╝');
        $this->newLine();

        $this->table(
            ['Status', 'Schritte'],
            [
                ['✅ Erfolgreich', count($this->stats['success']) . ' (' . implode(', ', $this->stats['success']) . ')'],
                ['⏭️  Übersprungen', count($this->stats['skipped']) . (count($this->stats['skipped']) ? ' (' . implode(', ', $this->stats['skipped']) . ')' : '')],
                ['❌ Fehlgeschlagen', count($this->stats['failed']) . (count($this->stats['failed']) ? ' (' . implode(', ', $this->stats['failed']) . ')' : '')],
            ]
        );

        $this->newLine();
        $this->info("⏱️  Gesamtdauer: {$duration} Sekunden");

        if ($dryRun) {
            $this->newLine();
            $this->warn('🔸 DRY-RUN - Führe ohne --dry-run aus um Daten zu speichern.');
        }

        return count($this->stats['failed']) > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Führt einen Import-Schritt aus
     */
    protected function runStep(string $step, string $name, callable $callback): void
    {
        $this->newLine();
        $this->info("═══════════════════════════════════════════════════════════");
        $this->info("📦 Schritt {$step}: {$name}");
        $this->info("═══════════════════════════════════════════════════════════");
        $this->newLine();

        try {
            $result = $callback();
            
            if ($result === -1) {
                // Übersprungen (Datei nicht gefunden)
                $this->stats['skipped'][] = $name;
            } elseif ($result === 0) {
                $this->stats['success'][] = $name;
                $this->newLine();
                $this->info("   ✅ {$name} abgeschlossen");
            } else {
                $this->stats['failed'][] = $name;
                $this->newLine();
                $this->error("   ❌ {$name} mit Fehlern beendet");
            }
        } catch (\Exception $e) {
            $this->stats['failed'][] = $name;
            $this->error("   ❌ Fehler: {$e->getMessage()}");
        }
    }
}
