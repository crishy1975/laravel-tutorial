<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ergänzt die Tabelle 'gebaeude' um Fattura-/Rechnungs-Default-Felder.
     */
    public function up(): void
    {
        Schema::table('gebaeude', function (Blueprint $table) {
            // 📝 Interne Buchhaltungs-Notiz (längerer Freitext)
            $table->text('bemerkung_buchhaltung')->nullable()->after('bemerkung');

            // 🧾 Öffentliche Vergabe-Codes (CUP/CIG)
            // Längen wie besprochen: CUP bis 20, CIG bis 10 Zeichen
            $table->string('cup', 20)->nullable()->after('bemerkung_buchhaltung');
            $table->string('cig', 10)->nullable()->after('cup');

            // 📑 Auftragsbezug: ID & Datum
            $table->string('auftrag_id', 50)->nullable()->after('cig')->index(); // Index, falls gesucht/gefiltert
            $table->date('auftrag_datum')->nullable()->after('auftrag_id');

            // 📋 Profil-Default (z. B. "Privat 10%", "Firma RC", "Öffentlich Split")
            // Hinweis: Ziel-Tabelle 'fattura_profiles' anpassen, falls bei dir abweichend benannt.
            $table->unsignedBigInteger('fattura_profile_id')->nullable()->after('auftrag_datum')->index();

            // 🔎 Erkennungstext-Template für Bankabgleich (z. B. "RESCH {invoice_number}/{invoice_year} COD {building_codex}")
            // Text statt string(255), damit du flexibel formatieren kannst.
            $table->text('bank_match_text_template')->nullable()->after('fattura_profile_id');
        });

        // Optional: FK separat definieren, damit die Migration auch läuft,
        // wenn 'fattura_profiles' (noch) nicht existiert. Falls vorhanden: FK aktivieren.
        Schema::table('gebaeude', function (Blueprint $table) {
            if (Schema::hasTable('fattura_profiles')) {
                // Null-On-Delete: Wenn Profil gelöscht wird, bleibt Gebäude bestehen und Feld wird auf NULL gesetzt.
                $table->foreign('fattura_profile_id')
                      ->references('id')->on('fattura_profiles')
                      ->nullOnDelete();
            }
        });
    }

    /**
     * Rollback: entfernt alle oben hinzugefügten Spalten/FKs.
     */
    public function down(): void
    {
        Schema::table('gebaeude', function (Blueprint $table) {
            // FK zuerst droppen (nur wenn existiert)
            if (Schema::hasColumn('gebaeude', 'fattura_profile_id')) {
                try {
                    $table->dropForeign(['fattura_profile_id']);
                } catch (\Throwable $e) {
                    // Ignorieren, falls kein FK existierte (z. B. wenn 'fattura_profiles' nie vorhanden war)
                }
            }

            // Spalten entfernen (Reihenfolge egal, hier logisch gruppiert)
            $table->dropColumn([
                'bank_match_text_template',
                'fattura_profile_id',
                'auftrag_datum',
                'auftrag_id',
                'cig',
                'cup',
                'bemerkung_buchhaltung',
            ]);
        });
    }
};
