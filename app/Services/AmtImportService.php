<?php

namespace App\Services;

use App\Models\Impianto;
use App\Models\Messung;

/**
 * Import-Service für das Amt-Format (Fix-Width, 58 Zeichen pro Zeile).
 * Siehe AmtExportService für Format-Details.
 */
class AmtImportService
{
    public const ZEILEN_LAENGE = 58;
    public const HEADER_PREFIX = 'Kontrolleur------------';

    /**
     * Parst den Inhalt einer Amt-Datei.
     *
     * @return array{
     *     kontrolleur_id: ?string,
     *     rows: array<int, array{line_no: int, data: ?array, errors: array<int, string>, existing: bool}>,
     *     header_error: ?string,
     * }
     */
    public function parseFile(string $content): array
    {
        // Zeilen-Trenner normalisieren (CRLF, LF, CR)
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        $lines = array_values(array_filter(
            explode("\n", $content),
            fn($l) => trim($l) !== ''
        ));

        $result = [
            'kontrolleur_id' => null,
            'rows' => [],
            'header_error' => null,
        ];

        if (empty($lines)) {
            $result['header_error'] = 'Datei ist leer.';
            return $result;
        }

        // Erste Zeile: Header
        $header = $lines[0];
        if (!str_starts_with($header, self::HEADER_PREFIX)) {
            $result['header_error'] = "Header fehlt oder ungültig. Erwartet: '" . self::HEADER_PREFIX . "<ID>', erhalten: '{$header}'";
            return $result;
        }
        $result['kontrolleur_id'] = trim(substr($header, strlen(self::HEADER_PREFIX)));

        // Datenzeilen
        $bestehendeKeys = $this->loadBestehendeKeys();

        for ($i = 1; $i < count($lines); $i++) {
            $line = $lines[$i];
            $row = $this->parseLine($line, $i + 1);

            // Duplikat-Check
            if ($row['data']) {
                $key = $this->makeKey(
                    $row['data']['cIM_CODICE'],
                    $row['data']['cMIS_DATA'],
                    $row['data']['cMIS_STADIO'],
                    $row['data']['cMIS_TIPO']
                );
                $row['existing'] = isset($bestehendeKeys[$key]);
                $row['existing_id'] = $bestehendeKeys[$key] ?? null;
            }

            $result['rows'][] = $row;
        }

        return $result;
    }

    /**
     * Parst eine einzelne Zeile.
     *
     * @return array{line_no: int, raw: string, data: ?array, errors: array<int, string>, existing: bool}
     */
    public function parseLine(string $line, int $lineNo): array
    {
        $out = [
            'line_no' => $lineNo,
            'raw' => $line,
            'data' => null,
            'errors' => [],
            'existing' => false,
            'existing_id' => null,
        ];

        if (strlen($line) !== self::ZEILEN_LAENGE) {
            $out['errors'][] = sprintf(
                'Zeile hat Länge %d, erwartet %d',
                strlen($line), self::ZEILEN_LAENGE
            );
            // Trotzdem versuchen zu parsen, falls nur Leerzeichen/Padding fehlt
            if (strlen($line) < self::ZEILEN_LAENGE) {
                return $out;
            }
        }

        try {
            $data = [
                'cIM_CODICE'              => substr($line, 0, 6),
                'cMIS_TIPO'               => substr($line, 6, 3),
                'cMIS_DATA'               => substr($line, 9, 8),
                'cMIS_STADIO'             => substr($line, 17, 1),
                'strEsito'                => substr($line, 18, 1),
                'cMIS_COMBUSTIBILE_CODE'  => substr($line, 19, 1),
                'cMIS_T_GAS_COMB'         => substr($line, 20, 3),
                'cMIS_T_ARIA_COMB'        => substr($line, 23, 2),
                'cMIS_OSSIGENO'           => substr($line, 25, 4),
                'cMIS_MONOSSSIDO'         => substr($line, 29, 4),
                'cMIS_BIOSSIDO_AZOTO'     => substr($line, 33, 4),
                'cMIS_ANIDRIDE_CARBONICA' => substr($line, 37, 4),
                'cMIS_PERD_FUMI'          => substr($line, 41, 4),
                'cMIS_T_LIQ_CONV'         => substr($line, 45, 3),
                'cMIS_IND_OPACITA'        => substr($line, 48, 1),
                'cMIS_TRACCE_OLEO'        => substr($line, 49, 1),
                'cMIS_CONSUMO'            => substr($line, 50, 8),
            ];

            // Brennstoff-Code → interner FuelId
            $bCode = (int) $data['cMIS_COMBUSTIBILE_CODE'];
            $brennstoffMap = config('messungen.amt_code_to_brennstoff', []);
            $data['cMIS_COMBUSTIBILE'] = $brennstoffMap[$bCode] ?? null;

            // cMIS_DATA2 aus cMIS_DATA generieren: ddMMyyyy → dd.MM.yyyy
            if (preg_match('/^(\d{2})(\d{2})(\d{4})$/', $data['cMIS_DATA'], $mm)) {
                $data['cMIS_DATA2'] = "{$mm[1]}.{$mm[2]}.{$mm[3]}";
            } else {
                $data['cMIS_DATA2'] = '';
                $out['errors'][] = "Datum '{$data['cMIS_DATA']}' ungültig";
            }

            // Brennstoff-Name
            $brennstoffe = config('messungen.brennstoff_amt_code', []);
            $namenMap = [
                'FUEL_LIGHT_OIL' => ['nr' => 1, 'text' => 'Heizöl/gasolio'],
                'FUEL_HEAVY_OIL' => ['nr' => 1, 'text' => 'Heizöl/gasolio'],
                'FUEL_NAT_GAS'   => ['nr' => 3, 'text' => 'Erdgas/metano'],
                'FUEL_PROPANE'   => ['nr' => 6, 'text' => 'Flüssiggas/gas liquido'],
                'FUEL_BUTANE'    => ['nr' => 6, 'text' => 'Flüssiggas/gas liquido'],
                'FUEL_PELLETS'   => ['nr' => 7, 'text' => 'Pellets'],
                'FUEL_WOOD'      => ['nr' => 7, 'text' => 'Holz/legna'],
            ];
            $nm = $namenMap[$data['cMIS_COMBUSTIBILE'] ?? ''] ?? ['nr' => 0, 'text' => ''];
            $data['cMIS_COMBUSTIBILE_N'] = $nm['nr'];
            $data['cMIS_COMBUSTIBILE_P'] = $nm['text'];

            $out['data'] = $data;
        } catch (\Throwable $e) {
            $out['errors'][] = 'Parse-Fehler: ' . $e->getMessage();
        }

        return $out;
    }

    /**
     * Liefert alle bestehenden (IM_CODICE, cMIS_DATA, cMIS_STADIO, cMIS_TIPO)-Kombinationen
     * als assoziatives Array [key => messung_id].
     */
    private function loadBestehendeKeys(): array
    {
        $map = [];
        Messung::select('id', 'cIM_CODICE', 'cMIS_DATA', 'cMIS_STADIO', 'cMIS_TIPO')
            ->orderBy('id')
            ->chunk(2000, function ($chunk) use (&$map) {
                foreach ($chunk as $m) {
                    $key = $this->makeKey(
                        $m->cIM_CODICE, $m->cMIS_DATA, $m->cMIS_STADIO, $m->cMIS_TIPO
                    );
                    $map[$key] = $m->id;
                }
            });
        return $map;
    }

    private function makeKey($codice, $data, $stadio, $tipo): string
    {
        return sprintf('%06d|%s|%s|%s',
            (int) $codice,
            $data,
            $stadio,
            $tipo
        );
    }

    /**
     * Commit: schreibt geparste, geprüfte Zeilen in die DB.
     * Berücksichtigt den vom User gewählten Modus pro Zeile.
     *
     * @param  array  $rows  von parseFile(), ggf. mit 'action'-Feld
     * @param  string  $defaultAction  'skip'|'update'|'insert'
     * @return array{inserted: int, updated: int, skipped: int, errors: array}
     */
    public function commit(array $rows, string $defaultAction = 'skip'): array
    {
        $stats = ['inserted' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []];
        $exportSvc = app(AmtExportService::class);

        foreach ($rows as $row) {
            if (!empty($row['errors']) || empty($row['data'])) {
                $stats['skipped']++;
                continue;
            }

            $action = $row['action'] ?? ($row['existing'] ? $defaultAction : 'insert');

            if ($action === 'skip') {
                $stats['skipped']++;
                continue;
            }

            $d = $row['data'];

            // Anlage existiert?
            $anlage = Impianto::where('Feld_a', $d['cIM_CODICE'])->first();

            $payload = [
                'cIM_CODICE' => $d['cIM_CODICE'],
                'cIM_NAME' => $anlage?->Feld_w ?? '',
                'cMIS_TIPO' => $d['cMIS_TIPO'],
                'cMIS_DATA' => $d['cMIS_DATA'],
                'cMIS_DATA2' => $d['cMIS_DATA2'],
                'cMIS_STADIO' => $d['cMIS_STADIO'],
                'strEsito' => $d['strEsito'],
                'cMIS_COMBUSTIBILE' => $d['cMIS_COMBUSTIBILE'] ?? 'FUEL_NAT_GAS',
                'cMIS_COMBUSTIBILE_N' => $d['cMIS_COMBUSTIBILE_N'],
                'cMIS_COMBUSTIBILE_P' => $d['cMIS_COMBUSTIBILE_P'],
                'cMIS_T_GAS_COMB' => ltrim($d['cMIS_T_GAS_COMB'], '0') ?: '0',
                'cMIS_T_ARIA_COMB' => ltrim($d['cMIS_T_ARIA_COMB'], '0') ?: '0',
                'cMIS_OSSIGENO' => $d['cMIS_OSSIGENO'],
                'cMIS_MONOSSSIDO' => ltrim($d['cMIS_MONOSSSIDO'], '0') ?: '0',
                'cMIS_BIOSSIDO_AZOTO' => ltrim($d['cMIS_BIOSSIDO_AZOTO'], '0') ?: '0',
                'cMIS_ANIDRIDE_CARBONICA' => $d['cMIS_ANIDRIDE_CARBONICA'],
                'cMIS_PERD_FUMI' => $d['cMIS_PERD_FUMI'],
                'cMIS_T_LIQ_CONV' => ltrim($d['cMIS_T_LIQ_CONV'], '0') ?: '0',
                'cMIS_IND_OPACITA' => $d['cMIS_IND_OPACITA'],
                'cMIS_TRACCE_OLEO' => $d['cMIS_TRACCE_OLEO'],
                'cMIS_CONSUMO' => $d['cMIS_CONSUMO'],
                'codeInImpianti' => $anlage ? 1 : 0,
                'boilerYear' => $anlage?->Feld_z ?? '',
                'boilerPower' => $anlage?->Feld_ab ?? '',
            ];

            try {
                if ($action === 'update' && !empty($row['existing_id'])) {
                    Messung::where('id', $row['existing_id'])->update($payload);
                    $stats['updated']++;
                } else {
                    Messung::create($payload);
                    $stats['inserted']++;
                }
            } catch (\Throwable $e) {
                $stats['errors'][] = "Zeile {$row['line_no']}: " . $e->getMessage();
                $stats['skipped']++;
            }
        }

        return $stats;
    }
}
