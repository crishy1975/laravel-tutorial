<?php

namespace App\Http\Controllers;

use App\Models\Rechnung;
use App\Models\Gebaeude;
use App\Models\Adresse;
use App\Models\FatturaProfile;
use App\Models\RechnungPosition;
use App\Models\FatturaXmlLog;
use App\Models\RechnungLog;
use App\Models\Unternehmensprofil;
use App\Models\GebaeudeLog;
use App\Enums\Zahlungsbedingung;
use App\Enums\RechnungLogTyp;
use App\Services\FatturaXmlGenerator;
use App\Mail\RechnungMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Barryvdh\DomPDF\Facade\Pdf;

class RechnungController extends Controller
{
    /**
     * Liste aller Rechnungen mit Filter und Statistiken.
     */
    public function index(Request $request)
    {
        // ──────────────────────────────────────────────────────────────────────────
        // 1. Filterwerte aus Request holen
        // ──────────────────────────────────────────────────────────────────────────
        $nummer = $request->input('nummer');
        $codex  = $request->input('codex');
        $suche  = $request->input('suche');

        $year = Carbon::now()->year;

        $datumVon = $request->input('datum_von')
            ?: Carbon::create($year, 1, 1)->format('Y-m-d');

        $datumBis = $request->input('datum_bis')
            ?: Carbon::create($year, 12, 31)->format('Y-m-d');

        // Status-Filter
        $statusFilter = $request->input('status_filter');

        // ──────────────────────────────────────────────────────────────────────────
        // 2. Basis-Query aufbauen
        // ──────────────────────────────────────────────────────────────────────────
        $query = Rechnung::query()->with('gebaeude');

        // ──────────────────────────────────────────────────────────────────────────
        // 3. Filter: Rechnungsnummer
        // ──────────────────────────────────────────────────────────────────────────
        if (!empty($nummer)) {
            $like = '%' . $nummer . '%';
            $query->whereRaw(
                "CONCAT(jahr, '/', LPAD(laufnummer, 4, '0')) LIKE ?",
                [$like]
            );
        }

        // ──────────────────────────────────────────────────────────────────────────
        // 4. Filter: Codex
        // ──────────────────────────────────────────────────────────────────────────
        if (!empty($codex)) {
            $like = '%' . $codex . '%';
            $query->where(function ($q) use ($like) {
                $q->where('geb_codex', 'like', $like)
                    ->orWhereHas('gebaeude', function ($sub) use ($like) {
                        $sub->where('codex', 'like', $like);
                    });
            });
        }

        // ──────────────────────────────────────────────────────────────────────────
        // 5. Filter: Suche (inkl. E-Mail)
        // ──────────────────────────────────────────────────────────────────────────
        if (!empty($suche)) {
            $like = '%' . $suche . '%';
            $query->where(function ($q) use ($like) {
                $q->where('geb_name', 'like', $like)
                    ->orWhere('re_name', 'like', $like)
                    ->orWhere('post_name', 'like', $like)
                    ->orWhere('post_email', 'like', $like);
            });
        }

        // ──────────────────────────────────────────────────────────────────────────
        // 6. Filter: Datum
        // ──────────────────────────────────────────────────────────────────────────
        if (!empty($datumVon) && !empty($datumBis)) {
            $query->whereBetween('rechnungsdatum', [$datumVon, $datumBis]);
        } elseif (!empty($datumVon)) {
            $query->whereDate('rechnungsdatum', '>=', $datumVon);
        } elseif (!empty($datumBis)) {
            $query->whereDate('rechnungsdatum', '<=', $datumBis);
        }

        // ──────────────────────────────────────────────────────────────────────────
        // 7. Filter: Zahlungsstatus
        // ──────────────────────────────────────────────────────────────────────────
        if (!empty($statusFilter)) {
            switch ($statusFilter) {
                case 'unbezahlt':
                    $query->unbezahlt();
                    break;
                case 'bezahlt':
                    $query->bezahlt();
                    break;
                case 'ueberfaellig':
                    $query->ueberfaellig();
                    break;
                case 'bald_faellig':
                    $query->baldFaellig(7);
                    break;
                case 'offen':
                    $query->offen();
                    break;
            }
        }

        // ──────────────────────────────────────────────────────────────────────────
        // 8. Sortierung & Pagination (nach Rechnungsnummer absteigend)
        // ──────────────────────────────────────────────────────────────────────────
        $rechnungen = $query
            ->orderByDesc('jahr')
            ->orderByDesc('laufnummer')
            ->paginate(25);

        // ──────────────────────────────────────────────────────────────────────────
        // 9. ⭐ ERWEITERTE Statistiken berechnen
        // ──────────────────────────────────────────────────────────────────────────
        $aktuellesJahr = $year;
        $vorjahr = $year - 1;
        $vorvorjahr = $year - 2;

        // Jahresvergleich-Daten
        $jahresvergleich = [];

        foreach ([$aktuellesJahr, $vorjahr, $vorvorjahr] as $statJahr) {
            // Rechnungen (ohne Gutschriften, ohne Stornierte)
            $jahresRechnungen = Rechnung::whereYear('rechnungsdatum', $statJahr)
                ->where('typ_rechnung', '!=', 'gutschrift')
                ->where('status', '!=', 'cancelled');

            $brutto = (clone $jahresRechnungen)->sum('brutto_summe');
            $netto = (clone $jahresRechnungen)->sum('netto_summe');
            $anzahl = (clone $jahresRechnungen)->count();

            // Gutschriften für dieses Jahr
            $gutschriften = Rechnung::whereYear('rechnungsdatum', $statJahr)
                ->where('typ_rechnung', 'gutschrift')
                ->where('status', '!=', 'cancelled')
                ->sum('brutto_summe');

            // Bezahlt/Offen
            $jahresBezahlt = (clone $jahresRechnungen)->bezahlt();
            $jahresOffen = (clone $jahresRechnungen)->unbezahlt();

            $jahresvergleich[$statJahr] = [
                'anzahl'       => $anzahl,
                'netto'        => $netto,
                'brutto'       => $brutto,
                'gutschriften' => $gutschriften,
                'umsatz'       => $brutto - $gutschriften,  // ⭐ Effektiver Umsatz
                'bezahlt'      => $jahresBezahlt->sum('brutto_summe'),
                'offen'        => $jahresOffen->sum('brutto_summe'),
            ];
        }

        $stats = [
            // Jahre
            'aktuelles_jahr' => $aktuellesJahr,
            'vorjahr'        => $vorjahr,
            'vorvorjahr'     => $vorvorjahr,

            // ⭐ Umsatz aktuelles Jahr (konsistent mit Jahresvergleich)
            'umsatz_aktuell' => $jahresvergleich[$aktuellesJahr]['umsatz'] ?? 0,
            'anzahl_aktuell' => $jahresvergleich[$aktuellesJahr]['anzahl'] ?? 0,

            // ⭐ Umsatz Vorjahr (konsistent mit Jahresvergleich)
            'umsatz_vorjahr' => $jahresvergleich[$vorjahr]['umsatz'] ?? 0,
            'anzahl_vorjahr' => $jahresvergleich[$vorjahr]['anzahl'] ?? 0,

            // Offene/Unbezahlte Rechnungen (alle Jahre)
            'unbezahlt_anzahl' => Rechnung::unbezahlt()->where('status', '!=', 'cancelled')->count(),
            'unbezahlt_summe'  => Rechnung::unbezahlt()->where('status', '!=', 'cancelled')->sum('brutto_summe'),

            // Überfällige Rechnungen
            'ueberfaellig_anzahl' => Rechnung::ueberfaellig()->count(),
            'ueberfaellig_summe'  => Rechnung::ueberfaellig()->sum('brutto_summe'),

            // Bald fällig (7 Tage)
            'bald_faellig_anzahl' => Rechnung::baldFaellig(7)->count(),
            'bald_faellig_summe'  => Rechnung::baldFaellig(7)->sum('brutto_summe'),

            // Jahresvergleich-Tabelle
            'jahresvergleich' => $jahresvergleich,
        ];

        // ──────────────────────────────────────────────────────────────────────────
        // 10. View zurueckgeben
        // ──────────────────────────────────────────────────────────────────────────
        return view('rechnung.index', [
            'rechnungen'    => $rechnungen,
            'nummer'        => $nummer,
            'codex'         => $codex,
            'suche'         => $suche,
            'datumVon'      => $datumVon,
            'datumBis'      => $datumBis,
            'statusFilter'  => $statusFilter,
            'stats'         => $stats,
        ]);
    }
   

    // ═══════════════════════════════════════════════════════════════════════════════
    // 🔍 INTEGRITÄTSPRÜFUNG & VALIDIERUNG (NEU)
    // ═══════════════════════════════════════════════════════════════════════════════

    /**
     * Integritäts-Report Seite anzeigen.
     * 
     * Zeigt:
     * - Lücken in Rechnungsnummern
     * - Mögliche Duplikate (gleicher Betrag auf gleiches Gebäude)
     * 
     * Route: GET /rechnung/integritaet
     */
    public function integritaetsReport(Request $request)
    {
        $jahr = $request->input('jahr', now()->year);
        $report = Rechnung::getIntegritaetsReport($jahr);

        // Verfügbare Jahre für Dropdown
        $verfuegbareJahre = Rechnung::selectRaw('DISTINCT jahr')
            ->orderByDesc('jahr')
            ->pluck('jahr')
            ->toArray();

        // Falls keine Rechnungen existieren, aktuelles Jahr + 2 Vorjahre
        if (empty($verfuegbareJahre)) {
            $verfuegbareJahre = [now()->year, now()->year - 1, now()->year - 2];
        }

        return view('rechnung.integritaet', compact('report', 'jahr', 'verfuegbareJahre'));
    }

    /**
     * AJAX Endpoint für Live-Validierung im Formular.
     * 
     * Prüft vor dem Erstellen auf:
     * - Mögliche Duplikate
     * - Lücken in Rechnungsnummern
     * 
     * Route: POST /rechnung/validate
     */
    public function validateBeforeCreate(Request $request)
    {
        $gebaeudeId = $request->input('gebaeude_id');
        $betrag = $request->input('betrag');
        $jahr = $request->input('jahr', now()->year);

        $warnings = [];

        // Duplikat-Prüfung
        if ($gebaeudeId && $betrag) {
            $duplikat = Rechnung::pruefeDuplikat((int) $gebaeudeId, (float) $betrag, (int) $jahr);
            if ($duplikat['is_duplicate']) {
                $warnings[] = [
                    'type' => 'duplicate',
                    'message' => $duplikat['message'],
                    'existing_id' => $duplikat['existing']->id ?? null,
                    'existing_nummer' => $duplikat['existing']?->rechnungsnummer,
                ];
            }
        }

        // Lücken-Prüfung für nächste Nummer
        $naechsteNummer = Rechnung::getNextLaufnummer((int) $jahr);
        $luecke = Rechnung::pruefeLaufnummerLuecke((int) $jahr, $naechsteNummer);
        if ($luecke['has_gap']) {
            $warnings[] = [
                'type' => 'gap',
                'message' => $luecke['message'],
                'missing' => $luecke['missing'],
            ];
        }

        return response()->json([
            'warnings' => $warnings,
            'next_number' => $naechsteNummer,
            'next_rechnungsnummer' => $jahr . '/' . str_pad($naechsteNummer, 4, '0', STR_PAD_LEFT),
        ]);
    }

    /**
     * Prüft Duplikate für ein bestimmtes Gebäude.
     * 
     * Route: GET /rechnung/duplikate/{gebaeudeId}
     */
    public function duplikatePruefen(Request $request, int $gebaeudeId)
    {
        $jahr = $request->input('jahr');

        $duplikate = Rechnung::findeDuplikate($gebaeudeId, $jahr);
        $gebaeude = Gebaeude::find($gebaeudeId);

        return response()->json([
            'gebaeude_id' => $gebaeudeId,
            'gebaeude_name' => $gebaeude?->gebaeude_name ?? $gebaeude?->codex,
            'jahr' => $jahr,
            'duplikate' => $duplikate,
            'hat_duplikate' => $duplikate->isNotEmpty(),
        ]);
    }

    /**
     * Prüft Lücken in Rechnungsnummern für ein Jahr.
     * 
     * Route: GET /rechnung/luecken/{jahr?}
     */
    public function lueckenPruefen(Request $request, ?int $jahr = null)
    {
        $jahr = $jahr ?? $request->input('jahr', now()->year);

        $luecken = Rechnung::findeAlleLuecken($jahr);

        return response()->json([
            'jahr' => $jahr,
            'luecken' => $luecken,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════════════
    // CREATE / STORE - Neue Rechnung erstellen
    // ═══════════════════════════════════════════════════════════════════════════════

    /**
     * Neue Rechnung aus einem Gebaeude erstellen.
     * 
     * Prueft auf:
     * - FatturaPA-Profil
     * - Rechnungsempfaenger
     * - Postadresse
     */
    public function create(Request $request)
    {
        if (!$request->filled('gebaeude_id')) {
            return redirect()
                ->route('rechnung.index')
                ->with('error', 'Bitte waehlen Sie zuerst ein Gebaeude aus.');
        }

        try {
            $gebaeude = Gebaeude::findOrFail($request->integer('gebaeude_id'));

            // Pruefungen mit session('warning')
            $fehlend = [];

            if (!$gebaeude->rechnungsempfaenger_id) {
                $fehlend[] = 'Rechnungsempfaenger';
            }
            if (!$gebaeude->postadresse_id) {
                $fehlend[] = 'Postadresse';
            }
            if (!$gebaeude->fattura_profile_id) {
                $fehlend[] = 'FatturaPA-Profil';
            }

            if (!empty($fehlend)) {
                return redirect()
                    ->route('gebaeude.edit', $gebaeude->id)
                    ->with('warning', 'Neue Rechnung nicht moeglich! Bitte fuellen Sie folgende Felder aus: ' . implode(', ', $fehlend) . '.');
            }

            $rechnung = Rechnung::createFromGebaeude($gebaeude);

            // ⭐ Validierung NACH Erstellung (mit tatsächlichen Werten)
            $warnings = [];

            Log::info('=== RECHNUNGS-VALIDIERUNG START ===', [
                'rechnung_id' => $rechnung->id,
                'rechnung_nummer' => $rechnung->rechnungsnummer,
                'gebaeude_id' => $gebaeude->id,
                'brutto_summe' => $rechnung->brutto_summe,
                'jahr' => $rechnung->jahr,
            ]);

            // 1. Duplikat-Prüfung (gleicher Betrag auf gleiches Gebäude)
            $duplikat = Rechnung::pruefeDuplikat(
                $gebaeude->id,
                (float) $rechnung->brutto_summe,
                $rechnung->jahr,
                $rechnung->id  // Eigene ID ausschließen
            );

            Log::info('Duplikat-Prüfung Ergebnis', [
                'is_duplicate' => $duplikat['is_duplicate'],
                'message' => $duplikat['message'],
            ]);

            if ($duplikat['is_duplicate']) {
                $warnings[] = $duplikat['message'];
            }

            // 2. Lücken-Prüfung (alle fehlenden Nummern im Jahr)
            $luecken = Rechnung::findeAlleLuecken($rechnung->jahr);

            Log::info('Lücken-Prüfung Ergebnis', [
                'has_gaps' => $luecken['has_gaps'],
                'missing' => $luecken['missing'] ?? [],
                'message' => $luecken['message'],
            ]);

            if ($luecken['has_gaps']) {
                $warnings[] = $luecken['message'];
            }

            Log::info('=== RECHNUNGS-VALIDIERUNG ENDE ===', [
                'warnings_count' => count($warnings),
                'warnings' => $warnings,
            ]);

            // Warnungen anzeigen
            $redirect = redirect()->route('rechnung.edit', $rechnung->id);

            if (!empty($warnings)) {
                session()->flash('rechnung_warnings', $warnings);
                return $redirect->with('warning', implode(' | ', $warnings));
            }

            return $redirect->with('success', "Rechnung {$rechnung->rechnungsnummer} wurde aus Gebaeude {$gebaeude->codex} erstellt.");
        } catch (\Exception $e) {
            Log::error('Fehler beim Erstellen der Rechnung aus Gebaeude', [
                'gebaeude_id' => $request->integer('gebaeude_id'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()
                ->back()
                ->with('error', 'Fehler beim Erstellen der Rechnung: ' . $e->getMessage());
        }
    }

    /**
     * Neue Rechnung speichern.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'gebaeude_id'        => 'required|exists:gebaeude,id',
            'rechnungsdatum'     => 'required|date',
            'zahlungsbedingungen' => 'nullable|in:sofort,netto_7,netto_14,netto_30,netto_60,netto_90,netto_120',
            'zahlungsziel'       => 'nullable|date',
            'status'             => 'nullable|in:draft,sent,paid,overdue,cancelled',
            'typ_rechnung'       => 'required|in:rechnung,gutschrift',
            'bezahlt_am'         => 'nullable|date',
            'leistungsdaten'     => 'nullable|string|max:255',
            'fattura_profile_id' => 'nullable|exists:fattura_profile,id',
            'cup'                => 'nullable|string|max:20',
            'cig'                => 'nullable|string|max:10',
            'codice_commessa'    => 'nullable|string|max:100',
            'auftrag_id'         => 'nullable|string|max:50',
            'auftrag_datum'      => 'nullable|date',
            'bemerkung'          => 'nullable|string',
            'bemerkung_kunde'    => 'nullable|string',
        ]);

        if (!isset($validated['status'])) {
            $validated['status'] = 'draft';
        }

        if (!isset($validated['zahlungsbedingungen'])) {
            $validated['zahlungsbedingungen'] = 'netto_30';
        }

        $gebaeude = Gebaeude::with(['rechnungsempfaenger', 'postadresse', 'fatturaProfile'])
            ->findOrFail($validated['gebaeude_id']);

        // Pruefungen mit session('warning')
        $fehlend = [];

        if (!$gebaeude->rechnungsempfaenger_id) {
            $fehlend[] = 'Rechnungsempfaenger';
        }
        if (!$gebaeude->postadresse_id) {
            $fehlend[] = 'Postadresse';
        }
        if (!$gebaeude->fattura_profile_id) {
            $fehlend[] = 'FatturaPA-Profil';
        }

        if (!empty($fehlend)) {
            return redirect()
                ->route('gebaeude.edit', $gebaeude->id)
                ->with('warning', 'Neue Rechnung nicht moeglich! Bitte fuellen Sie folgende Felder aus: ' . implode(', ', $fehlend) . '.');
        }

        $rechnung = $gebaeude->createRechnung($validated);

        // ⭐ Validierung NACH Erstellung (mit tatsächlichen Werten)
        $warnings = [];

        Log::info('=== RECHNUNGS-VALIDIERUNG (store) START ===', [
            'rechnung_id' => $rechnung->id,
            'rechnung_nummer' => $rechnung->rechnungsnummer,
            'gebaeude_id' => $gebaeude->id,
            'brutto_summe' => $rechnung->brutto_summe,
            'jahr' => $rechnung->jahr,
        ]);

        // 1. Duplikat-Prüfung (gleicher Betrag auf gleiches Gebäude)
        $duplikat = Rechnung::pruefeDuplikat(
            $gebaeude->id,
            (float) $rechnung->brutto_summe,
            $rechnung->jahr,
            $rechnung->id  // Eigene ID ausschließen
        );

        Log::info('Duplikat-Prüfung Ergebnis', [
            'is_duplicate' => $duplikat['is_duplicate'],
            'message' => $duplikat['message'],
        ]);

        if ($duplikat['is_duplicate']) {
            $warnings[] = $duplikat['message'];
        }

        // 2. Lücken-Prüfung (alle fehlenden Nummern im Jahr)
        $luecken = Rechnung::findeAlleLuecken($rechnung->jahr);

        Log::info('Lücken-Prüfung Ergebnis', [
            'has_gaps' => $luecken['has_gaps'],
            'missing' => $luecken['missing'] ?? [],
            'message' => $luecken['message'],
        ]);

        if ($luecken['has_gaps']) {
            $warnings[] = $luecken['message'];
        }

        Log::info('=== RECHNUNGS-VALIDIERUNG (store) ENDE ===', [
            'warnings_count' => count($warnings),
            'warnings' => $warnings,
        ]);

        // Warnungen anzeigen
        $redirect = redirect()->route('rechnung.edit', $rechnung->id);

        if (!empty($warnings)) {
            session()->flash('rechnung_warnings', $warnings);
            return $redirect->with('warning', implode(' | ', $warnings));
        }

        return $redirect->with('success', "Rechnung {$rechnung->rechnungsnummer} erfolgreich angelegt.");
    }

    // ═══════════════════════════════════════════════════════════════════════════════
    // SHOW / EDIT / UPDATE - Rechnung anzeigen und bearbeiten
    // ═══════════════════════════════════════════════════════════════════════════════

    /**
     * Einzelne Rechnung anzeigen.
     */
    public function show($id)
    {
        $rechnung = Rechnung::with(['positionen', 'gebaeude'])->findOrFail($id);
        return view('rechnung.show', compact('rechnung'));
    }

    /**
     * Formular: Rechnung bearbeiten.
     */
    public function edit($id)
    {
        $rechnung = Rechnung::with(['positionen'])->findOrFail($id);
        $gebaeude_liste = Gebaeude::orderBy('codex')->get();
        $profile = FatturaProfile::all();

        // ⭐ NEU: Adressen für Dropdown laden
        $adressen = Adresse::orderBy('name')->get();

        // Zahlungsbedingungen fuer Dropdown
        $zahlungsbedingungen = Zahlungsbedingung::options();

        return view('rechnung.form', compact('rechnung', 'gebaeude_liste', 'profile', 'zahlungsbedingungen', 'adressen'));
    }

    /**
     * Rechnung aktualisieren.
     */
    public function update(Request $request, $id)
    {
        Log::info('=== RECHNUNG UPDATE START ===', ['id' => $id]);

        $rechnung = Rechnung::findOrFail($id);
        Log::info('Rechnung geladen', ['re_name' => $rechnung->re_name]);

        // ══════════════════════════════════════════════════════════════════════════════
        // PATCH FÜR RechnungController::update()
        // Füge diesen Code am ANFANG der update() Methode ein (nach $rechnung = ...)
        // ══════════════════════════════════════════════════════════════════════════════

        // ⭐ BEZAHLT-AKTION: Spezielles Handling wenn Modal verwendet wurde
        if ($request->has('_bezahlt_aktion') && $request->input('_bezahlt_aktion') == '1') {

            $bezahltGrund = $request->input('bezahlt_grund', '');
            $bezahltAm = $request->input('bezahlt_am');

            // Rechnung aktualisieren
            $rechnung->update([
                'zahlungsbedingungen' => 'bezahlt',
                'status' => 'paid',
                'bezahlt_am' => $bezahltAm,
            ]);

            // Log-Eintrag erstellen wenn gewünscht
            if ($request->boolean('log_eintrag', true)) {
                RechnungLog::create([
                    'rechnung_id' => $rechnung->id,
                    'typ'         => RechnungLogTyp::ZAHLUNG_EINGEGANGEN->value,
                    'titel'       => 'Zahlung erhalten',
                    'nachricht'   => $bezahltGrund,
                    'metadata'    => [
                        'betrag'      => $rechnung->zahlbar_betrag,
                        'bezahlt_am'  => $bezahltAm,
                        'erfasst_von' => auth()->user()?->name ?? 'System',
                        'erfasst_am'  => now()->format('Y-m-d H:i:s'),
                    ],
                ]);
            }

            return redirect()
                ->route('rechnung.edit', $rechnung->id)
                ->with('success', "Rechnung {$rechnung->rechnungsnummer} wurde als bezahlt markiert.");
        }


        if (!$rechnung->ist_editierbar) {
            Log::info('Rechnung nicht editierbar');
            return redirect()
                ->back()
                ->with('error', 'Diese Rechnung kann nicht mehr bearbeitet werden.');
        }

        // Welche Felder duerfen aus dem Formular uebernommen werden?
        $validated = $request->validate([
            // Basisdaten
            'rechnungsdatum'     => ['required', 'date'],
            'zahlungsbedingungen' => ['nullable', 'in:sofort,netto_7,netto_14,netto_30,netto_60,netto_90,netto_120,bezahlt'],
            'zahlungsziel'       => ['nullable', 'date'],
            'faelligkeitsdatum'  => ['nullable', 'date'], // ⭐ Alias für zahlungsziel aus dem Formular
            'status'             => ['nullable', Rule::in(['draft', 'sent', 'paid', 'overdue', 'cancelled'])],
            'typ_rechnung'       => ['required', Rule::in(['rechnung', 'gutschrift'])],
            'bezahlt_am'         => ['nullable', 'date'],

            // ⭐ NEU: Jahr und Laufnummer (für manuelle Korrektur)
            'jahr'               => ['nullable', 'integer', 'min:2000', 'max:2099'],
            'laufnummer'         => ['nullable', 'integer', 'min:1'],

            // ⭐ NEU: Adress-Auswahl (Dropdown)
            'rechnungsempfaenger_id' => ['nullable', 'exists:adressen,id'],
            'postadresse_id'         => ['nullable', 'exists:adressen,id'],

            // Leistungsdaten (Text, kein Datum mehr)
            'leistungsdaten'     => ['nullable', 'string', 'max:255'],

            // Fattura-Profil
            'fattura_profile_id' => ['nullable', 'exists:fattura_profile,id'],

            // FatturaPA / oeffentliche Auftraege
            'cup'                => ['nullable', 'string', 'max:20'],
            'cig'                => ['nullable', 'string', 'max:10'],
            'codice_commessa'    => ['nullable', 'string', 'max:100'],
            'auftrag_id'         => ['nullable', 'string', 'max:50'],
            'auftrag_datum'      => ['nullable', 'date'],

            // Texte / Bemerkungen
            'bemerkung'          => ['nullable', 'string'],
            'bemerkung_kunde'    => ['nullable', 'string'],

            // Preis-Aufschlag (readonly, nur zur Info)
            'aufschlag_prozent'  => ['nullable', 'numeric', 'min:-100', 'max:100'],
            'aufschlag_typ'      => ['nullable', 'string', 'in:global,individuell,keiner'],

            // ⭐ NEU: Causale (Rechnungstext)
            'fattura_causale'    => ['nullable', 'string'],
        ]);

        Log::info('Validation OK', ['validated_keys' => array_keys($validated)]);

        // ⭐ Warnung loggen wenn Jahr oder Laufnummer geändert werden
        if (isset($validated['jahr']) || isset($validated['laufnummer'])) {
            Log::warning('⚠️ Rechnungsnummer manuell geändert!', [
                'rechnung_id'     => $rechnung->id,
                'alt_jahr'        => $rechnung->jahr,
                'alt_laufnummer'  => $rechnung->laufnummer,
                'neu_jahr'        => $validated['jahr'] ?? $rechnung->jahr,
                'neu_laufnummer'  => $validated['laufnummer'] ?? $rechnung->laufnummer,
            ]);
        }

        // ═══════════════════════════════════════════════════════════════════════════════
        // ⭐ NEU: RECHNUNGSEMPFÄNGER - Snapshot-Felder aus Adresse kopieren
        // ═══════════════════════════════════════════════════════════════════════════════
        if (isset($validated['rechnungsempfaenger_id']) && $validated['rechnungsempfaenger_id']) {
            $reAdresse = Adresse::find($validated['rechnungsempfaenger_id']);
            if ($reAdresse) {
                $rechnung->rechnungsempfaenger_id = $reAdresse->id;
                $rechnung->re_name           = $reAdresse->name;
                $rechnung->re_strasse        = $reAdresse->strasse;
                $rechnung->re_hausnummer     = $reAdresse->hausnummer;
                $rechnung->re_plz            = $reAdresse->plz;
                $rechnung->re_wohnort        = $reAdresse->wohnort;
                $rechnung->re_provinz        = $reAdresse->provinz;
                $rechnung->re_land           = $reAdresse->land;
                $rechnung->re_steuernummer   = $reAdresse->steuernummer;
                $rechnung->re_mwst_nummer    = $reAdresse->mwst_nummer;
                $rechnung->re_codice_univoco = $reAdresse->codice_univoco;
                $rechnung->re_pec            = $reAdresse->pec;

                Log::info('Rechnungsempfänger-Snapshot aktualisiert', [
                    'adresse_id' => $reAdresse->id,
                    're_name'    => $reAdresse->name,
                ]);
            }
        }

        // ═══════════════════════════════════════════════════════════════════════════════
        // ⭐ NEU: POSTADRESSE - Snapshot-Felder aus Adresse kopieren
        // ═══════════════════════════════════════════════════════════════════════════════
        if (isset($validated['postadresse_id']) && $validated['postadresse_id']) {
            $postAdresse = Adresse::find($validated['postadresse_id']);
            if ($postAdresse) {
                $rechnung->postadresse_id  = $postAdresse->id;
                $rechnung->post_name       = $postAdresse->name;
                $rechnung->post_strasse    = $postAdresse->strasse;
                $rechnung->post_hausnummer = $postAdresse->hausnummer;
                $rechnung->post_plz        = $postAdresse->plz;
                $rechnung->post_wohnort    = $postAdresse->wohnort;
                $rechnung->post_provinz    = $postAdresse->provinz;
                $rechnung->post_land       = $postAdresse->land;
                $rechnung->post_email      = $postAdresse->email;
                $rechnung->post_pec        = $postAdresse->pec;

                Log::info('Postadresse-Snapshot aktualisiert', [
                    'adresse_id' => $postAdresse->id,
                    'post_name'  => $postAdresse->name,
                ]);
            }
        }

        // Entferne die Adress-IDs aus validated, da wir sie manuell verarbeitet haben
        unset($validated['rechnungsempfaenger_id']);
        unset($validated['postadresse_id']);

        // ⭐ faelligkeitsdatum → zahlungsziel mappen (Formular verwendet anderen Namen)
        if (isset($validated['faelligkeitsdatum'])) {
            $validated['zahlungsziel'] = $validated['faelligkeitsdatum'];
            unset($validated['faelligkeitsdatum']);
        }

        // Felder in das Modell schreiben
        $rechnung->fill($validated);

        // Falls Profil-Wechsel: Snapshot-Felder aktualisieren
        if (array_key_exists('fattura_profile_id', $validated)) {
            $profil = null;

            if (!empty($validated['fattura_profile_id'])) {
                $profil = FatturaProfile::find($validated['fattura_profile_id']);
            }

            if ($profil) {
                $rechnung->profile_bezeichnung = $profil->bezeichnung;
                $rechnung->mwst_satz           = $profil->mwst_satz;
                $rechnung->split_payment       = (bool) $profil->split_payment;
                $rechnung->ritenuta            = (bool) $profil->ritenuta;
                $rechnung->ritenuta_prozent    = $profil->ritenuta_prozent;
            } else {
                $rechnung->profile_bezeichnung = null;
                $rechnung->mwst_satz           = null;
                $rechnung->split_payment       = false;
                $rechnung->ritenuta            = false;
                $rechnung->ritenuta_prozent    = null;
            }
        }

        // Speichern
        $rechnung->save();
        Log::info('Update ausgefuehrt', ['neue_leistungsdaten' => $rechnung->leistungsdaten]);

        $rechnung->refresh();
        Log::info('Nach Refresh', ['status' => $rechnung->status, 'typ_rechnung' => $rechnung->typ_rechnung]);

        Log::info('=== RECHNUNG UPDATE ENDE ===');

        return redirect()
            ->route('rechnung.edit', $rechnung->id)
            ->with('success', 'Rechnung erfolgreich aktualisiert.');
    }

    /**
     * Rechnung löschen (Soft Delete mit Begründung + Gebäude-Log).
     */
    public function destroy(Request $request, $id)
    {
        $rechnung = Rechnung::findOrFail($id);

        // ──────────────────────────────────────────────────────────────────────────
        // 1. Prüfung: Nur Entwürfe können gelöscht werden
        // ──────────────────────────────────────────────────────────────────────────
        if (!$rechnung->ist_editierbar) {
            return redirect()
                ->back()
                ->with('error', 'Nur Entwürfe können gelöscht werden. Bei versendeten Rechnungen erstellen Sie eine Gutschrift.');
        }

        // ──────────────────────────────────────────────────────────────────────────
        // 2. Validierung: Begründung ist Pflicht
        // ──────────────────────────────────────────────────────────────────────────
        $validated = $request->validate([
            'loeschgrund' => 'required|string|min:10|max:1000',
        ], [
            'loeschgrund.required' => 'Bitte geben Sie eine Begründung für die Löschung an.',
            'loeschgrund.min' => 'Die Begründung muss mindestens 10 Zeichen haben.',
        ]);

        $loeschgrund = trim($validated['loeschgrund']);
        $nummer = $rechnung->rechnungsnummer;
        $rechnungId = $rechnung->id;
        $gebaeudeId = $rechnung->gebaeude_id;
        $betrag = $rechnung->brutto_summe;

        DB::beginTransaction();
        try {
            // ──────────────────────────────────────────────────────────────────────
            // 3. LOG IM RECHNUNGS-LOG
            // ──────────────────────────────────────────────────────────────────────
            RechnungLog::create([
                'rechnung_id' => $rechnung->id,
                'typ'         => RechnungLogTyp::GELOESCHT->value,
                'titel'       => 'Rechnung gelöscht',
                'nachricht'   => $loeschgrund,
                'metadata'    => [
                    'rechnungsnummer'  => $nummer,
                    'gebaeude_id'      => $gebaeudeId,
                    'geb_codex'        => $rechnung->geb_codex,
                    'geb_name'         => $rechnung->geb_name,
                    're_name'          => $rechnung->re_name,
                    'brutto_summe'     => $betrag,
                    'netto_summe'      => $rechnung->netto_summe,
                    'status'           => $rechnung->status,
                    'rechnungsdatum'   => $rechnung->rechnungsdatum?->format('Y-m-d'),
                    'geloescht_von'    => auth()->user()?->name ?? 'System',
                    'geloescht_am'     => now()->format('Y-m-d H:i:s'),
                ],
            ]);

            // ──────────────────────────────────────────────────────────────────────
            // 4. ⭐ LOG IM GEBÄUDE-LOG (mit Wiederherstellungs-Möglichkeit)
            // ──────────────────────────────────────────────────────────────────────
            if ($gebaeudeId) {
                GebaeudeLog::rechnungGeloescht(
                    gebaeudeId: $gebaeudeId,
                    rechnungsnummer: $nummer,
                    betrag: (float) $betrag,
                    loeschgrund: $loeschgrund,
                    rechnungId: $rechnungId
                );
            }

            // ──────────────────────────────────────────────────────────────────────
            // 5. SOFT DELETE
            // ──────────────────────────────────────────────────────────────────────
            $rechnung->delete();

            DB::commit();

            Log::info('Rechnung soft-deleted', [
                'rechnung_id' => $rechnungId,
                'nummer'      => $nummer,
                'grund'       => $loeschgrund,
            ]);

            return redirect()
                ->route('rechnung.index')
                ->with('success', "Rechnung {$nummer} wurde gelöscht.");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Fehler beim Löschen', ['error' => $e->getMessage()]);
            return back()->with('error', 'Fehler: ' . $e->getMessage());
        }
    }

    /**
     * Gelöschte Rechnung wiederherstellen (aus Gebäude-Log).
     */
    public function restore(Request $request, $id)
    {
        $rechnung = Rechnung::withTrashed()->findOrFail($id);

        if (!$rechnung->trashed()) {
            return redirect()->back()->with('info', 'Diese Rechnung ist nicht gelöscht.');
        }

        // ──────────────────────────────────────────────────────────────────────────
        // 1. Prüfen ob Nummer bereits wieder vergeben
        // ──────────────────────────────────────────────────────────────────────────
        $existiert = Rechnung::where('jahr', $rechnung->jahr)
            ->where('laufnummer', $rechnung->laufnummer)
            ->whereNull('deleted_at')
            ->exists();

        if ($existiert) {
            return redirect()->back()->with(
                'error',
                "Rechnungsnummer {$rechnung->rechnungsnummer} ist bereits vergeben. " .
                    "Die Rechnung kann nicht wiederhergestellt werden."
            );
        }

        DB::beginTransaction();
        try {
            // ──────────────────────────────────────────────────────────────────────
            // 2. Rechnung wiederherstellen
            // ──────────────────────────────────────────────────────────────────────
            $rechnung->restore();

            // ──────────────────────────────────────────────────────────────────────
            // 3. Log im Rechnungs-Log
            // ──────────────────────────────────────────────────────────────────────
            RechnungLog::create([
                'rechnung_id' => $rechnung->id,
                'typ'         => RechnungLogTyp::STATUS_GEAENDERT->value,
                'titel'       => 'Rechnung wiederhergestellt',
                'nachricht'   => 'Gelöschte Rechnung wurde wiederhergestellt.',
                'metadata'    => [
                    'wiederhergestellt_von' => auth()->user()?->name ?? 'System',
                    'wiederhergestellt_am'  => now()->format('Y-m-d H:i:s'),
                ],
            ]);

            // ──────────────────────────────────────────────────────────────────────
            // 4. ⭐ Gebäude-Log: Wiederherstellung loggen + alten Eintrag markieren
            // ──────────────────────────────────────────────────────────────────────
            if ($rechnung->gebaeude_id) {
                // Neuer Log-Eintrag
                GebaeudeLog::rechnungWiederhergestellt(
                    gebaeudeId: $rechnung->gebaeude_id,
                    rechnungsnummer: $rechnung->rechnungsnummer,
                    rechnungId: $rechnung->id
                );

                // Alten "gelöscht"-Eintrag markieren (kann_wiederhergestellt_werden = false)
                $logId = $request->input('log_id');
                if ($logId) {
                    $alterLog = GebaeudeLog::find($logId);
                    if ($alterLog) {
                        $alterLog->markiereAlsWiederhergestellt();
                    }
                } else {
                    // Fallback: Letzten passenden Eintrag suchen
                    $alterLog = GebaeudeLog::where('gebaeude_id', $rechnung->gebaeude_id)
                        ->where('typ', GebaeudeLogTyp::RECHNUNG_GELOESCHT->value)
                        ->whereJsonContains('metadata->rechnung_id', $rechnung->id)
                        ->latest()
                        ->first();

                    if ($alterLog) {
                        $alterLog->markiereAlsWiederhergestellt();
                    }
                }
            }

            DB::commit();

            Log::info('Rechnung wiederhergestellt', [
                'rechnung_id' => $rechnung->id,
                'nummer'      => $rechnung->rechnungsnummer,
            ]);

            return redirect()
                ->route('rechnung.edit', $rechnung->id)
                ->with('success', "Rechnung {$rechnung->rechnungsnummer} wurde wiederhergestellt.");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Fehler beim Wiederherstellen', ['error' => $e->getMessage()]);
            return back()->with('error', 'Fehler: ' . $e->getMessage());
        }
    }

    /**
     * Liste gelöschter Rechnungen (Papierkorb).
     */
    public function trashed()
    {
        $rechnungen = Rechnung::onlyTrashed()
            ->with('gebaeude')
            ->orderByDesc('deleted_at')
            ->paginate(25);

        return view('rechnung.trashed', compact('rechnungen'));
    }


    // ═══════════════════════════════════════════════════════════════════════════════
    // STATUS-AENDERUNGEN
    // ═══════════════════════════════════════════════════════════════════════════════

    /**
     * Rechnung als bezahlt markieren.
     */
    public function markAsBezahlt(Request $request, $id)
    {
        $rechnung = Rechnung::findOrFail($id);

        if ($rechnung->istAlsBezahltMarkiert()) {
            return redirect()
                ->back()
                ->with('info', 'Rechnung ist bereits als bezahlt markiert.');
        }

        $validated = $request->validate([
            'bezahlt_am' => 'nullable|date',
        ]);

        $bezahltAm = isset($validated['bezahlt_am'])
            ? Carbon::parse($validated['bezahlt_am'])
            : null;

        $rechnung->markiereAlsBezahlt($bezahltAm);

        return redirect()
            ->back()
            ->with('success', "Rechnung {$rechnung->rechnungsnummer} wurde als bezahlt markiert.");
    }

    /**
     * Rechnung versenden (Status: sent).
     * 
     * @deprecated Verwende stattdessen markAsSent() oder sendEmail()
     */
    public function send($id)
    {
        $rechnung = Rechnung::findOrFail($id);

        if ($rechnung->status !== 'draft') {
            return redirect()
                ->back()
                ->with('error', 'Nur Entwuerfe koennen versendet werden.');
        }

        $rechnung->update(['status' => 'sent']);

        return redirect()
            ->back()
            ->with('success', "Rechnung {$rechnung->rechnungsnummer} wurde versendet.");
    }

    /**
     * Rechnung stornieren.
     */
    public function cancel($id)
    {
        $rechnung = Rechnung::findOrFail($id);

        if ($rechnung->status === 'cancelled') {
            return redirect()
                ->back()
                ->with('info', 'Rechnung ist bereits storniert.');
        }

        $rechnung->update(['status' => 'cancelled']);

        return redirect()
            ->back()
            ->with('success', "Rechnung {$rechnung->rechnungsnummer} wurde storniert.");
    }

    /**
     * Markiert Rechnung als versendet ohne E-Mail zu senden.
     * 
     * - Setzt Status auf 'sent'
     * - Generiert XML falls noch nicht vorhanden
     * - Erstellt Log-Eintrag
     * 
     * Route: POST /rechnung/{id}/mark-sent
     */
    public function markAsSent(Request $request, int $id)
    {
        $rechnung = Rechnung::findOrFail($id);

        // Nur draft-Rechnungen koennen als versendet markiert werden
        if ($rechnung->status !== 'draft') {
            return back()->with('warning', 'Diese Rechnung wurde bereits versendet.');
        }

        DB::beginTransaction();
        try {
            // 1. XML generieren falls noch nicht vorhanden
            $xmlLog = $this->generateXmlIfNeeded($rechnung);

            // 2. Status auf 'sent' setzen
            $rechnung->update(['status' => 'sent']);

            // 3. Log-Eintrag erstellen
            RechnungLog::log(
                rechnungId: $rechnung->id,
                typ: RechnungLogTyp::STATUS_GEAENDERT,
                beschreibung: 'Als versendet markiert (manuell, ohne E-Mail)',
                metadata: [
                    'alter_status' => 'draft',
                    'neuer_status' => 'sent',
                    'xml_generiert' => $xmlLog ? true : false,
                    'xml_progressivo' => $xmlLog?->progressivo_invio,
                ]
            );

            DB::commit();

            // 4. Gebaeude-Flag zuruecksetzen
            $this->resetGebaeudeRechnungFlag($rechnung);

            $message = 'Rechnung als versendet markiert.';
            if ($xmlLog) {
                $message .= " XML generiert: {$xmlLog->progressivo_invio}";
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Fehler beim Markieren als versendet', [
                'rechnung_id' => $id,
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', 'Fehler: ' . $e->getMessage());
        }
    }

    public function zurueckAufEntwurf(Request $request, Rechnung $rechnung)
    {
        // Validierung
        $request->validate([
            'grund' => 'required|string|min:10|max:1000',
        ], [
            'grund.required' => 'Bitte geben Sie einen Grund für die Zurücksetzung an.',
            'grund.min' => 'Der Grund muss mindestens 10 Zeichen lang sein.',
            'grund.max' => 'Der Grund darf maximal 1000 Zeichen lang sein.',
        ]);

        // Prüfen ob bereits Entwurf
        if ($rechnung->status === 'draft') {
            return redirect()->route('rechnung.edit', $rechnung->id)
                ->with('info', 'Die Rechnung ist bereits ein Entwurf.');
        }

        // Prüfen ob storniert
        if ($rechnung->status === 'cancelled') {
            return redirect()->route('rechnung.edit', $rechnung->id)
                ->with('error', 'Eine stornierte Rechnung kann nicht zurückgesetzt werden.');
        }

        $alterStatus = $rechnung->status;
        $grund = $request->input('grund');

        try {
            DB::beginTransaction();

            // Status auf draft setzen
            $rechnung->update([
                'status' => 'draft',
            ]);

            // Log-Eintrag erstellen
            RechnungLog::create([
                'rechnung_id' => $rechnung->id,
                'typ' => RechnungLogTyp::STATUS_ENTWURF,
                'titel' => 'Zurück auf Entwurf gesetzt',
                'beschreibung' => $grund,
                'user_id' => \Auth::id(),
                'prioritaet' => 'hoch',
                'metadata' => [
                    'alter_status' => $alterStatus,
                    'neuer_status' => 'draft',
                    'grund' => $grund,
                ],
            ]);

            DB::commit();

            Log::info('Rechnung auf Entwurf zurückgesetzt', [
                'rechnung_id' => $rechnung->id,
                'rechnung_nummer' => $rechnung->rechnungsnummer,
                'alter_status' => $alterStatus,
                'grund' => $grund,
                'user_id' => \Auth::id(),
            ]);

            // ⭐ WICHTIG: Expliziter Redirect zur Edit-Seite mit frischer Rechnung
            return redirect()->route('rechnung.edit', $rechnung->id)
                ->with('success', 'Rechnung wurde auf Entwurf zurückgesetzt. Sie können die Rechnung jetzt bearbeiten.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Fehler beim Zurücksetzen auf Entwurf', [
                'rechnung_id' => $rechnung->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('rechnung.edit', $rechnung->id)
                ->with('error', 'Fehler beim Zurücksetzen: ' . $e->getMessage());
        }
    }


    // ═══════════════════════════════════════════════════════════════════════════════
    // POSITIONEN - CRUD fuer Rechnungspositionen
    // ═══════════════════════════════════════════════════════════════════════════════

    /**
     * Neue Position zu Rechnung hinzufuegen.
     */
    public function storePosition(Request $request, $rechnungId)
    {
        $rechnung = Rechnung::findOrFail($rechnungId);

        if (!$rechnung->ist_editierbar) {
            return redirect()
                ->back()
                ->with('error', 'Rechnung kann nicht mehr bearbeitet werden.');
        }

        $validated = $request->validate([
            'beschreibung' => 'required|string|max:500',
            'anzahl'       => 'required|numeric|min:0',
            'einheit'      => 'nullable|string|max:10',
            'einzelpreis'  => 'required|numeric',
            'mwst_satz'    => 'required|numeric|min:0|max:100',
            'position'     => 'nullable|integer|min:1',
        ]);

        if (!isset($validated['position'])) {
            $maxPos = $rechnung->positionen()->max('position') ?? 0;
            $validated['position'] = $maxPos + 1;
        }

        $rechnung->positionen()->create($validated);

        return redirect()
            ->route('rechnung.edit', $rechnungId)
            ->with('success', 'Position wurde hinzugefuegt.');
    }

    /**
     * Position aktualisieren.
     */
    public function updatePosition(Request $request, $positionId)
    {
        $position = RechnungPosition::findOrFail($positionId);
        $rechnung = $position->rechnung;

        if (!$rechnung->ist_editierbar) {
            return redirect()
                ->back()
                ->with('error', 'Rechnung kann nicht mehr bearbeitet werden.');
        }

        $validated = $request->validate([
            'beschreibung' => 'required|string|max:500',
            'anzahl'       => 'required|numeric|min:0',
            'einheit'      => 'nullable|string|max:10',
            'einzelpreis'  => 'required|numeric',
            'mwst_satz'    => 'required|numeric|min:0|max:100',
            'position'     => 'required|integer|min:1',
        ]);

        $position->update($validated);

        return redirect()
            ->route('rechnung.edit', $rechnung->id)
            ->with('success', 'Position wurde aktualisiert.');
    }

    /**
     * Position loeschen.
     */
    public function destroyPosition($positionId)
    {
        $position = RechnungPosition::findOrFail($positionId);
        $rechnung = $position->rechnung;

        if (!$rechnung->ist_editierbar) {
            return redirect()
                ->back()
                ->with('error', 'Rechnung kann nicht mehr bearbeitet werden.');
        }

        $position->delete();

        return redirect()
            ->route('rechnung.edit', $rechnung->id)
            ->with('success', 'Position wurde geloescht.');
    }

    // ═══════════════════════════════════════════════════════════════════════════════
    // AJAX HELPERS
    // ═══════════════════════════════════════════════════════════════════════════════

    /**
     * AJAX: Zahlungsziel automatisch berechnen.
     */
    public function calculateZahlungsziel(Request $request)
    {
        $validated = $request->validate([
            'rechnungsdatum' => 'required|date',
            'zahlungsbedingungen' => 'required|in:sofort,netto_7,netto_14,netto_30,netto_60,netto_90,netto_120',
        ]);

        $rechnungsdatum = Carbon::parse($validated['rechnungsdatum']);
        $zahlungsbedingung = Zahlungsbedingung::from($validated['zahlungsbedingungen']);

        $zahlungsziel = $rechnungsdatum->copy()->addDays($zahlungsbedingung->tage());

        return response()->json([
            'ok' => true,
            'zahlungsziel' => $zahlungsziel->format('Y-m-d'),
            'zahlungsziel_formatiert' => $zahlungsziel->format('d.m.Y'),
            'tage' => $zahlungsbedingung->tage(),
            'label' => $zahlungsbedingung->label(),
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════════════
    // FATTURAPA XML GENERATION
    // ═══════════════════════════════════════════════════════════════════════════════

    /**
     * Generiert XML falls noch keines existiert (Helper).
     * 
     * @param Rechnung $rechnung
     * @return FatturaXmlLog|null Das XML-Log oder null bei Fehler
     */
    protected function generateXmlIfNeeded(Rechnung $rechnung): ?FatturaXmlLog
    {
        // Pruefe ob bereits ein gueltiges XML existiert
        $existingLog = FatturaXmlLog::where('rechnung_id', $rechnung->id)
            ->whereIn('status', [
                FatturaXmlLog::STATUS_GENERATED,
                FatturaXmlLog::STATUS_SIGNED,
                FatturaXmlLog::STATUS_SENT,
                FatturaXmlLog::STATUS_DELIVERED,
                FatturaXmlLog::STATUS_ACCEPTED,
            ])
            ->first();

        if ($existingLog) {
            Log::info('XML existiert bereits', [
                'rechnung_id' => $rechnung->id,
                'progressivo' => $existingLog->progressivo_invio,
            ]);
            return $existingLog;
        }

        // Pruefe ob FatturaPA-Profil zugeordnet
        if (!$rechnung->fattura_profile_id) {
            Log::warning('Kein FatturaPA-Profil - XML wird nicht generiert', [
                'rechnung_id' => $rechnung->id,
            ]);
            return null;
        }

        try {
            $generator = new FatturaXmlGenerator();
            $log = $generator->generate($rechnung);

            // In RechnungLog eintragen
            RechnungLog::log(
                rechnungId: $rechnung->id,
                typ: RechnungLogTyp::XML_ERSTELLT,
                beschreibung: "FatturaPA XML automatisch generiert: {$log->progressivo_invio}",
                metadata: [
                    'progressivo' => $log->progressivo_invio,
                    'dateiname' => $log->xml_filename ?? null,
                    'fattura_xml_log_id' => $log->id,
                    'automatisch' => true,
                ]
            );

            Log::info('XML automatisch generiert', [
                'rechnung_id' => $rechnung->id,
                'progressivo' => $log->progressivo_invio,
            ]);

            return $log;
        } catch (\Exception $e) {
            Log::error('Automatische XML-Generierung fehlgeschlagen', [
                'rechnung_id' => $rechnung->id,
                'error' => $e->getMessage(),
            ]);

            RechnungLog::log(
                rechnungId: $rechnung->id,
                typ: RechnungLogTyp::XML_FEHLER,
                beschreibung: "Automatische XML-Generierung fehlgeschlagen: {$e->getMessage()}",
                metadata: ['error' => $e->getMessage()]
            );

            return null;
        }
    }

    /**
     * XML manuell generieren.
     * 
     * Route: POST /rechnung/{id}/xml/generate
     */
    public function generateXml(int $id)
    {
        $rechnung = Rechnung::findOrFail($id);

        // Pruefe ob Rechnung bereits ein XML hat
        $existingLog = FatturaXmlLog::where('rechnung_id', $rechnung->id)
            ->whereIn('status', [
                FatturaXmlLog::STATUS_GENERATED,
                FatturaXmlLog::STATUS_SIGNED,
                FatturaXmlLog::STATUS_SENT,
                FatturaXmlLog::STATUS_DELIVERED,
                FatturaXmlLog::STATUS_ACCEPTED,
            ])
            ->first();

        if ($existingLog) {
            return back()->with('warning', sprintf(
                'Es existiert bereits ein XML fuer diese Rechnung (Progressivo: %s). Moechten Sie ein neues generieren?',
                $existingLog->progressivo_invio
            ));
        }

        try {
            $generator = new FatturaXmlGenerator();
            $log = $generator->generate($rechnung);

            // In RechnungLog eintragen
            RechnungLog::log(
                rechnungId: $rechnung->id,
                typ: RechnungLogTyp::XML_ERSTELLT,
                beschreibung: "FatturaPA XML generiert: {$log->progressivo_invio}",
                metadata: [
                    'progressivo' => $log->progressivo_invio,
                    'dateiname' => $log->xml_filename ?? null,
                    'fattura_xml_log_id' => $log->id,
                ]
            );

            return redirect()
                ->route('rechnung.edit', $id)
                ->with('success', sprintf(
                    'FatturaPA XML erfolgreich generiert! Progressivo: %s',
                    $log->progressivo_invio
                ));
        } catch (\Exception $e) {
            Log::error('Fehler bei XML-Generierung', [
                'rechnung_id' => $id,
                'error' => $e->getMessage(),
            ]);

            // Fehler auch in RechnungLog eintragen
            RechnungLog::log(
                rechnungId: $rechnung->id,
                typ: RechnungLogTyp::XML_FEHLER,
                beschreibung: "XML-Generierung fehlgeschlagen: {$e->getMessage()}",
                metadata: [
                    'error' => $e->getMessage(),
                ]
            );

            return back()->withErrors([
                'error' => 'Fehler bei XML-Generierung: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Regeneriert XML (ueberschreibt vorheriges).
     * 
     * Route: POST /rechnung/{id}/xml/regenerate
     */
    public function regenerateXml(int $id)
    {
        $rechnung = Rechnung::findOrFail($id);

        try {
            // Alte Logs als "superseded" markieren
            FatturaXmlLog::where('rechnung_id', $rechnung->id)
                ->whereNotIn('status', [
                    FatturaXmlLog::STATUS_SENT,
                    FatturaXmlLog::STATUS_DELIVERED,
                    FatturaXmlLog::STATUS_ACCEPTED,
                ])
                ->update([
                    'status' => 'superseded',
                    'status_detail' => 'Durch neue XML-Generierung ersetzt',
                ]);

            $generator = new FatturaXmlGenerator();
            $log = $generator->generate($rechnung);

            // In RechnungLog eintragen
            RechnungLog::log(
                rechnungId: $rechnung->id,
                typ: RechnungLogTyp::XML_ERSTELLT,
                beschreibung: "FatturaPA XML neu generiert: {$log->progressivo_invio}",
                metadata: [
                    'progressivo' => $log->progressivo_invio,
                    'dateiname' => $log->xml_filename ?? null,
                    'fattura_xml_log_id' => $log->id,
                    'ist_regeneriert' => true,
                ]
            );

            return redirect()
                ->route('rechnung.edit', $id)
                ->with('success', sprintf(
                    'FatturaPA XML neu generiert! Progressivo: %s',
                    $log->progressivo_invio
                ));
        } catch (\Exception $e) {
            Log::error('Fehler bei XML-Regenerierung', [
                'rechnung_id' => $id,
                'error' => $e->getMessage(),
            ]);

            // Fehler in RechnungLog eintragen
            RechnungLog::log(
                rechnungId: $rechnung->id,
                typ: RechnungLogTyp::XML_FEHLER,
                beschreibung: "XML-Regenerierung fehlgeschlagen: {$e->getMessage()}",
                metadata: [
                    'error' => $e->getMessage(),
                ]
            );

            return back()->withErrors([
                'error' => 'Fehler bei XML-Regenerierung: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Zeigt XML-Preview an (ohne zu speichern).
     * 
     * Route: GET /rechnung/{id}/xml/preview
     */
    public function previewXml(int $id)
    {
        $rechnung = Rechnung::findOrFail($id);

        try {
            $generator = new FatturaXmlGenerator();
            $xmlString = $generator->preview($rechnung);

            // Als Download mit XML-Header
            return response($xmlString, 200)
                ->header('Content-Type', 'application/xml; charset=UTF-8')
                ->header('Content-Disposition', 'inline; filename="preview.xml"');
        } catch (\Exception $e) {
            return back()->withErrors([
                'error' => 'Fehler bei Preview: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Laedt XML-Datei herunter.
     * 
     * Route: GET /rechnung/{id}/xml/download
     */
    public function downloadXml(int $id)
    {
        // Neuestes erfolgreiches XML fuer diese Rechnung
        $log = FatturaXmlLog::where('rechnung_id', $id)
            ->whereIn('status', [
                FatturaXmlLog::STATUS_GENERATED,
                FatturaXmlLog::STATUS_SIGNED,
                FatturaXmlLog::STATUS_SENT,
                FatturaXmlLog::STATUS_DELIVERED,
                FatturaXmlLog::STATUS_ACCEPTED,
            ])
            ->latest()
            ->firstOrFail();

        return $log->downloadXml();
    }


    /**
     * Bulk-Download: Mehrere XML-Dateien als ZIP herunterladen.
     * 
     * Route: POST /rechnung/bulk-xml-download
     */
    public function bulkDownloadXml(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer|exists:rechnungen,id',
        ]);

        $ids = $request->input('ids');

        // Alle neuesten erfolgreichen XML-Logs für die gewählten Rechnungen laden
        $logs = FatturaXmlLog::whereIn('rechnung_id', $ids)
            ->whereIn('status', [
                FatturaXmlLog::STATUS_GENERATED,
                FatturaXmlLog::STATUS_SIGNED,
                FatturaXmlLog::STATUS_SENT,
                FatturaXmlLog::STATUS_DELIVERED,
                FatturaXmlLog::STATUS_ACCEPTED,
            ])
            ->orderByDesc('created_at')
            ->get()
            ->unique('rechnung_id'); // Nur neuestes pro Rechnung

        if ($logs->isEmpty()) {
            return response()->json([
                'error' => 'Keine XML-Dateien für die ausgewählten Rechnungen gefunden.'
            ], 404);
        }

        // Bei nur einer Rechnung: direkt als XML herunterladen (kein ZIP nötig)
        if ($logs->count() === 1) {
            return $logs->first()->downloadXml();
        }

        // ZIP erstellen
        $zipFilename = 'FatturaPA_' . now()->format('Y-m-d_His') . '.zip';
        $zipPath = storage_path('app/temp/' . $zipFilename);

        // Temp-Ordner sicherstellen
        if (!is_dir(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return response()->json([
                'error' => 'ZIP-Datei konnte nicht erstellt werden.'
            ], 500);
        }

        $addedCount = 0;
        $skipped = [];

        foreach ($logs as $log) {
            if ($log->xmlExists()) {
                $xmlContent = Storage::get($log->xml_file_path);
                $filename = $log->xml_filename ?? ($log->progressivo_invio . '.xml');
                $zip->addFromString($filename, $xmlContent);
                $addedCount++;
            } else {
                $skipped[] = $log->rechnung_id;
            }
        }

        $zip->close();

        if ($addedCount === 0) {
            @unlink($zipPath);
            return response()->json([
                'error' => 'Keine XML-Dateien gefunden. Möglicherweise wurden die Dateien gelöscht.'
            ], 404);
        }

        Log::info('Bulk XML Download', [
            'anzahl_angefragt' => count($ids),
            'anzahl_im_zip'    => $addedCount,
            'uebersprungen'    => $skipped,
        ]);

        // ZIP herunterladen und danach löschen
        return response()->download($zipPath, $zipFilename, [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Laedt XML-Datei direkt ueber Log-ID herunter.
     * 
     * Route: GET /fattura-xml/{logId}/download
     */
    public function downloadXmlByLog(int $logId)
    {
        $log = FatturaXmlLog::findOrFail($logId);

        if (!$log->xmlExists()) {
            abort(404, 'XML-Datei nicht gefunden');
        }

        return $log->downloadXml();
    }

    /**
     * Zeigt alle XML-Logs fuer eine Rechnung.
     * 
     * Route: GET /rechnung/{id}/xml/logs
     */
    public function xmlLogs(int $id)
    {
        $rechnung = Rechnung::with('positionen')->findOrFail($id);

        $logs = FatturaXmlLog::where('rechnung_id', $rechnung->id)
            ->orderByDesc('created_at')
            ->get();

        return view('rechnung.xml-logs', compact('rechnung', 'logs'));
    }

    /**
     * Loescht ein XML-Log (und Datei).
     * 
     * Route: DELETE /fattura-xml/{logId}
     */
    public function deleteXmlLog(int $logId)
    {
        $log = FatturaXmlLog::findOrFail($logId);

        // Pruefe ob bereits gesendet
        if (in_array($log->status, [
            FatturaXmlLog::STATUS_SENT,
            FatturaXmlLog::STATUS_DELIVERED,
            FatturaXmlLog::STATUS_ACCEPTED,
        ])) {
            return back()->withErrors([
                'error' => 'XML wurde bereits versendet und kann nicht geloescht werden!'
            ]);
        }

        $rechnungId = $log->rechnung_id;

        // Dateien loeschen
        if ($log->xmlExists()) {
            Storage::delete($log->xml_file_path);
        }

        if ($log->p7mExists()) {
            Storage::delete($log->p7m_file_path);
        }

        $log->delete();

        return redirect()
            ->route('rechnung.edit', $rechnungId)
            ->with('success', 'XML-Log geloescht');
    }

    /**
     * Zeigt Debug-Info fuer XML-Generierung.
     * 
     * Route: GET /rechnung/{id}/xml/debug
     */
    public function debugXml(int $id)
    {
        $rechnung = Rechnung::with([
            'positionen',
            'rechnungsempfaenger',
            'postadresse',
            'fatturaProfile',
        ])->findOrFail($id);

        $profil = Unternehmensprofil::first();

        $generator = new FatturaXmlGenerator();

        $debug = [
            'rechnung' => [
                'id' => $rechnung->id,
                'nummer' => $rechnung->rechnungsnummer,
                'datum' => $rechnung->rechnungsdatum?->format('Y-m-d'),
                'positionen_count' => $rechnung->positionen->count(),
                'netto' => $rechnung->netto_summe,
                'brutto' => $rechnung->brutto_summe,
            ],
            'empfaenger' => [
                'name' => $rechnung->re_name,
                'codice_univoco' => $rechnung->re_codice_univoco,
                'pec' => $rechnung->re_pec,
                'mwst_nummer' => $rechnung->re_mwst_nummer,
            ],
            'profil' => [
                'ragione_sociale' => $profil?->ragione_sociale,
                'partita_iva' => $profil?->partita_iva_numeric,
                'ist_konfiguriert' => $profil?->istFatturapaKonfiguriert(),
                'fehlende_felder' => $profil?->fehlendeFelderFatturaPA() ?? [],
            ],
            'config' => [
                'formato' => config('fattura.trasmissione.formato_trasmissione'),
                'modalita_pagamento' => config('fattura.defaults.modalita_pagamento'),
                'validate_xsd' => config('fattura.xml.validate_xsd'),
            ],
        ];

        return response()->json($debug, 200, [], JSON_PRETTY_PRINT);
    }

    // ═══════════════════════════════════════════════════════════════════════════════
    // PDF-GENERIERUNG
    // ═══════════════════════════════════════════════════════════════════════════════

    /**
     * PDF generieren (Platzhalter fuer alte Methode).
     * 
     * @deprecated Verwende stattdessen downloadPdf() oder previewPdf()
     */
    public function generatePdf($id)
    {
        $rechnung = Rechnung::with(['positionen'])->findOrFail($id);

        return redirect()
            ->back()
            ->with('error', 'PDF-Export noch nicht implementiert.');
    }

    /**
     * PDF herunterladen.
     * 
     * Route: GET /rechnung/{id}/pdf/download
     */
    public function downloadPdf(int $id)
    {
        $rechnung = Rechnung::with(['positionen', 'fatturaProfile'])
            ->findOrFail($id);

        // Unternehmensprofil laden
        $unternehmen = Unternehmensprofil::first();

        // PDF generieren
        $pdf = Pdf::loadView('rechnung.pdf', [
            'rechnung' => $rechnung,
            'unternehmen' => $unternehmen,
        ])
            ->setPaper('a4', 'portrait')
            ->setOption('defaultFont', 'DejaVu Sans');

        // Dateiname (Schraegstriche ersetzen fuer Dateisystem)
        $filename = sprintf(
            'Rechnung_%s_%s.pdf',
            str_replace(['/', '\\'], '-', $rechnung->rechnungsnummer),
            $rechnung->rechnungsdatum->format('Y-m-d')
        );

        // Download
        return $pdf->download($filename);
    }

    /**
     * Zeigt PDF im Browser (Preview).
     * 
     * Route: GET /rechnung/{id}/pdf/preview
     */
    public function previewPdf(int $id)
    {
        $rechnung = Rechnung::with(['positionen', 'fatturaProfile'])
            ->findOrFail($id);

        // Unternehmensprofil laden
        $unternehmen = Unternehmensprofil::first();

        // PDF generieren
        $pdf = Pdf::loadView('rechnung.pdf', [
            'rechnung' => $rechnung,
            'unternehmen' => $unternehmen,
        ])
            ->setPaper('a4', 'portrait')
            ->setOption('defaultFont', 'DejaVu Sans');

        // Im Browser anzeigen
        return $pdf->stream('rechnung.pdf');
    }

    /**
     * PDF als Email versenden (veraltet).
     * 
     * @deprecated Verwende stattdessen sendEmail()
     */
    public function sendPdfEmail(int $id)
    {
        $rechnung = Rechnung::with(['positionen', 'fatturaProfile'])
            ->findOrFail($id);

        // Validierung
        if (!$rechnung->post_email) {
            return back()->with('error', 'Keine Email-Adresse hinterlegt!');
        }

        // PDF generieren
        $pdf = Pdf::loadView('rechnung.pdf', [
            'rechnung' => $rechnung,
        ])
            ->setPaper('a4', 'portrait')
            ->setOption('defaultFont', 'DejaVu Sans');

        $filename = sprintf(
            'Rechnung_%s.pdf',
            $rechnung->rechnungsnummer
        );

        // Email versenden
        Mail::to($rechnung->post_email)->send(
            new RechnungMail($rechnung, $pdf->output(), $filename)
        );

        return back()->with('success', 'Rechnung per Email versendet!');
    }

    // ═══════════════════════════════════════════════════════════════════════════════
    // E-MAIL VERSAND
    // ═══════════════════════════════════════════════════════════════════════════════

    /**
     * E-Mail mit Rechnung versenden.
     * 
     * Features:
     * - Nutzt SMTP-Konfiguration aus Unternehmensprofil
     * - Generiert PDF als Anhang
     * - Optional: XML als Anhang
     * - Logo als Inline-Bild (CID)
     * - HTML-Body aus Blade-View
     * - Setzt Status automatisch auf 'sent'
     * - Generiert XML automatisch falls nicht vorhanden
     * 
     * Route: POST /rechnung/{id}/email/send
     */
    public function sendEmail(Request $request, int $id)
    {
        $rechnung = Rechnung::with(['gebaeude', 'rechnungsempfaenger', 'fatturaProfile', 'positionen'])
            ->findOrFail($id);

        // ──────────────────────────────────────────────────────────────────────────
        // 1. VALIDIERUNG
        // ──────────────────────────────────────────────────────────────────────────
        $validated = $request->validate([
            'typ'        => ['required', 'in:email,pec'],
            'empfaenger' => ['nullable', 'email'],
            'pec'        => ['nullable', 'email'],
            'betreff'    => ['required', 'string', 'max:255'],
            'nachricht'  => ['required', 'string'],
            'attach_pdf' => ['nullable', 'in:0,1'],
            'attach_xml' => ['nullable', 'in:0,1'],
            'copy_me'    => ['nullable', 'in:0,1'],
        ]);

        $typ = $validated['typ'];
        $empfaenger = $typ === 'pec' ? $validated['pec'] : $validated['empfaenger'];

        // Auch ohne E-Mail kann Status geaendert werden
        if (empty($empfaenger)) {
            return $this->markAsSentWithoutEmail($rechnung);
        }

        // ──────────────────────────────────────────────────────────────────────────
        // 2. XML GENERIEREN (falls noch nicht vorhanden)
        // ──────────────────────────────────────────────────────────────────────────
        $xmlLog = $this->generateXmlIfNeeded($rechnung);

        // ──────────────────────────────────────────────────────────────────────────
        // 3. UNTERNEHMENSPROFIL LADEN
        // ──────────────────────────────────────────────────────────────────────────
        $profil = Unternehmensprofil::aktiv();

        if (!$profil) {
            Log::error('Kein aktives Unternehmensprofil gefunden');
            return back()->with('error', 'Kein aktives Unternehmensprofil gefunden!');
        }

        // ──────────────────────────────────────────────────────────────────────────
        // 4. SMTP-KONFIGURATION PRUEFEN
        // ──────────────────────────────────────────────────────────────────────────
        $usePec = ($typ === 'pec');

        if ($usePec) {
            if (!$profil->hatPecSmtpKonfiguration()) {
                return back()->with('error', 'PEC-SMTP nicht konfiguriert! Bitte im Unternehmensprofil einrichten.');
            }
            $smtpHost = $profil->pec_smtp_host;
            $smtpPort = $profil->pec_smtp_port;
            $smtpUser = $profil->pec_smtp_benutzername;
            $smtpPass = $profil->pec_smtp_passwort;
            $smtpEncryption = $profil->pec_smtp_verschluesselung;
            $absenderEmail = $profil->pec_email;
            $absenderName = $profil->firmenname;
            $mailerName = 'pec_dynamic';
        } else {
            if (!$profil->hatSmtpKonfiguration()) {
                return back()->with('error', 'SMTP nicht konfiguriert! Bitte im Unternehmensprofil einrichten.');
            }
            $smtpHost = $profil->smtp_host;
            $smtpPort = $profil->smtp_port;
            $smtpUser = $profil->smtp_benutzername;
            $smtpPass = $profil->smtp_passwort;
            $smtpEncryption = $profil->smtp_verschluesselung;
            // Absender = SMTP-Benutzername (wegen Aruba-Restriktion)
            $absenderEmail = $profil->smtp_benutzername;
            $absenderName = $profil->smtp_absender_name ?: $profil->firmenname;
            $mailerName = 'smtp_dynamic';
        }

        // ──────────────────────────────────────────────────────────────────────────
        // 5. DYNAMISCHE MAILER-KONFIGURATION
        // ──────────────────────────────────────────────────────────────────────────
        Config::set("mail.mailers.{$mailerName}", [
            'transport'  => 'smtp',
            'host'       => $smtpHost,
            'port'       => $smtpPort,
            'encryption' => ($smtpEncryption === 'none' || empty($smtpEncryption)) ? null : $smtpEncryption,
            'username'   => $smtpUser,
            'password'   => $smtpPass,
            'timeout'    => 30,
        ]);

        Log::info('E-Mail Versand gestartet', [
            'rechnung_id' => $rechnung->id,
            'typ'         => $typ,
            'empfaenger'  => $empfaenger,
            'smtp_host'   => $smtpHost,
            'smtp_user'   => $smtpUser,
            'absender'    => $absenderEmail,
        ]);

        // ──────────────────────────────────────────────────────────────────────────
        // 6. PDF GENERIEREN
        // ──────────────────────────────────────────────────────────────────────────
        $pdfContent = null;
        $pdfFilename = null;

        if ($validated['attach_pdf'] ?? true) {
            try {
                $pdf = Pdf::loadView('rechnung.pdf', [
                    'rechnung'    => $rechnung,
                    'unternehmen' => $profil,
                ]);

                $pdf->setPaper('A4', 'portrait');
                $pdfContent = $pdf->output();
                $pdfFilename = $this->generatePdfFilename($rechnung);

                Log::info('PDF generiert', [
                    'filename' => $pdfFilename,
                    'size'     => strlen($pdfContent),
                ]);
            } catch (\Exception $e) {
                Log::error('PDF-Generierung fehlgeschlagen', [
                    'error' => $e->getMessage(),
                ]);
                return back()->with('error', 'PDF konnte nicht generiert werden: ' . $e->getMessage());
            }
        }

        // ──────────────────────────────────────────────────────────────────────────
        // 7. XML LADEN (falls gewuenscht und vorhanden)
        // ──────────────────────────────────────────────────────────────────────────
        $xmlContent = null;
        $xmlFilename = null;

        if (($validated['attach_xml'] ?? false) && $rechnung->fattura_profile_id) {
            // Nutze das bereits generierte oder existierende XML
            $xmlLogForAttachment = $xmlLog ?? FatturaXmlLog::where('rechnung_id', $rechnung->id)
                ->whereIn('status', ['generated', 'signed', 'sent', 'delivered', 'accepted'])
                ->latest()
                ->first();

            if ($xmlLogForAttachment && $xmlLogForAttachment->xml_path && Storage::disk('local')->exists($xmlLogForAttachment->xml_path)) {
                $xmlContent = Storage::disk('local')->get($xmlLogForAttachment->xml_path);
                $xmlFilename = $xmlLogForAttachment->xml_filename;
                Log::info('XML angehaengt', ['filename' => $xmlFilename]);
            }
        }

        // ──────────────────────────────────────────────────────────────────────────
        // 8. LOGO-PFAD ERMITTELN
        // ──────────────────────────────────────────────────────────────────────────
        $logoPath = null;

        if ($profil->logo_email_pfad && Storage::disk('public')->exists($profil->logo_email_pfad)) {
            $logoPath = Storage::disk('public')->path($profil->logo_email_pfad);
        } elseif ($profil->logo_pfad && Storage::disk('public')->exists($profil->logo_pfad)) {
            $logoPath = Storage::disk('public')->path($profil->logo_pfad);
        }

        // ──────────────────────────────────────────────────────────────────────────
        // 9. E-MAIL SENDEN
        // ──────────────────────────────────────────────────────────────────────────
        try {
            $betreff = $validated['betreff'];
            $nachricht = $validated['nachricht'];
            $copyMe = ($validated['copy_me'] ?? false) ? $absenderEmail : null;

            Mail::mailer($mailerName)
                ->send([], [], function ($message) use (
                    $empfaenger,
                    $absenderEmail,
                    $absenderName,
                    $betreff,
                    $nachricht,
                    $rechnung,
                    $profil,
                    $pdfContent,
                    $pdfFilename,
                    $xmlContent,
                    $xmlFilename,
                    $logoPath,
                    $copyMe
                ) {
                    $message->to($empfaenger)
                        ->from($absenderEmail, $absenderName)
                        ->subject($betreff);

                    if ($copyMe) {
                        $message->bcc($copyMe);
                    }

                    $logoCid = null;
                    if ($logoPath && file_exists($logoPath)) {
                        $logoCid = $message->embed($logoPath);
                    }

                    $htmlBody = View::make('emails.rechnung', [
                        'rechnung'  => $rechnung,
                        'profil'    => $profil,
                        'nachricht' => $nachricht,
                        'logoCid'   => $logoCid,
                    ])->render();

                    $message->html($htmlBody);

                    if ($pdfContent && $pdfFilename) {
                        $message->attachData($pdfContent, $pdfFilename, [
                            'mime' => 'application/pdf',
                        ]);
                    }

                    if ($xmlContent && $xmlFilename) {
                        $message->attachData($xmlContent, $xmlFilename, [
                            'mime' => 'application/xml',
                        ]);
                    }
                });

            Log::info('E-Mail erfolgreich versandt', [
                'rechnung_id' => $rechnung->id,
                'empfaenger'  => $empfaenger,
                'typ'         => $typ,
            ]);

            // ──────────────────────────────────────────────────────────────────────────
            // 10. STATUS AUF 'SENT' SETZEN
            // ──────────────────────────────────────────────────────────────────────────
            $alterStatus = $rechnung->status;
            if ($rechnung->status === 'draft') {
                $rechnung->update(['status' => 'sent']);

                Log::info('Rechnungsstatus auf sent geaendert', [
                    'rechnung_id' => $rechnung->id,
                    'alter_status' => $alterStatus,
                ]);
            }

            // ──────────────────────────────────────────────────────────────────────────
            // 11. LOG ERSTELLEN
            // ──────────────────────────────────────────────────────────────────────────
            RechnungLog::create([
                'rechnung_id' => $rechnung->id,
                'typ'         => $typ === 'pec' ? RechnungLogTyp::PEC_VERSANDT->value : RechnungLogTyp::EMAIL_VERSANDT->value,
                'titel'       => $typ === 'pec' ? 'PEC versandt' : 'E-Mail versandt',
                'nachricht'   => "Versandt an {$empfaenger}",
                'metadata'    => [
                    'empfaenger'       => $empfaenger,
                    'betreff'          => $betreff,
                    'typ'              => $typ,
                    'status_geaendert' => $alterStatus !== 'sent',
                    'xml_generiert'    => $xmlLog ? true : false,
                    'attachments'      => array_filter([$pdfFilename, $xmlFilename]),
                ],
            ]);

            // ──────────────────────────────────────────────────────────────────────────
            // 12. GEBAEUDE-FLAG ZURUECKSETZEN
            // ──────────────────────────────────────────────────────────────────────────
            $this->resetGebaeudeRechnungFlag($rechnung);

            $typLabel = $typ === 'pec' ? 'PEC' : 'E-Mail';
            $statusMsg = $alterStatus === 'draft' ? ' Status: Versendet.' : '';
            return back()->with('success', "{$typLabel} erfolgreich versandt an {$empfaenger}.{$statusMsg}");
        } catch (\Exception $e) {
            Log::error('E-Mail Versand fehlgeschlagen', [
                'rechnung_id' => $rechnung->id,
                'empfaenger'  => $empfaenger,
                'error'       => $e->getMessage(),
            ]);

            return back()->with('error', 'E-Mail konnte nicht versandt werden: ' . $e->getMessage());
        }
    }

    /**
     * Status auf 'sent' setzen ohne E-Mail (Helper).
     * 
     * Wird aufgerufen wenn keine E-Mail-Adresse angegeben wurde.
     */
    protected function markAsSentWithoutEmail(Rechnung $rechnung)
    {
        DB::beginTransaction();
        try {
            // 1. XML generieren
            $xmlLog = $this->generateXmlIfNeeded($rechnung);

            // 2. Status aendern
            $alterStatus = $rechnung->status;
            if ($rechnung->status === 'draft') {
                $rechnung->update(['status' => 'sent']);
            }

            // 3. Log erstellen
            RechnungLog::log(
                rechnungId: $rechnung->id,
                typ: RechnungLogTyp::STATUS_GEAENDERT,
                beschreibung: 'Als versendet markiert (keine E-Mail-Adresse angegeben)',
                metadata: [
                    'alter_status' => $alterStatus,
                    'neuer_status' => 'sent',
                    'xml_generiert' => $xmlLog ? true : false,
                    'xml_progressivo' => $xmlLog?->progressivo_invio,
                    'hinweis' => 'Keine E-Mail-Adresse - nur Status geaendert',
                ]
            );

            DB::commit();

            // 4. Gebaeude-Flag zuruecksetzen
            $this->resetGebaeudeRechnungFlag($rechnung);

            $message = 'Rechnung als versendet markiert (keine E-Mail-Adresse).';
            if ($xmlLog) {
                $message .= " XML: {$xmlLog->progressivo_invio}";
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Fehler beim Markieren als versendet', [
                'rechnung_id' => $rechnung->id,
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', 'Fehler: ' . $e->getMessage());
        }
    }

    /**
     * PDF-Dateiname generieren.
     * 
     * Format: Fattura_2025-0001_Kundenname.pdf
     */
    private function generatePdfFilename(Rechnung $rechnung): string
    {
        $nummer = str_replace(['/', '\\', ' '], '-', $rechnung->rechnungsnummer);
        $kunde = substr(preg_replace('/[^a-zA-Z0-9]/', '', $rechnung->re_name ?? 'Kunde'), 0, 30);

        return "Fattura_{$nummer}_{$kunde}.pdf";
    }

    protected function resetGebaeudeRechnungFlag(Rechnung $rechnung): void
    {
        // Pruefen ob Rechnung einem Gebaeude zugeordnet ist
        if (!$rechnung->gebaeude_id) {
            return;
        }

        $gebaeude = Gebaeude::find($rechnung->gebaeude_id);

        // Pruefen ob Gebaeude existiert und Flag gesetzt ist
        if (!$gebaeude || !$gebaeude->rechnung_schreiben) {
            return;
        }

        // Flag zuruecksetzen
        $gebaeude->update(['rechnung_schreiben' => false]);

        Log::info('Gebaeude rechnung_schreiben Flag zurueckgesetzt', [
            'rechnung_id'   => $rechnung->id,
            'gebaeude_id'   => $gebaeude->id,
            'gebaeude_name' => $gebaeude->gebaeude_name,
            'codex'         => $gebaeude->codex,
        ]);
    }

    /**
     * ══════════════════════════════════════════════════════════════════════════════
     * METHODE FÜR: app/Http/Controllers/RechnungController.php
     * ══════════════════════════════════════════════════════════════════════════════
     * 
     * Diese Methode in die RechnungController-Klasse einfügen.
     */

    /**
     * Erstellt eine Gutschrift aus einer bestehenden Rechnung.
     * 
     * @param Rechnung $rechnung Die Original-Rechnung
     * @return \Illuminate\Http\RedirectResponse
     */
    public function gutschrift(Rechnung $rechnung)
    {
        // Nur aus Rechnungen, nicht aus Gutschriften
        if ($rechnung->typ_rechnung === 'gutschrift') {
            return back()->with('error', 'Aus einer Gutschrift kann keine weitere Gutschrift erstellt werden.');
        }

        // ⭐ Schon storniert?
        if ($rechnung->status === 'cancelled') {
            return back()->with('error', "Rechnung {$rechnung->rechnungsnummer} ist bereits storniert.");
        }

        $originalNummer = $rechnung->rechnungsnummer;

        try {
            $gutschrift = $rechnung->erstelleGutschrift();

            Log::info('Gutschrift erstellt (Voll-Storno)', [
                'original_id'       => $rechnung->id,
                'original_nummer'   => $originalNummer,
                'gutschrift_id'     => $gutschrift->id,
                'gutschrift_nummer' => $gutschrift->rechnungsnummer,
            ]);

            return redirect()
                ->route('rechnung.edit', $gutschrift->id)
                ->with('success', sprintf(
                    'Gutschrift %s wurde erstellt und als bezahlt markiert. Rechnung %s wurde storniert.',
                    $gutschrift->rechnungsnummer,
                    $originalNummer
                ));

        } catch (\RuntimeException $e) {
            // Erwartete Geschäftsregel-Verletzung (z.B. schon storniert)
            Log::warning('Gutschrift abgelehnt', [
                'rechnung_id' => $rechnung->id,
                'grund'       => $e->getMessage(),
            ]);
            return back()->with('error', $e->getMessage());

        } catch (\Exception $e) {
            // Unerwarteter technischer Fehler
            Log::error('Fehler beim Erstellen der Gutschrift', [
                'rechnung_id' => $rechnung->id,
                'error'       => $e->getMessage(),
                'trace'       => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Technischer Fehler beim Erstellen der Gutschrift: ' . $e->getMessage());
        }
    }
}
