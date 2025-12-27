<?php

namespace App\Console\Commands;

use App\Models\Backup;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class BackupCreate extends Command
{
    protected $signature = 'backup:create {--force : Backup auch erstellen wenn heute schon eines existiert}';
    protected $description = 'Erstellt ein Datenbank-Backup';

    public function handle(): int
    {
        $log = [];
        $log[] = ['zeit' => now()->format('H:i:s'), 'aktion' => 'Backup gestartet'];
        
        $this->info('🔄 Starte Datenbank-Backup...');

        // Prüfen ob heute schon ein Backup existiert
        if (!$this->option('force')) {
            $heuteBackup = Backup::whereDate('erstellt_am', today())->exists();
            if ($heuteBackup) {
                $this->warn('⚠️  Heute wurde bereits ein Backup erstellt. Nutze --force um trotzdem fortzufahren.');
                return Command::SUCCESS;
            }
        }

        // Backup-Verzeichnis erstellen
        $backupDir = storage_path('app/backups');
        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
            $log[] = ['zeit' => now()->format('H:i:s'), 'aktion' => 'Backup-Verzeichnis erstellt'];
        }

        // Dateiname generieren
        $timestamp = now()->format('Y-m-d_His');
        $dateiname = "backup_{$timestamp}.sql";
        $pfad = "backups/{$dateiname}";
        $vollpfad = storage_path("app/{$pfad}");

        // Backup-Eintrag erstellen
        $backup = Backup::create([
            'dateiname' => $dateiname,
            'pfad' => $pfad,
            'status' => 'erstellt',
            'erstellt_am' => now(),
            'log' => $log,
        ]);

        try {
            $dbName = config('database.connections.mysql.database');
            $log[] = ['zeit' => now()->format('H:i:s'), 'aktion' => "Datenbank: {$dbName}"];
            $this->info("📦 Datenbank: {$dbName}");

            $this->info('⏳ Exportiere Datenbank...');
            $log[] = ['zeit' => now()->format('H:i:s'), 'aktion' => 'PHP-Export wird ausgeführt...'];

            // SQL-Export mit PHP erstellen
            $sql = $this->exportDatabase();
            
            // SQL in Datei schreiben
            file_put_contents($vollpfad, $sql);
            
            if (!file_exists($vollpfad) || filesize($vollpfad) === 0) {
                throw new \Exception('Backup-Datei wurde nicht erstellt oder ist leer');
            }

            $groesse = filesize($vollpfad);
            $log[] = ['zeit' => now()->format('H:i:s'), 'aktion' => "Backup erstellt: " . $this->formatBytes($groesse)];

            // Komprimieren mit PHP (plattformunabhängig)
            $this->info('🗜️  Komprimiere Backup...');
            $gzPfad = $vollpfad . '.gz';
            
            try {
                $content = file_get_contents($vollpfad);
                $gzContent = gzencode($content, 9);
                file_put_contents($gzPfad, $gzContent);
                
                if (file_exists($gzPfad) && filesize($gzPfad) > 0) {
                    // Original löschen
                    unlink($vollpfad);
                    $dateiname .= '.gz';
                    $pfad .= '.gz';
                    $groesse = filesize($gzPfad);
                    $log[] = ['zeit' => now()->format('H:i:s'), 'aktion' => "Komprimiert: " . $this->formatBytes($groesse)];
                }
            } catch (\Exception $e) {
                // Komprimierung fehlgeschlagen, behalte unkomprimierte Datei
                $log[] = ['zeit' => now()->format('H:i:s'), 'aktion' => "Komprimierung übersprungen"];
            }

            // Backup-Eintrag aktualisieren
            $backup->update([
                'dateiname' => $dateiname,
                'pfad' => $pfad,
                'groesse' => $groesse,
                'status' => 'erstellt',
                'log' => $log,
            ]);

            $this->info("✅ Backup erfolgreich erstellt: {$dateiname}");
            $this->info("📁 Größe: " . $this->formatBytes($groesse));

            // Alte Backups aufräumen (älter als 30 Tage)
            $this->cleanupOldBackups($log);

            Log::info("Backup erstellt: {$dateiname}", ['groesse' => $groesse]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $log[] = ['zeit' => now()->format('H:i:s'), 'aktion' => 'FEHLER: ' . $e->getMessage()];
            
            $backup->update([
                'status' => 'fehlgeschlagen',
                'fehler' => $e->getMessage(),
                'log' => $log,
            ]);

            $this->error("❌ Backup fehlgeschlagen: {$e->getMessage()}");
            Log::error("Backup fehlgeschlagen", ['error' => $e->getMessage()]);

            return Command::FAILURE;
        }
    }

    /**
     * Datenbank mit PHP exportieren (ohne exec/mysqldump)
     */
    private function exportDatabase(): string
    {
        $sql = "-- Backup erstellt am " . now()->format('Y-m-d H:i:s') . "\n";
        $sql .= "-- PHP-basierter Export\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS=0;\n";
        $sql .= "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n\n";

        // Alle Tabellen holen
        $tables = DB::select('SHOW TABLES');
        $dbName = config('database.connections.mysql.database');
        $tableKey = "Tables_in_{$dbName}";

        foreach ($tables as $table) {
            $tableName = $table->$tableKey;
            $this->line("  → Exportiere: {$tableName}");

            // CREATE TABLE Statement
            $createTable = DB::select("SHOW CREATE TABLE `{$tableName}`");
            $sql .= "-- Tabelle: {$tableName}\n";
            $sql .= "DROP TABLE IF EXISTS `{$tableName}`;\n";
            $sql .= $createTable[0]->{'Create Table'} . ";\n\n";

            // Daten exportieren
            $rows = DB::table($tableName)->get();
            
            if ($rows->count() > 0) {
                $columns = array_keys((array) $rows->first());
                $columnList = '`' . implode('`, `', $columns) . '`';
                
                $sql .= "-- Daten für {$tableName}\n";
                
                // Batch-Insert für bessere Performance
                $batchSize = 100;
                $batches = $rows->chunk($batchSize);
                
                foreach ($batches as $batch) {
                    $values = [];
                    foreach ($batch as $row) {
                        $rowValues = [];
                        foreach ((array) $row as $value) {
                            if ($value === null) {
                                $rowValues[] = 'NULL';
                            } else {
                                $rowValues[] = "'" . addslashes($value) . "'";
                            }
                        }
                        $values[] = '(' . implode(', ', $rowValues) . ')';
                    }
                    $sql .= "INSERT INTO `{$tableName}` ({$columnList}) VALUES\n";
                    $sql .= implode(",\n", $values) . ";\n";
                }
                $sql .= "\n";
            }
        }

        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

        return $sql;
    }

    /**
     * Alte Backups löschen (älter als 30 Tage)
     */
    private function cleanupOldBackups(array &$log): void
    {
        $alteBackups = Backup::where('erstellt_am', '<', now()->subDays(30))
            ->where('status', '!=', 'fehlgeschlagen')
            ->get();

        $geloescht = 0;
        foreach ($alteBackups as $backup) {
            if ($backup->existiert()) {
                File::delete($backup->vollpfad);
            }
            $backup->delete();
            $geloescht++;
        }

        if ($geloescht > 0) {
            $log[] = ['zeit' => now()->format('H:i:s'), 'aktion' => "{$geloescht} alte Backups gelöscht"];
            $this->info("🗑️  {$geloescht} alte Backups gelöscht (älter als 30 Tage)");
        }
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' Bytes';
    }
}
