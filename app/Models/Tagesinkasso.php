<?php
/*
════════════════════════════════════════════════════════════════════════════
DATEI: Tagesinkasso.php
PFAD:  app/Models/Tagesinkasso.php
════════════════════════════════════════════════════════════════════════════
*/

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Tagesinkasso extends Model
{
    protected $table = 'tagesinkassi';

    protected $fillable = [
        'datum',
        'betrag_10',
        'betrag_22',
        'bemerkung',
    ];

    protected $casts = [
        'datum'     => 'date',
        'betrag_10' => 'decimal:2',
        'betrag_22' => 'decimal:2',
    ];

    /** Tagessumme brutto */
    public function getGesamtAttribute(): float
    {
        return round((float) $this->betrag_10 + (float) $this->betrag_22, 2);
    }

    /** Alle Einträge eines Monats (indexfreundlich per BETWEEN) */
    public function scopeImMonat(Builder $query, int $jahr, int $monat): Builder
    {
        $start = Carbon::create($jahr, $monat, 1);

        return $query->whereBetween('datum', [
            $start->toDateString(),
            $start->copy()->endOfMonth()->toDateString(),
        ]);
    }

    /** Alle Einträge eines Jahres */
    public function scopeImJahr(Builder $query, int $jahr): Builder
    {
        return $query->whereBetween('datum', ["{$jahr}-01-01", "{$jahr}-12-31"]);
    }

    /**
     * Scorporo: Brutto → Imponibile + IVA
     * @return array{imponibile: float, iva: float}
     */
    public static function scorporo(float $brutto, float $satzProzent): array
    {
        $imponibile = round($brutto / (1 + $satzProzent / 100), 2);

        return [
            'imponibile' => $imponibile,
            'iva'        => round($brutto - $imponibile, 2),
        ];
    }
}
