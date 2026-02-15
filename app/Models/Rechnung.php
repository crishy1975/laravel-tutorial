<?php
// app/Models/Rechnung.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Gebaeude;
use App\Models\ArtikelGebaeude;
use App\Models\Adresse;
use App\Models\FatturaProfile;
use App\Models\RechnungPosition;
use App\Enums\Zahlungsbedingung;
use App\Models\FatturaXmlLog;
use App\Enums\RechnungLogTyp;

class Rechnung extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'rechnungen';
    protected $appends = ['erwarteter_zahlbetrag'];

    protected $fillable = [
        'legacy_id',          // ⭐ NEU
        'legacy_progressivo', // ⭐ NEU
        'jahr',
        'laufnummer',
        'gebaeude_id',
        'rechnungsempfaenger_id',
        'postadresse_id',
        'fattura_profile_id',

        // Snapshot Rechnungsempfänger
        're_name',
        're_strasse',
        're_hausnummer',
        're_plz',
        're_wohnort',
        're_provinz',
        're_land',
        're_steuernummer',
        're_mwst_nummer',
        're_codice_univoco',
        're_pec',

        // Snapshot Postadresse
        'post_name',
        'post_strasse',
        'post_hausnummer',
        'post_plz',
        'post_wohnort',
        'post_provinz',
        'post_land',
        'post_email',
        'post_pec',

        // Snapshot Gebäude
        'geb_codex',
        'geb_name',
        'geb_adresse',
        'fattura_causale',

        // Datumsfelder
        'rechnungsdatum',
        'leistungsdaten',
        'zahlungsziel',
        'bezahlt_am',

        // Beträge
        'netto_summe',
        'mwst_betrag',
        'brutto_summe',
        'ritenuta_betrag',
        'zahlbar_betrag',

        // Status & Flags
        'status',
        'typ_rechnung',

        // Snapshot Profil
        'profile_bezeichnung',
        'mwst_satz',
        'split_payment',
        'reverse_charge',
        'ritenuta',
        'ritenuta_prozent',

        // FatturaPA
        'cup',
        'cig',
        'codice_commessa',
        'auftrag_id',
        'auftrag_datum',

        // NEU: Aufschlag-Tracking
        'aufschlag_prozent',
        'aufschlag_typ',

        // Sonstige
        'bemerkung',
        'bemerkung_kunde',
        'zahlungsbedingungen',
        'pdf_pfad',
        'xml_pfad',
        'externe_referenz',
    ];

    protected $casts = [
        'jahr'                => 'integer',
        'laufnummer'          => 'integer',
        'rechnungsdatum'      => 'date',
        'zahlungsziel'        => 'date',
        'bezahlt_am'          => 'date',
        'auftrag_datum'       => 'date',
        'netto_summe'         => 'decimal:2',
        'mwst_betrag'         => 'decimal:2',
        'brutto_summe'        => 'decimal:2',
        'ritenuta_betrag'     => 'decimal:2',
        'zahlbar_betrag'      => 'decimal:2',
        'mwst_satz'           => 'decimal:2',
        'ritenuta_prozent'    => 'decimal:2',
        'split_payment'       => 'boolean',
        'reverse_charge'      => 'boolean',
        'ritenuta'            => 'boolean',
        'aufschlag_prozent'   => 'decimal:2',
        'zahlungsbedingungen' => Zahlungsbedingung::class,
    ];

    // ═══════════════════════════════════════════════════════════
    // 🔗 RELATIONSHIPS
    // ═══════════════════════════════════════════════════════════


    // ─────────────────────────────────────────────────────────────────────────
    // ⭐ BOOT METHOD - KORRIGIERT mit automatischer Zahlungsziel-Berechnung
    // ─────────────────────────────────────────────────────────────────────────
    protected static function boot()
    {
        parent::boot();

        // Bei Erstellen: Automatische Causale generieren (falls leer)
        static::creating(function ($rechnung) {
            if (!$rechnung->fattura_causale) {
                $rechnung->fattura_causale = static::generateCausaleStatic($rechnung);
            }

            // ⭐ NEU: Zahlungsziel automatisch setzen wenn nicht vorhanden
            if (!$rechnung->zahlungsziel && $rechnung->rechnungsdatum) {
                $rechnung->zahlungsziel = static::berechneZahlungsziel(
                    $rechnung->rechnungsdatum,
                    $rechnung->zahlungsbedingungen
                );
            }
        });

        // ⭐ KORRIGIERT: Beim Speichern automatisch Zahlungsziel & Status aktualisieren
        static::saving(function ($rechnung) {
            // Ritenuta automatisch setzen (bestehende Logik)
            if ($rechnung->ritenuta) {
                if (!$rechnung->ritenuta_prozent || $rechnung->ritenuta_prozent == 0) {
                    $rechnung->ritenuta_prozent = 4.00;
                }
            }

            // ⭐ NEU: Wenn Zahlungsbedingungen geändert wurden → Zahlungsziel neu berechnen
            if ($rechnung->isDirty('zahlungsbedingungen') && $rechnung->rechnungsdatum) {
                $neueZahlungsbedingung = $rechnung->zahlungsbedingungen;

                // Wenn "bezahlt" → Zahlungsziel = heute, Status = paid
                if ($neueZahlungsbedingung === Zahlungsbedingung::BEZAHLT) {
                    $rechnung->status = 'paid';

                    // Bezahlt_am setzen falls nicht schon gesetzt
                    if (!$rechnung->bezahlt_am) {
                        $rechnung->bezahlt_am = now();
                    }

                    // Zahlungsziel auf bezahlt_am setzen
                    $rechnung->zahlungsziel = $rechnung->bezahlt_am;

                    \Log::info('Rechnung als bezahlt markiert', [
                        'rechnung_id' => $rechnung->id,
                        'bezahlt_am'  => $rechnung->bezahlt_am,
                    ]);
                } else {
                    // Normales Zahlungsziel berechnen
                    $rechnung->zahlungsziel = static::berechneZahlungsziel(
                        $rechnung->rechnungsdatum,
                        $neueZahlungsbedingung
                    );
                }
            }

            // ⭐ NEU: Wenn Rechnungsdatum geändert wurde UND nicht "bezahlt" → Zahlungsziel neu berechnen
            if ($rechnung->isDirty('rechnungsdatum') && $rechnung->zahlungsbedingungen !== Zahlungsbedingung::BEZAHLT) {
                $rechnung->zahlungsziel = static::berechneZahlungsziel(
                    $rechnung->rechnungsdatum,
                    $rechnung->zahlungsbedingungen
                );
            }
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ⭐ NEU: BOOTED METHOD - Soft-Delete Uniqueness Checks
    // ─────────────────────────────────────────────────────────────────────────
    protected static function booted()
    {
        // ⭐ Bei Erstellen: Prüfen ob Rechnungsnummer bereits existiert (nur aktive)
        static::creating(function ($rechnung) {
            $exists = static::where('jahr', $rechnung->jahr)
                ->where('laufnummer', $rechnung->laufnummer)
                ->whereNull('deleted_at')
                ->exists();
                
            if ($exists) {
                throw new \Exception(
                    "Rechnungsnummer {$rechnung->jahr}/" . 
                    str_pad($rechnung->laufnummer, 4, '0', STR_PAD_LEFT) . 
                    " existiert bereits."
                );
            }
        });
        
        // ⭐ Bei Wiederherstellen: Prüfen ob Nummer inzwischen vergeben wurde
        static::restoring(function ($rechnung) {
            $exists = static::where('jahr', $rechnung->jahr)
                ->where('laufnummer', $rechnung->laufnummer)
                ->whereNull('deleted_at')
                ->where('id', '!=', $rechnung->id)
                ->exists();
                
            if ($exists) {
                throw new \Exception(
                    "Rechnungsnummer {$rechnung->jahr}/" . 
                    str_pad($rechnung->laufnummer, 4, '0', STR_PAD_LEFT) . 
                    " ist bereits vergeben. Wiederherstellung nicht möglich."
                );
            }
        });
    }

    /**
     * ⭐ NEU: Berechnet das Zahlungsziel basierend auf Rechnungsdatum und Zahlungsbedingung
     * 
     * @param Carbon|string|null $rechnungsdatum
     * @param Zahlungsbedingung|string|null $zahlungsbedingung
     * @return Carbon|null
     */
    public static function berechneZahlungsziel($rechnungsdatum, $zahlungsbedingung): ?Carbon
    {
        if (!$rechnungsdatum) {
            return null;
        }

        // Carbon-Instanz sicherstellen
        if (!$rechnungsdatum instanceof Carbon) {
            $rechnungsdatum = Carbon::parse($rechnungsdatum);
        }

        // Zahlungsbedingung zu Enum konvertieren falls String
        if (is_string($zahlungsbedingung)) {
            $zahlungsbedingung = Zahlungsbedingung::tryFrom($zahlungsbedingung);
        }

        // Tage aus Zahlungsbedingung ermitteln
        $tage = $zahlungsbedingung?->tage() ?? 30; // Default: 30 Tage

        return $rechnungsdatum->copy()->addDays($tage);
    }


    // ═══════════════════════════════════════════════════════════
    // XML-LOG BEZIEHUNGEN
    // ═══════════════════════════════════════════════════════════

    /**
     * Alle XML-Logs fuer diese Rechnung
     */
    public function xmlLogs(): HasMany
    {
        return $this->hasMany(FatturaXmlLog::class)
            ->orderByDesc('created_at');
    }

    /**
     * Neuester erfolgreicher XML-Log
     */
    public function latestXmlLog()
    {
        return $this->hasOne(FatturaXmlLog::class)
            ->whereIn('status', [
                FatturaXmlLog::STATUS_GENERATED,
                FatturaXmlLog::STATUS_SIGNED,
                FatturaXmlLog::STATUS_SENT,
                FatturaXmlLog::STATUS_DELIVERED,
                FatturaXmlLog::STATUS_ACCEPTED,
            ])
            ->latest();
    }

    /**
     * Hat diese Rechnung eine generierte XML-Datei?
     * 
     * @return bool
     */
    public function getHatXmlAttribute(): bool
    {
        return FatturaXmlLog::where('rechnung_id', $this->id)
            ->whereIn('status', [
                FatturaXmlLog::STATUS_GENERATED,
                FatturaXmlLog::STATUS_SIGNED,
                FatturaXmlLog::STATUS_SENT,
                FatturaXmlLog::STATUS_DELIVERED,
                FatturaXmlLog::STATUS_ACCEPTED,
            ])
            ->exists();
    }

    // ═══════════════════════════════════════════════════════════
    // 🔍 VALIDIERUNG & WARNUNGEN (NEU)
    // ═══════════════════════════════════════════════════════════

    /**
     * Prüft auf mögliche Duplikate: Gleicher Betrag auf gleiches Gebäude im selben Jahr.
     * 
     * @param int $gebaeudeId
     * @param float $betrag (brutto_summe)
     * @param int|null $jahr (Standard: aktuelles Jahr)
     * @param int|null $excludeId ID einer bestehenden Rechnung ausschließen (für Updates)
     * @return array{is_duplicate: bool, existing: ?Rechnung, message: ?string}
     */
    public static function pruefeDuplikat(
        int $gebaeudeId,
        float $betrag,
        ?int $jahr = null,
        ?int $excludeId = null
    ): array {
        $jahr = $jahr ?? now()->year;

        // Toleranz für Rundungsdifferenzen (1 Cent)
        $toleranz = 0.02;
        $min = $betrag - $toleranz;
        $max = $betrag + $toleranz;

        $query = static::where('gebaeude_id', $gebaeudeId)
            ->where('jahr', $jahr)
            ->where('status', '!=', 'cancelled')
            ->where('brutto_summe', '>=', $min)
            ->where('brutto_summe', '<=', $max);

        // Bei Update: Eigene ID ausschließen
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        \Log::info('pruefeDuplikat DEBUG', [
            'gebaeude_id' => $gebaeudeId,
            'betrag' => $betrag,
            'jahr' => $jahr,
            'exclude_id' => $excludeId,
            'min' => $min,
            'max' => $max,
            'sql' => $query->toSql(),
        ]);

        $existing = $query->first();

        \Log::info('pruefeDuplikat RESULT', [
            'found' => $existing ? true : false,
            'existing_id' => $existing?->id,
            'existing_brutto' => $existing?->brutto_summe,
        ]);

        if ($existing) {
            $rechnungsnummer = $existing->jahr . '/' . str_pad($existing->laufnummer, 4, '0', STR_PAD_LEFT);

            return [
                'is_duplicate' => true,
                'existing' => $existing,
                'message' => "⚠️ Mögliches Duplikat: Rechnung {$rechnungsnummer} vom " .
                    $existing->rechnungsdatum?->format('d.m.Y') .
                    " hat den gleichen Betrag (" . number_format($betrag, 2, ',', '.') . " €) für dieses Gebäude.",
            ];
        }

        return [
            'is_duplicate' => false,
            'existing' => null,
            'message' => null,
        ];
    }

    /**
     * Prüft ob eine bestimmte Laufnummer eine Lücke erzeugen würde.
     * 
     * @param int $jahr
     * @param int $neueLaufnummer
     * @return array{has_gap: bool, missing: array, message: ?string}
     */
    public static function pruefeLaufnummerLuecke(int $jahr, int $neueLaufnummer): array
    {
        // Alle existierenden Laufnummern im Jahr holen
        $existierende = static::where('jahr', $jahr)
            ->whereNull('deleted_at')
            ->pluck('laufnummer')
            ->sort()
            ->values()
            ->toArray();

        // Wenn keine Rechnungen existieren und neue Nr. > 1 → Lücke
        if (empty($existierende) && $neueLaufnummer > 1) {
            $missing = range(1, $neueLaufnummer - 1);
            return [
                'has_gap' => true,
                'missing' => $missing,
                'message' => "⚠️ Lücke: Es fehlen die Nummern " . implode(', ', $missing) . " im Jahr {$jahr}.",
            ];
        }

        // Höchste existierende Nummer
        $hoechste = !empty($existierende) ? max($existierende) : 0;

        // Wenn neue Nummer größer als höchste + 1 → Lücke
        if ($neueLaufnummer > $hoechste + 1) {
            $missing = range($hoechste + 1, $neueLaufnummer - 1);
            return [
                'has_gap' => true,
                'missing' => $missing,
                'message' => "⚠️ Lücke: Es fehlen die Nummern " . implode(', ', $missing) . " im Jahr {$jahr}.",
            ];
        }

        return [
            'has_gap' => false,
            'missing' => [],
            'message' => null,
        ];
    }

    /**
     * Findet alle Lücken in den Rechnungsnummern eines Jahres.
     * 
     * @param int $jahr
     * @return array{has_gaps: bool, missing: array, message: ?string}
     */
    public static function findeAlleLuecken(int $jahr): array
    {
        // SoftDeletes: Nur nicht-gelöschte Rechnungen zählen
        $existierende = static::where('jahr', $jahr)
            ->pluck('laufnummer')
            ->sort()
            ->values()
            ->toArray();

        \Log::info('findeAlleLuecken DEBUG', [
            'jahr' => $jahr,
            'existierende' => $existierende,
            'anzahl' => count($existierende),
        ]);

        if (empty($existierende)) {
            return [
                'has_gaps' => false,
                'missing' => [],
                'message' => "Keine Rechnungen im Jahr {$jahr} vorhanden.",
            ];
        }

        $hoechste = max($existierende);
        $sollNummern = range(1, $hoechste);
        $fehlende = array_values(array_diff($sollNummern, $existierende));

        \Log::info('findeAlleLuecken RESULT', [
            'hoechste' => $hoechste,
            'soll' => $sollNummern,
            'fehlende' => $fehlende,
        ]);

        if (empty($fehlende)) {
            return [
                'has_gaps' => false,
                'missing' => [],
                'message' => "✓ Alle Rechnungsnummern 1-{$hoechste} im Jahr {$jahr} sind vorhanden.",
            ];
        }

        // Fehlende Nummern formatieren
        $formatierteLuecken = array_map(function ($nr) use ($jahr) {
            return $jahr . '/' . str_pad($nr, 4, '0', STR_PAD_LEFT);
        }, $fehlende);

        return [
            'has_gaps' => true,
            'missing' => $fehlende,
            'missing_formatted' => $formatierteLuecken,
            'message' => "⚠️ Fehlende Rechnungsnummern im Jahr {$jahr}: " . implode(', ', $formatierteLuecken),
        ];
    }

    /**
     * Findet alle möglichen Duplikate für ein Gebäude.
     * 
     * @param int $gebaeudeId
     * @param int|null $jahr (null = alle Jahre)
     * @return \Illuminate\Support\Collection Gruppiert nach Betrag
     */
    public static function findeDuplikate(int $gebaeudeId, ?int $jahr = null): \Illuminate\Support\Collection
    {
        $query = static::where('gebaeude_id', $gebaeudeId)
            ->where('status', '!=', 'cancelled')
            ->whereNull('deleted_at');

        if ($jahr) {
            $query->where('jahr', $jahr);
        }

        return $query->get()
            ->groupBy(function ($rechnung) {
                // Gruppiere nach Brutto-Summe (auf 2 Dezimalstellen gerundet)
                return number_format((float) $rechnung->brutto_summe, 2, '.', '');
            })
            ->filter(function ($group) {
                // Nur Gruppen mit mehr als einer Rechnung
                return $group->count() > 1;
            })
            ->map(function ($group) {
                return [
                    'betrag' => (float) $group->first()->brutto_summe,
                    'anzahl' => $group->count(),
                    'rechnungen' => $group->map(function ($r) {
                        return [
                            'id' => $r->id,
                            'nummer' => $r->jahr . '/' . str_pad($r->laufnummer, 4, '0', STR_PAD_LEFT),
                            'datum' => $r->rechnungsdatum?->format('d.m.Y'),
                            'status' => $r->status,
                        ];
                    }),
                ];
            });
    }

    /**
     * Führt alle Validierungen vor dem Erstellen durch.
     * 
     * @param int $gebaeudeId
     * @param float $betrag
     * @param int $jahr
     * @param int $laufnummer
     * @return array{warnings: array, can_proceed: bool}
     */
    public static function validiereVorErstellung(
        int $gebaeudeId,
        float $betrag,
        int $jahr,
        int $laufnummer
    ): array {
        $warnings = [];

        // 1. Duplikat-Prüfung
        $duplikat = static::pruefeDuplikat($gebaeudeId, $betrag, $jahr);
        if ($duplikat['is_duplicate']) {
            $warnings[] = $duplikat['message'];
        }

        // 2. Laufnummer-Lücken-Prüfung
        $luecke = static::pruefeLaufnummerLuecke($jahr, $laufnummer);
        if ($luecke['has_gap']) {
            $warnings[] = $luecke['message'];
        }

        return [
            'warnings' => $warnings,
            'can_proceed' => true, // Warnungen blockieren nicht, nur informieren
            'duplikat_info' => $duplikat,
            'luecken_info' => $luecke,
        ];
    }

    /**
     * Gibt eine Übersicht aller Integritätsprobleme zurück.
     * 
     * @param int|null $jahr
     * @return array
     */
    public static function getIntegritaetsReport(?int $jahr = null): array
    {
        $jahr = $jahr ?? now()->year;

        // 1. Lücken finden
        $luecken = static::findeAlleLuecken($jahr);

        // 2. Alle Duplikate finden (über alle Gebäude)
        $alleDuplikate = [];

        $gebaeudeIds = static::where('jahr', $jahr)
            ->whereNull('deleted_at')
            ->distinct()
            ->pluck('gebaeude_id');

        foreach ($gebaeudeIds as $gebaeudeId) {
            $duplikate = static::findeDuplikate($gebaeudeId, $jahr);

            if ($duplikate->isNotEmpty()) {
                $gebaeude = Gebaeude::find($gebaeudeId);
                $alleDuplikate[$gebaeudeId] = [
                    'gebaeude_name' => $gebaeude?->gebaeude_name ?? $gebaeude?->codex ?? "ID: {$gebaeudeId}",
                    'duplikate' => $duplikate,
                ];
            }
        }

        return [
            'jahr' => $jahr,
            'luecken' => $luecken,
            'duplikate' => $alleDuplikate,
            'hat_probleme' => $luecken['has_gaps'] || !empty($alleDuplikate),
        ];
    }

    /**
     * Nächste verfügbare Laufnummer für ein Jahr.
     * 
     * @param int|null $jahr
     * @return int
     */
    public static function getNextLaufnummer(?int $jahr = null): int
    {
        $jahr = $jahr ?? now()->year;

        $maxLaufnummer = (int) static::where('jahr', $jahr)
            ->max('laufnummer');

        return $maxLaufnummer + 1;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ⭐ NEU: SOFT-DELETE HELPER METHODEN
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Prüft ob eine Rechnungsnummer bereits aktiv existiert.
     * 
     * @param int $jahr
     * @param int $laufnummer
     * @param int|null $excludeId ID ausschließen (für Updates)
     * @return bool
     */
    public static function nummerExistiertAktiv(int $jahr, int $laufnummer, ?int $excludeId = null): bool
    {
        $query = static::where('jahr', $jahr)
            ->where('laufnummer', $laufnummer)
            ->whereNull('deleted_at');
            
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }

    /**
     * Scope: Nur gelöschte Rechnungen für ein Gebäude.
     */
    public function scopeGeloeschteFuerGebaeude($query, int $gebaeudeId)
    {
        return $query->onlyTrashed()
            ->where('gebaeude_id', $gebaeudeId)
            ->orderByDesc('deleted_at');
    }

    /**
     * Scope: Nur aktive (nicht gelöschte) Rechnungen.
     */
    public function scopeAktiv($query)
    {
        return $query->whereNull('deleted_at');
    }

    /**
     * Kann diese Rechnung gelöscht werden?
     * Nur Entwürfe (draft) können gelöscht werden.
     * 
     * @return bool
     */
    public function kannGeloeschtWerden(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Kann diese Rechnung wiederhergestellt werden?
     * Prüft ob die Nummer noch frei ist.
     * 
     * @return bool
     */
    public function kannWiederhergestelltWerden(): bool
    {
        if (!$this->trashed()) {
            return false;
        }
        
        return !static::nummerExistiertAktiv($this->jahr, $this->laufnummer);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // STATISCHE METHODEN
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * ⭐ Generiert Causale statisch (ULTRA-KOMPAKT)
     * 
     * Format:
     * Zeitraum/Periodo: Jahr/anno 2025 - Objekt/Oggetto: Name, Adresse
     * 
     * Beispiel:
     * Zeitraum/Periodo: Jahr/anno 2025 - Objekt/Oggetto: Cond. Romana, Fuchserstr. 2, 39055 Laives
     * 
     * @param Rechnung|object $rechnung
     * @return string|null
     */
    public static function generateCausaleStatic($rechnung): ?string
    {
        $teile = [];

        // 1. Leistungszeitraum (falls vorhanden)
        if ($rechnung->leistungsdaten ?? null) {
            $teile[] = sprintf(
                'Zeitraum/Periodo: %s',
                $rechnung->leistungsdaten
            );
        }

        // 2. Gebäude-Info (kompakt: Objekt/Oggetto mit Komma)
        $name = $rechnung->geb_name ?? null;
        $adresse = $rechnung->geb_adresse ?? null;

        if ($name && $adresse) {
            // Name + Adresse mit Komma getrennt
            $teile[] = sprintf(
                'Objekt/Oggetto: %s, %s',
                $name,
                $adresse
            );
        } elseif ($adresse) {
            // Nur Adresse
            $teile[] = sprintf(
                'Objekt/Oggetto: %s',
                $adresse
            );
        } elseif ($name) {
            // Nur Name
            $teile[] = sprintf(
                'Objekt/Oggetto: %s',
                $name
            );
        }

        // Zusammenfügen mit Separator " - "
        $causale = implode(' - ', $teile);

        // Max 200 Zeichen (SDI-Limit)
        return substr($causale, 0, 200) ?: null;
    }

    /**
     * Regeneriert die Causale basierend auf aktuellen Daten
     */
    public function regenerateCausale(): void
    {
        $this->fattura_causale = static::generateCausaleStatic($this);
        $this->save();
    }

    public function gebaeude(): BelongsTo
    {
        return $this->belongsTo(Gebaeude::class);
    }

    public function rechnungsempfaenger(): BelongsTo
    {
        return $this->belongsTo(Adresse::class, 'rechnungsempfaenger_id');
    }

    public function postadresse(): BelongsTo
    {
        return $this->belongsTo(Adresse::class, 'postadresse_id');
    }

    public function fatturaProfile(): BelongsTo
    {
        return $this->belongsTo(FatturaProfile::class, 'fattura_profile_id');
    }

    public function positionen(): HasMany
    {
        return $this->hasMany(RechnungPosition::class)
            ->orderBy('position');
    }

    /**
     * Log-Einträge für diese Rechnung
     */
    public function logs(): HasMany
    {
        return $this->hasMany(RechnungLog::class)
            ->orderByDesc('created_at');
    }

    // ═══════════════════════════════════════════════════════════
    // 🧮 BERECHNUNG
    // ═══════════════════════════════════════════════════════════

    /**
     * Berechnet alle Summen neu (aus den Positionen).
     * Berücksichtigt automatisch Ritenuta d'acconto bei aktiviertem Flag.
     */
    public function recalculate(): void
    {
        // Summen aus Positionen
        $netto = (float) $this->positionen->sum('netto_gesamt');
        $mwst  = (float) $this->positionen->sum('mwst_betrag');
        $brutto = (float) $this->positionen->sum('brutto_gesamt');

        // Ritenuta d'acconto (4% vom Netto, falls aktiviert)
        $ritenuta = 0.0;
        if ($this->ritenuta && $this->ritenuta_prozent > 0) {
            $ritenuta = round($netto * ((float) $this->ritenuta_prozent / 100), 2);
        }

        // Zahlbar = Brutto - Ritenuta
        $zahlbar = round($brutto - $ritenuta, 2);

        // Speichern
        $this->update([
            'netto_summe'    => $netto,
            'mwst_betrag'    => $mwst,
            'brutto_summe'   => $brutto,
            'ritenuta_betrag' => $ritenuta,
            'zahlbar_betrag' => $zahlbar,
        ]);
    }

    /**
     * Erstellt eine Rechnung aus einem Gebäude.
     * 
     * Features:
     * - Kopiert Snapshots von Gebäude, Adressen, FatturaPA-Profil
     * - Übernimmt aktive Artikel als Positionen
     * - ⭐ KORRIGIERT: Wendet Preis-Aufschläge PRO ARTIKEL basierend auf dessen basis_jahr an
     * - Markiert Timeline-Einträge als verrechnet
     * - Berechnet Leistungsdaten aus Timeline
     * 
     * @param Gebaeude $gebaeude
     * @param array $overrides Optionale Überschreibungen
     * @return self
     */
    public static function createFromGebaeude(
        Gebaeude $gebaeude,
        array $overrides = [],
        bool $istJahresrechnung = false
    ): self {
        // Jahr / Laufnummer ermitteln (mit Lock)
        $jahr = now()->year;

        $laufnummer = DB::transaction(function () use ($jahr) {
            $maxLaufnummer = (int) self::where('jahr', $jahr)
                ->lockForUpdate()
                ->max('laufnummer');
            return $maxLaufnummer + 1;
        });

        // Zugeordnete Adressen / Profile
        $rechnungsempfaenger = $gebaeude->rechnungsempfaenger;
        $postadresse         = $gebaeude->postadresse;
        $profile             = $gebaeude->fatturaProfile;

        // ═══════════════════════════════════════════════════════════
        // 🕒 Timeline-Einträge verarbeiten
        // ═══════════════════════════════════════════════════════════

        $timelineEintraege = \App\Models\Timeline::where('gebaeude_id', $gebaeude->id)
            ->where('verrechnen', true)
            ->whereNull('deleted_at')
            ->orderBy('datum')
            ->get();

        // ⭐ JAHRESRECHNUNG: Nur "Jahr/anno XXXX" statt Timeline-Daten
        if ($istJahresrechnung) {
            $leistungsdaten = "Jahr/anno {$jahr}";

            \Log::info('Jahresrechnung erstellt - Causale mit Jahr statt Timeline-Daten', [
                'gebaeude_id'                  => $gebaeude->id,
                'gebaeude_codex'               => $gebaeude->codex,
                'jahr'                         => $jahr,
                'timeline_eintraege_vorhanden' => $timelineEintraege->count(),
                'profil'                       => $profile?->bezeichnung,
            ]);
        } else {
            $leistungsdaten = self::formatLeistungsdaten($timelineEintraege, $jahr);
        }

        // ═══════════════════════════════════════════════════════════
        // 💰 AUFSCHLAG-TYP ERMITTELN (für Tracking)
        // ═══════════════════════════════════════════════════════════

        // Prüfen ob individueller Aufschlag existiert
        $gebaeudeAufschlag = \App\Models\GebaeudeAufschlag::fuerGebaeude($gebaeude->id)
            ->gueltig(now())
            ->first();

        $aufschlagTyp = 'global';
        if ($gebaeudeAufschlag) {
            $aufschlagTyp = 'individuell';
        }

        // ═══════════════════════════════════════════════════════════
        // ⭐ ZAHLUNGSBEDINGUNGEN DEFAULT
        // ═══════════════════════════════════════════════════════════

        $zahlungsbedingungen = $overrides['zahlungsbedingungen'] ?? Zahlungsbedingung::NETTO_30;
        $rechnungsdatum = Carbon::parse($overrides['rechnungsdatum'] ?? now());

        // Zahlungsziel automatisch berechnen
        $zahlungsziel = static::berechneZahlungsziel($rechnungsdatum, $zahlungsbedingungen);

        // ═══════════════════════════════════════════════════════════
        // 📄 RECHNUNG ERSTELLEN
        // ═══════════════════════════════════════════════════════════

        $rechnung = new self(array_merge([
            'jahr'                    => $jahr,
            'laufnummer'              => $laufnummer,
            'gebaeude_id'             => $gebaeude->id,
            'rechnungsempfaenger_id'  => $gebaeude->rechnungsempfaenger_id,
            'postadresse_id'          => $gebaeude->postadresse_id,
            'fattura_profile_id'      => $gebaeude->fattura_profile_id,

            // Datumsfelder
            'rechnungsdatum'          => $rechnungsdatum->toDateString(),
            'leistungsdaten'          => $leistungsdaten,
            'zahlungsziel'            => $zahlungsziel->toDateString(),
            'zahlungsbedingungen'     => $zahlungsbedingungen,

            // Status
            'status'                  => 'draft',
            'typ_rechnung'            => 'rechnung',

            // Snapshot Rechnungsempfänger
            're_name'                 => $rechnungsempfaenger->name,
            're_strasse'              => $rechnungsempfaenger->strasse,
            're_hausnummer'           => $rechnungsempfaenger->hausnummer,
            're_plz'                  => $rechnungsempfaenger->plz,
            're_wohnort'              => $rechnungsempfaenger->wohnort,
            're_provinz'              => $rechnungsempfaenger->provinz,
            're_land'                 => $rechnungsempfaenger->land,
            're_steuernummer'         => $rechnungsempfaenger->steuernummer,
            're_mwst_nummer'          => $rechnungsempfaenger->mwst_nummer,
            're_codice_univoco'       => $rechnungsempfaenger->codice_univoco,
            're_pec'                  => $rechnungsempfaenger->pec,

            // Snapshot Postadresse
            'post_name'               => $postadresse->name,
            'post_strasse'            => $postadresse->strasse,
            'post_hausnummer'         => $postadresse->hausnummer,
            'post_plz'                => $postadresse->plz,
            'post_wohnort'            => $postadresse->wohnort,
            'post_provinz'            => $postadresse->provinz,
            'post_land'               => $postadresse->land,
            'post_email'              => $postadresse->email,
            'post_pec'                => $postadresse->pec,

            // Snapshot Gebäude
            'geb_codex'               => $gebaeude->codex,
            'geb_name'                => $gebaeude->gebaeude_name,
            'geb_adresse'             => sprintf(
                '%s %s, %s %s',
                $gebaeude->strasse,
                $gebaeude->hausnummer,
                $gebaeude->plz,
                $gebaeude->wohnort
            ),

            // FatturaPA
            'cup'                     => $gebaeude->cup,
            'cig'                     => $gebaeude->cig,
            'codice_commessa'         => $gebaeude->codice_commessa,
            'auftrag_id'              => $gebaeude->auftrag_id,
            'auftrag_datum'           => $gebaeude->auftrag_datum,

            // Profil-Einstellungen (Snapshot)
            'profile_bezeichnung'     => $profile?->bezeichnung,
            'mwst_satz'               => $profile?->mwst_satz ?? 22.00,
            'split_payment'           => $profile?->split_payment ?? false,
            'reverse_charge'          => $profile?->reverse_charge ?? false,
            'ritenuta'                => $profile?->ritenuta ?? false,
            'ritenuta_prozent'        => $profile?->ritenuta ? 4.00 : null,

            // Aufschlag-Typ (wird unten aktualisiert)
            'aufschlag_prozent'       => 0.0,
            'aufschlag_typ'           => $aufschlagTyp,
        ], $overrides));

        $rechnung->save();

        // ═══════════════════════════════════════════════════════════
        // 📦 POSITIONEN ERSTELLEN (Pro Artikel basis_jahr!)
        // ═══════════════════════════════════════════════════════════

        $artikelListe = $gebaeude->aktiveArtikel()
            ->orderBy('reihenfolge')
            ->get();

        $position = 1;
        $totalAufschlag = 0.0;
        $artikelMitAufschlag = 0;

        foreach ($artikelListe as $artikel) {
            $mwstSatz = $profile?->mwst_satz ?? 22.00;

            // Preis mit kumulativem Aufschlag basierend auf ARTIKEL basis_jahr
            $basisPreis = (float) ($artikel->basis_preis ?? $artikel->einzelpreis);
            $artikelBasisJahr = (int) ($artikel->basis_jahr ?? $jahr);

            // Berechne den angepassten Preis für diesen Artikel
            $einzelpreisAngepasst = $gebaeude->berechnePreisMitKumulativerErhoehung(
                $basisPreis,
                $artikelBasisJahr,
                $jahr
            );

            // Aufschlag-Tracking
            $aufschlagBetrag = $einzelpreisAngepasst - $basisPreis;
            if ($aufschlagBetrag > 0 && $basisPreis > 0) {
                $prozent = ($aufschlagBetrag / $basisPreis) * 100;
                $totalAufschlag += $prozent;
                $artikelMitAufschlag++;
            }

            \Log::debug('Preis angepasst (pro Artikel basis_jahr)', [
                'artikel'          => $artikel->beschreibung,
                'basis_preis'      => $basisPreis,
                'basis_jahr'       => $artikelBasisJahr,
                'ziel_jahr'        => $jahr,
                'neu'              => $einzelpreisAngepasst,
                'aufschlag_betrag' => $aufschlagBetrag,
            ]);

            $rechnung->positionen()->create([
                'position'             => $position++,
                'beschreibung'         => $artikel->beschreibung,
                'anzahl'               => $artikel->anzahl,
                'einheit'              => 'Stk',
                'einzelpreis'          => $einzelpreisAngepasst,
                'mwst_satz'            => $mwstSatz,
                'artikel_gebaeude_id'  => $artikel->id,
            ]);
        }

        // Durchschnittlichen Aufschlag speichern (für Tracking)
        if ($artikelMitAufschlag > 0) {
            $durchschnittsAufschlag = round($totalAufschlag / $artikelMitAufschlag, 2);
            $rechnung->update(['aufschlag_prozent' => $durchschnittsAufschlag]);
        }

        // Abschließende Neuberechnung aller Summen
        $rechnung->recalculate();

        // ═══════════════════════════════════════════════════════════
        // 🕒 Timeline-Einträge als verrechnet markieren
        // ═══════════════════════════════════════════════════════════

        if ($timelineEintraege->isNotEmpty()) {
            $rechnungNummer = sprintf('%d/%04d', $rechnung->jahr, $rechnung->laufnummer);

            foreach ($timelineEintraege as $timeline) {
                $timeline->update([
                    'verrechnen'                => false,
                    'verrechnet_am'             => now()->toDateString(),
                    'verrechnet_mit_rn_nummer'  => $rechnungNummer,
                ]);
            }

            \Log::info('Timeline-Einträge als verrechnet markiert', [
                'rechnung_id'      => $rechnung->id,
                'rechnung_nummer'  => $rechnungNummer,
                'anzahl_eintraege' => $timelineEintraege->count(),
            ]);
        }

        return $rechnung;
    }

    /**
     * Formatiert Timeline-Einträge zu einem Leistungsdaten-String.
     */
    protected static function formatLeistungsdaten($timelineEintraege, ?int $jahr = null): string
    {
        if ($timelineEintraege->isEmpty()) {
            $jahr = $jahr ?? now()->year;
            return "Jahr/anno {$jahr}";
        }

        $daten = $timelineEintraege
            ->pluck('datum')
            ->map(fn($datum) => \Carbon\Carbon::parse($datum))
            ->sort()
            ->unique()
            ->values();

        if ($daten->count() === 1) {
            return $daten->first()->format('d.m.Y');
        }

        $erstesDatum = $daten->first();
        $letztesDatum = $daten->last();
        $differenzTage = $erstesDatum->diffInDays($letztesDatum);

        if ($differenzTage <= 7 && $daten->count() >= 3) {
            return sprintf(
                '%s - %s',
                $erstesDatum->format('d.m.Y'),
                $letztesDatum->format('d.m.Y')
            );
        }

        if ($daten->count() > 10) {
            $gezeigt = $daten->take(10)
                ->map(fn($d) => $d->format('d.m.Y'))
                ->join(', ');
            return $gezeigt . ' ...';
        }

        return $daten
            ->map(fn($d) => $d->format('d.m.Y'))
            ->join(', ');
    }

    // ═══════════════════════════════════════════════════════════
    // 🎯 SCOPES
    // ═══════════════════════════════════════════════════════════

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', '!=', 'paid')
            ->where('status', '!=', 'cancelled')
            ->where('zahlungsziel', '<', now());
    }

    public function scopeYear($query, int $year)
    {
        return $query->where('jahr', $year);
    }

    // ═══════════════════════════════════════════════════════════
    // 🏷️ ACCESSORS
    // ═══════════════════════════════════════════════════════════

    /**
     * Formatierte Rechnungsnummer (z.B. "2025/0042")
     */
    public function getRechnungsnummerAttribute(): string
    {
        return sprintf('%d/%04d', $this->jahr, $this->laufnummer);
    }

    /**
     * Status-Badge für UI
     */
    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'draft'     => '<span class="badge bg-secondary">Entwurf</span>',
            'sent'      => '<span class="badge bg-info">Versendet</span>',
            'paid'      => '<span class="badge bg-success">Bezahlt</span>',
            'cancelled' => '<span class="badge bg-danger">Storniert</span>',
            default     => '<span class="badge bg-light text-dark">' . $this->status . '</span>',
        };
    }

    /**
     * Aufschlag-Info für UI
     */
    public function getAufschlagInfoAttribute(): string
    {
        if (!$this->aufschlag_prozent || $this->aufschlag_prozent == 0) {
            return 'Kein Aufschlag';
        }

        $typ = $this->aufschlag_typ === 'individuell' ? 'Individuell' : 'Global';
        return sprintf('%s: %+.2f%%', $typ, $this->aufschlag_prozent);
    }

    /**
     * Prüft, ob die Rechnung editierbar ist.
     * Nur Rechnungen mit Status 'draft' können bearbeitet werden.
     * 
     * @return bool
     */
    public function getIstEditierbarAttribute(): bool
    {
        return $this->status === 'draft';
    }

    // ═══════════════════════════════════════════════════════════
    // 💰 ZAHLUNGSBEDINGUNG & FÄLLIGKEIT
    // ═══════════════════════════════════════════════════════════

    /**
     * Zahlungsbedingung als deutschen Text.
     * 
     * @return string
     */
    public function getZahlungsbedingungenLabelAttribute(): string
    {
        return $this->zahlungsbedingungen?->label() ?? 'Nicht gesetzt';
    }

    /**
     * Anzahl Tage der Zahlungsbedingung.
     * 
     * @return int
     */
    public function getZahlungsbedingungenTageAttribute(): int
    {
        return $this->zahlungsbedingungen?->tage() ?? 30;
    }

    /**
     * Badge für Zahlungsbedingung (für UI).
     * 
     * @return string HTML Badge
     */
    public function getZahlungsbedingungenBadgeAttribute(): string
    {
        if (!$this->zahlungsbedingungen) {
            return '<span class="badge bg-secondary">Nicht gesetzt</span>';
        }

        $class = $this->zahlungsbedingungen->badgeClass();
        $label = $this->zahlungsbedingungen->label();

        return "<span class=\"badge {$class}\">{$label}</span>";
    }

    /**
     * Ist die Rechnung bereits als bezahlt markiert?
     * 
     * @return bool
     */
    public function istAlsBezahltMarkiert(): bool
    {
        return $this->zahlungsbedingungen === Zahlungsbedingung::BEZAHLT;
    }

    /**
     * Berechnet das Fälligkeitsdatum basierend auf Zahlungsbedingung.
     * 
     * Falls bereits ein zahlungsziel gesetzt ist, wird dieses verwendet.
     * Ansonsten: rechnungsdatum + Zahlungsbedingung-Tage.
     * 
     * @return Carbon|null
     */
    public function getFaelligkeitsdatumAttribute(): ?Carbon
    {
        // Falls manuell gesetzt
        if ($this->zahlungsziel) {
            return $this->zahlungsziel;
        }

        // Falls kein Rechnungsdatum
        if (!$this->rechnungsdatum) {
            return null;
        }

        // Berechne aus Zahlungsbedingung
        $tage = $this->zahlungsbedingungen_tage;

        return $this->rechnungsdatum->copy()->addDays($tage);
    }

    /**
     * Ist die Rechnung überfällig?
     * 
     * @return bool
     */
    public function istUeberfaellig(): bool
    {
        // Bereits bezahlt? → Nicht überfällig
        if ($this->istAlsBezahltMarkiert()) {
            return false;
        }

        // Status 'paid' → Nicht überfällig
        if ($this->status === 'paid') {
            return false;
        }

        $faelligkeit = $this->faelligkeitsdatum;

        if (!$faelligkeit) {
            return false;
        }

        return $faelligkeit->isPast();
    }

    /**
     * Tage bis Fälligkeit (negativ = überfällig).
     * 
     * @return int|null
     */
    public function getTagebisFaelligkeitAttribute(): ?int
    {
        $faelligkeit = $this->faelligkeitsdatum;

        if (!$faelligkeit) {
            return null;
        }

        return now()->startOfDay()->diffInDays($faelligkeit->startOfDay(), false);
    }

    /**
     * Fälligkeits-Status als Badge.
     * 
     * @return string HTML Badge
     */
    public function getFaelligkeitsStatusBadgeAttribute(): string
    {
        if ($this->istAlsBezahltMarkiert() || $this->status === 'paid') {
            return '<span class="badge bg-success"><i class="bi bi-check-circle"></i> Bezahlt</span>';
        }

        if ($this->status === 'cancelled') {
            return '<span class="badge bg-danger"><i class="bi bi-x-circle"></i> Storniert</span>';
        }

        if ($this->istUeberfaellig()) {
            $tage = abs($this->tage_bis_faelligkeit);
            return "<span class=\"badge bg-danger\"><i class=\"bi bi-exclamation-triangle\"></i> Überfällig ({$tage} Tage)</span>";
        }

        $tage = $this->tage_bis_faelligkeit;

        if ($tage === null) {
            return '<span class="badge bg-secondary">Keine Fälligkeit</span>';
        }

        if ($tage <= 7) {
            return "<span class=\"badge bg-warning text-dark\"><i class=\"bi bi-clock\"></i> Fällig in {$tage} Tagen</span>";
        }

        return "<span class=\"badge bg-info\"><i class=\"bi bi-calendar\"></i> Fällig in {$tage} Tagen</span>";
    }

    /**
     * Markiert Rechnung als bezahlt.
     * 
     * @param Carbon|null $bezahltAm
     * @return void
     */
    public function markiereAlsBezahlt(?Carbon $bezahltAm = null): void
    {
        $this->zahlungsbedingungen = Zahlungsbedingung::BEZAHLT;
        $this->status = 'paid';
        $this->bezahlt_am = $bezahltAm ?? now();
        $this->save();
    }

    // ═══════════════════════════════════════════════════════════
    // 📊 ZUSÄTZLICHE SCOPES FÜR ZAHLUNGSBEDINGUNG
    // ═══════════════════════════════════════════════════════════

    /**
     * Scope: Nur bezahlte Rechnungen.
     */
    public function scopeBezahlt($query)
    {
        return $query->where(function ($q) {
            $q->where('status', 'paid')
                ->orWhere('zahlungsbedingungen', Zahlungsbedingung::BEZAHLT->value);
        });
    }

    /**
     * Scope: Nur unbezahlte Rechnungen.
     */
    public function scopeUnbezahlt($query)
    {
        return $query->where('status', '!=', 'paid')
            ->where(function ($q) {
                $q->whereNull('zahlungsbedingungen')
                    ->orWhere('zahlungsbedingungen', '!=', Zahlungsbedingung::BEZAHLT->value);
            });
    }

    /**
     * Scope: Überfällige Rechnungen.
     */
    public function scopeUeberfaellig($query)
    {
        return $query->where('status', '!=', 'paid')
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) {
                $q->whereNull('zahlungsbedingungen')
                    ->orWhere('zahlungsbedingungen', '!=', Zahlungsbedingung::BEZAHLT->value);
            })
            ->where(function ($q) {
                $q->whereDate('zahlungsziel', '<', now())
                    ->orWhere(function ($q2) {
                        $q2->whereNull('zahlungsziel')
                            ->whereDate('rechnungsdatum', '<', now()->subDays(30));
                    });
            });
    }

    /**
     * Scope: Bald fällig (innerhalb X Tagen).
     */
    public function scopeBaldFaellig($query, int $tage = 7)
    {
        $bis = now()->addDays($tage);

        return $query->where('status', '!=', 'paid')
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) {
                $q->whereNull('zahlungsbedingungen')
                    ->orWhere('zahlungsbedingungen', '!=', Zahlungsbedingung::BEZAHLT->value);
            })
            ->where(function ($q) use ($bis) {
                $q->whereBetween('zahlungsziel', [now(), $bis])
                    ->orWhere(function ($q2) use ($bis) {
                        $q2->whereNull('zahlungsziel')
                            ->whereBetween(DB::raw('DATE_ADD(rechnungsdatum, INTERVAL 30 DAY)'), [now(), $bis]);
                    });
            });
    }

    /**
     * Scope: Offene Rechnungen (sent, aber nicht paid).
     */
    public function scopeOffen($query)
    {
        return $query->where('status', 'sent')
            ->where(function ($q) {
                $q->whereNull('zahlungsbedingungen')
                    ->orWhere('zahlungsbedingungen', '!=', Zahlungsbedingung::BEZAHLT->value);
            });
    }

    public function getErwarteterZahlbetragAttribute(): float
    {
        // 1. Primär: zahlbar_betrag wenn bereits korrekt gesetzt
        if ($this->zahlbar_betrag !== null && (float) $this->zahlbar_betrag > 0) {
            return (float) $this->zahlbar_betrag;
        }

        // 2. Berechnung basierend auf Rechnungstyp
        $brutto = (float) ($this->brutto_summe ?? 0);
        $netto = (float) ($this->netto_summe ?? $brutto);
        $mwst = (float) ($this->mwst_betrag ?? ($brutto - $netto));
        $ritenuta = (float) ($this->ritenuta_betrag ?? 0);

        // Prüfe FatturaProfile oder direkte Flags
        $profile = $this->fatturaProfile;

        $isSplitPayment = $profile?->split_payment
            ?? $this->split_payment
            ?? false;

        $isReverseCharge = $profile?->reverse_charge
            ?? $this->reverse_charge
            ?? ($this->natura_esenzione !== null && in_array($this->natura_esenzione, ['N2', 'N2.1', 'N2.2', 'N3', 'N3.1', 'N3.2', 'N3.3', 'N3.4', 'N3.5', 'N3.6', 'N6', 'N6.1', 'N6.2', 'N6.3', 'N6.4', 'N6.5', 'N6.6', 'N6.7', 'N6.8', 'N6.9']))
            ?? false;

        // Ritenuta aus Profil holen falls nicht direkt gesetzt
        if ($ritenuta == 0 && $profile?->ritenuta > 0) {
            $ritenutaSatz = (float) $profile->ritenuta;
            $ritenuta = round($netto * ($ritenutaSatz / 100), 2);
        }

        // ─────────────────────────────────────────────────────────────────────
        // BERECHNUNG
        // ─────────────────────────────────────────────────────────────────────

        // Reverse Charge: Kunde zahlt nur Netto (MwSt wird vom Kunden selbst abgeführt)
        if ($isReverseCharge) {
            return round($netto - $ritenuta, 2);
        }

        // Split-Payment: Kunde zahlt Netto, MwSt geht direkt an Finanzamt
        if ($isSplitPayment) {
            return round($netto - $ritenuta, 2);
        }

        // Ritenuta ohne Split-Payment: Brutto minus Ritenuta
        if ($ritenuta > 0) {
            return round($brutto - $ritenuta, 2);
        }

        // Normal: Brutto
        return $brutto;
    }

    /**
     * Formatierter erwarteter Zahlbetrag
     */
    public function getErwarteterZahlbetragFormatAttribute(): string
    {
        return number_format($this->erwarteter_zahlbetrag, 2, ',', '.') . ' €';
    }

    /**
     * Erklärt wie der Zahlbetrag zustande kommt
     */
    public function getZahlbetragErklaerungAttribute(): string
    {
        $profile = $this->fatturaProfile;
        $isSplitPayment = $profile?->split_payment ?? $this->split_payment ?? false;
        $isReverseCharge = $profile?->reverse_charge ?? $this->reverse_charge ?? false;
        $ritenuta = (float) ($this->ritenuta_betrag ?? 0);

        if ($isReverseCharge) {
            return 'Reverse Charge: Netto' . ($ritenuta > 0 ? ' − Ritenuta' : '');
        }

        if ($isSplitPayment) {
            return 'Split-Payment: Netto' . ($ritenuta > 0 ? ' − Ritenuta' : '');
        }

        if ($ritenuta > 0) {
            return 'Brutto − Ritenuta';
        }

        return 'Brutto';
    }


    /**
     * ══════════════════════════════════════════════════════════════════════════════
     * METHODE FÜR: app/Models/Rechnung.php
     * ══════════════════════════════════════════════════════════════════════════════
     * 
     * Diese Methode in die Rechnung Model-Klasse einfügen.
     * 
     * Benötigte Imports (falls nicht vorhanden):
     * use App\Models\RechnungPosition;
     * use App\Models\RechnungLog;
     * use App\Enums\RechnungLogTyp;
     * use Illuminate\Support\Facades\DB;
     */

    /**
     * Erstellt eine Gutschrift aus dieser Rechnung.
     * 
     * - Kopiert alle Daten (Snapshots, Positionen)
     * - Neue Laufnummer im gleichen Nummernkreis
     * - typ_rechnung = 'gutschrift'
     * - Bemerkung wird gesetzt: "Gutschrift zu Rechnung XXXX/YYYY"
     * - Log-Eintrag wird erstellt
     * 
     * @return self Die neue Gutschrift
     */
    public function erstelleGutschrift(): self
    {
        // Jahr / Laufnummer ermitteln (mit Lock)
        $jahr = now()->year;

        $laufnummer = DB::transaction(function () use ($jahr) {
            $maxLaufnummer = (int) self::where('jahr', $jahr)
                ->lockForUpdate()
                ->max('laufnummer');
            return $maxLaufnummer + 1;
        });

        // Alle Felder kopieren außer die auszuschließenden
        $exclude = [
            'id',
            'created_at',
            'updated_at',
            'deleted_at',
            'jahr',
            'laufnummer',
            'rechnungsdatum',
            'zahlungsziel',
            'bezahlt_am',
            'status',
            'typ_rechnung',
            'pdf_pfad',
            'xml_pfad',
            'bemerkung',  // Wird neu gesetzt
        ];

        $data = collect($this->attributes)
            ->except($exclude)
            ->toArray();

        // Neue Werte setzen
        $data['jahr'] = $jahr;
        $data['laufnummer'] = $laufnummer;
        $data['rechnungsdatum'] = now()->toDateString();
        $data['zahlungsziel'] = static::berechneZahlungsziel(now(), $this->zahlungsbedingungen)?->toDateString();
        $data['status'] = 'draft';
        $data['typ_rechnung'] = 'gutschrift';
        $data['bezahlt_am'] = null;

        // ⭐ Bemerkung mit Referenz zur Original-Rechnung
        $data['bemerkung'] = "Gutschrift zu Rechnung {$this->rechnungsnummer}";

        // Gutschrift erstellen
        $gutschrift = self::create($data);

        // Positionen kopieren
        foreach ($this->positionen as $position) {
            $positionData = collect($position->attributes)
                ->except(['id', 'rechnung_id', 'created_at', 'updated_at', 'deleted_at'])
                ->toArray();

            $positionData['rechnung_id'] = $gutschrift->id;

            RechnungPosition::create($positionData);
        }

        // Summen neu berechnen (sicherheitshalber)
        $gutschrift->recalculate();

        // Log-Eintrag bei Original-Rechnung
        RechnungLog::create([
            'rechnung_id' => $this->id,
            'typ'         => RechnungLogTyp::GUTSCHRIFT_ERSTELLT,
            'titel'       => 'Gutschrift erstellt',
            'nachricht'   => "Gutschrift {$gutschrift->rechnungsnummer} wurde aus dieser Rechnung erstellt.",
            'metadata'    => [
                'gutschrift_id'     => $gutschrift->id,
                'gutschrift_nummer' => $gutschrift->rechnungsnummer,
                'betrag'            => $gutschrift->brutto_summe,
            ],
        ]);

        // Log-Eintrag bei Gutschrift
        RechnungLog::create([
            'rechnung_id' => $gutschrift->id,
            'typ'         => RechnungLogTyp::RECHNUNG_ERSTELLT,
            'titel'       => 'Gutschrift erstellt',
            'nachricht'   => "Erstellt aus Rechnung {$this->rechnungsnummer}.",
            'metadata'    => [
                'original_rechnung_id'     => $this->id,
                'original_rechnung_nummer' => $this->rechnungsnummer,
            ],
        ]);

        return $gutschrift;
    }
}
