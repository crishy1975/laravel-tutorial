<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Gemeinden mit Empfänger und Textvorlage
        Schema::create('gemeinden', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);                  // z.B. "Bozen"
            $table->string('aliase', 255)->nullable();    // z.B. "Bolzano, BZ"
            $table->string('pec', 255)->nullable();       // bevorzugter Empfänger
            $table->string('email', 255)->nullable();     // falls keine PEC
            $table->string('betreff', 255)->nullable();   // leer = Standard
            $table->text('text')->nullable();             // leer = Standard
            $table->boolean('aktiv')->default(true);
            $table->timestamps();
        });

        // Protokoll aller Meldungen (Nachweis)
        Schema::create('gemeinde_meldungen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gemeinde_id')->nullable()->constrained('gemeinden')->nullOnDelete();
            $table->string('empfaenger', 255)->nullable();
            $table->string('betreff', 255);
            $table->text('text');
            $table->json('gebaeude_ids');
            $table->string('versandart', 20);             // mail | manuell
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamp('gesendet_am');
            $table->timestamps();
        });

        // Status am Gebäude
        Schema::table('gebaeude', function (Blueprint $table) {
            $table->timestamp('nicht_erreicht_am')->nullable()->after('angekuendigt_am');
            $table->timestamp('gemeldet_am')->nullable()->after('nicht_erreicht_am');
        });
    }

    public function down(): void
    {
        Schema::table('gebaeude', function (Blueprint $table) {
            $table->dropColumn(['nicht_erreicht_am', 'gemeldet_am']);
        });
        Schema::dropIfExists('gemeinde_meldungen');
        Schema::dropIfExists('gemeinden');
    }
};
