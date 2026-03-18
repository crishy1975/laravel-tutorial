<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Messung;
use App\Models\Impianto;

class ProtokollController extends Controller
{
    /**
     * Brennstoff-Mapping: interner Key → PDF Dropdown-Wert
     */
    private const BRENNSTOFF_MAP = [
        'FUEL_LIGHT_OIL' => 'H',  // Heizöl
        'FUEL_HEAVY_OIL' => 'H',  // Heizöl
        'FUEL_NAT_GAS'   => 'E',  // Erdgas
        'FUEL_PROPANE'    => 'F',  // Flüssiggas
        'FUEL_BUTANE'     => 'F',  // Flüssiggas
        'FUEL_PELLETS'    => 'P',  // Pellets
        'FUEL_WOOD'       => 'H',  // Holz (mapped to H in dropdown)
    ];

    /**
     * Generiert ein PDF-Protokoll für eine Messung
     */
    public function generate($messungId)
    {
        $messung = Messung::findOrFail($messungId);
        
        // Anlage laden wenn zugeordnet
        $anlage = null;
        if ($messung->codeInImpianti > 0 && $messung->cIM_CODICE) {
            $anlage = Impianto::where('Feld_a', $messung->cIM_CODICE)->first();
        }

        // Feld-Werte zusammenbauen
        $fields = $this->buildFieldValues($messung, $anlage);

        // JSON-Datei schreiben
        $tempDir = sys_get_temp_dir();
        $fieldValuesPath = $tempDir . '/protokoll_fields_' . $messungId . '.json';
        $outputPath = $tempDir . '/protokoll_' . $messungId . '.pdf';
        $vorlagePath = resource_path('pdf/vorlage.pdf');

        if (!file_exists($vorlagePath)) {
            abort(404, 'PDF-Vorlage nicht gefunden. Bitte vorlage.pdf in resources/pdf/ ablegen.');
        }

        file_put_contents($fieldValuesPath, json_encode($fields, JSON_UNESCAPED_UNICODE));

        // Python-Script ausführen
        $scriptPath = base_path('scripts/fill_pdf.py');
        
        // Script erstellen falls nicht vorhanden
        if (!file_exists($scriptPath)) {
            $this->createFillScript($scriptPath);
        }

        $command = sprintf(
            'python3 %s %s %s %s 2>&1',
            escapeshellarg($scriptPath),
            escapeshellarg($vorlagePath),
            escapeshellarg($fieldValuesPath),
            escapeshellarg($outputPath)
        );

        $output = shell_exec($command);

        // Temporäre JSON-Datei aufräumen
        @unlink($fieldValuesPath);

        if (!file_exists($outputPath)) {
            abort(500, 'PDF konnte nicht generiert werden: ' . $output);
        }

        // Dateiname: Kodex_Datum.pdf
        $kodex = $messung->cIM_CODICE ?: 'ohne';
        $datum = str_replace('.', '', $messung->cMIS_DATA2 ?: date('dmY'));
        $filename = "Protokoll_{$kodex}_{$datum}.pdf";

        return response()->download($outputPath, $filename, [
            'Content-Type' => 'application/pdf',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Baut die Feld-Werte für das PDF-Formular
     */
    private function buildFieldValues(Messung $messung, ?Impianto $anlage): array
    {
        $fields = [];

        // === Betreiber / Aufstellungsort ===
        if ($anlage) {
            // Name (Betreiber)
            $betreiberName = trim(($anlage->Feld_c ?? '') . ' ' . ($anlage->Feld_d ?? ''));
            if (empty(trim($betreiberName))) {
                $betreiberName = $anlage->Feld_w ?? $messung->cIM_NAME ?? '';
            }
            $fields[] = ['field_id' => 'Name', 'page' => 1, 'value' => $betreiberName];

            // Aufstellungsort
            $fields[] = ['field_id' => 'Aufstellungsort', 'page' => 1, 'value' => $anlage->Feld_w ?? ''];

            // Adresse Aufstellungsort
            $strasse = $anlage->Feld_l ?: ($anlage->Feld_m ?? '');
            $fields[] = ['field_id' => 'StrasseAuf', 'page' => 1, 'value' => $strasse];
            $fields[] = ['field_id' => 'NrAuf', 'page' => 1, 'value' => $anlage->Feld_n ?? ''];

            // Gemeinde Aufstellungsort
            $gemeinde = $anlage->Feld_h ?: ($anlage->Feld_i ?? '');
            $fields[] = ['field_id' => 'GemeindeAuf', 'page' => 1, 'value' => $gemeinde];

            // Technische Daten
            $fields[] = ['field_id' => 'AnlagenCODE', 'page' => 1, 'value' => $anlage->Feld_a ?? ''];
            $fields[] = ['field_id' => 'Status der Anlage', 'page' => 1, 'value' => '1'];
            $fields[] = ['field_id' => 'Kessel Hersteller', 'page' => 1, 'value' => $anlage->Feld_y ?? ''];
            $fields[] = ['field_id' => 'Baujahr', 'page' => 1, 'value' => $anlage->Feld_z ?? ''];
            $fields[] = ['field_id' => 'Leistung in kw', 'page' => 1, 'value' => $anlage->Feld_ab ?? ''];
        } else {
            // Ohne Anlage: nur Messung-Daten
            $fields[] = ['field_id' => 'Name', 'page' => 1, 'value' => $messung->cIM_NAME ?? ''];
            $fields[] = ['field_id' => 'Aufstellungsort', 'page' => 1, 'value' => $messung->cIM_NAME ?? ''];
            $fields[] = ['field_id' => 'AnlagenCODE', 'page' => 1, 'value' => $messung->cIM_CODICE ?? ''];
            $fields[] = ['field_id' => 'Kessel Hersteller', 'page' => 1, 'value' => ''];
            $fields[] = ['field_id' => 'Baujahr', 'page' => 1, 'value' => $messung->boilerYear ?? ''];
            $fields[] = ['field_id' => 'Leistung in kw', 'page' => 1, 'value' => $messung->boilerPower ?? ''];
        }

        // === Brennstoff ===
        $brennstoffKey = $messung->cMIS_COMBUSTIBILE ?? 'FUEL_NAT_GAS';
        $brennstoffPdf = self::BRENNSTOFF_MAP[$brennstoffKey] ?? 'E';
        $fields[] = ['field_id' => 'DropdownBrennstoff', 'page' => 1, 'value' => $brennstoffPdf];

        // === Messergebnisse ===
        $fields[] = ['field_id' => 'Tag der Messung', 'page' => 1, 'value' => $messung->cMIS_DATA2 ?? ''];

        // Ergebnis: J = Ja (positiv), N = Nein (negativ)
        $ergebnis = ($messung->strEsito === '1') ? 'J' : 'N';
        $fields[] = ['field_id' => 'DropdownOK', 'page' => 1, 'value' => $ergebnis];

        // Rußzahl
        $fields[] = ['field_id' => 'Russzahl', 'page' => 1, 'value' => $messung->cMIS_IND_OPACITA ?? '0'];

        // Ölderivate: J = Ja (hat Spuren), N = Nein (keine Spuren)
        // cMIS_TRACCE_OLEO: 0 = Ja (Spuren), 1 = Nein (keine Spuren)
        $oelderivate = ($messung->cMIS_TRACCE_OLEO === '0') ? 'J' : 'N';
        $fields[] = ['field_id' => "Dropdown\u00d6D", 'page' => 1, 'value' => $oelderivate];

        // Temperaturen
        $fields[] = ['field_id' => 'WT', 'page' => 1, 'value' => $messung->cMIS_T_LIQ_CONV ?? ''];
        $fields[] = ['field_id' => 'Abgastemperatur', 'page' => 1, 'value' => $messung->cMIS_T_GAS_COMB ?? ''];
        $fields[] = ['field_id' => 'VT', 'page' => 1, 'value' => $messung->cMIS_T_ARIA_COMB ?? ''];

        // Messwerte
        $fields[] = ['field_id' => 'O2', 'page' => 1, 'value' => $messung->cMIS_OSSIGENO ?? ''];
        $fields[] = ['field_id' => 'CO2', 'page' => 1, 'value' => $messung->cMIS_ANIDRIDE_CARBONICA ?? ''];
        $fields[] = ['field_id' => 'NOx', 'page' => 1, 'value' => $messung->cMIS_BIOSSIDO_AZOTO ?? ''];
        $fields[] = ['field_id' => 'CO', 'page' => 1, 'value' => $messung->cMIS_MONOSSSIDO ?? ''];

        return $fields;
    }

    /**
     * Erstellt das Python-Script zum Befüllen der PDF-Formularfelder
     */
    private function createFillScript(string $path): void
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $script = <<<'PYTHON'
#!/usr/bin/env python3
"""Befüllt PDF-Formularfelder aus einer JSON-Datei."""
import sys
import json
from pypdf import PdfReader, PdfWriter

def fill_pdf(input_pdf, field_values_json, output_pdf):
    with open(field_values_json, 'r', encoding='utf-8') as f:
        fields = json.load(f)
    
    reader = PdfReader(input_pdf)
    writer = PdfWriter()
    
    # Alle Seiten kopieren
    for page in reader.pages:
        writer.add_page(page)
    
    # Felder nach Seite gruppieren
    fields_by_page = {}
    for field in fields:
        page = field.get('page', 1) - 1  # 0-basiert
        if page not in fields_by_page:
            fields_by_page[page] = {}
        fields_by_page[page][field['field_id']] = field['value']
    
    # Formularfelder befüllen
    for page_num, page_fields in fields_by_page.items():
        writer.update_page_form_field_values(
            writer.pages[page_num],
            page_fields
        )
    
    with open(output_pdf, 'wb') as f:
        writer.write(f)

if __name__ == '__main__':
    if len(sys.argv) != 4:
        print(f"Usage: {sys.argv[0]} <input.pdf> <fields.json> <output.pdf>")
        sys.exit(1)
    fill_pdf(sys.argv[1], sys.argv[2], sys.argv[3])
PYTHON;

        file_put_contents($path, $script);
        chmod($path, 0755);
    }
}
