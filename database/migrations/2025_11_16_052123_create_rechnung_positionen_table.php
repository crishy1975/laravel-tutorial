<?php
// database/migrations/2025_11_16_120001_create_rechnung_positionen_table.php

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
        Schema::create('rechnung_positionen', function (Blueprint $table) {
            $table->id();

            // ═══════════════════════════════════════════════════════════
            // 🔗 BEZIEHUNG ZUR RECHNUNG
            // ═══════════════════════════════════════════════════════════
            $table->foreignId('rechnung_id')
                ->constrained('rechnungen')
                ->cascadeOnDelete();                        // Rechnung gelöscht → Positionen auch

            // ═══════════════════════════════════════════════════════════
            // 🔗 REFERENZ ZUM ORIGINAL-ARTIKEL (optional, für Nachvollziehbarkeit)
            // ═══════════════════════════════════════════════════════════
            $table->unsignedBigInteger('artikel_gebaeude_id')->nullable();
            $table->foreign('artikel_gebaeude_id')
                ->references('id')->on('artikel_gebaeude')
                ->nullOnDelete();                           // Artikel gelöscht → Position bleibt

            // ═══════════════════════════════════════════════════════════
            // 🔢 POSITIONSNUMMER (für Sortierung auf Rechnung)
            // ═══════════════════════════════════════════════════════════
            $table->unsignedInteger('position')->default(0); // 1, 2, 3, ...

            // ═══════════════════════════════════════════════════════════
            // 📸 SNAPSHOT: ARTIKEL-DATEN (unveränderlich)
            // ═══════════════════════════════════════════════════════════
            $table->string('beschreibung', 500);            // Was wurde gemacht?
            $table->decimal('anzahl', 10, 2)->default(1);   // Menge
            $table->string('einheit', 20)->default('Stk');  // "Stk", "Std", "m²", "Psch"
            $table->decimal('einzelpreis', 12, 2);          // Preis pro Einheit (netto)

            // ═══════════════════════════════════════════════════════════
            // 💸 MWST (kann pro Position variieren!)
            // ═══════════════════════════════════════════════════════════
            $table->decimal('mwst_satz', 5, 2)->default(22.00);         // z.B. 22.00%
            
            // ═══════════════════════════════════════════════════════════
            // 💰 BERECHNETE BETRÄGE (werden automatisch befüllt)
            // ═══════════════════════════════════════════════════════════
            $table->decimal('netto_gesamt', 12, 2);                     // anzahl × einzelpreis
            $table->decimal('mwst_betrag', 12, 2)->default(0);          // netto × (mwst_satz / 100)
            $table->decimal('brutto_gesamt', 12, 2)->default(0);        // netto + mwst

            // ═══════════════════════════════════════════════════════════
            // 🕒 TIMESTAMPS
            // ═══════════════════════════════════════════════════════════
            $table->timestamps();

            // ═══════════════════════════════════════════════════════════
            // 🔍 INDIZES für Performance
            // ═══════════════════════════════════════════════════════════
            $table->index(['rechnung_id', 'position']);     // Sortierte Abfrage der Positionen
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rechnung_positionen');
    }
};