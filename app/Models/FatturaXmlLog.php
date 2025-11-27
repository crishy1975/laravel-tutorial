<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;

/**
 * FatturaPA XML Log
 * 
 * Tracking-System für generierte XML-Dateien und deren Status.
 * Speichert Generierung, Validierung, Versand und SDI-Responses.
 */
class FatturaXmlLog extends Model
{
    protected $table = 'fattura_xml_logs';

    protected $fillable = [
        'rechnung_id',
        'progressivo_invio',
        'formato_trasmissione',
        'codice_destinatario',
        'pec_destinatario',
        'xml_file_path',
        'xml_filename',
        'xml_file_size',
        'p7m_file_path',
        'p7m_filename',
        'xml_content',
        'status',
        'status_detail',
        'sdi_status_code',
        'generated_at',
        'signed_at',
        'sent_at',
        'delivered_at',
        'finalized_at',
        'is_valid',
        'validation_errors',
        'error_message',
        'error_details',
        'retry_count',
        'sdi_ricevuta',
        'sdi_notifiche',
        'sdi_last_message',
        'sdi_last_check_at',
        'notizen',
    ];

    protected $casts = [
        'generated_at'      => 'datetime',
        'signed_at'         => 'datetime',
        'sent_at'           => 'datetime',
        'delivered_at'      => 'datetime',
        'finalized_at'      => 'datetime',
        'sdi_last_check_at' => 'datetime',
        'is_valid'          => 'boolean',
        'validation_errors' => 'array',
        'sdi_notifiche'     => 'array',
        'retry_count'       => 'integer',
        'xml_file_size'     => 'integer',
    ];

    // ═══════════════════════════════════════════════════════════
    // 📊 STATUS CONSTANTS
    // ═══════════════════════════════════════════════════════════

    const STATUS_PENDING   = 'pending';    // Wartend auf Generierung
    const STATUS_GENERATED = 'generated';  // XML erfolgreich generiert
    const STATUS_SIGNED    = 'signed';     // Digital signiert
    const STATUS_SENT      = 'sent';       // An SDI gesendet
    const STATUS_DELIVERED = 'delivered';  // Von SDI empfangen
    const STATUS_ACCEPTED  = 'accepted';   // Vom Empfänger akzeptiert
    const STATUS_REJECTED  = 'rejected';   // Vom Empfänger abgelehnt
    const STATUS_ERROR     = 'error';      // Fehler

    // ═══════════════════════════════════════════════════════════
    // 🔗 RELATIONSHIPS
    // ═══════════════════════════════════════════════════════════

    public function rechnung(): BelongsTo
    {
        return $this->belongsTo(Rechnung::class);
    }

    // ═══════════════════════════════════════════════════════════
    // 🎯 SCOPES
    // ═══════════════════════════════════════════════════════════

    /**
     * Nur generierte XMLs
     */
    public function scopeGenerated($query)
    {
        return $query->where('status', self::STATUS_GENERATED);
    }

    /**
     * Nur gesendete XMLs
     */
    public function scopeSent($query)
    {
        return $query->where('status', self::STATUS_SENT);
    }

    /**
     * Nur akzeptierte XMLs
     */
    public function scopeAccepted($query)
    {
        return $query->where('status', self::STATUS_ACCEPTED);
    }

    /**
     * Nur abgelehnte XMLs
     */
    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    /**
     * Nur Fehler
     */
    public function scopeErrors($query)
    {
        return $query->where('status', self::STATUS_ERROR);
    }

    /**
     * Ausstehende (noch nicht gesendet)
     */
    public function scopePending($query)
    {
        return $query->whereIn('status', [
            self::STATUS_PENDING,
            self::STATUS_GENERATED,
            self::STATUS_SIGNED,
        ]);
    }

    /**
     * Abgeschlossen (akzeptiert oder abgelehnt)
     */
    public function scopeFinalized($query)
    {
        return $query->whereIn('status', [
            self::STATUS_ACCEPTED,
            self::STATUS_REJECTED,
        ]);
    }

    // ═══════════════════════════════════════════════════════════
    // 📁 FILE OPERATIONS
    // ═══════════════════════════════════════════════════════════

    /**
     * XML-Datei existiert?
     */
    public function xmlExists(): bool
    {
        return $this->xml_file_path && Storage::exists($this->xml_file_path);
    }

    /**
     * XML-Inhalt laden
     */
    public function getXmlContent(): ?string
    {
        // Zuerst gespeicherten Content prüfen
        if ($this->xml_content) {
            return $this->xml_content;
        }

        // Sonst aus Datei laden
        if ($this->xmlExists()) {
            return Storage::get($this->xml_file_path);
        }

        return null;
    }

    /**
     * XML-Datei herunterladen
     */
    public function downloadXml()
    {
        if (!$this->xmlExists()) {
            abort(404, 'XML-Datei nicht gefunden');
        }

        return Storage::download($this->xml_file_path, $this->xml_filename);
    }

    /**
     * XML-Datei-URL
     */
    public function getXmlUrlAttribute(): ?string
    {
        if (!$this->xmlExists()) {
            return null;
        }

        return route('fattura.xml.download', $this->id);
    }

    /**
     * P7M-Datei existiert?
     */
    public function p7mExists(): bool
    {
        return $this->p7m_file_path && Storage::exists($this->p7m_file_path);
    }

    /**
     * P7M-Datei herunterladen
     */
    public function downloadP7m()
    {
        if (!$this->p7mExists()) {
            abort(404, 'P7M-Datei nicht gefunden');
        }

        return Storage::download($this->p7m_file_path, $this->p7m_filename);
    }

    // ═══════════════════════════════════════════════════════════
    // 📊 STATUS MANAGEMENT
    // ═══════════════════════════════════════════════════════════

    /**
     * Status ändern mit Timestamp
     */
    public function setStatus(string $status, ?string $detail = null): void
    {
        $this->status = $status;
        $this->status_detail = $detail;

        // Automatische Timestamps setzen
        switch ($status) {
            case self::STATUS_GENERATED:
                $this->generated_at = now();
                break;
            case self::STATUS_SIGNED:
                $this->signed_at = now();
                break;
            case self::STATUS_SENT:
                $this->sent_at = now();
                break;
            case self::STATUS_DELIVERED:
                $this->delivered_at = now();
                break;
            case self::STATUS_ACCEPTED:
            case self::STATUS_REJECTED:
                $this->finalized_at = now();
                break;
        }

        $this->save();
    }

    /**
     * Als generiert markieren
     */
    public function markAsGenerated(string $xmlPath, string $filename): void
    {
        $this->xml_file_path = $xmlPath;
        $this->xml_filename = $filename;
        $this->xml_file_size = Storage::size($xmlPath);
        $this->setStatus(self::STATUS_GENERATED);
    }

    /**
     * Als signiert markieren
     */
    public function markAsSigned(string $p7mPath, string $filename): void
    {
        $this->p7m_file_path = $p7mPath;
        $this->p7m_filename = $filename;
        $this->setStatus(self::STATUS_SIGNED);
    }

    /**
     * Als gesendet markieren
     */
    public function markAsSent(?string $detail = null): void
    {
        $this->setStatus(self::STATUS_SENT, $detail);
    }

    /**
     * Als Fehler markieren
     */
    public function markAsError(string $message, ?string $details = null): void
    {
        $this->error_message = $message;
        $this->error_details = $details;
        $this->retry_count++;
        $this->setStatus(self::STATUS_ERROR, $message);
    }

    /**
     * Validierungs-Fehler setzen
     */
    public function setValidationErrors(array $errors): void
    {
        $this->is_valid = false;
        $this->validation_errors = $errors;
        $this->save();
    }

    /**
     * Als gültig markieren
     */
    public function markAsValid(): void
    {
        $this->is_valid = true;
        $this->validation_errors = null;
        $this->save();
    }

    // ═══════════════════════════════════════════════════════════
    // 🌐 SDI INTEGRATION
    // ═══════════════════════════════════════════════════════════

    /**
     * SDI-Ricevuta speichern
     */
    public function saveRicevuta(string $ricevuta): void
    {
        $this->sdi_ricevuta = $ricevuta;
        $this->sdi_last_check_at = now();
        $this->setStatus(self::STATUS_DELIVERED);
    }

    /**
     * SDI-Notifica hinzufügen
     */
    public function addNotifica(string $code, string $message): void
    {
        $notifiche = $this->sdi_notifiche ?? [];
        
        $notifiche[] = [
            'code'       => $code,
            'message'    => $message,
            'timestamp'  => now()->toIso8601String(),
        ];

        $this->sdi_notifiche = $notifiche;
        $this->sdi_last_message = $message;
        $this->sdi_status_code = $code;
        $this->sdi_last_check_at = now();
        $this->save();

        // Status anpassen basierend auf Code
        $this->updateStatusFromSdiCode($code);
    }

    /**
     * Status aus SDI-Code ermitteln
     */
    protected function updateStatusFromSdiCode(string $code): void
    {
        // RC = Ricevuta Consegna (Zugestellt)
        if ($code === 'RC') {
            $this->setStatus(self::STATUS_DELIVERED);
        }
        
        // MC = Mancata Consegna (Nicht zugestellt)
        elseif ($code === 'MC') {
            $this->setStatus(self::STATUS_REJECTED, 'Mancata consegna');
        }
        
        // NS = Notifica di Scarto (Abgelehnt)
        elseif ($code === 'NS') {
            $this->setStatus(self::STATUS_REJECTED, 'Notifica di scarto');
        }
        
        // AT = Attestazione di Trasmissione (Übermittelt)
        elseif ($code === 'AT') {
            $this->setStatus(self::STATUS_ACCEPTED, 'Accettata');
        }
    }

    // ═══════════════════════════════════════════════════════════
    // 🏷️ ACCESSORS
    // ═══════════════════════════════════════════════════════════

    /**
     * Status-Badge für UI
     */
    public function getStatusBadgeAttribute(): string
    {
        $badges = [
            self::STATUS_PENDING   => '<span class="badge bg-secondary">Wartend</span>',
            self::STATUS_GENERATED => '<span class="badge bg-info">Generiert</span>',
            self::STATUS_SIGNED    => '<span class="badge bg-primary">Signiert</span>',
            self::STATUS_SENT      => '<span class="badge bg-warning">Gesendet</span>',
            self::STATUS_DELIVERED => '<span class="badge bg-success">Zugestellt</span>',
            self::STATUS_ACCEPTED  => '<span class="badge bg-success">✓ Akzeptiert</span>',
            self::STATUS_REJECTED  => '<span class="badge bg-danger">✗ Abgelehnt</span>',
            self::STATUS_ERROR     => '<span class="badge bg-danger">Fehler</span>',
        ];

        return $badges[$this->status] ?? '<span class="badge bg-secondary">Unbekannt</span>';
    }

    /**
     * Status als Text (deutsch)
     */
    public function getStatusTextAttribute(): string
    {
        $texts = [
            self::STATUS_PENDING   => 'Wartend',
            self::STATUS_GENERATED => 'Generiert',
            self::STATUS_SIGNED    => 'Signiert',
            self::STATUS_SENT      => 'Gesendet',
            self::STATUS_DELIVERED => 'Zugestellt',
            self::STATUS_ACCEPTED  => 'Akzeptiert',
            self::STATUS_REJECTED  => 'Abgelehnt',
            self::STATUS_ERROR     => 'Fehler',
        ];

        return $texts[$this->status] ?? 'Unbekannt';
    }

    /**
     * Dateigröße formatiert
     */
    public function getFileSizeFormattedAttribute(): string
    {
        if (!$this->xml_file_size) {
            return '—';
        }

        $bytes = $this->xml_file_size;

        if ($bytes < 1024) {
            return $bytes . ' B';
        } elseif ($bytes < 1048576) {
            return round($bytes / 1024, 2) . ' KB';
        } else {
            return round($bytes / 1048576, 2) . ' MB';
        }
    }

    /**
     * Ist abgeschlossen?
     */
    public function getIsAbgeschlossenAttribute(): bool
    {
        return in_array($this->status, [
            self::STATUS_ACCEPTED,
            self::STATUS_REJECTED,
        ]);
    }

    /**
     * Ist erfolgreich?
     */
    public function getIsErfolgreichAttribute(): bool
    {
        return $this->status === self::STATUS_ACCEPTED;
    }

    /**
     * Kann erneut versucht werden?
     */
    public function getKannWiederversuchtWerdenAttribute(): bool
    {
        return $this->status === self::STATUS_ERROR && $this->retry_count < 3;
    }
}