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

    protected $table = 'rechnungen';

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
        'typ_rechnung',
        
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

        // NEU: Aufschlag-Tracking
        'aufschlag_prozent',
        'aufschlag_typ',
    ];

    protected $casts = [
        'jahr'                => 'integer',
        'laufnummer'          => 'integer',
        'rechnungsdatum'      => 'date',
        'zahlungsziel'        => 'date',
        'bezahlt_am'          => 'date',
        'auftrag_datum'       => 'date',
        'netto_summe'         => 'decimal:2',
        'mwst_betrag'         => 'decimal:2',
        'brutto_summe'        => 'decimal:2',
        'ritenuta_betrag'     => 'decimal:2',
        'zahlbar_betrag'      => 'decimal:2',
        'mwst_satz'           => 'decimal:2',
        'ritenuta_prozent'    => 'decimal:2',
        'split_payment'       => 'boolean',
        'ritenuta'            => 'boolean',
        'aufschlag_prozent'   => 'decimal:2',
    ];

    // ═══════════════════════════════════════════════════════════
    // 🔗 RELATIONSHIPS
    // ═══════════════════════════════════════════════════════════

    public function gebaeude(): BelongsTo
    {
        return $this->belongsTo(Gebaeude::class);
    }

    public function rechnungsempfaenger(): BelongsTo
    {
        return $this->belongsTo(Adresse::class, 'rechnungsempfaenger_id');
    }

    public function postadresse(): BelongsTo
    {
        return $this->belongsTo(Adresse::class, 'postadresse_id');
    }

    public function fatturaProfile(): BelongsTo
    {
        return $this->belongsTo(FatturaProfile::class, 'fattura_profile_id');
    }

    public function positionen(): HasMany
    {
        return $this->hasMany(RechnungPosition::class)
            ->orderBy('position');
    }

    // ═══════════════════════════════════════════════════════════
    // 🧮 BERECHNUNG
    // ═══════════════════════════════════════════════════════════

    /**
     * Berechnet alle Summen neu (aus den Positionen).
     * Berücksichtigt automatisch Ritenuta d'acconto bei aktiviertem Flag.
     */
    public function recalculate(): void
    {
        // Summen aus Positionen
        $netto = (float) $this->positionen->sum('netto_gesamt');
        $mwst  = (float) $this->positionen->sum('mwst_betrag');
        $brutto = (float) $this->positionen->sum('brutto_gesamt');

        // Ritenuta d'acconto (4% vom Netto, falls aktiviert)
        $ritenuta = 0.0;
        if ($this->ritenuta && $this->ritenuta_prozent > 0) {
            $ritenuta = round($netto * ((float) $this->ritenuta_prozent / 100), 2);
        }

        // Zahlbar = Brutto - Ritenuta
        $zahlbar = round($brutto - $ritenuta, 2);

        // Speichern
        $this->update([
            'netto_summe'    => $netto,
            'mwst_betrag'    => $mwst,
            'brutto_summe'   => $brutto,
            'ritenuta_betrag' => $ritenuta,
            'zahlbar_betrag' => $zahlbar,
        ]);
    }

    // ═══════════════════════════════════════════════════════════
    // 🏭 FACTORY: Rechnung aus Gebäude erstellen
    // ═══════════════════════════════════════════════════════════

    /**
     * Erstellt eine Rechnung aus einem Gebäude.
     * 
     * Features:
     * - Kopiert Snapshots von Gebäude, Adressen, FatturaPA-Profil
     * - Übernimmt aktive Artikel als Positionen
     * - ⭐ WICHTIG: Wendet automatisch Preis-Aufschlag auf Einzelpreise an
     * - Markiert Timeline-Einträge als verrechnet
     * - Berechnet Leistungsdaten aus Timeline
     * 
     * @param Gebaeude $gebaeude
     * @param array $overrides Optionale Überschreibungen
     * @return self
     */
    public static function createFromGebaeude(Gebaeude $gebaeude, array $overrides = []): self
    {
        // Jahr / Laufnummer ermitteln (mit Lock)
        $jahr = now()->year;

        $laufnummer = DB::transaction(function () use ($jahr) {
            $maxLaufnummer = (int) self::where('jahr', $jahr)
                ->lockForUpdate()
                ->max('laufnummer');
            return $maxLaufnummer + 1;
        });

        // Zugeordnete Adressen / Profile
        $rechnungsempfaenger = $gebaeude->rechnungsempfaenger;
        $postadresse         = $gebaeude->postadresse;
        $profile             = $gebaeude->fatturaProfile;

        // ═══════════════════════════════════════════════════════════
        // 🕒 Timeline-Einträge verarbeiten
        // ═══════════════════════════════════════════════════════════
        
        $timelineEintraege = \App\Models\Timeline::where('gebaeude_id', $gebaeude->id)
            ->where('verrechnen', true)
            ->whereNull('deleted_at')
            ->orderBy('datum')
            ->get();

        $leistungsdaten = self::formatLeistungsdaten($timelineEintraege, $jahr);

        // ═══════════════════════════════════════════════════════════
        // 💰 PREIS-AUFSCHLAG ERMITTELN
        // ═══════════════════════════════════════════════════════════
        
        $aufschlagProzent = $gebaeude->getAufschlagProzent($jahr);
        $aufschlagTyp = $gebaeude->hatIndividuellenAufschlag() ? 'individuell' : 'global';

        \Log::info('Rechnung erstellen - Aufschlag', [
            'gebaeude_id'      => $gebaeude->id,
            'aufschlag_prozent' => $aufschlagProzent,
            'aufschlag_typ'    => $aufschlagTyp,
        ]);

        // ═══════════════════════════════════════════════════════════
        // 📋 RECHNUNG ERSTELLEN (Snapshot)
        // ═══════════════════════════════════════════════════════════

        $rechnung = new self(array_merge([
            'jahr'                    => $jahr,
            'laufnummer'              => $laufnummer,
            'gebaeude_id'             => $gebaeude->id,
            'rechnungsempfaenger_id'  => $rechnungsempfaenger->id,
            'postadresse_id'          => $postadresse->id,
            'fattura_profile_id'      => $gebaeude->fattura_profile_id,
            'rechnungsdatum'          => now(),
            'leistungsdaten'          => $leistungsdaten,
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

            // FatturaPA
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

            // Aufschlag-Tracking
            'aufschlag_prozent'       => $aufschlagProzent,
            'aufschlag_typ'           => $aufschlagTyp,
        ], $overrides));

        $rechnung->save();

        // ═══════════════════════════════════════════════════════════
        // 📦 POSITIONEN ERSTELLEN (mit angepassten Preisen)
        // ═══════════════════════════════════════════════════════════

        $artikelListe = $gebaeude->aktiveArtikel()
            ->orderBy('reihenfolge')
            ->get();

        $position = 1;

        foreach ($artikelListe as $artikel) {
            $mwstSatz = $profile?->mwst_satz ?? 22.00;
            
            // ⭐ HIER: Preis mit Aufschlag berechnen
            $originalPreis = (float) $artikel->einzelpreis;
            $einzelpreisAngepasst = $originalPreis;
            
            if ($aufschlagProzent != 0) {
                $aufschlagBetrag = round($originalPreis * ($aufschlagProzent / 100), 2);
                $einzelpreisAngepasst = round($originalPreis + $aufschlagBetrag, 2);
                
                \Log::debug('Preis angepasst', [
                    'artikel'         => $artikel->beschreibung,
                    'original'        => $originalPreis,
                    'aufschlag'       => $aufschlagBetrag,
                    'neu'             => $einzelpreisAngepasst,
                    'prozent'         => $aufschlagProzent,
                ]);
            }

            $rechnung->positionen()->create([
                'position'             => $position++,
                'beschreibung'         => $artikel->beschreibung,
                'anzahl'               => $artikel->anzahl,
                'einheit'              => 'Stk',
                'einzelpreis'          => $einzelpreisAngepasst, // ⭐ Angepasster Preis
                'mwst_satz'            => $mwstSatz,
                'artikel_gebaeude_id'  => $artikel->id,
            ]);
        }

        // Abschließende Neuberechnung aller Summen
        $rechnung->recalculate();

        // ═══════════════════════════════════════════════════════════
        // 🕒 Timeline-Einträge als verrechnet markieren
        // ═══════════════════════════════════════════════════════════
        
        if ($timelineEintraege->isNotEmpty()) {
            $rechnungNummer = sprintf('%d/%04d', $rechnung->jahr, $rechnung->laufnummer);
            
            foreach ($timelineEintraege as $timeline) {
                $timeline->update([
                    'verrechnen'                => false,
                    'verrechnet_am'             => now()->toDateString(),
                    'verrechnet_mit_rn_nummer'  => $rechnungNummer,
                ]);
            }
            
            \Log::info('Timeline-Einträge als verrechnet markiert', [
                'rechnung_id'     => $rechnung->id,
                'rechnung_nummer' => $rechnungNummer,
                'anzahl_eintraege' => $timelineEintraege->count(),
            ]);
        }

        return $rechnung;
    }

    /**
     * Formatiert Timeline-Einträge zu einem Leistungsdaten-String.
     */
    protected static function formatLeistungsdaten($timelineEintraege, ?int $jahr = null): string
    {
        if ($timelineEintraege->isEmpty()) {
            $jahr = $jahr ?? now()->year;
            return "Jahr/anno {$jahr}";
        }

        $daten = $timelineEintraege
            ->pluck('datum')
            ->map(fn($datum) => \Carbon\Carbon::parse($datum))
            ->sort()
            ->unique()
            ->values();

        if ($daten->count() === 1) {
            return $daten->first()->format('d.m.Y');
        }

        $erstesDatum = $daten->first();
        $letztesDatum = $daten->last();
        $differenzTage = $erstesDatum->diffInDays($letztesDatum);

        if ($differenzTage <= 7 && $daten->count() >= 3) {
            return sprintf(
                '%s - %s',
                $erstesDatum->format('d.m.Y'),
                $letztesDatum->format('d.m.Y')
            );
        }

        if ($daten->count() > 10) {
            $gezeigt = $daten->take(10)
                ->map(fn($d) => $d->format('d.m.Y'))
                ->join(', ');
            return $gezeigt . ' ...';
        }

        return $daten
            ->map(fn($d) => $d->format('d.m.Y'))
            ->join(', ');
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

    // ═══════════════════════════════════════════════════════════
    // 🏷️ ACCESSORS
    // ═══════════════════════════════════════════════════════════

    /**
     * Formatierte Rechnungsnummer (z.B. "2025/0042")
     */
    public function getRechnungsnummerAttribute(): string
    {
        return sprintf('%d/%04d', $this->jahr, $this->laufnummer);
    }

    /**
     * Status-Badge für UI
     */
    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'draft'     => '<span class="badge bg-secondary">Entwurf</span>',
            'sent'      => '<span class="badge bg-info">Versendet</span>',
            'paid'      => '<span class="badge bg-success">Bezahlt</span>',
            'cancelled' => '<span class="badge bg-danger">Storniert</span>',
            default     => '<span class="badge bg-light text-dark">' . $this->status . '</span>',
        };
    }

    /**
     * Aufschlag-Info für UI
     */
    public function getAufschlagInfoAttribute(): string
    {
        if (!$this->aufschlag_prozent || $this->aufschlag_prozent == 0) {
            return 'Kein Aufschlag';
        }

        $typ = $this->aufschlag_typ === 'individuell' ? 'Individuell' : 'Global';
        return sprintf('%s: %+.2f%%', $typ, $this->aufschlag_prozent);
    }
}