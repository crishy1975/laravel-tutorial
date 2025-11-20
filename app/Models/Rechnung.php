<?php
// app/Models/Rechnung.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Gebaeude;
use App\Models\ArtikelGebaeude;
use App\Models\Adresse;
use App\Models\FatturaProfile;
use App\Models\RechnungPosition;

class Rechnung extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Expliziter Tabellenname.
     *
     * @var string
     */
    protected $table = 'rechnungen';

    /**
     * Mass-Assignable Felder.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'jahr',
        'laufnummer',
        'gebaeude_id',
        'rechnungsempfaenger_id',
        'postadresse_id',
        'fattura_profile_id',

        // Snapshot Rechnungsempfänger
        're_name',
        're_strasse',
        're_hausnummer',
        're_plz',
        're_wohnort',
        're_provinz',
        're_land',
        're_steuernummer',
        're_mwst_nummer',
        're_codice_univoco',
        're_pec',

        // Snapshot Postadresse
        'post_name',
        'post_strasse',
        'post_hausnummer',
        'post_plz',
        'post_wohnort',
        'post_provinz',
        'post_land',
        'post_email',
        'post_pec',

        // Snapshot Gebäude
        'geb_codex',
        'geb_name',
        'geb_adresse',

        // Datumsfelder
        'rechnungsdatum',
        'leistungsdaten',
        'zahlungsziel',
        'bezahlt_am',

        // Beträge
        'netto_summe',
        'mwst_betrag',
        'brutto_summe',
        'ritenuta_betrag',
        'zahlbar_betrag',

        // Status & Flags
        'status',

        // Snapshot Profil
        'profile_bezeichnung',
        'mwst_satz',
        'split_payment',
        'ritenuta',
        'ritenuta_prozent',

        // FatturaPA
        'cup',
        'cig',
        'auftrag_id',
        'auftrag_datum',

        // Texte
        'bemerkung',
        'bemerkung_kunde',
        'zahlungsbedingungen',

        // Dateipfade
        'pdf_pfad',
        'xml_pfad',
        'externe_referenz',
    ];

    /**
     * Attribut-Casts.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'rechnungsdatum'   => 'date',
        'zahlungsziel'     => 'date',
        'bezahlt_am'       => 'date',
        'auftrag_datum'    => 'date',

        'netto_summe'      => 'decimal:2',
        'mwst_betrag'      => 'decimal:2',
        'brutto_summe'     => 'decimal:2',
        'ritenuta_betrag'  => 'decimal:2',
        'zahlbar_betrag'   => 'decimal:2',
        'mwst_satz'        => 'decimal:2',
        'ritenuta_prozent' => 'decimal:2',

        'split_payment'    => 'boolean',
        'ritenuta'         => 'boolean',
    ];

    // ═══════════════════════════════════════════════════════════
    // 🔗 RELATIONSHIPS
    // ═══════════════════════════════════════════════════════════

    /**
     * Zugehöriges Gebäude.
     */
    public function gebaeude(): BelongsTo
    {
        return $this->belongsTo(Gebaeude::class);
    }

    /**
     * Rechnungsempfänger-Adresse.
     */
    public function rechnungsempfaenger(): BelongsTo
    {
        return $this->belongsTo(Adresse::class, 'rechnungsempfaenger_id');
    }

    /**
     * Postadresse für den Versand.
     */
    public function postadresse(): BelongsTo
    {
        return $this->belongsTo(Adresse::class, 'postadresse_id');
    }

    /**
     * Fattura-Profil.
     */
    public function fatturaProfile(): BelongsTo
    {
        return $this->belongsTo(FatturaProfile::class);
    }

    /**
     * Einzelne Rechnungspositionen.
     *
     * @return HasMany<RechnungPosition>
     */
    public function positionen(): HasMany
    {
        // Positionen automatisch nach 'position' sortiert zurückgeben
        return $this->hasMany(RechnungPosition::class)->orderBy('position');
    }

    // ═══════════════════════════════════════════════════════════
    // 🏷️ ACCESSORS
    // ═══════════════════════════════════════════════════════════

    /**
     * Formatierte Rechnungsnummer: "2025/0042".
     */
    public function getNummernAttribute(): string
    {
        return sprintf('%d_%04d', $this->jahr, $this->laufnummer);
    }

    /**
     * Ist die Rechnung überfällig?
     */
    public function getIstUeberfaelligAttribute(): bool
    {
        return $this->status !== 'paid'
            && $this->status !== 'cancelled'
            && $this->zahlungsziel
            && Carbon::parse($this->zahlungsziel)->isPast();
    }

    /**
     * Ist die Rechnung editierbar? (nur im Status "draft").
     */
    public function getIstEditierbarAttribute(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Status-Badge für UI.
     */
    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'draft'     => '<span class="badge bg-secondary">Entwurf</span>',
            'sent'      => '<span class="badge bg-primary">Versendet</span>',
            'paid'      => '<span class="badge bg-success">Bezahlt</span>',
            'cancelled' => '<span class="badge bg-danger">Storniert</span>',
            'overdue'   => '<span class="badge bg-warning text-dark">Überfällig</span>',
            default     => '<span class="badge bg-light text-dark">Unbekannt</span>',
        };
    }

    // ═══════════════════════════════════════════════════════════
    // 🧮 BERECHNUNGEN
    // ═══════════════════════════════════════════════════════════

    /**
     * Berechnet alle Summen neu aus den Positionen
     * und speichert sie in der Datenbank.
     */
    public function recalculate(): void
    {
        $positionen = $this->positionen;

        // Netto = Summe aller Netto-Gesamtbeträge
        $this->netto_summe = $positionen->sum('netto_gesamt');

        // MwSt-Betrag = Summe aller MwSt-Beträge
        $this->mwst_betrag = $positionen->sum('mwst_betrag');

        // Brutto = Netto + MwSt
        $this->brutto_summe = $this->netto_summe + $this->mwst_betrag;

        // Ritenuta nur, wenn aktiviert und Prozentsatz > 0
        if ($this->ritenuta && $this->ritenuta_prozent > 0) {
            $this->ritenuta_betrag = round(
                $this->netto_summe * ($this->ritenuta_prozent / 100),
                2
            );
        } else {
            $this->ritenuta_betrag = 0;
        }

        // Zahlbar = Brutto - Ritenuta
        $this->zahlbar_betrag = $this->brutto_summe - $this->ritenuta_betrag;

        $this->save();
    }

    // ═══════════════════════════════════════════════════════════
    // 🏗️ FACTORY METHODS
    // ═══════════════════════════════════════════════════════════

    /**
     * Erstellt eine neue Rechnung aus einem Gebäude
     * (übernimmt Artikel, Adresse, Profil).
     * 
     * @param Gebaeude $gebaeude                Das Gebäude, aus dem die Rechnung erzeugt wird.
     * @param array<string, mixed> $overrides   Optional: Felder überschreiben (z.B. Datum).
     * @return self
     */
    public static function createFromGebaeude(Gebaeude $gebaeude, array $overrides = []): self
    {
        // Neues Jahr / Laufnummer ermitteln (mit Lock gegen Race Conditions)
        $jahr = now()->year;

        // WICHTIG: Laufnummer mit DB-Lock ermitteln, um Duplikate zu vermeiden
        $laufnummer = DB::transaction(function () use ($jahr) {
            $maxLaufnummer = (int) self::where('jahr', $jahr)
                ->lockForUpdate()
                ->max('laufnummer');
            return $maxLaufnummer + 1;
        });

        // Zugeordnete Adressen / Profile aus dem Gebäude
        $rechnungsempfaenger = $gebaeude->rechnungsempfaenger;
        $postadresse         = $gebaeude->postadresse;
        $profile             = $gebaeude->fatturaProfile;

        // Basisdaten für die neue Rechnung (Snapshot der aktuellen Daten)
        $rechnung = new self(array_merge([
            'jahr'                    => $jahr,
            'laufnummer'              => $laufnummer,
            'gebaeude_id'             => $gebaeude->id,
            'rechnungsempfaenger_id'  => $rechnungsempfaenger->id,
            'postadresse_id'          => $postadresse->id,
            'fattura_profile_id'      => $gebaeude->fattura_profile_id,
            'rechnungsdatum'          => now(),
            // NEU: Leistungsdaten als String, z.B. identisch mit dem Rechnungsdatum
            'leistungsdaten'          => now()->toDateString(),
            'zahlungsziel'            => now()->addDays(30),
            'status'                  => 'draft',

            // Snapshot Rechnungsempfänger
            're_name'                 => $rechnungsempfaenger->name,
            're_strasse'              => $rechnungsempfaenger->strasse,
            're_hausnummer'           => $rechnungsempfaenger->hausnummer,
            're_plz'                  => $rechnungsempfaenger->plz,
            're_wohnort'              => $rechnungsempfaenger->wohnort,
            're_provinz'              => $rechnungsempfaenger->provinz,
            're_land'                 => $rechnungsempfaenger->land,
            're_steuernummer'         => $rechnungsempfaenger->steuernummer,
            're_mwst_nummer'          => $rechnungsempfaenger->mwst_nummer,
            're_codice_univoco'       => $rechnungsempfaenger->codice_univoco,
            're_pec'                  => $rechnungsempfaenger->pec,

            // Snapshot Postadresse
            'post_name'               => $postadresse->name,
            'post_strasse'            => $postadresse->strasse,
            'post_hausnummer'         => $postadresse->hausnummer,
            'post_plz'                => $postadresse->plz,
            'post_wohnort'            => $postadresse->wohnort,
            'post_provinz'            => $postadresse->provinz,
            'post_land'               => $postadresse->land,
            'post_email'              => $postadresse->email,
            'post_pec'                => $postadresse->pec,

            // Snapshot Gebäude
            'geb_codex'               => $gebaeude->codex,
            'geb_name'                => $gebaeude->gebaeude_name,
            'geb_adresse'             => sprintf(
                '%s %s, %s %s',
                $gebaeude->strasse,
                $gebaeude->hausnummer,
                $gebaeude->plz,
                $gebaeude->wohnort
            ),

            // FatturaPA aus Gebäude
            'cup'                     => $gebaeude->cup,
            'cig'                     => $gebaeude->cig,
            'auftrag_id'              => $gebaeude->auftrag_id,
            'auftrag_datum'           => $gebaeude->auftrag_datum,

            // Profil-Einstellungen (Snapshot)
            'profile_bezeichnung'     => $profile?->bezeichnung,
            'mwst_satz'               => $profile?->mwst_satz ?? 22.00,
            'split_payment'           => $profile?->split_payment ?? false,
            'ritenuta'                => $profile?->ritenuta ?? false,
            'ritenuta_prozent'        => $profile?->ritenuta ? 4.00 : null,
        ], $overrides));

        // Rechnung speichern, damit wir eine ID für Positionen haben
        $rechnung->save();

        // Artikel übernehmen (nur aktive)
        $artikelListe = $gebaeude->aktiveArtikel()->orderBy('reihenfolge')->get();

        // Netto-Summe der Artikel für spätere Aufschläge
        $artikelNettoSumme = $artikelListe->reduce(
            /**
             * @param float           $summe
             * @param ArtikelGebaeude $artikel
             */
            fn(float $summe, ArtikelGebaeude $artikel) =>
            $summe + ((float) $artikel->anzahl * (float) $artikel->einzelpreis),
            0.0
        );

        $position = 1;

        // Normale Artikelpositionen anlegen
        foreach ($artikelListe as $artikel) {
            $mwstSatz = $profile?->mwst_satz ?? 22.00;

            $rechnung->positionen()->create([
                'position'             => $position++,
                'beschreibung'         => $artikel->beschreibung,
                'anzahl'               => $artikel->anzahl,
                'einheit'              => 'Stk',
                'einzelpreis'          => $artikel->einzelpreis,
                'mwst_satz'            => $mwstSatz,
                'artikel_gebaeude_id'  => $artikel->id,
            ]);
        }

        // Aktive Preisaufschläge als zusätzliche Positionen hinzufügen
        $aufschlaege = $gebaeude->aktivePreisAufschlaege()->get();

        foreach ($aufschlaege as $aufschlag) {
            $mwstSatz = $profile?->mwst_satz ?? 22.00;
            $betrag   = $aufschlag->berechneBetrag($artikelNettoSumme);

            $beschreibung = $aufschlag->bezeichnung;
            if ($aufschlag->istProzentual()) {
                $beschreibung .= sprintf(' (%.2f%%)', $aufschlag->wert);
            }

            $rechnung->positionen()->create([
                'position'     => $position++,
                'beschreibung' => $beschreibung,
                'anzahl'       => 1,
                'einheit'      => 'Stk',
                'einzelpreis'  => $betrag,
                'mwst_satz'    => $mwstSatz,
            ]);
        }

        // Abschließende Neuberechnung aller Summen
        $rechnung->recalculate();

        return $rechnung;
    }

    // ═══════════════════════════════════════════════════════════
    // 🎯 SCOPES
    // ═══════════════════════════════════════════════════════════

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', '!=', 'paid')
            ->where('status', '!=', 'cancelled')
            ->where('zahlungsziel', '<', now());
    }

    public function scopeYear($query, int $year)
    {
        return $query->where('jahr', $year);
    }
}
