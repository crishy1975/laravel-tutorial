<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Textvorschlag extends Model
{
    protected $table = 'textvorschlaege';

    protected $fillable = [
        'kategorie',
        'sprache',
        'text',
        'aktiv',
        'sortierung',
    ];

    protected $casts = [
        'aktiv' => 'boolean',
        'sortierung' => 'integer',
    ];

    // =========================================================================
    // KONSTANTEN - Kategorien
    // =========================================================================

    public const KATEGORIEN = [
        'reinigung_bemerkung' => 'Reinigung - Bemerkung',
        'angebot_einleitung' => 'Angebot - Einleitung',
        'angebot_bemerkung_kunde' => 'Angebot - Bemerkung Kunde',
        'angebot_bemerkung_intern' => 'Angebot - Bemerkung Intern',
        'rechnung_bemerkung' => 'Rechnung - Bemerkung',
    ];

    public const SPRACHEN = [
        'de' => 'Deutsch',
        'it' => 'Italiano',
    ];

    // =========================================================================
    // SCOPES
    // =========================================================================

    public function scopeAktiv(Builder $query): Builder
    {
        return $query->where('aktiv', true);
    }

    public function scopeKategorie(Builder $query, string $kategorie): Builder
    {
        return $query->where('kategorie', $kategorie);
    }

    public function scopeSprache(Builder $query, string $sprache): Builder
    {
        return $query->where('sprache', $sprache);
    }

    public function scopeSortiert(Builder $query): Builder
    {
        return $query->orderBy('sortierung')->orderBy('text');
    }

    // =========================================================================
    // STATISCHE HELPER
    // =========================================================================

    /**
     * Holt alle aktiven Vorschläge für eine Kategorie, gruppiert nach Sprache
     */
    public static function fuerKategorie(string $kategorie): array
    {
        $vorschlaege = self::aktiv()
            ->kategorie($kategorie)
            ->sortiert()
            ->get();

        return [
            'de' => $vorschlaege->where('sprache', 'de')->pluck('text')->toArray(),
            'it' => $vorschlaege->where('sprache', 'it')->pluck('text')->toArray(),
        ];
    }

    /**
     * Holt alle aktiven Vorschläge für eine Kategorie als flaches Array
     */
    public static function fuerKategorieFlach(string $kategorie): array
    {
        return self::aktiv()
            ->kategorie($kategorie)
            ->sortiert()
            ->pluck('text')
            ->toArray();
    }

    // =========================================================================
    // ACCESSORS
    // =========================================================================

    public function getKategorieNameAttribute(): string
    {
        return self::KATEGORIEN[$this->kategorie] ?? $this->kategorie;
    }

    public function getSpracheNameAttribute(): string
    {
        return self::SPRACHEN[$this->sprache] ?? $this->sprache;
    }

    public function getSpracheFlagAttribute(): string
    {
        return match($this->sprache) {
            'de' => '🇩🇪',
            'it' => '🇮🇹',
            default => '🌐',
        };
    }
}
