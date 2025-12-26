<?php

namespace App\Console\Commands;

use App\Services\Import\TimelineImportService;
use Illuminate\Console\Command;

/**
 * Importiert Timeline/Reinigungsdaten aus DatumAusfuehrung.xml
 * 
 * Verwendung:
 * php artisan import:timeline storage/import/DatumAusfuehrung.xml
 * php artisan import:timeline storage/import/DatumAusfuehrung.xml --dry-run
 * php artisan import:timeline storage/import/DatumAusfuehrung.xml --min-jahr=2020
 * php artisan import:timeline storage/import/DatumAusfuehrung.xml --force
 */
class ImportTimelineCommand extends Command
{
    protected $signature = 'import:timeline 
                            {file? : Pfad zur XML-Datei (Standard: storage/import/DatumAusfuehrung.xml)}
                            {--dry-run : Nur simulieren, nichts speichern}
                            {--force : Bestehende Einträge überschreiben}
                            {--min-jahr=2024 : Nur ab diesem Jahr importieren}';

    protected $description = 'Importiert Timeline/Reinigungsdaten aus DatumAusfuehrung.xml';

    public function handle(TimelineImportService $service): int
    {
        $file = $this->argument('file') ?? storage_path('import/DatumAusfuehrung.xml');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');
        $minJahr = (int) $this->option('min-jahr');

        if (!file_exists($file)) {
            $this->error("❌ Datei nicht gefunden: $file");
            $this->line("");
            $this->info("Hinweis: Exportiere die DatumAusfuehrung-Tabelle aus Access als XML:");
            $this->line("  Rechtsklick auf DatumAusfuehrung → Exportieren → XML");
            return 1;
        }

        $this->info("═══════════════════════════════════════════════════════");
        $this->info("📅 Timeline-Import (Reinigungen)");
        $this->info("═══════════════════════════════════════════════════════");
        $this->line("Datei: $file");
        $this->line("Min. Jahr: $minJahr (ältere werden übersprungen)");
        
        if ($dryRun) {
            $this->warn("🔍 DRY-RUN Modus - nichts wird gespeichert");
        }
        if ($force) {
            $this->warn("⚠️  FORCE Modus - Duplikate werden nicht geprüft");
        }

        $this->newLine();
        $this->info("🏢 Lade Gebäude-Referenzen...");
        
        // Service konfigurieren
        $service->configure($dryRun, !$force, $minJahr);
        $service->buildLookups();

        $this->info("📄 Importiere Timeline-Einträge...");
        
        $startTime = microtime(true);
        $count = $service->importTimelines($file);
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
                ['⏭️  Übersprungen (Duplikate)', $stats['skipped']],
                ['🗓️  Gefiltert (< ' . $minJahr . ')', $stats['filtered']],
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
