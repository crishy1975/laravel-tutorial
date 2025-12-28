<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Diese Migration fügt die Mitarbeiter-Unterschrift hinzu.
 * Nur ausführen wenn die Tabelle schon existiert!
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('arbeitsberichte', function (Blueprint $table) {
            // Prüfen ob Spalten schon existieren
            if (!Schema::hasColumn('arbeitsberichte', 'unterschrift_mitarbeiter')) {
                $table->text('unterschrift_mitarbeiter')->nullable()->after('unterschrift_ip');
            }
            if (!Schema::hasColumn('arbeitsberichte', 'mitarbeiter_name')) {
                $table->string('mitarbeiter_name')->nullable()->after('unterschrift_mitarbeiter');
            }
        });
    }

    public function down(): void
    {
        Schema::table('arbeitsberichte', function (Blueprint $table) {
            $table->dropColumn(['unterschrift_mitarbeiter', 'mitarbeiter_name']);
        });
    }
};
