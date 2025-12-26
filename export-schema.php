<?php
/**
 * Datenbank-Schema Export
 * 
 * Verwendung: php export-schema.php
 * 
 * Exportiert alle CREATE TABLE Statements aus der aktuellen Datenbank.
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "-- ===============================================================\n";
echo "-- DATENBANK-SCHEMA EXPORT\n";
echo "-- Datum: " . date('Y-m-d H:i:s') . "\n";
echo "-- ===============================================================\n\n";

// Aktuelle Datenbank ermitteln
$currentDb = DB::connection()->getDatabaseName();
echo "-- Aktuelle Datenbank: {$currentDb}\n\n";

// Tabellen NUR aus der aktuellen Datenbank holen
$tables = DB::select("SHOW TABLES");
$tableKey = "Tables_in_{$currentDb}";

$tableNames = [];
foreach ($tables as $table) {
    $tableNames[] = $table->$tableKey;
}
sort($tableNames);

echo "-- Gefundene Tabellen: " . count($tableNames) . "\n";
echo "-- " . implode(', ', $tableNames) . "\n\n";

foreach ($tableNames as $table) {
    // migrations-Tabelle überspringen
    if ($table === 'migrations') {
        continue;
    }
    
    // phpmyadmin-Tabellen überspringen
    if (str_starts_with($table, 'pma__')) {
        continue;
    }
    
    try {
        $result = DB::select("SHOW CREATE TABLE `{$table}`");
        
        if (!empty($result)) {
            $createStatement = $result[0]->{'Create Table'};
            
            echo "-- ===============================================================\n";
            echo "-- Table: {$table}\n";
            echo "-- ===============================================================\n\n";
            
            // DROP IF EXISTS hinzufügen
            echo "DROP TABLE IF EXISTS `{$table}`;\n\n";
            echo $createStatement . ";\n\n\n";
        }
    } catch (Exception $e) {
        echo "-- FEHLER bei Tabelle {$table}: " . $e->getMessage() . "\n\n";
    }
}

echo "-- ===============================================================\n";
echo "-- EXPORT FERTIG\n";
echo "-- ===============================================================\n";
