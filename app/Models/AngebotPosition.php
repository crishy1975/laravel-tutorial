<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AngebotPosition extends Model
{
    protected $table = 'angebot_positionen';

    protected $fillable = [
        'angebot_id',
        'artikel_gebaeude_id',
        'position',
        'beschreibung',
        'anzahl',
        'einheit',
        'einzelpreis',
        'gesamtpreis',
    ];

    protected $casts = [
        'anzahl'      => 'decimal:2',
        'einzelpreis' => 'decimal:2',
        'gesamtpreis' => 'decimal:2',
        'position'    => 'integer',
    ];

    // ═══════════════════════════════════════════════════════════════════════
    // 🔗 RELATIONSHIPS
    // ═══════════════════════════════════════════════════════════════════════

    public function angebot(): BelongsTo
    {
        return $this->belongsTo(Angebot::class);
    }

    public function artikelGebaeude(): BelongsTo
    {
        return $this->belongsTo(ArtikelGebaeude::class, 'artikel_gebaeude_id');
    }

    // ═══════════════════════════════════════════════════════════════════════
    // 🏷️ ACCESSORS
    // ═══════════════════════════════════════════════════════════════════════

    public function getEinzelpreisFormatiertAttribute(): string
    {
        return number_format($this->einzelpreis, 2, ',', '.') . ' €';
    }

    public function getGesamtpreisFormatiertAttribute(): string
    {
        return number_format($this->gesamtpreis, 2, ',', '.') . ' €';
    }

    // ═══════════════════════════════════════════════════════════════════════
    // 🧮 BERECHNUNGEN
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Berechnet Gesamtpreis und speichert
     */
    public function berechneGesamtpreis(): void
    {
        $this->gesamtpreis = round($this->anzahl * $this->einzelpreis, 2);
        $this->save();
    }

    /**
     * Boot: Automatisch Gesamtpreis berechnen
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($position) {
            $position->gesamtpreis = round($position->anzahl * $position->einzelpreis, 2);
        });

        // Nach Speichern: Angebot-Summen aktualisieren
        static::saved(function ($position) {
            $position->angebot?->berechneBetraege();
        });

        static::deleted(function ($position) {
            $position->angebot?->berechneBetraege();
        });
    }
}
