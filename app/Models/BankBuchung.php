<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class BankBuchung extends Model
{
    protected $table = 'bank_buchungen';

    protected $fillable = [
        'import_datei',
        'import_hash',
        'import_datum',
        'iban',
        'konto_name',
        'ntry_ref',
        'msg_id',
        'betrag',
        'waehrung',
        'typ',
        'buchungsdatum',
        'valutadatum',
        'tx_code',
        'tx_issuer',
        'gegenkonto_name',
        'gegenkonto_iban',
        'verwendungszweck',
        'rechnung_id',
        'match_status',
        'match_info',
        'matched_at',
        'bemerkung',
    ];

    protected $casts = [
        'betrag'         => 'decimal:2',
        'buchungsdatum'  => 'date',
        'valutadatum'    => 'date',
        'import_datum'   => 'datetime',
        'matched_at'     => 'datetime',
    ];

    // =========================================================================
    // BEZIEHUNGEN
    // =========================================================================

    public function rechnung(): BelongsTo
    {
        return $this->belongsTo(Rechnung::class);
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Nur Eingaenge (Zahlungen)
     */
    public function scopeEingaenge($query)
    {
        return $query->where('typ', 'CRDT');
    }

    /**
     * Nur Ausgaenge
     */
    public function scopeAusgaenge($query)
    {
        return $query->where('typ', 'DBIT');
    }

    /**
     * Nicht zugeordnete Buchungen
     */
    public function scopeUnmatched($query)
    {
        return $query->where('match_status', 'unmatched');
    }

    /**
     * Zugeordnete Buchungen
     */
    public function scopeMatched($query)
    {
        return $query->whereIn('match_status', ['matched', 'manual']);
    }

    /**
     * Nach Zeitraum filtern
     */
    public function scopeZeitraum($query, $von, $bis)
    {
        if ($von) {
            $query->where('buchungsdatum', '>=', $von);
        }
        if ($bis) {
            $query->where('buchungsdatum', '<=', $bis);
        }
        return $query;
    }

    // =========================================================================
    // ACCESSORS
    // =========================================================================

    /**
     * Betrag mit Vorzeichen (+ fuer Eingang, - fuer Ausgang)
     */
    public function getBetragMitVorzeichenAttribute(): float
    {
        return $this->typ === 'CRDT' ? (float) $this->betrag : -abs((float) $this->betrag);
    }

    /**
     * Formatierter Betrag
     */
    public function getBetragFormatAttribute(): string
    {
        $betrag = $this->betrag_mit_vorzeichen;
        $prefix = $betrag >= 0 ? '+' : '';
        return $prefix . number_format($betrag, 2, ',', '.') . ' €';
    }

    /**
     * Typ als deutscher Text
     */
    public function getTypLabelAttribute(): string
    {
        return $this->typ === 'CRDT' ? 'Eingang' : 'Ausgang';
    }

    /**
     * Typ als Badge HTML
     */
    public function getTypBadgeAttribute(): string
    {
        if ($this->typ === 'CRDT') {
            return '<span class="badge bg-success">Eingang</span>';
        }
        return '<span class="badge bg-danger">Ausgang</span>';
    }

    /**
     * Match-Status als Badge
     */
    public function getMatchStatusBadgeAttribute(): string
    {
        return match ($this->match_status) {
            'matched' => '<span class="badge bg-success"><i class="bi bi-check-circle"></i> Zugeordnet</span>',
            'manual'  => '<span class="badge bg-info"><i class="bi bi-hand-index"></i> Manuell</span>',
            'partial' => '<span class="badge bg-warning text-dark"><i class="bi bi-exclamation-circle"></i> Teilweise</span>',
            'ignored' => '<span class="badge bg-secondary"><i class="bi bi-x-circle"></i> Ignoriert</span>',
            default   => '<span class="badge bg-light text-dark"><i class="bi bi-question-circle"></i> Offen</span>',
        };
    }

    /**
     * Transaktionscode lesbar
     */
    public function getTxCodeLabelAttribute(): string
    {
        // CBI Transaktionscodes
        $codes = [
            '48//26' => 'Überweisung Eingang',
            '26//C9' => 'Überweisung Ausgang',
            '43//93' => 'POS-Zahlung',
            '43//CD' => 'POS Ausland',
            '05//'   => 'Lastschrift',
            '09//'   => 'Dauerauftrag',
        ];

        foreach ($codes as $code => $label) {
            if (str_starts_with($this->tx_code ?? '', $code) || $this->tx_code === $code) {
                return $label;
            }
        }

        return $this->tx_code ?? 'Unbekannt';
    }

    // =========================================================================
    // STATISCHE METHODEN
    // =========================================================================

    /**
     * Generiert einen eindeutigen Hash fuer Duplikat-Erkennung
     */
    public static function generateHash(
        string $iban,
        string $buchungsdatum,
        string $betrag,
        string $typ,
        string $verwendungszweck
    ): string {
        return md5(implode('|', [
            $iban,
            $buchungsdatum,
            $betrag,
            $typ,
            substr($verwendungszweck, 0, 100),
        ]));
    }

    /**
     * Prueft ob Buchung bereits existiert
     */
    public static function existsByHash(string $hash): bool
    {
        return self::where('import_hash', $hash)->exists();
    }
}
