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

    /**
     * ⭐ Rechnungsnummer für Anzeige (öffentlicher Accessor)
     */
    public function getRechnungsnummerAnzeigeAttribute(): string
    {
        return $this->getRechnungsnummer();
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

    // ═══════════════════════════════════════════════════════════════════════
    // PLATZHALTER-GENERIERUNG
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * ⭐ Holt die Rechnungsnummer robust aus verschiedenen Quellen
     */
    protected function getRechnungsnummer(): string
    {
        $rechnung = $this->rechnung;
        
        if (!$rechnung) {
            return '-';
        }

        // 1. Priorität: volle_rechnungsnummer (Accessor)
        if (!empty($rechnung->volle_rechnungsnummer)) {
            return $rechnung->volle_rechnungsnummer;
        }

        // 2. Priorität: rechnungsnummer (direktes Feld)
        if (!empty($rechnung->rechnungsnummer)) {
            return $rechnung->rechnungsnummer;
        }

        // 3. Priorität: jahr/laufnummer zusammensetzen
        if (!empty($rechnung->jahr) && !empty($rechnung->laufnummer)) {
            return "{$rechnung->jahr}/{$rechnung->laufnummer}";
        }

        // 4. Fallback: nur laufnummer
        if (!empty($rechnung->laufnummer)) {
            return (string) $rechnung->laufnummer;
        }

        return '-';
    }

    /**
     * ⭐ Erstellt die Standard-Platzhalter für Texte
     */
    protected function getPlatzhalter(array $extraDaten = []): array
    {
        $rechnung = $this->rechnung;

        $platzhalter = [
            '{rechnungsnummer}'   => $this->getRechnungsnummer(),
            '{rechnungsdatum}'    => $rechnung?->rechnungsdatum?->format('d.m.Y') ?? '-',
            '{faelligkeitsdatum}' => $rechnung?->faelligkeitsdatum?->format('d.m.Y') 
                                     ?? $rechnung?->rechnungsdatum?->addDays(30)->format('d.m.Y') 
                                     ?? '-',
            '{betrag}'            => number_format($this->rechnungsbetrag, 2, ',', '.'),
            '{spesen}'            => number_format($this->spesen, 2, ',', '.'),
            '{gesamtbetrag}'      => number_format($this->gesamtbetrag, 2, ',', '.'),
            '{tage_ueberfaellig}' => $this->tage_ueberfaellig,
            '{firma}'             => config('app.firma_name', 'Resch GmbH'),
            '{kunde}'             => $rechnung?->rechnungsempfaenger?->name ?? '-',
        ];

        return array_merge($platzhalter, $extraDaten);
    }

    /**
     * Generiert den Mahntext ZWEISPRACHIG (DE + IT)
     */
    public function generiereText(array $extraDaten = []): string
    {
        $stufe = $this->stufe;
        if (!$stufe) {
            return '';
        }

        $platzhalter = $this->getPlatzhalter($extraDaten);

        // Deutschen Text
        $textDe = str_replace(
            array_keys($platzhalter), 
            array_values($platzhalter), 
            $stufe->getText('de')
        );

        // Italienischen Text
        $textIt = str_replace(
            array_keys($platzhalter), 
            array_values($platzhalter), 
            $stufe->getText('it')
        );

        // Kombinieren: DE zuerst, dann IT
        $separator = "\n\n" . str_repeat('─', 50) . "\n\n";
        
        return $textDe . $separator . $textIt;
    }

    /**
     * Generiert NUR den deutschen Mahntext (für Vorschau)
     */
    public function generiereTextDe(array $extraDaten = []): string
    {
        return $this->generiereTextSprache('de', $extraDaten);
    }

    /**
     * Generiert NUR den italienischen Mahntext (für Vorschau)
     */
    public function generiereTextIt(array $extraDaten = []): string
    {
        return $this->generiereTextSprache('it', $extraDaten);
    }

    /**
     * Interne Hilfsmethode: Text in einer Sprache generieren
     */
    protected function generiereTextSprache(string $sprache, array $extraDaten = []): string
    {
        $stufe = $this->stufe;
        if (!$stufe) {
            return '';
        }

        $platzhalter = $this->getPlatzhalter($extraDaten);

        return str_replace(
            array_keys($platzhalter), 
            array_values($platzhalter), 
            $stufe->getText($sprache)
        );
    }

    /**
     * Generiert den Betreff ZWEISPRACHIG (DE / IT)
     */
    public function generiereBetreff(): string
    {
        $stufe = $this->stufe;
        if (!$stufe) {
            return "Mahnung / Sollecito";
        }

        $platzhalter = [
            '{rechnungsnummer}' => $this->getRechnungsnummer(),
        ];

        $betreffDe = str_replace(
            array_keys($platzhalter), 
            array_values($platzhalter), 
            $stufe->getBetreff('de')
        );

        $betreffIt = str_replace(
            array_keys($platzhalter), 
            array_values($platzhalter), 
            $stufe->getBetreff('it')
        );

        // Kombinieren: DE / IT
        return $betreffDe . ' / ' . $betreffIt;
    }

    // ═══════════════════════════════════════════════════════════════════════
    // STATIC HELPERS
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * ⭐ Höchste GESENDETE Mahnstufe für eine Rechnung
     * 
     * NUR gesendete Mahnungen zählen! Entwürfe blockieren die nächste Stufe,
     * aber sie erhöhen nicht die "abgeschlossene" Stufe.
     * 
     * @return int Höchste gesendete Stufe, oder -1 wenn keine gesendet
     */
    public static function hoechsteGesendeteStufeVonRechnung(int $rechnungId): int
    {
        $result = self::vonRechnung($rechnungId)
            ->where('status', 'gesendet')
            ->max('mahnstufe');
        
        // ⭐ WICHTIG: max() gibt NULL zurück wenn keine Einträge
        // (int) null = 0, daher explizite Prüfung!
        return $result !== null ? (int) $result : -1;
    }

    /**
     * ⭐ Prüft ob ein offener Entwurf existiert
     * 
     * Wenn ja, muss dieser erst versendet werden bevor eine neue Mahnung erstellt werden kann.
     */
    public static function hatOffenenEntwurf(int $rechnungId): bool
    {
        return self::vonRechnung($rechnungId)
            ->where('status', 'entwurf')
            ->exists();
    }

    /**
     * ⭐ Holt den offenen Entwurf (falls vorhanden)
     */
    public static function getOffenerEntwurf(int $rechnungId): ?self
    {
        return self::vonRechnung($rechnungId)
            ->where('status', 'entwurf')
            ->first();
    }

    /**
     * Höchste Mahnstufe für eine Rechnung (alle außer storniert)
     * 
     * @deprecated Nutze hoechsteGesendeteStufeVonRechnung() für Stufen-Logik
     * @return int Höchste Stufe, oder -1 wenn keine vorhanden
     */
    public static function hoechsteStufeVonRechnung(int $rechnungId): int
    {
        $result = self::vonRechnung($rechnungId)
            ->where('status', '!=', 'storniert')
            ->max('mahnstufe');
        
        // ⭐ WICHTIG: max() gibt NULL zurück wenn keine Einträge
        return $result !== null ? (int) $result : -1;
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
