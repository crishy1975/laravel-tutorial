<?php
/*
════════════════════════════════════════════════════════════════════════════
DATEI: PosTransaktionenImport.php
PFAD:  app/Services/PosTransaktionenImport.php
(Liest den Export "account_transactions_*.xlsx" aus dem myPOS-Portal)
════════════════════════════════════════════════════════════════════════════
*/

namespace App\Services;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class PosTransaktionenImport
{
    /** Spaltenüberschriften (klein geschrieben) → interner Name */
    private const SPALTEN = [
        'datum'        => ['date initiated', 'datum', 'data'],
        'datum_alt'    => ['date settled'],
        'typ'          => ['type', 'typ', 'tipo'],
        'referenz'     => ['transaction reference', 'transaktionsreferenz', 'riferimento transazione'],
        'beschreibung' => ['description', 'beschreibung', 'descrizione'],
        'karte'        => ['payment from card', 'karte', 'carta'],
        'betrag'       => ['amount', 'betrag', 'importo'],
        'waehrung'     => ['currency', 'währung', 'valuta'],
        'terminal'     => ['terminal name', 'terminal'],
    ];

    /**
     * @return array{
     *   zahlungen: array<int, array{referenz:string, datum_zeit:string, betrag:float, karte:?string, terminal:?string, beschreibung:?string}>,
     *   ignoriert: array<string,int>
     * }
     */
    public function lesen(string $pfad, string $endung): array
    {
        $typ = match (strtolower($endung)) {
            'xlsx'        => 'Xlsx',
            'xls'         => 'Xls',
            'csv', 'txt'  => 'Csv',
            default       => throw new \RuntimeException("Dateityp .{$endung} wird nicht unterstützt."),
        };

        $reader = IOFactory::createReader($typ);
        $reader->setReadDataOnly(true);
        $zeilen = $reader->load($pfad)->getActiveSheet()->toArray(null, true, false, false);

        [$kopfIndex, $spalten] = $this->findeKopfzeile($zeilen);

        $zahlungen = [];
        $ignoriert = ['Gebühren' => 0, 'Überweisungen' => 0, 'Sonstige' => 0];

        foreach (array_slice($zeilen, $kopfIndex + 1) as $zeile) {
            $wert = fn(string $feld) => isset($spalten[$feld]) ? ($zeile[$spalten[$feld]] ?? null) : null;

            $referenz = trim((string) $wert('referenz'));
            $betrag   = $this->zahl($wert('betrag'));

            if ($referenz === '' || $betrag === null) {
                continue; // Leerzeile
            }

            $typText = strtolower(trim((string) $wert('typ')));

            if ($typText === 'fee' || ($typText === 'payment' && $betrag <= 0)) {
                $ignoriert['Gebühren']++;
                continue;
            }
            if (str_contains($typText, 'transfer')) {
                $ignoriert['Überweisungen']++;
                continue;
            }
            if ($typText !== 'payment' || $betrag <= 0) {
                $ignoriert['Sonstige']++;
                continue;
            }

            $waehrung = strtoupper(trim((string) $wert('waehrung')));
            if ($waehrung !== '' && $waehrung !== 'EUR') {
                $ignoriert['Sonstige']++;
                continue;
            }

            $datum = $this->datum($wert('datum')) ?? $this->datum($wert('datum_alt'));
            if (!$datum) {
                $ignoriert['Sonstige']++;
                continue;
            }

            $zahlungen[] = [
                'referenz'     => $referenz,
                'datum_zeit'   => $datum->format('Y-m-d H:i:s'),
                'betrag'       => round($betrag, 2),
                'karte'        => $this->text($wert('karte')),
                'terminal'     => $this->text($wert('terminal')),
                'beschreibung' => $this->text($wert('beschreibung'), 255),
            ];
        }

        return [
            'zahlungen' => $zahlungen,
            'ignoriert' => array_filter($ignoriert),
        ];
    }

    private function findeKopfzeile(array $zeilen): array
    {
        foreach (array_slice($zeilen, 0, 20, true) as $index => $zeile) {
            $spalten = [];
            foreach ($zeile as $i => $zelle) {
                $name = strtolower(trim((string) $zelle));
                foreach (self::SPALTEN as $feld => $namen) {
                    if (!isset($spalten[$feld]) && in_array($name, $namen, true)) {
                        $spalten[$feld] = $i;
                    }
                }
            }
            if (isset($spalten['referenz'], $spalten['betrag'], $spalten['typ'])) {
                return [$index, $spalten];
            }
        }

        throw new \RuntimeException('Kopfzeile nicht gefunden – ist das der Transaktions-Export aus dem POS-Portal?');
    }

    private function datum($wert): ?Carbon
    {
        if ($wert === null || $wert === '') {
            return null;
        }
        if ($wert instanceof \DateTimeInterface) {
            return Carbon::instance($wert);
        }
        if (is_numeric($wert)) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $wert));
        }

        $wert = trim((string) $wert);
        // myPOS exportiert US-Format: 01/16/2026 15:27:28
        foreach (['m/d/Y H:i:s', 'm/d/Y H:i', 'm/d/Y', 'd.m.Y H:i:s', 'd.m.Y H:i', 'd.m.Y', 'Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d'] as $format) {
            try {
                $d = Carbon::createFromFormat('!' . $format, $wert);
                if ($d && $d->format($format) === $wert) {
                    return $d;
                }
            } catch (\Throwable $e) {}
        }

        return null;
    }

    private function zahl($wert): ?float
    {
        if ($wert === null || $wert === '') {
            return null;
        }
        if (is_numeric($wert)) {
            return (float) $wert;
        }
        $wert = str_replace(['€', ' ', 'EUR'], '', (string) $wert);
        if (str_contains($wert, ',')) {
            $wert = str_replace(['.', ','], ['', '.'], $wert);
        }
        return is_numeric($wert) ? (float) $wert : null;
    }

    private function text($wert, int $max = 100): ?string
    {
        $t = trim((string) $wert);
        return $t === '' ? null : mb_substr($t, 0, $max);
    }
}
