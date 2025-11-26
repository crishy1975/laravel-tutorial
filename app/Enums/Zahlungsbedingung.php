<?php

namespace App\Enums;

/**
 * Zahlungsbedingungen Enum
 * 
 * Definiert alle möglichen Zahlungsbedingungen für Rechnungen.
 */
enum Zahlungsbedingung: string
{
    case SOFORT = 'sofort';
    case NETTO_7 = 'netto_7';
    case NETTO_14 = 'netto_14';
    case NETTO_30 = 'netto_30';
    case NETTO_60 = 'netto_60';
    case NETTO_90 = 'netto_90';
    case NETTO_120 = 'netto_120';
    case BEZAHLT = 'bezahlt';

    /**
     * Gibt den deutschen Label zurück.
     */
    public function label(): string
    {
        return match($this) {
            self::SOFORT => 'Sofort zahlbar',
            self::NETTO_7 => 'Netto 7 Tage',
            self::NETTO_14 => 'Netto 14 Tage',
            self::NETTO_30 => 'Netto 30 Tage',
            self::NETTO_60 => 'Netto 60 Tage',
            self::NETTO_90 => 'Netto 90 Tage',
            self::NETTO_120 => 'Netto 120 Tage',
            self::BEZAHLT => 'Bereits bezahlt',
        };
    }

    /**
     * Gibt die Anzahl Tage zurück.
     */
    public function tage(): int
    {
        return match($this) {
            self::SOFORT => 0,
            self::NETTO_7 => 7,
            self::NETTO_14 => 14,
            self::NETTO_30 => 30,
            self::NETTO_60 => 60,
            self::NETTO_90 => 90,
            self::NETTO_120 => 120,
            self::BEZAHLT => 0,
        };
    }

    /**
     * Ist bereits bezahlt?
     */
    public function istBezahlt(): bool
    {
        return $this === self::BEZAHLT;
    }

    /**
     * Badge-Farbe für UI.
     */
    public function badgeClass(): string
    {
        return match($this) {
            self::BEZAHLT => 'bg-success',
            self::SOFORT => 'bg-warning',
            self::NETTO_7, self::NETTO_14 => 'bg-info',
            self::NETTO_30 => 'bg-primary',
            self::NETTO_60, self::NETTO_90, self::NETTO_120 => 'bg-secondary',
        };
    }

    /**
     * Alle Optionen als Array für Dropdowns.
     * 
     * @return array<string, string> ['sofort' => 'Sofort zahlbar', ...]
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }
        return $options;
    }

    /**
     * Optionen ohne "Bezahlt" (für neue Rechnungen).
     * 
     * @return array<string, string>
     */
    public static function optionsOhneBezahlt(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            if ($case !== self::BEZAHLT) {
                $options[$case->value] = $case->label();
            }
        }
        return $options;
    }

    /**
     * Standard-Zahlungsbedingung (Netto 30 Tage).
     */
    public static function standard(): self
    {
        return self::NETTO_30;
    }

    /**
     * Erstellt Enum aus altem String-Wert (Migration Helper).
     * 
     * @param string|null $alt Alter Wert wie "30 Tage", "bezahlt", etc.
     * @return self|null
     */
    public static function fromLegacyString(?string $alt): ?self
    {
        if (empty($alt)) {
            return null;
        }

        $alt = strtolower(trim($alt));

        // Direkte Matches
        if ($alt === 'bezahlt' || $alt === 'paid') {
            return self::BEZAHLT;
        }

        if ($alt === 'sofort') {
            return self::SOFORT;
        }

        // Extrahiere Zahlen aus String
        if (preg_match('/(\d+)/', $alt, $matches)) {
            $tage = (int) $matches[1];

            return match($tage) {
                7 => self::NETTO_7,
                14 => self::NETTO_14,
                30 => self::NETTO_30,
                60 => self::NETTO_60,
                90 => self::NETTO_90,
                120 => self::NETTO_120,
                default => self::NETTO_30, // Fallback
            };
        }

        // Fallback: Standard
        return self::NETTO_30;
    }
}