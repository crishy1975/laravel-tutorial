<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Mahnung extends Model
{
    protected $table = 'mahnungen';

    protected $fillable = [
        'rechnung_id',
        'mahnung_stufe_id',
        'mahnstufe',
        'mahndatum',
        'tage_ueberfaellig',
        'rechnungsbetrag',
        'spesen',
        'gesamtbetrag',
        'versandart',
        'email_gesendet_am',
        'email_adresse',
        'email_fehler',
        'email_fehler_text',
        'pdf_pfad',
        'status',
        'bemerkung',
    ];

    protected $casts = [
        'mahnstufe'          => 'integer',
        'mahndatum'          => 'date',
        'tage_ueberfaellig'  => 'integer',
        'rechnungsbetrag'    => 'decimal:2',
        'spesen'             => 'decimal:2',
        'gesamtbetrag'       => 'decimal:2',
        'email_gesendet_am'  => 'datetime',
        'email_fehler'       => 'boolean',
    ];

    // ═══════════════════════════════════════════════════════════════════════
    // RELATIONSHIPS
    // ═══════════════════════════════════════════════════════════════════════

    public function rechnung(): BelongsTo
    {
        return $this->belongsTo(Rechnung::class);
    }

    public function stufe(): BelongsTo
    {
        return $this->belongsTo(MahnungStufe::class, 'mahnung_stufe_id');
    }

    // ═══════════════════════════════════════════════════════════════════════
    // SCOPES
    // ═══════════════════════════════════════════════════════════════════════

    public function scopeEntwurf($query)
    {
        return $query->where('status', 'entwurf');
    }

    public function scopeGesendet($query)
    {
        return $query->where('status', 'gesendet');
    }

    public function scopeVonRechnung($query, int $rechnungId)
    {
        return $query->where('rechnung_id', $rechnungId);
    }

    public function scopeMitEmailFehler($query)
    {
        return $query->where('email_fehler', true);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // ACCESSORS
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Stufen-Name (zweisprachig)
     */
    public function getStufenNameAttribute(): string
    {
        return $this->stufe?->name_de ?? "Stufe {$this->mahnstufe}";
    }

    /**
     * Status-Badge HTML
     */
    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'entwurf'   => '<span class="badge bg-secondary">Entwurf</span>',
            'gesendet'  => '<span class="badge bg-success">Gesendet</span>',
            'storniert' => '<span class="badge bg-danger">Storniert</span>',
            default     => '<span class="badge bg-light text-dark">Unbekannt</span>',
        };
    }

    /**
     * Versandart-Badge
     */
    public function getVersandartBadgeAttribute(): string
    {
        return match ($this->versandart) {
            'email' => '<span class="badge bg-info"><i class="bi bi-envelope"></i> E-Mail</span>',
            'post'  => '<span class="badge bg-warning text-dark"><i class="bi bi-mailbox"></i> Post</span>',
            'keine' => '<span class="badge bg-light text-dark">-</span>',
            default => '-',
        };
    }

    /**
     * Formatierter Gesamtbetrag
     */
    public function getGesamtbetragFormatiertAttribute(): string
    {
        return number_format($this->gesamtbetrag, 2, ',', '.') . ' €';
    }

    /**
     * Formatierte Spesen
     */
    public function getSpesenFormatiertAttribute(): string
    {
        return number_format($this->spesen, 2, ',', '.') . ' €';
    }

    /**
     * Wurde per E-Mail gesendet?
     */
    public function getIstPerEmailGesendetAttribute(): bool
    {
        return $this->versandart === 'email' && $this->email_gesendet_am !== null;
    }

    /**
     * Hat E-Mail-Fehler?
     */
    public function getHatEmailFehlerAttribute(): bool
    {
        return $this->email_fehler === true;
    }

    // ═══════════════════════════════════════════════════════════════════════
    // BUSINESS LOGIC
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Prüft ob Mahnung stornierbar ist
     */
    public function istStornierbar(): bool
    {
        return $this->status !== 'storniert';
    }

    /**
     * Storniert die Mahnung
     */
    public function stornieren(?string $grund = null): bool
    {
        if (!$this->istStornierbar()) {
            return false;
        }

        $this->status = 'storniert';
        $this->bemerkung = $grund ?? $this->bemerkung;
        return $this->save();
    }

    /**
     * Markiert als gesendet
     */
    public function markiereAlsGesendet(string $versandart, ?string $emailAdresse = null): void
    {
        $this->status = 'gesendet';
        $this->versandart = $versandart;
        
        if ($versandart === 'email') {
            $this->email_gesendet_am = now();
            $this->email_adresse = $emailAdresse;
        }
        
        $this->save();
    }

    /**
     * Markiert E-Mail-Fehler
     */
    public function markiereEmailFehler(string $fehlerText): void
    {
        $this->email_fehler = true;
        $this->email_fehler_text = $fehlerText;
        $this->save();
    }

    /**
     * Generiert den Mahntext mit Platzhaltern
     */
    public function generiereText(string $sprache = 'de', array $extraDaten = []): string
    {
        $stufe = $this->stufe;
        if (!$stufe) {
            return '';
        }

        $text = $stufe->getText($sprache);
        $rechnung = $this->rechnung;

        // Standard-Platzhalter
        $platzhalter = [
            '{rechnungsnummer}' => $rechnung?->volle_rechnungsnummer ?? $rechnung?->laufnummer ?? '-',
            '{rechnungsdatum}'  => $rechnung?->rechnungsdatum?->format('d.m.Y') ?? '-',
            '{faelligkeitsdatum}' => $rechnung?->faelligkeitsdatum?->format('d.m.Y') ?? '-',
            '{betrag}'          => number_format($this->rechnungsbetrag, 2, ',', '.'),
            '{spesen}'          => number_format($this->spesen, 2, ',', '.'),
            '{gesamtbetrag}'    => number_format($this->gesamtbetrag, 2, ',', '.'),
            '{tage_ueberfaellig}' => $this->tage_ueberfaellig,
            '{firma}'           => config('app.firma_name', 'Resch GmbH'),
            '{kunde}'           => $rechnung?->rechnungsempfaenger?->name ?? '-',
        ];

        // Extra-Daten überschreiben/ergänzen
        $platzhalter = array_merge($platzhalter, $extraDaten);

        return str_replace(array_keys($platzhalter), array_values($platzhalter), $text);
    }

    /**
     * Generiert den Betreff mit Platzhaltern
     */
    public function generiereBetreff(string $sprache = 'de'): string
    {
        $stufe = $this->stufe;
        if (!$stufe) {
            return "Mahnung";
        }

        $betreff = $stufe->getBetreff($sprache);
        $rechnung = $this->rechnung;

        $platzhalter = [
            '{rechnungsnummer}' => $rechnung?->volle_rechnungsnummer ?? $rechnung?->laufnummer ?? '-',
        ];

        return str_replace(array_keys($platzhalter), array_values($platzhalter), $betreff);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // STATIC HELPERS
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Höchste Mahnstufe für eine Rechnung
     */
    public static function hoechsteStufeVonRechnung(int $rechnungId): int
    {
        return (int) self::vonRechnung($rechnungId)
            ->where('status', '!=', 'storniert')
            ->max('mahnstufe') ?? -1;
    }

    /**
     * Letzte Mahnung einer Rechnung
     */
    public static function letzteVonRechnung(int $rechnungId): ?self
    {
        return self::vonRechnung($rechnungId)
            ->where('status', '!=', 'storniert')
            ->orderByDesc('mahnstufe')
            ->first();
    }
}
