<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Impianto extends Model
{
    protected $table = 'impianti';

    /*
    |--------------------------------------------------------------------------
    | Feld-Zuordnung (CSV-Spalten)
    |--------------------------------------------------------------------------
    |
    | Feld_a  = Anlagen-Kodex (PK)                         CSV: 0
    | Feld_b  = Kaminkehrer-Kodex1                          CSV: 1
    | Feld_c  = Kaminkehrer-Kodex2                          CSV: 2
    |
    | --- Aufstellungsort ---
    | Feld_h  = Gemeinde Aufstellungsort (IT)               CSV: 7
    | Feld_i  = Gemeinde Aufstellungsort (DE)               CSV: 8
    | Feld_J  = Fraktion Aufstellungsort (IT)               CSV: 9
    | Feld_K  = Fraktion Aufstellungsort (DE)               CSV: 10
    | Feld_l  = Straße Aufstellungsort (IT)                 CSV: 11
    | Feld_m  = Straße Aufstellungsort (DE)                 CSV: 12
    | Feld_n  = Hausnummer Aufstellungsort                  CSV: 13
    | Feld_w  = Name Aufstellungsort                        CSV: 22
    |
    | --- Betreiber ---
    | Feld_o  = Name Betreiber                              CSV: 14
    | Feld_p  = Gemeinde Betreiber (IT)                     CSV: 15
    | Feld_q  = Gemeinde Betreiber (DE)                     CSV: 16
    | Feld_r  = Fraktion Betreiber (IT)                     CSV: 17
    | Feld_s  = Fraktion Betreiber (DE)                     CSV: 18
    | Feld_t  = Straße Betreiber (IT)                       CSV: 19
    | Feld_u  = Straße Betreiber (DE)                       CSV: 20
    | Feld_v  = Hausnummer Betreiber                        CSV: 21
    |
    | --- Kessel ---
    | Feld_x  = Status                                      CSV: 23
    | Feld_y  = Hersteller Kessel                           CSV: 24
    | Feld_z  = Baujahr Kessel                              CSV: 25
    | Feld_ab = Leistung kW                                 CSV: 27
    |
    */

    protected $fillable = [
        // Identifikation
        'Feld_a', 'Feld_b', 'Feld_c',
        // Aufstellungsort
        'Feld_h', 'Feld_i', 'Feld_J', 'Feld_K',
        'Feld_l', 'Feld_m', 'Feld_n', 'Feld_w',
        // Betreiber
        'Feld_o', 'Feld_p', 'Feld_q',
        'Feld_r', 'Feld_s',
        'Feld_t', 'Feld_u', 'Feld_v',
        // Kessel
        'Feld_x', 'Feld_y', 'Feld_z', 'Feld_ab',
    ];


    // =========================================================================
    // Beziehungen
    // =========================================================================

    public function messungen(): HasMany
    {
        return $this->hasMany(Messung::class, 'cIM_CODICE', 'Feld_a');
    }

    public function messungenHeuer(): HasMany
    {
        return $this->messungen()
            ->whereRaw("YEAR(STR_TO_DATE(cMIS_DATA, '%d%m%Y')) = YEAR(CURDATE())");
    }

    public function hatMessungHeuer(): bool
    {
        return $this->messungenHeuer()->exists();
    }

    public function letzteMessung()
    {
        return $this->messungen()
            ->orderByRaw("STR_TO_DATE(cMIS_DATA, '%d%m%Y') DESC")
            ->first();
    }


    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopeOhneMessungImJahr($query, int $jahr = null)
    {
        $jahr = $jahr ?? date('Y');

        return $query->whereDoesntHave('messungen', function ($q) use ($jahr) {
            $q->whereRaw("YEAR(STR_TO_DATE(cMIS_DATA, '%d%m%Y')) = ?", [$jahr]);
        });
    }

    public function scopeMitMessungImJahr($query, int $jahr = null)
    {
        $jahr = $jahr ?? date('Y');

        return $query->whereHas('messungen', function ($q) use ($jahr) {
            $q->whereRaw("YEAR(STR_TO_DATE(cMIS_DATA, '%d%m%Y')) = ?", [$jahr]);
        });
    }


    // =========================================================================
    // Accessors – Aufstellungsort
    // =========================================================================

    public function getKodexAttribute(): string
    {
        return $this->Feld_a ?? '';
    }

    public function getNameAufstellungsortAttribute(): string
    {
        return $this->Feld_w ?? '';
    }

    public function getGemeindeAttribute(): string
    {
        return $this->Feld_i ?? $this->Feld_h ?? '';
    }

    public function getFraktionAttribute(): string
    {
        return $this->Feld_K ?? $this->Feld_J ?? '';
    }

    public function getStrasseAttribute(): string
    {
        return trim(($this->Feld_m ?? $this->Feld_l ?? '') . ' ' . ($this->Feld_n ?? ''));
    }


    // =========================================================================
    // Accessors – Betreiber
    // =========================================================================

    public function getBetreiberNameAttribute(): string
    {
        return $this->Feld_o ?? '';
    }

    public function getBetreiberGemeindeAttribute(): string
    {
        return $this->Feld_q ?? $this->Feld_p ?? '';
    }

    public function getBetreiberStrasseAttribute(): string
    {
        return trim(($this->Feld_u ?? $this->Feld_t ?? '') . ' ' . ($this->Feld_v ?? ''));
    }


    // =========================================================================
    // Accessors – Kessel
    // =========================================================================

    public function getHerstellerAttribute(): string
    {
        return $this->Feld_y ?? '';
    }

    public function getBaujahrAttribute(): ?int
    {
        return $this->Feld_z ? (int) $this->Feld_z : null;
    }

    public function getLeistungKwAttribute(): ?int
    {
        return $this->Feld_ab ? (int) $this->Feld_ab : null;
    }
}
