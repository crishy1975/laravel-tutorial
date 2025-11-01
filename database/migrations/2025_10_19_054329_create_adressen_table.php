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
        Schema::create('adressen', function (Blueprint $table) {
            $table->id();

            // Basisdaten
            $table->string('name', 200);
            $table->string('strasse', 255)->nullable();
            $table->string('hausnummer', 10)->nullable();
            $table->string('plz', 10)->nullable();
            $table->string('wohnort', 100)->nullable();
            $table->string('provinz', 4)->nullable();
            $table->string('land', 50)->nullable();

            // Kontaktinformationen
            $table->string('telefon', 50)->nullable();
            $table->string('handy', 50)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('email_zweit', 255)->nullable();
            $table->string('pec', 255)->nullable();

            // Steuer- / Unternehmensdaten
            $table->string('steuernummer', 50)->nullable();
            $table->string('mwst_nummer', 50)->nullable();
            $table->string('codice_univoco', 20)->nullable();

            // Sonstiges
            $table->text('bemerkung')->nullable();

            // interne Flags
            $table->boolean('veraendert')->default(false);
            $table->timestamp('veraendert_wann')->nullable();

            // SoftDeletes + Standardtimestamps
            $table->softDeletes(); // erstellt automatisch 'deleted_at'
            $table->timestamps();  // erstellt 'created_at' & 'updated_at'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('adressen');
    }
};
