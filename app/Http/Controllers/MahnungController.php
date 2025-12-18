<?php

namespace App\Http\Controllers;

use App\Models\Mahnung;
use App\Models\MahnungStufe;
use App\Models\MahnungAusschluss;
use App\Models\MahnungRechnungAusschluss;
use App\Models\Rechnung;
use App\Models\Adresse;
use App\Services\MahnungService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MahnungController extends Controller
{
    protected MahnungService $service;

    public function __construct(MahnungService $service)
    {
        $this->service = $service;
    }

    // ═══════════════════════════════════════════════════════════════════════
    // ÜBERSICHT & DASHBOARD
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Dashboard / Übersicht
     */
    public function index()
    {
        $statistiken = $this->service->getStatistiken();
        $bankAktualitaet = $this->service->getBankAktualitaet();
        
        // Letzte Mahnungen
        $letzteMahnungen = Mahnung::with(['rechnung.rechnungsempfaenger', 'stufe'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('mahnungen.index', [
            'statistiken'      => $statistiken,
            'bankAktualitaet'  => $bankAktualitaet,
            'letzteMahnungen'  => $letzteMahnungen,
        ]);
    }

    /**
     * Alle versendeten Mahnungen (Historie)
     */
    public function historie(Request $request)
    {
        $query = Mahnung::with(['rechnung.rechnungsempfaenger', 'stufe'])
            ->orderByDesc('created_at');

        // Filter: Status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter: Stufe
        if ($request->filled('stufe')) {
            $query->where('mahnstufe', $request->stufe);
        }

        // Filter: Zeitraum
        if ($request->filled('von')) {
            $query->whereDate('mahndatum', '>=', $request->von);
        }
        if ($request->filled('bis')) {
            $query->whereDate('mahndatum', '<=', $request->bis);
        }

        $mahnungen = $query->paginate(25);
        $stufen = MahnungStufe::orderBy('stufe')->get();

        return view('mahnungen.historie', [
            'mahnungen' => $mahnungen,
            'stufen'    => $stufen,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // MAHNLAUF
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Mahnlauf vorbereiten (Vorschau)
     */
    public function mahnlaufVorbereiten()
    {
        $bankAktualitaet = $this->service->getBankAktualitaet();
        $ueberfaellige = $this->service->getUeberfaelligeRechnungen();
        $stufen = MahnungStufe::getAlleAktiven();

        return view('mahnungen.mahnlauf', [
            'bankAktualitaet'  => $bankAktualitaet,
            'ueberfaellige'    => $ueberfaellige,
            'stufen'           => $stufen,
            'statistiken'      => $this->service->getStatistiken(),
        ]);
    }

    /**
     * Mahnungen erstellen (aus Vorschau)
     */
    public function mahnungenErstellen(Request $request)
    {
        $request->validate([
            'rechnung_ids'   => 'required|array|min:1',
            'rechnung_ids.*' => 'integer|exists:rechnungen,id',
        ]);

        $result = $this->service->erstelleMahnungenBatch($request->rechnung_ids);

        Log::info('Mahnungen erstellt', [
            'anzahl' => $result['anzahl'],
            'fehler' => count($result['fehler']),
        ]);

        if (count($result['fehler']) > 0) {
            return redirect()
                ->route('mahnungen.versand')
                ->with('warning', sprintf(
                    '%d Mahnungen erstellt, %d Fehler aufgetreten.',
                    $result['anzahl'],
                    count($result['fehler'])
                ));
        }

        return redirect()
            ->route('mahnungen.versand')
            ->with('success', sprintf('%d Mahnungen erstellt und bereit zum Versand.', $result['anzahl']));
    }

    // ═══════════════════════════════════════════════════════════════════════
    // VERSAND
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Versand-Übersicht (Entwürfe)
     */
    public function versand()
    {
        $entwuerfe = Mahnung::with(['rechnung.rechnungsempfaenger', 'stufe'])
            ->entwurf()
            ->orderByDesc('mahnstufe')
            ->orderByDesc('tage_ueberfaellig')
            ->get();

        // Gruppieren nach Versandart-Möglichkeit
        $mitEmail = $entwuerfe->filter(fn($m) => !empty($m->rechnung?->rechnungsempfaenger?->email));
        $ohneEmail = $entwuerfe->filter(fn($m) => empty($m->rechnung?->rechnungsempfaenger?->email));

        return view('mahnungen.versand', [
            'entwuerfe'  => $entwuerfe,
            'mitEmail'   => $mitEmail,
            'ohneEmail'  => $ohneEmail,
        ]);
    }

    /**
     * Mahnungen versenden
     */
    public function versenden(Request $request)
    {
        $request->validate([
            'mahnung_ids'   => 'required|array|min:1',
            'mahnung_ids.*' => 'integer|exists:mahnungen,id',
            'sprache'       => 'required|in:de,it',
        ]);

        $result = $this->service->versendeMahnungenBatch(
            $request->mahnung_ids,
            $request->sprache
        );

        Log::info('Mahnungen versendet', $result['statistik']);

        $message = sprintf(
            '%d Mahnungen per E-Mail versendet.',
            $result['statistik']['gesendet']
        );

        if ($result['statistik']['post_noetig'] > 0) {
            $message .= sprintf(' %d benötigen Postversand.', $result['statistik']['post_noetig']);
        }

        if ($result['statistik']['fehler'] > 0) {
            return redirect()
                ->route('mahnungen.versand')
                ->with('warning', $message . sprintf(' %d Fehler.', $result['statistik']['fehler']));
        }

        return redirect()
            ->route('mahnungen.historie')
            ->with('success', $message);
    }

    /**
     * Einzelne Mahnung als Post versendet markieren
     */
    public function alsPostVersendet(Mahnung $mahnung)
    {
        $mahnung->markiereAlsGesendet('post');

        return redirect()
            ->back()
            ->with('success', 'Mahnung als per Post versendet markiert.');
    }

    // ═══════════════════════════════════════════════════════════════════════
    // EINZELNE MAHNUNG
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Mahnung anzeigen
     */
    public function show(Mahnung $mahnung)
    {
        $mahnung->load(['rechnung.rechnungsempfaenger', 'stufe']);

        $textDe = $mahnung->generiereText('de');
        $textIt = $mahnung->generiereText('it');

        return view('mahnungen.show', [
            'mahnung' => $mahnung,
            'textDe'  => $textDe,
            'textIt'  => $textIt,
        ]);
    }

    /**
     * Mahnung stornieren
     */
    public function stornieren(Request $request, Mahnung $mahnung)
    {
        $grund = $request->input('grund');
        
        if ($mahnung->stornieren($grund)) {
            return redirect()
                ->back()
                ->with('success', 'Mahnung wurde storniert.');
        }

        return redirect()
            ->back()
            ->with('error', 'Mahnung konnte nicht storniert werden.');
    }

    /**
     * PDF herunterladen
     */
    public function downloadPdf(Mahnung $mahnung, string $sprache = 'de')
    {
        // PDF erstellen falls nicht vorhanden
        if (!$mahnung->pdf_pfad || !file_exists(storage_path('app/' . $mahnung->pdf_pfad))) {
            $mahnung->pdf_pfad = $this->service->erstellePdf($mahnung, $sprache);
            $mahnung->save();
        }

        $filename = sprintf(
            'Mahnung_%s_Stufe%d.pdf',
            $mahnung->rechnung?->volle_rechnungsnummer ?? $mahnung->id,
            $mahnung->mahnstufe
        );

        return response()->download(
            storage_path('app/' . $mahnung->pdf_pfad),
            $filename
        );
    }

    // ═══════════════════════════════════════════════════════════════════════
    // MAHNSTUFEN KONFIGURATION
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Mahnstufen anzeigen
     */
    public function stufen()
    {
        $stufen = MahnungStufe::orderBy('stufe')->get();

        return view('mahnungen.stufen', [
            'stufen' => $stufen,
        ]);
    }

    /**
     * Mahnstufe bearbeiten (Form)
     */
    public function stufeBearbeiten(MahnungStufe $stufe)
    {
        return view('mahnungen.stufe-form', [
            'stufe' => $stufe,
        ]);
    }

    /**
     * Mahnstufe speichern
     */
    public function stufeSpeichern(Request $request, MahnungStufe $stufe)
    {
        $validated = $request->validate([
            'name_de'            => 'required|string|max:100',
            'name_it'            => 'required|string|max:100',
            'tage_ueberfaellig'  => 'required|integer|min:1|max:365',
            'spesen'             => 'required|numeric|min:0|max:500',
            'betreff_de'         => 'required|string|max:255',
            'betreff_it'         => 'required|string|max:255',
            'text_de'            => 'required|string|max:5000',
            'text_it'            => 'required|string|max:5000',
            'aktiv'              => 'boolean',
        ]);

        $validated['aktiv'] = $request->boolean('aktiv');

        $stufe->update($validated);

        return redirect()
            ->route('mahnungen.stufen')
            ->with('success', "Mahnstufe \"{$stufe->name_de}\" gespeichert.");
    }

    // ═══════════════════════════════════════════════════════════════════════
    // AUSSCHLÜSSE
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Ausschlüsse anzeigen
     */
    public function ausschluesse()
    {
        $kundenAusschluesse = MahnungAusschluss::with('adresse')
            ->orderByDesc('created_at')
            ->get();

        $rechnungAusschluesse = MahnungRechnungAusschluss::with('rechnung.rechnungsempfaenger')
            ->orderByDesc('created_at')
            ->get();

        return view('mahnungen.ausschluesse', [
            'kundenAusschluesse'   => $kundenAusschluesse,
            'rechnungAusschluesse' => $rechnungAusschluesse,
        ]);
    }

    /**
     * Kunden-Ausschluss hinzufügen
     */
    public function kundeAusschliessen(Request $request)
    {
        $validated = $request->validate([
            'adresse_id' => 'required|exists:adressen,id',
            'grund'      => 'nullable|string|max:255',
            'bis_datum'  => 'nullable|date|after:today',
        ]);

        MahnungAusschluss::setAusschluss(
            $validated['adresse_id'],
            $validated['grund'] ?? null,
            isset($validated['bis_datum']) ? \Carbon\Carbon::parse($validated['bis_datum']) : null
        );

        return redirect()
            ->back()
            ->with('success', 'Kunde vom Mahnlauf ausgeschlossen.');
    }

    /**
     * Kunden-Ausschluss entfernen
     */
    public function kundeAusschlussEntfernen(int $adresseId)
    {
        MahnungAusschluss::entferneAusschluss($adresseId);

        return redirect()
            ->back()
            ->with('success', 'Ausschluss entfernt.');
    }

    /**
     * Rechnung vom Mahnlauf ausschließen
     */
    public function rechnungAusschliessen(Request $request)
    {
        $validated = $request->validate([
            'rechnung_id' => 'required|exists:rechnungen,id',
            'grund'       => 'nullable|string|max:255',
            'bis_datum'   => 'nullable|date|after:today',
        ]);

        MahnungRechnungAusschluss::setAusschluss(
            $validated['rechnung_id'],
            $validated['grund'] ?? null,
            isset($validated['bis_datum']) ? \Carbon\Carbon::parse($validated['bis_datum']) : null
        );

        return redirect()
            ->back()
            ->with('success', 'Rechnung vom Mahnlauf ausgeschlossen.');
    }

    /**
     * Rechnungs-Ausschluss entfernen
     */
    public function rechnungAusschlussEntfernen(int $rechnungId)
    {
        MahnungRechnungAusschluss::entferneAusschluss($rechnungId);

        return redirect()
            ->back()
            ->with('success', 'Ausschluss entfernt.');
    }

    // ═══════════════════════════════════════════════════════════════════════
    // API / AJAX
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Statistiken für Dashboard-Widget
     */
    public function apiStatistiken()
    {
        return response()->json([
            'statistiken'     => $this->service->getStatistiken(),
            'bank_aktualitaet' => $this->service->getBankAktualitaet(),
        ]);
    }
}
