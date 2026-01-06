<?php
/**
 * ════════════════════════════════════════════════════════════════════════════
 * DATEI: FatturaImportService.php
 * PFAD:  app/Services/FatturaImportService.php
 * ════════════════════════════════════════════════════════════════════════════
 * 
 * Service zum Importieren von FatturaPA XML-Dateien (Eingangsrechnungen).
 * Wird von der Livewire-Komponente aufgerufen.
 */

namespace App\Services;

use App\Models\Lieferant;
use App\Models\Eingangsrechnung;
use App\Models\EingangsrechnungArtikel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ZipArchive;
use Exception;

class FatturaImportService
{
    /**
     * Import-Ergebnis
     */
    protected array $result = [
        'erfolg'     => 0,
        'fehler'     => 0,
        'duplikate'  => 0,
        'meldungen'  => [],
    ];

    /**
     * Importiert eine hochgeladene Datei (XML oder ZIP)
     */
    public function importFromUpload(UploadedFile $file): array
    {
        $this->resetResult();

        $extension = strtolower($file->getClientOriginalExtension());

        if ($extension === 'zip') {
            return $this->importZip($file);
        } elseif ($extension === 'xml') {
            return $this->importSingleXml($file->getPathname(), $file->getClientOriginalName());
        }

        $this->addMeldung('error', "Ungültiges Dateiformat: {$extension}. Nur XML und ZIP erlaubt.");
        return $this->result;
    }

    /**
     * Importiert eine ZIP-Datei mit mehreren XMLs
     */
    public function importZip(UploadedFile $file): array
    {
        $this->resetResult();

        $zip = new ZipArchive();
        $tempPath = $file->getPathname();

        if ($zip->open($tempPath) !== true) {
            $this->addMeldung('error', 'ZIP-Datei konnte nicht geöffnet werden.');
            return $this->result;
        }

        // Temporäres Verzeichnis für Extraktion
        $extractPath = storage_path('app/temp/fattura_import_' . uniqid());
        $zip->extractTo($extractPath);
        $zip->close();

        // Alle XML-Dateien verarbeiten
        $xmlFiles = glob($extractPath . '/*.xml');
        
        foreach ($xmlFiles as $xmlPath) {
            $filename = basename($xmlPath);
            $this->importSingleXml($xmlPath, $filename);
        }

        // Aufräumen
        $this->deleteDirectory($extractPath);

        $this->addMeldung('info', "Import abgeschlossen: {$this->result['erfolg']} importiert, {$this->result['duplikate']} Duplikate, {$this->result['fehler']} Fehler.");

        return $this->result;
    }

    /**
     * Importiert eine einzelne XML-Datei
     */
    public function importSingleXml(string $xmlPath, string $filename): array
    {
        try {
            $xmlContent = file_get_contents($xmlPath);
            
            if (!$xmlContent) {
                throw new Exception("Datei konnte nicht gelesen werden.");
            }

            return $this->parseAndSave($xmlContent, $filename);

        } catch (Exception $e) {
            $this->result['fehler']++;
            $this->addMeldung('error', "{$filename}: {$e->getMessage()}");
            Log::error("FatturaImport Fehler", ['file' => $filename, 'error' => $e->getMessage()]);
            return $this->result;
        }
    }

    /**
     * Parst XML-Inhalt und speichert in Datenbank
     */
    protected function parseAndSave(string $xmlContent, string $filename): array
    {
        // XML parsen
        $xmlContent = $this->cleanXmlContent($xmlContent);
        $xml = @simplexml_load_string($xmlContent);

        if (!$xml) {
            throw new Exception("Ungültiges XML-Format.");
        }

        // Namespaces registrieren
        $xml->registerXPathNamespace('p', 'http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2');

        // Daten extrahieren
        $lieferantDaten = $this->extractLieferant($xml);
        $rechnungDaten = $this->extractRechnung($xml, $filename);
        $artikelDaten = $this->extractArtikel($xml);
        $zahlungsDaten = $this->extractZahlung($xml);

        // Validierung
        if (empty($lieferantDaten['partita_iva'])) {
            throw new Exception("Keine Partita IVA gefunden.");
        }

        if (empty($rechnungDaten['rechnungsnummer'])) {
            throw new Exception("Keine Rechnungsnummer gefunden.");
        }

        // In Datenbank speichern (Transaktion)
        DB::beginTransaction();

        try {
            // 1. Lieferant finden oder erstellen
            $lieferant = Lieferant::findOrCreateByPiva(
                $lieferantDaten['partita_iva'],
                $lieferantDaten
            );

            // IBAN aktualisieren falls vorhanden und Lieferant noch keine hat
            if (!empty($zahlungsDaten['iban']) && empty($lieferant->iban)) {
                $lieferant->update(['iban' => $zahlungsDaten['iban']]);
            }

            // 2. Duplikat-Prüfung
            $existiert = Eingangsrechnung::where('lieferant_id', $lieferant->id)
                ->where('rechnungsnummer', $rechnungDaten['rechnungsnummer'])
                ->exists();

            if ($existiert) {
                DB::rollBack();
                $this->result['duplikate']++;
                $this->addMeldung('warning', "{$filename}: Rechnung {$rechnungDaten['rechnungsnummer']} bereits vorhanden.");
                return $this->result;
            }

            // 3. Rechnung erstellen
            $rechnung = Eingangsrechnung::create([
                'lieferant_id'            => $lieferant->id,
                'dateiname'               => $filename,
                'tipo_documento'          => $rechnungDaten['tipo_documento'] ?? 'TD01',
                'rechnungsnummer'         => $rechnungDaten['rechnungsnummer'],
                'rechnungsdatum'          => $rechnungDaten['rechnungsdatum'],
                'faelligkeitsdatum'       => $zahlungsDaten['faelligkeitsdatum'] ?? null,
                'netto_betrag'            => $rechnungDaten['netto_betrag'],
                'mwst_betrag'             => $rechnungDaten['mwst_betrag'],
                'brutto_betrag'           => $rechnungDaten['brutto_betrag'],
                'modalita_pagamento'      => $zahlungsDaten['modalita_pagamento'] ?? null,
                'status'                  => 'offen',
                'xml_data'                => $this->xmlToArray($xml),
            ]);

            // 4. Artikel erstellen
            foreach ($artikelDaten as $artikel) {
                EingangsrechnungArtikel::create([
                    'eingangsrechnung_id' => $rechnung->id,
                    'zeile'               => $artikel['zeile'],
                    'artikelcode'         => $artikel['artikelcode'] ?? null,
                    'beschreibung'        => $artikel['beschreibung'],
                    'menge'               => $artikel['menge'],
                    'einheit'             => $artikel['einheit'] ?? null,
                    'einzelpreis'         => $artikel['einzelpreis'],
                    'gesamtpreis'         => $artikel['gesamtpreis'],
                    'mwst_satz'           => $artikel['mwst_satz'],
                ]);
            }

            DB::commit();
            $this->result['erfolg']++;
            
            // Erfolgsmeldung mit Typ (Rechnung/Gutschrift)
            $typText = ($rechnungDaten['ist_gutschrift'] ?? false) ? 'Gutschrift' : 'Rechnung';
            $this->addMeldung('success', "{$filename}: {$typText} {$rechnungDaten['rechnungsnummer']} von {$lieferant->name} importiert.");

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return $this->result;
    }

    /**
     * Extrahiert Lieferanten-Daten aus XML (CedentePrestatore)
     */
    protected function extractLieferant(\SimpleXMLElement $xml): array
    {
        // Verschiedene Pfade probieren (mit/ohne Namespace)
        $cedente = $this->findNode($xml, [
            '//CedentePrestatore',
            '//p:CedentePrestatore',
            '//*[local-name()="CedentePrestatore"]',
        ]);

        if (!$cedente) {
            return ['partita_iva' => null];
        }

        // P.IVA extrahieren
        $partitaIva = $this->getNodeValue($cedente, [
            './/IdFiscaleIVA/IdCodice',
            './/*[local-name()="IdFiscaleIVA"]/*[local-name()="IdCodice"]',
        ]);

        // Name extrahieren: Denominazione (Firma) oder Nome+Cognome (Einzelfirma)
        $name = $this->getNodeValue($cedente, ['.//Denominazione', './/*[local-name()="Denominazione"]']);
        
        if (empty($name)) {
            // Einzelfirma: Nome + Cognome
            $nome = $this->getNodeValue($cedente, ['.//Nome', './/*[local-name()="Nome"]']);
            $cognome = $this->getNodeValue($cedente, ['.//Cognome', './/*[local-name()="Cognome"]']);
            
            if ($nome || $cognome) {
                $name = trim("{$cognome} {$nome}");
            }
        }

        // Weitere Daten
        return [
            'partita_iva'    => $partitaIva,
            'codice_fiscale' => $this->getNodeValue($cedente, ['.//CodiceFiscale', './/*[local-name()="CodiceFiscale"]']),
            'name'           => html_entity_decode($name ?: 'Unbekannt'),
            'strasse'        => $this->getNodeValue($cedente, ['.//Sede/Indirizzo', './/*[local-name()="Sede"]/*[local-name()="Indirizzo"]']),
            'plz'            => $this->getNodeValue($cedente, ['.//Sede/CAP', './/*[local-name()="Sede"]/*[local-name()="CAP"]']),
            'ort'            => $this->getNodeValue($cedente, ['.//Sede/Comune', './/*[local-name()="Sede"]/*[local-name()="Comune"]']),
            'provinz'        => $this->getNodeValue($cedente, ['.//Sede/Provincia', './/*[local-name()="Sede"]/*[local-name()="Provincia"]']),
            'land'           => $this->getNodeValue($cedente, ['.//Sede/Nazione', './/*[local-name()="Sede"]/*[local-name()="Nazione"]']) ?? 'IT',
            'telefon'        => $this->getNodeValue($cedente, ['.//Contatti/Telefono', './/*[local-name()="Contatti"]/*[local-name()="Telefono"]']),
            'email'          => $this->getNodeValue($cedente, ['.//Contatti/Email', './/*[local-name()="Contatti"]/*[local-name()="Email"]']),
        ];
    }

    /**
     * Extrahiert Rechnungs-Daten aus XML (DatiGeneraliDocumento)
     */
    protected function extractRechnung(\SimpleXMLElement $xml, string $filename): array
    {
        $datiGenerali = $this->findNode($xml, [
            '//DatiGeneraliDocumento',
            '//p:DatiGeneraliDocumento',
            '//*[local-name()="DatiGeneraliDocumento"]',
        ]);

        // TipoDocumento auslesen (TD01=Rechnung, TD04=Gutschrift, TD05=Belastung, etc.)
        $tipoDocumento = $this->getNodeValue($datiGenerali, ['.//TipoDocumento', './/*[local-name()="TipoDocumento"]']) ?? 'TD01';
        
        // Ist es eine Gutschrift?
        $istGutschrift = in_array($tipoDocumento, ['TD04', 'TD05']); // TD04=Nota di credito, TD05=Nota di debito (selten)

        $brutto = (float) $this->getNodeValue($datiGenerali, ['.//ImportoTotaleDocumento', './/*[local-name()="ImportoTotaleDocumento"]']) ?: 0;

        // Netto und MwSt aus DatiRiepilogo
        $riepilogo = $this->findNode($xml, [
            '//DatiRiepilogo',
            '//p:DatiRiepilogo',
            '//*[local-name()="DatiRiepilogo"]',
        ]);

        $netto = (float) $this->getNodeValue($riepilogo, ['.//ImponibileImporto', './/*[local-name()="ImponibileImporto"]']) ?: 0;
        $mwst = (float) $this->getNodeValue($riepilogo, ['.//Imposta', './/*[local-name()="Imposta"]']) ?: 0;

        // Falls Brutto nicht angegeben, berechnen
        if ($brutto == 0 && ($netto > 0 || $mwst > 0)) {
            $brutto = $netto + $mwst;
        }

        // Bei Gutschrift: Beträge negativ machen
        if ($istGutschrift) {
            $brutto = -abs($brutto);
            $netto = -abs($netto);
            $mwst = -abs($mwst);
        }

        return [
            'tipo_documento'  => $tipoDocumento,
            'ist_gutschrift'  => $istGutschrift,
            'rechnungsnummer' => $this->getNodeValue($datiGenerali, ['.//Numero', './/*[local-name()="Numero"]']),
            'rechnungsdatum'  => $this->getNodeValue($datiGenerali, ['.//Data', './/*[local-name()="Data"]']),
            'brutto_betrag'   => $brutto,
            'netto_betrag'    => $netto,
            'mwst_betrag'     => $mwst,
        ];
    }

    /**
     * Extrahiert Artikel aus XML (DettaglioLinee)
     */
    protected function extractArtikel(\SimpleXMLElement $xml): array
    {
        $artikel = [];

        // Alle DettaglioLinee finden
        $linee = $xml->xpath('//*[local-name()="DettaglioLinee"]');

        foreach ($linee as $linea) {
            $zeile = (int) $this->getNodeValue($linea, ['.//NumeroLinea', './/*[local-name()="NumeroLinea"]']) ?: 1;
            
            // Artikelcode (erster CodiceValore)
            $artikelcode = $this->getNodeValue($linea, ['.//CodiceArticolo/CodiceValore', './/*[local-name()="CodiceArticolo"]/*[local-name()="CodiceValore"]']);

            $artikel[] = [
                'zeile'        => $zeile,
                'artikelcode'  => $artikelcode,
                'beschreibung' => $this->getNodeValue($linea, ['.//Descrizione', './/*[local-name()="Descrizione"]']) ?? '-',
                'menge'        => (float) ($this->getNodeValue($linea, ['.//Quantita', './/*[local-name()="Quantita"]']) ?? 1),
                'einheit'      => $this->getNodeValue($linea, ['.//UnitaMisura', './/*[local-name()="UnitaMisura"]']),
                'einzelpreis'  => (float) ($this->getNodeValue($linea, ['.//PrezzoUnitario', './/*[local-name()="PrezzoUnitario"]']) ?? 0),
                'gesamtpreis'  => (float) ($this->getNodeValue($linea, ['.//PrezzoTotale', './/*[local-name()="PrezzoTotale"]']) ?? 0),
                'mwst_satz'    => (float) ($this->getNodeValue($linea, ['.//AliquotaIVA', './/*[local-name()="AliquotaIVA"]']) ?? 22),
            ];
        }

        return $artikel;
    }

    /**
     * Extrahiert Zahlungsdaten aus XML (DatiPagamento)
     */
    protected function extractZahlung(\SimpleXMLElement $xml): array
    {
        $datiPagamento = $this->findNode($xml, [
            '//DettaglioPagamento',
            '//p:DettaglioPagamento',
            '//*[local-name()="DettaglioPagamento"]',
        ]);

        return [
            'modalita_pagamento' => $this->getNodeValue($datiPagamento, ['.//ModalitaPagamento', './/*[local-name()="ModalitaPagamento"]']),
            'faelligkeitsdatum'  => $this->getNodeValue($datiPagamento, ['.//DataScadenzaPagamento', './/*[local-name()="DataScadenzaPagamento"]']),
            'iban'               => $this->getNodeValue($datiPagamento, ['.//IBAN', './/*[local-name()="IBAN"]']),
        ];
    }

    // ═══════════════════════════════════════════════════════════
    // 🛠️ HILFSMETHODEN
    // ═══════════════════════════════════════════════════════════

    /**
     * Findet einen XML-Node mit verschiedenen XPath-Ausdrücken
     */
    protected function findNode(\SimpleXMLElement $xml, array $xpaths): ?\SimpleXMLElement
    {
        foreach ($xpaths as $xpath) {
            $result = $xml->xpath($xpath);
            if (!empty($result)) {
                return $result[0];
            }
        }
        return null;
    }

    /**
     * Holt einen Wert aus einem XML-Node mit verschiedenen XPath-Ausdrücken
     */
    protected function getNodeValue(?\SimpleXMLElement $node, array $xpaths): ?string
    {
        if (!$node) {
            return null;
        }

        foreach ($xpaths as $xpath) {
            $result = $node->xpath($xpath);
            if (!empty($result)) {
                $value = trim((string) $result[0]);
                return $value !== '' ? $value : null;
            }
        }
        return null;
    }

    /**
     * Bereinigt XML-Content (BOM, ungültige Zeichen)
     */
    protected function cleanXmlContent(string $content): string
    {
        // BOM entfernen
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
        
        // Windows-Zeilenumbrüche normalisieren
        $content = str_replace("\r\n", "\n", $content);
        
        return $content;
    }

    /**
     * Konvertiert SimpleXML zu Array (für JSON-Speicherung)
     */
    protected function xmlToArray(\SimpleXMLElement $xml): array
    {
        $json = json_encode($xml);
        return json_decode($json, true) ?? [];
    }

    /**
     * Löscht ein Verzeichnis rekursiv
     */
    protected function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        
        rmdir($dir);
    }

    /**
     * Fügt eine Meldung hinzu
     */
    protected function addMeldung(string $typ, string $text): void
    {
        $this->result['meldungen'][] = [
            'typ'  => $typ,
            'text' => $text,
            'zeit' => now()->format('H:i:s'),
        ];
    }

    /**
     * Setzt das Ergebnis zurück
     */
    protected function resetResult(): void
    {
        $this->result = [
            'erfolg'    => 0,
            'fehler'    => 0,
            'duplikate' => 0,
            'meldungen' => [],
        ];
    }

    /**
     * Gibt das aktuelle Ergebnis zurück
     */
    public function getResult(): array
    {
        return $this->result;
    }
}
