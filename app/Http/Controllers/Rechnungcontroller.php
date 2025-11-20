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
     * Formular: Neue Rechnung anlegen.
     */
    /**
     * Neue Rechnung sofort anlegen und direkt ins Bearbeitungsformular springen.
     */
    public function create(Request $request)
    {
        // Neue Instanz
        $rechnung = new Rechnung();

        // Falls von einem Gebäude aus aufgerufen (?gebaeude_id=...)
        if ($request->filled('gebaeude_id')) {
            $rechnung->gebaeude_id = $request->integer('gebaeude_id');

            // Optional: Hier könntest du später Daten aus dem Gebäude in die Rechnung übernehmen
            // (Kunde, Adresse, Beschreibung, etc.)
            //
            // $gebaeude = Gebaeude::find($rechnung->gebaeude_id);
            // if ($gebaeude) {
            //     $rechnung->kunde_id = $gebaeude->kunde_id;
            //     // weitere Felder...
            // }
        }

        // Standard-/Pflichtwerte setzen
        $rechnung->status         = 'draft';          // Entwurf
        $rechnung->typ_rechnung   = 'rechnung';       // Standard: Rechnung (kein Gutschrift)
        $rechnung->rechnungsdatum = now();            // Heute
        $rechnung->jahr           = now()->year;     // Aktuelles Jahr
        $rechnung->laufnummer     = 1;                // Vorläufige Laufnummer (wird später gesetzt)
        $rechnung->re_name = 'Rechnungsempfänger noch nicht gewählt';
        $rechnung->post_name = 'Postadresse noch nicht gewählt';
        // WICHTIG:
        // Falls deine Tabelle `rechnungen` noch weitere NOT NULL-Felder ohne Default hat,
        // musst du diese hier sinnvoll befüllen, sonst gibt es einen SQL-Fehler beim save().
        // z.B.:
        // $rechnung->waehrung = 'EUR';
        // $rechnung->sprache  = 'de';

        // Speichern in der Datenbank
        $rechnung->save();

        // Optional: Falls du nach dem Speichern eine Rechnungsnummer generierst
        // (z.B. basierend auf ID + Jahr), kannst du das hier tun:
        //
        // if (!$rechnung->nummern) {
        //     $rechnung->nummern = 'R-' . now()->year . '-' . str_pad($rechnung->id, 5, '0', STR_PAD_LEFT);
        //     $rechnung->save();
        // }

        // Direkt auf das Bearbeitungsformular umleiten
        return redirect()
            ->route('rechnung.edit', $rechnung->id)
            ->with('info', 'Neue Rechnung als Entwurf angelegt.');
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
            return redirect()->back()->with('error', 'Diese Rechnung kann nicht mehr bearbeitet werden.');
        }

        $validated = $request->validate([
            // ... deine Validation
        ]);

        \Log::info('Validation OK', ['validated_re_name' => $validated['re_name'] ?? 'NULL']);

        $rechnung->update($validated);
        \Log::info('Update ausgeführt', ['neue_re_name' => $rechnung->re_name]);

        $rechnung->refresh();
        \Log::info('Nach Refresh', ['re_name' => $rechnung->re_name]);

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
