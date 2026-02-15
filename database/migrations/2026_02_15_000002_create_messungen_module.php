<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Messungen-Modul: impianti + messungen + Views
     */
    public function up(): void
    {
        // =====================================================
        // TABELLE: impianti (Anlagen)
        // Quelle: CSV-Import
        // =====================================================
        Schema::create('impianti', function (Blueprint $table) {
            $table->id();
            
            // Identifikation
            $table->string('Feld_a', 10)->unique()->comment('Anlagen-Kodex (PK aus CSV)');
            $table->string('Feld_b', 10)->nullable()->comment('Gemeindecode');
            $table->string('Feld_c', 5)->nullable()->comment('Nummer');
            
            // Standort
            $table->string('Feld_h', 100)->nullable()->comment('Standort Ort IT');
            $table->string('Feld_i', 100)->nullable()->comment('Standort Ort DE');
            $table->string('Feld_j', 100)->nullable()->comment('Standort Straße IT');
            $table->string('Feld_k', 100)->nullable()->comment('Standort Straße DE');
            
            // Verwalter/Kontakt
            $table->string('Feld_l', 255)->nullable()->comment('Verwalter Name');
            $table->string('Feld_m', 100)->nullable()->comment('Verwalter Ort');
            $table->string('Feld_n', 20)->nullable()->comment('Hausnummer');
            $table->string('Feld_o', 255)->nullable()->comment('Kontaktperson Name');
            $table->string('Feld_p', 100)->nullable()->comment('Kontakt Ort IT');
            $table->string('Feld_q', 100)->nullable()->comment('Kontakt Ort DE');
            $table->string('Feld_r', 100)->nullable()->comment('Kontakt Straße IT');
            $table->string('Feld_s', 100)->nullable()->comment('Kontakt Straße DE');
            $table->string('Feld_t', 50)->nullable()->comment('Kontakt Hausnummer/Fraktion');
            $table->string('Feld_u', 100)->nullable()->comment('Zusatzfeld 1');
            $table->string('Feld_v', 100)->nullable()->comment('Zusatzfeld 2');
            
            // Anlage
            $table->string('Feld_w', 255)->nullable()->comment('Beschreibung/Anlagenname');
            $table->string('Feld_x', 10)->nullable()->comment('Anzahl Kessel/Status');
            $table->string('Feld_y', 100)->nullable()->comment('Hersteller');
            $table->string('Feld_z', 4)->nullable()->comment('Baujahr');
            $table->string('Feld_ab', 10)->nullable()->comment('Leistung kW');
            
            $table->timestamps();
            
            // Indizes
            $table->index(['Feld_i', 'Feld_k', 'Feld_n'], 'idx_standort');
            $table->index('Feld_w', 'idx_beschreibung');
            $table->index('Feld_y', 'idx_hersteller');
        });

        // =====================================================
        // TABELLE: messungen (Messdaten)
        // Quelle: XML-Import (Wöhler Messgerät)
        // =====================================================
        Schema::create('messungen', function (Blueprint $table) {
            $table->id();
            
            // Verknüpfung zur Anlage
            $table->string('cIM_CODICE', 6)->comment('FK zu impianti.Feld_a');
            $table->string('cIM_NAME', 255)->nullable()->comment('Kundenname aus XML');
            
            // Messung Identifikation
            $table->string('cMIS_TIPO', 3)->default('001')->comment('Messtyp');
            $table->string('cMIS_STADIO', 1)->default('1')->comment('Fireplace-Nummer');
            $table->string('cMIS_DATA', 8)->comment('Datum DDMMYYYY');
            $table->string('cMIS_DATA2', 10)->comment('Datum DD.MM.YYYY');
            $table->string('cMIS_ORA', 8)->nullable()->comment('Uhrzeit HH:MM:SS');
            
            // Ergebnis
            $table->string('strEsito', 1)->default('0')->comment('Ergebnis: 1=positiv, 0=negativ');
            
            // Brennstoff
            $table->string('cMIS_COMBUSTIBILE', 20)->nullable()->comment('Brennstoff-Code (FUEL_xxx)');
            $table->tinyInteger('cMIS_COMBUSTIBILE_N')->default(0)->comment('Brennstoff-Nr: 1=Öl,3=Gas,6=Flüssig,7=Pellet');
            $table->string('cMIS_COMBUSTIBILE_P', 50)->nullable()->comment('Brennstoff Text zweisprachig');
            
            // Temperaturen
            $table->string('cMIS_T_GAS_COMB', 3)->nullable()->comment('Abgastemperatur °C');
            $table->string('cMIS_T_ARIA_COMB', 3)->nullable()->comment('Verbrennungsluft °C');
            $table->string('cMIS_T_LIQ_CONV', 3)->nullable()->comment('Wärmeträger-Temperatur °C');
            
            // Messwerte
            $table->string('cMIS_OSSIGENO', 5)->nullable()->comment('Sauerstoff O2 %');
            $table->string('cMIS_ANIDRIDE_CARBONICA', 5)->nullable()->comment('CO2 %');
            $table->string('cMIS_MONOSSSIDO', 4)->nullable()->comment('CO mg/m³');
            $table->string('cMIS_BIOSSIDO_AZOTO', 4)->nullable()->comment('NOx mg/m³');
            $table->string('cMIS_PERD_FUMI', 5)->nullable()->comment('Abgasverlust %');
            
            // Zusätzliche Prüfungen (Öl/Pellet)
            $table->string('cMIS_IND_OPACITA', 1)->nullable()->comment('Rußzahl');
            $table->string('cMIS_TRACCE_OLEO', 1)->nullable()->comment('Ölspuren: 0=ja, 1=nein');
            
            // Kessel-Daten (aus XML Boiler)
            $table->string('boilerYear', 4)->nullable()->comment('Baujahr Kessel');
            $table->string('boilerPower', 4)->nullable()->comment('Leistung kW');
            
            // Verbrauch & Hilfsspalten
            $table->string('cMIS_CONSUMO', 12)->default('00000000')->comment('Verbrauch');
            $table->tinyInteger('codeInImpianti')->default(0)->comment('Anlage existiert: 0=nein, 1+=ja');
            
            $table->timestamps();
            
            // Foreign Key
            $table->foreign('cIM_CODICE')
                  ->references('Feld_a')
                  ->on('impianti')
                  ->onDelete('cascade');
            
            // Indizes
            $table->index('cMIS_DATA', 'idx_datum');
            $table->index('strEsito', 'idx_ergebnis');
            $table->index(['cIM_CODICE', 'cMIS_DATA'], 'idx_anlage_datum');
            
            // Unique: Eine Messung pro Anlage, Fireplace und Zeitpunkt
            $table->unique(
                ['cIM_CODICE', 'cMIS_STADIO', 'cMIS_DATA', 'cMIS_ORA'], 
                'unique_messung'
            );
        });

        // =====================================================
        // VIEW: v_anlagen_messung_status
        // Zeigt für jede Anlage ob Messung im aktuellen Jahr existiert
        // =====================================================
        DB::statement("
            CREATE OR REPLACE VIEW v_anlagen_messung_status AS
            SELECT 
                i.id,
                i.Feld_a AS anlage_kodex,
                i.Feld_w AS beschreibung,
                i.Feld_i AS ort,
                i.Feld_k AS strasse,
                i.Feld_n AS hausnummer,
                i.Feld_y AS hersteller,
                i.Feld_z AS baujahr,
                i.Feld_ab AS leistung_kw,
                YEAR(CURDATE()) AS pruef_jahr,
                MAX(m.id) AS letzte_messung_id,
                MAX(m.cMIS_DATA2) AS letzte_messung_datum,
                MAX(m.strEsito) AS letztes_ergebnis,
                CASE 
                    WHEN MAX(m.id) IS NOT NULL THEN 1 
                    ELSE 0 
                END AS hat_messung_heuer
            FROM impianti i
            LEFT JOIN messungen m 
                ON i.Feld_a = m.cIM_CODICE 
                AND YEAR(STR_TO_DATE(m.cMIS_DATA, '%d%m%Y')) = YEAR(CURDATE())
            GROUP BY 
                i.id, i.Feld_a, i.Feld_w, i.Feld_i, i.Feld_k, 
                i.Feld_n, i.Feld_y, i.Feld_z, i.Feld_ab
        ");

        // =====================================================
        // VIEW: v_anlagen_ohne_messung
        // Zeigt nur Anlagen OHNE Messung im aktuellen Jahr
        // =====================================================
        DB::statement("
            CREATE OR REPLACE VIEW v_anlagen_ohne_messung AS
            SELECT 
                i.id,
                i.Feld_a AS anlage_kodex,
                i.Feld_w AS beschreibung,
                i.Feld_i AS ort,
                i.Feld_k AS strasse,
                i.Feld_n AS hausnummer,
                i.Feld_y AS hersteller,
                i.Feld_z AS baujahr,
                i.Feld_ab AS leistung_kw,
                (
                    SELECT MAX(m2.cMIS_DATA2) 
                    FROM messungen m2 
                    WHERE m2.cIM_CODICE = i.Feld_a
                ) AS letzte_messung_ueberhaupt
            FROM impianti i
            WHERE NOT EXISTS (
                SELECT 1 
                FROM messungen m 
                WHERE m.cIM_CODICE = i.Feld_a 
                AND YEAR(STR_TO_DATE(m.cMIS_DATA, '%d%m%Y')) = YEAR(CURDATE())
            )
            ORDER BY i.Feld_i, i.Feld_k, i.Feld_n
        ");
    }

    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS v_anlagen_ohne_messung");
        DB::statement("DROP VIEW IF EXISTS v_anlagen_messung_status");
        Schema::dropIfExists('messungen');
        Schema::dropIfExists('impianti');
    }
};
