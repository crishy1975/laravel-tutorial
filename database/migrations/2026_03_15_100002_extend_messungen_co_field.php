<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Erweitert cMIS_MONOSSSIDO für extreme CO-Werte (z.B. defekte Anlagen)
     */
    public function up(): void
    {
        Schema::table('messungen', function (Blueprint $table) {
            $table->string('cMIS_MONOSSSIDO', 6)->change();
        });
    }

    public function down(): void
    {
        Schema::table('messungen', function (Blueprint $table) {
            $table->string('cMIS_MONOSSSIDO', 4)->change();
        });
    }
};
