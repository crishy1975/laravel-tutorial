<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Entfernt den Foreign Key und erweitert cIM_CODICE für flexiblen Import
     * 
     * Ermöglicht:
     * - Messungen ohne existierende Anlage
     * - Kodexe mit mehr als 6 Zeichen
     * - Nachträgliche Zuordnung zu Anlagen
     */
    public function up(): void
    {
        Schema::table('messungen', function (Blueprint $table) {
            // Foreign Key entfernen
            $table->dropForeign(['cIM_CODICE']);
            
            // Feld erweitern für längere Kodexe (z.B. 1609391 = 7 Zeichen)
            $table->string('cIM_CODICE', 16)->change();
        });
    }

    public function down(): void
    {
        Schema::table('messungen', function (Blueprint $table) {
            // Feld wieder auf 6 Zeichen
            $table->string('cIM_CODICE', 6)->change();
            
            // Foreign Key wiederherstellen
            $table->foreign('cIM_CODICE')
                ->references('Feld_a')
                ->on('impianti')
                ->onDelete('cascade');
        });
    }
};
