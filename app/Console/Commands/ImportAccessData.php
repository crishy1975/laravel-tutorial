<?php

namespace App\Console\Commands;

use App\Services\Import\AccessImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Access-Datenbank Import Command - MASTER
 * 
 * Importiert ALLE Daten aus XML-Exports der alten Access-Datenbank.
 * 
 * Verwendung:
 *   php artisan import:access                     # Interaktives Menü
 *   php artisan import:access --all               # Alles importieren
 *   php artisan import:access --adressen          # Nur Adressen
 *   php artisan import:access --gebaeude          # Nur Gebäude
 *   php artisan import:access --artikel           # Nur Artikel
 *   php artisan import:access --rechnungen        # Nur Rechnungen
 *   php artisan import:access --positionen        # Nur Positionen
 *   php artisan import:access --timeline          # Nur Timeline
 *   php artisan import:access --fix-namen         # Nur Gebäude-Namen fixen
 *   php artisan import:access --timeline --min-jahr=2020
 *   php artisan import:access --all --dry-run     # Testlauf
 *   php artisan import:access --all --force       # Bestehende überschreiben
 */
class ImportAccessData extends Command
{
    protected $signature = 'import:access 
                            {--all : Alle Tabellen importieren}
                            {--adressen : Nur Adressen importieren}
                            {--gebaeude : Nur Gebäude importieren}
                            {--artikel : Nur Artikel importieren}
                            {--rechnungen : Nur Rechnungen importieren}
                            {--positionen : Nur Rechnungspositionen importieren}
                            {--timeline : Nur Timeline importieren}
                            {--fix-namen : Nur Gebäude-Namen fixen}
                            {--dry-run : Testlauf ohne Speichern}
                            {--force : Bestehende Einträge überschreiben}
                            {--min-jahr=2024 : Minimum Jahr für Timeline}
                            {--path= : Pfad zum XML-Ordner (Standard: storage/import)}';

    protected $description = 'Importiert Daten aus Access-XML-Exports (Adressen, Gebäude, Artikel, Rechnungen, Positionen, Timeline)';

    protected AccessImportService $importer;
    protected string $importPath;

    // XML-Dateien Mapping
    protected array $xmlFiles = [
        'adressen'   => 'Adresse.xml',
        'gebaeude'   => 'Gebaeude.xml',
        'artikel'    => 'Artikel.xml',
        'rechnungen' => 'FatturaPA.xml',
        'positionen' => 'ArtikelFatturaPAAbfrage.xml',
        'timeline'   => 'DatumAusfuehrung.xml',
    ];

    public function __construct(AccessImportService $importer)
    {
        parent::__construct();
        $this->importer = $importer;
    }

    public function handle(): int
    {
        $this->importPath = $this->option('path') ?: storage_path('import');

        $dryRun = $this->option('dry-run');
        $skipExisting = !$this->option('force');
        $minJahr = (int) $this->option('min-jahr');

        $this->importer->configure($dryRun, $skipExisting, $minJahr);

        // Header
        $this->newLine();
        $this->info('╔══════════════════════════════════════════════════════════╗');
        $this->info('║           ACCESS → LARAVEL MASTER-IMPORT                 ║');
        $this->info('╚══════════════════════════════════════════════════════════╝');
        $this->newLine();

        if ($dryRun) {
            $this->warn('🔸 DRY-RUN MODUS - Es werden keine Daten gespeichert!');
            $this->newLine();
        }

        $this->info("📁 Import-Pfad: {$this->importPath}");
        $this->newLine();

        // Prüfen ob Pfad existiert
        if (!is_dir($this->importPath)) {
            $this->error("❌ Import-Ordner nicht gefunden: {$this->importPath}");
            $this->info("   Erstelle den Ordner und lege die XML-Dateien ab:");
            foreach ($this->xmlFiles as $name => $file) {
                $this->info("   - {$file}");
            }
            return Command::FAILURE;
        }

        // Was soll importiert werden?
        $tasks = $this->determineTasks();

        if (empty($tasks)) {
            $this->warn('Keine Import-Aufgabe ausgewählt.');
            return Command::SUCCESS;
        }

        // Bestätigung
        $this->info('📋 Folgende Importe werden ausgeführt:');
        foreach ($tasks as $task) {
            if ($task === 'fix-namen') {
                $this->line("   ✅ fix-namen (aus Datenbank)");
            } else {
                $file = $this->xmlFiles[$task] ?? '?';
                $exists = file_exists("{$this->importPath}/{$file}");
                $status = $exists ? '✅' : '❌';
                $this->line("   {$status} {$task} ({$file})");
            }
        }
        $this->newLine();

        if (!$dryRun && !$this->confirm('Fortfahren?', true)) {
            $this->info('Import abgebrochen.');
            return Command::SUCCESS;
        }

        // Import durchführen
        $this->newLine();
        $startTime = microtime(true);

        try {
            DB::beginTransaction();

            foreach ($tasks as $task) {
                $this->runImportTask($task);
            }

            if ($dryRun) {
                DB::rollBack();
                $this->warn('🔸 Rollback (Dry-Run) - Keine Änderungen gespeichert.');
            } else {
                DB::commit();
                $this->info('✅ Transaktion committed.');
            }

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("❌ Fehler: {$e->getMessage()}");
            $this->error("   Rollback durchgeführt - keine Änderungen gespeichert.");
            return Command::FAILURE;
        }

        // Zusammenfassung
        $duration = round(microtime(true) - $startTime, 2);
        $this->printSummary($duration);

        return Command::SUCCESS;
    }

    /**
     * Bestimmt welche Tabellen importiert werden sollen
     */
    protected function determineTasks(): array
    {
        // Explizite Optionen prüfen
        if ($this->option('all')) {
            return ['adressen', 'gebaeude', 'artikel', 'rechnungen', 'positionen', 'timeline', 'fix-namen'];
        }

        $tasks = [];
        if ($this->option('adressen')) $tasks[] = 'adressen';
        if ($this->option('gebaeude')) $tasks[] = 'gebaeude';
        if ($this->option('artikel')) $tasks[] = 'artikel';
        if ($this->option('rechnungen')) $tasks[] = 'rechnungen';
        if ($this->option('positionen')) $tasks[] = 'positionen';
        if ($this->option('timeline')) $tasks[] = 'timeline';
        if ($this->option('fix-namen')) $tasks[] = 'fix-namen';

        if (!empty($tasks)) {
            return $tasks;
        }

        // Interaktives Menü
        return $this->interactiveMenu();
    }

    /**
     * Interaktives Auswahlmenü
     */
    protected function interactiveMenu(): array
    {
        $this->info('Was möchtest du importieren?');
        $this->newLine();

        $choices = [
            'all'        => '📦 Alles (empfohlen für Erstimport)',
            'adressen'   => '📋 Nur Adressen',
            'gebaeude'   => '🏢 Nur Gebäude',
            'artikel'    => '📦 Nur Artikel',
            'rechnungen' => '🧾 Nur Rechnungen',
            'positionen' => '📝 Nur Rechnungspositionen',
            'timeline'   => '📅 Nur Timeline (Reinigungen)',
            'fix-namen'  => '🔧 Nur Gebäude-Namen fixen',
            'custom'     => '⚙️  Benutzerdefiniert...',
        ];

        $choice = $this->choice('Auswahl', array_values($choices), 0);
        $key = array_search($choice, $choices);

        if ($key === 'all') {
            return ['adressen', 'gebaeude', 'artikel', 'rechnungen', 'positionen', 'timeline', 'fix-namen'];
        }

        if ($key === 'custom') {
            $available = ['adressen', 'gebaeude', 'artikel', 'rechnungen', 'positionen', 'timeline', 'fix-namen'];
            return $this->choice(
                'Wähle Importe (mehrere mit Komma trennen)',
                $available,
                null,
                null,
                true
            );
        }

        return [$key];
    }

    /**
     * Führt einen Import-Task aus
     */
    protected function runImportTask(string $task): void
    {
        // fix-namen braucht keine XML-Datei
        if ($task === 'fix-namen') {
            $this->info("   ⏳ Gebäude-Namen fixen...");
            $count = $this->importer->fixGebaeudeNamen();
            $stats = $this->importer->getStats()['fix_namen'] ?? [];
            $imported = $stats['imported'] ?? 0;
            $skipped = $stats['skipped'] ?? 0;
            $errors = $stats['errors'] ?? 0;
            $this->line("      ✅ {$imported} korrigiert, ⏭️  {$skipped} übersprungen, ❌ {$errors} Fehler");
            return;
        }

        $file = $this->xmlFiles[$task] ?? null;
        if (!$file) {
            $this->warn("   ⚠️  Unbekannter Task: {$task}");
            return;
        }

        $path = "{$this->importPath}/{$file}";

        if (!file_exists($path)) {
            $this->warn("   ⚠️  {$task}: Datei nicht gefunden ({$file}) - übersprungen");
            return;
        }

        $this->info("   ⏳ {$task} importieren...");

        $count = match ($task) {
            'adressen'   => $this->importer->importAdressen($path),
            'gebaeude'   => $this->importer->importGebaeude($path),
            'artikel'    => $this->importer->importArtikel($path),
            'rechnungen' => $this->importer->importRechnungen($path),
            'positionen' => $this->importer->importPositionen($path),
            'timeline'   => $this->importer->importTimeline($path),
            default      => 0,
        };

        $stats = $this->importer->getStats()[$task] ?? [];
        $imported = $stats['imported'] ?? 0;
        $skipped = $stats['skipped'] ?? 0;
        $filtered = $stats['filtered'] ?? 0;
        $errors = $stats['errors'] ?? 0;

        $info = "✅ {$imported} importiert, ⏭️  {$skipped} übersprungen";
        if ($filtered > 0) {
            $info .= ", 🗓️  {$filtered} gefiltert";
        }
        $info .= ", ❌ {$errors} Fehler";

        $this->line("      {$info}");
    }

    /**
     * Druckt Zusammenfassung
     */
    protected function printSummary(float $duration): void
    {
        $this->newLine();
        $this->info('╔══════════════════════════════════════════════════════════╗');
        $this->info('║                    ZUSAMMENFASSUNG                       ║');
        $this->info('╚══════════════════════════════════════════════════════════╝');
        $this->newLine();

        $stats = $this->importer->getStats();
        $totalImported = 0;
        $totalSkipped = 0;
        $totalFiltered = 0;
        $totalErrors = 0;

        $tableData = [];
        foreach ($stats as $table => $stat) {
            if ($stat['imported'] === 0 && $stat['skipped'] === 0 && ($stat['errors'] ?? 0) === 0) {
                continue; // Leere Zeilen ausblenden
            }

            $totalImported += $stat['imported'];
            $totalSkipped += $stat['skipped'];
            $totalFiltered += $stat['filtered'] ?? 0;
            $totalErrors += $stat['errors'];

            $filtered = isset($stat['filtered']) && $stat['filtered'] > 0 ? $stat['filtered'] : '-';

            $tableData[] = [
                ucfirst(str_replace('_', ' ', $table)),
                $stat['imported'],
                $stat['skipped'],
                $filtered,
                $stat['errors'],
            ];
        }

        if (!empty($tableData)) {
            $this->table(
                ['Tabelle', 'Importiert', 'Übersprungen', 'Gefiltert', 'Fehler'],
                $tableData
            );
        }

        $this->newLine();
        $this->info("📊 Gesamt: {$totalImported} importiert, {$totalSkipped} übersprungen, {$totalErrors} Fehler");
        $this->info("⏱️  Dauer: {$duration} Sekunden");

        // Fehler anzeigen
        $errors = $this->importer->getErrors();
        if (!empty($errors)) {
            $logFile = storage_path('logs/import_errors_' . date('Y-m-d_His') . '.log');
            $logContent = "Import-Fehler vom " . date('d.m.Y H:i:s') . "\n";
            $logContent .= str_repeat('=', 60) . "\n\n";

            foreach ($errors as $error) {
                $logContent .= "[{$error['table']}] ID {$error['id']}: {$error['message']}\n";
            }

            file_put_contents($logFile, $logContent);

            $this->newLine();
            $this->warn('⚠️  Fehler-Details:');

            $showAll = $this->option('verbose') || $this->output->isVerbose();
            $maxShow = $showAll ? count($errors) : 20;

            foreach (array_slice($errors, 0, $maxShow) as $error) {
                $this->line("   [{$error['table']}] ID {$error['id']}: {$error['message']}");
            }

            if (count($errors) > $maxShow) {
                $this->line("   ... und " . (count($errors) - $maxShow) . " weitere Fehler");
            }

            $this->newLine();
            $this->info("📄 Alle " . count($errors) . " Fehler gespeichert in:");
            $this->line("   {$logFile}");
        }

        $this->newLine();
    }
}
