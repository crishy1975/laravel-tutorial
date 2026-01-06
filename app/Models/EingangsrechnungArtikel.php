<?php
/**
 * ════════════════════════════════════════════════════════════════════════════
 * DATEI: EingangsrechnungArtikel.php
 * PFAD:  app/Models/EingangsrechnungArtikel.php
 * ════════════════════════════════════════════════════════════════════════════
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EingangsrechnungArtikel extends Model
{
    use HasFactory;

    protected $table = 'eingangsrechnung_artikel';

    protected $fillable = [
        'eingangsrechnung_id',
        'zeile',
        'artikelcode',
        'beschreibung',
        'menge',
        'einheit',
        'einzelpreis',
        'gesamtpreis',
        'mwst_satz',
    ];

    protected $casts = [
        'zeile'        => 'integer',
        'menge'        => 'decimal:3',
        'einzelpreis'  => 'decimal:6',
        'gesamtpreis'  => 'decimal:2',
        'mwst_satz'    => 'decimal:2',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
    ];

    // ═══════════════════════════════════════════════════════════
    // 🔗 BEZIEHUNGEN
    // ═══════════════════════════════════════════════════════════

    /**
     * Zugehörige Rechnung
     */
    public function eingangsrechnung(): BelongsTo
    {
        return $this->belongsTo(Eingangsrechnung::class);
    }

    /**
     * Lieferant (über Rechnung)
     */
    public function lieferant(): BelongsTo
    {
        return $this->eingangsrechnung->lieferant();
    }

    // ═══════════════════════════════════════════════════════════
    // 🧮 BERECHNUNGEN
    // ═══════════════════════════════════════════════════════════

    /**
     * MwSt-Betrag für diese Position
     */
    public function getMwstBetragAttribute(): float
    {
        return round($this->gesamtpreis * ($this->mwst_satz / 100), 2);
    }

    /**
     * Brutto-Betrag für diese Position
     */
    public function getBruttoBetragAttribute(): float
    {
        return round($this->gesamtpreis + $this->mwst_betrag, 2);
    }

    /**
     * Einheit formatiert (Kurzform → Langform)
     */
    public function getEinheitFormatiertAttribute(): string
    {
        $einheiten = [
            'PZ'  => 'Stück',
            'L'   => 'Liter',
            'KG'  => 'kg',
            'M'   => 'Meter',
            'M2'  => 'm²',
            'M3'  => 'm³',
            'H'   => 'Stunden',
            'GG'  => 'Tage',
        ];

        return $einheiten[strtoupper($this->einheit ?? '')] ?? $this->einheit ?? '-';
    }

    /**
     * Beschreibung gekürzt (für Listen)
     */
    public function getBeschreibungKurzAttribute(): string
    {
        if (strlen($this->beschreibung) <= 50) {
            return $this->beschreibung;
        }

        return substr($this->beschreibung, 0, 47) . '...';
    }
}
