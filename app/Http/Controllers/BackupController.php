<?php

namespace App\Http\Controllers;

use App\Models\Backup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupController extends Controller
{
    /**
     * Backup-Übersicht
     */
    public function index()
    {
        $backups = Backup::orderBy('erstellt_am', 'desc')->paginate(20);
        
        // Statistiken
        $stats = [
            'gesamt' => Backup::count(),
            'nicht_heruntergeladen' => Backup::nichtHeruntergeladen(),
            'tage_seit_download' => Backup::tageSeitDownload(),
            'speicherplatz' => Backup::where('status', '!=', 'fehlgeschlagen')->sum('groesse'),
        ];
        
        return view('backup.index', compact('backups', 'stats'));
    }

    /**
     * Backup manuell erstellen
     */
    public function create(Request $request)
    {
        try {
            Artisan::call('backup:create', ['--force' => true]);
            $output = Artisan::output();
            
            return response()->json([
                'ok' => true,
                'message' => 'Backup wurde erstellt',
                'output' => $output,
            ]);
        } catch (\Exception $e) {
            Log::error('Manuelles Backup fehlgeschlagen', ['error' => $e->getMessage()]);
            
            return response()->json([
                'ok' => false,
                'message' => 'Backup fehlgeschlagen: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Backup herunterladen
     */
    public function download(Backup $backup): BinaryFileResponse
    {
        if (!$backup->existiert()) {
            abort(404, 'Backup-Datei nicht gefunden');
        }

        // Status aktualisieren
        $log = $backup->log ?? [];
        $log[] = ['zeit' => now()->format('H:i:s'), 'aktion' => 'Heruntergeladen'];
        
        $backup->update([
            'status' => 'heruntergeladen',
            'heruntergeladen_am' => now(),
            'log' => $log,
        ]);

        Log::info("Backup heruntergeladen: {$backup->dateiname}");

        return response()->download(
            $backup->vollpfad,
            $backup->dateiname,
            ['Content-Type' => 'application/gzip']
        );
    }

    /**
     * Backup löschen
     */
    public function destroy(Backup $backup)
    {
        $dateiname = $backup->dateiname;
        
        // Datei löschen wenn vorhanden
        if ($backup->existiert()) {
            File::delete($backup->vollpfad);
        }
        
        $backup->delete();
        
        Log::info("Backup gelöscht: {$dateiname}");

        if (request()->wantsJson()) {
            return response()->json(['ok' => true, 'message' => 'Backup gelöscht']);
        }

        return redirect()->route('backup.index')
            ->with('success', "Backup '{$dateiname}' wurde gelöscht");
    }

    /**
     * Backup-Log anzeigen (AJAX)
     */
    public function log(Backup $backup)
    {
        return response()->json([
            'ok' => true,
            'log' => $backup->log ?? [],
            'fehler' => $backup->fehler,
        ]);
    }

    /**
     * Alle heruntergeladenen Backups vom Server löschen
     */
    public function cleanup()
    {
        $heruntergeladen = Backup::where('status', 'heruntergeladen')->get();
        $geloescht = 0;
        
        foreach ($heruntergeladen as $backup) {
            if ($backup->existiert()) {
                File::delete($backup->vollpfad);
            }
            $backup->delete();
            $geloescht++;
        }

        Log::info("Cleanup: {$geloescht} heruntergeladene Backups gelöscht");

        return response()->json([
            'ok' => true,
            'message' => "{$geloescht} heruntergeladene Backups vom Server gelöscht",
        ]);
    }
}
