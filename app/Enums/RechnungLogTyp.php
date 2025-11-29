<?php

namespace App\Enums;

/**
 * Enum für Rechnungs-Log Typen
 * 
 * Kategorien:
 * - DOKUMENT: XML, PDF, Mahnung
 * - VERSAND: Email, Post, PEC
 * - KOMMUNIKATION: Telefon, Mitteilung
 * - STATUS: Änderungen am Rechnungsstatus
 * - ZAHLUNG: Zahlungseingänge
 * - SYSTEM: Automatische Aktionen
 */
enum RechnungLogTyp: string
{
    // ═══════════════════════════════════════════════════════════
    // 📄 DOKUMENTE
    // ═══════════════════════════════════════════════════════════
    
    case XML_ERSTELLT = 'xml_erstellt';
    case XML_VALIDIERT = 'xml_validiert';
    case XML_SIGNIERT = 'xml_signiert';
    case XML_VERSANDT = 'xml_versandt';
    case XML_ZUGESTELLT = 'xml_zugestellt';
    case XML_AKZEPTIERT = 'xml_akzeptiert';
    case XML_ABGELEHNT = 'xml_abgelehnt';
    case XML_FEHLER = 'xml_fehler';
    
    case PDF_ERSTELLT = 'pdf_erstellt';
    case PDF_VERSANDT = 'pdf_versandt';
    
    case MAHNUNG_ERSTELLT = 'mahnung_erstellt';
    case MAHNUNG_VERSANDT = 'mahnung_versandt';
    
    // ═══════════════════════════════════════════════════════════
    // 📧 VERSAND
    // ═══════════════════════════════════════════════════════════
    
    case EMAIL_VERSANDT = 'email_versandt';
    case EMAIL_GELESEN = 'email_gelesen';
    case EMAIL_FEHLER = 'email_fehler';
    
    case PEC_VERSANDT = 'pec_versandt';
    case PEC_ZUGESTELLT = 'pec_zugestellt';
    case PEC_FEHLER = 'pec_fehler';
    
    case POST_VERSANDT = 'post_versandt';
    
    // ═══════════════════════════════════════════════════════════
    // 📞 KOMMUNIKATION
    // ═══════════════════════════════════════════════════════════
    
    case TELEFONAT = 'telefonat';
    case TELEFONAT_EINGEHEND = 'telefonat_eingehend';
    case TELEFONAT_AUSGEHEND = 'telefonat_ausgehend';
    case TELEFONAT_VERPASST = 'telefonat_verpasst';
    
    case MITTEILUNG_KUNDE = 'mitteilung_kunde';
    case MITTEILUNG_INTERN = 'mitteilung_intern';
    
    case RUECKRUF_ANGEFORDERT = 'rueckruf_angefordert';
    case RUECKRUF_ERLEDIGT = 'rueckruf_erledigt';
    
    // ═══════════════════════════════════════════════════════════
    // 🔄 STATUS-ÄNDERUNGEN
    // ═══════════════════════════════════════════════════════════
    
    case STATUS_ENTWURF = 'status_entwurf';
    case STATUS_VERSENDET = 'status_versendet';
    case STATUS_BEZAHLT = 'status_bezahlt';
    case STATUS_STORNIERT = 'status_storniert';
    case STATUS_UEBERFAELLIG = 'status_ueberfaellig';
    
    // ═══════════════════════════════════════════════════════════
    // 💰 ZAHLUNGEN
    // ═══════════════════════════════════════════════════════════
    
    case ZAHLUNG_EINGEGANGEN = 'zahlung_eingegangen';
    case ZAHLUNG_TEILWEISE = 'zahlung_teilweise';
    case ZAHLUNG_RUECKBUCHUNG = 'zahlung_rueckbuchung';
    case ZAHLUNG_ERINNERUNG = 'zahlung_erinnerung';
    
    // ═══════════════════════════════════════════════════════════
    // ⚙️ SYSTEM / SONSTIGES
    // ═══════════════════════════════════════════════════════════
    
    case RECHNUNG_ERSTELLT = 'rechnung_erstellt';
    case RECHNUNG_BEARBEITET = 'rechnung_bearbeitet';
    case RECHNUNG_KOPIERT = 'rechnung_kopiert';
    
    case NOTIZ = 'notiz';
    case ERINNERUNG = 'erinnerung';
    case WIEDERVORLAGE = 'wiedervorlage';
    
    case SYSTEM_AUTO = 'system_auto';
    case IMPORT = 'import';
    case EXPORT = 'export';

    // ═══════════════════════════════════════════════════════════
    // 🏷️ LABELS & ICONS
    // ═══════════════════════════════════════════════════════════

    /**
     * Deutsches Label für Anzeige
     */
    public function label(): string
    {
        return match($this) {
            // Dokumente
            self::XML_ERSTELLT => 'XML erstellt',
            self::XML_VALIDIERT => 'XML validiert',
            self::XML_SIGNIERT => 'XML signiert',
            self::XML_VERSANDT => 'XML versandt',
            self::XML_ZUGESTELLT => 'XML zugestellt',
            self::XML_AKZEPTIERT => 'XML akzeptiert',
            self::XML_ABGELEHNT => 'XML abgelehnt',
            self::XML_FEHLER => 'XML Fehler',
            
            self::PDF_ERSTELLT => 'PDF erstellt',
            self::PDF_VERSANDT => 'PDF versandt',
            
            self::MAHNUNG_ERSTELLT => 'Mahnung erstellt',
            self::MAHNUNG_VERSANDT => 'Mahnung versandt',
            
            // Versand
            self::EMAIL_VERSANDT => 'E-Mail versandt',
            self::EMAIL_GELESEN => 'E-Mail gelesen',
            self::EMAIL_FEHLER => 'E-Mail Fehler',
            
            self::PEC_VERSANDT => 'PEC versandt',
            self::PEC_ZUGESTELLT => 'PEC zugestellt',
            self::PEC_FEHLER => 'PEC Fehler',
            
            self::POST_VERSANDT => 'Per Post versandt',
            
            // Kommunikation
            self::TELEFONAT => 'Telefonat',
            self::TELEFONAT_EINGEHEND => 'Anruf erhalten',
            self::TELEFONAT_AUSGEHEND => 'Anruf getätigt',
            self::TELEFONAT_VERPASST => 'Anruf verpasst',
            
            self::MITTEILUNG_KUNDE => 'Mitteilung vom Kunden',
            self::MITTEILUNG_INTERN => 'Interne Notiz',
            
            self::RUECKRUF_ANGEFORDERT => 'Rückruf angefordert',
            self::RUECKRUF_ERLEDIGT => 'Rückruf erledigt',
            
            // Status
            self::STATUS_ENTWURF => 'Status: Entwurf',
            self::STATUS_VERSENDET => 'Status: Versendet',
            self::STATUS_BEZAHLT => 'Status: Bezahlt',
            self::STATUS_STORNIERT => 'Status: Storniert',
            self::STATUS_UEBERFAELLIG => 'Status: Überfällig',
            
            // Zahlungen
            self::ZAHLUNG_EINGEGANGEN => 'Zahlung eingegangen',
            self::ZAHLUNG_TEILWEISE => 'Teilzahlung eingegangen',
            self::ZAHLUNG_RUECKBUCHUNG => 'Rückbuchung',
            self::ZAHLUNG_ERINNERUNG => 'Zahlungserinnerung',
            
            // System
            self::RECHNUNG_ERSTELLT => 'Rechnung erstellt',
            self::RECHNUNG_BEARBEITET => 'Rechnung bearbeitet',
            self::RECHNUNG_KOPIERT => 'Rechnung kopiert',
            
            self::NOTIZ => 'Notiz',
            self::ERINNERUNG => 'Erinnerung',
            self::WIEDERVORLAGE => 'Wiedervorlage',
            
            self::SYSTEM_AUTO => 'Automatische Aktion',
            self::IMPORT => 'Import',
            self::EXPORT => 'Export',
        };
    }

    /**
     * Bootstrap Icon Name
     */
    public function icon(): string
    {
        return match($this) {
            // Dokumente
            self::XML_ERSTELLT, self::XML_VALIDIERT, self::XML_SIGNIERT => 'bi-file-earmark-code',
            self::XML_VERSANDT, self::XML_ZUGESTELLT => 'bi-send-check',
            self::XML_AKZEPTIERT => 'bi-check-circle',
            self::XML_ABGELEHNT, self::XML_FEHLER => 'bi-x-circle',
            
            self::PDF_ERSTELLT => 'bi-file-earmark-pdf',
            self::PDF_VERSANDT => 'bi-file-earmark-arrow-up',
            
            self::MAHNUNG_ERSTELLT, self::MAHNUNG_VERSANDT => 'bi-exclamation-triangle',
            
            // Versand
            self::EMAIL_VERSANDT, self::EMAIL_GELESEN => 'bi-envelope',
            self::EMAIL_FEHLER => 'bi-envelope-x',
            
            self::PEC_VERSANDT, self::PEC_ZUGESTELLT => 'bi-envelope-check',
            self::PEC_FEHLER => 'bi-envelope-x',
            
            self::POST_VERSANDT => 'bi-mailbox',
            
            // Kommunikation
            self::TELEFONAT, self::TELEFONAT_EINGEHEND, 
            self::TELEFONAT_AUSGEHEND, self::TELEFONAT_VERPASST => 'bi-telephone',
            
            self::MITTEILUNG_KUNDE => 'bi-chat-left-text',
            self::MITTEILUNG_INTERN => 'bi-chat-left-dots',
            
            self::RUECKRUF_ANGEFORDERT, self::RUECKRUF_ERLEDIGT => 'bi-telephone-forward',
            
            // Status
            self::STATUS_ENTWURF => 'bi-pencil',
            self::STATUS_VERSENDET => 'bi-send',
            self::STATUS_BEZAHLT => 'bi-check-circle',
            self::STATUS_STORNIERT => 'bi-x-circle',
            self::STATUS_UEBERFAELLIG => 'bi-alarm',
            
            // Zahlungen
            self::ZAHLUNG_EINGEGANGEN, self::ZAHLUNG_TEILWEISE => 'bi-currency-euro',
            self::ZAHLUNG_RUECKBUCHUNG => 'bi-arrow-return-left',
            self::ZAHLUNG_ERINNERUNG => 'bi-bell',
            
            // System
            self::RECHNUNG_ERSTELLT => 'bi-plus-circle',
            self::RECHNUNG_BEARBEITET => 'bi-pencil-square',
            self::RECHNUNG_KOPIERT => 'bi-files',
            
            self::NOTIZ => 'bi-sticky',
            self::ERINNERUNG => 'bi-bell',
            self::WIEDERVORLAGE => 'bi-calendar-event',
            
            self::SYSTEM_AUTO => 'bi-gear',
            self::IMPORT => 'bi-box-arrow-in-down',
            self::EXPORT => 'bi-box-arrow-up',
        };
    }

    /**
     * Bootstrap Farbe für Badge
     */
    public function farbe(): string
    {
        return match($this) {
            // Erfolg (grün)
            self::XML_AKZEPTIERT, self::XML_ZUGESTELLT, self::STATUS_BEZAHLT,
            self::ZAHLUNG_EINGEGANGEN, self::PEC_ZUGESTELLT,
            self::RUECKRUF_ERLEDIGT => 'success',
            
            // Info (blau)
            self::XML_ERSTELLT, self::XML_VALIDIERT, self::XML_SIGNIERT,
            self::PDF_ERSTELLT, self::EMAIL_VERSANDT, self::EMAIL_GELESEN,
            self::RECHNUNG_ERSTELLT, self::RECHNUNG_BEARBEITET => 'info',
            
            // Primary (dunkelblau)
            self::XML_VERSANDT, self::PDF_VERSANDT, self::PEC_VERSANDT,
            self::STATUS_VERSENDET, self::POST_VERSANDT => 'primary',
            
            // Warning (gelb/orange)
            self::MAHNUNG_ERSTELLT, self::MAHNUNG_VERSANDT,
            self::ZAHLUNG_ERINNERUNG, self::STATUS_UEBERFAELLIG,
            self::ZAHLUNG_TEILWEISE, self::ERINNERUNG, self::WIEDERVORLAGE,
            self::RUECKRUF_ANGEFORDERT => 'warning',
            
            // Danger (rot)
            self::XML_ABGELEHNT, self::XML_FEHLER, self::EMAIL_FEHLER,
            self::PEC_FEHLER, self::STATUS_STORNIERT, 
            self::ZAHLUNG_RUECKBUCHUNG, self::TELEFONAT_VERPASST => 'danger',
            
            // Secondary (grau)
            self::TELEFONAT, self::TELEFONAT_EINGEHEND, self::TELEFONAT_AUSGEHEND,
            self::MITTEILUNG_KUNDE, self::MITTEILUNG_INTERN, self::NOTIZ,
            self::STATUS_ENTWURF, self::RECHNUNG_KOPIERT => 'secondary',
            
            // Dark
            self::SYSTEM_AUTO, self::IMPORT, self::EXPORT => 'dark',
        };
    }

    /**
     * Kategorie für Gruppierung
     */
    public function kategorie(): string
    {
        return match($this) {
            self::XML_ERSTELLT, self::XML_VALIDIERT, self::XML_SIGNIERT,
            self::XML_VERSANDT, self::XML_ZUGESTELLT, self::XML_AKZEPTIERT,
            self::XML_ABGELEHNT, self::XML_FEHLER,
            self::PDF_ERSTELLT, self::PDF_VERSANDT,
            self::MAHNUNG_ERSTELLT, self::MAHNUNG_VERSANDT => 'dokument',
            
            self::EMAIL_VERSANDT, self::EMAIL_GELESEN, self::EMAIL_FEHLER,
            self::PEC_VERSANDT, self::PEC_ZUGESTELLT, self::PEC_FEHLER,
            self::POST_VERSANDT => 'versand',
            
            self::TELEFONAT, self::TELEFONAT_EINGEHEND, self::TELEFONAT_AUSGEHEND,
            self::TELEFONAT_VERPASST, self::MITTEILUNG_KUNDE, self::MITTEILUNG_INTERN,
            self::RUECKRUF_ANGEFORDERT, self::RUECKRUF_ERLEDIGT => 'kommunikation',
            
            self::STATUS_ENTWURF, self::STATUS_VERSENDET, self::STATUS_BEZAHLT,
            self::STATUS_STORNIERT, self::STATUS_UEBERFAELLIG => 'status',
            
            self::ZAHLUNG_EINGEGANGEN, self::ZAHLUNG_TEILWEISE,
            self::ZAHLUNG_RUECKBUCHUNG, self::ZAHLUNG_ERINNERUNG => 'zahlung',
            
            self::RECHNUNG_ERSTELLT, self::RECHNUNG_BEARBEITET, self::RECHNUNG_KOPIERT,
            self::NOTIZ, self::ERINNERUNG, self::WIEDERVORLAGE,
            self::SYSTEM_AUTO, self::IMPORT, self::EXPORT => 'system',
        };
    }

    /**
     * Alle Typen einer Kategorie
     */
    public static function byKategorie(string $kategorie): array
    {
        return array_filter(self::cases(), fn($typ) => $typ->kategorie() === $kategorie);
    }

    /**
     * Kategorien mit Labels
     */
    public static function kategorien(): array
    {
        return [
            'dokument' => 'Dokumente',
            'versand' => 'Versand',
            'kommunikation' => 'Kommunikation',
            'status' => 'Status',
            'zahlung' => 'Zahlungen',
            'system' => 'System',
        ];
    }
}