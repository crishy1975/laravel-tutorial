<?php
// database/migrations/2025_11_16_140000_create_preis_aufschlaege_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('preis_aufschlaege', function (Blueprint $table) {
            $table->id();

            // ═══════════════════════════════════════════════════════════
            // 📅 JAHR
            // ═══════════════════════════════════════════════════════════
            $table->year('jahr')->index();                  // 2025, 2026, 2027...

            // ═══════════════════════════════════════════════════════════
            // 💰 AUFSCHLAG (KUMULATIV vom Basis-Jahr)
            // ═══════════════════════════════════════════════════════════
            $table->decimal('aufschlag_prozent', 5, 2)      // z.B. 5.00 für +5%
                ->default(0)
                ->comment('Kumulativer Aufschlag vom Basis-Jahr');

            // ═══════════════════════════════════════════════════════════
            // 🌍 GELTUNGSBEREICH
            // ═══════════════════════════════════════════════════════════
            $table->boolean('ist_global')->default(true)->index();
            // true  = Global (für alle Gebäude)
            // false = Spezifisch für ein Gebäude

            $table->foreignId('gebaeude_id')                // NULL = Global
                ->nullable()
                ->constrained('gebaeude')
                ->cascadeOnDelete();

            // ═══════════════════════════════════════════════════════════
            // 📝 NOTIZEN
            // ═══════════════════════════════════════════════════════════
            $table->text('bemerkung')->nullable();

            // ═══════════════════════════════════════════════════════════
            // 🕒 TIMESTAMPS
            // ═══════════════════════════════════════════════════════════
            $table->timestamps();

            // ═══════════════════════════════════════════════════════════
            // 🔍 UNIQUE CONSTRAINTS
            // ═══════════════════════════════════════════════════════════
            $table->unique(['jahr', 'ist_global', 'gebaeude_id'], 'unique_jahr_scope');
        });

        // ═══════════════════════════════════════════════════════════
        // 🌱 SEED: Standard-Werte (KUMULATIV vom Basis-Jahr 2024)
        // ═══════════════════════════════════════════════════════════
        DB::table('preis_aufschlaege')->insert([
            [
                'jahr'              => 2024,
                'aufschlag_prozent' => 0.00,   // Basis-Jahr (0%)
                'ist_global'        => true,
                'gebaeude_id'       => null,
                'bemerkung'         => 'Basis-Jahr (keine Erhöhung)',
                'created_at'        => now(),
                'updated_at'        => now(),
            ],
            [
                'jahr'              => 2025,
                'aufschlag_prozent' => 5.00,   // 1 Jahr = +5%
                'ist_global'        => true,
                'gebaeude_id'       => null,
                'bemerkung'         => '1 Jahr Erhöhung',
                'created_at'        => now(),
                'updated_at'        => now(),
            ],
            [
                'jahr'              => 2026,
                'aufschlag_prozent' => 10.00,  // 2 Jahre = +10%
                'ist_global'        => true,
                'gebaeude_id'       => null,
                'bemerkung'         => '2 Jahre Erhöhung',
                'created_at'        => now(),
                'updated_at'        => now(),
            ],
            [
                'jahr'              => 2027,
                'aufschlag_prozent' => 15.00,  // 3 Jahre = +15%
                'ist_global'        => true,
                'gebaeude_id'       => null,
                'bemerkung'         => '3 Jahre Erhöhung',
                'created_at'        => now(),
                'updated_at'        => now(),
            ],
            [
                'jahr'              => 2028,
                'aufschlag_prozent' => 20.00,  // 4 Jahre = +20%
                'ist_global'        => true,
                'gebaeude_id'       => null,
                'bemerkung'         => '4 Jahre Erhöhung',
                'created_at'        => now(),
                'updated_at'        => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('preis_aufschlaege');
    }
};