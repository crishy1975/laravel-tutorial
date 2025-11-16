<?php

namespace App\Http\Controllers;

use App\Models\Rechnung;
use App\Models\Gebaeude;
use App\Models\FatturaProfile;
use App\Models\RechnungPosition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RechnungController extends Controller
{
    /**
     * Liste aller Rechnungen mit Filter.
     */
    public function index(Request $request)
    {
        // Filter-Parameter
        $jahr = $request->input('jahr', now()->year);
        $status = $request->input('status');
        $gebaeude_id = $request->input('gebaeude_id');
        $suche = $request->input('suche');

        // Query Builder
        $query = Rechnung::query()
            ->with(['gebaeude', 'rechnungsempfaenger', 'postadresse'])
            ->orderByDesc('jahr')
            ->orderByDesc('laufnummer');

        // Filter: Jahr
        if ($jahr) {
            $query->where('jahr', $jahr);
        }

        // Filter: Status
        if ($status) {
            if ($status === 'overdue') {
                $query->overdue();
            } else {
                $query->where('status', $status);
            }
        }

        // Filter: Gebäude
        if ($gebaeude_id) {
            $query->where('gebaeude_id', $gebaeude_id);
        }

        // Filter: Suche (Nummer oder Kundenname)
        if ($suche) {
            $query->where(function ($q) use ($suche) {
                $q->where('laufnummer', 'LIKE', "%{$suche}%")
                  ->orWhere('re_name', 'LIKE', "%{$suche}%")
                  ->orWhere('geb_codex', 'LIKE', "%{$suche}%");
            });
        }

        // Paginierung
        $rechnungen = $query->paginate(50);

        // Jahre für Filter (letzten 5 Jahre)
        $jahre = range(now()->year, now()->year - 4);

        // Gebäude für Filter
        $gebaeudeFilter = Gebaeude::orderBy('codex')->get();

        return view('rechnung.index', compact(
            'rechnungen',
            'jahre',
            'jahr',
            'status',
            'gebaeude_id',
            'suche',
            'gebaeudeFilter'
        ));
    }

    /**
     * Formular: Neue Rechnung anlegen.
     */
    public function create()
    {
        $rechnung = new Rechnung();
        $gebaeude_liste = Gebaeude::orderBy('codex')->get();
        $profile = FatturaProfile::all();

        return view('rechnung.form', compact('rechnung', 'gebaeude_liste', 'profile'));
    }

    /**
     * Neue Rechnung speichern.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'gebaeude_id'       => 'required|exists:gebaeude,id',
            'rechnungsdatum'    => 'required|date',
            'leistungsdatum'    => 'nullable|date',
            'zahlungsziel'      => 'nullable|date',
            'status'            => 'required|in:draft,sent,paid,overdue,cancelled',
            'bezahlt_am'        => 'nullable|date',
            'fattura_profile_id'=> 'nullable|exists:fattura_profile,id',
            
            // FatturaPA
            'cup'               => 'nullable|string|max:20',
            'cig'               => 'nullable|string|max:10',
            'auftrag_id'        => 'nullable|string|max:50',
            'auftrag_datum'     => 'nullable|date',
            
            // Bemerkungen
            'bemerkung'         => 'nullable|string',
            'bemerkung_kunde'   => 'nullable|string',
        ]);

        // Gebäude laden
        $gebaeude = Gebaeude::with(['rechnungsempfaenger', 'postadresse', 'fatturaProfile'])->findOrFail($validated['gebaeude_id']);

        // Rechnung automatisch aus Gebäude erstellen
        $rechnung = $gebaeude->createRechnung($validated);

        return redirect()
            ->route('rechnung.edit', $rechnung->id)
            ->with('success', "Rechnung {$rechnung->nummern} erfolgreich angelegt.");
    }

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

        return view('rechnung.form', compact('rechnung', 'gebaeude_liste', 'profile'));
    }

    /**
     * Rechnung aktualisieren.
     */
    public function update(Request $request, $id)
    {
        $rechnung = Rechnung::findOrFail($id);

        // Nur Entwürfe dürfen bearbeitet werden
        if (!$rechnung->ist_editierbar) {
            return redirect()
                ->back()
                ->with('error', 'Diese Rechnung kann nicht mehr bearbeitet werden.');
        }

        $validated = $request->validate([
            'gebaeude_id'       => 'required|exists:gebaeude,id',
            'rechnungsdatum'    => 'required|date',
            'leistungsdatum'    => 'nullable|date',
            'zahlungsziel'      => 'nullable|date',
            'status'            => 'required|in:draft,sent,paid,overdue,cancelled',
            'bezahlt_am'        => 'nullable|date',
            'fattura_profile_id'=> 'nullable|exists:fattura_profile,id',
            
            // Rechnungsempfänger
            're_name'           => 'required|string|max:255',
            're_strasse'        => 'nullable|string|max:255',
            're_hausnummer'     => 'nullable|string|max:20',
            're_plz'            => 'nullable|string|max:10',
            're_wohnort'        => 'nullable|string|max:255',
            're_provinz'        => 'nullable|string|max:100',
            're_land'           => 'nullable|string|max:2',
            're_steuernummer'   => 'nullable|string|max:50',
            're_mwst_nummer'    => 'nullable|string|max:50',
            're_codice_univoco' => 'nullable|string|max:7',
            're_pec'            => 'nullable|email|max:255',
            
            // Postadresse
            'post_name'         => 'nullable|string|max:255',
            'post_strasse'      => 'nullable|string|max:255',
            'post_hausnummer'   => 'nullable|string|max:20',
            'post_plz'          => 'nullable|string|max:10',
            'post_wohnort'      => 'nullable|string|max:255',
            'post_provinz'      => 'nullable|string|max:100',
            'post_land'         => 'nullable|string|max:2',
            'post_email'        => 'nullable|email|max:255',
            'post_pec'          => 'nullable|email|max:255',
            
            // Gebäude
            'geb_codex'         => 'nullable|string|max:50',
            'geb_name'          => 'nullable|string|max:255',
            'geb_adresse'       => 'nullable|string|max:500',
            
            // FatturaPA
            'cup'               => 'nullable|string|max:20',
            'cig'               => 'nullable|string|max:10',
            'auftrag_id'        => 'nullable|string|max:50',
            'auftrag_datum'     => 'nullable|date',
            
            // Bemerkungen
            'bemerkung'         => 'nullable|string',
            'bemerkung_kunde'   => 'nullable|string',
        ]);

        $rechnung->update($validated);

        return redirect()
            ->route('rechnung.edit', $rechnung->id)
            ->with('success', 'Rechnung erfolgreich aktualisiert.');
    }

    /**
     * Rechnung löschen (nur Entwürfe).
     */
    public function destroy($id)
    {
        $rechnung = Rechnung::findOrFail($id);

        if (!$rechnung->ist_editierbar) {
            return redirect()
                ->back()
                ->with('error', 'Nur Entwürfe können gelöscht werden.');
        }

        $nummer = $rechnung->nummern;
        $rechnung->delete();

        return redirect()
            ->route('rechnung.index')
            ->with('success', "Rechnung {$nummer} wurde gelöscht.");
    }

    // ═══════════════════════════════════════════════════════════
    // 🧾 POSITIONEN VERWALTEN
    // ═══════════════════════════════════════════════════════════

    /**
     * Neue Position zu Rechnung hinzufügen.
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

        // Position automatisch ermitteln, falls nicht angegeben
        if (!isset($validated['position'])) {
            $maxPos = $rechnung->positionen()->max('position') ?? 0;
            $validated['position'] = $maxPos + 1;
        }

        $rechnung->positionen()->create($validated);

        return redirect()
            ->route('rechnung.edit', $rechnungId)
            ->with('success', 'Position wurde hinzugefügt.');
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
     * Position löschen.
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
            ->with('success', 'Position wurde gelöscht.');
    }

    // ═══════════════════════════════════════════════════════════
    // 📄 EXPORT / PDF (Vorbereitet)
    // ═══════════════════════════════════════════════════════════

    /**
     * PDF generieren (TODO: Implementation mit DomPDF/TCPDF).
     */
    public function generatePdf($id)
    {
        $rechnung = Rechnung::with(['positionen'])->findOrFail($id);

        // TODO: PDF-Generierung implementieren
        // Beispiel mit DomPDF:
        // $pdf = PDF::loadView('rechnung.pdf', compact('rechnung'));
        // return $pdf->download("rechnung-{$rechnung->nummern}.pdf");

        return redirect()
            ->back()
            ->with('error', 'PDF-Export noch nicht implementiert.');
    }

    /**
     * FatturaPA XML generieren (TODO).
     */
    public function generateXml($id)
    {
        $rechnung = Rechnung::with(['positionen'])->findOrFail($id);

        // TODO: XML-Generierung für FatturaPA
        
        return redirect()
            ->back()
            ->with('error', 'XML-Export noch nicht implementiert.');
    }
}