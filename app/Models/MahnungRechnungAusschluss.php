<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class MahnungRechnungAusschluss extends Model
{
    protected $table = 'mahnung_rechnung_ausschluesse';

    protected $fillable = [
        'rechnung_id',
        'grund',
        'bis_datum',
        'aktiv',
    ];

    protected $casts = [
        'bis_datum' => 'date',
        'aktiv'     => 'boolean',
    ];

    // ═══════════════════════════════════════════════════════════════════════
    // RELATIONSHIPS
    // ═══════════════════════════════════════════════════════════════════════

    public function rechnung(): BelongsTo
    {
        return $this->belongsTo(Rechnung::class);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // SCOPES
    // ═══════════════════════════════════════════════════════════════════════

    public function scopeGueltig($query)
    {
        return $query->where('aktiv', true)
            ->where(function ($q) {
                $q->whereNull('bis_datum')
                  ->orWhere('bis_datum', '>=', now()->toDateString());
            });
    }

    // ═══════════════════════════════════════════════════════════════════════
    // STATIC HELPERS
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Prüft ob Rechnung vom Mahnlauf ausgeschlossen ist
     */
    public static function istAusgeschlossen(int $rechnungId): bool
    {
        return self::where('rechnung_id', $rechnungId)
            ->gueltig()
            ->exists();
    }

    /**
     * Holt alle ausgeschlossenen Rechnungs-IDs
     */
    public static function getAusgeschlosseneIds(): array
    {
        return self::gueltig()
            ->pluck('rechnung_id')
            ->toArray();
    }

    /**
     * Rechnung vom Mahnlauf ausschließen
     */
    public static function setAusschluss(
        int $rechnungId,
        ?string $grund = null,
        ?Carbon $bisDatum = null
    ): self {
        return self::updateOrCreate(
            ['rechnung_id' => $rechnungId],
            [
                'grund'     => $grund,
                'bis_datum' => $bisDatum,
                'aktiv'     => true,
            ]
        );
    }

    /**
     * Ausschluss entfernen
     */
    public static function entferneAusschluss(int $rechnungId): bool
    {
        return self::where('rechnung_id', $rechnungId)->delete() > 0;
    }

    // ═══════════════════════════════════════════════════════════════════════
    // ACCESSORS
    // ═══════════════════════════════════════════════════════════════════════

    public function getIstGueltigAttribute(): bool
    {
        if (!$this->aktiv) {
            return false;
        }

        if ($this->bis_datum === null) {
            return true;
        }

        return $this->bis_datum->gte(now()->startOfDay());
    }

    public function getGueltigkeitBadgeAttribute(): string
    {
        if (!$this->aktiv) {
            return '<span class="badge bg-secondary">Inaktiv</span>';
        }

        if ($this->bis_datum === null) {
            return '<span class="badge bg-danger">Unbegrenzt</span>';
        }

        if ($this->bis_datum->lt(now())) {
            return '<span class="badge bg-secondary">Abgelaufen</span>';
        }

        return '<span class="badge bg-warning text-dark">Bis ' . $this->bis_datum->format('d.m.Y') . '</span>';
    }
}
