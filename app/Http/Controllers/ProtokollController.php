<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Messung;
use App\Models\Impianto;
use setasign\Fpdi\Fpdi;

class ProtokollController extends Controller
{
    /**
     * Brennstoff-Texte für das Protokoll
     */
    private const BRENNSTOFF_TEXT = [
        'FUEL_LIGHT_OIL' => 'Heizöl/Gasolio',
        'FUEL_HEAVY_OIL' => 'Heizöl/Gasolio',
        'FUEL_NAT_GAS'   => 'Erdgas/Metano',
        'FUEL_PROPANE'    => 'Flüssiggas/GPL',
        'FUEL_BUTANE'     => 'Flüssiggas/GPL',
        'FUEL_PELLETS'    => 'Pellet',
        'FUEL_WOOD'       => 'Holz/Legna',
    ];

    /**
     * PDF-Seitenhöhe (A4 in Points)
     */
    private const PAGE_HEIGHT = 841.89;

    /**
     * Generiert ein PDF-Protokoll für eine Messung
     */
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

        // PDF erstellen mit FPDI
        $pdf = new Fpdi();
        $pdf->SetAutoPageBreak(false);
        $pdf->AddPage();

        // Vorlage als Hintergrund importieren
        $templateId = $pdf->setSourceFile($vorlagePath);
        $tplId = $pdf->importPage(1);
        $pdf->useTemplate($tplId, 0, 0, 210, 297); // A4 in mm

        // Schrift setzen
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetTextColor(0, 0, 0);

        // Felder befüllen
        $this->fillFields($pdf, $messung, $anlage);

        // Dateiname
        $kodex = $messung->cIM_CODICE ?: 'ohne';
        $datum = str_replace('.', '', $messung->cMIS_DATA2 ?: date('dmY'));
        $filename = "Protokoll_{$kodex}_{$datum}.pdf";

        return response($pdf->Output('S'))
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $filename . '"');
    }

    /**
     * Befüllt alle Felder im PDF
     */
    private function fillFields(Fpdi $pdf, Messung $messung, ?Impianto $anlage): void
    {
        // === Betreiber / Aufstellungsort ===
        if ($anlage) {
            $betreiberName = trim(($anlage->Feld_c ?? '') . ' ' . ($anlage->Feld_d ?? ''));
            if (empty(trim($betreiberName))) {
                $betreiberName = $anlage->Feld_w ?? $messung->cIM_NAME ?? '';
            }

            $this->writeField($pdf, 'Name', $betreiberName);
            $this->writeField($pdf, 'Aufstellungsort', $anlage->Feld_w ?? '');
            $this->writeField($pdf, 'StrasseAuf', $anlage->Feld_l ?: ($anlage->Feld_m ?? ''));
            $this->writeField($pdf, 'NrAuf', $anlage->Feld_n ?? '');
            $this->writeField($pdf, 'GemeindeAuf', $anlage->Feld_h ?: ($anlage->Feld_i ?? ''));
            $this->writeField($pdf, 'AnlagenCODE', $anlage->Feld_a ?? '', 12, 'B');
            $this->writeField($pdf, 'Status der Anlage', '1');
            $this->writeField($pdf, 'Kessel Hersteller', $anlage->Feld_y ?? '');
            $this->writeField($pdf, 'Baujahr', $anlage->Feld_z ?? '');
            $this->writeField($pdf, 'Leistung in kw', $anlage->Feld_ab ?? '');
        } else {
            $this->writeField($pdf, 'Name', $messung->cIM_NAME ?? '');
            $this->writeField($pdf, 'Aufstellungsort', $messung->cIM_NAME ?? '');
            $this->writeField($pdf, 'AnlagenCODE', $messung->cIM_CODICE ?? '', 12, 'B');
            $this->writeField($pdf, 'Baujahr', $messung->boilerYear ?? '');
            $this->writeField($pdf, 'Leistung in kw', $messung->boilerPower ?? '');
        }

        // === Brennstoff ===
        $brennstoffKey = $messung->cMIS_COMBUSTIBILE ?? 'FUEL_NAT_GAS';
        $brennstoffText = self::BRENNSTOFF_TEXT[$brennstoffKey] ?? 'Erdgas/Metano';
        $this->writeField($pdf, 'DropdownBrennstoff', $brennstoffText);

        // === Messergebnisse ===
        $this->writeField($pdf, 'Tag der Messung', $messung->cMIS_DATA2 ?? '');

        // Ergebnis
        $ergebnisText = ($messung->strEsito === '1') ? 'ja/si' : 'nein/no';
        $this->writeField($pdf, 'DropdownOK', $ergebnisText);

        // Rußzahl
        $this->writeField($pdf, 'Russzahl', $messung->cMIS_IND_OPACITA ?? '0');

        // Ölderivate (cMIS_TRACCE_OLEO: 0=Ja/Spuren, 1=Nein/keine)
        $oelText = ($messung->cMIS_TRACCE_OLEO === '0') ? 'ja/si' : 'nein/no';
        $this->writeField($pdf, 'DropdownOeD', $oelText);

        // Temperaturen
        $this->writeField($pdf, 'WT', $messung->cMIS_T_LIQ_CONV ?? '');
        $this->writeField($pdf, 'Abgastemperatur', $messung->cMIS_T_GAS_COMB ?? '');
        $this->writeField($pdf, 'VT', $messung->cMIS_T_ARIA_COMB ?? '');

        // Messwerte
        $this->writeField($pdf, 'O2', $messung->cMIS_OSSIGENO ?? '');
        $this->writeField($pdf, 'CO2', $messung->cMIS_ANIDRIDE_CARBONICA ?? '');
        $this->writeField($pdf, 'NOx', $messung->cMIS_BIOSSIDO_AZOTO ?? '');
        $this->writeField($pdf, 'CO', $messung->cMIS_MONOSSSIDO ?? '');
    }

    /**
     * Schreibt einen Wert an die Position eines Formularfeldes
     * Koordinaten sind aus der field_info.json (PDF-Koordinaten, y=0 unten)
     */
    private function writeField(Fpdi $pdf, string $fieldId, string $value, float $fontSize = 10, string $style = ''): void
    {
        if ($value === '' || $value === null) {
            return;
        }

        $field = $this->getFieldPosition($fieldId);
        if (!$field) {
            return;
        }

        // PDF-Koordinaten (y=0 unten) zu FPDF-Koordinaten (y=0 oben) konvertieren
        // rect = [left, bottom, right, top] in PDF points (1pt = 1/72 inch)
        // FPDF arbeitet in mm (1mm = 2.835pt)
        $ptToMm = 25.4 / 72; // 0.3528 mm pro point

        $x = $field['rect'][0] * $ptToMm;
        $y = (self::PAGE_HEIGHT - $field['rect'][3]) * $ptToMm; // top in PDF coords
        $w = ($field['rect'][2] - $field['rect'][0]) * $ptToMm;
        $h = ($field['rect'][3] - $field['rect'][1]) * $ptToMm;

        $pdf->SetFont('Helvetica', $style, $fontSize);
        $pdf->SetXY($x, $y);
        $pdf->Cell($w, $h, utf8_decode($value), 0, 0, 'L');
    }

    /**
     * Gibt die Feld-Position aus der Konfiguration zurück
     */
    private function getFieldPosition(string $fieldId): ?array
    {
        // Feld-Positionen (aus field_info.json extrahiert)
        // rect = [left, bottom, right, top] in PDF points
        $fields = [
            'Name'              => ['rect' => [51.02, 734.20, 266.46, 748.38]],
            'Aufstellungsort'   => ['rect' => [340.16, 734.20, 571.10, 748.38]],
            'StrasseAuf'        => ['rect' => [314.65, 707.27, 464.65, 721.45]],
            'NrAuf'             => ['rect' => [531.06, 707.27, 578.27, 721.45]],
            'StrasseBet'        => ['rect' => [102.05, 694.52, 229.61, 708.69]],
            'NrBet'             => ['rect' => [257.95, 694.52, 308.98, 708.69]],
            'FraltionBet'       => ['rect' => [102.05, 673.26, 252.05, 687.43]],
            'FraktionAuf'       => ['rect' => [396.85, 673.26, 572.60, 687.43]],
            'GemeindeBet'       => ['rect' => [102.05, 649.16, 252.05, 663.34]],
            'GemeindeAuf'       => ['rect' => [396.85, 649.16, 572.60, 663.34]],
            'SteuernummerBet'   => ['rect' => [22.91, 623.65, 172.91, 637.83]],
            'AnlagenCODE'       => ['rect' => [500.19, 603.81, 576.98, 617.98]],
            'Text1'             => ['rect' => [150.24, 587.48, 447.87, 603.81]],
            'Status der Anlage' => ['rect' => [109.13, 568.38, 138.90, 582.55]],
            'Kessel Hersteller' => ['rect' => [107.72, 544.28, 286.30, 558.46]],
            'Baujahr'           => ['rect' => [333.07, 544.28, 399.69, 558.46]],
            'Leistung in kw'    => ['rect' => [531.46, 544.28, 572.60, 558.46]],
            'DropdownBrennstoff'=> ['rect' => [130.39, 507.27, 331.65, 527.27]],
            'Tag der Messung'   => ['rect' => [494.65, 463.49, 561.26, 477.67]],
            'DropdownOK'        => ['rect' => [288.00, 429.40, 360.00, 449.40]],
            'Russzahl'          => ['rect' => [189.81, 395.88, 240.94, 410.05]],
            'DropdownOeD'       => ['rect' => [493.23, 395.38, 572.60, 415.38]],
            'WT'                => ['rect' => [189.81, 372.79, 240.94, 386.96]],
            'Abgastemperatur'   => ['rect' => [493.23, 372.79, 547.09, 386.96]],
            'VT'                => ['rect' => [189.81, 350.11, 240.94, 364.28]],
            'O2'                => ['rect' => [493.23, 350.11, 547.09, 364.28]],
            'CO2'               => ['rect' => [189.92, 326.01, 241.05, 340.19]],
            'NOx'               => ['rect' => [493.23, 326.01, 547.09, 340.19]],
            'CO'                => ['rect' => [189.81, 302.34, 240.94, 316.51]],
            'Mitteilungen'      => ['rect' => [308.98, 162.86, 569.76, 246.64]],
        ];

        return $fields[$fieldId] ?? null;
    }
}
