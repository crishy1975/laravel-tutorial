<?php

namespace App\Support;

/**
 * AddressParser
 *  - Normalisiert Namen/Adressen aus VIES (oder ähnlichen Quellen)
 *  - Trennt Hausnummer aus dem Straßenfeld
 *  - Heilt italienische Ortsnamen (entfernt sekundäre/dt. Varianten)
 */
class AddressParser
{
    /**
     * Titel-Schreibung (multibyte) mit italienischen Ausnahmen
     * und Firmenkürzeln in Großbuchstaben.
     */
    public static function titleCaseIt(string $s): string
    {
        $s = trim($s);
        if ($s === '') return $s;

        // Erst komplett klein, dann Title-Case (UTF-8)
        $s = mb_convert_case(mb_strtolower($s, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');

        // Typische Wörter wieder klein schreiben
        $lowerWords = [
            'Di',
            'Del',
            'Della',
            'Dello',
            'Delle',
            'Degli',
            'Dei',
            'Da',
            'Dal',
            'Dalla',
            'Dalle',
            'E',
            'L’',
            'L\'',
            'La',
            'Le',
            'Lo',
            'Il',
            // Straßentypen:
            'Via',
            'Viale',
            'Piazza',
            'Corso',
            'Largo',
            // Heilige:
            'San',
            'Santa',
            'Santo',
        ];
        foreach ($lowerWords as $w) {
            // Wortgrenzen-Variante; \b funktioniert mit Unicode ok, für L’/L' sind beide Varianten gelistet
            $s = preg_replace('/\b' . $w . '\b/u', mb_strtolower($w, 'UTF-8'), $s);
        }

        // Firmenkürzel wieder GROSS (SRL, SRLS, SPA, SNC, SAS, SAPA, GMBH, KG, AG)
        $s = preg_replace_callback(
            '/\b(s\.?r\.?l\.?s?\.?|s\.?p\.?a\.?|s\.?n\.?c\.?|s\.?a\.?s\.?|s\.?a\.?p\.?a\.?|gmbh|k\.?g\.?|a\.?g\.?)\b/ui',
            fn($m) => mb_strtoupper($m[1], 'UTF-8'),
            $s
        );

        return $s;
    }

    /**
     * Nimmt z. B. "LAIVES .LEIFERS." und gibt "Laives" zurück.
     * Entfernt sekundäre/dt. Ortsnamen, überflüssige Punkte/Mehrfach-Leerzeichen.
     */
    public static function pickItalianCity(string $raw): string
    {
        $raw = trim($raw);
        // Unicode-Leerzeichen normalisieren
        $raw = preg_replace('/\s+/u', ' ', $raw);

        // Fälle wie "LAIVES .LEIFERS." → alles ab " ." (Punkt + Name) entfernen
        // Beispiel: "LAIVES .LEIFERS." → "LAIVES"
        $clean = preg_replace('/\s*\.[^.\n]+\.*/u', '', $raw);

        // Alternative Schreibweisen entfernen:
        //  - Klammern: "LAIVES (LEIFERS)" → "LAIVES"
        $clean = preg_replace('/\s*\([^)]+\)\s*$/u', '', $clean);
        //  - Slash:    "LAIVES/LEIFERS" → "LAIVES"
        $clean = preg_replace('/\s*\/\s*[^\/]+$/u', '', $clean);
        //  - Bindestrich: "LAIVES - LEIFERS" → "LAIVES"
        $clean = preg_replace('/\s*-\s*[^-]+$/u', '', $clean);

        // Restliche doppelte Leerzeichen entfernen
        $clean = preg_replace('/\s{2,}/u', ' ', $clean);

        return self::titleCaseIt($clean); // → "Laives"
    }


    /**
     * Trennt Straße und Hausnummer.
     * - Entfernt optional vorangestellten Firmenblock "FIRMA — ..."
     * - Erkennt am Ende stehende Nummern inkl. Suffixen (43, 43A, 43/A, 43-45)
     *
     * @return array{0:string,1:string} [straße, hausnr]
     */
    public static function splitStreetNumber(string $line): array
    {
        $line = trim($line);

        // Unicode-Leerzeichen normalisieren (NBSP, Tabs → normales Leerzeichen)
        $line = preg_replace('/\s+/u', ' ', $line);

        // Wenn versehentlich "FIRMA — VIA …" enthalten ist → alles vor EM-DASH weg
        $line = preg_replace('/^.+?—\s*/u', '', $line);

        // Häufig hängt nach der Nummer noch ein Komma/Punkt → entfernen
        $line = preg_replace('/([0-9][0-9A-Za-z\/\-]*)[.,]\s*$/u', '$1', $line);

        // Nummer am Ende erkennen:
        //  - 43 | 43A | 43/A | 43-45 | 43-45/A
        //  - beliebige horizontale Whitespaces (\h) und Unicode
        if (preg_match('/^(.+?)\h+(\d+(?:[A-Za-z])?(?:[\/\-]\d+(?:[A-Za-z])?)?)\s*$/u', $line, $m)) {
            $street = self::titleCaseIt($m[1]);
            $hnr    = mb_strtoupper($m[2], 'UTF-8');
            return [$street, $hnr];
        }

        // Fallback: keine Nummer → nur schön schreiben
        return [self::titleCaseIt($line), ''];
    }


    /**
     * Parst die typische VIES-Ausgabe:
     *   Zeile 1: "VIA … 43"
     *   Zeile n: "39055 LAIVES .LEIFERS. [BZ]"
     *
     * @return array{
     *   strasse:string,
     *   hausnr:string,
     *   plz:string,
     *   wohnort:string,
     *   provinz:string
     * }
     */
    public static function parseViesAddress(string $address): array
    {
        $address = trim($address);
        $lines   = array_values(array_filter(array_map('trim', preg_split('/\R/u', $address))));

        $streetRaw = $lines[0] ?? '';
        $last      = $lines[count($lines) - 1] ?? '';

        // Letzte Zeile hat häufig das Muster "PLZ ORT [PROV]" (PROV optional)
        $plz = '';
        $cityRaw = '';
        $prov = '';
        if (preg_match('/^(\d{4,6})\s+(.+?)(?:\s+([A-Z]{2}))?$/u', $last, $m)) {
            $plz     = $m[1] ?? '';
            $cityRaw = $m[2] ?? '';
            $prov    = $m[3] ?? '';
        }

        // Straße + Hausnummer
        [$street, $hnr] = self::splitStreetNumber($streetRaw);

        // Ort nur italienisch
        $city = $cityRaw ? self::pickItalianCity($cityRaw) : '';

        return [
            'strasse' => $street,
            'hausnr'  => $hnr,
            'plz'     => $plz,
            'wohnort' => $city,
            'provinz' => $prov,
        ];
    }

    /**
     * Optional: ISO-2 → Anzeigename des Landes (einfaches Mapping für gängige Fälle).
     * Für vollständige Lokalisierung besser über Locale-/Intl-Funktionen abbilden.
     */
    public static function countryName(string $iso2): string
    {
        $iso2 = strtoupper(trim($iso2));
        $map = [
            'IT' => 'Italia',
            'DE' => 'Deutschland',
            'AT' => 'Österreich',
            'FR' => 'France',
            'ES' => 'España',
            'CH' => 'Schweiz',
            'NL' => 'Nederland',
            'BE' => 'Belgique',
            'LU' => 'Luxembourg',
            'PT' => 'Portugal',
            'IE' => 'Ireland',
            'SE' => 'Sverige',
            'DK' => 'Danmark',
            'NO' => 'Norge',
            'FI' => 'Suomi',
            'PL' => 'Polska',
            'CZ' => 'Česko',
            'SK' => 'Slovensko',
            'SI' => 'Slovenija',
            'HU' => 'Magyarország',
            'RO' => 'România',
            'BG' => 'Bulgaria',
            'HR' => 'Hrvatska',
            'GR' => 'Ελλάδα',
        ];
        return $map[$iso2] ?? $iso2;
    }
}
