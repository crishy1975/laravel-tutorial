<?php
/*
════════════════════════════════════════════════════════════════════════════
DATEI: TagesinkassiVersand.php
PFAD:  app/Models/TagesinkassiVersand.php
════════════════════════════════════════════════════════════════════════════
*/

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TagesinkassiVersand extends Model
{
    protected $table = 'tagesinkassi_versand';

    protected $fillable = [
        'empfaenger',
        'umfang',
        'jahr',
        'monat',
        'betreff',
    ];

    protected $casts = [
        'jahr'  => 'integer',
        'monat' => 'integer',
    ];

    /** Zuletzt verwendete Empfängeradresse(n) */
    public static function letzteEmpfaenger(): string
    {
        return (string) static::latest('id')->value('empfaenger');
    }
}
