<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;
use App\Services\GrenzwertService;

class Messung extends Model
{
    protected $table = 'messungen';

    protected $fillable = [
        'cIM_CODICE',
        'cIM_NAME',
        'cMIS_TIPO',
        'cMIS_STADIO',
        'cMIS_DATA',
        'cMIS_DATA2',
        'cMIS_ORA',
        'strEsito',
        'cMIS_COMBUSTIBILE',
        'cMIS_COMBUSTIBILE_N',
        'cMIS_COMBUSTIBILE_P',
        'cMIS_T_GAS_COMB',
        'cMIS_T_ARIA_COMB',
        'cMIS_T_LIQ_CONV',
        'cMIS_OSSIGENO',
        'cMIS_ANIDRIDE_CARBONICA',
        'cMIS_MONOSSSIDO',
        'cMIS_BIOSSIDO_AZOTO',
        'cMIS_PERD_FUMI',
        'cMIS_IND_OPACITA',
        'cMIS_TRACCE_OLEO',
        'boilerYear',
        'boilerPower',
        'cMIS_CONSUMO',
        'codeInImpianti',
    ];

    /**
     * Brennstoff-Mapping
     */
    public const BRENNSTOFFE = [
        'FUEL_LIGHT_OIL' => ['nr' => 1, 'text' => 'Heizöl/gasolio'],
        'FUEL_HEAVY_OIL' => ['nr' => 1, 'text' => 'Heizöl/gasolio'],
        'FUEL_NAT_GAS'   => ['nr' => 3, 'text' => 'Erdgas/metano'],
        'FUEL_PROPANE'   => ['nr' => 6, 'text' => 'Flüssiggas/gas liquido'],
        'FUEL_BUTANE'    => ['nr' => 6, 'text' => 'Flüssiggas/gas liquido'],
        'FUEL_PELLETS'   => ['nr' => 7, 'text' => 'Pellet'],
        'FUEL_WOOD'      => ['nr' => 7, 'text' => 'Holz/legna'],
    ];

    /**
     * Beziehung: Messung gehört zu einer Anlage
     */
    public function impianto(): BelongsTo
    {
        return $this->belongsTo(Impianto::class, 'cIM_CODICE', 'Feld_a');
    }

    /**
     * Datum als Carbon-Objekt
     */
    public function getDatumAttribute(): ?Carbon
    {
        if (empty($this->cMIS_DATA)) {
            return null;
        }
        return Carbon::createFromFormat('dmY', $this->cMIS_DATA);
    }

    /**
     * Datum formatiert
     */
    public function getDatumFormatiertAttribute(): string
    {
        return $this->cMIS_DATA2 ?? '';
    }

    /**
     * Ergebnis als Boolean
     */
    public function getIstPositivAttribute(): bool
    {
        return $this->strEsito === '1';
    }

    /**
     * Ergebnis als Text
     */
    public function getErgebnisTextAttribute(): string
    {
        return $this->istPositiv ? 'positiv' : 'negativ';
    }

    /**
     * Accessors für Messwerte (als Float)
     */
    public function getO2Attribute(): ?float
    {
        return $this->cMIS_OSSIGENO ? (float) $this->cMIS_OSSIGENO : null;
    }

    public function getCo2Attribute(): ?float
    {
        return $this->cMIS_ANIDRIDE_CARBONICA ? (float) $this->cMIS_ANIDRIDE_CARBONICA : null;
    }

    public function getCoAttribute(): ?int
    {
        return $this->cMIS_MONOSSSIDO ? (int) $this->cMIS_MONOSSSIDO : null;
    }

    public function getNoxAttribute(): ?int
    {
        return $this->cMIS_BIOSSIDO_AZOTO ? (int) $this->cMIS_BIOSSIDO_AZOTO : null;
    }

    public function getAbgastemperaturAttribute(): ?int
    {
        return $this->cMIS_T_GAS_COMB ? (int) $this->cMIS_T_GAS_COMB : null;
    }

    public function getLufttemperaturAttribute(): ?int
    {
        return $this->cMIS_T_ARIA_COMB ? (int) $this->cMIS_T_ARIA_COMB : null;
    }

    public function getAbgasverlustAttribute(): ?float
    {
        return $this->cMIS_PERD_FUMI ? (float) $this->cMIS_PERD_FUMI : null;
    }

    public function getWirkungsgradAttribute(): ?float
    {
        if ($this->abgasverlust === null) {
            return null;
        }
        return 100 - $this->abgasverlust;
    }

    /**
     * Scope: Messungen eines bestimmten Jahres
     */
    public function scopeImJahr($query, int $jahr)
    {
        return $query->whereRaw("YEAR(STR_TO_DATE(cMIS_DATA, '%d%m%Y')) = ?", [$jahr]);
    }

    /**
     * Scope: Messungen des aktuellen Jahres
     */
    public function scopeHeuer($query)
    {
        return $query->imJahr(date('Y'));
    }

    /**
     * Scope: Positive Messungen
     */
    public function scopePositiv($query)
    {
        return $query->where('strEsito', '1');
    }

    /**
     * Scope: Negative Messungen
     */
    public function scopeNegativ($query)
    {
        return $query->where('strEsito', '0');
    }

    /**
     * Setze Brennstoff basierend auf FuelId
     */
    public function setBrennstoffFromFuelId(string $fuelId): void
    {
        $mapping = self::BRENNSTOFFE[$fuelId] ?? ['nr' => 0, 'text' => 'Unbekannt'];
        
        $this->cMIS_COMBUSTIBILE = $fuelId;
        $this->cMIS_COMBUSTIBILE_N = $mapping['nr'];
        $this->cMIS_COMBUSTIBILE_P = $mapping['text'];
    }

    /**
     * Formatiere Wert für DB (wie im Original-PHP)
     */
    public static function formatForDb(string $field, $value): string
    {
        return match ($field) {
            'cIM_CODICE' => sprintf('%06d', (int) $value),
            'cMIS_TIPO' => sprintf('%03d', (int) $value),
            'cMIS_STADIO' => sprintf('%01d', (int) $value),
            'cMIS_T_GAS_COMB' => sprintf('%03d', (int) $value),
            'cMIS_T_ARIA_COMB' => sprintf('%02d', (int) $value),
            'cMIS_T_LIQ_CONV' => sprintf('%03d', (int) $value),
            'cMIS_OSSIGENO', 'cMIS_ANIDRIDE_CARBONICA', 'cMIS_PERD_FUMI' => sprintf('%04.1f', (float) $value),
            'cMIS_MONOSSSIDO', 'cMIS_BIOSSIDO_AZOTO' => sprintf('%04d', (int) $value),
            'cMIS_IND_OPACITA', 'cMIS_TRACCE_OLEO', 'strEsito' => sprintf('%01d', (int) $value),
            'boilerYear' => sprintf('%04d', (int) $value),
            'boilerPower' => sprintf('%04d', (int) $value),
            'cMIS_CONSUMO' => sprintf('%08d', (int) $value),
            default => (string) $value,
        };
    }

    /**
     * Berechnet das Ergebnis (positiv/negativ) basierend auf Grenzwerten
     */
    public function berechneErgebnis(): int
    {
        $baujahr = (int) ($this->boilerYear ?: 2000);
        $leistung = (int) ($this->boilerPower ?: 0);
        $russWert = (int) ($this->cMIS_IND_OPACITA ?: 0);
        // Achtung: In DB ist 0=ja, 1=nein (invertiert!)
        $oelspuren = $this->cMIS_TRACCE_OLEO === '0';

        return GrenzwertService::check(
            $this->cMIS_COMBUSTIBILE ?? 'FUEL_NAT_GAS',
            $baujahr,
            $leistung,
            $this->co ?? 0,
            $this->nox ?? 0,
            $russWert,
            $oelspuren
        );
    }

    /**
     * Setzt das Ergebnis automatisch basierend auf Grenzwerten
     */
    public function setzeErgebnisAutomatisch(): self
    {
        $this->strEsito = (string) $this->berechneErgebnis();
        return $this;
    }

    /**
     * Prüft ob Anlage in impianti existiert und setzt codeInImpianti
     */
    public function pruefeAnlageExistiert(): self
    {
        $count = Impianto::where('Feld_a', $this->cIM_CODICE)->count();
        $this->codeInImpianti = $count;
        return $this;
    }

    /**
     * Gibt detaillierte Grenzwert-Info zurück
     */
    public function getGrenzwertDetails(): array
    {
        $service = new GrenzwertService();
        
        $baujahr = (int) ($this->boilerYear ?: 2000);
        $leistung = (int) ($this->boilerPower ?: 0);
        $russWert = (int) ($this->cMIS_IND_OPACITA ?: 0);
        $oelspuren = $this->cMIS_TRACCE_OLEO === '0';

        $service->pruefeGrenzwerte(
            $this->cMIS_COMBUSTIBILE ?? 'FUEL_NAT_GAS',
            $baujahr,
            $leistung,
            $this->co ?? 0,
            $this->nox ?? 0,
            $russWert,
            $oelspuren
        );

        return $service->getResult();
    }

    /**
     * Hat die Anlage eine gültige Verknüpfung?
     */
    public function getHatAnlageAttribute(): bool
    {
        return $this->codeInImpianti > 0;
    }
}
