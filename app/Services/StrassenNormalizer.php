<?php

namespace App\Services;

use Illuminate\Support\Str;

/**
 * Normalisiert Straßennamen für korrekte Sortierung
 * in zweisprachiger Umgebung (Deutsch/Italienisch).
 *
 * Beispiel: "Via J.F. Kennedy", "Kennedystr.", "J. Kennedy Str."
 *           → alle bekommen denselben sort_key "KENNEDY"
 */
class StrassenNormalizer
{
    // Italienische Präfixe (werden am Anfang entfernt)
    private const IT_PREFIXES = [
        'via', 'viale', 'v.le', 'piazza', 'p.zza', 'piazzale', 'piazzetta',
        'corso', 'c.so', 'vicolo', 'largo', 'lungadige', 'passaggio',
        'strada', 'sentiero', 'loc.', 'località', 'localita', 'zona',
        'fraz.', 'frazione',
    ];

    // Deutsche Suffixe (werden am Ende entfernt)
    private const DE_SUFFIXES = [
        'straße', 'strasse', 'str.', 'str',
        'gasse', 'g.',
        'weg',
        'platz', 'pl.',
        'ring',
        'allee',
        'ufer',
        'steig',
        'rain',
        'pfad',
    ];

    // Abkürzungen für Vornamen (werden entfernt)
    // NUR "J. ", "F. ", "Dr. ", "St. " etc. – Punkt UND Leerzeichen Pflicht!
    private const NAME_ABBREVIATIONS = [
        '/^[a-zäöü]\.\s+/iu',          // Einzelbuchstabe + Punkt + Space: "J. ", "F. "
        '/^[a-zäöü]{2,3}\.\s+/iu',     // 2-3 Buchstaben + Punkt + Space: "Dr. ", "St. ", "Naz. "
    ];

    /**
     * Normalisiert einen Straßennamen zu einem Sort-Key.
     *
     * "Via J.F. Kennedy"  → "KENNEDY"
     * "Kennedystr."       → "KENNEDY"
     * "Galvanistraße"     → "GALVANI"
     * "Piazza Walther"    → "WALTHER"
     * "Waltherplatz"      → "WALTHER"
     */
    public function normalize(?string $strasse): string
    {
        if (empty($strasse)) {
            return '';
        }

        $s = trim($strasse);

        // 1. Italienische Präfixe entfernen
        foreach (self::IT_PREFIXES as $prefix) {
            $pattern = '/^' . preg_quote($prefix, '/') . '\s+/iu';
            $s = preg_replace($pattern, '', $s);
        }

        // 2. Deutsche Suffixe entfernen
        foreach (self::DE_SUFFIXES as $suffix) {
            $pattern = '/' . preg_quote($suffix, '/') . '$/iu';
            $s = preg_replace($pattern, '', $s);
        }

        $s = trim($s);

        // 3. Namens-Abkürzungen am Anfang entfernen ("J.F. ", "J. ", "Dr. ")
        // Mehrfach durchlaufen für "J. F. Kennedy" → "F. Kennedy" → "Kennedy"
        for ($i = 0; $i < 3; $i++) {
            foreach (self::NAME_ABBREVIATIONS as $pattern) {
                $s = preg_replace($pattern, '', $s);
            }
            $s = trim($s);
        }

        // 4. Punkte, Bindestriche und Mehrfach-Leerzeichen bereinigen
        $s = str_replace(['.', '-', '_', ','], ' ', $s);
        $s = preg_replace('/\s+/', ' ', $s);
        $s = trim($s);

        // 5. Uppercase
        $s = mb_strtoupper($s, 'UTF-8');

        // 6. Umlaute normalisieren
        $s = str_replace(
            ['Ä', 'Ö', 'Ü', 'ß'],
            ['AE', 'OE', 'UE', 'SS'],
            $s
        );

        return $s ?: mb_strtoupper(trim($strasse), 'UTF-8');
    }

    /**
     * Normalisiert eine Hausnummer für korrekte numerische Sortierung.
     *
     * "2"    → "00002"
     * "10"   → "00010"
     * "12/A" → "00012/A"
     * "s.n." → "99999"  (ohne Nummer = ans Ende)
     */
    public function normalizeHausnummer(?string $hausnummer): string
    {
        if (empty($hausnummer)) {
            return '99999';
        }

        $h = trim($hausnummer);

        // "s.n.", "s.n.c.", "snc" → ans Ende
        if (preg_match('/^s\.?n\.?c?\.?$/i', $h)) {
            return '99999';
        }

        // Zahl am Anfang extrahieren + Rest (Buchstabe, /A, -B etc.)
        if (preg_match('/^(\d+)(.*)$/', $h, $matches)) {
            $nummer = str_pad($matches[1], 5, '0', STR_PAD_LEFT);
            $suffix = mb_strtoupper(trim($matches[2]), 'UTF-8');
            return $nummer . $suffix;
        }

        return mb_strtoupper($h, 'UTF-8');
    }

    /**
     * Extrahiert den Buchstaben-Prefix aus dem Codex.
     * "hof12" → "HOF", "ken34" → "KEN", "gal12" → "GAL"
     */
    public function extractCodexPrefix(?string $codex): string
    {
        if (empty($codex)) {
            return '';
        }

        // Alles vor der ersten Ziffer = Straßen-Prefix
        if (preg_match('/^([a-zäöü]+)/iu', trim($codex), $matches)) {
            return mb_strtoupper($matches[1], 'UTF-8');
        }

        return '';
    }

    /**
     * Extrahiert den numerischen Teil aus dem Codex.
     * "hof12" → "00012", "ken34" → "00034"
     */
    public function extractCodexNummer(?string $codex): string
    {
        if (empty($codex)) {
            return '99999';
        }

        if (preg_match('/(\d+)/', $codex, $matches)) {
            return str_pad($matches[1], 5, '0', STR_PAD_LEFT);
        }

        return '99999';
    }

    /**
     * Resolve: Codex-Prefix hat Vorrang, dann manuelles Mapping, dann Auto-Normalisierung.
     */
    public function resolve(string $strasse, ?string $codex = null): string
    {
        // 1. Codex-Prefix (zuverlässigster Identifier)
        $codexPrefix = $this->extractCodexPrefix($codex);
        if ($codexPrefix) {
            return $codexPrefix;
        }

        // 2. Manuelles Mapping
        $mapping = \App\Models\StrassenMapping::where('strasse_original', $strasse)->first();
        if ($mapping) {
            return $mapping->sort_key;
        }

        // 3. Automatische Normalisierung
        return $this->normalize($strasse);
    }

    /**
     * Analyse: Zeigt Details der Normalisierung.
     */
    public function analyze(string $strasse, ?string $codex = null): array
    {
        return [
            'original' => $strasse,
            'codex' => $codex,
            'codex_prefix' => $this->extractCodexPrefix($codex),
            'normalized' => $this->normalize($strasse),
            'resolved' => $this->resolve($strasse, $codex),
            'has_mapping' => \App\Models\StrassenMapping::where('strasse_original', $strasse)->exists(),
        ];
    }
}
