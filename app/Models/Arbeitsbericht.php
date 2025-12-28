<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class Arbeitsbericht extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'arbeitsberichte';

    protected $fillable = [
        'gebaeude_id',
        
        // Adresse (Snapshot)
        'adresse_name',
        'adresse_strasse',
        'adresse_hausnummer',
        'adresse_plz',
        'adresse_wohnort',
        
        // Arbeitsdaten
        'arbeitsdatum',
        'naechste_faelligkeit',
        'bemerkung',
        'positionen',
        
        // Unterschrift
        'unterschrift_kunde',
        'unterschrieben_am',
        'unterschrift_name',
        'unterschrift_ip',
        
        // Link-System
        'token',
        'gueltig_bis',
        'abgerufen_am',
        
        // Status
        'status',
    ];

    protected $casts = [
        'arbeitsdatum'        => 'date',
        'naechste_faelligkeit' => 'date',
        'positionen'          => 'array',
        'unterschrieben_am'   => 'datetime',
        'gueltig_bis'         => 'datetime',
        'abgerufen_am'        => 'datetime',
    ];

    // ═══════════════════════════════════════════════════════════
    // 🔗 RELATIONSHIPS
    // ═══════════════════════════════════════════════════════════

    public function gebaeude(): BelongsTo
    {
        return $this->belongsTo(Gebaeude::class);
    }

    // ═══════════════════════════════════════════════════════════
    // 🏭 FACTORY METHODS
    // ═══════════════════════════════════════════════════════════

    /**
     * Erstellt einen Arbeitsbericht aus einem Gebäude
     */
    public static function createFromGebaeude(Gebaeude $gebaeude, array $overrides = []): self
    {
        // Rechnungsempfänger laden
        $adresse = $gebaeude->rechnungsempfaenger;
        
        // Aktive Artikel sammeln
        $positionen = $gebaeude->aktiveArtikel->map(function ($artikel) {
            return [
                'bezeichnung' => $artikel->bezeichnung ?? $artikel->artikel?->bezeichnung ?? 'Unbekannt',
                'anzahl'      => $artikel->anzahl ?? 1,
                'einheit'     => $artikel->einheit ?? 'Stk',
            ];
        })->toArray();

        // Letztes Datum aus Timeline
        $letzterTermin = $gebaeude->timelines()->max('datum');
        $arbeitsdatum = $letzterTermin ? Carbon::parse($letzterTermin) : now();

        // Token generieren (URL-sicher)
        $token = Str::random(64);

        return self::create(array_merge([
            'gebaeude_id'          => $gebaeude->id,
            
            // Adresse Snapshot
            'adresse_name'         => $adresse?->name ?? $gebaeude->gebaeude_name,
            'adresse_strasse'      => $adresse?->strasse ?? $gebaeude->strasse,
            'adresse_hausnummer'   => $adresse?->hausnummer ?? $gebaeude->hausnummer,
            'adresse_plz'          => $adresse?->plz ?? $gebaeude->plz,
            'adresse_wohnort'      => $adresse?->wohnort ?? $gebaeude->wohnort,
            
            // Arbeitsdaten
            'arbeitsdatum'         => $arbeitsdatum,
            'naechste_faelligkeit' => $gebaeude->datum_faelligkeit,
            'positionen'           => $positionen,
            
            // Link-System: 10 Tage gültig
            'token'                => $token,
            'gueltig_bis'          => now()->addDays(10),
            
            'status'               => 'erstellt',
        ], $overrides));
    }

    // ═══════════════════════════════════════════════════════════
    // 📝 HELPER METHODS
    // ═══════════════════════════════════════════════════════════

    /**
     * Vollständige Adresse als String
     */
    public function getVolleAdresseAttribute(): string
    {
        $parts = array_filter([
            $this->adresse_name,
            trim($this->adresse_strasse . ' ' . $this->adresse_hausnummer),
            trim($this->adresse_plz . ' ' . $this->adresse_wohnort),
        ]);

        return implode("\n", $parts);
    }

    /**
     * Öffentlicher Link zum Bericht
     */
    public function getPublicLinkAttribute(): string
    {
        return route('arbeitsbericht.public', $this->token);
    }

    /**
     * Ist der Link noch gültig?
     */
    public function istGueltig(): bool
    {
        return $this->gueltig_bis->isFuture() && $this->status !== 'abgelaufen';
    }

    /**
     * Ist bereits unterschrieben?
     */
    public function istUnterschrieben(): bool
    {
        return $this->status === 'unterschrieben' && !empty($this->unterschrift_kunde);
    }

    /**
     * Unterschrift speichern
     */
    public function unterschreiben(string $signaturBase64, string $name, ?string $ip = null): bool
    {
        if (!$this->istGueltig()) {
            return false;
        }

        $this->update([
            'unterschrift_kunde' => $signaturBase64,
            'unterschrift_name'  => $name,
            'unterschrift_ip'    => $ip,
            'unterschrieben_am'  => now(),
            'status'             => 'unterschrieben',
        ]);

        return true;
    }

    /**
     * Markiert als abgerufen
     */
    public function markiereAlsAbgerufen(): void
    {
        if (!$this->abgerufen_am) {
            $this->update(['abgerufen_am' => now()]);
        }
    }

    /**
     * Status als gesendet markieren
     */
    public function markiereAlsGesendet(): void
    {
        if ($this->status === 'erstellt') {
            $this->update(['status' => 'gesendet']);
        }
    }

    // ═══════════════════════════════════════════════════════════
    // 🔍 SCOPES
    // ═══════════════════════════════════════════════════════════

    public function scopeGueltig($query)
    {
        return $query->where('gueltig_bis', '>', now())
                     ->where('status', '!=', 'abgelaufen');
    }

    public function scopeAbgelaufen($query)
    {
        return $query->where('gueltig_bis', '<=', now())
                     ->orWhere('status', 'abgelaufen');
    }

    public function scopeUnterschrieben($query)
    {
        return $query->where('status', 'unterschrieben');
    }

    public function scopeOffen($query)
    {
        return $query->whereIn('status', ['erstellt', 'gesendet'])
                     ->where('gueltig_bis', '>', now());
    }

    // ═══════════════════════════════════════════════════════════
    // 🔧 BOOT
    // ═══════════════════════════════════════════════════════════

    protected static function boot()
    {
        parent::boot();

        // Token automatisch generieren falls nicht vorhanden
        static::creating(function ($bericht) {
            if (empty($bericht->token)) {
                $bericht->token = Str::random(64);
            }
            if (empty($bericht->gueltig_bis)) {
                $bericht->gueltig_bis = now()->addDays(10);
            }
        });
    }
}
