<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use App\Models\Rechnung;

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
        'bemerkung_buchhaltung',
        'cup',
        'cig',
        'auftrag_id',
        'auftrag_datum',
        'fattura_profile_id',
        'bank_match_text_template',
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
            ->orderByRaw('COALESCE(reihenfolge, 999999) asc')
            ->orderBy('id');
    }

    public function getArtikelSummeAttribute(): float
    {
        // Eine einzelne SQL-Query – kein Laden aller Positionen (performant)
        $sum = \App\Models\ArtikelGebaeude::where('gebaeude_id', $this->id)
            ->select(DB::raw('COALESCE(SUM(anzahl * einzelpreis),0) AS total'))
            ->value('total');

        return (float) $sum;
    }

    /** Nur aktive Positionen (für Rechnung) */
    public function aktiveArtikel()
    {
        return $this->hasMany(\App\Models\ArtikelGebaeude::class, 'gebaeude_id')
            ->where('aktiv', true)
            ->orderByRaw('COALESCE(reihenfolge, 999999) asc')
            ->orderBy('id');
    }

    /** Summe NUR aktiver Positionen (z. B. für Rechnung) */
    public function getArtikelSummeAktivAttribute(): float
    {
        return (float) \App\Models\ArtikelGebaeude::where('gebaeude_id', $this->id)
            ->where('aktiv', true)
            ->selectRaw('COALESCE(SUM(anzahl * einzelpreis), 0) as total')
            ->value('total');
    }

    public function fatturaProfile()
    {
        return $this->belongsTo(\App\Models\FatturaProfile::class, 'fattura_profile_id');
    }

    /**
     * Prüft, ob der gegebene Monat (1..12) im Gebäude aktiv ist (m01..m12 == 1).
     */
    public function isMonthActive(int $month): bool
    {
        // month 1 => m01, 12 => m12
        $key = 'm' . str_pad((string)$month, 2, '0', STR_PAD_LEFT);
        return (int)($this->{$key} ?? 0) === 1;
    }

    /**
     * Liefert das letzte Reinigungsdatum (max datum aus Timeline) oder null.
     */
    public function lastCleaningDate(): ?Carbon
    {
        // Achtung: Relation heißt bei dir 'timelines'
        $d = $this->timelines()->max('datum');
        return $d ? Carbon::parse($d) : null;
    }

    /**
     * Rechnet das Flag 'faellig' anhand der Regel neu und speichert es (optional).
     * Regel: faellig = (aktueller Monat aktiv) && (letzte Reinigung < 1. Tag des Monats ODER keine Reinigung).
     *
     * @param  \Illuminate\Support\Carbon|null $today  für Tests überschreibbar
     * @param  bool $persist  true => schreibt in DB, false => nur berechnen
     * @return bool  der berechnete Fälligkeitswert
     */
    public function recomputeFaellig(?Carbon $today = null, bool $persist = true): bool
    {
        $today = $today ?: now();
        $monthActive = $this->isMonthActive((int)$today->month);

        // Wenn aktueller Monat nicht aktiv → nie fällig
        if (!$monthActive) {
            if ($persist) $this->update(['faellig' => 0]);
            return false;
        }

        $last = $this->lastCleaningDate(); // kann null sein
        $monthStart = $today->copy()->startOfMonth();

        $due = !$last || $last->lt($monthStart);

        if ($persist) {
            $this->update(['faellig' => $due ? 1 : 0]);
        }

        return $due;
    }

    /**
     * Statische Helferfunktion für Bulk-Neuberechnung (z.B. CRON/Command).
     * Gibt Anzahl der geänderten Datensätze zurück (nur wenn persist=true).
     */
    public static function bulkRecomputeFaellig(?Carbon $today = null): int
    {
        $today = $today ?: now();
        $changed = 0;

        // Sparsam selektieren (nur Spalten, die wir brauchen)
        static::query()
            ->select(['id', 'faellig', 'm01', 'm02', 'm03', 'm04', 'm05', 'm06', 'm07', 'm08', 'm09', 'm10', 'm11', 'm12'])
            ->chunkById(500, function ($chunk) use ($today, &$changed) {
                /** @var \App\Models\Gebaeude $g */
                foreach ($chunk as $g) {
                    $new = $g->recomputeFaellig($today, false); // nur berechnen
                    $newInt = $new ? 1 : 0;
                    if ((int)$g->faellig !== $newInt) {
                        $g->faellig = $newInt;
                        $g->save();
                        $changed++;
                    }
                }
            });

        return $changed;
    }

    /**
     * Rechnungen für dieses Gebäude
     */
    public function rechnungen(): HasMany
    {
        return $this->hasMany(Rechnung::class);
    }

// app/Models/Gebaeude.php - Nur der betroffene Teil

    /**
     * Erstellt automatisch eine Rechnung aus diesem Gebäude.
     * 
     * Kopiert automatisch:
     * - Rechnungsempfänger & Postadresse (Snapshot)
     * - Gebäude-Informationen (Snapshot)
     * - FatturaPA-Profile (Snapshot)
     * - Alle aktiven Artikel als Rechnungspositionen
     *
     * @param array<string, mixed> $overrides Optionale Überschreibungen (z.B. ['rechnungsdatum' => '2025-12-31'])
     * @return \App\Models\Rechnung Die erstellte Rechnung im Status 'draft'
     * 
     * @example
     * $gebaeude = Gebaeude::find(1);
     * $rechnung = $gebaeude->createRechnung();
     * 
     * // Mit Überschreibungen:
     * $rechnung = $gebaeude->createRechnung([
     *     'rechnungsdatum' => '2025-12-31',
     *     'zahlungsziel' => '2026-01-30',
     * ]);
     */
    public function createRechnung(array $overrides = []): Rechnung
    {
        return Rechnung::createFromGebaeude($this, $overrides);
    }
}
