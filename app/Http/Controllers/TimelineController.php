<?php

namespace App\Http\Controllers;

use App\Models\Timeline;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;      // ✅ wichtig
use Illuminate\Support\Facades\Schema;  // ✅ wichtig
use Throwable;

class TimelineController extends Controller
{
    /**
     * Speichert einen Timeline-Eintrag zu einem Gebäude.
     * - datum optional -> default: heute
     * - bemerkung optional
     * - setzt (optional) Statusfelder am Gebäude
     */
    public function timelineStore(Request $request, int $id)
    {
        // Eindeutige Debug-ID für Logs/Flash
        $debugId = (string) Str::uuid();

        // 1) Gebäude laden (404 wenn nicht vorhanden)
        $gebaeude = \App\Models\Gebaeude::findOrFail($id);

        // 2) Validierung – bemerkung & datum optional
        try {
            $data = $request->validate([
                'datum'     => ['nullable', 'date'],   // leer -> später "heute"
                'bemerkung' => ['nullable', 'string', 'max:1000'], // optional
            ]);
        } catch (\Illuminate\Validation\ValidationException $ve) {
            Log::warning('timelineStore VALIDATION FAILED', [
                'debugId'  => $debugId,
                'gebaeude' => $gebaeude->id,
                'errors'   => $ve->errors(),
                'payload'  => $request->all(),
            ]);
            throw $ve; // Standard-Redirect mit Fehlermeldungen
        }

        // 3) Defaults bestimmen
        $user     = $request->user();
        $datum    = $data['datum'] ?? now()->toDateString();
        $note     = $data['bemerkung'] ?? null;
        $returnTo = $request->input('returnTo'); // 🔙 aus Hidden-Feld

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

            // 4) Timeline schreiben
            // ACHTUNG: Damit create() funktioniert, müssen Felder im Timeline-Model $fillable sein.
            $timeline = Timeline::create([
                'gebaeude_id' => $gebaeude->id,
                'datum'       => $datum,
                'bemerkung'   => $note,
                'person_name' => $user?->name ?? 'Unbekannt',
                'person_id'   => $user?->id ?? 0,
            ]);

            // 5) Gebäude-Status aktualisieren (nur wenn Spalten existieren)
            $updates = [];

            // Beispiel-Felder – bitte an DEINE Spalten anpassen:
            // rechnung_schreiben (bool/int), gemachte_reinigungen (int), letzter_termin (date)
            $tableGebaeude = $gebaeude->getTable(); // z. B. 'gebaeude' oder 'gebaeudes'

            if (Schema::hasColumn($tableGebaeude, 'rechnung_schreiben')) {
                $updates['rechnung_schreiben'] = 1;
            }

            if (Schema::hasColumn($tableGebaeude, 'gemachte_reinigungen')) {
                $updates['gemachte_reinigungen'] = DB::raw('COALESCE(gemachte_reinigungen,0) + 1');
            }

            if (Schema::hasColumn($tableGebaeude, 'letzter_termin')) {
                $updates['letzter_termin'] = $datum;
            }

            if (!empty($updates)) {
                \App\Models\Gebaeude::whereKey($gebaeude->id)->update($updates);
            }

            DB::commit();

            Log::info('timelineStore COMMIT', [
                'debugId'      => $debugId,
                'timeline_id'  => $timeline->id,
                'gebaeude_id'  => $gebaeude->id,
                'set_updates'  => $updates,
            ]);

            // 🔙 sauber zurück – bevorzugt zu returnTo, sonst auf Edit-Seite
            return redirect()
                ->to($returnTo ?: route('gebaeude.edit', $gebaeude->id))
                ->with('success', "Timeline-Eintrag hinzugefügt. (Debug-ID: {$debugId})");
        } catch (Throwable $e) {
            DB::rollBack();

            Log::error('timelineStore ERROR', [
                'debugId'      => $debugId,
                'type'         => get_class($e),
                'message'      => $e->getMessage(),
                'trace_top'    => collect($e->getTrace())->take(5),
                'payload'      => $request->all(),
                'gebaeude_id'  => $gebaeude->id,
            ]);

            return back()
                ->withInput()
                ->with('error', "Timeline konnte nicht gespeichert werden. (Debug-ID: {$debugId})");
        }
    }

    /**
     * Löscht einen Timeline-Eintrag.
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

            return back()
                ->with('error', "Löschen fehlgeschlagen. (Fehler-ID: {$debugId})");
        }
    }
}
