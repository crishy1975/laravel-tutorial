<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Link abgelaufen / Link scaduto</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 40px;
            text-align: center;
            max-width: 450px;
        }
        
        .icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        
        h1 {
            font-size: 22px;
            color: #333;
            margin-bottom: 12px;
        }
        
        h1 .it {
            color: #666;
            font-weight: normal;
        }
        
        p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 24px;
        }
        
        p .it {
            color: #888;
        }
        
        .date-box {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 8px;
            padding: 12px;
            font-size: 14px;
            color: #856404;
            margin-bottom: 20px;
        }
        
        .info-box {
            background: #f5f5f5;
            border-radius: 8px;
            padding: 16px;
            font-size: 14px;
            color: #666;
        }
        
        .info-box strong {
            display: block;
            color: #333;
            margin-bottom: 4px;
        }
        
        .info-box .it {
            color: #888;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">⏰</div>
        <h1>
            Link abgelaufen <span class="it">/ Link scaduto</span>
        </h1>
        <p>
            Dieser Arbeitsbericht-Link ist leider nicht mehr gültig.<br>
            <span class="it">Questo link per il rapporto di lavoro non è più valido.</span>
        </p>
        
        <div class="date-box">
            Gültig bis / Valido fino al<br>
            <strong>{{ $bericht->gueltig_bis->format('d.m.Y H:i') }} Uhr</strong>
        </div>
        
        <div class="info-box">
            <strong>Was können Sie tun? <span class="it">/ Cosa può fare?</span></strong>
            Bitte kontaktieren Sie uns, um einen neuen Link anzufordern.<br>
            <span class="it">La preghiamo di contattarci per richiedere un nuovo link.</span>
        </div>
    </div>
</body>
</html>
