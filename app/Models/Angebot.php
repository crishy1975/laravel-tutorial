<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class Angebot extends Model
{
    use SoftDeletes;

    protected $table = 'angebote';

    protected $fillable = [
        'jahr',
        'laufnummer',
        'gebaeude_id',
        'adresse_id',
        'fattura_profile_id',
        'empfaenger_name',
        'empfaenger_strasse',
        'empfaenger_hausnummer',
        'empfaenger_plz',
        'empfaenger_ort',
        'empfaenger_land',
        'empfaenger_email',
        'empfaenger_steuernummer',
        'empfaenger_codice_fiscale',
        'empfaenger_pec',
        'empfaenger_codice_destinatario',
        'geb_codex',
        'geb_name',
        'geb_strasse',
        'geb_plz',
        'geb_ort',
        'titel',
        'datum',
        'gueltig_bis',
        'netto_summe',
        'mwst_satz',
        'mwst_betrag',
        'brutto_summe',
        'einleitung',
        'bemerkung_kunde',
        'bemerkung_intern',
        'status',
        'versendet_am',
        'versendet_an_email',
        'rechnung_id',
        'umgewandelt_am',
        'pdf_pfad',
    ];

    protected $casts = [
        'datum'           => 'date',
        'gueltig_bis'     => 'date',
        'versendet_am'    => 'datetime',
        'umgewandelt_am'  => 'datetime',
        'netto_summe'     => 'decimal:2',
        'mwst_satz'       => 'decimal:2',
        'mwst_betrag'     => 'decimal:2',
        'brutto_summe'    => 'decimal:2',
    ];

    protected $appends = ['angebotsnummer', 'status_badge'];

    // ═══════════════════════════════════════════════════════════
    // RELATIONSHIPS
    // ═══════════════════════════════════════════════════════════

    public function gebaeude(): BelongsTo
    {
        return $this->belongsTo(Gebaeude::class);
    }

    public function adresse(): BelongsTo
    {
        return $this->belongsTo(Adresse::class);
    }

    public function fatturaProfile(): BelongsTo
    {
        return $this->belongsTo(FatturaProfile::class, 'fattura_profile_id');
    }

    public function rechnung(): BelongsTo
    {
        return $this->belongsTo(Rechnung::class);
    }

    public function positionen(): HasMany
    {
        return $this->hasMany(AngebotPosition::class)->orderBy('position');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(AngebotLog::class)->orderByDesc('created_at');
    }

    // ═══════════════════════════════════════════════════════════
    // ACCESSORS
    // ═══════════════════════════════════════════════════════════

    /**
     * Formatierte Angebotsnummer: A2025/0001
     */
    public function getAngebotsnummerAttribute(): string
    {
        return sprintf('A%d/%04d', $this->jahr, $this->laufnummer);
    }

    /**
     * Status als Badge fuer UI
     */
    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'entwurf'    => '<span class="badge bg-secondary">Entwurf</span>',
            'versendet'  => '<span class="badge bg-primary">Versendet</span>',
            'angenommen' => '<span class="badge bg-success">Angenommen</span>',
            'abgelehnt'  => '<span class="badge bg-danger">Abgelehnt</span>',
            'abgelaufen' => '<span class="badge bg-warning text-dark">Abgelaufen</span>',
            'rechnung'   => '<span class="badge bg-info">Rechnung</span>',
            default      => '<span class="badge bg-secondary">' . $this->status . '</span>',
        };
    }

    /**
     * Status-Text (deutsch)
     */
    public function getStatusTextAttribute(): string
    {
        return match($this->status) {
            'entwurf'    => 'Entwurf',
            'versendet'  => 'Versendet',
            'angenommen' => 'Angenommen',
            'abgelehnt'  => 'Abgelehnt',
            'abgelaufen' => 'Abgelaufen',
            'rechnung'   => 'In Rechnung umgewandelt',
            default      => $this->status,
        };
    }

    /**
     * Ist abgelaufen?
     */
    public function getIstAbgelaufenAttribute(): bool
    {
        if (!$this->gueltig_bis) {
            return false;
        }
        return $this->gueltig_bis->isPast() && !in_array($this->status, ['angenommen', 'rechnung']);
    }

    /**
     * Netto formatiert
     */
    public function getNettoFormatiertAttribute(): string
    {
        return number_format($this->netto_summe, 2, ',', '.') . ' EUR';
    }

    /**
     * Brutto formatiert
     */
    public function getBruttoFormatiertAttribute(): string
    {
        return number_format($this->brutto_summe, 2, ',', '.') . ' EUR';
    }

    /**
     * Empfaenger-Adresse als mehrzeiligen String
     */
    public function getEmpfaengerAdresseAttribute(): string
    {
        $lines = [];
        
        if ($this->empfaenger_name) {
            $lines[] = $this->empfaenger_name;
        }
        
        $strasse = trim(($this->empfaenger_strasse ?? '') . ' ' . ($this->empfaenger_hausnummer ?? ''));
        if ($strasse) {
            $lines[] = $strasse;
        }
        
        $ort = trim(($this->empfaenger_plz ?? '') . ' ' . ($this->empfaenger_ort ?? ''));
        if ($ort) {
            $lines[] = $ort;
        }
        
        if ($this->empfaenger_land && strtoupper($this->empfaenger_land) !== 'IT') {
            $lines[] = $this->empfaenger_land;
        }
        
        return implode("\n", $lines);
    }

    // ═══════════════════════════════════════════════════════════
    // NUMMERNVERGABE
    // ═══════════════════════════════════════════════════════════

    /**
     * Naechste freie Angebotsnummer fuer ein Jahr
     */
    public static function naechsteLaufnummer(int $jahr): int
    {
        $max = static::withTrashed()
            ->where('jahr', $jahr)
            ->max('laufnummer');

        return ($max ?? 0) + 1;
    }

    // ═══════════════════════════════════════════════════════════
    // FACTORY METHODS
    // ═══════════════════════════════════════════════════════════

    /**
     * Erstellt ein Angebot aus einem Gebaeude
     * 
     * WICHTIG: E-Mail wird aus der POSTADRESSE geholt!
     */
    public static function createFromGebaeude(Gebaeude $gebaeude, array $overrides = []): self
    {
        $gebaeude->load(['rechnungsempfaenger', 'postadresse', 'aktiveArtikel', 'fatturaProfile']);
        
        $empfaenger = $gebaeude->rechnungsempfaenger;
        $postadresse = $gebaeude->postadresse;
        $jahr = now()->year;

        // Angebot erstellen
        $angebot = new static();
        $angebot->jahr = $jahr;
        $angebot->laufnummer = static::naechsteLaufnummer($jahr);
        $angebot->gebaeude_id = $gebaeude->id;
        $angebot->adresse_id = $empfaenger?->id;
        $angebot->fattura_profile_id = $gebaeude->fattura_profile_id;
        
        // Datum
        $angebot->datum = $overrides['datum'] ?? now();
        $angebot->gueltig_bis = $overrides['gueltig_bis'] ?? now()->addDays(30);
        
        // Titel
        $angebot->titel = $overrides['titel'] ?? 'Angebot fuer ' . ($gebaeude->gebaeude_name ?: $gebaeude->codex);
        
        // MwSt aus Fattura-Profil
        $mwstSatz = $gebaeude->fatturaProfile?->mwst_satz ?? 22.00;
        $angebot->mwst_satz = $mwstSatz;
        
        // Empfaenger-Snapshot (Adressdaten vom Rechnungsempfaenger)
        if ($empfaenger) {
            $angebot->empfaenger_name = $empfaenger->name;
            $angebot->empfaenger_strasse = $empfaenger->strasse;
            $angebot->empfaenger_hausnummer = $empfaenger->hausnummer;
            $angebot->empfaenger_plz = $empfaenger->plz;
            $angebot->empfaenger_ort = $empfaenger->wohnort;
            $angebot->empfaenger_land = $empfaenger->land ?? 'IT';
            $angebot->empfaenger_steuernummer = $empfaenger->steuernummer;
            $angebot->empfaenger_codice_fiscale = $empfaenger->codice_fiscale;
            $angebot->empfaenger_pec = $empfaenger->pec;
            $angebot->empfaenger_codice_destinatario = $empfaenger->codice_destinatario;
        }
        
        // E-Mail aus POSTADRESSE holen (Fallback: Rechnungsempfaenger)
        $angebot->empfaenger_email = $postadresse?->email ?? $empfaenger?->email;
        
        // Gebaeude-Snapshot
        $angebot->geb_codex = $gebaeude->codex;
        $angebot->geb_name = $gebaeude->gebaeude_name;
        $angebot->geb_strasse = trim(($gebaeude->strasse ?? '') . ' ' . ($gebaeude->hausnummer ?? ''));
        $angebot->geb_plz = $gebaeude->plz;
        $angebot->geb_ort = $gebaeude->wohnort;
        
        // Overrides anwenden
        foreach (['einleitung', 'bemerkung_kunde', 'bemerkung_intern'] as $field) {
            if (isset($overrides[$field])) {
                $angebot->$field = $overrides[$field];
            }
        }
        
        $angebot->status = 'entwurf';
        $angebot->save();
        
        // Positionen aus aktiven Artikeln erstellen
        $nettoSumme = 0;
        $position = 1;
        
        foreach ($gebaeude->aktiveArtikel as $artikel) {
            $einzelpreis = (float) $artikel->einzelpreis;
            $anzahl = (float) $artikel->anzahl;
            $gesamtpreis = round($einzelpreis * $anzahl, 2);
            
            $angebot->positionen()->create([
                'artikel_gebaeude_id' => $artikel->id,
                'position'            => $position++,
                'beschreibung'        => $artikel->beschreibung,
                'anzahl'              => $anzahl,
                'einheit'             => 'Stueck',
                'einzelpreis'         => $einzelpreis,
                'gesamtpreis'         => $gesamtpreis,
            ]);
            
            $nettoSumme += $gesamtpreis;
        }
        
        // Betraege berechnen
        $angebot->berechneBetraege();
        
        // Log erstellen
        AngebotLog::log(
            $angebot->id,
            'erstellt',
            'Angebot erstellt',
            'Angebot aus Gebaeude ' . ($gebaeude->codex ?: $gebaeude->id) . ' erstellt'
        );
        
        return $angebot;
    }

    // ═══════════════════════════════════════════════════════════
    // BERECHNUNGEN
    // ═══════════════════════════════════════════════════════════

    /**
     * Berechnet Netto, MwSt und Brutto aus Positionen
     */
    public function berechneBetraege(): void
    {
        $netto = $this->positionen()->sum('gesamtpreis');
        $mwst = round($netto * ($this->mwst_satz / 100), 2);
        $brutto = round($netto + $mwst, 2);
        
        $this->update([
            'netto_summe'  => $netto,
            'mwst_betrag'  => $mwst,
            'brutto_summe' => $brutto,
        ]);
    }

    // ═══════════════════════════════════════════════════════════
    // KONVERTIERUNG ZU RECHNUNG
    // ═══════════════════════════════════════════════════════════

    /**
     * Wandelt Angebot in Rechnung um
     */
    public function zuRechnung(array $overrides = []): Rechnung
    {
        if ($this->rechnung_id) {
            throw new \Exception('Angebot wurde bereits in Rechnung umgewandelt.');
        }
        
        $this->load(['positionen', 'gebaeude']);
        
        // Rechnung erstellen
        $rechnung = new Rechnung();
        $rechnung->jahr = now()->year;
        $rechnung->laufnummer = Rechnung::naechsteLaufnummer($rechnung->jahr);
        $rechnung->gebaeude_id = $this->gebaeude_id;
        $rechnung->fattura_profile_id = $this->fattura_profile_id;
        $rechnung->datum = $overrides['datum'] ?? now();
        $rechnung->status = 'draft';
        
        // Empfaenger uebernehmen
        $rechnung->re_name = $this->empfaenger_name;
        $rechnung->re_strasse = $this->empfaenger_strasse;
        $rechnung->re_hausnummer = $this->empfaenger_hausnummer;
        $rechnung->re_plz = $this->empfaenger_plz;
        $rechnung->re_wohnort = $this->empfaenger_ort;
        $rechnung->re_land = $this->empfaenger_land;
        $rechnung->re_steuernummer = $this->empfaenger_steuernummer;
        $rechnung->re_codice_fiscale = $this->empfaenger_codice_fiscale;
        $rechnung->re_pec = $this->empfaenger_pec;
        $rechnung->re_codice_destinatario = $this->empfaenger_codice_destinatario;
        
        // Gebaeude uebernehmen
        $rechnung->geb_codex = $this->geb_codex;
        $rechnung->geb_name = $this->geb_name;
        $rechnung->geb_strasse = $this->geb_strasse;
        $rechnung->geb_plz = $this->geb_plz;
        $rechnung->geb_wohnort = $this->geb_ort;
        
        // MwSt uebernehmen
        $rechnung->mwst_satz = $this->mwst_satz;
        
        // Bemerkung
        if ($this->bemerkung_kunde) {
            $rechnung->bemerkung = $this->bemerkung_kunde;
        }
        
        // Referenz zum Angebot
        $rechnung->angebot_referenz = $this->angebotsnummer;
        
        $rechnung->save();
        
        // Positionen uebernehmen
        foreach ($this->positionen as $pos) {
            $rechnung->positionen()->create([
                'artikel_gebaeude_id' => $pos->artikel_gebaeude_id,
                'beschreibung'        => $pos->beschreibung,
                'anzahl'              => $pos->anzahl,
                'einzelpreis'         => $pos->einzelpreis,
                'gesamtpreis'         => $pos->gesamtpreis,
            ]);
        }
        
        // Rechnung berechnen
        $rechnung->berechneBetraege();
        
        // Angebot aktualisieren
        $this->update([
            'status'         => 'rechnung',
            'rechnung_id'    => $rechnung->id,
            'umgewandelt_am' => now(),
        ]);
        
        // Logs
        AngebotLog::log(
            $this->id,
            'umgewandelt',
            'In Rechnung umgewandelt',
            'Rechnung ' . $rechnung->volle_rechnungsnummer . ' erstellt'
        );
        
        return $rechnung;
    }

    // ═══════════════════════════════════════════════════════════
    // VERSAND
    // ═══════════════════════════════════════════════════════════

    /**
     * Markiert als versendet
     */
    public function markiereAlsVersendet(?string $email = null): void
    {
        $this->update([
            'status'            => 'versendet',
            'versendet_am'      => now(),
            'versendet_an_email' => $email ?? $this->empfaenger_email,
        ]);
        
        AngebotLog::log(
            $this->id,
            'versendet',
            'Angebot versendet',
            'Per E-Mail an ' . ($email ?? $this->empfaenger_email)
        );
    }

    /**
     * Status aendern
     */
    public function setStatus(string $status, ?string $bemerkung = null): void
    {
        $alterStatus = $this->status;
        
        $this->update(['status' => $status]);
        
        AngebotLog::log(
            $this->id,
            'status_geaendert',
            'Status geaendert: ' . $alterStatus . ' -> ' . $status,
            $bemerkung
        );
    }

    // ═══════════════════════════════════════════════════════════
    // SCOPES
    // ═══════════════════════════════════════════════════════════

    public function scopeEntwurf($query)
    {
        return $query->where('status', 'entwurf');
    }

    public function scopeVersendet($query)
    {
        return $query->where('status', 'versendet');
    }

    public function scopeOffen($query)
    {
        return $query->whereIn('status', ['entwurf', 'versendet']);
    }

    public function scopeAbgeschlossen($query)
    {
        return $query->whereIn('status', ['angenommen', 'abgelehnt', 'abgelaufen', 'rechnung']);
    }

    public function scopeJahr($query, int $jahr)
    {
        return $query->where('jahr', $jahr);
    }
}
