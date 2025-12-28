<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('arbeitsberichte', function (Blueprint $table) {
            $table->id();
            
            // Gebäude-Referenz
            $table->foreignId('gebaeude_id')->constrained('gebaeude')->cascadeOnDelete();
            
            // Snapshot Adresse (vom Rechnungsempfänger)
            $table->string('adresse_name')->nullable();
            $table->string('adresse_strasse')->nullable();
            $table->string('adresse_hausnummer')->nullable();
            $table->string('adresse_plz')->nullable();
            $table->string('adresse_wohnort')->nullable();
            
            // Arbeitsdaten
            $table->date('arbeitsdatum');                    // Aktuelles Datum aus Timeline
            $table->date('naechste_faelligkeit')->nullable(); // Fälligkeit nächste Reinigung
            $table->text('bemerkung')->nullable();
            
            // Positionen als JSON (vereinfacht)
            $table->json('positionen')->nullable();
            
            // Digitale Unterschrift Kunde
            $table->text('unterschrift_kunde')->nullable();   // Base64 Signatur
            $table->dateTime('unterschrieben_am')->nullable();
            $table->string('unterschrift_name')->nullable();  // Name des Unterzeichners
            $table->string('unterschrift_ip')->nullable();    // IP für Nachweis
            
            // Digitale Unterschrift Mitarbeiter
            $table->text('unterschrift_mitarbeiter')->nullable();  // Base64 Signatur
            $table->string('mitarbeiter_name')->nullable();        // Name des Mitarbeiters
            
            // Link-System
            $table->string('token', 64)->unique();           // Eindeutiger Link-Token
            $table->dateTime('gueltig_bis');                 // 10 Tage Gültigkeit
            $table->dateTime('abgerufen_am')->nullable();    // Wann wurde Link geöffnet
            
            // Status
            $table->enum('status', ['erstellt', 'gesendet', 'unterschrieben', 'abgelaufen'])
                  ->default('erstellt');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Index für schnelle Token-Suche
            $table->index(['token', 'gueltig_bis']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arbeitsberichte');
    }
};
