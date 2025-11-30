{{-- resources/views/emails/rechnung.blade.php --}}
{{-- ═══════════════════════════════════════════════════════════════════════════
     E-MAIL TEMPLATE FÜR RECHNUNGSVERSAND
     
     Variablen:
     - $rechnung    → Rechnung Model
     - $profil      → Unternehmensprofil Model  
     - $nachricht   → Benutzerdefinierte Nachricht (String)
     - $logoCid     → Logo als CID-Referenz (String|null)
═══════════════════════════════════════════════════════════════════════════ --}}
@php
    // ═══════════════════════════════════════════════════════════
    // LABELS AUS SPRACHDATEI MIT FALLBACK
    // ═══════════════════════════════════════════════════════════
    $labelFattura = __('email.labels.fattura') !== 'email.labels.fattura' 
        ? __('email.labels.fattura') 
        : 'Fattura / Rechnung';
    
    $labelNumeroFattura = __('email.labels.numero_fattura') !== 'email.labels.numero_fattura'
        ? __('email.labels.numero_fattura')
        : 'Numero fattura';
    
    $labelRechnungsnummer = __('email.labels.rechnungsnummer') !== 'email.labels.rechnungsnummer'
        ? __('email.labels.rechnungsnummer')
        : 'Rechnungsnummer';
    
    $labelDataFattura = __('email.labels.data_fattura') !== 'email.labels.data_fattura'
        ? __('email.labels.data_fattura')
        : 'Data fattura';
    
    $labelRechnungsdatum = __('email.labels.rechnungsdatum') !== 'email.labels.rechnungsdatum'
        ? __('email.labels.rechnungsdatum')
        : 'Rechnungsdatum';
    
    $labelDataScadenza = __('email.labels.data_scadenza') !== 'email.labels.data_scadenza'
        ? __('email.labels.data_scadenza')
        : 'Data scadenza';
    
    $labelFaelligkeitsdatum = __('email.labels.faelligkeitsdatum') !== 'email.labels.faelligkeitsdatum'
        ? __('email.labels.faelligkeitsdatum')
        : 'Fälligkeitsdatum';
    
    $labelImponibile = __('email.labels.imponibile') !== 'email.labels.imponibile'
        ? __('email.labels.imponibile')
        : 'Imponibile';
    
    $labelNettobetrag = __('email.labels.nettobetrag') !== 'email.labels.nettobetrag'
        ? __('email.labels.nettobetrag')
        : 'Nettobetrag';
    
    $labelIva = __('email.labels.iva') !== 'email.labels.iva'
        ? __('email.labels.iva')
        : 'IVA';
    
    $labelMwst = __('email.labels.mwst') !== 'email.labels.mwst'
        ? __('email.labels.mwst')
        : 'MwSt';
    
    $labelTotale = __('email.labels.totale') !== 'email.labels.totale'
        ? __('email.labels.totale')
        : 'Totale';
    
    $labelGesamtbetrag = __('email.labels.gesamtbetrag') !== 'email.labels.gesamtbetrag'
        ? __('email.labels.gesamtbetrag')
        : 'Gesamtbetrag';
    
    $labelAllegati = __('email.labels.allegati') !== 'email.labels.allegati'
        ? __('email.labels.allegati')
        : 'Allegati';
    
    $labelAnhaenge = __('email.labels.anhaenge') !== 'email.labels.anhaenge'
        ? __('email.labels.anhaenge')
        : 'Anhänge';
    
    $footerAutomatisch = __('email.allgemein.footer_automatisch') !== 'email.allgemein.footer_automatisch'
        ? __('email.allgemein.footer_automatisch')
        : "Questa e-mail è stata generata automaticamente.\nDiese E-Mail wurde automatisch generiert.";

    // ═══════════════════════════════════════════════════════════
    // FARBEN & WERTE
    // ═══════════════════════════════════════════════════════════
    $primaryColor = $profil->farbe_primaer ?? '#007bff';
    $secondaryColor = $profil->farbe_sekundaer ?? '#0056b3';
    
    $rechnungsdatum = $rechnung->rechnungsdatum ? $rechnung->rechnungsdatum->format('d.m.Y') : '-';
    $faelligkeitsdatum = $rechnung->faelligkeitsdatum ? $rechnung->faelligkeitsdatum->format('d.m.Y') : '-';
    $gesamtbetrag = number_format($rechnung->brutto_summe ?? $rechnung->gesamtbetrag_brutto ?? 0, 2, ',', '.');
    $nettobetrag = number_format($rechnung->netto_summe ?? $rechnung->nettobetrag ?? 0, 2, ',', '.');
    $mwstBetrag = number_format($rechnung->mwst_betrag ?? 0, 2, ',', '.');
    $mwstSatz = number_format($rechnung->mwst_satz ?? 22, 0);
@endphp
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $labelFattura }} {{ $rechnung->rechnungsnummer }}</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 650px; margin: 0 auto; padding: 20px; background-color: #f5f5f5;">
    
    <div style="background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow: hidden;">
        
        {{-- ═══════════════════════════════════════════════════════════
             HEADER MIT LOGO
        ═══════════════════════════════════════════════════════════ --}}
        <div style="background: linear-gradient(135deg, {{ $primaryColor }} 0%, {{ $secondaryColor }} 100%); color: #ffffff; padding: 30px; text-align: center;">
            <div style="margin-bottom: 15px;">
                @if($logoCid ?? null)
                    <img src="{{ $logoCid }}" alt="Logo" style="max-height: 60px; max-width: 200px;">
                @else
                    {{-- Fallback: Initialen --}}
                    <div style="font-size: 40px; font-weight: bold; color: #ffffff;">
                        {{ strtoupper(substr($profil->firmenname ?? 'F', 0, 2)) }}
                    </div>
                @endif
            </div>
            <div style="font-size: 24px; font-weight: bold; margin-bottom: 5px;">
                {{ $profil->firmenname ?? config('app.name', 'Firma') }}
            </div>
            @if($profil->firma_zusatz ?? null)
                <div style="font-size: 14px; opacity: 0.9;">{{ $profil->firma_zusatz }}</div>
            @endif
        </div>
        
        {{-- ═══════════════════════════════════════════════════════════
             CONTENT
        ═══════════════════════════════════════════════════════════ --}}
        <div style="padding: 30px;">
            
            {{-- RECHNUNGSTITEL --}}
            <div style="text-align: center; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 2px solid #e9ecef;">
                <h2 style="color: {{ $primaryColor }}; margin: 0; font-size: 20px;">{{ $labelFattura }}</h2>
                <div style="font-size: 28px; font-weight: bold; color: #333; margin-top: 5px;">
                    {{ $rechnung->rechnungsnummer }}
                </div>
            </div>
            
            {{-- NACHRICHT --}}
            <div style="white-space: pre-line; margin: 25px 0; padding: 20px; background-color: #f8f9fa; border-radius: 8px; border-left: 4px solid {{ $primaryColor }};">
                {!! nl2br(e($nachricht)) !!}
            </div>
            
            {{-- RECHNUNGSDETAILS --}}
            <div style="background-color: #ffffff; border: 1px solid #e9ecef; border-radius: 8px; padding: 20px; margin: 20px 0;">
                <h3 style="margin: 0 0 15px 0; color: #495057; font-size: 16px; border-bottom: 1px solid #e9ecef; padding-bottom: 10px;">
                    📋 Dettagli fattura / Rechnungsdetails
                </h3>
                <table style="width: 100%; border-collapse: collapse;">
                    {{-- Rechnungsnummer --}}
                    <tr>
                        <th style="text-align: left; padding: 8px 10px 8px 0; color: #666; font-weight: normal; width: 50%; font-size: 14px;">
                            {{ $labelNumeroFattura }}<br>
                            <span style="color: #888; font-size: 13px;">{{ $labelRechnungsnummer }}</span>
                        </th>
                        <td style="padding: 8px 0; font-weight: bold; font-size: 14px;">{{ $rechnung->rechnungsnummer }}</td>
                    </tr>
                    {{-- Rechnungsdatum --}}
                    <tr>
                        <th style="text-align: left; padding: 8px 10px 8px 0; color: #666; font-weight: normal; width: 50%; font-size: 14px;">
                            {{ $labelDataFattura }}<br>
                            <span style="color: #888; font-size: 13px;">{{ $labelRechnungsdatum }}</span>
                        </th>
                        <td style="padding: 8px 0; font-weight: bold; font-size: 14px;">{{ $rechnungsdatum }}</td>
                    </tr>
                    {{-- Fälligkeitsdatum --}}
                    @if($rechnung->faelligkeitsdatum)
                    <tr>
                        <th style="text-align: left; padding: 8px 10px 8px 0; color: #666; font-weight: normal; width: 50%; font-size: 14px;">
                            {{ $labelDataScadenza }}<br>
                            <span style="color: #888; font-size: 13px;">{{ $labelFaelligkeitsdatum }}</span>
                        </th>
                        <td style="padding: 8px 0; font-weight: bold; font-size: 14px;">{{ $faelligkeitsdatum }}</td>
                    </tr>
                    @endif
                    {{-- Nettobetrag --}}
                    <tr>
                        <th style="text-align: left; padding: 8px 10px 8px 0; color: #666; font-weight: normal; width: 50%; font-size: 14px;">
                            {{ $labelImponibile }}<br>
                            <span style="color: #888; font-size: 13px;">{{ $labelNettobetrag }}</span>
                        </th>
                        <td style="padding: 8px 0; font-weight: bold; font-size: 14px;">€ {{ $nettobetrag }}</td>
                    </tr>
                    {{-- MwSt --}}
                    <tr>
                        <th style="text-align: left; padding: 8px 10px 8px 0; color: #666; font-weight: normal; width: 50%; font-size: 14px;">
                            {{ $labelIva }} ({{ $mwstSatz }}%)<br>
                            <span style="color: #888; font-size: 13px;">{{ $labelMwst }}</span>
                        </th>
                        <td style="padding: 8px 0; font-weight: bold; font-size: 14px;">€ {{ $mwstBetrag }}</td>
                    </tr>
                    {{-- Gesamtbetrag --}}
                    <tr>
                        <th style="text-align: left; padding: 8px 10px 8px 0; color: #666; font-weight: normal; width: 50%; font-size: 14px; padding-top: 15px; border-top: 2px solid #e9ecef;">
                            <strong>{{ $labelTotale }}</strong><br>
                            <span style="color: #888; font-size: 13px;">{{ $labelGesamtbetrag }}</span>
                        </th>
                        <td style="padding: 8px 0; font-size: 22px; color: {{ $primaryColor }}; padding-top: 15px; border-top: 2px solid #e9ecef; font-weight: bold;">
                            € {{ $gesamtbetrag }}
                        </td>
                    </tr>
                </table>
            </div>
            
            {{-- ZAHLUNGSINFORMATIONEN --}}
            @if(($profil->iban ?? null) || ($profil->bank_name ?? null))
            <div style="background-color: #ffffff; border: 1px solid #e9ecef; border-radius: 8px; padding: 20px; margin: 20px 0;">
                <h3 style="margin: 0 0 15px 0; color: #495057; font-size: 16px; border-bottom: 1px solid #e9ecef; padding-bottom: 10px;">
                    💳 Modalità di pagamento / Zahlungsinformationen
                </h3>
                <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                    @if($profil->bank_name ?? null)
                    <tr>
                        <th style="text-align: left; padding: 6px 10px 6px 0; color: #666; font-weight: normal; width: 30%;">Bank / Banca:</th>
                        <td style="padding: 6px 0; font-weight: bold;">{{ $profil->bank_name }}</td>
                    </tr>
                    @endif
                    @if($profil->iban ?? null)
                    <tr>
                        <th style="text-align: left; padding: 6px 10px 6px 0; color: #666; font-weight: normal; width: 30%;">IBAN:</th>
                        <td style="padding: 6px 0; font-weight: bold; font-family: monospace;">{{ $profil->iban }}</td>
                    </tr>
                    @endif
                    @if($profil->bic ?? null)
                    <tr>
                        <th style="text-align: left; padding: 6px 10px 6px 0; color: #666; font-weight: normal; width: 30%;">BIC/SWIFT:</th>
                        <td style="padding: 6px 0; font-weight: bold; font-family: monospace;">{{ $profil->bic }}</td>
                    </tr>
                    @endif
                </table>
            </div>
            @endif
            
            {{-- ANHÄNGE-HINWEIS --}}
            <div style="background-color: #e8f4fd; padding: 15px 20px; border-radius: 8px; margin-top: 25px;">
                <h4 style="margin: 0 0 10px 0; color: #0056b3; font-size: 14px;">📎 {{ $labelAllegati }} / {{ $labelAnhaenge }}</h4>
                <ul style="margin: 0; padding-left: 20px; font-size: 14px;">
                    <li>Fattura in formato PDF / Rechnung im PDF-Format</li>
                    @if($rechnung->fattura_profile_id ?? null)
                        <li>XML FatturaPA (se allegato / falls angehängt)</li>
                    @endif
                </ul>
            </div>
            
        </div>
        
        {{-- ═══════════════════════════════════════════════════════════
             FOOTER
        ═══════════════════════════════════════════════════════════ --}}
        <div style="background-color: #f8f9fa; padding: 25px 30px; font-size: 12px; color: #666; border-top: 1px solid #e9ecef;">
            <div style="margin-bottom: 15px;">
                <strong style="color: #333; font-size: 14px;">{{ $profil->firmenname ?? config('app.name', 'Firma') }}</strong>
            </div>
            <div style="line-height: 1.8;">
                @if(($profil->strasse ?? null) || ($profil->hausnummer ?? null))
                    {{ $profil->strasse }} {{ $profil->hausnummer }}<br>
                @endif
                @if(($profil->postleitzahl ?? null) || ($profil->ort ?? null))
                    {{ $profil->postleitzahl }} {{ $profil->ort }}<br>
                @endif
                @if($profil->bundesland ?? null)
                    {{ $profil->bundesland }}<br>
                @endif
                <br>
                @if($profil->telefon ?? null)
                    Tel: {{ $profil->telefon }}<br>
                @endif
                @if($profil->email ?? null)
                    E-Mail: {{ $profil->email }}<br>
                @endif
                @if($profil->pec_email ?? null)
                    PEC: {{ $profil->pec_email }}<br>
                @endif
                @if($profil->website ?? null)
                    Web: {{ $profil->website }}
                @endif
            </div>
            <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd; font-size: 11px; color: #999;">
                @if(($profil->partita_iva ?? null) || ($profil->umsatzsteuer_id ?? null))
                    P.IVA / USt-IdNr.: {{ $profil->partita_iva ?? $profil->umsatzsteuer_id }}<br>
                @endif
                @if($profil->codice_fiscale ?? null)
                    Codice Fiscale: {{ $profil->codice_fiscale }}<br>
                @endif
                @if(($profil->rea_ufficio ?? null) && ($profil->rea_numero ?? null))
                    REA: {{ $profil->rea_ufficio }} - {{ $profil->rea_numero }}<br>
                @endif
                <br>
                {!! nl2br(e($footerAutomatisch)) !!}
            </div>
        </div>
        
    </div>
    
</body>
</html>