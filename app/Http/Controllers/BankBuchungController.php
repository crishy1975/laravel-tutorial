<?php

namespace App\Http\Controllers;

use App\Models\BankBuchung;
use App\Models\BankImportLog;
use App\Models\Rechnung;
use App\Models\BankMatchingConfig;
use App\Services\BankImportService;
use App\Services\BankMatchingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BankBuchungController extends Controller
{
    protected BankMatchingService $matchingService;

    public function __construct(BankMatchingService $matchingService)
    {
        $this->matchingService = $matchingService;
    }

    /**
     * Uebersicht aller Buchungen
     */
    public function index(Request $request)
    {
        $query = BankBuchung::query()
            ->orderByDesc('buchungsdatum')
            ->orderByDesc('id');

        // Filter: Typ
        if ($request->filled('typ')) {
            $query->where('typ', $request->typ);
        }

        // Filter: Status
        if ($request->filled('status')) {
            $query->where('match_status', $request->status);
        }

        // Filter: Zeitraum
        if ($request->filled('von')) {
            $query->where('buchungsdatum', '>=', $request->von);
        }
        if ($request->filled('bis')) {
            $query->where('buchungsdatum', '<=', $request->bis);
        }

        // Filter: Suche
        if ($request->filled('suche')) {
            $suche = $request->suche;
            $query->where(function ($q) use ($suche) {
                $q->where('verwendungszweck', 'like', "%{$suche}%")
                  ->orWhere('gegenkonto_name', 'like', "%{$suche}%")
                  ->orWhere('gegenkonto_iban', 'like', "%{$suche}%");
            });
        }

        $buchungen = $query->with('rechnung')->paginate(50);

        // Statistiken
        $stats = [
            'gesamt'     => BankBuchung::count(),
            'unmatched'  => BankBuchung::where('match_status', 'unmatched')->count(),
            'matched'    => BankBuchung::whereIn('match_status', ['matched', 'manual'])->count(),
            'eingaenge'  => BankBuchung::where('typ', 'CRDT')->sum('betrag'),
            'ausgaenge'  => BankBuchung::where('typ', 'DBIT')->sum('betrag'),
        ];

        return view('bank.index', [
            'buchungen' => $buchungen,
            'stats'     => $stats,
            'filter'    => $request->only(['typ', 'status', 'von', 'bis', 'suche']),
        ]);
    }

    /**
     * Import-Formular anzeigen
     */
    public function importForm()
    {
        $imports = BankImportLog::orderByDesc('created_at')
            ->take(20)
            ->get();

        return view('bank.import', [
            'imports' => $imports,
        ]);
    }

    /**
     * XML-Import durchfuehren
     */
    public function import(Request $request)
    {
        $request->validate([
            'xml_datei' => 'required|file|mimes:xml|max:10240', // max 10MB
        ]);

        $file = $request->file('xml_datei');
        $path = $file->store('bank-imports', 'local');
        $fullPath = Storage::disk('local')->path($path);

        try {
            $service = new BankImportService();
            $result = $service->importFromFile($fullPath);

            if ($result['success']) {
                // Weiterleitung zur Progress-Seite für Auto-Matching
                return redirect()
                    ->route('bank.autoMatchProgress')
                    ->with('success', $result['message']);
            } else {
                return back()
                    ->with('warning', $result['message']);
            }

        } catch (\Exception $e) {
            Log::error('Bank-Import Fehler', [
                'datei' => $file->getClientOriginalName(),
                'error' => $e->getMessage(),
            ]);

            return back()
                ->with('error', 'Import fehlgeschlagen: ' . $e->getMessage());
        }
    }

    /**
     * Einzelne Buchung anzeigen
     */
    public function show(Request $request, BankBuchung $buchung)
    {
        $buchung->load('rechnung.rechnungsempfaenger');

        // Schalter: Auch bezahlte Rechnungen anzeigen? (Standard: NEIN)
        $includePaid = $request->boolean('include_paid', false);

        // Potenzielle Matches mit Scoring
        $potentielleMatches = collect();
        
        if ($buchung->typ === 'CRDT' && $buchung->match_status === 'unmatched') {
            $potentielleMatches = $this->matchingService->findMatches($buchung, 15, $includePaid);
        }

        // Extrahierte Daten für Anzeige
        $extractedData = $this->matchingService->extractMatchingData($buchung);

        return view('bank.show', [
            'buchung'            => $buchung,
            'potentielleMatches' => $potentielleMatches,
            'extractedData'      => $extractedData,
            'autoMatchThreshold' => BankMatchingConfig::get('auto_match_threshold', 80),
            'includePaid'        => $includePaid,
        ]);
    }

    /**
     * Manuell einer Rechnung zuordnen
     */
    public function match(Request $request, BankBuchung $buchung)
    {
        $request->validate([
            'rechnung_id' => 'required|exists:rechnungen,id',
            'mark_paid'   => 'nullable|boolean',
            'save_iban'   => 'nullable|boolean',
        ]);

        $rechnung = Rechnung::findOrFail($request->rechnung_id);
        $markPaid = $request->boolean('mark_paid', true);
        $saveIban = $request->boolean('save_iban', true);

        $result = $this->matchingService->manualMatch($buchung, $rechnung, $markPaid, $saveIban);

        Log::info('Bank-Buchung manuell zugeordnet', [
            'buchung_id'       => $buchung->id,
            'rechnung_id'      => $rechnung->id,
            'score'            => $result['score'],
            'was_already_paid' => $result['was_already_paid'] ?? false,
        ]);

        // Meldung je nach Status
        if ($result['was_already_paid'] ?? false) {
            $message = sprintf(
                'Buchung wurde mit bereits bezahlter Rechnung %s verknüpft (Score: %d).',
                $rechnung->rechnungsnummer,
                $result['score']
            );
        } else {
            $message = sprintf(
                'Buchung wurde Rechnung %s zugeordnet und als bezahlt markiert (Score: %d).',
                $rechnung->rechnungsnummer,
                $result['score']
            );
        }
        
        if ($saveIban && $buchung->gegenkonto_iban && $rechnung->gebaeude_id) {
            $message .= ' IBAN wurde gespeichert.';
        }

        return back()->with('success', $message);
    }

    /**
     * Zuordnung aufheben
     */
    public function unmatch(BankBuchung $buchung)
    {
        $rechnungNr = $buchung->rechnung?->rechnungsnummer ?? '?';
        
        $buchung->rechnung_id = null;
        $buchung->match_status = 'unmatched';
        $buchung->match_info = null;
        $buchung->matched_at = null;
        $buchung->save();

        return back()->with('success', "Zuordnung zu Rechnung {$rechnungNr} aufgehoben.");
    }

    /**
     * Als ignoriert markieren
     */
    public function ignore(Request $request, BankBuchung $buchung)
    {
        $buchung->match_status = 'ignored';
        $buchung->bemerkung = $request->input('bemerkung');
        $buchung->save();

        return back()->with('success', 'Buchung wurde als ignoriert markiert.');
    }

    /**
     * Auto-Matching fuer alle unzugeordneten Buchungen
     */
    public function autoMatchAll()
    {
        $result = $this->matchingService->autoMatchAll();

        if ($result['matched'] > 0) {
            return redirect()
                ->route('bank.matched')
                ->with('success', sprintf('%d Buchungen automatisch zugeordnet.', $result['matched']))
                ->with('match_results', $result['results']);
        }

        return back()->with('info', 'Keine neuen Zuordnungen möglich.');
    }

    /**
     * Nicht zugeordnete Eingaenge anzeigen
     */
    public function unmatched()
    {
        $buchungen = BankBuchung::where('match_status', 'unmatched')
            ->where('typ', 'CRDT')
            ->orderByDesc('buchungsdatum')
            ->paginate(50);

        return view('bank.unmatched', [
            'buchungen' => $buchungen,
        ]);
    }

    /**
     * Übersicht der zugeordneten Buchungen (Kontroll-Ansicht)
     */
    public function matched(Request $request)
    {
        $query = BankBuchung::with(['rechnung.rechnungsempfaenger', 'rechnung.gebaeude'])
            ->whereIn('match_status', ['matched', 'manual'])
            ->orderByDesc('matched_at');

        // Filter: Nur heute/letzte Woche
        if ($request->filled('zeitraum')) {
            if ($request->zeitraum === 'heute') {
                $query->whereDate('matched_at', today());
            } elseif ($request->zeitraum === 'woche') {
                $query->where('matched_at', '>=', now()->subWeek());
            }
        }

        // Filter: Nur Auto oder nur Manuell
        if ($request->filled('typ')) {
            $query->where('match_status', $request->typ);
        }

        $buchungen = $query->paginate(50);

        // Session-Results (von Auto-Match)
        $newResults = session('match_results', []);

        // Statistiken
        $stats = [
            'gesamt'   => BankBuchung::whereIn('match_status', ['matched', 'manual'])->count(),
            'auto'     => BankBuchung::where('match_status', 'matched')->count(),
            'manuell'  => BankBuchung::where('match_status', 'manual')->count(),
            'heute'    => BankBuchung::whereIn('match_status', ['matched', 'manual'])
                            ->whereDate('matched_at', today())->count(),
            'summe'    => BankBuchung::whereIn('match_status', ['matched', 'manual'])
                            ->where('typ', 'CRDT')->sum('betrag'),
        ];

        // Rechnungen als Map für schnellen Zugriff in der View
        $rechnungen = $buchungen->pluck('rechnung')->filter()->keyBy('id');

        return view('bank.matched', [
            'buchungen'  => $buchungen,
            'rechnungen' => $rechnungen,
            'newResults' => $newResults,
            'stats'      => $stats,
            'filter'     => $request->only(['zeitraum', 'typ']),
        ]);
    }

    /**
     * Offene Rechnungen zum Matching anzeigen
     */
    public function matchingOverview(Request $request)
    {
        // Filter-Parameter mit Defaults
        $jahr = $request->input('jahr', now()->year);
        $monate = $request->input('monate', '12'); // Standard: 12 Monate
        $status = $request->input('status', 'offen'); // Standard: nur offene

        // Nicht zugeordnete Eingänge
        $unmatchedBuchungen = BankBuchung::where('match_status', 'unmatched')
            ->where('typ', 'CRDT')
            ->orderByDesc('buchungsdatum')
            ->take(50)
            ->get();

        // Rechnungen-Query mit Filtern
        $query = Rechnung::with('rechnungsempfaenger')
            ->orderByDesc('rechnungsdatum');

        // Jahr-Filter
        if ($jahr) {
            $query->where('jahr', $jahr);
        }

        // Monate-Filter (nur letzte X Monate)
        if ($monate && is_numeric($monate)) {
            $query->where('rechnungsdatum', '>=', now()->subMonths((int) $monate)->startOfMonth());
        }

        // Status-Filter
        if ($status === 'offen') {
            $query->whereIn('status', ['sent', 'draft']);
        }
        // Bei 'alle' kein Status-Filter

        $rechnungen = $query->take(200)->get();

        return view('bank.matching', [
            'buchungen'  => $unmatchedBuchungen,
            'rechnungen' => $rechnungen,
            'filter' => [
                'jahr'   => $jahr,
                'monate' => $monate,
                'status' => $status,
            ],
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // AUTO-MATCH MIT PROGRESS
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Progress-Seite für Auto-Matching anzeigen
     */
    public function autoMatchProgress(Request $request)
    {
        $jahr = (int) $request->input('jahr', now()->year);
        
        $total = BankBuchung::where('match_status', 'unmatched')
            ->where('typ', 'CRDT')
            ->count();

        $rechnungenCount = Rechnung::whereIn('status', ['sent', 'draft'])
            ->where('jahr', $jahr)
            ->count();

        return view('bank.auto-match-progress', [
            'total'           => $total,
            'rechnungenCount' => $rechnungenCount,
            'jahr'            => $jahr,
        ]);
    }

    /**
     * Batch-Verarbeitung für Auto-Matching (AJAX)
     * 
     * Verwendet last_id um Endlosschleifen zu vermeiden (effizienter als ID-Array).
     */
    public function autoMatchBatch(Request $request)
    {
        $batchSize = $request->input('batch_size', 10);
        $lastId = $request->input('last_id', 0);
        $jahr = (int) $request->input('jahr', now()->year);

        // Unzugeordnete Buchungen holen, ID > last_id
        $buchungen = BankBuchung::where('match_status', 'unmatched')
            ->where('typ', 'CRDT')
            ->where('id', '>', $lastId)
            ->orderBy('id')
            ->take($batchSize)
            ->get();

        $matched = 0;
        $results = [];
        $newLastId = $lastId;

        foreach ($buchungen as $buchung) {
            $newLastId = $buchung->id;  // Letzte geprüfte ID
            
            // ⭐ Jahr an tryAutoMatch übergeben
            $result = $this->matchingService->tryAutoMatch($buchung, $jahr);

            if ($result['matched'] && $result['rechnung']) {
                $this->matchingService->executeMatch(
                    $buchung, 
                    $result['rechnung'], 
                    $result['score'], 
                    $result['details']
                );
                $matched++;

                $results[] = [
                    'buchung_id'      => $buchung->id,
                    'rechnung_id'     => $result['rechnung']->id,
                    'rechnungsnummer' => $result['rechnung']->rechnungsnummer,
                    'betrag'          => $buchung->betrag,
                    'score'           => $result['score'],
                ];
            }
        }

        // Verbleibende (ID > newLastId und unmatched)
        $remaining = BankBuchung::where('match_status', 'unmatched')
            ->where('typ', 'CRDT')
            ->where('id', '>', $newLastId)
            ->count();

        return response()->json([
            'processed' => $buchungen->count(),
            'matched'   => $matched,
            'remaining' => $remaining,
            'results'   => $results,
            'last_id'   => $newLastId,
            'done'      => $buchungen->isEmpty(),
        ]);
    }

    /**
     * Status für Auto-Matching (AJAX)
     */
    public function autoMatchStatus()
    {
        $unmatched = BankBuchung::where('match_status', 'unmatched')
            ->where('typ', 'CRDT')
            ->count();

        $matched = BankBuchung::whereIn('match_status', ['matched', 'manual'])->count();

        return response()->json([
            'unmatched' => $unmatched,
            'matched'   => $matched,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // KONFIGURATION
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Konfiguration anzeigen
     */
    public function config()
    {
        $config = BankMatchingConfig::getConfig();
        $descriptions = BankMatchingConfig::getFieldDescriptions();

        return view('bank.config', [
            'config'       => $config,
            'descriptions' => $descriptions,
        ]);
    }

    /**
     * Konfiguration speichern
     */
    public function updateConfig(Request $request)
    {
        $config = BankMatchingConfig::getConfig();

        $validated = $request->validate([
            'score_iban_match'         => 'required|integer|min:0|max:500',
            'score_cig_match'          => 'required|integer|min:0|max:500',
            'score_rechnungsnr_match'  => 'required|integer|min:0|max:500',
            'score_betrag_exakt'       => 'required|integer|min:0|max:500',
            'score_betrag_nah'         => 'required|integer|min:0|max:500',
            'score_betrag_abweichung'  => 'required|integer|max:0',
            'score_name_token_exact'   => 'required|integer|min:0|max:500',
            'score_name_token_partial' => 'required|integer|min:0|max:500',
            'auto_match_threshold'     => 'required|integer|min:1|max:500',
            'betrag_abweichung_limit'  => 'required|integer|min:1|max:100',
            'betrag_toleranz_exakt'    => 'required|numeric|min:0|max:100',
            'betrag_toleranz_nah'      => 'required|numeric|min:0|max:100',
        ]);

        $config->update($validated);

        Log::info('Bank-Matching-Konfiguration aktualisiert', $validated);

        return redirect()
            ->route('bank.config')
            ->with('success', 'Konfiguration gespeichert.');
    }

    /**
     * Konfiguration auf Standard zurücksetzen
     */
    public function resetConfig()
    {
        $config = BankMatchingConfig::getConfig();
        
        $config->update([
            'score_iban_match'         => 100,
            'score_cig_match'          => 80,
            'score_rechnungsnr_match'  => 50,
            'score_betrag_exakt'       => 30,
            'score_betrag_nah'         => 15,
            'score_betrag_abweichung'  => -40,
            'score_name_token_exact'   => 10,
            'score_name_token_partial' => 5,
            'auto_match_threshold'     => 80,
            'betrag_abweichung_limit'  => 30,
            'betrag_toleranz_exakt'    => 0.10,
            'betrag_toleranz_nah'      => 2.00,
        ]);

        Log::info('Bank-Matching-Konfiguration auf Standard zurückgesetzt');

        return redirect()
            ->route('bank.config')
            ->with('success', 'Konfiguration auf Standard-Werte zurückgesetzt.');
    }
}
