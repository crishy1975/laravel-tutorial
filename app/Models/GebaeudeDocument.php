<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GebaeudeDocument extends Model
{
    protected $table = 'gebaeude_dokumente';

    protected $fillable = [
        'gebaeude_id',
        'titel',
        'beschreibung',
        'kategorie',
        'dateiname',
        'original_name',
        'dateityp',
        'dateiendung',
        'dateigroesse',
        'pfad',
        'dokument_datum',
        'tags',
        'ist_wichtig',
        'ist_archiviert',
        'hochgeladen_von',
    ];

    protected $casts = [
        'dateigroesse'   => 'integer',
        'dokument_datum' => 'date',
        'ist_wichtig'    => 'boolean',
        'ist_archiviert' => 'boolean',
        'created_at'     => 'datetime',
        'updated_at'     => 'datetime',
    ];

    // ═══════════════════════════════════════════════════════════
    // 📁 KATEGORIEN
    // ═══════════════════════════════════════════════════════════

    public const KATEGORIEN = [
        'vertrag'   => 'Vertrag',
        'rechnung'  => 'Rechnung',
        'angebot'   => 'Angebot',
        'protokoll' => 'Protokoll',
        'foto'      => 'Foto',
        'plan'      => 'Plan/Grundriss',
        'korrespondenz' => 'Korrespondenz',
        'sonstiges' => 'Sonstiges',
    ];

    // ═══════════════════════════════════════════════════════════
    // 🔗 RELATIONSHIPS
    // ═══════════════════════════════════════════════════════════

    public function gebaeude(): BelongsTo
    {
        return $this->belongsTo(Gebaeude::class, 'gebaeude_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hochgeladen_von');
    }

    // ═══════════════════════════════════════════════════════════
    // 🎯 SCOPES
    // ═══════════════════════════════════════════════════════════

    public function scopeKategorie($query, string $kategorie)
    {
        return $query->where('kategorie', $kategorie);
    }

    public function scopeWichtig($query)
    {
        return $query->where('ist_wichtig', true);
    }

    public function scopeAktiv($query)
    {
        return $query->where('ist_archiviert', false);
    }

    public function scopeArchiviert($query)
    {
        return $query->where('ist_archiviert', true);
    }

    public function scopeBilder($query)
    {
        return $query->where('dateityp', 'like', 'image/%');
    }

    public function scopePdfs($query)
    {
        return $query->where('dateiendung', 'pdf');
    }

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;
        
        return $query->where(function($q) use ($term) {
            $q->where('titel', 'like', "%{$term}%")
              ->orWhere('beschreibung', 'like', "%{$term}%")
              ->orWhere('original_name', 'like', "%{$term}%")
              ->orWhere('tags', 'like', "%{$term}%");
        });
    }

    // ═══════════════════════════════════════════════════════════
    // 🏷️ ACCESSORS
    // ═══════════════════════════════════════════════════════════

    /**
     * Formatierte Dateigröße
     */
    public function getDateigroesseFormatiertAttribute(): string
    {
        $bytes = $this->dateigroesse;
        
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
     * Kategorie-Label
     */
    public function getKategorieLabelAttribute(): string
    {
        return self::KATEGORIEN[$this->kategorie] ?? $this->kategorie ?? 'Unbekannt';
    }

    /**
     * Icon basierend auf Dateityp
     */
    public function getIconAttribute(): string
    {
        $ext = strtolower($this->dateiendung);
        
        return match(true) {
            $ext === 'pdf' => 'bi-file-earmark-pdf text-danger',
            in_array($ext, ['doc', 'docx']) => 'bi-file-earmark-word text-primary',
            in_array($ext, ['xls', 'xlsx']) => 'bi-file-earmark-excel text-success',
            in_array($ext, ['ppt', 'pptx']) => 'bi-file-earmark-ppt text-warning',
            in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp']) => 'bi-file-earmark-image text-info',
            in_array($ext, ['zip', 'rar', '7z']) => 'bi-file-earmark-zip text-secondary',
            in_array($ext, ['txt', 'csv']) => 'bi-file-earmark-text text-muted',
            default => 'bi-file-earmark text-secondary',
        };
    }

    /**
     * Ist das Dokument ein Bild?
     */
    public function getIstBildAttribute(): bool
    {
        return Str::startsWith($this->dateityp, 'image/');
    }

    /**
     * Ist das Dokument ein PDF?
     */
    public function getIstPdfAttribute(): bool
    {
        return $this->dateiendung === 'pdf';
    }

    /**
     * Tags als Array
     */
    public function getTagsArrayAttribute(): array
    {
        if (!$this->tags) return [];
        
        return array_map('trim', explode(',', $this->tags));
    }

    /**
     * Vollständiger Storage-Pfad
     */
    public function getFullPathAttribute(): string
    {
        return Storage::disk('local')->path($this->pfad);
    }

    /**
     * Download-URL
     */
    public function getDownloadUrlAttribute(): string
    {
        return route('gebaeude.dokumente.download', $this->id);
    }

    /**
     * Preview-URL (für Bilder)
     */
    public function getPreviewUrlAttribute(): ?string
    {
        if (!$this->ist_bild) return null;
        
        return route('gebaeude.dokumente.preview', $this->id);
    }

    // ═══════════════════════════════════════════════════════════
    // 🧮 BUSINESS LOGIC
    // ═══════════════════════════════════════════════════════════

    /**
     * Datei existiert im Storage?
     */
    public function existiert(): bool
    {
        return Storage::disk('local')->exists($this->pfad);
    }

    /**
     * Datei löschen (inkl. Storage)
     */
    public function loeschenMitDatei(): bool
    {
        // Datei aus Storage löschen
        if ($this->existiert()) {
            Storage::disk('local')->delete($this->pfad);
        }
        
        // DB-Eintrag löschen
        return $this->delete();
    }

    /**
     * Als wichtig markieren
     */
    public function markiereWichtig(bool $wichtig = true): void
    {
        $this->update(['ist_wichtig' => $wichtig]);
    }

    /**
     * Archivieren
     */
    public function archivieren(bool $archiviert = true): void
    {
        $this->update(['ist_archiviert' => $archiviert]);
    }

    // ═══════════════════════════════════════════════════════════
    // 📊 STATIC HELPERS
    // ═══════════════════════════════════════════════════════════

    /**
     * Erlaubte Dateitypen
     */
    public static function erlaubteEndungen(): array
    {
        return [
            'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
            'txt', 'csv', 'rtf', 'odt', 'ods',
            'jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'tiff',
            'zip', 'rar', '7z',
        ];
    }

    /**
     * Max. Dateigröße in Bytes (20 MB)
     */
    public static function maxDateigroesse(): int
    {
        return 20 * 1024 * 1024;
    }

    /**
     * Generiert einen eindeutigen Dateinamen
     */
    public static function generateDateiname(string $originalName): string
    {
        $ext = pathinfo($originalName, PATHINFO_EXTENSION);
        return Str::uuid() . '.' . strtolower($ext);
    }

    /**
     * Generiert den Storage-Pfad
     */
    public static function generatePfad(int $gebaeudeId, string $dateiname): string
    {
        return "gebaeude-dokumente/{$gebaeudeId}/{$dateiname}";
    }
}
