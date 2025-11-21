<?php

namespace App\Http\Controllers;

use App\Models\Rechnung;
use App\Models\Gebaeude;
use App\Models\FatturaProfile;
use App\Models\RechnungPosition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Carbon;

class RechnungController extends Controller
{
    /**
     * Liste aller Rechnungen mit Filter.
     */
    /**
     * Liste aller Rechnungen mit Filter.
     */
    public function index(Request $request)
    {
        // ------------------------------
        // 1. Filterwerte aus Request holen
        // ------------------------------
        $nummer = $request->input('nummer');   // "Nummer" = Darstellung wie 2025/0001
        $codex  = $request->input('codex');    // Gebäudecodex
        $suche  = $request->input('suche');    // Gebäude / Empfänger / Postadresse

        // Standard: aktuelles Jahr, 01.01. - 31.12.
        $year = Carbon::now()->year;

        // Falls der User nichts schickt, verwenden wir die Jahresgrenzen.
        // Falls er Werte schickt, werden diese übernommen.
        $datumVon = $request->input('datum_von')
            ?: Carbon::create($year, 1, 1)->format('Y-m-d');

        $datumBis = $request->input('datum_bis')
            ?: Carbon::create($year, 12, 31)->format('Y-m-d');

        // ------------------------------
        // 2. Basis-Query aufbauen
        // ------------------------------
        $query = Rechnung::query()
            // Gebäude gleich mitladen, um N+1 zu vermeiden (Snapshot-Felder hast du aber ohnehin)
            ->with('gebaeude');

        // ------------------------------
        // 3. Filter: Rechnungsnummer
        //
        // Deine "Nummer" ist ein Accessor:
        //   getNummernAttribute() => "jahr/laufnummer"
        // In der DB sind die Felder: jahr, laufnummer
        //
        // Wir simulieren die gleiche Darstellung in SQL:
        //   CONCAT(jahr, '/', LPAD(laufnummer, 4, '0'))
        //
        // Hinweis: funktioniert so in MySQL/MariaDB (bei dir der Fall).
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
        //    -> Snapshot-Feld an Rechnung: geb_codex
        //    -> zusätzlich sicherheitshalber über Relation gebaeude.codex
        // ------------------------------
        if (!empty($codex)) {
            $like = '%' . $codex . '%';

            $query->where(function ($q) use ($like) {
                // Snapshot am Rechnungseintrag
                $q->where('geb_codex', 'like', $like)

                    // ODER Codex im verknüpften Gebäude
                    ->orWhereHas('gebaeude', function ($sub) use ($like) {
                        $sub->where('codex', 'like', $like);
                    });
            });
        }

        // ------------------------------
        // 5. Filter: Suche
        //    in:
        //    - Gebäudename  (Snapshot: geb_name)
        //    - Rechnungsempfänger (Snapshot: re_name)
        //    - Postadresse  (Snapshot: post_name)
        //
        // Optional könntest du hier auch Strasse/Ort mit aufnehmen, aber
        // du wolltest explizit Name-Felder.
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
        // 6. Filter: Datum (Rechnungsdatum)
        //    - Spalte: rechnungsdatum
        //    - Standard: aktuelles Jahr (datum_von/bis oben)
        // ------------------------------
        if (!empty($datumVon) && !empty($datumBis)) {
            $query->whereBetween('rechnungsdatum', [$datumVon, $datumBis]);
        } elseif (!empty($datumVon)) {
            $query->whereDate('rechnungsdatum', '>=', $datumVon);
        } elseif (!empty($datumBis)) {
            $query->whereDate('rechnungsdatum', '<=', $datumBis);
        }

        // ------------------------------
        // 7. Sortierung & Pagination
        //    - neueste Rechnungen zuerst
        // ------------------------------
        $rechnungen = $query
            ->orderByDesc('rechnungsdatum')
            ->paginate(25);

        // ------------------------------
        // 8. View zurückgeben
        //    - Filterwerte wieder mitgeben, damit sie im View
        //      in den Inputs vorausgefüllt werden
        // ------------------------------
        return view('rechnung.index', [
            'rechnungen' => $rechnungen,
            'nummer'     => $nummer,
            'codex'      => $codex,
            'suche'      => $suche,
            'datumVon'   => $datumVon,
            'datumBis'   => $datumBis,
        ]);
    }

    /**
     * Neue Rechnung aus einem Gebäude erstellen.
     * 
     * Diese Methode erstellt automatisch eine Rechnung inkl.:
     * - Rechnungsempfänger & Postadresse (Snapshot)
     * - Gebäude-Informationen (Snapshot)
     * - FatturaPA-Profile (Snapshot)
     * - Alle aktiven Artikel als Rechnungspositionen
     */
    public function create(Request $request)
    {
        // Gebäude-ID ist Pflicht für die automatische Rechnungserstellung
        if (!$request->filled('gebaeude_id')) {
            return redirect()
                ->route('rechnung.index')
                ->with('error', 'Bitte wählen Sie zuerst ein Gebäude aus.');
        }

        try {
            // Gebäude laden
            $gebaeude = Gebaeude::findOrFail($request->integer('gebaeude_id'));

            // Prüfen, ob Gebäude die nötigen Daten hat
            if (!$gebaeude->rechnungsempfaenger_id || !$gebaeude->postadresse_id) {
                return redirect()
                    ->route('gebaeude.edit', $gebaeude->id)
                    ->with('error', 'Bitte hinterlegen Sie zuerst einen Rechnungsempfänger und eine Postadresse für dieses Gebäude.');
            }

            // Rechnung automatisch aus Gebäude erstellen
            // Diese Methode übernimmt automatisch:
            // - Alle aktiven Artikel
            // - Rechnungsempfänger & Postadresse (Snapshot)
            // - FatturaPA-Daten
            // - Preisaufschläge
            $rechnung = Rechnung::createFromGebaeude($gebaeude);

            // Direkt zum Bearbeitungsformular weiterleiten
            return redirect()
                ->route('rechnung.edit', $rechnung->id)
                ->with('success', "Rechnung {$rechnung->nummern} wurde aus Gebäude {$gebaeude->codex} erstellt.");
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
            'gebaeude_id'       => 'required|exists:gebaeude,id',
            'rechnungsdatum'    => 'required|date',
            'zahlungsziel'      => 'nullable|date',
            'status'            => 'required|in:draft,sent,paid,overdue,cancelled',
            'bezahlt_am'        => 'nullable|date',
            'fattura_profile_id' => 'nullable|exists:fattura_profile,id',

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
            'zahlungsziel'       => ['nullable', 'date'],
            'status'             => ['required', Rule::in(['draft', 'sent', 'paid', 'overdue', 'cancelled'])],
            'typ_rechnung'       => ['required', Rule::in(['rechnung', 'gutschrift'])],
            'bezahlt_am'         => ['nullable', 'date'],

            // Rechnungs-/Leistungsdaten (Text, kein Datum mehr)
            'rechnungsdaten'     => ['nullable', 'string', 'max:255'],

            // Fattura-Profil
            'fattura_profile_id' => ['nullable', 'exists:fattura_profile,id'],

            // FatturaPA / öffentliche Aufträge
            'cup'                => ['nullable', 'string', 'max:20'],
            'cig'                => ['nullable', 'string', 'max:10'],
            'auftrag_id'         => ['nullable', 'string', 'max:50'],
            'auftrag_datum'      => ['nullable', 'date'],

            // Texte / Bemerkungen
            'bemerkung'          => ['nullable', 'string'],
            'bemerkung_kunde'    => ['nullable', 'string'],
            'zahlungsbedingungen' => ['nullable', 'string'],
        ]);

        \Log::info('Validation OK', ['validated_keys' => array_keys($validated)]);

        // Felder in das Modell schreiben
        $rechnung->fill($validated);

        // Falls du bei Profil-Wechsel noch Snapshot-Felder setzen willst,
        // kannst du das hier machen:
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
                // Profil entfernt → Snapshots leeren
                $rechnung->profile_bezeichnung = null;
                $rechnung->mwst_satz           = null;
                $rechnung->split_payment       = false;
                $rechnung->ritenuta            = false;
                $rechnung->ritenuta_prozent    = null;
            }
        }

        // Speichern
        $rechnung->save();
        \Log::info('Update ausgeführt', ['neue_rechnungsdaten' => $rechnung->rechnungsdaten]);

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
    /**
     * Rechnung löschen (nur Entwürfe).
     */
    public function destroy($id)
    {
        // Auch gelöschte Rechnungen laden, falls nötig:
        // hier reicht findOrFail, weil wir nur aktive Entwürfe löschen.
        $rechnung = Rechnung::findOrFail($id);

        if (!$rechnung->ist_editierbar) {
            return redirect()
                ->back()
                ->with('error', 'Nur Entwürfe können gelöscht werden.');
        }

        // Lesbare Rechnungsnummer merken (Accessor "nummern": jahr/laufnummer)
        $nummer = $rechnung->nummern;

        // Wenn die Positionen ggf. Foreign Keys auf rechnungen.id haben,
        // sollten wir die Positionen vor der Rechnung löschen.
        // Falls du ON DELETE CASCADE in der DB hast, kannst du den Block weglassen.
        \DB::transaction(function () use ($rechnung) {
            // Positionen löschen
            // (falls RechnungPosition kein SoftDeletes benutzt, ist das ein Hard-Delete)
            $rechnung->positionen()->delete();

            // Rechnung wirklich aus der DB entfernen (kein SoftDelete)
            $rechnung->forceDelete();
        });

        return redirect()
            ->route('rechnung.index')
            ->with('success', "Rechnung {$nummer} wurde endgültig gelöscht.");
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
