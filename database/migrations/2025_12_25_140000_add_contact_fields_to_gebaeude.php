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
        Schema::table('gebaeude', function (Blueprint $table) {
            // Adressen optional machen (falls noch nicht nullable)
            $table->unsignedBigInteger('postadresse_id')->nullable()->change();
            $table->unsignedBigInteger('rechnungsempfaenger_id')->nullable()->change();
            
            // Neue Kontaktfelder
            $table->string('telefon', 50)->nullable()->after('wohnort');
            $table->string('handy', 50)->nullable()->after('telefon');
            $table->string('email', 255)->nullable()->after('handy');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gebaeude', function (Blueprint $table) {
            $table->dropColumn(['telefon', 'handy', 'email']);
        });
    }
};
