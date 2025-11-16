<?php
// app/Models/RechnungPosition.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RechnungPosition extends Model
{
    use HasFactory;

    protected $table = 'rechnung_positionen';

    protected $fillable = [
        'rechnung_id',
        'artikel_gebaeude_id',
        'position',
        'beschreibung',
        'anzahl',
        'einheit',
        'einzelpreis',
        'mwst_satz',
        'netto_gesamt',
        'mwst_betrag',
        'brutto_gesamt',
    ];

    protected $casts = [
        'anzahl'          => 'decimal:2',
        'einzelpreis'     => 'decimal:2',
        'netto_gesamt'    => 'decimal:2',
        'mwst_satz'       => 'decimal:2',
        'mwst_betrag'     => 'decimal:2',
        'brutto_gesamt'   => 'decimal:2',
    ];

    // ═══════════════════════════════════════════════════════════
    // 🔗 RELATIONSHIPS
    // ═══════════════════════════════════════════════════════════

    /**
     * Gehört zu einer Rechnung
     */
    public function rechnung(): BelongsTo
    {
        return $this->belongsTo(Rechnung::class);
    }

    /**
     * Optional: Referenz zum Original-Artikel
     */
    public function artikelGebaeude(): BelongsTo
    {
        return $this->belongsTo(ArtikelGebaeude::class);
    }

    // ═══════════════════════════════════════════════════════════
    // 🧮 OBSERVERS / EVENTS
    // ═══════════════════════════════════════════════════════════

    /**
     * Model Events (Boot-Methode)
     */
    protected static function booted(): void
    {
        // Automatisch Beträge berechnen VOR dem Speichern
        static::saving(function (RechnungPosition $position) {
            $position->calculateAmounts();
        });

        // Nach dem Speichern: Rechnung neu berechnen
        static::saved(function (RechnungPosition $position) {
            $position->rechnung?->recalculate();
        });

        // Nach dem Löschen: Rechnung neu berechnen
        static::deleted(function (RechnungPosition $position) {
            $position->rechnung?->recalculate();
        });
    }

    // ═══════════════════════════════════════════════════════════
    // 🧮 BERECHNUNGEN
    // ═══════════════════════════════════════════════════════════

    /**
     * Berechnet netto_gesamt, mwst_betrag, brutto_gesamt
     * basierend auf anzahl, einzelpreis, mwst_satz
     */
    public function calculateAmounts(): void
    {
        // Netto = Anzahl × Einzelpreis
        $this->netto_gesamt = round(
            (float)$this->anzahl * (float)$this->einzelpreis,
            2
        );

        // MwSt-Betrag = Netto × (MwSt-Satz / 100)
        $this->mwst_betrag = round(
            $this->netto_gesamt * ((float)$this->mwst_satz / 100),
            2
        );

        // Brutto = Netto + MwSt
        $this->brutto_gesamt = round(
            $this->netto_gesamt + $this->mwst_betrag,
            2
        );
    }

    // ═══════════════════════════════════════════════════════════
    // 🏷️ ACCESSORS (optional, für UI)
    // ═══════════════════════════════════════════════════════════

    /**
     * Formatierter Netto-Betrag (für Blade-Views)
     * 
     * @return string z.B. "55,00 €"
     */
    public function getNettoFormattertAttribute(): string
    {
        return number_format($this->netto_gesamt, 2, ',', '.') . ' €';
    }

    /**
     * Formatierter Brutto-Betrag
     * 
     * @return string z.B. "67,10 €"
     */
    public function getBruttoFormattertAttribute(): string
    {
        return number_format($this->brutto_gesamt, 2, ',', '.') . ' €';
    }
}