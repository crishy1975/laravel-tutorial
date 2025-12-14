<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankImportLog extends Model
{
    protected $table = 'bank_import_logs';

    protected $fillable = [
        'dateiname',
        'datei_hash',
        'anzahl_buchungen',
        'anzahl_neu',
        'anzahl_duplikate',
        'anzahl_matched',
        'iban',
        'von_datum',
        'bis_datum',
        'saldo_anfang',
        'saldo_ende',
        'meta',
    ];

    protected $casts = [
        'von_datum'    => 'date',
        'bis_datum'    => 'date',
        'saldo_anfang' => 'decimal:2',
        'saldo_ende'   => 'decimal:2',
        'meta'         => 'array',
    ];

    /**
     * Buchungen aus diesem Import
     */
    public function buchungen(): HasMany
    {
        return $this->hasMany(BankBuchung::class, 'import_datei', 'dateiname');
    }

    /**
     * Prueft ob Datei bereits importiert wurde
     */
    public static function alreadyImported(string $hash): bool
    {
        return self::where('datei_hash', $hash)->exists();
    }
}
