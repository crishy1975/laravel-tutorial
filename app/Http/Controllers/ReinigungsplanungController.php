<?php

namespace App\Http\Controllers;

use App\Models\Gebaeude;
use App\Models\Textvorschlag;
use App\Models\Tour;
use App\Models\User;
use App\Services\FaelligkeitsService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class ReinigungsplanungController extends Controller
{
    public function __construct(
        protected FaelligkeitsService $faelligkeitsService
    ) {}

    /**
     * Reinigungsplanung-Übersicht mit Filtern
     * ⭐ NEU: Filter werden in Session gespeichert
     */
    public function index(Request $request)
    {
        // ⭐ Session-Key für Filter
        $sessionKey = 'reinigungsplanung_filter';
        
        // Wenn clear_filter gesetzt → Session löschen und redirect
        if ($request->has('clear_filter')) {
            $request->session()->forget($sessionKey);
            return redirect()->route('reinigungsplanung.index');
        }
        
        // Prüfen ob Query-Parameter vorhanden sind
        $hasQueryParams = $request->hasAny(['codex', 'gebaeude', 'monat', 'tour', 'status', 'datum', 'person']);
        
        // Filter aus Request oder Session
        if ($hasQueryParams) {
            // Query-Parameter → in Session speichern
            $filters = [
                'codex'    => $request->input('codex', ''),
                'gebaeude' => $request->input('gebaeude', ''),
                'monat'    => $request->input('monat', ''),
                'tour'     => $request->input('tour', ''),
                'status'   => $request->input('status', ''),
                'datum'    => $request->input('datum', ''),
                'person'   => $request->input('person', ''),
            ];
            $request->session()->put($sessionKey, $filters);
        } else {
            // Keine Query-Parameter → aus Session laden (falls vorhanden)
            $filters = $request->session()->get($sessionKey, [
                'codex'    => '',
                'gebaeude' => '',
                'monat'    => '',
                'tour'     => '',
                'status'   => '',
                'datum'    => '',
                'person'   => '',
            ]);
        }
        
        // Filter-Werte extrahieren
        $filterCodex    = $filters['codex'] ?? '';
        $filterGebaeude = $filters['gebaeude'] ?? '';
        $filterMonat    = $filters['monat'] ?? '';
        $filterTour     = $filters['tour'] ?? '';
        $filterStatus   = $filters['status'] ?? '';
        $filterDatum    = $filters['datum'] ?? '';
        $filterPerson   = $filters['person'] ?? '';

        // Query aufbauen
        $query = Gebaeude::query()->with(['touren']);
        
        // ⭐ NEU: Filter nach Datum und/oder Person (über Timeline)
        if (!empty($filterDatum) || !empty($filterPerson)) {
            $query->whereHas('timelines', function ($q) use ($filterDatum, $filterPerson) {
                if (!empty($filterDatum)) {
                    $q->whereDate('datum', $filterDatum);
                }
                if (!empty($filterPerson)) {
                    $q->where('person_id', $filterPerson);
                }
            });
        }

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

        // ⭐ NEU: FaelligkeitsService für Berechnung verwenden
        $gebaeude = $gebaeude->map(function ($g) {
            $letzteReinigung = $this->faelligkeitsService->getLetzteReinigung($g);
            $naechsteFaelligkeit = $this->faelligkeitsService->getNaechsteFaelligkeit($g);
            $istFaellig = $this->faelligkeitsService->istFaellig($g);

            // Als zusätzliche Attribute anhängen
            $g->letzte_reinigung_datum = $letzteReinigung;
            $g->ist_erledigt = !$istFaellig; // Erledigt = nicht fällig
            $g->naechste_faelligkeit = $naechsteFaelligkeit;

            return $g;
        });

        // Filter: Status (nach Berechnung)
        if ($filterStatus === 'offen') {
            $gebaeude = $gebaeude->filter(fn($g) => !$g->ist_erledigt);
        } elseif ($filterStatus === 'erledigt') {
            $gebaeude = $gebaeude->filter(fn($g) => $g->ist_erledigt);
        }

        // Statistiken (vor Pagination!)
        $stats = [
            'gesamt'   => $gebaeude->count(),
            'offen'    => $gebaeude->filter(fn($g) => !$g->ist_erledigt)->count(),
            'erledigt' => $gebaeude->filter(fn($g) => $g->ist_erledigt)->count(),
        ];

        // ⭐ Pagination (20 pro Seite)
        $perPage = 20;
        $currentPage = $request->input('page', 1);
        $pagedData = $gebaeude->forPage($currentPage, $perPage);
        
        $gebaeude = new \Illuminate\Pagination\LengthAwarePaginator(
            $pagedData->values(),
            $stats['gesamt'],
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'query' => $filters, // Filter an Pagination-Links anhängen
            ]
        );

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

        // ⭐ NEU: Nachricht-Vorschläge für SMS/WhatsApp Modal
        $nachrichtVorschlaege = Textvorschlag::fuerKategorie('reinigung_nachricht');

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
            'filterStatus',
            'filterDatum',
            'filterPerson',
            'nachrichtVorschlaege'
        ));
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

        // Gebäude aktualisieren: rechnung_schreiben = true (nur wenn FatturaPA-Profil vorhanden)
        $updateData = ['letzter_termin' => $datum];
        
        if ($gebaeude->fattura_profile_id) {
            $updateData['rechnung_schreiben'] = true;
        }
        
        $gebaeude->update($updateData);

        // ⭐ Fälligkeit über Service neu berechnen (aktualisiert auch gemachte_reinigungen)
        $this->faelligkeitsService->aktualisiereGebaeude($gebaeude);

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
        // ⭐ NEU: Filter auch aus Session laden für Export
        $sessionKey = 'reinigungsplanung_filter';
        $filters = $request->session()->get($sessionKey, []);
        
        // Query-Parameter überschreiben Session
        $filterCodex    = $request->input('codex', $filters['codex'] ?? '');
        $filterGebaeude = $request->input('gebaeude', $filters['gebaeude'] ?? '');
        $filterMonat    = $request->input('monat', $filters['monat'] ?? '');
        $filterTour     = $request->input('tour', $filters['tour'] ?? '');
        $filterStatus   = $request->input('status', $filters['status'] ?? '');
        $filterDatum    = $request->input('datum', $filters['datum'] ?? '');
        $filterPerson   = $request->input('person', $filters['person'] ?? '');

        $query = Gebaeude::query()->with(['touren']);
        
        // ⭐ NEU: Filter nach Datum und/oder Person (über Timeline)
        if (!empty($filterDatum) || !empty($filterPerson)) {
            $query->whereHas('timelines', function ($q) use ($filterDatum, $filterPerson) {
                if (!empty($filterDatum)) {
                    $q->whereDate('datum', $filterDatum);
                }
                if (!empty($filterPerson)) {
                    $q->where('person_id', $filterPerson);
                }
            });
        }

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
                $letzteReinigung = $this->faelligkeitsService->getLetzteReinigung($g);
                $naechsteFaelligkeit = $this->faelligkeitsService->getNaechsteFaelligkeit($g);
                $istFaellig = $this->faelligkeitsService->istFaellig($g);

                fputcsv($file, [
                    $g->codex,
                    $g->gebaeude_name,
                    trim($g->strasse . ' ' . $g->hausnummer . ', ' . $g->plz . ' ' . $g->wohnort),
                    $g->touren->pluck('name')->implode(', '),
                    $letzteReinigung ? $letzteReinigung->format('d.m.Y') : '-',
                    $naechsteFaelligkeit->format('d.m.Y'),
                    $istFaellig ? 'Offen' : 'Erledigt',
                ], ';');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
