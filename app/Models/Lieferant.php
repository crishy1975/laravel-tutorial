<?php
/**
 * ════════════════════════════════════════════════════════════════════════════
 * DATEI: Lieferant.php
 * PFAD:  app/Models/Lieferant.php
 * ════════════════════════════════════════════════════════════════════════════
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lieferant extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'lieferanten';

    protected $fillable = [
        'partita_iva',
        'codice_fiscale',
        'name',
        'strasse',
        'plz',
        'ort',
        'provinz',
        'land',
        'telefon',
        'email',
        'iban',
        'iban_inhaber',
        'bic',
        'notiz',
        'aktiv',
    ];

    protected $casts = [
        'aktiv'      => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // ═══════════════════════════════════════════════════════════
    // 🔗 BEZIEHUNGEN
    // ═══════════════════════════════════════════════════════════

    /**
     * Alle Eingangsrechnungen dieses Lieferanten
     */
    public function eingangsrechnungen(): HasMany
    {
        return $this->hasMany(Eingangsrechnung::class)
            ->orderByDesc('rechnungsdatum');
    }

    /**
     * Nur offene Rechnungen
     */
    public function offeneRechnungen(): HasMany
    {
        return $this->hasMany(Eingangsrechnung::class)
            ->where('status', 'offen')
            ->orderByDesc('rechnungsdatum');
    }

    /**
     * Nur bezahlte Rechnungen
     */
    public function bezahlteRechnungen(): HasMany
    {
        return $this->hasMany(Eingangsrechnung::class)
            ->where('status', 'bezahlt')
            ->orderByDesc('rechnungsdatum');
    }

    // ═══════════════════════════════════════════════════════════
    // 📊 STATISTIK-ATTRIBUTE
    // ═══════════════════════════════════════════════════════════

    /**
     * Anzahl aller Rechnungen
     */
    public function getAnzahlRechnungenAttribute(): int
    {
        return $this->eingangsrechnungen()->count();
    }

    /**
     * Anzahl offener Rechnungen
     */
    public function getAnzahlOffenAttribute(): int
    {
        return $this->eingangsrechnungen()->where('status', 'offen')->count();
    }

    /**
     * Summe aller offenen Rechnungen
     */
    public function getSummeOffenAttribute(): float
    {
        return (float) $this->eingangsrechnungen()
            ->where('status', 'offen')
            ->sum('brutto_betrag');
    }

    /**
     * Summe aller bezahlten Rechnungen
     */
    public function getSummeBezahltAttribute(): float
    {
        return (float) $this->eingangsrechnungen()
            ->where('status', 'bezahlt')
            ->sum('brutto_betrag');
    }

    /**
     * Gesamtsumme aller Rechnungen
     */
    public function getSummeGesamtAttribute(): float
    {
        return (float) $this->eingangsrechnungen()->sum('brutto_betrag');
    }

    // ═══════════════════════════════════════════════════════════
    // 🔍 SCOPES
    // ═══════════════════════════════════════════════════════════

    /**
     * Nur aktive Lieferanten
     */
    public function scopeAktiv($query)
    {
        return $query->where('aktiv', true);
    }

    /**
     * Suche nach Name oder P.IVA
     */
    public function scopeSuche($query, string $suchbegriff)
    {
        return $query->where(function ($q) use ($suchbegriff) {
            $q->where('name', 'like', "%{$suchbegriff}%")
              ->orWhere('partita_iva', 'like', "%{$suchbegriff}%");
        });
    }

    /**
     * Lieferanten mit offenen Rechnungen
     */
    public function scopeMitOffenenRechnungen($query)
    {
        return $query->whereHas('eingangsrechnungen', function ($q) {
            $q->where('status', 'offen');
        });
    }

    // ═══════════════════════════════════════════════════════════
    // 🛠️ HILFSMETHODEN
    // ═══════════════════════════════════════════════════════════

    /**
     * Findet oder erstellt Lieferant anhand P.IVA
     */
    public static function findOrCreateByPiva(string $partitaIva, array $daten = []): self
    {
        $lieferant = self::where('partita_iva', $partitaIva)->first();

        if (!$lieferant) {
            $lieferant = self::create(array_merge(
                ['partita_iva' => $partitaIva],
                $daten
            ));
        }

        return $lieferant;
    }

    /**
     * Vollständige Adresse formatiert
     */
    public function getAdresseFormatiertAttribute(): string
    {
        $teile = array_filter([
            $this->strasse,
            trim("{$this->plz} {$this->ort}"),
            $this->provinz ? "({$this->provinz})" : null,
        ]);

        return implode(', ', $teile);
    }

    /**
     * Hat der Lieferant eine IBAN?
     */
    public function hatIban(): bool
    {
        return !empty($this->iban);
    }

    /**
     * IBAN formatiert (mit Leerzeichen)
     */
    public function getIbanFormatiertAttribute(): ?string
    {
        if (!$this->iban) {
            return null;
        }

        return implode(' ', str_split($this->iban, 4));
    }
}
