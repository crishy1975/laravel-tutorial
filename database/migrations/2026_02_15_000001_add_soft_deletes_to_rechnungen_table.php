<?php
// database/migrations/2026_02_15_000001_add_soft_deletes_to_rechnungen_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * SoftDeletes für Rechnungen.
     * 
     * Die Eindeutigkeit der Rechnungsnummer wird in der Anwendungslogik
     * im Rechnung Model geprüft (booted() Methode).
     */
    public function up(): void
    {
        // 1. SoftDeletes hinzufügen
        Schema::table('rechnungen', function (Blueprint $table) {
            $table->softDeletes();
        });

        // 2. Index für Performance bei withTrashed-Queries
        Schema::table('rechnungen', function (Blueprint $table) {
            $table->index('deleted_at');
        });

        // 3. Composite Index für Rechnungsnummer-Suche
        //    Hilft bei: WHERE jahr = X AND laufnummer = Y AND deleted_at IS NULL
        Schema::table('rechnungen', function (Blueprint $table) {
            $table->index(['jahr', 'laufnummer', 'deleted_at'], 'idx_rechnungen_nummer_soft');
        });
    }

    public function down(): void
    {
        Schema::table('rechnungen', function (Blueprint $table) {
            $table->dropIndex('idx_rechnungen_nummer_soft');
            $table->dropIndex(['deleted_at']);
            $table->dropSoftDeletes();
        });
    }
};
