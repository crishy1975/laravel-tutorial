<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bank_buchungen', function (Blueprint $table) {
            // match_info von VARCHAR auf TEXT ändern für größere JSON-Daten
            $table->text('match_info')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bank_buchungen', function (Blueprint $table) {
            $table->string('match_info', 500)->nullable()->change();
        });
    }
};
