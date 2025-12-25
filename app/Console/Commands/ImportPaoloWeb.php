<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Gebaeude;
use App\Models\Timeline;
use Carbon\Carbon;

class ImportPaoloWeb extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'import:paoloweb 
                            {--dry-run : Nur simulieren, nichts speichern}
                            {--limit= : Nur X Einträge importieren (für Tests)}';

    /**
     * The console command description.
     */
    protected $description = 'Importiert Gebäude aus storage/import/paoloWeb.xml';

    /**
     * Statistiken
     */
    protected array $stats = [
        'total'       => 0,
        'imported'    => 0,
        'skipped'     => 0,
        'skipped_deleted' => 0,
        'skipped_exists'  => 0,
        'timelines'   => 0,
        'errors'      => 0,
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $xmlPath = storage_path('import/paoloWeb.xml');
        
        if (!file_exists($xmlPath)) {
            $this->error("❌ Datei nicht gefunden: {$xmlPath}");
            return Command::FAILURE;
        }

        $this->info("📂 Lade XML: {$xmlPath}");
        
        $isDryRun = $this->option('dry-run');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;
        
        if ($isDryRun) {
            $this->warn("🔍 DRY-RUN Modus - nichts wird gespeichert!");
        }

        // XML laden
        $xml = simplexml_load_file($xmlPath);
        if (!$xml) {
            $this->error("❌ XML konnte nicht geladen werden");
            return Command::FAILURE;
        }

        // Alle <table name="paoloWeb"> Elemente finden
        $entries = $xml->xpath('//database/table[@name="paoloWeb"]');
        $this->stats['total'] = count($entries);
        
        $this->info("📊 Gefunden: {$this->stats['total']} Einträge");
        
        if ($limit) {
            $entries = array_slice($entries, 0, $limit);
            $this->warn("⚠️ Limitiert auf {$limit} Einträge");
        }

        $bar = $this->output->createProgressBar(count($entries));
        $bar->start();

        foreach ($entries as $entry) {
            $this->processEntry($entry, $isDryRun);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Statistik ausgeben
        $this->printStats();

        return Command::SUCCESS;
    }

    /**
     * Verarbeitet einen einzelnen Eintrag
     */
    protected function processEntry(\SimpleXMLElement $entry, bool $isDryRun): void
    {
        $data = $this->parseEntry($entry);
        
        // Gelöschte überspringen
        if ($data['geloescht'] == 1) {
            $this->stats['skipped_deleted']++;
            $this->stats['skipped']++;
            return;
        }

        // Bereits importiert? (paoloweb_id prüfen)
        $exists = Gebaeude::where('paoloweb_id', $data['id'])->exists();
        if ($exists) {
            $this->stats['skipped_exists']++;
            $this->stats['skipped']++;
            return;
        }

        try {
            if (!$isDryRun) {
                DB::beginTransaction();
            }

            // Gebäude erstellen
            $gebaeude = $this->createGebaeude($data, $isDryRun);

            // Timeline-Einträge erstellen
            if ($gebaeude) {
                $this->createTimelineEntries($gebaeude, $data, $isDryRun);
            }

            if (!$isDryRun) {
                DB::commit();
            }

            $this->stats['imported']++;

        } catch (\Exception $e) {
            if (!$isDryRun) {
                DB::rollBack();
            }
            
            $this->stats['errors']++;
            Log::error('Import paoloWeb Fehler', [
                'legacy_id' => $data['id'],
                'error'     => $e->getMessage(),
            ]);
        }
    }

    /**
     * Parst einen XML-Eintrag zu Array
     */
    protected function parseEntry(\SimpleXMLElement $entry): array
    {
        $data = [];
        
        foreach ($entry->column as $column) {
            $name = (string) $column['name'];
            $value = trim((string) $column);
            $data[$name] = $value;
        }

        return $data;
    }

    /**
     * Erstellt ein Gebäude aus den Daten
     */
    protected function createGebaeude(array $data, bool $isDryRun): ?Gebaeude
    {
        // Gebäudename zusammensetzen
        $vorname = trim($data['Vorname'] ?? '');
        $nachname = trim($data['Nachname'] ?? '');
        $gebaeudeName = trim("{$vorname} {$nachname}");
        
        if (empty($gebaeudeName)) {
            $gebaeudeName = 'Unbekannt';
        }

        // Hausnummer + Intern
        $hausnummer = trim($data['Hausnummer'] ?? '');
        $intern = trim($data['Intern'] ?? '');
        if (!empty($intern)) {
            $hausnummer = trim("{$hausnummer}/{$intern}");
        }

        // Bemerkung + Anlagen
        $bemerkung = trim($data['Bemerkung'] ?? '');
        $anlagen = trim($data['anlagen'] ?? '');
        if (!empty($anlagen)) {
            $bemerkung = trim("{$bemerkung}\n\nAnlagen: {$anlagen}");
        }

        // Monats-Flags berechnen
        $monatsFlags = $this->calculateMonthFlags($data);

        $gebaeudeData = [
            'paoloweb_id'         => (int) $data['id'],
            'codex'               => strtolower(trim($data['Codex'] ?? '')),
            'gebaeude_name'       => $gebaeudeName,
            'strasse'             => trim($data['Strasse'] ?? ''),
            'hausnummer'          => $hausnummer,
            'plz'                 => '',
            'wohnort'             => trim($data['Wohnort'] ?? ''),
            'land'                => 'Italien',
            'telefon'             => trim($data['Telefon'] ?? ''),
            'handy'               => trim($data['Handy'] ?? ''),
            'email'               => trim($data['Email'] ?? ''),
            'bemerkung'           => $bemerkung ?: null,
            'rechnung_schreiben'  => 0,
            'faellig'             => 0,
            'geplante_reinigungen' => null,
            'gemachte_reinigungen' => null,
            // Monats-Flags
            'm01' => $monatsFlags[1],
            'm02' => $monatsFlags[2],
            'm03' => $monatsFlags[3],
            'm04' => $monatsFlags[4],
            'm05' => $monatsFlags[5],
            'm06' => $monatsFlags[6],
            'm07' => $monatsFlags[7],
            'm08' => $monatsFlags[8],
            'm09' => $monatsFlags[9],
            'm10' => $monatsFlags[10],
            'm11' => $monatsFlags[11],
            'm12' => $monatsFlags[12],
        ];

        if ($isDryRun) {
            return new Gebaeude($gebaeudeData);
        }

        return Gebaeude::create($gebaeudeData);
    }

    /**
     * Berechnet Monats-Flags aus LetzteKontrolle/LetzteReinigung
     */
    protected function calculateMonthFlags(array $data): array
    {
        $flags = array_fill(1, 12, 0);

        // LetzteKontrolle
        if (!empty($data['LetzteKontrolle']) && $data['LetzteKontrolle'] !== '0000-00-00') {
            try {
                $date = Carbon::parse($data['LetzteKontrolle']);
                $flags[$date->month] = 1;
            } catch (\Exception $e) {
                // ignorieren
            }
        }

        // LetzteReinigung
        if (!empty($data['LetzteReinigung']) && $data['LetzteReinigung'] !== '0000-00-00') {
            try {
                $date = Carbon::parse($data['LetzteReinigung']);
                $flags[$date->month] = 1;
            } catch (\Exception $e) {
                // ignorieren
            }
        }

        return $flags;
    }

    /**
     * Erstellt Timeline-Einträge
     */
    protected function createTimelineEntries(?Gebaeude $gebaeude, array $data, bool $isDryRun): void
    {
        if (!$gebaeude || !$gebaeude->id) {
            return;
        }

        $kontrolle = $data['LetzteKontrolle'] ?? null;
        $reinigung = $data['LetzteReinigung'] ?? null;

        // LetzteKontrolle
        if (!empty($kontrolle) && $kontrolle !== '0000-00-00') {
            $this->createTimeline($gebaeude->id, $kontrolle, 'Kontrolle (Import)', $isDryRun);
        }

        // LetzteReinigung (nur wenn anderes Datum als Kontrolle)
        if (!empty($reinigung) && $reinigung !== '0000-00-00' && $reinigung !== $kontrolle) {
            $this->createTimeline($gebaeude->id, $reinigung, 'Reinigung (Import)', $isDryRun);
        }
    }

    /**
     * Erstellt einen Timeline-Eintrag
     */
    protected function createTimeline(int $gebaeudeId, string $datum, string $bemerkung, bool $isDryRun): void
    {
        if ($isDryRun) {
            $this->stats['timelines']++;
            return;
        }

        try {
            Timeline::create([
                'gebaeude_id' => $gebaeudeId,
                'datum'       => $datum,
                'bemerkung'   => $bemerkung,
                'person_name' => 'Import',
                'person_id'   => 0,
            ]);
            $this->stats['timelines']++;
        } catch (\Exception $e) {
            Log::warning('Timeline Import Fehler', [
                'gebaeude_id' => $gebaeudeId,
                'datum'       => $datum,
                'error'       => $e->getMessage(),
            ]);
        }
    }

    /**
     * Gibt Statistiken aus
     */
    protected function printStats(): void
    {
        $this->info('═══════════════════════════════════════');
        $this->info('📊 IMPORT STATISTIK');
        $this->info('═══════════════════════════════════════');
        $this->line("   Gesamt in XML:        {$this->stats['total']}");
        $this->info("✅ Importiert:           {$this->stats['imported']}");
        $this->line("   Timeline-Einträge:    {$this->stats['timelines']}");
        $this->warn("⏭️  Übersprungen:         {$this->stats['skipped']}");
        $this->line("     - Gelöscht:         {$this->stats['skipped_deleted']}");
        $this->line("     - Bereits vorhanden:{$this->stats['skipped_exists']}");
        
        if ($this->stats['errors'] > 0) {
            $this->error("❌ Fehler:               {$this->stats['errors']}");
        }
        
        $this->info('═══════════════════════════════════════');
    }
}
