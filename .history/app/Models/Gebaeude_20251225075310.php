<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use App\Enums\GebaeudeLogTyp;

class Gebaeude extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'gebaeude';

    protected $fillable = [
        'legacy_id',
        'legacy_mid',
        'paoloweb_id',
        'codex',
        'postadresse_id',
        'rechnungsempfaenger_id',
        'gebaeude_name',
        'strasse',
        'hausnummer',
        'plz',
        'wohnort',
        'telefon',           // ⭐ NEU
        'handy',             // ⭐ NEU
        'email',             // ⭐ NEU
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
        'codice_commessa',
        'auftrag_id',
        'auftrag_datum',
        'fattura_profile_id',
        'bank_match_text_template',
    ];

    protected $casts = [
        'deleted_at'           => 'datetime',
        'veraendert_wann'      => 'datetime',
        'letzter_termin'       => 'date',
        'datum_faelligkeit'    => 'date',
        'created_at'           => 'datetime',
        'updated_at'           => 'datetime',
        'faellig'              => 'boolean',
        'rechnung_schreiben'   => 'boolean',
        'geplante_reinigungen' => 'integer',
        'gemachte_reinigungen' => 'integer',
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

    // ═══════════════════════════════════════════════════════════
    // 📞 KONTAKT-HELPER
    // ═══════════════════════════════════════════════════════════

    /**
     * Hat das Gebäude Kontaktdaten?
     */
    public function hatKontaktdaten(): bool
    {
        return !empty($this->telefon) || !empty($this->handy) || !empty($this->email);
    }

    /**
     * Primäre Telefonnummer (Handy bevorzugt)
     */
    public function getPrimaereTelefonnummerAttribute(): ?string
    {
        return $this->handy ?: $this->telefon;
    }

    /**
     * Alle Kontaktdaten als Array
     */
    public function getKontaktdatenAttribute(): array
    {
        return array_filter([
            'telefon' => $this->telefon,
            'handy'   => $this->handy,
            'email'   => $this->email,
        ]);
    }

    /**
     * Kontaktdaten formatiert für Anzeige
     */
    public function getKontaktdatenFormatiertAttribute(): string
    {
        $parts = [];
        
        if ($this->handy) {
            $parts[] = "📱 {$this->handy}";
        }
        if ($this->telefon) {
            $parts[] = "📞 {$this->telefon}";
        }
        if ($this->email) {
            $parts[] = "✉️ {$this->email}";
        }
        
        return implode(' | ', $parts);
    }

    // ═══════════════════════════════════════════════════════════
    // 🔗 RELATIONSHIPS
    // ═══════════════════════════════════════════════════════════

    public function postadresse(): BelongsTo
    {
        return $this->belongsTo(Adresse::class, 'postadresse_id');
    }

    public function rechnungsempfaenger(): BelongsTo
    {
        return $this->belongsTo(Adresse::class, 'rechnungsempfaenger_id');
    }

    public function touren(): BelongsToMany
    {
        return $this->belongsToMany(Tour::class, 'tourgebaeude', 'gebaeude_id', 'tour_id')
            ->withPivot('reihenfolge')
            ->orderBy('tourgebaeude.reihenfolge');
    }

    public function timelines(): HasMany
    {
        return $this->hasMany(Timeline::class, 'gebaeude_id')
            ->orderByDesc('datum')
            ->orderByDesc('id');
    }

    public function timeline(): HasMany
    {
        return $this->timelines();
    }

    public function artikel(): HasMany
    {
        return $this->hasMany(ArtikelGebaeude::class, 'gebaeude_id')
            ->orderByRaw('COALESCE(reihenfolge, 999999) asc')
            ->orderBy('id');
    }

    public function aktiveArtikel(): HasMany
    {
        return $this->hasMany(ArtikelGebaeude::class, 'gebaeude_id')
            ->where('aktiv', true)
            ->orderByRaw('COALESCE(reihenfolge, 999999) asc')
            ->orderBy('id');
    }

    public function fatturaProfile(): BelongsTo
    {
        return $this->belongsTo(FatturaProfile::class, 'fattura_profile_id');
    }

    public function rechnungen(): HasMany
    {
        return $this->hasMany(Rechnung::class);
    }

    /**
     * Aktuell gueltiger gebaeude-spezifischer Aufschlag (falls vorhanden)
     */
    public function gebaeudeAufschlag(): HasOne
    {
        return $this->hasOne(GebaeudeAufschlag::class)
            ->where('gueltig_ab', '<=', now())
            ->where(function ($q) {
                $q->whereNull('gueltig_bis')
                    ->orWhere('gueltig_bis', '>=', now());
            })
            ->latest('gueltig_ab');
    }

    /**
     * Alle Aufschlaege fuer dieses Gebaeude (Historie)
     */
    public function alleGebaeudeAufschlaege(): HasMany
    {
        return $this->hasMany(GebaeudeAufschlag::class)
            ->orderByDesc('gueltig_ab');
    }

    // ═══════════════════════════════════════════════════════════
    // ⭐ LOG RELATIONSHIPS
    // ═══════════════════════════════════════════════════════════

    /**
     * Alle Log-Eintraege fuer dieses Gebaeude
     */
    public function logs(): HasMany
    {
        return $this->hasMany(GebaeudeLog::class)
            ->orderByDesc('created_at');
    }

    /**
     * Nur die letzten X Log-Eintraege
     */
    public function letzteAktivitaeten(int $anzahl = 10): HasMany
    {
        return $this->hasMany(GebaeudeLog::class)
            ->orderByDesc('created_at')
            ->limit($anzahl);
    }

    /**
     * Offene Erinnerungen fuer dieses Gebaeude
     */
    public function offeneErinnerungen(): HasMany
    {
        return $this->hasMany(GebaeudeLog::class)
            ->whereNotNull('erinnerung_datum')
            ->where(function($q) {
                $q->where('erinnerung_erledigt', false)
                  ->orWhereNull('erinnerung_erledigt');
            })
            ->orderBy('erinnerung_datum');
    }

    /**
     * Offene Probleme/Reklamationen
     */
    public function offeneProbleme(): HasMany
    {
        return $this->hasMany(GebaeudeLog::class)
            ->whereIn('typ', [
                GebaeudeLogTyp::REKLAMATION->value,
                GebaeudeLogTyp::PROBLEM->value,
                GebaeudeLogTyp::MANGEL->value,
                GebaeudeLogTyp::SCHADENSMELDUNG->value,
            ])
            ->orderByDesc('created_at');
    }

    // ═══════════════════════════════════════════════════════════
    // ⭐ LOG HELPER METHODS
    // ═══════════════════════════════════════════════════════════

    /**
     * Schnell einen Log-Eintrag hinzufuegen
     */
    public function logEintrag(
        GebaeudeLogTyp $typ,
        ?string $beschreibung = null,
        array $metadata = []
    ): GebaeudeLog {
        return GebaeudeLog::log($this->id, $typ, $beschreibung, $metadata);
    }

    /**
     * Notiz hinzufuegen
     */
    public function notizHinzufuegen(string $text, string $prioritaet = 'normal'): GebaeudeLog
    {
        return GebaeudeLog::notiz($this->id, $text, $prioritaet);
    }

    /**
     * Hat offene Probleme?
     */
    public function hatOffeneProbleme(): bool
    {
        return $this->offeneProbleme()->exists();
    }

    /**
     * Anzahl offener Erinnerungen
     */
    public function anzahlOffeneErinnerungen(): int
    {
        return $this->offeneErinnerungen()->count();
    }

    // ═══════════════════════════════════════════════════════════
    // 📁 DOKUMENTE RELATIONSHIPS
    // ═══════════════════════════════════════════════════════════

    /**
     * Alle Dokumente dieses Gebaeudes (ohne archivierte)
     */
    public function dokumente(): HasMany
    {
        return $this->hasMany(GebaeudeDocument::class, 'gebaeude_id')
            ->where('ist_archiviert', false)
            ->orderByDesc('ist_wichtig')
            ->orderByDesc('created_at');
    }

    /**
     * Alle Dokumente (inkl. archivierte)
     */
    public function alleDokumente(): HasMany
    {
        return $this->hasMany(GebaeudeDocument::class, 'gebaeude_id')
            ->orderByDesc('created_at');
    }

    /**
     * Nur wichtige Dokumente
     */
    public function wichtigeDokumente(): HasMany
    {
        return $this->dokumente()->where('ist_wichtig', true);
    }

    /**
     * Nur Bilder
     */
    public function bilder(): HasMany
    {
        return $this->dokumente()->where('dateityp', 'like', 'image/%');
    }

    /**
     * Nur PDFs
     */
    public function pdfs(): HasMany
    {
        return $this->dokumente()->where('dateiendung', 'pdf');
    }

    /**
     * Dokumente nach Kategorie
     */
    public function dokumenteNachKategorie(string $kategorie): HasMany
    {
        return $this->dokumente()->where('kategorie', $kategorie);
    }

    /**
     * Anzahl Dokumente (fuer Listen-Anzeige)
     */
    public function getDokumenteCountAttribute(): int
    {
        return $this->dokumente()->count();
    }

    /**
     * Gesamtgroesse aller Dokumente
     */
    public function getDokumenteGroesseAttribute(): int
    {
        return $this->dokumente()->sum('dateigroesse');
    }

    /**
     * Hat wichtige Dokumente?
     */
    public function hatWichtigeDokumente(): bool
    {
        return $this->dokumente()->where('ist_wichtig', true)->exists();
    }

    // ═══════════════════════════════════════════════════════════
    // 🧮 PREIS-AUFSCHLAG LOGIK
    // ═══════════════════════════════════════════════════════════

    /**
     * Ermittelt den anzuwendenden Aufschlag fuer dieses Gebaeude.
     * 
     * Prioritaet:
     * 1. Gebaeude-spezifischer Aufschlag (falls vorhanden und gueltig)
     * 2. Globaler Aufschlag fuer das Jahr
     * 
     * @param int|null $jahr Jahr (Standard: aktuelles Jahr)
     * @param Carbon|null $datum Datum fuer Gueltigkeitspruefung
     * @return float Aufschlag in Prozent
     */
    public function getAufschlagProzent(?int $jahr = null, ?Carbon $datum = null): float
    {
        $jahr = $jahr ?? now()->year;
        $datum = $datum ?? now();

        // 1. Pruefe gebaeude-spezifischen Aufschlag
        $gebaeudeAufschlag = GebaeudeAufschlag::fuerGebaeude($this->id)
            ->gueltig($datum)
            ->first();

        if ($gebaeudeAufschlag) {
            return (float) $gebaeudeAufschlag->prozent;
        }

        // 2. Fallback: Globaler Aufschlag
        return PreisAufschlag::getGlobalerAufschlag($jahr);
    }

    /**
     * Hat dieses Gebaeude einen individuellen Aufschlag?
     * 
     * @param Carbon|null $datum Datum fuer Gueltigkeitspruefung
     * @return bool
     */
    public function hatIndividuellenAufschlag(?Carbon $datum = null): bool
    {
        $datum = $datum ?? now();

        return GebaeudeAufschlag::fuerGebaeude($this->id)
            ->gueltig($datum)
            ->exists();
    }

    /**
     * Setzt einen individuellen Aufschlag fuer dieses Gebaeude.
     * 
     * @param float $prozent Aufschlag in %
     * @param string|null $grund Begruendung
     * @param Carbon|null $gueltigAb Ab wann gueltig (Standard: heute)
     * @param Carbon|null $gueltigBis Bis wann gueltig (NULL = unbegrenzt)
     * @return GebaeudeAufschlag
     */
    public function setAufschlag(
        float $prozent,
        ?string $grund = null,
        ?Carbon $gueltigAb = null,
        ?Carbon $gueltigBis = null
    ): GebaeudeAufschlag {
        // Alte Aufschlaege beenden (gueltig_bis auf gestern setzen)
        $gestern = now()->subDay();

        GebaeudeAufschlag::where('gebaeude_id', $this->id)
            ->whereNull('gueltig_bis')
            ->orWhere('gueltig_bis', '>', $gestern)
            ->update(['gueltig_bis' => $gestern]);

        // Neuen Aufschlag erstellen
        return GebaeudeAufschlag::create([
            'gebaeude_id' => $this->id,
            'prozent'     => $prozent,
            'grund'       => $grund,
            'gueltig_ab'  => $gueltigAb ?? now(),
            'gueltig_bis' => $gueltigBis,
        ]);
    }

    /**
     * Entfernt individuellen Aufschlag (nutzt dann wieder globalen).
     * 
     * @return void
     */
    public function entferneIndividuellenAufschlag(): void
    {
        GebaeudeAufschlag::where('gebaeude_id', $this->id)
            ->whereNull('gueltig_bis')
            ->orWhere('gueltig_bis', '>=', now())
            ->update(['gueltig_bis' => now()->subDay()]);
    }

    /**
     * Berechnet Artikelpreis MIT Aufschlag.
     * 
     * @param float $basispreis Original-Einzelpreis
     * @param int|null $jahr Jahr fuer Aufschlag
     * @return float Neuer Preis
     */
    public function berechnePreisMitAufschlag(float $basispreis, ?int $jahr = null): float
    {
        $prozent = $this->getAufschlagProzent($jahr);

        if ($prozent == 0) {
            return $basispreis;
        }

        $aufschlag = round($basispreis * ($prozent / 100), 2);
        return round($basispreis + $aufschlag, 2);
    }

    // ═══════════════════════════════════════════════════════════
    // 🏷️ SUMS & CALCULATIONS
    // ═══════════════════════════════════════════════════════════

    public function getArtikelSummeAttribute(): float
    {
        $sum = ArtikelGebaeude::where('gebaeude_id', $this->id)
            ->select(DB::raw('COALESCE(SUM(anzahl * einzelpreis),0) AS total'))
            ->value('total');

        return (float) $sum;
    }

    public function getArtikelSummeAktivAttribute(): float
    {
        return (float) ArtikelGebaeude::where('gebaeude_id', $this->id)
            ->where('aktiv', true)
            ->selectRaw('COALESCE(SUM(anzahl * einzelpreis), 0) as total')
            ->value('total');
    }

    /**
     * Artikel-Summe MIT Aufschlag
     */
    public function getArtikelSummeMitAufschlagAttribute(): float
    {
        $basis = $this->artikel_summe_aktiv;
        $prozent = $this->getAufschlagProzent();

        $aufschlag = round($basis * ($prozent / 100), 2);
        return round($basis + $aufschlag, 2);
    }

    // ═══════════════════════════════════════════════════════════
    // 📅 MONTH & CLEANING LOGIC
    // ═══════════════════════════════════════════════════════════

    public function isMonthActive(int $month): bool
    {
        $key = 'm' . str_pad((string) $month, 2, '0', STR_PAD_LEFT);
        return (int) ($this->{$key} ?? 0) === 1;
    }

    public function lastCleaningDate(): ?Carbon
    {
        $d = $this->timelines()->max('datum');
        return $d ? Carbon::parse($d) : null;
    }

    public function recomputeFaellig(?Carbon $today = null, bool $persist = true): bool
    {
        $today = $today ?: now();
        $monthActive = $this->isMonthActive((int) $today->month);

        if (!$monthActive) {
            if ($persist) {
                $this->update(['faellig' => 0]);
            }
            return false;
        }

        $last = $this->lastCleaningDate();
        $monthStart = $today->copy()->startOfMonth();

        $due = !$last || $last->lt($monthStart);

        if ($persist) {
            $this->update(['faellig' => $due ? 1 : 0]);
        }

        return $due;
    }

    public static function bulkRecomputeFaellig(?Carbon $today = null): int
    {
        $today = $today ?: now();
        $changed = 0;

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
                foreach ($chunk as $g) {
                    $new = $g->recomputeFaellig($today, false);
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

    // ═══════════════════════════════════════════════════════════
    // 🧾 RECHNUNG CREATION
    // ═══════════════════════════════════════════════════════════

    /**
     * Erstellt automatisch eine Rechnung aus diesem Gebaeude.
     * Preise werden automatisch mit Aufschlag berechnet.
     * 
     * @param array<string, mixed> $overrides Optionale Ueberschreibungen
     * @return Rechnung Die erstellte Rechnung im Status 'draft'
     */
    public function createRechnung(array $overrides = []): Rechnung
    {
        return Rechnung::createFromGebaeude($this, $overrides);
    }

    // ═══════════════════════════════════════════════════════════
    // 🔧 KUMULATIVER AUFSCHLAG
    // ═══════════════════════════════════════════════════════════

    /**
     * Berechnet kumulativen Aufschlag von basis_jahr bis ziel_jahr
     * 
     * Beispiel:
     * - basis_jahr = 2021, ziel_jahr = 2024
     * - 2022: +3%, 2023: +4%, 2024: +3%
     * - Kumulative Berechnung: 100 * 1.03 * 1.04 * 1.03 = 110.35
     * 
     * @param int $basisJahr Startjahr
     * @param int $zielJahr Endjahr
     * @return float Kumulativer Aufschlagsfaktor (z.B. 1.1035 = +10.35%)
     */
    public function getKumulativerAufschlagFaktor(int $basisJahr, int $zielJahr): float
    {
        // Wenn basis_jahr >= ziel_jahr -> kein Aufschlag
        if ($basisJahr >= $zielJahr) {
            return 1.0;
        }

        $faktor = 1.0;

        // Fuer jedes Jahr zwischen basis_jahr und ziel_jahr
        for ($jahr = $basisJahr + 1; $jahr <= $zielJahr; $jahr++) {
            $aufschlag = $this->getAufschlagProzent($jahr);

            // Faktor multiplizieren: z.B. 1.0 * 1.03 * 1.04 * 1.03
            $faktor *= (1 + $aufschlag / 100);
        }

        return $faktor;
    }

    /**
     * Berechnet Preis mit kumulativem Aufschlag
     * 
     * @param float $basisPreis Original-Preis
     * @param int $basisJahr Ab welchem Jahr gilt dieser Preis
     * @param int|null $zielJahr Bis zu welchem Jahr berechnen (default: aktuelles Jahr)
     * @return float Berechneter Preis
     */
    public function berechnePreisMitKumulativerErhoehung(
        float $basisPreis,
        int $basisJahr,
        ?int $zielJahr = null
    ): float {
        $zielJahr = $zielJahr ?? now()->year;

        $faktor = $this->getKumulativerAufschlagFaktor($basisJahr, $zielJahr);

        return round($basisPreis * $faktor, 2);
    }
}
