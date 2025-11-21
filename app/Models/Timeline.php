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
        'verrechnen',
        'verrechnet_am',
        'verrechnet_mit_rn_nummer',
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
        'verrechnet_am'  => 'date',     // NEU
        'verrechnen'     => 'boolean',  // NEU
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

    // ═══════════════════════════════════════════════════════════
    // 🎯 SCOPES
    // ═══════════════════════════════════════════════════════════

    /**
     * Scope: Nur Einträge, die verrechnet werden sollen
     */
    public function scopeZuVerrechnen($query)
    {
        return $query->where('verrechnen', true);
    }

    /**
     * Scope: Nur Einträge, die bereits verrechnet wurden
     */
    public function scopeVerrechnet($query)
    {
        return $query->where('verrechnen', false)
                    ->whereNotNull('verrechnet_am');
    }

    /**
     * Scope: Noch nicht verrechnete Einträge
     */
    public function scopeNichtVerrechnet($query)
    {
        return $query->where('verrechnen', false)
                    ->whereNull('verrechnet_am');
    }

    /**
     * Scope: Für ein bestimmtes Gebäude
     */
    public function scopeForGebaeude($query, int $gebaeudeId)
    {
        return $query->where('gebaeude_id', $gebaeudeId);
    }

    // ═══════════════════════════════════════════════════════════
    // 🏷️ ACCESSORS & HELPERS
    // ═══════════════════════════════════════════════════════════

    /**
     * Ist dieser Eintrag bereits verrechnet?
     */
    public function istVerrechnet(): bool
    {
        return !$this->verrechnen && $this->verrechnet_am !== null;
    }

    /**
     * Soll dieser Eintrag verrechnet werden?
     */
    public function sollVerrechnetWerden(): bool
    {
        return (bool) $this->verrechnen;
    }

    /**
     * Formatiertes Verrechnungsdatum für die Anzeige
     */
    public function getVerrechnetAmFormatiertAttribute(): ?string
    {
        return $this->verrechnet_am 
            ? $this->verrechnet_am->format('d.m.Y')
            : null;
    }

    /**
     * Formatiertes Datum für die Anzeige
     */
    public function getDatumFormatiertAttribute(): string
    {
        return $this->datum 
            ? $this->datum->format('d.m.Y')
            : '';
    }

    /**
     * Status-Badge für UI
     */
    public function getStatusBadgeAttribute(): string
    {
        if ($this->istVerrechnet()) {
            return '<span class="badge bg-success" title="Verrechnet am ' . $this->verrechnet_am_formatiert . '">
                <i class="bi bi-check-circle"></i> Verrechnet
            </span>';
        }

        if ($this->sollVerrechnetWerden()) {
            return '<span class="badge bg-warning text-dark">
                <i class="bi bi-clock-history"></i> Zu verrechnen
            </span>';
        }

        return '<span class="badge bg-secondary">
            <i class="bi bi-dash-circle"></i> Nicht verrechnet
        </span>';
    }
}