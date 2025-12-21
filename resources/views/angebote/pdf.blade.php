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
            color: #2d3748;
            padding: 8mm;
        }
        .header {
            display: table;
            width: 100%;
            margin-bottom: 4mm;
            padding-bottom: 3mm;
            border-bottom: 2px solid #38b2ac;
        }
        .header-left {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .header-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            text-align: right;
        }
        .logo {
            max-width: 180px;
            max-height: 50px;
            margin-bottom: 2mm;
        }
        .company-name {
            font-size: 12pt;
            font-weight: bold;
            color: #2c7a7b;
            margin-bottom: 1mm;
        }
        .company-info {
            font-size: 7pt;
            color: #718096;
            line-height: 1.4;
        }
        .title-box {
            background-color: #38b2ac;
            color: white;
            padding: 4mm 5mm;
            margin-bottom: 4mm;
            text-align: center;
        }
        .title-main {
            font-size: 14pt;
            font-weight: bold;
            letter-spacing: 1px;
        }
        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 4mm;
        }
        .info-cell {
            display: table-cell;
            vertical-align: top;
        }
        .info-cell.empfaenger {
            width: 50%;
            padding-right: 4mm;
        }
        .info-cell.meta {
            width: 50%;
            padding-left: 4mm;
        }
        .address-card {
            border: 1px solid #e2e8f0;
            border-left: 3px solid #38b2ac;
            padding: 2mm;
            background-color: #f7fafc;
            min-height: 22mm;
        }
        .address-title {
            font-size: 6pt;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #38b2ac;
            font-weight: bold;
            margin-bottom: 1mm;
        }
        .address-name {
            font-size: 9pt;
            font-weight: bold;
            color: #2d3748;
        }
        .address-text {
            font-size: 7pt;
            color: #4a5568;
            line-height: 1.4;
        }
        .meta-table {
            width: 100%;
            border-collapse: collapse;
        }
        .meta-table td {
            padding: 1.5mm 2mm;
            background-color: #edf2f7;
            border-bottom: 1px solid #d1d5db;
        }
        .meta-table tr:last-child td {
            border-bottom: 2px solid #38b2ac;
        }
        .meta-label {
            font-size: 6pt;
            color: #718096;
            text-transform: uppercase;
        }
        .meta-value {
            font-size: 9pt;
            font-weight: bold;
            color: #2d3748;
        }
        .betreff {
            background-color: #e6fffa;
            border-left: 3px solid #38b2ac;
            padding: 2mm 3mm;
            margin-bottom: 3mm;
            font-size: 8pt;
        }
        .betreff-label {
            font-size: 6pt;
            color: #38b2ac;
            text-transform: uppercase;
            font-weight: bold;
        }
        .einleitung {
            margin-bottom: 3mm;
            padding: 2mm;
            font-size: 8pt;
            line-height: 1.4;
            color: #4a5568;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 3mm;
        }
        .items-table th {
            background-color: #2c7a7b;
            color: white;
            font-weight: bold;
            padding: 2mm 1.5mm;
            text-align: left;
            font-size: 7pt;
        }
        .items-table th.right { text-align: right; }
        .items-table td {
            padding: 1.5mm;
            border-bottom: 1px solid #e2e8f0;
            font-size: 8pt;
        }
        .items-table td.right { text-align: right; }
        .items-table tr:nth-child(even) td {
            background-color: #f7fafc;
        }
        .totals-wrapper {
            display: table;
            width: 100%;
        }
        .totals-spacer {
            display: table-cell;
            width: 55%;
        }
        .totals-box {
            display: table-cell;
            width: 45%;
        }
        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }
        .totals-table tr.total td {
            background-color: #38b2ac;
            color: white;
            font-size: 10pt;
            padding: 2mm;
        }
        .totals-table tr.total .label {
            color: white;
        }
        .totals-table .value {
            text-align: right;
            font-weight: bold;
        }
        .mwst-hinweis {
            text-align: right;
            font-size: 6pt;
            color: #718096;
            font-style: italic;
            margin-top: 1mm;
        }
        .validity {
            margin-top: 4mm;
            padding: 2mm;
            background-color: #fefcbf;
            border: 1px solid #ecc94b;
            font-size: 7pt;
            text-align: center;
        }
        .validity strong {
            color: #b7791f;
        }
        .notes {
            margin-top: 3mm;
            padding: 2mm;
            background-color: #f7fafc;
            border: 1px solid #e2e8f0;
            font-size: 7pt;
        }
        .notes-title {
            font-weight: bold;
            color: #2c7a7b;
            margin-bottom: 1mm;
            font-size: 7pt;
        }
        .footer {
            position: fixed;
            bottom: 5mm;
            left: 8mm;
            right: 8mm;
            font-size: 6pt;
            text-align: center;
            color: #a0aec0;
            padding-top: 2mm;
            border-top: 1px solid #e2e8f0;
        }
    </style>
</head>
<body>
    @php
        $empfaengerEmail = $angebot->adresse->email ?? $angebot->empfaenger_email ?? null;
    @endphp

    <div class="header">
        <div class="header-left">
            @php
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
                            break;
                        }
                    }
                }
            @endphp
            
            @if($logoPath)
                <img src="{{ $logoPath }}" alt="Logo" class="logo">
            @endif
            
            <div class="company-name">{{ $unternehmen->firmenname ?? 'Resch GmbH' }}</div>
            <div class="company-info">
                @if($unternehmen)
                    {{ $unternehmen->strasse }} {{ $unternehmen->hausnummer }}, {{ $unternehmen->postleitzahl }} {{ $unternehmen->ort }}<br>
                    @if($unternehmen->telefon)Tel: {{ $unternehmen->telefon }} | @endif
                    @if($unternehmen->email){{ $unternehmen->email }}@endif
                @endif
            </div>
        </div>
        <div class="header-right">
            <div style="font-size: 7pt; color: #718096;">
                @if($unternehmen)
                    @if($unternehmen->partita_iva)P.IVA: {{ $unternehmen->partita_iva }}@endif
                    @if($unternehmen->codice_fiscale) | CF: {{ $unternehmen->codice_fiscale }}@endif
                @endif
            </div>
        </div>
    </div>

    <div class="title-box">
        <div class="title-main">ANGEBOT / OFFERTA</div>
    </div>

    <div class="info-grid">
        <div class="info-cell empfaenger">
            <div class="address-card">
                <div class="address-title">Empfaenger / Destinatario</div>
                <div class="address-name">{{ $angebot->empfaenger_name }}</div>
                <div class="address-text">
                    {{ $angebot->empfaenger_strasse }} {{ $angebot->empfaenger_hausnummer }}<br>
                    {{ $angebot->empfaenger_plz }} {{ $angebot->empfaenger_ort }}
                    @if($empfaengerEmail)
                        <br>{{ $empfaengerEmail }}
                    @endif
                    @if($angebot->empfaenger_steuernummer)
                        <br>MwSt-Nr.: {{ $angebot->empfaenger_steuernummer }}
                    @endif
                </div>
            </div>
        </div>
        <div class="info-cell meta">
            <table class="meta-table">
                <tr>
                    <td>
                        <div class="meta-label">Angebots-Nr. / N. Offerta</div>
                        <div class="meta-value">{{ $angebot->angebotsnummer }}</div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="meta-label">Datum / Data</div>
                        <div class="meta-value">{{ $angebot->datum->format('d.m.Y') }}</div>
                    </td>
                </tr>
                @if($angebot->gueltig_bis)
                <tr>
                    <td>
                        <div class="meta-label">Gueltig bis / Valido fino al</div>
                        <div class="meta-value">{{ $angebot->gueltig_bis->format('d.m.Y') }}</div>
                    </td>
                </tr>
                @endif
            </table>
        </div>
    </div>

    @if($angebot->titel)
    <div class="betreff">
        <div class="betreff-label">Betreff / Oggetto</div>
        {{ $angebot->titel }}
    </div>
    @endif

    @if($angebot->einleitung)
    <div class="einleitung">
        {!! nl2br(e($angebot->einleitung)) !!}
    </div>
    @endif

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 6%;">Pos.</th>
                <th style="width: 52%;">Beschreibung / Descrizione</th>
                <th class="right" style="width: 12%;">Menge / Qta</th>
                <th class="right" style="width: 15%;">Preis / Prezzo</th>
                <th class="right" style="width: 15%;">Gesamt / Totale</th>
            </tr>
        </thead>
        <tbody>
            @foreach($angebot->positionen as $pos)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $pos->beschreibung }}</td>
                <td class="right">{{ number_format($pos->anzahl, 2, ',', '.') }} {{ $pos->einheit ?? 'Stk' }}</td>
                <td class="right">{{ number_format($pos->einzelpreis, 2, ',', '.') }} EUR</td>
                <td class="right">{{ number_format($pos->gesamtpreis, 2, ',', '.') }} EUR</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals-wrapper">
        <div class="totals-spacer"></div>
        <div class="totals-box">
            <table class="totals-table">
                <tr class="total">
                    <td class="label">GESAMTBETRAG / TOTALE</td>
                    <td class="value">{{ number_format($angebot->netto_summe, 2, ',', '.') }} EUR</td>
                </tr>
            </table>
            <div class="mwst-hinweis">
                Preise ohne MwSt. / Prezzi senza IVA.
            </div>
        </div>
    </div>

    @if($angebot->gueltig_bis)
    <div class="validity">
        <strong>Gueltig bis {{ $angebot->gueltig_bis->format('d.m.Y') }}</strong> |
        <em>Valido fino al {{ $angebot->gueltig_bis->format('d.m.Y') }}</em>
    </div>
    @endif

    @if($angebot->bemerkung_kunde)
    <div class="notes">
        <div class="notes-title">Bemerkungen / Note</div>
        {{ $angebot->bemerkung_kunde }}
    </div>
    @endif

    <div class="footer">
        @if($unternehmen)
            {{ $unternehmen->firmenname }} | {{ $unternehmen->strasse }} {{ $unternehmen->hausnummer }}, {{ $unternehmen->postleitzahl }} {{ $unternehmen->ort }}
            @if($unternehmen->telefon) | {{ $unternehmen->telefon }}@endif
            @if($unternehmen->email) | {{ $unternehmen->email }}@endif
        @endif
    </div>
</body>
</html>
