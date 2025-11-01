<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gebaeude', function (Blueprint $table) {
            $table->id();
            $table->string('codex', 15)->nullable();

            // Beziehungen zu Adressen
            $table->foreignId('postadresse_id')->constrained('adressen')->cascadeOnDelete();
            $table->foreignId('rechnungsempfaenger_id')->constrained('adressen')->cascadeOnDelete();

            // Grunddaten
            $table->string('gebaeude_name', 100)->nullable();
            $table->string('strasse', 255)->nullable();
            $table->string('hausnummer', 10)->nullable();
            $table->string('plz', 10)->nullable();
            $table->string('wohnort', 100)->nullable();
            $table->string('land', 50)->nullable();
            $table->text('bemerkung')->nullable();

            // Zustandsflags
            $table->boolean('veraendert')->default(false);
            $table->timestamp('veraendert_wann')->nullable();

            // Termine und Zähler
            $table->date('letzter_termin')->nullable();
            $table->date('datum_faelligkeit')->nullable();
            $table->integer('geplante_reinigungen')->default(0);
            $table->integer('gemachte_reinigungen')->default(0);
            $table->boolean('faellig')->default(false);
            $table->boolean('rechnung_schreiben')->default(false);

            // Monatsfelder
            for ($i = 1; $i <= 12; $i++) {
                $table->boolean(sprintf('m%02d', $i))->default(false);
            }

            // Touren-Verknüpfung (optional)
            $table->unsignedBigInteger('select_tour')->nullable();

            // SoftDeletes + Timestamps
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gebaeude');
    }
};
