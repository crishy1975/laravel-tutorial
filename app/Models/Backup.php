<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Backup extends Model
{
    protected $fillable = [
        'dateiname',
        'pfad',
        'groesse',
        'status',
        'erstellt_am',
        'heruntergeladen_am',
        'log',
        'fehler',
    ];

    protected $casts = [
        'erstellt_am' => 'datetime',
        'heruntergeladen_am' => 'datetime',
        'log' => 'array',
        'groesse' => 'integer',
    ];

    /**
     * Dateigröße formatiert
     */
    public function getGroesseFormatiertAttribute(): string
    {
        $bytes = $this->groesse;
        
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2, ',', '.') . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2, ',', '.') . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2, ',', '.') . ' KB';
        }
        
        return $bytes . ' Bytes';
    }

    /**
     * Prüft ob Datei noch existiert
     */
    public function existiert(): bool
    {
        return file_exists(storage_path('app/' . $this->pfad));
    }

    /**
     * Vollständiger Pfad zur Datei
     */
    public function getVollpfadAttribute(): string
    {
        return storage_path('app/' . $this->pfad);
    }

    /**
     * Tage seit Erstellung
     */
    public function getAlterInTagenAttribute(): int
    {
        return (int) round($this->erstellt_am->diffInDays(now()));
    }

    /**
     * Letztes heruntergeladenes Backup
     */
    public static function letztesHeruntergeladen(): ?self
    {
        return self::where('status', 'heruntergeladen')
            ->whereNotNull('heruntergeladen_am')
            ->latest('heruntergeladen_am')
            ->first();
    }

    /**
     * Tage seit letztem Download
     */
    public static function tageSeitDownload(): ?int
    {
        $letztes = self::letztesHeruntergeladen();
        
        if (!$letztes) {
            // Kein Download bisher - prüfe ältestes Backup
            $erstesBackup = self::oldest('erstellt_am')->first();
            if ($erstesBackup) {
                return (int) round($erstesBackup->erstellt_am->diffInDays(now()));
            }
            return null;
        }
        
        return (int) round($letztes->heruntergeladen_am->diffInDays(now()));
    }

    /**
     * Anzahl nicht heruntergeladener Backups
     */
    public static function nichtHeruntergeladen(): int
    {
        return self::where('status', 'erstellt')->count();
    }
}
