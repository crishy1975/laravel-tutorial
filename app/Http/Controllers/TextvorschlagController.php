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

        // Filter: Status
        if ($request->filled('status')) {
            $query->where('aktiv', $request->status === 'aktiv');
        }

        // Filter: Suche
        if ($request->filled('suche')) {
            $suche = $request->suche;
            $query->where(function($q) use ($suche) {
                $q->where('titel', 'like', "%{$suche}%")
                  ->orWhere('text', 'like', "%{$suche}%");
            });
        }

        $vorschlaege = $query
            ->orderBy('kategorie')
            ->orderBy('titel')
            ->paginate(50)
            ->withQueryString();

        $kategorien = Textvorschlag::KATEGORIEN;

        // Statistik
        $stats = [
            'gesamt' => Textvorschlag::count(),
            'aktiv' => Textvorschlag::where('aktiv', true)->count(),
        ];

        return view('textvorschlaege.index', compact(
            'vorschlaege', 'kategorien', 'stats'
        ));
    }

    /**
     * Formular: Neuen Vorschlag erstellen
     */
    public function create()
    {
        $vorschlag = new Textvorschlag();
        $kategorien = Textvorschlag::KATEGORIEN;

        return view('textvorschlaege.form', compact('vorschlag', 'kategorien'));
    }

    /**
     * Neuen Vorschlag speichern
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'kategorie' => 'required|string|max:50',
            'titel' => 'nullable|string|max:100',
            'text' => 'required|string|max:2000',
            'aktiv' => 'boolean',
        ]);

        $data['aktiv'] = $request->has('aktiv');

        Textvorschlag::create($data);

        // Bei AJAX-Request
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Vorlage gespeichert.']);
        }

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

        return view('textvorschlaege.form', compact('vorschlag', 'kategorien'));
    }

    /**
     * Vorschlag aktualisieren
     */
    public function update(Request $request, Textvorschlag $textvorschlag)
    {
        $data = $request->validate([
            'kategorie' => 'required|string|max:50',
            'titel' => 'nullable|string|max:100',
            'text' => 'required|string|max:2000',
            'aktiv' => 'boolean',
        ]);

        $data['aktiv'] = $request->has('aktiv');

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
     * API: Vorschläge für eine Kategorie (für AJAX/Dropdown)
     */
    public function api(Request $request)
    {
        $kategorie = $request->input('kategorie');
        
        if (!$kategorie) {
            return response()->json(['error' => 'Kategorie erforderlich'], 400);
        }

        $vorschlaege = Textvorschlag::fuerKategorie($kategorie)
            ->map(fn($v) => [
                'id' => $v->id,
                'titel' => $v->anzeige_name,
                'text' => $v->text,
            ]);

        return response()->json($vorschlaege);
    }

    /**
     * API: Neue Vorlage speichern (AJAX aus Modal)
     */
    public function apiStore(Request $request)
    {
        $data = $request->validate([
            'kategorie' => 'required|string|max:50',
            'titel' => 'nullable|string|max:100',
            'text' => 'required|string|max:2000',
        ]);

        $data['aktiv'] = true;

        $vorschlag = Textvorschlag::create($data);

        return response()->json([
            'success' => true,
            'id' => $vorschlag->id,
            'titel' => $vorschlag->anzeige_name,
            'message' => 'Vorlage gespeichert!'
        ]);
    }
}
