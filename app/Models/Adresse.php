<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Adresse extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'adressen'; // Tabellenname im Plural

    protected $fillable = [
        'legacy_id',      // ⭐ NEU
        'legacy_mid',     // ⭐ NEU
        'name',
        'strasse',
        'hausnummer',
        'plz',
        'wohnort',
        'provinz',
        'land',
        'telefon',
        'handy',
        'email',
        'email_zweit',
        'pec',
        'steuernummer',
        'mwst_nummer',
        'codice_univoco',
        'bemerkung',
        'veraendert',
        'veraendert_wann',
    ];

    protected $dates = [
        'deleted_at',
        'veraendert_wann',
        'created_at',
        'updated_at',
    ];

    /**
     * Rechnungen, bei denen diese Adresse Rechnungsempfänger (Zahler) ist
     */
    public function rechnungenAlsEmpfaenger(): HasMany
    {
        return $this->hasMany(Rechnung::class, 'rechnungsempfaenger_id');
    }

    /**
     * Rechnungen, bei denen diese Adresse Postadresse (Versand) ist
     */
    public function rechnungenAlsPostadresse(): HasMany
    {
        return $this->hasMany(Rechnung::class, 'postadresse_id');
    }
}
