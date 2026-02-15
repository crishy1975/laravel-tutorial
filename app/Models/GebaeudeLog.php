<?php

namespace App\Models;

use App\Enums\GebaeudeLogTyp;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class GebaeudeLog extends Model
{
    protected $table = 'gebaeude_logs';

    protected $fillable = [
        'gebaeude_id',
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
        'typ' => GebaeudeLogTyp::class,
        'metadata' => 'array',
        'ist_oeffentlich' => 'boolean',
        'erinnerung_erledigt' => 'boolean',
        'erinnerung_datum' => 'date',
    ];

    // ═══════════════════════════════════════════════════════════
    // 🔗 RELATIONSHIPS
    // ═══════════════════════════════════════════════════════════

    public function gebaeude(): BelongsTo
    {
        return $this->belongsTo(Gebaeude::class);
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
        $typen = GebaeudeLogTyp::byKategorie($kategorie);
        $werte = array_map(fn($t) => $t->value, $typen);
        
        return $query->whereIn('typ', $werte);
    }

    /**
     * Nur Logs eines bestimmten Typs
     */
    public function scopeVonTyp($query, GebaeudeLogTyp $typ)
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
     * Nur Probleme/Reklamationen
     */
    public function scopeProbleme($query)
    {
        return $query->kategorie('probleme');
    }

    /**
     * Offene Probleme (nicht behoben/erledigt)
     */
    public function scopeOffeneProbleme($query)
    {
        return $query->whereIn('typ', [
            GebaeudeLogTyp::REKLAMATION->value,
            GebaeudeLogTyp::PROBLEM->value,
            GebaeudeLogTyp::MANGEL->value,
            GebaeudeLogTyp::SCHADENSMELDUNG->value,
        ]);
    }

    /**
     * ⭐ NEU: Nur gelöschte Rechnungen (die wiederhergestellt werden können)
     */
    public function scopeGeloeschteRechnungen($query)
    {
        return $query->where('typ', GebaeudeLogTyp::RECHNUNG_GELOESCHT->value)
                     ->whereJsonContains('metadata->kann_wiederhergestellt_werden', true);
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
            'normal' => 'light text-dark',
            'hoch' => 'warning',
            'kritisch' => 'danger',
        ];
        
        if ($this->prioritaet === 'normal') {
            return '';
        }
        
        $farbe = $farben[$this->prioritaet] ?? 'secondary';
        $label = ucfirst($this->prioritaet);
        
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
        int $gebaeudeId,
        GebaeudeLogTyp $typ,
        ?string $beschreibung = null,
        array $metadata = [],
        ?string $titel = null
    ): self {
        return self::create([
            'gebaeude_id' => $gebaeudeId,
            'typ' => $typ,
            'titel' => $titel ?? $typ->label(),
            'beschreibung' => $beschreibung,
            'user_id' => Auth::id(),
            'metadata' => $metadata ?: null,
        ]);
    }

    // ═══════════════════════════════════════════════════════════
    // 📋 STAMMDATEN
    // ═══════════════════════════════════════════════════════════

    /**
     * Gebäude erstellt loggen
     */
    public static function erstellt(int $gebaeudeId, ?string $codex = null): self
    {
        return self::log(
            $gebaeudeId,
            GebaeudeLogTyp::ERSTELLT,
            $codex ? "Gebäude {$codex} wurde erstellt" : "Gebäude wurde erstellt",
            ['codex' => $codex]
        );
    }

    /**
     * Gebäude geändert loggen
     */
    public static function geaendert(int $gebaeudeId, array $geaenderteFelder = []): self
    {
        $beschreibung = null;
        if (!empty($geaenderteFelder)) {
            $felder = implode(', ', array_keys($geaenderteFelder));
            $beschreibung = "Geänderte Felder: {$felder}";
        }
        
        return self::log(
            $gebaeudeId,
            GebaeudeLogTyp::GEAENDERT,
            $beschreibung,
            ['geaendert' => $geaenderteFelder]
        );
    }

    // ═══════════════════════════════════════════════════════════
    // 🚐 TOUREN
    // ═══════════════════════════════════════════════════════════

    /**
     * Tour zugewiesen loggen
     */
    public static function tourZugewiesen(int $gebaeudeId, string $tourName, ?int $tourId = null): self
    {
        return self::log(
            $gebaeudeId,
            GebaeudeLogTyp::TOUR_ZUGEWIESEN,
            "Tour \"{$tourName}\" zugewiesen",
            ['tour_id' => $tourId, 'tour_name' => $tourName]
        );
    }

    /**
     * Tour entfernt loggen
     */
    public static function tourEntfernt(int $gebaeudeId, string $tourName, ?int $tourId = null): self
    {
        return self::log(
            $gebaeudeId,
            GebaeudeLogTyp::TOUR_ENTFERNT,
            "Tour \"{$tourName}\" entfernt",
            ['tour_id' => $tourId, 'tour_name' => $tourName]
        );
    }

    // ═══════════════════════════════════════════════════════════
    // 📦 ARTIKEL
    // ═══════════════════════════════════════════════════════════

    /**
     * Artikel hinzugefügt loggen
     */
    public static function artikelHinzugefuegt(
        int $gebaeudeId,
        string $beschreibung,
        float $preis,
        ?int $artikelId = null
    ): self {
        return self::log(
            $gebaeudeId,
            GebaeudeLogTyp::ARTIKEL_HINZUGEFUEGT,
            "\"{$beschreibung}\" für " . number_format($preis, 2, ',', '.') . " €",
            ['artikel_id' => $artikelId, 'beschreibung' => $beschreibung, 'preis' => $preis]
        );
    }

    /**
     * Artikel geändert loggen
     */
    public static function artikelGeaendert(
        int $gebaeudeId,
        string $beschreibung,
        ?float $alterPreis = null,
        ?float $neuerPreis = null,
        ?int $artikelId = null
    ): self {
        $text = "\"{$beschreibung}\"";
        if ($alterPreis !== null && $neuerPreis !== null && $alterPreis != $neuerPreis) {
            $text .= sprintf(
                " - Preis: %s → %s €",
                number_format($alterPreis, 2, ',', '.'),
                number_format($neuerPreis, 2, ',', '.')
            );
        }
        
        return self::log(
            $gebaeudeId,
            GebaeudeLogTyp::ARTIKEL_GEAENDERT,
            $text,
            [
                'artikel_id' => $artikelId,
                'beschreibung' => $beschreibung,
                'alter_preis' => $alterPreis,
                'neuer_preis' => $neuerPreis,
            ]
        );
    }

    /**
     * Artikel entfernt loggen
     */
    public static function artikelEntfernt(int $gebaeudeId, string $beschreibung, ?int $artikelId = null): self
    {
        return self::log(
            $gebaeudeId,
            GebaeudeLogTyp::ARTIKEL_ENTFERNT,
            "\"{$beschreibung}\" entfernt",
            ['artikel_id' => $artikelId, 'beschreibung' => $beschreibung]
        );
    }

    // ═══════════════════════════════════════════════════════════
    // 💰 FINANZEN
    // ═══════════════════════════════════════════════════════════

    /**
     * Aufschlag gesetzt loggen
     */
    public static function aufschlagGesetzt(int $gebaeudeId, float $prozent, ?string $grund = null): self
    {
        $beschreibung = sprintf("Individueller Aufschlag: %+.2f%%", $prozent);
        if ($grund) {
            $beschreibung .= " - {$grund}";
        }
        
        return self::log(
            $gebaeudeId,
            GebaeudeLogTyp::AUFSCHLAG_GESETZT,
            $beschreibung,
            ['prozent' => $prozent, 'grund' => $grund]
        );
    }

    /**
     * Aufschlag entfernt loggen
     */
    public static function aufschlagEntfernt(int $gebaeudeId): self
    {
        return self::log(
            $gebaeudeId,
            GebaeudeLogTyp::AUFSCHLAG_ENTFERNT,
            "Individueller Aufschlag entfernt - nutzt nun globalen Aufschlag"
        );
    }

    /**
     * Rechnung erstellt loggen
     */
    public static function rechnungErstellt(
        int $gebaeudeId,
        string $rechnungsnummer,
        float $betrag,
        ?int $rechnungId = null
    ): self {
        return self::log(
            $gebaeudeId,
            GebaeudeLogTyp::RECHNUNG_ERSTELLT,
            "Rechnung {$rechnungsnummer} erstellt: " . number_format($betrag, 2, ',', '.') . " €",
            [
                'rechnung_id' => $rechnungId,
                'rechnungsnummer' => $rechnungsnummer,
                'betrag' => $betrag,
            ]
        );
    }

    /**
     * ⭐ NEU: Rechnung gelöscht loggen (mit Wiederherstellungs-Möglichkeit)
     */
    public static function rechnungGeloescht(
        int $gebaeudeId,
        string $rechnungsnummer,
        float $betrag,
        string $loeschgrund,
        int $rechnungId
    ): self {
        return self::create([
            'gebaeude_id'  => $gebaeudeId,
            'typ'          => GebaeudeLogTyp::RECHNUNG_GELOESCHT,
            'titel'        => "Rechnung {$rechnungsnummer} gelöscht",
            'beschreibung' => $loeschgrund,
            'user_id'      => Auth::id(),
            'prioritaet'   => 'hoch',
            'referenz_id'  => $rechnungId,
            'referenz_typ' => 'rechnung',
            'metadata'     => [
                'rechnung_id'     => $rechnungId,
                'rechnungsnummer' => $rechnungsnummer,
                'betrag'          => $betrag,
                'loeschgrund'     => $loeschgrund,
                'geloescht_von'   => Auth::user()?->name ?? 'System',
                'geloescht_am'    => now()->format('Y-m-d H:i:s'),
                'kann_wiederhergestellt_werden' => true,
            ],
        ]);
    }

    /**
     * ⭐ NEU: Rechnung wiederhergestellt loggen
     */
    public static function rechnungWiederhergestellt(
        int $gebaeudeId,
        string $rechnungsnummer,
        int $rechnungId
    ): self {
        return self::log(
            $gebaeudeId,
            GebaeudeLogTyp::RECHNUNG_WIEDERHERGESTELLT,
            "Rechnung {$rechnungsnummer} wurde wiederhergestellt",
            [
                'rechnung_id'            => $rechnungId,
                'rechnungsnummer'        => $rechnungsnummer,
                'wiederhergestellt_von'  => Auth::user()?->name ?? 'System',
                'wiederhergestellt_am'   => now()->format('Y-m-d H:i:s'),
            ],
            "Rechnung {$rechnungsnummer} wiederhergestellt"
        );
    }

    /**
     * Angebot erstellt loggen
     */
    public static function angebotErstellt(
        int $gebaeudeId,
        string $angebotsnummer,
        float $betrag,
        ?int $angebotId = null
    ): self {
        return self::log(
            $gebaeudeId,
            GebaeudeLogTyp::ANGEBOT_ERSTELLT,
            "Angebot {$angebotsnummer} erstellt: " . number_format($betrag, 2, ',', '.') . " €",
            [
                'angebot_id' => $angebotId,
                'angebotsnummer' => $angebotsnummer,
                'betrag' => $betrag,
            ]
        );
    }

    // ═══════════════════════════════════════════════════════════
    // 🧹 REINIGUNG
    // ═══════════════════════════════════════════════════════════

    /**
     * Reinigung durchgeführt loggen
     */
    public static function reinigungDurchgefuehrt(int $gebaeudeId, ?string $datum = null, ?string $bemerkung = null): self
    {
        $beschreibung = $datum ? "Reinigung am {$datum}" : "Reinigung durchgeführt";
        if ($bemerkung) {
            $beschreibung .= " - {$bemerkung}";
        }
        
        return self::log(
            $gebaeudeId,
            GebaeudeLogTyp::REINIGUNG_DURCHGEFUEHRT,
            $beschreibung,
            ['datum' => $datum, 'bemerkung' => $bemerkung]
        );
    }

    /**
     * Reinigungsplan geändert loggen
     */
    public static function reinigungsplanGeaendert(int $gebaeudeId, array $aktiveMonate = []): self
    {
        $monatNamen = ['Jan', 'Feb', 'Mär', 'Apr', 'Mai', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dez'];
        $aktiv = [];
        foreach ($aktiveMonate as $monat => $status) {
            if ($status) {
                $idx = (int) ltrim($monat, 'm') - 1;
                $aktiv[] = $monatNamen[$idx] ?? $monat;
            }
        }
        
        $beschreibung = empty($aktiv) 
            ? "Reinigungsplan deaktiviert" 
            : "Aktive Monate: " . implode(', ', $aktiv);
        
        return self::log(
            $gebaeudeId,
            GebaeudeLogTyp::REINIGUNGSPLAN_GEAENDERT,
            $beschreibung,
            ['monate' => $aktiveMonate]
        );
    }

    // ═══════════════════════════════════════════════════════════
    // 💬 KOMMUNIKATION
    // ═══════════════════════════════════════════════════════════

    /**
     * Notiz hinzufügen
     */
    public static function notiz(
        int $gebaeudeId,
        string $beschreibung,
        string $prioritaet = 'normal',
        ?string $erinnerungDatum = null
    ): self {
        return self::create([
            'gebaeude_id' => $gebaeudeId,
            'typ' => GebaeudeLogTyp::NOTIZ,
            'titel' => 'Notiz',
            'beschreibung' => $beschreibung,
            'user_id' => Auth::id(),
            'prioritaet' => $prioritaet,
            'erinnerung_datum' => $erinnerungDatum,
        ]);
    }

    /**
     * Telefonat loggen
     */
    public static function telefonat(
        int $gebaeudeId,
        string $beschreibung,
        ?string $kontaktPerson = null,
        ?string $telefon = null
    ): self {
        return self::create([
            'gebaeude_id' => $gebaeudeId,
            'typ' => GebaeudeLogTyp::TELEFONAT,
            'titel' => 'Telefonat',
            'beschreibung' => $beschreibung,
            'user_id' => Auth::id(),
            'kontakt_person' => $kontaktPerson,
            'kontakt_telefon' => $telefon,
        ]);
    }

    /**
     * E-Mail versandt loggen
     */
    public static function emailVersandt(
        int $gebaeudeId,
        string $betreff,
        string $empfaenger,
        ?string $beschreibung = null
    ): self {
        return self::create([
            'gebaeude_id' => $gebaeudeId,
            'typ' => GebaeudeLogTyp::EMAIL_VERSANDT,
            'titel' => 'E-Mail versandt',
            'beschreibung' => $beschreibung ?? "An: {$empfaenger} - Betreff: {$betreff}",
            'user_id' => Auth::id(),
            'kontakt_email' => $empfaenger,
            'metadata' => ['betreff' => $betreff, 'empfaenger' => $empfaenger],
        ]);
    }

    // ═══════════════════════════════════════════════════════════
    // ⚠️ PROBLEME
    // ═══════════════════════════════════════════════════════════

    /**
     * Reklamation loggen
     */
    public static function reklamation(
        int $gebaeudeId,
        string $beschreibung,
        string $prioritaet = 'hoch',
        ?string $kontaktPerson = null
    ): self {
        return self::create([
            'gebaeude_id' => $gebaeudeId,
            'typ' => GebaeudeLogTyp::REKLAMATION,
            'titel' => 'Reklamation',
            'beschreibung' => $beschreibung,
            'user_id' => Auth::id(),
            'prioritaet' => $prioritaet,
            'kontakt_person' => $kontaktPerson,
        ]);
    }

    /**
     * Problem loggen
     */
    public static function problem(
        int $gebaeudeId,
        string $beschreibung,
        string $prioritaet = 'hoch'
    ): self {
        return self::create([
            'gebaeude_id' => $gebaeudeId,
            'typ' => GebaeudeLogTyp::PROBLEM,
            'titel' => 'Problem',
            'beschreibung' => $beschreibung,
            'user_id' => Auth::id(),
            'prioritaet' => $prioritaet,
        ]);
    }

    /**
     * Reklamation/Problem als erledigt markieren
     */
    public static function problemBehoben(int $gebaeudeId, string $beschreibung, ?int $referenzLogId = null): self
    {
        return self::log(
            $gebaeudeId,
            GebaeudeLogTyp::PROBLEM_BEHOBEN,
            $beschreibung,
            ['referenz_log_id' => $referenzLogId]
        );
    }

    // ═══════════════════════════════════════════════════════════
    // ⏰ ERINNERUNGEN
    // ═══════════════════════════════════════════════════════════

    /**
     * Erinnerung erstellen
     */
    public static function erinnerung(
        int $gebaeudeId,
        string $beschreibung,
        string $datum,
        string $prioritaet = 'normal'
    ): self {
        return self::create([
            'gebaeude_id' => $gebaeudeId,
            'typ' => GebaeudeLogTyp::ERINNERUNG,
            'titel' => 'Erinnerung',
            'beschreibung' => $beschreibung,
            'user_id' => Auth::id(),
            'prioritaet' => $prioritaet,
            'erinnerung_datum' => $datum,
            'erinnerung_erledigt' => false,
        ]);
    }

    /**
     * Erinnerung als erledigt markieren
     */
    public function alsErledigtMarkieren(): bool
    {
        return $this->update(['erinnerung_erledigt' => true]);
    }

    /**
     * ⭐ NEU: Nach Wiederherstellung einer Rechnung - Flag im Log-Eintrag aktualisieren
     */
    public function markiereAlsWiederhergestellt(): bool
    {
        $metadata = $this->metadata ?? [];
        $metadata['kann_wiederhergestellt_werden'] = false;
        $metadata['wiederhergestellt_am'] = now()->format('Y-m-d H:i:s');
        
        return $this->update(['metadata' => $metadata]);
    }
}
