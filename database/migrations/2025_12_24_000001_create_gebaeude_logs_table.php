<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Gebäude-Log Tabelle für Aktivitätsprotokoll
     */
    public function up(): void
    {
        Schema::create('gebaeude_logs', function (Blueprint $table) {
            $table->id();
            
            // Beziehung zum Gebäude
            $table->foreignId('gebaeude_id')
                  ->constrained('gebaeude')
                  ->cascadeOnDelete();
            
            // Log-Typ (Enum)
            $table->string('typ', 50);
            
            // Inhalt
            $table->string('titel', 255);
            $table->text('beschreibung')->nullable();
            
            // Wer hat's gemacht?
            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            
            // Zusätzliche Daten (JSON)
            $table->json('metadata')->nullable();
            
            // Dokument-Anhang
            $table->string('dokument_pfad', 500)->nullable();
            
            // Referenz auf andere Entität (z.B. Rechnung, Angebot)
            $table->unsignedBigInteger('referenz_id')->nullable();
            $table->string('referenz_typ', 50)->nullable();
            
            // Kontakt-Informationen
            $table->string('kontakt_person', 100)->nullable();
            $table->string('kontakt_telefon', 50)->nullable();
            $table->string('kontakt_email', 100)->nullable();
            
            // Priorität für wichtige Einträge
            $table->enum('prioritaet', ['niedrig', 'normal', 'hoch', 'kritisch'])
                  ->default('normal');
            
            // Sichtbarkeit
            $table->boolean('ist_oeffentlich')->default(false);
            
            // Erinnerung/Wiedervorlage
            $table->date('erinnerung_datum')->nullable();
            $table->boolean('erinnerung_erledigt')->default(false);
            
            $table->timestamps();
            
            // Indizes für Performance
            $table->index(['gebaeude_id', 'created_at']);
            $table->index(['gebaeude_id', 'typ']);
            $table->index('typ');
            $table->index('erinnerung_datum');
            $table->index('prioritaet');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gebaeude_logs');
    }
};
