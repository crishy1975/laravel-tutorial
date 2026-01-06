<?php
/**
 * ════════════════════════════════════════════════════════════════════════════
 * DATEI: Eingangsrechnung.php
 * PFAD:  app/Models/Eingangsrechnung.php
 * ════════════════════════════════════════════════════════════════════════════
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Eingangsrechnung extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'eingangsrechnungen';

    protected $fillable = [
        'lieferant_id',
        'dateiname',
        'tipo_documento',
        'rechnungsnummer',
        'rechnungsdatum',
        'faelligkeitsdatum',
        'netto_betrag',
        'mwst_betrag',
        'brutto_betrag',
        'modalita_pagamento',
        'modalita_pagamento_text',
        'status',
        'zahlungsmethode',
        'bezahlt_am',
        'notiz',
        'xml_data',
    ];

    protected $casts = [
        'rechnungsdatum'    => 'date',
        'faelligkeitsdatum' => 'date',
        'bezahlt_am'        => 'date',
        'netto_betrag'      => 'decimal:2',
        'mwst_betrag'       => 'decimal:2',
        'brutto_betrag'     => 'decimal:2',
        'xml_data'          => 'array',
        'created_at'        => 'datetime',
        'updated_at'        => 'datetime',
        'deleted_at'        => 'datetime',
    ];

    // ═══════════════════════════════════════════════════════════
    // 📋 KONSTANTEN - Dokumenttypen (FatturaPA)
    // ═══════════════════════════════════════════════════════════

    public const TIPO_DOCUMENTO = [
        'TD01' => 'Fattura (Rechnung)',
        'TD02' => 'Acconto/Anticipo su fattura',
        'TD03' => 'Acconto/Anticipo su parcella',
        'TD04' => 'Nota di credito (Gutschrift)',
        'TD05' => 'Nota di debito (Belastung)',
        'TD06' => 'Parcella',
        'TD16' => 'Integrazione fattura reverse charge',
        'TD17' => 'Integrazione/autofattura acquisti servizi estero',
        'TD18' => 'Integrazione acquisti beni intracomunitari',
        'TD19' => 'Integrazione/autofattura acquisti beni ex art.17',
        'TD20' => 'Autofattura per regolarizzazione',
        'TD21' => 'Autofattura per splafonamento',
        'TD22' => 'Estrazione beni da Deposito IVA',
        'TD23' => 'Estrazione beni da Deposito IVA con versamento IVA',
        'TD24' => 'Fattura differita art.21 c.4 lett. a',
        'TD25' => 'Fattura differita art.21 c.4 lett. b',
        'TD26' => 'Cessione di beni ammortizzabili',
        'TD27' => 'Fattura per autoconsumo',
    ];

    // ═══════════════════════════════════════════════════════════
    // 📋 KONSTANTEN - Zahlungsarten (FatturaPA)
    // ═══════════════════════════════════════════════════════════

    public const MODALITA_PAGAMENTO = [
        'MP01' => 'Contanti (Bar)',
        'MP02' => 'Assegno',
        'MP03' => 'Assegno circolare',
        'MP04' => 'Contanti presso Tesoreria',
        'MP05' => 'Bonifico (Überweisung)',
        'MP06' => 'Vaglia cambiario',
        'MP07' => 'Bollettino bancario',
        'MP08' => 'Carta di pagamento (Karte)',
        'MP09' => 'RID',
        'MP10' => 'RID utenze',
        'MP11' => 'RID veloce',
        'MP12' => 'RIBA',
        'MP13' => 'MAV',
        'MP14' => 'Quietanza erario',
        'MP15' => 'Giroconto su conti di contabilità speciale',
        'MP16' => 'Domiciliazione bancaria',
        'MP17' => 'Domiciliazione postale',
        'MP18' => 'Bollettino di c/c postale',
        'MP19' => 'SEPA Direct Debit',
        'MP20' => 'SEPA Direct Debit CORE',
        'MP21' => 'SEPA Direct Debit B2B',
        'MP22' => 'Trattenuta su somme già riscosse',
        'MP23' => 'PagoPA',
    ];

    /**
     * Mapping: Modalita Pagamento → Zahlungsmethode
     */
    public const MODALITA_TO_METHODE = [
        'MP01' => 'bar',
        'MP02' => 'bank',
        'MP03' => 'bank',
        'MP04' => 'bar',
        'MP05' => 'bank',
        'MP06' => 'bank',
        'MP07' => 'bank',
        'MP08' => 'karte',
        'MP09' => 'bank',
        'MP10' => 'bank',
        'MP11' => 'bank',
        'MP12' => 'bank',
        'MP13' => 'bank',
        'MP14' => 'bank',
        'MP15' => 'bank',
        'MP16' => 'bank',
        'MP17' => 'bank',
        'MP18' => 'bank',
        'MP19' => 'bank',
        'MP20' => 'bank',
        'MP21' => 'bank',
        'MP22' => 'bank',
        'MP23' => 'bank',
    ];

    // ═══════════════════════════════════════════════════════════
    // 🔗 BEZIEHUNGEN
    // ═══════════════════════════════════════════════════════════

    /**
     * Lieferant der Rechnung
     */
    public function lieferant(): BelongsTo
    {
        return $this->belongsTo(Lieferant::class);
    }

    /**
     * Artikel/Positionen der Rechnung
     */
    public function artikel(): HasMany
    {
        return $this->hasMany(EingangsrechnungArtikel::class)
            ->orderBy('zeile');
    }

    // ═══════════════════════════════════════════════════════════
    // 🔍 SCOPES
    // ═══════════════════════════════════════════════════════════

    /**
     * Nur offene Rechnungen
     */
    public function scopeOffen($query)
    {
        return $query->where('status', 'offen');
    }

    /**
     * Nur bezahlte Rechnungen
     */
    public function scopeBezahlt($query)
    {
        return $query->where('status', 'bezahlt');
    }

    /**
     * Nur ignorierte Rechnungen
     */
    public function scopeIgnoriert($query)
    {
        return $query->where('status', 'ignoriert');
    }

    /**
     * Überfällige Rechnungen (offen + Fälligkeit überschritten)
     */
    public function scopeUeberfaellig($query)
    {
        return $query->where('status', 'offen')
            ->whereNotNull('faelligkeitsdatum')
            ->where('faelligkeitsdatum', '<', now());
    }

    /**
     * Rechnungen eines bestimmten Zeitraums
     */
    public function scopeZeitraum($query, Carbon $von, Carbon $bis)
    {
        return $query->whereBetween('rechnungsdatum', [$von, $bis]);
    }

    /**
     * Rechnungen eines Monats
     */
    public function scopeMonat($query, int $monat, int $jahr)
    {
        return $query->whereYear('rechnungsdatum', $jahr)
            ->whereMonth('rechnungsdatum', $monat);
    }

    // ═══════════════════════════════════════════════════════════
    // 🛠️ HILFSMETHODEN
    // ═══════════════════════════════════════════════════════════

    /**
     * Rechnung als bezahlt markieren
     */
    public function markiereAlsBezahlt(?string $methode = null, ?Carbon $datum = null): bool
    {
        $this->status = 'bezahlt';
        $this->bezahlt_am = $datum ?? now();
        
        if ($methode) {
            $this->zahlungsmethode = $methode;
        } elseif (!$this->zahlungsmethode && $this->modalita_pagamento) {
            // Automatisch aus Modalita Pagamento ableiten
            $this->zahlungsmethode = self::MODALITA_TO_METHODE[$this->modalita_pagamento] ?? 'bank';
        }

        return $this->save();
    }

    /**
     * Rechnung als ignoriert markieren
     */
    public function markiereAlsIgnoriert(): bool
    {
        $this->status = 'ignoriert';
        return $this->save();
    }

    /**
     * Rechnung wieder öffnen
     */
    public function wiederOeffnen(): bool
    {
        $this->status = 'offen';
        $this->bezahlt_am = null;
        $this->zahlungsmethode = null;
        return $this->save();
    }

    /**
     * Ist die Rechnung offen?
     */
    public function istOffen(): bool
    {
        return $this->status === 'offen';
    }

    /**
     * Ist die Rechnung bezahlt?
     */
    public function istBezahlt(): bool
    {
        return $this->status === 'bezahlt';
    }

    /**
     * Ist die Rechnung überfällig?
     */
    public function istUeberfaellig(): bool
    {
        return $this->status === 'offen' 
            && $this->faelligkeitsdatum 
            && $this->faelligkeitsdatum->lt(now());
    }

    /**
     * Ist es eine Gutschrift (Nota di credito)?
     */
    public function istGutschrift(): bool
    {
        return in_array($this->tipo_documento, ['TD04', 'TD05']);
    }

    /**
     * Dokumenttyp als lesbare Bezeichnung
     */
    public function getTipoDocumentoTextAttribute(): string
    {
        return self::TIPO_DOCUMENTO[$this->tipo_documento ?? 'TD01'] ?? 'Rechnung';
    }

    /**
     * Kurze Dokumenttyp-Bezeichnung (Rechnung/Gutschrift)
     */
    public function getDokumenttypAttribute(): string
    {
        return $this->istGutschrift() ? 'Gutschrift' : 'Rechnung';
    }

    /**
     * Tage bis Fälligkeit (negativ = überfällig)
     */
    public function getTagebisFaelligAttribute(): ?int
    {
        if (!$this->faelligkeitsdatum) {
            return null;
        }

        return now()->startOfDay()->diffInDays($this->faelligkeitsdatum, false);
    }

    /**
     * Lesbare Bezeichnung der Zahlungsart aus XML
     */
    public function getModalitaPagamentoTextAttribute(): string
    {
        if (!$this->modalita_pagamento) {
            return '-';
        }

        return self::MODALITA_PAGAMENTO[$this->modalita_pagamento] 
            ?? $this->modalita_pagamento;
    }

    /**
     * Status als Badge-Klasse (für Bootstrap)
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'offen'     => $this->istUeberfaellig() ? 'bg-danger' : 'bg-warning text-dark',
            'bezahlt'   => 'bg-success',
            'ignoriert' => 'bg-secondary',
            default     => 'bg-light text-dark',
        };
    }

    /**
     * Status als Icon (Bootstrap Icons)
     */
    public function getStatusIconAttribute(): string
    {
        return match ($this->status) {
            'offen'     => $this->istUeberfaellig() ? 'bi-exclamation-triangle-fill' : 'bi-hourglass-split',
            'bezahlt'   => 'bi-check-circle-fill',
            'ignoriert' => 'bi-dash-circle',
            default     => 'bi-question-circle',
        };
    }

    /**
     * Zahlungsmethode als Icon
     */
    public function getZahlungsmethodeIconAttribute(): string
    {
        return match ($this->zahlungsmethode) {
            'bank'  => 'bi-bank',
            'karte' => 'bi-credit-card',
            'bar'   => 'bi-cash-stack',
            default => 'bi-question-circle',
        };
    }
}
