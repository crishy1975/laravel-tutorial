<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FatturaProfile extends Model
{
    use HasFactory, SoftDeletes;

    // Tabellenname explizit (dein Projekt nutzt oft Singular-Tabellennamen)
    protected $table = 'fattura_profile';

    // Mass Assignment
    protected $fillable = [
        'bezeichnung',
        'split_payment',
        'ritenuta',
        'mwst_satz',
        'bemerkung',
    ];

    // Typ-Casts
    protected $casts = [
        'split_payment' => 'boolean',
        'ritenuta'      => 'boolean',
        'mwst_satz'     => 'decimal:2',
    ];

    /**
     * Gebäude, die dieses Profil verwenden.
     * (nur sinnvoll, wenn du bereits eine Spalte 'fattura_profile_id' in 'gebaeude' hast)
     */
    public function gebaeude()
    {
        return $this->hasMany(Gebaeude::class, 'fattura_profile_id');
    }
}
