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
        $partitaIva = $this->profil->partita_iva_numeric;
        return "{$partitaIva}_{$progressivo}.xml";
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

        if ($this->profil->codice_fiscale && 
            $this->profil->codice_fiscale !== $this->profil->partita_iva_numeric) {
            $this->addElement('CodiceFiscale', $datiAnagrafici, $this->profil->codice_fiscale);
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

    protected function buildCessionarioCommittente(DOMElement $parent): void
    {
        $cessionario = $this->createElement('CessionarioCommittente', $parent);

        $datiAnagrafici = $this->createElement('DatiAnagrafici', $cessionario);

        if ($this->rechnung->re_mwst_nummer) {
            $idFiscale = $this->createElement('IdFiscaleIVA', $datiAnagrafici);
            
            $land = strtoupper(substr($this->rechnung->re_mwst_nummer, 0, 2));
            if (!preg_match('/^[A-Z]{2}$/', $land)) {
                $land = $this->rechnung->re_land ?: 'IT';
            }
            
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

    protected function buildDatiGeneraliDocumento(DOMElement $parent): void
    {
        $datiDoc = $this->createElement('DatiGeneraliDocumento', $parent);

        $tipoDocumento = $this->getTipoDocumento();
        $this->addElement('TipoDocumento', $datiDoc, $tipoDocumento);
        $this->addElement('Divisa', $datiDoc, 'EUR');
        $this->addElement('Data', $datiDoc, $this->formatDate($this->rechnung->rechnungsdatum));
        $this->addElement('Numero', $datiDoc, $this->rechnung->rechnungsnummer);

        if ($this->rechnung->ritenuta && $this->rechnung->ritenuta_betrag > 0) {
            $this->buildDatiRitenuta($datiDoc);
        }

        // ⭐ Causale: Leistungsbeschreibung (Deutsch + Italienisch) + Gebäude-Adresse
        $causale = $this->buildCausale();
        if ($causale) {
            $this->addElement('Causale', $datiDoc, $causale);
        }

        if ($this->rechnung->leistungsdaten) {
            $this->addElement('Art', $datiDoc, $this->rechnung->leistungsdaten);
        }

        $this->addElement('ImportoTotaleDocumento', $datiDoc, $this->formatAmount($this->rechnung->brutto_summe));
    }

    protected function getTipoDocumento(): string
    {
        if ($this->rechnung->typ_rechnung === 'gutschrift') {
            return 'TD04';
        }

        return 'TD01';
    }

    protected function buildDatiRitenuta(DOMElement $parent): void
    {
        $ritenuta = $this->createElement('DatiRitenuta', $parent);

        $this->addElement('TipoRitenuta', $ritenuta, 'RT02');
        $this->addElement('ImportoRitenuta', $ritenuta, $this->formatAmount($this->rechnung->ritenuta_betrag));
        $this->addElement('AliquotaRitenuta', $ritenuta, $this->formatAmount($this->rechnung->ritenuta_prozent));
        $this->addElement('CausalePagamento', $ritenuta, 'Z');
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
     * Diese Methode kann auch vom Rechnung Model aufgerufen werden!
     */
    public function generateCausale(): ?string
    {
        $teile = [];

        // 1. Leistungsart (Deutsch / Italienisch)
        $teile[] = 'Reinigungsarbeiten / Servizi di pulizia';

        // 2. Leistungszeitraum (falls vorhanden)
        if ($this->rechnung->leistungsdaten) {
            $teile[] = sprintf(
                'Zeitraum: %s / Periodo: %s',
                $this->rechnung->leistungsdaten,
                $this->rechnung->leistungsdaten
            );
        }

        // 3. Gebäude-Info (Name + Adresse)
        if ($this->rechnung->geb_name && $this->rechnung->geb_adresse) {
            $teile[] = sprintf(
                'Objekt: %s (%s) / Oggetto: %s (%s)',
                $this->rechnung->geb_name,
                $this->rechnung->geb_adresse,
                $this->rechnung->geb_name,
                $this->rechnung->geb_adresse
            );
        } elseif ($this->rechnung->geb_adresse) {
            $teile[] = sprintf(
                'Objekt: %s / Oggetto: %s',
                $this->rechnung->geb_adresse,
                $this->rechnung->geb_adresse
            );
        } elseif ($this->rechnung->geb_name) {
            $teile[] = sprintf(
                'Objekt: %s / Oggetto: %s',
                $this->rechnung->geb_name,
                $this->rechnung->geb_name
            );
        }

        // 4. Kunden-Bemerkung (falls vorhanden und Platz ist)
        if ($this->rechnung->bemerkung_kunde) {
            $teile[] = $this->rechnung->bemerkung_kunde;
        }

        // Zusammenfügen mit " - " und auf 200 Zeichen kürzen
        $causale = implode(' - ', $teile);
        
        return substr($causale, 0, 200) ?: null;
    }

    protected function buildDatiOrdineAcquisto(DOMElement $parent): void
    {
        if (!$this->rechnung->cup && !$this->rechnung->cig && !$this->rechnung->codice_commessa) {
            return;
        }

        $datiOrdine = $this->createElement('DatiOrdineAcquisto', $parent);

        $this->addElement('RiferimentoNumeroLinea', $datiOrdine, '1');

        if ($this->rechnung->cup) {
            $this->addElement('CodiceCUP', $datiOrdine, strtoupper($this->rechnung->cup));
        }

        if ($this->rechnung->cig) {
            $this->addElement('CodiceCIG', $datiOrdine, strtoupper($this->rechnung->cig));
        }

        if ($this->rechnung->codice_commessa) {
            $this->addElement('CodiceCommessaConvenzione', $datiOrdine, $this->rechnung->codice_commessa);
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

        if ($position->mwst_satz == 0) {
            $this->addElement('Natura', $linea, 'N1');
        }
    }

    protected function buildDatiRiepilogo(DOMElement $parent): void
    {
        $grouped = $this->rechnung->positionen->groupBy('mwst_satz');

        foreach ($grouped as $satz => $positionen) {
            $riepilogo = $this->createElement('DatiRiepilogo', $parent);

            $nettoSumme = $positionen->sum('netto_gesamt');
            $mwstBetrag = $positionen->sum('mwst_betrag');

            $this->addElement('AliquotaIVA', $riepilogo, $this->formatAmount($satz));

            if ($satz == 0) {
                $this->addElement('Natura', $riepilogo, 'N1');
            }

            $this->addElement('ImponibileImporto', $riepilogo, $this->formatAmount($nettoSumme));
            $this->addElement('Imposta', $riepilogo, $this->formatAmount($mwstBetrag));

            $esigibilita = $this->rechnung->split_payment ? 'S' : 'I';
            $this->addElement('EsigibilitaIVA', $riepilogo, $esigibilita);
        }
    }

    protected function buildDatiPagamento(DOMElement $parent): void
    {
        $datiPagamento = $this->createElement('DatiPagamento', $parent);

        $condizioni = $this->config['defaults']['condizioni_pagamento'] ?? 'TP02';
        $this->addElement('CondizioniPagamento', $datiPagamento, $condizioni);

        $this->buildDettaglioPagamento($datiPagamento);
    }

    protected function buildDettaglioPagamento(DOMElement $parent): void
    {
        $dettaglio = $this->createElement('DettaglioPagamento', $parent);

        $modalita = $this->config['defaults']['modalita_pagamento'] ?? 'MP05';
        $this->addElement('ModalitaPagamento', $dettaglio, $modalita);

        if ($this->rechnung->zahlungsziel) {
            $this->addElement('DataScadenzaPagamento', $dettaglio, $this->formatDate($this->rechnung->zahlungsziel));
        }

        $importo = $this->rechnung->ritenuta 
            ? $this->rechnung->zahlbar_betrag 
            : $this->rechnung->brutto_summe;

        $this->addElement('ImportoPagamento', $dettaglio, $this->formatAmount($importo));

        if ($modalita === 'MP05' && $this->profil->iban) {
            $iban = str_replace(' ', '', strtoupper($this->profil->iban));
            $this->addElement('IBAN', $dettaglio, $iban);
        }

        if ($modalita === 'MP05' && $this->profil->bic) {
            $this->addElement('BIC', $dettaglio, strtoupper($this->profil->bic));
        }

        if ($this->profil->bank_name) {
            $this->addElement('IstitutoFinanziario', $dettaglio, $this->profil->bank_name);
        }
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
        $value = htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');

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