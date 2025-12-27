<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Spruch extends Model
{
    use HasFactory;

    protected $table = 'sprueche';

    protected $fillable = [
        'kategorie',
        'text',
        'aktiv',
        'sort_order',
    ];

    protected $casts = [
        'aktiv' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Kategorien mit Labels und Emojis
     */
    public const KATEGORIEN = [
        'morgen' => ['label' => 'Morgen (5-12 Uhr)', 'emoji' => '☀️'],
        'mittag' => ['label' => 'Mittag (12-17 Uhr)', 'emoji' => '🌤️'],
        'abend' => ['label' => 'Abend (17-21 Uhr)', 'emoji' => '🌅'],
        'nacht' => ['label' => 'Nacht (21-5 Uhr)', 'emoji' => '🌙'],
        'wochenende' => ['label' => 'Wochenende', 'emoji' => '🎉'],
    ];

    /**
     * Scope: Nur aktive Sprüche
     */
    public function scopeAktiv(Builder $query): Builder
    {
        return $query->where('aktiv', true);
    }

    /**
     * Scope: Nach Kategorie filtern
     */
    public function scopeKategorie(Builder $query, string $kategorie): Builder
    {
        return $query->where('kategorie', $kategorie);
    }

    /**
     * Scope: Sortiert
     */
    public function scopeSorted(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Zufälligen aktiven Spruch aus Kategorie holen
     */
    public static function zufaellig(string $kategorie): ?self
    {
        return static::aktiv()
            ->kategorie($kategorie)
            ->inRandomOrder()
            ->first();
    }

    /**
     * Spruch-Text mit Namen formatieren
     */
    public function formatiert(string $name): string
    {
        return sprintf($this->text, $name);
    }

    /**
     * Emoji für die Kategorie
     */
    public function getEmojiAttribute(): string
    {
        return self::KATEGORIEN[$this->kategorie]['emoji'] ?? '💬';
    }

    /**
     * Label für die Kategorie
     */
    public function getKategorieLabelAttribute(): string
    {
        return self::KATEGORIEN[$this->kategorie]['label'] ?? $this->kategorie;
    }
}
