<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fügt Aufschlag-Tracking zur rechnungen Tabelle hinzu
 * 
 * Neue Felder:
 * - aufschlag_prozent: Welcher Aufschlag wurde angewendet?
 * - aufschlag_typ: War es global, individuell oder keiner?
 */
return new class extends Migration
{
    public function up(): void
    {
        // Prüfe ob Felder bereits existieren (idempotent)
        $hasAufschlagProzent = Schema::hasColumn('rechnungen', 'aufschlag_prozent');
        $hasAufschlagTyp = Schema::hasColumn('rechnungen', 'aufschlag_typ');

        if ($hasAufschlagProzent && $hasAufschlagTyp) {
            echo "✓ Aufschlag-Felder bereits vorhanden in rechnungen\n";
            return;
        }

        Schema::table('rechnungen', function (Blueprint $table) use ($hasAufschlagProzent, $hasAufschlagTyp) {
            if (!$hasAufschlagProzent) {
                $table->decimal('aufschlag_prozent', 5, 2)->nullable()
                    ->after('ritenuta_prozent')
                    ->comment('Aufschlag in % (z.B. 3.5 für 3,5%)');
                
                echo "✓ Feld aufschlag_prozent hinzugefügt\n";
            }
            
            if (!$hasAufschlagTyp) {
                $table->enum('aufschlag_typ', ['global', 'individuell', 'keiner'])->nullable()
                    ->after('aufschlag_prozent')
                    ->comment('Quelle des Aufschlags');
                
                echo "✓ Feld aufschlag_typ hinzugefügt\n";
            }
        });

        echo "✅ Migration rechnungen abgeschlossen\n";
    }

    public function down(): void
    {
        Schema::table('rechnungen', function (Blueprint $table) {
            if (Schema::hasColumn('rechnungen', 'aufschlag_prozent')) {
                $table->dropColumn('aufschlag_prozent');
            }
            if (Schema::hasColumn('rechnungen', 'aufschlag_typ')) {
                $table->dropColumn('aufschlag_typ');
            }
        });

        echo "✅ Rollback rechnungen abgeschlossen\n";
    }
};