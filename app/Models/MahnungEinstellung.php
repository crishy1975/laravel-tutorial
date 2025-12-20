<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Konfigurationseinstellungen für das Mahnwesen
 * 
 * Schlüssel:
 * - zahlungsfrist_tage: Tage nach Rechnungsdatum bis Fälligkeit (Standard: 30)
 * - wartezeit_zwischen_mahnungen: Mindestabstand zwischen Mahnungen in Tagen (Standard: 10)
 * - min_tage_ueberfaellig: Mindestanzahl Tage überfällig vor erster Mahnung (Standard: 0)
 */
class MahnungEinstellung extends Model
{
    protected $table = 'mahnung_einstellungen';

    protected $fillable = [
        'schluessel',
        'wert',
        'beschreibung',
        'typ',
    ];

    // ═══════════════════════════════════════════════════════════════════════
    // STATISCHE HELPER
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Wert abrufen (mit Caching)
     */
    public static function get(string $schluessel, mixed $default = null): mixed
    {
        $cacheKey = "mahnung_einstellung_{$schluessel}";
        
        return Cache::remember($cacheKey, 3600, function () use ($schluessel, $default) {
            $einstellung = self::where('schluessel', $schluessel)->first();
            
            if (!$einstellung) {
                return $default;
            }

            return self::castWert($einstellung->wert, $einstellung->typ);
        });
    }

    /**
     * Wert setzen (und Cache leeren)
     */
    public static function set(string $schluessel, mixed $wert, ?string $beschreibung = null): void
    {
        self::updateOrCreate(
            ['schluessel' => $schluessel],
            [
                'wert' => (string) $wert,
                'beschreibung' => $beschreibung,
            ]
        );

        // Cache leeren
        Cache::forget("mahnung_einstellung_{$schluessel}");
    }

    /**
     * Wert zum richtigen Typ casten
     */
    protected static function castWert(string $wert, string $typ): mixed
    {
        return match ($typ) {
            'integer' => (int) $wert,
            'boolean' => filter_var($wert, FILTER_VALIDATE_BOOLEAN),
            'float'   => (float) $wert,
            default   => $wert,
        };
    }

    // ═══════════════════════════════════════════════════════════════════════
    // SPEZIFISCHE GETTER (Convenience-Methoden)
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Zahlungsfrist in Tagen
     */
    public static function getZahlungsfristTage(): int
    {
        return (int) self::get('zahlungsfrist_tage', 30);
    }

    /**
     * Wartezeit zwischen Mahnungen in Tagen
     */
    public static function getWartezeitZwischenMahnungen(): int
    {
        return (int) self::get('wartezeit_zwischen_mahnungen', 10);
    }

    /**
     * Mindestanzahl Tage überfällig vor erster Mahnung
     */
    public static function getMinTageUeberfaellig(): int
    {
        return (int) self::get('min_tage_ueberfaellig', 0);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // ALLE EINSTELLUNGEN
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Alle Einstellungen als Key-Value-Array
     */
    public static function alle(): array
    {
        return self::all()->mapWithKeys(function ($item) {
            return [$item->schluessel => self::castWert($item->wert, $item->typ)];
        })->toArray();
    }

    /**
     * Cache für alle Einstellungen leeren
     */
    public static function clearCache(): void
    {
        $einstellungen = self::pluck('schluessel');
        
        foreach ($einstellungen as $schluessel) {
            Cache::forget("mahnung_einstellung_{$schluessel}");
        }
    }
}
