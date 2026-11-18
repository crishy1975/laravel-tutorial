<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GemeindeMeldung extends Model
{
    protected $table = 'gemeinde_meldungen';

    protected $fillable = [
        'gemeinde_id',
        'empfaenger',
        'betreff',
        'text',
        'gebaeude_ids',
        'versandart',
        'user_id',
        'gesendet_am',
    ];

    protected $casts = [
        'gebaeude_ids' => 'array',
        'gesendet_am'  => 'datetime',
    ];

    public function gemeinde(): BelongsTo
    {
        return $this->belongsTo(Gemeinde::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
