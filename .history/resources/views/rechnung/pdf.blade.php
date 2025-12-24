<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    {{-- ⭐ TITEL DYNAMISCH --}}
    <title>{{ $rechnung->typ_rechnung === 'gutschrift' ? 'Gutschrift' : 'Rechnung' }} {{ $rechnung->rechnungsnummer }}</title>
    <style>
        /* ═══════════════════════════════════════════════════════════════
           MODERNES FARBIGES PDF - dompdf-kompatibel
           Primärfarbe: #1a4a7c (Dunkelblau)
        ═══════════════════════════════════════════════════════════════ */
        
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
            color: #333;
            background-color: #fff;
            padding: 10mm;
        }
        
        /* BRIEFKOPF */
        .letterhead {
            margin-bottom: 4mm;
            padding: 3mm;
            padding-bottom: 4mm;
            border-bottom: 3px solid #1a4a7c;
            background-color: #f8fafc;
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
            font-size: 12pt;
            font-weight: bold;
            margin-bottom: 2mm;
            color: #1a4a7c;
        }
        
        .company-info {
            font-size: 7.5pt;
            line-height: 1.4;
            color: #555;
        }
        
        .logo {
            max-width: 220px;
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
        
        .address-col.post,
        .address-col.billing,
        .address-col.data {
            width: 33.33%;
        }
        
        .address-box {
            padding: 2mm;
            min-height: 24mm;
            background-color: transparent;
        }
        
        .address-label {
            font-size: 7.5pt;
            font-weight: bold;
            margin-bottom: 1.5mm;
            padding: 1.5mm 2mm;
            background-color: #1a4a7c;
            color: #fff;
        }
        
        .address-content {
            font-size: 8pt;
            line-height: 1.4;
            padding: 1mm;
        }
        
        .address-name {
            font-weight: bold;
            font-size: 9pt;
            margin-bottom: 1mm;
            color: #1a4a7c;
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
            color: #555;
        }
        
        /* TITEL */
        .invoice-title {
            font-size: 12pt;
            font-weight: bold;
            margin: 3mm 0 2mm 0;
            text-align: center;
            padding: 2mm 0;
            background-color: #1a4a7c;
            color: #fff;
            letter-spacing: 1px;
        }
        
        /* GUTSCHRIFT-TITEL (orange) */
        .invoice-title.gutschrift {
            background-color: #d97706;
        }
        
        /* CAUSALE BOX */
        .causale-box {
            border: 1px solid #1a4a7c;
            padding: 3mm;
            margin: 4mm 0;
            font-size: 8.5pt;
            background-color: #f0f7ff;
        }
        
        .causale-label {
            font-weight: bold;
            margin-bottom: 1mm;
            font-size: 9pt;
            color: #1a4a7c;
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
            background-color: #1a4a7c;
            color: #fff;
            font-weight: bold;
            padding: 2mm;
            text-align: left;
            font-size: 7.5pt;
        }
        
        .items-table th.right,
        .items-table td.right {
            text-align: right;
        }
        
        .items-table td {
            padding: 1.5mm 2mm;
            border-bottom: 1px solid #ddd;
        }
        
        .items-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        
        .items-table tr:last-child td {
            border-bottom: 2px solid #1a4a7c;
        }
        
        /* Zweisprachige Header */
        .bilingual {
            display: block;
        }
        
        .bilingual .de {
            font-weight: bold;
        }
        
        .bilingual .it {
            font-weight: normal;
        }
        
        /* SUMMEN */
        .totals-section {
            margin-top: 4mm;
            float: right;
            width: 50%;
        }
        
        .totals-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #ccc;
        }
        
        .totals-table td {
            padding: 1.5mm 2mm;
            border-bottom: 1px solid #ddd;
        }
        
        .totals-table .label {
            font-size: 7.5pt;
            color: #555;
        }
        
        .totals-table .value {
            text-align: right;
            font-weight: bold;
            font-size: 8pt;
        }
        
        .totals-table tr.total {
            background-color: #1a4a7c;
        }
        
        .totals-table tr.total td {
            padding: 2.5mm 2mm;
            font-size: 10pt;
            border-bottom: none;
            color: #fff;
        }
        
        .totals-table tr.total .label {
            color: #fff;
        }
        
        /* Hinweis-Zeilen in Summen */
        .hint-row-yellow td {
            background-color: #fef3c7;
            border-left: 3px solid #d97706;
        }
        
        .hint-row-blue td {
            background-color: #e0f2fe;
            border-left: 3px solid #0284c7;
        }
        
        /* INFO BOXEN */
        .info-box {
            border: 1px solid #ddd;
            padding: 3mm;
            margin-top: 5mm;
            font-size: 7.5pt;
            background-color: #f8fafc;
        }
        
        .info-box h3 {
            font-size: 8pt;
            margin-bottom: 2mm;
            border-bottom: 1px solid #ddd;
            padding-bottom: 1mm;
            color: #1a4a7c;
        }
        
        .info-box.success {
            border-color: #16a34a;
            background-color: #f0fdf4;
        }
        
        .info-box.success h3 {
            color: #16a34a;
        }
        
        .info-box.warning {
            border-color: #d97706;
            background-color: #fffbeb;
        }
        
        .info-box.warning h3 {
            color: #d97706;
        }
        
        /* FOOTER */
        .footer {
            position: fixed;
            bottom: 5mm;
            left: 10mm;
            right: 10mm;
            font-size: 6.5pt;
            text-align: center;
            border-top: 2px solid #1a4a7c;
            padding-top: 2mm;
            color: #555;
        }
        
        /* PAGE BREAK */
        .page-break {
            page-break-before: always;
        }
        
        /* CLEAR FIX */
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
                    <div style="font-size: 7.5pt; line-height: 1.5;">
                        @if($unternehmen)
                            @if($unternehmen->telefon)<strong>Tel:</strong> {{ $unternehmen->telefon }}<br>@endif
                            @if($unternehmen->email)<strong>E-Mail:</strong> {{ $unternehmen->email }}<br>@endif
                            @if($unternehmen->website)<strong>Web:</strong> {{ $unternehmen->website }}<br>@endif
                            @if($unternehmen->partita_iva)<strong>P.IVA:</strong> {{ $unternehmen->partita_iva }}<br>@endif
                            @if($unternehmen->codice_fiscale)<strong>CF:</strong> {{ $unternehmen->codice_fiscale }}@endif
                        @else
                            <strong>Tel:</strong> +39 0471 123456<br>
                            <strong>E-Mail:</strong> info@example.com<br>
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
                            Postadresse / Indirizzo Postale
                        </div>
                        <div class="address-content">
                            @if($rechnung->post_name)
                                {{-- Snapshot-Daten verwenden --}}
                                <div class="address-name">{{ $rechnung->post_name }}</div>
                                {{ $rechnung->post_strasse }} {{ $rechnung->post_hausnummer }}<br>
                                {{ $rechnung->post_plz }} {{ $rechnung->post_wohnort }}
                            @elseif($rechnung->gebaeude && $rechnung->gebaeude->postadresse)
                                <div class="address-name">{{ $rechnung->gebaeude->postadresse->name }}</div>
                                {{ $rechnung->gebaeude->postadresse->strasse }} {{ $rechnung->gebaeude->postadresse->hausnummer }}<br>
                                {{ $rechnung->gebaeude->postadresse->plz }} {{ $rechnung->gebaeude->postadresse->wohnort }}
                            @else
                                <div class="address-name">-</div>
                            @endif
                        </div>
                    </div>
                </div>
                
                {{-- RECHNUNGSEMPFÄNGER --}}
                <div class="address-col billing">
                    <div class="address-box">
                        <div class="address-label">
                            Empfänger / Destinatario
                        </div>
                        <div class="address-content">
                            @if($rechnung->re_name)
                                {{-- Snapshot-Daten verwenden --}}
                                <div class="address-name">{{ $rechnung->re_name }}</div>
                                {{ $rechnung->re_strasse }} {{ $rechnung->re_hausnummer }}<br>
                                {{ $rechnung->re_plz }} {{ $rechnung->re_wohnort }}
                                @if($rechnung->re_steuernummer)
                                    <br><small>CF: {{ $rechnung->re_steuernummer }}</small>
                                @endif
                                @if($rechnung->re_mwst_nummer)
                                    <br><small>P.IVA: {{ $rechnung->re_mwst_nummer }}</small>
                                @endif
                            @elseif($rechnung->rechnungsempfaenger)
                                <div class="address-name">{{ $rechnung->rechnungsempfaenger->name }}</div>
                                {{ $rechnung->rechnungsempfaenger->strasse }} {{ $rechnung->rechnungsempfaenger->hausnummer }}<br>
                                {{ $rechnung->rechnungsempfaenger->plz }} {{ $rechnung->rechnungsempfaenger->wohnort }}
                            @elseif($rechnung->gebaeude && $rechnung->gebaeude->rechnungsempfaenger)
                                <div class="address-name">{{ $rechnung->gebaeude->rechnungsempfaenger->name }}</div>
                                {{ $rechnung->gebaeude->rechnungsempfaenger->strasse }} {{ $rechnung->gebaeude->rechnungsempfaenger->hausnummer }}<br>
                                {{ $rechnung->gebaeude->rechnungsempfaenger->plz }} {{ $rechnung->gebaeude->rechnungsempfaenger->wohnort }}
                            @else
                                <div class="address-name">-</div>
                            @endif
                        </div>
                    </div>
                </div>
                
                {{-- RECHNUNGSDATEN --}}
                <div class="address-col data">
                    <div class="address-box">
                        <div class="address-label">
                            {{-- ⭐ DYNAMISCH: Rechnungsdaten / Gutschriftdaten --}}
                            @if($rechnung->typ_rechnung === 'gutschrift')
                                Gutschriftdaten / Dati Credito
                            @else
                                Rechnungsdaten / Dati Fattura
                            @endif
                        </div>
                        <div class="address-content">
                            <table class="invoice-data-table">
                                <tr>
                                    <td class="label">Nr. / N.:</td>
                                    <td><strong style="color: #1a4a7c;">{{ $rechnung->rechnungsnummer }}</strong></td>
                                </tr>
                                <tr>
                                    <td class="label">Datum / Data:</td>
                                    <td>{{ $rechnung->rechnungsdatum->format('d.m.Y') }}</td>
                                </tr>
                                @if($rechnung->leistungsdatum)
                                <tr>
                                    <td class="label">Leistung / Prestazione:</td>
                                    <td>{{ $rechnung->leistungsdatum->format('d.m.Y') }}</td>
                                </tr>
                                @endif
                                @if($rechnung->faelligkeitsdatum && $rechnung->typ_rechnung !== 'gutschrift')
                                <tr>
                                    <td class="label">Fällig / Scadenza:</td>
                                    <td>{{ $rechnung->faelligkeitsdatum->format('d.m.Y') }}</td>
                                </tr>
                                @endif
                            </table>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
        
        {{-- ⭐⭐⭐ TITEL DYNAMISCH: RECHNUNG vs. GUTSCHRIFT ⭐⭐⭐ --}}
        <div class="invoice-title {{ $rechnung->typ_rechnung === 'gutschrift' ? 'gutschrift' : '' }}">
            @if($rechnung->typ_rechnung === 'gutschrift')
                GUTSCHRIFT / NOTA DI CREDITO Nr. {{ $rechnung->rechnungsnummer }}
            @else
                RECHNUNG / FATTURA Nr. {{ $rechnung->rechnungsnummer }}
            @endif
        </div>
        
        {{-- ⭐⭐⭐ CAUSALE (Rechnungsgrund) --}}
        @if($rechnung->fattura_causale)
        <div class="causale-box">
            <div class="causale-label">
                @if($rechnung->typ_rechnung === 'gutschrift')
                    Gutschriftgrund / Causale Nota di Credito:
                @else
                    Rechnungsgrund / Causale:
                @endif
            </div>
            <div class="causale-text">
                {{ $rechnung->fattura_causale }}
            </div>
        </div>
        @endif
        
        {{-- POSITIONEN TABELLE --}}
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 8%;">
                        Pos.
                    </th>
                    <th style="width: 42%;">
                        Beschreibung / Descrizione
                    </th>
                    <th class="right" style="width: 10%;">
                        Menge / Quantità
                    </th>
                    <th class="right" style="width: 12%;">
                        Einheit / Unità
                    </th>
                    <th class="right" style="width: 14%;">
                        Einzelpreis / Prezzo
                    </th>
                    <th class="right" style="width: 14%;">
                        Gesamt / Totale
                    </th>
                </tr>
            </thead>
            <tbody>
                @foreach($rechnung->positionen as $pos)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $pos->beschreibung }}</td>
                    <td class="right">{{ number_format($pos->anzahl, 2, ',', '.') }}</td>
                    <td class="right">{{ $pos->einheit ?? 'Stk' }}</td>
                    <td class="right">{{ number_format($pos->einzelpreis, 2, ',', '.') }} €</td>
                    <td class="right">{{ number_format($pos->netto_gesamt, 2, ',', '.') }} €</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        
        {{-- SUMMEN --}}
        <div class="totals-section">
            <table class="totals-table">
                <tr>
                    <td class="label">
                        Zwischensumme (Netto) / Subtotale (Netto)
                    </td>
                    <td class="value">{{ number_format($rechnung->netto_summe, 2, ',', '.') }} €</td>
                </tr>
                
                {{-- MwSt - IMMER anzeigen (auch bei 0%) --}}
                <tr>
                    <td class="label">
                        MwSt / IVA {{ number_format($rechnung->mwst_satz, 1) }}%
                    </td>
                    <td class="value">
                        @if($rechnung->mwst_betrag > 0)
                            {{ number_format($rechnung->mwst_betrag, 2, ',', '.') }} €
                        @else
                            0,00 €
                        @endif
                    </td>
                </tr>
                
                {{-- Reverse Charge Zeile --}}
                @if($rechnung->reverse_charge)
                <tr class="hint-row-yellow">
                    <td colspan="2" style="padding: 2mm; font-size: 7pt;">
                        <strong>! Reverse Charge</strong> - Umkehrung der Steuerschuldnerschaft<br>
                        <span style="font-style: italic; color: #666;">
                            Inversione contabile (Art. 17 DPR 633/72)
                        </span>
                    </td>
                </tr>
                @endif
                
                {{-- Split Payment Zeile --}}
                @if($rechnung->split_payment)
                <tr class="hint-row-blue">
                    <td colspan="2" style="padding: 2mm; font-size: 7pt;">
                        <strong>! Split Payment</strong> - Geteilte Zahlung<br>
                        <span style="font-style: italic; color: #666;">
                            Scissione dei pagamenti (Legge 190/2014)
                        </span>
                    </td>
                </tr>
                @endif
                
                @if($rechnung->ritenuta_betrag > 0)
                <tr>
                    <td class="label">
                        - Quellensteuer / Ritenuta {{ number_format($rechnung->ritenuta_prozent, 1) }}%
                    </td>
                    <td class="value" style="color: #dc2626;">-{{ number_format($rechnung->ritenuta_betrag, 2, ',', '.') }} €</td>
                </tr>
                @endif
                
                <tr class="total">
                    <td class="label">
                        @if($rechnung->typ_rechnung === 'gutschrift')
                            GUTSCHRIFTBETRAG / IMPORTO CREDITO
                        @else
                            GESAMTBETRAG / IMPORTO TOTALE
                        @endif
                    </td>
                    <td class="value">{{ number_format($rechnung->brutto_summe, 2, ',', '.') }} €</td>
                </tr>
            </table>
        </div>
        
        <div style="clear: both;"></div>
        
        {{-- REVERSE CHARGE / SPLIT PAYMENT HINWEISE --}}
        @if($rechnung->reverse_charge || $rechnung->split_payment)
        <div class="info-box warning">
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
        
        {{-- ZAHLUNGSINFORMATIONEN (nur bei Rechnung, nicht bei Gutschrift) --}}
        @if($rechnung->typ_rechnung !== 'gutschrift')
        <div class="info-box {{ ($rechnung->status === 'paid' || $rechnung->istAlsBezahltMarkiert()) ? 'success' : '' }}">
            {{-- ⭐⭐⭐ BEZAHLT: Anderer Titel und Inhalt ⭐⭐⭐ --}}
            @if($rechnung->status === 'paid' || $rechnung->istAlsBezahltMarkiert())
                <h3>BEZAHLT / PAGATO</h3>
                <table style="width: 100%; font-size: 7.5pt;">
                    <tr>
                        <td style="width: 30%; font-weight: bold;">Status:</td>
                        <td style="font-weight: bold; color: #16a34a;">Bezahlt / Pagato</td>
                    </tr>
                    @if($rechnung->bezahlt_am)
                    <tr>
                        <td style="font-weight: bold;">Bezahlt am:</td>
                        <td>{{ $rechnung->bezahlt_am->format('d.m.Y') }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td style="font-weight: bold;">Rechnungsdatum:</td>
                        <td>{{ $rechnung->rechnungsdatum->format('d.m.Y') }}</td>
                    </tr>
                </table>
            @else
                {{-- UNBEZAHLT: Normale Zahlungsinformationen mit Bankdaten --}}
                <h3>Zahlungsinformationen / Informazioni di Pagamento</h3>
                <table style="width: 100%; font-size: 7.5pt;">
                    <tr>
                        <td style="width: 30%; font-weight: bold;">Zahlungsziel:</td>
                        <td>
                            @if($rechnung->faelligkeitsdatum)
                                {{ $rechnung->faelligkeitsdatum->format('d.m.Y') }}
                            @else
                                {{ $rechnung->rechnungsdatum->addDays(30)->format('d.m.Y') }}
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">Zahlungsart:</td>
                        <td>{{ $rechnung->zahlungsart ?? 'Banküberweisung / Bonifico Bancario' }}</td>
                    </tr>
                    @if($unternehmen && $unternehmen->bank_name)
                    <tr>
                        <td style="font-weight: bold;">Bank:</td>
                        <td>{{ $unternehmen->bank_name }}</td>
                    </tr>
                    @endif
                    @if($unternehmen && $unternehmen->iban)
                    <tr>
                        <td style="font-weight: bold;">IBAN:</td>
                        <td><strong>{{ $unternehmen->iban }}</strong></td>
                    </tr>
                    @endif
                    @if($unternehmen && $unternehmen->bic)
                    <tr>
                        <td style="font-weight: bold;">BIC/SWIFT:</td>
                        <td>{{ $unternehmen->bic }}</td>
                    </tr>
                    @endif
                </table>
            @endif
        </div>
        @else
        {{-- Bei Gutschrift: Erstattungshinweis --}}
        <div class="info-box">
            <h3>Erstattungshinweis / Informazioni Rimborso</h3>
            <p style="line-height: 1.4; font-size: 7.5pt;">
                <strong>DE:</strong> Der Gutschriftbetrag wird mit offenen Forderungen verrechnet oder auf Ihr Konto überwiesen.<br>
                <strong>IT:</strong> L'importo della nota di credito sarà compensato con fatture aperte o accreditato sul vostro conto.
            </p>
        </div>
        @endif
        
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
                    <td style="font-weight: bold;">Auftrags-ID:</td>
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
            <strong>{{ $unternehmen->firmenname }}</strong> | 
            {{ $unternehmen->strasse }} {{ $unternehmen->hausnummer }}, {{ $unternehmen->postleitzahl }} {{ $unternehmen->ort }}
            @if($unternehmen->codice_fiscale) | CF: {{ $unternehmen->codice_fiscale }}@endif
            @if($unternehmen->partita_iva) | P.IVA: {{ $unternehmen->partita_iva }}@endif
            @if($unternehmen->telefon) | Tel: {{ $unternehmen->telefon }}@endif
            @if($unternehmen->email) | {{ $unternehmen->email }}@endif
        @else
            Meisterbetrieb Resch GmbH | Musterstraße 123, 39100 Bozen | P.IVA: 12345678901
        @endif
    </div>
    
</body>
</html>
