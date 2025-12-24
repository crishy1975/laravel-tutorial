{{-- resources/views/mahnungen/pdf.blade.php --}}
{{-- ZWEISPRACHIGES MAHNUNGS-PDF (Deutsch + Italienisch) --}}
{{-- Daten aus Unternehmensprofil --}}
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Mahnung / Sollecito - {{ $mahnung->rechnungsnummer_anzeige }}</title>
    <style>
        @page {
            margin: 20mm 18mm 25mm 18mm;
        }
        
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.4;
            color: #333;
        }
        
        .header {
            margin-bottom: 10px;
        }
        
        .firma {
            font-size: 8pt;
            color: #666;
            border-bottom: 1px solid #ccc;
            padding-bottom: 3px;
            margin-bottom: 10px;
        }
        
        .empfaenger {
            min-height: 60px;
            margin-bottom: 10px;
        }
        
        .empfaenger-name {
            font-weight: bold;
            font-size: 11pt;
        }
        
        .datum-zeile {
            text-align: right;
            margin-bottom: 10px;
            color: #666;
            font-size: 9pt;
        }
        
        .betreff {
            font-weight: bold;
            font-size: 11pt;
            margin-bottom: 10px;
            padding: 6px 10px;
            background-color: #f8f9fa;
            border-left: 4px solid #dc3545;
        }
        
        .mahnstufe-0 .betreff { border-left-color: #0d6efd; }
        .mahnstufe-1 .betreff { border-left-color: #fd7e14; }
        .mahnstufe-2 .betreff { border-left-color: #dc3545; }
        .mahnstufe-3 .betreff { border-left-color: #6f42c1; }
        
        .sprach-block {
            margin-bottom: 10px;
        }
        
        .inhalt {
            font-size: 10pt;
            line-height: 1.4;
            margin: 0;
            white-space: pre-line;
        }
        
        .rechnungs-tabelle {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
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
        
        .trennlinie {
            border-top: 1px dashed #ccc;
            margin: 12px 0;
        }
        
        .bankdaten {
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            padding: 10px;
            margin: 12px 0;
            font-size: 9pt;
        }
        
        .bankdaten h4 {
            margin: 0 0 6px 0;
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
            margin-top: 15px;
            padding-top: 8px;
            border-top: 1px solid #ddd;
            font-size: 8pt;
            color: #666;
            text-align: center;
        }
        
        .warnung {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 6px 8px;
            margin: 10px 0;
            font-weight: bold;
            color: #721c24;
            font-size: 9pt;
        }
        
        .ueberfaellig-box {
            display: inline-block;
            background: #dc3545;
            color: white;
            padding: 3px 8px;
            font-weight: bold;
            margin: 8px 0;
            font-size: 9pt;
        }
        
        .gruss {
            margin-top: 15px;
        }
    </style>
</head>
<body class="mahnstufe-{{ $mahnung->mahnstufe }}">

    <div class="header">
        {{-- Absender-Zeile aus Unternehmensprofil --}}
        <div class="firma">
            {{ $profil?->firmenname ?? $firma }} · 
            {{ $profil?->strasse ?? '' }} {{ $profil?->hausnummer ?? '' }} · 
            {{ $profil?->postleitzahl ?? '' }} {{ $profil?->ort ?? '' }}
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
        
        {{-- Datum mit Ort aus Profil --}}
        <div class="datum-zeile">
            {{ $profil?->ort ?? 'Bozen' }}, {{ now()->format('d.m.Y') }}
        </div>
    </div>

    {{-- Betreff ZWEISPRACHIG --}}
    <div class="betreff">
        {{ $betreff_de }}<br>
        {{ $betreff_it }}
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
            <td><strong>{{ $mahnung->rechnungsnummer_anzeige }}</strong></td>
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

    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    {{-- DEUTSCHER TEXT --}}
    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    <div class="sprach-block">
        <div class="inhalt">{{ $text_de }}</div>
    </div>

    {{-- Trennlinie --}}
    <div class="trennlinie"></div>

    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    {{-- ITALIENISCHER TEXT --}}
    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    <div class="sprach-block">
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

    {{-- Bankdaten aus Unternehmensprofil (zweisprachig) --}}
    <div class="bankdaten">
        <h4>Bankverbindung / Coordinate bancarie:</h4>
        <table>
            <tr>
                <td class="label">Kontoinhaber / Intestatario:</td>
                <td><strong>{{ $profil?->kontoinhaber ?? $profil?->firmenname ?? $firma }}</strong></td>
            </tr>
            <tr>
                <td class="label">IBAN:</td>
                <td><strong>{{ $profil?->iban_formatiert ?? $profil?->iban ?? '-' }}</strong></td>
            </tr>
            <tr>
                <td class="label">BIC/SWIFT:</td>
                <td>{{ $profil?->bic ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Bank / Banca:</td>
                <td>{{ $profil?->bank_name ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Verwendungszweck / Causale:</td>
                <td><strong>{{ $mahnung->rechnungsnummer_anzeige }}</strong></td>
            </tr>
        </table>
    </div>

    {{-- Grußformel --}}
    <div class="gruss">
        <p style="margin-bottom: 15px;">
            Mit freundlichen Grüßen / Cordiali saluti
        </p>
        <p style="margin: 0;">
            <strong>{{ $profil?->firmenname ?? $firma }}</strong>
        </p>
    </div>

    {{-- Footer aus Unternehmensprofil --}}
    <div class="footer">
        {{ $profil?->firmenname ?? $firma }} · 
        {{ $profil?->strasse ?? '' }} {{ $profil?->hausnummer ?? '' }}, 
        {{ $profil?->postleitzahl ?? '' }} {{ $profil?->ort ?? '' }} · 
        P.IVA/USt-IdNr.: {{ $profil?->partita_iva ?? $profil?->umsatzsteuer_id ?? '-' }} · 
        Tel: {{ $profil?->telefon ?? '-' }}
        @if($profil?->email)
            · {{ $profil->email }}
        @endif
    </div>

</body>
</html>
