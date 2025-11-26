<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration: Zahlungsbedingung zu Enum ändern
 * 
 * Ändert das Feld "zahlungsbedingung" von String zu Enum.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Schritt 1: Temporäre Spalte erstellen
        Schema::table('rechnungen', function (Blueprint $table) {
            $table->string('zahlungsbedingungen_temp', 20)->nullable()->after('zahlungsbedingungen');
        });

        // Schritt 2: Daten migrieren (alte Strings zu Enum-Werten)
        DB::table('rechnungen')->orderBy('id')->chunk(100, function ($rechnungen) {
            foreach ($rechnungen as $rechnung) {
                $alt = $rechnung->zahlungsbedingungen;
                $neu = $this->mapLegacyValue($alt);
                
                DB::table('rechnungen')
                    ->where('id', $rechnung->id)
                    ->update(['zahlungsbedingungen_temp' => $neu]);
            }
        });

        // Schritt 3: Alte Spalte löschen
        Schema::table('rechnungen', function (Blueprint $table) {
            $table->dropColumn('zahlungsbedingungen');
        });

        // Schritt 4: Temporäre Spalte umbenennen und zu Enum ändern
        Schema::table('rechnungen', function (Blueprint $table) {
            $table->renameColumn('zahlungsbedingungen_temp', 'zahlungsbedingungen');
        });

        // Schritt 5: Enum Constraint hinzufügen (MySQL/MariaDB)
        if (DB::getDriverName() === 'mysql') {
            DB::statement("
                ALTER TABLE rechnungen 
                MODIFY COLUMN zahlungsbedingungen 
                ENUM('sofort', 'netto_7', 'netto_14', 'netto_30', 'netto_60', 'netto_90', 'netto_120', 'bezahlt') 
                DEFAULT 'netto_30'
            ");
        } else {
            // Für PostgreSQL oder SQLite - Check Constraint
            Schema::table('rechnungen', function (Blueprint $table) {
                $table->string('zahlungsbedingungen', 20)->default('netto_30')->change();
            });
        }
    }

    public function down(): void
    {
        // Zurück zu String
        Schema::table('rechnungen', function (Blueprint $table) {
            $table->text('zahlungsbedingungen')->nullable()->change();
        });
    }

    /**
     * Konvertiert alte String-Werte zu Enum-Werten.
     */
    private function mapLegacyValue(?string $alt): string
    {
        if (empty($alt)) {
            return 'netto_30'; // Standard
        }

        $alt = strtolower(trim($alt));

        // Direkte Matches
        if ($alt === 'bezahlt' || $alt === 'paid') {
            return 'bezahlt';
        }

        if ($alt === 'sofort') {
            return 'sofort';
        }

        // Extrahiere Zahlen
        if (preg_match('/(\d+)/', $alt, $matches)) {
            $tage = (int) $matches[1];

            return match($tage) {
                7 => 'netto_7',
                14 => 'netto_14',
                30 => 'netto_30',
                60 => 'netto_60',
                90 => 'netto_90',
                120 => 'netto_120',
                default => 'netto_30',
            };
        }

        // Fallback
        return 'netto_30';
    }
};