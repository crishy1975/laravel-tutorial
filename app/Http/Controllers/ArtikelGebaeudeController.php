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
        $gebaeude = Gebaeude::findOrFail($gebaeudeId);

        $data = $request->validate([
            'beschreibung' => ['required', 'string', 'max:255'],
            'anzahl'       => ['required', 'numeric', 'min:0'],
            'einzelpreis'  => ['required', 'numeric', 'min:0'],
        ]);

        $data['gebaeude_id'] = $gebaeude->id;

        ArtikelGebaeude::create($data);

        // Redirect oder JSON – wie du’s brauchst
        return back()->with('success', 'Artikel hinzugefügt.');
    }

    public function destroy(int $id)
    {
        $pos = ArtikelGebaeude::findOrFail($id);
        $gid = $pos->gebaeude_id;
        $pos->delete();

        return redirect()->route('gebaeude.edit', $gid)->with('success', 'Artikel gelöscht.');
    }
}
