<?php

namespace App\Http\Controllers;

use App\Models\Mahnung;
use App\Models\MahnungStufe;
use App\Models\MahnungAusschluss;
use App\Models\MahnungRechnungAusschluss;
use App\Models\MahnungEinstellung;
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
        $letzteMahnungen = Mahnung::with([
            'rechnung.rechnungsempfaenger', 
            'rechnung.gebaeude.postadresse',  // ⭐ Postadresse laden!
            'stufe'
        ])
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
        $query = Mahnung::with([
            'rechnung.rechnungsempfaenger', 
            'rechnung.gebaeude.postadresse',  // ⭐ Postadresse laden!
            'stufe'
        ])
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
     * ⭐ VEREINFACHT: Mahnlauf vorbereiten
     * 
     * Zeigt alle überfälligen Rechnungen mit Status:
     * - ist_mahnbar = true → kann gemahnt werden
     * - ist_mahnbar = false → in Wartezeit oder offener Entwurf
     */
    public function mahnlaufVorbereiten(Request $request)
    {
        $bankAktualitaet = $this->service->getBankAktualitaet();
        $stufen = MahnungStufe::getAlleAktiven();
        
        // Alle überfälligen Rechnungen (mit ist_mahnbar Flag)
        $ueberfaellige = $this->service->getUeberfaelligeRechnungen();
        
        // Gesperrte Rechnungen separat laden
        $gesperrte = $this->service->getGesperrteRechnungen();
        
        // Einstellungen für Anzeige
        $einstellungen = [
            'zahlungsfrist_tage'            => MahnungEinstellung::getZahlungsfristTage(),
            'wartezeit_zwischen_mahnungen'  => MahnungEinstellung::getWartezeitZwischenMahnungen(),
        ];

        return view('mahnungen.mahnlauf', [
            'bankAktualitaet'  => $bankAktualitaet,
            'ueberfaellige'    => $ueberfaellige,
            'stufen'           => $stufen,
            'statistiken'      => $this->service->getStatistiken(),
            'gesperrte'        => $gesperrte,
            'einstellungen'    => $einstellungen,
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
     * 
     * ⭐ E-Mail wird aus POSTADRESSE des Gebäudes geholt!
     */
    public function versand()
    {
        $entwuerfe = Mahnung::with([
            'rechnung.rechnungsempfaenger', 
            'rechnung.gebaeude.postadresse',  // ⭐ Postadresse laden!
            'stufe'
        ])
            ->entwurf()
            ->orderByDesc('mahnstufe')
            ->orderByDesc('tage_ueberfaellig')
            ->get();

        // ⭐ Gruppieren nach Versandart-Möglichkeit (Postadresse-E-Mail!)
        $mitEmail = $entwuerfe->filter(function($m) {
            $postEmail = $m->rechnung?->gebaeude?->postadresse?->email;
            $rechnungEmail = $m->rechnung?->rechnungsempfaenger?->email;
            return !empty($postEmail) || !empty($rechnungEmail);
        });
        
        $ohneEmail = $entwuerfe->filter(function($m) {
            $postEmail = $m->rechnung?->gebaeude?->postadresse?->email;
            $rechnungEmail = $m->rechnung?->rechnungsempfaenger?->email;
            return empty($postEmail) && empty($rechnungEmail);
        });

        return view('mahnungen.versand', [
            'entwuerfe'  => $entwuerfe,
            'mitEmail'   => $mitEmail,
            'ohneEmail'  => $ohneEmail,
        ]);
    }

    /**
     * Mahnungen versenden (ZWEISPRACHIG)
     */
    public function versenden(Request $request)
    {
        $request->validate([
            'mahnung_ids'   => 'required|array|min:1',
            'mahnung_ids.*' => 'integer|exists:mahnungen,id',
        ]);

        $result = $this->service->versendeMahnungenBatch($request->mahnung_ids);

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
     * ⭐ AJAX: Einzelne Mahnung versenden (für Progress-Anzeige)
     * 
     * Route: POST /mahnungen/{mahnung}/versende-einzeln
     */
    public function versendeEinzeln(Mahnung $mahnung)
    {
        try {
            // Prüfen ob noch Entwurf
            if ($mahnung->status !== 'entwurf') {
                return response()->json([
                    'ok'      => false,
                    'message' => 'Mahnung wurde bereits versendet.',
                ], 400);
            }

            // Versenden über Service
            $result = $this->service->versendeMahnung($mahnung);

            if ($result['erfolg']) {
                return response()->json([
                    'ok'      => true,
                    'message' => 'Versendet an ' . ($result['email'] ?? 'unbekannt'),
                    'email'   => $result['email'] ?? null,
                ]);
            }

            // Postversand nötig?
            if ($result['post_noetig'] ?? false) {
                return response()->json([
                    'ok'          => false,
                    'post_noetig' => true,
                    'message'     => $result['grund'] ?? 'Postversand erforderlich',
                ]);
            }

            return response()->json([
                'ok'      => false,
                'message' => $result['grund'] ?? 'Unbekannter Fehler',
            ], 500);

        } catch (\Exception $e) {
            Log::error('AJAX Mahnungsversand fehlgeschlagen', [
                'mahnung_id' => $mahnung->id,
                'error'      => $e->getMessage(),
            ]);

            return response()->json([
                'ok'      => false,
                'message' => $e->getMessage(),
            ], 500);
        }
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
        $mahnung->load([
            'rechnung.rechnungsempfaenger', 
            'rechnung.gebaeude.postadresse',  // ⭐ Postadresse laden!
            'stufe'
        ]);

        // Separate Texte für die Tab-Vorschau
        $textDe = $mahnung->generiereTextDe();
        $textIt = $mahnung->generiereTextIt();

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
     * PDF herunterladen oder im Browser anzeigen
     * 
     * ?preview=1 → Im Browser anzeigen (inline)
     * ?regenerate=1 → PDF neu generieren (Cache umgehen)
     * ohne Parameter → Download
     */
    public function downloadPdf(Request $request, Mahnung $mahnung)
    {
        // ⭐ Force-Regenerate: Altes PDF löschen und neu erstellen
        if ($request->boolean('regenerate')) {
            if ($mahnung->pdf_pfad && file_exists(storage_path('app/' . $mahnung->pdf_pfad))) {
                @unlink(storage_path('app/' . $mahnung->pdf_pfad));
            }
            $mahnung->pdf_pfad = null;
        }

        // PDF erstellen falls nicht vorhanden (immer zweisprachig)
        if (!$mahnung->pdf_pfad || !file_exists(storage_path('app/' . $mahnung->pdf_pfad))) {
            $mahnung->pdf_pfad = $this->service->erstellePdf($mahnung);
            $mahnung->save();
        }

        $filename = sprintf(
            'Mahnung_Sollecito_%s_Stufe%d.pdf',
            str_replace('/', '-', $mahnung->rechnungsnummer_anzeige),
            $mahnung->mahnstufe
        );

        $path = storage_path('app/' . $mahnung->pdf_pfad);

        // ⭐ Preview = Im Browser anzeigen (inline)
        if ($request->boolean('preview')) {
            return response()->file($path, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $filename . '"',
            ]);
        }

        // ⭐ Download
        return response()->download($path, $filename);
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
    // EINSTELLUNGEN
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Einstellungen anzeigen
     */
    public function einstellungen()
    {
        $einstellungen = MahnungEinstellung::alle();

        return view('mahnungen.einstellungen', [
            'einstellungen' => $einstellungen,
        ]);
    }

    /**
     * Einstellungen speichern
     */
    public function einstellungenSpeichern(Request $request)
    {
        $validated = $request->validate([
            'zahlungsfrist_tage'           => 'required|integer|min:1|max:365',
            'wartezeit_zwischen_mahnungen' => 'required|integer|min:1|max:365',
            'min_tage_ueberfaellig'        => 'required|integer|min:0|max:365',
        ]);

        foreach ($validated as $schluessel => $wert) {
            MahnungEinstellung::set($schluessel, $wert);
        }

        Log::info('Mahnwesen Einstellungen aktualisiert', [
            'einstellungen' => $validated,
            'user_id' => auth()->id(),
        ]);

        return redirect()
            ->back()
            ->with('success', 'Einstellungen erfolgreich gespeichert.');
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
