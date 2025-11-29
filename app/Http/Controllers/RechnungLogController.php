<?php

namespace App\Http\Controllers;

use App\Models\Rechnung;
use App\Models\RechnungLog;
use App\Enums\RechnungLogTyp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RechnungLogController extends Controller
{
    // ═══════════════════════════════════════════════════════════
    // 📋 INDEX / LISTE
    // ═══════════════════════════════════════════════════════════

    /**
     * Alle Logs für eine Rechnung anzeigen
     */
    public function index(int $rechnungId, Request $request)
    {
        $rechnung = Rechnung::with('positionen')->findOrFail($rechnungId);
        
        $query = RechnungLog::where('rechnung_id', $rechnungId)
            ->with('user')
            ->chronologisch();
        
        // Filter nach Kategorie
        if ($request->filled('kategorie')) {
            $query->kategorie($request->kategorie);
        }
        
        // Filter nach Typ
        if ($request->filled('typ')) {
            $typ = RechnungLogTyp::tryFrom($request->typ);
            if ($typ) {
                $query->vonTyp($typ);
            }
        }
        
        // Filter nach Datum
        if ($request->filled('von')) {
            $query->whereDate('created_at', '>=', $request->von);
        }
        if ($request->filled('bis')) {
            $query->whereDate('created_at', '<=', $request->bis);
        }
        
        $logs = $query->paginate(50);
        
        // Statistiken
        $stats = [
            'gesamt' => RechnungLog::where('rechnung_id', $rechnungId)->count(),
            'dokumente' => RechnungLog::where('rechnung_id', $rechnungId)->kategorie('dokument')->count(),
            'kommunikation' => RechnungLog::where('rechnung_id', $rechnungId)->kategorie('kommunikation')->count(),
            'offene_erinnerungen' => RechnungLog::where('rechnung_id', $rechnungId)->offeneErinnerungen()->count(),
        ];
        
        if ($request->expectsJson()) {
            return response()->json([
                'logs' => $logs,
                'stats' => $stats,
            ]);
        }
        
        return view('rechnung.logs.index', [
            'rechnung' => $rechnung,
            'logs' => $logs,
            'stats' => $stats,
            'kategorien' => RechnungLogTyp::kategorien(),
            'filter' => $request->only(['kategorie', 'typ', 'von', 'bis']),
        ]);
    }

    // ═══════════════════════════════════════════════════════════
    // ➕ STORE / ERSTELLEN
    // ═══════════════════════════════════════════════════════════

    /**
     * Neuen Log-Eintrag erstellen
     */
    public function store(Request $request, int $rechnungId)
    {
        $rechnung = Rechnung::findOrFail($rechnungId);
        
        $validated = $request->validate([
            'typ' => ['required', 'string'],
            'titel' => ['nullable', 'string', 'max:255'],
            'beschreibung' => ['nullable', 'string', 'max:5000'],
            'kontakt_person' => ['nullable', 'string', 'max:255'],
            'kontakt_telefon' => ['nullable', 'string', 'max:50'],
            'kontakt_email' => ['nullable', 'email', 'max:255'],
            'prioritaet' => ['nullable', 'in:niedrig,normal,hoch,kritisch'],
            'erinnerung_datum' => ['nullable', 'date'],
        ]);
        
        $typ = RechnungLogTyp::tryFrom($validated['typ']);
        
        if (!$typ) {
            return back()->withErrors(['typ' => 'Ungültiger Log-Typ']);
        }
        
        $log = RechnungLog::create([
            'rechnung_id' => $rechnung->id,
            'typ' => $typ,
            'titel' => $validated['titel'] ?? $typ->label(),
            'beschreibung' => $validated['beschreibung'],
            'user_id' => Auth::id(),
            'kontakt_person' => $validated['kontakt_person'] ?? null,
            'kontakt_telefon' => $validated['kontakt_telefon'] ?? null,
            'kontakt_email' => $validated['kontakt_email'] ?? null,
            'prioritaet' => $validated['prioritaet'] ?? 'normal',
            'erinnerung_datum' => $validated['erinnerung_datum'] ?? null,
            'erinnerung_erledigt' => false,
        ]);
        
        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Log-Eintrag erstellt',
                'log' => $log,
            ]);
        }
        
        return back()->with('success', 'Log-Eintrag wurde hinzugefügt.');
    }

    // ═══════════════════════════════════════════════════════════
    // ✏️ UPDATE / BEARBEITEN
    // ═══════════════════════════════════════════════════════════

    /**
     * Log-Eintrag aktualisieren
     */
    public function update(Request $request, int $logId)
    {
        $log = RechnungLog::findOrFail($logId);
        
        $validated = $request->validate([
            'beschreibung' => ['nullable', 'string', 'max:5000'],
            'kontakt_person' => ['nullable', 'string', 'max:255'],
            'kontakt_telefon' => ['nullable', 'string', 'max:50'],
            'kontakt_email' => ['nullable', 'email', 'max:255'],
            'prioritaet' => ['nullable', 'in:niedrig,normal,hoch,kritisch'],
            'erinnerung_datum' => ['nullable', 'date'],
            'erinnerung_erledigt' => ['nullable', 'boolean'],
        ]);
        
        $log->update($validated);
        
        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Log-Eintrag aktualisiert',
                'log' => $log->fresh(),
            ]);
        }
        
        return back()->with('success', 'Log-Eintrag wurde aktualisiert.');
    }

    // ═══════════════════════════════════════════════════════════
    // 🗑️ DELETE / LÖSCHEN
    // ═══════════════════════════════════════════════════════════

    /**
     * Log-Eintrag löschen
     */
    public function destroy(int $logId)
    {
        $log = RechnungLog::findOrFail($logId);
        $rechnungId = $log->rechnung_id;
        
        $log->delete();
        
        if (request()->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Log-Eintrag gelöscht',
            ]);
        }
        
        return back()->with('success', 'Log-Eintrag wurde gelöscht.');
    }

    // ═══════════════════════════════════════════════════════════
    // ✅ ERINNERUNG ERLEDIGEN
    // ═══════════════════════════════════════════════════════════

    /**
     * Erinnerung als erledigt markieren
     */
    public function erinnerungErledigt(int $logId)
    {
        $log = RechnungLog::findOrFail($logId);
        
        $log->update(['erinnerung_erledigt' => true]);
        
        if (request()->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Erinnerung als erledigt markiert',
            ]);
        }
        
        return back()->with('success', 'Erinnerung wurde als erledigt markiert.');
    }

    // ═══════════════════════════════════════════════════════════
    // 📊 QUICK-ACTIONS
    // ═══════════════════════════════════════════════════════════

    /**
     * Schnell: Telefonat hinzufügen
     */
    public function quickTelefonat(Request $request, int $rechnungId)
    {
        $validated = $request->validate([
            'beschreibung' => ['required', 'string', 'max:5000'],
            'kontakt_person' => ['nullable', 'string', 'max:255'],
            'kontakt_telefon' => ['nullable', 'string', 'max:50'],
            'richtung' => ['nullable', 'in:eingehend,ausgehend'],
        ]);
        
        $typ = match($validated['richtung'] ?? 'ausgehend') {
            'eingehend' => RechnungLogTyp::TELEFONAT_EINGEHEND,
            'ausgehend' => RechnungLogTyp::TELEFONAT_AUSGEHEND,
            default => RechnungLogTyp::TELEFONAT,
        };
        
        $log = RechnungLog::telefonat(
            $rechnungId,
            $validated['beschreibung'],
            $validated['kontakt_person'] ?? null,
            $validated['kontakt_telefon'] ?? null,
            $typ
        );
        
        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'log' => $log]);
        }
        
        return back()->with('success', 'Telefonat wurde dokumentiert.');
    }

    /**
     * Schnell: Notiz hinzufügen
     */
    public function quickNotiz(Request $request, int $rechnungId)
    {
        $validated = $request->validate([
            'beschreibung' => ['required', 'string', 'max:5000'],
            'prioritaet' => ['nullable', 'in:niedrig,normal,hoch,kritisch'],
            'erinnerung_datum' => ['nullable', 'date'],
        ]);
        
        $log = RechnungLog::notiz(
            $rechnungId,
            $validated['beschreibung'],
            $validated['prioritaet'] ?? 'normal',
            $validated['erinnerung_datum'] ?? null
        );
        
        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'log' => $log]);
        }
        
        return back()->with('success', 'Notiz wurde hinzugefügt.');
    }

    /**
     * Schnell: Kundenmitteilung hinzufügen
     */
    public function quickMitteilung(Request $request, int $rechnungId)
    {
        $validated = $request->validate([
            'beschreibung' => ['required', 'string', 'max:5000'],
            'kontakt_person' => ['nullable', 'string', 'max:255'],
            'kontakt_email' => ['nullable', 'email', 'max:255'],
        ]);
        
        $log = RechnungLog::mitteilungKunde(
            $rechnungId,
            $validated['beschreibung'],
            $validated['kontakt_person'] ?? null,
            $validated['kontakt_email'] ?? null
        );
        
        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'log' => $log]);
        }
        
        return back()->with('success', 'Kundenmitteilung wurde dokumentiert.');
    }

    // ═══════════════════════════════════════════════════════════
    // 📈 DASHBOARD / ÜBERSICHT
    // ═══════════════════════════════════════════════════════════

    /**
     * Globale Übersicht aller offenen Erinnerungen
     */
    public function dashboard()
    {
        $offeneErinnerungen = RechnungLog::with(['rechnung', 'user'])
            ->offeneErinnerungen()
            ->orderBy('erinnerung_datum')
            ->limit(50)
            ->get();
        
        $zukuenftigeErinnerungen = RechnungLog::with(['rechnung', 'user'])
            ->zukuenftigeErinnerungen()
            ->orderBy('erinnerung_datum')
            ->limit(20)
            ->get();
        
        $letzteAktivitaeten = RechnungLog::with(['rechnung', 'user'])
            ->chronologisch()
            ->limit(30)
            ->get();
        
        return view('rechnung.logs.dashboard', [
            'offeneErinnerungen' => $offeneErinnerungen,
            'zukuenftigeErinnerungen' => $zukuenftigeErinnerungen,
            'letzteAktivitaeten' => $letzteAktivitaeten,
        ]);
    }
}