<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Textvorschlag extends Model
{
    protected $table = 'textvorschlaege';

    protected $fillable = [
        'kategorie',
        'titel',
        'text',
        'aktiv',
    ];

    protected $casts = [
        'aktiv' => 'boolean',
    ];

    // =========================================================================
    // KONSTANTEN - Kategorien
    // =========================================================================

    public const KATEGORIEN = [
        'reinigung_nachricht' => 'Reinigung - SMS/WhatsApp',
        'reinigung_bemerkung' => 'Reinigung - Bemerkung',
        'angebot_einleitung' => 'Angebot - Einleitung',
        'angebot_bemerkung_kunde' => 'Angebot - Bemerkung Kunde',
        'angebot_bemerkung_intern' => 'Angebot - Bemerkung Intern',
        'rechnung_bemerkung' => 'Rechnung - Bemerkung',
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

    // =========================================================================
    // STATISCHE HELPER
    // =========================================================================

    /**
     * Holt alle aktiven Vorschläge für eine Kategorie
     */
    public static function fuerKategorie(string $kategorie): \Illuminate\Database\Eloquent\Collection
    {
        return self::aktiv()
            ->kategorie($kategorie)
            ->orderBy('titel')
            ->get();
    }

    // =========================================================================
    // ACCESSORS
    // =========================================================================

    public function getKategorieNameAttribute(): string
    {
        return self::KATEGORIEN[$this->kategorie] ?? $this->kategorie;
    }

    /**
     * Gibt den Titel oder gekürzte Version des Texts zurück
     */
    public function getAnzeigeNameAttribute(): string
    {
        return $this->titel ?: \Str::limit($this->text, 40);
    }
}
