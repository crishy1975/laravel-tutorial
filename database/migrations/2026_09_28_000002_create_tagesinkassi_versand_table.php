<?php
/*
════════════════════════════════════════════════════════════════════════════
DATEI: 2026_09_28_000002_create_tagesinkassi_versand_table.php
PFAD:  database/migrations/2026_09_28_000002_create_tagesinkassi_versand_table.php
(Protokoll der versendeten Registro-PDFs – letzte Adresse wird vorbelegt)
════════════════════════════════════════════════════════════════════════════
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tagesinkassi_versand', function (Blueprint $table) {
            $table->id();
            $table->string('empfaenger', 500);                  // eine oder mehrere Adressen, kommagetrennt
            $table->string('umfang', 10)->default('monat');     // monat | jahr
            $table->unsignedSmallInteger('jahr');
            $table->unsignedTinyInteger('monat')->nullable();   // null bei Jahres-PDF
            $table->string('betreff', 255);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tagesinkassi_versand');
    }
};
