<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lohnstunde extends Model
{
    use HasFactory;

    protected $table = 'lohnstunden';

    protected $fillable = [
        'user_id',
        'datum',
        'typ',
        'stunden',
        'notizen',
    ];

    protected $casts = [
        'datum' => 'date',
        'stunden' => 'decimal:2',
    ];

    /**
     * Alle verfügbaren Typen mit Übersetzungen (DE/IT)
     */
    public static function getTypen(): array
    {
        return [
            'No' => 'Normalstunden / Ore normali',
            'Üb' => 'Überstunden / Straordinari',
            'F'  => 'Ferien / Ferie',
            'P'  => 'Permessi / Freistunden',
            'A'  => 'Abwesend / Assente',
            'C'  => 'Lohnausgleich / Cassa integrazione',
            'K'  => 'Krankheit / Malattia',
            'U'  => 'Unfall / Infortunio',
            'S'  => 'Schule / Scuola',
            'M'  => 'Mutterschaft / Maternità',
            'BS' => 'Blutspende / Donazione sangue',
            'H'  => 'Hochzeitsurlaub / Congedo matrimoniale',
        ];
    }

    /**
     * Typ-Bezeichnung holen
     */
    public function getTypBezeichnungAttribute(): string
    {
        return self::getTypen()[$this->typ] ?? $this->typ;
    }

    /**
     * Beziehung zum User (Mitarbeiter)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: Einträge eines bestimmten Monats
     */
    public function scopeMonat($query, int $monat, int $jahr)
    {
        return $query->whereYear('datum', $jahr)
                     ->whereMonth('datum', $monat);
    }

    /**
     * Scope: Einträge dieser Woche
     */
    public function scopeDieseWoche($query)
    {
        return $query->whereBetween('datum', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    /**
     * Scope: Einträge von heute
     */
    public function scopeHeute($query)
    {
        return $query->whereDate('datum', today());
    }
}
