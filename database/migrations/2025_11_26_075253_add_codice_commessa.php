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
       

        // Rechnung: codice_commessa hinzufügen
        Schema::table('rechnung', function (Blueprint $table) {
            $table->string('codice_commessa', 100)->nullable()->after('cig');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       

        Schema::table('rechnung', function (Blueprint $table) {
            $table->dropColumn('codice_commessa');
        });
    }
};