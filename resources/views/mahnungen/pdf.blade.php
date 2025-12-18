{{-- resources/views/mahnungen/pdf.blade.php --}}
<!DOCTYPE html>
<html lang="{{ $sprache }}">
<head>
    <meta charset="UTF-8">
    <title>{{ $betreff }}</title>
    <style>
        @page {
            margin: 25mm 20mm 25mm 20mm;
        }
        
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.5;
            color: #333;
        }
        
        .header {
            margin-bottom: 30px;
        }
        
        .firma {
            font-size: 9pt;
            color: #666;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
            margin-bottom: 20px;
        }
        
        .empfaenger {
            min-height: 90px;
            margin-bottom: 30px;
        }
        
        .empfaenger-name {
            font-weight: bold;
            font-size: 12pt;
        }
        
        .datum-zeile {
            text-align: right;
            margin-bottom: 20px;
            color: #666;
        }
        
        .betreff {
            font-weight: bold;
            font-size: 13pt;
            margin-bottom: 20px;
            color: #c00;
        }
        
        .mahnstufe-0 .betreff { color: #0066cc; }
        .mahnstufe-1 .betreff { color: #cc6600; }
        .mahnstufe-2 .betreff { color: #cc3300; }
        .mahnstufe-3 .betreff { color: #cc0000; }
        
        .inhalt {
            white-space: pre-wrap;
            margin-bottom: 30px;
        }
        
        .rechnungs-tabelle {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        .rechnungs-tabelle th,
        .rechnungs-tabelle td {
            border: 1px solid #ddd;
            padding: 8px 12px;
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
        
        .bankdaten {
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            padding: 15px;
            margin: 20px 0;
        }
        
        .bankdaten h4 {
            margin: 0 0 10px 0;
            font-size: 11pt;
        }
        
        .bankdaten table {
            width: 100%;
        }
        
        .bankdaten td {
            padding: 3px 0;
        }
        
        .bankdaten .label {
            width: 120px;
            color: #666;
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 9pt;
            color: #666;
        }
        
        .warnung {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            padding: 10px;
            margin: 20px 0;
            font-weight: bold;
        }
        
        .mahnstufe-3 .warnung {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
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
            @if($sprache === 'it')
                {{ config('app.firma_ort', 'Musterstadt') }}, {{ now()->format('d.m.Y') }}
            @else
                {{ config('app.firma_ort', 'Musterstadt') }}, den {{ now()->format('d.m.Y') }}
            @endif
        </div>
    </div>

    {{-- Betreff --}}
    <div class="betreff">
        {{ $betreff }}
    </div>

    {{-- Anrede --}}
    <p>
        @if($sprache === 'it')
            Gentili Signore e Signori,
        @else
            Sehr geehrte Damen und Herren,
        @endif
    </p>

    {{-- Rechnungs-Details Tabelle --}}
    <table class="rechnungs-tabelle">
        <tr>
            <th>@if($sprache === 'it') Fattura n. @else Rechnung Nr. @endif</th>
            <th>@if($sprache === 'it') Data @else Datum @endif</th>
            <th>@if($sprache === 'it') Scadenza @else Fälligkeit @endif</th>
            <th class="text-right">@if($sprache === 'it') Importo @else Betrag @endif</th>
        </tr>
        <tr>
            <td><strong>{{ $rechnung?->volle_rechnungsnummer ?? '-' }}</strong></td>
            <td>{{ $rechnung?->rechnungsdatum?->format('d.m.Y') ?? '-' }}</td>
            <td>{{ $rechnung?->rechnungsdatum?->addDays(30)->format('d.m.Y') ?? '-' }}</td>
            <td class="text-right">{{ number_format($mahnung->rechnungsbetrag, 2, ',', '.') }} €</td>
        </tr>
        @if($mahnung->spesen > 0)
            <tr>
                <td colspan="3">
                    @if($sprache === 'it')
                        Spese di sollecito
                    @else
                        Mahnspesen
                    @endif
                </td>
                <td class="text-right">{{ number_format($mahnung->spesen, 2, ',', '.') }} €</td>
            </tr>
        @endif
        <tr class="summe">
            <td colspan="3">
                <strong>
                    @if($sprache === 'it')
                        Importo totale dovuto
                    @else
                        Gesamtbetrag fällig
                    @endif
                </strong>
            </td>
            <td class="text-right"><strong>{{ number_format($mahnung->gesamtbetrag, 2, ',', '.') }} €</strong></td>
        </tr>
    </table>

    {{-- Überfälligkeit --}}
    <p>
        @if($sprache === 'it')
            <strong>Giorni di ritardo: {{ $mahnung->tage_ueberfaellig }}</strong>
        @else
            <strong>Tage überfällig: {{ $mahnung->tage_ueberfaellig }}</strong>
        @endif
    </p>

    {{-- Haupt-Text (aus Mahnstufe) --}}
    <div class="inhalt">{{ $text }}</div>

    {{-- Warnung bei letzter Mahnung --}}
    @if($mahnung->mahnstufe >= 3)
        <div class="warnung">
            @if($sprache === 'it')
                ⚠️ ATTENZIONE: Questa è la nostra ultima comunicazione stragiudiziale prima di intraprendere azioni legali.
            @else
                ⚠️ ACHTUNG: Dies ist unsere letzte außergerichtliche Mahnung vor Einleitung rechtlicher Schritte.
            @endif
        </div>
    @endif

    {{-- Bankdaten --}}
    <div class="bankdaten">
        <h4>
            @if($sprache === 'it')
                Coordinate bancarie:
            @else
                Bankverbindung:
            @endif
        </h4>
        <table>
            <tr>
                <td class="label">@if($sprache === 'it') Intestatario @else Kontoinhaber @endif:</td>
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
                <td class="label">@if($sprache === 'it') Banca @else Bank @endif:</td>
                <td>{{ config('app.firma_bank', 'Musterbank') }}</td>
            </tr>
            <tr>
                <td class="label">@if($sprache === 'it') Causale @else Verwendungszweck @endif:</td>
                <td><strong>{{ $rechnung?->volle_rechnungsnummer ?? 'Rechnung' }}</strong></td>
            </tr>
        </table>
    </div>

    {{-- Grußformel --}}
    <p>
        @if($sprache === 'it')
            Cordiali saluti
        @else
            Mit freundlichen Grüßen
        @endif
    </p>
    
    <p style="margin-top: 30px;">
        <strong>{{ $firma }}</strong>
    </p>

    {{-- Footer --}}
    <div class="footer">
        {{ $firma }} · 
        {{ config('app.firma_strasse', 'Musterstraße 1') }}, 
        {{ config('app.firma_plz', '12345') }} {{ config('app.firma_ort', 'Musterstadt') }} · 
        @if($sprache === 'it')
            P.IVA: {{ config('app.firma_ustid', 'IT00000000000') }} · 
            Tel: {{ config('app.firma_telefon', '+39 0000 000000') }}
        @else
            USt-IdNr.: {{ config('app.firma_ustid', 'IT00000000000') }} · 
            Tel: {{ config('app.firma_telefon', '+39 0000 000000') }}
        @endif
    </div>

</body>
</html>
