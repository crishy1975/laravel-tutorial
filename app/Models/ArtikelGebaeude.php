<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArtikelGebaeude extends Model
{
    protected $table = 'artikel_gebaeude';

    protected $fillable = [
        'gebaeude_id',
        'beschreibung',
        'anzahl',
        'einzelpreis',
        'aktiv',
        'basis_preis',
        'reihenfolge',
    ];

    protected $casts = [
        'anzahl'      => 'decimal:2',
        'einzelpreis' => 'decimal:2',
        'aktiv'       => 'boolean',
        'reihenfolge' => 'integer',
    ];

    protected $appends = ['gesamtpreis'];

    public function gebaeude()
    {
        return $this->belongsTo(Gebaeude::class, 'gebaeude_id');
    }

    public function getGesamtpreisAttribute(): string
    {
        $anzahl      = (float) ($this->attributes['anzahl'] ?? 0);
        $einzelpreis = (float) ($this->attributes['einzelpreis'] ?? 0);
        return number_format($anzahl * $einzelpreis, 2, '.', '');
    }

    /** Nur aktive Positionen (für Rechnung etc.) */
    public function scopeAktiv($q)
    {
        return $q->where('aktiv', true);
    }

    /**
     * Rechnungspositionen, die aus diesem Artikel erstellt wurden
     * (für Nachvollziehbarkeit: "Diese Position stammt von Artikel #123")
     */
    public function rechnungPositionen(): HasMany
    {
        return $this->hasMany(RechnungPosition::class, 'artikel_gebaeude_id');
    }

    /**
     * Wurde dieser Artikel bereits abgerechnet?
     */
    public function istAbgerechnet(): bool
    {
        return $this->rechnungPositionen()
            ->whereHas('rechnung', fn($q) => $q->whereIn('status', ['sent', 'paid']))
            ->exists();
    }
}
