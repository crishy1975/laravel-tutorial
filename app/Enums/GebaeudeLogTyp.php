<?php

namespace App\Enums;

/**
 * Gebäude-Log Typen
 * 
 * Kategorien:
 * - stammdaten: Änderungen an Gebäude-Daten
 * - artikel: Artikel-Änderungen
 * - finanzen: Aufschläge, Rechnungen, Angebote
 * - reinigung: Reinigungsbezogen
 * - kommunikation: Kontakt, Notizen
 * - dokumente: Hochgeladene Dateien
 * - system: Automatische Einträge
 */
enum GebaeudeLogTyp: string
{
    // ═══════════════════════════════════════════════════════════
    // 📋 STAMMDATEN
    // ═══════════════════════════════════════════════════════════
    case ERSTELLT = 'erstellt';
    case GEAENDERT = 'geaendert';
    case GELOESCHT = 'geloescht';
    case WIEDERHERGESTELLT = 'wiederhergestellt';
    
    // ═══════════════════════════════════════════════════════════
    // 🚐 TOUREN
    // ═══════════════════════════════════════════════════════════
    case TOUR_ZUGEWIESEN = 'tour_zugewiesen';
    case TOUR_ENTFERNT = 'tour_entfernt';
    case TOUR_REIHENFOLGE = 'tour_reihenfolge';
    
    // ═══════════════════════════════════════════════════════════
    // 📦 ARTIKEL
    // ═══════════════════════════════════════════════════════════
    case ARTIKEL_HINZUGEFUEGT = 'artikel_hinzugefuegt';
    case ARTIKEL_GEAENDERT = 'artikel_geaendert';
    case ARTIKEL_ENTFERNT = 'artikel_entfernt';
    case ARTIKEL_DEAKTIVIERT = 'artikel_deaktiviert';
    case ARTIKEL_AKTIVIERT = 'artikel_aktiviert';
    
    // ═══════════════════════════════════════════════════════════
    // 💰 FINANZEN
    // ═══════════════════════════════════════════════════════════
    case PREIS_GEAENDERT = 'preis_geaendert';
    case AUFSCHLAG_GESETZT = 'aufschlag_gesetzt';
    case AUFSCHLAG_ENTFERNT = 'aufschlag_entfernt';
    case RECHNUNG_ERSTELLT = 'rechnung_erstellt';
    case ANGEBOT_ERSTELLT = 'angebot_erstellt';
    case FATTURA_PROFIL_GEAENDERT = 'fattura_profil_geaendert';
    
    // ═══════════════════════════════════════════════════════════
    // 🧹 REINIGUNG
    // ═══════════════════════════════════════════════════════════
    case REINIGUNG_DURCHGEFUEHRT = 'reinigung_durchgefuehrt';
    case REINIGUNG_GEPLANT = 'reinigung_geplant';
    case REINIGUNGSPLAN_GEAENDERT = 'reinigungsplan_geaendert';
    case FAELLIGKEIT_GEAENDERT = 'faelligkeit_geaendert';
    
    // ═══════════════════════════════════════════════════════════
    // 💬 KOMMUNIKATION
    // ═══════════════════════════════════════════════════════════
    case NOTIZ = 'notiz';
    case TELEFONAT = 'telefonat';
    case TELEFONAT_EINGEHEND = 'telefonat_eingehend';
    case TELEFONAT_AUSGEHEND = 'telefonat_ausgehend';
    case EMAIL_VERSANDT = 'email_versandt';
    case EMAIL_EMPFANGEN = 'email_empfangen';
    case BESICHTIGUNG = 'besichtigung';
    case KUNDENKONTAKT = 'kundenkontakt';
    
    // ═══════════════════════════════════════════════════════════
    // ⚠️ PROBLEME
    // ═══════════════════════════════════════════════════════════
    case REKLAMATION = 'reklamation';
    case REKLAMATION_ERLEDIGT = 'reklamation_erledigt';
    case PROBLEM = 'problem';
    case PROBLEM_BEHOBEN = 'problem_behoben';
    case MANGEL = 'mangel';
    case SCHADENSMELDUNG = 'schadensmeldung';
    
    // ═══════════════════════════════════════════════════════════
    // 📄 DOKUMENTE
    // ═══════════════════════════════════════════════════════════
    case DOKUMENT_HOCHGELADEN = 'dokument_hochgeladen';
    case FOTO_HOCHGELADEN = 'foto_hochgeladen';
    case VERTRAG_HINZUGEFUEGT = 'vertrag_hinzugefuegt';
    
    // ═══════════════════════════════════════════════════════════
    // ⏰ ERINNERUNGEN
    // ═══════════════════════════════════════════════════════════
    case ERINNERUNG = 'erinnerung';
    case WIEDERVORLAGE = 'wiedervorlage';
    
    // ═══════════════════════════════════════════════════════════
    // 🔧 SYSTEM
    // ═══════════════════════════════════════════════════════════
    case IMPORT = 'import';
    case MIGRATION = 'migration';
    
    // ═══════════════════════════════════════════════════════════
    // 🏷️ LABELS
    // ═══════════════════════════════════════════════════════════
    
    public function label(): string
    {
        return match($this) {
            // Stammdaten
            self::ERSTELLT => 'Erstellt',
            self::GEAENDERT => 'Geändert',
            self::GELOESCHT => 'Gelöscht',
            self::WIEDERHERGESTELLT => 'Wiederhergestellt',
            
            // Touren
            self::TOUR_ZUGEWIESEN => 'Tour zugewiesen',
            self::TOUR_ENTFERNT => 'Tour entfernt',
            self::TOUR_REIHENFOLGE => 'Tour-Reihenfolge geändert',
            
            // Artikel
            self::ARTIKEL_HINZUGEFUEGT => 'Artikel hinzugefügt',
            self::ARTIKEL_GEAENDERT => 'Artikel geändert',
            self::ARTIKEL_ENTFERNT => 'Artikel entfernt',
            self::ARTIKEL_DEAKTIVIERT => 'Artikel deaktiviert',
            self::ARTIKEL_AKTIVIERT => 'Artikel aktiviert',
            
            // Finanzen
            self::PREIS_GEAENDERT => 'Preis geändert',
            self::AUFSCHLAG_GESETZT => 'Aufschlag gesetzt',
            self::AUFSCHLAG_ENTFERNT => 'Aufschlag entfernt',
            self::RECHNUNG_ERSTELLT => 'Rechnung erstellt',
            self::ANGEBOT_ERSTELLT => 'Angebot erstellt',
            self::FATTURA_PROFIL_GEAENDERT => 'Fattura-Profil geändert',
            
            // Reinigung
            self::REINIGUNG_DURCHGEFUEHRT => 'Reinigung durchgeführt',
            self::REINIGUNG_GEPLANT => 'Reinigung geplant',
            self::REINIGUNGSPLAN_GEAENDERT => 'Reinigungsplan geändert',
            self::FAELLIGKEIT_GEAENDERT => 'Fälligkeit geändert',
            
            // Kommunikation
            self::NOTIZ => 'Notiz',
            self::TELEFONAT => 'Telefonat',
            self::TELEFONAT_EINGEHEND => 'Telefonat (eingehend)',
            self::TELEFONAT_AUSGEHEND => 'Telefonat (ausgehend)',
            self::EMAIL_VERSANDT => 'E-Mail versandt',
            self::EMAIL_EMPFANGEN => 'E-Mail empfangen',
            self::BESICHTIGUNG => 'Besichtigung',
            self::KUNDENKONTAKT => 'Kundenkontakt',
            
            // Probleme
            self::REKLAMATION => 'Reklamation',
            self::REKLAMATION_ERLEDIGT => 'Reklamation erledigt',
            self::PROBLEM => 'Problem',
            self::PROBLEM_BEHOBEN => 'Problem behoben',
            self::MANGEL => 'Mangel',
            self::SCHADENSMELDUNG => 'Schadensmeldung',
            
            // Dokumente
            self::DOKUMENT_HOCHGELADEN => 'Dokument hochgeladen',
            self::FOTO_HOCHGELADEN => 'Foto hochgeladen',
            self::VERTRAG_HINZUGEFUEGT => 'Vertrag hinzugefügt',
            
            // Erinnerungen
            self::ERINNERUNG => 'Erinnerung',
            self::WIEDERVORLAGE => 'Wiedervorlage',
            
            // System
            self::IMPORT => 'Import',
            self::MIGRATION => 'Migration',
        };
    }
    
    // ═══════════════════════════════════════════════════════════
    // 🎨 ICONS (Bootstrap Icons)
    // ═══════════════════════════════════════════════════════════
    
    public function icon(): string
    {
        return match($this) {
            // Stammdaten
            self::ERSTELLT => 'bi-plus-circle-fill',
            self::GEAENDERT => 'bi-pencil-fill',
            self::GELOESCHT => 'bi-trash-fill',
            self::WIEDERHERGESTELLT => 'bi-arrow-counterclockwise',
            
            // Touren
            self::TOUR_ZUGEWIESEN => 'bi-signpost-2-fill',
            self::TOUR_ENTFERNT => 'bi-signpost-split',
            self::TOUR_REIHENFOLGE => 'bi-sort-numeric-down',
            
            // Artikel
            self::ARTIKEL_HINZUGEFUEGT => 'bi-cart-plus-fill',
            self::ARTIKEL_GEAENDERT => 'bi-cart-fill',
            self::ARTIKEL_ENTFERNT => 'bi-cart-x-fill',
            self::ARTIKEL_DEAKTIVIERT => 'bi-cart-dash-fill',
            self::ARTIKEL_AKTIVIERT => 'bi-cart-check-fill',
            
            // Finanzen
            self::PREIS_GEAENDERT => 'bi-currency-euro',
            self::AUFSCHLAG_GESETZT => 'bi-percent',
            self::AUFSCHLAG_ENTFERNT => 'bi-slash-circle',
            self::RECHNUNG_ERSTELLT => 'bi-receipt',
            self::ANGEBOT_ERSTELLT => 'bi-file-earmark-text',
            self::FATTURA_PROFIL_GEAENDERT => 'bi-file-earmark-code',
            
            // Reinigung
            self::REINIGUNG_DURCHGEFUEHRT => 'bi-check-circle-fill',
            self::REINIGUNG_GEPLANT => 'bi-calendar-check',
            self::REINIGUNGSPLAN_GEAENDERT => 'bi-calendar-event',
            self::FAELLIGKEIT_GEAENDERT => 'bi-clock-history',
            
            // Kommunikation
            self::NOTIZ => 'bi-sticky-fill',
            self::TELEFONAT => 'bi-telephone-fill',
            self::TELEFONAT_EINGEHEND => 'bi-telephone-inbound-fill',
            self::TELEFONAT_AUSGEHEND => 'bi-telephone-outbound-fill',
            self::EMAIL_VERSANDT => 'bi-envelope-arrow-up-fill',
            self::EMAIL_EMPFANGEN => 'bi-envelope-arrow-down-fill',
            self::BESICHTIGUNG => 'bi-eye-fill',
            self::KUNDENKONTAKT => 'bi-person-lines-fill',
            
            // Probleme
            self::REKLAMATION => 'bi-exclamation-triangle-fill',
            self::REKLAMATION_ERLEDIGT => 'bi-check-square-fill',
            self::PROBLEM => 'bi-exclamation-circle-fill',
            self::PROBLEM_BEHOBEN => 'bi-patch-check-fill',
            self::MANGEL => 'bi-x-octagon-fill',
            self::SCHADENSMELDUNG => 'bi-shield-exclamation',
            
            // Dokumente
            self::DOKUMENT_HOCHGELADEN => 'bi-file-earmark-arrow-up-fill',
            self::FOTO_HOCHGELADEN => 'bi-image-fill',
            self::VERTRAG_HINZUGEFUEGT => 'bi-file-earmark-medical-fill',
            
            // Erinnerungen
            self::ERINNERUNG => 'bi-bell-fill',
            self::WIEDERVORLAGE => 'bi-arrow-repeat',
            
            // System
            self::IMPORT => 'bi-cloud-arrow-down-fill',
            self::MIGRATION => 'bi-gear-fill',
        };
    }
    
    // ═══════════════════════════════════════════════════════════
    // 🎨 FARBEN (Bootstrap Colors)
    // ═══════════════════════════════════════════════════════════
    
    public function farbe(): string
    {
        return match($this) {
            // Stammdaten
            self::ERSTELLT => 'success',
            self::GEAENDERT => 'primary',
            self::GELOESCHT => 'danger',
            self::WIEDERHERGESTELLT => 'info',
            
            // Touren
            self::TOUR_ZUGEWIESEN => 'success',
            self::TOUR_ENTFERNT => 'warning',
            self::TOUR_REIHENFOLGE => 'secondary',
            
            // Artikel
            self::ARTIKEL_HINZUGEFUEGT => 'success',
            self::ARTIKEL_GEAENDERT => 'primary',
            self::ARTIKEL_ENTFERNT => 'danger',
            self::ARTIKEL_DEAKTIVIERT => 'warning',
            self::ARTIKEL_AKTIVIERT => 'success',
            
            // Finanzen
            self::PREIS_GEAENDERT => 'info',
            self::AUFSCHLAG_GESETZT => 'warning',
            self::AUFSCHLAG_ENTFERNT => 'secondary',
            self::RECHNUNG_ERSTELLT => 'success',
            self::ANGEBOT_ERSTELLT => 'primary',
            self::FATTURA_PROFIL_GEAENDERT => 'info',
            
            // Reinigung
            self::REINIGUNG_DURCHGEFUEHRT => 'success',
            self::REINIGUNG_GEPLANT => 'info',
            self::REINIGUNGSPLAN_GEAENDERT => 'primary',
            self::FAELLIGKEIT_GEAENDERT => 'warning',
            
            // Kommunikation
            self::NOTIZ => 'secondary',
            self::TELEFONAT => 'info',
            self::TELEFONAT_EINGEHEND => 'success',
            self::TELEFONAT_AUSGEHEND => 'primary',
            self::EMAIL_VERSANDT => 'primary',
            self::EMAIL_EMPFANGEN => 'info',
            self::BESICHTIGUNG => 'primary',
            self::KUNDENKONTAKT => 'info',
            
            // Probleme
            self::REKLAMATION => 'danger',
            self::REKLAMATION_ERLEDIGT => 'success',
            self::PROBLEM => 'danger',
            self::PROBLEM_BEHOBEN => 'success',
            self::MANGEL => 'warning',
            self::SCHADENSMELDUNG => 'danger',
            
            // Dokumente
            self::DOKUMENT_HOCHGELADEN => 'secondary',
            self::FOTO_HOCHGELADEN => 'secondary',
            self::VERTRAG_HINZUGEFUEGT => 'info',
            
            // Erinnerungen
            self::ERINNERUNG => 'warning',
            self::WIEDERVORLAGE => 'info',
            
            // System
            self::IMPORT => 'secondary',
            self::MIGRATION => 'secondary',
        };
    }
    
    // ═══════════════════════════════════════════════════════════
    // 📁 KATEGORIEN
    // ═══════════════════════════════════════════════════════════
    
    public function kategorie(): string
    {
        return match($this) {
            self::ERSTELLT, self::GEAENDERT, self::GELOESCHT, self::WIEDERHERGESTELLT 
                => 'stammdaten',
            
            self::TOUR_ZUGEWIESEN, self::TOUR_ENTFERNT, self::TOUR_REIHENFOLGE 
                => 'touren',
            
            self::ARTIKEL_HINZUGEFUEGT, self::ARTIKEL_GEAENDERT, self::ARTIKEL_ENTFERNT,
            self::ARTIKEL_DEAKTIVIERT, self::ARTIKEL_AKTIVIERT 
                => 'artikel',
            
            self::PREIS_GEAENDERT, self::AUFSCHLAG_GESETZT, self::AUFSCHLAG_ENTFERNT,
            self::RECHNUNG_ERSTELLT, self::ANGEBOT_ERSTELLT, self::FATTURA_PROFIL_GEAENDERT 
                => 'finanzen',
            
            self::REINIGUNG_DURCHGEFUEHRT, self::REINIGUNG_GEPLANT,
            self::REINIGUNGSPLAN_GEAENDERT, self::FAELLIGKEIT_GEAENDERT 
                => 'reinigung',
            
            self::NOTIZ, self::TELEFONAT, self::TELEFONAT_EINGEHEND, self::TELEFONAT_AUSGEHEND,
            self::EMAIL_VERSANDT, self::EMAIL_EMPFANGEN,
            self::BESICHTIGUNG, self::KUNDENKONTAKT 
                => 'kommunikation',
            
            self::REKLAMATION, self::REKLAMATION_ERLEDIGT, self::PROBLEM, 
            self::PROBLEM_BEHOBEN, self::MANGEL, self::SCHADENSMELDUNG 
                => 'probleme',
            
            self::DOKUMENT_HOCHGELADEN, self::FOTO_HOCHGELADEN, self::VERTRAG_HINZUGEFUEGT 
                => 'dokumente',
            
            self::ERINNERUNG, self::WIEDERVORLAGE 
                => 'erinnerungen',
            
            self::IMPORT, self::MIGRATION 
                => 'system',
        };
    }
    
    /**
     * Alle Typen einer Kategorie
     */
    public static function byKategorie(string $kategorie): array
    {
        return array_filter(
            self::cases(),
            fn(self $typ) => $typ->kategorie() === $kategorie
        );
    }
    
    /**
     * Dropdown für Formulare
     */
    public static function dropdown(): array
    {
        $grouped = [];
        foreach (self::cases() as $typ) {
            $kat = $typ->kategorie();
            $grouped[$kat][$typ->value] = $typ->label();
        }
        return $grouped;
    }
    
    /**
     * Kategorie-Labels
     */
    public static function kategorieLabel(string $kategorie): string
    {
        return match($kategorie) {
            'stammdaten' => 'Stammdaten',
            'touren' => 'Touren',
            'artikel' => 'Artikel',
            'finanzen' => 'Finanzen',
            'reinigung' => 'Reinigung',
            'kommunikation' => 'Kommunikation',
            'probleme' => 'Probleme',
            'dokumente' => 'Dokumente',
            'erinnerungen' => 'Erinnerungen',
            'system' => 'System',
            default => ucfirst($kategorie),
        };
    }
}
