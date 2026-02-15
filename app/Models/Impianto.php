<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Impianto extends Model
{
    protected $table = 'impianti';

    protected $fillable = [
        'Feld_a', 'Feld_b', 'Feld_c',
        'Feld_h', 'Feld_i', 'Feld_j', 'Feld_k',
        'Feld_l', 'Feld_m', 'Feld_n',
        'Feld_o', 'Feld_p', 'Feld_q', 'Feld_r', 'Feld_s', 'Feld_t',
        'Feld_u', 'Feld_v', 'Feld_w',
        'Feld_x', 'Feld_y', 'Feld_z', 'Feld_ab',
    ];

    /**
     * Beziehung: Eine Anlage hat viele Messungen
     */
    public function messungen(): HasMany
    {
        return $this->hasMany(Messung::class, 'cIM_CODICE', 'Feld_a');
    }

    /**
     * Messungen des aktuellen Jahres
     */
    public function messungenHeuer(): HasMany
    {
        return $this->messungen()
            ->whereRaw("YEAR(STR_TO_DATE(cMIS_DATA, '%d%m%Y')) = YEAR(CURDATE())");
    }

    /**
     * Hat die Anlage eine Messung im aktuellen Jahr?
     */
    public function hatMessungHeuer(): bool
    {
        return $this->messungenHeuer()->exists();
    }

    /**
     * Letzte Messung (egal welches Jahr)
     */
    public function letzteMessung()
    {
        return $this->messungen()
            ->orderByRaw("STR_TO_DATE(cMIS_DATA, '%d%m%Y') DESC")
            ->first();
    }

    /**
     * Scope: Anlagen ohne Messung im Jahr
     */
    public function scopeOhneMessungImJahr($query, int $jahr = null)
    {
        $jahr = $jahr ?? date('Y');
        
        return $query->whereDoesntHave('messungen', function ($q) use ($jahr) {
            $q->whereRaw("YEAR(STR_TO_DATE(cMIS_DATA, '%d%m%Y')) = ?", [$jahr]);
        });
    }

    /**
     * Scope: Anlagen mit Messung im Jahr
     */
    public function scopeMitMessungImJahr($query, int $jahr = null)
    {
        $jahr = $jahr ?? date('Y');
        
        return $query->whereHas('messungen', function ($q) use ($jahr) {
            $q->whereRaw("YEAR(STR_TO_DATE(cMIS_DATA, '%d%m%Y')) = ?", [$jahr]);
        });
    }

    /**
     * Accessors für lesbare Feldnamen
     */
    public function getKodexAttribute(): string
    {
        return $this->Feld_a ?? '';
    }

    public function getBeschreibungAttribute(): string
    {
        return $this->Feld_w ?? '';
    }

    public function getOrtAttribute(): string
    {
        return $this->Feld_i ?? $this->Feld_h ?? '';
    }

    public function getStrasseAttribute(): string
    {
        return trim(($this->Feld_k ?? $this->Feld_j ?? '') . ' ' . ($this->Feld_n ?? ''));
    }

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
