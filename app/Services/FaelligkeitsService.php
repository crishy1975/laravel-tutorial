<?php

namespace App\Services;

use App\Models\Gebaeude;
use App\Models\Timeline;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * FaelligkeitsService - Zentrale Logik für Reinigungsfälligkeiten
 * 
 * REGELN:
 * 1. Ein Gebäude wird am 1. eines aktiven Monats fällig
 * 2. Wenn keine Monate aktiv → fällig am 1. Januar jedes Jahres
 * 3. Fällig = nächster Fälligkeitstermin nach letzter Reinigung ist erreicht/überschritten
 * 
 * BEISPIELE:
 * - Letzte Reinigung: 31.12.2025, Monate: Feb+Aug → Fällig ab 01.02.2026
 * - Letzte Reinigung: 03.02.2026, Monate: Feb+Aug → Fällig ab 01.08.2026
 * - Letzte Reinigung: 31.12.2025, keine Monate   → Fällig ab 01.01.2026
 */
class FaelligkeitsService
{
    /**
     * Ermittelt die aktiven Monate eines Gebäudes (m01-m12)
     * 
     * @param Gebaeude $gebaeude
     * @return array Array mit Monatsnummern [2, 8] für Feb+Aug
     */
    public function getAktiveMonate(Gebaeude $gebaeude): array
    {
        $aktiveMonate = [];
        
        for ($m = 1; $m <= 12; $m++) {
            $feld = 'm' . str_pad($m, 2, '0', STR_PAD_LEFT);
            if ($gebaeude->{$feld}) {
                $aktiveMonate[] = $m;
            }
        }
        
        return $aktiveMonate;
    }

    /**
     * Ermittelt das Datum der letzten Reinigung aus der Timeline
     * 
     * @param Gebaeude $gebaeude
     * @return Carbon|null
     */
    public function getLetzteReinigung(Gebaeude $gebaeude): ?Carbon
    {
        $datum = $gebaeude->timelines()
            ->reorder()
            ->max('datum');
        
        return $datum ? Carbon::parse($datum) : null;
    }

    /**
     * Zählt die Reinigungen in einem Jahr
     * 
     * @param Gebaeude $gebaeude
     * @param int|null $jahr
     * @return int
     */
    public function zaehleReinigungen(Gebaeude $gebaeude, ?int $jahr = null): int
    {
        $jahr = $jahr ?? now()->year;
        
        return $gebaeude->timelines()
            ->reorder()
            ->whereYear('datum', $jahr)
            ->count();
    }

    /**
     * ⭐ KERNLOGIK: Ermittelt den nächsten Fälligkeitstermin nach einem Datum
     * 
     * @param Gebaeude $gebaeude
     * @param Carbon|null $nachDatum Nach welchem Datum suchen (Standard: letzte Reinigung)
     * @return Carbon Der nächste Fälligkeitstermin
     */
    public function getNaechsteFaelligkeit(Gebaeude $gebaeude, ?Carbon $nachDatum = null): Carbon
    {
        $nachDatum = $nachDatum ?? $this->getLetzteReinigung($gebaeude);
        $aktiveMonate = $this->getAktiveMonate($gebaeude);
        
        // Kein Referenzdatum → suche ab heute
        if (!$nachDatum) {
            $nachDatum = now()->subDay(); // Gestern, damit heute auch gefunden wird
        }
        
        // Keine aktiven Monate → 1. Januar des nächsten Jahres nach Reinigung
        if (empty($aktiveMonate)) {
            $jahr = $nachDatum->year;
            $januar = Carbon::create($jahr, 1, 1)->startOfDay();
            
            // Wenn Reinigung vor/am 1.1. → dieses Jahr fällig
            // Wenn Reinigung nach 1.1. → nächstes Jahr fällig
            if ($nachDatum->lt($januar)) {
                return $januar;
            }
            return Carbon::create($jahr + 1, 1, 1)->startOfDay();
        }
        
        // Suche den nächsten aktiven Monat NACH der Reinigung
        $referenzJahr = $nachDatum->year;
        $referenzMonat = $nachDatum->month;
        $referenzTag = $nachDatum->day;
        
        // Prüfe ob Reinigung VOR dem 1. des Monats war
        // Wenn ja, könnte dieser Monat noch fällig sein
        $reinigungVorMonatsanfang = ($referenzTag < 1); // Immer false, aber für Klarheit
        
        // Suche in den nächsten 24 Monaten
        for ($offset = 0; $offset <= 24; $offset++) {
            $pruefDatum = $nachDatum->copy()->addMonths($offset)->startOfMonth();
            $pruefMonat = $pruefDatum->month;
            
            // Ist dieser Monat aktiv?
            if (in_array($pruefMonat, $aktiveMonate)) {
                // Fälligkeit ist am 1. dieses Monats
                $faelligkeit = Carbon::create($pruefDatum->year, $pruefMonat, 1)->startOfDay();
                
                // Nur wenn Fälligkeit NACH der Reinigung liegt
                if ($faelligkeit->gt($nachDatum)) {
                    return $faelligkeit;
                }
            }
        }
        
        // Fallback: 1 Jahr nach Reinigung
        return $nachDatum->copy()->addYear()->startOfMonth();
    }

    /**
     * ⭐ HAUPTMETHODE: Prüft ob ein Gebäude fällig ist
     * 
     * @param Gebaeude $gebaeude
     * @param Carbon|null $stichtag Prüfdatum (Standard: heute)
     * @return bool
     */
    public function istFaellig(Gebaeude $gebaeude, ?Carbon $stichtag = null): bool
    {
        $stichtag = $stichtag ?? now();
        
        $naechsteFaelligkeit = $this->getNaechsteFaelligkeit($gebaeude);
        
        // Fällig wenn der nächste Fälligkeitstermin erreicht oder überschritten ist
        return $naechsteFaelligkeit->lte($stichtag);
    }

    /**
     * Aktualisiert das Fälligkeits-Flag eines Gebäudes
     * 
     * @param Gebaeude $gebaeude
     * @param Carbon|null $stichtag
     * @return array Status-Informationen
     */
    public function aktualisiereGebaeude(Gebaeude $gebaeude, ?Carbon $stichtag = null): array
    {
        $stichtag = $stichtag ?? now();
        
        $letzteReinigung = $this->getLetzteReinigung($gebaeude);
        $naechsteFaelligkeit = $this->getNaechsteFaelligkeit($gebaeude);
        $istFaellig = $naechsteFaelligkeit->lte($stichtag);
        $aktiveMonate = $this->getAktiveMonate($gebaeude);
        
        // Reinigungen im aktuellen Jahr zählen
        $gemachteReinigungen = $this->zaehleReinigungen($gebaeude, $stichtag->year);
        $geplanteReinigungen = count($aktiveMonate) ?: 1; // Min. 1 wenn keine Monate
        
        $altFaellig = (bool) $gebaeude->faellig;
        $geaendert = ($altFaellig !== $istFaellig) 
                  || ($gebaeude->gemachte_reinigungen != $gemachteReinigungen)
                  || ($gebaeude->geplante_reinigungen != $geplanteReinigungen);
        
        if ($geaendert) {
            $gebaeude->forceFill([
                'faellig' => $istFaellig,
                'datum_faelligkeit' => $naechsteFaelligkeit,
                'letzter_termin' => $letzteReinigung,
                'gemachte_reinigungen' => $gemachteReinigungen,
                'geplante_reinigungen' => $geplanteReinigungen,
            ])->save();
        }
        
        return [
            'gebaeude_id' => $gebaeude->id,
            'codex' => $gebaeude->codex,
            'letzte_reinigung' => $letzteReinigung?->format('d.m.Y'),
            'naechste_faelligkeit' => $naechsteFaelligkeit->format('d.m.Y'),
            'aktive_monate' => $aktiveMonate,
            'ist_faellig' => $istFaellig,
            'vorher_faellig' => $altFaellig,
            'geaendert' => $geaendert,
            'gemachte_reinigungen' => $gemachteReinigungen,
            'geplante_reinigungen' => $geplanteReinigungen,
        ];
    }

    /**
     * ⭐ BATCH: Aktualisiert alle Gebäude
     * 
     * @param Carbon|null $stichtag
     * @param bool $nurFaellige Nur fällige aktualisieren
     * @return array Statistik
     */
    public function aktualisiereAlle(?Carbon $stichtag = null, bool $nurFaellige = false): array
    {
        $stichtag = $stichtag ?? now();
        $stats = [
            'gesamt' => 0,
            'faellig' => 0,
            'nicht_faellig' => 0,
            'geaendert' => 0,
            'fehler' => 0,
        ];
        
        Gebaeude::query()
            ->orderBy('id')
            ->chunkById(500, function ($chunk) use (&$stats, $stichtag) {
                foreach ($chunk as $gebaeude) {
                    try {
                        $result = $this->aktualisiereGebaeude($gebaeude, $stichtag);
                        
                        $stats['gesamt']++;
                        if ($result['ist_faellig']) {
                            $stats['faellig']++;
                        } else {
                            $stats['nicht_faellig']++;
                        }
                        if ($result['geaendert']) {
                            $stats['geaendert']++;
                        }
                    } catch (\Exception $e) {
                        $stats['fehler']++;
                        Log::error('Fälligkeit-Update fehlgeschlagen', [
                            'gebaeude_id' => $gebaeude->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });
        
        Log::info('Fälligkeit-Batch abgeschlossen', $stats);
        
        return $stats;
    }

    /**
     * ⭐ SIMULATOR: Testet die Fälligkeitslogik mit beliebigen Parametern
     * 
     * @param array $aktiveMonate z.B. [2, 8] für Feb+Aug
     * @param Carbon|null $letzteReinigung
     * @param Carbon|null $stichtag
     * @return array Detaillierte Analyse
     */
    public function simuliere(
        array $aktiveMonate,
        ?Carbon $letzteReinigung = null,
        ?Carbon $stichtag = null
    ): array {
        $stichtag = $stichtag ?? now();
        
        // Virtuelles Gebäude erstellen
        $virtuell = new Gebaeude();
        foreach (range(1, 12) as $m) {
            $feld = 'm' . str_pad($m, 2, '0', STR_PAD_LEFT);
            $virtuell->{$feld} = in_array($m, $aktiveMonate);
        }
        
        // Fälligkeit berechnen
        $naechsteFaelligkeit = $this->getNaechsteFaelligkeitVirtuell($aktiveMonate, $letzteReinigung);
        $istFaellig = $naechsteFaelligkeit->lte($stichtag);
        
        // Alle Fälligkeitstermine im Jahr berechnen
        $faelligkeitsTermine = [];
        $jahr = $stichtag->year;
        
        if (empty($aktiveMonate)) {
            $faelligkeitsTermine[] = Carbon::create($jahr, 1, 1)->format('d.m.Y');
        } else {
            foreach ($aktiveMonate as $monat) {
                $faelligkeitsTermine[] = Carbon::create($jahr, $monat, 1)->format('d.m.Y');
            }
        }
        
        return [
            'eingabe' => [
                'aktive_monate' => $aktiveMonate,
                'aktive_monate_namen' => $this->monateAlsNamen($aktiveMonate),
                'letzte_reinigung' => $letzteReinigung?->format('d.m.Y') ?? 'nie',
                'stichtag' => $stichtag->format('d.m.Y'),
            ],
            'ergebnis' => [
                'naechste_faelligkeit' => $naechsteFaelligkeit->format('d.m.Y'),
                'ist_faellig' => $istFaellig,
                'tage_bis_faellig' => $istFaellig ? 0 : $stichtag->diffInDays($naechsteFaelligkeit),
                'tage_ueberfaellig' => $istFaellig ? $naechsteFaelligkeit->diffInDays($stichtag) : 0,
            ],
            'alle_termine_im_jahr' => $faelligkeitsTermine,
            'erklaerung' => $this->erzeugeErklaerung($aktiveMonate, $letzteReinigung, $naechsteFaelligkeit, $istFaellig, $stichtag),
        ];
    }

    /**
     * Hilfsmethode für Simulation ohne echtes Gebäude
     */
    private function getNaechsteFaelligkeitVirtuell(array $aktiveMonate, ?Carbon $nachDatum): Carbon
    {
        // Kein Referenzdatum → suche ab heute
        if (!$nachDatum) {
            $nachDatum = now()->subDay();
        }
        
        // Keine aktiven Monate → 1. Januar
        if (empty($aktiveMonate)) {
            $jahr = $nachDatum->year;
            $januar = Carbon::create($jahr, 1, 1)->startOfDay();
            
            if ($nachDatum->lt($januar)) {
                return $januar;
            }
            return Carbon::create($jahr + 1, 1, 1)->startOfDay();
        }
        
        // Sortiere Monate
        sort($aktiveMonate);
        
        // Suche nächsten aktiven Monat nach Reinigung
        for ($offset = 0; $offset <= 24; $offset++) {
            $pruefDatum = $nachDatum->copy()->addMonths($offset)->startOfMonth();
            $pruefMonat = $pruefDatum->month;
            
            if (in_array($pruefMonat, $aktiveMonate)) {
                $faelligkeit = Carbon::create($pruefDatum->year, $pruefMonat, 1)->startOfDay();
                
                if ($faelligkeit->gt($nachDatum)) {
                    return $faelligkeit;
                }
            }
        }
        
        return $nachDatum->copy()->addYear()->startOfMonth();
    }

    /**
     * Wandelt Monatsnummern in Namen um
     */
    private function monateAlsNamen(array $monate): array
    {
        $namen = [
            1 => 'Januar', 2 => 'Februar', 3 => 'März', 4 => 'April',
            5 => 'Mai', 6 => 'Juni', 7 => 'Juli', 8 => 'August',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Dezember',
        ];
        
        return array_map(fn($m) => $namen[$m] ?? '?', $monate);
    }

    /**
     * Erzeugt eine verständliche Erklärung
     */
    private function erzeugeErklaerung(
        array $aktiveMonate,
        ?Carbon $letzteReinigung,
        Carbon $naechsteFaelligkeit,
        bool $istFaellig,
        Carbon $stichtag
    ): string {
        $monateText = empty($aktiveMonate) 
            ? 'keine Monate aktiv (→ jährlich am 1. Januar)'
            : 'aktiv in: ' . implode(', ', $this->monateAlsNamen($aktiveMonate));
        
        $reinigungText = $letzteReinigung 
            ? "Letzte Reinigung: {$letzteReinigung->format('d.m.Y')}"
            : "Keine Reinigung eingetragen";
        
        $faelligText = $istFaellig
            ? "→ FÄLLIG seit {$naechsteFaelligkeit->format('d.m.Y')}"
            : "→ Nächste Fälligkeit: {$naechsteFaelligkeit->format('d.m.Y')}";
        
        return "{$reinigungText}\n{$monateText}\nStichtag: {$stichtag->format('d.m.Y')}\n{$faelligText}";
    }
}
