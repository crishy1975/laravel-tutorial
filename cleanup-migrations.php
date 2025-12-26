<?php
/**
 * Alte Migrations aufräumen
 * 
 * Verwendung: php cleanup-migrations.php
 * 
 * Dieses Script:
 * 1. Zeigt alle vorhandenen Migrations an
 * 2. Löscht alle außer der Master-Migration (mit Bestätigung)
 */

$migrationsPath = __DIR__ . '/database/migrations';

if (!is_dir($migrationsPath)) {
    die("Migrations-Ordner nicht gefunden: {$migrationsPath}\n");
}

$files = glob($migrationsPath . '/*.php');
$masterMigration = '2025_01_01_000000_create_all_tables.php';

echo "===========================================\n";
echo " MIGRATIONS CLEANUP\n";
echo "===========================================\n\n";

echo "Gefundene Migrations: " . count($files) . "\n\n";

$toDelete = [];
$toKeep = [];

foreach ($files as $file) {
    $filename = basename($file);
    
    if ($filename === $masterMigration) {
        $toKeep[] = $filename;
    } else {
        $toDelete[] = $file;
    }
}

echo "BEHALTEN:\n";
foreach ($toKeep as $f) {
    echo "  ✓ {$f}\n";
}

echo "\nLÖSCHEN (" . count($toDelete) . " Dateien):\n";
foreach ($toDelete as $f) {
    echo "  ✗ " . basename($f) . "\n";
}

if (count($toDelete) === 0) {
    echo "\nKeine Dateien zum Löschen.\n";
    exit(0);
}

echo "\n";
echo "ACHTUNG: Dies löscht " . count($toDelete) . " Migrations-Dateien!\n";
echo "Fortfahren? (ja/nein): ";

$handle = fopen("php://stdin", "r");
$line = fgets($handle);
fclose($handle);

if (trim(strtolower($line)) !== 'ja') {
    echo "Abgebrochen.\n";
    exit(0);
}

echo "\nLösche Dateien...\n";

$deleted = 0;
foreach ($toDelete as $file) {
    if (unlink($file)) {
        echo "  Gelöscht: " . basename($file) . "\n";
        $deleted++;
    } else {
        echo "  FEHLER: " . basename($file) . "\n";
    }
}

echo "\n";
echo "===========================================\n";
echo " FERTIG: {$deleted} Dateien gelöscht\n";
echo "===========================================\n";
echo "\n";
echo "Nächste Schritte:\n";
echo "1. php artisan migrate:fresh  (ACHTUNG: Löscht alle Daten!)\n";
echo "   ODER\n";
echo "   php artisan migrate        (Nur fehlende Tabellen erstellen)\n";
echo "\n";
