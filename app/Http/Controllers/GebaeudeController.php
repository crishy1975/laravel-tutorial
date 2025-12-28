<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Gebaeude;
use App\Models\Adresse;
use App\Models\Tour;
use App\Models\Timeline;
use App\Models\FatturaProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Schema;
use App\Services\FaelligkeitsService;
use Illuminate\Http\JsonResponse;
use Throwable;
use Illuminate\Support\Carbon;

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
            // Touren (Plural) nach Pivot sortiert
            'touren' => fn($q) => $q->orderBy('tourgebaeude.reihenfolge'),
            // Timeline eager laden
            'timelines' => fn($q) => $q->orderBy('datum', 'desc')->orderBy('id', 'desc'),
            // ⭐ Rechnungen laden (für Tab-Badge)
            'rechnungen',
            // ⭐ Logs laden (für Protokoll-Tab)
            'logs' => fn($q) => $q->orderByDesc('created_at')->limit(50),
            // ⭐ NEU: Dokumente laden (für Dokumente-Tab)
            'dokumente' => fn($q) => $q->where('ist_archiviert', false)
                ->orderByDesc('ist_wichtig')
                ->orderByDesc('created_at'),
        ])->findOrFail($id);

        // Adress-Auswahl
        $adressen = Adresse::orderBy('name')->get(['id', 'name', 'wohnort']);

        // Fattura-Profile (robust: nur laden, wenn Tabelle existiert)
        $fatturaProfiles = collect();
        try {
            if (Schema::hasTable('fattura_profile')) {
                $fatturaProfiles = FatturaProfile::orderBy('bezeichnung')
                    ->get(['id', 'bezeichnung', 'mwst_satz', 'split_payment', 'ritenuta']);
            }
        } catch (Throwable $e) {
            $fatturaProfiles = collect();
        }

        // Codex-Präfix-Vorschläge
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

        // Optionaler Rücksprung-Link
        $returnTo = $request->query('returnTo', url()->current());

        // Touren-Auswahl
        $tourenAlle = Tour::orderBy('name')->get(['id', 'name', 'beschreibung', 'aktiv']);
        $tourenMap  = $tourenAlle->keyBy('id');

        // View
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
            $gebaeude = Gebaeude::findOrFail($id);

            $validated = $request->validate([
                'codex'                  => 'nullable|string|max:10',
                'gebaeude_name'          => 'nullable|string|max:100',
                'strasse'                => 'nullable|string|max:255',
                'hausnummer'             => 'nullable|string|max:10',
                'plz'                    => 'nullable|string|max:10',
                'wohnort'                => 'nullable|string|max:100',
                'land'                   => 'nullable|string|max:50',
                'bemerkung'              => 'nullable|string',

                // ⭐ GEÄNDERT: Adressen jetzt optional
                'postadresse_id'         => 'nullable|integer|exists:adressen,id',
                'rechnungsempfaenger_id' => 'nullable|integer|exists:adressen,id',

                // ⭐ NEU: Kontaktfelder
                'telefon'                => 'nullable|string|max:50',
                'handy'                  => 'nullable|string|max:50',
                'email'                  => 'nullable|email|max:255',

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

                'geplante_reinigungen'   => 'nullable|integer|min:0',
                'gemachte_reinigungen'   => 'nullable|integer|min:0',

                'rechnung_schreiben'     => 'required|in:0,1',
                'faellig'                => 'required|in:0,1',

                'bemerkung_buchhaltung'      => 'nullable|string',
                'cup'                         => 'nullable|string|max:20',
                'cig'                         => 'nullable|string|max:10',
                'codice_commessa'            => 'nullable|string|max:100',
                'auftrag_id'                  => 'nullable|string|max:50',
                'auftrag_datum'               => 'nullable|date',
                'fattura_profile_id'          => 'nullable|integer|exists:fattura_profile,id',
                'bank_match_text_template'    => 'nullable|string',
            ], [
                'postadresse_id.exists'           => 'Die ausgewählte Postadresse ist ungültig.',
                'rechnungsempfaenger_id.exists'   => 'Der ausgewählte Rechnungsempfänger ist ungültig.',
                'gemachte_reinigungen.lte'        => '„Gemachte Reinigungen" darf nicht größer sein als „Geplante Reinigungen".',
                'email.email'                     => 'Bitte eine gültige E-Mail-Adresse eingeben.',
            ]);

            $request->validate([
                'tour_ids'      => ['nullable', 'array'],
                'tour_ids.*'    => ['integer', 'exists:tour,id'],
                'reihenfolge'   => ['nullable', 'array'],
                'reihenfolge.*' => ['nullable', 'integer', 'min:1'],
            ]);

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

            // ⭐ Codex: Komplett speichern (nur lowercase)
            if ($request->filled('codex')) {
                $validated['codex'] = strtolower(trim($request->input('codex')));
            }

            $attach = [];
            $ids = array_values($request->input('tour_ids', []));
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

            DB::transaction(function () use ($gebaeude, $validated, $attach, $debugId) {
                $gebaeude->update($validated);
                $gebaeude->touren()->sync($attach);

                Log::info('Gebaeude.update COMMIT', [
                    'debugId'  => $debugId,
                    'gebaeude' => $gebaeude->id,
                    'sync_cnt' => count($attach),
                ]);
            });

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
     * ⭐ Gebäude-Index mit erweiterten Filtern
     * 
     * NEU: Filter für Tour und Rechnung hinzugefügt
     * NEU: Statistiken für Dashboard
     * NEU: Mobile-optimierte Ausgabe
     */
    public function index(Request $request)
    {
        // ⭐ NEU: Filter aus Session laden falls keine Query-Parameter
        $sessionKey = 'gebaeude_filter';

        // Wenn clear_filter gesetzt → Session löschen und redirect
        if ($request->has('clear_filter')) {
            $request->session()->forget($sessionKey);
            return redirect()->route('gebaeude.index');
        }

        // Prüfen ob Query-Parameter vorhanden sind
        $hasQueryParams = $request->hasAny(['codex', 'gebaeude_name', 'strasse', 'hausnummer', 'wohnort', 'tour', 'rechnung']);

        // Filter aus Request oder Session
        if ($hasQueryParams) {
            // Query-Parameter → in Session speichern
            $filters = [
                'codex'         => trim($request->get('codex', '')),
                'gebaeude_name' => trim($request->get('gebaeude_name', '')),
                'strasse'       => trim($request->get('strasse', '')),
                'hausnummer'    => trim($request->get('hausnummer', '')),
                'wohnort'       => trim($request->get('wohnort', '')),
                'tour'          => $request->get('tour', ''),
                'rechnung'      => $request->get('rechnung', ''),
            ];
            $request->session()->put($sessionKey, $filters);
        } else {
            // Keine Query-Parameter → aus Session laden (falls vorhanden)
            $filters = $request->session()->get($sessionKey, [
                'codex'         => '',
                'gebaeude_name' => '',
                'strasse'       => '',
                'hausnummer'    => '',
                'wohnort'       => '',
                'tour'          => '',
                'rechnung'      => '',
            ]);
        }

        // Filter-Werte extrahieren
        $codex          = $filters['codex'] ?? '';
        $gebaeude_name  = $filters['gebaeude_name'] ?? '';
        $strasse        = $filters['strasse'] ?? '';
        $hausnummer     = $filters['hausnummer'] ?? '';
        $wohnort        = $filters['wohnort'] ?? '';
        $filterTour     = $filters['tour'] ?? '';
        $filterRechnung = $filters['rechnung'] ?? '';

        // Query aufbauen mit Touren eager loading
        $query = Gebaeude::query()->with('touren');

        // Standard-Filter
        if ($codex !== '') {
            $query->where('codex', 'like', "%{$codex}%");
        }
        if ($gebaeude_name !== '') {
            $query->where('gebaeude_name', 'like', "%{$gebaeude_name}%");
        }
        if ($strasse !== '') {
            $query->where('strasse', 'like', "%{$strasse}%");
        }
        if ($hausnummer !== '') {
            $query->where('hausnummer', 'like', "%{$hausnummer}%");
        }
        if ($wohnort !== '') {
            $query->where('wohnort', 'like', "%{$wohnort}%");
        }

        // Filter Tour
        if ($filterTour === 'ohne') {
            $query->whereDoesntHave('touren');
        } elseif ($filterTour === 'mit') {
            $query->whereHas('touren');
        } elseif ($filterTour !== '' && is_numeric($filterTour)) {
            $query->whereHas('touren', function ($q) use ($filterTour) {
                $q->where('tour.id', $filterTour);
            });
        }

        // Filter Rechnung
        if ($filterRechnung === '1') {
            $query->where('rechnung_schreiben', true);
        } elseif ($filterRechnung === '0') {
            $query->where(function ($q) {
                $q->where('rechnung_schreiben', false)
                    ->orWhereNull('rechnung_schreiben');
            });
        }

        // Sortierung: Straße, dann Hausnummer (numerisch)
        $query->orderBy('strasse')
            ->orderByRaw('CAST(hausnummer AS UNSIGNED)')
            ->orderBy('hausnummer');

        // Pagination - Filter an URL anhängen
        $gebaeude = $query->paginate(25)->appends($filters);

        // Statistiken berechnen
        $stats = [
            'gesamt'         => Gebaeude::count(),
            'rechnung_offen' => Gebaeude::where('rechnung_schreiben', true)->count(),
            'mit_tour'       => Gebaeude::whereHas('touren')->count(),
            'ohne_tour'      => Gebaeude::whereDoesntHave('touren')->count(),
        ];

        // Touren für Dropdown laden
        $touren = Tour::orderBy('aktiv', 'desc')
            ->orderBy('name')
            ->get(['id', 'name', 'aktiv']);

        return view('gebaeude.index', compact(
            'gebaeude',
            'codex',
            'gebaeude_name',
            'strasse',
            'hausnummer',
            'wohnort',
            'filterTour',
            'filterRechnung',
            'stats',
            'touren'
        ));
    }

    /**
     * Neues Gebäude vorbereiten.
     */
    public function create()
    {
        $gebaeude = new Gebaeude();
        $adressen = Adresse::orderBy('name')->get(['id', 'name', 'wohnort']);

        $fatturaProfiles = collect();
        try {
            if (Schema::hasTable('fattura_profile')) {
                $fatturaProfiles = FatturaProfile::orderBy('bezeichnung')
                    ->get(['id', 'bezeichnung', 'mwst_satz', 'split_payment', 'ritenuta']);
            }
        } catch (Throwable $e) {
            $fatturaProfiles = collect();
        }

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

        // ⭐ NEU: tourenAlle und tourenMap für neue Gebäude
        $tourenAlle = Tour::orderBy('name')->get(['id', 'name', 'beschreibung', 'aktiv']);
        $tourenMap  = $tourenAlle->keyBy('id');

        return view('gebaeude.form', compact(
            'gebaeude',
            'adressen',
            'codexPrefixTips',
            'fatturaProfiles',
            'tourenAlle',
            'tourenMap'
        ));
    }

    /**
     * Gebäude speichern.
     */
    public function store(Request $request)
    {
        $debugId = (string) Str::uuid();

        try {
            $validated = $request->validate([
                'codex'                  => 'nullable|string|max:10',
                'gebaeude_name'          => 'nullable|string|max:100',
                'strasse'                => 'nullable|string|max:255',
                'hausnummer'             => 'nullable|string|max:10',
                'plz'                    => 'nullable|string|max:10',
                'wohnort'                => 'nullable|string|max:100',
                'land'                   => 'nullable|string|max:50',
                'bemerkung'              => 'nullable|string',

                // ⭐ GEÄNDERT: Adressen jetzt optional
                'postadresse_id'         => 'nullable|integer|exists:adressen,id',
                'rechnungsempfaenger_id' => 'nullable|integer|exists:adressen,id',

                // ⭐ NEU: Kontaktfelder
                'telefon'                => 'nullable|string|max:50',
                'handy'                  => 'nullable|string|max:50',
                'email'                  => 'nullable|email|max:255',

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

                'geplante_reinigungen'   => 'nullable|integer|min:0',
                'gemachte_reinigungen'   => 'nullable|integer|min:0|lte:geplante_reinigungen',

                'rechnung_schreiben'     => 'required|in:0,1',
                'faellig'                => 'required|in:0,1',

                'bemerkung_buchhaltung'      => 'nullable|string',
                'cup'                         => 'nullable|string|max:20',
                'cig'                         => 'nullable|string|max:10',
                'codice_commessa'            => 'nullable|string|max:100',
                'auftrag_id'                  => 'nullable|string|max:50',
                'auftrag_datum'               => 'nullable|date',
                'fattura_profile_id'          => 'nullable|integer|exists:fattura_profile,id',
                'bank_match_text_template'    => 'nullable|string',
            ], [
                'postadresse_id.exists'           => 'Die ausgewählte Postadresse ist ungültig.',
                'rechnungsempfaenger_id.exists'   => 'Der ausgewählte Rechnungsempfänger ist ungültig.',
                'gemachte_reinigungen.lte'        => '„Gemachte Reinigungen" darf nicht größer sein als „Geplante Reinigungen".',
                'email.email'                     => 'Bitte eine gültige E-Mail-Adresse eingeben.',
            ]);

            $validated['geplante_reinigungen'] = isset($validated['geplante_reinigungen']) ? (int)$validated['geplante_reinigungen'] : null;
            $validated['gemachte_reinigungen'] = isset($validated['gemachte_reinigungen']) ? (int)$validated['gemachte_reinigungen'] : null;

            foreach (['m01', 'm02', 'm03', 'm04', 'm05', 'm06', 'm07', 'm08', 'm09', 'm10', 'm11', 'm12', 'rechnung_schreiben', 'faellig'] as $flag) {
                $validated[$flag] = (int)($validated[$flag] ?? 0) === 1 ? 1 : 0;
            }

            $gebaeude = Gebaeude::create($validated);

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
            return back()->with('error', 'Gebaeude kann nicht gelöscht werden (verknüpfte Daten vorhanden).');
        }
    }

    /**
     * Mehrere Gebäude einer Tour zuordnen (Bulk).
     */
    public function bulkAttachTour(Request $request)
    {
        if ($request->input('_method')) {
            $request->request->remove('_method');
        }

        $data = $request->validate([
            'ids'               => ['required', 'array', 'min:1'],
            'ids.*'             => ['integer', 'exists:gebaeude,id'],
            'tour_id'           => ['required', 'integer', 'exists:tour,id'],
            'replace_existing'  => ['nullable', 'in:0,1'],
        ]);

        $gebaeudeIds     = $data['ids'];
        $tourId          = $data['tour_id'];
        $replaceExisting = ($data['replace_existing'] ?? '0') === '1';

        $countAttached = 0;

        DB::transaction(function () use ($gebaeudeIds, $tourId, $replaceExisting, &$countAttached) {
            foreach ($gebaeudeIds as $gid) {
                $g = Gebaeude::find($gid);
                if (!$g) continue;

                if ($replaceExisting) {
                    $g->touren()->sync([$tourId => ['reihenfolge' => 1]]);
                } else {
                    $already = $g->touren()->wherePivot('tour_id', $tourId)->exists();
                    if (!$already) {
                        // ⭐ FIX: Tabellenname explizit angeben wegen Ambiguität
                        $maxOrd = $g->touren()->max('tourgebaeude.reihenfolge') ?? 0;
                        $g->touren()->attach($tourId, ['reihenfolge' => $maxOrd + 1]);
                    }
                }

                $countAttached++;
            }
        });

        return back()->with('success', "{$countAttached} Gebäude wurden der Tour zugeordnet.");
    }

    /**
     * ⭐ NEU: Mehrere Gebäude auf einmal löschen (Bulk Delete)
     */
    public function bulkDestroy(Request $request)
    {
        $data = $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:gebaeude,id'],
        ]);

        $ids = $data['ids'];
        $count = 0;
        $errors = [];

        DB::transaction(function () use ($ids, &$count, &$errors) {
            foreach ($ids as $id) {
                try {
                    $gebaeude = Gebaeude::find($id);
                    if ($gebaeude) {
                        // SoftDelete - Gebäude wird nicht wirklich gelöscht
                        $gebaeude->delete();
                        $count++;
                    }
                } catch (\Exception $e) {
                    $errors[] = "ID {$id}: " . $e->getMessage();
                    Log::error('Bulk Delete Fehler', ['id' => $id, 'error' => $e->getMessage()]);
                }
            }
        });

        if ($count > 0) {
            $message = "{$count} Gebäude wurden gelöscht.";
            if (!empty($errors)) {
                $message .= " " . count($errors) . " Fehler aufgetreten.";
            }
            return back()->with('success', $message);
        }

        return back()->with('error', 'Keine Gebäude konnten gelöscht werden.');
    }

    /**
     * Timeline-Eintrag speichern.
     * 
     * ⭐ NEU: rechnung_schreiben wird nur auf 1 gesetzt, wenn ein FatturaPA-Profil vorhanden ist
     */
    public function storeTimeline(Request $request, Gebaeude $gebaeude)
    {
        $data = $request->validate([
            'datum'     => 'required|date',
            'eintrag'   => 'nullable|string|max:5000',
            'ma_id'     => 'nullable|integer|exists:users,id',
        ]);

        $data['gebaeude_id'] = $gebaeude->id;

        Timeline::create($data);

        // Nach Speichern: gemachte_reinigungen +1
        if ($gebaeude->gemachte_reinigungen !== null) {
            $gebaeude->increment('gemachte_reinigungen');
        }

        // ⭐ NEU: rechnung_schreiben nur auf 1 setzen, wenn FatturaPA-Profil vorhanden
        $message = 'Timeline-Eintrag gespeichert.';

        if ($gebaeude->fattura_profile_id) {
            $gebaeude->update(['rechnung_schreiben' => 1]);
            $message .= ' Rechnung schreiben aktiviert.';
        } else {
            // Kein Profil → Hinweis geben
            $message .= ' (Kein FatturaPA-Profil - Rechnung schreiben bleibt deaktiviert)';
        }

        return back()->with('success', $message);
    }

    /**
     * Timeline-Eintrag löschen.
     */
    public function destroyTimeline(Gebaeude $gebaeude, $timeline)
    {
        $entry = Timeline::where('id', $timeline)
            ->where('gebaeude_id', $gebaeude->id)
            ->firstOrFail();

        $entry->delete();

        return back()->with('success', 'Timeline-Eintrag gelöscht.');
    }

    /**
     * Sicherer Redirect: erlaubt nur relative Pfade oder gleiche Origin.
     */
    protected function safeReturnTo(?string $url, string $fallback): string
    {
        if (!$url) return $fallback;

        if (str_starts_with($url, '/')) return $url;

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
     */
    public function resetGemachteReinigungen(Request $request)
    {
        if (!$request->user()) {
            abort(403);
        }

        if ($request->filled('confirm') && $request->input('confirm') !== 'YES') {
            return back()->with('error', 'Aktion nicht bestätigt.');
        }

        $countTotal = DB::table('gebaeude')->count();
        $sumBefore  = (int) DB::table('gebaeude')->sum('gemachte_reinigungen');

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

    public function recalcFaelligkeit(int $id, FaelligkeitsService $svc)
    {
        $g = Gebaeude::findOrFail($id);
        $isFaellig = $svc->recalcForGebaeude($g);

        return response()->json([
            'ok'       => true,
            'faellig'  => $isFaellig,
            'message'  => $isFaellig
                ? 'Anlage ist fällig (für den aktuellen Monat).'
                : 'Anlage ist aktuell nicht fällig.',
        ]);
    }

    public function recalcFaelligAll(Request $request, FaelligkeitsService $svc)
    {
        $processed = $svc->recalcAll();

        if (!$request->expectsJson()) {
            return back()->with(
                'success',
                "Fälligkeit für {$processed} Gebäude neu berechnet."
            );
        }

        return response()->json([
            'ok'        => true,
            'processed' => $processed,
        ]);
    }

    /**
     * Erstellt eine neue Rechnung aus einem Gebäude.
     */
    public function createRechnung(int $id)
    {
        try {
            $gebaeude = Gebaeude::with([
                'rechnungsempfaenger',
                'postadresse',
                'fatturaProfile',
                'aktiveArtikel'
            ])->findOrFail($id);

            if (!$gebaeude->rechnungsempfaenger_id || !$gebaeude->postadresse_id) {
                return redirect()
                    ->route('gebaeude.edit', $gebaeude->id)
                    ->with('error', 'Bitte hinterlegen Sie zuerst einen Rechnungsempfänger und eine Postadresse für dieses Gebäude.');
            }

            if ($gebaeude->aktiveArtikel->isEmpty()) {
                return redirect()
                    ->route('gebaeude.edit', $gebaeude->id)
                    ->with('warning', 'Dieses Gebäude hat keine aktiven Artikel. Bitte fügen Sie zuerst Artikel hinzu.');
            }

            $rechnung = \App\Models\Rechnung::createFromGebaeude($gebaeude);

            Log::info('Rechnung aus Gebäude erstellt', [
                'rechnung_id'    => $rechnung->id,
                'rechnung_nr'    => $rechnung->nummern,
                'gebaeude_id'    => $gebaeude->id,
                'gebaeude_codex' => $gebaeude->codex,
                'positionen'     => $rechnung->positionen->count(),
            ]);

            return redirect()
                ->route('rechnung.edit', $rechnung->id)
                ->with('success', "Rechnung {$rechnung->nummern} wurde erfolgreich aus Gebäude {$gebaeude->codex} erstellt.");
        } catch (\Exception $e) {
            Log::error('Fehler beim Erstellen der Rechnung aus Gebäude', [
                'gebaeude_id' => $id,
                'error'       => $e->getMessage(),
                'trace'       => $e->getTraceAsString()
            ]);

            return redirect()
                ->back()
                ->with('error', 'Fehler beim Erstellen der Rechnung: ' . $e->getMessage());
        }
    }

    /**
     * Setzt individuellen Aufschlag für Gebäude
     */
    public function setAufschlag(Request $request, Gebaeude $gebaeude)
    {
        $validated = $request->validate([
            'prozent'     => 'required|numeric|min:-100|max:100',
            'grund'       => 'nullable|string|max:255',
            'gueltig_ab'  => 'nullable|date',
            'gueltig_bis' => 'nullable|date|after:gueltig_ab',
        ]);

        $gebaeude->setAufschlag(
            $validated['prozent'],
            $validated['grund'] ?? null,
            isset($validated['gueltig_ab']) ? Carbon::parse($validated['gueltig_ab']) : null,
            isset($validated['gueltig_bis']) ? Carbon::parse($validated['gueltig_bis']) : null
        );

        return redirect()
            ->route('gebaeude.edit', $gebaeude->id)
            ->with('success', 'Individueller Aufschlag wurde erfolgreich gesetzt.');
    }

    /**
     * Entfernt individuellen Aufschlag
     */
    public function removeAufschlag(Gebaeude $gebaeude)
    {
        $gebaeude->entferneIndividuellenAufschlag();

        return redirect()
            ->route('gebaeude.edit', $gebaeude->id)
            ->with('success', 'Individueller Aufschlag wurde entfernt. Es gilt wieder der globale Aufschlag.');
    }

    /**
     * Gibt Aufschlag für ein Gebäude und Jahr zurück (für JavaScript/AJAX)
     */
    public function getAufschlag(Request $request, int $id)
    {
        try {
            $gebaeude = Gebaeude::findOrFail($id);
            $jahr = $request->integer('jahr', now()->year);

            $aufschlag = $gebaeude->getAufschlagProzent($jahr);
            $hatIndividuell = $gebaeude->hatIndividuellenAufschlag();

            return response()->json([
                'ok'              => true,
                'aufschlag'       => $aufschlag,
                'jahr'            => $jahr,
                'ist_individuell' => $hatIndividuell,
            ]);
        } catch (\Exception $e) {
            Log::error('Fehler beim Abrufen des Aufschlags', [
                'gebaeude_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'ok'      => false,
                'message' => 'Fehler beim Abrufen des Aufschlags',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Erstellt Adresse aus Gebäudedaten
     */
    public function erstelleAdresse(Gebaeude $gebaeude)
    {
        // Bereits vorhanden?
        if ($gebaeude->postadresse_id || $gebaeude->rechnungsempfaenger_id) {
            return back()->with('warning', 'Dieses Gebäude hat bereits eine Adresse.');
        }

        // Minimale Daten vorhanden?
        if (empty($gebaeude->strasse) || empty($gebaeude->wohnort)) {
            return back()->with('error', 'Keine Adressdaten vorhanden (Straße/Ort fehlt).');
        }

        try {
            $adresse = $gebaeude->erstelleAdresseAusGebaeude();

            return back()->with('success', "Adresse \"{$adresse->name}\" erstellt und zugewiesen.");
        } catch (\Exception $e) {
            return back()->with('error', 'Fehler: ' . $e->getMessage());
        }
    }
}
