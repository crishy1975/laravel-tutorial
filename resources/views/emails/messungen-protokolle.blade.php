{{-- resources/views/emails/messungen-protokolle.blade.php --}}
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; background-color: #f4f6f8; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">

    {{-- Wrapper --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f6f8; padding: 20px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width: 600px; width: 100%;">

                    {{-- Header --}}
                    <tr>
                        <td style="background: linear-gradient(135deg, #1a365d 0%, #2c5282 100%); padding: 28px 32px; border-radius: 8px 8px 0 0;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td>
                                        <h1 style="margin: 0; color: #ffffff; font-size: 22px; font-weight: 700; letter-spacing: 0.5px;">
                                            🔥 Resch GmbH
                                        </h1>
                                        <p style="margin: 4px 0 0; color: #a0c4e8; font-size: 13px;">
                                            Kaminkehrer &middot; Spazzacamino
                                        </p>
                                    </td>
                                    <td align="right" valign="middle">
                                        <span style="display: inline-block; background: rgba(255,255,255,0.15); color: #ffffff; padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                                            📋 Messprotokolle
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="background-color: #ffffff; padding: 32px;">

                            {{-- Begrüßung --}}
                            <p style="margin: 0 0 16px; color: #2d3748; font-size: 15px; line-height: 1.6;">
                                Sehr geehrte Damen und Herren,
                            </p>

                            <p style="margin: 0 0 20px; color: #4a5568; font-size: 14px; line-height: 1.6;">
                                anbei erhalten Sie <strong>{{ $anzahlProtokolle }} Messprotokoll{{ $anzahlProtokolle > 1 ? 'e' : '' }}</strong>
                                der Emissionskontrolle als PDF im Anhang.
                            </p>

                            {{-- Persönliche Nachricht --}}
                            @if(!empty($nachricht))
                                <div style="background-color: #f7fafc; border-left: 4px solid #2c5282; padding: 14px 18px; margin: 0 0 24px; border-radius: 0 6px 6px 0;">
                                    <p style="margin: 0; color: #4a5568; font-size: 14px; line-height: 1.6; white-space: pre-line;">{{ $nachricht }}</p>
                                </div>
                            @endif

                            {{-- Tabelle --}}
                            <h3 style="margin: 0 0 12px; color: #1a365d; font-size: 14px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
                                📎 Protokolle im Anhang
                            </h3>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse; margin-bottom: 24px;">
                                <thead>
                                    <tr>
                                        <th style="background-color: #1a365d; color: #ffffff; padding: 10px 12px; font-size: 12px; font-weight: 600; text-align: left; border-radius: 4px 0 0 0;">
                                            Kodex
                                        </th>
                                        <th style="background-color: #1a365d; color: #ffffff; padding: 10px 12px; font-size: 12px; font-weight: 600; text-align: left;">
                                            Aufstellungsort
                                        </th>
                                        <th style="background-color: #1a365d; color: #ffffff; padding: 10px 12px; font-size: 12px; font-weight: 600; text-align: center;">
                                            Datum
                                        </th>
                                        <th style="background-color: #1a365d; color: #ffffff; padding: 10px 12px; font-size: 12px; font-weight: 600; text-align: center;">
                                            Brennstoff
                                        </th>
                                        <th style="background-color: #1a365d; color: #ffffff; padding: 10px 12px; font-size: 12px; font-weight: 600; text-align: center; border-radius: 0 4px 0 0;">
                                            Ergebnis
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($messungen as $i => $m)
                                        @php
                                            $bgColor = $i % 2 === 0 ? '#ffffff' : '#f7fafc';
                                            $ergebnisText = $m->strEsito === '1' ? '✅ Positiv' : ($m->strEsito === '0' ? '❌ Negativ' : '─');
                                            $ergebnisColor = $m->strEsito === '1' ? '#276749' : ($m->strEsito === '0' ? '#c53030' : '#718096');
                                        @endphp
                                        <tr>
                                            <td style="background-color: {{ $bgColor }}; padding: 9px 12px; font-size: 13px; color: #2d3748; border-bottom: 1px solid #e2e8f0; font-weight: 600;">
                                                {{ $m->cIM_CODICE }}
                                            </td>
                                            <td style="background-color: {{ $bgColor }}; padding: 9px 12px; font-size: 13px; color: #4a5568; border-bottom: 1px solid #e2e8f0;">
                                                {{ $m->cIM_NAME }}
                                            </td>
                                            <td style="background-color: {{ $bgColor }}; padding: 9px 12px; font-size: 13px; color: #4a5568; border-bottom: 1px solid #e2e8f0; text-align: center;">
                                                {{ $m->cMIS_DATA2 }}
                                            </td>
                                            <td style="background-color: {{ $bgColor }}; padding: 9px 12px; font-size: 12px; color: #718096; border-bottom: 1px solid #e2e8f0; text-align: center;">
                                                {{ $m->cMIS_COMBUSTIBILE_P ?: '─' }}
                                            </td>
                                            <td style="background-color: {{ $bgColor }}; padding: 9px 12px; font-size: 13px; color: {{ $ergebnisColor }}; border-bottom: 1px solid #e2e8f0; text-align: center; font-weight: 600;">
                                                {{ $ergebnisText }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            {{-- Hinweis bei Fehlern --}}
                            @if(!empty($fehler))
                                <div style="background-color: #fff5f5; border: 1px solid #feb2b2; padding: 12px 16px; border-radius: 6px; margin-bottom: 20px;">
                                    <p style="margin: 0; color: #c53030; font-size: 13px; font-weight: 600;">⚠️ Hinweis:</p>
                                    @foreach($fehler as $f)
                                        <p style="margin: 4px 0 0; color: #742a2a; font-size: 12px;">{{ $f }}</p>
                                    @endforeach
                                </div>
                            @endif

                            {{-- Abschluss --}}
                            <p style="margin: 0 0 4px; color: #4a5568; font-size: 14px; line-height: 1.6;">
                                Mit freundlichen Grüßen / Cordiali saluti
                            </p>
                            <p style="margin: 0; color: #2d3748; font-size: 14px; font-weight: 600;">
                                Resch GmbH – Kaminkehrer
                            </p>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="background-color: #edf2f7; padding: 20px 32px; border-radius: 0 0 8px 8px; border-top: 2px solid #e2e8f0;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="color: #718096; font-size: 12px; line-height: 1.8;">
                                        <strong style="color: #4a5568;">Resch GmbH / S.r.l.</strong><br>
                                        Kennedystr. 96 – 39011 Lana (BZ)<br>
                                        📞 338 4693481 &nbsp;&middot;&nbsp; ✉️ info@resch.bz
                                    </td>
                                    <td align="right" valign="top" style="color: #a0aec0; font-size: 11px;">
                                        Gesendet via UschiWeb<br>
                                        {{ now()->format('d.m.Y H:i') }}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

</body>
</html>
