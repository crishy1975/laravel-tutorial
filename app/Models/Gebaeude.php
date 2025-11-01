<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB; // ✅ Richtig importieren

class Gebaeude extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Expliziter Tabellenname.
     */
    protected $table = 'gebaeude';

    /**
     * Mass-Assignable Felder.
     */
    protected $fillable = [
        'codex',
        'postadresse_id',
        'rechnungsempfaenger_id',
        'gebaeude_name',
        'strasse',
        'hausnummer',
        'plz',
        'wohnort',
        'land',
        'bemerkung',
        'veraendert',
        'veraendert_wann',
        'letzter_termin',
        'datum_faelligkeit',
        'geplante_reinigungen',
        'gemachte_reinigungen',
        'faellig',
        'rechnung_schreiben',
        'm01',
        'm02',
        'm03',
        'm04',
        'm05',
        'm06',
        'm07',
        'm08',
        'm09',
        'm10',
        'm11',
        'm12',
        'select_tour',
    ];

    /**
     * Moderne Casts (statt $dates).
     * Passe Bool-/Int-Casts an deine DB-Typen an.
     */
    protected $casts = [
        'deleted_at'         => 'datetime',
        'veraendert_wann'    => 'datetime',
        'letzter_termin'     => 'date',
        'datum_faelligkeit'  => 'date',
        'created_at'         => 'datetime',
        'updated_at'         => 'datetime',

        'faellig'             => 'boolean',
        'rechnung_schreiben'  => 'boolean',
        'geplante_reinigungen' => 'integer',
        'gemachte_reinigungen' => 'integer',

        // Nur als boolean casten, wenn TINYINT(1)/BOOLEAN
        'm01' => 'boolean',
        'm02' => 'boolean',
        'm03' => 'boolean',
        'm04' => 'boolean',
        'm05' => 'boolean',
        'm06' => 'boolean',
        'm07' => 'boolean',
        'm08' => 'boolean',
        'm09' => 'boolean',
        'm10' => 'boolean',
        'm11' => 'boolean',
        'm12' => 'boolean',
    ];

    // ─────────────────────────────────────────────────────────────────────────────
    // Beziehungen
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Postadresse (BelongsTo).
     */
    public function postadresse(): BelongsTo
    {
        return $this->belongsTo(Adresse::class, 'postadresse_id');
    }

    /**
     * Rechnungsempfänger (BelongsTo).
     */
    public function rechnungsempfaenger(): BelongsTo
    {
        return $this->belongsTo(Adresse::class, 'rechnungsempfaenger_id');
    }

    /**
     * Viele-zu-Viele: Gebäude ⇄ Tour über Pivot 'tourgebaeude'.
     * Sortiert nach Pivot-Feld 'reihenfolge'.
     */
    public function touren(): BelongsToMany
    {
        return $this->belongsToMany(Tour::class, 'tourgebaeude', 'gebaeude_id', 'tour_id')
            ->withPivot('reihenfolge')
            ->orderBy('tourgebaeude.reihenfolge');
    }

    /**
     * Timeline-Einträge (HasMany), neueste zuerst.
     */
    public function timelines(): HasMany
    {
        return $this->hasMany(Timeline::class, 'gebaeude_id')
            ->orderByDesc('datum')
            ->orderByDesc('id');
    }

    /**
     * BC-Alias (Singular) für bestehende Views: $gebaeude->timeline()
     */
    public function timeline(): HasMany
    {
        return $this->timelines();
    }

    /**
     * Artikel-Positionen zum Gebäude (z. B. für Angebote/Rechnungen).
     */
    public function artikel()
    {
        return $this->hasMany(\App\Models\ArtikelGebaeude::class, 'gebaeude_id')
            ->orderBy('id'); // oder nach beschreibung/created_at sortieren
    }

    public function getArtikelSummeAttribute(): float
    {
        // Eine einzelne SQL-Query – kein Laden aller Positionen (performant)
        $sum = \App\Models\ArtikelGebaeude::where('gebaeude_id', $this->id)
            ->select(DB::raw('COALESCE(SUM(anzahl * einzelpreis),0) AS total'))
            ->value('total');

        return (float) $sum;
    }
}
