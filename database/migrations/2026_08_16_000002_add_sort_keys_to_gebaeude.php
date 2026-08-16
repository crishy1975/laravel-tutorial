<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gebaeude', function (Blueprint $table) {
            $table->string('strasse_sort_key')->nullable()->after('strasse');
            $table->string('hausnummer_sort_key')->nullable()->after('hausnummer');

            $table->index(['strasse_sort_key', 'hausnummer_sort_key'], 'idx_gebaeude_adress_sort');
        });
    }

    public function down(): void
    {
        Schema::table('gebaeude', function (Blueprint $table) {
            $table->dropIndex('idx_gebaeude_adress_sort');
            $table->dropColumn(['strasse_sort_key', 'hausnummer_sort_key']);
        });
    }
};
