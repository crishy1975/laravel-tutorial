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
use App\Models\RechnungPosition;
use App\Models\ArtikelGebaeude;
use App\Models\PreisAufschlag;
use App\Models\Timeline;
use App\Models\Tour;
use App\Models\Adresse;
use App\Models\FatturaProfile;

class Gebaeude extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Expliziter Tabellenname.
     *
     * @var string
     */
    protected $table = 'gebaeude';

    /**
     * Mass-Assignable Felder.
     *
     * @var array<int, string>
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
     * Attribute-Casts (statt $dates).
     *
     * @var array<string, string>
     */
    protected $casts = [
        'deleted_at'         => 'datetime',
        'veraendert_wann'    => 'datetime',
        'letzter_termin'     => 'date',
        'datum_faelligkeit'  => 'date',
        'created_at'         => 'datetime',
        'updated_at'         => 'datetime',

        'faellig'              => 'boolean',
        'rechnung_schreiben'   => 'boolean',
        'geplante_reinigungen' => 'integer',
        'gemachte_reinigungen' => 'integer',

        // Monats-Flags: nur boolean casten, wenn TINYINT(1)/BOOLEAN
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

    // ─────────────────────────────────────────────────────────────
    // Beziehungen
    // ─────────────────────────────────────────────────────────────

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
     *
     * @return HasMany<Timeline>
     */
    public function timelines(): HasMany
    {
        return $this->hasMany(Timeline::class, 'gebaeude_id')
            ->orderByDesc('datum')
            ->orderByDesc('id');
    }

    /**
     * BC-Alias (Singular) für bestehende Views: $gebaeude->timeline()
     *
     * @return HasMany<Timeline>
     */
    public function timeline(): HasMany
    {
        return $this->timelines();
    }

    /**
     * Artikel-Positionen zum Gebäude (z. B. für Angebote/Rechnungen).
     *
     * @return HasMany<ArtikelGebaeude>
     */
    public function artikel(): HasMany
    {
        // Alle Artikel zu diesem Gebäude (unabhängig von aktiv/inaktiv)
        return $this->hasMany(ArtikelGebaeude::class, 'gebaeude_id')
            ->orderByRaw('COALESCE(reihenfolge, 999999) asc')
            ->orderBy('id');
    }

    /**
     * Nur aktive Positionen (für Rechnung).
     *
     * @return HasMany<ArtikelGebaeude>
     */
    public function aktiveArtikel(): HasMany
    {
        // Nur Datensätze mit aktiv = true, sortiert nach Reihenfolge
        return $this->hasMany(ArtikelGebaeude::class, 'gebaeude_id')
            ->where('aktiv', true)
            ->orderByRaw('COALESCE(reihenfolge, 999999) asc')
            ->orderBy('id');
    }

    /**
     * Preisaufschläge, die zu diesem Gebäude gehören (z. B. Fahrtkosten, Zuschläge).
     *
     * @return HasMany<PreisAufschlag>
     */
    public function preisAufschlaege(): HasMany
    {
        return $this->hasMany(PreisAufschlag::class, 'gebaeude_id')
            ->orderByRaw('COALESCE(reihenfolge, 999999) asc')
            ->orderBy('id');
    }

    /**
     * Summe aller Artikel (aktiv + inaktiv) aus ArtikelGebaeude.
     */
    public function getArtikelSummeAttribute(): float
    {
        // Eine einzelne SQL-Query – kein Laden aller Positionen (performant)
        $sum = ArtikelGebaeude::where('gebaeude_id', $this->id)
            ->select(DB::raw('COALESCE(SUM(anzahl * einzelpreis),0) AS total'))
            ->value('total');

        return (float) $sum;
    }

    /**
     * Summe NUR aktiver Positionen (z. B. für Rechnung).
     */
    public function getArtikelSummeAktivAttribute(): float
    {
        return (float) ArtikelGebaeude::where('gebaeude_id', $this->id)
            ->where('aktiv', true)
            ->selectRaw('COALESCE(SUM(anzahl * einzelpreis), 0) as total')
            ->value('total');
    }

    /**
     * Preisaufschläge die für dieses Gebäude gelten (angepasst an Inflations-Schema).
     * 
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function aktivePreisAufschlaege()
    {
        $jahr = now()->year;

        // 1. Prüfe ob gebäudespezifischer Aufschlag existiert
        $gebaeudespezifisch = PreisAufschlag::where('ist_global', false)
            ->where('gebaeude_id', $this->id)
            ->where('jahr', $jahr)
            ->first();

        if ($gebaeudespezifisch) {
            // Gebäudespezifischen Aufschlag zurückgeben
            return PreisAufschlag::where('id', $gebaeudespezifisch->id);
        }

        // 2. Fallback: Globaler Aufschlag
        return PreisAufschlag::where('ist_global', true)
            ->whereNull('gebaeude_id')
            ->where('jahr', $jahr);
    }

    /**
     * Berechnet die Summe aller aktiven Aufschläge basierend auf einer Netto-Basis.
     */
    public function berechnePreisAufschlaege(float $basisNetto): float
    {
        $aufschlag = $this->aktivePreisAufschlaege()->first();

        if (!$aufschlag) {
            return 0.0; // Kein Aufschlag
        }

        return $aufschlag->berechneBetrag($basisNetto);
    }

    /**
     * Ermittelt den Aufschlag-Prozentsatz für dieses Gebäude.
     */
    public function getAufschlagProzent(?int $jahr = null): float
    {
        $jahr = $jahr ?? now()->year;

        // 1. Prüfe gebäudespezifischen Aufschlag
        $gebaeudespezifisch = PreisAufschlag::where('ist_global', false)
            ->where('gebaeude_id', $this->id)
            ->where('jahr', $jahr)
            ->first();

        if ($gebaeudespezifisch) {
            return (float) $gebaeudespezifisch->aufschlag_prozent;
        }

        // 2. Fallback: Globaler Aufschlag
        $global = PreisAufschlag::where('ist_global', true)
            ->whereNull('gebaeude_id')
            ->where('jahr', $jahr)
            ->first();

        if ($global) {
            return (float) $global->aufschlag_prozent;
        }

        // 3. Kein Aufschlag
        return 0.0;
    }

    /**
     * Hat dieses Gebäude einen individuellen Aufschlag?
     */
    public function hatIndividuellenAufschlag(?int $jahr = null): bool
    {
        $jahr = $jahr ?? now()->year;

        return PreisAufschlag::where('ist_global', false)
            ->where('gebaeude_id', $this->id)
            ->where('jahr', $jahr)
            ->exists();
    }

    /**
     * Setzt einen individuellen Aufschlag für dieses Gebäude.
     */
    public function setAufschlag(float $prozent, ?int $jahr = null, ?string $bemerkung = null): PreisAufschlag
    {
        $jahr = $jahr ?? now()->year;

        return PreisAufschlag::updateOrCreate(
            [
                'jahr' => $jahr,
                'ist_global' => false,
                'gebaeude_id' => $this->id,
            ],
            [
                'aufschlag_prozent' => $prozent,
                'bemerkung' => $bemerkung,
            ]
        );
    }

    /**
     * Entfernt individuellen Aufschlag (nutzt dann wieder globalen).
     */
    public function entferneIndividuellenAufschlag(?int $jahr = null): void
    {
        $jahr = $jahr ?? now()->year;

        PreisAufschlag::where('ist_global', false)
            ->where('gebaeude_id', $this->id)
            ->where('jahr', $jahr)
            ->delete();
    }

    /**
     * Fattura-Profil, das dem Gebäude zugeordnet ist.
     */
    public function fatturaProfile(): BelongsTo
    {
        return $this->belongsTo(FatturaProfile::class, 'fattura_profile_id');
    }

    /**
     * Prüft, ob der gegebene Monat (1..12) im Gebäude aktiv ist (m01..m12 == 1).
     */
    public function isMonthActive(int $month): bool
    {
        // month 1 => m01, 12 => m12
        $key = 'm' . str_pad((string) $month, 2, '0', STR_PAD_LEFT);

        return (int) ($this->{$key} ?? 0) === 1;
    }

    /**
     * Liefert das letzte Reinigungsdatum (max datum aus Timeline) oder null.
     */
    public function lastCleaningDate(): ?Carbon
    {
        // Achtung: Relation heißt 'timelines'
        $d = $this->timelines()->max('datum');

        return $d ? Carbon::parse($d) : null;
    }

    /**
     * Rechnet das Flag 'faellig' anhand der Regel neu und speichert es (optional).
     *
     * Regel:
     *  faellig = (aktueller Monat aktiv)
     *            && (letzte Reinigung < 1. Tag des Monats ODER keine Reinigung).
     *
     * @param  \Illuminate\Support\Carbon|null $today  für Tests überschreibbar
     * @param  bool $persist  true => schreibt in DB, false => nur berechnen
     * @return bool  der berechnete Fälligkeitswert
     */
    public function recomputeFaellig(?Carbon $today = null, bool $persist = true): bool
    {
        $today = $today ?: now();
        $monthActive = $this->isMonthActive((int) $today->month);

        // Wenn aktueller Monat nicht aktiv → nie fällig
        if (! $monthActive) {
            if ($persist) {
                $this->update(['faellig' => 0]);
            }

            return false;
        }

        $last = $this->lastCleaningDate(); // kann null sein
        $monthStart = $today->copy()->startOfMonth();

        $due = ! $last || $last->lt($monthStart);

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
            ->select([
                'id',
                'faellig',
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
            ])
            ->chunkById(500, function ($chunk) use ($today, &$changed) {
                /** @var \App\Models\Gebaeude $g */
                foreach ($chunk as $g) {
                    $new = $g->recomputeFaellig($today, false); // nur berechnen
                    $newInt = $new ? 1 : 0;

                    if ((int) $g->faellig !== $newInt) {
                        $g->faellig = $newInt;
                        $g->save();
                        $changed++;
                    }
                }
            });

        return $changed;
    }

    /**
     * Rechnungen für dieses Gebäude.
     *
     * @return HasMany<Rechnung>
     */
    public function rechnungen(): HasMany
    {
        return $this->hasMany(Rechnung::class);
    }

    /**
     * Erstellt automatisch eine Rechnung aus diesem Gebäude.
     * 
     * Kopiert automatisch:
     * - Rechnungsempfänger & Postadresse (Snapshot)
     * - Gebäude-Informationen (Snapshot)
     * - FatturaPA-Profile (Snapshot)
     * - Alle aktiven Artikel als Rechnungspositionen
     *
     * @param array<string, mixed> $overrides Optionale Überschreibungen
     * @return \App\Models\Rechnung Die erstellte Rechnung im Status 'draft'
     *
     * @example
     * $gebaeude = Gebaeude::find(1);
     * $rechnung = $gebaeude->createRechnung();
     */
    public function createRechnung(array $overrides = []): Rechnung
    {
        // Delegiert die eigentliche Logik an das Rechnung-Model
        return Rechnung::createFromGebaeude($this, $overrides);
    }
}
