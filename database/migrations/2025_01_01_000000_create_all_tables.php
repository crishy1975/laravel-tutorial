<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Master-Migration: Erstellt ALLE Tabellen wenn nicht vorhanden
 * 
 * Diese Migration ersetzt alle einzelnen Migrationen.
 * Sie kann sicher mehrfach ausgeführt werden (idempotent).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ═══════════════════════════════════════════════════════════════
        // 1. BASIS-TABELLEN (ohne Foreign Keys)
        // ═══════════════════════════════════════════════════════════════

        // users
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

        // password_reset_tokens
        if (!Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }

        // sessions
        if (!Schema::hasTable('sessions')) {
            Schema::create('sessions', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->foreignId('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload');
                $table->integer('last_activity')->index();
            });
        }

        // cache
        if (!Schema::hasTable('cache')) {
            Schema::create('cache', function (Blueprint $table) {
                $table->string('key')->primary();
                $table->mediumText('value');
                $table->integer('expiration');
            });
        }

        // cache_locks
        if (!Schema::hasTable('cache_locks')) {
            Schema::create('cache_locks', function (Blueprint $table) {
                $table->string('key')->primary();
                $table->string('owner');
                $table->integer('expiration');
            });
        }

        // jobs
        if (!Schema::hasTable('jobs')) {
            Schema::create('jobs', function (Blueprint $table) {
                $table->id();
                $table->string('queue')->index();
                $table->longText('payload');
                $table->unsignedTinyInteger('attempts');
                $table->unsignedInteger('reserved_at')->nullable();
                $table->unsignedInteger('available_at');
                $table->unsignedInteger('created_at');
            });
        }

        // job_batches
        if (!Schema::hasTable('job_batches')) {
            Schema::create('job_batches', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->string('name');
                $table->integer('total_jobs');
                $table->integer('pending_jobs');
                $table->integer('failed_jobs');
                $table->longText('failed_job_ids');
                $table->mediumText('options')->nullable();
                $table->integer('cancelled_at')->nullable();
                $table->integer('created_at');
                $table->integer('finished_at')->nullable();
            });
        }

        // failed_jobs
        if (!Schema::hasTable('failed_jobs')) {
            Schema::create('failed_jobs', function (Blueprint $table) {
                $table->id();
                $table->string('uuid')->unique();
                $table->text('connection');
                $table->text('queue');
                $table->longText('payload');
                $table->longText('exception');
                $table->timestamp('failed_at')->useCurrent();
            });
        }

        // ═══════════════════════════════════════════════════════════════
        // 2. STAMMDATEN-TABELLEN
        // ═══════════════════════════════════════════════════════════════

        // adressen
        if (!Schema::hasTable('adressen')) {
            Schema::create('adressen', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('legacy_id')->nullable()->comment('Alte Access ID');
                $table->unsignedBigInteger('legacy_mid')->nullable()->comment('Alte Access mId (Referenz-Schlüssel)');
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
                
                $table->index('legacy_mid', 'idx_adressen_legacy_mid');
            });
        }

        // tour
        if (!Schema::hasTable('tour')) {
            Schema::create('tour', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100);
                $table->text('beschreibung')->nullable();
                $table->boolean('aktiv')->default(true);
                $table->timestamps();
            });
        }

        // fattura_profile
        if (!Schema::hasTable('fattura_profile')) {
            Schema::create('fattura_profile', function (Blueprint $table) {
                $table->id();
                $table->string('bezeichnung', 100);
                $table->decimal('mwst_satz', 5, 2)->default(22.00);
                $table->string('natura', 10)->nullable()->comment('N1-N7 für MwSt-Befreiung');
                $table->boolean('split_payment')->default(false);
                $table->boolean('ritenuta')->default(false);
                $table->decimal('ritenuta_satz', 5, 2)->nullable();
                $table->string('ritenuta_tipo', 4)->nullable();
                $table->string('ritenuta_causale', 2)->nullable();
                $table->text('bemerkung')->nullable();
                $table->boolean('aktiv')->default(true);
                $table->timestamps();
            });
        }

        // preis_aufschlaege
        if (!Schema::hasTable('preis_aufschlaege')) {
            Schema::create('preis_aufschlaege', function (Blueprint $table) {
                $table->id();
                $table->integer('jahr')->unique();
                $table->decimal('prozent', 5, 2)->default(0);
                $table->string('beschreibung', 500)->nullable();
                $table->timestamps();
            });
        }

        // unternehmensprofil
        if (!Schema::hasTable('unternehmensprofil')) {
            Schema::create('unternehmensprofil', function (Blueprint $table) {
                $table->id();
                $table->string('firmenname');
                $table->string('inhabername')->nullable();
                $table->string('strasse')->nullable();
                $table->string('hausnummer', 20)->nullable();
                $table->string('plz', 10)->nullable();
                $table->string('wohnort', 100)->nullable();
                $table->string('provinz', 4)->nullable();
                $table->string('land', 50)->default('IT');
                $table->string('telefon', 50)->nullable();
                $table->string('mobil', 50)->nullable();
                $table->string('fax', 50)->nullable();
                $table->string('email')->nullable();
                $table->string('website')->nullable();
                
                // Bank
                $table->string('bank_name')->nullable();
                $table->string('iban', 34)->nullable();
                $table->string('bic', 11)->nullable();
                
                // SMTP
                $table->string('smtp_host')->nullable();
                $table->integer('smtp_port')->nullable();
                $table->string('smtp_encryption', 10)->nullable();
                $table->string('smtp_username')->nullable();
                $table->string('smtp_password')->nullable();
                $table->string('smtp_from_address')->nullable();
                $table->string('smtp_from_name')->nullable();
                
                // PEC SMTP
                $table->string('pec_smtp_host')->nullable();
                $table->integer('pec_smtp_port')->nullable();
                $table->string('pec_smtp_encryption', 10)->nullable();
                $table->string('pec_smtp_username')->nullable();
                $table->string('pec_smtp_password')->nullable();
                $table->string('pec_from_address')->nullable();
                $table->string('pec_from_name')->nullable();
                
                // Design
                $table->string('logo_pfad')->nullable();
                $table->integer('logo_breite')->default(200);
                $table->integer('logo_hoehe')->default(80);
                $table->text('briefkopf_text')->nullable();
                $table->text('briefkopf_rechts')->nullable();
                $table->text('fusszeile_text')->nullable();
                $table->string('farbe_primaer', 7)->default('#003366');
                $table->string('farbe_sekundaer', 7)->default('#666666');
                $table->string('farbe_akzent', 7)->default('#0066CC');
                $table->string('schriftart', 50)->default('Helvetica');
                $table->integer('schriftgroesse')->default(10);
                
                // Rechnung
                $table->string('rechnungsnummer_praefix', 10)->nullable();
                $table->integer('rechnungsnummer_startjahr')->default(2025);
                $table->integer('rechnungsnummer_laenge')->default(5);
                $table->integer('zahlungsziel_tage')->default(30);
                $table->text('zahlungshinweis')->nullable();
                $table->text('kleinunternehmer_hinweis')->nullable();
                $table->text('rechnung_einleitung')->nullable();
                $table->text('rechnung_schlusstext')->nullable();
                $table->text('rechnung_agb_text')->nullable();
                
                // Italien FatturaPA
                $table->string('ragione_sociale')->nullable();
                $table->string('partita_iva', 11)->nullable();
                $table->string('codice_fiscale', 16)->nullable();
                $table->string('regime_fiscale', 4)->default('RF01');
                $table->string('pec_email')->nullable();
                $table->string('rea_ufficio', 2)->nullable();
                $table->string('rea_numero', 20)->nullable();
                $table->decimal('capitale_sociale', 15, 2)->nullable();
                $table->enum('stato_liquidazione', ['LN', 'LS'])->default('LN');
                
                // Allgemein
                $table->string('waehrung', 3)->default('EUR');
                $table->string('sprache', 2)->default('de');
                $table->string('zeitzone', 50)->default('Europe/Rome');
                $table->string('datumsformat', 20)->default('d.m.Y');
                $table->string('zahlenformat', 20)->default('de_DE');
                $table->boolean('ist_kleinunternehmer')->default(false);
                $table->boolean('mwst_ausweisen')->default(true);
                $table->decimal('standard_mwst_satz', 5, 2)->default(22.00);
                $table->boolean('ist_aktiv')->default(true);
                $table->text('notizen')->nullable();
                $table->timestamps();
                
                $table->index('ist_aktiv');
                $table->index('firmenname');
            });
        }

        // ═══════════════════════════════════════════════════════════════
        // 3. GEBÄUDE-TABELLEN (mit Foreign Keys zu adressen, tour)
        // ═══════════════════════════════════════════════════════════════

        // gebaeude
        if (!Schema::hasTable('gebaeude')) {
            Schema::create('gebaeude', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('legacy_id')->nullable()->comment('Alte Access ID');
                $table->unsignedBigInteger('legacy_mid')->nullable()->comment('Alte Access mId');
                $table->string('codex', 50)->nullable();
                $table->unsignedBigInteger('postadresse_id')->nullable();
                $table->unsignedBigInteger('rechnungsempfaenger_id')->nullable();
                $table->string('gebaeude_name', 255)->nullable();
                $table->string('strasse', 255)->nullable();
                $table->string('hausnummer', 50)->nullable();
                $table->string('plz', 10)->nullable();
                $table->string('wohnort', 100)->nullable();
                $table->string('land', 50)->nullable();
                $table->text('bemerkung')->nullable();
                $table->boolean('veraendert')->default(false);
                $table->timestamp('veraendert_wann')->nullable();
                $table->date('letzter_termin')->nullable();
                $table->date('datum_faelligkeit')->nullable();
                $table->integer('geplante_reinigungen')->default(0);
                $table->integer('gemachte_reinigungen')->default(0);
                $table->boolean('faellig')->default(false);
                $table->boolean('rechnung_schreiben')->default(false);
                
                // Monate
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
                $table->text('bemerkung_buchhaltung')->nullable();
                $table->string('cup', 50)->nullable();
                $table->string('cig', 50)->nullable();
                $table->string('auftrag_id', 100)->nullable();
                $table->date('auftrag_datum')->nullable();
                $table->unsignedBigInteger('fattura_profile_id')->nullable();
                $table->string('bank_match_text_template', 255)->nullable();
                
                $table->softDeletes();
                $table->timestamps();
                
                $table->index('legacy_id');
                $table->index('legacy_mid');
                $table->index('codex');
                $table->index('faellig');
                
                $table->foreign('postadresse_id')->references('id')->on('adressen')->nullOnDelete();
                $table->foreign('rechnungsempfaenger_id')->references('id')->on('adressen')->nullOnDelete();
                $table->foreign('fattura_profile_id')->references('id')->on('fattura_profile')->nullOnDelete();
            });
        }

        // tourgebaeude (Pivot)
        if (!Schema::hasTable('tourgebaeude')) {
            Schema::create('tourgebaeude', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tour_id');
                $table->unsignedBigInteger('gebaeude_id');
                $table->integer('reihenfolge')->default(0);
                $table->timestamps();
                
                $table->unique(['tour_id', 'gebaeude_id']);
                $table->foreign('tour_id')->references('id')->on('tour')->cascadeOnDelete();
                $table->foreign('gebaeude_id')->references('id')->on('gebaeude')->cascadeOnDelete();
            });
        }

        // gebaeude_aufschlaege
        if (!Schema::hasTable('gebaeude_aufschlaege')) {
            Schema::create('gebaeude_aufschlaege', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('gebaeude_id');
                $table->decimal('prozent', 5, 2)->default(0);
                $table->string('grund', 500)->nullable();
                $table->date('gueltig_ab');
                $table->date('gueltig_bis')->nullable();
                $table->timestamps();
                
                $table->index(['gebaeude_id', 'gueltig_ab']);
                $table->foreign('gebaeude_id')->references('id')->on('gebaeude')->cascadeOnDelete();
            });
        }

        // gebaeude_dokumente
        if (!Schema::hasTable('gebaeude_dokumente')) {
            Schema::create('gebaeude_dokumente', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('gebaeude_id');
                $table->string('dateiname');
                $table->string('original_name');
                $table->string('mime_type', 150);
                $table->unsignedBigInteger('dateigroesse');
                $table->string('kategorie', 50)->nullable();
                $table->text('beschreibung')->nullable();
                $table->unsignedBigInteger('hochgeladen_von')->nullable();
                $table->timestamps();
                
                $table->index('gebaeude_id');
                $table->foreign('gebaeude_id')->references('id')->on('gebaeude')->cascadeOnDelete();
                $table->foreign('hochgeladen_von')->references('id')->on('users')->nullOnDelete();
            });
        }

        // gebaeude_logs
        if (!Schema::hasTable('gebaeude_logs')) {
            Schema::create('gebaeude_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('gebaeude_id');
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('typ', 50);
                $table->string('titel');
                $table->text('nachricht')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                
                $table->index(['gebaeude_id', 'created_at']);
                $table->foreign('gebaeude_id')->references('id')->on('gebaeude')->cascadeOnDelete();
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            });
        }

        // artikel_gebaeude
        if (!Schema::hasTable('artikel_gebaeude')) {
            Schema::create('artikel_gebaeude', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('legacy_id')->nullable();
                $table->unsignedBigInteger('legacy_mid')->nullable();
                $table->unsignedBigInteger('gebaeude_id');
                $table->string('beschreibung', 255);
                $table->decimal('anzahl', 10, 2)->default(1);
                $table->decimal('einzelpreis', 10, 2)->default(0);
                $table->decimal('basis_preis', 10, 2)->nullable();
                $table->integer('basis_jahr')->nullable();
                $table->boolean('aktiv')->default(true);
                $table->integer('reihenfolge')->nullable();
                $table->timestamps();
                
                $table->index('gebaeude_id');
                $table->foreign('gebaeude_id')->references('id')->on('gebaeude')->cascadeOnDelete();
            });
        }

        // timeline
        if (!Schema::hasTable('timeline')) {
            Schema::create('timeline', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('gebaeude_id');
                $table->date('datum');
                $table->text('bemerkung')->nullable();
                $table->boolean('verrechnen')->default(false);
                $table->date('verrechnet_am')->nullable();
                $table->timestamps();
                
                $table->index(['gebaeude_id', 'datum']);
                $table->foreign('gebaeude_id')->references('id')->on('gebaeude')->cascadeOnDelete();
            });
        }

        // ═══════════════════════════════════════════════════════════════
        // 4. RECHNUNGS-TABELLEN
        // ═══════════════════════════════════════════════════════════════

        // rechnungen
        if (!Schema::hasTable('rechnungen')) {
            Schema::create('rechnungen', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('legacy_id')->nullable();
                $table->unsignedBigInteger('gebaeude_id')->nullable();
                $table->unsignedBigInteger('adresse_id')->nullable();
                $table->unsignedBigInteger('fattura_profile_id')->nullable();
                
                $table->string('rechnungsnummer', 50)->nullable();
                $table->date('rechnungsdatum');
                $table->date('faelligkeitsdatum')->nullable();
                $table->string('status', 20)->default('draft');
                
                // Empfänger-Snapshot
                $table->string('empfaenger_name');
                $table->string('empfaenger_strasse')->nullable();
                $table->string('empfaenger_hausnummer', 50)->nullable();
                $table->string('empfaenger_plz', 10)->nullable();
                $table->string('empfaenger_wohnort', 100)->nullable();
                $table->string('empfaenger_provinz', 4)->nullable();
                $table->string('empfaenger_land', 50)->default('IT');
                $table->string('empfaenger_steuernummer', 50)->nullable();
                $table->string('empfaenger_mwst_nummer', 50)->nullable();
                $table->string('empfaenger_codice_univoco', 20)->nullable();
                $table->string('empfaenger_pec')->nullable();
                
                // FatturaPA
                $table->string('fattura_tipo', 4)->default('TD01');
                $table->decimal('mwst_satz', 5, 2)->default(22.00);
                $table->string('natura', 10)->nullable();
                $table->boolean('split_payment')->default(false);
                $table->boolean('ritenuta')->default(false);
                $table->decimal('ritenuta_satz', 5, 2)->nullable();
                $table->string('ritenuta_tipo', 4)->nullable();
                $table->string('ritenuta_causale', 2)->nullable();
                
                // Beträge
                $table->decimal('netto_summe', 12, 2)->default(0);
                $table->decimal('mwst_betrag', 12, 2)->default(0);
                $table->decimal('brutto_summe', 12, 2)->default(0);
                $table->decimal('ritenuta_betrag', 12, 2)->default(0);
                $table->decimal('zahlbetrag', 12, 2)->default(0);
                
                // Referenzen
                $table->string('cup', 50)->nullable();
                $table->string('cig', 50)->nullable();
                $table->string('auftrag_id', 100)->nullable();
                $table->date('auftrag_datum')->nullable();
                
                // Texte
                $table->text('einleitung')->nullable();
                $table->text('schlusstext')->nullable();
                $table->text('bemerkung')->nullable();
                
                // Versand
                $table->timestamp('gesendet_am')->nullable();
                $table->string('gesendet_an')->nullable();
                $table->timestamp('bezahlt_am')->nullable();
                $table->decimal('bezahlt_betrag', 12, 2)->nullable();
                
                $table->timestamps();
                $table->softDeletes();
                
                $table->index('rechnungsnummer');
                $table->index('status');
                $table->index('rechnungsdatum');
                $table->index('gebaeude_id');
                $table->index('adresse_id');
                
                $table->foreign('gebaeude_id')->references('id')->on('gebaeude')->nullOnDelete();
                $table->foreign('adresse_id')->references('id')->on('adressen')->nullOnDelete();
                $table->foreign('fattura_profile_id')->references('id')->on('fattura_profile')->nullOnDelete();
            });
        }

        // rechnung_positionen
        if (!Schema::hasTable('rechnung_positionen')) {
            Schema::create('rechnung_positionen', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('rechnung_id');
                $table->unsignedBigInteger('artikel_gebaeude_id')->nullable();
                $table->integer('position')->default(0);
                $table->string('beschreibung');
                $table->decimal('anzahl', 10, 2)->default(1);
                $table->decimal('einzelpreis', 10, 2)->default(0);
                $table->decimal('gesamtpreis', 12, 2)->default(0);
                $table->decimal('mwst_satz', 5, 2)->default(22.00);
                $table->timestamps();
                
                $table->index('rechnung_id');
                $table->foreign('rechnung_id')->references('id')->on('rechnungen')->cascadeOnDelete();
                $table->foreign('artikel_gebaeude_id')->references('id')->on('artikel_gebaeude')->nullOnDelete();
            });
        }

        // rechnung_logs
        if (!Schema::hasTable('rechnung_logs')) {
            Schema::create('rechnung_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('rechnung_id');
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('typ', 50);
                $table->string('titel');
                $table->text('nachricht')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                
                $table->index(['rechnung_id', 'created_at']);
                $table->foreign('rechnung_id')->references('id')->on('rechnungen')->cascadeOnDelete();
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            });
        }

        // fattura_xml_logs
        if (!Schema::hasTable('fattura_xml_logs')) {
            Schema::create('fattura_xml_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('rechnung_id');
                $table->string('dateiname');
                $table->string('progressivo', 10)->nullable();
                $table->string('status', 20)->default('generated');
                $table->longText('xml_content')->nullable();
                $table->text('fehler_meldung')->nullable();
                $table->timestamp('gesendet_am')->nullable();
                $table->timestamps();
                
                $table->index('rechnung_id');
                $table->foreign('rechnung_id')->references('id')->on('rechnungen')->cascadeOnDelete();
            });
        }

        // ═══════════════════════════════════════════════════════════════
        // 5. ANGEBOTE-TABELLEN
        // ═══════════════════════════════════════════════════════════════

        // angebote
        if (!Schema::hasTable('angebote')) {
            Schema::create('angebote', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('gebaeude_id')->nullable();
                $table->unsignedBigInteger('adresse_id')->nullable();
                
                $table->string('angebotsnummer', 50)->nullable();
                $table->date('angebotsdatum');
                $table->date('gueltig_bis')->nullable();
                $table->string('status', 20)->default('draft');
                
                // Empfänger-Snapshot
                $table->string('empfaenger_name');
                $table->string('empfaenger_strasse')->nullable();
                $table->string('empfaenger_plz', 10)->nullable();
                $table->string('empfaenger_wohnort', 100)->nullable();
                
                // Beträge
                $table->decimal('netto_summe', 12, 2)->default(0);
                $table->decimal('mwst_satz', 5, 2)->default(22.00);
                $table->decimal('mwst_betrag', 12, 2)->default(0);
                $table->decimal('brutto_summe', 12, 2)->default(0);
                
                // Texte
                $table->text('einleitung')->nullable();
                $table->text('schlusstext')->nullable();
                $table->text('bemerkung')->nullable();
                
                $table->timestamps();
                $table->softDeletes();
                
                $table->index('angebotsnummer');
                $table->index('status');
                $table->foreign('gebaeude_id')->references('id')->on('gebaeude')->nullOnDelete();
                $table->foreign('adresse_id')->references('id')->on('adressen')->nullOnDelete();
            });
        }

        // angebot_positionen
        if (!Schema::hasTable('angebot_positionen')) {
            Schema::create('angebot_positionen', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('angebot_id');
                $table->integer('position')->default(0);
                $table->string('beschreibung');
                $table->decimal('anzahl', 10, 2)->default(1);
                $table->decimal('einzelpreis', 10, 2)->default(0);
                $table->decimal('gesamtpreis', 12, 2)->default(0);
                $table->timestamps();
                
                $table->foreign('angebot_id')->references('id')->on('angebote')->cascadeOnDelete();
            });
        }

        // angebot_logs
        if (!Schema::hasTable('angebot_logs')) {
            Schema::create('angebot_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('angebot_id');
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('typ', 50);
                $table->string('titel');
                $table->text('nachricht')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                
                $table->index(['angebot_id', 'created_at']);
                $table->foreign('angebot_id')->references('id')->on('angebote')->cascadeOnDelete();
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            });
        }

        // ═══════════════════════════════════════════════════════════════
        // 6. MAHNUNGEN-TABELLEN
        // ═══════════════════════════════════════════════════════════════

        // mahnung_stufen
        if (!Schema::hasTable('mahnung_stufen')) {
            Schema::create('mahnung_stufen', function (Blueprint $table) {
                $table->id();
                $table->integer('stufe')->unique();
                $table->string('bezeichnung');
                $table->integer('tage_nach_faelligkeit');
                $table->decimal('gebuehr', 10, 2)->default(0);
                $table->text('text_vorlage')->nullable();
                $table->boolean('aktiv')->default(true);
                $table->timestamps();
            });
        }

        // mahnung_einstellungen
        if (!Schema::hasTable('mahnung_einstellungen')) {
            Schema::create('mahnung_einstellungen', function (Blueprint $table) {
                $table->id();
                $table->string('schluessel')->unique();
                $table->text('wert')->nullable();
                $table->string('typ', 20)->default('string');
                $table->string('beschreibung')->nullable();
                $table->timestamps();
            });
        }

        // mahnungen
        if (!Schema::hasTable('mahnungen')) {
            Schema::create('mahnungen', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('rechnung_id');
                $table->unsignedBigInteger('mahnung_stufe_id');
                $table->string('mahnnummer', 50)->nullable();
                $table->date('mahndatum');
                $table->date('faellig_bis')->nullable();
                $table->decimal('offener_betrag', 12, 2);
                $table->decimal('mahngebuehr', 10, 2)->default(0);
                $table->decimal('gesamtbetrag', 12, 2);
                $table->string('status', 20)->default('erstellt');
                $table->timestamp('gesendet_am')->nullable();
                $table->text('bemerkung')->nullable();
                $table->timestamps();
                $table->softDeletes();
                
                $table->index(['rechnung_id', 'mahnung_stufe_id']);
                $table->foreign('rechnung_id')->references('id')->on('rechnungen')->cascadeOnDelete();
                $table->foreign('mahnung_stufe_id')->references('id')->on('mahnung_stufen');
            });
        }

        // mahnung_ausschluesse (Adressen)
        if (!Schema::hasTable('mahnung_ausschluesse')) {
            Schema::create('mahnung_ausschluesse', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('adresse_id');
                $table->string('grund')->nullable();
                $table->date('gueltig_bis')->nullable();
                $table->unsignedBigInteger('erstellt_von')->nullable();
                $table->timestamps();
                
                $table->unique('adresse_id');
                $table->foreign('adresse_id')->references('id')->on('adressen')->cascadeOnDelete();
                $table->foreign('erstellt_von')->references('id')->on('users')->nullOnDelete();
            });
        }

        // mahnung_rechnung_ausschluesse
        if (!Schema::hasTable('mahnung_rechnung_ausschluesse')) {
            Schema::create('mahnung_rechnung_ausschluesse', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('rechnung_id');
                $table->string('grund')->nullable();
                $table->date('gueltig_bis')->nullable();
                $table->unsignedBigInteger('erstellt_von')->nullable();
                $table->timestamps();
                
                $table->unique('rechnung_id');
                $table->foreign('rechnung_id')->references('id')->on('rechnungen')->cascadeOnDelete();
                $table->foreign('erstellt_von')->references('id')->on('users')->nullOnDelete();
            });
        }

        // ═══════════════════════════════════════════════════════════════
        // 7. BANK-TABELLEN
        // ═══════════════════════════════════════════════════════════════

        // bank_buchungen
        if (!Schema::hasTable('bank_buchungen')) {
            Schema::create('bank_buchungen', function (Blueprint $table) {
                $table->id();
                $table->date('buchungsdatum');
                $table->date('wertstellung')->nullable();
                $table->string('verwendungszweck', 500)->nullable();
                $table->string('auftraggeber', 255)->nullable();
                $table->string('iban_auftraggeber', 34)->nullable();
                $table->decimal('betrag', 12, 2);
                $table->string('waehrung', 3)->default('EUR');
                $table->string('buchungsart', 50)->nullable();
                
                // Matching
                $table->unsignedBigInteger('rechnung_id')->nullable();
                $table->string('match_status', 20)->default('unmatched');
                $table->integer('match_score')->nullable();
                $table->timestamp('matched_at')->nullable();
                $table->unsignedBigInteger('matched_by')->nullable();
                
                // Import
                $table->string('import_hash', 64)->nullable()->unique();
                $table->unsignedBigInteger('import_log_id')->nullable();
                
                $table->timestamps();
                
                $table->index('buchungsdatum');
                $table->index('match_status');
                $table->index('rechnung_id');
                $table->foreign('rechnung_id')->references('id')->on('rechnungen')->nullOnDelete();
                $table->foreign('matched_by')->references('id')->on('users')->nullOnDelete();
            });
        }

        // bank_import_logs
        if (!Schema::hasTable('bank_import_logs')) {
            Schema::create('bank_import_logs', function (Blueprint $table) {
                $table->id();
                $table->string('dateiname');
                $table->integer('anzahl_zeilen')->default(0);
                $table->integer('importiert')->default(0);
                $table->integer('duplikate')->default(0);
                $table->integer('fehler')->default(0);
                $table->unsignedBigInteger('user_id')->nullable();
                $table->timestamps();
                
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            });
        }

        // bank_matching_config
        if (!Schema::hasTable('bank_matching_config')) {
            Schema::create('bank_matching_config', function (Blueprint $table) {
                $table->id();
                $table->string('schluessel')->unique();
                $table->text('wert')->nullable();
                $table->string('typ', 20)->default('string');
                $table->string('beschreibung')->nullable();
                $table->timestamps();
            });
        }

        // ═══════════════════════════════════════════════════════════════
        // FERTIG
        // ═══════════════════════════════════════════════════════════════
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // In umgekehrter Reihenfolge löschen (wegen Foreign Keys)
        Schema::dropIfExists('bank_matching_config');
        Schema::dropIfExists('bank_import_logs');
        Schema::dropIfExists('bank_buchungen');
        Schema::dropIfExists('mahnung_rechnung_ausschluesse');
        Schema::dropIfExists('mahnung_ausschluesse');
        Schema::dropIfExists('mahnungen');
        Schema::dropIfExists('mahnung_einstellungen');
        Schema::dropIfExists('mahnung_stufen');
        Schema::dropIfExists('angebot_logs');
        Schema::dropIfExists('angebot_positionen');
        Schema::dropIfExists('angebote');
        Schema::dropIfExists('fattura_xml_logs');
        Schema::dropIfExists('rechnung_logs');
        Schema::dropIfExists('rechnung_positionen');
        Schema::dropIfExists('rechnungen');
        Schema::dropIfExists('timeline');
        Schema::dropIfExists('artikel_gebaeude');
        Schema::dropIfExists('gebaeude_logs');
        Schema::dropIfExists('gebaeude_dokumente');
        Schema::dropIfExists('gebaeude_aufschlaege');
        Schema::dropIfExists('tourgebaeude');
        Schema::dropIfExists('gebaeude');
        Schema::dropIfExists('unternehmensprofil');
        Schema::dropIfExists('preis_aufschlaege');
        Schema::dropIfExists('fattura_profile');
        Schema::dropIfExists('tour');
        Schema::dropIfExists('adressen');
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('cache_locks');
        Schema::dropIfExists('cache');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
