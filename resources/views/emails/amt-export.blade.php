{{-- resources/views/emails/amt-export.blade.php --}}
<!DOCTYPE html>
<html lang="de">
<head><meta charset="UTF-8"></head>
<body style="font-family: Arial, sans-serif; color: #333; line-height: 1.5;">
    <p>Sehr geehrte Damen und Herren,</p>

    <p>im Anhang finden Sie den Export der durchgeführten Kaminmessungen.</p>

    <table style="border-collapse: collapse; margin: 16px 0;">
        <tr>
            <td style="padding: 4px 12px 4px 0;"><strong>Kontrolleur-ID:</strong></td>
            <td style="padding: 4px 0;">{{ $kontrolleurId }}</td>
        </tr>
        <tr>
            <td style="padding: 4px 12px 4px 0;"><strong>Anzahl Messungen:</strong></td>
            <td style="padding: 4px 0;">{{ $anzahl }}</td>
        </tr>
        @if($zeitraum)
        <tr>
            <td style="padding: 4px 12px 4px 0;"><strong>Zeitraum:</strong></td>
            <td style="padding: 4px 0;">{{ $zeitraum }}</td>
        </tr>
        @endif
        <tr>
            <td style="padding: 4px 12px 4px 0;"><strong>Datei:</strong></td>
            <td style="padding: 4px 0;">{{ $fileName }}</td>
        </tr>
    </table>

    <p>Mit freundlichen Grüßen<br>
    {{ $absenderName ?? config('messungen.amt_email_from_name') }}</p>

    <hr style="border: none; border-top: 1px solid #ddd; margin: 24px 0;">
    <p style="font-size: 11px; color: #888;">
        Automatisch generiert von UschiWeb am {{ now()->format('d.m.Y H:i') }}.
    </p>
</body>
</html>
