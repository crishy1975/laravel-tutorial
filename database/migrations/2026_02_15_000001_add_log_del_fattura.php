<?php
// database/migrations/2024_xx_xx_add_soft_deletes_to_rechnungen_table.php
// ══════════════════════════════════════════════════════════════════════════════
// EINFACHERE ALTERNATIVE (funktioniert mit allen MySQL/MariaDB Versionen)
// ══════════════════════════════════════════════════════════════════════════════

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * SoftDeletes für Rechnungen.
     * 
     * LÖSUNG für das Duplikat-Problem:
     * Statt eines komplexen Unique-Index prüfen wir die Eindeutigkeit
     * in der Anwendungslogik (siehe Rechnung Model).
     */
    public function up(): void
    {
        Schema::table('rechnungen', function (Blueprint $table) {
            // 1. SoftDeletes hinzufügen
            $table->softDeletes();
        });

        // 2. Index für Performance bei withTrashed-Queries
        Schema::table('rechnungen', function (Blueprint $table) {
            $table->index('deleted_at');
        });

        // 3. Composite Index für Rechnungsnummer-Suche (optional)
        //    Hilft bei: WHERE jahr = X AND laufnummer = Y AND deleted_at IS NULL
        Schema::table('rechnungen', function (Blueprint $table) {
            $table->index(['jahr', 'laufnummer', 'deleted_at'], 'idx_rechnungen_nummer_soft');
        });
    }

    public function down(): void
    {
        Schema::table('rechnungen', function (Blueprint $table) {
            $table->dropIndex('idx_rechnungen_nummer_soft');
            $table->dropIndex(['deleted_at']);
            $table->dropSoftDeletes();
        });
    }
};


// ══════════════════════════════════════════════════════════════════════════════
// PRÜFUNG IN DER ANWENDUNGSLOGIK (im Rechnung Model hinzufügen)
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Prüft VOR dem Speichern, ob die Rechnungsnummer bereits aktiv existiert.
 * 
 * Füge dies in der boot() Methode des Rechnung Models hinzu:
 */
protected static function booted()
{
    static::creating(function ($rechnung) {
        // Prüfen ob Nummer bereits existiert (aktive Rechnung)
        $exists = static::where('jahr', $rechnung->jahr)
            ->where('laufnummer', $rechnung->laufnummer)
            ->whereNull('deleted_at')
            ->exists();
            
        if ($exists) {
            throw new \Exception(
                "Rechnungsnummer {$rechnung->jahr}/{$rechnung->laufnummer} existiert bereits."
            );
        }
    });
    
    static::restoring(function ($rechnung) {
        // Beim Wiederherstellen prüfen, ob Nummer inzwischen vergeben wurde
        $exists = static::where('jahr', $rechnung->jahr)
            ->where('laufnummer', $rechnung->laufnummer)
            ->whereNull('deleted_at')
            ->where('id', '!=', $rechnung->id)
            ->exists();
            
        if ($exists) {
            throw new \Exception(
                "Rechnungsnummer {$rechnung->jahr}/{$rechnung->laufnummer} ist bereits vergeben. " .
                "Wiederherstellung nicht möglich."
            );
        }
    });
}
