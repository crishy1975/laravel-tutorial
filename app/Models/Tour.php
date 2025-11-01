<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tour extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tour'; // deine Tour-Tabelle (singular)

    protected $fillable = [
        'name',
        'beschreibung',
        'aktiv',
        'reihenfolge'];

    protected $casts = [
        'aktiv' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // 🔗 Rückbeziehung: Tour ⇄ Gebäude
    public function gebaeude()
    {
        return $this->belongsToMany(\App\Models\Gebaeude::class, 'tourgebaeude', 'tour_id', 'gebaeude_id')
            ->withPivot('reihenfolge')
            ->orderByPivot('reihenfolge');
            // oder ->orderBy('tourgebaeude.reihenfolge');
    }
}
