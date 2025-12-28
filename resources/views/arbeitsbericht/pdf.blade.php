<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Arbeitsbericht - {{ $bericht->adresse_name }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10pt;
            color: #333;
            line-height: 1.4;
        }

        /* ══════════════════════════════════════════════════════════════════ */
        /* HEADER MIT LOGO */
        /* ══════════════════════════════════════════════════════════════════ */
        .header {
            width: 100%;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #333;
        }

        .header-table {
            width: 100%;
        }

        .logo-cell {
            width: 30%;
            vertical-align: middle;
        }

        .logo {
            max-height: 70px;
            max-width: 180px;
        }

        .title-cell {
            width: 40%;
            text-align: center;
            vertical-align: middle;
        }

        .title-cell h1 {
            font-size: 20pt;
            color: #1a1a1a;
            margin: 0;
        }

        .title-cell .datum {
            font-size: 11pt;
            color: #666;
            margin-top: 5px;
        }

        .firma-cell {
            width: 30%;
            text-align: right;
            vertical-align: middle;
            font-size: 9pt;
            color: #666;
        }

        .firma-cell .firma-name {
            font-weight: bold;
            color: #333;
            font-size: 10pt;
        }

        /* Status Badge */
        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 9pt;
            font-weight: bold;
            background: #d4edda;
            color: #155724;
        }

        /* ══════════════════════════════════════════════════════════════════ */
        /* CONTENT SECTIONS */
        /* ══════════════════════════════════════════════════════════════════ */
        .section {
            margin-bottom: 18px;
        }

        .section-title {
            font-size: 10pt;
            font-weight: bold;
            color: #1a1a1a;
            background: #f5f5f5;
            padding: 6px 10px;
            margin-bottom: 8px;
            border-left: 3px solid #333;
        }

        .section-content {
            padding: 0 10px;
        }

        /* Adresse */
        .adresse {
            white-space: pre-line;
            font-size: 10pt;
        }

        /* Positionen Tabelle */
        .positionen-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }

        .positionen-table th,
        .positionen-table td {
            padding: 6px 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .positionen-table th {
            background: #f9f9f9;
            font-weight: bold;
            font-size: 9pt;
            color: #666;
        }

        .positionen-table td.anzahl {
            text-align: right;
            width: 80px;
        }

        /* Bemerkung */
        .bemerkung {
            background: #f9f9f9;
            padding: 10px;
            border-radius: 4px;
            font-style: italic;
            font-size: 10pt;
        }

        /* Fälligkeit */
        .faelligkeit-box {
            background: #e8f4fd;
            padding: 10px;
            border-radius: 4px;
            border-left: 3px solid #0066cc;
        }

        .faelligkeit-box strong {
            font-size: 13pt;
            color: #0066cc;
        }

        /* ══════════════════════════════════════════════════════════════════ */
        /* UNTERSCHRIFT */
        /* ══════════════════════════════════════════════════════════════════ */
        .unterschrift-section {
            margin-top: 30px;
            page-break-inside: avoid;
        }

        .unterschrift-table {
            width: 100%;
        }

        .unterschrift-box {
            width: 45%;
            vertical-align: top;
            padding: 10px;
        }

        .unterschrift-bild {
            max-height: 70px;
            margin-bottom: 5px;
        }

        .unterschrift-linie {
            border-top: 1px solid #333;
            padding-top: 5px;
            font-size: 10pt;
        }

        .unterschrift-info {
            font-size: 9pt;
            color: #666;
            margin-top: 3px;
        }

        /* ══════════════════════════════════════════════════════════════════ */
        /* FOOTER */
        /* ══════════════════════════════════════════════════════════════════ */
        .footer {
            position: fixed;
            bottom: 15mm;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8pt;
            color: #999;
            border-top: 1px solid #eee;
            padding-top: 8px;
        }
    </style>
</head>
<body>
    <!-- ══════════════════════════════════════════════════════════════════ -->
    <!-- HEADER MIT LOGO -->
    <!-- ══════════════════════════════════════════════════════════════════ -->
    <div class="header">
        <table class="header-table">
            <tr>
                <!-- Logo links -->
                <td class="logo-cell">
                    @if(isset($profil) && $profil->logo_pfad && file_exists(public_path($profil->logo_pfad)))
                        <img src="{{ public_path($profil->logo_pfad) }}" class="logo" alt="Logo">
                    @elseif(isset($profil) && $profil->logo_pfad && file_exists(storage_path('app/public/' . $profil->logo_pfad)))
                        <img src="{{ storage_path('app/public/' . $profil->logo_pfad) }}" class="logo" alt="Logo">
                    @endif
                </td>
                
                <!-- Titel Mitte -->
                <td class="title-cell">
                    <h1>ARBEITSBERICHT</h1>
                    <div class="datum">
                        {{ $bericht->arbeitsdatum->format('d.m.Y') }}
                    </div>
                    <div style="margin-top: 8px;">
                        <span class="status-badge">✓ UNTERSCHRIEBEN</span>
                    </div>
                </td>
                
                <!-- Firmendaten rechts -->
                <td class="firma-cell">
                    @if(isset($profil))
                        <div class="firma-name">{{ $profil->firma ?? $profil->name ?? '' }}</div>
                        @if($profil->strasse)
                            {{ $profil->strasse }} {{ $profil->hausnummer ?? '' }}<br>
                        @endif
                        @if($profil->plz || $profil->wohnort)
                            {{ $profil->plz }} {{ $profil->wohnort }}<br>
                        @endif
                        @if($profil->telefon)
                            Tel: {{ $profil->telefon }}<br>
                        @endif
                        @if($profil->email)
                            {{ $profil->email }}
                        @endif
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <!-- ══════════════════════════════════════════════════════════════════ -->
    <!-- ADRESSE / OBJEKT -->
    <!-- ══════════════════════════════════════════════════════════════════ -->
    <div class="section">
        <div class="section-title">📍 Objekt / Kunde</div>
        <div class="section-content">
            <div class="adresse">{{ $bericht->volle_adresse }}</div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════════ -->
    <!-- POSITIONEN -->
    <!-- ══════════════════════════════════════════════════════════════════ -->
    @if(!empty($bericht->positionen))
    <div class="section">
        <div class="section-title">📋 Durchgeführte Arbeiten</div>
        <div class="section-content">
            <table class="positionen-table">
                <thead>
                    <tr>
                        <th>Bezeichnung</th>
                        <th class="anzahl">Anzahl</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($bericht->positionen as $position)
                    <tr>
                        <td>{{ $position['bezeichnung'] }}</td>
                        <td class="anzahl">{{ $position['anzahl'] }} {{ $position['einheit'] ?? '' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- ══════════════════════════════════════════════════════════════════ -->
    <!-- BEMERKUNG -->
    <!-- ══════════════════════════════════════════════════════════════════ -->
    @if($bericht->bemerkung)
    <div class="section">
        <div class="section-title">📝 Bemerkung</div>
        <div class="section-content">
            <div class="bemerkung">{{ $bericht->bemerkung }}</div>
        </div>
    </div>
    @endif

    <!-- ══════════════════════════════════════════════════════════════════ -->
    <!-- NÄCHSTE FÄLLIGKEIT -->
    <!-- ══════════════════════════════════════════════════════════════════ -->
    @if($bericht->naechste_faelligkeit)
    <div class="section">
        <div class="section-title">🗓️ Nächste Reinigung fällig</div>
        <div class="section-content">
            <div class="faelligkeit-box">
                <strong>{{ $bericht->naechste_faelligkeit->format('d.m.Y') }}</strong>
            </div>
        </div>
    </div>
    @endif

    <!-- ══════════════════════════════════════════════════════════════════ -->
    <!-- UNTERSCHRIFTEN -->
    <!-- ══════════════════════════════════════════════════════════════════ -->
    <div class="unterschrift-section">
        <div class="section-title">✍️ Unterschrift</div>
        <div class="section-content" style="margin-top: 15px;">
            <table class="unterschrift-table">
                <tr>
                    <!-- Kunde -->
                    <td class="unterschrift-box">
                        @if($bericht->unterschrift_kunde)
                            <img src="{{ $bericht->unterschrift_kunde }}" class="unterschrift-bild" alt="Unterschrift">
                        @endif
                        <div class="unterschrift-linie">
                            <strong>{{ $bericht->unterschrift_name }}</strong>
                        </div>
                        <div class="unterschrift-info">
                            Kunde<br>
                            {{ $bericht->unterschrieben_am->format('d.m.Y, H:i') }} Uhr
                        </div>
                    </td>

                    <td style="width: 10%;"></td>

                    <!-- Mitarbeiter / Firma -->
                    <td class="unterschrift-box">
                        <div style="height: 70px;"></div>
                        <div class="unterschrift-linie">
                            @if(isset($profil))
                                {{ $profil->firma ?? $profil->name ?? 'Firma' }}
                            @else
                                Ausführende Firma
                            @endif
                        </div>
                        <div class="unterschrift-info">
                            Mitarbeiter<br>
                            Datum: ____________________
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════════ -->
    <!-- FOOTER -->
    <!-- ══════════════════════════════════════════════════════════════════ -->
    <div class="footer">
        Arbeitsbericht #{{ $bericht->id }} 
        @if(isset($profil) && $profil->firma)
            | {{ $profil->firma }}
        @endif
        | Erstellt am {{ $bericht->created_at->format('d.m.Y H:i') }}
    </div>
</body>
</html>
