<?php

// database/migrations/XXXX_XX_XX_XXXXXX_update_tourgebaeude_indexes.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tourgebaeude', function (Blueprint $table) {
            // Falls es bereits einen single-PK oder eine auto-inc ID gäbe: vorher droppen.
            // Beispiel (nur falls vorhanden!):
            // $table->dropPrimary(); $table->dropColumn('id');

            // Stelle sicher, dass die Spalten existieren und unsigned sind:
            // (weglassen, wenn schon korrekt)
            // $table->unsignedBigInteger('tour_id')->change();
            // $table->unsignedBigInteger('gebaeude_id')->change();

            // ✅ Composite Primary Key
            $table->primary(['tour_id', 'gebaeude_id']);

            // ✅ Optionaler Ordnungsindex je Tour (für schnelle Sortierung)
            $table->index(['tour_id', 'reihenfolge']);

            // ✅ Foreign Keys (Namen ggf. anpassen, falls schon vorhanden)
            $table->foreign('tour_id')->references('id')->on('tour')->onDelete('cascade');
            $table->foreign('gebaeude_id')->references('id')->on('gebaeude')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('tourgebaeude', function (Blueprint $table) {
            $table->dropForeign(['tour_id']);
            $table->dropForeign(['gebaeude_id']);
            $table->dropIndex(['tour_id', 'reihenfolge']);
            $table->dropPrimary();
            // Falls nötig: alten Zustand wiederherstellen
        });
    }
};
