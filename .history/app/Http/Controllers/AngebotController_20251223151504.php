<?php

namespace App\Http\Controllers;

use App\Models\Angebot;
use App\Models\AngebotPosition;
use App\Models\AngebotLog;
use App\Models\Gebaeude;
use App\Models\Adresse;
use App\Models\FatturaProfile;
use App\Models\Unternehmensprofil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class AngebotController extends Controller
{
    // =======================================================================
    // UEBERSICHT
    // =======================================================================

    public function index(Request $request)
    {
        $query = Angebot::with(['gebaeude', 'adresse'])
            ->orderByDesc('datum')
            ->orderByDesc('id');

        if ($request->filled('jahr')) {
            $query->where('jahr', $request->jahr);
        } else {
            $query->where('jahr', now()->year);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('suche')) {
            $suche = $request->suche;
            $query->where(function ($q) use ($suche) {
                $q->where('titel', 'like', "%{$suche}%")
                  ->orWhere('empfaenger_name', 'like', "%{$suche}%")
                  ->orWhere('geb_codex', 'like', "%{$suche}%")
                  ->orWhereRaw("CONCAT('A', jahr, '/', LPAD(laufnummer, 4, '0')) LIKE ?", ["%{$suche}%"]);
            });
        }

        $angebote = $query->paginate(25)->withQueryString();

        $statistik = [
            'gesamt'     => Angebot::jahr($request->input('jahr', now()->year))->count(),
            'entwurf'    => Angebot::jahr($request->input('jahr', now()->year))->where('status', 'entwurf')->count(),
            'versendet'  => Angebot::jahr($request->input('jahr', now()->year))->where('status', 'versendet')->count(),
            'angenommen' => Angebot::jahr($request->input('jahr', now()->year))->where('status', 'angenommen')->count(),
            'abgelehnt'  => Angebot::jahr($request->input('jahr', now()->year))->where('status', 'abgelehnt')->count(),
        ];

        $jahre = Angebot::selectRaw('DISTINCT jahr')->orderByDesc('jahr')->pluck('jahr');

        return view('angebote.index', compact('angebote', 'statistik', 'jahre'));
    }

    // =======================================================================
    // ERSTELLEN
    // =======================================================================

    public function createFromGebaeude(Request $request, Gebaeude $gebaeude)
    {
        try {
            $angebot = Angebot::createFromGebaeude($gebaeude, [
                'titel'       => $request->input('titel'),
                'gueltig_bis' => $request->input('gueltig_bis'),
            ]);

            return redirect()
                ->route('angebote.edit', $angebot)
                ->with('success', 'Angebot ' . $angebot->angebotsnummer . ' erstellt.');

        } catch (\Exception $e) {
            Log::error('Angebot erstellen fehlgeschlagen', [
                'gebaeude_id' => $gebaeude->id,
                'error'       => $e->getMessage(),
            ]);

            return back()->with('error', 'Fehler beim Erstellen: ' . $e->getMessage());
        }
    }

    public function create()
    {
        $gebaeude = Gebaeude::orderBy('codex')
            ->whereHas('aktiveArtikel')
            ->get(['id', 'codex', 'gebaeude_name', 'wohnort']);

        return view('angebote.create', compact('gebaeude'));
    }

    // =======================================================================
    // BEARBEITEN
    // =======================================================================

    public function edit(Angebot $angebot)
    {
        $angebot->load(['positionen', 'logs', 'gebaeude', 'rechnung']);

        $fatturaProfiles = FatturaProfile::orderBy('bezeichnung')->get();

        return view('angebote.edit', compact('angebot', 'fatturaProfiles'));
    }

    public function update(Request $request, Angebot $angebot)
    {
        $data = $request->validate([
            'titel'             => 'required|string|max:255',
            'datum'             => 'required|date',
            'gueltig_bis'       => 'nullable|date|after_or_equal:datum',
            'mwst_satz'         => 'required|numeric|min:0|max:100',
            'einleitung'        => 'nullable|string',
            'bemerkung_kunde'   => 'nullable|string',
            'bemerkung_intern'  => 'nullable|string',
            'fattura_profile_id' => 'nullable|exists:fattura_profile,id',
            'empfaenger_name'    => 'nullable|string|max:255',
            'empfaenger_strasse' => 'nullable|string|max:255',
            'empfaenger_hausnummer' => 'nullable|string|max:20',
            'empfaenger_plz'     => 'nullable|string|max:10',
            'empfaenger_ort'     => 'nullable|string|max:100',
            'empfaenger_email'   => 'nullable|email|max:255',
        ]);

        $angebot->update($data);
        $angebot->berechneBetraege();

        AngebotLog::bearbeitet($angebot->id);

        return redirect()
            ->route('angebote.edit', $angebot)
            ->with('success', 'Angebot gespeichert.');
    }

    // =======================================================================
    // POSITIONEN
    // =======================================================================

    public function addPosition(Request $request, Angebot $angebot)
    {
        $data = $request->validate([
            'beschreibung' => 'required|string|max:500',
            'anzahl'       => 'required|numeric|min:0.01',
            'einheit'      => 'nullable|string|max:50',
            'einzelpreis'  => 'required|numeric|min:0',
        ]);

        $maxPosition = $angebot->positionen()->max('position') ?? 0;

        $angebot->positionen()->create([
            'position'    => $maxPosition + 1,
            'beschreibung' => $data['beschreibung'],
            'anzahl'      => $data['anzahl'],
            'einheit'     => $data['einheit'] ?? 'Stueck',
            'einzelpreis' => $data['einzelpreis'],
            'gesamtpreis' => round($data['anzahl'] * $data['einzelpreis'], 2),
        ]);

        $angebot->berechneBetraege();

        return back()->with('success', 'Position hinzugefuegt.');
    }

    public function updatePosition(Request $request, AngebotPosition $position)
    {
        $data = $request->validate([
            'beschreibung' => 'required|string|max:500',
            'anzahl'       => 'required|numeric|min:0.01',
            'einheit'      => 'nullable|string|max:50',
            'einzelpreis'  => 'required|numeric|min:0',
        ]);

        $data['gesamtpreis'] = round($data['anzahl'] * $data['einzelpreis'], 2);
        $position->update($data);

        $position->angebot->berechneBetraege();

        return back()->with('success', 'Position aktualisiert.');
    }

    public function deletePosition(AngebotPosition $position)
    {
        $angebot = $position->angebot;
        $position->delete();

        $angebot->berechneBetraege();

        return back()->with('success', 'Position geloescht.');
    }

    public function reorderPositions(Request $request, Angebot $angebot)
    {
        $request->validate([
            'positions'   => 'required|array',
            'positions.*' => 'integer|exists:angebot_positionen,id',
        ]);

        foreach ($request->positions as $index => $positionId) {
            AngebotPosition::where('id', $positionId)
                ->where('angebot_id', $angebot->id)
                ->update(['position' => $index + 1]);
        }

        return response()->json(['ok' => true]);
    }

    // =======================================================================
    // PDF - VERWENDET loadHTML STATT loadView!
    // =======================================================================

    /**
     * PDF generieren - HTML wird direkt im Controller generiert
     * NICHT loadView verwenden wegen Windows-Encoding-Problemen!
     */
    public function pdf(Angebot $angebot, Request $request)
    {
        $angebot->load(['positionen', 'fatturaProfile', 'adresse']);
        $unternehmen = Unternehmensprofil::first();

        // HTML direkt generieren (NICHT loadView!)
        $html = $this->generatePdfHtml($angebot, $unternehmen);

        $pdf = Pdf::loadHTML($html)
            ->setPaper('a4', 'portrait')
            ->setOption('defaultFont', 'DejaVu Sans');

        $safeNummer = str_replace(['/', '\\'], '-', $angebot->angebotsnummer);
        $pdfName = 'Angebot_' . $safeNummer . '.pdf';

        if ($request->has('preview')) {
            return $pdf->stream($pdfName);
        }

        return $pdf->download($pdfName);
    }

    /**
     * Generiert das HTML fuer das Angebot-PDF
     * Modernes Teal-Design, kompakt, nur Netto-Summe
     */
    protected function generatePdfHtml(Angebot $angebot, $unternehmen): string
    {
        $firma = e($unternehmen->firmenname ?? 'Resch GmbH');
        $strasse = e(trim(($unternehmen->strasse ?? '') . ' ' . ($unternehmen->hausnummer ?? '')));
        $plzOrt = e(trim(($unternehmen->postleitzahl ?? '') . ' ' . ($unternehmen->ort ?? '')));
        $telefon = $unternehmen->telefon ?? '';
        $email = $unternehmen->email ?? '';
        $piva = $unternehmen->partita_iva ?? '';
        $cf = $unternehmen->codice_fiscale ?? '';

        // Logo laden
        $logoHtml = '';
        if ($unternehmen) {
            $logoPfad = $unternehmen->logo_rechnung_pfad ?? $unternehmen->logo_pfad ?? null;
            if ($logoPfad) {
                $paths = [
                    storage_path('app/public/' . $logoPfad),
                    public_path('storage/' . $logoPfad),
                    public_path($logoPfad),
                ];
                foreach ($paths as $path) {
                    if (file_exists($path) && is_readable($path)) {
                        $logoHtml = '<img src="' . $path . '" style="max-width:180px;max-height:50px;margin-bottom:2mm;">';
                        break;
                    }
                }
            }
        }

        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Angebot ' . e($angebot->angebotsnummer) . '</title>
</head>
<body style="font-family:DejaVu Sans,sans-serif;font-size:8pt;line-height:1.3;color:#2d3748;margin:0;padding:8mm;">

<div style="margin-bottom:4mm;padding-bottom:3mm;border-bottom:2px solid #38b2ac;">
    ' . $logoHtml . '
    <div style="font-size:14pt;font-weight:bold;color:#2c7a7b;">' . $firma . '</div>
    <div style="font-size:7pt;color:#718096;line-height:1.5;margin-top:1mm;">
        ' . $strasse . ' | ' . $plzOrt . '
        ' . ($telefon ? ' | Tel: ' . e($telefon) : '') . '
        ' . ($email ? ' | ' . e($email) : '') . '<br>
        ' . ($piva ? 'P.IVA: ' . e($piva) : '') . ($cf && $piva ? ' | ' : '') . ($cf ? 'C.F.: ' . e($cf) : '') . '
    </div>
</div>

<div style="background-color:#38b2ac;color:white;padding:4mm 5mm;margin-bottom:4mm;text-align:center;">
    <div style="font-size:14pt;font-weight:bold;letter-spacing:1px;">ANGEBOT / OFFERTA</div>
</div>

<div style="display:table;width:100%;margin-bottom:4mm;">
    <div style="display:table-cell;width:50%;vertical-align:top;padding-right:4mm;">
        <div style="border:1px solid #e2e8f0;border-left:3px solid #38b2ac;padding:2mm;background-color:#f7fafc;min-height:22mm;">
            <div style="font-size:6pt;text-transform:uppercase;letter-spacing:0.5px;color:#38b2ac;font-weight:bold;margin-bottom:1mm;">Empfaenger / Destinatario</div>
            <div style="font-size:9pt;font-weight:bold;color:#2d3748;">' . e($angebot->empfaenger_name) . '</div>
            <div style="font-size:7pt;color:#4a5568;line-height:1.4;">
                ' . e($angebot->empfaenger_strasse) . ' ' . e($angebot->empfaenger_hausnummer) . '<br>
                ' . e($angebot->empfaenger_plz) . ' ' . e($angebot->empfaenger_ort) . '
                ' . ($angebot->empfaenger_steuernummer ? '<br>MwSt-Nr.: ' . e($angebot->empfaenger_steuernummer) : '') . '
            </div>
        </div>
    </div>
    <div style="display:table-cell;width:50%;vertical-align:top;padding-left:4mm;">
        <table style="width:100%;border-collapse:collapse;">
            <tr><td style="padding:1.5mm 2mm;background-color:#edf2f7;border-bottom:1px solid #d1d5db;">
                <div style="font-size:6pt;color:#718096;text-transform:uppercase;">Angebots-Nr. / N. Offerta</div>
                <div style="font-size:9pt;font-weight:bold;color:#2d3748;">' . e($angebot->angebotsnummer) . '</div>
            </td></tr>
            <tr><td style="padding:1.5mm 2mm;background-color:#edf2f7;border-bottom:1px solid #d1d5db;">
                <div style="font-size:6pt;color:#718096;text-transform:uppercase;">Datum / Data</div>
                <div style="font-size:9pt;font-weight:bold;color:#2d3748;">' . $angebot->datum->format('d.m.Y') . '</div>
            </td></tr>
            ' . ($angebot->gueltig_bis ? '<tr><td style="padding:1.5mm 2mm;background-color:#edf2f7;">
                <div style="font-size:6pt;color:#718096;text-transform:uppercase;">Gueltig bis / Valido fino al</div>
                <div style="font-size:9pt;font-weight:bold;color:#2d3748;">' . $angebot->gueltig_bis->format('d.m.Y') . '</div>
            </td></tr>' : '') . '
        </table>
    </div>
</div>

' . ($angebot->titel ? '<div style="background-color:#e6fffa;border-left:3px solid #38b2ac;padding:2mm 3mm;margin-bottom:3mm;font-size:8pt;">
    <div style="font-size:6pt;color:#38b2ac;text-transform:uppercase;font-weight:bold;">Betreff / Oggetto</div>
    ' . e($angebot->titel) . '
</div>' : '') . '

' . ($angebot->einleitung ? '<div style="margin-bottom:3mm;padding:2mm;font-size:8pt;line-height:1.4;color:#4a5568;">' . nl2br(e($angebot->einleitung)) . '</div>' : '') . '

<table style="width:100%;border-collapse:collapse;margin-bottom:3mm;">
    <thead>
        <tr>
            <th style="background-color:#2c7a7b;color:white;font-weight:bold;padding:2mm 1.5mm;text-align:left;font-size:7pt;width:6%;">Pos.</th>
            <th style="background-color:#2c7a7b;color:white;font-weight:bold;padding:2mm 1.5mm;text-align:left;font-size:7pt;width:52%;">Beschreibung / Descrizione</th>
            <th style="background-color:#2c7a7b;color:white;font-weight:bold;padding:2mm 1.5mm;text-align:right;font-size:7pt;width:12%;">Menge / Qta</th>
            <th style="background-color:#2c7a7b;color:white;font-weight:bold;padding:2mm 1.5mm;text-align:right;font-size:7pt;width:15%;">Preis / Prezzo</th>
            <th style="background-color:#2c7a7b;color:white;font-weight:bold;padding:2mm 1.5mm;text-align:right;font-size:7pt;width:15%;">Gesamt / Totale</th>
        </tr>
    </thead>
    <tbody>';

        $i = 1;
        foreach ($angebot->positionen as $pos) {
            $bg = ($i % 2 == 0) ? 'background-color:#f7fafc;' : '';
            $html .= '<tr>
            <td style="padding:1.5mm;border-bottom:1px solid #e2e8f0;' . $bg . '">' . $i . '</td>
            <td style="padding:1.5mm;border-bottom:1px solid #e2e8f0;' . $bg . '">' . e($pos->beschreibung) . '</td>
            <td style="padding:1.5mm;border-bottom:1px solid #e2e8f0;text-align:right;' . $bg . '">' . number_format($pos->anzahl, 2, ',', '.') . ' ' . e($pos->einheit ?? 'Stk') . '</td>
            <td style="padding:1.5mm;border-bottom:1px solid #e2e8f0;text-align:right;' . $bg . '">' . number_format($pos->einzelpreis, 2, ',', '.') . ' EUR</td>
            <td style="padding:1.5mm;border-bottom:1px solid #e2e8f0;text-align:right;' . $bg . '">' . number_format($pos->gesamtpreis, 2, ',', '.') . ' EUR</td>
        </tr>';
            $i++;
        }

        $html .= '</tbody>
</table>

<div style="display:table;width:100%;">
    <div style="display:table-cell;width:55%;"></div>
    <div style="display:table-cell;width:45%;">
        <table style="width:100%;border-collapse:collapse;">
            <tr>
                <td style="background-color:#38b2ac;color:white;font-size:10pt;padding:2mm;">GESAMTBETRAG / TOTALE</td>
                <td style="background-color:#38b2ac;color:white;font-size:10pt;padding:2mm;text-align:right;font-weight:bold;">' . number_format($angebot->netto_summe, 2, ',', '.') . ' EUR</td>
            </tr>
        </table>
        <div style="text-align:right;font-size:6pt;color:#718096;font-style:italic;margin-top:1mm;">
            Preise ohne MwSt. / Prezzi senza IVA.
        </div>
    </div>
</div>

' . ($angebot->gueltig_bis ? '<div style="margin-top:4mm;padding:2mm;background-color:#fefcbf;border:1px solid #ecc94b;font-size:7pt;text-align:center;">
    <strong style="color:#b7791f;">Gueltig bis ' . $angebot->gueltig_bis->format('d.m.Y') . '</strong> |
    <em>Valido fino al ' . $angebot->gueltig_bis->format('d.m.Y') . '</em>
</div>' : '') . '

' . ($angebot->bemerkung_kunde ? '<div style="margin-top:3mm;padding:2mm;background-color:#f7fafc;border:1px solid #e2e8f0;font-size:7pt;">
    <div style="font-weight:bold;color:#2c7a7b;margin-bottom:1mm;font-size:7pt;">Bemerkungen / Note</div>
    ' . nl2br(e($angebot->bemerkung_kunde)) . '
</div>' : '') . '

<div style="position:fixed;bottom:5mm;left:8mm;right:8mm;font-size:6pt;text-align:center;color:#a0aec0;padding-top:2mm;border-top:1px solid #e2e8f0;">
    ' . $firma . ' | ' . $strasse . ', ' . $plzOrt . '
    ' . ($telefon ? ' | ' . e($telefon) : '') . ($email ? ' | ' . e($email) : '') . '
</div>

</body>
</html>';

        return $html;
    }

    // =======================================================================
    // E-MAIL VERSAND
    // =======================================================================

    public function showVersand(Angebot $angebot)
    {
        $angebot->load(['positionen']);

        $email = $angebot->empfaenger_email;
        $betreff = 'Angebot ' . $angebot->angebotsnummer . ' / Offerta ' . $angebot->angebotsnummer;
        $text = $this->getStandardEmailText($angebot);

        return view('angebote.versand', compact('angebot', 'email', 'betreff', 'text'));
    }

    /**
     * E-Mail senden - verwendet auch loadHTML!
     */
    public function versenden(Request $request, Angebot $angebot)
    {
        $data = $request->validate([
            'email'   => 'required|email',
            'betreff' => 'required|string|max:255',
            'text'    => 'required|string',
        ]);

        try {
            $profil = Unternehmensprofil::first();

            if (!$profil || !$profil->hatSmtpKonfiguration()) {
                return back()->with('error', 'SMTP-Konfiguration fehlt im Unternehmensprofil.');
            }

            $mailerName = 'angebot_smtp';
            Config::set("mail.mailers.{$mailerName}", [
                'transport'  => 'smtp',
                'host'       => $profil->smtp_host,
                'port'       => $profil->smtp_port,
                'encryption' => $profil->smtp_verschluesselung ?: 'tls',
                'username'   => $profil->smtp_benutzername,
                'password'   => $profil->smtp_passwort,
                'timeout'    => 30,
            ]);

            $absenderEmail = $profil->smtp_benutzername;
            $absenderName = $profil->smtp_absender_name ?: $profil->firmenname;

            // PDF mit loadHTML generieren (NICHT loadView!)
            $angebot->load(['positionen', 'fatturaProfile', 'adresse']);
            $html = $this->generatePdfHtml($angebot, $profil);

            $pdf = Pdf::loadHTML($html)
                ->setPaper('a4', 'portrait')
                ->setOption('defaultFont', 'DejaVu Sans');

            $tempDir = storage_path('app/temp');
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
            
            $safeNummer = str_replace(['/', '\\'], '-', $angebot->angebotsnummer);
            $pdfPath = $tempDir . '/angebot_' . $angebot->id . '_' . time() . '.pdf';
            $pdf->save($pdfPath);

            Mail::mailer($mailerName)
                ->send([], [], function ($message) use ($data, $absenderEmail, $absenderName, $pdfPath, $safeNummer) {
                    $message->to($data['email'])
                        ->from($absenderEmail, $absenderName)
                        ->subject($data['betreff'])
                        ->text($data['text']);

                    $message->attach($pdfPath, [
                        'as'   => 'Angebot_' . $safeNummer . '.pdf',
                        'mime' => 'application/pdf',
                    ]);
                });

            if (file_exists($pdfPath)) {
                unlink($pdfPath);
            }

            $angebot->markiereAlsVersendet($data['email']);

            return redirect()
                ->route('angebote.edit', $angebot)
                ->with('success', 'Angebot per E-Mail an ' . $data['email'] . ' versendet.');

        } catch (\Exception $e) {
            Log::error('Angebot E-Mail-Versand fehlgeschlagen', [
                'angebot_id' => $angebot->id,
                'error'      => $e->getMessage(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'E-Mail-Versand fehlgeschlagen: ' . $e->getMessage());
        }
    }

    protected function getStandardEmailText(Angebot $angebot): string
    {
        $unternehmen = Unternehmensprofil::first();
        $firma = $unternehmen->firmenname ?? 'Resch GmbH';

        $gueltigBis = $angebot->gueltig_bis ? $angebot->gueltig_bis->format('d.m.Y') : '';

        $text = "Sehr geehrte Damen und Herren,\n\n";
        $text .= "anbei erhalten Sie unser Angebot {$angebot->angebotsnummer} vom {$angebot->datum->format('d.m.Y')}.\n\n";
        $text .= "Angebotssumme: {$angebot->brutto_formatiert} (inkl. MwSt)\n";
        if ($gueltigBis) {
            $text .= "Gueltig bis: {$gueltigBis}\n";
        }
        $text .= "\nBei Fragen stehen wir Ihnen gerne zur Verfuegung.\n\n";
        $text .= "Mit freundlichen Gruessen\n{$firma}\n\n";
        $text .= "---\n\n";
        $text .= "Gentili Signore e Signori,\n\n";
        $text .= "in allegato trovate la nostra offerta {$angebot->netto_formatiert} del {$angebot->datum->format('d.m.Y')}.\n\n";
        $text .= "Importo totale: {$angebot->ne} (IVA inclusa)\n";
        if ($gueltigBis) {
            $text .= "Valida fino al: {$gueltigBis}\n";
        }
        $text .= "\nPer qualsiasi domanda, restiamo a Vostra disposizione.\n\n";
        $text .= "Cordiali saluti\n{$firma}";

        return $text;
    }

    // =======================================================================
    // STATUS & KONVERTIERUNG
    // =======================================================================

    public function setStatus(Request $request, Angebot $angebot)
    {
        $data = $request->validate([
            'status' => 'required|in:entwurf,versendet,angenommen,abgelehnt,abgelaufen',
        ]);

        $angebot->setStatus($data['status'], $request->input('bemerkung'));

        return back()->with('success', 'Status geaendert.');
    }

    public function zuRechnung(Request $request, Angebot $angebot)
    {
        if ($angebot->rechnung_id) {
            return back()->with('error', 'Angebot wurde bereits in Rechnung umgewandelt.');
        }

        try {
            $rechnung = $angebot->zuRechnung([
                'datum' => $request->input('datum', now()),
            ]);

            return redirect()
                ->route('rechnung.edit', $rechnung)
                ->with('success', 'Rechnung ' . $rechnung->volle_rechnungsnummer . ' aus Angebot erstellt.');

        } catch (\Exception $e) {
            Log::error('Angebot zu Rechnung fehlgeschlagen', [
                'angebot_id' => $angebot->id,
                'error'      => $e->getMessage(),
            ]);

            return back()->with('error', 'Fehler: ' . $e->getMessage());
        }
    }

    // =======================================================================
    // LOESCHEN
    // =======================================================================

    public function destroy(Angebot $angebot)
    {
        if ($angebot->rechnung_id) {
            return back()->with('error', 'Angebot mit verknuepfter Rechnung kann nicht geloescht werden.');
        }

        $nr = $angebot->angebotsnummer;
        $angebot->delete();

        return redirect()
            ->route('angebote.index')
            ->with('success', 'Angebot ' . $nr . ' geloescht.');
    }

    // =======================================================================
    // KOPIEREN
    // =======================================================================

    public function kopieren(Angebot $angebot)
    {
        $angebot->load(['positionen']);

        $jahr = now()->year;

        $neues = $angebot->replicate([
            'id', 'jahr', 'laufnummer', 'status', 'versendet_am', 'versendet_an_email',
            'rechnung_id', 'umgewandelt_am', 'pdf_pfad', 'created_at', 'updated_at', 'deleted_at'
        ]);

        $neues->jahr = $jahr;
        $neues->laufnummer = Angebot::naechsteLaufnummer($jahr);
        $neues->datum = now();
        $neues->gueltig_bis = now()->addDays(30);
        $neues->status = 'entwurf';
        $neues->save();

        foreach ($angebot->positionen as $pos) {
            $neues->positionen()->create([
                'position'     => $pos->position,
                'beschreibung' => $pos->beschreibung,
                'anzahl'       => $pos->anzahl,
                'einheit'      => $pos->einheit,
                'einzelpreis'  => $pos->einzelpreis,
                'gesamtpreis'  => $pos->gesamtpreis,
            ]);
        }

        $neues->berechneBetraege();

        AngebotLog::log($neues->id, 'erstellt', 'Angebot kopiert', 'Kopie von ' . $angebot->angebotsnummer);

        return redirect()
            ->route('angebote.edit', $neues)
            ->with('success', 'Angebot kopiert als ' . $neues->angebotsnummer);
    }
}
