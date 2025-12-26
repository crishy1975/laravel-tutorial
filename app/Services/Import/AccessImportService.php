<?php

namespace App\Services\Import;

use App\Models\Adresse;
use App\Models\Gebaeude;
use App\Models\ArtikelGebaeude;
use App\Models\Rechnung;
use App\Models\RechnungPosition;
use App\Models\Timeline;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use Exception;

/**
 * Access Import Service - MASTER
 * 
 * Importiert ALLE Daten aus der alten Access-Datenbank (XML-Export) in Laravel.
 * 
 * XML-DATEIEN:
 * - Adresse.xml              → Adressen
 * - Gebaeude.xml             → Gebäude
 * - Artikel.xml              → Artikel (Stamm)
 * - FatturaPA.xml            → Rechnungen (NEU, ersetzt FatturaPAXmlAbfrage)
 * - ArtikelFatturaPAAbfrage.xml → Rechnungspositionen
 * - DatumAusfuehrung.xml     → Timeline/Reinigungen
 * 
 * IMPORT-REIHENFOLGE (wichtig wegen Referenzen!):
 * 1. Adressen
 * 2. Gebäude
 * 3. Artikel
 * 4. Rechnungen
 * 5. Positionen
 * 6. Timeline
 * 7. Fix Gebäude-Namen (optional)
 */
class AccessImportService
{
    protected array $stats = [
        'adressen'   => ['imported' => 0, 'skipped' => 0, 'errors' => 0],
        'gebaeude'   => ['imported' => 0, 'skipped' => 0, 'errors' => 0],
        'artikel'    => ['imported' => 0, 'skipped' => 0, 'errors' => 0],
        'rechnungen' => ['imported' => 0, 'skipped' => 0, 'errors' => 0],
        'positionen' => ['imported' => 0, 'skipped' => 0, 'errors' => 0],
        'timeline'   => ['imported' => 0, 'skipped' => 0, 'filtered' => 0, 'errors' => 0],
        'fix_namen'  => ['imported' => 0, 'skipped' => 0, 'errors' => 0],
    ];

    protected array $errors = [];
    protected bool $dryRun = false;
    protected bool $skipExisting = true;
    protected int $minJahr = 2024;  // Für Timeline-Filter

    // Lookup-Tabellen für Referenz-Auflösung
    protected array $adressenMap = [];       // legacy_mid → neue ID
    protected array $gebaeudeMap = [];       // legacy_mid → neue ID
    protected array $gebaeudeMapById = [];   // legacy_id → neue ID
    protected array $rechnungenMap = [];     // legacy_id → neue ID
    protected array $existingTimelines = []; // Duplikat-Check für Timeline

    // ═══════════════════════════════════════════════════════════════════════════
    // HARDCODED MAPPINGS (aus Access-Tabellen)
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * typFatturaPA: TipoDocumento ID → Codex
     */
    protected const TIPO_DOCUMENTO_MAP = [
        1 => 'TD04',  // Gutschrift/Nota d'acredito
        2 => 'TD01',  // Rechnung/Fattura
    ];

    /**
     * typMwSt: TipoIva ID → MwSt-Einstellungen
     */
    protected const TIPO_IVA_MAP = [
        7  => ['mwst_satz' => 10.00, 'split_payment' => false, 'reverse_charge' => false, 'natura' => null],
        8  => ['mwst_satz' => 22.00, 'split_payment' => false, 'reverse_charge' => false, 'natura' => null],
        9  => ['mwst_satz' => 22.00, 'split_payment' => true,  'reverse_charge' => false, 'natura' => 'S'],
        10 => ['mwst_satz' => 0.00,  'split_payment' => false, 'reverse_charge' => false, 'natura' => null],
        11 => ['mwst_satz' => 0.00,  'split_payment' => false, 'reverse_charge' => true,  'natura' => 'N6.7'],
        12 => ['mwst_satz' => 10.00, 'split_payment' => true,  'reverse_charge' => false, 'natura' => 'S'],
    ];

    // ═══════════════════════════════════════════════════════════════════════════
    // KONFIGURATION
    // ═══════════════════════════════════════════════════════════════════════════

    public function configure(bool $dryRun = false, bool $skipExisting = true, int $minJahr = 2024): self
    {
        $this->dryRun = $dryRun;
        $this->skipExisting = $skipExisting;
        $this->minJahr = $minJahr;
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

    // ═══════════════════════════════════════════════════════════════════════════
    // 1. ADRESSEN IMPORT
    // ═══════════════════════════════════════════════════════════════════════════

    public function importAdressen(string $xmlPath): int
    {
        $xml = $this->loadXml($xmlPath);
        $count = 0;

        foreach ($xml->Adresse as $item) {
            try {
                $count += $this->importAdresseItem($item);
            } catch (Exception $e) {
                $this->logError('adressen', (string)$item->id, $e->getMessage());
            }
        }

        $this->buildAdressenMap();
        return $count;
    }

    protected function importAdresseItem(\SimpleXMLElement $item): int
    {
        $legacyMid = (int) $item->mId;
        $legacyId = (int) $item->id;

        $existing = Adresse::where('legacy_mid', $legacyMid)->first();
        if ($existing && $this->skipExisting) {
            $this->stats['adressen']['skipped']++;
            return 0;
        }

        $vorname = trim((string) $item->Vorname);
        $nachname = trim((string) $item->Nachname);
        $name = trim("$vorname $nachname") ?: 'Unbekannt';

        $data = [
            'legacy_id'      => $legacyId,
            'legacy_mid'     => $legacyMid,
            'name'           => $name,
            'anrede'         => (string) $item->Anrede ?: null,
            'strasse'        => (string) $item->Strasse ?: null,
            'hausnummer'     => (string) $item->Nr ?: null,
            'plz'            => (string) $item->PLZ ?: null,
            'wohnort'        => (string) $item->Wohnort ?: null,
            'provinz'        => (string) $item->Provinz ?: null,
            'land'           => (string) $item->Land ?: 'IT',
            'telefon'        => (string) $item->Telefon ?: null,
            'handy'          => (string) $item->Handy ?: null,
            'email'          => (string) $item->Email ?: null,
            'pec'            => (string) $item->Pec ?: null,
            'steuernummer'   => (string) $item->Steuernummer ?: null,
            'mwst_nummer'    => (string) $item->Mwst ?: null,
            'codice_univoco' => (string) $item->CodiceUnivoco ?: null,
            'bemerkung'      => (string) $item->Bemerkung ?: null,
        ];

        if ($this->dryRun) {
            $this->stats['adressen']['imported']++;
            return 1;
        }

        if ($existing) {
            $existing->update($data);
        } else {
            Adresse::create($data);
        }
        $this->stats['adressen']['imported']++;
        return 1;
    }

    protected function buildAdressenMap(): void
    {
        $this->adressenMap = Adresse::whereNotNull('legacy_mid')
            ->pluck('id', 'legacy_mid')
            ->toArray();
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // 2. GEBÄUDE IMPORT
    // ═══════════════════════════════════════════════════════════════════════════

    public function importGebaeude(string $xmlPath): int
    {
        $xml = $this->loadXml($xmlPath);
        $count = 0;

        if (empty($this->adressenMap)) {
            $this->buildAdressenMap();
        }

        foreach ($xml->Gebaeude as $item) {
            try {
                $count += $this->importGebaeudeItem($item);
            } catch (Exception $e) {
                $this->logError('gebaeude', (string)$item->id, $e->getMessage());
            }
        }

        $this->buildGebaeudeMap();
        $this->buildGebaeudeMapById();
        return $count;
    }

    protected function importGebaeudeItem(\SimpleXMLElement $item): int
    {
        $legacyMid = (int) $item->mId;
        $legacyId = (int) $item->id;

        $existing = Gebaeude::where('legacy_id', $legacyId)->first();
        if ($existing && $this->skipExisting) {
            $this->stats['gebaeude']['skipped']++;
            return 0;
        }

        $postadresseId = $this->resolveAdresse((int) $item->Postadresse);
        $rechnungsempfaengerId = $this->resolveAdresse((int) $item->Rechnungsempfaenger);

        // Letzter Termin parsen
        $letzterTermin = $this->parseDate((string) $item->LetzterTermin);
        if ($letzterTermin && $letzterTermin->year <= 2000) {
            $letzterTermin = null;
        }

        // TypKunde für fattura_profile_id
        $typKunde = (int) $item->TypKunde ?: null;
        $fatturaProfileId = $this->mapTypKundeToProfileId($typKunde);

        // rechnung_schreiben: Nur wenn Rechnungsempfänger UND fatturaProfile
        $rechnungSchreiben = ($rechnungsempfaengerId && $fatturaProfileId) ? true : false;

        $data = [
            'legacy_id'             => $legacyId,
            'legacy_mid'            => $legacyMid,
            'codex'                 => strtolower(trim((string) $item->Codex)) ?: null,
            'gebaeude_name'         => (string) $item->Namen1 ?: null,
            'strasse'               => (string) $item->Strasse ?: null,
            'hausnummer'            => (string) $item->Hausnummer ?: null,
            'plz'                   => (string) $item->PLZ ?: null,
            'wohnort'               => (string) $item->Wohnort ?: null,
            'land'                  => (string) $item->Land ?: 'IT',
            'bemerkung'             => (string) $item->Bemerkung ?: null,
            'postadresse_id'        => $postadresseId,
            'rechnungsempfaenger_id'=> $rechnungsempfaengerId,
            'fattura_profile_id'    => $fatturaProfileId,
            'rechnung_schreiben'    => $rechnungSchreiben,
            'letzter_termin'        => $letzterTermin,
            'cup'                   => (string) $item->CUP ?: null,
            'cig'                   => (string) $item->CIG ?: null,
            'auftrag_id'            => (string) $item->OrdineId ?: null,
            'auftrag_datum'         => $this->parseDate((string) $item->OrdineData),
            'm01' => (int) $item->M01 ? true : false,
            'm02' => (int) $item->M02 ? true : false,
            'm03' => (int) $item->M03 ? true : false,
            'm04' => (int) $item->M04 ? true : false,
            'm05' => (int) $item->M05 ? true : false,
            'm06' => (int) $item->M06 ? true : false,
            'm07' => (int) $item->M07 ? true : false,
            'm08' => (int) $item->M08 ? true : false,
            'm09' => (int) $item->M09 ? true : false,
            'm10' => (int) $item->M10 ? true : false,
            'm11' => (int) $item->M11 ? true : false,
            'm12' => (int) $item->M12 ? true : false,
            'geplante_reinigungen'  => $this->countActiveMonths($item),
            'gemachte_reinigungen'  => 0,
            'faellig'               => false,
        ];

        if ($this->dryRun) {
            $this->stats['gebaeude']['imported']++;
            return 1;
        }

        if ($existing) {
            $existing->update($data);
        } else {
            Gebaeude::create($data);
        }
        $this->stats['gebaeude']['imported']++;
        return 1;
    }

    protected function countActiveMonths(\SimpleXMLElement $item): int
    {
        $count = 0;
        for ($i = 1; $i <= 12; $i++) {
            $key = 'M' . str_pad($i, 2, '0', STR_PAD_LEFT);
            if ((int) $item->$key) $count++;
        }
        return max(1, $count);
    }

    protected function buildGebaeudeMap(): void
    {
        $this->gebaeudeMap = Gebaeude::whereNotNull('legacy_mid')
            ->pluck('id', 'legacy_mid')
            ->toArray();
    }

    protected function buildGebaeudeMapById(): void
    {
        $this->gebaeudeMapById = Gebaeude::whereNotNull('legacy_id')
            ->pluck('id', 'legacy_id')
            ->toArray();
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // 3. ARTIKEL IMPORT
    // ═══════════════════════════════════════════════════════════════════════════

    public function importArtikel(string $xmlPath): int
    {
        $xml = $this->loadXml($xmlPath);
        $count = 0;

        if (empty($this->gebaeudeMap)) {
            $this->buildGebaeudeMap();
        }

        foreach ($xml->Artikel as $item) {
            try {
                $count += $this->importArtikelItem($item);
            } catch (Exception $e) {
                $this->logError('artikel', (string)$item->id, $e->getMessage());
            }
        }

        return $count;
    }

    protected function importArtikelItem(\SimpleXMLElement $item): int
    {
        $legacyId = (int) $item->id;
        $herkunft = (int) $item->herkunft;

        $existing = ArtikelGebaeude::where('legacy_id', $legacyId)->first();
        if ($existing && $this->skipExisting) {
            $this->stats['artikel']['skipped']++;
            return 0;
        }

        // herkunft → Gebäude.mId (legacy_mid)
        $gebaeudeId = $this->gebaeudeMap[$herkunft] ?? null;

        if (!$gebaeudeId) {
            $this->logError('artikel', (string)$legacyId, "Gebäude nicht gefunden: herkunft=$herkunft");
            return 0;
        }

        $data = [
            'legacy_id'    => $legacyId,
            'gebaeude_id'  => $gebaeudeId,
            'beschreibung' => (string) $item->Beschreibung ?: 'Ohne Beschreibung',
            'einzelpreis'  => (float) $item->Einzelpreis ?: 0,
            'anzahl'       => (float) $item->Anzahl ?: 1,
            'aktiv'        => true,
            'basis_preis'  => (float) $item->Einzelpreis ?: 0,
            'basis_jahr'   => now()->year,
        ];

        if ($this->dryRun) {
            $this->stats['artikel']['imported']++;
            return 1;
        }

        if ($existing) {
            $existing->update($data);
        } else {
            ArtikelGebaeude::create($data);
        }
        $this->stats['artikel']['imported']++;
        return 1;
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // 4. RECHNUNGEN IMPORT (NEU: FatturaPA.xml)
    // ═══════════════════════════════════════════════════════════════════════════

    public function importRechnungen(string $xmlPath): int
    {
        $xml = $this->loadXml($xmlPath);
        $count = 0;

        if (empty($this->adressenMap)) $this->buildAdressenMap();
        if (empty($this->gebaeudeMap)) $this->buildGebaeudeMap();
        if (empty($this->gebaeudeMapById)) $this->buildGebaeudeMapById();

        // Unterstütze beide XML-Formate
        $items = $xml->FatturaPA ?? $xml->FatturaPAXmlAbfrage ?? $xml->children();

        foreach ($items as $item) {
            try {
                $count += $this->importRechnungItem($item);
            } catch (Exception $e) {
                $legacyId = (string)($item->id ?? $item->idFatturaPA ?? 'unknown');
                $this->logError('rechnungen', $legacyId, $e->getMessage());
            }
        }

        $this->buildRechnungenMap();
        return $count;
    }

    protected function importRechnungItem(\SimpleXMLElement $item): int
    {
        // ID kann 'id' oder 'idFatturaPA' heißen
        $legacyId = (int) ($item->id ?? $item->idFatturaPA ?? 0);
        if ($legacyId <= 0) {
            throw new Exception("Keine gültige ID");
        }

        $existing = Rechnung::where('legacy_id', $legacyId)->first();
        if ($existing && $this->skipExisting) {
            $this->stats['rechnungen']['skipped']++;
            return 0;
        }

        // Gebäude auflösen (für Snapshots)
        $herkunft = (int) $item->herkunft;
        $gebaeudeId = $this->gebaeudeMapById[$herkunft] ?? $this->gebaeudeMap[$herkunft] ?? null;

        $gebaeude = null;
        $rechnungsempfaenger = null;
        $postadresse = null;

        if ($gebaeudeId) {
            $gebaeude = Gebaeude::with(['rechnungsempfaenger', 'postadresse'])->find($gebaeudeId);
            if ($gebaeude) {
                $rechnungsempfaenger = $gebaeude->rechnungsempfaenger;
                $postadresse = $gebaeude->postadresse;
            }
        }

        // Basisdaten
        $rechnungsdatum = $this->parseDate((string) $item->Data);
        $zahlungsziel = $this->parseDate((string) ($item->DataPagamento ?? $item->DataScadenzaPagamento ?? ''));
        $jahr = $rechnungsdatum ? $rechnungsdatum->year : now()->year;
        $laufnummer = (int) $item->Numero;

        $bezahlt = (int) $item->Bezahlt === 1;
        $status = $bezahlt ? 'paid' : 'sent';

        // TipoDocumento → TD01/TD04
        $tipoDocId = (int) $item->TipoDocumento;
        $tipoDocCodex = self::TIPO_DOCUMENTO_MAP[$tipoDocId] ?? 'TD01';
        $typRechnung = $tipoDocCodex === 'TD04' ? 'gutschrift' : 'rechnung';

        // TipoIva → MwSt-Einstellungen
        $tipoIva = (int) $item->TipoIva;
        $ivaSettings = self::TIPO_IVA_MAP[$tipoIva] ?? self::TIPO_IVA_MAP[8];

        // Ritenuta
        $hatRitenuta = (int) $item->Ritenuta === 1;
        $ritenutaProzent = $hatRitenuta ? 4.0 : 0.0;

        // Beträge
        $nettoSumme = $this->parseBetrag($item->RechnungsBetrag);
        $mwstBetrag = $this->parseBetrag($item->MwStr);
        $ritenutaBetrag = $this->parseBetrag($item->Rit);
        
        // Bei Reverse Charge: MwSt = 0
        if ($ivaSettings['reverse_charge']) {
            $mwstBetrag = 0;
        }

        $bruttoSumme = round($nettoSumme + $mwstBetrag, 2);
        $zahlbarBetrag = $this->berechneZahlbarBetrag(
            $nettoSumme, $mwstBetrag, $bruttoSumme, $ritenutaBetrag,
            $ivaSettings['split_payment'], $ivaSettings['reverse_charge']
        );

        // Fattura-Profil ermitteln
        $profilMapping = $this->mapFatturaProfilFromIva($ivaSettings, $hatRitenuta);

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
            'typ_rechnung'           => $typRechnung,

            // Beträge
            'netto_summe'            => $nettoSumme,
            'mwst_betrag'            => $mwstBetrag,
            'brutto_summe'           => $bruttoSumme,
            'ritenuta_betrag'        => $ritenutaBetrag,
            'zahlbar_betrag'         => $zahlbarBetrag,

            // Fattura-Profil
            'mwst_satz'              => $ivaSettings['mwst_satz'],
            'split_payment'          => $ivaSettings['split_payment'],
            'reverse_charge'         => $ivaSettings['reverse_charge'],
            'ritenuta'               => $hatRitenuta,
            'ritenuta_prozent'       => $ritenutaProzent,
            'fattura_profile_id'     => $profilMapping['fattura_profile_id'],

            // FatturaPA-Felder
            'fattura_causale'        => $this->cleanString((string) $item->Causale),
            'cig'                    => $this->cleanString((string) $item->CIG) ?: null,
            'cup'                    => $this->cleanString((string) $item->CUP) ?: null,
            'auftrag_id'             => $this->cleanString((string) $item->OrdineId) ?: null,
            'auftrag_datum'          => $this->parseDate((string) $item->OrdineData),

            // Snapshot Gebäude
            'geb_codex'              => $gebaeude?->codex,
            'geb_name'               => $gebaeude?->gebaeude_name,

            // Snapshot Rechnungsempfänger
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

            // Snapshot Postadresse
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

        if ($this->dryRun) {
            $this->stats['rechnungen']['imported']++;
            return 1;
        }

        if ($existing) {
            $existing->update($data);
        } else {
            Rechnung::create($data);
        }
        $this->stats['rechnungen']['imported']++;
        return 1;
    }

    protected function buildRechnungenMap(): void
    {
        $this->rechnungenMap = Rechnung::whereNotNull('legacy_id')
            ->pluck('id', 'legacy_id')
            ->toArray();
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // 5. POSITIONEN IMPORT
    // ═══════════════════════════════════════════════════════════════════════════

    public function importPositionen(string $xmlPath): int
    {
        $xml = $this->loadXml($xmlPath);
        $count = 0;

        if (empty($this->rechnungenMap)) {
            $this->buildRechnungenMap();
        }

        // Positionen nach Rechnung gruppieren
        $positionenProRechnung = [];
        foreach ($xml->ArtikelFatturaPAAbfrage as $item) {
            $herkunft = (int) $item->herkunft;
            if (!isset($positionenProRechnung[$herkunft])) {
                $positionenProRechnung[$herkunft] = [];
            }
            $positionenProRechnung[$herkunft][] = $item;
        }

        foreach ($positionenProRechnung as $herkunft => $positionen) {
            $posNr = 1;
            foreach ($positionen as $item) {
                try {
                    $count += $this->importPositionItem($item, $posNr);
                    $posNr++;
                } catch (Exception $e) {
                    $this->logError('positionen', (string)$item->id, $e->getMessage());
                }
            }
        }

        return $count;
    }

    protected function importPositionItem(\SimpleXMLElement $item, int $posNr): int
    {
        $legacyId = (int) $item->id;

        $existing = RechnungPosition::where('legacy_id', $legacyId)->first();
        if ($existing && $this->skipExisting) {
            $this->stats['positionen']['skipped']++;
            return 0;
        }

        $rechnungId = $this->rechnungenMap[(int) $item->herkunft] ?? null;
        if (!$rechnungId) {
            $this->logError('positionen', (string)$legacyId, "Rechnung nicht gefunden: herkunft=" . (int)$item->herkunft);
            return 0;
        }

        $mwstSatz = (float) $item->MwStSatz ?: 22;
        $einzelpreis = (float) $item->Einzelpreis ?: 0;
        $anzahl = (float) $item->Anzahl ?: 1;
        $nettoGesamt = $einzelpreis * $anzahl;
        $mwstBetrag = round($nettoGesamt * ($mwstSatz / 100), 2);

        $data = [
            'legacy_id'         => $legacyId,
            'legacy_artikel_id' => (int) $item->idHerkunftArtikel ?: null,
            'rechnung_id'       => $rechnungId,
            'position'          => $posNr,
            'beschreibung'      => html_entity_decode((string) $item->Beschreibung ?: 'Ohne Beschreibung'),
            'anzahl'            => $anzahl,
            'einzelpreis'       => $einzelpreis,
            'mwst_satz'         => $mwstSatz,
            'netto_gesamt'      => $nettoGesamt,
            'mwst_betrag'       => $mwstBetrag,
        ];

        if ($this->dryRun) {
            $this->stats['positionen']['imported']++;
            return 1;
        }

        if ($existing) {
            $existing->update($data);
        } else {
            RechnungPosition::create($data);
        }
        $this->stats['positionen']['imported']++;
        return 1;
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // 6. TIMELINE IMPORT (NEU integriert)
    // ═══════════════════════════════════════════════════════════════════════════

    public function importTimeline(string $xmlPath): int
    {
        $xml = $this->loadXml($xmlPath);
        $count = 0;

        if (empty($this->gebaeudeMap)) $this->buildGebaeudeMap();
        if (empty($this->gebaeudeMapById)) $this->buildGebaeudeMapById();

        // Bestehende Timelines laden (Duplikat-Check)
        if ($this->skipExisting) {
            $this->existingTimelines = Timeline::query()
                ->whereYear('datum', '>=', $this->minJahr)
                ->get(['gebaeude_id', 'datum'])
                ->mapWithKeys(fn($t) => [$t->gebaeude_id . ':' . $t->datum->format('Y-m-d') => true])
                ->toArray();
        }

        foreach ($xml->DatumAusfuehrung as $item) {
            try {
                $count += $this->importTimelineItem($item);
            } catch (Exception $e) {
                $this->logError('timeline', (string)$item->id, $e->getMessage());
            }
        }

        return $count;
    }

    protected function importTimelineItem(\SimpleXMLElement $item): int
    {
        $legacyId = (int) $item->id;

        // Datum parsen
        $datum = $this->parseDate((string) $item->Datum);
        if (!$datum) {
            throw new Exception("Ungültiges Datum");
        }

        // Jahr-Filter
        if ($datum->year < $this->minJahr) {
            $this->stats['timeline']['filtered']++;
            return 0;
        }

        // Gebäude auflösen
        $herkunft = (int) $item->Herkunft;
        $gebaeudeId = $this->gebaeudeMapById[$herkunft] ?? $this->gebaeudeMap[$herkunft] ?? null;

        if (!$gebaeudeId) {
            throw new Exception("Gebäude nicht gefunden (Herkunft: $herkunft)");
        }

        // Duplikat-Check
        $dupKey = $gebaeudeId . ':' . $datum->format('Y-m-d');
        if ($this->skipExisting && isset($this->existingTimelines[$dupKey])) {
            $this->stats['timeline']['skipped']++;
            return 0;
        }

        $verrechnet = (int) $item->verrechnet === 1;

        $data = [
            'gebaeude_id'   => $gebaeudeId,
            'datum'         => $datum,
            'bemerkung'     => 'Import aus Access (ID: ' . $legacyId . ')',
            'verrechnen'    => !$verrechnet,
            'verrechnet_am' => $verrechnet ? $datum : null,
        ];

        if ($this->dryRun) {
            $this->stats['timeline']['imported']++;
            $this->existingTimelines[$dupKey] = true;
            return 1;
        }

        Timeline::create($data);
        $this->stats['timeline']['imported']++;
        $this->existingTimelines[$dupKey] = true;
        return 1;
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // 7. FIX GEBÄUDE-NAMEN (NEU integriert)
    // ═══════════════════════════════════════════════════════════════════════════

    public function fixGebaeudeNamen(): int
    {
        $count = 0;

        $gebaeude = Gebaeude::query()
            ->whereNotNull('rechnungsempfaenger_id')
            ->where(function ($q) {
                $q->whereNull('gebaeude_name')
                  ->orWhere('gebaeude_name', '')
                  ->orWhere('gebaeude_name', '?');
            })
            ->get();

        foreach ($gebaeude as $geb) {
            try {
                $re = Adresse::find($geb->rechnungsempfaenger_id);
                if (!$re) {
                    $this->stats['fix_namen']['skipped']++;
                    continue;
                }

                $changes = [];

                if (empty($geb->gebaeude_name) || $geb->gebaeude_name === '?') {
                    $changes['gebaeude_name'] = $re->name;
                }
                if (empty($geb->strasse)) {
                    $changes['strasse'] = $re->strasse;
                }
                if (empty($geb->hausnummer)) {
                    $changes['hausnummer'] = $re->hausnummer;
                }
                if (empty($geb->plz)) {
                    $changes['plz'] = $re->plz;
                }
                if (empty($geb->wohnort)) {
                    $changes['wohnort'] = $re->wohnort;
                }
                if (empty($geb->land)) {
                    $changes['land'] = $re->land ?: 'IT';
                }

                if (empty($changes)) {
                    $this->stats['fix_namen']['skipped']++;
                    continue;
                }

                if (!$this->dryRun) {
                    $geb->update($changes);
                }
                $this->stats['fix_namen']['imported']++;
                $count++;

            } catch (Exception $e) {
                $this->logError('fix_namen', (string)$geb->id, $e->getMessage());
            }
        }

        return $count;
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // MAPPING METHODS
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * TypKunde → fattura_profile_id
     */
    protected function mapTypKundeToProfileId(?int $typKunde): ?int
    {
        if (!$typKunde) return null;

        $mapping = [
            1 => 4,  // Kondominium → Profil 4
            2 => 3,  // Öffentlich → Profil 3 (Split)
            3 => 2,  // Privat → Profil 2
            4 => 1,  // Firma RC → Profil 1
            5 => 1,  // Sanierung → Profil 1
            7 => 3,  // Split 22% → Profil 3
            8 => 6,  // Firma 22% → Profil 6
            9 => 5,  // Kondo 22% → Profil 5
        ];

        return $mapping[$typKunde] ?? null;
    }

    /**
     * TipoIva-Settings + Ritenuta → fattura_profile_id
     */
    protected function mapFatturaProfilFromIva(array $ivaSettings, bool $hatRitenuta): array
    {
        if ($ivaSettings['reverse_charge']) {
            return ['fattura_profile_id' => 1];
        }
        if ($ivaSettings['split_payment']) {
            return ['fattura_profile_id' => 3];
        }
        if ($hatRitenuta && $ivaSettings['mwst_satz'] == 10) {
            return ['fattura_profile_id' => 4];
        }
        if ($ivaSettings['mwst_satz'] == 10) {
            return ['fattura_profile_id' => 2];
        }
        if ($ivaSettings['mwst_satz'] == 22 && $hatRitenuta) {
            return ['fattura_profile_id' => 5];
        }
        if ($ivaSettings['mwst_satz'] == 22) {
            return ['fattura_profile_id' => 6];
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
        if ($splitPayment || $reverseCharge) {
            return round($netto - $ritenuta, 2);
        }
        return round($brutto - $ritenuta, 2);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // HELPER METHODS
    // ═══════════════════════════════════════════════════════════════════════════

    protected function loadXml(string $path): \SimpleXMLElement
    {
        if (!file_exists($path)) {
            throw new Exception("XML-Datei nicht gefunden: $path");
        }

        $content = file_get_contents($path);
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content); // BOM entfernen

        return simplexml_load_string($content);
    }

    protected function resolveAdresse(int $legacyMid): ?int
    {
        if ($legacyMid <= 0) return null;
        return $this->adressenMap[$legacyMid] ?? null;
    }

    protected function parseDate(?string $dateString): ?Carbon
    {
        if (!$dateString || $dateString === '' || $dateString === '0000-00-00') {
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

            // Deutsche Formate: 28.11.2025
            if (preg_match('/^(\d{2})\.(\d{2})\.(\d{4})/', $dateString, $m)) {
                return Carbon::createFromFormat('d.m.Y', "{$m[1]}.{$m[2]}.{$m[3]}");
            }

            return Carbon::parse($dateString);
        } catch (Exception $e) {
            return null;
        }
    }

    protected function parseBetrag($betrag): float
    {
        if (is_numeric($betrag)) {
            return (float) $betrag;
        }

        $betrag = (string) $betrag;
        $betrag = str_replace(['€', ' '], '', trim($betrag));

        if ($betrag === '' || $betrag === '-') {
            return 0.0;
        }

        // Deutsches Format: 1.234,56 → 1234.56
        if (str_contains($betrag, ',')) {
            $betrag = str_replace('.', '', $betrag);
            $betrag = str_replace(',', '.', $betrag);
        }

        return (float) $betrag;
    }

    protected function cleanString(?string $str): ?string
    {
        if (!$str) return null;
        $str = trim($str);
        $str = trim($str, '"\'');
        return $str === '' ? null : $str;
    }

    protected function logError(string $table, string $id, string $message): void
    {
        $this->stats[$table]['errors']++;
        $this->errors[] = [
            'table'   => $table,
            'id'      => $id,
            'message' => $message,
        ];

        Log::warning("Import-Fehler [$table]", ['id' => $id, 'message' => $message]);
    }
}
