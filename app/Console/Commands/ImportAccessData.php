<?php

namespace App\Console\Commands;

use App\Services\Import\AccessImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Access-Datenbank Import Command
 * 
 * Importiert Daten aus XML-Exports der alten Access-Datenbank.
 * 
 * Verwendung:
 *   php artisan import:access                     # Interaktives Menü
 *   php artisan import:access --all               # Alles importieren
 *   php artisan import:access --adressen          # Nur Adressen
 *   php artisan import:access --dry-run           # Test ohne Speichern
 *   php artisan import:access --force             # Bestehende überschreiben
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
                            {--dry-run : Testlauf ohne Speichern}
                            {--force : Bestehende Einträge überschreiben}
                            {--path= : Pfad zum XML-Ordner (Standard: storage/import)}';

    protected $description = 'Importiert Daten aus Access-XML-Exports';

    protected AccessImportService $importer;
    protected string $importPath;

    // Standard-Dateinamen für XML-Exports
    protected array $xmlFiles = [
        'adressen'   => 'Adresse.xml',
        'gebaeude'   => 'Gebaeude.xml',
        'artikel'    => 'Artikel.xml',
        'rechnungen' => 'FatturaPAXmlAbfrage.xml',
        'positionen' => 'ArtikelFatturaPAAbfrage.xml',
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

        $this->importer->configure($dryRun, $skipExisting);

        // Header
        $this->newLine();
        $this->info('╔══════════════════════════════════════════════════════════╗');
        $this->info('║           ACCESS → LARAVEL IMPORT                        ║');
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
            $this->info("   - Adresse.xml");
            $this->info("   - Gebaeude.xml");
            $this->info("   - Artikel.xml");
            $this->info("   - FatturaPAXmlAbfrage.xml");
            $this->info("   - ArtikelFatturaPAAbfrage.xml");
            return Command::FAILURE;
        }

        // Was soll importiert werden?
        $tasks = $this->determineTasks();

        if (empty($tasks)) {
            $this->warn('Keine Import-Aufgabe ausgewählt.');
            return Command::SUCCESS;
        }

        // Bestätigung
        $this->info('📋 Folgende Tabellen werden importiert:');
        foreach ($tasks as $task) {
            $file = $this->xmlFiles[$task];
            $exists = file_exists("{$this->importPath}/{$file}");
            $status = $exists ? '✅' : '❌';
            $this->line("   {$status} {$task} ({$file})");
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
            return ['adressen', 'gebaeude', 'artikel', 'rechnungen', 'positionen'];
        }

        $tasks = [];
        if ($this->option('adressen')) $tasks[] = 'adressen';
        if ($this->option('gebaeude')) $tasks[] = 'gebaeude';
        if ($this->option('artikel')) $tasks[] = 'artikel';
        if ($this->option('rechnungen')) $tasks[] = 'rechnungen';
        if ($this->option('positionen')) $tasks[] = 'positionen';

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
            'all'        => '🔄 Alles (empfohlen für Erstimport)',
            'adressen'   => '📋 Nur Adressen',
            'gebaeude'   => '🏢 Nur Gebäude',
            'artikel'    => '📦 Nur Artikel',
            'rechnungen' => '🧾 Nur Rechnungen',
            'positionen' => '📝 Nur Rechnungspositionen',
            'custom'     => '⚙️  Benutzerdefiniert...',
        ];

        $choice = $this->choice('Auswahl', array_values($choices), 0);
        $key = array_search($choice, $choices);

        if ($key === 'all') {
            return ['adressen', 'gebaeude', 'artikel', 'rechnungen', 'positionen'];
        }

        if ($key === 'custom') {
            $available = ['adressen', 'gebaeude', 'artikel', 'rechnungen', 'positionen'];
            return $this->choice(
                'Wähle Tabellen (mehrere mit Komma trennen)',
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
        $file = $this->xmlFiles[$task];
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
            'positionen' => $this->importer->importRechnungspositionen($path),
            default      => 0,
        };

        $stats = $this->importer->getStats()[$task] ?? [];
        $imported = $stats['imported'] ?? 0;
        $skipped = $stats['skipped'] ?? 0;
        $errors = $stats['errors'] ?? 0;

        $this->line("      ✅ {$imported} importiert, ⏭️  {$skipped} übersprungen, ❌ {$errors} Fehler");
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
        $totalErrors = 0;

        $this->table(
            ['Tabelle', 'Importiert', 'Übersprungen', 'Fehler'],
            collect($stats)->map(function ($stat, $table) use (&$totalImported, &$totalSkipped, &$totalErrors) {
                $totalImported += $stat['imported'];
                $totalSkipped += $stat['skipped'];
                $totalErrors += $stat['errors'];
                return [
                    ucfirst($table),
                    $stat['imported'],
                    $stat['skipped'],
                    $stat['errors'],
                ];
            })->toArray()
        );

        $this->newLine();
        $this->info("📊 Gesamt: {$totalImported} importiert, {$totalSkipped} übersprungen, {$totalErrors} Fehler");
        $this->info("⏱️  Dauer: {$duration} Sekunden");

        // Fehler anzeigen und in Datei schreiben
        $errors = $this->importer->getErrors();
        if (!empty($errors)) {
            // ⭐ IMMER in Log-Datei schreiben
            $logFile = storage_path('logs/import_errors_' . date('Y-m-d_His') . '.log');
            $logContent = "Import-Fehler vom " . date('d.m.Y H:i:s') . "\n";
            $logContent .= str_repeat('=', 60) . "\n\n";
            
            foreach ($errors as $error) {
                $logContent .= "[{$error['table']}] ID {$error['id']}: {$error['message']}\n";
            }
            
            file_put_contents($logFile, $logContent);
            
            $this->newLine();
            $this->warn('⚠️  Fehler-Details:');
            
            // Wie viele anzeigen?
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