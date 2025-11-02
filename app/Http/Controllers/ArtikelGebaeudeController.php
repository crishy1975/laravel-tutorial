<?php
// app/Http/Controllers/ArtikelGebaeudeController.php

namespace App\Http\Controllers;

use App\Models\Gebaeude;
use App\Models\ArtikelGebaeude;
use Illuminate\Http\Request;

class ArtikelGebaeudeController extends Controller
{
    public function store(Request $request, int $gebaeudeId)
    {
        $gebaeude = \App\Models\Gebaeude::findOrFail($gebaeudeId);

        $data = $request->validate([
            'beschreibung' => ['required', 'string', 'max:255'],
            'anzahl'       => ['required', 'numeric', 'min:0'],
            'einzelpreis'  => ['required', 'numeric', 'min:0'],
            'aktiv'        => ['nullable', 'boolean'],
            'reihenfolge'  => ['nullable', 'integer', 'min:0'],
        ]);

        $data['gebaeude_id'] = $gebaeude->id;
        $data['aktiv'] = isset($data['aktiv']) ? (bool)$data['aktiv'] : true;

        \App\Models\ArtikelGebaeude::create($data);

        return back()->with('success', 'Artikel hinzugefügt.');
    }

    public function destroy(int $id)
    {
        $pos = ArtikelGebaeude::findOrFail($id);
        $gid = $pos->gebaeude_id;
        $pos->delete();

        return redirect()->route('gebaeude.edit', $gid)->with('success', 'Artikel gelöscht.');
    }

    public function update(Request $request, int $id)
    {
        $pos = \App\Models\ArtikelGebaeude::findOrFail($id);

        $data = $request->validate([
            'beschreibung' => ['required', 'string', 'max:255'],
            'anzahl'       => ['required', 'numeric', 'min:0'],
            'einzelpreis'  => ['required', 'numeric', 'min:0'],
            'aktiv'        => ['nullable', 'boolean'],
            'reihenfolge'  => ['nullable', 'integer', 'min:0'],
        ]);

        $data['aktiv'] = isset($data['aktiv']) ? (bool)$data['aktiv'] : false;

        $pos->update($data);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'message' => 'Gespeichert.']);
        }
        return back()->with('success', 'Gespeichert.');
    }

    // app/Http/Controllers/ArtikelGebaeudeController.php

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
            \App\Models\ArtikelGebaeude::where('gebaeude_id', $gebaeudeId)
                ->where('id', $id)
                ->update(['reihenfolge' => $order]);
            $order += 10;
        }

        return response()->json(['ok' => true, 'message' => 'Reihenfolge aktualisiert.']);
    }
}
