<?php
// database/migrations/2026_02_17_000001_add_deleted_marker_to_rechnungen.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * ═══════════════════════════════════════════════════════════════════════════════
     * SOFT-DELETE MIT UNIQUE INDEX FÜR RECHNUNGEN
     * ═══════════════════════════════════════════════════════════════════════════════
     * 
     * Problem: Der Unique Index auf (jahr, laufnummer) blockiert auch gelöschte Rechnungen.
     * 
     * Lösung: Generated Column "deleted_marker"
     * - Aktive Rechnung: deleted_marker = 0
     * - Gelöschte Rechnung: deleted_marker = UNIX_TIMESTAMP(deleted_at)
     * 
     * Neuer Unique Index: (jahr, laufnummer, deleted_marker)
     * → Erlaubt mehrere gelöschte Rechnungen mit gleicher Nummer
     * → Verhindert doppelte aktive Rechnungen
     */
    public function up(): void
    {
        // ─────────────────────────────────────────────────────────────────────────────
        // 1. Alten Unique Index entfernen (falls vorhanden)
        // ─────────────────────────────────────────────────────────────────────────────
        $indexExists = collect(DB::select("SHOW INDEX FROM rechnungen WHERE Key_name = 'rechnungen_jahr_laufnummer_unique'"))->isNotEmpty();
        
        if ($indexExists) {
            Schema::table('rechnungen', function (Blueprint $table) {
                $table->dropUnique('rechnungen_jahr_laufnummer_unique');
            });
            
            \Log::info('Migration: Alter Unique Index entfernt');
        }

        // ─────────────────────────────────────────────────────────────────────────────
        // 2. Generated Column hinzufügen (mit UNIX_TIMESTAMP statt id)
        // ─────────────────────────────────────────────────────────────────────────────
        $columnExists = Schema::hasColumn('rechnungen', 'deleted_marker');
        
        if (!$columnExists) {
            // MySQL Generated Column (STORED für Index-Nutzung)
            // UNIX_TIMESTAMP ist eindeutig genug für gelöschte Rechnungen
            DB::statement("
                ALTER TABLE rechnungen 
                ADD COLUMN deleted_marker BIGINT GENERATED ALWAYS AS (
                    CASE WHEN deleted_at IS NULL THEN 0 ELSE UNIX_TIMESTAMP(deleted_at) END
                ) STORED
            ");
            
            \Log::info('Migration: Generated Column deleted_marker hinzugefügt');
        }

        // ─────────────────────────────────────────────────────────────────────────────
        // 3. Neuen Unique Index erstellen
        // ─────────────────────────────────────────────────────────────────────────────
        $newIndexExists = collect(DB::select("SHOW INDEX FROM rechnungen WHERE Key_name = 'rechnungen_unique_aktiv'"))->isNotEmpty();
        
        if (!$newIndexExists) {
            DB::statement("
                CREATE UNIQUE INDEX rechnungen_unique_aktiv 
                ON rechnungen(jahr, laufnummer, deleted_marker)
            ");
            
            \Log::info('Migration: Neuer Unique Index rechnungen_unique_aktiv erstellt');
        }

        // ─────────────────────────────────────────────────────────────────────────────
        // 4. Index auf deleted_at für Performance (falls nicht vorhanden)
        // ─────────────────────────────────────────────────────────────────────────────
        $deletedAtIndexExists = collect(DB::select("SHOW INDEX FROM rechnungen WHERE Key_name = 'rechnungen_deleted_at_index'"))->isNotEmpty();
        
        if (!$deletedAtIndexExists) {
            Schema::table('rechnungen', function (Blueprint $table) {
                $table->index('deleted_at', 'rechnungen_deleted_at_index');
            });
            
            \Log::info('Migration: Index auf deleted_at hinzugefügt');
        }
    }

    public function down(): void
    {
        // ─────────────────────────────────────────────────────────────────────────────
        // 1. Neuen Unique Index entfernen
        // ─────────────────────────────────────────────────────────────────────────────
        $newIndexExists = collect(DB::select("SHOW INDEX FROM rechnungen WHERE Key_name = 'rechnungen_unique_aktiv'"))->isNotEmpty();
        
        if ($newIndexExists) {
            DB::statement("DROP INDEX rechnungen_unique_aktiv ON rechnungen");
        }

        // ─────────────────────────────────────────────────────────────────────────────
        // 2. Generated Column entfernen
        // ─────────────────────────────────────────────────────────────────────────────
        $columnExists = Schema::hasColumn('rechnungen', 'deleted_marker');
        
        if ($columnExists) {
            Schema::table('rechnungen', function (Blueprint $table) {
                $table->dropColumn('deleted_marker');
            });
        }

        // ─────────────────────────────────────────────────────────────────────────────
        // 3. Alten Unique Index wiederherstellen
        // ─────────────────────────────────────────────────────────────────────────────
        Schema::table('rechnungen', function (Blueprint $table) {
            $table->unique(['jahr', 'laufnummer'], 'rechnungen_jahr_laufnummer_unique');
        });

        // ─────────────────────────────────────────────────────────────────────────────
        // 4. deleted_at Index entfernen
        // ─────────────────────────────────────────────────────────────────────────────
        $deletedAtIndexExists = collect(DB::select("SHOW INDEX FROM rechnungen WHERE Key_name = 'rechnungen_deleted_at_index'"))->isNotEmpty();
        
        if ($deletedAtIndexExists) {
            Schema::table('rechnungen', function (Blueprint $table) {
                $table->dropIndex('rechnungen_deleted_at_index');
            });
        }
    }
};
