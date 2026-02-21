<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Korrektur: Spaltenkommentare in impianti-Tabelle richtigstellen
     * und Index auf korrekte Felder anpassen.
     */
    public function up(): void
    {
        Schema::table('impianti', function (Blueprint $table) {
            // Identifikation
            $table->string('Feld_a', 10)->comment('Anlagen-Kodex (PK)')->change();
            $table->string('Feld_b', 10)->nullable()->comment('Kaminkehrer-Kodex 1')->change();
            $table->string('Feld_c', 5)->nullable()->comment('Kaminkehrer-Kodex 2')->change();

            // Aufstellungsort
            $table->string('Feld_h', 100)->nullable()->comment('Gemeinde Aufstellungsort IT')->change();
            $table->string('Feld_i', 100)->nullable()->comment('Gemeinde Aufstellungsort DE')->change();
            $table->string('Feld_j', 100)->nullable()->comment('Fraktion Aufstellungsort IT')->change();
            $table->string('Feld_k', 100)->nullable()->comment('Fraktion Aufstellungsort DE')->change();
            $table->string('Feld_l', 255)->nullable()->comment('Straße Aufstellungsort IT')->change();
            $table->string('Feld_m', 100)->nullable()->comment('Straße Aufstellungsort DE')->change();
            $table->string('Feld_n', 20)->nullable()->comment('Hausnummer Aufstellungsort')->change();
            $table->string('Feld_w', 255)->nullable()->comment('Name Aufstellungsort')->change();

            // Betreiber
            $table->string('Feld_o', 255)->nullable()->comment('Name Betreiber')->change();
            $table->string('Feld_p', 100)->nullable()->comment('Gemeinde Betreiber IT')->change();
            $table->string('Feld_q', 100)->nullable()->comment('Gemeinde Betreiber DE')->change();
            $table->string('Feld_r', 100)->nullable()->comment('Fraktion Betreiber IT')->change();
            $table->string('Feld_s', 100)->nullable()->comment('Fraktion Betreiber DE')->change();
            $table->string('Feld_t', 50)->nullable()->comment('Straße Betreiber IT')->change();
            $table->string('Feld_u', 100)->nullable()->comment('Straße Betreiber DE')->change();
            $table->string('Feld_v', 100)->nullable()->comment('Hausnummer Betreiber')->change();

            // Kessel
            $table->string('Feld_x', 10)->nullable()->comment('Status')->change();
            $table->string('Feld_y', 100)->nullable()->comment('Hersteller Kessel')->change();
            $table->string('Feld_z', 4)->nullable()->comment('Baujahr Kessel')->change();
            $table->string('Feld_ab', 10)->nullable()->comment('Leistung kW')->change();
        });

        // Alten Index auf Feld_k (falsch) durch Index auf Feld_m (Straße DE) ersetzen
        Schema::table('impianti', function (Blueprint $table) {
            // Alten Index droppen (falls vorhanden)
            try {
                $table->dropIndex('idx_standort');
            } catch (\Exception $e) {
                // Index existiert nicht, ignorieren
            }
        });

        Schema::table('impianti', function (Blueprint $table) {
            // Neuen Index auf korrekte Felder
            $table->index(['Feld_i', 'Feld_m', 'Feld_n'], 'idx_standort');
        });
    }

    public function down(): void
    {
        // Nicht reversibel nötig - Kommentare sind nur Doku
    }
};
