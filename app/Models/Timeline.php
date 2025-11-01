<?php
// app/Models/Timeline.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; // ← für deleted_at

class Timeline extends Model
{
    use SoftDeletes; // aktiviert SoftDeletes (setzt/liest deleted_at)

    /**
     * Tabelle existiert bereits und heißt 'timeline' (singular).
     * Ohne diese Angabe würde Laravel 'timelines' erwarten.
     */
    protected $table = 'timeline';

    /**
     * Mass-Assignment: Diese Felder dürfen per create()/fill() gesetzt werden.
     */
    protected $fillable = [
        'gebaeude_id',
        'datum',        // DATE/DATETIME in DB (siehe Casts)
        'bemerkung',
        'person_name',
        'person_id',    // je nach Nutzung: User-ID ODER Adresse-ID (siehe Relation unten)
    ];

    /**
     * Timestamps:
     * - created_at: existiert bereits in deiner Tabelle
     * - updated_at: per Migration ergänzt
     * - deleted_at: per Migration ergänzt (SoftDeletes)
     * Standardmäßig ist $timestamps = true → created_at/updated_at werden automatisch gepflegt.
     */

    /**
     * Casts:
     * - 'datum' ist (laut deiner Vorgabe) ein DATE-Feld → 'date' cast.
     *   Falls es DATETIME/TIMESTAMP ist, auf 'datetime' ändern.
     * - Zeitstempel werden als datetime gecastet.
     */
    protected $casts = [
        'datum'       => 'date',     // bei DATETIME stattdessen: 'datetime'
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
        'deleted_at'  => 'datetime',
    ];

    // ───────────────────────────────── Beziehungen ─────────────────────────────────

    /**
     * Zugehöriges Gebäude.
     */
    public function gebaeude()
    {
        return $this->belongsTo(\App\Models\Gebaeude::class, 'gebaeude_id');
    }

    /**
     * Optionale Person-Referenz.
     * HINWEIS:
     * - In deinem Controller wird aktuell user() genutzt → person_id wäre dann User-ID.
     * - Wenn person_id stattdessen auf Adresse zeigen soll, ist diese Relation korrekt.
     *   Ansonsten (bei User-IDs) bitte auf User::class anpassen.
     */
    public function person()
    {
        return $this->belongsTo(\App\Models\Adresse::class, 'person_id');
        // Alternative (falls person_id auf users.id zeigt):
        // return $this->belongsTo(\App\Models\User::class, 'person_id');
    }
}
