<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class MahnungAusschluss extends Model
{
    protected $table = 'mahnung_ausschluesse';

    protected $fillable = [
        'adresse_id',
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

    public function adresse(): BelongsTo
    {
        return $this->belongsTo(Adresse::class);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // SCOPES
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Nur aktuell gültige Ausschlüsse
     */
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
     * Prüft ob Adresse ausgeschlossen ist
     */
    public static function istAusgeschlossen(int $adresseId): bool
    {
        return self::where('adresse_id', $adresseId)
            ->gueltig()
            ->exists();
    }

    /**
     * Holt alle ausgeschlossenen Adress-IDs
     */
    public static function getAusgeschlosseneIds(): array
    {
        return self::gueltig()
            ->pluck('adresse_id')
            ->toArray();
    }

    /**
     * Ausschluss für Adresse setzen
     */
    public static function setAusschluss(
        int $adresseId,
        ?string $grund = null,
        ?Carbon $bisDatum = null
    ): self {
        return self::updateOrCreate(
            ['adresse_id' => $adresseId],
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
    public static function entferneAusschluss(int $adresseId): bool
    {
        return self::where('adresse_id', $adresseId)->delete() > 0;
    }

    // ═══════════════════════════════════════════════════════════════════════
    // ACCESSORS
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Ist aktuell gültig?
     */
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

    /**
     * Gültigkeits-Badge
     */
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
