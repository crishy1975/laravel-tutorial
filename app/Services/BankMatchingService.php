<?php

namespace App\Services;

use App\Models\BankBuchung;
use App\Models\Rechnung;
use App\Models\Gebaeude;
use App\Models\BankMatchingConfig;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class BankMatchingService
{
    // ═══════════════════════════════════════════════════════════════════════
    // KONFIGURATION (wird aus DB geladen)
    // ═══════════════════════════════════════════════════════════════════════
    
    protected ?BankMatchingConfig $config = null;
    
    /**
     * Holt die aktuelle Konfiguration
     */
    protected function getConfig(): BankMatchingConfig
    {
        if ($this->config === null) {
            $this->config = BankMatchingConfig::getConfig();
        }
        return $this->config;
    }
    
    // Füllwörter die ignoriert werden
    const STOP_WORDS = [
        'di', 'da', 'del', 'della', 'dei', 'degli', 'delle', 'e', 'ed', 'o',
        'a', 'al', 'alla', 'ai', 'alle', 'in', 'con', 'su', 'per', 'tra', 'fra',
        'il', 'lo', 'la', 'i', 'gli', 'le', 'un', 'uno', 'una',
        'srl', 'spa', 'snc', 'sas', 'srl', 'gmbh', 'kg', 'ohg', 'ag',
        'dr', 'ing', 'geom', 'arch', 'rag', 'dott', 'sig', 'via', 'str',
    ];

    // ═══════════════════════════════════════════════════════════════════════
    // HAUPT-METHODEN
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Findet potentielle Rechnungs-Matches für eine Buchung
     * 
     * @param BankBuchung $buchung
     * @param int $limit Max. Anzahl Ergebnisse
     * @param bool $includePaid Auch bereits bezahlte Rechnungen anzeigen
     * @param int|null $jahr Rechnungen aus welchem Jahr (null = alle)
     * @return Collection [{rechnung, score, details, is_paid}]
     */
    public function findMatches(BankBuchung $buchung, int $limit = 20, bool $includePaid = true, ?int $jahr = null): Collection
    {
        // Nur für Eingänge
        if ($buchung->typ !== 'CRDT') {
            return collect();
        }

        // Daten aus Buchung extrahieren
        $extracted = $this->extractMatchingData($buchung);
        $betrag = (float) $buchung->betrag;

        // Rechnungen laden (mit oder ohne bezahlte)
        $query = Rechnung::with(['rechnungsempfaenger', 'gebaeude']);
        
        if (!$includePaid) {
            $query->whereIn('status', ['sent', 'draft']);
        }
        
        // ⭐ Jahr-Filter (wenn angegeben)
        if ($jahr !== null) {
            $query->where('jahr', $jahr);
        }
        
        $rechnungen = $query->get();

        // Scores berechnen
        $results = $rechnungen->map(function ($rechnung) use ($extracted, $betrag) {
            $scoreResult = $this->calculateScore($rechnung, $extracted, $betrag);
            return [
                'rechnung' => $rechnung,
                'score'    => $scoreResult['score'],
                'details'  => $scoreResult['details'],
                'is_paid'  => $rechnung->status === 'paid',
            ];
        })
        ->filter(fn($r) => $r['score'] > 0)  // Nur mit Score > 0
        ->sortByDesc('score')
        ->take($limit)
        ->values();

        return $results;
    }

    /**
     * Versucht Auto-Match für eine Buchung
     * (Nur für unbezahlte Rechnungen!)
     * 
     * @param BankBuchung $buchung
     * @param int|null $jahr Startjahr für Suche (null = aktuelles Jahr), sucht immer 2 Jahre zurück
     * @return array{matched: bool, rechnung: ?Rechnung, score: int, details: array}
     */
    public function tryAutoMatch(BankBuchung $buchung, ?int $jahr = null): array
    {
        // Startjahr bestimmen (angegeben oder aktuell)
        $startYear = $jahr ?? now()->year;
        
        // Immer vom Startjahr 2 Jahre zurück durchsuchen
        $matches = collect();
        
        for ($y = $startYear; $y >= $startYear - 2; $y--) {
            $yearMatches = $this->findMatches($buchung, 10, false, $y);
            $matches = $matches->concat($yearMatches);
        }
        
        // Nach Score sortieren und auf 10 begrenzen
        $matches = $matches->sortByDesc('score')->take(10)->values();
        
        // Nur unbezahlte für Auto-Match
        $matches = $matches->filter(fn($m) => !$m['is_paid']);

        if ($matches->isEmpty()) {
            return [
                'matched'  => false,
                'rechnung' => null,
                'score'    => 0,
                'details'  => [],
            ];
        }

        $best = $matches->first();

        // Nur wenn Score >= Threshold
        if ($best['score'] >= $this->getConfig()->auto_match_threshold) {
            return [
                'matched'  => true,
                'rechnung' => $best['rechnung'],
                'score'    => $best['score'],
                'details'  => $best['details'],
            ];
        }

        return [
            'matched'  => false,
            'rechnung' => null,
            'score'    => $best['score'],
            'details'  => $best['details'],
        ];
    }

    /**
     * Auto-Match für alle unzugeordneten Buchungen durchführen
     * 
     * @return array{matched: int, skipped: int, results: array}
     */
    public function autoMatchAll(): array
    {
        $buchungen = BankBuchung::where('match_status', 'unmatched')
            ->where('typ', 'CRDT')
            ->get();

        $matched = 0;
        $skipped = 0;
        $results = [];

        foreach ($buchungen as $buchung) {
            $result = $this->tryAutoMatch($buchung);

            if ($result['matched'] && $result['rechnung']) {
                // Match durchführen
                $this->executeMatch($buchung, $result['rechnung'], $result['score'], $result['details']);
                $matched++;

                $results[] = [
                    'buchung_id'      => $buchung->id,
                    'rechnung_id'     => $result['rechnung']->id,
                    'rechnungsnummer' => $result['rechnung']->rechnungsnummer,
                    'betrag'          => $buchung->betrag,
                    'score'           => $result['score'],
                    'details'         => $result['details'],
                ];
            } else {
                $skipped++;
            }
        }

        return [
            'matched' => $matched,
            'skipped' => $skipped,
            'results' => $results,
        ];
    }

    // ═══════════════════════════════════════════════════════════════════════
    // EXTRAKTION
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Extrahiert alle relevanten Matching-Daten aus einer Buchung
     */
    public function extractMatchingData(BankBuchung $buchung): array
    {
        $verwendungszweck = $buchung->verwendungszweck ?? '';
        $gegenkontoName = $buchung->gegenkonto_name ?? '';
        $fullText = $verwendungszweck . ' ' . $gegenkontoName;

        return [
            'iban'     => $buchung->gegenkonto_iban,
            'cig'      => $this->extractCig($verwendungszweck),
            'nummern'  => $this->extractNummern($verwendungszweck),
            'tokens'   => $this->extractNameTokens($fullText),
            'raw_text' => $fullText,
        ];
    }

    /**
     * Extrahiert CIG-Nummer
     */
    protected function extractCig(string $text): ?string
    {
        if (preg_match('/CIG[:\s]*([A-Z0-9]{10})/i', $text, $m)) {
            return strtoupper($m[1]);
        }
        return null;
    }

    /**
     * Extrahiert alle möglichen Rechnungsnummern (1-4 Ziffern)
     * 
     * Filtert typische Nicht-RN-Nummern (Transaktions-IDs, Jahre, etc.)
     */
    protected function extractNummern(string $text): array
    {
        $nummern = [];

        // Pattern: Alleinstehende 1-4 stellige Zahlen
        // Nicht am Anfang langer Zahlenfolgen
        if (preg_match_all('/(?<!\d)(\d{1,4})(?!\d)/', $text, $matches)) {
            foreach ($matches[1] as $num) {
                $int = (int) $num;
                
                // Filter:
                // - Nicht 0
                // - Nicht typische Jahre (2020-2030)
                // - Nicht typische Tage/Monate (01-12, 01-31)
                if ($int > 0 && $int < 9999) {
                    // Jahre ausschließen
                    if ($int >= 2020 && $int <= 2030) {
                        continue;
                    }
                    // Sehr kleine Zahlen nur wenn > 10
                    if ($int > 10 || $int === (int) ltrim($num, '0')) {
                        $nummern[] = $int;
                    }
                }
            }
        }

        // Spezielle Patterns priorisieren
        // "fatt.548" / "fattura n 548" → Diese Nummer nach vorne
        if (preg_match('/(?:fatt\.?|fattura\s+n\.?)\s*(\d{1,4})/i', $text, $m)) {
            $prio = (int) $m[1];
            // Nach vorne sortieren
            $nummern = array_unique(array_merge([$prio], $nummern));
        }

        // "NUMERO DOCUM. 0006322" → Führende Nullen entfernen
        if (preg_match('/NUMERO\s+DOCUM\.?\s*(\d+)/i', $text, $m)) {
            $prio = (int) ltrim($m[1], '0');
            if ($prio > 0 && $prio < 9999) {
                $nummern = array_unique(array_merge([$prio], $nummern));
            }
        }

        return array_values(array_unique($nummern));
    }

    /**
     * Extrahiert Name-Tokens für Vergleich
     * 
     * "GABRIELI LUCIANO S.A.S. DI GABRIELI R."
     * → ["gabrieli", "luciano", "s.a.s"]
     */
    protected function extractNameTokens(string $text): array
    {
        // Zuerst: Text vor bekannten Trennern extrahieren
        // (Namen stehen meist am Anfang, vor TRANSID, CRO, etc.)
        $text = preg_replace('/\s+(TRANSID|CRO|RIF\.?PAG|BENEF|BONIF)\s+.*/i', '', $text);
        
        // Normalisieren
        $text = mb_strtolower($text);
        
        // Sonderzeichen zu Leerzeichen (außer Punkt für S.A.S.)
        $text = preg_replace('/[^a-z0-9.\s]/u', ' ', $text);
        
        // In Tokens aufteilen
        $tokens = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        
        // Filtern
        $tokens = array_filter($tokens, function ($token) {
            // Mindestens 2 Zeichen (außer bekannte Abkürzungen)
            if (strlen($token) < 2) {
                return false;
            }
            // Keine Füllwörter
            $clean = str_replace('.', '', $token);
            if (in_array($clean, self::STOP_WORDS)) {
                return false;
            }
            // Keine reinen Zahlen
            if (is_numeric($clean)) {
                return false;
            }
            return true;
        });

        // Duplikate entfernen, aber Reihenfolge beibehalten
        return array_values(array_unique($tokens));
    }

    // ═══════════════════════════════════════════════════════════════════════
    // SCORING
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Berechnet Score für eine Rechnung
     */
    protected function calculateScore(Rechnung $rechnung, array $extracted, float $betrag): array
    {
        $score = 0;
        $details = [];
        $config = $this->getConfig();

        // 1. IBAN-Match (über Gebäude)
        if ($extracted['iban'] && $rechnung->gebaeude_id) {
            $gebaeude = $rechnung->gebaeude;
            if ($gebaeude && $this->matchIban($extracted['iban'], $gebaeude)) {
                $score += $config->score_iban_match;
                $details[] = [
                    'typ'    => 'iban',
                    'punkte' => $config->score_iban_match,
                    'text'   => 'IBAN im Gebäude gefunden',
                ];
            }
        }

        // 2. CIG-Match
        if ($extracted['cig'] && $rechnung->cig) {
            if (strtoupper($rechnung->cig) === $extracted['cig']) {
                $score += $config->score_cig_match;
                $details[] = [
                    'typ'    => 'cig',
                    'punkte' => $config->score_cig_match,
                    'text'   => "CIG {$extracted['cig']} stimmt",
                ];
            }
        }

        // 3. Rechnungsnummer-Match
        if (!empty($extracted['nummern']) && $rechnung->laufnummer) {
            if (in_array((int) $rechnung->laufnummer, $extracted['nummern'])) {
                $score += $config->score_rechnungsnr_match;
                $details[] = [
                    'typ'    => 'rechnungsnr',
                    'punkte' => $config->score_rechnungsnr_match,
                    'text'   => "Laufnummer {$rechnung->laufnummer} gefunden",
                ];
            }
        }

        // 4. Betrags-Match (mit korrektem Vergleichsbetrag!)
        $rechnungBetrag = $this->getVergleichsbetrag($rechnung);
        $differenz = abs($betrag - $rechnungBetrag);
        
        // Prozentuale Abweichung berechnen
        $prozentAbweichung = $rechnungBetrag > 0 
            ? ($differenz / $rechnungBetrag) * 100 
            : 100;

        if ($differenz <= (float) $config->betrag_toleranz_exakt) {
            // Exakter Match
            $score += $config->score_betrag_exakt;
            $details[] = [
                'typ'    => 'betrag_exakt',
                'punkte' => $config->score_betrag_exakt,
                'text'   => sprintf('Betrag exakt (%.2f€)', $rechnungBetrag),
            ];
        } elseif ($differenz <= (float) $config->betrag_toleranz_nah) {
            // Nah dran (z.B. Rundungsdifferenzen)
            $score += $config->score_betrag_nah;
            $details[] = [
                'typ'    => 'betrag_nah',
                'punkte' => $config->score_betrag_nah,
                'text'   => sprintf('Betrag nah (%.2f€, Diff: %.2f€)', $rechnungBetrag, $differenz),
            ];
        } elseif ($prozentAbweichung > $config->betrag_abweichung_limit) {
            // Große Abweichung = sehr unwahrscheinlich!
            $score += $config->score_betrag_abweichung;
            $details[] = [
                'typ'    => 'betrag_abweichung',
                'punkte' => $config->score_betrag_abweichung,
                'text'   => sprintf('Betrag weicht %.0f%% ab (%.2f€ vs %.2f€)', $prozentAbweichung, $betrag, $rechnungBetrag),
            ];
        }

        // 5. Name-Token-Matches
        if (!empty($extracted['tokens'])) {
            $nameMatches = $this->matchNameTokens($extracted['tokens'], $rechnung);
            
            foreach ($nameMatches as $match) {
                $score += $match['punkte'];
                $details[] = $match;
            }
        }

        // Score kann nicht negativ werden
        $score = max(0, $score);

        return [
            'score'   => $score,
            'details' => $details,
        ];
    }

    /**
     * Ermittelt den korrekten Vergleichsbetrag für eine Rechnung
     * 
     * Nutzt den Accessor im Rechnung Model, der berücksichtigt:
     * - Reverse Charge: Netto (keine MwSt)
     * - Split-Payment: Netto (MwSt geht an Staat)
     * - Ritenuta: Abzug vom entsprechenden Betrag
     * - Normal: Brutto
     * 
     * @param Rechnung $rechnung
     * @return float Der erwartete Zahlungsbetrag
     */
    protected function getVergleichsbetrag(Rechnung $rechnung): float
    {
        // Nutze den Accessor im Model (falls vorhanden)
        if (method_exists($rechnung, 'getErwarteterZahlbetragAttribute')) {
            return $rechnung->erwarteter_zahlbetrag;
        }

        // Fallback: Eigene Berechnung
        return $this->berechneVergleichsbetrag($rechnung);
    }

    /**
     * Fallback-Berechnung falls Accessor nicht verfügbar
     */
    protected function berechneVergleichsbetrag(Rechnung $rechnung): float
    {
        // 1. Primär: zahlbar_betrag (sollte bereits korrekt berechnet sein)
        if ($rechnung->zahlbar_betrag !== null && (float) $rechnung->zahlbar_betrag > 0) {
            return (float) $rechnung->zahlbar_betrag;
        }

        // 2. Fallback: Manuell berechnen basierend auf Profil/Flags
        $brutto = (float) ($rechnung->brutto_summe ?? 0);
        $netto = (float) ($rechnung->netto_summe ?? $brutto);
        $ritenuta = (float) ($rechnung->ritenuta_betrag ?? 0);

        // Prüfe FatturaProfile oder direkte Flags
        $profile = $rechnung->fatturaProfile;
        $isSplitPayment = $profile?->split_payment ?? ($rechnung->split_payment ?? false);
        $isReverseCharge = $profile?->reverse_charge ?? ($rechnung->reverse_charge ?? false);
        $hasRitenuta = $ritenuta > 0 || ($profile?->ritenuta ?? 0) > 0;
        
        // Ritenuta aus Profil berechnen falls nicht direkt gesetzt
        if ($ritenuta == 0 && $profile?->ritenuta > 0) {
            $ritenuta = round($netto * ((float) $profile->ritenuta / 100), 2);
        }

        // Reverse Charge: Nur Netto (keine MwSt)
        if ($isReverseCharge) {
            return round($netto - $ritenuta, 2);
        }

        // Split-Payment: Netto (MwSt geht direkt an Finanzamt)
        if ($isSplitPayment) {
            return round($netto - $ritenuta, 2);
        }

        // Ritenuta ohne Split-Payment: Brutto minus Ritenuta
        if ($hasRitenuta) {
            return round($brutto - $ritenuta, 2);
        }

        // Normal: Brutto
        return $brutto;
    }

    /**
     * Prüft IBAN-Match im Gebäude
     */
    protected function matchIban(string $iban, Gebaeude $gebaeude): bool
    {
        $template = $gebaeude->bank_match_text_template ?? '';
        return stripos($template, $iban) !== false;
    }

    /**
     * Matcht Name-Tokens gegen Rechnung
     */
    protected function matchNameTokens(array $tokens, Rechnung $rechnung): array
    {
        $matches = [];
        
        // Alle zu prüfenden Namen sammeln
        $targetNames = [];
        
        if ($rechnung->re_name) {
            $targetNames[] = mb_strtolower($rechnung->re_name);
        }
        if ($rechnung->rechnungsempfaenger?->name) {
            $targetNames[] = mb_strtolower($rechnung->rechnungsempfaenger->name);
        }
        if ($rechnung->geb_name) {
            $targetNames[] = mb_strtolower($rechnung->geb_name);
        }
        if ($rechnung->gebaeude?->gebaeude_name) {
            $targetNames[] = mb_strtolower($rechnung->gebaeude->gebaeude_name);
        }

        $targetText = implode(' ', $targetNames);
        $matchedTokens = [];
        $config = $this->getConfig();

        foreach ($tokens as $token) {
            // Schon gematcht? (Duplikate vermeiden)
            if (in_array($token, $matchedTokens)) {
                continue;
            }

            // Exakter Match
            if (strpos($targetText, $token) !== false) {
                $matches[] = [
                    'typ'    => 'name_exact',
                    'punkte' => $config->score_name_token_exact,
                    'text'   => "Name-Token \"{$token}\" gefunden",
                ];
                $matchedTokens[] = $token;
                continue;
            }

            // Partieller Match (Token ist Teil eines Wortes oder umgekehrt)
            // Nur für Tokens mit mind. 4 Zeichen
            if (strlen($token) >= 4) {
                foreach ($targetNames as $name) {
                    // Token in Name enthalten?
                    if (strpos($name, $token) !== false) {
                        $matches[] = [
                            'typ'    => 'name_partial',
                            'punkte' => $config->score_name_token_partial,
                            'text'   => "Name-Token \"{$token}\" teilweise",
                        ];
                        $matchedTokens[] = $token;
                        break;
                    }
                    // Name-Teil in Token enthalten? (z.B. "gabriel" in "gabrieli")
                    $nameTokens = preg_split('/\s+/', $name);
                    foreach ($nameTokens as $nameToken) {
                        if (strlen($nameToken) >= 4 && strpos($token, $nameToken) !== false) {
                            $matches[] = [
                                'typ'    => 'name_partial',
                                'punkte' => $config->score_name_token_partial,
                                'text'   => "Name \"{$nameToken}\" in Token",
                            ];
                            $matchedTokens[] = $token;
                            break 2;
                        }
                    }
                }
            }
        }

        // Max. 5 Token-Matches zählen (sonst zu viel Gewicht)
        return array_slice($matches, 0, 5);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // MATCH AUSFÜHREN
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Führt Match durch und speichert alle Infos
     */
    public function executeMatch(
        BankBuchung $buchung, 
        Rechnung $rechnung, 
        int $score, 
        array $details,
        bool $saveIban = true
    ): void {
        // Match-Info speichern
        $matchInfo = [
            'score'      => $score,
            'details'    => $details,
            'threshold'  => $this->getConfig()->auto_match_threshold,
            'auto'       => true,
            'matched_at' => now()->toIso8601String(),
        ];

        $buchung->rechnung_id = $rechnung->id;
        $buchung->match_status = 'matched';
        $buchung->match_info = json_encode($matchInfo);
        $buchung->matched_at = now();
        $buchung->save();

        // IBAN im Gebäude speichern (für zukünftiges Matching)
        if ($saveIban && $buchung->gegenkonto_iban && $rechnung->gebaeude_id) {
            $gebaeude = $rechnung->gebaeude;
            if ($gebaeude) {
                $currentTemplate = $gebaeude->bank_match_text_template ?? '';
                
                if (strpos($currentTemplate, $buchung->gegenkonto_iban) === false) {
                    $gebaeude->bank_match_text_template = trim($currentTemplate . "\n" . $buchung->gegenkonto_iban);
                    $gebaeude->save();
                }
            }
        }

        // Rechnung als bezahlt markieren
        if ($rechnung->status !== 'paid') {
            $rechnung->status = 'paid';
            $rechnung->bezahlt_am = $buchung->buchungsdatum;
            $rechnung->save();
        }

        Log::info('Bank-Matching durchgeführt', [
            'buchung_id'  => $buchung->id,
            'rechnung_id' => $rechnung->id,
            'score'       => $score,
            'auto'        => true,
        ]);
    }

    /**
     * Manueller Match mit Score-Berechnung
     * 
     * @param BankBuchung $buchung
     * @param Rechnung $rechnung
     * @param bool $markAsPaid Rechnung als bezahlt markieren (ignoriert wenn bereits bezahlt)
     * @param bool $saveIban IBAN im Gebäude speichern
     * @return array{score: int, details: array, was_already_paid: bool}
     */
    public function manualMatch(
        BankBuchung $buchung,
        Rechnung $rechnung,
        bool $markAsPaid = true,
        bool $saveIban = true
    ): array {
        $wasAlreadyPaid = $rechnung->status === 'paid';
        
        $extracted = $this->extractMatchingData($buchung);
        $scoreResult = $this->calculateScore($rechnung, $extracted, (float) $buchung->betrag);

        $matchInfo = [
            'score'            => $scoreResult['score'],
            'details'          => $scoreResult['details'],
            'threshold'        => $this->getConfig()->auto_match_threshold,
            'auto'             => false,
            'was_already_paid' => $wasAlreadyPaid,
            'matched_at'       => now()->toIso8601String(),
        ];

        $buchung->rechnung_id = $rechnung->id;
        $buchung->match_status = 'manual';
        $buchung->match_info = json_encode($matchInfo);
        $buchung->matched_at = now();
        $buchung->save();

        // IBAN speichern
        if ($saveIban && $buchung->gegenkonto_iban && $rechnung->gebaeude_id) {
            $gebaeude = $rechnung->gebaeude;
            if ($gebaeude) {
                $currentTemplate = $gebaeude->bank_match_text_template ?? '';
                
                if (strpos($currentTemplate, $buchung->gegenkonto_iban) === false) {
                    $gebaeude->bank_match_text_template = trim($currentTemplate . "\n" . $buchung->gegenkonto_iban);
                    $gebaeude->save();
                }
            }
        }

        // Rechnung als bezahlt markieren (nur wenn noch nicht bezahlt)
        if ($markAsPaid && !$wasAlreadyPaid) {
            $rechnung->status = 'paid';
            $rechnung->bezahlt_am = $buchung->buchungsdatum;
            $rechnung->save();
        }

        return [
            'score'            => $scoreResult['score'],
            'details'          => $scoreResult['details'],
            'was_already_paid' => $wasAlreadyPaid,
        ];
    }
}
