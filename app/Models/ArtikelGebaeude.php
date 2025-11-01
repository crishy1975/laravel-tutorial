<?php
// app/Models/ArtikelGebaeude.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArtikelGebaeude extends Model
{
    // Expliziter Tabellenname
    protected $table = 'artikel_gebaeude';

    // Erlaubte Felder für Mass Assignment
    protected $fillable = [
        'gebaeude_id',
        'beschreibung',
        'anzahl',
        'einzelpreis',
    ];

    // Typ-Casts
    protected $casts = [
        'anzahl'      => 'decimal:2',
        'einzelpreis' => 'decimal:2',
    ];

    // Dieses Attribut wird automatisch an Array/JSON angehängt
    protected $appends = ['gesamtpreis'];

    /**
     * Beziehung: gehört zu einem Gebäude.
     */
    public function gebaeude()
    {
        return $this->belongsTo(Gebaeude::class, 'gebaeude_id');
    }

    /**
     * Berechnetes Feld: gesamtpreis = anzahl * einzelpreis
     */
    public function getGesamtpreisAttribute(): string
    {
        $anzahl      = (float) ($this->attributes['anzahl'] ?? 0);
        $einzelpreis = (float) ($this->attributes['einzelpreis'] ?? 0);

        // kaufmännisch auf 2 Nachkommastellen runden
        return number_format($anzahl * $einzelpreis, 2, '.', '');
    }
}
