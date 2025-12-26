<?php

namespace App\Services\Import;

use App\Models\Adresse;
use App\Models\Gebaeude;
use App\Models\Rechnung;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use Exception;

/**
 * Rechnungs-Import Service (Vereinfacht)
 * 
 * Importiert Rechnungen aus XML-Export der FatturaPA-Tabelle.
 * 
 * FELDER IM XML:
 * - id, ProgressivoInvio, Data, Numero, DataPagamento
 * - herkunft (→ Gebäude), Bezahlt, TipoDocumento, TipoIva
 * - RechnungsBetrag, MwStr, Rit, Ritenuta
 * - Causale, CIG, CUP, OrdineId, OrdineData
 * 
 * SNAPSHOTS werden aus bereits importierten Gebäuden/Adressen geladen!
 * 
 * VORAUSSETZUNG: Adressen und Gebäude müssen VORHER importiert sein!
 */
class RechnungImportService
{
    protected array $stats = [
        'imported' => 0,
        'skipped' => 0,
        'errors' => 0,
    ];

    protected array $errors = [];
    protected bool $dryRun = false;
    protected bool $skipExisting = true;

    // Lookup-Tabellen
    protected array $gebaeudeMap = [];       // legacy_mid -> neue ID
    protected array $gebaeudeMapById = [];   // legacy_id -> neue ID

    // ═══════════════════════════════════════════════════════════════════════
    // HARDCODED MAPPINGS (aus Access-Tabellen)
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * typFatturaPA: TipoDocumento ID → Codex
     */
    protected const TIPO_DOCUMENTO_MAP = [
        1 => 'TD04',  // Gutschrift/Nota d'acredito
        2 => 'TD01',  // Rechnung/Fattura
    ];

    /**
     * typMwSt: TipoIva ID → MwSt-Einstellungen
     * 
     * Aus deiner Tabelle:
     * 7  = 10% normal
     * 8  = 22% normal
     * 9  = 22% Split Payment
     * 10 = 0% (MwSt-frei)
     * 11 = 0% Reverse Charge (N6.7)
     * 12 = 10% Split Payment
     */
    protected const TIPO_IVA_MAP = [
        7 => [
            'mwst_satz'       => 10.00,
            'split_payment'   => false,
            'reverse_charge'  => false,
            'natura'          => null,
        ],
        8 => [
            'mwst_satz'       => 22.00,
            'split_payment'   => false,
            'reverse_charge'  => false,
            'natura'          => null,
        ],
        9 => [
            'mwst_satz'       => 22.00,
            'split_payment'   => true,
            'reverse_charge'  => false,
            'natura'          => 'S',
        ],
        10 => [
            'mwst_satz'       => 0.00,
            'split_payment'   => false,
            'reverse_charge'  => false,
            'natura'          => null,
        ],
        11 => [
            'mwst_satz'       => 0.00,
            'split_payment'   => false,
            'reverse_charge'  => true,
            'natura'          => 'N6.7',
        ],
        12 => [
            'mwst_satz'       => 10.00,
            'split_payment'   => true,
            'reverse_charge'  => false,
            'natura'          => 'S',
        ],
    ];

    /**
     * Konfiguration
     */
    public function configure(bool $dryRun = false, bool $skipExisting = true): self
    {
        $this->dryRun = $dryRun;
        $this->skipExisting = $skipExisting;
        return $this;
    }

    public function getStats(): array
    {
        return $this->stats;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Lookup-Tabellen aufbauen
     */
    public function buildLookups(): void
    {
        // Gebäude nach legacy_mid
        $this->gebaeudeMap = Gebaeude::whereNotNull('legacy_mid')
            ->pluck('id', 'legacy_mid')
            ->toArray();

        // Gebäude nach legacy_id  
        $this->gebaeudeMapById = Gebaeude::whereNotNull('legacy_id')
            ->pluck('id', 'legacy_id')
            ->toArray();

        Log::info('RechnungImport: Lookups aufgebaut', [
            'gebaeude_mid' => count($this->gebaeudeMap),
            'gebaeude_id' => count($this->gebaeudeMapById),
        ]);
    }

    /**
     * ⭐ HAUPTMETHODE: Rechnungen importieren
     */
    public function importRechnungen(string $xmlPath): int
    {
        $xml = $this->loadXml($xmlPath);
        $count = 0;

        // Lookups aufbauen falls noch nicht geschehen
        if (empty($this->gebaeudeMap)) {
            $this->buildLookups();
        }

        // XML-Root-Element ermitteln
        // Format: <dataroot><FatturaPA>...</FatturaPA><FatturaPA>...</FatturaPA></dataroot>
        $items = $xml->FatturaPA ?? $xml->FatturaPAXmlAbfrage ?? $xml->children();

        foreach ($items as $item) {
            try {
                $count += $this->importRechnungItem($item);
            } catch (Exception $e) {
                $this->logError((string)($item->id ?? 'unknown'), $e->getMessage());
            }
        }

        return $count;
    }

    /**
     * Einzelne Rechnung importieren
     */
    protected function importRechnungItem(\SimpleXMLElement $item): int
    {
        // ID kann 'id' oder 'idFatturaPA' heißen
        $legacyId = (int) ($item->id ?? $item->idFatturaPA ?? 0);
        
        if ($legacyId <= 0) {
            throw new Exception("Keine gültige ID");
        }

        // Duplikat-Prüfung
        $existing = Rechnung::where('legacy_id', $legacyId)->first();
        if ($existing && $this->skipExisting) {
            $this->stats['skipped']++;
            return 0;
        }

        // ═══════════════════════════════════════════════════════════════
        // GEBÄUDE AUFLÖSEN (für Snapshots und typ_kunde)
        // ═══════════════════════════════════════════════════════════════
        
        $herkunft = (int) $item->herkunft;
        $gebaeudeId = $this->gebaeudeMapById[$herkunft] ?? $this->gebaeudeMap[$herkunft] ?? null;

        $gebaeude = null;
        $rechnungsempfaenger = null;
        $postadresse = null;
        $typKunde = null;

        if ($gebaeudeId) {
            $gebaeude = Gebaeude::with(['rechnungsempfaenger', 'postadresse'])
                ->find($gebaeudeId);
            
            if ($gebaeude) {
                $rechnungsempfaenger = $gebaeude->rechnungsempfaenger;
                $postadresse = $gebaeude->postadresse;
                
                // ⭐ typ_kunde aus Gebäude holen
                $typKunde = $gebaeude->typ_kunde ?? $gebaeude->TypKunde ?? null;
            }
        }

        // ═══════════════════════════════════════════════════════════════
        // BASISDATEN PARSEN
        // ═══════════════════════════════════════════════════════════════

        $rechnungsdatum = $this->parseDate((string) $item->Data);
        $zahlungsziel = $this->parseDate((string) ($item->DataPagamento ?? $item->DataScadenzaPagamento ?? ''));
        
        $jahr = $rechnungsdatum ? $rechnungsdatum->year : now()->year;
        $laufnummer = (int) $item->Numero;

        // Status (Bezahlt = 0/1)
        $bezahlt = (int) $item->Bezahlt === 1;
        $status = $bezahlt ? 'paid' : 'sent';

        // ═══════════════════════════════════════════════════════════════
        // TIPO DOCUMENTO → TD01/TD04
        // ═══════════════════════════════════════════════════════════════

        $tipoDocId = (int) $item->TipoDocumento;
        $tipoDocCodex = self::TIPO_DOCUMENTO_MAP[$tipoDocId] ?? 'TD01';
        $typ = ($tipoDocCodex === 'TD04') ? 'gutschrift' : 'rechnung';

        // ═══════════════════════════════════════════════════════════════
        // TIPO IVA → MwSt-Einstellungen
        // ═══════════════════════════════════════════════════════════════

        $tipoIva = (int) $item->TipoIva;
        $ivaSettings = self::TIPO_IVA_MAP[$tipoIva] ?? [
            'mwst_satz'      => 22.00,
            'split_payment'  => false,
            'reverse_charge' => false,
            'natura'         => null,
        ];

        // ═══════════════════════════════════════════════════════════════
        // BETRÄGE PARSEN (Zahlen ohne €)
        // ═══════════════════════════════════════════════════════════════

        $nettoSumme = (float) $item->RechnungsBetrag;
        $mwstBetrag = (float) $item->MwStr;
        $ritenutaBetrag = (float) $item->Rit;
        
        // Brutto berechnen
        $bruttoSumme = $nettoSumme + $mwstBetrag;

        // Bei Reverse Charge: MwSt = 0, Brutto = Netto
        if ($ivaSettings['reverse_charge']) {
            $mwstBetrag = 0;
            $bruttoSumme = $nettoSumme;
        }

        // Ritenuta Flag (0/1)
        $hatRitenuta = (int) $item->Ritenuta === 1 || $ritenutaBetrag > 0;

        // ═══════════════════════════════════════════════════════════════
        // FATTURA-PROFIL ERMITTELN (aus typ_kunde)
        // ═══════════════════════════════════════════════════════════════

        $profilMapping = $this->mapFatturaProfil($typKunde, $ivaSettings, $hatRitenuta);

        // Zahlbar-Betrag berechnen
        $zahlbarBetrag = $this->berechneZahlbarBetrag(
            $nettoSumme, $mwstBetrag, $bruttoSumme, $ritenutaBetrag, 
            $ivaSettings['split_payment'], $ivaSettings['reverse_charge']
        );

        // ═══════════════════════════════════════════════════════════════
        // DATEN ZUSAMMENSTELLEN
        // ═══════════════════════════════════════════════════════════════

        $data = [
            'legacy_id'              => $legacyId,
            'legacy_progressivo'     => (int) $item->ProgressivoInvio,
            'jahr'                   => $jahr,
            'laufnummer'             => $laufnummer,
            'gebaeude_id'            => $gebaeudeId,
            'rechnungsempfaenger_id' => $rechnungsempfaenger?->id,
            'rechnungsdatum'         => $rechnungsdatum,
            'zahlungsziel'           => $zahlungsziel,
            'status'                 => $status,
            'typ_rechnung'           => $typ,
            
            // Beträge
            'netto_summe'            => $nettoSumme,
            'mwst_betrag'            => $mwstBetrag,
            'brutto_summe'           => $bruttoSumme,
            'ritenuta_betrag'        => $ritenutaBetrag,
            'zahlbar_betrag'         => $zahlbarBetrag,

            // MwSt-Einstellungen (aus TipoIva)
            'mwst_satz'              => $ivaSettings['mwst_satz'],
            'split_payment'          => $ivaSettings['split_payment'],
            'reverse_charge'         => $ivaSettings['reverse_charge'],
            
            // Ritenuta
            'ritenuta'               => $hatRitenuta,
            'ritenuta_prozent'       => $hatRitenuta ? 4.00 : 0.00,

            // Fattura-Profil (aus typ_kunde)
            'fattura_profile_id'     => $profilMapping['fattura_profile_id'],

            // FatturaPA-Felder
            'fattura_causale'        => $this->cleanString((string) $item->Causale),
            'cig'                    => $this->cleanString((string) $item->CIG) ?: null,
            'cup'                    => $this->cleanString((string) $item->CUP) ?: null,
            'auftrag_id'             => $this->cleanString((string) $item->OrdineId) ?: null,
            'auftrag_datum'          => $this->parseDate((string) $item->OrdineData),
            
            // ⭐ Snapshot Gebäude (aus bereits importierten Daten!)
            'geb_codex'              => $gebaeude?->codex,
            'geb_name'               => $gebaeude?->gebaeude_name,
            
            // ⭐ Snapshot Rechnungsempfänger (aus bereits importierten Adressen!)
            're_name'                => $rechnungsempfaenger?->name,
            're_strasse'             => $rechnungsempfaenger?->strasse,
            're_hausnummer'          => $rechnungsempfaenger?->hausnummer,
            're_plz'                 => $rechnungsempfaenger?->plz,
            're_wohnort'             => $rechnungsempfaenger?->wohnort,
            're_provinz'             => $rechnungsempfaenger?->provinz,
            're_land'                => $rechnungsempfaenger?->land ?? 'IT',
            're_mwst_nummer'         => $rechnungsempfaenger?->mwst_nummer,
            're_steuernummer'        => $rechnungsempfaenger?->steuernummer,
            're_codice_univoco'      => $rechnungsempfaenger?->codice_univoco,
            're_pec'                 => $rechnungsempfaenger?->pec,

            // ⭐ Snapshot Postadresse (aus bereits importierten Adressen!)
            'post_name'              => $postadresse?->name,
            'post_strasse'           => $postadresse?->strasse,
            'post_hausnummer'        => $postadresse?->hausnummer,
            'post_plz'               => $postadresse?->plz,
            'post_wohnort'           => $postadresse?->wohnort,
            'post_provinz'           => $postadresse?->provinz,
            'post_land'              => $postadresse?->land ?? 'IT',
            'post_email'             => $postadresse?->email,
            'post_pec'               => $postadresse?->pec,
        ];

        // Speichern
        if ($this->dryRun) {
            Log::info('[DRY-RUN] Würde Rechnung importieren', [
                'legacy_id' => $legacyId,
                'nummer' => "$jahr/$laufnummer",
                'gebaeude' => $gebaeude?->codex,
                're' => $rechnungsempfaenger?->name,
                'tipoIva' => $tipoIva,
                'split' => $ivaSettings['split_payment'],
                'rc' => $ivaSettings['reverse_charge'],
            ]);
            $this->stats['imported']++;
            return 1;
        }

        if ($existing) {
            $existing->update($data);
        } else {
            Rechnung::create($data);
        }
        
        $this->stats['imported']++;
        return 1;
    }

    // ═══════════════════════════════════════════════════════════════════════
    // MAPPING METHODS
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Fattura-Profil aus typ_kunde ermitteln
     * 
     * Falls typ_kunde nicht vorhanden, aus TipoIva ableiten
     */
    protected function mapFatturaProfil(?int $typKunde, array $ivaSettings, bool $hatRitenuta): array
    {
        // Wenn typ_kunde vorhanden, direkt mappen
        if ($typKunde !== null) {
            $mapping = [
                1 => ['fattura_profile_id' => 4],  // Kondominium 10% + Ritenuta
                2 => ['fattura_profile_id' => 3],  // Öffentlich 22% Split
                3 => ['fattura_profile_id' => 2],  // Privat 10%
                4 => ['fattura_profile_id' => 1],  // Reverse Charge
                5 => ['fattura_profile_id' => 1],  // Reverse Charge Alt
                7 => ['fattura_profile_id' => 3],  // Split Payment 22%
                8 => ['fattura_profile_id' => 6],  // Firma 22%
                9 => ['fattura_profile_id' => 5],  // Firma 22% + Ritenuta
            ];

            return $mapping[$typKunde] ?? ['fattura_profile_id' => null];
        }

        // Fallback: Aus TipoIva-Settings ableiten
        if ($ivaSettings['reverse_charge']) {
            return ['fattura_profile_id' => 1]; // Reverse Charge
        }
        if ($ivaSettings['split_payment']) {
            return ['fattura_profile_id' => 3]; // Split Payment
        }
        if ($hatRitenuta && $ivaSettings['mwst_satz'] == 10) {
            return ['fattura_profile_id' => 4]; // Kondominium
        }
        if ($ivaSettings['mwst_satz'] == 10) {
            return ['fattura_profile_id' => 2]; // Privat 10%
        }
        if ($ivaSettings['mwst_satz'] == 22 && $hatRitenuta) {
            return ['fattura_profile_id' => 5]; // Firma + Ritenuta
        }
        if ($ivaSettings['mwst_satz'] == 22) {
            return ['fattura_profile_id' => 6]; // Firma 22%
        }

        return ['fattura_profile_id' => null];
    }

    /**
     * Zahlbar-Betrag berechnen
     */
    protected function berechneZahlbarBetrag(
        float $netto, 
        float $mwst, 
        float $brutto, 
        float $ritenuta,
        bool $splitPayment,
        bool $reverseCharge
    ): float {
        // Bei Split Payment oder Reverse Charge: Netto - Ritenuta
        if ($splitPayment || $reverseCharge) {
            return round($netto - $ritenuta, 2);
        }
        
        // Sonst: Brutto - Ritenuta
        return round($brutto - $ritenuta, 2);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // HELPER METHODS
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * XML laden
     */
    protected function loadXml(string $path): \SimpleXMLElement
    {
        if (!file_exists($path)) {
            throw new Exception("XML-Datei nicht gefunden: $path");
        }

        $content = file_get_contents($path);
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content); // BOM entfernen

        return simplexml_load_string($content);
    }

    /**
     * Datum parsen (ISO-Format: 2019-01-25T00:00:00)
     */
    protected function parseDate(?string $dateString): ?Carbon
    {
        if (!$dateString || $dateString === '') {
            return null;
        }
        
        // Dummy-Datum ignorieren
        if (str_contains($dateString, '2001-01-01') || str_contains($dateString, '01.01.2001')) {
            return null;
        }

        try {
            // ISO-Format: 2019-01-25T00:00:00
            if (str_contains($dateString, 'T')) {
                return Carbon::parse($dateString)->startOfDay();
            }
            
            // Deutsche Formate: 28.11.2025 oder 28.11.2025 08:35:20
            if (preg_match('/^(\d{2})\.(\d{2})\.(\d{4})/', $dateString, $m)) {
                return Carbon::createFromFormat('d.m.Y', "{$m[1]}.{$m[2]}.{$m[3]}");
            }
            
            return Carbon::parse($dateString);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Betrag parsen (Zahl oder deutsches Format)
     * 
     * Unterstützt:
     * - 2050 (Zahl)
     * - 819.67 (Dezimal mit Punkt)
     * - 1.234,56 € (deutsches Format)
     */
    protected function parseBetrag($betrag): float
    {
        if (is_numeric($betrag)) {
            return (float) $betrag;
        }
        
        $betrag = (string) $betrag;
        
        // Entferne €, Leerzeichen
        $betrag = str_replace(['€', ' '], '', trim($betrag));
        
        if ($betrag === '' || $betrag === '-') {
            return 0.0;
        }
        
        // Deutsches Format: 1.234,56 → 1234.56
        if (str_contains($betrag, ',')) {
            $betrag = str_replace('.', '', $betrag);  // Tausender-Punkt weg
            $betrag = str_replace(',', '.', $betrag); // Komma → Punkt
        }

        return (float) $betrag;
    }

    /**
     * String bereinigen
     */
    protected function cleanString(?string $str): ?string
    {
        if (!$str) return null;
        $str = trim($str);
        // Anführungszeichen am Anfang/Ende entfernen
        $str = trim($str, '"\'');
        return $str === '' ? null : $str;
    }

    /**
     * Fehler loggen
     */
    protected function logError(string $id, string $message): void
    {
        $this->stats['errors']++;
        $this->errors[] = ['id' => $id, 'message' => $message];
        Log::warning("RechnungImport Fehler", ['id' => $id, 'message' => $message]);
    }
}
