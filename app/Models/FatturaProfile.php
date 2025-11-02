<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FatturaProfile extends Model
{
    use HasFactory;
    use SoftDeletes; // vorausgesetzt: Tabelle hat 'deleted_at'

    // Tabelle (du nutzt Singular-Tabellennamen)
    protected $table = 'fattura_profile';

    // Erlaubte Felder
    protected $fillable = [
        'bezeichnung',
        'split_payment',
        'ritenuta',
        'mwst_satz',
        'bemerkung',
    ];

    // Casts
    protected $casts = [
        'split_payment' => 'boolean',
        'ritenuta'      => 'boolean',
        'mwst_satz'     => 'decimal:2',
    ];

    // Relation: viele Gebäude verwenden ein Profil
    public function gebaeude()
    {
        return $this->hasMany(Gebaeude::class, 'fattura_profile_id');
    }
}
