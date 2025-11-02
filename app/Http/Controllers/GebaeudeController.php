<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Gebaeude;
use App\Models\Adresse;
use App\Models\Tour;
use App\Models\Timeline;
use App\Models\FatturaProfile; // ✅ NEU: FatturaProfile importieren
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Schema;
use Throwable;

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
            // ✅ Beziehung "touren" (Plural) nach Pivot sortiert
            'touren' => fn($q) => $q->orderBy('tourgebaeude.reihenfolge'),
            // 🕒 Optional: Timeline eager laden (spart Queries in der View)
            'timelines' => fn($q) => $q->orderBy('datum', 'desc')->orderBy('id', 'desc'),
        ])->findOrFail($id);

        // 📇 Adress-Auswahl
        $adressen = Adresse::orderBy('name')->get(['id', 'name', 'wohnort']);

        // 🧾 Fattura-Profile (robust: nur laden, wenn Tabelle existiert)
        $fatturaProfiles = collect();
        try {
            if (Schema::hasTable('fattura_profile')) {
                // ⚠️ Tabelle heißt 'fattura_profile', Sortierung nach 'bezeichnung'
                $fatturaProfiles = FatturaProfile::orderBy('bezeichnung')
                    ->get(['id', 'bezeichnung', 'mwst_satz', 'split_payment', 'ritenuta']);
            }
        } catch (Throwable $e) {
            $fatturaProfiles = collect();
        }

        // ✨ Codex-Präfix-Vorschläge
        $codexPrefixTips = Gebaeude::query()
            ->select(['codex', 'strasse', 'wohnort'])
            ->whereNotNull('codex')
            ->where('codex', '!=', '')
            ->get()
            ->map(function ($g) {
                // Präfix = nur führende Buchstaben (z. B. "gam" aus "gam43")
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
            ->take(300); // Sicherheitslimit

        // 🔙 optionaler Rücksprung-Link
        $returnTo = $request->query('returnTo', url()->current());

        // 🗺️ Touren-Auswahl
        $tourenAlle = Tour::orderBy('name')->get(['id', 'name', 'beschreibung', 'aktiv']);
        $tourenMap  = $tourenAlle->keyBy('id');

        // ➜ View
        return view('gebaeude.form', compact(
            'gebaeude',
            'adressen',
            'returnTo',
            'tourenAlle',
            'tourenMap',
            'codexPrefixTips',
            'fatturaProfiles'
        ));
    }

    /**
     * Gebäude aktualisieren inkl. Pivot (Touren) und FatturaPA-Feldern.
     */
    public function update(Request $request, $id)
    {
        $debugId = (string) Str::uuid();

        try {
            // Datensatz laden
            $gebaeude = Gebaeude::findOrFail($id);

            // 1) Validierung Grunddaten + FatturaPA-Defaults
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

                // Zähler (❗ KEINE lte-Regel mehr)
                'geplante_reinigungen'   => 'nullable|integer|min:0',
                'gemachte_reinigungen'   => 'nullable|integer|min:0',


                // Flags
                'rechnung_schreiben'     => 'required|in:0,1',
                'faellig'                => 'required|in:0,1',

                // --- FatturaPA/Defaults (NEU) ---
                'bemerkung_buchhaltung'      => 'nullable|string',
                'cup'                         => 'nullable|string|max:20',
                'cig'                         => 'nullable|string|max:10',
                'auftrag_id'                  => 'nullable|string|max:50',
                'auftrag_datum'               => 'nullable|date',
                // ✅ richtige Tabelle & Spalte:
                'fattura_profile_id'          => 'nullable|integer|exists:fattura_profile,id',
                'bank_match_text_template'    => 'nullable|string',
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
                'tour_ids.*'    => ['integer', 'exists:tour,id'],
                'reihenfolge'   => ['nullable', 'array'],
                'reihenfolge.*' => ['nullable', 'integer', 'min:1'],
            ]);

            // 3) Casting / Normalisierung
            $validated['geplante_reinigungen'] = isset($validated['geplante_reinigungen'])
                ? (int)$validated['geplante_reinigungen'] : null;
            $validated['gemachte_reinigungen'] = isset($validated['gemachte_reinigungen'])
                ? (int)$validated['gemachte_reinigungen'] : null;

            foreach (
                [
                    'm01',
                    'm02',
                    'm03',
                    'm04',
                    'm05',
                    'm06',
                    'm07',
                    'm08',
                    'm09',
                    'm10',
                    'm11',
                    'm12',
                    'rechnung_schreiben',
                    'faellig'
                ] as $flag
            ) {
                $validated[$flag] = (int)($validated[$flag] ?? 0) === 1 ? 1 : 0;
            }

            // 3a) Codex-Präfix (nur führende Buchstaben, klein)
            if ($request->filled('codex')) {
                $raw = (string)$request->input('codex');
                if (preg_match('/^[A-Za-z]+/', $raw, $m)) {
                    $validated['codex'] = strtolower($m[0]);
                } else {
                    $validated['codex'] = null;
                }
            }

            // 4) Pivot-Array: [tour_id => ['reihenfolge' => n], ...]
            $attach = [];
            $ids = array_values($request->input('tour_ids', [])); // Auswahl-Reihenfolge
            $pos = 1;
            foreach ($ids as $tourId) {
                $tourId = (int)$tourId;
                $ord = (int)($request->input("reihenfolge.$tourId") ?? 0);
                if ($ord < 1) {
                    $ord = $pos;
                }
                $attach[$tourId] = ['reihenfolge' => $ord];
                $pos++;
            }

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

            // 6) Erfolg
            $returnTo = $this->safeReturnTo($request->input('returnTo'), route('gebaeude.edit', $gebaeude->id));

            return redirect()
                ->to($returnTo)
                ->with('success', 'Gebäude wurde erfolgreich aktualisiert (inkl. Touren).');
        } catch (ValidationException $ve) {
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

        $q = Gebaeude::query()
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
        $gebaeude = new Gebaeude();
        $adressen = Adresse::orderBy('name')->get(['id', 'name', 'wohnort']);

        // 🧾 Fattura-Profile (robust)
        $fatturaProfiles = collect();
        try {
            if (Schema::hasTable('fattura_profile')) {
                $fatturaProfiles = FatturaProfile::orderBy('bezeichnung')
                    ->get(['id', 'bezeichnung', 'mwst_satz', 'split_payment', 'ritenuta']);
            }
        } catch (Throwable $e) {
            $fatturaProfiles = collect();
        }

        // ✨ Codex-Präfix-Tipps
        $codexPrefixTips = Gebaeude::query()
            ->select(['codex', 'strasse', 'wohnort'])
            ->whereNotNull('codex')
            ->where('codex', '!=', '')
            ->get()
            ->map(function ($g) {
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

        return view('gebaeude.form', compact('gebaeude', 'adressen', 'codexPrefixTips', 'fatturaProfiles'));
    }

    /**
     * Gebäude speichern.
     */
    public function store(Request $request)
    {
        $debugId = (string) Str::uuid();

        try {
            // 1) Validierung (inkl. FatturaPA-Defaults)
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

                // --- FatturaPA/Defaults (NEU) ---
                'bemerkung_buchhaltung'      => 'nullable|string',
                'cup'                         => 'nullable|string|max:20',
                'cig'                         => 'nullable|string|max:10',
                'auftrag_id'                  => 'nullable|string|max:50',
                'auftrag_datum'               => 'nullable|date',
                // ✅ richtige Tabelle & Spalte:
                'fattura_profile_id'          => 'nullable|integer|exists:fattura_profile,id',
                'bank_match_text_template'    => 'nullable|string',
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

            foreach (['m01', 'm02', 'm03', 'm04', 'm05', 'm06', 'm07', 'm08', 'm09', 'm10', 'm11', 'm12', 'rechnung_schreiben', 'faellig'] as $flag) {
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
        // Safety: Method-Spoofing für diese Route ignorieren
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
        $tour  = Tour::findOrFail($data['tour_id']);
        $order = $data['pivot_reihenfolge'] ?? null;

        DB::transaction(function () use ($ids, $tour, $order) {
            foreach ($ids as $gid) {
                $reihenfolge = $order ?: ((int) DB::table('tourgebaeude')
                    ->where('tour_id', $tour->id)
                    ->max('reihenfolge') + 1);

                $gebaeude = Gebaeude::findOrFail($gid);
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
        $gebaeude = Gebaeude::findOrFail($id);

        // bemerkung optional!
        try {
            $data = $request->validate([
                'datum'     => ['nullable', 'date'],
                'bemerkung' => ['nullable', 'string'],
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

        $user   = $request->user();
        $datum  = $data['datum'] ?? now()->toDateString();
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

            // 1) Timeline-Eintrag anlegen
            $timeline = Timeline::create([
                'gebaeude_id' => $gebaeude->id,
                'datum'       => $datum,
                'bemerkung'   => $note,
                'person_name' => $user?->name ?? 'Unbekannt',
                'person_id'   => $user?->id ?? 0,
            ]);

            // 2) Gebäude-Status aktualisieren
            $updates = [
                'rechnung_schreiben'   => 1,
                'gemachte_reinigungen' => DB::raw('COALESCE(gemachte_reinigungen,0) + 1'),
            ];

            try {
                if (Schema::hasColumn('gebaeude', 'letzter_termin')) {
                    $updates['letzter_termin'] = $datum;
                }
            } catch (Throwable $e) {
                // ignore Schema-Check-Fehler
            }

            Gebaeude::whereKey($gebaeude->id)->update($updates);

            DB::commit();

            Log::info('timelineStore COMMIT', [
                'debugId'     => $debugId,
                'timeline_id' => $timeline->id,
                'gebaeude_id' => $gebaeude->id,
            ]);

            return back()->with('success', "Timeline-Eintrag hinzugefügt und Status aktualisiert. (Debug-ID: {$debugId})");
        } catch (Throwable $e) {
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
        $gebaeude = Gebaeude::findOrFail($id);
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
        } catch (Throwable $e) {
            // ignore
        }

        return $fallback;
    }

    /**
     * Setzt für ALLE Gebäude 'gemachte_reinigungen' auf 0.
     * Wird über einen separaten Button/POST-Formular ausgelöst.
     */
    public function resetGemachteReinigungen(Request $request)
    {
        // Optional: einfache Schutzabfrage (CSRF ist ohnehin aktiv).
        // Du kannst hier auch Rollen/Gates verwenden (z.B. Gate::authorize('admin')).
        if (!$request->user()) {
            abort(403);
        }

        // Mini-Bestätigung: falls du einen Hidden-Input "confirm" mitsendest.
        // (Kannst du auch weglassen; der JS-confirm im Button reicht.)
        if ($request->filled('confirm') && $request->input('confirm') !== 'YES') {
            return back()->with('error', 'Aktion nicht bestätigt.');
        }

        // Für Feedback/Logging ein paar Kennzahlen erfassen
        $countTotal = DB::table('gebaeude')->count();
        $sumBefore  = (int) DB::table('gebaeude')->sum('gemachte_reinigungen');

        // Update aller Datensätze auf 0
        $affected = DB::table('gebaeude')->update(['gemachte_reinigungen' => 0]);

        $sumAfter = (int) DB::table('gebaeude')->sum('gemachte_reinigungen');

        Log::info('Reset gemachte_reinigungen per Button', [
            'user_id'   => $request->user()->id,
            'countTotal' => $countTotal,
            'affected'  => $affected,
            'sumBefore' => $sumBefore,
            'sumAfter'  => $sumAfter,
        ]);

        return back()->with(
            'success',
            "Zurückgesetzt: {$affected} Datensätze. Summe vorher: {$sumBefore}, nachher: {$sumAfter}."
        );
    }
}
