<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Passt die preis_aufschlaege Tabelle an die neue Struktur an
 * 
 * ALT: aufschlag_prozent, ist_global, gebaeude_id, bemerkung
 * NEU: prozent, beschreibung (nur globale Einträge)
 */
return new class extends Migration
{
    public function up(): void
    {
        // ═══════════════════════════════════════════════════════════
        // 1️⃣ Prüfe ob Tabelle bereits neue Struktur hat
        // ═══════════════════════════════════════════════════════════
        
        if (Schema::hasColumn('preis_aufschlaege', 'prozent')) {
            echo "✓ preis_aufschlaege hat bereits die neue Struktur\n";
            return;
        }

        // ═══════════════════════════════════════════════════════════
        // 2️⃣ Neue Tabelle: gebaeude_aufschlaege erstellen
        // ═══════════════════════════════════════════════════════════
        
        if (!Schema::hasTable('gebaeude_aufschlaege')) {
            Schema::create('gebaeude_aufschlaege', function (Blueprint $table) {
                $table->id();
                
                $table->foreignId('gebaeude_id')
                    ->constrained('gebaeude')
                    ->cascadeOnDelete();
                
                $table->decimal('prozent', 5, 2)->default(0)
                    ->comment('Überschreibt globalen Aufschlag. 0 = keine Erhöhung');
                
                $table->string('grund', 500)->nullable()
                    ->comment('Z.B. "Langzeitvertrag bis 2027"');
                
                $table->date('gueltig_ab')->nullable()
                    ->comment('Ab wann gilt dieser Aufschlag');
                
                $table->date('gueltig_bis')->nullable()
                    ->comment('Bis wann gilt dieser Aufschlag (NULL = unbegrenzt)');
                
                $table->timestamps();
                
                $table->index(['gebaeude_id', 'gueltig_ab', 'gueltig_bis']);
            });

            echo "✓ Tabelle gebaeude_aufschlaege erstellt\n";
        }

        // ═══════════════════════════════════════════════════════════
        // 3️⃣ Daten migrieren: Gebäude-spezifisch → gebaeude_aufschlaege
        // ═══════════════════════════════════════════════════════════
        
        $gebaeudeEintraege = DB::table('preis_aufschlaege')
            ->where('ist_global', 0)
            ->whereNotNull('gebaeude_id')
            ->get();

        foreach ($gebaeudeEintraege as $eintrag) {
            DB::table('gebaeude_aufschlaege')->insert([
                'gebaeude_id'  => $eintrag->gebaeude_id,
                'prozent'      => $eintrag->aufschlag_prozent,
                'grund'        => $eintrag->bemerkung,
                'gueltig_ab'   => now(),
                'gueltig_bis'  => null,
                'created_at'   => $eintrag->created_at,
                'updated_at'   => $eintrag->updated_at,
            ]);
        }

        if ($gebaeudeEintraege->count() > 0) {
            echo "✓ {$gebaeudeEintraege->count()} gebäude-spezifische Einträge migriert\n";
        }

        // ═══════════════════════════════════════════════════════════
        // 4️⃣ Gebäude-spezifische Einträge löschen
        // ═══════════════════════════════════════════════════════════
        
        DB::table('preis_aufschlaege')->where('ist_global', 0)->delete();

        // ═══════════════════════════════════════════════════════════
        // 5️⃣ Foreign Keys dynamisch ermitteln und entfernen
        // ═══════════════════════════════════════════════════════════

        // Hole alle Foreign Keys für gebaeude_id Spalte
        $foreignKeys = DB::select("
            SELECT CONSTRAINT_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'preis_aufschlaege'
              AND COLUMN_NAME = 'gebaeude_id'
              AND REFERENCED_TABLE_NAME IS NOT NULL
        ");

        foreach ($foreignKeys as $fk) {
            try {
                DB::statement("ALTER TABLE preis_aufschlaege DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
                echo "✓ Foreign Key '{$fk->CONSTRAINT_NAME}' entfernt\n";
            } catch (\Exception $e) {
                echo "⚠ Foreign Key '{$fk->CONSTRAINT_NAME}' konnte nicht entfernt werden\n";
            }
        }

        // ═══════════════════════════════════════════════════════════
        // 6️⃣ Indizes dynamisch ermitteln und entfernen
        // ═══════════════════════════════════════════════════════════

        // Hole alle Indizes für ist_global und gebaeude_id
        $indexes = DB::select("
            SELECT DISTINCT INDEX_NAME
            FROM INFORMATION_SCHEMA.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'preis_aufschlaege'
              AND COLUMN_NAME IN ('ist_global', 'gebaeude_id')
              AND INDEX_NAME != 'PRIMARY'
        ");

        foreach ($indexes as $idx) {
            try {
                DB::statement("ALTER TABLE preis_aufschlaege DROP INDEX `{$idx->INDEX_NAME}`");
                echo "✓ Index '{$idx->INDEX_NAME}' entfernt\n";
            } catch (\Exception $e) {
                echo "⚠ Index '{$idx->INDEX_NAME}' konnte nicht entfernt werden\n";
            }
        }

        // ═══════════════════════════════════════════════════════════
        // 7️⃣ Alte Spalten entfernen
        // ═══════════════════════════════════════════════════════════

        Schema::table('preis_aufschlaege', function (Blueprint $table) {
            $table->dropColumn(['ist_global', 'gebaeude_id', 'bemerkung']);
        });

        echo "✓ Alte Spalten entfernt\n";

        // ═══════════════════════════════════════════════════════════
        // 8️⃣ Spalte umbenennen: aufschlag_prozent → prozent
        // ═══════════════════════════════════════════════════════════

        DB::statement('ALTER TABLE preis_aufschlaege CHANGE COLUMN aufschlag_prozent prozent DECIMAL(5,2) NOT NULL DEFAULT 0 COMMENT "Aufschlag in Prozent"');

        echo "✓ Spalte umbenannt: aufschlag_prozent → prozent\n";

        // ═══════════════════════════════════════════════════════════
        // 9️⃣ Neue Spalte hinzufügen
        // ═══════════════════════════════════════════════════════════

        Schema::table('preis_aufschlaege', function (Blueprint $table) {
            $table->string('beschreibung', 500)->nullable()
                ->after('prozent')
                ->comment('Z.B. "Inflation 2025"');
        });

        echo "✓ Spalte beschreibung hinzugefügt\n";

        // ═══════════════════════════════════════════════════════════
        // 🔟 Beschreibungen für bestehende Einträge setzen
        // ═══════════════════════════════════════════════════════════
        
        DB::table('preis_aufschlaege')
            ->whereNull('beschreibung')
            ->update([
                'beschreibung' => DB::raw("CONCAT('Aufschlag ', jahr)")
            ]);

        // ═══════════════════════════════════════════════════════════
        // 1️⃣1️⃣ Standard-Eintrag für aktuelles Jahr
        // ═══════════════════════════════════════════════════════════
        
        $aktuellesJahr = now()->year;
        $existiert = DB::table('preis_aufschlaege')
            ->where('jahr', $aktuellesJahr)
            ->exists();

        if (!$existiert) {
            DB::table('preis_aufschlaege')->insert([
                'jahr'         => $aktuellesJahr,
                'prozent'      => 0.00,
                'beschreibung' => "Standard-Aufschlag {$aktuellesJahr}",
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);

            echo "✓ Standard-Eintrag für {$aktuellesJahr} erstellt\n";
        }

        echo "\n✅ Migration preis_aufschlaege abgeschlossen!\n";
    }

    public function down(): void
    {
        // Rollback nur wenn neue Struktur vorhanden
        if (!Schema::hasColumn('preis_aufschlaege', 'prozent')) {
            echo "⚠ Tabelle hat bereits alte Struktur\n";
            return;
        }

        // Daten aus gebaeude_aufschlaege zurück migrieren
        if (Schema::hasTable('gebaeude_aufschlaege')) {
            $gebaeudeAufschlaege = DB::table('gebaeude_aufschlaege')->get();

            // Alte Spalten wiederherstellen
            DB::statement('ALTER TABLE preis_aufschlaege CHANGE COLUMN prozent aufschlag_prozent DECIMAL(5,2) NOT NULL DEFAULT 0');

            Schema::table('preis_aufschlaege', function (Blueprint $table) {
                $table->dropColumn('beschreibung');
                $table->tinyInteger('ist_global')->default(1)->after('aufschlag_prozent');
                $table->unsignedBigInteger('gebaeude_id')->nullable()->after('ist_global');
                $table->text('bemerkung')->nullable()->after('gebaeude_id');
                
                $table->index('ist_global');
                $table->index('gebaeude_id');
            });

            // Gebäude-Aufschläge zurück migrieren
            foreach ($gebaeudeAufschlaege as $aufschlag) {
                $jahr = \Carbon\Carbon::parse($aufschlag->created_at)->year;
                
                DB::table('preis_aufschlaege')->insert([
                    'jahr'              => $jahr,
                    'aufschlag_prozent' => $aufschlag->prozent,
                    'ist_global'        => 0,
                    'gebaeude_id'       => $aufschlag->gebaeude_id,
                    'bemerkung'         => $aufschlag->grund,
                    'created_at'        => $aufschlag->created_at,
                    'updated_at'        => $aufschlag->updated_at,
                ]);
            }

            // Tabelle löschen
            Schema::dropIfExists('gebaeude_aufschlaege');
        }

        echo "✅ Rollback preis_aufschlaege abgeschlossen\n";
    }
};