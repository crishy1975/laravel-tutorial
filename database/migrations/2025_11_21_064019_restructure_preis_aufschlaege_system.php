<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Strukturiert die bestehende preis_aufschlaege Tabelle um
 * 
 * ALT: ist_global, gebaeude_id, aufschlag_prozent, bemerkung
 * NEU: prozent, beschreibung (nur noch globale Einträge)
 * 
 * Gebäude-spezifische Einträge werden in neue Tabelle gebaeude_aufschlaege migriert
 */
return new class extends Migration
{
    public function up(): void
    {
        // ═══════════════════════════════════════════════════════════
        // 1️⃣ NEUE TABELLE: gebaeude_aufschlaege erstellen
        // ═══════════════════════════════════════════════════════════
        
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

        // ═══════════════════════════════════════════════════════════
        // 2️⃣ DATEN MIGRIEREN: Gebäude-spezifische → gebaeude_aufschlaege
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
                'gueltig_ab'   => now(), // Ab jetzt gültig
                'gueltig_bis'  => null,  // Unbegrenzt
                'created_at'   => $eintrag->created_at,
                'updated_at'   => $eintrag->updated_at,
            ]);
        }

        // ═══════════════════════════════════════════════════════════
        // 3️⃣ TABELLE preis_aufschlaege UMSTRUKTURIEREN
        // ═══════════════════════════════════════════════════════════

        // Gebäude-spezifische Einträge löschen (sind jetzt in gebaeude_aufschlaege)
        DB::table('preis_aufschlaege')
            ->where('ist_global', 0)
            ->delete();

        Schema::table('preis_aufschlaege', function (Blueprint $table) {
            // Alte Spalten entfernen
            $table->dropColumn(['ist_global', 'gebaeude_id', 'bemerkung']);
            
            // aufschlag_prozent → prozent umbenennen
            $table->renameColumn('aufschlag_prozent', 'prozent');
        });

        // Neue Spalte hinzufügen
        Schema::table('preis_aufschlaege', function (Blueprint $table) {
            $table->string('beschreibung', 500)->nullable()
                ->after('prozent')
                ->comment('Z.B. "Inflation 2025"');
        });

        // ═══════════════════════════════════════════════════════════
        // 4️⃣ STANDARD-EINTRAG erstellen (falls nicht vorhanden)
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
        }
    }

    public function down(): void
    {
        // Rollback: Alte Struktur wiederherstellen
        
        // Daten aus gebaeude_aufschlaege zurück migrieren
        $gebaeudeAufschlaege = DB::table('gebaeude_aufschlaege')->get();

        Schema::table('preis_aufschlaege', function (Blueprint $table) {
            $table->renameColumn('prozent', 'aufschlag_prozent');
            $table->dropColumn('beschreibung');
        });

        Schema::table('preis_aufschlaege', function (Blueprint $table) {
            $table->tinyInteger('ist_global')->default(1)->after('aufschlag_prozent');
            $table->foreignId('gebaeude_id')->nullable()->after('ist_global');
            $table->text('bemerkung')->nullable()->after('gebaeude_id');
            
            $table->index('ist_global');
            $table->index('gebaeude_id');
        });

        // Gebäude-Aufschläge zurück migrieren
        foreach ($gebaeudeAufschlaege as $aufschlag) {
            // Hole Jahr aus created_at oder nutze aktuelles Jahr
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

        // Neue Tabelle löschen
        Schema::dropIfExists('gebaeude_aufschlaege');
    }
};