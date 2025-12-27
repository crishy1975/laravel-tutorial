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

    // ⭐ Namespace-Präfix für XPath (wird automatisch erkannt)
    protected string $stmtNs = 'ns4';

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

        // ⭐ Format erkennen (ns4 oder ns5)
        $this->detectFormat($xml);

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
                'anzahl_matched'   => 0,
                'iban'             => $stmtData['account']['iban'],
                'von_datum'        => $stmtData['balances']['von_datum'],
                'bis_datum'        => $stmtData['balances']['bis_datum'],
                'saldo_anfang'     => $stmtData['balances']['saldo_anfang'],
                'saldo_ende'       => $stmtData['balances']['saldo_ende'],
                'meta'             => [
                    'konto_name' => $stmtData['account']['name'],
                    'waehrung'   => $stmtData['account']['waehrung'],
                    'msg_id'     => $stmtData['msg_id'] ?? null,
                    'format'     => $this->stmtNs, // ⭐ Format merken
                ],
            ]);

            DB::commit();

            Log::info('Bank-Import erfolgreich', [
                'datei'     => $this->dateiname,
                'stats'     => $this->stats,
                'import_id' => $importLog->id,
                'format'    => $this->stmtNs,
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
     * Parst XML und gibt DOMDocument zurück
     */
    protected function parseXml(string $xmlContent): \DOMDocument
    {
        $dom = new \DOMDocument();
        $dom->preserveWhiteSpace = false;
        
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
     * ⭐ Erkennt das XML-Format (ns4 oder ns5)
     */
    protected function detectFormat(\DOMDocument $dom): void
    {
        $xpath = new \DOMXPath($dom);
        
        // Alle Namespaces registrieren
        $xpath->registerNamespace('cbi', 'urn:CBI:xsd:CBIBdyBkToCstmrStmtReq.00.01.02');
        $xpath->registerNamespace('ns3', 'urn:CBI:xsd:CBIBkToCstmrStmtReqLogMsg.00.01.02');
        $xpath->registerNamespace('ns4', 'urn:CBI:xsd:CBIPrdcStmtReqLogMsg.00.01.02');
        $xpath->registerNamespace('ns5', 'urn:CBI:xsd:CBIPrdcStmtReqLogMsg.00.01.02');

        // Prüfen ob ns5:Stmt existiert (Sparkasse-Format)
        $ns5Nodes = $xpath->query('//ns5:Stmt');
        if ($ns5Nodes->length > 0) {
            $this->stmtNs = 'ns5';
            Log::debug('Bank-Import: Format ns5 erkannt (Sparkasse)');
            return;
        }

        // Prüfen ob ns4:Stmt existiert (Raika-Format)
        $ns4Nodes = $xpath->query('//ns4:Stmt');
        if ($ns4Nodes->length > 0) {
            $this->stmtNs = 'ns4';
            Log::debug('Bank-Import: Format ns4 erkannt (Raika)');
            return;
        }

        // Fallback
        $this->stmtNs = 'ns4';
        Log::warning('Bank-Import: Kein bekanntes Format erkannt, verwende ns4');
    }

    /**
     * Extrahiert alle Statement-Daten aus dem XML
     */
    protected function extractStatementData(\DOMDocument $dom): array
    {
        $xpath = new \DOMXPath($dom);
        
        // Alle Namespaces registrieren
        $xpath->registerNamespace('cbi', 'urn:CBI:xsd:CBIBdyBkToCstmrStmtReq.00.01.02');
        $xpath->registerNamespace('ns3', 'urn:CBI:xsd:CBIBkToCstmrStmtReqLogMsg.00.01.02');
        $xpath->registerNamespace('ns4', 'urn:CBI:xsd:CBIPrdcStmtReqLogMsg.00.01.02');
        $xpath->registerNamespace('ns5', 'urn:CBI:xsd:CBIPrdcStmtReqLogMsg.00.01.02');

        $ns = $this->stmtNs;

        // MsgId aus Header
        $msgIdNodes = $xpath->query("//{$ns}:GrpHdr/{$ns}:MsgId");
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
        $ns = $this->stmtNs;

        $iban = $this->getXPathValue($xpath, "//{$ns}:Stmt/{$ns}:Acct/{$ns}:Id/{$ns}:IBAN");
        
        // ⭐ Name: Verschiedene Pfade je nach Format
        $name = $this->getXPathValue($xpath, "//{$ns}:Stmt/{$ns}:Acct/{$ns}:Nm");
        if (!$name) {
            $name = $this->getXPathValue($xpath, "//{$ns}:Stmt/{$ns}:Acct/{$ns}:Ownr/{$ns}:Nm");
        }
        
        $waehrung = $this->getXPathValue($xpath, "//{$ns}:Stmt/{$ns}:Acct/{$ns}:Ccy") ?: 'EUR';

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
        $ns = $this->stmtNs;

        $saldoAnfang = null;
        $saldoEnde = null;
        $vonDatum = null;
        $bisDatum = null;

        $balNodes = $xpath->query("//{$ns}:Stmt/{$ns}:Bal");

        foreach ($balNodes as $balNode) {
            $code = $this->getNodeValue($xpath, "{$ns}:Tp/{$ns}:CdOrPrtry/{$ns}:Cd", $balNode);
            $amount = (float) $this->getNodeValue($xpath, "{$ns}:Amt", $balNode);
            $indicator = $this->getNodeValue($xpath, "{$ns}:CdtDbtInd", $balNode);
            
            // ⭐ Datum: DtTm oder Dt
            $dateStr = $this->getNodeValue($xpath, "{$ns}:Dt/{$ns}:DtTm", $balNode);
            if (!$dateStr) {
                $dateStr = $this->getNodeValue($xpath, "{$ns}:Dt/{$ns}:Dt", $balNode);
            }

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
        $ns = $this->stmtNs;
        $entries = [];
        $entryNodes = $xpath->query("//{$ns}:Stmt/{$ns}:Ntry");

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
        $ns = $this->stmtNs;

        // Basis-Daten
        $ntryRef = $this->getNodeValue($xpath, "{$ns}:NtryRef", $entryNode);
        $betrag = (float) $this->getNodeValue($xpath, "{$ns}:Amt", $entryNode);
        $waehrung = $this->getNodeAttribute($xpath, "{$ns}:Amt", 'Ccy', $entryNode) ?: 'EUR';
        $typ = $this->getNodeValue($xpath, "{$ns}:CdtDbtInd", $entryNode) ?: 'CRDT';

        // ⭐ Datum parsen: DtTm oder Dt
        $buchungsdatumStr = $this->getNodeValue($xpath, "{$ns}:BookgDt/{$ns}:DtTm", $entryNode);
        if (!$buchungsdatumStr) {
            $buchungsdatumStr = $this->getNodeValue($xpath, "{$ns}:BookgDt/{$ns}:Dt", $entryNode);
        }
        
        $valutadatumStr = $this->getNodeValue($xpath, "{$ns}:ValDt/{$ns}:DtTm", $entryNode);
        if (!$valutadatumStr) {
            $valutadatumStr = $this->getNodeValue($xpath, "{$ns}:ValDt/{$ns}:Dt", $entryNode);
        }

        $buchungsdatum = $buchungsdatumStr ? Carbon::parse($buchungsdatumStr)->toDateString() : now()->toDateString();
        $valutadatum = $valutadatumStr ? Carbon::parse($valutadatumStr)->toDateString() : null;

        // Transaktionscode
        $txCode = $this->getNodeValue($xpath, "{$ns}:BkTxCd/{$ns}:Prtry/{$ns}:Cd", $entryNode);
        $txIssuer = $this->getNodeValue($xpath, "{$ns}:BkTxCd/{$ns}:Prtry/{$ns}:Issr", $entryNode);

        // Details (TxDtls)
        $gegenkontoName = '';
        $gegenkontoIban = '';
        $verwendungszweck = '';

        // InitgPty/Nm - Auftraggeber Name
        $gegenkontoName = $this->getNodeValue($xpath, "{$ns}:NtryDtls/{$ns}:TxDtls/{$ns}:RltdPties/{$ns}:InitgPty/{$ns}:Nm", $entryNode);
        
        // ⭐ Falls kein InitgPty, versuche Dbtr/Nm
        if (!$gegenkontoName) {
            $gegenkontoName = $this->getNodeValue($xpath, "{$ns}:NtryDtls/{$ns}:TxDtls/{$ns}:RltdPties/{$ns}:Dbtr/{$ns}:Nm", $entryNode);
        }
        
        // DbtrAcct IBAN
        $gegenkontoIban = $this->getNodeValue($xpath, "{$ns}:NtryDtls/{$ns}:TxDtls/{$ns}:RltdPties/{$ns}:DbtrAcct/{$ns}:Id/{$ns}:IBAN", $entryNode);
        
        // Falls kein Debtor, versuche Creditor
        if (!$gegenkontoIban) {
            $gegenkontoIban = $this->getNodeValue($xpath, "{$ns}:NtryDtls/{$ns}:TxDtls/{$ns}:RltdPties/{$ns}:CdtrAcct/{$ns}:Id/{$ns}:IBAN", $entryNode);
        }

        // Verwendungszweck
        $verwendungszweck = $this->getNodeValue($xpath, "{$ns}:NtryDtls/{$ns}:TxDtls/{$ns}:AddtlTxInf", $entryNode);

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
        // Hash für Duplikat-Erkennung
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
     * Gibt Import-Statistiken zurück
     */
    public function getStats(): array
    {
        return $this->stats;
    }

    /**
     * Gibt Fehler zurück
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
