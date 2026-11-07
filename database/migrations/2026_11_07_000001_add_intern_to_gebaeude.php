<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gebaeude', function (Blueprint $table) {
            // Interne Nummer (Wohnung / Interno), z.B. "3", "12", "3a"
            $table->string('intern', 20)->nullable()->after('hausnummer');
            // Sortierschlüssel (wird im Model automatisch berechnet)
            $table->string('intern_sort_key', 30)->nullable()->after('intern');
            $table->index(['strasse_sort_key', 'hausnummer_sort_key', 'intern_sort_key'], 'gebaeude_adresse_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::table('gebaeude', function (Blueprint $table) {
            $table->dropIndex('gebaeude_adresse_sort_idx');
            $table->dropColumn(['intern', 'intern_sort_key']);
        });
    }
};
