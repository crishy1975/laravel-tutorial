<?php
/**
 * ════════════════════════════════════════════════════════════════════════════
 * DATEI: 2026_01_06_000001_create_lieferanten_table.php
 * PFAD:  database/migrations/2026_01_06_000001_create_lieferanten_table.php
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
        Schema::create('lieferanten', function (Blueprint $table) {
            $table->id();

            // Identifikation (für automatische Erkennung beim Import)
            $table->string('partita_iva', 20)->unique()->comment('P.IVA - eindeutig für Erkennung');
            $table->string('codice_fiscale', 20)->nullable();

            // Stammdaten
            $table->string('name');
            $table->string('strasse')->nullable();
            $table->string('plz', 10)->nullable();
            $table->string('ort')->nullable();
            $table->string('provinz', 5)->nullable();
            $table->string('land', 2)->default('IT');

            // Kontakt
            $table->string('telefon', 50)->nullable();
            $table->string('email')->nullable();

            // Bankdaten (editierbar - manchmal im XML falsch)
            $table->string('iban', 34)->nullable()->comment('IBAN - kann manuell korrigiert werden');
            $table->string('iban_inhaber')->nullable()->comment('Kontoinhaber falls abweichend');
            $table->string('bic', 11)->nullable();

            // Sonstiges
            $table->text('notiz')->nullable();
            $table->boolean('aktiv')->default(true);

            $table->timestamps();
            $table->softDeletes();

            // Index für schnelle Suche
            $table->index('name');
            $table->index('aktiv');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lieferanten');
    }
};
