<?php

namespace App\Services;

use App\Models\Rechnung;
use App\Models\Unternehmensprofil;
use App\Models\FatturaXmlLog;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use DOMDocument;
use DOMElement;
use Exception;

/**
 * FatturaPA XML Generator Service
 * 
 * Generiert elektronische Rechnungen im italienischen FatturaPA-Format (v1.8).
 * 
 * ⭐ KORREKTUREN V.2025:
 * - CausalePagamento: "Z" → "ZO" (V.2025 kompatibel)
 * - Element-Reihenfolge: Causale NACH ImportoTotaleDocumento
 * - Element-Reihenfolge: IstitutoFinanziario → IBAN → BIC
 * - "Art" Element entfernt (ungültig in FatturaPA)
 * - faelligkeitsdatum (Accessor für zahlungsziel)
 * - IdPaese: convertToIsoCode() für alle Länder-Felder
 * - DatiOrdineAcquisto: IdDocumento ist PFLICHT + korrekte Reihenfolge
 * - auftrag_id statt auftrag_nummer
 */
class FatturaXmlGenerator
{
    protected DOMDocument $dom;
    protected DOMElement $root;
    protected Rechnung $rechnung;
    protected Unternehmensprofil $profil;
    protected array $config;

    public function __construct()
    {
        $this->config = config('fattura');
    }

    // ═══════════════════════════════════════════════════════════
    // 🎯 MAIN ENTRY POINT
    // ═══════════════════════════════════════════════════════════

    /**
     * Generiert FatturaPA XML für eine Rechnung.
     */
    public function generate(Rechnung $rechnung): FatturaXmlLog
    {
        $this->rechnung = $rechnung;

        try {
            $this->profil = Unternehmensprofil::aktivOderFehler();
        } catch (Exception $e) {
            throw new Exception('Unternehmensprofil nicht konfiguriert: ' . $e->getMessage());
        }

        $this->validate();
        $progressivo = $this->generateProgressivoInvio();

        $log = FatturaXmlLog::create([
            'rechnung_id'           => $rechnung->id,
            'progressivo_invio'     => $progressivo,
            'formato_trasmissione'  => $this->getFormatoTrasmissione(),
            'codice_destinatario'   => $this->getCodiceDestinatario(),
            'pec_destinatario'      => $rechnung->re_pec,
            'status'                => FatturaXmlLog::STATUS_PENDING,
        ]);

        try {
            $this->initializeDom();
            $this->buildFatturaElettronicaHeader($progressivo);
            $this->buildFatturaElettronicaBody();

            $xmlString = $this->formatXml();
            $filename = $this->generateFilename($progressivo);
            $path = $this->saveXml($xmlString, $filename);

            $log->markAsGenerated($path, $filename);

            if ($this->config['debug']['log_xml_content'] ?? false) {
                $log->update(['xml_content' => $xmlString]);
            }

            if ($this->config['xml']['validate_xsd'] ?? false) {
                $this->validateAgainstXsd($xmlString, $log);
            } else {
                $log->markAsValid();
            }

            Log::info('FatturaPA XML erfolgreich generiert', [
                'rechnung_id' => $rechnung->id,
                'progressivo' => $progressivo,
                'filename' => $filename,
            ]);

            return $log;

        } catch (Exception $e) {
            $log->markAsError($e->getMessage(), $e->getTraceAsString());
            Log::error('Fehler bei XML-Generierung', [
                'rechnung_id' => $rechnung->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Preview: Generiert Vorschau ohne zu speichern.
     */
    public function preview(Rechnung $rechnung): string
    {
        $this->rechnung = $rechnung;
        $this->profil = Unternehmensprofil::aktivOderFehler();
        $this->validate();

        $progressivo = 'PREVIEW_' . time();

        $this->initializeDom();
        $this->buildFatturaElettronicaHeader($progressivo);
        $this->buildFatturaElettronicaBody();

        return $this->formatXml();
    }

    // ═══════════════════════════════════════════════════════════
    // 🔧 VALIDATION
    // ═══════════════════════════════════════════════════════════

    protected function validate(): void
    {
        $errors = [];
        $warnings = [];

        if (!$this->profil->istFatturapaKonfiguriert()) {
            $fehlend = $this->profil->fehlendeFelderFatturaPA();
            $errors[] = 'Unternehmensprofil unvollständig: ' . implode(', ', $fehlend);
        }

        if (!$this->rechnung->rechnungsempfaenger_id) {
            $errors[] = 'Rechnungsempfänger fehlt';
        }

        // ⭐ ENTFERNT: Codice und PEC sind BEIDE optional!
        // Ohne beide → 0000000 (Manuelle Abholung im SDI-Portal)

        if ($this->rechnung->positionen->isEmpty()) {
            $errors[] = 'Rechnung hat keine Positionen';
        }

        if (!$this->rechnung->rechnungsdatum) {
            $errors[] = 'Rechnungsdatum fehlt';
        }

        // ⭐ NEU: Warnung für PA-Rechnungen ohne CIG/CUP
        if ($this->getFormatoTrasmissione() === 'FPA12') {
            if (!$this->rechnung->cig && !$this->rechnung->cup) {
                $warnings[] = 'PA-Rechnung ohne CIG/CUP: Empfohlen für öffentliche Aufträge';
                Log::warning('FatturaPA: PA-Rechnung ohne CIG/CUP', [
                    'rechnung_id' => $this->rechnung->id,
                    'rechnungsnummer' => $this->rechnung->rechnungsnummer,
                ]);
            }
        }

        if (!empty($errors)) {
            throw new Exception('Validierung fehlgeschlagen: ' . implode('; ', $errors));
        }
    }

    // ═══════════════════════════════════════════════════════════
    // 📨 IDENTIFIERS
    // ═══════════════════════════════════════════════════════════

    protected function generateProgressivoInvio(): string
    {
        $prefix = $this->config['trasmissione']['progressivo_prefix'] ?? 'IT';
        $jahr = $this->rechnung->jahr;

        $maxLaufnummer = FatturaXmlLog::where('progressivo_invio', 'LIKE', "{$prefix}{$jahr}%")
            ->selectRaw('MAX(CAST(SUBSTRING(progressivo_invio, ' . (strlen($prefix) + 5) . ') AS UNSIGNED)) as max_nummer')
            ->value('max_nummer') ?? 0;

        $neueNummer = $maxLaufnummer + 1;

        // ⭐ FIX: Max 10 Zeichen! Format: IT2025001 (Prefix 2 + Jahr 4 + Nummer 4 = 10)
        return sprintf('%s%d%04d', $prefix, $jahr, $neueNummer);
    }

    protected function getFormatoTrasmissione(): string
    {
        $codice = $this->rechnung->re_codice_univoco;

        // ⭐ KORRIGIERT: 6 Zeichen = PA (FPA12), 7 Zeichen = Privat (FPR12)
        if ($codice && strlen($codice) === 6) {
            return 'FPA12';  // Pubblica Amministrazione
        }

        return 'FPR12';  // Privati (Standard)
    }

    protected function getCodiceDestinatario(): string
    {
        $codice = $this->rechnung->re_codice_univoco;

        // ⭐ KORRIGIERT:
        // - Leer oder NULL → 0000000 (PEC-Versand)
        // - 6 Zeichen = PA → Code verwenden
        // - 7 Zeichen = Privat → Code verwenden
        // - Andere Länge → 0000000 (PEC-Versand)
        
        // Explizit prüfen: leer, null, oder nur Leerzeichen
        if (!$codice || trim($codice) === '') {
            return '0000000';  // PEC-Versand
        }

        $codice = trim($codice);
        $len = strlen($codice);
        
        // 6 oder 7 Zeichen → verwenden
        if ($len === 6 || $len === 7) {
            return strtoupper($codice);
        }

        // Andere Länge → PEC-Versand
        return '0000000';
    }

    protected function generateFilename(string $progressivo): string
    {
        // Format: {Rechnungsnummer}_{Datum}.xml
        $rechnungsnummer = $this->rechnung->rechnungsnummer;
        $datum = $this->formatDate($this->rechnung->rechnungsdatum);
        
        // Sonderzeichen entfernen (z.B. "/" → "-")
        $rechnungsnummer = str_replace(['/', '\\', ' '], '-', $rechnungsnummer);
        
        return "{$rechnungsnummer}_{$datum}.xml";
    }

    // ═══════════════════════════════════════════════════════════
    // 🏗️ DOM
    // ═══════════════════════════════════════════════════════════

    protected function initializeDom(): void
    {
        $this->dom = new DOMDocument('1.0', 'UTF-8');
        $this->dom->formatOutput = $this->config['xml']['pretty_print'] ?? true;
        $this->dom->preserveWhiteSpace = false;

        $namespace = $this->config['xml']['namespace'];
        $prefix = $this->config['xml']['namespace_prefix'] ?? 'p';
        
        // ⭐ KORRIGIERT: versione = FormatoTrasmissione (FPA12 oder FPR12)
        $version = $this->getFormatoTrasmissione();

        $this->root = $this->dom->createElementNS($namespace, "{$prefix}:FatturaElettronica");
        $this->root->setAttribute('versione', $version);
        $this->dom->appendChild($this->root);
    }

    // ═══════════════════════════════════════════════════════════
    // 📄 HEADER
    // ═══════════════════════════════════════════════════════════

    protected function buildFatturaElettronicaHeader(string $progressivo): void
    {
        $header = $this->createElement('FatturaElettronicaHeader');
        
        $this->buildDatiTrasmissione($header, $progressivo);
        $this->buildCedentePrestatore($header);
        $this->buildCessionarioCommittente($header);
        
        $this->root->appendChild($header);
    }

    protected function buildDatiTrasmissione(DOMElement $parent, string $progressivo): void
    {
        $dati = $this->createElement('DatiTrasmissione', $parent);

        // ⭐ IdPaese aus Partita IVA extrahieren (erste 2 Zeichen)
        $paese = $this->getLandFromPartitaIva($this->profil->partita_iva);
        
        $idTrasmittente = $this->createElement('IdTrasmittente', $dati);
        $this->addElement('IdPaese', $idTrasmittente, $paese);
        $this->addElement('IdCodice', $idTrasmittente, $this->profil->partita_iva_numeric);

        $this->addElement('ProgressivoInvio', $dati, $progressivo);
        $this->addElement('FormatoTrasmissione', $dati, $this->getFormatoTrasmissione());
        $this->addElement('CodiceDestinatario', $dati, $this->getCodiceDestinatario());

        if ($this->rechnung->re_pec && $this->getCodiceDestinatario() === '0000000') {
            $this->addElement('PECDestinatario', $dati, $this->rechnung->re_pec);
        }
    }

    protected function buildCedentePrestatore(DOMElement $parent): void
    {
        $cedente = $this->createElement('CedentePrestatore', $parent);

        $datiAnagrafici = $this->createElement('DatiAnagrafici', $cedente);

        // ⭐ IdPaese aus Partita IVA extrahieren (erste 2 Zeichen)
        $paese = $this->getLandFromPartitaIva($this->profil->partita_iva);
        
        $idFiscale = $this->createElement('IdFiscaleIVA', $datiAnagrafici);
        $this->addElement('IdPaese', $idFiscale, $paese);
        $this->addElement('IdCodice', $idFiscale, $this->profil->partita_iva_numeric);

        // ⭐ FIX: CodiceFiscale IMMER angeben (D.P.R. 605-1973)
        // Falls kein separater CodiceFiscale vorhanden → Partita IVA verwenden
        $codiceFiscale = $this->profil->codice_fiscale ?: $this->profil->partita_iva_numeric;
        if ($codiceFiscale) {
            $this->addElement('CodiceFiscale', $datiAnagrafici, $codiceFiscale);
        }

        $anagrafica = $this->createElement('Anagrafica', $datiAnagrafici);
        $this->addElement('Denominazione', $anagrafica, $this->profil->ragione_sociale);

        $regimeFiscale = $this->profil->regime_fiscale ?: 'RF01';
        $this->addElement('RegimeFiscale', $datiAnagrafici, $regimeFiscale);

        $sede = $this->createElement('Sede', $cedente);
        $this->addElement('Indirizzo', $sede, $this->profil->strasse ?: 'Via non specificata');
        
        if ($this->profil->hausnummer) {
            $this->addElement('NumeroCivico', $sede, $this->profil->hausnummer);
        }
        
        // ⭐ FIX 5+6: CAP muss GENAU 5 Ziffern sein!
        $cap = preg_replace('/[^0-9]/', '', $this->profil->postleitzahl ?: '00000'); // Nur Ziffern
        $cap = str_pad(substr($cap, 0, 5), 5, '0', STR_PAD_LEFT); // Genau 5 Zeichen
        $this->addElement('CAP', $sede, $cap);
        
        $this->addElement('Comune', $sede, $this->profil->ort ?: 'Non specificato');
        
        // ⭐ bundesland = Provinz (z.B. "BZ" für Bozen)
        $provinz = strtoupper(substr($this->profil->bundesland ?: 'XX', 0, 2));
        $this->addElement('Provincia', $sede, $provinz);
        
        // ⭐ FIX: Nazione muss ISO-Code sein (IT), nicht Länder-Name (Italien)
        $nazione = $this->convertToIsoCode($this->profil->land ?: 'IT');
        $this->addElement('Nazione', $sede, $nazione);

        if ($this->profil->rea_ufficio && $this->profil->rea_numero) {
            $rea = $this->createElement('IscrizioneREA', $cedente);
            $this->addElement('Ufficio', $rea, strtoupper($this->profil->rea_ufficio));
            $this->addElement('NumeroREA', $rea, $this->profil->rea_numero);
            
            if ($this->profil->capitale_sociale) {
                $this->addElement('CapitaleSociale', $rea, number_format($this->profil->capitale_sociale, 2, '.', ''));
            }
            
            // ⭐ FIX 3: Stato Liquidazione ist PFLICHTFELD in REA!
            // LN = Liquidazione Normale, LS = Liquidazione Straordinaria
            $statoLiquidazione = $this->profil->stato_liquidazione ?? 'LN';
            $this->addElement('StatoLiquidazione', $rea, $statoLiquidazione);
        }

        if ($this->profil->telefon || $this->profil->email) {
            $contatti = $this->createElement('Contatti', $cedente);
            
            if ($this->profil->telefon) {
                // ⭐ FIX 4: Telefon max 12 Zeichen (5-12)
                $telefon = preg_replace('/[^0-9+]/', '', $this->profil->telefon); // Nur Ziffern + Plus
                $telefon = substr($telefon, 0, 12); // Max 12 Zeichen
                $this->addElement('Telefono', $contatti, $telefon);
            }
            
            if ($this->profil->email) {
                $this->addElement('Email', $contatti, $this->profil->email);
            }
        }
    }

    /**
     * ⭐ KORRIGIERT: IdPaese wird jetzt IMMER mit convertToIsoCode() konvertiert!
     * 
     * Vorher: "Italia" wurde nicht zu "IT" konvertiert
     * Nachher: Alle Länder-Namen werden korrekt zu ISO-Codes
     */
    protected function buildCessionarioCommittente(DOMElement $parent): void
    {
        $cessionario = $this->createElement('CessionarioCommittente', $parent);

        $datiAnagrafici = $this->createElement('DatiAnagrafici', $cessionario);

        if ($this->rechnung->re_mwst_nummer) {
            $idFiscale = $this->createElement('IdFiscaleIVA', $datiAnagrafici);
            
            // Versuche Land aus MwSt-Nummer zu extrahieren (erste 2 Zeichen)
            $land = strtoupper(substr($this->rechnung->re_mwst_nummer, 0, 2));
            
            // ⭐ FIX: Wenn keine 2 Großbuchstaben → convertToIsoCode verwenden!
            if (!preg_match('/^[A-Z]{2}$/', $land)) {
                $land = $this->convertToIsoCode($this->rechnung->re_land ?: 'IT');
            }
            
            // Nummer ohne Land-Präfix
            $nummer = preg_replace('/^[A-Z]{2}/', '', $this->rechnung->re_mwst_nummer);
            
            $this->addElement('IdPaese', $idFiscale, $land);
            $this->addElement('IdCodice', $idFiscale, $nummer);
        }

        if ($this->rechnung->re_steuernummer) {
            $this->addElement('CodiceFiscale', $datiAnagrafici, $this->rechnung->re_steuernummer);
        }

        $anagrafica = $this->createElement('Anagrafica', $datiAnagrafici);
        $this->addElement('Denominazione', $anagrafica, $this->rechnung->re_name);

        $sede = $this->createElement('Sede', $cessionario);
        $this->addElement('Indirizzo', $sede, $this->rechnung->re_strasse ?: 'Via non specificata');
        
        if ($this->rechnung->re_hausnummer) {
            $this->addElement('NumeroCivico', $sede, $this->rechnung->re_hausnummer);
        }
        
        // ⭐ FIX 5+6: CAP muss GENAU 5 Ziffern sein!
        $cap = preg_replace('/[^0-9]/', '', $this->rechnung->re_plz ?: '00000'); // Nur Ziffern
        $cap = str_pad(substr($cap, 0, 5), 5, '0', STR_PAD_LEFT); // Genau 5 Zeichen
        $this->addElement('CAP', $sede, $cap);
        
        $this->addElement('Comune', $sede, $this->rechnung->re_wohnort ?: 'Non specificato');
        
        if ($this->rechnung->re_provinz) {
            $provinz = strtoupper(substr($this->rechnung->re_provinz, 0, 2));
            $this->addElement('Provincia', $sede, $provinz);
        }
        
        // ⭐ FIX: Nazione muss ISO-Code sein (IT), nicht Länder-Name (Italien)
        $nazione = $this->convertToIsoCode($this->rechnung->re_land ?: 'IT');
        $this->addElement('Nazione', $sede, $nazione);
    }

    // ═══════════════════════════════════════════════════════════
    // 📦 BODY
    // ═══════════════════════════════════════════════════════════

    protected function buildFatturaElettronicaBody(): void
    {
        $body = $this->createElement('FatturaElettronicaBody');

        $this->buildDatiGenerali($body);
        $this->buildDatiBeniServizi($body);
        $this->buildDatiPagamento($body);

        $this->root->appendChild($body);
    }

    protected function buildDatiGenerali(DOMElement $parent): void
    {
        $datiGenerali = $this->createElement('DatiGenerali', $parent);

        $this->buildDatiGeneraliDocumento($datiGenerali);
        $this->buildDatiOrdineAcquisto($datiGenerali);
    }

    /**
     * ⭐ KORRIGIERT: Element-Reihenfolge laut XSD Schema
     * 
     * Korrekte Reihenfolge:
     * 1. TipoDocumento
     * 2. Divisa
     * 3. Data
     * 4. Numero
     * 5. DatiRitenuta (optional)
     * 6. DatiBollo (optional)
     * 7. DatiCassaPrevidenziale (optional)
     * 8. ScontoMaggiorazione (optional)
     * 9. ImportoTotaleDocumento
     * 10. Arrotondamento (optional)
     * 11. Causale (optional, kann mehrfach vorkommen) ← MUSS NACH ImportoTotaleDocumento!
     * 12. Art73 (optional)
     */
    protected function buildDatiGeneraliDocumento(DOMElement $parent): void
    {
        $datiDoc = $this->createElement('DatiGeneraliDocumento', $parent);

        // 1. TipoDocumento
        $tipoDocumento = $this->getTipoDocumento();
        $this->addElement('TipoDocumento', $datiDoc, $tipoDocumento);
        
        // 2. Divisa
        $this->addElement('Divisa', $datiDoc, 'EUR');
        
        // 3. Data
        $this->addElement('Data', $datiDoc, $this->formatDate($this->rechnung->rechnungsdatum));
        
        // 4. Numero
        $this->addElement('Numero', $datiDoc, $this->rechnung->rechnungsnummer);

        // 5. DatiRitenuta (optional)
        if ($this->rechnung->ritenuta && $this->rechnung->ritenuta_betrag > 0) {
            $this->buildDatiRitenuta($datiDoc);
        }

        // 6-8. DatiBollo, DatiCassaPrevidenziale, ScontoMaggiorazione (nicht implementiert)

        // 9. ImportoTotaleDocumento - MUSS VOR Causale!
        // ⭐ FIX: Muss mit DatiRiepilogo übereinstimmen (ImponibileImporto + Imposta)
        $importoTotale = $this->calculateImportoTotaleDocumento();
        $this->addElement('ImportoTotaleDocumento', $datiDoc, $this->formatAmount($importoTotale));

        // 10. Arrotondamento (nicht implementiert)

        // 11. Causale - MUSS NACH ImportoTotaleDocumento!
        $causale = $this->buildCausale();
        if ($causale) {
            $this->addElement('Causale', $datiDoc, $causale);
        }

        // 12. Art73 (nicht implementiert)
        
        // ❌ ENTFERNT: "Art" ist kein gültiges FatturaPA-Element!
        // Das alte "Art"-Element wurde komplett entfernt.
    }

    protected function getTipoDocumento(): string
    {
        if ($this->rechnung->typ_rechnung === 'gutschrift') {
            return 'TD04';
        }

        return 'TD01';
    }

    /**
     * ⭐ KORRIGIERT: CausalePagamento "Z" → "ZO" (V.2025 kompatibel)
     * 
     * Der Code "Z" ist ab FatturaPA Version 2025 ungültig und wurde
     * durch "ZO" (Titolo diverso dai precedenti) ersetzt.
     */
    protected function buildDatiRitenuta(DOMElement $parent): void
    {
        $ritenuta = $this->createElement('DatiRitenuta', $parent);

        $this->addElement('TipoRitenuta', $ritenuta, 'RT02');
        $this->addElement('ImportoRitenuta', $ritenuta, $this->formatAmount($this->rechnung->ritenuta_betrag));
        $this->addElement('AliquotaRitenuta', $ritenuta, $this->formatAmount($this->rechnung->ritenuta_prozent));
        
        // ⭐ FIX V.2025: "Z" ist ungültig → "ZO" verwenden!
        $this->addElement('CausalePagamento', $ritenuta, 'ZO');
    }

    /**
     * ⭐ NEU: Erstellt Causale (2.1.1.11) - Zweisprachige Leistungsbeschreibung
     * 
     * Priorität:
     * 1. Manuelle Überschreibung ($rechnung->fattura_causale)
     * 2. Automatische Generierung aus Rechnung-Daten
     * 
     * Format (automatisch):
     * Reinigungsarbeiten / Servizi di pulizia
     * Leistungszeitraum: Januar 2025 / Periodo: gennaio 2025
     * Objekt: Bürogebäude (Via Roma 123, 39100 Bolzano) / Oggetto: Bürogebäude (Via Roma 123, 39100 Bolzano)
     * 
     * Max 200 Zeichen pro Causale (kann mehrfach vorkommen)
     */
    protected function buildCausale(): ?string
    {
        // ⭐ 1. PRIORITÄT: Manuelle Causale (falls vom Benutzer bearbeitet)
        if ($this->rechnung->fattura_causale) {
            return substr(trim($this->rechnung->fattura_causale), 0, 200);
        }

        // ⭐ 2. Automatische Generierung aus Rechnung
        return $this->generateCausale();
    }

    /**
     * ⭐ Generiert automatische Causale aus Rechnung-Daten
     * 
     * Format (ULTRA-KOMPAKT):
     * Zeitraum/Periodo: Jahr/anno 2025 - Objekt/Oggetto: Name, Adresse
     * 
     * Beispiel:
     * Zeitraum/Periodo: Jahr/anno 2025 - Objekt/Oggetto: Cond. Romana, Fuchserstr. 2, 39055 Laives
     */
    public function generateCausale(): ?string
    {
        $teile = [];

        // 1. Leistungszeitraum (falls vorhanden)
        if ($this->rechnung->leistungsdaten) {
            $teile[] = sprintf(
                'Zeitraum/Periodo: %s',
                $this->rechnung->leistungsdaten
            );
        }

        // 2. Gebäude-Info (kompakt: Objekt/Oggetto mit Komma)
        if ($this->rechnung->geb_name && $this->rechnung->geb_adresse) {
            // Name + Adresse mit Komma getrennt
            $teile[] = sprintf(
                'Objekt/Oggetto: %s, %s',
                $this->rechnung->geb_name,
                $this->rechnung->geb_adresse
            );
        } elseif ($this->rechnung->geb_adresse) {
            // Nur Adresse
            $teile[] = sprintf(
                'Objekt/Oggetto: %s',
                $this->rechnung->geb_adresse
            );
        } elseif ($this->rechnung->geb_name) {
            // Nur Name
            $teile[] = sprintf(
                'Objekt/Oggetto: %s',
                $this->rechnung->geb_name
            );
        }

        // Zusammenfügen mit Separator " - "
        $causale = implode(' - ', $teile);

        // Max 200 Zeichen (SDI-Limit)
        return substr($causale, 0, 200) ?: null;
    }

    /**
     * ⭐ KORRIGIERT: DatiOrdineAcquisto mit korrekter Element-Reihenfolge
     * 
     * Korrekte Reihenfolge laut XSD Schema:
     * 1. RiferimentoNumeroLinea (optional, kann mehrfach vorkommen)
     * 2. IdDocumento (PFLICHTFELD wenn DatiOrdineAcquisto existiert!)
     * 3. Data (optional)
     * 4. NumItem (optional)
     * 5. CodiceCommessaConvenzione (optional)
     * 6. CodiceCUP (optional) ← MUSS NACH IdDocumento!
     * 7. CodiceCIG (optional) ← MUSS NACH CodiceCUP!
     */
    protected function buildDatiOrdineAcquisto(DOMElement $parent): void
    {
        // Nur erstellen wenn mindestens CUP, CIG oder Codice Commessa vorhanden
        if (!$this->rechnung->cup && !$this->rechnung->cig && !$this->rechnung->codice_commessa) {
            return;
        }

        // ⭐ Warnung: Bei PA-Rechnungen sollte CIG oder CUP angegeben werden
        if ($this->getFormatoTrasmissione() === 'FPA12' && !$this->rechnung->cig && !$this->rechnung->cup) {
            Log::warning('FatturaPA: DatiOrdineAcquisto bei PA ohne CIG/CUP', [
                'rechnung_id' => $this->rechnung->id,
                'hinweis' => 'Bei PA-Rechnungen wird CIG oder CUP empfohlen',
            ]);
        }

        $datiOrdine = $this->createElement('DatiOrdineAcquisto', $parent);

        // 1. RiferimentoNumeroLinea (optional)
        $this->addElement('RiferimentoNumeroLinea', $datiOrdine, '1');

        // 2. IdDocumento (PFLICHTFELD!)
        // ⭐ FIX: IdDocumento ist PFLICHT wenn DatiOrdineAcquisto existiert!
        // Verwende auftrag_id oder Rechnungsnummer als Fallback
        $idDocumento = $this->rechnung->auftrag_id 
            ?? $this->rechnung->rechnungsnummer 
            ?? 'N/A';
        $this->addElement('IdDocumento', $datiOrdine, $idDocumento);

        // 3. Data (optional) - Auftragsdatum falls vorhanden
        if ($this->rechnung->auftrag_datum) {
            $this->addElement('Data', $datiOrdine, $this->formatDate($this->rechnung->auftrag_datum));
        }

        // 4. NumItem (optional, nicht implementiert)

        // 5. CodiceCommessaConvenzione (optional)
        if ($this->rechnung->codice_commessa) {
            $this->addElement('CodiceCommessaConvenzione', $datiOrdine, $this->rechnung->codice_commessa);
        }

        // 6. CodiceCUP (optional) - MUSS NACH IdDocumento und CodiceCommessaConvenzione!
        if ($this->rechnung->cup) {
            $this->addElement('CodiceCUP', $datiOrdine, strtoupper($this->rechnung->cup));
        }

        // 7. CodiceCIG (optional) - MUSS NACH CodiceCUP!
        if ($this->rechnung->cig) {
            $this->addElement('CodiceCIG', $datiOrdine, strtoupper($this->rechnung->cig));
        }
    }

    protected function buildDatiBeniServizi(DOMElement $parent): void
    {
        $datiBeni = $this->createElement('DatiBeniServizi', $parent);

        foreach ($this->rechnung->positionen as $position) {
            $this->buildDettaglioLinee($datiBeni, $position);
        }

        $this->buildDatiRiepilogo($datiBeni);
    }

    protected function buildDettaglioLinee(DOMElement $parent, $position): void
    {
        $linea = $this->createElement('DettaglioLinee', $parent);

        $this->addElement('NumeroLinea', $linea, (string) $position->position);
        $this->addElement('Descrizione', $linea, $position->beschreibung);
        $this->addElement('Quantita', $linea, $this->formatAmount($position->anzahl));

        $einheit = $position->einheit ?: 'Stk';
        $this->addElement('UnitaMisura', $linea, $einheit);

        $this->addElement('PrezzoUnitario', $linea, $this->formatAmount($position->einzelpreis));
        $this->addElement('PrezzoTotale', $linea, $this->formatAmount($position->netto_gesamt));
        $this->addElement('AliquotaIVA', $linea, $this->formatAmount($position->mwst_satz));

        // ⭐ FIX: Natura-Code korrekt setzen basierend auf Reverse Charge
        if ($position->mwst_satz == 0) {
            $natura = $this->getNaturaCode();
            $this->addElement('Natura', $linea, $natura);
        }
    }

    protected function buildDatiRiepilogo(DOMElement $parent): void
    {
        $grouped = $this->rechnung->positionen->groupBy('mwst_satz');

        foreach ($grouped as $satz => $positionen) {
            $riepilogo = $this->createElement('DatiRiepilogo', $parent);

            $nettoSumme = $positionen->sum('netto_gesamt');
            
            // ⭐ FIX: Imposta direkt aus ImponibileImporto berechnen!
            // Formel laut FatturaPA: (AliquotaIVA * ImponibileImporto) / 100
            // NICHT: Summe der gerundeten Einzelbeträge (führt zu Rundungsdifferenzen)
            $mwstBetrag = round((float) $nettoSumme * (float) $satz / 100, 2);

            // ⭐ Korrekte Reihenfolge laut FatturaPA XSD (2.2.2):
            // 1. AliquotaIVA
            $this->addElement('AliquotaIVA', $riepilogo, $this->formatAmount($satz));

            // 2. Natura (bei MwSt = 0)
            if ($satz == 0) {
                $natura = $this->getNaturaCode();
                $this->addElement('Natura', $riepilogo, $natura);
            }

            // 3. SpeseAccessorie (nicht implementiert)
            // 4. Arrotondamento (nicht implementiert)

            // 5. ImponibileImporto
            $this->addElement('ImponibileImporto', $riepilogo, $this->formatAmount($nettoSumme));

            // 6. Imposta
            $this->addElement('Imposta', $riepilogo, $this->formatAmount($mwstBetrag));

            // 7. EsigibilitaIVA - NUR bei normaler MwSt, NICHT bei Reverse Charge
            if (!($satz == 0 && $this->rechnung->reverse_charge)) {
                $esigibilita = $this->rechnung->split_payment ? 'S' : 'I';
                $this->addElement('EsigibilitaIVA', $riepilogo, $esigibilita);
            }

            // 8. RiferimentoNormativo - MUSS am Ende stehen!
            if ($satz == 0 && $this->rechnung->reverse_charge) {
                $this->addElement('RiferimentoNormativo', $riepilogo, 'Non imponibile art. 17, c.6, lett. a-ter) DPR 633/72');
            } elseif ($this->rechnung->split_payment) {
                $this->addElement('RiferimentoNormativo', $riepilogo, 'Splitpayment ex art. 17-ter DPR 633/72');
            }
        }
    }

    protected function buildDatiPagamento(DOMElement $parent): void
    {
        $datiPagamento = $this->createElement('DatiPagamento', $parent);

        $condizioni = $this->config['defaults']['condizioni_pagamento'] ?? 'TP02';
        $this->addElement('CondizioniPagamento', $datiPagamento, $condizioni);

        $this->buildDettaglioPagamento($datiPagamento);
    }

    /**
     * ⭐ KORRIGIERT: Element-Reihenfolge laut XSD Schema
     * 
     * Korrekte Reihenfolge:
     * 1. Beneficiario (optional)
     * 2. ModalitaPagamento
     * 3. DataRiferimentoTerminiPagamento (optional)
     * 4. GiorniTerminiPagamento (optional)
     * 5. DataScadenzaPagamento (optional)
     * 6. ImportoPagamento
     * 7-11. CodUfficioPostale, etc. (optional)
     * 12. IstitutoFinanziario (optional) ← MUSS VOR IBAN!
     * 13. IBAN (optional)
     * 14-15. ABI, CAB (optional)
     * 16. BIC (optional) ← MUSS NACH IBAN!
     * 17-21. Sconti, etc. (optional)
     */
    protected function buildDettaglioPagamento(DOMElement $parent): void
    {
        $dettaglio = $this->createElement('DettaglioPagamento', $parent);

        // 1. Beneficiario (optional, nicht implementiert)

        // 2. ModalitaPagamento
        $modalita = $this->config['defaults']['modalita_pagamento'] ?? 'MP05';
        $this->addElement('ModalitaPagamento', $dettaglio, $modalita);

        // 3-4. DataRiferimentoTerminiPagamento, GiorniTerminiPagamento (nicht implementiert)

        // 5. DataScadenzaPagamento (optional)
        // ⭐ FIX: faelligkeitsdatum ist ein Accessor der zahlungsziel zurückgibt
        if ($this->rechnung->faelligkeitsdatum) {
            $this->addElement('DataScadenzaPagamento', $dettaglio, $this->formatDate($this->rechnung->faelligkeitsdatum));
        }

        // 6. ImportoPagamento
        // ⭐ FIX: Bei Split Payment zahlt der Kunde nur NETTO (MwSt geht direkt an Fiskus)
        $importo = $this->calculateImportoPagamento();
        $this->addElement('ImportoPagamento', $dettaglio, $this->formatAmount($importo));

        // 7-11. CodUfficioPostale, Quietanzante-Felder (nicht implementiert)

        // 12. IstitutoFinanziario - MUSS VOR IBAN!
        if ($this->profil->bank_name) {
            $this->addElement('IstitutoFinanziario', $dettaglio, $this->profil->bank_name);
        }

        // 13. IBAN - MUSS NACH IstitutoFinanziario!
        if ($modalita === 'MP05' && $this->profil->iban) {
            $iban = str_replace(' ', '', strtoupper($this->profil->iban));
            $this->addElement('IBAN', $dettaglio, $iban);
        }

        // 14-15. ABI, CAB (nicht implementiert)

        // 16. BIC - MUSS NACH IBAN!
        if ($modalita === 'MP05' && $this->profil->bic) {
            $this->addElement('BIC', $dettaglio, strtoupper($this->profil->bic));
        }

        // 17-21. Sconti, Penalità, CodicePagamento (nicht implementiert)
    }

    /**
     * ⭐ NEU: Berechnet den korrekten ImportoPagamento
     * 
     * Logik:
     * - Split Payment (EsigibilitaIVA = "S"): Kunde zahlt nur NETTO (MwSt geht direkt an Fiskus)
     * - Ritenuta: Abzug vom Zahlbetrag
     * - Standard: Brutto-Summe (konsistent mit ImportoTotaleDocumento)
     * 
     * Kombinationen:
     * - Split Payment + Ritenuta: netto_summe - ritenuta_betrag
     * - Split Payment ohne Ritenuta: netto_summe
     * - Ritenuta ohne Split Payment: brutto - ritenuta
     * - Keine Sonderfälle: brutto_summe
     */
    protected function calculateImportoPagamento(): float
    {
        // Verwende berechnete Werte für Konsistenz
        $brutto = $this->calculateImportoTotaleDocumento();
        $netto = (float) $this->rechnung->positionen->sum('netto_gesamt');
        $ritenuta = (float) ($this->rechnung->ritenuta_betrag ?? 0);
        
        // Split Payment: Kunde zahlt nur Netto
        if ($this->rechnung->split_payment) {
            // Mit Ritenuta: Netto - Ritenuta
            if ($this->rechnung->ritenuta && $ritenuta > 0) {
                return round($netto - $ritenuta, 2);
            }
            // Ohne Ritenuta: nur Netto
            return round($netto, 2);
        }
        
        // Kein Split Payment, aber Ritenuta
        if ($this->rechnung->ritenuta && $ritenuta > 0) {
            return round($brutto - $ritenuta, 2);
        }
        
        // Standard: Brutto (bereits gerundet)
        return $brutto;
    }

    /**
     * ⭐ NEU: Berechnet ImportoTotaleDocumento konsistent mit DatiRiepilogo
     * 
     * Formel: sum(ImponibileImporto) + sum(Imposta)
     * 
     * WICHTIG: Die Imposta muss genauso berechnet werden wie in buildDatiRiepilogo(),
     * nämlich als round(netto * satz / 100, 2) pro MwSt-Gruppe.
     * NICHT als Summe der bereits gerundeten Einzelbeträge!
     */
    protected function calculateImportoTotaleDocumento(): float
    {
        $grouped = $this->rechnung->positionen->groupBy('mwst_satz');
        
        $totalImponibile = 0.0;
        $totalImposta = 0.0;
        
        foreach ($grouped as $satz => $positionen) {
            $nettoSumme = (float) $positionen->sum('netto_gesamt');
            
            // Imposta EXAKT wie in buildDatiRiepilogo() berechnen!
            $imposta = round($nettoSumme * (float) $satz / 100, 2);
            
            $totalImponibile += $nettoSumme;
            $totalImposta += $imposta;
        }
        
        return round($totalImponibile + $totalImposta, 2);
    }

    // ═══════════════════════════════════════════════════════════
    // 🔧 HELPER
    // ═══════════════════════════════════════════════════════════

    protected function createElement(string $name, ?DOMElement $parent = null): DOMElement
    {
        $element = $this->dom->createElement($name);
        
        if ($parent) {
            $parent->appendChild($element);
        }
        
        return $element;
    }

    protected function addElement(string $name, DOMElement $parent, $value): DOMElement
    {
        $value = $this->escapeXmlValue($value);
        $element = $this->dom->createElement($name, $value);
        $parent->appendChild($element);
        return $element;
    }

    protected function escapeXmlValue($value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $value = (string) $value;
        
        // ⭐ FIX: Sonderzeichen normalisieren (FatturaPA erlaubt nur Latin-1 Zeichen)
        $value = $this->normalizeSpecialCharacters($value);
        
        $value = htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        return $value;
    }

    /**
     * ⭐ NEU: Normalisiert Sonderzeichen für FatturaPA-Kompatibilität
     * 
     * FatturaPA akzeptiert nur Latin-1 Zeichen (ISO-8859-1).
     * Diese Methode:
     * 1. Ersetzt bekannte typografische Zeichen durch ASCII-Äquivalente
     * 2. Entfernt alle verbleibenden ungültigen Zeichen
     */
    protected function normalizeSpecialCharacters(string $value): string
    {
        // 1. Bekannte Ersetzungen für typografische Zeichen (hex-codiert für PHP-Kompatibilität)
        $replacements = [
            // Dashes
            "\xE2\x80\x93" => '-',  // En-Dash (U+2013)
            "\xE2\x80\x94" => '-',  // Em-Dash (U+2014)
            "\xE2\x80\x90" => '-',  // Hyphen (U+2010)
            "\xE2\x80\x91" => '-',  // Non-Breaking Hyphen (U+2011)
            "\xE2\x88\x92" => '-',  // Minus Sign (U+2212)
            "\xE2\x80\x92" => '-',  // Figure Dash (U+2012)
            
            // Quotes
            "\xE2\x80\x98" => "'",  // Left Single Quote (U+2018)
            "\xE2\x80\x99" => "'",  // Right Single Quote (U+2019)
            "\xE2\x80\x9A" => "'",  // Single Low-9 Quote (U+201A)
            "\xE2\x80\x9B" => "'",  // Single High-Reversed-9 Quote (U+201B)
            "\xE2\x80\x9C" => '"',  // Left Double Quote (U+201C)
            "\xE2\x80\x9D" => '"',  // Right Double Quote (U+201D)
            "\xE2\x80\x9E" => '"',  // Double Low-9 Quote (U+201E)
            "\xE2\x80\x9F" => '"',  // Double High-Reversed-9 Quote (U+201F)
            "\xC2\xAB" => '"',      // Left Guillemet («)
            "\xC2\xBB" => '"',      // Right Guillemet (»)
            "\xE2\x80\xB9" => "'",  // Single Left Guillemet (‹)
            "\xE2\x80\xBA" => "'",  // Single Right Guillemet (›)
            
            // Spaces
            "\xC2\xA0" => ' ',      // Non-Breaking Space (U+00A0)
            "\xE2\x80\x82" => ' ',  // En Space (U+2002)
            "\xE2\x80\x83" => ' ',  // Em Space (U+2003)
            "\xE2\x80\x84" => ' ',  // Three-Per-Em Space (U+2004)
            "\xE2\x80\x85" => ' ',  // Four-Per-Em Space (U+2005)
            "\xE2\x80\x86" => ' ',  // Six-Per-Em Space (U+2006)
            "\xE2\x80\x87" => ' ',  // Figure Space (U+2007)
            "\xE2\x80\x88" => ' ',  // Punctuation Space (U+2008)
            "\xE2\x80\x89" => ' ',  // Thin Space (U+2009)
            "\xE2\x80\x8A" => ' ',  // Hair Space (U+200A)
            "\xE2\x80\x8B" => '',   // Zero Width Space (U+200B)
            
            // Dots & Bullets
            "\xE2\x80\xA6" => '...', // Ellipsis (U+2026)
            "\xE2\x80\xA2" => '-',   // Bullet (U+2022)
            "\xE2\x97\xA6" => '-',   // White Bullet (U+25E6)
            "\xC2\xB7" => '.',       // Middle Dot (U+00B7)
            "\xE2\x80\xA4" => '.',   // One Dot Leader (U+2024)
            
            // Arrows
            "\xE2\x86\x92" => '->',  // Right Arrow (→)
            "\xE2\x86\x90" => '<-',  // Left Arrow (←)
            "\xE2\x86\x94" => '<->', // Left Right Arrow (↔)
            "\xE2\x87\x92" => '=>',  // Double Right Arrow (⇒)
            "\xE2\x87\x90" => '<=',  // Double Left Arrow (⇐)
            
            // Math & Symbols
            "\xC3\x97" => 'x',       // Multiplication Sign (×)
            "\xC3\xB7" => '/',       // Division Sign (÷)
            "\xC2\xB1" => '+/-',     // Plus-Minus (±)
            "\xE2\x89\xA4" => '<=',  // Less Than or Equal (≤)
            "\xE2\x89\xA5" => '>=',  // Greater Than or Equal (≥)
            "\xE2\x89\xA0" => '!=',  // Not Equal (≠)
            "\xE2\x89\x88" => '~',   // Almost Equal (≈)
            "\xE2\x88\x9E" => 'inf', // Infinity (∞)
            
            // Currency
            "\xC2\xA3" => 'GBP',     // Pound (£)
            "\xC2\xA5" => 'JPY',     // Yen (¥)
            "\xE2\x82\xB9" => 'INR', // Indian Rupee (₹)
            "\xE2\x82\xBD" => 'RUB', // Russian Ruble (₽)
            
            // Trademark & Legal
            "\xE2\x84\xA2" => '(TM)', // Trademark (™)
            "\xC2\xAE" => '(R)',      // Registered (®)
            "\xC2\xA9" => '(C)',      // Copyright (©)
            
            // Fractions
            "\xC2\xBD" => '1/2',     // ½
            "\xC2\xBC" => '1/4',     // ¼
            "\xC2\xBE" => '3/4',     // ¾
            "\xE2\x85\x93" => '1/3', // ⅓
            "\xE2\x85\x94" => '2/3', // ⅔
        ];

        $value = str_replace(array_keys($replacements), array_values($replacements), $value);

        // 2. Versuche Transliteration für andere Unicode-Zeichen (z.B. ñ → n)
        if (function_exists('iconv')) {
            $transliterated = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
            if ($transliterated !== false) {
                $value = $transliterated;
            }
        }

        // 3. Entferne alle verbleibenden Nicht-Latin-1 Zeichen
        // Latin-1 (ISO-8859-1) erlaubt: 0x00-0xFF
        // Wir behalten nur druckbare ASCII + deutsche Umlaute + übliche Sonderzeichen
        $value = preg_replace('/[^\x20-\x7E\xA0-\xFF]/u', '', $value);

        return $value;
    }

    protected function formatAmount($amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }

    protected function formatDate($date): string
    {
        if ($date instanceof Carbon) {
            return $date->format('Y-m-d');
        }

        if (is_string($date)) {
            return Carbon::parse($date)->format('Y-m-d');
        }

        return Carbon::now()->format('Y-m-d');
    }

    protected function formatXml(): string
    {
        return $this->dom->saveXML();
    }

    /**
     * ⭐ NEU: Extrahiert Land-Code aus Partita IVA / MwSt-Nummer
     * 
     * Beispiele:
     * - "IT01699660211" → "IT"
     * - "DE123456789" → "DE"
     * - "01699660211" → "IT" (Fallback wenn keine Länder-Kennung)
     * 
     * @param string|null $partitaIva
     * @return string 2-Buchstaben Länder-Code
     */
    protected function getLandFromPartitaIva(?string $partitaIva): string
    {
        if (!$partitaIva) {
            return 'IT'; // Fallback
        }

        // Erste 2 Zeichen prüfen
        $land = strtoupper(substr($partitaIva, 0, 2));

        // Sind es 2 Buchstaben? → Land-Code
        if (preg_match('/^[A-Z]{2}$/', $land)) {
            return $land;
        }

        // Sonst Fallback auf IT (Standard für Italien)
        return $this->profil->land ?? 'IT';
    }

    /**
     * ⭐ NEU: Ermittelt den korrekten Natura-Code für MwSt-freie Positionen
     * 
     * FatturaPA Natura-Codes bei AliquotaIVA = 0:
     * - N1     = Escluse ex art. 15 DPR 633/72 (ausgeschlossen)
     * - N2.1   = Non soggette - artt. 7-7septies DPR 633/72
     * - N2.2   = Non soggette - altri casi  
     * - N3.x   = Non imponibili (Export, etc.)
     * - N4     = Esenti (befreit)
     * - N5     = Regime del margine
     * - N6.x   = Inversione contabile / Reverse Charge (Art. 17):
     *   - N6.1 = Cessione di rottami (Altmetall)
     *   - N6.2 = Cessione di oro/argento puro
     *   - N6.3 = Subappalto settore edile
     *   - N6.4 = Cessione di fabbricati
     *   - N6.5 = Cessione di telefoni cellulari
     *   - N6.6 = Cessione di prodotti elettronici
     *   - N6.7 = Prestazioni comparto edile
     *   - N6.8 = Operazioni settore energetico
     *   - N6.9 = Altri casi (Standard für Art. 17 Reverse Charge!)
     * - N7     = IVA assolta in altro stato UE
     * 
     * @return string Natura-Code (z.B. "N6.9" für Reverse Charge)
     */
    protected function getNaturaCode(): string
    {
        // Priorität 1: Explizit gesetzter Natura-Code in der Rechnung
        if (!empty($this->rechnung->natura_esenzione)) {
            return $this->rechnung->natura_esenzione;
        }

        // Priorität 2: Reverse Charge Flag → N6.9 (Art. 17 - altri casi)
        if ($this->rechnung->reverse_charge) {
            return 'N6.7';
        }

        // Priorität 3: Split Payment ohne Reverse Charge ist normalerweise kein 0% MwSt-Fall
        // aber falls doch → N1 als Fallback

        // Default: N1 (Escluse ex art. 15)
        return 'N1';
    }

    /**
     * ⭐ NEU: Konvertiert Länder-Namen zu ISO-Code
     * 
     * Beispiele:
     * - "Italien" → "IT"
     * - "Deutschland" → "DE"
     * - "IT" → "IT" (bleibt gleich wenn schon Code)
     * 
     * @param string|null $land Länder-Name oder ISO-Code
     * @return string ISO-Code (2 Buchstaben)
     */
    protected function convertToIsoCode(?string $land): string
    {
        if (!$land) {
            return 'IT';
        }

        $land = trim($land);

        // Ist es bereits ein 2-Buchstaben Code? → Zurückgeben
        if (strlen($land) === 2 && preg_match('/^[A-Z]{2}$/i', $land)) {
            return strtoupper($land);
        }

        // Mapping: Länder-Name → ISO-Code
        $mapping = [
            // Deutsch
            'Italien'       => 'IT',
            'Deutschland'   => 'DE',
            'Österreich'    => 'AT',
            'Schweiz'       => 'CH',
            'Frankreich'    => 'FR',
            'Spanien'       => 'ES',
            'Niederlande'   => 'NL',
            'Belgien'       => 'BE',
            
            // Italienisch
            'Italia'        => 'IT',
            'Germania'      => 'DE',
            'Austria'       => 'AT',
            'Svizzera'      => 'CH',
            'Francia'       => 'FR',
            'Spagna'        => 'ES',
            'Paesi Bassi'   => 'NL',
            'Belgio'        => 'BE',
            
            // Englisch
            'Italy'         => 'IT',
            'Germany'       => 'DE',
            'Austria'       => 'AT',
            'Switzerland'   => 'CH',
            'France'        => 'FR',
            'Spain'         => 'ES',
            'Netherlands'   => 'NL',
            'Belgium'       => 'BE',
        ];

        // Case-insensitive Suche
        foreach ($mapping as $name => $code) {
            if (strcasecmp($name, $land) === 0) {
                return $code;
            }
        }

        // Fallback: Wenn nicht gefunden, versuche erste 2 Buchstaben
        $first2 = strtoupper(substr($land, 0, 2));
        if (preg_match('/^[A-Z]{2}$/', $first2)) {
            return $first2;
        }

        // Letzter Fallback
        return 'IT';
    }

    protected function saveXml(string $content, string $filename): string
    {
        $directory = $this->config['storage']['xml_path'] ?? 'fattura/xml';
        $path = $directory . '/' . $filename;

        Storage::put($path, $content);

        return $path;
    }

    protected function validateAgainstXsd(string $xmlString, FatturaXmlLog $log): void
    {
        $xsdPath = $this->config['xml']['xsd_path'] ?? null;

        if (!$xsdPath || !file_exists($xsdPath)) {
            Log::warning('XSD-Validierung übersprungen: Datei nicht gefunden');
            $log->markAsValid();
            return;
        }

        $validationDom = new DOMDocument();
        $validationDom->loadXML($xmlString);

        libxml_use_internal_errors(true);
        $valid = $validationDom->schemaValidate($xsdPath);

        if (!$valid) {
            $errors = libxml_get_errors();
            $errorMessages = array_map(function ($error) {
                return trim($error->message);
            }, $errors);

            libxml_clear_errors();
            $log->setValidationErrors($errorMessages);

            Log::warning('XML-Validierung fehlgeschlagen', [
                'rechnung_id' => $this->rechnung->id,
                'errors' => $errorMessages,
            ]);
        } else {
            $log->markAsValid();
        }
    }
}