<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Füge der Tabelle "rechnungen" das Feld "typ_rechnung" hinzu.
     *
     * Werte:
     * - "rechnung"   = normale Rechnung
     * - "gutschrift" = Storno / Gutschrift
     */
    public function up(): void
    {
        Schema::table('rechnungen', function (Blueprint $table) {
            // Wenn du ENUM verwenden willst:
            // -> default "rechnung", damit bestehende Datensätze automatisch gültig sind.
            $table->enum('typ_rechnung', ['rechnung', 'gutschrift'])
                  ->default('rechnung')
                  ->after('status'); // "after" kannst du anpassen, je nach Spaltenreihenfolge
        });
    }

    /**
     * Rolle die Änderung wieder zurück:
     * Entfernt das Feld "typ_rechnung" aus der Tabelle "rechnungen".
     */
    public function down(): void
    {
        Schema::table('rechnungen', function (Blueprint $table) {
            $table->dropColumn('typ_rechnung');
        });
    }
};
