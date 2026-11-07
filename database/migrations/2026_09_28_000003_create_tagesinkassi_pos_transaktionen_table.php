<?php
/*
════════════════════════════════════════════════════════════════════════════
DATEI: 2026_09_28_000003_create_tagesinkassi_pos_transaktionen_table.php
PFAD:  database/migrations/2026_09_28_000003_create_tagesinkassi_pos_transaktionen_table.php
(Importierte POS-Zahlungen – verhindert doppelten Import über die Referenz)
════════════════════════════════════════════════════════════════════════════
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tagesinkassi_pos_transaktionen', function (Blueprint $table) {
            $table->id();
            $table->string('referenz', 64)->unique();           // "Transaction reference" aus dem POS-Portal
            $table->dateTime('datum_zeit');
            $table->decimal('betrag', 10, 2);
            $table->string('karte', 20)->nullable();            // z. B. *2938
            $table->string('terminal', 100)->nullable();        // z. B. JakobPos
            $table->string('beschreibung', 255)->nullable();
            // Wird der Tageseintrag gelöscht, verschwinden auch die Zuordnungen → erneuter Import möglich
            $table->foreignId('tagesinkasso_id')->constrained('tagesinkassi')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tagesinkassi_pos_transaktionen');
    }
};
