<?php

namespace App\Services;

use App\Models\Messung;
use Illuminate\Support\Collection;

/**
 * Export-Service für das Amt-Format (Fix-Width, 62 Zeichen pro Zeile).
 *
 * Format (Positionen 1-basiert, inklusive):
 *   1-6    IM_CODICE              6 Ziffern       %06d
 *   7-9    MIS_TIPO               3 Ziffern       %03d
 *   10-17  MIS_DATA               8 Ziffern       ddMMyyyy
 *   18     MIS_STADIO             1 Ziffer        %01d
 *   19     strEsito               1 Ziffer        %01d (0/1)
 *   20     MIS_COMBUSTIBILE       1 Ziffer        Amt-Code (0/1/3/6/7)
 *   21-23  MIS_T_GAS_COMB         3 Ziffern       %03d
 *   24-25  MIS_T_ARIA_COMB        2 Ziffern       %02d
 *   26-29  MIS_OSSIGENO           XX.X            %04.1f
 *   30-33  MIS_MONOSSIDO (CO)     4 Ziffern       %04d
 *   34-37  MIS_BIOSSIDO_AZOTO     4 Ziffern       %04d
 *   38-41  MIS_ANIDRIDE_CARBONICA XX.X            %04.1f
 *   42-45  MIS_PERD_FUMI          XX.X            %04.1f
 *   46-48  MIS_T_LIQ_CONV         3 Ziffern       %03d
 *   49     MIS_IND_OPACITA        1 Ziffer        %01d
 *   50     MIS_TRACCE_OLEO        1 Ziffer        %01d
 *   51-58  MIS_CONSUMO            8 Ziffern       %08d
 *
 * Kopfzeile: "Kontrolleur------------<KONTROLLEUR_ID>"
 * (12 Minus-Zeichen zwischen "Kontrolleur" und ID)
 */
class AmtExportService
{
    public const ZEILEN_LAENGE = 58;
    public const HEADER_PREFIX = 'Kontrolleur------------';

    /**
     * Erzeugt die komplette Export-Datei (Header + Zeilen) als String.
     *
     * @param  Collection<int, Messung>  $messungen
     * @param  string  $kontrolleurId
     * @return string  Dateiinhalt (CRLF-getrennt)
     */
    public function buildFile(Collection $messungen, string $kontrolleurId): string
    {
        $this->validateKontrolleurId($kontrolleurId);

        $lines = [self::HEADER_PREFIX . $kontrolleurId];

        foreach ($messungen as $messung) {
            $lines[] = $this->buildLine($messung);
        }

        return implode("\r\n", $lines) . "\r\n";
    }

    /**
     * Erzeugt eine einzelne Fix-Width-Zeile aus einer Messung.
     *
     * @throws \InvalidArgumentException bei Validierungsfehler
     */
    public function buildLine(Messung $m): string
    {
        $errors = $this->validateMessung($m);
        if (!empty($errors)) {
            throw new \InvalidArgumentException(
                'Messung #' . $m->id . ' ungültig: ' . implode(' | ', $errors)
            );
        }

        $brennstoffCode = $this->getBrennstoffAmtCode($m->cMIS_COMBUSTIBILE);

        // Datum: in DB liegt cMIS_DATA als "ddMMyyyy" (8 Zeichen) → direkt übernehmen
        $datum = $this->normalizeDatum($m->cMIS_DATA);

        $line = ''
            . sprintf('%06d', (int) $m->cIM_CODICE)                     // 1-6
            . sprintf('%03d', (int) ($m->cMIS_TIPO ?: 1))               // 7-9
            . $datum                                                     // 10-17
            . sprintf('%01d', (int) ($m->cMIS_STADIO ?: 1))             // 18
            . sprintf('%01d', (int) $m->strEsito)                       // 19
            . sprintf('%01d', $brennstoffCode)                          // 20
            . sprintf('%03d', (int) $m->cMIS_T_GAS_COMB)                // 21-23
            . sprintf('%02d', (int) $m->cMIS_T_ARIA_COMB)               // 24-25
            . $this->formatDecimal($m->cMIS_OSSIGENO, 2, 1)             // 26-29
            . sprintf('%04d', (int) $m->cMIS_MONOSSSIDO)                // 30-33
            . sprintf('%04d', (int) $m->cMIS_BIOSSIDO_AZOTO)            // 34-37
            . $this->formatDecimal($m->cMIS_ANIDRIDE_CARBONICA, 2, 1)   // 38-41
            . $this->formatDecimal($m->cMIS_PERD_FUMI, 2, 1)            // 42-45
            . sprintf('%03d', (int) ($m->cMIS_T_LIQ_CONV ?: 0))         // 46-48
            . sprintf('%01d', (int) ($m->cMIS_IND_OPACITA ?: 0))        // 49
            . sprintf('%01d', (int) ($m->cMIS_TRACCE_OLEO ?: 0))        // 50
            . sprintf('%08d', (int) ($m->cMIS_CONSUMO ?: 0));          // 51-58

        if (strlen($line) !== self::ZEILEN_LAENGE) {
            throw new \RuntimeException(sprintf(
                'Zeile hat falsche Länge: %d statt %d (Messung #%d): "%s"',
                strlen($line), self::ZEILEN_LAENGE, $m->id, $line
            ));
        }

        return $line;
    }

    /**
     * Validiert eine einzelne Messung. Gibt Liste von Fehlern zurück (leer = OK).
     *
     * @return array<int, string>
     */
    public function validateMessung(Messung $m): array
    {
        $errors = [];

        // IM_CODICE: 6 Ziffern, 0-999999
        $codex = (int) $m->cIM_CODICE;
        if ($codex < 0 || $codex > 999999) {
            $errors[] = "Kodex '{$m->cIM_CODICE}' außerhalb Bereich 0-999999";
        }

        // TIPO: 3 Ziffern, 0-999
        $tipo = (int) ($m->cMIS_TIPO ?: 1);
        if ($tipo < 0 || $tipo > 999) {
            $errors[] = "Typ '{$m->cMIS_TIPO}' außerhalb 0-999";
        }

        // DATA: muss 8 Ziffern im Format ddMMyyyy sein
        if (empty($m->cMIS_DATA) || !preg_match('/^\d{8}$/', $m->cMIS_DATA)) {
            $errors[] = "Datum '{$m->cMIS_DATA}' nicht im Format ddMMyyyy";
        } else {
            $tag = (int) substr($m->cMIS_DATA, 0, 2);
            $monat = (int) substr($m->cMIS_DATA, 2, 2);
            $jahr = (int) substr($m->cMIS_DATA, 4, 4);
            if (!checkdate($monat, $tag, $jahr)) {
                $errors[] = "Datum '{$m->cMIS_DATA}' ungültig";
            }
        }

        // STADIO: 1 Ziffer, 0-9 (höhere Werte werden via applyAutoCorrections auf 1 gesetzt)
        $stadio = (int) ($m->cMIS_STADIO ?: 0);
        if ($stadio < 0 || $stadio > 9) {
            $errors[] = "Stadio '{$m->cMIS_STADIO}' muss 0-9 sein (max 1 Ziffer)";
        }

        // strEsito: 0 oder 1
        $esito = (int) $m->strEsito;
        if (!in_array($esito, [0, 1], true)) {
            $errors[] = "Esito '{$m->strEsito}' muss 0 oder 1 sein";
        }

        // COMBUSTIBILE: mappable auf Amt-Code
        $brennstoffCode = $this->getBrennstoffAmtCode($m->cMIS_COMBUSTIBILE);
        if ($brennstoffCode < 0 || $brennstoffCode > 9) {
            $errors[] = "Brennstoff '{$m->cMIS_COMBUSTIBILE}' liefert ungültigen Amt-Code {$brennstoffCode}";
        }

        // T_GAS_COMB (Abgastemperatur): 10-500°C
        $tGas = (int) $m->cMIS_T_GAS_COMB;
        if ($tGas < 10 || $tGas > 500) {
            $errors[] = "Abgastemperatur '{$m->cMIS_T_GAS_COMB}' außerhalb 10-500°C";
        }

        // T_ARIA_COMB (Verbrennungslufttemperatur): 0-60°C
        // (Negative Werte werden via applyAutoCorrections auf 1 gesetzt)
        $tAria = (int) $m->cMIS_T_ARIA_COMB;
        if ($tAria < 0 || $tAria > 60) {
            $errors[] = "Verbrennungslufttemperatur '{$m->cMIS_T_ARIA_COMB}' außerhalb 0-60°C";
        }

        // OSSIGENO (O2): 0-21 %
        $o2 = (float) str_replace(',', '.', (string) $m->cMIS_OSSIGENO);
        if ($o2 < 0 || $o2 > 21) {
            $errors[] = "O2 '{$m->cMIS_OSSIGENO}' außerhalb 0,0-21,0 %";
        }

        // MONOSSSIDO (CO): 0-9999 (höhere Werte werden auf 9999 geclampt)
        $co = (int) $m->cMIS_MONOSSSIDO;
        if ($co < 0 || $co > 9999) {
            $errors[] = "CO '{$m->cMIS_MONOSSSIDO}' außerhalb 0-9999 mg/m³";
        }

        // BIOSSIDO_AZOTO (NOx): 0-9999 (höhere Werte werden auf 9999 geclampt)
        $nox = (int) $m->cMIS_BIOSSIDO_AZOTO;
        if ($nox < 0 || $nox > 9999) {
            $errors[] = "NOx '{$m->cMIS_BIOSSIDO_AZOTO}' außerhalb 0-9999 mg/m³";
        }

        // ANIDRIDE_CARBONICA (CO2): 0.0-99.9 %, zusätzlich max. CO2max je Brennstoff
        $co2 = (float) str_replace(',', '.', (string) $m->cMIS_ANIDRIDE_CARBONICA);
        if ($co2 < 0 || $co2 > 99.9) {
            $errors[] = "CO2 '{$m->cMIS_ANIDRIDE_CARBONICA}' außerhalb 0,0-99,9 %";
        } else {
            $co2Max = $this->getCo2Max($m->cMIS_COMBUSTIBILE);
            if ($co2Max > 0 && $co2 > $co2Max) {
                $errors[] = sprintf(
                    "CO2 %.1f %% übersteigt CO2max %.1f %% für Brennstoff %s",
                    $co2, $co2Max, $m->cMIS_COMBUSTIBILE
                );
            }
        }

        // PERD_FUMI (Abgasverlust): 0.0-99.9 %
        // (Negative Werte werden via applyAutoCorrections auf 1.0 gesetzt)
        $perd = (float) str_replace(',', '.', (string) $m->cMIS_PERD_FUMI);
        if ($perd < 0 || $perd > 99.9) {
            $errors[] = "Abgasverlust '{$m->cMIS_PERD_FUMI}' außerhalb 0,0-99,9 %";
        }

        // T_LIQ_CONV (Wärmeträgertemperatur): 5-300°C, 0 toleriert (= nicht gemessen)
        $tLiq = (int) ($m->cMIS_T_LIQ_CONV ?: 0);
        if ($tLiq !== 0 && ($tLiq < 5 || $tLiq > 300)) {
            $errors[] = "Wärmeträgertemperatur '{$m->cMIS_T_LIQ_CONV}' außerhalb 5-300°C";
        }

        // IND_OPACITA: 0-9
        $opa = (int) ($m->cMIS_IND_OPACITA ?: 0);
        if ($opa < 0 || $opa > 9) {
            $errors[] = "Rußzahl '{$m->cMIS_IND_OPACITA}' außerhalb 0-9";
        }

        // TRACCE_OLEO: 0 oder 1
        $oel = (int) ($m->cMIS_TRACCE_OLEO ?: 0);
        if (!in_array($oel, [0, 1], true)) {
            $errors[] = "Ölspuren '{$m->cMIS_TRACCE_OLEO}' muss 0 oder 1 sein";
        }

        // CONSUMO: 0-99999999 (8 Ziffern)
        $consumo = (int) ($m->cMIS_CONSUMO ?: 0);
        if ($consumo < 0 || $consumo > 99999999) {
            $errors[] = "Konsum '{$m->cMIS_CONSUMO}' außerhalb 8 Ziffern (max 99999999)";
        }

        return $errors;
    }

    /**
     * Validiert alle Messungen und gibt Report zurück.
     * Wendet vorher Auto-Korrekturen an (speichert diese auch in die DB).
     *
     * @param  Collection<int, Messung>  $messungen
     * @return array{valid: Collection, invalid: array<int, array{messung: Messung, errors: array}>, corrected: array<int, array{messung: Messung, corrections: array}>}
     */
    public function validateAll(Collection $messungen): array
    {
        $valid = collect();
        $invalid = [];
        $corrected = [];

        foreach ($messungen as $m) {
            $corrections = $this->applyAutoCorrections($m);
            if (!empty($corrections)) {
                $corrected[] = ['messung' => $m, 'corrections' => $corrections];
            }

            $errors = $this->validateMessung($m);
            if (empty($errors)) {
                $valid->push($m);
            } else {
                $invalid[] = ['messung' => $m, 'errors' => $errors];
            }
        }

        return ['valid' => $valid, 'invalid' => $invalid, 'corrected' => $corrected];
    }

    /**
     * Validiert Kontrolleur-ID (Format NNNNN.N o.ä.)
     */
    public function validateKontrolleurId(string $id): void
    {
        $id = trim($id);
        if ($id === '') {
            throw new \InvalidArgumentException(
                'Kontrolleur-ID fehlt. Bitte in .env setzen: KONTROLLEUR_ID=...'
            );
        }
        if (!preg_match('/^[\d.]{1,20}$/', $id)) {
            throw new \InvalidArgumentException(
                "Kontrolleur-ID '{$id}' ungültig. Nur Ziffern und Punkt erlaubt."
            );
        }
    }

    /**
     * Maximaler CO2-Gehalt je Brennstoff (Vol-% trocken, stöchiometrisch).
     * Referenzwerte; Messungen mit CO2 > CO2max sind physikalisch unmöglich.
     */
    public const CO2_MAX = [
        'FUEL_LIGHT_OIL' => 15.4,  // Heizöl leicht
        'FUEL_HEAVY_OIL' => 15.4,  // Heizöl schwer (ca.)
        'FUEL_NAT_GAS'   => 11.9,  // Methan / Erdgas
        'FUEL_PROPANE'   => 13.8,  // Propan
        'FUEL_BUTANE'    => 14.1,  // Butan
        'FUEL_PELLETS'   => 20.0,  // Pellets wie Holz
        'FUEL_WOOD'      => 20.0,  // Holz
    ];

    /**
     * CO2max für einen Brennstoff (0.0 wenn unbekannt → Check greift nicht).
     */
    public function getCo2Max(?string $fuelId): float
    {
        if (empty($fuelId)) {
            return 0.0;
        }
        return (float) (self::CO2_MAX[$fuelId] ?? 0.0);
    }

    /**
     * Wendet Auto-Korrekturen auf das Messung-Modell an und speichert sie in die DB.
     *
     * Korrigiert werden:
     *   - cMIS_PERD_FUMI < 0            → 1.0    (z.B. Brennwertkessel mit -0.1)
     *   - cMIS_STADIO > 9               → 1      (nur 1 Ziffer im Amt-Format)
     *   - cMIS_T_ARIA_COMB < 0          → 1      (negative Lufttemp unsinnig)
     *   - cMIS_MONOSSSIDO > 9999        → 9999   (clamp auf Max-Ziffern)
     *   - cMIS_BIOSSIDO_AZOTO > 9999    → 9999   (clamp auf Max-Ziffern)
     *
     * @return array<int, string>  Liste der durchgeführten Korrekturen (leer = nichts geändert)
     */
    public function applyAutoCorrections(Messung $m): array
    {
        $corrections = [];
        $dirty = false;

        $perd = (float) str_replace(',', '.', (string) ($m->cMIS_PERD_FUMI ?? ''));
        if ($perd < 0) {
            $corrections[] = "Abgasverlust {$m->cMIS_PERD_FUMI} → 1.0";
            $m->cMIS_PERD_FUMI = '1.0';
            $dirty = true;
        }

        $stadio = (int) ($m->cMIS_STADIO ?? 0);
        if ($stadio > 9) {
            $corrections[] = "Stadio {$m->cMIS_STADIO} → 1";
            $m->cMIS_STADIO = '1';
            $dirty = true;
        }

        // Nur echte Einträge (nicht null/leer) auf negativ prüfen
        if ($m->cMIS_T_ARIA_COMB !== null && $m->cMIS_T_ARIA_COMB !== '') {
            $tAria = (int) $m->cMIS_T_ARIA_COMB;
            if ($tAria < 0) {
                $corrections[] = "Lufttemperatur {$m->cMIS_T_ARIA_COMB} → 1";
                $m->cMIS_T_ARIA_COMB = '1';
                $dirty = true;
            }
        }

        $co = (int) ($m->cMIS_MONOSSSIDO ?? 0);
        if ($co > 9999) {
            $corrections[] = "CO {$m->cMIS_MONOSSSIDO} → 9999";
            $m->cMIS_MONOSSSIDO = '9999';
            $dirty = true;
        }

        $nox = (int) ($m->cMIS_BIOSSIDO_AZOTO ?? 0);
        if ($nox > 9999) {
            $corrections[] = "NOx {$m->cMIS_BIOSSIDO_AZOTO} → 9999";
            $m->cMIS_BIOSSIDO_AZOTO = '9999';
            $dirty = true;
        }

        if ($dirty) {
            $m->save();
        }

        return $corrections;
    }

    /**
     * Interner FuelId → Amt-Code (1 Ziffer)
     */
    public function getBrennstoffAmtCode(?string $fuelId): int
    {
        if (empty($fuelId)) {
            return 0;
        }
        $map = config('messungen.brennstoff_amt_code', []);
        return (int) ($map[$fuelId] ?? 0);
    }

    /**
     * Normalisiert Datum auf 8-stelliges ddMMyyyy.
     * Akzeptiert auch "dd.MM.yyyy" und konvertiert.
     */
    private function normalizeDatum(?string $datum): string
    {
        if (empty($datum)) {
            return '00000000';
        }
        // Bereits ddMMyyyy?
        if (preg_match('/^\d{8}$/', $datum)) {
            return $datum;
        }
        // dd.MM.yyyy?
        if (preg_match('/^(\d{2})\.(\d{2})\.(\d{4})$/', $datum, $m)) {
            return $m[1] . $m[2] . $m[3];
        }
        // Fallback: alles was keine Ziffer ist raus
        $clean = preg_replace('/\D/', '', $datum);
        return str_pad(substr($clean, 0, 8), 8, '0', STR_PAD_LEFT);
    }

    /**
     * Formatiert Dezimalzahl mit Punkt, fester Gesamtlänge.
     * z.B. (6.3, 2, 1) → "06.3"  (Länge = 2 + 1 + 1 = 4)
     */
    private function formatDecimal($value, int $intDigits, int $fracDigits): string
    {
        $v = (float) str_replace(',', '.', (string) ($value ?? 0));
        if ($v < 0) $v = 0;
        $max = (10 ** $intDigits) - (10 ** -$fracDigits);
        if ($v > $max) $v = $max;

        // sprintf mit Punkt als Dezimaltrenner (Locale-unabhängig dank %F)
        return sprintf('%0' . ($intDigits + $fracDigits + 1) . '.' . $fracDigits . 'F', $v);
    }

    /**
     * Erzeugt Dateinamen: amt-export_YYYY-MM-DD_<anzahl>msg.txt
     */
    public function buildFilename(int $anzahl): string
    {
        return sprintf('amt-export_%s_%dmsg.txt', date('Y-m-d'), $anzahl);
    }
}
