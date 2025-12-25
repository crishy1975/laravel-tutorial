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
 * ⭐ OPTIMIERT: Batch-Updates ohne N+1 Queries
 */
class FaelligkeitsService
{
    /**
     * Ermittelt die aktiven Monate eines Gebäudes (m01-m12)
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
     * ⭐ KERNLOGIK: Ermittelt den nächsten Fälligkeitstermin
     */
    public function getNaechsteFaelligkeit(Gebaeude $gebaeude, ?Carbon $nachDatum = null): Carbon
    {
        $letzteReinigung = $nachDatum ?? $this->getLetzteReinigung($gebaeude);
        $aktiveMonate = $this->getAktiveMonate($gebaeude);
        
        return $this->berechneNaechsteFaelligkeit($aktiveMonate, $letzteReinigung);
    }

    /**
     * ⭐ REINE BERECHNUNG ohne DB-Zugriff
     */
    private function berechneNaechsteFaelligkeit(array $aktiveMonate, ?Carbon $letzteReinigung): Carbon
    {
        // Kein Referenzdatum → fällig seit Anfang des Jahres
        if (!$letzteReinigung) {
            $letzteReinigung = Carbon::create(now()->year - 1, 12, 31);
        }
        
        // Keine aktiven Monate → 1. Januar jährlich
        if (empty($aktiveMonate)) {
            return Carbon::create($letzteReinigung->year + 1, 1, 1)->startOfDay();
        }
        
        sort($aktiveMonate);
        
        $jahr = $letzteReinigung->year;
        $monat = $letzteReinigung->month;
        
        // Suche max 3 Jahre voraus
        for ($jahrOffset = 0; $jahrOffset <= 3; $jahrOffset++) {
            $pruefJahr = $jahr + $jahrOffset;
            
            foreach ($aktiveMonate as $aktiverMonat) {
                // Im ersten Jahr: nur Monate NACH der Reinigung
                if ($jahrOffset === 0 && $aktiverMonat <= $monat) {
                    continue;
                }
                
                return Carbon::create($pruefJahr, $aktiverMonat, 1)->startOfDay();
            }
        }
        
        // Fallback
        return Carbon::create($jahr + 1, $aktiveMonate[0] ?? 1, 1)->startOfDay();
    }

    /**
     * Prüft ob ein Gebäude fällig ist
     */
    public function istFaellig(Gebaeude $gebaeude, ?Carbon $stichtag = null): bool
    {
        $stichtag = $stichtag ?? now();
        $naechsteFaelligkeit = $this->getNaechsteFaelligkeit($gebaeude);
        
        return $naechsteFaelligkeit->lte($stichtag);
    }

    /**
     * Aktualisiert das Fälligkeits-Flag eines einzelnen Gebäudes
     */
    public function aktualisiereGebaeude(Gebaeude $gebaeude, ?Carbon $stichtag = null): array
    {
        $stichtag = $stichtag ?? now();
        
        $letzteReinigung = $this->getLetzteReinigung($gebaeude);
        $aktiveMonate = $this->getAktiveMonate($gebaeude);
        $naechsteFaelligkeit = $this->berechneNaechsteFaelligkeit($aktiveMonate, $letzteReinigung);
        $istFaellig = $naechsteFaelligkeit->lte($stichtag);
        
        $gemachteReinigungen = $this->zaehleReinigungen($gebaeude, $stichtag->year);
        $geplanteReinigungen = count($aktiveMonate) ?: 1;
        
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
            'ist_faellig' => $istFaellig,
            'geaendert' => $geaendert,
        ];
    }

    /**
     * ⭐⭐⭐ HOCHOPTIMIERT: Aktualisiert alle Gebäude mit minimalen DB-Queries
     */
    public function aktualisiereAlle(?Carbon $stichtag = null, bool $nurFaellige = false): array
    {
        $stichtag = $stichtag ?? now();
        $startTime = microtime(true);
        
        $stats = [
            'gesamt' => 0,
            'faellig' => 0,
            'nicht_faellig' => 0,
            'geaendert' => 0,
            'fehler' => 0,
        ];
        
        // ═══════════════════════════════════════════════════════════════════
        // SCHRITT 1: Alle Timeline-Daten auf einmal laden (vermeidet N+1!)
        // ═══════════════════════════════════════════════════════════════════
        
        Log::info('Fälligkeit-Update: Lade Timeline-Daten...');
        
        // Letzte Reinigung pro Gebäude
        $letzteReinigungen = DB::table('timeline')
            ->select('gebaeude_id', DB::raw('MAX(datum) as letzte_reinigung'))
            ->groupBy('gebaeude_id')
            ->pluck('letzte_reinigung', 'gebaeude_id')
            ->map(fn($d) => $d ? Carbon::parse($d) : null)
            ->toArray();
        
        // Reinigungen im aktuellen Jahr pro Gebäude
        $reinigungsZaehler = DB::table('timeline')
            ->select('gebaeude_id', DB::raw('COUNT(*) as anzahl'))
            ->whereYear('datum', $stichtag->year)
            ->groupBy('gebaeude_id')
            ->pluck('anzahl', 'gebaeude_id')
            ->toArray();
        
        Log::info('Fälligkeit-Update: Timeline-Daten geladen', [
            'gebaeude_mit_reinigung' => count($letzteReinigungen),
            'dauer' => round(microtime(true) - $startTime, 2) . 's',
        ]);
        
        // ═══════════════════════════════════════════════════════════════════
        // SCHRITT 2: Gebäude durchgehen und aktualisieren
        // ═══════════════════════════════════════════════════════════════════
        
        $gebaeudeQuery = Gebaeude::query()
            ->select([
                'id', 'codex', 'faellig', 'datum_faelligkeit', 'letzter_termin',
                'gemachte_reinigungen', 'geplante_reinigungen',
                'm01', 'm02', 'm03', 'm04', 'm05', 'm06', 'm07', 'm08', 'm09', 'm10', 'm11', 'm12'
            ]);
        
        // Updates sammeln für Batch-Update
        $updates = [];
        
        foreach ($gebaeudeQuery->cursor() as $gebaeude) {
            try {
                $stats['gesamt']++;
                
                // Aktive Monate aus dem Gebäude-Objekt (kein DB-Call!)
                $aktiveMonate = [];
                for ($m = 1; $m <= 12; $m++) {
                    $feld = 'm' . str_pad($m, 2, '0', STR_PAD_LEFT);
                    if ($gebaeude->{$feld}) {
                        $aktiveMonate[] = $m;
                    }
                }
                
                // Letzte Reinigung aus vorgeladenen Daten
                $letzteReinigung = $letzteReinigungen[$gebaeude->id] ?? null;
                
                // Fälligkeit berechnen (reine PHP-Berechnung!)
                $naechsteFaelligkeit = $this->berechneNaechsteFaelligkeit($aktiveMonate, $letzteReinigung);
                $istFaellig = $naechsteFaelligkeit->lte($stichtag);
                
                // Zähler aus vorgeladenen Daten
                $gemachteReinigungen = $reinigungsZaehler[$gebaeude->id] ?? 0;
                $geplanteReinigungen = count($aktiveMonate) ?: 1;
                
                // Statistik
                if ($istFaellig) {
                    $stats['faellig']++;
                } else {
                    $stats['nicht_faellig']++;
                }
                
                // Prüfen ob Update nötig
                $altFaellig = (bool) $gebaeude->faellig;
                $needsUpdate = ($altFaellig !== $istFaellig)
                    || ($gebaeude->gemachte_reinigungen != $gemachteReinigungen)
                    || ($gebaeude->geplante_reinigungen != $geplanteReinigungen);
                
                if ($needsUpdate) {
                    $stats['geaendert']++;
                    
                    // Direkt updaten (einzeln, aber ohne N+1 bei SELECT)
                    DB::table('gebaeude')
                        ->where('id', $gebaeude->id)
                        ->update([
                            'faellig' => $istFaellig,
                            'datum_faelligkeit' => $naechsteFaelligkeit->format('Y-m-d'),
                            'letzter_termin' => $letzteReinigung?->format('Y-m-d'),
                            'gemachte_reinigungen' => $gemachteReinigungen,
                            'geplante_reinigungen' => $geplanteReinigungen,
                            'updated_at' => now(),
                        ]);
                }
                
            } catch (\Exception $e) {
                $stats['fehler']++;
                Log::error('Fälligkeit-Update Fehler', [
                    'gebaeude_id' => $gebaeude->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        $stats['dauer_sekunden'] = round(microtime(true) - $startTime, 2);
        
        Log::info('Fälligkeit-Batch abgeschlossen', $stats);
        
        return $stats;
    }

    /**
     * ⭐ SIMULATOR: Testet die Fälligkeitslogik mit beliebigen Parametern
     */
    public function simuliere(
        array $aktiveMonate,
        ?Carbon $letzteReinigung = null,
        ?Carbon $stichtag = null
    ): array {
        $stichtag = $stichtag ?? now();
        
        // Fälligkeit berechnen
        $naechsteFaelligkeit = $this->berechneNaechsteFaelligkeit($aktiveMonate, $letzteReinigung);
        $istFaellig = $naechsteFaelligkeit->lte($stichtag);
        
        // Alle Fälligkeitstermine im Jahr
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

    private function monateAlsNamen(array $monate): array
    {
        $namen = [
            1 => 'Januar', 2 => 'Februar', 3 => 'März', 4 => 'April',
            5 => 'Mai', 6 => 'Juni', 7 => 'Juli', 8 => 'August',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Dezember',
        ];
        
        return array_map(fn($m) => $namen[$m] ?? '?', $monate);
    }

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
