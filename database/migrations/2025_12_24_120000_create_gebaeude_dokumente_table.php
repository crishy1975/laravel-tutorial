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
        Schema::create('gebaeude_dokumente', function (Blueprint $table) {
            $table->id();
            
            // Beziehung zum Gebäude
            $table->foreignId('gebaeude_id')
                  ->constrained('gebaeude')
                  ->cascadeOnDelete();
            
            // Dokument-Metadaten
            $table->string('titel', 255);
            $table->text('beschreibung')->nullable();
            $table->string('kategorie', 100)->nullable(); // vertrag, rechnung, foto, protokoll, sonstiges
            
            // Datei-Informationen
            $table->string('dateiname', 255);           // Gespeicherter Name (UUID)
            $table->string('original_name', 255);       // Original-Dateiname
            $table->string('dateityp', 50);             // MIME-Type
            $table->string('dateiendung', 20);          // pdf, xlsx, docx, jpg, etc.
            $table->unsignedBigInteger('dateigroesse'); // in Bytes
            $table->string('pfad', 500);                // Relativer Pfad in Storage
            
            // Optionale Felder
            $table->date('dokument_datum')->nullable(); // Datum des Dokuments
            $table->string('tags', 500)->nullable();    // Komma-getrennte Tags
            $table->boolean('ist_wichtig')->default(false);
            $table->boolean('ist_archiviert')->default(false);
            
            // Benutzer
            $table->foreignId('hochgeladen_von')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            
            $table->timestamps();
            
            // Indizes
            $table->index('kategorie');
            $table->index('dateityp');
            $table->index('dokument_datum');
            $table->index('ist_wichtig');
            $table->index('ist_archiviert');
            $table->index(['gebaeude_id', 'kategorie']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gebaeude_dokumente');
    }
};
