<?php
// database/migrations/2026_02_15_000001_add_soft_deletes_to_rechnungen_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * SoftDeletes für Rechnungen.
     * Prüft ob Spalte/Indizes bereits existieren.
     */
    public function up(): void
    {
        // 1. SoftDeletes nur hinzufügen wenn noch nicht vorhanden
        if (!Schema::hasColumn('rechnungen', 'deleted_at')) {
            Schema::table('rechnungen', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // 2. Index für Performance bei withTrashed-Queries
        Schema::table('rechnungen', function (Blueprint $table) {
            // Prüfen ob Index bereits existiert
            $indexExists = collect(\DB::select("SHOW INDEX FROM rechnungen WHERE Key_name = 'rechnungen_deleted_at_index'"))->isNotEmpty();
            
            if (!$indexExists) {
                $table->index('deleted_at');
            }
        });

        // 3. Composite Index für Rechnungsnummer-Suche
        Schema::table('rechnungen', function (Blueprint $table) {
            $indexExists = collect(\DB::select("SHOW INDEX FROM rechnungen WHERE Key_name = 'idx_rechnungen_nummer_soft'"))->isNotEmpty();
            
            if (!$indexExists) {
                $table->index(['jahr', 'laufnummer', 'deleted_at'], 'idx_rechnungen_nummer_soft');
            }
        });
    }

    public function down(): void
    {
        Schema::table('rechnungen', function (Blueprint $table) {
            // Index nur droppen wenn er existiert
            $softIndexExists = collect(\DB::select("SHOW INDEX FROM rechnungen WHERE Key_name = 'idx_rechnungen_nummer_soft'"))->isNotEmpty();
            if ($softIndexExists) {
                $table->dropIndex('idx_rechnungen_nummer_soft');
            }
            
            $deletedAtIndexExists = collect(\DB::select("SHOW INDEX FROM rechnungen WHERE Key_name = 'rechnungen_deleted_at_index'"))->isNotEmpty();
            if ($deletedAtIndexExists) {
                $table->dropIndex(['deleted_at']);
            }
            
            // SoftDeletes Spalte NICHT entfernen da sie schon vorher existierte
            // $table->dropSoftDeletes();
        });
    }
};
