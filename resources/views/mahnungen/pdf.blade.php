{{-- resources/views/mahnungen/pdf.blade.php --}}
{{-- ZWEISPRACHIGES MAHNUNGS-PDF (Deutsch + Italienisch) --}}
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Mahnung / Sollecito - {{ $rechnung?->volle_rechnungsnummer ?? '' }}</title>
    <style>
        @page {
            margin: 20mm 18mm 20mm 18mm;
        }
        
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.4;
            color: #333;
        }
        
        .header {
            margin-bottom: 20px;
        }
        
        .firma {
            font-size: 8pt;
            color: #666;
            border-bottom: 1px solid #ccc;
            padding-bottom: 4px;
            margin-bottom: 15px;
        }
        
        .empfaenger {
            min-height: 70px;
            margin-bottom: 15px;
        }
        
        .empfaenger-name {
            font-weight: bold;
            font-size: 11pt;
        }
        
        .datum-zeile {
            text-align: right;
            margin-bottom: 15px;
            color: #666;
            font-size: 9pt;
        }
        
        .betreff {
            font-weight: bold;
            font-size: 12pt;
            margin-bottom: 15px;
            padding: 8px 12px;
            background-color: #f8f9fa;
            border-left: 4px solid #dc3545;
        }
        
        .mahnstufe-0 .betreff { border-left-color: #0d6efd; }
        .mahnstufe-1 .betreff { border-left-color: #fd7e14; }
        .mahnstufe-2 .betreff { border-left-color: #dc3545; }
        .mahnstufe-3 .betreff { border-left-color: #6f42c1; }
        
        .sprach-block {
            margin-bottom: 20px;
        }
        
        .sprach-titel {
            font-size: 9pt;
            font-weight: bold;
            color: #666;
            border-bottom: 1px solid #ddd;
            padding-bottom: 3px;
            margin-bottom: 10px;
        }
        
        .inhalt {
            white-space: pre-wrap;
            font-size: 10pt;
            line-height: 1.5;
        }
        
        .rechnungs-tabelle {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 9pt;
        }
        
        .rechnungs-tabelle th,
        .rechnungs-tabelle td {
            border: 1px solid #ddd;
            padding: 6px 10px;
            text-align: left;
        }
        
        .rechnungs-tabelle th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        
        .rechnungs-tabelle .text-right {
            text-align: right;
        }
        
        .rechnungs-tabelle .summe {
            font-weight: bold;
            background-color: #fff3cd;
        }
        
        .zwei-spalten {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }
        
        .spalte {
            display: table-cell;
            width: 48%;
            vertical-align: top;
            padding-right: 2%;
        }
        
        .spalte:last-child {
            padding-right: 0;
            padding-left: 2%;
        }
        
        .trennlinie {
            border-top: 2px dashed #ccc;
            margin: 20px 0;
            position: relative;
        }
        
        .trennlinie-text {
            position: absolute;
            top: -10px;
            left: 50%;
            transform: translateX(-50%);
            background: white;
            padding: 0 10px;
            font-size: 8pt;
            color: #999;
        }
        
        .bankdaten {
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            padding: 12px;
            margin: 15px 0;
            font-size: 9pt;
        }
        
        .bankdaten h4 {
            margin: 0 0 8px 0;
            font-size: 10pt;
        }
        
        .bankdaten table {
            width: 100%;
        }
        
        .bankdaten td {
            padding: 2px 0;
        }
        
        .bankdaten .label {
            width: 140px;
            color: #666;
        }
        
        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            font-size: 8pt;
            color: #666;
            text-align: center;
        }
        
        .warnung {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 8px;
            margin: 15px 0;
            font-weight: bold;
            color: #721c24;
            font-size: 9pt;
        }
        
        .ueberfaellig-box {
            display: inline-block;
            background: #dc3545;
            color: white;
            padding: 4px 10px;
            font-weight: bold;
            margin: 10px 0;
            font-size: 10pt;
        }
        
        .gruss {
            margin-top: 20px;
        }
    </style>
</head>
<body class="mahnstufe-{{ $mahnung->mahnstufe }}">

    <div class="header">
        {{-- Absender-Zeile --}}
        <div class="firma">
            {{ $firma }} · {{ config('app.firma_strasse', 'Musterstraße 1') }} · {{ config('app.firma_plz', '12345') }} {{ config('app.firma_ort', 'Musterstadt') }}
        </div>
        
        {{-- Empfänger --}}
        <div class="empfaenger">
            <div class="empfaenger-name">{{ $empfaenger?->name }}</div>
            @if($empfaenger?->zusatz)
                <div>{{ $empfaenger->zusatz }}</div>
            @endif
            <div>{{ $empfaenger?->strasse }} {{ $empfaenger?->hausnummer }}</div>
            <div>{{ $empfaenger?->plz }} {{ $empfaenger?->wohnort }}</div>
            @if($empfaenger?->land && $empfaenger->land !== 'IT')
                <div>{{ $empfaenger->land }}</div>
            @endif
        </div>
        
        {{-- Datum --}}
        <div class="datum-zeile">
            {{ config('app.firma_ort', 'Musterstadt') }}, {{ now()->format('d.m.Y') }}
        </div>
    </div>

    {{-- Betreff ZWEISPRACHIG --}}
    <div class="betreff">
        🇩🇪 {{ $betreff_de }}<br>
        🇮🇹 {{ $betreff_it }}
    </div>

    {{-- Überfälligkeit --}}
    <div class="ueberfaellig-box">
        {{ $mahnung->tage_ueberfaellig }} Tage überfällig / {{ $mahnung->tage_ueberfaellig }} giorni di ritardo
    </div>

    {{-- Rechnungs-Details Tabelle (zweisprachig) --}}
    <table class="rechnungs-tabelle">
        <tr>
            <th>Rechnung Nr. / Fattura n.</th>
            <th>Datum / Data</th>
            <th>Fälligkeit / Scadenza</th>
            <th class="text-right">Betrag / Importo</th>
        </tr>
        <tr>
            <td><strong>{{ $rechnung?->volle_rechnungsnummer ?? '-' }}</strong></td>
            <td>{{ $rechnung?->rechnungsdatum?->format('d.m.Y') ?? '-' }}</td>
            <td>{{ $rechnung?->faelligkeitsdatum?->format('d.m.Y') ?? ($rechnung?->rechnungsdatum?->addDays(30)->format('d.m.Y') ?? '-') }}</td>
            <td class="text-right">{{ number_format($mahnung->rechnungsbetrag, 2, ',', '.') }} €</td>
        </tr>
        @if($mahnung->spesen > 0)
            <tr>
                <td colspan="3">Mahnspesen / Spese di sollecito</td>
                <td class="text-right">{{ number_format($mahnung->spesen, 2, ',', '.') }} €</td>
            </tr>
        @endif
        <tr class="summe">
            <td colspan="3"><strong>Gesamtbetrag fällig / Importo totale dovuto</strong></td>
            <td class="text-right"><strong>{{ number_format($mahnung->gesamtbetrag, 2, ',', '.') }} €</strong></td>
        </tr>
    </table>

    {{-- ════════════════════════════════════════════════════════════════ --}}
    {{-- DEUTSCHER TEXT --}}
    {{-- ════════════════════════════════════════════════════════════════ --}}
    <div class="sprach-block">
        <div class="sprach-titel">🇩🇪 DEUTSCH</div>
        <p>Sehr geehrte Damen und Herren,</p>
        <div class="inhalt">{{ $text_de }}</div>
    </div>

    {{-- Trennlinie --}}
    <div class="trennlinie">
        <span class="trennlinie-text">───────────────────</span>
    </div>

    {{-- ════════════════════════════════════════════════════════════════ --}}
    {{-- ITALIENISCHER TEXT --}}
    {{-- ════════════════════════════════════════════════════════════════ --}}
    <div class="sprach-block">
        <div class="sprach-titel">🇮🇹 ITALIANO</div>
        <p>Gentili Signore e Signori,</p>
        <div class="inhalt">{{ $text_it }}</div>
    </div>

    {{-- Warnung bei letzter Mahnung --}}
    @if($mahnung->mahnstufe >= 3)
        <div class="warnung">
            ⚠️ ACHTUNG / ATTENZIONE:<br>
            Dies ist unsere letzte außergerichtliche Mahnung vor Einleitung rechtlicher Schritte.<br>
            Questa è la nostra ultima comunicazione stragiudiziale prima di intraprendere azioni legali.
        </div>
    @endif

    {{-- Bankdaten (zweisprachig) --}}
    <div class="bankdaten">
        <h4>Bankverbindung / Coordinate bancarie:</h4>
        <table>
            <tr>
                <td class="label">Kontoinhaber / Intestatario:</td>
                <td><strong>{{ config('app.firma_name', $firma) }}</strong></td>
            </tr>
            <tr>
                <td class="label">IBAN:</td>
                <td><strong>{{ config('app.firma_iban', 'IT00 X000 0000 0000 0000 0000 000') }}</strong></td>
            </tr>
            <tr>
                <td class="label">BIC/SWIFT:</td>
                <td>{{ config('app.firma_bic', 'XXXXXXXX') }}</td>
            </tr>
            <tr>
                <td class="label">Bank / Banca:</td>
                <td>{{ config('app.firma_bank', 'Musterbank') }}</td>
            </tr>
            <tr>
                <td class="label">Verwendungszweck / Causale:</td>
                <td><strong>{{ $rechnung?->volle_rechnungsnummer ?? 'Rechnung' }}</strong></td>
            </tr>
        </table>
    </div>

    {{-- Grußformel --}}
    <div class="gruss">
        <p>
            Mit freundlichen Grüßen / Cordiali saluti
        </p>
        <p style="margin-top: 20px;">
            <strong>{{ $firma }}</strong>
        </p>
    </div>

    {{-- Footer --}}
    <div class="footer">
        {{ $firma }} · 
        {{ config('app.firma_strasse', 'Musterstraße 1') }}, 
        {{ config('app.firma_plz', '12345') }} {{ config('app.firma_ort', 'Musterstadt') }} · 
        P.IVA/USt-IdNr.: {{ config('app.firma_ustid', 'IT00000000000') }} · 
        Tel: {{ config('app.firma_telefon', '+39 0000 000000') }}
    </div>

</body>
</html>
