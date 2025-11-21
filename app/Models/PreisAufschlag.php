<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Globale Preis-Aufschläge (Inflation)
 * 
 * Pro Jahr gibt es EINEN globalen Standard-Aufschlag.
 * Gebäude können diesen über GebaeudeAufschlag überschreiben.
 */
class PreisAufschlag extends Model
{
    protected $table = 'preis_aufschlaege';

    protected $fillable = [
        'jahr',
        'prozent',
        'beschreibung',
    ];

    protected $casts = [
        'prozent' => 'decimal:2',
        'jahr'    => 'integer',
    ];

    // ═══════════════════════════════════════════════════════════
    // 🔍 STATIC HELPERS
    // ═══════════════════════════════════════════════════════════

    /**
     * Gibt den globalen Aufschlag für ein Jahr zurück.
     * 
     * @param int|null $jahr Jahr (Standard: aktuelles Jahr)
     * @return float Aufschlag in Prozent (z.B. 3.5)
     */
    public static function getGlobalerAufschlag(?int $jahr = null): float
    {
        $jahr = $jahr ?? now()->year;
        
        $aufschlag = self::where('jahr', $jahr)->first();
        
        return $aufschlag ? (float) $aufschlag->prozent : 0.0;
    }

    /**
     * Setzt den globalen Aufschlag für ein Jahr.
     * 
     * @param int $jahr Jahr
     * @param float $prozent Aufschlag in %
     * @param string|null $beschreibung Optionale Beschreibung
     * @return self
     */
    public static function setGlobalerAufschlag(
        int $jahr, 
        float $prozent, 
        ?string $beschreibung = null
    ): self {
        return self::updateOrCreate(
            ['jahr' => $jahr],
            [
                'prozent' => $prozent,
                'beschreibung' => $beschreibung ?? "Preisanpassung $jahr",
            ]
        );
    }

    /**
     * Berechnet den Aufschlagsbetrag auf eine Basis.
     * 
     * @param float $basis Netto-Betrag
     * @return float Aufschlagsbetrag
     */
    public function berechneBetrag(float $basis): float
    {
        return round($basis * ((float) $this->prozent / 100), 2);
    }

    /**
     * Berechnet den Gesamtpreis inklusive Aufschlag.
     * 
     * @param float $basis Original-Preis
     * @return float Preis + Aufschlag
     */
    public function berechneNeuerPreis(float $basis): float
    {
        $aufschlag = $this->berechneBetrag($basis);
        return round($basis + $aufschlag, 2);
    }

    // ═══════════════════════════════════════════════════════════
    // 🏷️ ACCESSORS
    // ═══════════════════════════════════════════════════════════

    /**
     * Formatierter Aufschlag (z.B. "3,5 %")
     */
    public function getProzentFormatiertAttribute(): string
    {
        return number_format($this->prozent, 2, ',', '') . ' %';
    }

    /**
     * Kurzbezeichnung für UI
     */
    public function getBezeichnungAttribute(): string
    {
        return $this->beschreibung ?? "Aufschlag {$this->jahr} ({$this->prozent_formatiert})";
    }
}