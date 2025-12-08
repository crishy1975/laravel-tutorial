<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Legacy-ID Felder für Access-Import
 * 
 * Fügt legacy_id und legacy_mid Felder zu allen relevanten Tabellen hinzu,
 * um die Referenzen aus der alten Access-Datenbank zu erhalten.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ═══════════════════════════════════════════════════════════
        // 📋 ADRESSEN
        // ═══════════════════════════════════════════════════════════
        Schema::table('adressen', function (Blueprint $table) {
            $table->unsignedBigInteger('legacy_id')->nullable()->after('id')
                ->comment('Alte Access ID');
            $table->unsignedBigInteger('legacy_mid')->nullable()->after('legacy_id')
                ->comment('Alte Access mId (Referenz-Schlüssel)');
            
            // Index für schnelles Lookup beim Import
            $table->index('legacy_mid', 'idx_adressen_legacy_mid');
        });

        // ═══════════════════════════════════════════════════════════
        // 🏢 GEBÄUDE
        // ═══════════════════════════════════════════════════════════
        Schema::table('gebaeude', function (Blueprint $table) {
            $table->unsignedBigInteger('legacy_id')->nullable()->after('id')
                ->comment('Alte Access ID');
            $table->unsignedBigInteger('legacy_mid')->nullable()->after('legacy_id')
                ->comment('Alte Access mId (Referenz-Schlüssel)');
            
            $table->index('legacy_mid', 'idx_gebaeude_legacy_mid');
        });

        // ═══════════════════════════════════════════════════════════
        // 📦 ARTIKEL (STAMM) - artikel_gebaeude
        // ═══════════════════════════════════════════════════════════
        Schema::table('artikel_gebaeude', function (Blueprint $table) {
            $table->unsignedBigInteger('legacy_id')->nullable()->after('id')
                ->comment('Alte Access ID');
            $table->unsignedBigInteger('legacy_mid')->nullable()->after('legacy_id')
                ->comment('Alte Access mId');
            
            $table->index('legacy_mid', 'idx_artikel_gebaeude_legacy_mid');
        });

        // ═══════════════════════════════════════════════════════════
        // 🧾 RECHNUNGEN
        // ═══════════════════════════════════════════════════════════
        Schema::table('rechnungen', function (Blueprint $table) {
            $table->unsignedBigInteger('legacy_id')->nullable()->after('id')
                ->comment('Alte Access idFatturaPA');
            $table->unsignedBigInteger('legacy_progressivo')->nullable()->after('legacy_id')
                ->comment('Alte Access ProgressivoInvio');
            
            $table->index('legacy_id', 'idx_rechnungen_legacy_id');
        });

        // ═══════════════════════════════════════════════════════════
        // 📝 RECHNUNGSPOSITIONEN
        // ═══════════════════════════════════════════════════════════
        Schema::table('rechnung_positionen', function (Blueprint $table) {
            $table->unsignedBigInteger('legacy_id')->nullable()->after('id')
                ->comment('Alte Access ID');
            $table->unsignedBigInteger('legacy_artikel_id')->nullable()->after('legacy_id')
                ->comment('Alte Access idHerkunftArtikel');
            
            $table->index('legacy_id', 'idx_rechnung_positionen_legacy_id');
        });
    }

    public function down(): void
    {
        Schema::table('adressen', function (Blueprint $table) {
            $table->dropIndex('idx_adressen_legacy_mid');
            $table->dropColumn(['legacy_id', 'legacy_mid']);
        });

        Schema::table('gebaeude', function (Blueprint $table) {
            $table->dropIndex('idx_gebaeude_legacy_mid');
            $table->dropColumn(['legacy_id', 'legacy_mid']);
        });

        Schema::table('artikel_gebaeude', function (Blueprint $table) {
            $table->dropIndex('idx_artikel_gebaeude_legacy_mid');
            $table->dropColumn(['legacy_id', 'legacy_mid']);
        });

        Schema::table('rechnungen', function (Blueprint $table) {
            $table->dropIndex('idx_rechnungen_legacy_id');
            $table->dropColumn(['legacy_id', 'legacy_progressivo']);
        });

        Schema::table('rechnung_positionen', function (Blueprint $table) {
            $table->dropIndex('idx_rechnung_positionen_legacy_id');
            $table->dropColumn(['legacy_id', 'legacy_artikel_id']);
        });
    }
};