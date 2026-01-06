<?php
/**
 * ════════════════════════════════════════════════════════════════════════════
 * DATEI: 2026_01_06_000003_create_eingangsrechnung_artikel_table.php
 * PFAD:  database/migrations/2026_01_06_000003_create_eingangsrechnung_artikel_table.php
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
        Schema::create('eingangsrechnung_artikel', function (Blueprint $table) {
            $table->id();

            // Beziehung zur Rechnung
            $table->foreignId('eingangsrechnung_id')
                  ->constrained('eingangsrechnungen')
                  ->onDelete('cascade');

            // Artikeldaten aus XML (DettaglioLinee)
            $table->unsignedInteger('zeile')->comment('NumeroLinea aus XML');
            $table->string('artikelcode')->nullable()->comment('CodiceValore aus XML');
            $table->text('beschreibung');
            
            // Mengen & Preise
            $table->decimal('menge', 12, 3)->default(1)->comment('Quantita');
            $table->string('einheit', 20)->nullable()->comment('UnitaMisura (PZ, L, KG...)');
            $table->decimal('einzelpreis', 12, 6)->default(0)->comment('PrezzoUnitario');
            $table->decimal('gesamtpreis', 12, 2)->default(0)->comment('PrezzoTotale');
            
            // MwSt
            $table->decimal('mwst_satz', 5, 2)->default(22)->comment('AliquotaIVA');

            $table->timestamps();

            // Index für schnelle Abfragen
            $table->index(['eingangsrechnung_id', 'zeile']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('eingangsrechnung_artikel');
    }
};
