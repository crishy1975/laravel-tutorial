<?php
/*
════════════════════════════════════════════════════════════════════════════
DATEI: TagesinkassiPosTransaktion.php
PFAD:  app/Models/TagesinkassiPosTransaktion.php
════════════════════════════════════════════════════════════════════════════
*/

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TagesinkassiPosTransaktion extends Model
{
    protected $table = 'tagesinkassi_pos_transaktionen';

    protected $fillable = [
        'referenz',
        'datum_zeit',
        'betrag',
        'karte',
        'terminal',
        'beschreibung',
        'tagesinkasso_id',
    ];

    protected $casts = [
        'datum_zeit' => 'datetime',
        'betrag'     => 'decimal:2',
    ];

    public function tagesinkasso(): BelongsTo
    {
        return $this->belongsTo(Tagesinkasso::class);
    }
}
