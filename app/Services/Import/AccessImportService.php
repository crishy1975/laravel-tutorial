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
 * 2. Gebaeude
 * 3. Artikel (Stamm)
 * 4. Rechnungen
 * 5. Rechnungspositionen
 * 
 * WICHTIG - Index-Referenzen:
 * - Adressen: mId ist der primaere Schluessel (legacy_mid)
 * - Gebaeude: id ist der primaere Schluessel (legacy_id), mId ist sekundaer
 * - Artikel: id ist der primaere Schluessel, herkunft -> Gebaeude.id
 * - Rechnungen: idFatturaPA ist der primaere Schluessel
 * - Positionen: id ist der primaere Schluessel, herkunft -> FatturaPA.idFatturaPA
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

    // Lookup-Tabellen fuer Referenz-Aufloesung
    protected array $adressenMap = [];       // legacy_mid -> neue ID
    protected array $gebaeudeMap = [];       // legacy_mid -> neue ID (fuer Rechnungen)
    protected array $gebaeudeMapById = [];   // legacy_id -> neue ID (fuer Artikel!)
    protected array $rechnungenMap = [];     // legacy_id (idFatturaPA) -> neue ID

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

    // =====================================================================
    // ADRESSEN IMPORT
    // =====================================================================

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

        // Duplikat-Pruefung
        $existing = Adresse::where('legacy_mid', $legacyMid)->first();
        if ($existing) {
            if ($this->skipExisting) {
                $this->stats['adressen']['skipped']++;
                Log::debug("Adresse uebersprungen (Duplikat)", [
                    'legacy_mid' => $legacyMid,
                    'existing_id' => $existing->id
                ]);
                return 0;
            }
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
            Log::info('[DRY-RUN] Wuerde Adresse importieren', ['legacy_mid' => $legacyMid, 'name' => $name]);
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
        
        Log::info("AdressenMap aufgebaut", ['count' => count($this->adressenMap)]);
    }

    // =====================================================================
    // GEBAEUDE IMPORT
    // =====================================================================

    /**
     * Importiert Gebaeude aus XML
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

        // Lookup-Tabellen aufbauen (beide!)
        $this->buildGebaeudeMap();
        $this->buildGebaeudeMapById();

        return $count;
    }

    protected function importGebaeudeItem(\SimpleXMLElement $item): int
    {
        $legacyMid = (int) $item->mId;
        $legacyId = (int) $item->id;

        // Duplikat-Pruefung auf legacy_id (primaerer Schluessel)
        $existing = Gebaeude::where('legacy_id', $legacyId)->first();
        if ($existing) {
            if ($this->skipExisting) {
                $this->stats['gebaeude']['skipped']++;
                Log::debug("Gebaeude uebersprungen (Duplikat)", [
                    'legacy_id' => $legacyId,
                    'codex' => (string) $item->Codex,
                    'existing_id' => $existing->id
                ]);
                return 0;
            }
        }

        // Referenzen aufloesen
        $postadresseId = $this->resolveAdresse((int) $item->Postadresse);
        $rechnungsempfaengerId = $this->resolveAdresse((int) $item->Rechnungsempfaenger);

        // Rechnungsempfaenger laden (fuer Fallback bei fehlenden Daten)
        $rechnungsempfaenger = null;
        if ($rechnungsempfaengerId) {
            $rechnungsempfaenger = Adresse::find($rechnungsempfaengerId);
        }

        // Letzter Termin parsen (Dummy-Datum 2000-01-01 ignorieren)
        $letzterTermin = $this->parseDate((string) $item->LetzterTermin);
        if ($letzterTermin && $letzterTermin->year <= 2000) {
            $letzterTermin = null;
        }

        // FatturaPA-Profil aus TypKunde ermitteln (Grossbuchstaben!)
        $typKunde = (int) $item->TypKunde;
        $fatturaProfil = $this->mapFatturaProfil($typKunde);
        $fatturaProfileId = $fatturaProfil['fattura_profile_id'];

        Log::debug("Gebaeude FatturaPA-Profil gemappt", [
            'legacy_id' => $legacyId,
            'codex' => (string) $item->Codex,
            'TypKunde' => $typKunde,
            'fattura_profile_id' => $fatturaProfileId,
            'profile_bezeichnung' => $fatturaProfil['profile_bezeichnung'],
        ]);

        // CUP, CIG, Codice Commessa auslesen (falls vorhanden)
        $cup = trim((string) $item->CUP) ?: null;
        $cig = trim((string) $item->CIG) ?: null;
        $codiceCommessa = trim((string) $item->CodiceCommessa) ?: null;
        $auftragId = trim((string) $item->AuftragId) ?: null;
        $auftragDatum = $this->parseDate((string) $item->AuftragDatum);

        // Daten zusammenstellen MIT FALLBACK auf Rechnungsempfaenger
        $xmlName = trim((string) $item->Namen1);
        $xmlStrasse = trim((string) $item->Strasse);
        $xmlHausnummer = trim((string) $item->Hausnummer);
        $xmlPlz = trim((string) $item->PLZ);
        $xmlWohnort = trim((string) $item->Wohnort);

        // Fallback: Wenn Gebaeude-Daten leer sind, vom Rechnungsempfaenger uebernehmen
        $gebaeudeName = $xmlName;
        $strasse = $xmlStrasse;
        $hausnummer = $xmlHausnummer;
        $plz = $xmlPlz;
        $wohnort = $xmlWohnort;

        if ($rechnungsempfaenger) {
            if (empty($gebaeudeName) || $gebaeudeName === '?') {
                $gebaeudeName = $rechnungsempfaenger->name;
                Log::debug("Gebaeude-Name vom RE uebernommen", [
                    'codex' => (string) $item->Codex,
                    'name' => $gebaeudeName,
                ]);
            }
            if (empty($strasse)) {
                $strasse = $rechnungsempfaenger->strasse;
            }
            if (empty($hausnummer)) {
                $hausnummer = $rechnungsempfaenger->hausnummer;
            }
            if (empty($plz)) {
                $plz = $rechnungsempfaenger->plz;
            }
            if (empty($wohnort)) {
                $wohnort = $rechnungsempfaenger->wohnort;
            }
        }

        $data = [
            'legacy_id'              => $legacyId,
            'legacy_mid'             => $legacyMid,
            'codex'                  => (string) $item->Codex ?: null,
            'gebaeude_name'          => $gebaeudeName ?: null,
            'strasse'                => $strasse ?: null,
            'hausnummer'             => $hausnummer ?: null,
            'plz'                    => $plz ?: null,
            'wohnort'                => $wohnort ?: null,
            'land'                   => 'IT',
            'bemerkung'              => (string) $item->Bemerkung ?: null,
            'postadresse_id'         => $postadresseId,
            'rechnungsempfaenger_id' => $rechnungsempfaengerId,
            'letzter_termin'         => $letzterTermin,
            'faellig'                => (int) $item->Faellig === 1,
            'geplante_reinigungen'   => (int) $item->anzReinigungPlan ?: 0,
            'gemachte_reinigungen'   => (int) $item->anzReinigung ?: 0,

            // FatturaPA-Profil
            'fattura_profile_id'     => $fatturaProfileId,

            // FatturaPA-Zusatzdaten
            'cup'                    => $cup,
            'cig'                    => $cig,
            'codice_commessa'        => $codiceCommessa,
            'auftrag_id'             => $auftragId,
            'auftrag_datum'          => $auftragDatum,

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
            Log::info('[DRY-RUN] Wuerde Gebaeude importieren', [
                'legacy_id' => $legacyId,
                'codex' => $data['codex'],
                'gebaeude_name' => $data['gebaeude_name'],
                'TypKunde' => $typKunde,
                'fattura_profile_id' => $fatturaProfileId,
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
        
        Log::info("GebaeudeMap (legacy_mid) aufgebaut", ['count' => count($this->gebaeudeMap)]);
    }

    protected function buildGebaeudeMapById(): void
    {
        $this->gebaeudeMapById = Gebaeude::whereNotNull('legacy_id')
            ->pluck('id', 'legacy_id')
            ->toArray();
        
        Log::info("GebaeudeMapById (legacy_id) aufgebaut", ['count' => count($this->gebaeudeMapById)]);
    }

    // =====================================================================
    // ARTIKEL (STAMM) IMPORT
    // =====================================================================

    /**
     * Importiert Artikel (Stammdaten) aus XML -> artikel_gebaeude
     * 
     * WICHTIG: 
     * - id ist der primaere Schluessel (nicht mId!)
     * - herkunft verweist auf Gebaeude.mId (legacy_mid)
     */
    public function importArtikel(string $xmlPath): int
    {
        $xml = $this->loadXml($xmlPath);
        $count = 0;

        // Sicherstellen dass Gebaeude-Map existiert (nach legacy_mid!)
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
        // id ist der primaere Schluessel
        $legacyId = (int) $item->id;
        $herkunft = (int) $item->herkunft;
        $beschreibung = (string) $item->Beschreibung;

        // Duplikat-Pruefung auf legacy_id
        $existing = ArtikelGebaeude::where('legacy_id', $legacyId)->first();
        if ($existing) {
            if ($this->skipExisting) {
                $this->stats['artikel']['skipped']++;
                Log::debug("Artikel uebersprungen (Duplikat)", [
                    'legacy_id' => $legacyId,
                    'existing_id' => $existing->id
                ]);
                return 0;
            }
        }

        // herkunft verweist auf Gebaeude.mId (legacy_mid)
        $gebaeudeId = $this->resolveGebaeude($herkunft);

        if (!$gebaeudeId) {
            $this->logError('artikel', (string)$legacyId, "Gebaeude nicht gefunden: herkunft=$herkunft (legacy_mid)");
            return 0;
        }

        $data = [
            'legacy_id'    => $legacyId,
            'legacy_mid'   => null,
            'gebaeude_id'  => $gebaeudeId,
            'beschreibung' => $beschreibung ?: 'Ohne Beschreibung',
            'einzelpreis'  => (float) $item->Einzelpreis ?: 0,
            'anzahl'       => (float) $item->Anzahl ?: 1,
            'aktiv'        => true,
            'basis_preis'  => (float) $item->Einzelpreis ?: 0,
            'basis_jahr'   => now()->year,
        ];

        if ($this->dryRun) {
            Log::info('[DRY-RUN] Wuerde Artikel importieren', [
                'legacy_id' => $legacyId,
                'herkunft' => $herkunft,
                'gebaeude_id' => $gebaeudeId,
                'beschreibung' => substr($beschreibung, 0, 50),
            ]);
            $this->stats['artikel']['imported']++;
            return 1;
        }

        ArtikelGebaeude::create($data);
        $this->stats['artikel']['imported']++;

        return 1;
    }

    // =====================================================================
    // RECHNUNGEN IMPORT
    // =====================================================================

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
        if (empty($this->gebaeudeMapById)) {
            $this->buildGebaeudeMapById();
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

        // Duplikat-Pruefung
        $existing = Rechnung::where('legacy_id', $legacyId)->first();
        if ($existing) {
            if ($this->skipExisting) {
                $this->stats['rechnungen']['skipped']++;
                Log::debug("Rechnung uebersprungen (Duplikat)", [
                    'legacy_id' => $legacyId,
                    'existing_id' => $existing->id
                ]);
                return 0;
            }
        }

        // Referenzen aufloesen
        // HINWEIS: Bei Rechnungen kann herkunft auf legacy_mid ODER legacy_id verweisen
        // Wir versuchen zuerst legacy_id, dann legacy_mid
        $herkunft = (int) $item->herkunft;
        $gebaeudeId = $this->resolveGebaeudeById($herkunft);
        if (!$gebaeudeId) {
            $gebaeudeId = $this->resolveGebaeude($herkunft);
        }
        
        $rechnungsempfaengerId = $this->resolveAdresse((int) $item->Rechnungsempfaenger);

        // Rechnungsdatum parsen
        $rechnungsdatum = $this->parseDate((string) $item->Data);
        $zahlungsziel = $this->parseDate((string) $item->DataPagamento);

        // Jahr und Laufnummer
        $jahr = $rechnungsdatum ? $rechnungsdatum->year : now()->year;
        $laufnummer = (int) $item->Numero;

        // Status ermitteln
        $status = (int) $item->Bezahlt === 1 ? 'paid' : 'sent';

        // Typ ermitteln (TipoDocumento: TD04 = Gutschrift)
        $typ = 'rechnung';
        $tipoDoc = (string) $item->TipoDocumentoCodex;
        if ($tipoDoc === 'TD04') {
            $typ = 'gutschrift';
        }

        // Fattura-Profil aus mTypKunde mappen
        $mTypKunde = (int) $item->mTypKunde;
        $profilMapping = $this->mapFatturaProfil($mTypKunde);

        // ═══════════════════════════════════════════════════════════════════════
        // BETRÄGE KORREKT BERECHNEN
        // ═══════════════════════════════════════════════════════════════════════
        
        $nettoSumme = (float) $item->RechnungsBetrag ?: 0;
        $mwstBetrag = (float) $item->MwStr ?: 0;
        $bruttoSumme = (float) $item->Betrag ?: 0;
        $ritenutaBetrag = (float) $item->Rit ?: 0;

        // Bei Reverse Charge: MwSt = 0, Brutto = Netto
        if ($profilMapping['reverse_charge']) {
            $mwstBetrag = 0;
            $bruttoSumme = $nettoSumme;
            
            Log::debug("Reverse Charge Rechnung - MwSt auf 0 gesetzt", [
                'legacy_id' => $legacyId,
                'mTypKunde' => $mTypKunde,
                'netto' => $nettoSumme,
                'brutto_korrigiert' => $bruttoSumme,
            ]);
        }

        // Zahlbar-Betrag berechnen basierend auf Rechnungstyp
        $zahlbarBetrag = $this->berechneZahlbarBetrag(
            $nettoSumme,
            $mwstBetrag,
            $bruttoSumme,
            $ritenutaBetrag,
            $profilMapping
        );

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
            
            // Korrigierte Beträge
            'netto_summe'            => $nettoSumme,
            'mwst_betrag'            => $mwstBetrag,
            'brutto_summe'           => $bruttoSumme,
            'ritenuta_betrag'        => $ritenutaBetrag,
            'zahlbar_betrag'         => $zahlbarBetrag,

            // Fattura-Profil Felder aus Mapping
            'fattura_profile_id'     => $profilMapping['fattura_profile_id'],
            'profile_bezeichnung'    => $profilMapping['profile_bezeichnung'],
            'mwst_satz'              => $profilMapping['mwst_satz'],
            'split_payment'          => $profilMapping['split_payment'],
            'reverse_charge'         => $profilMapping['reverse_charge'],
            'ritenuta'               => $profilMapping['ritenuta'],
            'ritenuta_prozent'       => $profilMapping['ritenuta_prozent'],

            'fattura_causale'        => (string) $item->Causale ?: null,
            'cig'                    => (string) $item->{'FatturaPAAbfrage.CIG'} ?: null,
            'auftrag_id'             => (string) $item->OrdineId ?: null,
            'auftrag_datum'          => $this->parseDate((string) $item->OrdineData),
            
            // Snapshot Gebaeude
            'geb_codex'              => (string) $item->Codex ?: null,
            'geb_name'               => (string) $item->Namen1 ?: null,
            
            // Snapshot Rechnungsempfaenger
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

            // Snapshot Postadresse
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
            Log::info('[DRY-RUN] Wuerde Rechnung importieren', [
                'legacy_id' => $legacyId,
                'nummer' => "$jahr/$laufnummer",
                'profil' => $profilMapping['profile_bezeichnung'],
                'mTypKunde' => $mTypKunde,
                'reverse_charge' => $profilMapping['reverse_charge'],
                'zahlbar_betrag' => $zahlbarBetrag,
            ]);
            $this->stats['rechnungen']['imported']++;
            return 1;
        }

        Rechnung::create($data);
        $this->stats['rechnungen']['imported']++;

        return 1;
    }

    /**
     * Berechnet den korrekten Zahlbar-Betrag basierend auf Rechnungstyp
     * 
     * @param float $netto Netto-Summe
     * @param float $mwst MwSt-Betrag
     * @param float $brutto Brutto-Summe
     * @param float $ritenuta Ritenuta-Betrag
     * @param array $profil Fattura-Profil Mapping
     * @return float Zahlbar-Betrag
     */
    protected function berechneZahlbarBetrag(
        float $netto,
        float $mwst,
        float $brutto,
        float $ritenuta,
        array $profil
    ): float {
        // Reverse Charge: Kunde zahlt nur Netto (keine MwSt)
        if ($profil['reverse_charge']) {
            return round($netto - $ritenuta, 2);
        }

        // Split-Payment: Kunde zahlt Netto, MwSt geht direkt an Finanzamt
        if ($profil['split_payment']) {
            return round($netto - $ritenuta, 2);
        }

        // Mit Ritenuta: Brutto minus Ritenuta
        if ($ritenuta > 0) {
            return round($brutto - $ritenuta, 2);
        }

        // Normal: Brutto
        return $brutto;
    }

    protected function buildRechnungenMap(): void
    {
        $this->rechnungenMap = Rechnung::whereNotNull('legacy_id')
            ->pluck('id', 'legacy_id')
            ->toArray();
        
        Log::info("RechnungenMap aufgebaut", ['count' => count($this->rechnungenMap)]);
    }

    // =====================================================================
    // RECHNUNGSPOSITIONEN IMPORT
    // =====================================================================

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

        // Positionen nach Rechnung gruppieren (fuer position-Nummerierung)
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

        // Duplikat-Pruefung
        $existing = RechnungPosition::where('legacy_id', $legacyId)->first();
        if ($existing) {
            if ($this->skipExisting) {
                $this->stats['positionen']['skipped']++;
                Log::debug("Position uebersprungen (Duplikat)", [
                    'legacy_id' => $legacyId,
                    'existing_id' => $existing->id
                ]);
                return 0;
            }
        }

        // Rechnung-Referenz aufloesen (herkunft -> FatturaPAAbfrage.idFatturaPA)
        $rechnungId = $this->resolveRechnung((int) $item->herkunft);

        if (!$rechnungId) {
            $this->logError('positionen', (string)$legacyId, "Rechnung nicht gefunden: herkunft=" . (int)$item->herkunft);
            return 0;
        }

        // ═══════════════════════════════════════════════════════════════════════
        // RECHNUNG LADEN UM REVERSE CHARGE STATUS ZU PRÜFEN
        // ═══════════════════════════════════════════════════════════════════════
        $rechnung = Rechnung::find($rechnungId);
        $istReverseCharge = $rechnung && ($rechnung->reverse_charge || $rechnung->fattura_profile_id == 1);
        $istSplitPayment = $rechnung && $rechnung->split_payment;

        // MwSt-Satz bestimmen
        $mwstSatz = (float) $item->MwStSatz ?: 22;

        // ⭐ Bei Reverse Charge: MwSt = 0!
        if ($istReverseCharge) {
            $mwstSatz = 0;
        }

        $einzelpreis = (float) $item->Einzelpreis ?: 0;
        $anzahl = (float) $item->Anzahl ?: 1;
        $nettoGesamt = round($einzelpreis * $anzahl, 2);
        
        // ⭐ MwSt-Betrag nur berechnen wenn NICHT Reverse Charge
        $mwstBetrag = $istReverseCharge ? 0 : round($nettoGesamt * ($mwstSatz / 100), 2);

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
            Log::info('[DRY-RUN] Wuerde Position importieren', [
                'legacy_id' => $legacyId,
                'rechnung_id' => $rechnungId,
                'position' => $posNr,
                'reverse_charge' => $istReverseCharge,
            ]);
            $this->stats['positionen']['imported']++;
            return 1;
        }

        RechnungPosition::create($data);
        $this->stats['positionen']['imported']++;

        return 1;
    }

    // =====================================================================
    // HELPER METHODS
    // =====================================================================

    /**
     * Laedt XML-Datei
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
     * Adresse-Referenz aufloesen (legacy_mid -> neue ID)
     */
    protected function resolveAdresse(int $legacyMid): ?int
    {
        if ($legacyMid <= 0) {
            return null;
        }
        return $this->adressenMap[$legacyMid] ?? null;
    }

    /**
     * Gebaeude-Referenz aufloesen nach legacy_mid (fuer Rechnungen)
     */
    protected function resolveGebaeude(int $legacyMid): ?int
    {
        if ($legacyMid <= 0) {
            return null;
        }
        return $this->gebaeudeMap[$legacyMid] ?? null;
    }

    /**
     * Gebaeude-Referenz aufloesen nach legacy_id (fuer Artikel!)
     * 
     * WICHTIG: Artikel.herkunft verweist auf Gebaeude.id (legacy_id),
     * NICHT auf Gebaeude.mId (legacy_mid)!
     */
    protected function resolveGebaeudeById(int $legacyId): ?int
    {
        if ($legacyId <= 0) {
            return null;
        }
        return $this->gebaeudeMapById[$legacyId] ?? null;
    }

    /**
     * Rechnung-Referenz aufloesen (legacy_id/idFatturaPA -> neue ID)
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

    // =====================================================================
    // FATTURA-PROFIL MAPPING
    // =====================================================================

    /**
     * Mappt mTypKunde auf Fattura-Profil-Daten
     * 
     * Access-Tabelle TypKunde:
     * id | beschreibung              | Ritenuta | typMwst | typRechnung
     * 1  | Kondominium               | Ja       | 7 (4%)  | 1 (normal)
     * 2  | Oeffentliches Gebaeude    | Nein     | 11 (0%) | 2 (split)
     * 3  | Privat Kunde              | Nein     | 7 (4%)  | 1 (normal)
     * 4  | Firmen Kunde              | Nein     | 11 (0%)| 1 (normal)
     * 5  | Sanierung                 | Nein     | 7 (4%)  | 1 (normal)
     * 7  | 22% MwSt. Split Payment   | Nein     | 9 (22%)| 2 (split)
     * 8  | FirmenKunde 22% MwSt      | Nein     | 8 (22%)| 1 (normal)
     * 9  | Kondominium 22%           | Ja       | 8 (22%)| 1 (normal)
     */
    protected function mapFatturaProfil(int $mTypKunde): array
    {
        $mapping = [
            // mTypKunde 1: Kondominium -> Profil 4 (Kondominium, 10%, ritenuta)
            1 => [
                'fattura_profile_id'  => 4,
                'profile_bezeichnung' => 'Kondominium',
                'mwst_satz'           => 10.00,
                'split_payment'       => false,
                'reverse_charge'      => false,
                'ritenuta'            => true,
                'ritenuta_prozent'    => 4.00,
            ],
            // mTypKunde 2: Oeffentliches Gebaeude -> Profil 3 (Oeffentlich, 22%, split_payment)
            2 => [
                'fattura_profile_id'  => 3,
                'profile_bezeichnung' => 'Oeffentlich',
                'mwst_satz'           => 22.00,
                'split_payment'       => true,
                'reverse_charge'      => false,
                'ritenuta'            => false,
                'ritenuta_prozent'    => 0.00,
            ],
            // mTypKunde 3: Privat Kunde -> Profil 2 (Privatkunde, 10%)
            3 => [
                'fattura_profile_id'  => 2,
                'profile_bezeichnung' => 'Privatkunde',
                'mwst_satz'           => 10.00,
                'split_payment'       => false,
                'reverse_charge'      => false,
                'ritenuta'            => false,
                'ritenuta_prozent'    => 0.00,
            ],
            // mTypKunde 4: Firmen Kunde -> Profil 1 (Firma, Reverse Charge, 0%)
            4 => [
                'fattura_profile_id'  => 1,
                'profile_bezeichnung' => 'Firma, Reverse Charge',
                'mwst_satz'           => 0.00,
                'split_payment'       => false,
                'reverse_charge'      => true,
                'ritenuta'            => false,
                'ritenuta_prozent'    => 0.00,
            ],
            // mTypKunde 5: Sanierung -> Profil 1 (Firma, Reverse Charge, 0%)
            5 => [
                'fattura_profile_id'  => 1,
                'profile_bezeichnung' => 'Firma, Reverse Charge',
                'mwst_satz'           => 0.00,
                'split_payment'       => false,
                'reverse_charge'      => true,
                'ritenuta'            => false,
                'ritenuta_prozent'    => 0.00,
            ],
            // mTypKunde 7: 22% MwSt. Split Payment -> Profil 3 (Oeffentlich, 22%, split_payment)
            7 => [
                'fattura_profile_id'  => 3,
                'profile_bezeichnung' => 'Oeffentlich',
                'mwst_satz'           => 22.00,
                'split_payment'       => true,
                'reverse_charge'      => false,
                'ritenuta'            => false,
                'ritenuta_prozent'    => 0.00,
            ],
            // mTypKunde 8: FirmenKunde 22% MwSt -> Profil 6 (Firma, 22%)
            8 => [
                'fattura_profile_id'  => 6,
                'profile_bezeichnung' => 'Firma, 22%',
                'mwst_satz'           => 22.00,
                'split_payment'       => false,
                'reverse_charge'      => false,
                'ritenuta'            => false,
                'ritenuta_prozent'    => 0.00,
            ],
            // mTypKunde 9: Kondominium 22% -> Profil 5 (Kondominium Gewerblich, 22%, ritenuta)
            9 => [
                'fattura_profile_id'  => 5,
                'profile_bezeichnung' => 'Kondominium Gewerblich',
                'mwst_satz'           => 22.00,
                'split_payment'       => false,
                'reverse_charge'      => false,
                'ritenuta'            => true,
                'ritenuta_prozent'    => 4.00,
            ],
        ];

        // Default-Profil falls nicht gefunden
        $default = [
            'fattura_profile_id'  => null,
            'profile_bezeichnung' => 'Unbekannt',
            'mwst_satz'           => 22.00,
            'split_payment'       => false,
            'reverse_charge'      => false,
            'ritenuta'            => false,
            'ritenuta_prozent'    => 0.00,
        ];

        return $mapping[$mTypKunde] ?? $default;
    }
}
