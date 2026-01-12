<?php
// app/Models/GebaeudeAenderungsvorschlag.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class GebaeudeAenderungsvorschlag extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'gebaeude_aenderungsvorschlaege';

    // ═══════════════════════════════════════════════════════════
    // 📋 MASS ASSIGNMENT
    // ═══════════════════════════════════════════════════════════
    
    protected $fillable = [
        'gebaeude_id',
        'user_id',
        'typ',
        'status',
        'alte_daten',
        'neue_daten',
        'bemerkung',
        'bearbeitet_von',
        'bearbeitet_am',
        'ablehnungsgrund',
    ];

    // ═══════════════════════════════════════════════════════════
    // 🎯 CASTS
    // ═══════════════════════════════════════════════════════════
    
    protected $casts = [
        'alte_daten'     => 'array',
        'neue_daten'     => 'array',
        'bearbeitet_am'  => 'datetime',
        'created_at'     => 'datetime',
        'updated_at'     => 'datetime',
        'deleted_at'     => 'datetime',
    ];

    // ═══════════════════════════════════════════════════════════
    // 🔗 BEZIEHUNGEN
    // ═══════════════════════════════════════════════════════════
    
    /**
     * Gebäude (falls Änderung, sonst NULL bei neuem Gebäude)
     */
    public function gebaeude(): BelongsTo
    {
        return $this->belongsTo(Gebaeude::class, 'gebaeude_id');
    }

    /**
     * Mitarbeiter der den Vorschlag erstellt hat
     */
    public function ersteller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Admin der den Vorschlag bearbeitet hat
     */
    public function bearbeiter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'bearbeitet_von');
    }

    // ═══════════════════════════════════════════════════════════
    // 🎯 SCOPES
    // ═══════════════════════════════════════════════════════════
    
    /**
     * Nur ausstehende Vorschläge
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Nur genehmigte Vorschläge
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Nur abgelehnte Vorschläge
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Nur neue Gebäude
     */
    public function scopeNeueGebaeude($query)
    {
        return $query->where('typ', 'neu');
    }

    /**
     * Nur Änderungen
     */
    public function scopeAenderungen($query)
    {
        return $query->where('typ', 'aenderung');
    }

    /**
     * Von bestimmtem Mitarbeiter
     */
    public function scopeVonMitarbeiter($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Sortierung: Neueste zuerst
     */
    public function scopeNeueste($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    // ═══════════════════════════════════════════════════════════
    // 🛠️ HELPER-METHODEN
    // ═══════════════════════════════════════════════════════════
    
    /**
     * Ist dieser Vorschlag ausstehend?
     */
    public function istPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Ist dieser Vorschlag genehmigt?
     */
    public function istGenehmigt(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Ist dieser Vorschlag abgelehnt?
     */
    public function istAbgelehnt(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Ist dies ein neues Gebäude?
     */
    public function istNeuesGebaeude(): bool
    {
        return $this->typ === 'neu';
    }

    /**
     * Ist dies eine Änderung?
     */
    public function istAenderung(): bool
    {
        return $this->typ === 'aenderung';
    }

    /**
     * Status-Badge für UI
     */
    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'pending'  => '<span class="badge bg-warning text-dark"><i class="bi bi-clock"></i> Ausstehend</span>',
            'approved' => '<span class="badge bg-success"><i class="bi bi-check-circle"></i> Genehmigt</span>',
            'rejected' => '<span class="badge bg-danger"><i class="bi bi-x-circle"></i> Abgelehnt</span>',
            default    => '<span class="badge bg-secondary">Unbekannt</span>',
        };
    }

    /**
     * Typ-Badge für UI
     */
    public function getTypBadgeAttribute(): string
    {
        return match($this->typ) {
            'neu'       => '<span class="badge bg-primary"><i class="bi bi-plus-circle"></i> Neu</span>',
            'aenderung' => '<span class="badge bg-info"><i class="bi bi-pencil"></i> Änderung</span>',
            default     => '<span class="badge bg-secondary">Unbekannt</span>',
        };
    }

    /**
     * Formatiertes Erstellungsdatum
     */
    public function getErstelltAmFormatiertAttribute(): string
    {
        return $this->created_at->format('d.m.Y H:i');
    }

    /**
     * Formatiertes Bearbeitungsdatum
     */
    public function getBearbeitetAmFormatiertAttribute(): ?string
    {
        return $this->bearbeitet_am?->format('d.m.Y H:i');
    }

    // ═══════════════════════════════════════════════════════════
    // ✅ AKTIONEN (Genehmigen/Ablehnen)
    // ═══════════════════════════════════════════════════════════
    
    /**
     * Vorschlag genehmigen und Gebäude erstellen/aktualisieren
     */
    public function genehmigen(User $admin): bool
    {
        // Status auf genehmigt setzen
        $this->update([
            'status'          => 'approved',
            'bearbeitet_von'  => $admin->id,
            'bearbeitet_am'   => now(),
        ]);

        // Je nach Typ: Gebäude erstellen oder aktualisieren
        if ($this->istNeuesGebaeude()) {
            return $this->erstelleNeuesGebaeude();
        } else {
            return $this->aktualisiereGebaeude();
        }
    }

    /**
     * Vorschlag ablehnen
     */
    public function ablehnen(User $admin, ?string $grund = null): bool
    {
        return $this->update([
            'status'           => 'rejected',
            'bearbeitet_von'   => $admin->id,
            'bearbeitet_am'    => now(),
            'ablehnungsgrund'  => $grund,
        ]);
    }

    /**
     * Erstellt ein neues Gebäude aus den vorgeschlagenen Daten
     */
    protected function erstelleNeuesGebaeude(): bool
    {
        if (!is_array($this->neue_daten) || empty($this->neue_daten)) {
            \Log::error('Keine Daten zum Erstellen eines neuen Gebäudes vorhanden');
            return false;
        }

        try {
            // Touren separat behandeln (Many-to-Many Beziehung)
            $touren = null;
            $datenOhneTouren = $this->neue_daten;
            
            if (isset($datenOhneTouren['touren'])) {
                $touren = $datenOhneTouren['touren'];
                unset($datenOhneTouren['touren']);
            }

            // Neues Gebäude erstellen
            $neuesGebaeude = Gebaeude::create($datenOhneTouren);
            
            // Touren syncen (falls vorhanden)
            if (is_array($touren) && !empty($touren)) {
                $neuesGebaeude->touren()->sync($touren);
            }
            
            // Gebäude-ID im Vorschlag speichern
            $this->update(['gebaeude_id' => $neuesGebaeude->id]);
            
            return true;
        } catch (\Exception $e) {
            \Log::error('Fehler beim Erstellen des Gebäudes: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Aktualisiert ein bestehendes Gebäude mit den vorgeschlagenen Daten
     */
    protected function aktualisiereGebaeude(): bool
    {
        if (!$this->gebaeude) {
            \Log::error('Kein Gebäude zum Aktualisieren vorhanden');
            return false;
        }

        if (!is_array($this->neue_daten) || empty($this->neue_daten)) {
            \Log::error('Keine Daten zum Aktualisieren des Gebäudes vorhanden');
            return false;
        }

        try {
            // Touren separat behandeln (Many-to-Many Beziehung)
            $touren = null;
            $datenOhneTouren = $this->neue_daten;
            
            if (isset($datenOhneTouren['touren'])) {
                $touren = $datenOhneTouren['touren'];
                unset($datenOhneTouren['touren']);
            }

            // Gebäude-Daten aktualisieren
            $this->gebaeude->update($datenOhneTouren);

            // Touren syncen (falls vorhanden)
            if (is_array($touren)) {
                $this->gebaeude->touren()->sync($touren);
            }

            return true;
        } catch (\Exception $e) {
            \Log::error('Fehler beim Aktualisieren des Gebäudes: ' . $e->getMessage());
            return false;
        }
    }

    // ═══════════════════════════════════════════════════════════
    // 🔍 ÄNDERUNGS-VERGLEICH
    // ═══════════════════════════════════════════════════════════
    
    /**
     * Gibt die geänderten Felder zurück (nur bei Änderungen)
     */
    public function getGeaendeerteFelderAttribute(): array
    {
        if ($this->istNeuesGebaeude() || !$this->alte_daten || !is_array($this->neue_daten)) {
            return [];
        }

        $geaendert = [];
        
        foreach ($this->neue_daten as $feld => $neuerWert) {
            $alterWert = $this->alte_daten[$feld] ?? null;
            
            if ($alterWert != $neuerWert) {
                $geaendert[$feld] = [
                    'alt' => $alterWert,
                    'neu' => $neuerWert,
                ];
            }
        }

        return $geaendert;
    }

    /**
     * Anzahl der geänderten Felder
     */
    public function getAnzahlAenderungenAttribute(): int
    {
        $felder = $this->geaenderte_felder;
        return is_array($felder) ? count($felder) : 0;
    }

    // ═══════════════════════════════════════════════════════════
    // 📊 STATIC HELPER
    // ═══════════════════════════════════════════════════════════
    
    /**
     * Erstellt einen neuen Änderungsvorschlag
     */
    public static function erstelleVorschlag(
        User $mitarbeiter,
        string $typ,
        array $neueDaten,
        ?int $gebaeudeId = null,
        ?string $bemerkung = null
    ): self {
        $vorschlag = new self([
            'user_id'    => $mitarbeiter->id,
            'typ'        => $typ,
            'neue_daten' => $neueDaten,
            'bemerkung'  => $bemerkung,
            'status'     => 'pending',
        ]);

        // Bei Änderung: Gebäude-ID und alte Daten speichern
        if ($typ === 'aenderung' && $gebaeudeId) {
            $gebaeude = Gebaeude::findOrFail($gebaeudeId);
            
            $vorschlag->gebaeude_id = $gebaeudeId;
            $vorschlag->alte_daten = $gebaeude->only(array_keys($neueDaten));
        }

        $vorschlag->save();

        return $vorschlag;
    }
}
