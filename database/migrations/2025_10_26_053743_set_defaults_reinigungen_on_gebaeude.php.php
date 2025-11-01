<?php
// database/migrations/2025_10_26_000001_set_defaults_reinigungen_on_gebaeude.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Vorhandene NULLs auf 1 setzen (idempotent)
        DB::table('gebaeude')
            ->whereNull('geplante_reinigungen')
            ->update(['geplante_reinigungen' => 1]);

        DB::table('gebaeude')
            ->whereNull('gemachte_reinigungen')
            ->update(['gemachte_reinigungen' => 1]);

        Schema::table('gebaeude', function (Blueprint $table) {
            // Default 1 setzen (achtet: Spalten müssen integer sein)
            $table->integer('geplante_reinigungen')->default(1)->change();
            $table->integer('gemachte_reinigungen')->default(1)->change();
        });
    }

    public function down(): void
    {
        Schema::table('gebaeude', function (Blueprint $table) {
            // Defaults wieder entfernen (zurück zu "kein Default")
            $table->integer('geplante_reinigungen')->nullable()->default(null)->change();
            $table->integer('gemachte_reinigungen')->nullable()->default(null)->change();
        });
    }
};
