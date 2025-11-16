<?php
// database/migrations/2025_11_16_120000_create_rechnungen_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rechnungen', function (Blueprint $table) {
            $table->id();

            // ═══════════════════════════════════════════════════════════
            // 📋 RECHNUNGSNUMMER (zusammengesetzt)
            // ═══════════════════════════════════════════════════════════
            $table->year('jahr');                           // z.B. 2025
            $table->unsignedInteger('laufnummer');          // z.B. 42 → "2025/0042"
            $table->unique(['jahr', 'laufnummer']);         // verhindert Duplikate

            // ═══════════════════════════════════════════════════════════
            // 🔗 REFERENZEN (für Navigation, nullable mit nullOnDelete)
            // ═══════════════════════════════════════════════════════════
            $table->foreignId('gebaeude_id')
                ->nullable()
                ->constrained('gebaeude')
                ->nullOnDelete();

            $table->foreignId('rechnungsempfaenger_id')     // Kondominium (Zahler)
                ->nullable()
                ->constrained('adressen')
                ->nullOnDelete();

            $table->foreignId('postadresse_id')             // Verwalter (Versand)
                ->nullable()
                ->constrained('adressen')
                ->nullOnDelete();

            $table->foreignId('fattura_profile_id')
                ->nullable()
                ->constrained('fattura_profile')
                ->nullOnDelete();

            // ═══════════════════════════════════════════════════════════
            // 📸 SNAPSHOT: Rechnungsempfänger (Kondominium = Zahler)
            // ═══════════════════════════════════════════════════════════
            $table->string('re_name', 200);                 // Pflicht für Rechnung!
            $table->string('re_strasse', 255)->nullable();
            $table->string('re_hausnummer', 10)->nullable();
            $table->string('re_plz', 10)->nullable();
            $table->string('re_wohnort', 100)->nullable();
            $table->string('re_provinz', 4)->nullable();
            $table->string('re_land', 50)->nullable();
            $table->string('re_steuernummer', 50)->nullable();
            $table->string('re_mwst_nummer', 50)->nullable();
            $table->string('re_codice_univoco', 20)->nullable();
            $table->string('re_pec', 255)->nullable();

            // ═══════════════════════════════════════════════════════════
            // 📸 SNAPSHOT: Postadresse (Verwalter = Empfänger)
            // ═══════════════════════════════════════════════════════════
            $table->string('post_name', 200);               // Pflicht für Versand!
            $table->string('post_strasse', 255)->nullable();
            $table->string('post_hausnummer', 10)->nullable();
            $table->string('post_plz', 10)->nullable();
            $table->string('post_wohnort', 100)->nullable();
            $table->string('post_provinz', 4)->nullable();
            $table->string('post_land', 50)->nullable();
            $table->string('post_email', 255)->nullable();  // Wichtig für E-Mail-Versand
            $table->string('post_pec', 255)->nullable();    // Wichtig für PEC-Versand

            // ═══════════════════════════════════════════════════════════
            // 📸 SNAPSHOT: Gebäude-Info (Kontext)
            // ═══════════════════════════════════════════════════════════
            $table->string('geb_codex', 15)->nullable();
            $table->string('geb_name', 100)->nullable();
            $table->string('geb_adresse', 500)->nullable(); // Vollständige Adresse

            // ═══════════════════════════════════════════════════════════
            // 📅 DATUMSFELDER
            // ═══════════════════════════════════════════════════════════
            $table->date('rechnungsdatum');                 // Ausstellungsdatum
            $table->date('leistungsdatum')->nullable();     // Datum der Leistung
            $table->date('zahlungsziel')->nullable();       // Fällig bis
            $table->date('bezahlt_am')->nullable();         // Tatsächlicher Zahlungseingang

            // ═══════════════════════════════════════════════════════════
            // 💰 BETRÄGE (EUR, 2 Dezimalstellen)
            // ═══════════════════════════════════════════════════════════
            $table->decimal('netto_summe', 12, 2)->default(0);
            $table->decimal('mwst_betrag', 12, 2)->default(0);
            $table->decimal('brutto_summe', 12, 2)->default(0);
            $table->decimal('ritenuta_betrag', 12, 2)->default(0);     // Quellensteuer
            $table->decimal('zahlbar_betrag', 12, 2)->default(0);      // Brutto - Ritenuta

            // ═══════════════════════════════════════════════════════════
            // 🔖 STATUS & FLAGS
            // ═══════════════════════════════════════════════════════════
            $table->enum('status', [
                'draft',        // Entwurf (editierbar)
                'sent',         // Versendet
                'paid',         // Bezahlt
                'cancelled',    // Storniert
                'overdue'       // Überfällig
            ])->default('draft')->index();

            // ═══════════════════════════════════════════════════════════
            // 📸 SNAPSHOT: Profil-Einstellungen (aus fattura_profile)
            // ═══════════════════════════════════════════════════════════
            $table->string('profile_bezeichnung', 100)->nullable();
            $table->decimal('mwst_satz', 5, 2)->default(22.00);        // Standard-MwSt
            $table->boolean('split_payment')->default(false);
            $table->boolean('ritenuta')->default(false);
            $table->decimal('ritenuta_prozent', 5, 2)->nullable();     // z.B. 4.00

            // ═══════════════════════════════════════════════════════════
            // 📄 FATTURAPA-FELDER (aus Gebäude übernommen)
            // ═══════════════════════════════════════════════════════════
            $table->string('cup', 20)->nullable();
            $table->string('cig', 10)->nullable();
            $table->string('auftrag_id', 50)->nullable();
            $table->date('auftrag_datum')->nullable();

            // ═══════════════════════════════════════════════════════════
            // 📝 TEXTE & NOTIZEN
            // ═══════════════════════════════════════════════════════════
            $table->text('bemerkung')->nullable();                      // Intern
            $table->text('bemerkung_kunde')->nullable();                // Auf Rechnung sichtbar
            $table->text('zahlungsbedingungen')->nullable();            // z.B. "30 Tage netto"

            // ═══════════════════════════════════════════════════════════
            // 🔗 DATEIPFADE & EXTERNE REFERENZEN
            // ═══════════════════════════════════════════════════════════
            $table->string('pdf_pfad', 500)->nullable();                // storage/rechnungen/2025/0042.pdf
            $table->string('xml_pfad', 500)->nullable();                // FatturaPA XML
            $table->string('externe_referenz', 100)->nullable();        // Buchhaltungssoftware-ID

            // ═══════════════════════════════════════════════════════════
            // 🕒 TIMESTAMPS & SOFT DELETES
            // ═══════════════════════════════════════════════════════════
            $table->timestamps();
            $table->softDeletes();

            // ═══════════════════════════════════════════════════════════
            // 🔍 INDIZES für Performance
            // ═══════════════════════════════════════════════════════════
            $table->index(['jahr', 'laufnummer']);          // Rechnungsnummer-Suche
            $table->index(['status', 'zahlungsziel']);      // Überfällige Rechnungen
            $table->index(['rechnungsdatum']);              // Chronologische Suche
            $table->index(['gebaeude_id', 'status']);       // Rechnungen pro Gebäude
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rechnungen');
    }
};