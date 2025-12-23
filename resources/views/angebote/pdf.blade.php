<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Angebot {{ $angebot->angebotsnummer }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 8pt;
            line-height: 1.3;
            color: #333;
            padding: 10mm;
        }
        .letterhead {
            margin-bottom: 4mm;
            padding: 3mm;
            border-bottom: 3px solid #1a4a7c;
            background-color: #f8fafc;
        }
        .letterhead-row { display: table; width: 100%; }
        .letterhead-left { display: table-cell; width: 55%; vertical-align: top; }
        .letterhead-right { display: table-cell; width: 45%; vertical-align: top; text-align: right; }
        .company-name { font-size: 12pt; font-weight: bold; margin-bottom: 2mm; color: #1a4a7c; }
        .company-info { font-size: 7.5pt; line-height: 1.4; color: #555; }
        .address-section { margin-bottom: 6mm; }
        .address-row { display: table; width: 100%; }
        .address-col { display: table-cell; vertical-align: top; padding: 0 2mm; width: 33.33%; }
        .address-col:first-child { padding-left: 0; }
        .address-col:last-child { padding-right: 0; }
        .address-box { padding: 2mm; min-height: 24mm; }
        .address-label {
            font-size: 7.5pt;
            font-weight: bold;
            margin-bottom: 1.5mm;
            padding: 1.5mm 2mm;
            background-color: #1a4a7c;
            color: #fff;
        }
        .address-content { font-size: 8pt; line-height: 1.4; padding: 1mm; }
        .address-name { font-weight: bold; font-size: 9pt; margin-bottom: 1mm; color: #1a4a7c; }
        .invoice-data-table { width: 100%; font-size: 7.5pt; }
        .invoice-data-table td { padding: 1mm 0; }
        .invoice-data-table .label { font-weight: bold; width: 40%; color: #555; }
        .invoice-title {
            font-size: 12pt;
            font-weight: bold;
            margin: 3mm 0 2mm 0;
            text-align: center;
            padding: 2mm 0;
            background-color: #1a4a7c;
            color: #fff;
        }
        .causale-box {
            border: 1px solid #1a4a7c;
            padding: 3mm;
            margin: 4mm 0;
            font-size: 8.5pt;
            background-color: #f0f7ff;
        }
        .causale-label { font-weight: bold; margin-bottom: 1mm; font-size: 9pt; color: #1a4a7c; }
        .items-table { width: 100%; border-collapse: collapse; margin: 4mm 0; font-size: 8pt; }
        .items-table th {
            background-color: #1a4a7c;
            color: #fff;
            font-weight: bold;
            padding: 2mm;
            text-align: left;
            font-size: 7.5pt;
        }
        .items-table th.right, .items-table td.right { text-align: right; }
        .items-table td { padding: 1.5mm 2mm; border-bottom: 1px solid #ddd; }
        .items-table tr:nth-child(even) td { background-color: #f8fafc; }
        .items-table tr:last-child td { border-bottom: 2px solid #1a4a7c; }
        .totals-section { margin-top: 4mm; float: right; width: 50%; }
        .totals-table { width: 100%; border-collapse: collapse; border: 1px solid #ccc; }
        .totals-table td { padding: 1.5mm 2mm; border-bottom: 1px solid #ddd; }
        .totals-table .label { font-size: 7.5pt; color: #555; }
        .totals-table .value { text-align: right; font-weight: bold; font-size: 8pt; }
        .totals-table tr.total { background-color: #1a4a7c; }
        .totals-table tr.total td { padding: 2.5mm 2mm; font-size: 10pt; border-bottom: none; color: #fff; }
        .totals-table tr.total .label { color: #fff; }
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
        .validity-box {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            padding: 3mm;
            margin: 4mm 0;
            font-size: 8pt;
        }
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
    </style>
</head>
<body>
    <div class="content">
        <div class="letterhead">
            <div class="letterhead-row">
                <div class="letterhead-left">
                    <div class="company-name">{{ $unternehmen->firmenname ?? 'Meisterbetrieb Resch GmbH' }}</div>
                    <div class="company-info">
                        @if($unternehmen)
                            {{ $unternehmen->strasse }} {{ $unternehmen->hausnummer }}<br>
                            {{ $unternehmen->postleitzahl }} {{ $unternehmen->ort }}<br>
                            @if($unternehmen->telefon)Tel: {{ $unternehmen->telefon }}<br>@endif
                            @if($unternehmen->email){{ $unternehmen->email }}@endif
                        @endif
                    </div>
                </div>
                <div class="letterhead-right">
                    <div style="font-size: 14pt; font-weight: bold; color: #1a4a7c;">ANGEBOT</div>
                    <div style="font-size: 10pt; color: #666;">OFFERTA</div>
                    <div style="font-size: 11pt; margin-top: 2mm;">{{ $angebot->angebotsnummer }}</div>
                </div>
            </div>
        </div>

        <div class="address-section">
            <div class="address-row">
                <div class="address-col">
                    <div class="address-box">
                        <div class="address-label">Empfaenger / Destinatario</div>
                        <div class="address-content">
                            <div class="address-name">{{ $angebot->empfaenger_name }}</div>
                            {{ $angebot->empfaenger_strasse }} {{ $angebot->empfaenger_hausnummer }}<br>
                            {{ $angebot->empfaenger_plz }} {{ $angebot->empfaenger_ort }}
                            @if($angebot->empfaenger_steuernummer)
                                <br><small>MwSt-Nr.: {{ $angebot->empfaenger_steuernummer }}</small>
                            @endif
                        </div>
                    </div>
                </div>

                @if($angebot->geb_codex || $angebot->geb_name)
                <div class="address-col">
                    <div class="address-box">
                        <div class="address-label">Objekt / Edificio</div>
                        <div class="address-content">
                            @if($angebot->geb_codex)
                                <div style="font-weight: bold; color: #1a4a7c;">{{ $angebot->geb_codex }}</div>
                            @endif
                            <div class="address-name">{{ $angebot->geb_name }}</div>
                            {{ $angebot->geb_strasse }}<br>
                            {{ $angebot->geb_plz }} {{ $angebot->geb_ort }}
                        </div>
                    </div>
                </div>
                @endif

                <div class="address-col">
                    <div class="address-box">
                        <div class="address-label">Angebotsdaten / Dati Offerta</div>
                        <div class="address-content">
                            <table class="invoice-data-table">
                                <tr>
                                    <td class="label">Angebots-Nr.:</td>
                                    <td><strong>{{ $angebot->angebotsnummer }}</strong></td>
                                </tr>
                                <tr>
                                    <td class="label">Datum:</td>
                                    <td>{{ $angebot->datum->format('d.m.Y') }}</td>
                                </tr>
                                @if($angebot->gueltig_bis)
                                <tr>
                                    <td class="label">Gueltig bis:</td>
                                    <td><strong>{{ $angebot->gueltig_bis->format('d.m.Y') }}</strong></td>
                                </tr>
                                @endif
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="invoice-title">ANGEBOT / OFFERTA</div>

        @if($angebot->titel)
        <div class="causale-box">
            <div class="causale-label">Betreff / Oggetto:</div>
            <div>{{ $angebot->titel }}</div>
        </div>
        @endif

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 8%;">Pos.</th>
                    <th style="width: 42%;">Beschreibung / Descrizione</th>
                    <th class="right" style="width: 10%;">Menge</th>
                    <th class="right" style="width: 12%;">Einheit</th>
                    <th class="right" style="width: 14%;">Einzelpreis</th>
                    <th class="right" style="width: 14%;">Gesamt</th>
                </tr>
            </thead>
            <tbody>
                @foreach($angebot->positionen as $pos)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $pos->beschreibung }}</td>
                    <td class="right">{{ number_format($pos->anzahl, 2, ',', '.') }}</td>
                    <td class="right">{{ $pos->einheit ?? 'Stk' }}</td>
                    <td class="right">{{ number_format($pos->einzelpreis, 2, ',', '.') }} EUR</td>
                    <td class="right">{{ number_format($pos->gesamtpreis, 2, ',', '.') }} EUR</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals-section">
            <table class="totals-table">
                <tr>
                    <td class="label">Zwischensumme (Netto)</td>
                    <td class="value">{{ number_format($angebot->netto_summe, 2, ',', '.') }} EUR</td>
                </tr>
                <tr>
                    <td class="label">MwSt / IVA {{ number_format($angebot->mwst_satz, 1) }}%</td>
                    <td class="value">{{ number_format($angebot->mwst_betrag, 2, ',', '.') }} EUR</td>
                </tr>
                <tr class="total">
                    <td class="label">GESAMTBETRAG / IMPORTO TOTALE</td>
                    <td class="value">{{ number_format($angebot->brutto_summe, 2, ',', '.') }} EUR</td>
                </tr>
            </table>
        </div>

        <div style="clear: both;"></div>

        @if($angebot->gueltig_bis)
        <div class="validity-box">
            <strong>Hinweis / Nota:</strong><br>
            Dieses Angebot ist gueltig bis {{ $angebot->gueltig_bis->format('d.m.Y') }}.<br>
            <em>Questa offerta e valida fino al {{ $angebot->gueltig_bis->format('d.m.Y') }}.</em>
        </div>
        @endif

        <div class="info-box">
            <h3>Zahlungsbedingungen / Condizioni di Pagamento</h3>
            <p style="line-height: 1.5;">
                <strong>DE:</strong> Bei Auftragserteilung wird eine Anzahlung von 30% faellig.<br>
                <strong>IT:</strong> Al conferimento dell'ordine e dovuto un acconto del 30%.
            </p>
        </div>
    </div>

    <div class="footer">
        @if($unternehmen)
            <strong>{{ $unternehmen->firmenname }}</strong> |
            {{ $unternehmen->strasse }} {{ $unternehmen->hausnummer }}, {{ $unternehmen->postleitzahl }} {{ $unternehmen->ort }}
            @if($unternehmen->codice_fiscale) | CF: {{ $unternehmen->codice_fiscale }}@endif
            @if($unternehmen->partita_iva) | P.IVA: {{ $unternehmen->partita_iva }}@endif
        @endif
    </div>
</body>
</html>
