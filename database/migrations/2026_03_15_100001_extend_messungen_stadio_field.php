<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Erweitert cMIS_STADIO für Anlagen mit mehr als 9 Feuerungsanlagen
     */
    public function up(): void
    {
        Schema::table('messungen', function (Blueprint $table) {
            $table->string('cMIS_STADIO', 2)->change();
        });
    }

    public function down(): void
    {
        Schema::table('messungen', function (Blueprint $table) {
            $table->string('cMIS_STADIO', 1)->change();
        });
    }
};
