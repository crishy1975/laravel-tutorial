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
        Schema::create('rechnung_logs', function (Blueprint $table) {
            $table->id();
            
            // Beziehung zur Rechnung
            $table->foreignId('rechnung_id')
                  ->constrained('rechnungen')
                  ->cascadeOnDelete();
            
            // Log-Typ (Enum-Wert)
            $table->string('typ', 50)->index();
            
            // Titel (automatisch oder manuell)
            $table->string('titel', 255);
            
            // Beschreibung / Details
            $table->text('beschreibung')->nullable();
            
            // Wer hat die Aktion ausgeführt
            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            
            // Zusätzliche Metadaten (JSON)
            $table->json('metadata')->nullable();
            
            // Für Dokument-Referenzen (z.B. PDF-Pfad, XML-Log-ID)
            $table->string('dokument_pfad')->nullable();
            $table->unsignedBigInteger('referenz_id')->nullable();
            $table->string('referenz_typ', 100)->nullable();
            
            // Kontakt-Info (für Telefonate/Mitteilungen)
            $table->string('kontakt_person')->nullable();
            $table->string('kontakt_telefon')->nullable();
            $table->string('kontakt_email')->nullable();
            
            // Wichtigkeit / Priorität
            $table->enum('prioritaet', ['niedrig', 'normal', 'hoch', 'kritisch'])
                  ->default('normal');
            
            // Ist öffentlich sichtbar (für Kundenportal etc.)
            $table->boolean('ist_oeffentlich')->default(false);
            
            // Erinnerung / Follow-up
            $table->date('erinnerung_datum')->nullable();
            $table->boolean('erinnerung_erledigt')->default(false);
            
            $table->timestamps();
            
            // Indizes für Performance
            $table->index(['rechnung_id', 'typ']);
            $table->index(['rechnung_id', 'created_at']);
            $table->index('erinnerung_datum');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rechnung_logs');
    }
};