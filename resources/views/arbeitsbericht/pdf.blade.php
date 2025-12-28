<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Arbeitsbericht / Rapporto di lavoro - {{ $bericht->adresse_name }}</title>
    <style>
        @page {
            margin: 20mm 15mm 25mm 15mm;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9pt;
            color: #333;
            line-height: 1.4;
        }

        /* ══════════════════════════════════════════════════════════════════ */
        /* BRIEFKOPF / INTESTAZIONE */
        /* ══════════════════════════════════════════════════════════════════ */
        .header {
            width: 100%;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .logo-cell {
            width: 25%;
            vertical-align: top;
        }

        .logo {
            max-height: 60px;
            max-width: 150px;
        }

        .firma-cell {
            width: 45%;
            vertical-align: top;
            padding-left: 15px;
        }

        .firma-name {
            font-size: 14pt;
            font-weight: bold;
            color: #1a1a1a;
            margin-bottom: 3px;
        }

        .firma-details {
            font-size: 8pt;
            color: #555;
            line-height: 1.5;
        }

        .dokument-cell {
            width: 30%;
            text-align: right;
            vertical-align: top;
        }

        .dokument-titel {
            font-size: 16pt;
            font-weight: bold;
            color: #1a1a1a;
            line-height: 1.2;
        }

        .dokument-subtitel {
            font-size: 10pt;
            color: #666;
            font-style: italic;
        }

        .dokument-datum {
            margin-top: 10px;
            font-size: 11pt;
            color: #333;
        }

        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 10px;
            font-size: 8pt;
            font-weight: bold;
            background: #d4edda;
            color: #155724;
            margin-top: 5px;
        }

        /* ══════════════════════════════════════════════════════════════════ */
        /* ZWEI-SPALTEN LAYOUT */
        /* ══════════════════════════════════════════════════════════════════ */
        .info-row {
            width: 100%;
            margin-bottom: 15px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-box {
            width: 48%;
            vertical-align: top;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: #fafafa;
        }

        .info-box-spacer {
            width: 4%;
        }

        .info-label {
            font-size: 7pt;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #888;
            margin-bottom: 3px;
        }

        .info-value {
            font-size: 10pt;
            color: #333;
        }

        /* ══════════════════════════════════════════════════════════════════ */
        /* SECTIONS */
        /* ══════════════════════════════════════════════════════════════════ */
        .section {
            margin-bottom: 15px;
        }

        .section-title {
            font-size: 9pt;
            font-weight: bold;
            color: #fff;
            background: #333;
            padding: 6px 10px;
            margin-bottom: 0;
        }

        .section-subtitle {
            font-size: 8pt;
            font-weight: normal;
            color: #ccc;
            font-style: italic;
        }

        /* ══════════════════════════════════════════════════════════════════ */
        /* POSITIONEN TABELLE */
        /* ══════════════════════════════════════════════════════════════════ */
        .positionen-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #ddd;
        }

        .positionen-table th {
            background: #f5f5f5;
            font-size: 8pt;
            font-weight: bold;
            padding: 8px;
            text-align: left;
            border-bottom: 2px solid #ddd;
            color: #555;
        }

        .positionen-table th.right {
            text-align: right;
        }

        .positionen-table th.center {
            text-align: center;
        }

        .positionen-table td {
            padding: 8px;
            border-bottom: 1px solid #eee;
            font-size: 9pt;
        }

        .positionen-table td.right {
            text-align: right;
        }

        .positionen-table td.center {
            text-align: center;
        }

        .positionen-table tr:nth-child(even) {
            background: #fafafa;
        }

        .positionen-table tfoot td {
            background: #f0f0f0;
            font-weight: bold;
            border-top: 2px solid #333;
            font-size: 10pt;
        }

        /* ══════════════════════════════════════════════════════════════════ */
        /* BEMERKUNG */
        /* ══════════════════════════════════════════════════════════════════ */
        .bemerkung-box {
            background: #fff9e6;
            border: 1px solid #ffe0a6;
            border-left: 4px solid #ffc107;
            padding: 10px;
            font-style: italic;
            font-size: 9pt;
        }

        /* ══════════════════════════════════════════════════════════════════ */
        /* NÄCHSTER TERMIN */
        /* ══════════════════════════════════════════════════════════════════ */
        .termin-box {
            background: #e8f4fd;
            border: 1px solid #b8daff;
            border-left: 4px solid #007bff;
            padding: 10px;
        }

        .termin-label {
            font-size: 8pt;
            color: #666;
        }

        .termin-datum {
            font-size: 14pt;
            font-weight: bold;
            color: #007bff;
        }

        /* ══════════════════════════════════════════════════════════════════ */
        /* UNTERSCHRIFTEN */
        /* ══════════════════════════════════════════════════════════════════ */
        .unterschriften-section {
            margin-top: 25px;
            page-break-inside: avoid;
        }

        .unterschriften-table {
            width: 100%;
            border-collapse: collapse;
        }

        .unterschrift-cell {
            width: 45%;
            vertical-align: top;
            padding: 10px;
            text-align: center;
        }

        .unterschrift-spacer {
            width: 10%;
        }

        .unterschrift-bild {
            max-height: 60px;
            max-width: 200px;
            margin-bottom: 5px;
        }

        .unterschrift-linie {
            border-top: 1px solid #333;
            padding-top: 5px;
            margin-top: 10px;
        }

        .unterschrift-name {
            font-size: 10pt;
            font-weight: bold;
            color: #333;
        }

        .unterschrift-rolle {
            font-size: 8pt;
            color: #666;
        }

        .unterschrift-datum {
            font-size: 7pt;
            color: #999;
            margin-top: 3px;
        }

        /* ══════════════════════════════════════════════════════════════════ */
        /* FOOTER */
        /* ══════════════════════════════════════════════════════════════════ */
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 7pt;
            color: #999;
            padding: 10px 15mm;
            border-top: 1px solid #eee;
        }
    </style>
</head>
<body>
    <!-- ══════════════════════════════════════════════════════════════════ -->
    <!-- BRIEFKOPF / INTESTAZIONE -->
    <!-- ══════════════════════════════════════════════════════════════════ -->
    <div class="header">
        <table class="header-table">
            <tr>
                <!-- Logo -->
                <td class="logo-cell">
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
                </td>
                
                <!-- Firmendaten / Dati azienda -->
                <td class="firma-cell">
                    @if(isset($profil))
                        <div class="firma-name">{{ $profil->firma ?? $profil->name ?? 'Firma' }}</div>
                        <div class="firma-details">
                            @if($profil->strasse ?? false)
                                {{ $profil->strasse }} {{ $profil->hausnummer ?? '' }}<br>
                            @endif
                            @if(($profil->plz ?? false) || ($profil->wohnort ?? false))
                                {{ $profil->plz ?? '' }} {{ $profil->wohnort ?? '' }}<br>
                            @endif
                            @if($profil->steuernummer ?? false)
                                St.-Nr./C.F.: {{ $profil->steuernummer }}<br>
                            @endif
                            @if($profil->mwst_nummer ?? false)
                                MwSt.-Nr./P.IVA: {{ $profil->mwst_nummer }}<br>
                            @endif
                            @if($profil->telefon ?? false)
                                Tel.: {{ $profil->telefon }}
                            @endif
                            @if($profil->email ?? false)
                                | {{ $profil->email }}
                            @endif
                        </div>
                    @endif
                </td>
                
                <!-- Dokumenttitel -->
                <td class="dokument-cell">
                    <div class="dokument-titel">ARBEITSBERICHT</div>
                    <div class="dokument-subtitel">Rapporto di lavoro</div>
                    <div class="dokument-datum">
                        {{ $bericht->arbeitsdatum->format('d.m.Y') }}
                    </div>
                    <div class="status-badge">✓ UNTERSCHRIEBEN / FIRMATO</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- ══════════════════════════════════════════════════════════════════ -->
    <!-- KUNDE & OBJEKT -->
    <!-- ══════════════════════════════════════════════════════════════════ -->
    <div class="info-row">
        <table class="info-table">
            <tr>
                <td class="info-box">
                    <div class="info-label">Kunde / Cliente</div>
                    <div class="info-value">
                        <strong>{{ $bericht->adresse_name }}</strong><br>
                        {{ $bericht->adresse_strasse }} {{ $bericht->adresse_hausnummer }}<br>
                        {{ $bericht->adresse_plz }} {{ $bericht->adresse_wohnort }}
                    </div>
                </td>
                <td class="info-box-spacer"></td>
                <td class="info-box">
                    <div class="info-label">Objekt / Edificio</div>
                    <div class="info-value">
                        @if($bericht->gebaeude)
                            <strong>{{ $bericht->gebaeude->gebaeude_name }}</strong>
                            @if($bericht->gebaeude->codex)
                                <span style="color: #666;">({{ $bericht->gebaeude->codex }})</span>
                            @endif
                            <br>
                            {{ $bericht->gebaeude->strasse }} {{ $bericht->gebaeude->hausnummer }}<br>
                            {{ $bericht->gebaeude->plz }} {{ $bericht->gebaeude->wohnort }}
                        @else
                            {{ $bericht->adresse_name }}
                        @endif
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- ══════════════════════════════════════════════════════════════════ -->
    <!-- POSITIONEN / LAVORI ESEGUITI -->
    <!-- ══════════════════════════════════════════════════════════════════ -->
    <div class="section">
        <div class="section-title">
            DURCHGEFÜHRTE ARBEITEN <span class="section-subtitle">/ Lavori eseguiti</span>
        </div>
        <table class="positionen-table">
            <thead>
                <tr>
                    <th style="width: 50%;">Bezeichnung / Descrizione</th>
                    <th class="center" style="width: 15%;">Menge / Qtà</th>
                    <th class="right" style="width: 17%;">Einzelpreis / Prezzo</th>
                    <th class="right" style="width: 18%;">Gesamt / Totale</th>
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
                        <td class="center">{{ $anzahl }} {{ $position['einheit'] ?? '' }}</td>
                        <td class="right">{{ number_format($einzelpreis, 2, ',', '.') }} €</td>
                        <td class="right">{{ number_format($gesamt, 2, ',', '.') }} €</td>
                    </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="4" style="text-align: center; color: #999; padding: 20px;">
                            Keine Positionen / Nessuna posizione
                        </td>
                    </tr>
                @endif
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" style="text-align: right; padding-right: 10px;">
                        <strong>SUMME / TOTALE:</strong>
                    </td>
                    <td class="right">
                        <strong>{{ number_format($summe, 2, ',', '.') }} €</strong>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- ══════════════════════════════════════════════════════════════════ -->
    <!-- BEMERKUNG / NOTE -->
    <!-- ══════════════════════════════════════════════════════════════════ -->
    @if($bericht->bemerkung)
    <div class="section">
        <div class="section-title">
            BEMERKUNG <span class="section-subtitle">/ Note</span>
        </div>
        <div class="bemerkung-box">
            {{ $bericht->bemerkung }}
        </div>
    </div>
    @endif

    <!-- ══════════════════════════════════════════════════════════════════ -->
    <!-- NÄCHSTER TERMIN / PROSSIMO APPUNTAMENTO -->
    <!-- ══════════════════════════════════════════════════════════════════ -->
    @if($bericht->naechste_faelligkeit)
    <div class="section">
        <div class="section-title">
            NÄCHSTER TERMIN <span class="section-subtitle">/ Prossimo appuntamento</span>
        </div>
        <div class="termin-box">
            <div class="termin-label">Fällig am / Scadenza:</div>
            <div class="termin-datum">{{ $bericht->naechste_faelligkeit->format('d.m.Y') }}</div>
        </div>
    </div>
    @endif

    <!-- ══════════════════════════════════════════════════════════════════ -->
    <!-- UNTERSCHRIFTEN / FIRME -->
    <!-- ══════════════════════════════════════════════════════════════════ -->
    <div class="unterschriften-section">
        <div class="section-title">
            UNTERSCHRIFTEN <span class="section-subtitle">/ Firme</span>
        </div>
        <table class="unterschriften-table" style="margin-top: 15px;">
            <tr>
                <!-- Kunde / Cliente -->
                <td class="unterschrift-cell">
                    @if($bericht->unterschrift_kunde)
                        <img src="{{ $bericht->unterschrift_kunde }}" class="unterschrift-bild" alt="Unterschrift Kunde">
                    @else
                        <div style="height: 60px;"></div>
                    @endif
                    <div class="unterschrift-linie">
                        <div class="unterschrift-name">{{ $bericht->unterschrift_name ?? '________________' }}</div>
                        <div class="unterschrift-rolle">Kunde / Cliente</div>
                        @if($bericht->unterschrieben_am)
                            <div class="unterschrift-datum">{{ $bericht->unterschrieben_am->format('d.m.Y, H:i') }} Uhr</div>
                        @endif
                    </div>
                </td>
                
                <td class="unterschrift-spacer"></td>
                
                <!-- Mitarbeiter / Operatore -->
                <td class="unterschrift-cell">
                    @if($bericht->unterschrift_mitarbeiter)
                        <img src="{{ $bericht->unterschrift_mitarbeiter }}" class="unterschrift-bild" alt="Unterschrift Mitarbeiter">
                    @else
                        <div style="height: 60px;"></div>
                    @endif
                    <div class="unterschrift-linie">
                        <div class="unterschrift-name">{{ $bericht->mitarbeiter_name ?? '________________' }}</div>
                        <div class="unterschrift-rolle">Mitarbeiter / Operatore</div>
                        @if($bericht->unterschrieben_am)
                            <div class="unterschrift-datum">{{ $bericht->unterschrieben_am->format('d.m.Y, H:i') }} Uhr</div>
                        @endif
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- ══════════════════════════════════════════════════════════════════ -->
    <!-- FOOTER -->
    <!-- ══════════════════════════════════════════════════════════════════ -->
    <div class="footer">
        Arbeitsbericht / Rapporto di lavoro #{{ $bericht->id }} 
        @if(isset($profil) && ($profil->firma ?? false))
            | {{ $profil->firma }}
        @endif
        | Erstellt am / Creato il {{ $bericht->created_at->format('d.m.Y H:i') }}
    </div>
</body>
</html>
