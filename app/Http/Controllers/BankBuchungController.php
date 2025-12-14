<?php

namespace App\Http\Controllers;

use App\Models\BankBuchung;
use App\Models\BankImportLog;
use App\Models\Rechnung;
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
                // Nach Import: Auto-Matching durchführen
                $matchResult = $this->matchingService->autoMatchAll();
                
                $message = $result['message'];
                if ($matchResult['matched'] > 0) {
                    $message .= sprintf(' Davon %d automatisch zugeordnet.', $matchResult['matched']);
                }

                return redirect()
                    ->route('bank.matched')
                    ->with('success', $message)
                    ->with('match_results', $matchResult['results']);
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
    public function show(BankBuchung $buchung)
    {
        $buchung->load('rechnung.rechnungsempfaenger');

        // Potenzielle Matches mit Scoring
        $potentielleMatches = collect();
        
        if ($buchung->typ === 'CRDT' && $buchung->match_status === 'unmatched') {
            $potentielleMatches = $this->matchingService->findMatches($buchung, 15);
        }

        // Extrahierte Daten für Anzeige
        $extractedData = $this->matchingService->extractMatchingData($buchung);

        return view('bank.show', [
            'buchung'            => $buchung,
            'potentielleMatches' => $potentielleMatches,
            'extractedData'      => $extractedData,
            'autoMatchThreshold' => BankMatchingService::AUTO_MATCH_THRESHOLD,
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

        return view('bank.matched', [
            'buchungen'  => $buchungen,
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
        // Nicht zugeordnete Eingaenge
        $unmatchedBuchungen = BankBuchung::where('match_status', 'unmatched')
            ->where('typ', 'CRDT')
            ->orderByDesc('buchungsdatum')
            ->take(50)
            ->get();

        // Rechnungen (mit oder ohne bezahlte)
        $query = Rechnung::with('rechnungsempfaenger')
            ->orderByDesc('rechnungsdatum');
        
        if ($request->boolean('show_paid')) {
            // Alle Rechnungen
            $query->take(200);
        } else {
            // Nur offene
            $query->whereIn('status', ['sent', 'draft'])
                  ->take(100);
        }
        
        $rechnungen = $query->get();

        return view('bank.matching', [
            'buchungen'  => $unmatchedBuchungen,
            'rechnungen' => $rechnungen,
        ]);
    }
}
