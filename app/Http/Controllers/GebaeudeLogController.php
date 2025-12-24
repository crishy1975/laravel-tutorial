<?php

namespace App\Http\Controllers;

use App\Models\Gebaeude;
use App\Models\GebaeudeLog;
use App\Enums\GebaeudeLogTyp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GebaeudeLogController extends Controller
{
    /**
     * Alle Logs für ein Gebäude anzeigen
     */
    public function index(Request $request, int $gebaeudeId)
    {
        $gebaeude = Gebaeude::findOrFail($gebaeudeId);
        
        $query = $gebaeude->logs();
        
        // Filter: Kategorie
        if ($request->filled('kategorie')) {
            $query->kategorie($request->kategorie);
        }
        
        // Filter: Typ
        if ($request->filled('typ')) {
            $query->where('typ', $request->typ);
        }
        
        // Filter: Priorität
        if ($request->filled('prioritaet')) {
            $query->where('prioritaet', $request->prioritaet);
        }
        
        // Filter: Nur offene Erinnerungen
        if ($request->boolean('erinnerungen')) {
            $query->offeneErinnerungen();
        }
        
        // Filter: Suche
        if ($request->filled('suche')) {
            $suche = $request->suche;
            $query->where(function ($q) use ($suche) {
                $q->where('titel', 'like', "%{$suche}%")
                  ->orWhere('beschreibung', 'like', "%{$suche}%");
            });
        }
        
        $logs = $query->paginate(25)->withQueryString();
        
        // Kategorien für Filter-Dropdown
        $kategorien = [
            'stammdaten' => 'Stammdaten',
            'touren' => 'Touren',
            'artikel' => 'Artikel',
            'finanzen' => 'Finanzen',
            'reinigung' => 'Reinigung',
            'kommunikation' => 'Kommunikation',
            'probleme' => 'Probleme',
            'dokumente' => 'Dokumente',
            'erinnerungen' => 'Erinnerungen',
            'system' => 'System',
        ];
        
        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'logs' => $logs,
            ]);
        }
        
        return view('gebaeude.logs.index', compact('gebaeude', 'logs', 'kategorien'));
    }

    /**
     * Neuen Log-Eintrag speichern
     */
    public function store(Request $request, int $gebaeudeId)
    {
        $gebaeude = Gebaeude::findOrFail($gebaeudeId);
        
        $data = $request->validate([
            'typ' => ['required', 'string'],
            'beschreibung' => ['required', 'string', 'max:5000'],
            'prioritaet' => ['nullable', 'in:niedrig,normal,hoch,kritisch'],
            'kontakt_person' => ['nullable', 'string', 'max:100'],
            'kontakt_telefon' => ['nullable', 'string', 'max:50'],
            'kontakt_email' => ['nullable', 'email', 'max:100'],
            'erinnerung_datum' => ['nullable', 'date'],
        ]);
        
        $typ = GebaeudeLogTyp::tryFrom($data['typ']);
        
        if (!$typ) {
            return back()->withErrors(['typ' => 'Ungültiger Log-Typ']);
        }
        
        $log = GebaeudeLog::create([
            'gebaeude_id' => $gebaeude->id,
            'typ' => $typ,
            'titel' => $typ->label(),
            'beschreibung' => $data['beschreibung'],
            'user_id' => Auth::id(),
            'prioritaet' => $data['prioritaet'] ?? 'normal',
            'kontakt_person' => $data['kontakt_person'] ?? null,
            'kontakt_telefon' => $data['kontakt_telefon'] ?? null,
            'kontakt_email' => $data['kontakt_email'] ?? null,
            'erinnerung_datum' => $data['erinnerung_datum'] ?? null,
            'erinnerung_erledigt' => false,
        ]);
        
        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Eintrag hinzugefügt',
                'log' => $log,
            ]);
        }
        
        return back()->with('success', 'Eintrag wurde hinzugefügt.');
    }

    /**
     * Log-Eintrag löschen
     */
    public function destroy(int $id)
    {
        $log = GebaeudeLog::findOrFail($id);
        $gebaeudeId = $log->gebaeude_id;
        
        $log->delete();
        
        if (request()->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Eintrag gelöscht',
            ]);
        }
        
        return redirect()
            ->route('gebaeude.logs.index', $gebaeudeId)
            ->with('success', 'Eintrag wurde gelöscht.');
    }

    /**
     * Erinnerung als erledigt markieren
     */
    public function erinnerungErledigt(int $id)
    {
        $log = GebaeudeLog::findOrFail($id);
        
        $log->update(['erinnerung_erledigt' => true]);
        
        if (request()->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Erinnerung erledigt',
            ]);
        }
        
        return back()->with('success', 'Erinnerung als erledigt markiert.');
    }

    /**
     * Schnell eine Notiz hinzufügen (für Modal)
     */
    public function quickNotiz(Request $request, int $gebaeudeId)
    {
        $gebaeude = Gebaeude::findOrFail($gebaeudeId);
        
        $data = $request->validate([
            'beschreibung' => ['required', 'string', 'max:2000'],
            'prioritaet' => ['nullable', 'in:niedrig,normal,hoch,kritisch'],
        ]);
        
        $log = GebaeudeLog::notiz(
            $gebaeude->id,
            $data['beschreibung'],
            $data['prioritaet'] ?? 'normal'
        );
        
        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Notiz hinzugefügt',
                'log' => $log,
            ]);
        }
        
        return back()->with('success', 'Notiz wurde hinzugefügt.');
    }

    /**
     * Telefonat loggen
     */
    public function telefonat(Request $request, int $gebaeudeId)
    {
        $gebaeude = Gebaeude::findOrFail($gebaeudeId);
        
        $data = $request->validate([
            'beschreibung' => ['required', 'string', 'max:2000'],
            'kontakt_person' => ['nullable', 'string', 'max:100'],
            'kontakt_telefon' => ['nullable', 'string', 'max:50'],
        ]);
        
        $log = GebaeudeLog::telefonat(
            $gebaeude->id,
            $data['beschreibung'],
            $data['kontakt_person'] ?? null,
            $data['kontakt_telefon'] ?? null
        );
        
        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Telefonat protokolliert',
                'log' => $log,
            ]);
        }
        
        return back()->with('success', 'Telefonat wurde protokolliert.');
    }

    /**
     * Reklamation/Problem melden
     */
    public function problem(Request $request, int $gebaeudeId)
    {
        $gebaeude = Gebaeude::findOrFail($gebaeudeId);
        
        $data = $request->validate([
            'typ' => ['required', 'in:reklamation,problem,mangel,schadensmeldung'],
            'beschreibung' => ['required', 'string', 'max:2000'],
            'prioritaet' => ['nullable', 'in:niedrig,normal,hoch,kritisch'],
            'kontakt_person' => ['nullable', 'string', 'max:100'],
        ]);
        
        $typ = match($data['typ']) {
            'reklamation' => GebaeudeLogTyp::REKLAMATION,
            'problem' => GebaeudeLogTyp::PROBLEM,
            'mangel' => GebaeudeLogTyp::MANGEL,
            'schadensmeldung' => GebaeudeLogTyp::SCHADENSMELDUNG,
        };
        
        $log = GebaeudeLog::create([
            'gebaeude_id' => $gebaeude->id,
            'typ' => $typ,
            'titel' => $typ->label(),
            'beschreibung' => $data['beschreibung'],
            'user_id' => Auth::id(),
            'prioritaet' => $data['prioritaet'] ?? 'hoch',
            'kontakt_person' => $data['kontakt_person'] ?? null,
        ]);
        
        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => $typ->label() . ' erfasst',
                'log' => $log,
            ]);
        }
        
        return back()->with('success', $typ->label() . ' wurde erfasst.');
    }

    /**
     * Erinnerung erstellen
     */
    public function erinnerung(Request $request, int $gebaeudeId)
    {
        $gebaeude = Gebaeude::findOrFail($gebaeudeId);
        
        $data = $request->validate([
            'beschreibung' => ['required', 'string', 'max:2000'],
            'erinnerung_datum' => ['required', 'date', 'after_or_equal:today'],
            'prioritaet' => ['nullable', 'in:niedrig,normal,hoch,kritisch'],
        ]);
        
        $log = GebaeudeLog::create([
            'gebaeude_id' => $gebaeude->id,
            'typ' => GebaeudeLogTyp::ERINNERUNG,
            'titel' => 'Erinnerung',
            'beschreibung' => $data['beschreibung'],
            'user_id' => Auth::id(),
            'prioritaet' => $data['prioritaet'] ?? 'normal',
            'erinnerung_datum' => $data['erinnerung_datum'],
            'erinnerung_erledigt' => false,
        ]);
        
        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Erinnerung erstellt fuer ' . \Carbon\Carbon::parse($data['erinnerung_datum'])->format('d.m.Y'),
                'log' => $log,
            ]);
        }
        
        return back()->with('success', 'Erinnerung wurde fuer den ' . \Carbon\Carbon::parse($data['erinnerung_datum'])->format('d.m.Y') . ' erstellt.');
    }

    /**
     * Alle offenen Erinnerungen (Dashboard-Widget)
     */
    public function alleErinnerungen()
    {
        $erinnerungen = GebaeudeLog::with('gebaeude')
            ->offeneErinnerungen()
            ->orderBy('erinnerung_datum')
            ->limit(50)
            ->get();
        
        if (request()->expectsJson()) {
            return response()->json([
                'ok' => true,
                'erinnerungen' => $erinnerungen,
            ]);
        }
        
        return view('gebaeude.logs.erinnerungen', compact('erinnerungen'));
    }
}
