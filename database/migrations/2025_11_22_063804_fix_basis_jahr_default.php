<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. DEFAULT-Wert von basis_jahr entfernen
        Schema::table('artikel_gebaeude', function (Blueprint $table) {
            $table->year('basis_jahr')->nullable(false)->default(null)->change();
        });

        // 2. Bestehende Daten korrigieren
        $naechstesJahr = now()->year + 1;
        
        DB::table('artikel_gebaeude')
            ->where(function($query) use ($naechstesJahr) {
                $query->whereNull('basis_jahr')
                      ->orWhere('basis_jahr', '<', $naechstesJahr);
            })
            ->update(['basis_jahr' => $naechstesJahr]);

        // 3. Sicherstellen dass basis_jahr NOT NULL ist
        Schema::table('artikel_gebaeude', function (Blueprint $table) {
            $table->year('basis_jahr')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // DEFAULT-Wert wiederherstellen
        Schema::table('artikel_gebaeude', function (Blueprint $table) {
            $table->year('basis_jahr')->default(2024)->change();
        });
    }
};