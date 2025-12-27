<?php

namespace App\Http\Controllers;

use App\Models\Textvorschlag;
use Illuminate\Http\Request;

class TextvorschlagController extends Controller
{
    /**
     * Übersicht aller Textvorschläge
     */
    public function index(Request $request)
    {
        $query = Textvorschlag::query();

        // Filter: Kategorie
        if ($request->filled('kategorie')) {
            $query->where('kategorie', $request->kategorie);
        }

        // Filter: Sprache
        if ($request->filled('sprache')) {
            $query->where('sprache', $request->sprache);
        }

        // Filter: Status
        if ($request->filled('status')) {
            $query->where('aktiv', $request->status === 'aktiv');
        }

        $vorschlaege = $query
            ->orderBy('kategorie')
            ->orderBy('sprache')
            ->orderBy('sortierung')
            ->orderBy('text')
            ->paginate(50)
            ->withQueryString();

        $kategorien = Textvorschlag::KATEGORIEN;
        $sprachen = Textvorschlag::SPRACHEN;

        // Statistik
        $stats = [
            'gesamt' => Textvorschlag::count(),
            'aktiv' => Textvorschlag::where('aktiv', true)->count(),
            'deutsch' => Textvorschlag::where('sprache', 'de')->count(),
            'italienisch' => Textvorschlag::where('sprache', 'it')->count(),
        ];

        return view('textvorschlaege.index', compact(
            'vorschlaege', 'kategorien', 'sprachen', 'stats'
        ));
    }

    /**
     * Formular: Neuen Vorschlag erstellen
     */
    public function create()
    {
        $vorschlag = new Textvorschlag();
        $kategorien = Textvorschlag::KATEGORIEN;
        $sprachen = Textvorschlag::SPRACHEN;

        return view('textvorschlaege.form', compact('vorschlag', 'kategorien', 'sprachen'));
    }

    /**
     * Neuen Vorschlag speichern
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'kategorie' => 'required|string|max:50',
            'sprache' => 'required|in:de,it',
            'text' => 'required|string|max:1000',
            'aktiv' => 'boolean',
            'sortierung' => 'nullable|integer|min:0',
        ]);

        $data['aktiv'] = $request->has('aktiv');
        $data['sortierung'] = $data['sortierung'] ?? 0;

        Textvorschlag::create($data);

        return redirect()
            ->route('textvorschlaege.index')
            ->with('success', 'Textvorschlag erstellt.');
    }

    /**
     * Formular: Vorschlag bearbeiten
     */
    public function edit(Textvorschlag $textvorschlag)
    {
        $vorschlag = $textvorschlag;
        $kategorien = Textvorschlag::KATEGORIEN;
        $sprachen = Textvorschlag::SPRACHEN;

        return view('textvorschlaege.form', compact('vorschlag', 'kategorien', 'sprachen'));
    }

    /**
     * Vorschlag aktualisieren
     */
    public function update(Request $request, Textvorschlag $textvorschlag)
    {
        $data = $request->validate([
            'kategorie' => 'required|string|max:50',
            'sprache' => 'required|in:de,it',
            'text' => 'required|string|max:1000',
            'aktiv' => 'boolean',
            'sortierung' => 'nullable|integer|min:0',
        ]);

        $data['aktiv'] = $request->has('aktiv');
        $data['sortierung'] = $data['sortierung'] ?? 0;

        $textvorschlag->update($data);

        return redirect()
            ->route('textvorschlaege.index')
            ->with('success', 'Textvorschlag aktualisiert.');
    }

    /**
     * Vorschlag löschen
     */
    public function destroy(Textvorschlag $textvorschlag)
    {
        $textvorschlag->delete();

        return redirect()
            ->route('textvorschlaege.index')
            ->with('success', 'Textvorschlag gelöscht.');
    }

    /**
     * Schnell-Toggle: Aktiv/Inaktiv
     */
    public function toggleAktiv(Textvorschlag $textvorschlag)
    {
        $textvorschlag->update(['aktiv' => !$textvorschlag->aktiv]);

        return back()->with('success', 
            $textvorschlag->aktiv ? 'Vorschlag aktiviert.' : 'Vorschlag deaktiviert.'
        );
    }

    /**
     * API: Vorschläge für eine Kategorie (für AJAX)
     */
    public function api(Request $request)
    {
        $kategorie = $request->input('kategorie');
        
        if (!$kategorie) {
            return response()->json(['error' => 'Kategorie erforderlich'], 400);
        }

        return response()->json(Textvorschlag::fuerKategorie($kategorie));
    }
}
