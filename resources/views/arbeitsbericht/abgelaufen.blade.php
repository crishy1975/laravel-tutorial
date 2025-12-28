<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Link abgelaufen</title>
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
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 40px;
            text-align: center;
            max-width: 400px;
        }
        
        .icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        
        h1 {
            font-size: 24px;
            color: #333;
            margin-bottom: 12px;
        }
        
        p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 24px;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">⏰</div>
        <h1>Link abgelaufen</h1>
        <p>
            Dieser Arbeitsbericht-Link ist leider nicht mehr gültig. 
            Der Link war bis zum <strong>{{ $bericht->gueltig_bis->format('d.m.Y H:i') }} Uhr</strong> verfügbar.
        </p>
        
        <div class="info-box">
            <strong>Was können Sie tun?</strong>
            Bitte kontaktieren Sie uns, um einen neuen Link anzufordern.
        </div>
    </div>
</body>
</html>
