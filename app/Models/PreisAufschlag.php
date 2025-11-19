<?php

namespace App\Models;

use App\Models\Gebaeude;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PreisAufschlag - Jährlicher Inflationsaufschlag
 * 
 * Business-Logik:
 * - Global: Standard-Aufschlag für alle Gebäude (ist_global = 1, gebaeude_id = NULL)
 * - Gebäudespezifisch: Individueller Aufschlag (ist_global = 0, gebaeude_id = X)
 * - Kein Aufschlag: Einfach kein Eintrag für das Gebäude
 */
class PreisAufschlag extends Model
{
    protected $table = 'preis_aufschlaege';

    protected $fillable = [
        'jahr',
        'aufschlag_prozent',
        'ist_global',
        'gebaeude_id',
        'bemerkung',
    ];

    protected $casts = [
        'aufschlag_prozent' => 'decimal:2',
        'ist_global'        => 'boolean',
        'jahr'              => 'integer',
    ];

    /**
     * Zugehöriges Gebäude (falls gebäudespezifisch).
     */
    public function gebaeude(): BelongsTo
    {
        return $this->belongsTo(Gebaeude::class, 'gebaeude_id');
    }

    // ═══════════════════════════════════════════════════════════
    // 🔍 SCOPES
    // ═══════════════════════════════════════════════════════════

    /**
     * Nur globale Aufschläge.
     */
    public function scopeGlobal($query)
    {
        return $query->where('ist_global', true)
                     ->whereNull('gebaeude_id');
    }

    /**
     * Nur gebäudespezifische Aufschläge.
     */
    public function scopeFuerGebaeude($query, int $gebaeudeId)
    {
        return $query->where('ist_global', false)
                     ->where('gebaeude_id', $gebaeudeId);
    }

    /**
     * Aufschläge für ein bestimmtes Jahr.
     */
    public function scopeJahr($query, int $jahr)
    {
        return $query->where('jahr', $jahr);
    }

    // ═══════════════════════════════════════════════════════════
    // 🧮 BUSINESS LOGIC
    // ═══════════════════════════════════════════════════════════

    /**
     * Ermittelt den Aufschlag für ein Gebäude in einem Jahr.
     * 
     * Priorisierung:
     * 1. Gebäudespezifischer Aufschlag (falls vorhanden)
     * 2. Globaler Aufschlag (Fallback)
     * 3. 0% (kein Aufschlag)
     */
    public static function getAufschlagFuerGebaeude(int $gebaeudeId, ?int $jahr = null): float
    {
        $jahr = $jahr ?? now()->year;

        // 1. Prüfe gebäudespezifischen Aufschlag
        $gebaeudespezifisch = self::fuerGebaeude($gebaeudeId)
            ->jahr($jahr)
            ->first();

        if ($gebaeudespezifisch) {
            return (float) $gebaeudespezifisch->aufschlag_prozent;
        }

        // 2. Fallback: Globaler Aufschlag
        $global = self::global()
            ->jahr($jahr)
            ->first();

        if ($global) {
            return (float) $global->aufschlag_prozent;
        }

        // 3. Kein Aufschlag
        return 0.0;
    }

    /**
     * Berechnet den Aufschlagsbetrag auf eine Basis.
     */
    public function berechneBetrag(float $basis): float
    {
        return round($basis * ((float) $this->aufschlag_prozent / 100), 2);
    }

    /**
     * Erstellt oder aktualisiert globalen Aufschlag für ein Jahr.
     */
    public static function setGlobalerAufschlag(int $jahr, float $prozent, ?string $bemerkung = null): self
    {
        return self::updateOrCreate(
            [
                'jahr' => $jahr,
                'ist_global' => true,
                'gebaeude_id' => null,
            ],
            [
                'aufschlag_prozent' => $prozent,
                'bemerkung' => $bemerkung ?? "Globaler Aufschlag {$jahr}",
            ]
        );
    }

    /**
     * Erstellt oder aktualisiert gebäudespezifischen Aufschlag.
     */
    public static function setGebaeudeAufschlag(int $gebaeudeId, int $jahr, float $prozent, ?string $bemerkung = null): self
    {
        return self::updateOrCreate(
            [
                'jahr' => $jahr,
                'ist_global' => false,
                'gebaeude_id' => $gebaeudeId,
            ],
            [
                'aufschlag_prozent' => $prozent,
                'bemerkung' => $bemerkung,
            ]
        );
    }

    // ═══════════════════════════════════════════════════════════
    // 🏷️ ACCESSORS (für Kompatibilität mit Rechnung-Code)
    // ═══════════════════════════════════════════════════════════

    /**
     * Alias für aufschlag_prozent (für Kompatibilität).
     */
    public function getWertAttribute(): float
    {
        return (float) $this->aufschlag_prozent;
    }

    /**
     * Alias für bemerkung (für Kompatibilität).
     */
    public function getBezeichnungAttribute(): string
    {
        if ($this->ist_global) {
            return "Preisanpassung {$this->jahr} ({$this->aufschlag_prozent}%)";
        }
        
        return $this->bemerkung ?? "Individueller Aufschlag {$this->jahr}";
    }

    /**
     * Immer prozentual (nie fix).
     */
    public function istProzentual(): bool
    {
        return true;
    }
}