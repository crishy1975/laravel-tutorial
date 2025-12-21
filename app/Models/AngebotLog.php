<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class AngebotLog extends Model
{
    protected $table = 'angebot_logs';

    protected $fillable = [
        'angebot_id',
        'user_id',
        'typ',
        'titel',
        'nachricht',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    // ═══════════════════════════════════════════════════════════════════════
    // 🔗 RELATIONSHIPS
    // ═══════════════════════════════════════════════════════════════════════

    public function angebot(): BelongsTo
    {
        return $this->belongsTo(Angebot::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // 🏷️ ACCESSORS
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Icon für Log-Typ
     */
    public function getIconAttribute(): string
    {
        return match($this->typ) {
            'erstellt'        => 'bi-plus-circle text-success',
            'bearbeitet'      => 'bi-pencil text-primary',
            'versendet'       => 'bi-envelope text-info',
            'umgewandelt'     => 'bi-arrow-right-circle text-success',
            'status_geaendert' => 'bi-flag text-warning',
            'pdf_erstellt'    => 'bi-file-pdf text-danger',
            'geloescht'       => 'bi-trash text-danger',
            default           => 'bi-info-circle text-secondary',
        };
    }

    /**
     * Benutzername oder "System"
     */
    public function getUserNameAttribute(): string
    {
        return $this->user?->name ?? 'System';
    }

    // ═══════════════════════════════════════════════════════════════════════
    // 📝 STATIC HELPERS
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Einfaches Logging
     */
    public static function log(
        int $angebotId,
        string $typ,
        string $titel,
        ?string $nachricht = null,
        ?array $metadata = null
    ): self {
        return static::create([
            'angebot_id' => $angebotId,
            'user_id'    => Auth::id(),
            'typ'        => $typ,
            'titel'      => $titel,
            'nachricht'  => $nachricht,
            'metadata'   => $metadata,
        ]);
    }

    /**
     * Angebot erstellt
     */
    public static function erstellt(int $angebotId, ?string $quelle = null): self
    {
        return static::log($angebotId, 'erstellt', 'Angebot erstellt', $quelle);
    }

    /**
     * Angebot bearbeitet
     */
    public static function bearbeitet(int $angebotId, ?string $details = null): self
    {
        return static::log($angebotId, 'bearbeitet', 'Angebot bearbeitet', $details);
    }

    /**
     * Angebot versendet
     */
    public static function versendet(int $angebotId, string $email): self
    {
        return static::log($angebotId, 'versendet', 'Angebot versendet', 'Per E-Mail an ' . $email);
    }

    /**
     * PDF erstellt
     */
    public static function pdfErstellt(int $angebotId): self
    {
        return static::log($angebotId, 'pdf_erstellt', 'PDF generiert');
    }
}
