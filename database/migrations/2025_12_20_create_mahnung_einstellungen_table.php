<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mahnung_einstellungen', function (Blueprint $table) {
            $table->id();
            $table->string('schluessel', 100)->unique();
            $table->string('wert', 255);
            $table->string('beschreibung', 500)->nullable();
            $table->string('typ', 20)->default('integer'); // integer, string, boolean
            $table->timestamps();
        });

        // Standard-Einstellungen einfügen
        DB::table('mahnung_einstellungen')->insert([
            [
                'schluessel'   => 'zahlungsfrist_tage',
                'wert'         => '30',
                'beschreibung' => 'Tage nach Rechnungsdatum bis zur Fälligkeit',
                'typ'          => 'integer',
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'schluessel'   => 'wartezeit_zwischen_mahnungen',
                'wert'         => '10',
                'beschreibung' => 'Mindestabstand in Tagen zwischen zwei Mahnungen',
                'typ'          => 'integer',
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'schluessel'   => 'min_tage_ueberfaellig',
                'wert'         => '0',
                'beschreibung' => 'Mindestanzahl Tage überfällig bevor gemahnt wird',
                'typ'          => 'integer',
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mahnung_einstellungen');
    }
};
