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
        Schema::create('fattura_xml_logs', function (Blueprint $table) {
            $table->id();
            
            // ═══════════════════════════════════════════════════════════
            // 🔗 BEZIEHUNGEN
            // ═══════════════════════════════════════════════════════════
            
            // Rechnung (Pflichtfeld)
            $table->foreignId('rechnung_id')
                ->constrained('rechnungen')
                ->onDelete('cascade');
            
            // ═══════════════════════════════════════════════════════════
            // 📨 ÜBERTRAGUNGSDATEN
            // ═══════════════════════════════════════════════════════════
            
            // Progressivo Invio (eindeutige Sendungsnummer)
            // Format: IT202500001
            $table->string('progressivo_invio', 50)->unique();
            
            // Formato Trasmissione (FPR12 / FPA12)
            $table->string('formato_trasmissione', 10)->default('FPR12');
            
            // Codice Destinatario (7 Zeichen)
            $table->string('codice_destinatario', 7)->nullable();
            
            // PEC Destinatario (falls verwendet)
            $table->string('pec_destinatario')->nullable();
            
            // ═══════════════════════════════════════════════════════════
            // 📁 DATEIEN
            // ═══════════════════════════════════════════════════════════
            
            // XML-Datei (original)
            $table->string('xml_file_path')->nullable();
            $table->string('xml_filename', 255)->nullable();
            $table->unsignedInteger('xml_file_size')->nullable(); // Bytes
            
            // P7M-Datei (signiert, optional)
            $table->string('p7m_file_path')->nullable();
            $table->string('p7m_filename', 255)->nullable();
            
            // XML-Inhalt (optional, für schnellen Zugriff)
            // Nur bei Bedarf befüllen (kann groß werden!)
            $table->longText('xml_content')->nullable();
            
            // ═══════════════════════════════════════════════════════════
            // 📊 STATUS & TRACKING
            // ═══════════════════════════════════════════════════════════
            
            // Status:
            // - pending: Wartend auf Generierung
            // - generated: XML erfolgreich generiert
            // - signed: Digital signiert (P7M)
            // - sent: An SDI gesendet
            // - delivered: Von SDI empfangen
            // - accepted: Von Empfänger akzeptiert
            // - rejected: Von Empfänger abgelehnt
            // - error: Fehler bei Generierung/Versand
            $table->string('status', 50)->default('pending'); // ✅ KEIN ->index() hier!
            
            // Untertyp (für detaillierteres Tracking)
            $table->string('status_detail', 100)->nullable();
            
            // SDI-Status-Code (z.B. RC, MC, NS, etc.)
            $table->string('sdi_status_code', 10)->nullable();
            
            // ═══════════════════════════════════════════════════════════
            // ⏱️ ZEITSTEMPEL
            // ═══════════════════════════════════════════════════════════
            
            // Generiert am
            $table->timestamp('generated_at')->nullable();
            
            // Signiert am
            $table->timestamp('signed_at')->nullable();
            
            // Gesendet am
            $table->timestamp('sent_at')->nullable();
            
            // Von SDI empfangen am
            $table->timestamp('delivered_at')->nullable();
            
            // Vom Empfänger akzeptiert/abgelehnt am
            $table->timestamp('finalized_at')->nullable();
            
            // ═══════════════════════════════════════════════════════════
            // ⚠️ FEHLER & VALIDIERUNG
            // ═══════════════════════════════════════════════════════════
            
            // Validierung erfolgreich
            $table->boolean('is_valid')->default(false);
            
            // Validierungs-Fehler (JSON)
            $table->json('validation_errors')->nullable();
            
            // Allgemeine Fehler-Nachricht
            $table->text('error_message')->nullable();
            
            // Fehler-Details (Stack Trace, etc.)
            $table->text('error_details')->nullable();
            
            // Anzahl Versuche (bei wiederholten Fehlern)
            $table->unsignedTinyInteger('retry_count')->default(0);
            
            // ═══════════════════════════════════════════════════════════
            // 📧 SDI KOMMUNIKATION
            // ═══════════════════════════════════════════════════════════
            
            // SDI Ricevuta (Empfangsbestätigung)
            $table->text('sdi_ricevuta')->nullable();
            
            // SDI Notifiche (Benachrichtigungen - JSON Array)
            $table->json('sdi_notifiche')->nullable();
            
            // Letzte SDI-Nachricht
            $table->text('sdi_last_message')->nullable();
            
            // SDI-Nachricht erhalten am
            $table->timestamp('sdi_last_check_at')->nullable();
            
            // ═══════════════════════════════════════════════════════════
            // 📝 NOTIZEN
            // ═══════════════════════════════════════════════════════════
            
            // Interne Notizen (für Buchhaltung)
            $table->text('notizen')->nullable();
            
            // ═══════════════════════════════════════════════════════════
            // 🕐 TIMESTAMPS
            // ═══════════════════════════════════════════════════════════
            
            $table->timestamps();
            
            // ═══════════════════════════════════════════════════════════
            // 🔍 INDIZES für Performance (ALLE AM ENDE!)
            // ═══════════════════════════════════════════════════════════
            
            $table->index('status');              // ✅ Hier explizit hinzufügen!
            $table->index('generated_at');
            $table->index('sent_at');
            $table->index(['rechnung_id', 'status']); // Compound Index
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fattura_xml_logs');
    }
};