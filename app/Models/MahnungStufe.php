<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class MahnungStufe extends Model
{
    protected $table = 'mahnung_stufen';

    protected $fillable = [
        'stufe',
        'name_de',
        'name_it',
        'tage_ueberfaellig',
        'spesen',
        'text_de',
        'text_it',
        'betreff_de',
        'betreff_it',
        'aktiv',
    ];

    protected $casts = [
        'stufe'             => 'integer',
        'tage_ueberfaellig' => 'integer',
        'spesen'            => 'decimal:2',
        'aktiv'             => 'boolean',
    ];

    // ═══════════════════════════════════════════════════════════════════════
    // RELATIONSHIPS
    // ═══════════════════════════════════════════════════════════════════════

    public function mahnungen(): HasMany
    {
        return $this->hasMany(Mahnung::class, 'mahnung_stufe_id');
    }

    // ═══════════════════════════════════════════════════════════════════════
    // SCOPES
    // ═══════════════════════════════════════════════════════════════════════

    public function scopeAktiv($query)
    {
        return $query->where('aktiv', true);
    }

    public function scopeOrderByStufe($query)
    {
        return $query->orderBy('stufe');
    }

    // ═══════════════════════════════════════════════════════════════════════
    // STATIC HELPERS
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Holt alle aktiven Stufen (cached)
     */
    public static function getAlleAktiven(): \Illuminate\Database\Eloquent\Collection
    {
        return Cache::remember('mahnung_stufen_aktiv', 3600, function () {
            return self::aktiv()->orderByStufe()->get();
        });
    }

    /**
     * Findet die passende Mahnstufe basierend auf Tagen überfällig
     */
    public static function findPassendeStufe(int $tageUeberfaellig): ?self
    {
        $stufen = self::getAlleAktiven();
        
        // Von höchster zu niedrigster Stufe
        $passend = null;
        foreach ($stufen as $stufe) {
            if ($tageUeberfaellig >= $stufe->tage_ueberfaellig) {
                $passend = $stufe;
            }
        }
        
        return $passend;
    }

    /**
     * Holt eine Stufe nach Nummer
     */
    public static function getByStufe(int $stufeNr): ?self
    {
        return self::where('stufe', $stufeNr)->first();
    }

    /**
     * Cache leeren
     */
    protected static function booted(): void
    {
        static::saved(fn() => Cache::forget('mahnung_stufen_aktiv'));
        static::deleted(fn() => Cache::forget('mahnung_stufen_aktiv'));
    }

    // ═══════════════════════════════════════════════════════════════════════
    // ACCESSORS
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Name basierend auf Sprache
     */
    public function getName(string $sprache = 'de'): string
    {
        return $sprache === 'it' ? $this->name_it : $this->name_de;
    }

    /**
     * Text basierend auf Sprache
     */
    public function getText(string $sprache = 'de'): string
    {
        return $sprache === 'it' ? $this->text_it : $this->text_de;
    }

    /**
     * Betreff basierend auf Sprache
     */
    public function getBetreff(string $sprache = 'de'): string
    {
        return $sprache === 'it' ? $this->betreff_it : $this->betreff_de;
    }

    /**
     * Formatierte Spesen
     */
    public function getSpesenFormatiertAttribute(): string
    {
        return number_format($this->spesen, 2, ',', '.') . ' €';
    }

    /**
     * Badge-Klasse für UI
     */
    public function getBadgeClassAttribute(): string
    {
        return match ((int) $this->stufe) {
            0 => 'bg-info',
            1 => 'bg-warning text-dark',
            2 => 'bg-orange',
            3 => 'bg-danger',
            default => 'bg-secondary',
        };
    }

    /**
     * Icon für UI
     */
    public function getIconAttribute(): string
    {
        return match ((int) $this->stufe) {
            0 => 'bi-bell',
            1 => 'bi-exclamation-circle',
            2 => 'bi-exclamation-triangle',
            3 => 'bi-exclamation-octagon',
            default => 'bi-envelope',
        };
    }
}
