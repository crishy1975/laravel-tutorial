<?php

namespace App\Services;

/**
 * Service zur Prüfung der Emissionsgrenzwerte
 * 
 * Basierend auf Südtiroler Verordnung für Heizanlagen
 * Grenzwerte abhängig von: Brennstoff, Baujahr, Leistung
 */
class GrenzwertService
{
    /**
     * Grenzwert-Konfiguration nach Brennstoff
     */
    private const GRENZWERTE = [
        'FUEL_LIGHT_OIL' => [
            'maxCo' => 385,
            'maxNoX' => 275,
            'maxSoot' => 1,
            'checkSoot' => true,
            'checkOilDeriv' => true,
            'checkNoX' => true,
        ],
        'FUEL_HEAVY_OIL' => [
            'maxCo' => 385,
            'maxNoX' => 275,
            'maxSoot' => 1,
            'checkSoot' => true,
            'checkOilDeriv' => true,
            'checkNoX' => true,
        ],
        'FUEL_NAT_GAS' => [
            'maxCo' => 385,
            'maxNoX' => 275, // 120 wenn Baujahr >= 2018 UND kW > 1000
            'checkSoot' => false,
            'checkOilDeriv' => false,
            'checkNoX' => true,
        ],
        'FUEL_PROPANE' => [
            'maxCo' => 385,
            'maxNoX' => 275, // 120 wenn Baujahr >= 2018 UND kW > 1000
            'checkSoot' => false,
            'checkOilDeriv' => false,
            'checkNoX' => true,
        ],
        'FUEL_BUTANE' => [
            'maxCo' => 385,
            'maxNoX' => 275, // 120 wenn Baujahr >= 2018 UND kW > 1000
            'checkSoot' => false,
            'checkOilDeriv' => false,
            'checkNoX' => true,
        ],
        'FUEL_PELLETS' => [
            'checkSoot' => false,
            'checkOilDeriv' => false,
            'checkNoX' => false,
            // CO abhängig von kW
        ],
        'FUEL_WOOD' => [
            'checkSoot' => false,
            'checkOilDeriv' => false,
            'checkNoX' => false,
            // CO abhängig von kW
        ],
    ];

    /**
     * Ergebnis der Grenzwertprüfung
     */
    public array $result = [
        'positiv' => true,
        'maxCo' => 0,
        'maxNoX' => 0,
        'maxSoot' => 1,
        'coUeberschritten' => false,
        'noxUeberschritten' => false,
        'russUeberschritten' => false,
        'oelspurenUeberschritten' => false,
    ];

    /**
     * Prüft ob Messwerte innerhalb der Grenzwerte liegen
     * 
     * @param string $brennstoff  FUEL_xxx Code
     * @param int    $baujahr     Baujahr des Kessels
     * @param int    $kw          Leistung in kW
     * @param int    $coWert      CO-Messwert in mg/m³
     * @param int    $noxWert     NOx-Messwert in mg/m³
     * @param int    $russWert    Rußzahl (0-9)
     * @param bool   $oelspuren   Ölspuren vorhanden
     * 
     * @return int 1 = positiv, 0 = negativ
     */
    public function pruefeGrenzwerte(
        string $brennstoff,
        int $baujahr,
        int $kw,
        int $coWert,
        int $noxWert,
        int $russWert = 0,
        bool $oelspuren = false
    ): int {
        // Reset
        $this->result = [
            'positiv' => true,
            'maxCo' => 0,
            'maxNoX' => 0,
            'maxSoot' => 1,
            'coUeberschritten' => false,
            'noxUeberschritten' => false,
            'russUeberschritten' => false,
            'oelspurenUeberschritten' => false,
        ];

        // Grenzwerte ermitteln
        $grenzwerte = $this->ermittleGrenzwerte($brennstoff, $baujahr, $kw);
        
        $this->result['maxCo'] = $grenzwerte['maxCo'];
        $this->result['maxNoX'] = $grenzwerte['maxNoX'];
        $this->result['maxSoot'] = $grenzwerte['maxSoot'];

        // CO prüfen
        if ($coWert > $grenzwerte['maxCo']) {
            $this->result['coUeberschritten'] = true;
            $this->result['positiv'] = false;
        }

        // NOx prüfen (wenn erforderlich)
        if ($grenzwerte['checkNoX'] && $noxWert > $grenzwerte['maxNoX']) {
            $this->result['noxUeberschritten'] = true;
            $this->result['positiv'] = false;
        }

        // Rußzahl prüfen (wenn erforderlich - nur bei Öl)
        if ($grenzwerte['checkSoot'] && $russWert > $grenzwerte['maxSoot']) {
            $this->result['russUeberschritten'] = true;
            $this->result['positiv'] = false;
        }

        // Ölspuren prüfen (wenn erforderlich - nur bei Öl)
        if ($grenzwerte['checkOilDeriv'] && $oelspuren) {
            $this->result['oelspurenUeberschritten'] = true;
            $this->result['positiv'] = false;
        }

        return $this->result['positiv'] ? 1 : 0;
    }

    /**
     * Ermittelt die gültigen Grenzwerte basierend auf Brennstoff, Baujahr und Leistung
     */
    private function ermittleGrenzwerte(string $brennstoff, int $baujahr, int $kw): array
    {
        $defaults = [
            'maxCo' => 385,
            'maxNoX' => 275,
            'maxSoot' => 1,
            'checkSoot' => false,
            'checkOilDeriv' => false,
            'checkNoX' => true,
        ];

        // Basis-Grenzwerte aus Konfiguration
        $config = self::GRENZWERTE[$brennstoff] ?? $defaults;

        $result = array_merge($defaults, $config);

        // Sonderfälle für Gas-Brennstoffe (NOx-Grenzwert)
        if (in_array($brennstoff, ['FUEL_NAT_GAS', 'FUEL_PROPANE', 'FUEL_BUTANE'])) {
            // Strengerer NOx-Grenzwert für neuere, große Anlagen
            if ($baujahr >= 2018 && $kw > 1000) {
                $result['maxNoX'] = 120;
            }
        }

        // Sonderfälle für Biomasse (CO abhängig von Leistung)
        if (in_array($brennstoff, ['FUEL_PELLETS', 'FUEL_WOOD'])) {
            $result['checkNoX'] = false;
            
            if ($kw <= 150) {
                $result['maxCo'] = 1000;
            } elseif ($kw <= 500) {
                $result['maxCo'] = 350;
            } elseif ($kw <= 1000) {
                $result['maxCo'] = 250;
            } else {
                $result['maxCo'] = 250; // Default für > 1000 kW
            }
        }

        return $result;
    }

    /**
     * Gibt das detaillierte Prüfergebnis zurück
     */
    public function getResult(): array
    {
        return $this->result;
    }

    /**
     * Statische Hilfsmethode für schnelle Prüfung
     */
    public static function check(
        string $brennstoff,
        int $baujahr,
        int $kw,
        int $coWert,
        int $noxWert,
        int $russWert = 0,
        bool $oelspuren = false
    ): int {
        $service = new self();
        return $service->pruefeGrenzwerte($brennstoff, $baujahr, $kw, $coWert, $noxWert, $russWert, $oelspuren);
    }

    /**
     * Gibt die Grenzwerte für einen Brennstoff zurück (für Anzeige im UI)
     */
    public static function getGrenzwerteInfo(string $brennstoff, int $baujahr = 2020, int $kw = 100): array
    {
        $service = new self();
        return $service->ermittleGrenzwerte($brennstoff, $baujahr, $kw);
    }
}
