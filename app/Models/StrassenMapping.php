<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StrassenMapping extends Model
{
    protected $table = 'strassen_mappings';

    protected $fillable = [
        'strasse_original',
        'sort_key',
        'is_manual',
    ];

    protected $casts = [
        'is_manual' => 'boolean',
    ];

    /**
     * Alle Gebäude mit diesem Straßennamen.
     */
    public function gebaeude()
    {
        return Gebaeude::where('strasse', $this->strasse_original);
    }

    /**
     * Gruppe umbenennen: Aktualisiert alle Mappings UND alle Gebäude.
     */
    public static function gruppeUmbenennen(string $alterKey, string $neuerKey): int
    {
        $neuerKey = mb_strtoupper(trim($neuerKey), 'UTF-8');

        // Mappings aktualisieren
        static::where('sort_key', $alterKey)->update([
            'sort_key'  => $neuerKey,
            'is_manual' => true,
        ]);

        // Gebäude aktualisieren
        return Gebaeude::where('strasse_sort_key', $alterKey)->update([
            'strasse_sort_key' => $neuerKey,
        ]);
    }
}
