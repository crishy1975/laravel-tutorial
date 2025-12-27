<?php

namespace App\Console\Commands;

use App\Models\Backup;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

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
            // Datenbank-Konfiguration holen
            $connection = config('database.default');
            $config = config("database.connections.{$connection}");

            $log[] = ['zeit' => now()->format('H:i:s'), 'aktion' => "Datenbank: {$config['database']}"];
            $this->info("📦 Datenbank: {$config['database']}");

            // mysqldump Befehl zusammenbauen
            $command = sprintf(
                'mysqldump --user=%s --password=%s --host=%s --port=%s %s > %s 2>&1',
                escapeshellarg($config['username']),
                escapeshellarg($config['password']),
                escapeshellarg($config['host']),
                escapeshellarg($config['port'] ?? 3306),
                escapeshellarg($config['database']),
                escapeshellarg($vollpfad)
            );

            $log[] = ['zeit' => now()->format('H:i:s'), 'aktion' => 'mysqldump wird ausgeführt...'];
            $this->info('⏳ Exportiere Datenbank...');

            // Backup ausführen
            $output = [];
            $returnCode = 0;
            exec($command, $output, $returnCode);

            if ($returnCode !== 0) {
                throw new \Exception('mysqldump fehlgeschlagen: ' . implode("\n", $output));
            }

            // Prüfen ob Datei existiert und Größe hat
            if (!file_exists($vollpfad) || filesize($vollpfad) === 0) {
                throw new \Exception('Backup-Datei wurde nicht erstellt oder ist leer');
            }

            $groesse = filesize($vollpfad);
            $log[] = ['zeit' => now()->format('H:i:s'), 'aktion' => "Backup erstellt: " . $this->formatBytes($groesse)];

            // Komprimieren mit gzip
            $this->info('🗜️  Komprimiere Backup...');
            $gzipCommand = sprintf('gzip -f %s', escapeshellarg($vollpfad));
            exec($gzipCommand, $output, $returnCode);

            if ($returnCode === 0 && file_exists($vollpfad . '.gz')) {
                $dateiname .= '.gz';
                $pfad .= '.gz';
                $groesse = filesize($vollpfad . '.gz');
                $log[] = ['zeit' => now()->format('H:i:s'), 'aktion' => "Komprimiert: " . $this->formatBytes($groesse)];
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
