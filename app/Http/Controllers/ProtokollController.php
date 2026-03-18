<?php

namespace App\Http\Controllers;

use App\Models\Messung;
use App\Models\Impianto;
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

    public function generate($messungId)
    {
        $messung = Messung::findOrFail($messungId);

        $anlage = null;
        if ($messung->codeInImpianti > 0 && $messung->cIM_CODICE) {
            $anlage = Impianto::where('Feld_a', $messung->cIM_CODICE)->first();
        }

        $vorlagePath = resource_path('pdf/vorlage.pdf');
        if (!file_exists($vorlagePath)) {
            abort(404, 'PDF-Vorlage nicht gefunden. Bitte vorlage.pdf in resources/pdf/ ablegen.');
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

        $kodex = $messung->cIM_CODICE ?: 'ohne';
        $datum = str_replace('.', '', $messung->cMIS_DATA2 ?: date('dmY'));
        $filename = "Protokoll_{$kodex}_{$datum}.pdf";

        return response($pdf->Output('S'))
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $filename . '"');
    }

    private function fillFields(Fpdi $pdf, Messung $messung, ?Impianto $anlage): void
    {
        if ($anlage) {
            $betreiberName = trim(($anlage->Feld_c ?? '') . ' ' . ($anlage->Feld_d ?? ''));
            if (empty(trim($betreiberName))) {
                $betreiberName = $anlage->Feld_w ?? $messung->cIM_NAME ?? '';
            }

            $this->w($pdf, 'Name', $betreiberName);
            $this->w($pdf, 'Aufstellungsort', $anlage->Feld_w ?? '');
            $this->w($pdf, 'StrasseAuf', $anlage->Feld_l ?: ($anlage->Feld_m ?? ''));
            $this->w($pdf, 'NrAuf', $anlage->Feld_n ?? '');
            $this->w($pdf, 'GemeindeAuf', $anlage->Feld_h ?: ($anlage->Feld_i ?? ''));
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
        $pdf->Cell($w, $h, $text, 0, 0, 'L');
    }

    private function getRect(string $fieldId): ?array
    {
        $fields = [
            'Name'              => [51.02, 734.20, 266.46, 748.38],
            'Aufstellungsort'   => [340.16, 734.20, 571.10, 748.38],
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
