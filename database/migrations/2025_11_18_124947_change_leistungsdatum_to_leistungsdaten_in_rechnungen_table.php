<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Führt die Schema-Änderung aus:
     * - neues Feld "leistungsdaten" als String
     * - altes Feld "leistungsdatum" entfernen
     */
    public function up(): void
    {
        Schema::table('rechnungen', function (Blueprint $table) {
            // Neuen String für Leistungsdaten hinzufügen.
            // Nullable, damit alte Datensätze nicht knallen.
            $table->string('leistungsdaten', 255)
                  ->nullable()
                  ->after('rechnungsdatum')
                  ->comment('Textuelle Beschreibung des Leistungszeitraums / Leistungsdatums');

            // Altes Datumsfeld entfernen
            // ❗ Achtung: Das löscht den Inhalt irreversibel!
            $table->dropColumn('leistungsdatum');
        });
    }

    /**
     * Macht die Änderung rückgängig:
     * - altes Feld "leistungsdatum" wieder anlegen
     * - neues Feld "leistungsdaten" entfernen
     */
    public function down(): void
    {
        Schema::table('rechnungen', function (Blueprint $table) {
            // Altes Datumsfeld wiederherstellen
            $table->date('leistungsdatum')
                  ->nullable()
                  ->after('rechnungsdatum')
                  ->comment('Datum der Leistung (alt)');

            // Neues String-Feld wieder entfernen
            $table->dropColumn('leistungsdaten');
        });
    }
};
