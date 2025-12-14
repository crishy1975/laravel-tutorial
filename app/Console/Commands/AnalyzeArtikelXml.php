<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Analysiert die Artikel.xml um Duplikate und Probleme zu finden
 * 
 * Usage: php artisan debug:analyze-xml storage/import/Artikel.xml
 */
class AnalyzeArtikelXml extends Command
{
    protected $signature = 'debug:analyze-xml {file : Pfad zur XML-Datei}';
    protected $description = 'Analysiert XML-Datei auf Duplikate und Probleme';

    public function handle(): int
    {
        $path = $this->argument('file');

        if (!file_exists($path)) {
            $this->error("Datei nicht gefunden: $path");
            return 1;
        }

        $this->info("Analysiere: $path");
        $this->newLine();

        // XML laden
        $content = file_get_contents($path);
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
        $xml = simplexml_load_string($content);

        // Statistiken sammeln
        $total = 0;
        $mIds = [];          // mId => count
        $ids = [];           // id => count
        $herkunftStats = []; // herkunft => count
        $beispiele = [];     // Beispiel-Duplikate

        foreach ($xml->Artikel as $item) {
            $total++;
            
            $id = (int) $item->id;
            $mId = (int) $item->mId;
            $herkunft = (int) $item->herkunft;
            $beschreibung = (string) $item->Beschreibung;

            // mId zaehlen
            if (!isset($mIds[$mId])) {
                $mIds[$mId] = [];
            }
            $mIds[$mId][] = [
                'id' => $id,
                'herkunft' => $herkunft,
                'beschreibung' => substr($beschreibung, 0, 40),
            ];

            // id zaehlen
            $ids[$id] = ($ids[$id] ?? 0) + 1;

            // herkunft zaehlen
            $herkunftStats[$herkunft] = ($herkunftStats[$herkunft] ?? 0) + 1;
        }

        // =====================================================================
        // ERGEBNISSE
        // =====================================================================
        
        $this->info("=== GESAMT ===");
        $this->line("Total Artikel in XML: $total");
        $this->line("Unique mId: " . count($mIds));
        $this->line("Unique id: " . count($ids));
        $this->line("Unique herkunft (Gebaeude): " . count($herkunftStats));
        $this->newLine();

        // Duplikate finden (mId kommt mehrfach vor)
        $duplikateMid = array_filter($mIds, fn($items) => count($items) > 1);
        
        $this->info("=== mId DUPLIKATE ===");
        $this->line("Anzahl mIds die mehrfach vorkommen: " . count($duplikateMid));
        
        if (count($duplikateMid) > 0) {
            $this->newLine();
            $this->warn("Erste 10 Duplikate:");
            
            $shown = 0;
            foreach ($duplikateMid as $mId => $items) {
                if ($shown >= 10) break;
                
                $this->line("  mId=$mId kommt " . count($items) . "x vor:");
                foreach ($items as $item) {
                    $this->line("    - id={$item['id']}, herkunft={$item['herkunft']}, '{$item['beschreibung']}'");
                }
                $shown++;
            }
        }

        $this->newLine();

        // id Duplikate
        $duplikateId = array_filter($ids, fn($count) => $count > 1);
        $this->info("=== id DUPLIKATE ===");
        $this->line("Anzahl ids die mehrfach vorkommen: " . count($duplikateId));
        
        $this->newLine();

        // Herkunft ohne Gebaeude pruefen
        $this->info("=== HERKUNFT STATISTIK ===");
        $this->line("Top 10 herkunft (Gebaeude) nach Anzahl Artikel:");
        
        arsort($herkunftStats);
        $shown = 0;
        foreach ($herkunftStats as $herkunft => $count) {
            if ($shown >= 10) break;
            $this->line("  herkunft=$herkunft: $count Artikel");
            $shown++;
        }

        // Gebaeude-Map laden und fehlende pruefen
        $this->newLine();
        $this->info("=== FEHLENDE GEBAEUDE ===");
        
        $gebaeudeMap = \App\Models\Gebaeude::whereNotNull('legacy_mid')
            ->pluck('id', 'legacy_mid')
            ->toArray();
        
        $this->line("Gebaeude in DB: " . count($gebaeudeMap));
        
        $fehlendeHerkunft = [];
        foreach ($herkunftStats as $herkunft => $count) {
            if (!isset($gebaeudeMap[$herkunft])) {
                $fehlendeHerkunft[$herkunft] = $count;
            }
        }
        
        $this->line("Fehlende herkunft-Werte: " . count($fehlendeHerkunft));
        
        if (count($fehlendeHerkunft) > 0) {
            $artikelOhneGebaeude = array_sum($fehlendeHerkunft);
            $this->warn("Artikel ohne Gebaeude: $artikelOhneGebaeude");
            $this->newLine();
            $this->line("Fehlende herkunft-Werte (Gebaeude legacy_mid):");
            foreach ($fehlendeHerkunft as $herkunft => $count) {
                $this->line("  herkunft=$herkunft: $count Artikel betroffen");
            }
        }

        return 0;
    }
}
