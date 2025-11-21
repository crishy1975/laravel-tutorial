<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Gebäude-spezifische Aufschlag-Überschreibungen
 * 
 * Wenn ein Gebäude einen anderen Aufschlag als den globalen haben soll.
 * Kein Eintrag = globaler Aufschlag wird verwendet.
 */
class GebaeudeAufschlag extends Model
{
    protected $table = 'gebaeude_aufschlaege';

    protected $fillable = [
        'gebaeude_id',
        'prozent',
        'grund',
        'gueltig_ab',
        'gueltig_bis',
    ];

    protected $casts = [
        'prozent'     => 'decimal:2',
        'gueltig_ab'  => 'date',
        'gueltig_bis' => 'date',
    ];

    // ═══════════════════════════════════════════════════════════
    // 🔗 RELATIONSHIPS
    // ═══════════════════════════════════════════════════════════

    public function gebaeude(): BelongsTo
    {
        return $this->belongsTo(Gebaeude::class);
    }

    // ═══════════════════════════════════════════════════════════
    // 🎯 SCOPES
    // ═══════════════════════════════════════════════════════════

    /**
     * Nur aktuell gültige Aufschläge
     */
    public function scopeGueltig($query, ?Carbon $datum = null)
    {
        $datum = $datum ?? now();

        return $query->where(function ($q) use ($datum) {
            $q->where('gueltig_ab', '<=', $datum)
              ->where(function ($q2) use ($datum) {
                  $q2->whereNull('gueltig_bis')
                     ->orWhere('gueltig_bis', '>=', $datum);
              });
        });
    }

    /**
     * Für ein bestimmtes Gebäude
     */
    public function scopeFuerGebaeude($query, int $gebaeudeId)
    {
        return $query->where('gebaeude_id', $gebaeudeId);
    }

    // ═══════════════════════════════════════════════════════════
    // 🧮 BUSINESS LOGIC
    // ═══════════════════════════════════════════════════════════

    /**
     * Ist dieser Aufschlag aktuell gültig?
     */
    public function istGueltig(?Carbon $datum = null): bool
    {
        $datum = $datum ?? now();

        // gueltig_ab muss gesetzt und in der Vergangenheit sein
        if (!$this->gueltig_ab || $this->gueltig_ab->greaterThan($datum)) {
            return false;
        }

        // gueltig_bis ist entweder NULL (unbegrenzt) oder in der Zukunft
        return !$this->gueltig_bis || $this->gueltig_bis->greaterThanOrEqualTo($datum);
    }

    /**
     * Berechnet den Aufschlagsbetrag auf eine Basis.
     */
    public function berechneBetrag(float $basis): float
    {
        return round($basis * ((float) $this->prozent / 100), 2);
    }

    /**
     * Berechnet den neuen Preis inklusive Aufschlag.
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
     * Formatierter Aufschlag
     */
    public function getProzentFormatiertAttribute(): string
    {
        return number_format($this->prozent, 2, ',', '') . ' %';
    }

    /**
     * Gültigkeitsbereich als Text
     */
    public function getGueltigkeitAttribute(): string
    {
        $von = $this->gueltig_ab ? $this->gueltig_ab->format('d.m.Y') : '?';
        $bis = $this->gueltig_bis ? $this->gueltig_bis->format('d.m.Y') : 'unbegrenzt';

        return "Gültig von {$von} bis {$bis}";
    }

    /**
     * Status-Badge für UI
     */
    public function getStatusBadgeAttribute(): string
    {
        if ($this->istGueltig()) {
            return '<span class="badge bg-success">Aktiv</span>';
        }

        if ($this->gueltig_ab && $this->gueltig_ab->isFuture()) {
            return '<span class="badge bg-info">Geplant</span>';
        }

        return '<span class="badge bg-secondary">Abgelaufen</span>';
    }
}