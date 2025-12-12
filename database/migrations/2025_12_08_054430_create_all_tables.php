<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Haupt-Migration: Erstellt alle Anwendungstabellen
 * 
 * ⭐ WICHTIG: Prüft ob Tabellen bereits existieren (hasTable)
 *    Kann daher auch bei bestehender Datenbank ausgeführt werden!
 */
return new class extends Migration
{
    public function up(): void
    {
        // ═══════════════════════════════════════════════════════════════════
        // 1. USERS (Laravel Standard)
        // ═══════════════════════════════════════════════════════════════════
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->rememberToken();
                $table->timestamps();
            });
        }

        // ═══════════════════════════════════════════════════════════════════
        // 2. ADRESSEN
        // ═══════════════════════════════════════════════════════════════════
        if (!Schema::hasTable('adressen')) {
            Schema::create('adressen', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('legacy_id')->nullable();
                $table->unsignedBigInteger('legacy_mid')->nullable()->index();
                $table->string('name', 200);
                $table->string('strasse', 255)->nullable();
                $table->string('hausnummer', 100)->nullable();
                $table->string('plz', 10)->nullable();
                $table->string('wohnort', 100)->nullable();
                $table->string('provinz', 4)->nullable();
                $table->string('land', 50)->nullable();
                $table->string('telefon', 50)->nullable();
                $table->string('handy', 50)->nullable();
                $table->string('email', 255)->nullable();
                $table->string('email_zweit', 255)->nullable();
                $table->string('pec', 255)->nullable();
                $table->string('steuernummer', 50)->nullable();
                $table->string('mwst_nummer', 50)->nullable();
                $table->string('codice_univoco', 20)->nullable();
                $table->text('bemerkung')->nullable();
                $table->boolean('veraendert')->default(false);
                $table->timestamp('veraendert_wann')->nullable();
                $table->softDeletes();
                $table->timestamps();
            });
        }

        // ═══════════════════════════════════════════════════════════════════
        // 3. TOUR
        // ═══════════════════════════════════════════════════════════════════
        if (!Schema::hasTable('tour')) {
            Schema::create('tour', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('reihenfolge')->default(0)->index();
                $table->string('name', 100)->unique();
                $table->text('beschreibung')->nullable();
                $table->boolean('aktiv')->nullable()->default(true);
                $table->softDeletes();
                $table->timestamps();
            });
        }

        // ═══════════════════════════════════════════════════════════════════
        // 4. FATTURA PROFILE
        // ═══════════════════════════════════════════════════════════════════
        if (!Schema::hasTable('fattura_profile')) {
            Schema::create('fattura_profile', function (Blueprint $table) {
                $table->id();
                $table->string('bezeichnung', 100);
                $table->text('bemerkung')->nullable();
                $table->boolean('split_payment')->default(false)->index();
                $table->boolean('reverse_charge')->default(false);
                $table->boolean('ritenuta')->default(false);
                $table->decimal('mwst_satz', 5, 2)->default(22.00);
                $table->string('code', 30)->nullable()->unique();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // ═══════════════════════════════════════════════════════════════════
        // 5. UNTERNEHMENSPROFIL
        // ═══════════════════════════════════════════════════════════════════
        if (!Schema::hasTable('unternehmensprofil')) {
            Schema::create('unternehmensprofil', function (Blueprint $table) {
                $table->id();
                
                // Stammdaten
                $table->string('firmenname', 255)->index();
                $table->string('firma_zusatz', 255)->nullable();
                $table->string('geschaeftsfuehrer', 255)->nullable();
                $table->string('handelsregister', 255)->nullable();
                $table->string('registergericht', 255)->nullable();
                
                // Adresse
                $table->string('strasse', 255);
                $table->string('hausnummer', 10);
                $table->string('adresszusatz', 255)->nullable();
                $table->string('postleitzahl', 10);
                $table->string('ort', 255);
                $table->string('bundesland', 255)->nullable();
                $table->string('land', 2)->default('IT');
                
                // Kontakt
                $table->string('telefon', 30)->nullable();
                $table->string('telefon_mobil', 30)->nullable();
                $table->string('fax', 30)->nullable();
                $table->string('email', 255);
                $table->string('email_buchhaltung', 255)->nullable();
                $table->string('website', 255)->nullable();
                
                // Finanzen
                $table->string('steuernummer', 255)->nullable();
                $table->string('umsatzsteuer_id', 255)->nullable();
                $table->string('bank_name', 255)->nullable();
                $table->string('iban', 34)->nullable();
                $table->string('bic', 11)->nullable();
                $table->string('kontoinhaber', 255)->nullable();
                
                // Standard SMTP
                $table->string('smtp_host', 255)->nullable();
                $table->integer('smtp_port')->nullable()->default(587);
                $table->string('smtp_verschluesselung', 10)->nullable()->default('tls');
                $table->string('smtp_benutzername', 255)->nullable();
                $table->string('smtp_passwort', 255)->nullable();
                $table->string('email_absender', 255)->nullable();
                $table->string('email_absender_name', 255)->nullable();
                $table->string('email_antwort_an', 255)->nullable();
                $table->string('email_cc', 255)->nullable();
                $table->string('email_bcc', 255)->nullable();
                $table->text('email_signatur')->nullable();
                $table->text('email_fusszeile')->nullable();
                
                // PEC SMTP
                $table->string('pec_smtp_host', 255)->nullable();
                $table->integer('pec_smtp_port')->nullable()->default(587);
                $table->string('pec_smtp_verschluesselung', 10)->nullable()->default('tls');
                $table->string('pec_smtp_benutzername', 255)->nullable();
                $table->string('pec_smtp_passwort', 255)->nullable();
                $table->string('pec_email_absender', 255)->nullable();
                $table->string('pec_email_absender_name', 255)->nullable();
                $table->string('pec_email_antwort_an', 255)->nullable();
                $table->string('pec_email_cc', 255)->nullable();
                $table->string('pec_email_bcc', 255)->nullable();
                $table->text('pec_email_signatur')->nullable();
                $table->text('pec_email_fusszeile')->nullable();
                $table->boolean('pec_aktiv')->default(false);
                
                // Logos & Design
                $table->string('logo_pfad', 255)->nullable();
                $table->string('logo_rechnung_pfad', 255)->nullable();
                $table->string('logo_email_pfad', 255)->nullable();
                $table->integer('logo_breite')->nullable()->default(200);
                $table->integer('logo_hoehe')->nullable()->default(80);
                $table->text('briefkopf_text')->nullable();
                $table->text('briefkopf_rechts')->nullable();
                $table->text('fusszeile_text')->nullable();
                $table->string('farbe_primaer', 7)->nullable()->default('#003366');
                $table->string('farbe_sekundaer', 7)->nullable()->default('#666666');
                $table->string('farbe_akzent', 7)->nullable()->default('#0066CC');
                $table->string('schriftart', 50)->nullable()->default('Helvetica');
                $table->integer('schriftgroesse')->nullable()->default(10);
                
                // Rechnungseinstellungen
                $table->string('rechnungsnummer_praefix', 10)->nullable();
                $table->integer('rechnungsnummer_startjahr')->nullable()->default(2025);
                $table->integer('rechnungsnummer_laenge')->nullable()->default(5);
                $table->integer('zahlungsziel_tage')->nullable()->default(30);
                $table->text('zahlungshinweis')->nullable();
                $table->text('kleinunternehmer_hinweis')->nullable();
                $table->text('rechnung_einleitung')->nullable();
                $table->text('rechnung_schlusstext')->nullable();
                $table->text('rechnung_agb_text')->nullable();
                
                // FatturaPA Italien
                $table->string('ragione_sociale', 255)->nullable();
                $table->string('partita_iva', 11)->nullable();
                $table->string('codice_fiscale', 16)->nullable();
                $table->string('regime_fiscale', 4)->nullable()->default('RF01');
                $table->string('pec_email', 255)->nullable();
                $table->string('rea_ufficio', 2)->nullable();
                $table->string('rea_numero', 20)->nullable();
                $table->decimal('capitale_sociale', 15, 2)->nullable();
                $table->enum('stato_liquidazione', ['LN', 'LS'])->default('LN');
                
                // System
                $table->string('waehrung', 3)->default('EUR');
                $table->string('sprache', 2)->default('de');
                $table->string('zeitzone', 50)->default('Europe/Rome');
                $table->string('datumsformat', 20)->default('d.m.Y');
                $table->string('zahlenformat', 20)->default('de_DE');
                $table->boolean('ist_kleinunternehmer')->default(false);
                $table->boolean('mwst_ausweisen')->default(true);
                $table->decimal('standard_mwst_satz', 5, 2)->default(22.00);
                $table->boolean('ist_aktiv')->default(true)->index();
                $table->text('notizen')->nullable();
                
                $table->timestamps();
            });
        }

        // ═══════════════════════════════════════════════════════════════════
        // 6. PREIS AUFSCHLAEGE (Global)
        // ═══════════════════════════════════════════════════════════════════
        if (!Schema::hasTable('preis_aufschlaege')) {
            Schema::create('preis_aufschlaege', function (Blueprint $table) {
                $table->id();
                $table->year('jahr')->unique();
                $table->decimal('prozent', 5, 2)->default(0.00);
                $table->string('beschreibung', 500)->nullable();
                $table->timestamps();
            });
        }

        // ═══════════════════════════════════════════════════════════════════
        // 7. GEBAEUDE (abhängig von: adressen, fattura_profile)
        // ═══════════════════════════════════════════════════════════════════
        if (!Schema::hasTable('gebaeude')) {
            Schema::create('gebaeude', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('legacy_id')->nullable();
                $table->unsignedBigInteger('legacy_mid')->nullable()->index();
                $table->string('codex', 15)->nullable();
                $table->unsignedBigInteger('postadresse_id')->nullable()->index();
                $table->unsignedBigInteger('rechnungsempfaenger_id')->nullable()->index();
                $table->string('gebaeude_name', 100)->nullable();
                $table->string('strasse', 255)->nullable();
                $table->string('hausnummer', 100)->nullable();
                $table->string('plz', 50)->nullable();
                $table->string('wohnort', 100)->nullable();
                $table->string('land', 50)->nullable();
                $table->text('bemerkung')->nullable();
                $table->text('bemerkung_buchhaltung')->nullable();
                
                // FatturaPA Felder
                $table->string('cup', 20)->nullable();
                $table->string('cig', 10)->nullable();
                $table->string('codice_commessa', 100)->nullable();
                $table->string('auftrag_id', 50)->nullable()->index();
                $table->date('auftrag_datum')->nullable();
                $table->unsignedBigInteger('fattura_profile_id')->nullable()->index();
                $table->text('bank_match_text_template')->nullable();
                
                // Status
                $table->boolean('veraendert')->default(false);
                $table->timestamp('veraendert_wann')->nullable();
                $table->date('letzter_termin')->nullable();
                $table->date('datum_faelligkeit')->nullable();
                $table->integer('geplante_reinigungen')->default(1);
                $table->integer('gemachte_reinigungen')->default(1);
                $table->boolean('faellig')->default(false);
                $table->boolean('rechnung_schreiben')->default(false);
                
                // Monats-Flags
                $table->boolean('m01')->default(false);
                $table->boolean('m02')->default(false);
                $table->boolean('m03')->default(false);
                $table->boolean('m04')->default(false);
                $table->boolean('m05')->default(false);
                $table->boolean('m06')->default(false);
                $table->boolean('m07')->default(false);
                $table->boolean('m08')->default(false);
                $table->boolean('m09')->default(false);
                $table->boolean('m10')->default(false);
                $table->boolean('m11')->default(false);
                $table->boolean('m12')->default(false);
                
                $table->unsignedBigInteger('select_tour')->nullable();
                $table->softDeletes();
                $table->timestamps();
                
                // Foreign Keys
                $table->foreign('postadresse_id')->references('id')->on('adressen')->nullOnDelete();
                $table->foreign('rechnungsempfaenger_id')->references('id')->on('adressen')->nullOnDelete();
                $table->foreign('fattura_profile_id')->references('id')->on('fattura_profile')->nullOnDelete();
            });
        }

        // ═══════════════════════════════════════════════════════════════════
        // 8. TOURGEBAEUDE (Pivot: tour <-> gebaeude)
        // ═══════════════════════════════════════════════════════════════════
        if (!Schema::hasTable('tourgebaeude')) {
            Schema::create('tourgebaeude', function (Blueprint $table) {
                $table->unsignedBigInteger('tour_id');
                $table->unsignedBigInteger('gebaeude_id');
                $table->integer('reihenfolge')->default(0);
                
                $table->primary(['tour_id', 'gebaeude_id']);
                $table->foreign('tour_id')->references('id')->on('tour')->cascadeOnDelete();
                $table->foreign('gebaeude_id')->references('id')->on('gebaeude')->cascadeOnDelete();
            });
        }

        // ═══════════════════════════════════════════════════════════════════
        // 9. ARTIKEL GEBAEUDE (Stammartikel pro Gebäude)
        // ═══════════════════════════════════════════════════════════════════
        if (!Schema::hasTable('artikel_gebaeude')) {
            Schema::create('artikel_gebaeude', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('legacy_id')->nullable();
                $table->unsignedBigInteger('legacy_mid')->nullable()->index();
                $table->unsignedBigInteger('gebaeude_id')->index();
                $table->string('beschreibung', 255);
                $table->decimal('anzahl', 10, 2)->default(1.00);
                $table->decimal('einzelpreis', 10, 2)->default(0.00);
                $table->year('basis_jahr');
                $table->decimal('basis_preis', 12, 2);
                $table->boolean('aktiv')->default(true);
                $table->unsignedInteger('reihenfolge')->nullable();
                $table->timestamps();
                
                $table->foreign('gebaeude_id')->references('id')->on('gebaeude')->cascadeOnDelete();
            });
        }

        // ═══════════════════════════════════════════════════════════════════
        // 10. GEBAEUDE AUFSCHLAEGE (Individuell pro Gebäude)
        // ═══════════════════════════════════════════════════════════════════
        if (!Schema::hasTable('gebaeude_aufschlaege')) {
            Schema::create('gebaeude_aufschlaege', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('gebaeude_id')->index();
                $table->decimal('prozent', 5, 2)->default(0.00);
                $table->string('grund', 500)->nullable();
                $table->date('gueltig_ab')->nullable();
                $table->date('gueltig_bis')->nullable();
                $table->timestamps();
                
                $table->foreign('gebaeude_id')->references('id')->on('gebaeude')->cascadeOnDelete();
            });
        }

        // ═══════════════════════════════════════════════════════════════════
        // 11. TIMELINE (Reinigungshistorie)
        // ═══════════════════════════════════════════════════════════════════
        if (!Schema::hasTable('timeline')) {
            Schema::create('timeline', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('gebaeude_id')->index();
                $table->date('datum')->index();
                $table->boolean('verrechnen')->default(true)->index();
                $table->date('verrechnet_am')->nullable();
                $table->string('verrechnet_mit_rn_nummer', 20)->nullable();
                $table->text('bemerkung');
                $table->string('person_name', 150);
                $table->unsignedInteger('person_id')->index();
                $table->datetime('created_at')->useCurrent();
                $table->timestamp('updated_at')->nullable();
                $table->softDeletes();
            });
        }

        // ═══════════════════════════════════════════════════════════════════
        // 12. RECHNUNGEN
        // ═══════════════════════════════════════════════════════════════════
        if (!Schema::hasTable('rechnungen')) {
            Schema::create('rechnungen', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('legacy_id')->nullable()->index();
                $table->unsignedBigInteger('legacy_progressivo')->nullable();
                $table->year('jahr')->index();
                $table->unsignedInteger('laufnummer');
                
                // Referenzen
                $table->unsignedBigInteger('gebaeude_id')->nullable()->index();
                $table->unsignedBigInteger('rechnungsempfaenger_id')->nullable()->index();
                $table->unsignedBigInteger('postadresse_id')->nullable()->index();
                $table->unsignedBigInteger('fattura_profile_id')->nullable()->index();
                
                // Snapshot Rechnungsempfänger
                $table->string('re_name', 255)->nullable();
                $table->string('re_strasse', 255)->nullable();
                $table->string('re_hausnummer', 100)->nullable();
                $table->string('re_plz', 20)->nullable();
                $table->string('re_wohnort', 255)->nullable();
                $table->string('re_provinz', 10)->nullable();
                $table->string('re_land', 10)->nullable();
                $table->string('re_steuernummer', 50)->nullable();
                $table->string('re_mwst_nummer', 50)->nullable();
                $table->string('re_codice_univoco', 20)->nullable();
                $table->string('re_pec', 255)->nullable();
                
                // Snapshot Postadresse
                $table->string('post_name', 255)->nullable();
                $table->string('post_strasse', 255)->nullable();
                $table->string('post_hausnummer', 100)->nullable();
                $table->string('post_plz', 20)->nullable();
                $table->string('post_wohnort', 255)->nullable();
                $table->string('post_provinz', 10)->nullable();
                $table->string('post_land', 10)->nullable();
                $table->string('post_email', 255)->nullable();
                $table->string('post_pec', 255)->nullable();
                
                // Snapshot Gebäude
                $table->string('geb_codex', 50)->nullable();
                $table->string('geb_name', 255)->nullable();
                $table->string('geb_adresse', 500)->nullable();
                
                // Datum
                $table->date('rechnungsdatum')->index();
                $table->string('leistungsdaten', 255)->nullable();
                $table->text('fattura_causale')->nullable();
                $table->date('zahlungsziel')->nullable();
                $table->date('bezahlt_am')->nullable();
                
                // Beträge
                $table->decimal('netto_summe', 12, 2)->default(0.00);
                $table->decimal('mwst_betrag', 12, 2)->default(0.00);
                $table->decimal('brutto_summe', 12, 2)->default(0.00);
                $table->decimal('ritenuta_betrag', 12, 2)->default(0.00);
                $table->decimal('zahlbar_betrag', 12, 2)->default(0.00);
                
                // Status
                $table->enum('status', ['draft', 'sent', 'paid', 'cancelled', 'overdue'])->default('draft')->index();
                $table->enum('typ_rechnung', ['rechnung', 'gutschrift'])->default('rechnung');
                
                // Profil-Snapshot
                $table->string('profile_bezeichnung', 100)->nullable();
                $table->decimal('mwst_satz', 5, 2)->default(22.00);
                $table->boolean('split_payment')->default(false);
                $table->boolean('reverse_charge')->default(false);
                $table->boolean('ritenuta')->default(false);
                $table->decimal('ritenuta_prozent', 5, 2)->nullable();
                
                // Aufschlag-Tracking
                $table->decimal('aufschlag_prozent', 5, 2)->nullable();
                $table->enum('aufschlag_typ', ['global', 'individuell', 'keiner'])->nullable();
                
                // FatturaPA
                $table->string('cup', 50)->nullable();
                $table->string('cig', 50)->nullable();
                $table->string('codice_commessa', 100)->nullable();
                $table->string('auftrag_id', 100)->nullable();
                $table->date('auftrag_datum')->nullable();
                
                // Sonstiges
                $table->text('bemerkung')->nullable();
                $table->text('bemerkung_kunde')->nullable();
                $table->enum('zahlungsbedingungen', [
                    'sofort', 'netto_7', 'netto_14', 'netto_30', 
                    'netto_60', 'netto_90', 'netto_120', 'bezahlt'
                ])->nullable()->default('netto_30');
                $table->string('pdf_pfad', 500)->nullable();
                $table->string('xml_pfad', 500)->nullable();
                $table->string('externe_referenz', 100)->nullable();
                
                $table->timestamps();
                $table->softDeletes();
                
                // Foreign Keys
                $table->foreign('gebaeude_id')->references('id')->on('gebaeude')->nullOnDelete();
                $table->foreign('rechnungsempfaenger_id')->references('id')->on('adressen')->nullOnDelete();
                $table->foreign('postadresse_id')->references('id')->on('adressen')->nullOnDelete();
                $table->foreign('fattura_profile_id')->references('id')->on('fattura_profile')->nullOnDelete();
                
                // Unique: Pro Jahr nur eine Laufnummer
                $table->unique(['jahr', 'laufnummer']);
            });
        }

        // ═══════════════════════════════════════════════════════════════════
        // 13. RECHNUNG POSITIONEN
        // ═══════════════════════════════════════════════════════════════════
        if (!Schema::hasTable('rechnung_positionen')) {
            Schema::create('rechnung_positionen', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('legacy_id')->nullable()->index();
                $table->unsignedBigInteger('legacy_artikel_id')->nullable();
                $table->unsignedBigInteger('rechnung_id')->index();
                $table->unsignedBigInteger('artikel_gebaeude_id')->nullable()->index();
                $table->unsignedInteger('position')->default(0);
                $table->string('beschreibung', 500);
                $table->decimal('anzahl', 10, 2)->default(1.00);
                $table->string('einheit', 20)->default('Stk');
                $table->decimal('einzelpreis', 12, 2);
                $table->decimal('mwst_satz', 5, 2)->default(22.00);
                $table->decimal('netto_gesamt', 12, 2);
                $table->decimal('mwst_betrag', 12, 2)->default(0.00);
                $table->decimal('brutto_gesamt', 12, 2)->default(0.00);
                $table->timestamps();
                
                $table->foreign('rechnung_id')->references('id')->on('rechnungen')->cascadeOnDelete();
                $table->foreign('artikel_gebaeude_id')->references('id')->on('artikel_gebaeude')->nullOnDelete();
            });
        }

        // ═══════════════════════════════════════════════════════════════════
        // 14. RECHNUNG LOGS (Aktivitätsprotokoll)
        // ═══════════════════════════════════════════════════════════════════
        if (!Schema::hasTable('rechnung_logs')) {
            Schema::create('rechnung_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('rechnung_id')->index();
                $table->string('typ', 50)->index();
                $table->string('titel', 255);
                $table->text('beschreibung')->nullable();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->json('metadata')->nullable();
                $table->string('dokument_pfad', 255)->nullable();
                $table->unsignedBigInteger('referenz_id')->nullable();
                $table->string('referenz_typ', 100)->nullable();
                $table->string('kontakt_person', 255)->nullable();
                $table->string('kontakt_telefon', 255)->nullable();
                $table->string('kontakt_email', 255)->nullable();
                $table->enum('prioritaet', ['niedrig', 'normal', 'hoch', 'kritisch'])->default('normal');
                $table->boolean('ist_oeffentlich')->default(false);
                $table->date('erinnerung_datum')->nullable()->index();
                $table->boolean('erinnerung_erledigt')->default(false);
                $table->timestamps();
                
                $table->foreign('rechnung_id')->references('id')->on('rechnungen')->cascadeOnDelete();
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            });
        }

        // ═══════════════════════════════════════════════════════════════════
        // 15. FATTURA XML LOGS (FatturaPA Protokoll)
        // ═══════════════════════════════════════════════════════════════════
        if (!Schema::hasTable('fattura_xml_logs')) {
            Schema::create('fattura_xml_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('rechnung_id')->index();
                $table->string('progressivo_invio', 50)->unique();
                $table->string('formato_trasmissione', 10)->default('FPR12');
                $table->string('codice_destinatario', 7)->nullable();
                $table->string('pec_destinatario', 255)->nullable();
                
                // Dateien
                $table->string('xml_file_path', 255)->nullable();
                $table->string('xml_filename', 255)->nullable();
                $table->unsignedInteger('xml_file_size')->nullable();
                $table->string('p7m_file_path', 255)->nullable();
                $table->string('p7m_filename', 255)->nullable();
                $table->longText('xml_content')->nullable();
                
                // Status
                $table->string('status', 50)->default('pending')->index();
                $table->string('status_detail', 100)->nullable();
                $table->string('sdi_status_code', 10)->nullable();
                
                // Timestamps
                $table->timestamp('generated_at')->nullable()->index();
                $table->timestamp('signed_at')->nullable();
                $table->timestamp('sent_at')->nullable()->index();
                $table->timestamp('delivered_at')->nullable();
                $table->timestamp('finalized_at')->nullable();
                
                // Validierung
                $table->boolean('is_valid')->default(false);
                $table->json('validation_errors')->nullable();
                $table->text('error_message')->nullable();
                $table->text('error_details')->nullable();
                $table->unsignedTinyInteger('retry_count')->default(0);
                
                // SDI Antworten
                $table->text('sdi_ricevuta')->nullable();
                $table->json('sdi_notifiche')->nullable();
                $table->text('sdi_last_message')->nullable();
                $table->timestamp('sdi_last_check_at')->nullable();
                
                $table->text('notizen')->nullable();
                $table->timestamps();
                
                $table->foreign('rechnung_id')->references('id')->on('rechnungen')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        // In umgekehrter Reihenfolge löschen (wegen Foreign Keys)
        Schema::dropIfExists('fattura_xml_logs');
        Schema::dropIfExists('rechnung_logs');
        Schema::dropIfExists('rechnung_positionen');
        Schema::dropIfExists('rechnungen');
        Schema::dropIfExists('timeline');
        Schema::dropIfExists('gebaeude_aufschlaege');
        Schema::dropIfExists('artikel_gebaeude');
        Schema::dropIfExists('tourgebaeude');
        Schema::dropIfExists('gebaeude');
        Schema::dropIfExists('preis_aufschlaege');
        Schema::dropIfExists('unternehmensprofil');
        Schema::dropIfExists('fattura_profile');
        Schema::dropIfExists('tour');
        Schema::dropIfExists('adressen');
        Schema::dropIfExists('users');
    }
};