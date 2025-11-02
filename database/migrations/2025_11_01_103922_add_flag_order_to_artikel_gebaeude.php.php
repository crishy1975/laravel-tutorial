<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('artikel_gebaeude', function (Blueprint $table) {
            // Aktiv-Flag (Standard: aktiv)
            $table->boolean('aktiv')->default(true)->after('einzelpreis');

            // Reihenfolge für manuelle Sortierung (optional)
            $table->unsignedInteger('reihenfolge')->nullable()->after('aktiv');

            // Optional: Indexe
            $table->index(['gebaeude_id', 'aktiv']);
            $table->index(['gebaeude_id', 'reihenfolge']);
        });
    }

    public function down(): void
    {
        Schema::table('artikel_gebaeude', function (Blueprint $table) {
            $table->dropIndex(['artikel_gebaeude_gebaeude_id_aktiv_index']);
            $table->dropIndex(['artikel_gebaeude_gebaeude_id_reihenfolge_index']);
            $table->dropColumn(['aktiv', 'reihenfolge']);
        });
    }
};
