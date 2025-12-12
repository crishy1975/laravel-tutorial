<?php

namespace App\Http\Controllers;

use App\Models\Gebaeude;
use App\Models\Tour;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class ReinigungsplanungController extends Controller
{
    /**
     * Reinigungsplanung-Übersicht mit Filtern
     */
    public function index(Request $request)
    {
        // Filter-Parameter
        $filterCodex    = $request->input('codex', '');
        $filterGebaeude = $request->input('gebaeude', '');
        $filterMonat    = $request->input('monat', ''); // Leer = alle Monate
        $filterTour     = $request->input('tour', '');
        $filterStatus   = $request->input('status', ''); // '', 'offen', 'erledigt'

        // Query aufbauen
        $query = Gebaeude::query()->with(['touren']);

        // Filter: Monat (nur wenn ausgewählt) - zeigt nur Gebäude die in diesem Monat aktiv sind
        if (!empty($filterMonat) && $filterMonat >= 1 && $filterMonat <= 12) {
            $monatFeld = 'm' . str_pad($filterMonat, 2, '0', STR_PAD_LEFT);
            $query->where($monatFeld, true);
        }

        // Filter: Codex
        if (!empty($filterCodex)) {
            $query->where('codex', 'LIKE', '%' . $filterCodex . '%');
        }

        // Filter: Gebäude-Name
        if (!empty($filterGebaeude)) {
            $query->where(function ($q) use ($filterGebaeude) {
                $q->where('gebaeude_name', 'LIKE', '%' . $filterGebaeude . '%')
                  ->orWhere('strasse', 'LIKE', '%' . $filterGebaeude . '%')
                  ->orWhere('wohnort', 'LIKE', '%' . $filterGebaeude . '%');
            });
        }

        // Filter: Tour (Tabelle heißt "tour" nicht "touren")
        if (!empty($filterTour)) {
            $query->whereHas('touren', function ($q) use ($filterTour) {
                $q->where('tour.id', $filterTour);
            });
        }

        // Sortierung: Straße, dann Hausnummer (numerisch)
        $query->orderBy('strasse')
              ->orderByRaw('CAST(hausnummer AS UNSIGNED)')
              ->orderBy('hausnummer');

        // Gebäude laden
        $gebaeude = $query->get();

        // Für jedes Gebäude: letzte Reinigung und Status berechnen
        $gebaeude = $gebaeude->map(function ($g) {
            // Letzte Reinigung aus Timeline
            $letzteReinigung = $g->lastCleaningDate();
            
            // ⭐ NEUE LOGIK: Erledigt basierend auf Fälligkeits-Intervallen
            $erledigt = $this->istGebaeudeErledigt($g, $letzteReinigung);

            // Nächste Fälligkeit berechnen
            $naechsteFaelligkeit = $this->getNaechsteFaelligkeit($g);

            // Als zusätzliche Attribute anhängen
            $g->letzte_reinigung_datum = $letzteReinigung;
            $g->ist_erledigt = $erledigt;
            $g->naechste_faelligkeit = $naechsteFaelligkeit;

            return $g;
        });

        // Filter: Status (nach Berechnung)
        if ($filterStatus === 'offen') {
            $gebaeude = $gebaeude->filter(fn($g) => !$g->ist_erledigt);
        } elseif ($filterStatus === 'erledigt') {
            $gebaeude = $gebaeude->filter(fn($g) => $g->ist_erledigt);
        }

        // Statistiken
        $stats = [
            'gesamt'   => $gebaeude->count(),
            'offen'    => $gebaeude->filter(fn($g) => !$g->ist_erledigt)->count(),
            'erledigt' => $gebaeude->filter(fn($g) => $g->ist_erledigt)->count(),
        ];

        // Touren für Dropdown
        $touren = Tour::orderBy('name')->get(['id', 'name', 'aktiv']);

        // User für Dropdown (Person-Auswahl)
        $users = User::orderBy('name')->get(['id', 'name']);

        // Monatsnamen für Dropdown
        $monate = [
            1  => 'Januar',
            2  => 'Februar',
            3  => 'März',
            4  => 'April',
            5  => 'Mai',
            6  => 'Juni',
            7  => 'Juli',
            8  => 'August',
            9  => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Dezember',
        ];

        return view('reinigungsplanung.index', compact(
            'gebaeude',
            'touren',
            'users',
            'monate',
            'stats',
            'filterCodex',
            'filterGebaeude',
            'filterMonat',
            'filterTour',
            'filterStatus'
        ));
    }

    /**
     * ⭐ Ermittelt die aktiven Monate eines Gebäudes (m01-m12)
     * 
     * @param Gebaeude $gebaeude
     * @return array Array mit Monatsnummern [1, 4, 7, 10] für quartalsweise
     */
    private function getAktiveMonate(Gebaeude $gebaeude): array
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
     * ⭐ Ermittelt den letzten Fälligkeitstermin vor/an einem Datum
     * 
     * @param Gebaeude $gebaeude
     * @param Carbon|null $datum Referenzdatum (Standard: heute)
     * @return Carbon Der letzte Fälligkeitstermin
     */
    private function getLetzteFaelligkeit(Gebaeude $gebaeude, ?Carbon $datum = null): Carbon
    {
        $datum = $datum ?? now();
        $aktiveMonate = $this->getAktiveMonate($gebaeude);
        
        // Wenn keine Monate aktiv → einmal im Jahr am 01.01.
        if (empty($aktiveMonate)) {
            return Carbon::create($datum->year, 1, 1)->startOfDay();
        }
        
        $aktuellerMonat = $datum->month;
        $aktuellesJahr = $datum->year;
        
        // Finde den letzten aktiven Monat <= aktueller Monat
        $letzterAktiverMonat = null;
        $jahrOffset = 0;
        
        // Zuerst im aktuellen Jahr suchen (Monate <= heute)
        foreach (array_reverse($aktiveMonate) as $m) {
            if ($m <= $aktuellerMonat) {
                $letzterAktiverMonat = $m;
                break;
            }
        }
        
        // Wenn nicht gefunden → letzter Monat vom Vorjahr
        if ($letzterAktiverMonat === null) {
            $letzterAktiverMonat = end($aktiveMonate); // höchster aktiver Monat
            $jahrOffset = -1;
        }
        
        return Carbon::create($aktuellesJahr + $jahrOffset, $letzterAktiverMonat, 1)->startOfDay();
    }

    /**
     * ⭐ Ermittelt den nächsten Fälligkeitstermin nach einem Datum
     * 
     * @param Gebaeude $gebaeude
     * @param Carbon|null $datum Referenzdatum (Standard: heute)
     * @return Carbon Der nächste Fälligkeitstermin
     */
    private function getNaechsteFaelligkeit(Gebaeude $gebaeude, ?Carbon $datum = null): Carbon
    {
        $datum = $datum ?? now();
        $aktiveMonate = $this->getAktiveMonate($gebaeude);
        
        // Wenn keine Monate aktiv → nächstes Jahr 01.01.
        if (empty($aktiveMonate)) {
            return Carbon::create($datum->year + 1, 1, 1)->startOfDay();
        }
        
        $aktuellerMonat = $datum->month;
        $aktuellesJahr = $datum->year;
        
        // Finde den nächsten aktiven Monat > aktueller Monat
        $naechsterAktiverMonat = null;
        $jahrOffset = 0;
        
        foreach ($aktiveMonate as $m) {
            if ($m > $aktuellerMonat) {
                $naechsterAktiverMonat = $m;
                break;
            }
        }
        
        // Wenn nicht gefunden → erster Monat vom nächsten Jahr
        if ($naechsterAktiverMonat === null) {
            $naechsterAktiverMonat = reset($aktiveMonate); // niedrigster aktiver Monat
            $jahrOffset = 1;
        }
        
        return Carbon::create($aktuellesJahr + $jahrOffset, $naechsterAktiverMonat, 1)->startOfDay();
    }

    /**
     * ⭐ Prüft ob ein Gebäude "erledigt" ist
     * 
     * Logik:
     * - Ermittle den letzten Fälligkeitstermin basierend auf m01-m12
     * - Erledigt = letzte Reinigung >= letzter Fälligkeitstermin
     * 
     * Beispiel: m01=true, m04=true (Januar & April)
     * - Heute: 12.12.2025
     * - Letzte Fälligkeit: 01.04.2025
     * - Erledigt wenn Reinigung >= 01.04.2025
     * 
     * @param Gebaeude $gebaeude
     * @param Carbon|null $letzteReinigung
     * @return bool
     */
    private function istGebaeudeErledigt(Gebaeude $gebaeude, ?Carbon $letzteReinigung): bool
    {
        // Keine Reinigung → nie erledigt
        if (!$letzteReinigung) {
            return false;
        }
        
        // Letzten Fälligkeitstermin ermitteln
        $letzteFaelligkeit = $this->getLetzteFaelligkeit($gebaeude);
        
        // Erledigt wenn Reinigung >= Fälligkeit
        return $letzteReinigung->greaterThanOrEqualTo($letzteFaelligkeit);
    }

    /**
     * Schnell-Aktion: Als erledigt markieren (Timeline-Eintrag erstellen)
     */
    public function markErledigt(Request $request, int $gebaeudeId)
    {
        $gebaeude = Gebaeude::findOrFail($gebaeudeId);

        $data = $request->validate([
            'datum'     => ['nullable', 'date'],
            'bemerkung' => ['nullable', 'string', 'max:500'],
            'person_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $datum = isset($data['datum']) ? Carbon::parse($data['datum']) : now();

        // User laden für person_name
        $user = User::find($data['person_id']);

        // Timeline-Eintrag erstellen
        $gebaeude->timelines()->create([
            'datum'       => $datum,
            'bemerkung'   => $data['bemerkung'] ?? 'Reinigung durchgeführt',
            'person_id'   => $data['person_id'],
            'person_name' => $user->name,
        ]);

        // ⭐ Gebäude aktualisieren: rechnung_schreiben = true
        $gebaeude->update([
            'rechnung_schreiben' => true,
            'letzter_termin'     => $datum,
        ]);

        // Fälligkeitsstatus neu berechnen
        $gebaeude->recomputeFaellig();

        if ($request->expectsJson()) {
            return response()->json([
                'ok'      => true,
                'message' => 'Reinigung wurde eingetragen.',
            ]);
        }

        return back()->with('success', 'Reinigung für ' . ($gebaeude->gebaeude_name ?: $gebaeude->codex) . ' eingetragen.');
    }

    /**
     * Export als CSV
     */
    public function export(Request $request)
    {
        // Gleiche Filter wie index()
        $filterCodex    = $request->input('codex', '');
        $filterGebaeude = $request->input('gebaeude', '');
        $filterMonat    = $request->input('monat', '');
        $filterTour     = $request->input('tour', '');
        $filterStatus   = $request->input('status', '');

        $query = Gebaeude::query()->with(['touren']);

        // Filter: Monat (nur wenn ausgewählt)
        if (!empty($filterMonat) && $filterMonat >= 1 && $filterMonat <= 12) {
            $monatFeld = 'm' . str_pad($filterMonat, 2, '0', STR_PAD_LEFT);
            $query->where($monatFeld, true);
        }

        if (!empty($filterCodex)) {
            $query->where('codex', 'LIKE', '%' . $filterCodex . '%');
        }

        if (!empty($filterGebaeude)) {
            $query->where(function ($q) use ($filterGebaeude) {
                $q->where('gebaeude_name', 'LIKE', '%' . $filterGebaeude . '%')
                  ->orWhere('strasse', 'LIKE', '%' . $filterGebaeude . '%')
                  ->orWhere('wohnort', 'LIKE', '%' . $filterGebaeude . '%');
            });
        }

        // Filter: Tour (Tabelle heißt "tour" nicht "touren")
        if (!empty($filterTour)) {
            $query->whereHas('touren', function ($q) use ($filterTour) {
                $q->where('tour.id', $filterTour);
            });
        }

        // Sortierung: Straße, dann Hausnummer (numerisch)
        $query->orderBy('strasse')
              ->orderByRaw('CAST(hausnummer AS UNSIGNED)')
              ->orderBy('hausnummer');

        $gebaeude = $query->get();

        // CSV erstellen
        $monate = [
            1 => 'Januar', 2 => 'Februar', 3 => 'März', 4 => 'April',
            5 => 'Mai', 6 => 'Juni', 7 => 'Juli', 8 => 'August',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Dezember',
        ];

        $monatName = !empty($filterMonat) ? $monate[$filterMonat] : 'Alle';
        $filename = 'reinigungsplanung_' . $monatName . '_' . now()->year . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($gebaeude) {
            $file = fopen('php://output', 'w');
            
            // BOM für Excel UTF-8
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Header
            fputcsv($file, ['Codex', 'Gebäude', 'Adresse', 'Tour(en)', 'Letzte Reinigung', 'Nächste Fälligkeit', 'Status'], ';');

            foreach ($gebaeude as $g) {
                $letzteReinigung = $g->lastCleaningDate();
                $erledigt = $this->istGebaeudeErledigt($g, $letzteReinigung);
                $naechsteFaelligkeit = $this->getNaechsteFaelligkeit($g);

                fputcsv($file, [
                    $g->codex,
                    $g->gebaeude_name,
                    trim($g->strasse . ' ' . $g->hausnummer . ', ' . $g->plz . ' ' . $g->wohnort),
                    $g->touren->pluck('name')->implode(', '),
                    $letzteReinigung ? $letzteReinigung->format('d.m.Y') : '-',
                    $naechsteFaelligkeit->format('d.m.Y'),
                    $erledigt ? 'Erledigt' : 'Offen',
                ], ';');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
