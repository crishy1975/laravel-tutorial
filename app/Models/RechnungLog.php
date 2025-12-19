<?php

namespace App\Models;

use App\Enums\RechnungLogTyp;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class RechnungLog extends Model
{
    protected $table = 'rechnung_logs';

    protected $fillable = [
        'rechnung_id',
        'typ',
        'titel',
        'beschreibung',
        'user_id',
        'metadata',
        'dokument_pfad',
        'referenz_id',
        'referenz_typ',
        'kontakt_person',
        'kontakt_telefon',
        'kontakt_email',
        'prioritaet',
        'ist_oeffentlich',
        'erinnerung_datum',
        'erinnerung_erledigt',
    ];

    protected $casts = [
        'typ' => RechnungLogTyp::class,
        'metadata' => 'array',
        'ist_oeffentlich' => 'boolean',
        'erinnerung_erledigt' => 'boolean',
        'erinnerung_datum' => 'date',
    ];

    // ═══════════════════════════════════════════════════════════
    // 🔗 RELATIONSHIPS
    // ═══════════════════════════════════════════════════════════

    public function rechnung(): BelongsTo
    {
        return $this->belongsTo(Rechnung::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ═══════════════════════════════════════════════════════════
    // 🎯 SCOPES
    // ═══════════════════════════════════════════════════════════

    /**
     * Nur Logs einer bestimmten Kategorie
     */
    public function scopeKategorie($query, string $kategorie)
    {
        $typen = RechnungLogTyp::byKategorie($kategorie);
        $werte = array_map(fn($t) => $t->value, $typen);
        
        return $query->whereIn('typ', $werte);
    }

    /**
     * Nur Logs eines bestimmten Typs
     */
    public function scopeVonTyp($query, RechnungLogTyp $typ)
    {
        return $query->where('typ', $typ->value);
    }

    /**
     * Offene Erinnerungen
     */
    public function scopeOffeneErinnerungen($query)
    {
        return $query->whereNotNull('erinnerung_datum')
                     ->where('erinnerung_erledigt', false)
                     ->where('erinnerung_datum', '<=', now());
    }

    /**
     * Zukünftige Erinnerungen
     */
    public function scopeZukuenftigeErinnerungen($query)
    {
        return $query->whereNotNull('erinnerung_datum')
                     ->where('erinnerung_erledigt', false)
                     ->where('erinnerung_datum', '>', now());
    }

    /**
     * Hohe Priorität
     */
    public function scopeHohePrioritaet($query)
    {
        return $query->whereIn('prioritaet', ['hoch', 'kritisch']);
    }

    /**
     * Chronologisch (neueste zuerst)
     */
    public function scopeChronologisch($query)
    {
        return $query->orderByDesc('created_at');
    }

    /**
     * ⭐ NEU: Nur Mahnungs-relevante Logs
     */
    public function scopeMahnungen($query)
    {
        return $query->whereIn('typ', [
            RechnungLogTyp::MAHNUNG_ERSTELLT->value,
            RechnungLogTyp::MAHNUNG_VERSANDT->value,
            RechnungLogTyp::MAHNUNG_TELEFONISCH->value,
            RechnungLogTyp::MAHNUNG_STORNIERT->value,
        ]);
    }

    // ═══════════════════════════════════════════════════════════
    // 🏷️ ACCESSORS
    // ═══════════════════════════════════════════════════════════

    /**
     * Icon für den Log-Typ
     */
    public function getIconAttribute(): string
    {
        return $this->typ->icon();
    }

    /**
     * Farbe für Badge
     */
    public function getFarbeAttribute(): string
    {
        return $this->typ->farbe();
    }

    /**
     * Kategorie des Logs
     */
    public function getKategorieAttribute(): string
    {
        return $this->typ->kategorie();
    }

    /**
     * Bootstrap Badge HTML
     */
    public function getTypBadgeAttribute(): string
    {
        $farbe = $this->typ->farbe();
        $icon = $this->typ->icon();
        $label = $this->typ->label();
        
        return sprintf(
            '<span class="badge bg-%s"><i class="%s me-1"></i>%s</span>',
            $farbe,
            $icon,
            $label
        );
    }

    /**
     * Priorität Badge
     */
    public function getPrioritaetBadgeAttribute(): string
    {
        $farben = [
            'niedrig' => 'secondary',
            'normal' => 'info',
            'hoch' => 'warning',
            'kritisch' => 'danger',
        ];
        
        $labels = [
            'niedrig' => 'Niedrig',
            'normal' => 'Normal',
            'hoch' => 'Hoch',
            'kritisch' => 'Kritisch',
        ];
        
        $farbe = $farben[$this->prioritaet] ?? 'secondary';
        $label = $labels[$this->prioritaet] ?? $this->prioritaet;
        
        return sprintf('<span class="badge bg-%s">%s</span>', $farbe, $label);
    }

    /**
     * Formatiertes Datum
     */
    public function getDatumFormatiertAttribute(): string
    {
        return $this->created_at->format('d.m.Y H:i');
    }

    /**
     * Relativer Zeitstempel
     */
    public function getZeitRelativAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Benutzername oder "System"
     */
    public function getBenutzerNameAttribute(): string
    {
        return $this->user?->name ?? 'System';
    }

    // ═══════════════════════════════════════════════════════════
    // 🛠️ STATIC HELPER METHODS
    // ═══════════════════════════════════════════════════════════

    /**
     * Schnell einen Log-Eintrag erstellen
     */
    public static function log(
        int $rechnungId,
        RechnungLogTyp $typ,
        ?string $beschreibung = null,
        array $metadata = [],
        ?string $titel = null
    ): self {
        return self::create([
            'rechnung_id' => $rechnungId,
            'typ' => $typ,
            'titel' => $titel ?? $typ->label(),
            'beschreibung' => $beschreibung,
            'user_id' => Auth::id(),
            'metadata' => $metadata ?: null,
        ]);
    }

    /**
     * XML erstellt loggen
     */
    public static function xmlErstellt(int $rechnungId, string $progressivo, ?string $dateiname = null): self
    {
        return self::log(
            $rechnungId,
            RechnungLogTyp::XML_ERSTELLT,
            "FatturaPA XML generiert: {$progressivo}",
            [
                'progressivo' => $progressivo,
                'dateiname' => $dateiname,
            ]
        );
    }

    /**
     * XML versandt loggen
     */
    public static function xmlVersandt(int $rechnungId, string $progressivo, string $empfaenger): self
    {
        return self::log(
            $rechnungId,
            RechnungLogTyp::XML_VERSANDT,
            "FatturaPA XML versandt an SDI",
            [
                'progressivo' => $progressivo,
                'empfaenger' => $empfaenger,
            ]
        );
    }

    /**
     * PDF erstellt loggen
     */
    public static function pdfErstellt(int $rechnungId, ?string $pfad = null): self
    {
        return self::log(
            $rechnungId,
            RechnungLogTyp::PDF_ERSTELLT,
            "PDF-Rechnung generiert",
            ['pfad' => $pfad]
        );
    }

    /**
     * PDF versandt loggen
     */
    public static function pdfVersandt(int $rechnungId, string $email, ?string $betreff = null): self
    {
        return self::log(
            $rechnungId,
            RechnungLogTyp::PDF_VERSANDT,
            "PDF per E-Mail versandt an: {$email}",
            [
                'email' => $email,
                'betreff' => $betreff,
            ]
        );
    }

    /**
     * Telefonat loggen
     */
    public static function telefonat(
        int $rechnungId,
        string $beschreibung,
        ?string $kontaktPerson = null,
        ?string $telefon = null,
        RechnungLogTyp $typ = RechnungLogTyp::TELEFONAT
    ): self {
        return self::create([
            'rechnung_id' => $rechnungId,
            'typ' => $typ,
            'titel' => $typ->label(),
            'beschreibung' => $beschreibung,
            'user_id' => Auth::id(),
            'kontakt_person' => $kontaktPerson,
            'kontakt_telefon' => $telefon,
        ]);
    }

    /**
     * Mitteilung vom Kunden loggen
     */
    public static function mitteilungKunde(
        int $rechnungId,
        string $beschreibung,
        ?string $kontaktPerson = null,
        ?string $email = null
    ): self {
        return self::create([
            'rechnung_id' => $rechnungId,
            'typ' => RechnungLogTyp::MITTEILUNG_KUNDE,
            'titel' => 'Mitteilung vom Kunden',
            'beschreibung' => $beschreibung,
            'user_id' => Auth::id(),
            'kontakt_person' => $kontaktPerson,
            'kontakt_email' => $email,
        ]);
    }

    /**
     * Mahnung erstellt loggen
     */
    public static function mahnungErstellt(int $rechnungId, int $stufe, ?string $betrag = null): self
    {
        return self::log(
            $rechnungId,
            RechnungLogTyp::MAHNUNG_ERSTELLT,
            "Mahnung Stufe {$stufe} erstellt",
            [
                'stufe' => $stufe,
                'betrag' => $betrag,
            ]
        );
    }

    /**
     * Mahnung versandt loggen
     */
    public static function mahnungVersandt(int $rechnungId, int $stufe, string $versandart): self
    {
        return self::log(
            $rechnungId,
            RechnungLogTyp::MAHNUNG_VERSANDT,
            "Mahnung Stufe {$stufe} versandt per {$versandart}",
            [
                'stufe' => $stufe,
                'versandart' => $versandart,
            ]
        );
    }

    /**
     * ⭐ NEU: Telefonische Mahnung loggen
     * 
     * @param int $rechnungId
     * @param string $beschreibung Was wurde besprochen?
     * @param string|null $kontaktPerson Mit wem gesprochen?
     * @param string|null $telefon Telefonnummer
     * @param string|null $ergebnis Ergebnis des Gesprächs (z.B. "Zahlungszusage", "Nicht erreicht")
     * @param string|null $wiedervorlage Datum für Wiedervorlage (YYYY-MM-DD)
     * @return self
     */
    public static function mahnungTelefonisch(
        int $rechnungId,
        string $beschreibung,
        ?string $kontaktPerson = null,
        ?string $telefon = null,
        ?string $ergebnis = null,
        ?string $wiedervorlage = null
    ): self {
        return self::create([
            'rechnung_id' => $rechnungId,
            'typ' => RechnungLogTyp::MAHNUNG_TELEFONISCH,
            'titel' => 'Telefonische Mahnung',
            'beschreibung' => $beschreibung,
            'user_id' => Auth::id(),
            'kontakt_person' => $kontaktPerson,
            'kontakt_telefon' => $telefon,
            'prioritaet' => 'hoch',
            'metadata' => array_filter([
                'ergebnis' => $ergebnis,
                'wiedervorlage' => $wiedervorlage,
            ]),
            'erinnerung_datum' => $wiedervorlage,
            'erinnerung_erledigt' => false,
        ]);
    }

    /**
     * Zahlung eingegangen loggen
     */
    public static function zahlungEingegangen(int $rechnungId, float $betrag, ?string $referenz = null): self
    {
        return self::log(
            $rechnungId,
            RechnungLogTyp::ZAHLUNG_EINGEGANGEN,
            sprintf("Zahlung eingegangen: %.2f €", $betrag),
            [
                'betrag' => $betrag,
                'referenz' => $referenz,
            ]
        );
    }

    /**
     * ⭐ NEU: Bank-Match loggen
     */
    public static function bankMatch(int $rechnungId, float $betrag, ?int $buchungId = null): self
    {
        return self::log(
            $rechnungId,
            RechnungLogTyp::BANK_MATCH,
            sprintf("Bankzuordnung: %.2f €", $betrag),
            [
                'betrag' => $betrag,
                'bank_buchung_id' => $buchungId,
            ]
        );
    }

    /**
     * Status geändert loggen
     */
    public static function statusGeaendert(int $rechnungId, string $alterStatus, string $neuerStatus): self
    {
        $typ = match($neuerStatus) {
            'draft' => RechnungLogTyp::STATUS_ENTWURF,
            'sent' => RechnungLogTyp::STATUS_VERSENDET,
            'paid' => RechnungLogTyp::STATUS_BEZAHLT,
            'cancelled' => RechnungLogTyp::STATUS_STORNIERT,
            'overdue' => RechnungLogTyp::STATUS_UEBERFAELLIG,
            default => RechnungLogTyp::STATUS_GEAENDERT,
        };
        
        return self::log(
            $rechnungId,
            $typ,
            "Status geändert: {$alterStatus} → {$neuerStatus}",
            [
                'alter_status' => $alterStatus,
                'neuer_status' => $neuerStatus,
            ]
        );
    }

    /**
     * Notiz hinzufügen
     */
    public static function notiz(
        int $rechnungId,
        string $beschreibung,
        string $prioritaet = 'normal',
        ?string $erinnerungDatum = null
    ): self {
        return self::create([
            'rechnung_id' => $rechnungId,
            'typ' => RechnungLogTyp::NOTIZ,
            'titel' => 'Notiz',
            'beschreibung' => $beschreibung,
            'user_id' => Auth::id(),
            'prioritaet' => $prioritaet,
            'erinnerung_datum' => $erinnerungDatum,
        ]);
    }

    /**
     * Erinnerung erstellen
     */
    public static function erinnerung(
        int $rechnungId,
        string $beschreibung,
        string $datum,
        string $prioritaet = 'normal'
    ): self {
        return self::create([
            'rechnung_id' => $rechnungId,
            'typ' => RechnungLogTyp::ERINNERUNG,
            'titel' => 'Erinnerung',
            'beschreibung' => $beschreibung,
            'user_id' => Auth::id(),
            'prioritaet' => $prioritaet,
            'erinnerung_datum' => $datum,
            'erinnerung_erledigt' => false,
        ]);
    }
}
