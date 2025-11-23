<?php
// app/Http/Controllers/ArtikelGebaeudeController.php

namespace App\Http\Controllers;

use App\Models\Gebaeude;
use App\Models\ArtikelGebaeude;
use Illuminate\Http\Request;

class ArtikelGebaeudeController extends Controller
{
    /**
     * ⭐ KORRIGIERT: Neuen Artikel erstellen
     * - basis_jahr wird auf AKTUELLES Jahr gesetzt (nicht nächstes!)
     */
    public function store(Request $request, int $gebaeudeId)
    {
        $gebaeude = Gebaeude::findOrFail($gebaeudeId);

        $data = $request->validate([
            'beschreibung' => ['required', 'string', 'max:255'],
            'anzahl'       => ['required', 'numeric', 'min:0'],
            'einzelpreis'  => ['required', 'numeric', 'min:0'],
            'basis_preis'  => ['nullable', 'numeric', 'min:0'],
            'basis_jahr'   => ['nullable', 'integer', 'min:2020'],
            'aktiv'        => ['nullable', 'boolean'],
            'reihenfolge'  => ['nullable', 'integer', 'min:0'],
        ]);

        $aktuellesJahr = now()->year;  // ⭐ AKTUELLES Jahr!

        $data['gebaeude_id'] = $gebaeude->id;
        $data['aktiv'] = isset($data['aktiv']) ? (bool)$data['aktiv'] : true;
        
        // ⭐ basis_preis: Falls nicht übergeben, gleich wie einzelpreis
        $data['basis_preis'] = $data['basis_preis'] ?? $data['einzelpreis'];
        
        // ⭐ basis_jahr: AKTUELLES Jahr (Erhöhung greift ab nächstem Jahr!)
        $data['basis_jahr'] = $data['basis_jahr'] ?? $aktuellesJahr;

        ArtikelGebaeude::create($data);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'message' => 'Artikel hinzugefügt.']);
        }

        return back()->with('success', 'Artikel hinzugefügt.');
    }

    /**
     * Artikel löschen (keine Änderung)
     */
    public function destroy(int $id)
    {
        $pos = ArtikelGebaeude::findOrFail($id);
        $gid = $pos->gebaeude_id;
        $pos->delete();

        if (request()->expectsJson()) {
            return response()->json(['ok' => true, 'message' => 'Artikel gelöscht.']);
        }

        return redirect()->route('gebaeude.edit', $gid)->with('success', 'Artikel gelöscht.');
    }

    /**
     * ⭐ KORRIGIERT: Artikel aktualisieren
     * - basis_jahr wird auf AKTUELLES Jahr gesetzt, wenn einzelpreis geändert wird
     * - Wenn der Preis geändert wird, ist dieser Preis AKTUELL und erhält keine historischen Aufschläge
     */
    public function update(Request $request, int $id)
    {
        $pos = ArtikelGebaeude::findOrFail($id);

        $data = $request->validate([
            'beschreibung' => ['sometimes', 'string', 'max:255'],
            'anzahl'       => ['sometimes', 'numeric', 'min:0'],
            'einzelpreis'  => ['sometimes', 'numeric', 'min:0'],
            'basis_preis'  => ['sometimes', 'numeric', 'min:0'],
            'basis_jahr'   => ['sometimes', 'integer', 'min:2020'],
            'aktiv'        => ['sometimes', 'boolean'],
            'reihenfolge'  => ['sometimes', 'integer', 'min:0'],
        ]);

        $aktuellesJahr = now()->year;  // ⭐ AKTUELLES Jahr!

        // ⭐ WICHTIG: Wenn einzelpreis im Update enthalten ist
        if (isset($data['einzelpreis'])) {
            // Der einzelpreis ist JETZT der aktuelle Preis (egal ob geändert oder nicht)
            // → basis_preis = einzelpreis (aktueller Preis ohne historische Erhöhungen)
            $data['basis_preis'] = $data['einzelpreis'];
            
            // ⭐ basis_jahr IMMER auf AKTUELLES Jahr setzen
            // → Ab nächstem Jahr greift dann die Erhöhung auf diesen Basispreis!
            $data['basis_jahr'] = $aktuellesJahr;
        }

        // aktiv-Handling
        if (array_key_exists('aktiv', $data)) {
            $data['aktiv'] = (bool)$data['aktiv'];
        }

        $pos->update($data);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'message' => 'Gespeichert.']);
        }
        
        return back()->with('success', 'Gespeichert.');
    }

    /**
     * Reihenfolge ändern (keine Änderung)
     */
    public function reorder(Request $request, int $gebaeudeId)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'distinct'],
        ]);

        $ids = $data['ids'];

        // Reihenfolge in 10er-Schritten
        $order = 10;
        foreach ($ids as $id) {
            ArtikelGebaeude::where('gebaeude_id', $gebaeudeId)
                ->where('id', $id)
                ->update(['reihenfolge' => $order]);
            $order += 10;
        }

        return response()->json(['ok' => true, 'message' => 'Reihenfolge aktualisiert.']);
    }
}