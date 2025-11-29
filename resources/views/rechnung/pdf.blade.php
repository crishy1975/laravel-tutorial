<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rechnung {{ $rechnung->rechnungsnummer }}</title>
    <style>
        /* ═══════════════════════════════════════════════════════════
           SCHWARZ-WEISS PDF (S/W-Drucker optimiert)
        ═══════════════════════════════════════════════════════════ */
        
        /* BASIS */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 8pt;
            line-height: 1.3;
            color: #000;
            background: #fff;
            padding: 10mm;
        }
        
        /* BRIEFKOPF */
        .letterhead {
            margin-bottom: 4mm;
            padding-bottom: 3mm;
            border-bottom: 2px solid #000;
        }
        
        .letterhead-row {
            display: table;
            width: 100%;
        }
        
        .letterhead-left {
            display: table-cell;
            width: 55%;
            vertical-align: top;
        }
        
        .letterhead-right {
            display: table-cell;
            width: 45%;
            vertical-align: top;
            text-align: right;
        }
        
        .company-name {
            font-size: 11pt;
            font-weight: bold;
            margin-bottom: 2mm;
        }
        
        .company-info {
            font-size: 7.5pt;
            line-height: 1.4;
        }
        
        .logo {
            max-width: 200px;
            max-height: 70px;
            margin-bottom: 2mm;
        }
        
        /* ADRESSEN (3-Spalten) */
        .address-section {
            margin-bottom: 6mm;
        }
        
        .address-row {
            display: table;
            width: 100%;
        }
        
        .address-col {
            display: table-cell;
            vertical-align: top;
            padding: 0 2mm;
        }
        
        .address-col:first-child {
            padding-left: 0;
        }
        
        .address-col:last-child {
            padding-right: 0;
        }
        
        .address-col.post {
            width: 33%;
        }
        
        .address-col.billing {
            width: 33%;
        }
        
        .address-col.data {
            width: 34%;
        }
        
        .address-box {
            border: none;
            padding: 2mm;
            min-height: 24mm;
        }
        
        .address-label {
            font-size: 7pt;
            font-weight: bold;
            margin-bottom: 1.5mm;
            border-bottom: 1px solid #000;
            padding-bottom: 0.5mm;
        }
        
        .address-content {
            font-size: 8pt;
            line-height: 1.4;
        }
        
        .address-name {
            font-weight: bold;
            font-size: 9pt;
            margin-bottom: 1mm;
        }
        
        /* Rechnungsdaten-Tabelle */
        .invoice-data-table {
            width: 100%;
            font-size: 7.5pt;
        }
        
        .invoice-data-table td {
            padding: 1mm 0;
        }
        
        .invoice-data-table .label {
            font-weight: bold;
            width: 40%;
        }
        
        /* TITEL */
        .invoice-title {
            font-size: 14pt;
            font-weight: bold;
            margin: 4mm 0 2mm 0;
            text-align: center;
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
            padding: 2mm 0;
        }
        
        /* CAUSALE BOX */
        .causale-box {
            border: 2px solid #000;
            padding: 3mm;
            margin: 4mm 0;
            font-size: 8.5pt;
        }
        
        .causale-label {
            font-weight: bold;
            margin-bottom: 1mm;
            font-size: 9pt;
        }
        
        .causale-text {
            line-height: 1.4;
        }
        
        /* POSITIONEN TABELLE */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 4mm 0;
            font-size: 8pt;
        }
        
        .items-table th {
            background: #fff;
            color: #000;
            font-weight: bold;
            padding: 2mm;
            text-align: left;
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
            font-size: 8pt;
        }
        
        .items-table th.right,
        .items-table td.right {
            text-align: right;
        }
        
        .items-table td {
            padding: 1.5mm 2mm;
            border-bottom: 1px solid #000;
        }
        
        .items-table tr:last-child td {
            border-bottom: 2px solid #000;
        }
        
        /* Zweisprachige Header */
        .bilingual {
            display: block;
        }
        
        .bilingual .de {
            font-weight: bold;
        }
        
        .bilingual .it {
            font-style: italic;
            font-size: 7pt;
        }
        
        /* SUMMEN */
        .totals-section {
            margin-top: 4mm;
            float: right;
            width: 50%;
        }
        
        .totals-table {
            width: 100%;
            font-size: 8pt;
            border-collapse: collapse;
        }
        
        .totals-table td {
            padding: 1.5mm 2mm;
            border-bottom: 1px solid #000;
        }
        
        .totals-table .label {
            text-align: left;
            font-weight: bold;
        }
        
        .totals-table .value {
            text-align: right;
            font-weight: bold;
        }
        
        .totals-table tr.total {
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
        }
        
        .totals-table tr.total td {
            padding: 2mm;
            font-size: 10pt;
            font-weight: bold;
        }
        
        /* INFO BOXEN */
        .info-box {
            clear: both;
            border: 1px solid #000;
            padding: 3mm;
            margin: 4mm 0;
            font-size: 7.5pt;
        }
        
        .info-box h3 {
            font-size: 9pt;
            font-weight: bold;
            margin-bottom: 2mm;
            border-bottom: 1px solid #000;
            padding-bottom: 1mm;
        }
        
        /* FOOTER */
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 8mm;
            border-top: 1px solid #000;
            padding-top: 1mm;
            font-size: 6.5pt;
            text-align: center;
            line-height: 1.3;
        }
        
        /* MAIN CONTENT */
        .content {
            margin-bottom: 10mm;
        }
        
        /* UTILITIES */
        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }
    </style>
</head>
<body>

    <div class="content">
        
        {{-- BRIEFKOPF --}}
        <div class="letterhead">
            <div class="letterhead-row">
                <div class="letterhead-left">
                    {{-- Logo --}}
                    @php
                        $logoExists = false;
                        $logoPath = null;
                        $logoPfad = $unternehmen->logo_rechnung_pfad ?? $unternehmen->logo_pfad ?? null;
                        
                        if ($unternehmen && $logoPfad) {
                            $paths = [
                                storage_path('app/public/' . $logoPfad),
                                public_path('storage/' . $logoPfad),
                                public_path($logoPfad),
                            ];
                            
                            foreach ($paths as $path) {
                                if (file_exists($path) && is_readable($path)) {
                                    $logoPath = $path;
                                    $logoExists = true;
                                    break;
                                }
                            }
                        }
                    @endphp
                    
                    @if($logoExists && $logoPath)
                        <img src="{{ $logoPath }}" alt="Logo" class="logo">
                    @endif
                    
                    <div class="company-name">
                        {{ $unternehmen->firmenname ?? 'Meisterbetrieb Resch GmbH' }}
                    </div>
                    <div class="company-info">
                        @if($unternehmen)
                            {{ $unternehmen->strasse }} {{ $unternehmen->hausnummer }},
                            {{ $unternehmen->postleitzahl }} {{ $unternehmen->ort }}
                            @if($unternehmen->bundesland) ({{ $unternehmen->bundesland }})@endif
                        @else
                            Musterstraße 123, 39100 Bozen (BZ)
                        @endif
                    </div>
                </div>
                
                <div class="letterhead-right">
                    <div style="font-size: 7.5pt; line-height: 1.4;">
                        @if($unternehmen)
                            @if($unternehmen->telefon)<strong>Tel:</strong> {{ $unternehmen->telefon }}<br>@endif
                            @if($unternehmen->email)<strong>E-Mail:</strong> {{ $unternehmen->email }}<br>@endif
                            @if($unternehmen->website)<strong>Web:</strong> {{ $unternehmen->website }}<br>@endif
                            @if($unternehmen->partita_iva)<strong>P.IVA:</strong> {{ $unternehmen->partita_iva }}<br>@endif
                            @if($unternehmen->codice_fiscale)<strong>CF:</strong> {{ $unternehmen->codice_fiscale }}@endif
                        @else
                            <strong>Tel:</strong> +39 0471 123456<br>
                            <strong>E-Mail:</strong> <a href="/cdn-cgi/l/email-protection" class="__cf_email__" data-cfemail="e48d8a828ba4968197878cca869e">[email&#160;protected]</a><br>
                            <strong>P.IVA:</strong> 12345678901
                        @endif
                    </div>
                </div>
            </div>
        </div>
        
        {{-- ADRESSEN (3-Spalten: Post, Rechnung, Daten) --}}
        <div class="address-section">
            <div class="address-row">
                
                {{-- POSTADRESSE --}}
                <div class="address-col post">
                    <div class="address-box">
                        <div class="address-label">
                            <span class="de">Postadresse</span><br>
                            <span class="it" style="font-style: italic; font-size: 6.5pt;">Indirizzo Postale</span>
                        </div>
                        <div class="address-content">
                            @if($rechnung->gebaeude && $rechnung->gebaeude->postadresse)
                                <div class="address-name">{{ $rechnung->gebaeude->postadresse->name }}</div>
                                {{ $rechnung->gebaeude->postadresse->strasse }} {{ $rechnung->gebaeude->postadresse->hausnummer }}<br>
                                {{ $rechnung->gebaeude->postadresse->plz }} {{ $rechnung->gebaeude->postadresse->wohnort }}
                            @else
                                <div class="address-name">Muster GmbH</div>
                                Musterstraße 1<br>
                                39100 Bozen
                            @endif
                        </div>
                    </div>
                </div>
                
                {{-- RECHNUNGSEMPFÄNGER --}}
                <div class="address-col billing">
                    <div class="address-box">
                        <div class="address-label">
                            <span class="de">Rechnungsempfänger</span><br>
                            <span class="it" style="font-style: italic; font-size: 6.5pt;">Destinatario Fattura</span>
                        </div>
                        <div class="address-content">
                            @if($rechnung->rechnungsempfaenger)
                                <div class="address-name">{{ $rechnung->rechnungsempfaenger->name }}</div>
                                {{ $rechnung->rechnungsempfaenger->strasse }} {{ $rechnung->rechnungsempfaenger->hausnummer }}<br>
                                {{ $rechnung->rechnungsempfaenger->plz }} {{ $rechnung->rechnungsempfaenger->wohnort }}
                            @elseif($rechnung->gebaeude && $rechnung->gebaeude->rechnungsempfaenger)
                                <div class="address-name">{{ $rechnung->gebaeude->rechnungsempfaenger->name }}</div>
                                {{ $rechnung->gebaeude->rechnungsempfaenger->strasse }} {{ $rechnung->gebaeude->rechnungsempfaenger->hausnummer }}<br>
                                {{ $rechnung->gebaeude->rechnungsempfaenger->plz }} {{ $rechnung->gebaeude->rechnungsempfaenger->wohnort }}
                            @else
                                <div class="address-name">Muster GmbH</div>
                                Musterstraße 1<br>
                                39100 Bozen
                            @endif
                        </div>
                    </div>
                </div>
                
                {{-- RECHNUNGSDATEN --}}
                <div class="address-col data">
                    <div class="address-box">
                        <div class="address-label">
                            <span class="de">Rechnungsdaten</span><br>
                            <span class="it" style="font-style: italic; font-size: 6.5pt;">Dati Fattura</span>
                        </div>
                        <div class="address-content">
                            <table class="invoice-data-table">
                                <tr>
                                    <td class="label">
                                        <span class="de">Rechnung-Nr.:</span><br>
                                        <span class="it" style="font-style: italic; font-size: 6.5pt;">Fattura N.:</span>
                                    </td>
                                    <td><strong>{{ $rechnung->rechnungsnummer }}</strong></td>
                                </tr>
                                <tr>
                                    <td class="label">
                                        <span class="de">Datum:</span><br>
                                        <span class="it" style="font-style: italic; font-size: 6.5pt;">Data:</span>
                                    </td>
                                    <td>{{ $rechnung->rechnungsdatum->format('d.m.Y') }}</td>
                                </tr>
                                @if($rechnung->leistungsdatum)
                                <tr>
                                    <td class="label">
                                        <span class="de">Leistung:</span><br>
                                        <span class="it" style="font-style: italic; font-size: 6.5pt;">Prestazione:</span>
                                    </td>
                                    <td>{{ $rechnung->leistungsdatum->format('d.m.Y') }}</td>
                                </tr>
                                @endif
                                @if($rechnung->faelligkeitsdatum)
                                <tr>
                                    <td class="label">
                                        <span class="de">Fällig:</span><br>
                                        <span class="it" style="font-style: italic; font-size: 6.5pt;">Scadenza:</span>
                                    </td>
                                    <td>{{ $rechnung->faelligkeitsdatum->format('d.m.Y') }}</td>
                                </tr>
                                @endif
                                @if($rechnung->causale)
                                <tr>
                                    <td class="label">
                                        <span class="de">Causale:</span><br>
                                        <span class="it" style="font-style: italic; font-size: 6.5pt;">Causale:</span>
                                    </td>
                                    <td style="font-size: 7pt;">{{ Str::limit($rechnung->causale, 50) }}</td>
                                </tr>
                                @endif
                            </table>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
        
        {{-- TITEL --}}
        <div class="invoice-title">
            RECHNUNG / FATTURA<br>
            <span style="font-size: 12pt;">{{ $rechnung->rechnungsnummer }}</span>
        </div>
        
        {{-- CAUSALE (Rechnungsgrund) - PROMINENT --}}
        @if($rechnung->causale)
        <div class="causale-box">
            <div class="causale-label">
                Rechnungsgrund / Causale:
            </div>
            <div class="causale-text">
                {{ $rechnung->causale }}
            </div>
        </div>
        @endif
        
        {{-- POSITIONEN TABELLE --}}
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 8%;">
                        <span class="bilingual">
                            <span class="de">Pos.</span><br>
                            <span class="it">Pos.</span>
                        </span>
                    </th>
                    <th style="width: 42%;">
                        <span class="bilingual">
                            <span class="de">Beschreibung</span><br>
                            <span class="it">Descrizione</span>
                        </span>
                    </th>
                    <th class="right" style="width: 10%;">
                        <span class="bilingual">
                            <span class="de">Menge</span><br>
                            <span class="it">Quantità</span>
                        </span>
                    </th>
                    <th class="right" style="width: 12%;">
                        <span class="bilingual">
                            <span class="de">Einheit</span><br>
                            <span class="it">Unità</span>
                        </span>
                    </th>
                    <th class="right" style="width: 14%;">
                        <span class="bilingual">
                            <span class="de">Einzelpreis</span><br>
                            <span class="it">Prezzo Unit.</span>
                        </span>
                    </th>
                    <th class="right" style="width: 14%;">
                        <span class="bilingual">
                            <span class="de">Gesamt</span><br>
                            <span class="it">Totale</span>
                        </span>
                    </th>
                </tr>
            </thead>
            <tbody>
                @foreach($rechnung->positionen as $pos)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $pos->beschreibung }}</td>
                    <td class="right">{{ number_format($pos->menge, 2, ',', '.') }}</td>
                    <td class="right">{{ $pos->einheit ?? 'Stk' }}</td>
                    <td class="right">{{ number_format($pos->einzelpreis, 2, ',', '.') }} €</td>
                    <td class="right">{{ number_format($pos->gesamtpreis, 2, ',', '.') }} €</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        
        {{-- SUMMEN --}}
        <div class="totals-section">
            <table class="totals-table">
                <tr>
                    <td class="label">
                        <span class="de">Zwischensumme (Netto)</span><br>
                        <span class="it" style="font-style: italic; font-size: 7pt;">Subtotale (Netto)</span>
                    </td>
                    <td class="value">{{ number_format($rechnung->netto_summe, 2, ',', '.') }} €</td>
                </tr>
                
                {{-- MwSt - IMMER anzeigen (auch bei 0%) --}}
                <tr>
                    <td class="label">
                        <span class="de">MwSt {{ number_format($rechnung->mwst_satz, 1) }}%</span><br>
                        <span class="it" style="font-style: italic; font-size: 7pt;">IVA {{ number_format($rechnung->mwst_satz, 1) }}%</span>
                    </td>
                    <td class="value">
                        @if($rechnung->mwst_betrag > 0)
                            {{ number_format($rechnung->mwst_betrag, 2, ',', '.') }} €
                        @else
                            0,00 €
                        @endif
                    </td>
                </tr>
                
                {{-- Reverse Charge Zeile (SEPARATE ZEILE!) --}}
                @if($rechnung->reverse_charge)
                <tr>
                    <td colspan="2" style="padding: 2mm; border-bottom: 1px solid #000; font-size: 7.5pt;">
                        <strong>⚠ Reverse Charge</strong> - Umkehrung der Steuerschuldnerschaft<br>
                        <span style="font-style: italic;">
                            Inversione contabile (Art. 17 DPR 633/72) - IVA a carico del committente
                        </span>
                    </td>
                </tr>
                @endif
                
                {{-- Split Payment Zeile (SEPARATE ZEILE!) --}}
                @if($rechnung->split_payment)
                <tr>
                    <td colspan="2" style="padding: 2mm; border-bottom: 1px solid #000; font-size: 7.5pt;">
                        <strong>⚠ Split Payment</strong> - Geteilte Zahlung<br>
                        <span style="font-style: italic;">
                            Scissione dei pagamenti (Legge 190/2014) - IVA versata dall'ente pubblico
                        </span>
                    </td>
                </tr>
                @endif
                
                @if($rechnung->ritenuta_betrag > 0)
                <tr>
                    <td class="label">
                        <span class="de">Quellensteuer {{ number_format($rechnung->ritenuta_prozent, 1) }}%</span><br>
                        <span class="it" style="font-style: italic; font-size: 7pt;">Ritenuta {{ number_format($rechnung->ritenuta_prozent, 1) }}%</span>
                    </td>
                    <td class="value">-{{ number_format($rechnung->ritenuta_betrag, 2, ',', '.') }} €</td>
                </tr>
                @endif
                
                <tr class="total">
                    <td class="label">
                        <span class="de">GESAMTBETRAG</span><br>
                        <span class="it" style="font-style: italic; font-size: 8pt;">IMPORTO TOTALE</span>
                    </td>
                    <td class="value">{{ number_format($rechnung->brutto_summe, 2, ',', '.') }} €</td>
                </tr>
            </table>
        </div>
        
        <div style="clear: both;"></div>
        
        {{-- REVERSE CHARGE / SPLIT PAYMENT HINWEISE --}}
        @if($rechnung->reverse_charge || $rechnung->split_payment)
        <div class="info-box">
            <h3>Steuerrechtliche Hinweise / Note Fiscali</h3>
            
            @if($rechnung->reverse_charge)
            <p style="margin-bottom: 2mm; line-height: 1.4;">
                <strong>Reverse Charge (Umkehrung der Steuerschuldnerschaft):</strong><br>
                <span style="font-style: italic;">
                    Inversione contabile ai sensi dell'Art. 17, comma 6, DPR 633/72.
                    L'IVA è dovuta dal committente.
                </span>
            </p>
            @endif
            
            @if($rechnung->split_payment)
            <p style="line-height: 1.4;">
                <strong>Split Payment (Geteilte Zahlung):</strong><br>
                <span style="font-style: italic;">
                    Scissione dei pagamenti ai sensi della Legge 190/2014, Art. 1, comma 629.
                    L'IVA sarà versata direttamente dall'ente pubblico all'Erario.
                </span>
            </p>
            @endif
        </div>
        @endif
        
        {{-- ZAHLUNGSINFORMATIONEN --}}
        <div class="info-box">
            <h3>Zahlungsinformationen / Informazioni di Pagamento</h3>
            <table style="width: 100%; font-size: 7.5pt;">
                <tr>
                    <td style="width: 30%; font-weight: bold;">
                        Zahlungsziel / Scadenza:
                    </td>
                    <td>
                        @if($rechnung->faelligkeitsdatum)
                            {{ $rechnung->faelligkeitsdatum->format('d.m.Y') }}
                        @else
                            {{ $rechnung->rechnungsdatum->addDays(30)->format('d.m.Y') }}
                        @endif
                    </td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">
                        Zahlungsart / Modalità:
                    </td>
                    <td>{{ $rechnung->zahlungsart ?? 'Banküberweisung / Bonifico Bancario' }}</td>
                </tr>
                @if($unternehmen && $unternehmen->bank_name)
                <tr>
                    <td style="font-weight: bold;">
                        Bank / Banca:
                    </td>
                    <td>{{ $unternehmen->bank_name }}</td>
                </tr>
                @endif
                @if($unternehmen && $unternehmen->iban)
                <tr>
                    <td style="font-weight: bold;">IBAN:</td>
                    <td>{{ $unternehmen->iban }}</td>
                </tr>
                @endif
                @if($unternehmen && $unternehmen->bic)
                <tr>
                    <td style="font-weight: bold;">BIC/SWIFT:</td>
                    <td>{{ $unternehmen->bic }}</td>
                </tr>
                @endif
            </table>
        </div>
        
        {{-- ITALIENISCHE PFLICHTFELDER (CUP, CIG, etc.) --}}
        @if($rechnung->cup || $rechnung->cig || $rechnung->codice_commessa || $rechnung->auftrag_id)
        <div class="info-box">
            <h3>Zusätzliche Angaben / Informazioni Aggiuntive</h3>
            <table style="width: 100%; font-size: 7.5pt;">
                @if($rechnung->cup)
                <tr>
                    <td style="width: 30%; font-weight: bold;">CUP:</td>
                    <td>{{ $rechnung->cup }}</td>
                </tr>
                @endif
                @if($rechnung->cig)
                <tr>
                    <td style="font-weight: bold;">CIG:</td>
                    <td>{{ $rechnung->cig }}</td>
                </tr>
                @endif
                @if($rechnung->codice_commessa)
                <tr>
                    <td style="font-weight: bold;">Codice Commessa:</td>
                    <td>{{ $rechnung->codice_commessa }}</td>
                </tr>
                @endif
                @if($rechnung->auftrag_id)
                <tr>
                    <td style="font-weight: bold;">Auftrags-ID / ID Ordine:</td>
                    <td>{{ $rechnung->auftrag_id }}</td>
                </tr>
                @endif
            </table>
        </div>
        @endif
        
        {{-- BEMERKUNGEN --}}
        @if($rechnung->bemerkung_kunde)
        <div class="info-box">
            <h3>Bemerkungen / Note</h3>
            <p style="line-height: 1.4;">{{ $rechnung->bemerkung_kunde }}</p>
        </div>
        @endif
        
    </div>
    
    {{-- FOOTER --}}
    <div class="footer">
        @if($unternehmen)
            <strong>{{ $unternehmen->firmenname }}</strong> · 
            {{ $unternehmen->strasse }} {{ $unternehmen->hausnummer }}, {{ $unternehmen->postleitzahl }} {{ $unternehmen->ort }}
            @if($unternehmen->codice_fiscale) · CF: {{ $unternehmen->codice_fiscale }}@endif
            @if($unternehmen->partita_iva) · P.IVA: {{ $unternehmen->partita_iva }}@endif
            @if($unternehmen->telefon) · Tel: {{ $unternehmen->telefon }}@endif
            @if($unternehmen->email) · {{ $unternehmen->email }}@endif

        @else
            Meisterbetrieb Resch GmbH · Musterstraße 123, 39100 Bozen · P.IVA: 12345678901 · Tel: +39 0471 123456 · info@resch.it
        @endif
    </div>  
</body>
</html>

{{-- ═══════════════════════════════════════════════════════════
   ENDE DER DATEI
    ═══════════════════════════════════════════════════════════ --}}
{{-- Vergleiche mit resources/views/rechnung/show.blade.php --}}
