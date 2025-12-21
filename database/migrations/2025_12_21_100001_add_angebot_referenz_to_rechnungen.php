<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fügt angebot_referenz Feld zur rechnungen-Tabelle hinzu
 * (für die Verknüpfung von Angebot → Rechnung)
 */
return new class extends Migration
{
    public function up(): void
    {
        // Nur hinzufügen wenn Feld noch nicht existiert
        if (!Schema::hasColumn('rechnungen', 'angebot_referenz')) {
            Schema::table('rechnungen', function (Blueprint $table) {
                $table->string('angebot_referenz', 20)->nullable()->after('bemerkung')
                    ->comment('Referenz zum ursprünglichen Angebot (z.B. A2025/0001)');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('rechnungen', 'angebot_referenz')) {
            Schema::table('rechnungen', function (Blueprint $table) {
                $table->dropColumn('angebot_referenz');
            });
        }
    }
};
