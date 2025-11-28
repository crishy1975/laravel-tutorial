<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rechnungen', function (Blueprint $table) {
            // ⭐ Causale für FatturaPA (manuell editierbar, max 200 Zeichen)
            $table->string('fattura_causale', 200)->nullable()->after('leistungsdaten');
        });
    }

    public function down(): void
    {
        Schema::table('rechnungen', function (Blueprint $table) {
            $table->dropColumn('fattura_causale');
        });
    }
};