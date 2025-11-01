<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Adresse extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'adressen'; // Tabellenname im Plural

    protected $fillable = [
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
}
