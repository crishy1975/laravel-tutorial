<?php
/**
 * ════════════════════════════════════════════════════════════════════════════
 * DATEI: FeiertagHelper.php
 * PFAD:  app/Helpers/FeiertagHelper.php
 * ════════════════════════════════════════════════════════════════════════════
 */

namespace App\Helpers;

use Illuminate\Support\Carbon;

class FeiertagHelper
{
    /**
     * Feste italienische Feiertage (Tag.Monat)
     */
    protected static array $festeFeiertage = [
        '01.01', // Capodanno - Neujahr
        '06.01', // Epifania - Heilige Drei Könige
        '25.04', // Festa della Liberazione - Tag der Befreiung
        '01.05', // Festa dei Lavoratori - Tag der Arbeit
        '02.06', // Festa della Repubblica - Tag der Republik
        '15.08', // Ferragosto - Mariä Himmelfahrt
        '01.11', // Tutti i Santi - Allerheiligen
        '08.12', // Immacolata Concezione - Mariä Empfängnis
        '25.12', // Natale - Weihnachten
        '26.12', // Santo Stefano - Stephanstag
    ];

    /**
     * Berechnet das Osterdatum für ein Jahr (Gauss-Algorithmus)
     */
    public static function ostern(int $jahr): Carbon
    {
        $a = $jahr % 19;
        $b = intdiv($jahr, 100);
        $c = $jahr % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $monat = intdiv($h + $l - 7 * $m + 114, 31);
        $tag = (($h + $l - 7 * $m + 114) % 31) + 1;

        return Carbon::create($jahr, $monat, $tag);
    }

    /**
     * Gibt alle Feiertage eines Jahres zurück
     */
    public static function feiertageImJahr(int $jahr): array
    {
        $feiertage = [];

        // Feste Feiertage
        foreach (self::$festeFeiertage as $datum) {
            [$tag, $monat] = explode('.', $datum);
            $feiertage[] = Carbon::create($jahr, (int)$monat, (int)$tag)->format('Y-m-d');
        }

        // Bewegliche Feiertage (Ostern-basiert)
        $ostern = self::ostern($jahr);
        
        // Ostermontag (Lunedì dell'Angelo / Pasquetta)
        $feiertage[] = $ostern->copy()->addDay()->format('Y-m-d');

        return $feiertage;
    }

    /**
     * Prüft ob ein Datum ein Feiertag ist
     */
    public static function istFeiertag(Carbon $datum): bool
    {
        $feiertage = self::feiertageImJahr($datum->year);
        return in_array($datum->format('Y-m-d'), $feiertage);
    }

    /**
     * Prüft ob ein Datum ein Wochenende ist
     */
    public static function istWochenende(Carbon $datum): bool
    {
        return $datum->isWeekend();
    }

    /**
     * Prüft ob ein Datum ein Bankarbeitstag ist
     */
    public static function istBankarbeitstag(Carbon $datum): bool
    {
        return !self::istWochenende($datum) && !self::istFeiertag($datum);
    }

    /**
     * Gibt den nächsten Bankarbeitstag zurück
     */
    public static function naechsterBankarbeitstag(Carbon $datum): Carbon
    {
        $result = $datum->copy();
        
        while (!self::istBankarbeitstag($result)) {
            $result->addDay();
        }

        return $result;
    }

    /**
     * Berechnet das Zahlungsdatum: Rechnungsdatum + Tage, nächster Bankarbeitstag
     */
    public static function berechneZahlungsdatum(Carbon $rechnungsdatum, int $tage = 30): Carbon
    {
        $zahlungsdatum = $rechnungsdatum->copy()->addDays($tage);
        return self::naechsterBankarbeitstag($zahlungsdatum);
    }
}
