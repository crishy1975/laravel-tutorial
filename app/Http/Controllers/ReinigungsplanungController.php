<?php
// ═══════════════════════════════════════════════════════════════════════════════
// DATEI: app/Http/Controllers/ReinigungsplanungController.php
// AKTION: Komplette Datei ersetzen
//
// ⭐ FaelligkeitsService wird NICHT bei index() aufgerufen –
//    faellig / datum_faelligkeit / letzter_termin kommen aus der DB.
//    Der Service läuft nur bei markErledigt() (Timeline-Eintrag).
//
// ⭐ Status "Angekündigt" (angekuendigt_am / angekuendigt_fuer)
// ⭐ Interne Nr. (intern) mit ±-Korrektur
// ⭐ NEU: Filterlogik zentral in buildQuery() – genutzt von
//         index(), export() und ids() ("alle Seiten auswählen")
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
    protected const SESSION_KEY = 'reinigungsplanung_filter';

    protected const FILTER_KEYS = ['codex', 'gebaeude', 'monat', 'tour', 'status', 'datum', 'person'];

    public function __construct(
        protected FaelligkeitsService $faelligkeitsService
    ) {}

    // ═══════════════════════════════════════════════════════════════════════
    // ÜBERSICHT
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Reinigungsplanung-Übersicht mit Filtern
     */
    public function index(Request $request)
    {
        // Wenn clear_filter gesetzt → Session löschen und redirect
        if ($request->has('clear_filter')) {
            $request->session()->forget(self::SESSION_KEY);
            return redirect()->route('reinigungsplanung.index');
        }

        // Filter aus Request (→ Session speichern) oder aus Session
        if ($request->hasAny(self::FILTER_KEYS)) {
            $filters = [];
            foreach (self::FILTER_KEYS as $key) {
                $filters[$key] = $request->input($key, '');
            }
            $request->session()->put(self::SESSION_KEY, $filters);
        } else {
            $filters = $this->filtersAusSession($request);
        }

        // Filter-Werte für die View
        $filterCodex    = $filters['codex'] ?? '';
        $filterGebaeude = $filters['gebaeude'] ?? '';
        $filterMonat    = $filters['monat'] ?? '';
        $filterTour     = $filters['tour'] ?? '';
        $filterStatus   = $filters['status'] ?? '';
        $filterDatum    = $filters['datum'] ?? '';
        $filterPerson   = $filters['person'] ?? '';

        // ── Statistik (gleiche Filter, aber OHNE Status) ──────────────────
        $statsBase = $this->buildQuery(array_merge($filters, ['status' => '']));

        $stats = [
            'gesamt'       => (clone $statsBase)->count(),
            'offen'        => $this->buildQuery(array_merge($filters, ['status' => 'offen']))->count(),
            'angekuendigt' => $this->buildQuery(array_merge($filters, ['status' => 'angekuendigt']))->count(),
            'erledigt'     => $this->buildQuery(array_merge($filters, ['status' => 'erledigt']))->count(),
        ];

        // ── Liste (mit Status-Filter) ──────────────────────────────────────
        $perPage  = 20;
        $gebaeude = $this->buildQuery($filters)
            ->with(['touren'])
            ->sortByAddress()
            ->paginate($perPage)
            ->appends($filters);

        // Attribute für View (aus DB-Feldern, KEIN Service-Aufruf!)
        $gebaeude->getCollection()->transform(function ($g) {
            $g->letzte_reinigung_datum = $g->letzter_termin ? Carbon::parse($g->letzter_termin) : null;
            $g->ist_erledigt           = !$g->faellig;
            $g->ist_angekuendigt       = !$g->ist_erledigt && $g->angekuendigt_am !== null;
            $g->naechste_faelligkeit   = $g->datum_faelligkeit ? Carbon::parse($g->datum_faelligkeit) : null;
            return $g;
        });

        $touren = Tour::orderBy('name')->get(['id', 'name', 'aktiv']);
        $users  = User::orderBy('name')->get(['id', 'name']);
        $monate = $this->monatsNamen();

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
     * ⭐ NEU: Alle Gebäude-IDs zum aktuellen Filter (alle Seiten).
     *
     * Für "Alle X Gebäude auf allen Seiten auswählen" in der Bulk-Leiste.
     * Nutzt exakt dieselben Filter wie die Liste (aus der Session).
     *
     * Route: GET /reinigungsplanung/ids
     */
    public function ids(Request $request)
    {
        $filters = $this->filtersAusSession($request);

        $ids = $this->buildQuery($filters)
            ->sortByAddress()
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->values();

        return response()->json([
            'ok'    => true,
            'count' => $ids->count(),
            'ids'   => $ids,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // AKTIONEN
    // ═══════════════════════════════════════════════════════════════════════

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

        $user = User::find($data['person_id']);

        $gebaeude->timelines()->create([
            'datum'       => $datum,
            'bemerkung'   => $data['bemerkung'] ?? 'Reinigung durchgeführt',
            'person_id'   => $data['person_id'],
            'person_name' => $user->name,
        ]);

        // Gebäude aktualisieren + Ankündigung zurücksetzen
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
     * Gebäude als angekündigt markieren / Markierung entfernen
     *
     * JSON: { "angekuendigt": 1|0, "termin": "YYYY-MM-DD"|null }
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
     * ⭐ NEU: Neue Tour schnell anlegen (aus der Bulk-Leiste)
     *
     * JSON: { "name": "Tour Leifers" }
     * Existiert bereits eine Tour mit gleichem Namen, wird diese zurückgegeben.
     *
     * Route: POST /reinigungsplanung/tour-neu
     */
    public function tourAnlegen(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ], [
            'name.required' => 'Bitte einen Namen für die Tour angeben.',
        ]);

        $name = trim($data['name']);

        // Doppelte Namen vermeiden (Groß-/Kleinschreibung egal)
        $vorhanden = Tour::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();
        if ($vorhanden) {
            return response()->json([
                'ok'        => true,
                'id'        => $vorhanden->id,
                'name'      => $vorhanden->name,
                'vorhanden' => true,
            ]);
        }

        $tour = new Tour();
        $tour->name  = $name;
        $tour->aktiv = true;
        $tour->save();

        return response()->json([
            'ok'        => true,
            'id'        => $tour->id,
            'name'      => $tour->name,
            'vorhanden' => false,
        ], 201);
    }

    /**
     * Interne Nr. setzen oder schnell um ±1 korrigieren
     *
     * JSON: { "delta": 1 | -1 }  oder  { "intern": "3a" | null }
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
     * - "3a" ± 1    → "4a" / "2a"
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

    // ═══════════════════════════════════════════════════════════════════════
    // EXPORT
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Export als CSV (gleiche Filter wie die Liste)
     */
    public function export(Request $request)
    {
        // Session-Filter, Query-Parameter überschreiben
        $filters = $this->filtersAusSession($request);
        foreach (self::FILTER_KEYS as $key) {
            if ($request->has($key)) {
                $filters[$key] = $request->input($key, '');
            }
        }

        $gebaeude = $this->buildQuery($filters)
            ->with(['touren'])
            ->sortByAddress()
            ->get();

        $monate      = $this->monatsNamen();
        $filterMonat = $filters['monat'] ?? '';
        $monatName   = !empty($filterMonat) && isset($monate[(int) $filterMonat]) ? $monate[(int) $filterMonat] : 'Alle';
        $filename    = 'reinigungsplanung_' . $monatName . '_' . now()->year . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($gebaeude) {
            $file = fopen('php://output', 'w');

            // BOM für Excel UTF-8
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($file, ['Codex', 'Gebäude', 'Adresse', 'Intern', 'Tour(en)', 'Letzte Reinigung', 'Nächste Fälligkeit', 'Status', 'Angekündigt für'], ';');

            foreach ($gebaeude as $g) {
                $letzteReinigung     = $g->letzter_termin ? Carbon::parse($g->letzter_termin) : null;
                $naechsteFaelligkeit = $g->datum_faelligkeit ? Carbon::parse($g->datum_faelligkeit) : null;
                $istFaellig          = (bool) $g->faellig;
                $istAngekuendigt     = $istFaellig && $g->angekuendigt_am !== null;

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

    // ═══════════════════════════════════════════════════════════════════════
    // FILTER-LOGIK (zentral)
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Filter aus der Session (mit leeren Defaults)
     */
    protected function filtersAusSession(Request $request): array
    {
        $defaults = array_fill_keys(self::FILTER_KEYS, '');
        return array_merge($defaults, (array) $request->session()->get(self::SESSION_KEY, []));
    }

    /**
     * ⭐ Baut die gefilterte Gebäude-Query (ohne Sortierung/Pagination).
     *
     * Wird von index(), export() und ids() verwendet,
     * damit überall exakt dieselben Gebäude herauskommen.
     */
    protected function buildQuery(array $f)
    {
        $query = Gebaeude::query();

        $datum  = $f['datum'] ?? '';
        $person = $f['person'] ?? '';
        $monat  = $f['monat'] ?? '';
        $codex  = $f['codex'] ?? '';
        $suche  = $f['gebaeude'] ?? '';
        $tour   = $f['tour'] ?? '';
        $status = $f['status'] ?? '';

        // Datum und/oder Person (über Timeline)
        if (!empty($datum) || !empty($person)) {
            $query->whereHas('timelines', function ($q) use ($datum, $person) {
                if (!empty($datum)) {
                    $q->whereDate('datum', $datum);
                }
                if (!empty($person)) {
                    $q->where('person_id', $person);
                }
            });
        }

        // Monat: nur Gebäude, die in diesem Monat aktiv sind
        if (!empty($monat) && $monat >= 1 && $monat <= 12) {
            $query->where('m' . str_pad((string) $monat, 2, '0', STR_PAD_LEFT), true);
        }

        // Codex
        if (!empty($codex)) {
            $query->where('codex', 'LIKE', '%' . $codex . '%');
        }

        // Gebäude-Name / Straße / Ort
        if (!empty($suche)) {
            $query->where(function ($q) use ($suche) {
                $q->where('gebaeude_name', 'LIKE', '%' . $suche . '%')
                  ->orWhere('strasse', 'LIKE', '%' . $suche . '%')
                  ->orWhere('wohnort', 'LIKE', '%' . $suche . '%');
            });
        }

        // Tour (Tabelle heißt "tour")
        if (!empty($tour)) {
            $query->whereHas('touren', function ($q) use ($tour) {
                $q->where('tour.id', $tour);
            });
        }

        // Status
        $this->applyStatusFilter($query, $status);

        return $query;
    }

    /**
     * Nur Gebäude, bei denen mindestens ein Reinigungsmonat (m01–m12) gesetzt ist.
     */
    protected function mitMindestensEinemMonat($query)
    {
        return $query->where(function ($q) {
            for ($i = 1; $i <= 12; $i++) {
                $q->orWhere('m' . str_pad((string) $i, 2, '0', STR_PAD_LEFT), true);
            }
        });
    }

    /**
     * Status-Filter (offen / angekündigt / erledigt)
     *
     * - offen:        fällig, NICHT angekündigt und mind. ein Monat gesetzt
     * - angekuendigt: fällig und angekündigt
     * - erledigt:     nicht fällig
     */
    protected function applyStatusFilter($query, ?string $filterStatus): void
    {
        if ($filterStatus === 'offen') {
            $query->where('faellig', true)->whereNull('angekuendigt_am');
            $this->mitMindestensEinemMonat($query);
        } elseif ($filterStatus === 'angekuendigt') {
            $query->where('faellig', true)->whereNotNull('angekuendigt_am');
        } elseif ($filterStatus === 'erledigt') {
            $query->where('faellig', false);
        }
    }

    protected function monatsNamen(): array
    {
        return [
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
    }
}
