<?php

namespace App\Http\Controllers;

use App\Models\Messung;
use App\Models\Impianto;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;

class ProtokollController extends Controller
{
    private const BRENNSTOFF_TEXT = [
        'FUEL_LIGHT_OIL' => 'Heizöl/Gasolio',
        'FUEL_HEAVY_OIL' => 'Heizöl/Gasolio',
        'FUEL_NAT_GAS'   => 'Erdgas/Metano',
        'FUEL_PROPANE'    => 'Flüssiggas/GPL',
        'FUEL_BUTANE'     => 'Flüssiggas/GPL',
        'FUEL_PELLETS'    => 'Pellet',
        'FUEL_WOOD'       => 'Holz/Legna',
    ];

    private const PAGE_HEIGHT = 841.89;

    /**
     * Öffentlicher Download über Token (kein Login nötig)
     */
    public function download($token)
    {
        $dir = 'protokolle';
        $files = Storage::disk('local')->files($dir);

        // Datei mit passendem Token finden
        $match = collect($files)->first(fn($f) => str_contains($f, $token));

        if (!$match || !Storage::disk('local')->exists($match)) {
            abort(404, 'Protokoll nicht gefunden oder abgelaufen.');
        }

        $filename = basename($match);
        // Token-Prefix entfernen für den Download-Namen
        $downloadName = preg_replace('/^[a-f0-9]+_/', '', $filename);

        return response(Storage::disk('local')->get($match))
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $downloadName . '"');
    }

    public function generate($messungId)
    {
        $messung = Messung::findOrFail($messungId);

        $pdfString = $this->generatePdfString($messung);

        $kodex = $messung->cIM_CODICE ?: 'ohne';
        $datum = str_replace('.', '', $messung->cMIS_DATA2 ?: date('dmY'));
        $filename = "Protokoll_{$kodex}_{$datum}.pdf";

        return response($pdfString)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $filename . '"');
    }

    /**
     * Erzeugt das PDF als String (für Anhänge, Downloads etc.)
     */
    public function generatePdfString(Messung $messung): string
    {
        $anlage = null;
        if ($messung->codeInImpianti > 0 && $messung->cIM_CODICE) {
            $anlage = Impianto::where('Feld_a', $messung->cIM_CODICE)->first();
        }

        $vorlagePath = resource_path('pdf/vorlage.pdf');
        if (!file_exists($vorlagePath)) {
            throw new \RuntimeException('PDF-Vorlage nicht gefunden. Bitte vorlage.pdf in resources/pdf/ ablegen.');
        }

        $pdf = new Fpdi();
        $pdf->SetAutoPageBreak(false);
        $pdf->AddPage();

        $templateId = $pdf->setSourceFile($vorlagePath);
        $tplId = $pdf->importPage(1);
        $pdf->useTemplate($tplId, 0, 0, 210, 297);

        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetTextColor(0, 0, 0);

        $this->fillFields($pdf, $messung, $anlage);

        return $pdf->Output('S');
    }

    /**
     * Erzeugt den Dateinamen für ein Protokoll-PDF
     */
    public static function getFilename(Messung $messung): string
    {
        $kodex = $messung->cIM_CODICE ?: 'ohne';
        $datum = str_replace('.', '', $messung->cMIS_DATA2 ?: date('dmY'));
        return "Protokoll_{$kodex}_{$datum}.pdf";
    }

    private function fillFields(Fpdi $pdf, Messung $messung, ?Impianto $anlage): void
    {
        if ($anlage) {
            // === LINKE SEITE: Betreiber (Verwalter) ===
            $this->w($pdf, 'Name', $anlage->Feld_o ?? '', 9);
            $this->w($pdf, 'StrasseBet', $anlage->Feld_t ?: ($anlage->Feld_u ?? ''));
            $this->w($pdf, 'NrBet', $anlage->Feld_v ?? '');
            $this->w($pdf, 'FraltionBet', $anlage->Feld_r ?: ($anlage->Feld_s ?? ''));
            $this->w($pdf, 'GemeindeBet', $anlage->Feld_p ?: ($anlage->Feld_q ?? ''));

            // === RECHTE SEITE: Aufstellungsort ===
            $this->w($pdf, 'Aufstellungsort', $anlage->Feld_w ?? '', 9);
            $this->w($pdf, 'StrasseAuf', $anlage->Feld_l ?: ($anlage->Feld_m ?? ''));
            $this->w($pdf, 'NrAuf', $anlage->Feld_n ?? '');
            $this->w($pdf, 'GemeindeAuf', $anlage->Feld_h ?: ($anlage->Feld_i ?? ''));

            // === TECHNISCHE DATEN ===
            $this->w($pdf, 'AnlagenCODE', $anlage->Feld_a ?? '', 12, 'B');
            $this->w($pdf, 'Status der Anlage', '1');
            $this->w($pdf, 'Kessel Hersteller', $anlage->Feld_y ?? '');
            $this->w($pdf, 'Baujahr', $anlage->Feld_z ?? '');
            $this->w($pdf, 'Leistung in kw', $anlage->Feld_ab ?? '');
        } else {
            $this->w($pdf, 'Name', $messung->cIM_NAME ?? '');
            $this->w($pdf, 'Aufstellungsort', $messung->cIM_NAME ?? '');
            $this->w($pdf, 'AnlagenCODE', $messung->cIM_CODICE ?? '', 12, 'B');
            $this->w($pdf, 'Baujahr', $messung->boilerYear ?? '');
            $this->w($pdf, 'Leistung in kw', $messung->boilerPower ?? '');
        }

        $brennstoffKey = $messung->cMIS_COMBUSTIBILE ?? 'FUEL_NAT_GAS';
        $this->w($pdf, 'DropdownBrennstoff', self::BRENNSTOFF_TEXT[$brennstoffKey] ?? 'Erdgas/Metano');

        $this->w($pdf, 'Tag der Messung', $messung->cMIS_DATA2 ?? '');
        $this->w($pdf, 'DropdownOK', ($messung->strEsito === '1') ? 'ja/si' : 'nein/no');
        $this->w($pdf, 'Russzahl', $messung->cMIS_IND_OPACITA ?? '0');
        $this->w($pdf, 'DropdownOeD', ($messung->cMIS_TRACCE_OLEO === '0') ? 'ja/si' : 'nein/no');

        $this->w($pdf, 'WT', $messung->cMIS_T_LIQ_CONV ?? '');
        $this->w($pdf, 'Abgastemperatur', $messung->cMIS_T_GAS_COMB ?? '');
        $this->w($pdf, 'VT', $messung->cMIS_T_ARIA_COMB ?? '');

        $this->w($pdf, 'O2', $messung->cMIS_OSSIGENO ?? '');
        $this->w($pdf, 'CO2', $messung->cMIS_ANIDRIDE_CARBONICA ?? '');
        $this->w($pdf, 'NOx', $messung->cMIS_BIOSSIDO_AZOTO ?? '');
        $this->w($pdf, 'CO', $messung->cMIS_MONOSSSIDO ?? '');

        // === Mitteilungen: Stufen-Vermerk (nur wenn Stufe > 1) ===
        $this->writeMitteilungen($pdf, $messung);

        // === Stempel und Unterschrift ===
        $this->writeStempel($pdf);
    }

    /**
     * Schreibt den Stufen-Vermerk ins Mitteilungen-Feld.
     * Nur bei Stufe > 1, zweisprachig (z.B. "Stufe 2 / Stadio 2")
     */
    private function writeMitteilungen(Fpdi $pdf, Messung $messung): void
    {
        $stufe = (int) ($messung->cMIS_STADIO ?? 0);

        if ($stufe <= 1) {
            return;
        }

        $rect = $this->getRect('Mitteilungen');
        if (!$rect) {
            return;
        }

        $ptToMm = 25.4 / 72;
        $x = $rect[0] * $ptToMm;
        $y = (self::PAGE_HEIGHT - $rect[3]) * $ptToMm;
        $w = ($rect[2] - $rect[0]) * $ptToMm;

        // Kleiner Innenabstand, damit der Text nicht am Rand klebt
        $x += 2;
        $y += 2;

        $text = "Stufe {$stufe} / Stadio {$stufe}";
        $text = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text) ?: $text;

        $pdf->SetFont('Helvetica', 'B', 10);
        $pdf->SetXY($x, $y);
        $pdf->Cell($w - 4, 5, $text, 0, 0, 'L');
    }

    /**
     * Schreibt den Firmenstempel in den Stempel-Bereich
     */
    private function writeStempel(Fpdi $pdf): void
    {
        $ptToMm = 25.4 / 72;

        // Stempel-Bereich: links unten im PDF (ca. x=60, y=162-246 in PDF-Pts)
        $startX = 60 * $ptToMm;  // ca. 21mm
        $startY = (self::PAGE_HEIGHT - 246) * $ptToMm; // ca. 210mm von oben

        // Unterschrift-Bild einfügen (falls vorhanden)
        $unterschriftPath = resource_path('pdf/unterschrift.png');
        if (file_exists($unterschriftPath)) {
            $pdf->Image($unterschriftPath, $startX + 5, $startY + 1, 35, 0, 'PNG');
        }

        // Firmenname
        $y = $startY + 14;
        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->SetXY($startX, $y);
        $pdf->Cell(80, 4, @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', 'Christian Resch OHG-SNC') ?: 'Christian Resch OHG-SNC', 0, 0, 'C');

        // Adresse
        $y += 4;
        $pdf->SetFont('Helvetica', '', 7);
        $pdf->SetXY($startX, $y);
        $pdf->Cell(80, 3.5, @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', 'Galvanistr. 6 - 39100 Bozen') ?: 'Galvanistr. 6 - 39100 Bozen', 0, 0, 'C');

        // Telefon
        $y += 3.5;
        $pdf->SetXY($startX, $y);
        $pdf->Cell(80, 3.5, 'Tel. 338 4693481', 0, 0, 'C');

        // Email
        $y += 3.5;
        $pdf->SetXY($startX, $y);
        $pdf->Cell(80, 3.5, 'info@resch.bz', 0, 0, 'C');
    }

    private function w(Fpdi $pdf, string $fieldId, string $value, float $fontSize = 10, string $style = ''): void
    {
        if ($value === '' || $value === null) {
            return;
        }

        $rect = $this->getRect($fieldId);
        if (!$rect) {
            return;
        }

        $ptToMm = 25.4 / 72;
        $x = $rect[0] * $ptToMm;
        $y = (self::PAGE_HEIGHT - $rect[3]) * $ptToMm;
        $w = ($rect[2] - $rect[0]) * $ptToMm;
        $h = ($rect[3] - $rect[1]) * $ptToMm;

        $pdf->SetFont('Helvetica', $style, $fontSize);
        $pdf->SetXY($x, $y);

        $text = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $value) ?: $value;

        // Mehrzeilige Felder (Höhe > 18pt)
        if (($rect[3] - $rect[1]) > 18) {
            $lineH = $fontSize * 0.4; // Zeilenhöhe in mm
            $pdf->MultiCell($w, $lineH, $text, 0, 'L');
        } else {
            $pdf->Cell($w, $h, $text, 0, 0, 'L');
        }
    }

    private function getRect(string $fieldId): ?array
    {
        $fields = [
            'Name'              => [51.02, 718.00, 266.46, 752.00],
            'Aufstellungsort'   => [340.16, 718.00, 571.10, 752.00],
            'StrasseAuf'        => [314.65, 707.27, 464.65, 721.45],
            'NrAuf'             => [531.06, 707.27, 578.27, 721.45],
            'StrasseBet'        => [102.05, 694.52, 229.61, 708.69],
            'NrBet'             => [257.95, 694.52, 308.98, 708.69],
            'FraltionBet'       => [102.05, 673.26, 252.05, 687.43],
            'FraktionAuf'       => [396.85, 673.26, 572.60, 687.43],
            'GemeindeBet'       => [102.05, 649.16, 252.05, 663.34],
            'GemeindeAuf'       => [396.85, 649.16, 572.60, 663.34],
            'SteuernummerBet'   => [22.91, 623.65, 172.91, 637.83],
            'AnlagenCODE'       => [500.19, 603.81, 576.98, 617.98],
            'Text1'             => [150.24, 587.48, 447.87, 603.81],
            'Status der Anlage' => [109.13, 568.38, 138.90, 582.55],
            'Kessel Hersteller' => [107.72, 544.28, 286.30, 558.46],
            'Baujahr'           => [333.07, 544.28, 399.69, 558.46],
            'Leistung in kw'    => [531.46, 544.28, 572.60, 558.46],
            'DropdownBrennstoff'=> [130.39, 507.27, 331.65, 527.27],
            'Tag der Messung'   => [494.65, 463.49, 561.26, 477.67],
            'DropdownOK'        => [288.00, 429.40, 360.00, 449.40],
            'Russzahl'          => [189.81, 395.88, 240.94, 410.05],
            'DropdownOeD'       => [493.23, 395.38, 572.60, 415.38],
            'WT'                => [189.81, 372.79, 240.94, 386.96],
            'Abgastemperatur'   => [493.23, 372.79, 547.09, 386.96],
            'VT'                => [189.81, 350.11, 240.94, 364.28],
            'O2'                => [493.23, 350.11, 547.09, 364.28],
            'CO2'               => [189.92, 326.01, 241.05, 340.19],
            'NOx'               => [493.23, 326.01, 547.09, 340.19],
            'CO'                => [189.81, 302.34, 240.94, 316.51],
            'Mitteilungen'      => [308.98, 162.86, 569.76, 246.64],
        ];

        return $fields[$fieldId] ?? null;
    }
}
