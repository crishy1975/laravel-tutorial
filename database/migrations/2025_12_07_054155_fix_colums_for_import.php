<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fix: Alle Spalten für Import anpassen
 * 
 * Behebt:
 * 1. adressen.hausnummer zu kurz
 * 2. gebaeude.hausnummer zu kurz
 * 3. gebaeude.plz zu kurz (fehlerhafte Daten)
 * 4. gebaeude.postadresse_id NOT NULL
 * 5. gebaeude.rechnungsempfaenger_id NOT NULL
 * 6. rechnungen.post_* Felder NOT NULL
 */
return new class extends Migration
{
    public function up(): void
    {
        // ═══════════════════════════════════════════════════════════
        // 📋 ADRESSEN
        // ═══════════════════════════════════════════════════════════
        Schema::table('adressen', function (Blueprint $table) {
            // Hausnummer: "4a - 3°Piano", "244 a-b-c-d", "21 Primo piano interno 2"
            $table->string('hausnummer', 100)->nullable()->change();
        });

        // ═══════════════════════════════════════════════════════════
        // 🏢 GEBÄUDE
        // ═══════════════════════════════════════════════════════════
        Schema::table('gebaeude', function (Blueprint $table) {
            // Hausnummer: "19c4/21c8/21a5", "31 - 33 - 35"
            $table->string('hausnummer', 100)->nullable()->change();
            
            // PLZ: "39055 Laives" (fehlerhafte Daten mit Ort)
            $table->string('plz', 50)->nullable()->change();
            
            // Postadresse und Rechnungsempfänger - MÜSSEN nullable sein!
            $table->unsignedBigInteger('postadresse_id')->nullable()->change();
            $table->unsignedBigInteger('rechnungsempfaenger_id')->nullable()->change();
        });

        // ═══════════════════════════════════════════════════════════
        // 🧾 RECHNUNGEN - Snapshot-Felder nullable machen
        // ═══════════════════════════════════════════════════════════
        Schema::table('rechnungen', function (Blueprint $table) {
            // Snapshot Postadresse - alle nullable!
            $table->string('post_name', 255)->nullable()->change();
            $table->string('post_strasse', 255)->nullable()->change();
            $table->string('post_hausnummer', 100)->nullable()->change();
            $table->string('post_plz', 20)->nullable()->change();
            $table->string('post_wohnort', 255)->nullable()->change();
            $table->string('post_provinz', 10)->nullable()->change();
            $table->string('post_land', 10)->nullable()->change();
            $table->string('post_email', 255)->nullable()->change();
            $table->string('post_pec', 255)->nullable()->change();
            
            // Snapshot Rechnungsempfänger - alle nullable!
            $table->string('re_name', 255)->nullable()->change();
            $table->string('re_strasse', 255)->nullable()->change();
            $table->string('re_hausnummer', 100)->nullable()->change();
            $table->string('re_plz', 20)->nullable()->change();
            $table->string('re_wohnort', 255)->nullable()->change();
            $table->string('re_provinz', 10)->nullable()->change();
            $table->string('re_land', 10)->nullable()->change();
            $table->string('re_steuernummer', 50)->nullable()->change();
            $table->string('re_mwst_nummer', 50)->nullable()->change();
            $table->string('re_codice_univoco', 20)->nullable()->change();
            $table->string('re_pec', 255)->nullable()->change();
            
            // Snapshot Gebäude
            $table->string('geb_codex', 50)->nullable()->change();
            $table->string('geb_name', 255)->nullable()->change();
            $table->string('geb_adresse', 500)->nullable()->change();
            
            // Sonstige Felder die evtl. NOT NULL sind
            $table->text('fattura_causale')->nullable()->change();
            $table->string('cup', 50)->nullable()->change();
            $table->string('cig', 50)->nullable()->change();
            $table->string('codice_commessa', 100)->nullable()->change();
            $table->string('auftrag_id', 100)->nullable()->change();
        });
    }

    public function down(): void
    {
        // Achtung: Down könnte Probleme verursachen wenn Daten existieren!
        // Hier nichts ändern - Felder bleiben nullable
    }
};