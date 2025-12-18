<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class BankMatchingConfig extends Model
{
    protected $table = 'bank_matching_config';

    protected $fillable = [
        'score_iban_match',
        'score_cig_match',
        'score_rechnungsnr_match',
        'score_betrag_exakt',
        'score_betrag_nah',
        'score_betrag_abweichung',
        'score_name_token_exact',
        'score_name_token_partial',
        'auto_match_threshold',
        'betrag_abweichung_limit',
        'betrag_toleranz_exakt',
        'betrag_toleranz_nah',
    ];

    protected $casts = [
        'score_iban_match'         => 'integer',
        'score_cig_match'          => 'integer',
        'score_rechnungsnr_match'  => 'integer',
        'score_betrag_exakt'       => 'integer',
        'score_betrag_nah'         => 'integer',
        'score_betrag_abweichung'  => 'integer',
        'score_name_token_exact'   => 'integer',
        'score_name_token_partial' => 'integer',
        'auto_match_threshold'     => 'integer',
        'betrag_abweichung_limit'  => 'integer',
        'betrag_toleranz_exakt'    => 'decimal:2',
        'betrag_toleranz_nah'      => 'decimal:2',
    ];

    // ═══════════════════════════════════════════════════════════════════════
    // SINGLETON PATTERN - Es gibt nur eine Konfiguration
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Holt die aktuelle Konfiguration (mit Cache)
     */
    public static function getConfig(): self
    {
        return Cache::remember('bank_matching_config', 3600, function () {
            return self::first() ?? self::createDefault();
        });
    }

    /**
     * Erstellt Standard-Konfiguration falls keine existiert
     */
    public static function createDefault(): self
    {
        return self::create([
            'score_iban_match'         => 100,
            'score_cig_match'          => 80,
            'score_rechnungsnr_match'  => 50,
            'score_betrag_exakt'       => 30,
            'score_betrag_nah'         => 15,
            'score_betrag_abweichung'  => -40,
            'score_name_token_exact'   => 10,
            'score_name_token_partial' => 5,
            'auto_match_threshold'     => 80,
            'betrag_abweichung_limit'  => 30,
            'betrag_toleranz_exakt'    => 0.10,
            'betrag_toleranz_nah'      => 2.00,
        ]);
    }

    /**
     * Cache leeren nach Speichern
     */
    protected static function booted(): void
    {
        static::saved(function () {
            Cache::forget('bank_matching_config');
        });
    }

    // ═══════════════════════════════════════════════════════════════════════
    // HELPER - Einzelne Werte abrufen
    // ═══════════════════════════════════════════════════════════════════════

    public static function get(string $key, $default = null)
    {
        $config = self::getConfig();
        return $config->{$key} ?? $default;
    }

    // ═══════════════════════════════════════════════════════════════════════
    // BESCHREIBUNGEN für UI
    // ═══════════════════════════════════════════════════════════════════════

    public static function getFieldDescriptions(): array
    {
        return [
            'score_iban_match' => [
                'label' => 'IBAN-Match',
                'description' => 'Punkte wenn IBAN im Gebäude-Template gefunden wird',
                'icon' => 'bi-bank',
                'group' => 'scores',
            ],
            'score_cig_match' => [
                'label' => 'CIG-Match',
                'description' => 'Punkte wenn CIG-Nummer übereinstimmt',
                'icon' => 'bi-hash',
                'group' => 'scores',
            ],
            'score_rechnungsnr_match' => [
                'label' => 'Rechnungsnummer-Match',
                'description' => 'Punkte wenn Rechnungsnummer im Verwendungszweck gefunden',
                'icon' => 'bi-receipt',
                'group' => 'scores',
            ],
            'score_betrag_exakt' => [
                'label' => 'Betrag exakt',
                'description' => 'Punkte wenn Betrag innerhalb enger Toleranz',
                'icon' => 'bi-bullseye',
                'group' => 'scores',
            ],
            'score_betrag_nah' => [
                'label' => 'Betrag nah',
                'description' => 'Punkte wenn Betrag innerhalb größerer Toleranz (z.B. Ritenuta)',
                'icon' => 'bi-target',
                'group' => 'scores',
            ],
            'score_betrag_abweichung' => [
                'label' => 'Betrag-Abweichung (Malus)',
                'description' => 'Abzug wenn Betrag stark abweicht (negativ!)',
                'icon' => 'bi-exclamation-triangle',
                'group' => 'scores',
            ],
            'score_name_token_exact' => [
                'label' => 'Name exakt',
                'description' => 'Punkte pro exaktem Namen-Token-Match',
                'icon' => 'bi-person-check',
                'group' => 'scores',
            ],
            'score_name_token_partial' => [
                'label' => 'Name teilweise',
                'description' => 'Punkte pro teilweisem Namen-Match',
                'icon' => 'bi-person',
                'group' => 'scores',
            ],
            'auto_match_threshold' => [
                'label' => 'Auto-Match Schwelle',
                'description' => 'Mindest-Score für automatische Zuordnung',
                'icon' => 'bi-lightning',
                'group' => 'thresholds',
            ],
            'betrag_abweichung_limit' => [
                'label' => 'Abweichungs-Limit (%)',
                'description' => 'Prozent-Abweichung ab der Malus greift',
                'icon' => 'bi-percent',
                'group' => 'thresholds',
            ],
            'betrag_toleranz_exakt' => [
                'label' => 'Toleranz exakt (€)',
                'description' => 'Max. Abweichung für "exakten" Betrag-Match',
                'icon' => 'bi-currency-euro',
                'group' => 'tolerances',
            ],
            'betrag_toleranz_nah' => [
                'label' => 'Toleranz nah (€)',
                'description' => 'Max. Abweichung für "nahen" Betrag-Match',
                'icon' => 'bi-currency-euro',
                'group' => 'tolerances',
            ],
        ];
    }
}
