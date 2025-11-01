<?php
// database/migrations/2025_11_01_000000_create_artikel_gebaeude_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('artikel_gebaeude', function (Blueprint $table) {
            $table->id();

            // FK auf gebaeude.id (ON DELETE CASCADE: löscht Artikel, wenn Gebäude gelöscht wird)
            $table->unsignedBigInteger('gebaeude_id');
            $table->foreign('gebaeude_id')
                  ->references('id')->on('gebaeude')
                  ->onDelete('cascade');

            // Datenfelder
            $table->string('beschreibung', 255);       // kurze Bezeichnung (Pflicht)
            $table->decimal('anzahl', 10, 2)->default(1);      // Menge (z. B. 1.00, 2.50)
            $table->decimal('einzelpreis', 10, 2)->default(0); // Preis pro Stück

            // Timestamps + SoftDelete (optional – hier nicht zwingend)
            $table->timestamps();
            // $table->softDeletes(); // falls gewünscht aktivieren
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('artikel_gebaeude');
    }
};
