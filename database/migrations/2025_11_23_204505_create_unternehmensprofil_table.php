<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Unternehmens-Profil (Company Profile)
 * 
 * Zentrale Tabelle für alle Firmeneinstellungen:
 * - Allgemeine Firmendaten (deutsch)
 * - E-Mail-Versand Einstellungen
 * - PDF/Briefkopf Design
 * - FatturaPA-spezifische Daten (italienisch)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unternehmensprofil', function (Blueprint $table) {
            $table->id();
            
            // ════════════════════════════════════════════════════════
            // 🏢 ALLGEMEINE FIRMENDATEN (Deutsch)
            // ════════════════════════════════════════════════════════
            
            $table->string('firmenname')->comment('Offizieller Firmenname');
            $table->string('firma_zusatz')->nullable()->comment('Zusatz (z.B. "GmbH", "SRL")');
            $table->string('geschaeftsfuehrer')->nullable()->comment('Name Geschäftsführer');
            $table->string('handelsregister')->nullable()->comment('Handelsregisternummer');
            $table->string('registergericht')->nullable()->comment('Registergericht');
            
            // Adresse
            $table->string('strasse')->comment('Straße');
            $table->string('hausnummer', 10)->comment('Hausnummer');
            $table->string('adresszusatz')->nullable()->comment('Adresszusatz (Stockwerk, etc.)');
            $table->string('postleitzahl', 10)->comment('PLZ');
            $table->string('ort')->comment('Ort/Stadt');
            $table->string('bundesland')->nullable()->comment('Bundesland/Provinz');
            $table->string('land', 2)->default('IT')->comment('Land (ISO 2-stellig)');
            
            // Kontaktdaten
            $table->string('telefon', 30)->nullable()->comment('Telefon');
            $table->string('telefon_mobil', 30)->nullable()->comment('Mobiltelefon');
            $table->string('fax', 30)->nullable()->comment('Fax');
            $table->string('email')->comment('Haupt-E-Mail');
            $table->string('email_buchhaltung')->nullable()->comment('E-Mail Buchhaltung');
            $table->string('website')->nullable()->comment('Webseite');
            
            // Steuerdaten (allgemein)
            $table->string('steuernummer')->nullable()->comment('Steuernummer');
            $table->string('umsatzsteuer_id')->nullable()->comment('USt-IdNr. / Partita IVA');
            
            // ════════════════════════════════════════════════════════
            // 🏦 BANKDATEN
            // ════════════════════════════════════════════════════════
            
            $table->string('bank_name')->nullable()->comment('Name der Bank');
            $table->string('iban', 34)->nullable()->comment('IBAN');
            $table->string('bic', 11)->nullable()->comment('BIC/SWIFT');
            $table->string('kontoinhaber')->nullable()->comment('Kontoinhaber (falls abweichend)');
            
            // ════════════════════════════════════════════════════════
            // 📧 E-MAIL VERSAND EINSTELLUNGEN (Normal)
            // ════════════════════════════════════════════════════════
            
            $table->string('smtp_host')->nullable()->comment('SMTP Server');
            $table->integer('smtp_port')->nullable()->default(587)->comment('SMTP Port');
            $table->string('smtp_verschluesselung', 10)->nullable()->default('tls')->comment('TLS/SSL');
            $table->string('smtp_benutzername')->nullable()->comment('SMTP Login');
            $table->string('smtp_passwort')->nullable()->comment('SMTP Passwort (verschlüsselt)');
            $table->string('email_absender')->nullable()->comment('Absender E-Mail');
            $table->string('email_absender_name')->nullable()->comment('Absender Name');
            $table->string('email_antwort_an')->nullable()->comment('Reply-To E-Mail');
            $table->string('email_cc')->nullable()->comment('CC (mehrere durch Komma)');
            $table->string('email_bcc')->nullable()->comment('BCC (mehrere durch Komma)');
            
            // E-Mail Templates
            $table->text('email_signatur')->nullable()->comment('Standard E-Mail Signatur');
            $table->text('email_fusszeile')->nullable()->comment('Standard E-Mail Fußzeile');
            
            // ════════════════════════════════════════════════════════
            // 📧 PEC E-MAIL VERSAND EINSTELLUNGEN (Zertifiziert)
            // ════════════════════════════════════════════════════════
            
            $table->string('pec_smtp_host')->nullable()->comment('PEC SMTP Server');
            $table->integer('pec_smtp_port')->nullable()->default(587)->comment('PEC SMTP Port');
            $table->string('pec_smtp_verschluesselung', 10)->nullable()->default('tls')->comment('PEC TLS/SSL');
            $table->string('pec_smtp_benutzername')->nullable()->comment('PEC SMTP Login');
            $table->string('pec_smtp_passwort')->nullable()->comment('PEC SMTP Passwort (verschlüsselt)');
            $table->string('pec_email_absender')->nullable()->comment('PEC Absender E-Mail (zertifiziert)');
            $table->string('pec_email_absender_name')->nullable()->comment('PEC Absender Name');
            $table->string('pec_email_antwort_an')->nullable()->comment('PEC Reply-To E-Mail');
            $table->string('pec_email_cc')->nullable()->comment('PEC CC (mehrere durch Komma)');
            $table->string('pec_email_bcc')->nullable()->comment('PEC BCC (mehrere durch Komma)');
            
            // PEC Templates
            $table->text('pec_email_signatur')->nullable()->comment('PEC E-Mail Signatur');
            $table->text('pec_email_fusszeile')->nullable()->comment('PEC E-Mail Fußzeile');
            
            $table->boolean('pec_aktiv')->default(false)->comment('PEC E-Mail-Versand aktiviert');
            
            // ════════════════════════════════════════════════════════
            // 🎨 PDF / BRIEFKOPF DESIGN
            // ════════════════════════════════════════════════════════
            
            // Logos
            $table->string('logo_pfad')->nullable()->comment('Pfad zum Haupt-Logo');
            $table->string('logo_rechnung_pfad')->nullable()->comment('Logo für Rechnungen');
            $table->string('logo_email_pfad')->nullable()->comment('Logo für E-Mails');
            $table->integer('logo_breite')->nullable()->default(200)->comment('Logo Breite (px)');
            $table->integer('logo_hoehe')->nullable()->default(80)->comment('Logo Höhe (px)');
            
            // Briefkopf-Text
            $table->text('briefkopf_text')->nullable()->comment('Zusätzlicher Text im Briefkopf');
            $table->text('briefkopf_rechts')->nullable()->comment('Text rechts oben');
            $table->text('fusszeile_text')->nullable()->comment('Fußzeile auf allen Seiten');
            
            // Farben (Hex-Codes)
            $table->string('farbe_primaer', 7)->nullable()->default('#003366')->comment('Primärfarbe (z.B. #003366)');
            $table->string('farbe_sekundaer', 7)->nullable()->default('#666666')->comment('Sekundärfarbe');
            $table->string('farbe_akzent', 7)->nullable()->default('#0066CC')->comment('Akzentfarbe');
            
            // Schriftarten
            $table->string('schriftart', 50)->nullable()->default('Helvetica')->comment('Standard-Schriftart');
            $table->integer('schriftgroesse')->nullable()->default(10)->comment('Standard-Schriftgröße (pt)');
            
            // ════════════════════════════════════════════════════════
            // 📄 RECHNUNGS-EINSTELLUNGEN
            // ════════════════════════════════════════════════════════
            
            $table->string('rechnungsnummer_praefix', 10)->nullable()->comment('Präfix (z.B. "RE-")');
            $table->integer('rechnungsnummer_startjahr')->nullable()->default(2025)->comment('Jahr für Nummerierung');
            $table->integer('rechnungsnummer_laenge')->nullable()->default(5)->comment('Länge Laufnummer (z.B. 5 = 00001)');
            $table->integer('zahlungsziel_tage')->nullable()->default(30)->comment('Standard Zahlungsziel (Tage)');
            $table->text('zahlungshinweis')->nullable()->comment('Text auf Rechnung (z.B. "Bitte unter Angabe...")');
            $table->text('kleinunternehmer_hinweis')->nullable()->comment('Kleinunternehmer §19 UStG Text');
            
            // Standardtexte
            $table->text('rechnung_einleitung')->nullable()->comment('Standard Einleitungstext');
            $table->text('rechnung_schlusstext')->nullable()->comment('Standard Schlusstext');
            $table->text('rechnung_agb_text')->nullable()->comment('AGB-Text für Rechnung');
            
            // ════════════════════════════════════════════════════════
            // 🇮🇹 FATTURAPA-SPEZIFISCHE DATEN (Italienisch)
            // ════════════════════════════════════════════════════════
            
            $table->string('ragione_sociale')->nullable()->comment('IT: Offizielle Firmenbezeichnung');
            $table->string('partita_iva', 11)->nullable()->comment('IT: Partita IVA (11 Ziffern)');
            $table->string('codice_fiscale', 16)->nullable()->comment('IT: Codice Fiscale');
            $table->string('regime_fiscale', 4)->nullable()->default('RF01')->comment('IT: RF01-RF19');
            $table->string('pec_email')->nullable()->comment('IT: Zertifizierte PEC E-Mail');
            
            // REA-Daten (Registro Imprese)
            $table->string('rea_ufficio', 2)->nullable()->comment('IT: REA Büro (z.B. MI, RM)');
            $table->string('rea_numero', 20)->nullable()->comment('IT: REA Nummer');
            $table->decimal('capitale_sociale', 15, 2)->nullable()->comment('IT: Stammkapital');
            $table->enum('stato_liquidazione', ['LN', 'LS'])->default('LN')->comment('IT: LN=Nicht in Liquidation');
            
            // ════════════════════════════════════════════════════════
            // ⚙️ SYSTEM & SONSTIGES
            // ════════════════════════════════════════════════════════
            
            $table->string('waehrung', 3)->default('EUR')->comment('Währung (ISO 3-stellig)');
            $table->string('sprache', 2)->default('de')->comment('Standard-Sprache (ISO 2-stellig)');
            $table->string('zeitzone', 50)->default('Europe/Rome')->comment('Zeitzone');
            $table->string('datumsformat', 20)->default('d.m.Y')->comment('Datumsformat (PHP)');
            $table->string('zahlenformat', 20)->default('de_DE')->comment('Zahlenformat Locale');
            
            $table->boolean('ist_kleinunternehmer')->default(false)->comment('Kleinunternehmer §19 UStG');
            $table->boolean('mwst_ausweisen')->default(true)->comment('MwSt auf Dokumenten ausweisen');
            $table->decimal('standard_mwst_satz', 5, 2)->default(22.00)->comment('Standard MwSt-Satz (%)');
            
            $table->boolean('ist_aktiv')->default(true)->comment('Aktives Profil');
            $table->text('notizen')->nullable()->comment('Interne Notizen');
            
            $table->timestamps();
            
            // Indizes
            $table->index('ist_aktiv');
            $table->index('firmenname');
            
            $table->comment('Zentrales Unternehmensprofil mit allen Einstellungen');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unternehmensprofil');
    }
};