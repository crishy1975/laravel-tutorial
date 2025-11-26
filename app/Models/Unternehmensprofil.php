<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Unternehmensprofil
 * 
 * Zentrale Verwaltung aller Firmeneinstellungen:
 * - Firmendaten (deutsch)
 * - E-Mail-Versand
 * - PDF/Briefkopf Design
 * - FatturaPA (italienisch)
 */
class Unternehmensprofil extends Model
{
    protected $table = 'unternehmensprofil';

    protected $fillable = [
        // Firmendaten
        'firmenname',
        'firma_zusatz',
        'geschaeftsfuehrer',
        'handelsregister',
        'registergericht',
        
        // Adresse
        'strasse',
        'hausnummer',
        'adresszusatz',
        'postleitzahl',
        'ort',
        'bundesland',
        'land',
        
        // Kontakt
        'telefon',
        'telefon_mobil',
        'fax',
        'email',
        'email_buchhaltung',
        'website',
        
        // Steuern
        'steuernummer',
        'umsatzsteuer_id',
        
        // Bank
        'bank_name',
        'iban',
        'bic',
        'kontoinhaber',
        
        // E-Mail Versand (Normal)
        'smtp_host',
        'smtp_port',
        'smtp_verschluesselung',
        'smtp_benutzername',
        'smtp_passwort',
        'email_absender',
        'email_absender_name',
        'email_antwort_an',
        'email_cc',
        'email_bcc',
        'email_signatur',
        'email_fusszeile',
        
        // PEC E-Mail Versand
        'pec_smtp_host',
        'pec_smtp_port',
        'pec_smtp_verschluesselung',
        'pec_smtp_benutzername',
        'pec_smtp_passwort',
        'pec_email_absender',
        'pec_email_absender_name',
        'pec_email_antwort_an',
        'pec_email_cc',
        'pec_email_bcc',
        'pec_email_signatur',
        'pec_email_fusszeile',
        'pec_aktiv',
        
        // PDF/Briefkopf
        'logo_pfad',
        'logo_rechnung_pfad',
        'logo_email_pfad',
        'logo_breite',
        'logo_hoehe',
        'briefkopf_text',
        'briefkopf_rechts',
        'fusszeile_text',
        'farbe_primaer',
        'farbe_sekundaer',
        'farbe_akzent',
        'schriftart',
        'schriftgroesse',
        
        // Rechnungen
        'rechnungsnummer_praefix',
        'rechnungsnummer_startjahr',
        'rechnungsnummer_laenge',
        'zahlungsziel_tage',
        'zahlungshinweis',
        'kleinunternehmer_hinweis',
        'rechnung_einleitung',
        'rechnung_schlusstext',
        'rechnung_agb_text',
        
        // FatturaPA (italienisch)
        'ragione_sociale',
        'partita_iva',
        'codice_fiscale',
        'regime_fiscale',
        'pec_email',
        'rea_ufficio',
        'rea_numero',
        'capitale_sociale',
        'stato_liquidazione',
        
        // System
        'waehrung',
        'sprache',
        'zeitzone',
        'datumsformat',
        'zahlenformat',
        'ist_kleinunternehmer',
        'mwst_ausweisen',
        'standard_mwst_satz',
        'ist_aktiv',
        'notizen',
    ];

    protected $casts = [
        'smtp_port'               => 'integer',
        'pec_smtp_port'           => 'integer',
        'logo_breite'             => 'integer',
        'logo_hoehe'              => 'integer',
        'rechnungsnummer_startjahr' => 'integer',
        'rechnungsnummer_laenge'  => 'integer',
        'zahlungsziel_tage'       => 'integer',
        'capitale_sociale'        => 'decimal:2',
        'standard_mwst_satz'      => 'decimal:2',
        'ist_kleinunternehmer'    => 'boolean',
        'mwst_ausweisen'          => 'boolean',
        'ist_aktiv'               => 'boolean',
        'pec_aktiv'               => 'boolean',
    ];

    protected $hidden = [
        'smtp_passwort',      // Passwort nicht in Arrays/JSON ausgeben
        'pec_smtp_passwort',  // PEC Passwort auch verstecken
    ];

    // ═══════════════════════════════════════════════════════════
    // 🔍 STATIC HELPERS
    // ═══════════════════════════════════════════════════════════

    /**
     * Gibt das aktive Profil zurück.
     */
    public static function aktiv(): ?self
    {
        return self::where('ist_aktiv', true)->first();
    }

    /**
     * Gibt das aktive Profil zurück oder wirft Exception.
     */
    public static function aktivOderFehler(): self
    {
        $profil = self::aktiv();
        
        if (!$profil) {
            throw new \RuntimeException(
                'Kein aktives Unternehmensprofil gefunden. ' .
                'Bitte zuerst ein Profil in den Einstellungen erstellen.'
            );
        }
        
        return $profil;
    }

    // ═══════════════════════════════════════════════════════════
    // 🏢 FIRMENDATEN
    // ═══════════════════════════════════════════════════════════

    /**
     * Vollständiger Firmenname mit Zusatz.
     */
    public function getVollstaendigerFirmennameAttribute(): string
    {
        $parts = [$this->firmenname];
        
        if ($this->firma_zusatz) {
            $parts[] = $this->firma_zusatz;
        }
        
        return implode(' ', $parts);
    }

    /**
     * Vollständige Adresse als String.
     */
    public function getVollstaendigeAdresseAttribute(): string
    {
        $parts = [
            $this->strasse . ' ' . $this->hausnummer,
        ];
        
        if ($this->adresszusatz) {
            $parts[] = $this->adresszusatz;
        }
        
        $parts[] = $this->postleitzahl . ' ' . $this->ort;
        
        if ($this->bundesland) {
            $parts[] = $this->bundesland;
        }
        
        return implode("\n", $parts);
    }

    /**
     * Vollständige Adresse als mehrzeiliger Array (für PDFs).
     */
    public function getAdresseZeilenAttribute(): array
    {
        $zeilen = [
            $this->firmenname,
        ];
        
        if ($this->firma_zusatz) {
            $zeilen[] = $this->firma_zusatz;
        }
        
        $zeilen[] = $this->strasse . ' ' . $this->hausnummer;
        
        if ($this->adresszusatz) {
            $zeilen[] = $this->adresszusatz;
        }
        
        $zeilen[] = $this->postleitzahl . ' ' . $this->ort;
        
        return $zeilen;
    }

    // ═══════════════════════════════════════════════════════════
    // 📧 E-MAIL FUNKTIONEN
    // ═══════════════════════════════════════════════════════════

    /**
     * SMTP-Konfiguration als Array (für Mail-Config).
     */
    public function getSmtpConfigAttribute(): array
    {
        return [
            'host'       => $this->smtp_host,
            'port'       => $this->smtp_port,
            'encryption' => $this->smtp_verschluesselung,
            'username'   => $this->smtp_benutzername,
            'password'   => $this->smtp_passwort, // Sollte verschlüsselt sein!
            'from'       => [
                'address' => $this->email_absender ?? $this->email,
                'name'    => $this->email_absender_name ?? $this->firmenname,
            ],
        ];
    }

    /**
     * PEC-SMTP-Konfiguration als Array (für Mail-Config).
     */
    public function getPecSmtpConfigAttribute(): array
    {
        return [
            'host'       => $this->pec_smtp_host,
            'port'       => $this->pec_smtp_port,
            'encryption' => $this->pec_smtp_verschluesselung,
            'username'   => $this->pec_smtp_benutzername,
            'password'   => $this->pec_smtp_passwort, // Sollte verschlüsselt sein!
            'from'       => [
                'address' => $this->pec_email_absender ?? $this->pec_email,
                'name'    => $this->pec_email_absender_name ?? $this->firmenname,
            ],
        ];
    }

    /**
     * Hat vollständige SMTP-Konfiguration?
     */
    public function hatSmtpKonfiguration(): bool
    {
        return !empty($this->smtp_host) 
            && !empty($this->smtp_port) 
            && !empty($this->smtp_benutzername) 
            && !empty($this->smtp_passwort);
    }

    /**
     * Hat vollständige PEC-SMTP-Konfiguration?
     */
    public function hatPecSmtpKonfiguration(): bool
    {
        return !empty($this->pec_smtp_host) 
            && !empty($this->pec_smtp_port) 
            && !empty($this->pec_smtp_benutzername) 
            && !empty($this->pec_smtp_passwort);
    }

    /**
     * E-Mail Signatur mit Standard-Fußzeile.
     */
    public function getEmailSignaturVollstaendigAttribute(): string
    {
        $parts = [];
        
        if ($this->email_signatur) {
            $parts[] = $this->email_signatur;
        }
        
        if ($this->email_fusszeile) {
            $parts[] = $this->email_fusszeile;
        }
        
        return implode("\n\n", $parts);
    }

    /**
     * PEC E-Mail Signatur mit Standard-Fußzeile.
     */
    public function getPecEmailSignaturVollstaendigAttribute(): string
    {
        $parts = [];
        
        if ($this->pec_email_signatur) {
            $parts[] = $this->pec_email_signatur;
        }
        
        if ($this->pec_email_fusszeile) {
            $parts[] = $this->pec_email_fusszeile;
        }
        
        return implode("\n\n", $parts);
    }

    // ═══════════════════════════════════════════════════════════
    // 🎨 LOGO & DESIGN
    // ═══════════════════════════════════════════════════════════

    /**
     * Logo-URL (vollständiger Pfad).
     */
    public function getLogoUrlAttribute(): ?string
    {
        if (!$this->logo_pfad) {
            return null;
        }
        
        return Storage::url($this->logo_pfad);
    }

    /**
     * Rechnungs-Logo-URL.
     */
    public function getRechnungsLogoUrlAttribute(): ?string
    {
        $pfad = $this->logo_rechnung_pfad ?? $this->logo_pfad;
        
        if (!$pfad) {
            return null;
        }
        
        return Storage::url($pfad);
    }

    /**
     * E-Mail-Logo-URL.
     */
    public function getEmailLogoUrlAttribute(): ?string
    {
        $pfad = $this->logo_email_pfad ?? $this->logo_pfad;
        
        if (!$pfad) {
            return null;
        }
        
        return Storage::url($pfad);
    }

    /**
     * Hat Logo?
     */
    public function hatLogo(): bool
    {
        return !empty($this->logo_pfad) && Storage::exists($this->logo_pfad);
    }

    /**
     * Logo hochladen und speichern.
     * 
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $typ 'haupt', 'rechnung', 'email'
     * @return string Pfad zum gespeicherten Logo
     */
    public function logoHochladen($file, string $typ = 'haupt'): string
    {
        // Dateiname generieren
        $extension = $file->getClientOriginalExtension();
        $filename = 'logo_' . $typ . '_' . time() . '.' . $extension;
        
        // Speichern in public/logos
        $pfad = $file->storeAs('logos', $filename, 'public');
        
        // Pfad im Profil speichern
        switch ($typ) {
            case 'rechnung':
                $this->logo_rechnung_pfad = $pfad;
                break;
            case 'email':
                $this->logo_email_pfad = $pfad;
                break;
            default:
                $this->logo_pfad = $pfad;
        }
        
        $this->save();
        
        return $pfad;
    }

    // ═══════════════════════════════════════════════════════════
    // 📄 RECHNUNGS-FUNKTIONEN
    // ═══════════════════════════════════════════════════════════

    /**
     * Nächste Rechnungsnummer generieren.
     */
    public function naechsteRechnungsnummer(int $jahr): string
    {
        $praefix = $this->rechnungsnummer_praefix ?? 'RE-';
        $laenge = $this->rechnungsnummer_laenge ?? 5;
        
        // Höchste Laufnummer für das Jahr ermitteln
        $maxLaufnummer = \App\Models\Rechnung::where('jahr', $jahr)
            ->max('laufnummer') ?? 0;
        
        $neueLaufnummer = $maxLaufnummer + 1;
        
        return sprintf(
            '%s%d/%0' . $laenge . 'd',
            $praefix,
            $jahr,
            $neueLaufnummer
        );
    }

    /**
     * Standard Zahlungsziel-Datum berechnen.
     */
    public function getStandardZahlungszielAttribute(): \Carbon\Carbon
    {
        $tage = $this->zahlungsziel_tage ?? 30;
        return now()->addDays($tage);
    }

    // ═══════════════════════════════════════════════════════════
    // 🇮🇹 FATTURAPA-FUNKTIONEN
    // ═══════════════════════════════════════════════════════════

    /**
     * Partita IVA ohne "IT" Präfix.
     */
    public function getPartitaIvaNumericAttribute(): string
    {
        return preg_replace('/^IT/', '', $this->partita_iva ?? '');
    }

    /**
     * Partita IVA mit "IT" Präfix.
     */
    public function getPartitaIvaFormattertAttribute(): string
    {
        $numeric = $this->partita_iva_numeric;
        
        if (empty($numeric)) {
            return '';
        }
        
        return 'IT' . $numeric;
    }

    /**
     * Ist für FatturaPA konfiguriert?
     */
    public function istFatturapaKonfiguriert(): bool
    {
        $erforderlich = [
            'ragione_sociale',
            'partita_iva',
            'codice_fiscale',
            'regime_fiscale',
            'pec_email',
        ];
        
        foreach ($erforderlich as $feld) {
            if (empty($this->$feld)) {
                return false;
            }
        }
        
        // Partita IVA Format prüfen
        if (!preg_match('/^\d{11}$/', $this->partita_iva_numeric)) {
            return false;
        }
        
        return true;
    }

    /**
     * Fehlende FatturaPA-Felder.
     */
    public function fehlendeFelderFatturaPA(): array
    {
        $erforderlich = [
            'ragione_sociale'  => 'Ragione Sociale (Firmenname IT)',
            'partita_iva'      => 'Partita IVA (11 Ziffern)',
            'codice_fiscale'   => 'Codice Fiscale',
            'regime_fiscale'   => 'Regime Fiscale (RF01-RF19)',
            'pec_email'        => 'PEC E-Mail (zertifiziert)',
        ];
        
        $fehlend = [];
        
        foreach ($erforderlich as $feld => $label) {
            if (empty($this->$feld)) {
                $fehlend[$feld] = $label;
            }
        }
        
        return $fehlend;
    }

    // ═══════════════════════════════════════════════════════════
    // ✅ VALIDIERUNG
    // ═══════════════════════════════════════════════════════════

    /**
     * Ist das Profil vollständig?
     */
    public function istVollstaendig(): bool
    {
        $pflichtfelder = [
            'firmenname',
            'strasse',
            'hausnummer',
            'postleitzahl',
            'ort',
            'email',
        ];
        
        foreach ($pflichtfelder as $feld) {
            if (empty($this->$feld)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Fehlende Pflichtfelder.
     */
    public function fehlendePflichtfelder(): array
    {
        $pflichtfelder = [
            'firmenname'    => 'Firmenname',
            'strasse'       => 'Straße',
            'hausnummer'    => 'Hausnummer',
            'postleitzahl'  => 'Postleitzahl',
            'ort'           => 'Ort',
            'email'         => 'E-Mail',
        ];
        
        $fehlend = [];
        
        foreach ($pflichtfelder as $feld => $label) {
            if (empty($this->$feld)) {
                $fehlend[$feld] = $label;
            }
        }
        
        return $fehlend;
    }

    // ═══════════════════════════════════════════════════════════
    // 🏷️ ACCESSORS (für UI)
    // ═══════════════════════════════════════════════════════════

    /**
     * Status-Badge für UI.
     */
    public function getStatusBadgeAttribute(): string
    {
        if (!$this->ist_aktiv) {
            return '<span class="badge bg-secondary">Inaktiv</span>';
        }
        
        if (!$this->istVollstaendig()) {
            return '<span class="badge bg-warning">Unvollständig</span>';
        }
        
        return '<span class="badge bg-success">Aktiv</span>';
    }

    /**
     * FatturaPA-Status-Badge.
     */
    public function getFatturapaStatusBadgeAttribute(): string
    {
        if (!$this->istFatturapaKonfiguriert()) {
            return '<span class="badge bg-secondary">Nicht konfiguriert</span>';
        }
        
        return '<span class="badge bg-success">✓ FatturaPA bereit</span>';
    }

    // ═══════════════════════════════════════════════════════════
    // 🔧 HELPER
    // ═══════════════════════════════════════════════════════════

    /**
     * Formatiert IBAN für Anzeige (mit Leerzeichen).
     */
    public function getIbanFormatiertAttribute(): string
    {
        if (!$this->iban) {
            return '';
        }
        
        return chunk_split($this->iban, 4, ' ');
    }

    /**
     * Regime Fiscale Optionen.
     */
    public static function getRegimeFiscaleOptionen(): array
    {
        return [
            'RF01' => 'Ordinario',
            'RF02' => 'Contribuenti minimi',
            'RF04' => 'Agricoltura e attività connesse',
            'RF19' => 'Forfettario',
        ];
    }
}