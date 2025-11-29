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
        Schema::table('fattura_profile', function (Blueprint $table) {
            // Reverse Charge Flag hinzufügen (nach split_payment)
            if (!Schema::hasColumn('fattura_profile', 'reverse_charge')) {
                $table->boolean('reverse_charge')->default(false)->after('split_payment');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fattura_profile', function (Blueprint $table) {
            if (Schema::hasColumn('fattura_profile', 'reverse_charge')) {
                $table->dropColumn('reverse_charge');
            }
        });
    }
};