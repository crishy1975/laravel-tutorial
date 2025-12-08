<?php

namespace App\Services\Import;

use App\Models\Adresse;
use App\Models\Gebaeude;
use App\Models\ArtikelGebaeude;
use App\Models\Rechnung;
use App\Models\RechnungPosition;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use Exception;

/**
 * Access Import Service
 * 
 * Importiert Daten aus der alten Access-Datenbank (XML-Export) in Laravel.
 * 
 * Import-Reihenfolge (wichtig wegen Referenzen!):
 * 1. Adressen
 * 2. Gebäude
 * 3. Artikel (Stamm)
 * 4. Rechnungen
 * 5. Rechnungspositionen
 */
class AccessImportService
{
    protected array $stats = [
        'adressen'    => ['imported' => 0, 'skipped' => 0, 'errors' => 0],
        'gebaeude'    => ['imported' => 0, 'skipped' => 0, 'errors' => 0],
        'artikel'     => ['imported' => 0, 'skipped' => 0, 'errors' => 0],
        'rechnungen'  => ['imported' => 0, 'skipped' => 0, 'errors' => 0],
        'positionen'  => ['imported' => 0, 'skipped' => 0, 'errors' => 0],
    ];

    protected array $errors = [];
    protected bool $dryRun = false;
    protected bool $skipExisting = true;

    // Lookup-Tabellen für Referenz-Auflösung
    protected array $adressenMap = [];     // legacy_mid → neue ID
    protected array $gebaeudeMap = [];     // legacy_mid → neue ID
    protected array $rechnungenMap = [];   // legacy_id (idFatturaPA) → neue ID

    /**
     * Konfiguration setzen
     */
    public function configure(bool $dryRun = false, bool $skipExisting = true): self
    {
        $this->dryRun = $dryRun;
        $this->skipExisting = $skipExisting;
        return $this;
    }

    /**
     * Statistiken abrufen
     */
    public function getStats(): array
    {
        return $this->stats;
    }

    /**
     * Fehler abrufen
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    // ═══════════════════════════════════════════════════════════
    // 📋 ADRESSEN IMPORT
    // ═══════════════════════════════════════════════════════════

    /**
     * Importiert Adressen aus XML
     */
    public function importAdressen(string $xmlPath): int
    {
        $xml = $this->loadXml($xmlPath);
        $count = 0;

        foreach ($xml->Adresse as $item) {
            try {
                $count += $this->importAdresse($item);
            } catch (Exception $e) {
                $this->logError('adressen', (string)$item->id, $e->getMessage());
            }
        }

        // Lookup-Tabelle aufbauen
        $this->buildAdressenMap();

        return $count;
    }

    protected function importAdresse(\SimpleXMLElement $item): int
    {
        $legacyMid = (int) $item->mId;
        $legacyId = (int) $item->id;

        // ⭐ Duplikat-Prüfung (IMMER, nicht nur bei skipExisting)
        $existing = Adresse::where('legacy_mid', $legacyMid)->first();
        if ($existing) {
            if ($this->skipExisting) {
                $this->stats['adressen']['skipped']++;
                Log::debug("Adresse übersprungen (Duplikat)", [
                    'legacy_mid' => $legacyMid,
                    'existing_id' => $existing->id
                ]);
                return 0;
            }
            // Bei --force: Existierenden aktualisieren statt neu anlegen
            // (Optional: hier könnte man $existing->update($data) machen)
        }

        // Name zusammensetzen (Vorname + Nachname)
        $vorname = trim((string) $item->Vorname);
        $nachname = trim((string) $item->Nachname);
        $name = trim("$vorname $nachname") ?: 'Unbekannt';

        $data = [
            'legacy_id'       => $legacyId,
            'legacy_mid'      => $legacyMid,
            'name'            => $name,
            'anrede'          => (string) $item->Anrede ?: null,
            'strasse'         => (string) $item->Strasse ?: null,
            'hausnummer'      => (string) $item->Nr ?: null,
            'plz'             => (string) $item->PLZ ?: null,
            'wohnort'         => (string) $item->Wohnort ?: null,
            'provinz'         => (string) $item->Provinz ?: null,
            'land'            => (string) $item->Land ?: 'IT',
            'telefon'         => (string) $item->Telefon ?: null,
            'handy'           => (string) $item->Handy ?: null,
            'email'           => (string) $item->Email ?: null,
            'pec'             => (string) $item->Pec ?: null,
            'steuernummer'    => (string) $item->Steuernummer ?: null,
            'mwst_nummer'     => (string) $item->Mwst ?: null,
            'codice_univoco'  => (string) $item->CodiceUnivoco ?: null,
            'bemerkung'       => (string) $item->Bemerkung ?: null,
        ];

        if ($this->dryRun) {
            Log::info('[DRY-RUN] Würde Adresse importieren', ['legacy_mid' => $legacyMid, 'name' => $name]);
            $this->stats['adressen']['imported']++;
            return 1;
        }

        Adresse::create($data);
        $this->stats['adressen']['imported']++;

        return 1;
    }

    protected function buildAdressenMap(): void
    {
        $this->adressenMap = Adresse::whereNotNull('legacy_mid')
            ->pluck('id', 'legacy_mid')
            ->toArray();
    }

    // ═══════════════════════════════════════════════════════════
    // 🏢 GEBÄUDE IMPORT
    // ═══════════════════════════════════════════════════════════

    /**
     * Importiert Gebäude aus XML
     */
    public function importGebaeude(string $xmlPath): int
    {
        $xml = $this->loadXml($xmlPath);
        $count = 0;

        // Sicherstellen dass Adressen-Map existiert
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

        // Lookup-Tabelle aufbauen
        $this->buildGebaeudeMap();

        return $count;
    }

    protected function importGebaeudeItem(\SimpleXMLElement $item): int
    {
        $legacyMid = (int) $item->mId;
        $legacyId = (int) $item->id;

        // ⭐ Duplikat-Prüfung (IMMER)
        $existing = Gebaeude::where('legacy_mid', $legacyMid)->first();
        if ($existing) {
            if ($this->skipExisting) {
                $this->stats['gebaeude']['skipped']++;
                Log::debug("Gebäude übersprungen (Duplikat)", [
                    'legacy_mid' => $legacyMid,
                    'codex' => (string) $item->Codex,
                    'existing_id' => $existing->id
                ]);
                return 0;
            }
        }

        // Referenzen auflösen
        $postadresseId = $this->resolveAdresse((int) $item->Postadresse);
        $rechnungsempfaengerId = $this->resolveAdresse((int) $item->Rechnungsempfaenger);

        // Letzter Termin parsen (Dummy-Datum 2000-01-01 ignorieren)
        $letzterTermin = $this->parseDate((string) $item->LetzterTermin);
        if ($letzterTermin && $letzterTermin->year <= 2000) {
            $letzterTermin = null;
        }

        $data = [
            'legacy_id'              => $legacyId,
            'legacy_mid'             => $legacyMid,
            'codex'                  => (string) $item->Codex ?: null,
            'gebaeude_name'          => (string) $item->Namen1 ?: null,
            'strasse'                => (string) $item->Strasse ?: null,
            'hausnummer'             => (string) $item->Hausnummer ?: null,
            'plz'                    => (string) $item->PLZ ?: null,
            'wohnort'                => (string) $item->Wohnort ?: null,
            'land'                   => 'IT',
            'bemerkung'              => (string) $item->Bemerkung ?: null,
            'postadresse_id'         => $postadresseId,
            'rechnungsempfaenger_id' => $rechnungsempfaengerId,
            'letzter_termin'         => $letzterTermin,
            'faellig'                => (int) $item->Faellig === 1,
            'geplante_reinigungen'   => (int) $item->anzReinigungPlan ?: 0,
            'gemachte_reinigungen'   => (int) $item->anzReinigung ?: 0,
            // Monate
            'm01' => (int) $item->jan === 1,
            'm02' => (int) $item->feb === 1,
            'm03' => (int) $item->mar === 1,
            'm04' => (int) $item->apr === 1,
            'm05' => (int) $item->mai === 1,
            'm06' => (int) $item->jun === 1,
            'm07' => (int) $item->jul === 1,
            'm08' => (int) $item->aug === 1,
            'm09' => (int) $item->sep === 1,
            'm10' => (int) $item->okt === 1,
            'm11' => (int) $item->nov === 1,
            'm12' => (int) $item->dez === 1,
        ];

        if ($this->dryRun) {
            Log::info('[DRY-RUN] Würde Gebäude importieren', [
                'legacy_mid' => $legacyMid,
                'codex' => $data['codex'],
            ]);
            $this->stats['gebaeude']['imported']++;
            return 1;
        }

        Gebaeude::create($data);
        $this->stats['gebaeude']['imported']++;

        return 1;
    }

    protected function buildGebaeudeMap(): void
    {
        $this->gebaeudeMap = Gebaeude::whereNotNull('legacy_mid')
            ->pluck('id', 'legacy_mid')
            ->toArray();
    }

    // ═══════════════════════════════════════════════════════════
    // 📦 ARTIKEL (STAMM) IMPORT
    // ═══════════════════════════════════════════════════════════

    /**
     * Importiert Artikel (Stammdaten) aus XML → artikel_gebaeude
     */
    public function importArtikel(string $xmlPath): int
    {
        $xml = $this->loadXml($xmlPath);
        $count = 0;

        // Sicherstellen dass Gebäude-Map existiert
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
        $legacyMid = (int) $item->mId;
        $legacyId = (int) $item->id;

        // ⭐ Duplikat-Prüfung (IMMER)
        $existing = ArtikelGebaeude::where('legacy_mid', $legacyMid)->first();
        if ($existing) {
            if ($this->skipExisting) {
                $this->stats['artikel']['skipped']++;
                Log::debug("Artikel übersprungen (Duplikat)", [
                    'legacy_mid' => $legacyMid,
                    'existing_id' => $existing->id
                ]);
                return 0;
            }
        }

        // Gebäude-Referenz auflösen (herkunft → Gebaeude.mId)
        $gebaeudeId = $this->resolveGebaeude((int) $item->herkunft);

        if (!$gebaeudeId) {
            $this->logError('artikel', $legacyId, "Gebäude nicht gefunden: herkunft=" . (int)$item->herkunft);
            return 0;
        }

        $data = [
            'legacy_id'    => $legacyId,
            'legacy_mid'   => $legacyMid,
            'gebaeude_id'  => $gebaeudeId,
            'beschreibung' => (string) $item->Beschreibung ?: 'Ohne Beschreibung',
            'einzelpreis'  => (float) $item->Einzelpreis ?: 0,
            'anzahl'       => (float) $item->Anzahl ?: 1,
            'aktiv'        => true,
            'basis_preis'  => (float) $item->Einzelpreis ?: 0,
            'basis_jahr'   => now()->year,
        ];

        if ($this->dryRun) {
            Log::info('[DRY-RUN] Würde Artikel importieren', [
                'legacy_mid' => $legacyMid,
                'beschreibung' => substr($data['beschreibung'], 0, 50),
            ]);
            $this->stats['artikel']['imported']++;
            return 1;
        }

        ArtikelGebaeude::create($data);
        $this->stats['artikel']['imported']++;

        return 1;
    }

    // ═══════════════════════════════════════════════════════════
    // 🧾 RECHNUNGEN IMPORT
    // ═══════════════════════════════════════════════════════════

    /**
     * Importiert Rechnungen aus XML
     */
    public function importRechnungen(string $xmlPath): int
    {
        $xml = $this->loadXml($xmlPath);
        $count = 0;

        // Sicherstellen dass Maps existieren
        if (empty($this->adressenMap)) {
            $this->buildAdressenMap();
        }
        if (empty($this->gebaeudeMap)) {
            $this->buildGebaeudeMap();
        }

        foreach ($xml->FatturaPAXmlAbfrage as $item) {
            try {
                $count += $this->importRechnungItem($item);
            } catch (Exception $e) {
                $this->logError('rechnungen', (string)$item->idFatturaPA, $e->getMessage());
            }
        }

        // Lookup-Tabelle aufbauen
        $this->buildRechnungenMap();

        return $count;
    }

    protected function importRechnungItem(\SimpleXMLElement $item): int
    {
        $legacyId = (int) $item->idFatturaPA;
        $progressivo = (int) $item->ProgressivoInvio;

        // ⭐ Duplikat-Prüfung (IMMER)
        $existing = Rechnung::where('legacy_id', $legacyId)->first();
        if ($existing) {
            if ($this->skipExisting) {
                $this->stats['rechnungen']['skipped']++;
                Log::debug("Rechnung übersprungen (Duplikat)", [
                    'legacy_id' => $legacyId,
                    'existing_id' => $existing->id
                ]);
                return 0;
            }
        }

        // Referenzen auflösen
        $gebaeudeId = $this->resolveGebaeude((int) $item->herkunft);
        $rechnungsempfaengerId = $this->resolveAdresse((int) $item->Rechnungsempfaenger);

        // Rechnungsdatum parsen
        $rechnungsdatum = $this->parseDate((string) $item->Data);
        $zahlungsziel = $this->parseDate((string) $item->DataPagamento);

        // Jahr und Laufnummer
        $jahr = $rechnungsdatum ? $rechnungsdatum->year : now()->year;
        $laufnummer = (int) $item->Numero;

        // Status ermitteln
        $status = (int) $item->Bezahlt === 1 ? 'paid' : 'sent';

        // Typ ermitteln (TipoDocumento: 2 = Rechnung, sonst Gutschrift?)
        $typ = 'rechnung';
        $tipoDoc = (string) $item->TipoDocumentoCodex;
        if ($tipoDoc === 'TD04') {
            $typ = 'gutschrift';
        }

        $data = [
            'legacy_id'              => $legacyId,
            'legacy_progressivo'     => $progressivo,
            'jahr'                   => $jahr,
            'laufnummer'             => $laufnummer,
            'gebaeude_id'            => $gebaeudeId,
            'rechnungsempfaenger_id' => $rechnungsempfaengerId,
            'rechnungsdatum'         => $rechnungsdatum,
            'zahlungsziel'           => $zahlungsziel,
            'status'                 => $status,
            'typ_rechnung'           => $typ,
            'netto_summe'            => (float) $item->RechnungsBetrag ?: 0,
            'mwst_betrag'            => (float) $item->MwStr ?: 0,
            'brutto_summe'           => (float) $item->Betrag ?: 0,
            'ritenuta_betrag'        => (float) $item->Rit ?: 0,
            'mwst_satz'              => (float) $item->mMwStSatz ?: 22,
            'fattura_causale'        => (string) $item->Causale ?: null,
            'cig'                    => (string) $item->{'FatturaPAAbfrage.CIG'} ?: null,
            'auftrag_id'             => (string) $item->OrdineId ?: null,
            'auftrag_datum'          => $this->parseDate((string) $item->OrdineData),
            // Snapshot Gebäude
            'geb_codex'              => (string) $item->Codex ?: null,
            'geb_name'               => (string) $item->Namen1 ?: null,
            // ⭐ Snapshot Rechnungsempfänger - Vorname + Nachname zusammenführen!
            're_name'                => trim(
                ((string) $item->{'GebaeudeAbfrage.Rechnungsempfaenger.Vorname'} ?: '') . ' ' .
                ((string) $item->{'GebaeudeAbfrage.Rechnungsempfaenger.Nachname'} ?: '')
            ) ?: ((string) $item->aNachname ?: null),
            're_strasse'             => (string) $item->{'GebaeudeAbfrage.Rechnungsempfaenger.Strasse'} ?: ((string) $item->aStrasse ?: null),
            're_hausnummer'          => (string) $item->{'GebaeudeAbfrage.Rechnungsempfaenger.Nr'} ?: ((string) $item->aHausnummer ?: null),
            're_plz'                 => (string) $item->{'GebaeudeAbfrage.Rechnungsempfaenger.PLZ'} ?: ((string) $item->aPLZ ?: null),
            're_wohnort'             => (string) $item->{'GebaeudeAbfrage.Rechnungsempfaenger.Wohnort'} ?: ((string) $item->aGemeinde ?: null),
            're_provinz'             => (string) $item->{'GebaeudeAbfrage.Rechnungsempfaenger.Provinz'} ?: ((string) $item->aProvinz ?: null),
            're_land'                => (string) $item->{'GebaeudeAbfrage.Rechnungsempfaenger.Land'} ?: ((string) $item->aLand ?: 'IT'),
            're_mwst_nummer'         => (string) $item->{'GebaeudeAbfrage.Rechnungsempfaenger.Mwst'} ?: ((string) $item->mMwSt ?: null),
            're_steuernummer'        => (string) $item->{'GebaeudeAbfrage.Rechnungsempfaenger.Steuernummer'} ?: ((string) $item->mSteuernummer ?: null),
            're_codice_univoco'      => (string) $item->{'GebaeudeAbfrage.Rechnungsempfaenger.CodiceUnivoco'} ?: ((string) $item->mCodiceDestinatario ?: null),
            're_pec'                 => (string) $item->{'GebaeudeAbfrage.Rechnungsempfaenger.Pec'} ?: null,
            
            // ⭐ Snapshot Postadresse - Vorname + Nachname zusammenführen!
            'post_name'              => trim(
                ((string) $item->{'GebaeudeAbfrage.Postadresse.Vorname'} ?: '') . ' ' .
                ((string) $item->{'GebaeudeAbfrage.Postadresse.Nachname'} ?: '')
            ) ?: ((string) $item->pNachname ?: null),
            'post_strasse'           => (string) $item->{'GebaeudeAbfrage.Postadresse.Strasse'} ?: ((string) $item->pStrasse ?: null),
            'post_hausnummer'        => (string) $item->{'GebaeudeAbfrage.Postadresse.Nr'} ?: ((string) $item->pHausnummer ?: null),
            'post_plz'               => (string) $item->{'GebaeudeAbfrage.Postadresse.PLZ'} ?: null,
            'post_wohnort'           => (string) $item->{'GebaeudeAbfrage.Postadresse.Wohnort'} ?: ((string) $item->pWohnort ?: null),
            'post_provinz'           => (string) $item->{'GebaeudeAbfrage.Postadresse.Provinz'} ?: null,
            'post_land'              => (string) $item->{'GebaeudeAbfrage.Postadresse.Land'} ?: 'IT',
            'post_email'             => (string) $item->{'GebaeudeAbfrage.Postadresse.Email'} ?: null,
            'post_pec'               => (string) $item->{'GebaeudeAbfrage.Postadresse.Pec'} ?: null,
        ];

        if ($this->dryRun) {
            Log::info('[DRY-RUN] Würde Rechnung importieren', [
                'legacy_id' => $legacyId,
                'nummer' => "$jahr/$laufnummer",
            ]);
            $this->stats['rechnungen']['imported']++;
            return 1;
        }

        Rechnung::create($data);
        $this->stats['rechnungen']['imported']++;

        return 1;
    }

    protected function buildRechnungenMap(): void
    {
        $this->rechnungenMap = Rechnung::whereNotNull('legacy_id')
            ->pluck('id', 'legacy_id')
            ->toArray();
    }

    // ═══════════════════════════════════════════════════════════
    // 📝 RECHNUNGSPOSITIONEN IMPORT
    // ═══════════════════════════════════════════════════════════

    /**
     * Importiert Rechnungspositionen aus XML
     */
    public function importRechnungspositionen(string $xmlPath): int
    {
        $xml = $this->loadXml($xmlPath);
        $count = 0;

        // Sicherstellen dass Rechnungen-Map existiert
        if (empty($this->rechnungenMap)) {
            $this->buildRechnungenMap();
        }

        // Positionen nach Rechnung gruppieren (für position-Nummerierung)
        $positionenProRechnung = [];

        foreach ($xml->ArtikelFatturaPAAbfrage as $item) {
            $herkunft = (int) $item->herkunft;
            if (!isset($positionenProRechnung[$herkunft])) {
                $positionenProRechnung[$herkunft] = [];
            }
            $positionenProRechnung[$herkunft][] = $item;
        }

        // Jetzt importieren mit korrekter Nummerierung
        foreach ($positionenProRechnung as $herkunft => $positionen) {
            $posNr = 1;
            foreach ($positionen as $item) {
                try {
                    $count += $this->importRechnungspositionItem($item, $posNr);
                    $posNr++;
                } catch (Exception $e) {
                    $this->logError('positionen', (string)$item->id, $e->getMessage());
                }
            }
        }

        return $count;
    }

    protected function importRechnungspositionItem(\SimpleXMLElement $item, int $posNr): int
    {
        $legacyId = (int) $item->id;

        // ⭐ Duplikat-Prüfung (IMMER)
        $existing = RechnungPosition::where('legacy_id', $legacyId)->first();
        if ($existing) {
            if ($this->skipExisting) {
                $this->stats['positionen']['skipped']++;
                Log::debug("Position übersprungen (Duplikat)", [
                    'legacy_id' => $legacyId,
                    'existing_id' => $existing->id
                ]);
                return 0;
            }
        }

        // Rechnung-Referenz auflösen (herkunft → FatturaPAAbfrage.idFatturaPA)
        $rechnungId = $this->resolveRechnung((int) $item->herkunft);

        if (!$rechnungId) {
            $this->logError('positionen', $legacyId, "Rechnung nicht gefunden: herkunft=" . (int)$item->herkunft);
            return 0;
        }

        // MwSt-Satz aus Natura oder Standard
        $mwstSatz = (float) $item->MwStSatz ?: 22;

        $einzelpreis = (float) $item->Einzelpreis ?: 0;
        $anzahl = (float) $item->Anzahl ?: 1;
        $nettoGesamt = $einzelpreis * $anzahl;
        $mwstBetrag = round($nettoGesamt * ($mwstSatz / 100), 2);

        $data = [
            'legacy_id'          => $legacyId,
            'legacy_artikel_id'  => (int) $item->idHerkunftArtikel ?: null,
            'rechnung_id'        => $rechnungId,
            'position'           => $posNr,
            'beschreibung'       => html_entity_decode((string) $item->Beschreibung ?: 'Ohne Beschreibung'),
            'anzahl'             => $anzahl,
            'einzelpreis'        => $einzelpreis,
            'mwst_satz'          => $mwstSatz,
            'netto_gesamt'       => $nettoGesamt,
            'mwst_betrag'        => $mwstBetrag,
        ];

        if ($this->dryRun) {
            Log::info('[DRY-RUN] Würde Position importieren', [
                'legacy_id' => $legacyId,
                'rechnung_id' => $rechnungId,
                'position' => $posNr,
            ]);
            $this->stats['positionen']['imported']++;
            return 1;
        }

        RechnungPosition::create($data);
        $this->stats['positionen']['imported']++;

        return 1;
    }

    // ═══════════════════════════════════════════════════════════
    // 🔧 HELPER METHODS
    // ═══════════════════════════════════════════════════════════

    /**
     * Lädt XML-Datei
     */
    protected function loadXml(string $path): \SimpleXMLElement
    {
        if (!file_exists($path)) {
            throw new Exception("XML-Datei nicht gefunden: $path");
        }

        $content = file_get_contents($path);
        
        // BOM entfernen falls vorhanden
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

        return simplexml_load_string($content);
    }

    /**
     * Adresse-Referenz auflösen (legacy_mid → neue ID)
     */
    protected function resolveAdresse(int $legacyMid): ?int
    {
        if ($legacyMid <= 0) {
            return null;
        }
        return $this->adressenMap[$legacyMid] ?? null;
    }

    /**
     * Gebäude-Referenz auflösen (legacy_mid → neue ID)
     */
    protected function resolveGebaeude(int $legacyMid): ?int
    {
        if ($legacyMid <= 0) {
            return null;
        }
        return $this->gebaeudeMap[$legacyMid] ?? null;
    }

    /**
     * Rechnung-Referenz auflösen (legacy_id/idFatturaPA → neue ID)
     */
    protected function resolveRechnung(int $legacyId): ?int
    {
        if ($legacyId <= 0) {
            return null;
        }
        return $this->rechnungenMap[$legacyId] ?? null;
    }

    /**
     * Datum parsen
     */
    protected function parseDate(?string $dateString): ?Carbon
    {
        if (!$dateString || $dateString === '' || $dateString === '0000-00-00') {
            return null;
        }

        try {
            return Carbon::parse($dateString);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Fehler loggen
     */
    protected function logError(string $table, string $id, string $message): void
    {
        $this->stats[$table]['errors']++;
        $this->errors[] = [
            'table'   => $table,
            'id'      => $id,
            'message' => $message,
        ];

        Log::warning("Import-Fehler [$table]", [
            'id' => $id,
            'message' => $message,
        ]);
    }
}