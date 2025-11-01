<?php
// database/migrations/2025_10_27_000001_add_updated_and_deleted_to_timeline_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Fügt 'updated_at' (nullable) und 'deleted_at' (SoftDeletes) zur Tabelle 'timeline' hinzu.
     * Hinweis: 'created_at' existiert bereits und wird NICHT erneut angelegt.
     */
    public function up(): void
    {
        Schema::table('timeline', function (Blueprint $table) {
            // ⚠️ Nur hinzufügen, wenn Spalte noch nicht existiert (idempotent)
            if (!Schema::hasColumn('timeline', 'updated_at')) {
                // nullable, damit bestehende Zeilen kein Problem sind
                $table->timestamp('updated_at')->nullable()->after('created_at');
            }

            if (!Schema::hasColumn('timeline', 'deleted_at')) {
                // SoftDeletes-Spalte (TIMESTAMP NULL) hinzufügen
                $table->softDeletes()->after('updated_at');
            }
        });
    }

    /**
     * Rollback: entfernt nur die neu hinzugefügten Spalten (falls vorhanden).
     */
    public function down(): void
    {
        Schema::table('timeline', function (Blueprint $table) {
            if (Schema::hasColumn('timeline', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
            if (Schema::hasColumn('timeline', 'updated_at')) {
                $table->dropColumn('updated_at');
            }
        });
    }
};
