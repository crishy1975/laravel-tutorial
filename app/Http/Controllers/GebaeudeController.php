<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Gebaeude;
use App\Models\Adresse;
use App\Models\Tour;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Throwable;
use App\Models\Timeline;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class GebaeudeController extends Controller
{
    /**
     * Gebäude bearbeiten: lädt Beziehungen + Auswahllisten.
     */
    public function edit(Request $request, $id)
    {
        $gebaeude = Gebaeude::with([
            'postadresse',
            'rechnungsempfaenger',
            // ✅ Beziehung heißt "touren" (Plural) und wird nach Pivot sortiert
            'touren' => fn($q) => $q->orderBy('tourgebaeude.reihenfolge'),
            // 🕒 Optional: Timeline eager laden (spart Queries in der View)
            'timelines' => fn($q) => $q->orderBy('datum', 'desc')->orderBy('id', 'desc'),
        ])->findOrFail($id);

        // Für Auswahlfelder: sinnvoll sortiert
        $adressen = Adresse::orderBy('name')->get(['id', 'name', 'wohnort']);

        // ✨ Codex-Präfix-Vorschläge aus bestehenden Gebäuden
        $codexPrefixTips = \App\Models\Gebaeude::query()
            ->select(['codex', 'strasse', 'wohnort'])
            ->whereNotNull('codex')
            ->where('codex', '!=', '')
            ->get()
            ->map(function ($g) {
                // Präfix = nur Buchstaben vom Anfang (z. B. "gam" aus "gam43")
                if (!preg_match('/^[A-Za-z]+/', (string) $g->codex, $m)) {
                    return null;
                }
                $prefix = strtolower($m[0]); // klein vereinheitlichen
                return [
                    'prefix'  => $prefix,
                    'strasse' => $g->strasse ?: '',
                    'wohnort' => $g->wohnort ?: '',
                ];
            })
            ->filter() // nulls entfernen
            ->groupBy('prefix') // Duplikate je Präfix zusammenfassen
            ->map(function ($items, $prefix) {
                // Einen repräsentativen Datensatz für die Hint-Zeile nehmen
                $one   = $items->first();
                $hint  = trim(($one['strasse'] ?: '') . ($one['wohnort'] ? ', ' . $one['wohnort'] : ''));
                return [
                    'prefix' => $prefix,
                    'hint'   => $hint, // z. B. "Höfeweg, Leifers"
                ];
            })
            ->values()
            ->sortBy('prefix')
            ->take(300); // Sicherheitslimit

        // 🔹 optionaler Rücksprung-Link mit Fallback auf aktuelle Seite
        $returnTo = $request->query('returnTo', url()->current());

        // Touren-Auswahl (nur existierende Spalten selektieren)
        $tourenAlle = Tour::orderBy('name')->get(['id', 'name', 'beschreibung', 'aktiv']);
        $tourenMap  = $tourenAlle->keyBy('id');

        // ✅ korrekte Variablen an View übergeben
        return view('gebaeude.form', compact(
            'gebaeude',
            'adressen',
            'returnTo',
            'tourenAlle',
            'tourenMap',
            'codexPrefixTips'
        ));
    }

    /**
     * Gebäude aktualisieren inkl. Pivot (Touren).
     */
    public function update(Request $request, $id)
    {
        // ➤ Korrelations-ID für Log + UI
        $debugId = (string) Str::uuid();

        try {
            // 0) Datensatz laden
            $gebaeude = Gebaeude::findOrFail($id);

            // 1) Validierung Grunddaten (DE-Messages)
            $validated = $request->validate([
                // --- Basisfelder ---
                'codex'                  => 'nullable|string|max:10',
                'gebaeude_name'          => 'nullable|string|max:100',
                'strasse'                => 'nullable|string|max:255',
                'hausnummer'             => 'nullable|string|max:10',
                'plz'                    => 'nullable|string|max:10',
                'wohnort'                => 'nullable|string|max:100',
                'land'                   => 'nullable|string|max:50',
                'bemerkung'              => 'nullable|string',

                // Pflicht-Referenzen
                'postadresse_id'         => 'required|integer|exists:adressen,id',
                'rechnungsempfaenger_id' => 'required|integer|exists:adressen,id',

                // Monate
                'm01' => 'required|in:0,1',
                'm02' => 'required|in:0,1',
                'm03' => 'required|in:0,1',
                'm04' => 'required|in:0,1',
                'm05' => 'required|in:0,1',
                'm06' => 'required|in:0,1',
                'm07' => 'required|in:0,1',
                'm08' => 'required|in:0,1',
                'm09' => 'required|in:0,1',
                'm10' => 'required|in:0,1',
                'm11' => 'required|in:0,1',
                'm12' => 'required|in:0,1',

                // Zähler
                'geplante_reinigungen'   => 'nullable|integer|min:0',
                'gemachte_reinigungen'   => 'nullable|integer|min:0|lte:geplante_reinigungen',

                // Flags
                'rechnung_schreiben'     => 'required|in:0,1',
                'faellig'                => 'required|in:0,1',
            ], [
                'postadresse_id.required'         => 'Bitte eine Postadresse auswählen.',
                'postadresse_id.exists'           => 'Die ausgewählte Postadresse ist ungültig.',
                'rechnungsempfaenger_id.required' => 'Bitte einen Rechnungsempfänger auswählen.',
                'rechnungsempfaenger_id.exists'   => 'Der ausgewählte Rechnungsempfänger ist ungültig.',
                'gemachte_reinigungen.lte'        => '„Gemachte Reinigungen“ darf nicht größer sein als „Geplante Reinigungen“.',
            ]);

            // 2) Validierung Pivot (Touren)
            $request->validate([
                'tour_ids'      => ['nullable', 'array'],
                'tour_ids.*'    => ['integer', 'exists:tour,id'], // Tabelle: 'tour' (singular)
                'reihenfolge'   => ['nullable', 'array'],
                'reihenfolge.*' => ['nullable', 'integer', 'min:1'],
            ]);

            // 3) Casting / Normalisierung
            $validated['geplante_reinigungen'] = isset($validated['geplante_reinigungen'])
                ? (int)$validated['geplante_reinigungen'] : null;
            $validated['gemachte_reinigungen'] = isset($validated['gemachte_reinigungen'])
                ? (int)$validated['gemachte_reinigungen'] : null;

            foreach ([
                'm01','m02','m03','m04','m05','m06','m07','m08','m09','m10','m11','m12',
                'rechnung_schreiben','faellig'
            ] as $flag) {
                $validated[$flag] = (int)($validated[$flag] ?? 0) === 1 ? 1 : 0;
            }

            // 3a) **Codex-Präfix**: nur führende Buchstaben (z. B. "gam" aus "gam43")
            if ($request->filled('codex')) {
                $raw = (string)$request->input('codex');
                if (preg_match('/^[A-Za-z]+/', $raw, $m)) {
                    $validated['codex'] = strtolower($m[0]); // oder strtoupper(...)
                } else {
                    // kein Buchstabenpräfix → leer/null setzen (oder weglassen, wenn du freie Eingabe willst)
                    $validated['codex'] = null;
                }
            }

            // 4) Pivot-Array bauen: [tour_id => ['reihenfolge' => n], ...]
            $attach = [];
            $ids = array_values($request->input('tour_ids', [])); // Auswahl-Reihenfolge
            $pos = 1;
            foreach ($ids as $tourId) {
                $tourId = (int)$tourId;
                $ord = (int)($request->input("reihenfolge.$tourId") ?? 0);
                if ($ord < 1) { $ord = $pos; } // Fallback 1..N
                $attach[$tourId] = ['reihenfolge' => $ord];
                $pos++;
            }

            // Debug: Startlog
            Log::info('Gebaeude.update START', [
                'debugId'   => $debugId,
                'gebaeude'  => $gebaeude->id,
                'payload'   => $request->all(),
                'attach'    => $attach,
                'user_id'   => optional($request->user())->id,
            ]);

            // 5) Transaktion
            DB::transaction(function () use ($gebaeude, $validated, $attach, $debugId) {
                $gebaeude->update($validated);
                $gebaeude->touren()->sync($attach);

                Log::info('Gebaeude.update COMMIT', [
                    'debugId'  => $debugId,
                    'gebaeude' => $gebaeude->id,
                    'sync_cnt' => count($attach),
                ]);
            });

            // 6) Erfolg (sicherer Redirect)
            $returnTo = $this->safeReturnTo($request->input('returnTo'), route('gebaeude.edit', $gebaeude->id));

            return redirect()
                ->to($returnTo)
                ->with('success', 'Gebäude wurde erfolgreich aktualisiert (inkl. Touren).');

        } catch (ValidationException $ve) {
            // ➤ Validierungsfehler
            Log::warning('Gebaeude.update VALIDATION FAILED', [
                'debugId' => $debugId,
                'errors'  => $ve->errors(),
            ]);
            $first = Arr::first(Arr::flatten($ve->errors()));
            return back()
                ->withErrors($ve->errors())
                ->withInput()
                ->with('error', "Speichern fehlgeschlagen. Bitte Eingaben prüfen. (Fehler-ID: {$debugId})")
                ->with('error_detail', $first);
        } catch (QueryException $qe) {
            // ➤ DB-/SQL-Fehler
            Log::error('Gebaeude.update DB ERROR', [
                'debugId'  => $debugId,
                'code'     => $qe->getCode(),
                'sql'      => $qe->getSql(),
                'bindings' => $qe->getBindings(),
                'message'  => $qe->getMessage(),
            ]);
            return back()
                ->withInput()
                ->with('error', "Speichern fehlgeschlagen (DB-Fehler). Debug-ID: {$debugId}");
        } catch (Throwable $e) {
            // ➤ Unerwarteter Fehler
            Log::error('Gebaeude.update UNEXPECTED ERROR', [
                'debugId' => $debugId,
                'type'    => get_class($e),
                'message' => $e->getMessage(),
                'trace'   => collect($e->getTrace())->take(10),
            ]);
            return back()
                ->withInput()
                ->with('error', "Unerwarteter Fehler beim Speichern. Debug-ID: {$debugId}");
        }
    }

    /**
     * Gebäude-Index mit Filtern und MariaDB-kompatibler Sortierung.
     */
    public function index(Request $request)
    {
        $codex         = trim($request->get('codex', ''));
        $gebaeude_name = trim($request->get('gebaeude_name', ''));
        $strasse       = trim($request->get('strasse', ''));
        $hausnummer    = trim($request->get('hausnummer', ''));
        $wohnort       = trim($request->get('wohnort', ''));

        $q = \App\Models\Gebaeude::query()
            ->when($codex !== '',         fn($q) => $q->where('codex', 'like', "%{$codex}%"))
            ->when($gebaeude_name !== '', fn($q) => $q->where('gebaeude_name', 'like', "%{$gebaeude_name}%"))
            ->when($strasse !== '',       fn($q) => $q->where('strasse', 'like', "%{$strasse}%"))
            ->when($hausnummer !== '',    fn($q) => $q->where('hausnummer', 'like', "%{$hausnummer}%"))
            ->when($wohnort !== '',       fn($q) => $q->where('wohnort', 'like', "%{$wohnort}%"));

        // ✅ MariaDB-robust: erst Zahlenteil (CAST), dann kompletter String
        $q->orderBy('codex')
          ->orderBy('strasse')
          ->orderByRaw('CAST(hausnummer AS UNSIGNED)')
          ->orderBy('hausnummer');

        $gebaeude = $q->paginate(15)->appends($request->query());

        return view('gebaeude.index', compact(
            'gebaeude',
            'codex',
            'gebaeude_name',
            'strasse',
            'hausnummer',
            'wohnort'
        ));
    }

    /**
     * Neues Gebäude vorbereiten.
     */
    public function create()
    {
        $gebaeude = new \App\Models\Gebaeude();
        $adressen = Adresse::orderBy('name')->get(['id', 'name', 'wohnort']);

        // Vorschlagsliste für Codex-Präfix + Hint (Straße, Ort)
        $codexPrefixTips = \App\Models\Gebaeude::query()
            ->select(['codex', 'strasse', 'wohnort'])
            ->whereNotNull('codex')
            ->where('codex', '!=', '')
            ->get()
            ->map(function ($g) {
                // Präfix = nur Buchstaben vom Anfang (z. B. "gam" aus "gam43")
                if (!preg_match('/^[A-Za-z]+/', (string) $g->codex, $m)) {
                    return null;
                }
                $prefix = strtolower($m[0]);
                return [
                    'prefix'  => $prefix,
                    'strasse' => $g->strasse ?: '',
                    'wohnort' => $g->wohnort ?: '',
                ];
            })
            ->filter()
            ->groupBy('prefix')
            ->map(function ($items, $prefix) {
                $one  = $items->first();
                $hint = trim(($one['strasse'] ?: '') . ($one['wohnort'] ? ', ' . $one['wohnort'] : ''));
                return ['prefix' => $prefix, 'hint' => $hint];
            })
            ->values()
            ->sortBy('prefix')
            ->take(300);

        return view('gebaeude.form', compact('gebaeude', 'adressen', 'codexPrefixTips'));
    }

    /**
     * Gebäude speichern.
     */
    public function store(Request $request)
    {
        $debugId = (string) Str::uuid();

        try {
            // 1) Validierung (mit deutschen Messages)
            $validated = $request->validate([
                // --- Basisfelder ---
                'codex'                  => 'nullable|string|max:10',
                'gebaeude_name'          => 'nullable|string|max:100',
                'strasse'                => 'nullable|string|max:255',
                'hausnummer'             => 'nullable|string|max:10',
                'plz'                    => 'nullable|string|max:10',
                'wohnort'                => 'nullable|string|max:100',
                'land'                   => 'nullable|string|max:50',
                'bemerkung'              => 'nullable|string',

                // Pflicht-Referenzen
                'postadresse_id'         => 'required|integer|exists:adressen,id',
                'rechnungsempfaenger_id' => 'required|integer|exists:adressen,id',

                // Monate
                'm01' => 'required|in:0,1',
                'm02' => 'required|in:0,1',
                'm03' => 'required|in:0,1',
                'm04' => 'required|in:0,1',
                'm05' => 'required|in:0,1',
                'm06' => 'required|in:0,1',
                'm07' => 'required|in:0,1',
                'm08' => 'required|in:0,1',
                'm09' => 'required|in:0,1',
                'm10' => 'required|in:0,1',
                'm11' => 'required|in:0,1',
                'm12' => 'required|in:0,1',

                // Zähler
                'geplante_reinigungen'   => 'nullable|integer|min:0',
                'gemachte_reinigungen'   => 'nullable|integer|min:0|lte:geplante_reinigungen',

                // Flags
                'rechnung_schreiben'     => 'required|in:0,1',
                'faellig'                => 'required|in:0,1',
            ], [
                'postadresse_id.required'         => 'Bitte eine Postadresse auswählen.',
                'postadresse_id.exists'           => 'Die ausgewählte Postadresse ist ungültig.',
                'rechnungsempfaenger_id.required' => 'Bitte einen Rechnungsempfänger auswählen.',
                'rechnungsempfaenger_id.exists'   => 'Der ausgewählte Rechnungsempfänger ist ungültig.',
                'gemachte_reinigungen.lte'        => '„Gemachte Reinigungen“ darf nicht größer sein als „Geplante Reinigungen“.',
            ]);

            // 2) Casting / Normalisierung
            $validated['geplante_reinigungen'] = isset($validated['geplante_reinigungen']) ? (int)$validated['geplante_reinigungen'] : null;
            $validated['gemachte_reinigungen'] = isset($validated['gemachte_reinigungen']) ? (int)$validated['gemachte_reinigungen'] : null;

            foreach (['m01','m02','m03','m04','m05','m06','m07','m08','m09','m10','m11','m12','rechnung_schreiben','faellig'] as $flag) {
                $validated[$flag] = (int)($validated[$flag] ?? 0) === 1 ? 1 : 0;
            }

            // 3) Anlegen
            $gebaeude = Gebaeude::create($validated);

            // 4) Erfolg
            return redirect()
                ->route('gebaeude.edit', $gebaeude->id)
                ->with('success', 'Gebäude erfolgreich angelegt.');
        } catch (ValidationException $ve) {
            Log::warning('Gebaeude.store VALIDATION FAILED', [
                'debugId' => $debugId,
                'errors'  => $ve->errors(),
            ]);

            $first = Arr::first(Arr::flatten($ve->errors()));

            return back()
                ->withErrors($ve->errors())
                ->withInput()
                ->with('error', "Anlegen fehlgeschlagen. Bitte Eingaben prüfen. (Fehler-ID: {$debugId})")
                ->with('error_detail', $first);
        } catch (QueryException $qe) {
            Log::error('Gebaeude.store DB ERROR', [
                'debugId'  => $debugId,
                'code'     => $qe->getCode(),
                'sql'      => $qe->getSql(),
                'bindings' => $qe->getBindings(),
                'message'  => $qe->getMessage(),
            ]);

            return back()
                ->withInput()
                ->with('error', "Anlegen fehlgeschlagen (DB-Fehler). Debug-ID: {$debugId}");
        } catch (Throwable $e) {
            Log::error('Gebaeude.store UNEXPECTED ERROR', [
                'debugId' => $debugId,
                'type'    => get_class($e),
                'message' => $e->getMessage(),
                'trace'   => collect($e->getTrace())->take(10),
            ]);

            return back()
                ->withInput()
                ->with('error', "Unerwarteter Fehler beim Anlegen. Debug-ID: {$debugId}");
        }
    }

    /**
     * Gebäude löschen.
     */
    public function destroy($id)
    {
        $gebaeude = Gebaeude::findOrFail($id);

        try {
            $gebaeude->delete();
            return redirect()
                ->route('gebaeude.index')
                ->with('success', 'Gebaeude wurde gelöscht.');
        } catch (QueryException $e) {
            // z. B. wegen Fremdschlüssel-Verknüpfungen
            return back()->with('error', 'Gebaeude kann nicht gelöscht werden (verknüpfte Daten vorhanden).');
        }
    }

    /**
     * Mehrere Gebäude einer Tour zuordnen (Bulk).
     */
    public function bulkAttachTour(Request $request)
    {
        // Safety: ignorier Method-Spoofing für diese Route
        if ($request->input('_method')) {
            $request->request->remove('_method');
        }

        $data = $request->validate([
            'ids'               => ['required', 'array', 'min:1'],
            'ids.*'             => ['integer', 'exists:gebaeude,id'],
            'tour_id'           => ['required', 'integer', 'exists:tour,id'],
            'pivot_reihenfolge' => ['nullable', 'integer', 'min:1'],
            'returnTo'          => ['nullable', 'string'],
        ]);

        $ids   = $data['ids'];
        $tour  = \App\Models\Tour::findOrFail($data['tour_id']);
        $order = $data['pivot_reihenfolge'] ?? null;

        DB::transaction(function () use ($ids, $tour, $order) {
            foreach ($ids as $gid) {
                $reihenfolge = $order ?: ((int) DB::table('tourgebaeude')
                    ->where('tour_id', $tour->id)
                    ->max('reihenfolge') + 1);

                $gebaeude = \App\Models\Gebaeude::findOrFail($gid);
                $gebaeude->touren()->syncWithoutDetaching([
                    $tour->id => ['reihenfolge' => $reihenfolge],
                ]);
            }
        });

        $to = $this->safeReturnTo($data['returnTo'] ?? null, route('gebaeude.index'));

        return redirect()->to($to)
            ->with('success', 'Ausgewählte Gebäude wurden mit der Tour verknüpft.');
    }

    /**
     * Timeline-Eintrag speichern + Gebäude-Status aktualisieren.
     */
    public function timelineStore(Request $request, int $id)
    {
        $debugId = (string) Str::uuid();

        // Gebäude muss existieren
        $gebaeude = \App\Models\Gebaeude::findOrFail($id);

        // ✅ Validierung: bemerkung optional!
        try {
            $data = $request->validate([
                'datum'     => ['nullable', 'date'],   // darf leer sein → wird dann heute gesetzt
                'bemerkung' => ['nullable', 'string'],// optional
            ]);
        } catch (ValidationException $ve) {
            Log::warning('timelineStore VALIDATION FAILED', [
                'debugId'  => $debugId,
                'gebaeude' => $gebaeude->id,
                'errors'   => $ve->errors(),
                'payload'  => $request->all(),
            ]);
            throw $ve;
        }

        $user   = $request->user(); // eingeloggte Person (kann null sein)
        $datum  = $data['datum'] ?? now()->toDateString(); // Model castet 'date' → ok
        $note   = $data['bemerkung'] ?? null;

        Log::info('timelineStore START', [
            'debugId'     => $debugId,
            'gebaeude_id' => $gebaeude->id,
            'user_id'     => $user?->id,
            'datum'       => $datum,
            'bemerkung'   => $note,
        ]);

        try {
            DB::beginTransaction();

            // 1) Timeline-Eintrag anlegen (Eloquent pflegt created_at/updated_at)
            $timeline = Timeline::create([
                'gebaeude_id' => $gebaeude->id,
                'datum'       => $datum,
                'bemerkung'   => $note,
                'person_name' => $user?->name ?? 'Unbekannt',
                'person_id'   => $user?->id ?? 0,
            ]);

            // 2) Gebäude-Status aktualisieren:
            //    - „anzReinigung“: gemachte_reinigungen +1
            //    - „isRechnungSchreiben“: rechnung_schreiben = 1
            //    - optional: letzter_termin = datum (falls Spalte existiert)
            $updates = [
                'rechnung_schreiben'   => 1,
                'gemachte_reinigungen' => DB::raw('COALESCE(gemachte_reinigungen,0) + 1'),
            ];

            try {
                if (Schema::hasColumn('gebaeude', 'letzter_termin')) {
                    $updates['letzter_termin'] = $datum;
                }
            } catch (\Throwable $e) {
                // Schema-Check optional ignorieren
            }

            \App\Models\Gebaeude::whereKey($gebaeude->id)->update($updates);

            DB::commit();

            Log::info('timelineStore COMMIT', [
                'debugId'     => $debugId,
                'timeline_id' => $timeline->id,
                'gebaeude_id' => $gebaeude->id,
            ]);

            return back()->with('success', "Timeline-Eintrag hinzugefügt und Status aktualisiert. (Debug-ID: {$debugId})");
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('timelineStore ERROR', [
                'debugId'     => $debugId,
                'type'        => get_class($e),
                'message'     => $e->getMessage(),
                'trace_top'   => collect($e->getTrace())->take(5),
                'payload'     => $request->all(),
                'gebaeude_id' => $gebaeude->id,
            ]);

            return back()
                ->withInput()
                ->with('error', "Timeline konnte nicht gespeichert werden. (Debug-ID: {$debugId})");
        }
    }

    /**
     * Timeline-Eintrag löschen (SoftDelete im Timeline-Model aktiv).
     */
    public function timelineDestroy(Request $request, int $id, int $timeline)
    {
        // Sicherheit: nur Timeline dieses Gebäudes löschbar
        $gebaeude = \App\Models\Gebaeude::findOrFail($id);
        $entry = Timeline::where('id', $timeline)
            ->where('gebaeude_id', $gebaeude->id)
            ->firstOrFail();

        $entry->delete(); // SoftDelete

        return back()->with('success', 'Timeline-Eintrag gelöscht.');
    }

    /**
     * Sicherer Redirect: erlaubt nur relative Pfade oder gleiche Origin.
     */
    protected function safeReturnTo(?string $url, string $fallback): string
    {
        if (!$url) return $fallback;

        // Relative Pfade sind ok
        if (str_starts_with($url, '/')) return $url;

        // Voll-URL nur akzeptieren, wenn Host identisch mit APP_URL ist
        try {
            $appUrl = parse_url(config('app.url'));
            $retUrl = parse_url($url);
            if ($retUrl && $appUrl && (($retUrl['host'] ?? null) === ($appUrl['host'] ?? null))) {
                return $url;
            }
        } catch (\Throwable $e) {
            // still
        }

        return $fallback;
    }
}
