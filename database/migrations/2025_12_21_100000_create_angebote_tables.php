<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ═══════════════════════════════════════════════════════════════════════
        // ANGEBOTE
        // ═══════════════════════════════════════════════════════════════════════
        Schema::create('angebote', function (Blueprint $table) {
            $table->id();
            
            // Angebotsnummer (eigene Serie: A2025/0001)
            $table->integer('jahr');
            $table->integer('laufnummer');
            
            // Beziehungen
            $table->foreignId('gebaeude_id')->nullable()->constrained('gebaeude')->nullOnDelete();
            $table->foreignId('adresse_id')->nullable()->constrained('adressen')->nullOnDelete();
            $table->foreignId('fattura_profile_id')->nullable()->constrained('fattura_profile')->nullOnDelete();
            
            // Empfänger-Snapshot (wie bei Rechnung)
            $table->string('empfaenger_name')->nullable();
            $table->string('empfaenger_strasse')->nullable();
            $table->string('empfaenger_hausnummer')->nullable();
            $table->string('empfaenger_plz')->nullable();
            $table->string('empfaenger_ort')->nullable();
            $table->string('empfaenger_land')->nullable();
            $table->string('empfaenger_email')->nullable();
            $table->string('empfaenger_steuernummer')->nullable();
            $table->string('empfaenger_codice_fiscale')->nullable();
            $table->string('empfaenger_pec')->nullable();
            $table->string('empfaenger_codice_destinatario', 7)->nullable();
            
            // Gebäude-Snapshot
            $table->string('geb_codex')->nullable();
            $table->string('geb_name')->nullable();
            $table->string('geb_strasse')->nullable();
            $table->string('geb_plz')->nullable();
            $table->string('geb_ort')->nullable();
            
            // Angebotsdaten
            $table->string('titel', 255)->nullable();
            $table->date('datum');
            $table->date('gueltig_bis')->nullable();
            
            // Beträge
            $table->decimal('netto_summe', 12, 2)->default(0);
            $table->decimal('mwst_satz', 5, 2)->default(22.00);
            $table->decimal('mwst_betrag', 12, 2)->default(0);
            $table->decimal('brutto_summe', 12, 2)->default(0);
            
            // Texte
            $table->text('einleitung')->nullable();          // Text vor Positionen
            $table->text('bemerkung_kunde')->nullable();     // Text nach Positionen (auf PDF)
            $table->text('bemerkung_intern')->nullable();    // Interne Notiz
            
            // Status
            $table->enum('status', [
                'entwurf',      // Noch in Bearbeitung
                'versendet',    // An Kunde gesendet
                'angenommen',   // Kunde hat angenommen
                'abgelehnt',    // Kunde hat abgelehnt
                'abgelaufen',   // Gültigkeitsdatum überschritten
                'rechnung',     // In Rechnung umgewandelt
            ])->default('entwurf');
            
            // Versand
            $table->timestamp('versendet_am')->nullable();
            $table->string('versendet_an_email')->nullable();
            
            // Konvertierung zu Rechnung
            $table->foreignId('rechnung_id')->nullable()->constrained('rechnungen')->nullOnDelete();
            $table->timestamp('umgewandelt_am')->nullable();
            
            // PDF
            $table->string('pdf_pfad')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Eindeutige Angebotsnummer pro Jahr
            $table->unique(['jahr', 'laufnummer']);
            
            // Indizes
            $table->index('status');
            $table->index('datum');
            $table->index('gebaeude_id');
        });

        // ═══════════════════════════════════════════════════════════════════════
        // ANGEBOT-POSITIONEN
        // ═══════════════════════════════════════════════════════════════════════
        Schema::create('angebot_positionen', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('angebot_id')->constrained('angebote')->cascadeOnDelete();
            $table->foreignId('artikel_gebaeude_id')->nullable()->constrained('artikel_gebaeude')->nullOnDelete();
            
            $table->integer('position')->default(0);         // Reihenfolge
            $table->string('beschreibung', 500);
            $table->decimal('anzahl', 10, 2)->default(1);
            $table->string('einheit', 50)->default('Stück'); // Stück, Stunden, m², etc.
            $table->decimal('einzelpreis', 12, 2);
            $table->decimal('gesamtpreis', 12, 2);
            
            $table->timestamps();
            
            $table->index('angebot_id');
        });

        // ═══════════════════════════════════════════════════════════════════════
        // ANGEBOT-LOGS (wie RechnungLog)
        // ═══════════════════════════════════════════════════════════════════════
        Schema::create('angebot_logs', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('angebot_id')->constrained('angebote')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            
            $table->string('typ', 50);              // erstellt, bearbeitet, versendet, etc.
            $table->string('titel', 255);
            $table->text('nachricht')->nullable();
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            
            $table->index(['angebot_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('angebot_logs');
        Schema::dropIfExists('angebot_positionen');
        Schema::dropIfExists('angebote');
    }
};
