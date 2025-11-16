<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PreisAufschlag extends Model
{
    protected $table = 'preis_aufschlaege';

    protected $fillable = [
        'gebaeude_id',
        'bezeichnung',
        'typ',
        'wert',
        'aktiv',
        'reihenfolge',
    ];

    protected $casts = [
        'wert'        => 'decimal:2',
        'aktiv'       => 'boolean',
        'reihenfolge' => 'integer',
    ];

    public function gebaeude(): BelongsTo
    {
        return $this->belongsTo(Gebaeude::class, 'gebaeude_id');
    }

    public function scopeAktiv($query)
    {
        return $query->where('aktiv', true);
    }

    public function istProzentual(): bool
    {
        return $this->typ === 'prozent';
    }

    public function berechneBetrag(float $basis): float
    {
        if ($this->istProzentual()) {
            return round($basis * ((float) $this->wert / 100), 2);
        }

        return (float) $this->wert;
    }
}
