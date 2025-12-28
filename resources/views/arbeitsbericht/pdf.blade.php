<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Arbeitsbericht / Rapporto di lavoro - {{ $bericht->adresse_name }}</title>
    <style>
        /* ═══════════════════════════════════════════════════════════════
           ARBEITSBERICHT PDF - Stil wie Rechnung
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
        
        /* ═══════════════════════════════════════════════════════════════
           BRIEFKOPF - wie bei Rechnung
        ═══════════════════════════════════════════════════════════════ */
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
        
        /* ═══════════════════════════════════════════════════════════════
           ZWEISPRACHIG - gleiche Größe, zwei Farben
        ═══════════════════════════════════════════════════════════════ */
        .bilingual {
            display: block;
        }
        
        .bilingual .de {
            color: #000;
            font-weight: bold;
        }
        
        .bilingual .it {
            color: #555;
            font-weight: bold;
        }
        
        /* Inline Version */
        .bi-inline .de {
            color: #000;
        }
        
        .bi-inline .it {
            color: #555;
        }
        
        /* ═══════════════════════════════════════════════════════════════
           TITEL
        ═══════════════════════════════════════════════════════════════ */
        .document-title {
            font-size: 11pt;
            font-weight: bold;
            margin: 4mm 0;
            text-align: center;
            padding: 3mm 0;
            background-color: #1a4a7c;
            color: #fff;
            letter-spacing: 1px;
        }
        
        .document-title .it {
            color: #b8d4f0;
            font-weight: bold;
        }
        
        /* Status Badge */
        .status-badge {
            display: inline-block;
            padding: 1mm 3mm;
            font-size: 7pt;
            background-color: #16a34a;
            color: #fff;
            border-radius: 2mm;
            margin-left: 3mm;
        }
        
        /* ═══════════════════════════════════════════════════════════════
           KUNDENADRESSE
        ═══════════════════════════════════════════════════════════════ */
        .address-section {
            margin-bottom: 5mm;
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
            width: 60%;
        }
        
        .address-col:last-child {
            padding-right: 0;
            width: 40%;
        }
        
        .address-box {
            padding: 2mm;
            min-height: 20mm;
        }
        
        .address-label {
            font-size: 7.5pt;
            font-weight: bold;
            margin-bottom: 1.5mm;
            padding: 1.5mm 2mm;
            background-color: #1a4a7c;
            color: #fff;
        }
        
        .address-label .it {
            color: #b8d4f0;
            font-weight: normal;
        }
        
        .address-content {
            font-size: 8pt;
            line-height: 1.4;
            padding: 2mm;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
        }
        
        .address-name {
            font-weight: bold;
            font-size: 9pt;
            margin-bottom: 1mm;
            color: #1a4a7c;
        }
        
        /* Rechnungsdaten */
        .invoice-data-table {
            width: 100%;
            font-size: 7.5pt;
        }
        
        .invoice-data-table td {
            padding: 1mm 0;
        }
        
        .invoice-data-table .label {
            font-weight: bold;
            width: 50%;
            color: #555;
        }
        
        .invoice-data-table .value {
            font-weight: bold;
            color: #1a4a7c;
        }
        
        /* ═══════════════════════════════════════════════════════════════
           POSITIONEN TABELLE
        ═══════════════════════════════════════════════════════════════ */
        .section-title {
            font-size: 8pt;
            font-weight: bold;
            margin-bottom: 0;
            padding: 2mm;
            background-color: #1a4a7c;
            color: #fff;
        }
        
        .section-title .it {
            color: #b8d4f0;
            font-weight: normal;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4mm;
            font-size: 8pt;
        }
        
        .items-table th {
            background-color: #e2e8f0;
            color: #333;
            font-weight: bold;
            padding: 2mm;
            text-align: left;
            font-size: 7.5pt;
            border-bottom: 1px solid #1a4a7c;
        }
        
        .items-table th .it {
            color: #555;
            font-weight: normal;
        }
        
        .items-table th.right,
        .items-table td.right {
            text-align: right;
        }
        
        .items-table th.center,
        .items-table td.center {
            text-align: center;
        }
        
        .items-table td {
            padding: 2mm;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .items-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        
        /* Summenzeile */
        .items-table tfoot td {
            background-color: #1a4a7c !important;
            color: #fff;
            font-weight: bold;
            font-size: 9pt;
            padding: 2.5mm;
            border: none;
        }
        
        .items-table tfoot .it {
            color: #b8d4f0;
        }
        
        /* ═══════════════════════════════════════════════════════════════
           INFO BOXEN
        ═══════════════════════════════════════════════════════════════ */
        .info-box {
            border: 1px solid #e2e8f0;
            padding: 3mm;
            margin: 3mm 0;
            font-size: 8pt;
            background-color: #f8fafc;
        }
        
        .info-box.highlight {
            background-color: #f0f7ff;
            border-color: #1a4a7c;
            border-left: 3px solid #1a4a7c;
        }
        
        .info-box h3 {
            font-size: 8pt;
            color: #1a4a7c;
            margin-bottom: 2mm;
            padding-bottom: 1mm;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .info-box h3 .it {
            color: #555;
            font-weight: normal;
        }
        
        .info-box-content {
            line-height: 1.4;
        }
        
        /* Termin Box */
        .termin-box {
            text-align: center;
            padding: 3mm;
        }
        
        .termin-datum {
            font-size: 14pt;
            font-weight: bold;
            color: #1a4a7c;
        }
        
        /* ═══════════════════════════════════════════════════════════════
           UNTERSCHRIFTEN
        ═══════════════════════════════════════════════════════════════ */
        .signatures-section {
            margin-top: 6mm;
            page-break-inside: avoid;
        }
        
        .signatures-row {
            display: table;
            width: 100%;
        }
        
        .signature-col {
            display: table-cell;
            width: 45%;
            vertical-align: top;
            text-align: center;
            padding: 2mm;
        }
        
        .signature-spacer {
            display: table-cell;
            width: 10%;
        }
        
        .signature-box {
            border: 1px solid #e2e8f0;
            padding: 3mm;
            background-color: #f8fafc;
            min-height: 25mm;
        }
        
        .signature-label {
            font-size: 7pt;
            color: #555;
            margin-bottom: 2mm;
        }
        
        .signature-label .it {
            color: #777;
        }
        
        .signature-image {
            max-height: 50px;
            max-width: 180px;
            margin: 2mm 0;
        }
        
        .signature-line {
            border-top: 1px solid #333;
            margin-top: 2mm;
            padding-top: 2mm;
        }
        
        .signature-name {
            font-size: 9pt;
            font-weight: bold;
            color: #333;
        }
        
        .signature-role {
            font-size: 7pt;
            color: #555;
        }
        
        .signature-role .it {
            color: #777;
        }
        
        .signature-date {
            font-size: 6.5pt;
            color: #888;
            margin-top: 1mm;
        }
        
        /* ═══════════════════════════════════════════════════════════════
           FOOTER
        ═══════════════════════════════════════════════════════════════ */
        .footer {
            position: fixed;
            bottom: 5mm;
            left: 10mm;
            right: 10mm;
            text-align: center;
            font-size: 6.5pt;
            color: #888;
            padding-top: 2mm;
            border-top: 1px solid #e2e8f0;
        }
    </style>
</head>
<body>
    <!-- ═══════════════════════════════════════════════════════════════
         BRIEFKOPF - wie bei Rechnung
    ═══════════════════════════════════════════════════════════════ -->
    <div class="letterhead">
        <div class="letterhead-row">
            <!-- Logo links -->
            <div class="letterhead-left">
                @if(isset($profil) && $profil->logo_pfad)
                    @php
                        $logoPath = public_path($profil->logo_pfad);
                        if (!file_exists($logoPath)) {
                            $logoPath = storage_path('app/public/' . $profil->logo_pfad);
                        }
                    @endphp
                    @if(file_exists($logoPath))
                        <img src="{{ $logoPath }}" class="logo" alt="Logo">
                    @endif
                @endif
            </div>
            
            <!-- Firmendaten rechts -->
            <div class="letterhead-right">
                @if(isset($profil))
                    <div class="company-name">{{ $profil->firmenname ?? $profil->firma ?? $profil->name ?? '' }}</div>
                    <div class="company-info">
                        @if($profil->strasse ?? false)
                            {{ $profil->strasse }} {{ $profil->hausnummer ?? '' }}<br>
                        @endif
                        @if(($profil->postleitzahl ?? $profil->plz ?? false) || ($profil->ort ?? $profil->wohnort ?? false))
                            {{ $profil->postleitzahl ?? $profil->plz ?? '' }} {{ $profil->ort ?? $profil->wohnort ?? '' }}<br>
                        @endif
                        @if($profil->codice_fiscale ?? $profil->steuernummer ?? false)
                            CF {{ $profil->codice_fiscale ?? $profil->steuernummer }}<br>
                        @endif
                        @if($profil->partita_iva ?? $profil->mwst_nummer ?? false)
                            P.IVA {{ $profil->partita_iva ?? $profil->mwst_nummer }}<br>
                        @endif
                        @if($profil->telefon ?? false)
                            Tel {{ $profil->telefon }}<br>
                        @endif
                        @if($profil->email ?? false)
                            {{ $profil->email }}
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════
         TITEL
    ═══════════════════════════════════════════════════════════════ -->
    <div class="document-title">
        <span class="de">ARBEITSBERICHT</span> / <span class="it">RAPPORTO DI LAVORO</span>
        <span class="status-badge">✓ UNTERSCHRIEBEN / FIRMATO</span>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════
         KUNDE & DATUM
    ═══════════════════════════════════════════════════════════════ -->
    <div class="address-section">
        <div class="address-row">
            <!-- Kunde -->
            <div class="address-col">
                <div class="address-box">
                    <div class="address-label">
                        <span class="de">KUNDE</span> / <span class="it">CLIENTE</span>
                    </div>
                    <div class="address-content">
                        <div class="address-name">{{ $bericht->adresse_name }}</div>
                        {{ $bericht->adresse_strasse }} {{ $bericht->adresse_hausnummer }}<br>
                        {{ $bericht->adresse_plz }} {{ $bericht->adresse_wohnort }}
                    </div>
                </div>
            </div>
            
            <!-- Daten -->
            <div class="address-col">
                <div class="address-box">
                    <div class="address-label">
                        <span class="de">DATEN</span> / <span class="it">DATI</span>
                    </div>
                    <div class="address-content">
                        <table class="invoice-data-table">
                            <tr>
                                <td class="label">Datum / Data:</td>
                                <td class="value">{{ $bericht->arbeitsdatum->format('d.m.Y') }}</td>
                            </tr>
                            <tr>
                                <td class="label">Bericht-Nr. / N° rapporto:</td>
                                <td class="value">#{{ $bericht->id }}</td>
                            </tr>
                            @if($bericht->gebaeude && $bericht->gebaeude->codex)
                            <tr>
                                <td class="label">Codex:</td>
                                <td class="value">{{ $bericht->gebaeude->codex }}</td>
                            </tr>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════
         POSITIONEN
    ═══════════════════════════════════════════════════════════════ -->
    <div class="section-title">
        <span class="de">DURCHGEFÜHRTE ARBEITEN</span> / <span class="it">LAVORI ESEGUITI</span>
    </div>
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 50%;">
                    <span class="de">Beschreibung</span> / <span class="it">Descrizione</span>
                </th>
                <th class="center" style="width: 12%;">
                    <span class="de">Menge</span> / <span class="it">Qtà</span>
                </th>
                <th class="right" style="width: 19%;">
                    <span class="de">Einzelpreis</span> / <span class="it">Prezzo</span>
                </th>
                <th class="right" style="width: 19%;">
                    <span class="de">Gesamt</span> / <span class="it">Totale</span>
                </th>
            </tr>
        </thead>
        <tbody>
            @php $summe = 0; @endphp
            @if(!empty($bericht->positionen))
                @foreach($bericht->positionen as $position)
                @php 
                    $einzelpreis = (float) ($position['einzelpreis'] ?? 0);
                    $anzahl = (float) ($position['anzahl'] ?? 1);
                    $gesamt = (float) ($position['gesamtpreis'] ?? ($einzelpreis * $anzahl));
                    $summe += $gesamt;
                @endphp
                <tr>
                    <td>{{ $position['bezeichnung'] ?? '-' }}</td>
                    <td class="center">{{ number_format($anzahl, 0) }}</td>
                    <td class="right">{{ number_format($einzelpreis, 2, ',', '.') }} €</td>
                    <td class="right">{{ number_format($gesamt, 2, ',', '.') }} €</td>
                </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="4" style="text-align: center; color: #888; padding: 5mm;">
                        Keine Positionen / Nessuna posizione
                    </td>
                </tr>
            @endif
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" style="text-align: right; padding-right: 3mm;">
                    <span class="de">SUMME</span> / <span class="it">TOTALE</span>
                </td>
                <td class="right">{{ number_format($summe, 2, ',', '.') }} €</td>
            </tr>
        </tfoot>
    </table>

    <!-- ═══════════════════════════════════════════════════════════════
         BEMERKUNG
    ═══════════════════════════════════════════════════════════════ -->
    @if($bericht->bemerkung)
    <div class="info-box">
        <h3>
            <span class="de">Bemerkungen</span> / <span class="it">Note</span>
        </h3>
        <div class="info-box-content">
            {{ $bericht->bemerkung }}
        </div>
    </div>
    @endif

    <!-- ═══════════════════════════════════════════════════════════════
         NÄCHSTER TERMIN
    ═══════════════════════════════════════════════════════════════ -->
    @if($bericht->naechste_faelligkeit)
    <div class="info-box highlight" style="padding: 2mm 3mm;">
        <table style="width: 100%;">
            <tr>
                <td style="font-size: 8pt; color: #1a4a7c; font-weight: bold;">
                    <span class="de">Nächster Termin</span> / <span class="it" style="color: #555; font-weight: normal;">Prossimo appuntamento</span>
                </td>
                <td style="text-align: right; font-size: 11pt; font-weight: bold; color: #1a4a7c;">
                    {{ $bericht->naechste_faelligkeit->format('d.m.Y') }}
                </td>
            </tr>
        </table>
    </div>
    @endif

    <!-- ═══════════════════════════════════════════════════════════════
         UNTERSCHRIFTEN
    ═══════════════════════════════════════════════════════════════ -->
    <div class="signatures-section">
        <div class="section-title">
            <span class="de">UNTERSCHRIFTEN</span> / <span class="it">FIRME</span>
        </div>
        
        <div class="signatures-row" style="margin-top: 3mm;">
            <!-- Kunde -->
            <div class="signature-col">
                <div class="signature-box">
                    <div class="signature-label">
                        <span class="de">Kunde</span> / <span class="it">Cliente</span>
                    </div>
                    @if($bericht->unterschrift_kunde)
                        <img src="{{ $bericht->unterschrift_kunde }}" class="signature-image" alt="Unterschrift Kunde">
                    @else
                        <div style="height: 40px;"></div>
                    @endif
                    <div class="signature-line">
                        <div class="signature-name">{{ $bericht->unterschrift_name ?? '' }}</div>
                        <div class="signature-role">
                            <span class="de">Kunde</span> / <span class="it">Cliente</span>
                        </div>
                        @if($bericht->unterschrieben_am)
                        <div class="signature-date">{{ $bericht->unterschrieben_am->format('d.m.Y, H:i') }} Uhr</div>
                        @endif
                    </div>
                </div>
            </div>
            
            <div class="signature-spacer"></div>
            
            <!-- Mitarbeiter -->
            <div class="signature-col">
                <div class="signature-box">
                    <div class="signature-label">
                        <span class="de">Mitarbeiter</span> / <span class="it">Operatore</span>
                    </div>
                    @if($bericht->unterschrift_mitarbeiter)
                        <img src="{{ $bericht->unterschrift_mitarbeiter }}" class="signature-image" alt="Unterschrift Mitarbeiter">
                    @else
                        <div style="height: 40px;"></div>
                    @endif
                    <div class="signature-line">
                        <div class="signature-name">{{ $bericht->mitarbeiter_name ?? '' }}</div>
                        <div class="signature-role">
                            <span class="de">Mitarbeiter</span> / <span class="it">Operatore</span>
                        </div>
                        @if($bericht->unterschrieben_am)
                        <div class="signature-date">{{ $bericht->unterschrieben_am->format('d.m.Y, H:i') }} Uhr</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════
         FOOTER
    ═══════════════════════════════════════════════════════════════ -->
    <div class="footer">
        @if(isset($profil))
            <strong>{{ $profil->firmenname ?? $profil->firma ?? $profil->name ?? '' }}</strong> | 
            {{ $profil->strasse ?? '' }} {{ $profil->hausnummer ?? '' }}, 
            {{ $profil->postleitzahl ?? $profil->plz ?? '' }} {{ $profil->ort ?? $profil->wohnort ?? '' }}
            @if($profil->codice_fiscale ?? $profil->steuernummer ?? false) | CF {{ $profil->codice_fiscale ?? $profil->steuernummer }}@endif
            @if($profil->partita_iva ?? $profil->mwst_nummer ?? false) | P.IVA {{ $profil->partita_iva ?? $profil->mwst_nummer }}@endif
        @endif
        <br>
        Arbeitsbericht / Rapporto di lavoro #{{ $bericht->id }} | Erstellt am / Creato il {{ $bericht->created_at->format('d.m.Y H:i') }}
    </div>
</body>
</html>
