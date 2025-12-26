<?php

namespace App\Services\Import;

use App\Models\Gebaeude;
use App\Models\Timeline;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use Exception;

/**
 * Timeline-Import Service
 * 
 * Importiert Reinigungsdaten aus DatumAusfuehrung.xml in die Timeline-Tabelle.
 * 
 * XML-FORMAT:
 * <DatumAusfuehrung>
 *   <id>49637</id>              → legacy_timeline_id (für Duplikat-Check)
 *   <Herkunft>554</Herkunft>    → Gebäude-Lookup (legacy_id/legacy_mid)
 *   <Datum>2019-11-16T00:00:00</Datum>
 *   <verrechnet>0</verrechnet>  → verrechnen = !verrechnet
 * </DatumAusfuehrung>
 * 
 * VORAUSSETZUNG: Gebäude müssen VORHER importiert sein!
 */
class TimelineImportService
{
    protected array $stats = [
        'imported' => 0,
        'skipped' => 0,
        'filtered' => 0,  // Zu alt (< 2024)
        'errors' => 0,
    ];

    protected array $errors = [];
    protected bool $dryRun = false;
    protected bool $skipExisting = true;
    protected int $minJahr = 2024;  // Nur ab diesem Jahr importieren

    // Lookup-Tabellen
    protected array $gebaeudeMap = [];       // legacy_mid -> neue ID
    protected array $gebaeudeMapById = [];   // legacy_id -> neue ID

    // Duplikat-Check: gebaeude_id:datum -> exists
    protected array $existingTimelines = [];

    /**
     * Konfiguration
     */
    public function configure(
        bool $dryRun = false, 
        bool $skipExisting = true,
        int $minJahr = 2024
    ): self {
        $this->dryRun = $dryRun;
        $this->skipExisting = $skipExisting;
        $this->minJahr = $minJahr;
        return $this;
    }

    public function getStats(): array
    {
        return $this->stats;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Lookup-Tabellen aufbauen
     */
    public function buildLookups(): void
    {
        // Gebäude nach legacy_mid
        $this->gebaeudeMap = Gebaeude::whereNotNull('legacy_mid')
            ->pluck('id', 'legacy_mid')
            ->toArray();

        // Gebäude nach legacy_id  
        $this->gebaeudeMapById = Gebaeude::whereNotNull('legacy_id')
            ->pluck('id', 'legacy_id')
            ->toArray();

        // Bestehende Timeline-Einträge (für Duplikat-Check)
        // Format: "gebaeude_id:Y-m-d" -> true
        if ($this->skipExisting) {
            $this->existingTimelines = Timeline::query()
                ->whereYear('datum', '>=', $this->minJahr)
                ->get(['gebaeude_id', 'datum'])
                ->mapWithKeys(function ($t) {
                    $key = $t->gebaeude_id . ':' . $t->datum->format('Y-m-d');
                    return [$key => true];
                })
                ->toArray();
        }

        Log::info('TimelineImport: Lookups aufgebaut', [
            'gebaeude_mid' => count($this->gebaeudeMap),
            'gebaeude_id' => count($this->gebaeudeMapById),
            'existing_timelines' => count($this->existingTimelines),
        ]);
    }

    /**
     * ⭐ HAUPTMETHODE: Timeline-Einträge importieren
     */
    public function importTimelines(string $xmlPath): int
    {
        $xml = $this->loadXml($xmlPath);
        $count = 0;

        // Lookups aufbauen falls noch nicht geschehen
        if (empty($this->gebaeudeMap)) {
            $this->buildLookups();
        }

        // XML-Elemente durchlaufen
        $items = $xml->DatumAusfuehrung ?? $xml->children();

        foreach ($items as $item) {
            try {
                $count += $this->importTimelineItem($item);
            } catch (Exception $e) {
                $this->logError((string)($item->id ?? 'unknown'), $e->getMessage());
            }
        }

        return $count;
    }

    /**
     * Einzelnen Timeline-Eintrag importieren
     */
    protected function importTimelineItem(\SimpleXMLElement $item): int
    {
        $legacyId = (int) $item->id;
        
        // ═══════════════════════════════════════════════════════════════
        // DATUM PARSEN & JAHR-FILTER
        // ═══════════════════════════════════════════════════════════════
        
        $datum = $this->parseDate((string) $item->Datum);
        
        if (!$datum) {
            throw new Exception("Ungültiges Datum");
        }

        // Filter: Nur ab minJahr (Standard: 2024)
        if ($datum->year < $this->minJahr) {
            $this->stats['filtered']++;
            return 0;
        }

        // ═══════════════════════════════════════════════════════════════
        // GEBÄUDE AUFLÖSEN
        // ═══════════════════════════════════════════════════════════════
        
        $herkunft = (int) $item->Herkunft;
        
        // ⭐ Lookup: Erst legacy_id, dann legacy_mid
        $gebaeudeId = $this->gebaeudeMapById[$herkunft] 
                   ?? $this->gebaeudeMap[$herkunft] 
                   ?? null;

        if (!$gebaeudeId) {
            throw new Exception("Gebäude nicht gefunden (Herkunft: $herkunft)");
        }

        // ═══════════════════════════════════════════════════════════════
        // DUPLIKAT-CHECK
        // ═══════════════════════════════════════════════════════════════
        
        $dupKey = $gebaeudeId . ':' . $datum->format('Y-m-d');
        
        if ($this->skipExisting && isset($this->existingTimelines[$dupKey])) {
            $this->stats['skipped']++;
            return 0;
        }

        // ═══════════════════════════════════════════════════════════════
        // VERRECHNET-STATUS
        // ═══════════════════════════════════════════════════════════════
        
        // verrechnet = 1 bedeutet: bereits verrechnet
        // verrechnen = false (soll NICHT mehr verrechnet werden)
        $verrechnet = (int) $item->verrechnet === 1;

        // ═══════════════════════════════════════════════════════════════
        // DATEN ZUSAMMENSTELLEN
        // ═══════════════════════════════════════════════════════════════

        $data = [
            'gebaeude_id'  => $gebaeudeId,
            'datum'        => $datum,
            'bemerkung'    => 'Import aus Access (ID: ' . $legacyId . ')',
            'verrechnen'   => !$verrechnet,  // Wenn verrechnet=1, dann verrechnen=false
            'verrechnet_am' => $verrechnet ? $datum : null,  // Falls verrechnet, Datum als verrechnet_am
        ];

        // ═══════════════════════════════════════════════════════════════
        // SPEICHERN
        // ═══════════════════════════════════════════════════════════════

        if ($this->dryRun) {
            Log::info('[DRY-RUN] Würde Timeline importieren', [
                'legacy_id' => $legacyId,
                'gebaeude_id' => $gebaeudeId,
                'datum' => $datum->format('Y-m-d'),
                'verrechnet' => $verrechnet,
            ]);
            $this->stats['imported']++;
            
            // In Duplikat-Cache eintragen (für Dry-Run Konsistenz)
            $this->existingTimelines[$dupKey] = true;
            
            return 1;
        }

        Timeline::create($data);
        $this->stats['imported']++;
        
        // In Duplikat-Cache eintragen
        $this->existingTimelines[$dupKey] = true;

        return 1;
    }

    // ═══════════════════════════════════════════════════════════════════════
    // HELPER METHODS
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * XML laden
     */
    protected function loadXml(string $path): \SimpleXMLElement
    {
        if (!file_exists($path)) {
            throw new Exception("XML-Datei nicht gefunden: $path");
        }

        $content = file_get_contents($path);
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content); // BOM entfernen

        return simplexml_load_string($content);
    }

    /**
     * Datum parsen (ISO-Format: 2019-11-16T00:00:00)
     */
    protected function parseDate(?string $dateString): ?Carbon
    {
        if (!$dateString || $dateString === '') {
            return null;
        }
        
        // Dummy-Datum ignorieren
        if (str_contains($dateString, '2001-01-01')) {
            return null;
        }

        try {
            // ISO-Format: 2019-11-16T00:00:00
            if (str_contains($dateString, 'T')) {
                return Carbon::parse($dateString)->startOfDay();
            }
            
            // Deutsche Formate: 16.11.2019
            if (preg_match('/^(\d{2})\.(\d{2})\.(\d{4})/', $dateString, $m)) {
                return Carbon::createFromFormat('d.m.Y', "{$m[1]}.{$m[2]}.{$m[3]}");
            }
            
            return Carbon::parse($dateString);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Fehler loggen
     */
    protected function logError(string $id, string $message): void
    {
        $this->stats['errors']++;
        $this->errors[] = ['id' => $id, 'message' => $message];
        Log::warning("TimelineImport Fehler", ['id' => $id, 'message' => $message]);
    }
}
