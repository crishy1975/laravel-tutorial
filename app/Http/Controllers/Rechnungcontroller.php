<?php

namespace App\Http\Controllers;

use App\Models\Rechnung;
use App\Models\Gebaeude;
use App\Models\FatturaProfile;
use App\Models\RechnungPosition;
use App\Enums\Zahlungsbedingung;  // ← NEU
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Carbon;
use App\Services\FatturaXmlGenerator;
use App\Models\FatturaXmlLog;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use App\Models\RechnungLog;
use App\Enums\RechnungLogTyp;

class RechnungController extends Controller
{
    /**
     * Liste aller Rechnungen mit Filter.
     */
    public function index(Request $request)
    {
        // ------------------------------
        // 1. Filterwerte aus Request holen
        // ------------------------------
        $nummer = $request->input('nummer');
        $codex  = $request->input('codex');
        $suche  = $request->input('suche');

        $year = Carbon::now()->year;

        $datumVon = $request->input('datum_von')
            ?: Carbon::create($year, 1, 1)->format('Y-m-d');

        $datumBis = $request->input('datum_bis')
            ?: Carbon::create($year, 12, 31)->format('Y-m-d');

        // ⭐ NEU: Status-Filter
        $statusFilter = $request->input('status_filter');

        // ------------------------------
        // 2. Basis-Query aufbauen
        // ------------------------------
        $query = Rechnung::query()->with('gebaeude');

        // ------------------------------
        // 3. Filter: Rechnungsnummer
        // ------------------------------
        if (!empty($nummer)) {
            $like = '%' . $nummer . '%';
            $query->whereRaw(
                "CONCAT(jahr, '/', LPAD(laufnummer, 4, '0')) LIKE ?",
                [$like]
            );
        }

        // ------------------------------
        // 4. Filter: Codex
        // ------------------------------
        if (!empty($codex)) {
            $like = '%' . $codex . '%';
            $query->where(function ($q) use ($like) {
                $q->where('geb_codex', 'like', $like)
                    ->orWhereHas('gebaeude', function ($sub) use ($like) {
                        $sub->where('codex', 'like', $like);
                    });
            });
        }

        // ------------------------------
        // 5. Filter: Suche
        // ------------------------------
        if (!empty($suche)) {
            $like = '%' . $suche . '%';
            $query->where(function ($q) use ($like) {
                $q->where('geb_name', 'like', $like)
                    ->orWhere('re_name', 'like', $like)
                    ->orWhere('post_name', 'like', $like);
            });
        }

        // ------------------------------
        // 6. Filter: Datum
        // ------------------------------
        if (!empty($datumVon) && !empty($datumBis)) {
            $query->whereBetween('rechnungsdatum', [$datumVon, $datumBis]);
        } elseif (!empty($datumVon)) {
            $query->whereDate('rechnungsdatum', '>=', $datumVon);
        } elseif (!empty($datumBis)) {
            $query->whereDate('rechnungsdatum', '<=', $datumBis);
        }

        // ------------------------------
        // ⭐ NEU: 7. Filter: Zahlungsstatus
        // ------------------------------
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

        // ------------------------------
        // 8. Sortierung & Pagination
        // ------------------------------
        $rechnungen = $query
            ->orderByDesc('rechnungsdatum')
            ->paginate(25);

        // ------------------------------
        // ⭐ NEU: Statistiken berechnen
        // ------------------------------
        $stats = [
            'unbezahlt_anzahl' => Rechnung::unbezahlt()->count(),
            'unbezahlt_summe' => Rechnung::unbezahlt()->sum('zahlbar_betrag'),
            'ueberfaellig_anzahl' => Rechnung::ueberfaellig()->count(),
            'ueberfaellig_summe' => Rechnung::ueberfaellig()->sum('zahlbar_betrag'),
            'bald_faellig_anzahl' => Rechnung::baldFaellig(7)->count(),
        ];

        // ------------------------------
        // 9. View zurückgeben
        // ------------------------------
        return view('rechnung.index', [
            'rechnungen'    => $rechnungen,
            'nummer'        => $nummer,
            'codex'         => $codex,
            'suche'         => $suche,
            'datumVon'      => $datumVon,
            'datumBis'      => $datumBis,
            'statusFilter'  => $statusFilter,  // ← NEU
            'stats'         => $stats,         // ← NEU
        ]);
    }

    /**
     * Neue Rechnung aus einem Gebäude erstellen.
     */
    public function create(Request $request)
    {
        if (!$request->filled('gebaeude_id')) {
            return redirect()
                ->route('rechnung.index')
                ->with('error', 'Bitte wählen Sie zuerst ein Gebäude aus.');
        }

        try {
            $gebaeude = Gebaeude::findOrFail($request->integer('gebaeude_id'));

            if (!$gebaeude->rechnungsempfaenger_id || !$gebaeude->postadresse_id) {
                return redirect()
                    ->route('gebaeude.edit', $gebaeude->id)
                    ->with('error', 'Bitte hinterlegen Sie zuerst einen Rechnungsempfänger und eine Postadresse für dieses Gebäude.');
            }

            $rechnung = Rechnung::createFromGebaeude($gebaeude);

            return redirect()
                ->route('rechnung.edit', $rechnung->id)
                ->with('success', "Rechnung {$rechnung->rechnungsnummer} wurde aus Gebäude {$gebaeude->codex} erstellt.");
        } catch (\Exception $e) {
            \Log::error('Fehler beim Erstellen der Rechnung aus Gebäude', [
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
            'codice_commessa'    => 'nullable|string|max:100',  // ⭐ NEU
            'auftrag_id'         => 'nullable|string|max:50',
            'auftrag_datum'      => 'nullable|date',
            'bemerkung'          => 'nullable|string',
            'bemerkung_kunde'    => 'nullable|string',
        ]);

        // Status default auf 'draft' setzen wenn nicht vorhanden
        if (!isset($validated['status'])) {
            $validated['status'] = 'draft';
        }

        // ⭐ NEU: Zahlungsbedingungen Default
        if (!isset($validated['zahlungsbedingungen'])) {
            $validated['zahlungsbedingungen'] = 'netto_30';
        }

        $gebaeude = Gebaeude::with(['rechnungsempfaenger', 'postadresse', 'fatturaProfile'])->findOrFail($validated['gebaeude_id']);
        $rechnung = $gebaeude->createRechnung($validated);

        return redirect()
            ->route('rechnung.edit', $rechnung->id)
            ->with('success', "Rechnung {$rechnung->rechnungsnummer} erfolgreich angelegt.");
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

        // ⭐ NEU: Zahlungsbedingungen für Dropdown
        $zahlungsbedingungen = Zahlungsbedingung::options();

        return view('rechnung.form', compact('rechnung', 'gebaeude_liste', 'profile', 'zahlungsbedingungen'));
    }

    /**
     * Rechnung aktualisieren.
     */
    public function update(Request $request, $id)
    {
        \Log::info('=== RECHNUNG UPDATE START ===', ['id' => $id]);

        $rechnung = Rechnung::findOrFail($id);
        \Log::info('Rechnung geladen', ['re_name' => $rechnung->re_name]);

        if (!$rechnung->ist_editierbar) {
            \Log::info('Rechnung nicht editierbar');
            return redirect()
                ->back()
                ->with('error', 'Diese Rechnung kann nicht mehr bearbeitet werden.');
        }

        // Welche Felder dürfen aus dem Formular übernommen werden?
        $validated = $request->validate([
            // Basisdaten
            'rechnungsdatum'     => ['required', 'date'],
            'zahlungsbedingungen' => ['nullable', 'in:sofort,netto_7,netto_14,netto_30,netto_60,netto_90,netto_120,bezahlt'],
            'zahlungsziel'       => ['nullable', 'date'],
            'status'             => ['nullable', Rule::in(['draft', 'sent', 'paid', 'overdue', 'cancelled'])],
            'typ_rechnung'       => ['required', Rule::in(['rechnung', 'gutschrift'])],
            'bezahlt_am'         => ['nullable', 'date'],

            // Leistungsdaten (Text, kein Datum mehr)
            'leistungsdaten'     => ['nullable', 'string', 'max:255'],

            // Fattura-Profil
            'fattura_profile_id' => ['nullable', 'exists:fattura_profile,id'],

            // FatturaPA / öffentliche Aufträge
            'cup'                => ['nullable', 'string', 'max:20'],
            'cig'                => ['nullable', 'string', 'max:10'],
            'codice_commessa'    => ['nullable', 'string', 'max:100'],  // ⭐ NEU
            'auftrag_id'         => ['nullable', 'string', 'max:50'],
            'auftrag_datum'      => ['nullable', 'date'],

            // Texte / Bemerkungen
            'bemerkung'          => ['nullable', 'string'],
            'bemerkung_kunde'    => ['nullable', 'string'],

            // ⭐ NEU: Preis-Aufschlag (readonly, nur zur Info)
            'aufschlag_prozent'  => ['nullable', 'numeric', 'min:-100', 'max:100'],
            'aufschlag_typ'      => ['nullable', 'string', 'in:global,individuell,keiner'],
        ]);

        \Log::info('Validation OK', ['validated_keys' => array_keys($validated)]);

        // Status bleibt unverändert wenn nicht mitgesendet (da Feld disabled ist)
        // Felder in das Modell schreiben
        $rechnung->fill($validated);

        // Falls du bei Profil-Wechsel noch Snapshot-Felder setzen willst
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
        \Log::info('Update ausgeführt', ['neue_leistungsdaten' => $rechnung->leistungsdaten]);

        $rechnung->refresh();
        \Log::info('Nach Refresh', ['status' => $rechnung->status, 'typ_rechnung' => $rechnung->typ_rechnung]);

        \Log::info('=== RECHNUNG UPDATE ENDE ===');

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

        $nummer = $rechnung->rechnungsnummer;

        \DB::transaction(function () use ($rechnung) {
            $rechnung->positionen()->delete();
            $rechnung->forceDelete();
        });

        return redirect()
            ->route('rechnung.index')
            ->with('success', "Rechnung {$nummer} wurde endgültig gelöscht.");
    }

    // ═══════════════════════════════════════════════════════════
    // 💰 NEU: ZAHLUNGS-AKTIONEN
    // ═══════════════════════════════════════════════════════════

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
     */
    public function send($id)
    {
        $rechnung = Rechnung::findOrFail($id);

        if ($rechnung->status !== 'draft') {
            return redirect()
                ->back()
                ->with('error', 'Nur Entwürfe können versendet werden.');
        }

        $rechnung->update(['status' => 'sent']);

        // TODO: Hier PDF generieren und per E-Mail versenden

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

        return redirect()
            ->back()
            ->with('error', 'PDF-Export noch nicht implementiert.');
    }


    // ═══════════════════════════════════════════════════════════
    // 📊 NEU: AJAX HELPERS
    // ═══════════════════════════════════════════════════════════

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


    // ═══════════════════════════════════════════════════════════
    // 🧾 FATTURAPA XML GENERATION
    // ═══════════════════════════════════════════════════════════

    public function generateXml(int $id)
    {
        $rechnung = Rechnung::findOrFail($id);

        // Prüfe ob Rechnung bereits ein XML hat
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
                'Es existiert bereits ein XML für diese Rechnung (Progressivo: %s). Möchten Sie ein neues generieren?',
                $existingLog->progressivo_invio
            ));
        }

        try {
            $generator = new FatturaXmlGenerator();
            $log = $generator->generate($rechnung);

            // ⭐ NEU: Automatisch in RechnungLog eintragen
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

            // ⭐ NEU: Fehler auch in RechnungLog eintragen
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
     * Regeneriert XML (überschreibt vorheriges).
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

            // ⭐ NEU: Automatisch in RechnungLog eintragen
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

            // ⭐ NEU: Fehler auch in RechnungLog eintragen
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
     * Lädt XML-Datei herunter.
     * 
     * Route: GET /rechnung/{id}/xml/download
     * Route: GET /fattura-xml/{logId}/download
     */
    public function downloadXml(int $id)
    {
        // Neuestes erfolgreiches XML für diese Rechnung
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
     * Lädt XML-Datei direkt über Log-ID herunter.
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
     * Zeigt alle XML-Logs für eine Rechnung.
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
     * Löscht ein XML-Log (und Datei).
     * 
     * Route: DELETE /fattura-xml/{logId}
     */
    public function deleteXmlLog(int $logId)
    {
        $log = FatturaXmlLog::findOrFail($logId);

        // Prüfe ob bereits gesendet
        if (in_array($log->status, [
            FatturaXmlLog::STATUS_SENT,
            FatturaXmlLog::STATUS_DELIVERED,
            FatturaXmlLog::STATUS_ACCEPTED,
        ])) {
            return back()->withErrors([
                'error' => 'XML wurde bereits versendet und kann nicht gelöscht werden!'
            ]);
        }

        $rechnungId = $log->rechnung_id;

        // Dateien löschen
        if ($log->xmlExists()) {
            Storage::delete($log->xml_file_path);
        }

        if ($log->p7mExists()) {
            Storage::delete($log->p7m_file_path);
        }

        $log->delete();

        return redirect()
            ->route('rechnung.edit', $rechnungId)
            ->with('success', 'XML-Log gelöscht');
    }

    /**
     * Zeigt Debug-Info für XML-Generierung.
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

        $profil = \App\Models\Unternehmensprofil::first();

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


    // ═══════════════════════════════════════════════════════════════════════════
    // 📄 PDF-GENERIERUNG FÜR RECHNUNGEN
    // ═══════════════════════════════════════════════════════════════════════════
    //
    // INTEGRATION IN: app/Http/Controllers/RechnungController.php
    //
    // ═══════════════════════════════════════════════════════════════════════════




    public function downloadPdf(int $id)
    {
        $rechnung = Rechnung::with(['positionen', 'fatturaProfile'])
            ->findOrFail($id);

        // ⭐ Unternehmensprofil laden
        $unternehmen = \App\Models\Unternehmensprofil::first();

        // PDF generieren
        $pdf = Pdf::loadView('rechnung.pdf', [
            'rechnung' => $rechnung,
            'unternehmen' => $unternehmen, // ⭐ NEU
        ])
            ->setPaper('a4', 'portrait')
            ->setOption('defaultFont', 'DejaVu Sans');

        // Dateiname (Schrägstriche ersetzen für Dateisystem)
        $filename = sprintf(
            'Rechnung_%s_%s.pdf',
            str_replace(['/', '\\'], '-', $rechnung->rechnungsnummer), // ⭐ Schrägstriche ersetzen
            $rechnung->rechnungsdatum->format('Y-m-d')
        );

        // Download
        return $pdf->download($filename);
    }

    /**
     * Zeigt PDF im Browser (Preview)
     * 
     * @param int $id Rechnungs-ID
     * @return \Illuminate\Http\Response
     */
    public function previewPdf(int $id)
    {
        $rechnung = Rechnung::with(['positionen', 'fatturaProfile'])
            ->findOrFail($id);

        // ⭐ Unternehmensprofil laden
        $unternehmen = \App\Models\Unternehmensprofil::first();

        // PDF generieren
        $pdf = Pdf::loadView('rechnung.pdf', [
            'rechnung' => $rechnung,
            'unternehmen' => $unternehmen, // ⭐ NEU
        ])
            ->setPaper('a4', 'portrait')
            ->setOption('defaultFont', 'DejaVu Sans');

        // Im Browser anzeigen
        return $pdf->stream('rechnung.pdf');
    }

    /**
     * PDF als Email versenden
     * 
     * @param int $id Rechnungs-ID
     * @return \Illuminate\Http\RedirectResponse
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

        // Email versenden (Beispiel mit Laravel Mail)
        \Mail::to($rechnung->post_email)->send(
            new \App\Mail\RechnungMail($rechnung, $pdf->output(), $filename)
        );

        return back()->with('success', 'Rechnung per Email versendet!');
    }


    // ═══════════════════════════════════════════════════════════════════════════
    // ENDE DER CONTROLLER-METHODEN
    // ═══════════════════════════════════════════════════════════════════════════

}
