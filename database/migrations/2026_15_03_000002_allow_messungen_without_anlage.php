<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

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
        // Schritt 1: Foreign Key mit explizitem Namen entfernen
        Schema::table('messungen', function (Blueprint $table) {
            $table->dropForeign('messungen_cim_codice_foreign');
        });
        
        // Schritt 2: Spalte erweitern (separater Schema-Aufruf)
        Schema::table('messungen', function (Blueprint $table) {
            $table->string('cIM_CODICE', 16)->change();
        });
    }

    public function down(): void
    {
        Schema::table('messungen', function (Blueprint $table) {
            $table->string('cIM_CODICE', 6)->change();
        });
        
        Schema::table('messungen', function (Blueprint $table) {
            $table->foreign('cIM_CODICE', 'messungen_cim_codice_foreign')
                ->references('Feld_a')
                ->on('impianti')
                ->onDelete('cascade');
        });
    }
};
