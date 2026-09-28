<?php
/*
════════════════════════════════════════════════════════════════════════════
DATEI: 2026_09_28_000001_create_tagesinkassi_table.php
PFAD:  database/migrations/2026_09_28_000001_create_tagesinkassi_table.php
════════════════════════════════════════════════════════════════════════════
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tagesinkassi', function (Blueprint $table) {
            $table->id();
            $table->date('datum')->unique();                    // ein Eintrag pro Tag
            $table->decimal('betrag_10', 10, 2)->default(0);    // brutto inkl. 10 % MwSt.
            $table->decimal('betrag_22', 10, 2)->default(0);    // brutto inkl. 22 % MwSt.
            $table->string('bemerkung', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tagesinkassi');
    }
};
