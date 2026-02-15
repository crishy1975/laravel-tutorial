<?php
// ══════════════════════════════════════════════════════════════════════════════
// FINALE CONTROLLER-METHODEN FÜR: app/Http/Controllers/RechnungController.php
// ══════════════════════════════════════════════════════════════════════════════

use App\Models\GebaeudeLog;
use App\Enums\GebaeudeLogTyp;



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
