<?php
// ══════════════════════════════════════════════════════════════════════════════
// FINALE CONTROLLER-METHODEN FÜR: app/Http/Controllers/RechnungController.php
// ══════════════════════════════════════════════════════════════════════════════

use App\Models\GebaeudeLog;
use App\Enums\GebaeudeLogTyp;

/**
 * Rechnung löschen (Soft Delete mit Begründung + Gebäude-Log).
 */
public function destroy(Request $request, $id)
{
    $rechnung = Rechnung::findOrFail($id);

    // ──────────────────────────────────────────────────────────────────────────
    // 1. Prüfung: Nur Entwürfe können gelöscht werden
    // ──────────────────────────────────────────────────────────────────────────
    if (!$rechnung->ist_editierbar) {
        return redirect()
            ->back()
            ->with('error', 'Nur Entwürfe können gelöscht werden. Bei versendeten Rechnungen erstellen Sie eine Gutschrift.');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // 2. Validierung: Begründung ist Pflicht
    // ──────────────────────────────────────────────────────────────────────────
    $validated = $request->validate([
        'loeschgrund' => 'required|string|min:10|max:1000',
    ], [
        'loeschgrund.required' => 'Bitte geben Sie eine Begründung für die Löschung an.',
        'loeschgrund.min' => 'Die Begründung muss mindestens 10 Zeichen haben.',
    ]);

    $loeschgrund = trim($validated['loeschgrund']);
    $nummer = $rechnung->rechnungsnummer;
    $rechnungId = $rechnung->id;
    $gebaeudeId = $rechnung->gebaeude_id;
    $betrag = $rechnung->brutto_summe;

    DB::beginTransaction();
    try {
        // ──────────────────────────────────────────────────────────────────────
        // 3. LOG IM RECHNUNGS-LOG
        // ──────────────────────────────────────────────────────────────────────
        RechnungLog::create([
            'rechnung_id' => $rechnung->id,
            'typ'         => RechnungLogTyp::GELOESCHT->value,
            'titel'       => 'Rechnung gelöscht',
            'nachricht'   => $loeschgrund,
            'metadata'    => [
                'rechnungsnummer'  => $nummer,
                'gebaeude_id'      => $gebaeudeId,
                'geb_codex'        => $rechnung->geb_codex,
                'geb_name'         => $rechnung->geb_name,
                're_name'          => $rechnung->re_name,
                'brutto_summe'     => $betrag,
                'netto_summe'      => $rechnung->netto_summe,
                'status'           => $rechnung->status,
                'rechnungsdatum'   => $rechnung->rechnungsdatum?->format('Y-m-d'),
                'geloescht_von'    => auth()->user()?->name ?? 'System',
                'geloescht_am'     => now()->format('Y-m-d H:i:s'),
            ],
        ]);

        // ──────────────────────────────────────────────────────────────────────
        // 4. ⭐ LOG IM GEBÄUDE-LOG (mit Wiederherstellungs-Möglichkeit)
        // ──────────────────────────────────────────────────────────────────────
        if ($gebaeudeId) {
            GebaeudeLog::rechnungGeloescht(
                gebaeudeId: $gebaeudeId,
                rechnungsnummer: $nummer,
                betrag: (float) $betrag,
                loeschgrund: $loeschgrund,
                rechnungId: $rechnungId
            );
        }

        // ──────────────────────────────────────────────────────────────────────
        // 5. SOFT DELETE
        // ──────────────────────────────────────────────────────────────────────
        $rechnung->delete();

        DB::commit();

        Log::info('Rechnung soft-deleted', [
            'rechnung_id' => $rechnungId,
            'nummer'      => $nummer,
            'grund'       => $loeschgrund,
        ]);

        return redirect()
            ->route('rechnung.index')
            ->with('success', "Rechnung {$nummer} wurde gelöscht.");

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Fehler beim Löschen', ['error' => $e->getMessage()]);
        return back()->with('error', 'Fehler: ' . $e->getMessage());
    }
}

/**
 * Gelöschte Rechnung wiederherstellen (aus Gebäude-Log).
 */
public function restore(Request $request, $id)
{
    $rechnung = Rechnung::withTrashed()->findOrFail($id);

    if (!$rechnung->trashed()) {
        return redirect()->back()->with('info', 'Diese Rechnung ist nicht gelöscht.');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // 1. Prüfen ob Nummer bereits wieder vergeben
    // ──────────────────────────────────────────────────────────────────────────
    $existiert = Rechnung::where('jahr', $rechnung->jahr)
        ->where('laufnummer', $rechnung->laufnummer)
        ->whereNull('deleted_at')
        ->exists();

    if ($existiert) {
        return redirect()->back()->with('error', 
            "Rechnungsnummer {$rechnung->rechnungsnummer} ist bereits vergeben. " .
            "Die Rechnung kann nicht wiederhergestellt werden.");
    }

    DB::beginTransaction();
    try {
        // ──────────────────────────────────────────────────────────────────────
        // 2. Rechnung wiederherstellen
        // ──────────────────────────────────────────────────────────────────────
        $rechnung->restore();

        // ──────────────────────────────────────────────────────────────────────
        // 3. Log im Rechnungs-Log
        // ──────────────────────────────────────────────────────────────────────
        RechnungLog::create([
            'rechnung_id' => $rechnung->id,
            'typ'         => RechnungLogTyp::STATUS_GEAENDERT->value,
            'titel'       => 'Rechnung wiederhergestellt',
            'nachricht'   => 'Gelöschte Rechnung wurde wiederhergestellt.',
            'metadata'    => [
                'wiederhergestellt_von' => auth()->user()?->name ?? 'System',
                'wiederhergestellt_am'  => now()->format('Y-m-d H:i:s'),
            ],
        ]);

        // ──────────────────────────────────────────────────────────────────────
        // 4. ⭐ Gebäude-Log: Wiederherstellung loggen + alten Eintrag markieren
        // ──────────────────────────────────────────────────────────────────────
        if ($rechnung->gebaeude_id) {
            // Neuer Log-Eintrag
            GebaeudeLog::rechnungWiederhergestellt(
                gebaeudeId: $rechnung->gebaeude_id,
                rechnungsnummer: $rechnung->rechnungsnummer,
                rechnungId: $rechnung->id
            );

            // Alten "gelöscht"-Eintrag markieren (kann_wiederhergestellt_werden = false)
            $logId = $request->input('log_id');
            if ($logId) {
                $alterLog = GebaeudeLog::find($logId);
                if ($alterLog) {
                    $alterLog->markiereAlsWiederhergestellt();
                }
            } else {
                // Fallback: Letzten passenden Eintrag suchen
                $alterLog = GebaeudeLog::where('gebaeude_id', $rechnung->gebaeude_id)
                    ->where('typ', GebaeudeLogTyp::RECHNUNG_GELOESCHT->value)
                    ->whereJsonContains('metadata->rechnung_id', $rechnung->id)
                    ->latest()
                    ->first();
                    
                if ($alterLog) {
                    $alterLog->markiereAlsWiederhergestellt();
                }
            }
        }

        DB::commit();

        Log::info('Rechnung wiederhergestellt', [
            'rechnung_id' => $rechnung->id,
            'nummer'      => $rechnung->rechnungsnummer,
        ]);

        return redirect()
            ->route('rechnung.edit', $rechnung->id)
            ->with('success', "Rechnung {$rechnung->rechnungsnummer} wurde wiederhergestellt.");

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Fehler beim Wiederherstellen', ['error' => $e->getMessage()]);
        return back()->with('error', 'Fehler: ' . $e->getMessage());
    }
}

/**
 * Liste gelöschter Rechnungen (Papierkorb).
 */
public function trashed()
{
    $rechnungen = Rechnung::onlyTrashed()
        ->with('gebaeude')
        ->orderByDesc('deleted_at')
        ->paginate(25);

    return view('rechnung.trashed', compact('rechnungen'));
}
