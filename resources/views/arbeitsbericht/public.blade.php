<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Arbeitsbericht Download</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            max-width: 420px;
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
            font-size: 24px;
            color: #333;
            margin-bottom: 8px;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 15px;
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
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            color: #666;
            font-size: 14px;
        }
        
        .info-value {
            font-weight: 600;
            color: #333;
        }
        
        .btn {
            display: block;
            width: 100%;
            padding: 16px 24px;
            border: none;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
        
        .expire-notice {
            margin-top: 20px;
            font-size: 13px;
            color: #999;
        }
        
        .expire-notice strong {
            color: #666;
        }
        
        .signature-preview {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .signature-preview img {
            max-height: 60px;
            opacity: 0.7;
        }
        
        .signature-name {
            font-size: 13px;
            color: #666;
            margin-top: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">📄</div>
        
        <span class="badge">✓ Unterschrieben</span>
        
        <h1>Ihr Arbeitsbericht</h1>
        <p class="subtitle">steht zum Download bereit</p>
        
        <div class="info-card">
            <div class="info-row">
                <span class="info-label">Datum</span>
                <span class="info-value">{{ $bericht->arbeitsdatum->format('d.m.Y') }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Objekt</span>
                <span class="info-value">{{ $bericht->adresse_name }}</span>
            </div>
            @if($bericht->naechste_faelligkeit)
            <div class="info-row">
                <span class="info-label">Nächster Termin</span>
                <span class="info-value">{{ $bericht->naechste_faelligkeit->format('d.m.Y') }}</span>
            </div>
            @endif
        </div>
        
        <a href="{{ route('arbeitsbericht.public.pdf', $bericht->token) }}" class="btn btn-primary">
            📥 PDF herunterladen
        </a>
        
        @if($bericht->unterschrift_kunde)
        <div class="signature-preview">
            <img src="{{ $bericht->unterschrift_kunde }}" alt="Ihre Unterschrift">
            <div class="signature-name">
                Unterschrieben von {{ $bericht->unterschrift_name }}<br>
                am {{ $bericht->unterschrieben_am->format('d.m.Y') }}
            </div>
        </div>
        @endif
        
        <div class="expire-notice">
            Download verfügbar bis<br>
            <strong>{{ $bericht->gueltig_bis->format('d.m.Y, H:i') }} Uhr</strong>
        </div>
    </div>
</body>
</html>
