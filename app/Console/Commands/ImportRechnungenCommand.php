<?php

namespace App\Console\Commands;

use App\Services\Import\RechnungImportService;
use Illuminate\Console\Command;

/**
 * Importiert Rechnungen aus vereinfachtem XML-Export
 * 
 * Verwendung:
 * php artisan import:rechnungen storage/import/FatturaPA.xml
 * php artisan import:rechnungen storage/import/FatturaPA.xml --dry-run
 * php artisan import:rechnungen storage/import/FatturaPA.xml --force
 */
class ImportRechnungenCommand extends Command
{
    protected $signature = 'import:rechnungen 
                            {file? : Pfad zur XML-Datei (Standard: storage/import/FatturaPA.xml)}
                            {--dry-run : Nur simulieren, nichts speichern}
                            {--force : Bestehende Rechnungen überschreiben}';

    protected $description = 'Importiert Rechnungen aus XML-Export (vereinfachte FatturaPA-Tabelle)';

    public function handle(RechnungImportService $service): int
    {
        $file = $this->argument('file') ?? storage_path('import/FatturaPA.xml');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        if (!file_exists($file)) {
            $this->error("❌ Datei nicht gefunden: $file");
            $this->line("");
            $this->info("Hinweis: Exportiere die FatturaPA-Tabelle aus Access als XML:");
            $this->line("  Rechtsklick auf FatturaPA → Exportieren → XML");
            return 1;
        }

        $this->info("═══════════════════════════════════════════════════════");
        $this->info("📥 Rechnungs-Import");
        $this->info("═══════════════════════════════════════════════════════");
        $this->line("Datei: $file");
        
        if ($dryRun) {
            $this->warn("🔍 DRY-RUN Modus - nichts wird gespeichert");
        }
        if ($force) {
            $this->warn("⚠️  FORCE Modus - bestehende werden überschrieben");
        }

        $this->newLine();
        $this->info("🏢 Lade Gebäude-Referenzen...");
        
        // Service konfigurieren
        $service->configure($dryRun, !$force);
        $service->buildLookups();

        $this->info("📄 Importiere Rechnungen...");
        
        $startTime = microtime(true);
        $count = $service->importRechnungen($file);
        $duration = round(microtime(true) - $startTime, 2);

        $stats = $service->getStats();
        $errors = $service->getErrors();

        $this->newLine();
        $this->info("═══════════════════════════════════════════════════════");
        $this->info("📊 Ergebnis:");
        $this->info("═══════════════════════════════════════════════════════");
        
        $this->table(
            ['Metrik', 'Anzahl'],
            [
                ['✅ Importiert', $stats['imported']],
                ['⏭️  Übersprungen', $stats['skipped']],
                ['❌ Fehler', $stats['errors']],
                ['⏱️  Dauer', "{$duration}s"],
            ]
        );

        if (count($errors) > 0) {
            $this->newLine();
            $this->warn("⚠️  Fehler-Details:");
            foreach (array_slice($errors, 0, 10) as $err) {
                $this->line("   ID {$err['id']}: {$err['message']}");
            }
            if (count($errors) > 10) {
                $this->line("   ... und " . (count($errors) - 10) . " weitere");
            }
        }

        $this->newLine();
        if ($dryRun) {
            $this->info("💡 Um tatsächlich zu importieren, ohne --dry-run ausführen.");
        }

        return $stats['errors'] > 0 ? 1 : 0;
    }
}
