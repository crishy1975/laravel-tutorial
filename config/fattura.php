<?php

return [

    /*
    |--------------------------------------------------------------------------
    | 🏢 CEDENTE/PRESTATORE (Rechnungssteller)
    |--------------------------------------------------------------------------
    |
    | Firmendaten werden DYNAMISCH aus dem Unternehmensprofil Model geholt!
    | 
    | Model: App\Models\Unternehmensprofil
    | 
    | Die Firmendaten kommen aus:
    | - ragione_sociale (IT)
    | - partita_iva (IT)
    | - codice_fiscale (IT)
    | - regime_fiscale (IT)
    | - strasse, postleitzahl, ort, etc.
    | - iban, bank_name
    |
    | VERWENDUNG:
    | $profil = Unternehmensprofil::aktivOderFehler();
    | $partitaIva = $profil->partita_iva;
    |
    */

    // ✅ Keine statischen Firmendaten mehr!
    // ✅ Alles kommt aus Unternehmensprofil Model

    /*
    |--------------------------------------------------------------------------
    | 📨 TRASMISSIONE (Übertragungsdaten)
    |--------------------------------------------------------------------------
    */

    'trasmissione' => [
        // Formato Trasmissione
        // FPR12 = Standard für private Unternehmen
        // FPA12 = Standard für öffentliche Verwaltung (PA)
        'formato_trasmissione' => env('FATTURA_FORMATO', 'FPR12'),

        // Codice Destinatario Default (7 Zeichen)
        // 0000000 = wenn PEC verwendet wird
        'codice_destinatario_default' => '0000000',

        // Progressivo Invio (Prefix für Sendungsnummer)
        // Format: {prefix}{jahr}{laufnummer}
        // z.B. "IT202500001" für erste Sendung 2025
        'progressivo_prefix' => env('FATTURA_PROGRESSIVO_PREFIX', 'IT'),
    ],

    /*
    |--------------------------------------------------------------------------
    | 💰 DEFAULTS (Standardwerte)
    |--------------------------------------------------------------------------
    */

    'defaults' => [
        // Zahlungsbedingungen Standard
        // TP01 = Pagamento a rate (Teilzahlung)
        // TP02 = Pagamento completo (Komplett) ⭐ STANDARD
        'condizioni_pagamento' => 'TP02',

        // Zahlungsmethode Standard
        // MP05 = Bonifico (Überweisung) ⭐ STANDARD
        'modalita_pagamento' => 'MP05',

        // Zahlungsziel Standard (Tage) - wird überschrieben durch Zahlungsbedingung!
        'giorni_scadenza' => 30,

        // Esigibilità IVA Standard
        // I = Immediata (sofort) ⭐ STANDARD
        // D = Differita (aufgeschoben)
        // S = Split Payment (wird aus FatturaProfile geholt!)
        'esigibilita_iva' => 'I',
    ],

    /*
    |--------------------------------------------------------------------------
    | 📁 FILE STORAGE (Dateispeicherung)
    |--------------------------------------------------------------------------
    */

    'storage' => [
        // Pfad für generierte XML-Dateien (relativ zu storage/app)
        'xml_path' => 'fattura/xml',

        // Pfad für signierte P7M-Dateien (später für digitale Signatur)
        'p7m_path' => 'fattura/signed',

        // Dateinamen-Format
        // Platzhalter: {paese}, {codice}, {progressivo}, {anno}, {numero}
        // z.B. IT_12345678901_00001.xml
        'filename_format' => '{paese}_{codice}_{progressivo}.xml',

        // Automatisch alte XMLs löschen nach X Tagen (0 = nie)
        'auto_delete_days' => 0,
    ],

    /*
    |--------------------------------------------------------------------------
    | ⚙️ XML GENERATION (XML-Generierung)
    |--------------------------------------------------------------------------
    */

    'xml' => [
        // XML-Version (FatturaPA v1.2)
        'versione' => 'FPR12',

        // Namespace
        'namespace' => 'http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2',

        // Namespace Prefix
        'namespace_prefix' => 'p',

        // Encoding
        'encoding' => 'UTF-8',

        // Pretty Print (formatiertes XML, nur für Entwicklung)
        'pretty_print' => env('APP_DEBUG', false),

        // Validierung gegen XSD aktivieren
        'validate_xsd' => env('FATTURA_VALIDATE_XSD', true),

        // Pfad zum XSD-Schema (optional, für lokale Validierung)
        'xsd_path' => storage_path('app/fattura/schema/Schema_del_file_xml_FatturaPA_versione_1.2.1.xsd'),
    ],

    /*
    |--------------------------------------------------------------------------
    | 🔐 DIGITAL SIGNATURE (Digitale Signatur - Optional)
    |--------------------------------------------------------------------------
    |
    | Für den Versand via PEC ist eine digitale Signatur erforderlich
    |
    */

    'signature' => [
        // Signierung aktivieren
        'enabled' => env('FATTURA_SIGNATURE_ENABLED', false),

        // Pfad zum Zertifikat (.pfx oder .p12)
        'certificate_path' => env('FATTURA_CERTIFICATE_PATH', ''),

        // Zertifikats-Passwort
        'certificate_password' => env('FATTURA_CERTIFICATE_PASSWORD', ''),

        // Signatur-Algorithmus
        'algorithm' => 'sha256',
    ],

    /*
    |--------------------------------------------------------------------------
    | 🌐 SDI INTEGRATION (Sistema di Interscambio - Optional)
    |--------------------------------------------------------------------------
    */

    'sdi' => [
        // SDI-Integration aktivieren
        'enabled' => env('FATTURA_SDI_ENABLED', false),

        // SDI-PEC-Adresse (Standard für alle)
        'pec_address' => 'sdi01@pec.fatturapa.it',

        // Automatischer Status-Abruf
        'auto_fetch_status' => false,

        // Status-Abruf Intervall (Minuten)
        'status_check_interval' => 60,

        // Webhook für SDI-Benachrichtigungen (optional)
        'webhook_url' => env('FATTURA_SDI_WEBHOOK_URL', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | 📊 CODICI (Italienische Steuer-Codes)
    |--------------------------------------------------------------------------
    */

    'codici' => [
        // Tipo Documento
        'tipo_documento' => [
            'TD01' => 'Fattura',
            'TD04' => 'Nota di Credito',
            'TD05' => 'Nota di Debito',
            'TD06' => 'Parcella',
        ],

        // Regime Fiscale
        'regime_fiscale' => [
            'RF01' => 'Ordinario',
            'RF02' => 'Contribuenti minimi',
            'RF04' => 'Agricoltura e attività connesse',
            'RF05' => 'Vendita sali e tabacchi',
            'RF06' => 'Commercio fiammiferi',
            'RF07' => 'Editoria',
            'RF08' => 'Gestione servizi telefonia pubblica',
            'RF09' => 'Rivendita documenti di trasporto',
            'RF10' => 'Intrattenimenti, giochi e altre attività',
            'RF11' => 'Agenzie viaggi e turismo',
            'RF12' => 'Agriturismo',
            'RF13' => 'Vendite a domicilio',
            'RF14' => 'Rivendita beni usati',
            'RF15' => 'Agenzie di vendite all\'asta',
            'RF16' => 'IVA per cassa P.A.',
            'RF17' => 'IVA per cassa (tutti)',
            'RF18' => 'Altro',
            'RF19' => 'Regime forfettario',
        ],

        // Modalità Pagamento
        'modalita_pagamento' => [
            'MP01' => 'Contanti',
            'MP02' => 'Assegno',
            'MP03' => 'Assegno circolare',
            'MP04' => 'Contanti presso Tesoreria',
            'MP05' => 'Bonifico',
            'MP06' => 'Vaglia cambiario',
            'MP07' => 'Bollettino bancario',
            'MP08' => 'Carta di pagamento',
            'MP09' => 'RID',
            'MP10' => 'RID utenze',
            'MP11' => 'RID veloce',
            'MP12' => 'RIBA',
            'MP13' => 'MAV',
            'MP14' => 'Quietanza erario',
            'MP15' => 'Giroconto su conti',
            'MP16' => 'Domiciliazione bancaria',
            'MP17' => 'Domiciliazione postale',
            'MP18' => 'Bollettino di c/c postale',
            'MP19' => 'SEPA Direct Debit',
            'MP20' => 'SEPA Direct Debit CORE',
            'MP21' => 'SEPA Direct Debit B2B',
            'MP22' => 'Trattenuta su somme già riscosse',
        ],

        // Condizioni Pagamento
        'condizioni_pagamento' => [
            'TP01' => 'Pagamento a rate',
            'TP02' => 'Pagamento completo',
            'TP03' => 'Anticipo',
        ],

        // Esigibilità IVA
        'esigibilita_iva' => [
            'I' => 'Esigibilità immediata',
            'D' => 'Esigibilità differita',
            'S' => 'Scissione dei pagamenti (Split Payment)',
        ],

        // Natura (bei IVA = 0%)
        'natura' => [
            'N1' => 'Escluse ex art. 15',
            'N2' => 'Non soggette',
            'N3' => 'Non imponibili',
            'N4' => 'Esenti',
            'N5' => 'Regime del margine',
            'N6' => 'Inversione contabile',
            'N7' => 'IVA assolta in altro stato UE',
        ],

        // Tipo Ritenuta
        'tipo_ritenuta' => [
            'RT01' => 'Ritenuta persone fisiche',
            'RT02' => 'Ritenuta persone giuridiche',
        ],

        // Causale Pagamento (für Ritenuta)
        'causale_pagamento' => [
            'A' => 'Prestazioni di lavoro autonomo',
            'B' => 'Utilizzazione economica di opere dell\'ingegno',
            'C' => 'Utili da associazione in partecipazione',
            'D' => 'Utili da contratti di cointeressenza',
            'E' => 'Levata di protesti',
            'G' => 'Indennità di esproprio',
            'H' => 'Indennità per cessazione di rapporto',
            'I' => 'Indennità per cessazione di rapporto',
            'L' => 'Utilizzazione economica di opere dell\'ingegno',
            'M' => 'Prestazioni di lavoro autonomo',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 🧪 DEBUG & LOGGING
    |--------------------------------------------------------------------------
    */

    'debug' => [
        // Detailliertes Logging
        'verbose_logging' => env('FATTURA_DEBUG_LOGGING', env('APP_DEBUG', false)),

        // Test-Modus (keine echte Übertragung)
        'test_mode' => env('FATTURA_TEST_MODE', false),

        // XML in Logfile schreiben
        'log_xml_content' => env('FATTURA_LOG_XML', false),
    ],

];