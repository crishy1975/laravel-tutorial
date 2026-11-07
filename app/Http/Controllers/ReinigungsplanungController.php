<?php
// ═══════════════════════════════════════════════════════════════════════════════
// DATEI: app/Http/Controllers/ReinigungsplanungController.php
// AKTION: Komplette Datei ersetzen
// 
// ⭐ KORREKTUR: FaelligkeitsService wird NICHT mehr bei index() aufgerufen!
//    Die Fälligkeitsdaten werden aus der Datenbank gelesen (bereits gespeichert).
//    Der Service wird nur bei markErledigt() aufgerufen (Timeline-Eintrag).
//
// ⭐ NEU: Status "Angekündigt" (angekuendigt_am / angekuendigt_fuer)
//    - setAngekuendigt(): manuell oder automatisch bei SMS/WhatsApp
//    - markErledigt(): setzt Ankündigung wieder auf NULL
//    - Status-Filter + Statistik "angekuendigt"
// ═══════════════════════════════════════════════════════════════════════════════

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
     * 
     * ⭐ OPTIMIERT: Keine FaelligkeitsService-Aufrufe mehr!
     *    Die Felder faellig, datum_faelligkeit, letzter_termin sind bereits in der DB.
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
        
        // ⭐ Filter nach Datum und/oder Person (über Timeline)
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

        // ⭐ Filter: Status über DB-Felder 'faellig' + 'angekuendigt_am'
        $this->applyStatusFilter($query, $filterStatus);

        // Sortierung: Normalisierte Adresse (Codex-Prefix → Straße → Hausnummer)
        $query->sortByAddress();

        // ⭐ Statistiken VOR Pagination berechnen (Clone der Query)
        $statsQuery = clone $query;
        $totalCount = $statsQuery->count();
        
        // Für Status-Statistik: Ohne Status-Filter
        $statsBaseQuery = Gebaeude::query()->with(['touren']);
        
        // Gleiche Filter wie oben (außer Status)
        if (!empty($filterDatum) || !empty($filterPerson)) {
            $statsBaseQuery->whereHas('timelines', function ($q) use ($filterDatum, $filterPerson) {
                if (!empty($filterDatum)) {
                    $q->whereDate('datum', $filterDatum);
                }
                if (!empty($filterPerson)) {
                    $q->where('person_id', $filterPerson);
                }
            });
        }
        if (!empty($filterMonat) && $filterMonat >= 1 && $filterMonat <= 12) {
            $monatFeld = 'm' . str_pad($filterMonat, 2, '0', STR_PAD_LEFT);
            $statsBaseQuery->where($monatFeld, true);
        }
        if (!empty($filterCodex)) {
            $statsBaseQuery->where('codex', 'LIKE', '%' . $filterCodex . '%');
        }
        if (!empty($filterGebaeude)) {
            $statsBaseQuery->where(function ($q) use ($filterGebaeude) {
                $q->where('gebaeude_name', 'LIKE', '%' . $filterGebaeude . '%')
                  ->orWhere('strasse', 'LIKE', '%' . $filterGebaeude . '%')
                  ->orWhere('wohnort', 'LIKE', '%' . $filterGebaeude . '%');
            });
        }
        if (!empty($filterTour)) {
            $statsBaseQuery->whereHas('touren', function ($q) use ($filterTour) {
                $q->where('tour.id', $filterTour);
            });
        }
        
        $gesamtCount       = $statsBaseQuery->count();
        $offenCount        = (clone $statsBaseQuery)->where('faellig', true)->whereNull('angekuendigt_am')->count();
        $erledigtCount     = (clone $statsBaseQuery)->where('faellig', false)->count();
        $angekuendigtCount = (clone $statsBaseQuery)->where('faellig', true)->whereNotNull('angekuendigt_am')->count();
        
        $stats = [
            'gesamt'       => $gesamtCount,
            'offen'        => $offenCount,
            'erledigt'     => $erledigtCount,
            'angekuendigt' => $angekuendigtCount,
        ];

        // ⭐ Pagination (20 pro Seite) - direkt über Query Builder
        $perPage = 20;
        $gebaeude = $query->paginate($perPage)->appends($filters);

        // ⭐ Attribute für View hinzufügen (aus DB-Feldern, KEIN Service-Aufruf!)
        $gebaeude->getCollection()->transform(function ($g) {
            // Diese Felder sind bereits in der Datenbank gespeichert!
            $g->letzte_reinigung_datum = $g->letzter_termin ? Carbon::parse($g->letzter_termin) : null;
            $g->ist_erledigt = !$g->faellig; // Erledigt = nicht fällig
            $g->ist_angekuendigt = !$g->ist_erledigt && $g->angekuendigt_am !== null;
            $g->naechste_faelligkeit = $g->datum_faelligkeit ? Carbon::parse($g->datum_faelligkeit) : null;
            return $g;
        });

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

        // ⭐ Nachricht-Vorschläge für SMS/WhatsApp Modal
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
     * 
     * ⭐ HIER wird der FaelligkeitsService aufgerufen - nur bei Änderungen!
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

        // Gebäude aktualisieren + ⭐ Ankündigung zurücksetzen
        $updateData = [
            'letzter_termin'    => $datum,
            'angekuendigt_fuer' => null,
            'angekuendigt_am'   => null,
        ];
        
        // rechnung_schreiben = true (nur wenn FatturaPA-Profil vorhanden)
        if ($gebaeude->fattura_profile_id) {
            $updateData['rechnung_schreiben'] = true;
        }
        
        $gebaeude->update($updateData);

        // ⭐ FaelligkeitsService: Nur HIER aufrufen (bei Timeline-Änderung)
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
     * ⭐ NEU: Gebäude als angekündigt markieren / Markierung entfernen
     *
     * Erwartet per JSON (fetch):
     *   { "angekuendigt": 1|0, "termin": "YYYY-MM-DD"|null }
     */
    public function setAngekuendigt(Request $request, int $gebaeude)
    {
        $g = Gebaeude::findOrFail($gebaeude);

        $data = $request->validate([
            'angekuendigt' => ['required', 'boolean'],
            'termin'       => ['nullable', 'date'],
        ]);

        if ($data['angekuendigt']) {
            $g->update([
                'angekuendigt_fuer' => $data['termin'] ?? null,
                'angekuendigt_am'   => now(),
            ]);
        } else {
            $g->update([
                'angekuendigt_fuer' => null,
                'angekuendigt_am'   => null,
            ]);
        }

        return response()->json([
            'ok'           => true,
            'angekuendigt' => (bool) $data['angekuendigt'],
        ]);
    }

    /**
     * ⭐ NEU: Interne Nr. setzen oder schnell um ±1 korrigieren
     *
     * Erwartet per JSON (fetch):
     *   { "delta": 1 | -1 }        → Zahl um 1 erhöhen / verringern
     *   { "intern": "3a" | null }  → Wert direkt setzen
     */
    public function setIntern(Request $request, int $gebaeude)
    {
        $g = Gebaeude::findOrFail($gebaeude);

        $data = $request->validate([
            'delta'  => ['nullable', 'integer', 'in:-1,1'],
            'intern' => ['nullable', 'string', 'max:20'],
        ]);

        if (isset($data['delta'])) {
            $neu = $this->internVerschieben($g->intern, (int) $data['delta']);
        } else {
            $neu = isset($data['intern']) ? trim($data['intern']) : null;
        }

        $g->update(['intern' => ($neu === '' ? null : $neu)]);

        return response()->json([
            'ok'     => true,
            'intern' => $g->intern,
        ]);
    }

    /**
     * Zahl in der internen Nr. um $delta verschieben.
     *
     * - leer + "+"  → "1"
     * - "3"  ± 1    → "4" / "2"
     * - "3a" ± 1    → "4a" / "2a"   (Präfix/Suffix bleiben erhalten)
     * - kleiner als 1 wird nicht unterschritten
     * - ohne Zahl ("A") → unverändert
     */
    protected function internVerschieben(?string $intern, int $delta): ?string
    {
        $intern = trim((string) $intern);

        if ($intern === '') {
            return $delta > 0 ? '1' : null;
        }

        if (preg_match('/^(\D*)(\d+)(.*)$/', $intern, $m)) {
            $zahl = max(1, (int) $m[2] + $delta);
            return $m[1] . $zahl . $m[3];
        }

        return $intern;
    }

    /**
     * Export als CSV
     * 
     * ⭐ OPTIMIERT: Verwendet ebenfalls DB-Felder statt Service
     */
    public function export(Request $request)
    {
        // ⭐ Filter auch aus Session laden für Export
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
        
        // ⭐ Filter nach Datum und/oder Person (über Timeline)
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

        // ⭐ Status-Filter über DB-Felder
        $this->applyStatusFilter($query, $filterStatus);

        // Sortierung: Normalisierte Adresse (Codex-Prefix → Straße → Hausnummer)
        $query->sortByAddress();

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
            fputcsv($file, ['Codex', 'Gebäude', 'Adresse', 'Intern', 'Tour(en)', 'Letzte Reinigung', 'Nächste Fälligkeit', 'Status', 'Angekündigt für'], ';');

            foreach ($gebaeude as $g) {
                // ⭐ Direkt aus DB-Feldern lesen - KEIN Service-Aufruf!
                $letzteReinigung = $g->letzter_termin ? Carbon::parse($g->letzter_termin) : null;
                $naechsteFaelligkeit = $g->datum_faelligkeit ? Carbon::parse($g->datum_faelligkeit) : null;
                $istFaellig = (bool) $g->faellig;
                $istAngekuendigt = $istFaellig && $g->angekuendigt_am !== null;

                $status = !$istFaellig ? 'Erledigt' : ($istAngekuendigt ? 'Angekündigt' : 'Offen');

                fputcsv($file, [
                    $g->codex,
                    $g->gebaeude_name,
                    trim($g->strasse . ' ' . $g->hausnummer . ', ' . $g->plz . ' ' . $g->wohnort),
                    $g->intern ?? '',
                    $g->touren->pluck('name')->implode(', '),
                    $letzteReinigung ? $letzteReinigung->format('d.m.Y') : '-',
                    $naechsteFaelligkeit ? $naechsteFaelligkeit->format('d.m.Y') : '-',
                    $status,
                    ($istAngekuendigt && $g->angekuendigt_fuer) ? Carbon::parse($g->angekuendigt_fuer)->format('d.m.Y') : '',
                ], ';');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * ⭐ Status-Filter (offen / angekündigt / erledigt)
     *
     * - offen:        fällig und NICHT angekündigt
     * - angekuendigt: fällig und angekündigt
     * - erledigt:     nicht fällig
     */
    protected function applyStatusFilter($query, ?string $filterStatus): void
    {
        if ($filterStatus === 'offen') {
            $query->where('faellig', true)->whereNull('angekuendigt_am');
        } elseif ($filterStatus === 'angekuendigt') {
            $query->where('faellig', true)->whereNotNull('angekuendigt_am');
        } elseif ($filterStatus === 'erledigt') {
            $query->where('faellig', false);
        }
    }
}
