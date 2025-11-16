<?php
// database/migrations/2025_11_16_150000_add_basis_preis_to_artikel_gebaeude.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('artikel_gebaeude', function (Blueprint $table) {
            // ═══════════════════════════════════════════════════════════
            // 📅 BASIS-JAHR (ab wann gilt dieser Preis?)
            // ═══════════════════════════════════════════════════════════
            $table->year('basis_jahr')
                ->default(2024)
                ->after('einzelpreis')
                ->comment('Ab welchem Jahr gilt der Basis-Preis?');

            // ═══════════════════════════════════════════════════════════
            // 💰 BASIS-PREIS (Original-Preis ohne Aufschläge)
            // ═══════════════════════════════════════════════════════════
            $table->decimal('basis_preis', 12, 2)
                ->nullable()
                ->after('basis_jahr')
                ->comment('Preis im Basis-Jahr (für Aufschlag-Berechnung)');
        });

        // ═══════════════════════════════════════════════════════════
        // 🔄 BESTEHENDE DATEN MIGRIEREN
        // ═══════════════════════════════════════════════════════════
        // Setze basis_preis = einzelpreis für bestehende Artikel
        DB::statement('UPDATE artikel_gebaeude SET basis_preis = einzelpreis WHERE basis_preis IS NULL');

        // Jetzt basis_preis auf NOT NULL setzen
        Schema::table('artikel_gebaeude', function (Blueprint $table) {
            $table->decimal('basis_preis', 12, 2)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('artikel_gebaeude', function (Blueprint $table) {
            $table->dropColumn(['basis_jahr', 'basis_preis']);
        });
    }
};