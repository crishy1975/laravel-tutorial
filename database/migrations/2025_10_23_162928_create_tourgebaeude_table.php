<?php
// Datei: database/migrations/2025_10_23_000100_create_tourgebaeude_table.php
// (Timestamp-Beispiel: passe den Dateinamen an)

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pivot-Tabelle: tourgebaeude
     * - Viele-zu-Viele zwischen 'tour' (id) und 'gebaeude' (id)
     * - Zusatzspalte 'reihenfolge' zur Sortierung je Gebäude
     * - Composite Primary Key (tour_id, gebaeude_id)
     */
    public function up(): void
    {
        // Falls eine der alten Varianten existiert und die neue noch nicht, kannst du unten
        // zusätzlich eine Rename-Migration ausführen (siehe Abschnitt 2).
        if (Schema::hasTable('tourgebaeude')) {
            return; // bereits vorhanden → nichts tun
        }

        Schema::create('tourgebaeude', function (Blueprint $table) {
            // IDs passend zu $table->id() (BIGINT UNSIGNED)
            $table->unsignedBigInteger('tour_id');
            $table->unsignedBigInteger('gebaeude_id');

            // Sortierwert (0 = erste Position)
            $table->integer('reihenfolge')->default(0);

            // Composite Primary Key (verhindert Duplikate)
            $table->primary(['tour_id', 'gebaeude_id']);

            // Indizes für gängige Abfragen
            $table->index(['gebaeude_id', 'reihenfolge']);
            $table->index('tour_id');

            // ⚠️ Optional: FK-Constraints aktivieren, wenn Engine & Spaltentypen passen
            // $table->foreign('tour_id')->references('id')->on('tour')->cascadeOnDelete();
            // $table->foreign('gebaeude_id')->references('id')->on('gebaeude')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('tourgebaeude')) {
            Schema::drop('tourgebaeude');
        }
    }
};
