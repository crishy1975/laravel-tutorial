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
use App\Enums\Zahlungsbedingung;  // ← NEU

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
        'fattura_causale',

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
        'codice_commessa',
        'auftrag_id',
        'auftrag_datum',

        // NEU: Aufschlag-Tracking
        'aufschlag_prozent',
        'aufschlag_typ',

        // Sonstige
        'bemerkung',
        'bemerkung_kunde',
        'zahlungsbedingungen',  // ← NEU
        'pdf_pfad',
        'xml_pfad',
        'externe_referenz',
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
        'zahlungsbedingungen' => Zahlungsbedingung::class,  // ← NEU
    ];

    // ═══════════════════════════════════════════════════════════
    // 🔗 RELATIONSHIPS
    // ═══════════════════════════════════════════════════════════


    // ─────────────────────────────────────────────────────────────────────────
    // SCHRITT 2: Boot Method (nach $casts einfügen)
    // ─────────────────────────────────────────────────────────────────────────
    protected static function boot()
    {
        parent::boot();

        // Bei Erstellen: Automatische Causale generieren (falls leer)
        static::creating(function ($rechnung) {
            if (!$rechnung->fattura_causale) {
                $rechnung->fattura_causale = static::generateCausaleStatic($rechnung);
            }
        });

        // Ritenuta automatisch setzen (bestehende Logik)
        static::saving(function ($rechnung) {
            if ($rechnung->ritenuta) {
                if (!$rechnung->ritenuta_prozent || $rechnung->ritenuta_prozent == 0) {
                    $rechnung->ritenuta_prozent = 4.00;
                }
            }
        });
    }

// ─────────────────────────────────────────────────────────────────────────
// SCHRITT 3: Statische Methoden (am Ende der Klasse einfügen)
// ─────────────────────────────────────────────────────────────────────────

    /**
     * ⭐ Generiert Causale statisch (ULTRA-KOMPAKT)
     * 
     * Format:
     * Zeitraum/Periodo: Jahr/anno 2025 - Objekt/Oggetto: Name, Adresse
     * 
     * Beispiel:
     * Zeitraum/Periodo: Jahr/anno 2025 - Objekt/Oggetto: Cond. Romana, Fuchserstr. 2, 39055 Laives
     * 
     * @param Rechnung|object $rechnung
     * @return string|null
     */
    public static function generateCausaleStatic($rechnung): ?string
    {
        $teile = [];

        // 1. Leistungszeitraum (falls vorhanden)
        if ($rechnung->leistungsdaten ?? null) {
            $teile[] = sprintf(
                'Zeitraum/Periodo: %s',
                $rechnung->leistungsdaten
            );
        }

        // 2. Gebäude-Info (kompakt: Objekt/Oggetto mit Komma)
        $name = $rechnung->geb_name ?? null;
        $adresse = $rechnung->geb_adresse ?? null;

        if ($name && $adresse) {
            // Name + Adresse mit Komma getrennt
            $teile[] = sprintf(
                'Objekt/Oggetto: %s, %s',
                $name,
                $adresse
            );
        } elseif ($adresse) {
            // Nur Adresse
            $teile[] = sprintf(
                'Objekt/Oggetto: %s',
                $adresse
            );
        } elseif ($name) {
            // Nur Name
            $teile[] = sprintf(
                'Objekt/Oggetto: %s',
                $name
            );
        }

        // Zusammenfügen mit Separator " - "
        $causale = implode(' - ', $teile);

        // Max 200 Zeichen (SDI-Limit)
        return substr($causale, 0, 200) ?: null;
    }

    /**
     * Regeneriert die Causale basierend auf aktuellen Daten
     */
    public function regenerateCausale(): void
    {
        $this->fattura_causale = static::generateCausaleStatic($this);
        $this->save();
    }

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
     * - ⭐ WICHTIG: Wendet KUMULATIV alle Preis-Aufschläge seit Basisjahr an
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
        // 💰 PREIS-AUFSCHLAG ERMITTELN (KUMULATIV)
        // ═══════════════════════════════════════════════════════════

        // Basisjahr für Preise (erstes Jahr mit Aufschlag)
        $basisJahr = \App\Models\PreisAufschlag::min('jahr') ?? $jahr;

        // Alle Aufschläge vom Basisjahr bis zum aktuellen Jahr sammeln
        $alleAufschlaege = \App\Models\PreisAufschlag::where('jahr', '>=', $basisJahr)
            ->where('jahr', '<=', $jahr)
            ->orderBy('jahr')
            ->get();

        // Gebäude-spezifischen Aufschlag für aktuelles Jahr prüfen
        $gebaeudeAufschlag = \App\Models\GebaeudeAufschlag::fuerGebaeude($gebaeude->id)
            ->gueltig(now())
            ->first();

        // Aufschlag-Tracking
        $aufschlagProzent = 0.0;
        $aufschlagTyp = 'keiner';

        if ($gebaeudeAufschlag) {
            // Individueller Aufschlag (überschreibt globale Aufschläge)
            $aufschlagProzent = (float) $gebaeudeAufschlag->prozent;
            $aufschlagTyp = 'individuell';

            \Log::info('Individueller Aufschlag verwendet', [
                'gebaeude_id' => $gebaeude->id,
                'prozent'     => $aufschlagProzent,
            ]);
        } elseif ($alleAufschlaege->isNotEmpty()) {
            // KUMULATIVER AUFSCHLAG: Alle Jahre multiplizieren
            $faktor = 1.0;
            foreach ($alleAufschlaege as $aufschlag) {
                $faktor *= (1 + ((float) $aufschlag->prozent / 100));
            }

            // Gesamtprozent berechnen: (Faktor - 1) * 100
            $aufschlagProzent = round(($faktor - 1) * 100, 2);
            $aufschlagTyp = 'global';

            \Log::info('Kumulativer Aufschlag berechnet', [
                'gebaeude_id' => $gebaeude->id,
                'jahre'       => $alleAufschlaege->pluck('jahr')->toArray(),
                'faktor'      => $faktor,
                'prozent'     => $aufschlagProzent,
            ]);
        }

        // ═══════════════════════════════════════════════════════════
        // 📄 RECHNUNG ERSTELLEN
        // ═══════════════════════════════════════════════════════════

        $rechnung = new self(array_merge([
            'jahr'                    => $jahr,
            'laufnummer'              => $laufnummer,
            'gebaeude_id'             => $gebaeude->id,
            'rechnungsempfaenger_id'  => $gebaeude->rechnungsempfaenger_id,
            'postadresse_id'          => $gebaeude->postadresse_id,
            'fattura_profile_id'      => $gebaeude->fattura_profile_id,

            // Datumsfelder
            'rechnungsdatum'          => now()->toDateString(),
            'leistungsdaten'          => $leistungsdaten,
            'zahlungsziel'            => now()->addDays(30)->toDateString(),

            // Status
            'status'                  => 'draft',
            'typ_rechnung'            => 'rechnung',

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
            'codice_commessa'        => $gebaeude->codice_commessa,
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

            // ⭐ HIER: Preis mit kumulativem Aufschlag berechnen
            $originalPreis = (float) $artikel->einzelpreis;
            $einzelpreisAngepasst = $originalPreis;

            if ($aufschlagProzent != 0) {
                $aufschlagBetrag = round($originalPreis * ($aufschlagProzent / 100), 2);
                $einzelpreisAngepasst = round($originalPreis + $aufschlagBetrag, 2);

                \Log::debug('Preis angepasst (kumulativ)', [
                    'artikel'         => $artikel->beschreibung,
                    'original'        => $originalPreis,
                    'aufschlag'       => $aufschlagBetrag,
                    'neu'             => $einzelpreisAngepasst,
                    'prozent'         => $aufschlagProzent,
                    'jahre'           => $alleAufschlaege->pluck('jahr')->toArray(),
                ]);
            }

            $rechnung->positionen()->create([
                'position'             => $position++,
                'beschreibung'         => $artikel->beschreibung,
                'anzahl'               => $artikel->anzahl,
                'einheit'              => 'Stk',
                'einzelpreis'          => $einzelpreisAngepasst, // ⭐ Angepasster Preis (kumulativ)
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
        return match ($this->status) {
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

    /**
     * Prüft, ob die Rechnung editierbar ist.
     * Nur Rechnungen mit Status 'draft' können bearbeitet werden.
     * 
     * @return bool
     */
    public function getIstEditierbarAttribute(): bool
    {
        return $this->status === 'draft';
    }

    // ═══════════════════════════════════════════════════════════
    // 💰 ZAHLUNGSBEDINGUNG & FÄLLIGKEIT
    // ═══════════════════════════════════════════════════════════

    /**
     * Zahlungsbedingung als deutschen Text.
     * 
     * @return string
     */
    public function getZahlungsbedingungenLabelAttribute(): string
    {
        return $this->zahlungsbedingungen?->label() ?? 'Nicht gesetzt';
    }

    /**
     * Anzahl Tage der Zahlungsbedingung.
     * 
     * @return int
     */
    public function getZahlungsbedingungenTageAttribute(): int
    {
        return $this->zahlungsbedingungen?->tage() ?? 30;
    }

    /**
     * Badge für Zahlungsbedingung (für UI).
     * 
     * @return string HTML Badge
     */
    public function getZahlungsbedingungenBadgeAttribute(): string
    {
        if (!$this->zahlungsbedingungen) {
            return '<span class="badge bg-secondary">Nicht gesetzt</span>';
        }

        $class = $this->zahlungsbedingungen->badgeClass();
        $label = $this->zahlungsbedingungen->label();

        return "<span class=\"badge {$class}\">{$label}</span>";
    }

    /**
     * Ist die Rechnung bereits als bezahlt markiert?
     * 
     * @return bool
     */
    public function istAlsBezahltMarkiert(): bool
    {
        return $this->zahlungsbedingungen === Zahlungsbedingung::BEZAHLT;
    }

    /**
     * Berechnet das Fälligkeitsdatum basierend auf Zahlungsbedingung.
     * 
     * Falls bereits ein zahlungsziel gesetzt ist, wird dieses verwendet.
     * Ansonsten: rechnungsdatum + Zahlungsbedingung-Tage.
     * 
     * @return Carbon|null
     */
    public function getFaelligkeitsdatumAttribute(): ?Carbon
    {
        // Falls manuell gesetzt
        if ($this->zahlungsziel) {
            return $this->zahlungsziel;
        }

        // Falls kein Rechnungsdatum
        if (!$this->rechnungsdatum) {
            return null;
        }

        // Berechne aus Zahlungsbedingung
        $tage = $this->zahlungsbedingungen_tage;

        return $this->rechnungsdatum->copy()->addDays($tage);
    }

    /**
     * Ist die Rechnung überfällig?
     * 
     * @return bool
     */
    public function istUeberfaellig(): bool
    {
        // Bereits bezahlt? → Nicht überfällig
        if ($this->istAlsBezahltMarkiert()) {
            return false;
        }

        // Status 'paid' → Nicht überfällig
        if ($this->status === 'paid') {
            return false;
        }

        $faelligkeit = $this->faelligkeitsdatum;

        if (!$faelligkeit) {
            return false;
        }

        return $faelligkeit->isPast();
    }

    /**
     * Tage bis Fälligkeit (negativ = überfällig).
     * 
     * @return int|null
     */
    public function getTagebisFaelligkeitAttribute(): ?int
    {
        $faelligkeit = $this->faelligkeitsdatum;

        if (!$faelligkeit) {
            return null;
        }

        return now()->startOfDay()->diffInDays($faelligkeit->startOfDay(), false);
    }

    /**
     * Fälligkeits-Status als Badge.
     * 
     * @return string HTML Badge
     */
    public function getFaelligkeitsStatusBadgeAttribute(): string
    {
        if ($this->istAlsBezahltMarkiert() || $this->status === 'paid') {
            return '<span class="badge bg-success"><i class="bi bi-check-circle"></i> Bezahlt</span>';
        }

        if ($this->status === 'cancelled') {
            return '<span class="badge bg-danger"><i class="bi bi-x-circle"></i> Storniert</span>';
        }

        if ($this->istUeberfaellig()) {
            $tage = abs($this->tage_bis_faelligkeit);
            return "<span class=\"badge bg-danger\"><i class=\"bi bi-exclamation-triangle\"></i> Überfällig ({$tage} Tage)</span>";
        }

        $tage = $this->tage_bis_faelligkeit;

        if ($tage === null) {
            return '<span class="badge bg-secondary">Keine Fälligkeit</span>';
        }

        if ($tage <= 7) {
            return "<span class=\"badge bg-warning text-dark\"><i class=\"bi bi-clock\"></i> Fällig in {$tage} Tagen</span>";
        }

        return "<span class=\"badge bg-info\"><i class=\"bi bi-calendar\"></i> Fällig in {$tage} Tagen</span>";
    }

    /**
     * Markiert Rechnung als bezahlt.
     * 
     * @param Carbon|null $bezahltAm
     * @return void
     */
    public function markiereAlsBezahlt(?Carbon $bezahltAm = null): void
    {
        $this->zahlungsbedingungen = Zahlungsbedingung::BEZAHLT;
        $this->status = 'paid';
        $this->bezahlt_am = $bezahltAm ?? now();
        $this->save();
    }

    // ═══════════════════════════════════════════════════════════
    // 📊 ZUSÄTZLICHE SCOPES FÜR ZAHLUNGSBEDINGUNG
    // ═══════════════════════════════════════════════════════════

    /**
     * Scope: Nur bezahlte Rechnungen.
     */
    public function scopeBezahlt($query)
    {
        return $query->where(function ($q) {
            $q->where('status', 'paid')
                ->orWhere('zahlungsbedingungen', Zahlungsbedingung::BEZAHLT->value);
        });
    }

    /**
     * Scope: Nur unbezahlte Rechnungen.
     */
    public function scopeUnbezahlt($query)
    {
        return $query->where('status', '!=', 'paid')
            ->where(function ($q) {
                $q->whereNull('zahlungsbedingungen')
                    ->orWhere('zahlungsbedingungen', '!=', Zahlungsbedingung::BEZAHLT->value);
            });
    }

    /**
     * Scope: Überfällige Rechnungen.
     */
    public function scopeUeberfaellig($query)
    {
        return $query->where('status', '!=', 'paid')
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) {
                $q->whereNull('zahlungsbedingungen')
                    ->orWhere('zahlungsbedingungen', '!=', Zahlungsbedingung::BEZAHLT->value);
            })
            ->where(function ($q) {
                $q->whereDate('zahlungsziel', '<', now())
                    ->orWhere(function ($q2) {
                        $q2->whereNull('zahlungsziel')
                            ->whereDate('rechnungsdatum', '<', now()->subDays(30));
                    });
            });
    }

    /**
     * Scope: Bald fällig (innerhalb X Tagen).
     */
    public function scopeBaldFaellig($query, int $tage = 7)
    {
        $bis = now()->addDays($tage);

        return $query->where('status', '!=', 'paid')
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) {
                $q->whereNull('zahlungsbedingungen')
                    ->orWhere('zahlungsbedingungen', '!=', Zahlungsbedingung::BEZAHLT->value);
            })
            ->where(function ($q) use ($bis) {
                $q->whereBetween('zahlungsziel', [now(), $bis])
                    ->orWhere(function ($q2) use ($bis) {
                        $q2->whereNull('zahlungsziel')
                            ->whereBetween(DB::raw('DATE_ADD(rechnungsdatum, INTERVAL 30 DAY)'), [now(), $bis]);
                    });
            });
    }

    /**
     * Scope: Offene Rechnungen (sent, aber nicht paid).
     */
    public function scopeOffen($query)
    {
        return $query->where('status', 'sent')
            ->where(function ($q) {
                $q->whereNull('zahlungsbedingungen')
                    ->orWhere('zahlungsbedingungen', '!=', Zahlungsbedingung::BEZAHLT->value);
            });
    }
}
