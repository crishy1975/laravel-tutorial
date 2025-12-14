<?php

namespace App\Services;

use App\Models\BankBuchung;
use App\Models\BankImportLog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BankImportService
{
    protected string $dateiname = '';
    protected array $stats = [
        'buchungen'  => 0,
        'neu'        => 0,
        'duplikate'  => 0,
        'fehler'     => 0,
    ];
    protected array $errors = [];

    /**
     * Importiert CBI-XML Datei
     */
    public function importFromFile(string $filepath): array
    {
        $this->dateiname = basename($filepath);
        
        if (!file_exists($filepath)) {
            throw new \Exception("Datei nicht gefunden: {$filepath}");
        }

        $content = file_get_contents($filepath);
        $fileHash = md5($content);

        // Bereits importiert?
        if (BankImportLog::alreadyImported($fileHash)) {
            return [
                'success'  => false,
                'message'  => 'Diese Datei wurde bereits importiert.',
                'stats'    => $this->stats,
            ];
        }

        return $this->importFromXml($content, $fileHash);
    }

    /**
     * Importiert aus XML-String
     */
    public function importFromXml(string $xmlContent, ?string $fileHash = null): array
    {
        $fileHash = $fileHash ?? md5($xmlContent);

        // XML parsen mit Namespace-Handling
        $xml = $this->parseXml($xmlContent);

        // Statement-Daten extrahieren
        $stmtData = $this->extractStatementData($xml);

        if (empty($stmtData['entries'])) {
            throw new \Exception("Keine Buchungen (Ntry) in der Datei gefunden.");
        }

        DB::beginTransaction();

        try {
            // Buchungen verarbeiten
            foreach ($stmtData['entries'] as $entry) {
                $this->stats['buchungen']++;
                
                try {
                    $this->processEntry($entry, $stmtData['account']);
                } catch (\Exception $e) {
                    $this->stats['fehler']++;
                    $this->errors[] = [
                        'ntry_ref' => $entry['ntry_ref'] ?? '?',
                        'error'    => $e->getMessage(),
                    ];
                }
            }

            // Import-Log erstellen
            $importLog = BankImportLog::create([
                'dateiname'        => $this->dateiname,
                'datei_hash'       => $fileHash,
                'anzahl_buchungen' => $this->stats['buchungen'],
                'anzahl_neu'       => $this->stats['neu'],
                'anzahl_duplikate' => $this->stats['duplikate'],
                'anzahl_matched'   => 0, // Wird nach Auto-Match vom Controller aktualisiert
                'iban'             => $stmtData['account']['iban'],
                'von_datum'        => $stmtData['balances']['von_datum'],
                'bis_datum'        => $stmtData['balances']['bis_datum'],
                'saldo_anfang'     => $stmtData['balances']['saldo_anfang'],
                'saldo_ende'       => $stmtData['balances']['saldo_ende'],
                'meta'             => [
                    'konto_name' => $stmtData['account']['name'],
                    'waehrung'   => $stmtData['account']['waehrung'],
                    'msg_id'     => $stmtData['msg_id'] ?? null,
                ],
            ]);

            DB::commit();

            Log::info('Bank-Import erfolgreich', [
                'datei'     => $this->dateiname,
                'stats'     => $this->stats,
                'import_id' => $importLog->id,
            ]);

            return [
                'success'    => true,
                'message'    => sprintf(
                    '%d Buchungen importiert (%d neu, %d Duplikate)',
                    $this->stats['buchungen'],
                    $this->stats['neu'],
                    $this->stats['duplikate']
                ),
                'stats'      => $this->stats,
                'import_id'  => $importLog->id,
                'konto'      => $stmtData['account'],
                'salden'     => $stmtData['balances'],
                'errors'     => $this->errors,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bank-Import fehlgeschlagen', [
                'datei' => $this->dateiname,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Parst XML und gibt DOMDocument zurueck
     */
    protected function parseXml(string $xmlContent): \DOMDocument
    {
        $dom = new \DOMDocument();
        $dom->preserveWhiteSpace = false;
        
        // Fehler unterdruecken und manuell pruefen
        libxml_use_internal_errors(true);
        $result = $dom->loadXML($xmlContent);
        
        if (!$result) {
            $errors = libxml_get_errors();
            libxml_clear_errors();
            throw new \Exception("XML-Parsing fehlgeschlagen: " . ($errors[0]->message ?? 'Unbekannter Fehler'));
        }

        return $dom;
    }

    /**
     * Extrahiert alle Statement-Daten aus dem XML
     */
    protected function extractStatementData(\DOMDocument $dom): array
    {
        // XPath mit Namespaces
        $xpath = new \DOMXPath($dom);
        
        // Alle Namespaces registrieren
        $xpath->registerNamespace('cbi', 'urn:CBI:xsd:CBIBdyBkToCstmrStmtReq.00.01.02');
        $xpath->registerNamespace('ns3', 'urn:CBI:xsd:CBIBkToCstmrStmtReqLogMsg.00.01.02');
        $xpath->registerNamespace('ns4', 'urn:CBI:xsd:CBIPrdcStmtReqLogMsg.00.01.02');

        // MsgId aus Header
        $msgIdNodes = $xpath->query('//ns4:GrpHdr/ns4:MsgId');
        $msgId = $msgIdNodes->length > 0 ? $msgIdNodes->item(0)->textContent : null;

        // Konto-Info
        $account = $this->extractAccountInfo($xpath);

        // Salden
        $balances = $this->extractBalances($xpath);

        // Buchungen
        $entries = $this->extractEntries($xpath);

        return [
            'msg_id'   => $msgId,
            'account'  => $account,
            'balances' => $balances,
            'entries'  => $entries,
        ];
    }

    /**
     * Extrahiert Konto-Informationen
     */
    protected function extractAccountInfo(\DOMXPath $xpath): array
    {
        $iban = $this->getXPathValue($xpath, '//ns4:Stmt/ns4:Acct/ns4:Id/ns4:IBAN');
        $name = $this->getXPathValue($xpath, '//ns4:Stmt/ns4:Acct/ns4:Nm');
        $waehrung = $this->getXPathValue($xpath, '//ns4:Stmt/ns4:Acct/ns4:Ccy') ?: 'EUR';

        return [
            'iban'     => $iban,
            'name'     => $name,
            'waehrung' => $waehrung,
        ];
    }

    /**
     * Extrahiert Salden
     */
    protected function extractBalances(\DOMXPath $xpath): array
    {
        $saldoAnfang = null;
        $saldoEnde = null;
        $vonDatum = null;
        $bisDatum = null;

        $balNodes = $xpath->query('//ns4:Stmt/ns4:Bal');

        foreach ($balNodes as $balNode) {
            $code = $this->getNodeValue($xpath, 'ns4:Tp/ns4:CdOrPrtry/ns4:Cd', $balNode);
            $amount = (float) $this->getNodeValue($xpath, 'ns4:Amt', $balNode);
            $indicator = $this->getNodeValue($xpath, 'ns4:CdtDbtInd', $balNode);
            $dateStr = $this->getNodeValue($xpath, 'ns4:Dt/ns4:DtTm', $balNode);

            // Vorzeichen korrigieren
            if ($indicator === 'DBIT') {
                $amount = -$amount;
            }

            $date = $dateStr ? Carbon::parse($dateStr) : null;

            if ($code === 'OPBD') { // Opening Balance
                $saldoAnfang = $amount;
                $vonDatum = $date;
            } elseif ($code === 'CLBD') { // Closing Balance
                $saldoEnde = $amount;
                $bisDatum = $date;
            }
        }

        return [
            'saldo_anfang' => $saldoAnfang,
            'saldo_ende'   => $saldoEnde,
            'von_datum'    => $vonDatum,
            'bis_datum'    => $bisDatum,
        ];
    }

    /**
     * Extrahiert alle Buchungen
     */
    protected function extractEntries(\DOMXPath $xpath): array
    {
        $entries = [];
        $entryNodes = $xpath->query('//ns4:Stmt/ns4:Ntry');

        foreach ($entryNodes as $entryNode) {
            $entry = $this->extractSingleEntry($xpath, $entryNode);
            if ($entry) {
                $entries[] = $entry;
            }
        }

        return $entries;
    }

    /**
     * Extrahiert eine einzelne Buchung
     */
    protected function extractSingleEntry(\DOMXPath $xpath, \DOMNode $entryNode): array
    {
        // Basis-Daten
        $ntryRef = $this->getNodeValue($xpath, 'ns4:NtryRef', $entryNode);
        $betrag = (float) $this->getNodeValue($xpath, 'ns4:Amt', $entryNode);
        $waehrung = $this->getNodeAttribute($xpath, 'ns4:Amt', 'Ccy', $entryNode) ?: 'EUR';
        $typ = $this->getNodeValue($xpath, 'ns4:CdtDbtInd', $entryNode) ?: 'CRDT';

        // Datum parsen
        $buchungsdatumStr = $this->getNodeValue($xpath, 'ns4:BookgDt/ns4:DtTm', $entryNode);
        $valutadatumStr = $this->getNodeValue($xpath, 'ns4:ValDt/ns4:DtTm', $entryNode);

        $buchungsdatum = $buchungsdatumStr ? Carbon::parse($buchungsdatumStr)->toDateString() : now()->toDateString();
        $valutadatum = $valutadatumStr ? Carbon::parse($valutadatumStr)->toDateString() : null;

        // Transaktionscode
        $txCode = $this->getNodeValue($xpath, 'ns4:BkTxCd/ns4:Prtry/ns4:Cd', $entryNode);
        $txIssuer = $this->getNodeValue($xpath, 'ns4:BkTxCd/ns4:Prtry/ns4:Issr', $entryNode);

        // Details (TxDtls)
        $gegenkontoName = '';
        $gegenkontoIban = '';
        $verwendungszweck = '';

        // InitgPty/Nm - Auftraggeber Name
        $gegenkontoName = $this->getNodeValue($xpath, 'ns4:NtryDtls/ns4:TxDtls/ns4:RltdPties/ns4:InitgPty/ns4:Nm', $entryNode);
        
        // DbtrAcct IBAN
        $gegenkontoIban = $this->getNodeValue($xpath, 'ns4:NtryDtls/ns4:TxDtls/ns4:RltdPties/ns4:DbtrAcct/ns4:Id/ns4:IBAN', $entryNode);
        
        // Falls kein Debtor, versuche Creditor
        if (!$gegenkontoIban) {
            $gegenkontoIban = $this->getNodeValue($xpath, 'ns4:NtryDtls/ns4:TxDtls/ns4:RltdPties/ns4:CdtrAcct/ns4:Id/ns4:IBAN', $entryNode);
        }

        // Verwendungszweck
        $verwendungszweck = $this->getNodeValue($xpath, 'ns4:NtryDtls/ns4:TxDtls/ns4:AddtlTxInf', $entryNode);

        return [
            'ntry_ref'          => $ntryRef,
            'betrag'            => $betrag,
            'waehrung'          => $waehrung,
            'typ'               => $typ,
            'buchungsdatum'     => $buchungsdatum,
            'valutadatum'       => $valutadatum,
            'tx_code'           => $txCode,
            'tx_issuer'         => $txIssuer,
            'gegenkonto_name'   => $gegenkontoName,
            'gegenkonto_iban'   => $gegenkontoIban,
            'verwendungszweck'  => $verwendungszweck,
        ];
    }

    /**
     * Verarbeitet eine einzelne Buchung
     */
    protected function processEntry(array $entry, array $kontoInfo): void
    {
        // Hash fuer Duplikat-Erkennung
        $hash = BankBuchung::generateHash(
            $kontoInfo['iban'] ?? '',
            $entry['buchungsdatum'],
            (string) $entry['betrag'],
            $entry['typ'],
            $entry['verwendungszweck'] ?? ''
        );

        // Duplikat?
        if (BankBuchung::existsByHash($hash)) {
            $this->stats['duplikate']++;
            return;
        }

        // Buchung erstellen
        BankBuchung::create([
            'import_datei'      => $this->dateiname,
            'import_hash'       => $hash,
            'import_datum'      => now(),
            'iban'              => $kontoInfo['iban'],
            'konto_name'        => $kontoInfo['name'],
            'ntry_ref'          => $entry['ntry_ref'],
            'betrag'            => $entry['betrag'],
            'waehrung'          => $entry['waehrung'],
            'typ'               => $entry['typ'],
            'buchungsdatum'     => $entry['buchungsdatum'],
            'valutadatum'       => $entry['valutadatum'],
            'tx_code'           => $entry['tx_code'],
            'tx_issuer'         => $entry['tx_issuer'],
            'gegenkonto_name'   => $entry['gegenkonto_name'],
            'gegenkonto_iban'   => $entry['gegenkonto_iban'],
            'verwendungszweck'  => $entry['verwendungszweck'],
            'match_status'      => 'unmatched',
        ]);

        $this->stats['neu']++;
        
        // Auto-Matching wird vom Controller nach dem Import durchgeführt
        // (dort wird der BankMatchingService verwendet)
    }

    /**
     * Hilfsmethode: XPath-Wert aus Root holen
     */
    protected function getXPathValue(\DOMXPath $xpath, string $query): ?string
    {
        $nodes = $xpath->query($query);
        if ($nodes->length > 0) {
            return trim($nodes->item(0)->textContent);
        }
        return null;
    }

    /**
     * Hilfsmethode: Wert relativ zu einem Node holen
     */
    protected function getNodeValue(\DOMXPath $xpath, string $query, \DOMNode $contextNode): ?string
    {
        // Relative XPath-Abfrage mit ./ prefix
        $relativeQuery = './' . $query;
        $nodes = $xpath->query($relativeQuery, $contextNode);
        if ($nodes->length > 0) {
            return trim($nodes->item(0)->textContent);
        }
        return null;
    }

    /**
     * Hilfsmethode: Attribut eines Nodes holen
     */
    protected function getNodeAttribute(\DOMXPath $xpath, string $query, string $attribute, \DOMNode $contextNode): ?string
    {
        // Relative XPath-Abfrage mit ./ prefix
        $relativeQuery = './' . $query;
        $nodes = $xpath->query($relativeQuery, $contextNode);
        if ($nodes->length > 0) {
            $node = $nodes->item(0);
            if ($node instanceof \DOMElement && $node->hasAttribute($attribute)) {
                return $node->getAttribute($attribute);
            }
        }
        return null;
    }

    /**
     * Gibt Import-Statistiken zurueck
     */
    public function getStats(): array
    {
        return $this->stats;
    }

    /**
     * Gibt Fehler zurueck
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
