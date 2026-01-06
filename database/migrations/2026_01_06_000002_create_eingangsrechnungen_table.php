<?php
/**
 * ════════════════════════════════════════════════════════════════════════════
 * DATEI: 2026_01_06_000002_create_eingangsrechnungen_table.php
 * PFAD:  database/migrations/2026_01_06_000002_create_eingangsrechnungen_table.php
 * ════════════════════════════════════════════════════════════════════════════
 */

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
        Schema::create('eingangsrechnungen', function (Blueprint $table) {
            $table->id();

            // Beziehung zum Lieferanten
            $table->foreignId('lieferant_id')
                  ->constrained('lieferanten')
                  ->onDelete('cascade');

            // Rechnungsdaten aus XML
            $table->string('dateiname')->comment('Original XML-Dateiname');
            $table->string('rechnungsnummer');
            $table->date('rechnungsdatum');
            $table->date('faelligkeitsdatum')->nullable();

            // Beträge
            $table->decimal('netto_betrag', 12, 2)->default(0);
            $table->decimal('mwst_betrag', 12, 2)->default(0);
            $table->decimal('brutto_betrag', 12, 2)->default(0);

            // Zahlungsinformationen aus XML (ModalitaPagamento)
            // MP01=Bar, MP05=Überweisung, MP08=Karte, MP19=SEPA, etc.
            $table->string('modalita_pagamento', 10)->nullable()->comment('Zahlungsart aus XML (MP01, MP05, MP08...)');
            $table->string('modalita_pagamento_text', 50)->nullable()->comment('Lesbare Bezeichnung');

            // Status & Zahlung
            $table->enum('status', ['offen', 'bezahlt', 'ignoriert'])->default('offen');
            $table->enum('zahlungsmethode', ['bank', 'karte', 'bar'])->nullable()->comment('Tatsächliche Zahlungsmethode');
            $table->date('bezahlt_am')->nullable();

            // Sonstiges
            $table->text('notiz')->nullable();
            $table->json('xml_data')->nullable()->comment('Komplette XML-Daten als JSON');

            $table->timestamps();
            $table->softDeletes();

            // Indices
            $table->index('rechnungsnummer');
            $table->index('rechnungsdatum');
            $table->index('status');
            $table->index(['lieferant_id', 'status']);

            // Duplikat-Prüfung: gleiche Rechnung vom gleichen Lieferanten
            $table->unique(['lieferant_id', 'rechnungsnummer'], 'unique_rechnung_pro_lieferant');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('eingangsrechnungen');
    }
};
