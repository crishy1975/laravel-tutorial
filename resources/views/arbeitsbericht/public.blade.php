<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Arbeitsbericht / Rapporto di lavoro</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #1a4a7c 0%, #2d6a9f 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
            text-align: center;
            max-width: 450px;
            width: 100%;
        }
        
        .icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        
        .badge {
            display: inline-block;
            background: #d4edda;
            color: #155724;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        
        h1 {
            font-size: 22px;
            color: #333;
            margin-bottom: 4px;
        }
        
        h1 .it {
            color: #666;
            font-weight: normal;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 15px;
        }
        
        .subtitle .it {
            color: #888;
        }
        
        .info-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 24px;
            text-align: left;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            color: #666;
            font-size: 13px;
        }
        
        .info-label .it {
            color: #888;
        }
        
        .info-value {
            font-weight: 600;
            color: #333;
            text-align: right;
        }
        
        .btn {
            display: block;
            width: 100%;
            padding: 16px 24px;
            border: none;
            border-radius: 12px;
            font-size: 17px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #1a4a7c 0%, #2d6a9f 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(26, 74, 124, 0.4);
        }
        
        .btn-text {
            font-size: 15px;
        }
        
        .btn-text .it {
            opacity: 0.8;
        }
        
        .expire-notice {
            margin-top: 20px;
            font-size: 12px;
            color: #999;
        }
        
        .expire-notice strong {
            color: #666;
        }
        
        .signature-preview {
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .signatures-row {
            display: flex;
            justify-content: space-around;
            gap: 20px;
        }
        
        .signature-item {
            flex: 1;
            text-align: center;
        }
        
        .signature-item img {
            max-height: 50px;
            opacity: 0.8;
        }
        
        .signature-name {
            font-size: 12px;
            color: #666;
            margin-top: 8px;
        }
        
        .signature-role {
            font-size: 11px;
            color: #999;
        }
        
        .signature-role .it {
            color: #aaa;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">📄</div>
        
        <span class="badge">✓ Unterschrieben / Firmato</span>
        
        <h1>
            Ihr Arbeitsbericht <span class="it">/ Il Suo rapporto di lavoro</span>
        </h1>
        <p class="subtitle">
            steht zum Download bereit <span class="it">/ è pronto per il download</span>
        </p>
        
        <div class="info-card">
            <div class="info-row">
                <span class="info-label">Datum <span class="it">/ Data</span></span>
                <span class="info-value">{{ $bericht->arbeitsdatum->format('d.m.Y') }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Kunde <span class="it">/ Cliente</span></span>
                <span class="info-value">{{ $bericht->adresse_name }}</span>
            </div>
            @if($bericht->naechste_faelligkeit)
            <div class="info-row">
                <span class="info-label">Nächster Termin <span class="it">/ Prossimo</span></span>
                <span class="info-value">{{ $bericht->naechste_faelligkeit->format('d.m.Y') }}</span>
            </div>
            @endif
        </div>
        
        <a href="{{ route('arbeitsbericht.public.pdf', $bericht->token) }}" class="btn btn-primary">
            <span class="btn-text">
                📥 PDF herunterladen <span class="it">/ Scarica PDF</span>
            </span>
        </a>
        
        @if($bericht->unterschrift_kunde || $bericht->unterschrift_mitarbeiter)
        <div class="signature-preview">
            <div class="signatures-row">
                @if($bericht->unterschrift_kunde)
                <div class="signature-item">
                    <img src="{{ $bericht->unterschrift_kunde }}" alt="Unterschrift Kunde">
                    <div class="signature-name">{{ $bericht->unterschrift_name }}</div>
                    <div class="signature-role">Kunde <span class="it">/ Cliente</span></div>
                </div>
                @endif
                
                @if($bericht->unterschrift_mitarbeiter)
                <div class="signature-item">
                    <img src="{{ $bericht->unterschrift_mitarbeiter }}" alt="Unterschrift Mitarbeiter">
                    <div class="signature-name">{{ $bericht->mitarbeiter_name }}</div>
                    <div class="signature-role">Mitarbeiter <span class="it">/ Operatore</span></div>
                </div>
                @endif
            </div>
        </div>
        @endif
        
        <div class="expire-notice">
            Download verfügbar bis / Download disponibile fino al<br>
            <strong>{{ $bericht->gueltig_bis->format('d.m.Y, H:i') }} Uhr</strong>
        </div>
    </div>
</body>
</html>
