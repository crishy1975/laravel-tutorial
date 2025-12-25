<?php

namespace App\Http\Controllers;

use App\Models\Gebaeude;
use App\Models\Timeline;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class TimelineController extends Controller
{
    /**
     * Speichert einen Timeline-Eintrag zu einem Gebäude.
     *
     * Unterstützt:
     *  - Klassisches POST-Submit (Redirect zurück, Flash-Message)
     *  - AJAX/fetch mit "Accept: application/json" (JSON-Response)
     *
     * Erwartete Felder:
     *  - datum (nullable|date)     -> Standard: heute
     *  - bemerkung (nullable|str)
     *  - returnTo (optional)       -> Ziel-URL für Redirects
     */
    public function timelineStore(Request $request, int $id)
    {
        // Eindeutige Debug-ID für Logs/Fehlersuche
        $debugId = (string) Str::uuid();

        // 1) Gebäude laden (404 wenn nicht vorhanden)
        $gebaeude = Gebaeude::findOrFail($id);

        // 2) Validierung (beide Felder optional)
        try {
            $data = $request->validate([
                'datum'     => ['nullable', 'date'],
                'bemerkung' => ['nullable', 'string', 'max:1000'],
            ]);
        } catch (\Illuminate\Validation\ValidationException $ve) {
            // Bei klassischem Submit übernimmt Laravel Redirect + Errors automatisch.
            // Für AJAX liefern wir 422 mit Fehlerdetails.
            Log::warning('timelineStore VALIDATION FAILED', [
                'debugId'  => $debugId,
                'gebaeude' => $gebaeude->id,
                'errors'   => $ve->errors(),
                'payload'  => $request->all(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'ok'      => false,
                    'errors'  => $ve->errors(),
                    'msg'     => 'Validierung fehlgeschlagen',
                    'debugId' => $debugId,
                ], 422);
            }

            throw $ve;
        }

        // 3) Defaults & Meta
        $user     = $request->user();
        $datum    = $data['datum'] ?? now()->toDateString();
        $note     = isset($data['bemerkung']) ? trim((string) $data['bemerkung']) : '';
        $returnTo = $request->input('returnTo');

        Log::info('timelineStore START', [
            'debugId'      => $debugId,
            'gebaeude_id'  => $gebaeude->id,
            'user_id'      => $user?->id,
            'datum'        => $datum,
            'bemerkung'    => $note,
            'raw_payload'  => $request->all(),
        ]);

        try {
            DB::beginTransaction();

            // 4) Timeline-Eintrag schreiben
            // ACHTUNG: Timeline::$fillable muss die Felder erlauben.
            $timeline = Timeline::create([
                'gebaeude_id' => $gebaeude->id,
                'datum'       => $datum,
                'bemerkung'   => $note,
                'person_name' => $user?->name ?? 'Unbekannt',
                'person_id'   => $user?->id ?? 0,
            ]);

            // 5) Gebäude-Status aktualisieren (nur wenn Spalten existieren)
            $tableGebaeude = $gebaeude->getTable();
            $updates       = [];

            // ⭐ GEÄNDERT: rechnung_schreiben NUR setzen wenn FatturaPA-Profil vorhanden!
            if (Schema::hasColumn($tableGebaeude, 'rechnung_schreiben')) {
                // Nur wenn ein FatturaPA-Profil zugewiesen ist
                if ($gebaeude->fattura_profile_id) {
                    $updates['rechnung_schreiben'] = 1;
                }
                // Sonst: rechnung_schreiben bleibt unverändert (nicht in $updates)
            }

            if (Schema::hasColumn($tableGebaeude, 'gemachte_reinigungen')) {
                $updates['gemachte_reinigungen'] = DB::raw('COALESCE(gemachte_reinigungen,0) + 1');
            }
            if (Schema::hasColumn($tableGebaeude, 'letzter_termin')) {
                $updates['letzter_termin'] = $datum;
            }

            if (!empty($updates)) {
                Gebaeude::whereKey($gebaeude->id)->update($updates);
            }

            DB::commit();

            Log::info('timelineStore COMMIT', [
                'debugId'            => $debugId,
                'timeline_id'        => $timeline->id,
                'gebaeude_id'        => $gebaeude->id,
                'set_updates'        => $updates,
                'fattura_profile_id' => $gebaeude->fattura_profile_id,
            ]);

            // 6) Fälligkeit direkt nach erfolgreichem Commit neu berechnen
            //    Erwartet die Methode Gebaeude::recomputeFaellig() (wie von uns vorgeschlagen).
            $faellig = null;
            try {
                $gebaeude->refresh();              // sicherheitshalber neu laden
                if (method_exists($gebaeude, 'recomputeFaellig')) {
                    $faellig = $gebaeude->recomputeFaellig(); // persistiert intern
                }
            } catch (\Throwable $re) {
                Log::warning('faellig recompute after timeline failed', [
                    'gebaeude_id' => $gebaeude->id,
                    'msg'         => $re->getMessage(),
                ]);
            }

            // 7) Response je nach Erwartung: JSON oder Redirect
            if ($request->expectsJson()) {
                // ⭐ NEU: Info über rechnung_schreiben im Response
                $rechnungSchreibenAktiviert = isset($updates['rechnung_schreiben']) && $updates['rechnung_schreiben'] == 1;
                
                return response()->json([
                    'ok'                          => true,
                    'message'                     => 'Timeline-Eintrag hinzugefügt.',
                    'timeline_id'                 => $timeline->id,
                    'faellig'                     => is_bool($faellig) ? (int) $faellig : (int) ($gebaeude->faellig ?? 0),
                    'rechnung_schreiben_aktiviert' => $rechnungSchreibenAktiviert,
                    'debugId'                     => $debugId,
                ], 201);
            }

            // ⭐ NEU: Unterschiedliche Meldung je nach FatturaPA-Profil
            $message = 'Timeline-Eintrag hinzugefügt.';
            if (isset($updates['rechnung_schreiben'])) {
                $message .= ' Rechnung schreiben aktiviert.';
            } elseif (!$gebaeude->fattura_profile_id) {
                $message .= ' (Kein FatturaPA-Profil - Rechnung schreiben bleibt unverändert)';
            }

            return redirect()
                ->to($returnTo ?: route('gebaeude.edit', $gebaeude->id))
                ->with('success', $message);
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

            if ($request->expectsJson()) {
                return response()->json([
                    'ok'      => false,
                    'message' => 'Timeline konnte nicht gespeichert werden.',
                    'debugId' => $debugId,
                ], 500);
            }

            return back()
                ->withInput()
                ->with('error', "Timeline konnte nicht gespeichert werden. (Debug-ID: {$debugId})");
        }
    }


    /**
     * Löscht einen Timeline-Eintrag.
     *
     * Unterstützt sowohl echtes DELETE (Form mit @method('DELETE'))
     * als auch AJAX/fetch mit POST + _method=DELETE (JSON-Body).
     */
    public function destroy(Request $request, int $id)
    {
        $debugId = (string) Str::uuid();

        try {
            $entry    = Timeline::findOrFail($id);
            $gid      = $entry->gebaeude_id;
            $returnTo = $request->input('returnTo');

            $entry->delete();

            Log::info('Timeline.destroy OK', [
                'debugId'     => $debugId,
                'entry_id'    => $id,
                'gebaeude_id' => $gid,
                'user_id'     => optional($request->user())->id,
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'ok'      => true,
                    'message' => 'Timeline-Eintrag gelöscht.',
                    'debugId' => $debugId,
                ]);
            }

            return redirect()
                ->to($returnTo ?: route('gebaeude.edit', $gid))
                ->with('success', 'Timeline-Eintrag gelöscht.');
        } catch (Throwable $e) {
            Log::error('Timeline.destroy ERROR', [
                'debugId'  => $debugId,
                'entry_id' => $id,
                'type'     => get_class($e),
                'message'  => $e->getMessage(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'ok'      => false,
                    'message' => 'Löschen fehlgeschlagen.',
                    'debugId' => $debugId,
                ], 500);
            }

            return back()
                ->with('error', "Löschen fehlgeschlagen. (Fehler-ID: {$debugId})");
        }
    }

    /**
     * Verrechnen-Flag eines Timeline-Eintrags setzen.
     *
     * Erwartet per JSON (fetch):
     *   { "verrechnen": 1|0 }
     */
    public function toggleVerrechnen(Request $request, int $id)
    {
        // Eintrag laden (404, falls nicht vorhanden)
        $entry = Timeline::findOrFail($id);

        // Eingaben prüfen: verrechnen muss boolean sein
        $validated = $request->validate([
            'verrechnen' => 'required|boolean',
        ]);

        // Flag setzen
        $entry->verrechnen = $validated['verrechnen'];

        // OPTIONAL:
        // Wenn du beim Einschalten automatisch "verrechnet_am" setzen möchtest:
        // if ($entry->verrechnen && !$entry->verrechnet_am) {
        //     $entry->verrechnet_am = now();
        // }
        //
        // Wenn du beim Ausschalten das Datum wieder löschen willst:
        // if (!$entry->verrechnen) {
        //     $entry->verrechnet_am = null;
        //     $entry->verrechnet_mit_rn_nummer = null;
        // }

        $entry->save();

        // Für dein fetch() im View: saubere JSON-Antwort
        return response()->json([
            'ok'         => true,
            'id'         => $entry->id,
            'verrechnen' => (bool) $entry->verrechnen,
        ]);
    }
}
