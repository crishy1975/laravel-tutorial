<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Neue Felder für Verrechnungs-Status in der Timeline-Tabelle hinzufügen.
     */
    public function up(): void
    {
        Schema::table('timeline', function (Blueprint $table) {
            // Boolean-Feld: soll dieser Timeline-Eintrag verrechnet werden?
            // Default = true (oder false, je nach Logik)
            $table->boolean('verrechnen')
                  ->default(true)
                  ->after('datum')
                  ->comment('Gibt an, ob der Timeline-Eintrag verrechnet werden soll');

            // Datum, an dem der Eintrag tatsächlich verrechnet wurde
            $table->date('verrechnet_am')
                  ->nullable()
                  ->after('verrechnen')
                  ->comment('Datum, an dem der Timeline-Eintrag verrechnet wurde');

            // Referenz auf Rechnungsnummer, mit der verrechnet wurde
            // z.B. "2025/0001"
            $table->string('verrechnet_mit_rn_nummer', 20)
                  ->nullable()
                  ->after('verrechnet_am')
                  ->comment('Rechnungsnummer, mit der der Timeline-Eintrag verrechnet wurde');

            // Optional: Index auf "verrechnen" für schnellere Filter (z.B. "alle nicht verrechneten")
            $table->index('verrechnen', 'timeline_verrechnen_index');
        });
    }

    /**
     * Änderungen rückgängig machen.
     */
    public function down(): void
    {
        Schema::table('timeline', function (Blueprint $table) {
            // Erst den Index entfernen
            $table->dropIndex('timeline_verrechnen_index');

            // Dann die Spalten wieder löschen
            $table->dropColumn([
                'verrechnen',
                'verrechnet_am',
                'verrechnet_mit_rn_nummer',
            ]);
        });
    }
};
