<?php

namespace App\Http\Controllers;

use App\Models\PreisAufschlag;
use App\Models\GebaeudeAufschlag;
use App\Models\Gebaeude;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PreisAufschlagController extends Controller
{
    // ═══════════════════════════════════════════════════════════
    // 🌍 GLOBALE AUFSCHLÄGE
    // ═══════════════════════════════════════════════════════════

    /**
     * Übersicht aller globalen Aufschläge
     */
    public function index()
    {
        $aufschlaege = PreisAufschlag::orderByDesc('jahr')->get();
        
        return view('preis-aufschlaege.index', [
            'aufschlaege' => $aufschlaege,
        ]);
    }

    /**
     * Globalen Aufschlag setzen/aktualisieren
     */
    public function storeGlobal(Request $request)
    {
        $data = $request->validate([
            'jahr'         => ['required', 'integer', 'min:2020', 'max:2099'],
            'prozent'      => ['required', 'numeric', 'min:-100', 'max:100'],
            'beschreibung' => ['nullable', 'string', 'max:500'],
        ]);

        $aufschlag = PreisAufschlag::setGlobalerAufschlag(
            jahr: $data['jahr'],
            prozent: $data['prozent'],
            beschreibung: $data['beschreibung'] ?? null
        );

        Log::info('Globaler Aufschlag gesetzt', [
            'jahr'    => $data['jahr'],
            'prozent' => $data['prozent'],
            'user_id' => Auth::id(),
        ]);

        return back()->with('success', sprintf(
            'Globaler Aufschlag für %d gesetzt: %+.2f%%',
            $aufschlag->jahr,
            $aufschlag->prozent
        ));
    }

    /**
     * Globalen Aufschlag löschen
     */
    public function destroyGlobal(int $id)
    {
        $aufschlag = PreisAufschlag::findOrFail($id);
        $jahr = $aufschlag->jahr;
        
        $aufschlag->delete();

        Log::warning('Globaler Aufschlag gelöscht', [
            'jahr'    => $jahr,
            'user_id' => Auth::id(),
        ]);

        return back()->with('success', "Globaler Aufschlag für {$jahr} gelöscht.");
    }

    // ═══════════════════════════════════════════════════════════
    // 🏢 GEBÄUDE-SPEZIFISCHE AUFSCHLÄGE
    // ═══════════════════════════════════════════════════════════

    /**
     * Übersicht aller Gebäude mit individuellen Aufschlägen
     */
    public function indexGebaeude()
    {
        $gebaeudeAufschlaege = GebaeudeAufschlag::with('gebaeude')
            ->orderByDesc('gueltig_ab')
            ->get();

        return view('preis-aufschlaege.gebaeude', [
            'gebaeudeAufschlaege' => $gebaeudeAufschlaege,
        ]);
    }

    /**
     * Individuellen Aufschlag für ein Gebäude setzen
     */
    public function storeGebaeude(Request $request, int $gebaeudeId)
    {
        $gebaeude = Gebaeude::findOrFail($gebaeudeId);

        $data = $request->validate([
            'prozent'     => ['required', 'numeric', 'min:-100', 'max:100'],
            'grund'       => ['nullable', 'string', 'max:500'],
            'gueltig_ab'  => ['nullable', 'date'],
            'gueltig_bis' => ['nullable', 'date', 'after_or_equal:gueltig_ab'],
        ]);

        $aufschlag = $gebaeude->setAufschlag(
            prozent: $data['prozent'],
            grund: $data['grund'] ?? null,
            gueltigAb: isset($data['gueltig_ab']) ? \Carbon\Carbon::parse($data['gueltig_ab']) : null,
            gueltigBis: isset($data['gueltig_bis']) ? \Carbon\Carbon::parse($data['gueltig_bis']) : null
        );

        Log::info('Gebäude-Aufschlag gesetzt', [
            'gebaeude_id' => $gebaeude->id,
            'prozent'     => $data['prozent'],
            'user_id' => Auth::id(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'ok'      => true,
                'message' => 'Aufschlag gespeichert',
                'data'    => $aufschlag,
            ]);
        }

        return back()->with('success', sprintf(
            'Individueller Aufschlag für %s gesetzt: %+.2f%%',
            $gebaeude->gebaeude_name,
            $aufschlag->prozent
        ));
    }

    /**
     * Individuellen Aufschlag entfernen (Gebäude nutzt wieder globalen)
     */
    public function destroyGebaeude(int $gebaeudeId)
    {
        $gebaeude = Gebaeude::findOrFail($gebaeudeId);
        
        $gebaeude->entferneIndividuellenAufschlag();

        Log::info('Gebäude-Aufschlag entfernt', [
            'gebaeude_id' => $gebaeude->id,
            'user_id' => Auth::id(),
        ]);

        if (request()->expectsJson()) {
            return response()->json([
                'ok'      => true,
                'message' => 'Aufschlag entfernt - nutzt nun globalen Aufschlag',
            ]);
        }

        return back()->with('success', sprintf(
            '%s nutzt nun wieder den globalen Aufschlag.',
            $gebaeude->gebaeude_name
        ));
    }

    /**
     * Zeigt aktuellen Aufschlag für ein Gebäude
     */
    public function showGebaeude(int $gebaeudeId)
    {
        $gebaeude = Gebaeude::findOrFail($gebaeudeId);
        
        $aktuellerAufschlag = $gebaeude->getAufschlagProzent();
        $istIndividuell = $gebaeude->hatIndividuellenAufschlag();
        $globalerAufschlag = PreisAufschlag::getGlobalerAufschlag();

        if (request()->expectsJson()) {
            return response()->json([
                'gebaeude_id'        => $gebaeude->id,
                'gebaeude_name'      => $gebaeude->gebaeude_name,
                'aktueller_aufschlag' => $aktuellerAufschlag,
                'ist_individuell'    => $istIndividuell,
                'globaler_aufschlag' => $globalerAufschlag,
            ]);
        }

        return view('preis-aufschlaege.gebaeude-detail', [
            'gebaeude'           => $gebaeude,
            'aktuellerAufschlag' => $aktuellerAufschlag,
            'istIndividuell'     => $istIndividuell,
            'globalerAufschlag'  => $globalerAufschlag,
        ]);
    }

    // ═══════════════════════════════════════════════════════════
    // 🧮 VORSCHAU & SIMULATION
    // ═══════════════════════════════════════════════════════════

    /**
     * Zeigt Vorschau, wie sich ein Aufschlag auswirken würde
     */
    public function preview(Request $request)
    {
        $data = $request->validate([
            'gebaeude_id' => ['required', 'integer', 'exists:gebaeude,id'],
            'prozent'     => ['required', 'numeric', 'min:-100', 'max:100'],
        ]);

        $gebaeude = Gebaeude::findOrFail($data['gebaeude_id']);
        $prozent = (float) $data['prozent'];

        // Artikel laden
        $artikel = $gebaeude->aktiveArtikel;
        
        $preview = $artikel->map(function ($a) use ($prozent) {
            $original = (float) $a->einzelpreis;
            $aufschlag = round($original * ($prozent / 100), 2);
            $neu = round($original + $aufschlag, 2);
            
            return [
                'beschreibung'   => $a->beschreibung,
                'anzahl'         => $a->anzahl,
                'preis_original' => $original,
                'aufschlag'      => $aufschlag,
                'preis_neu'      => $neu,
                'gesamt_original' => round($original * $a->anzahl, 2),
                'gesamt_neu'     => round($neu * $a->anzahl, 2),
            ];
        });

        $summe_original = $preview->sum('gesamt_original');
        $summe_neu = $preview->sum('gesamt_neu');
        $differenz = $summe_neu - $summe_original;

        return response()->json([
            'ok'             => true,
            'prozent'        => $prozent,
            'artikel'        => $preview,
            'summe_original' => $summe_original,
            'summe_neu'      => $summe_neu,
            'differenz'      => $differenz,
        ]);
    }
}