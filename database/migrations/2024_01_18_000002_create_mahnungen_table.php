<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Mahnungen - Eine pro Rechnung und Mahnstufe
        Schema::create('mahnungen', function (Blueprint $table) {
            $table->id();
            
            // Beziehungen
            $table->foreignId('rechnung_id')->constrained('rechnungen')->cascadeOnDelete();
            $table->foreignId('mahnung_stufe_id')->constrained('mahnung_stufen');
            
            // Mahndaten
            $table->integer('mahnstufe');                         // 0, 1, 2, 3
            $table->date('mahndatum');                            // Wann erstellt
            $table->integer('tage_ueberfaellig');                 // Tage überfällig bei Erstellung
            
            // Beträge
            $table->decimal('rechnungsbetrag', 10, 2);            // Original-Betrag
            $table->decimal('spesen', 8, 2)->default(0);          // Mahnspesen dieser Stufe
            $table->decimal('gesamtbetrag', 10, 2);               // Rechnungsbetrag + Spesen
            
            // Versand
            $table->enum('versandart', ['email', 'post', 'keine'])->default('keine');
            $table->timestamp('email_gesendet_am')->nullable();
            $table->string('email_adresse')->nullable();          // An welche Adresse
            $table->boolean('email_fehler')->default(false);      // Zustellung fehlgeschlagen?
            $table->text('email_fehler_text')->nullable();
            
            // PDF
            $table->string('pdf_pfad')->nullable();
            
            // Status
            $table->enum('status', ['entwurf', 'gesendet', 'storniert'])->default('entwurf');
            $table->text('bemerkung')->nullable();
            
            $table->timestamps();
            
            // Eine Mahnstufe pro Rechnung
            $table->unique(['rechnung_id', 'mahnstufe']);
        });

        // Ausschlüsse - Kunden die keine Mahnungen bekommen
        Schema::create('mahnung_ausschluesse', function (Blueprint $table) {
            $table->id();
            $table->foreignId('adresse_id')->constrained('adressen')->cascadeOnDelete();
            $table->string('grund', 255)->nullable();             // Warum ausgeschlossen
            $table->date('bis_datum')->nullable();                // Temporär oder NULL = unbegrenzt
            $table->boolean('aktiv')->default(true);
            $table->timestamps();
            
            $table->unique('adresse_id');
        });

        // Einzelne Rechnungen vom Mahnlauf ausschließen
        Schema::create('mahnung_rechnung_ausschluesse', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rechnung_id')->constrained('rechnungen')->cascadeOnDelete();
            $table->string('grund', 255)->nullable();             // z.B. "Reklamation", "Ratenzahlung"
            $table->date('bis_datum')->nullable();
            $table->boolean('aktiv')->default(true);
            $table->timestamps();
            
            $table->unique('rechnung_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mahnung_rechnung_ausschluesse');
        Schema::dropIfExists('mahnung_ausschluesse');
        Schema::dropIfExists('mahnungen');
    }
};
