{{-- resources/views/rechnung/partials/_fattura_xml.blade.php --}}
{{-- ⭐ MIT E-MAIL-VERSAND FUNKTION + STATUS-ÄNDERUNG --}}
{{-- ⭐ Nutzt Sprachdatei: lang/de/email.php --}}

@php
    use App\Models\FatturaXmlLog;
    use App\Models\RechnungLog;
    use App\Enums\RechnungLogTyp;
    
    // Neuestes erfolgreiches XML-Log
    $xmlLog = FatturaXmlLog::where('rechnung_id', $rechnung->id)
        ->whereIn('status', [
            FatturaXmlLog::STATUS_GENERATED,
            FatturaXmlLog::STATUS_SIGNED,
            FatturaXmlLog::STATUS_SENT,
            FatturaXmlLog::STATUS_DELIVERED,
            FatturaXmlLog::STATUS_ACCEPTED,
        ])
        ->latest()
        ->first();
    
    // Alle Logs zählen
    $logsCount = FatturaXmlLog::where('rechnung_id', $rechnung->id)->count();
    
    // Letzte E-Mail-Versendungen
    $emailLogs = RechnungLog::where('rechnung_id', $rechnung->id)
        ->whereIn('typ', [
            RechnungLogTyp::EMAIL_VERSANDT->value,
            RechnungLogTyp::PEC_VERSANDT->value,
            RechnungLogTyp::PDF_VERSANDT->value,
        ])
        ->orderByDesc('created_at')
        ->limit(5)
        ->get();
    
    // Standard-Empfänger ermitteln
    $defaultEmail = $rechnung->re_email ?? $rechnung->rechnungsempfaenger?->email ?? '';
    $defaultPec = $rechnung->re_pec ?? '';
    
    // Status-Check für Buttons
    $istEntwurf = ($rechnung->status === 'draft');
    
    // Standard-E-Mail-Nachricht
    $standardNachrichtKey = 'email.rechnung.standard_nachricht';
    $standardNachricht = __($standardNachrichtKey, ['nummer' => $rechnung->rechnungsnummer]);
    
    if ($standardNachricht === $standardNachrichtKey) {
        $standardNachricht = "Gentili Signore e Signori,
Sehr geehrte Damen und Herren,

in allegato la nostra fattura n. {$rechnung->rechnungsnummer}.
Anbei erhalten Sie unsere Rechnung Nr. {$rechnung->rechnungsnummer}.

Cordiali saluti
Mit freundlichen Grüßen";
    }
@endphp

{{-- Nur anzeigen wenn FatturaPA-Profil zugeordnet ist --}}
@if($rechnung->fattura_profile_id)

<div class="row g-4">

    {{-- ═══════════════════════════════════════════════════════════
        ⭐ NEU: STATUS-BANNER (nur bei Entwurf)
    ═══════════════════════════════════════════════════════════ --}}
    @if($istEntwurf)
    <div class="col-12">
        <div class="alert alert-warning d-flex justify-content-between align-items-center mb-0">
            <div>
                <i class="bi bi-exclamation-triangle"></i>
                <strong>Status: Entwurf</strong> – Diese Rechnung wurde noch nicht versendet.
            </div>
            <button type="button" 
                    class="btn btn-warning"
                    onclick="markAsSent()">
                <i class="bi bi-check2-circle"></i>
                Als versendet markieren
            </button>
        </div>
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════
        CARD 1: XML STATUS & GENERIERUNG
    ═══════════════════════════════════════════════════════════ --}}
    <div class="col-12">
        <div class="card {{ $xmlLog ? 'border-success' : 'border-warning' }}">
            <div class="card-header {{ $xmlLog ? 'bg-success' : 'bg-warning' }} {{ $xmlLog ? 'text-white' : '' }}">
                <h6 class="mb-0">
                    <i class="bi bi-file-earmark-code"></i> 
                    FatturaPA XML
                    @if($logsCount > 0)
                        <span class="badge bg-light text-dark ms-2">{{ $logsCount }}</span>
                    @endif
                </h6>
            </div>
            <div class="card-body">
                
                {{-- KEIN XML VORHANDEN --}}
                @if(!$xmlLog)
                    
                    <div class="alert alert-info mb-3">
                        <i class="bi bi-info-circle"></i>
                        Noch kein FatturaPA XML generiert.
                        @if($istEntwurf)
                            <br><small class="text-muted">Das XML wird automatisch generiert wenn Sie die Rechnung versenden oder als versendet markieren.</small>
                        @endif
                    </div>
                    
                    <div class="row g-2">
                        <div class="col-md-6">
                            <button type="button" 
                                    class="btn btn-primary w-100"
                                    onclick="generateXml('{{ route('rechnung.xml.generate', $rechnung->id) }}')">
                                <i class="bi bi-file-earmark-plus"></i>
                                XML generieren
                            </button>
                        </div>
                        <div class="col-md-3">
                            <a href="{{ route('rechnung.xml.preview', $rechnung->id) }}" 
                               class="btn btn-outline-secondary w-100"
                               target="_blank">
                                <i class="bi bi-eye"></i>
                                Vorschau
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="{{ route('rechnung.xml.debug', $rechnung->id) }}" 
                               class="btn btn-outline-info w-100"
                               target="_blank">
                                <i class="bi bi-bug"></i>
                                Debug
                            </a>
                        </div>
                    </div>
                
                {{-- XML VORHANDEN --}}
                @else
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <div>
                                <strong>Status:</strong><br>
                                {!! $xmlLog->status_badge !!}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div>
                                <strong>Progressivo Invio:</strong><br>
                                <code class="fs-6">{{ $xmlLog->progressivo_invio }}</code>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <strong>Dateiname:</strong><br>
                            <span class="small text-break">{{ $xmlLog->xml_filename }}</span>
                        </div>
                        <div class="col-md-2">
                            <strong>Größe:</strong><br>
                            {{ $xmlLog->file_size_formatted }}
                        </div>
                        <div class="col-md-3">
                            <strong>Erstellt:</strong><br>
                            {{ $xmlLog->created_at->format('d.m.Y H:i') }}
                        </div>
                        <div class="col-md-3">
                            <strong>Validierung:</strong><br>
                            @if($xmlLog->is_valid)
                                <span class="badge bg-success">✓ Gültig</span>
                            @else
                                <span class="badge bg-warning">⚠ Nicht validiert</span>
                            @endif
                        </div>
                    </div>
                    
                    {{-- Validation Errors --}}
                    @if(!$xmlLog->is_valid && $xmlLog->validation_errors)
                        <div class="alert alert-warning mb-3">
                            <strong><i class="bi bi-exclamation-triangle"></i> Validierungs-Fehler:</strong>
                            <ul class="mb-0 mt-2 small">
                                @foreach($xmlLog->validation_errors as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    
                    {{-- Actions --}}
                    <div class="row g-2">
                        <div class="col-md-4">
                            <a href="{{ route('rechnung.xml.download', $rechnung->id) }}" 
                               class="btn btn-success w-100">
                                <i class="bi bi-download"></i>
                                Herunterladen
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="{{ route('rechnung.xml.preview', $rechnung->id) }}" 
                               class="btn btn-outline-secondary w-100"
                               target="_blank">
                                <i class="bi bi-eye"></i>
                                Vorschau
                            </a>
                        </div>
                        <div class="col-md-4">
                            <button type="button" 
                                    class="btn btn-outline-warning w-100"
                                    onclick="regenerateXml('{{ route('rechnung.xml.regenerate', $rechnung->id) }}')">
                                <i class="bi bi-arrow-clockwise"></i>
                                Neu generieren
                            </button>
                        </div>
                    </div>
                    
                    {{-- Zusätzliche Buttons --}}
                    <div class="row g-2 mt-2">
                        @if($logsCount > 1)
                            <div class="col-md-6">
                                <a href="{{ route('rechnung.xml.logs', $rechnung->id) }}" 
                                   class="btn btn-outline-info w-100 btn-sm">
                                    <i class="bi bi-clock-history"></i>
                                    Alle Logs ({{ $logsCount }})
                                </a>
                            </div>
                        @endif
                        
                        @if(!$xmlLog->is_abgeschlossen)
                            <div class="col-md-{{ $logsCount > 1 ? '6' : '12' }}">
                                <button type="button" 
                                        class="btn btn-outline-danger w-100 btn-sm"
                                        onclick="deleteXml('{{ route('fattura.xml.delete', $xmlLog->id) }}')">
                                    <i class="bi bi-trash"></i>
                                    Löschen
                                </button>
                            </div>
                        @endif
                    </div>
                    
                @endif
                
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
        CARD 2: E-MAIL VERSAND
    ═══════════════════════════════════════════════════════════ --}}
    <div class="col-12">
        <div class="card border-primary">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0">
                    <i class="bi bi-envelope"></i> 
                    E-Mail Versand
                    @if($emailLogs->count() > 0)
                        <span class="badge bg-light text-dark ms-2">{{ $emailLogs->count() }} gesendet</span>
                    @endif
                </h6>
            </div>
            <div class="card-body">
                
                {{-- ⭐ Hinweis wenn keine E-Mail-Adresse --}}
                @if(empty($defaultEmail) && empty($defaultPec))
                    <div class="alert alert-warning mb-3">
                        <i class="bi bi-exclamation-triangle"></i>
                        <strong>Keine E-Mail-Adresse hinterlegt!</strong>
                        <br>Sie können die Rechnung trotzdem als "Versendet" markieren.
                        @if($istEntwurf)
                            <br>
                            <button type="button" 
                                    class="btn btn-warning btn-sm mt-2"
                                    onclick="markAsSent()">
                                <i class="bi bi-check2-circle"></i>
                                Jetzt als versendet markieren
                            </button>
                        @endif
                    </div>
                @endif
                
                {{-- E-Mail Formular --}}
                <div class="row g-3">
                    
                    {{-- Empfänger --}}
                    <div class="col-md-6">
                        <label class="form-label">
                            <i class="bi bi-envelope"></i> E-Mail Empfänger
                        </label>
                        <input type="email" 
                               class="form-control" 
                               id="email_empfaenger" 
                               value="{{ $defaultEmail }}"
                               placeholder="email@example.com">
                    </div>
                    
                    {{-- PEC (optional) --}}
                    <div class="col-md-6">
                        <label class="form-label">
                            <i class="bi bi-envelope-check"></i> PEC (optional)
                        </label>
                        <input type="email" 
                               class="form-control" 
                               id="email_pec" 
                               value="{{ $defaultPec }}"
                               placeholder="pec@pec.example.it">
                    </div>
                    
                    {{-- Betreff --}}
                    <div class="col-12">
                        <label class="form-label">Betreff</label>
                        <input type="text" 
                               class="form-control" 
                               id="email_betreff" 
                               value="Fattura {{ $rechnung->rechnungsnummer }} - {{ $rechnung->re_name }}">
                    </div>
                    
                    {{-- Nachricht --}}
                    <div class="col-12">
                        <label class="form-label">Nachricht</label>
                        <textarea class="form-control" 
                                  id="email_nachricht" 
                                  rows="6"
                                  placeholder="Nachricht eingeben...">{{ $standardNachricht }}</textarea>
                    </div>
                    
                    {{-- Anhänge auswählen --}}
                    <div class="col-12">
                        <label class="form-label">Anhänge</label>
                        <div class="row g-2">
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="attach_pdf" checked>
                                    <label class="form-check-label" for="attach_pdf">
                                        <i class="bi bi-file-earmark-pdf text-danger"></i> 
                                        PDF-Rechnung
                                    </label>
                                </div>
                            </div>
                            @if($xmlLog)
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="attach_xml">
                                    <label class="form-check-label" for="attach_xml">
                                        <i class="bi bi-file-earmark-code text-success"></i> 
                                        FatturaPA XML
                                    </label>
                                </div>
                            </div>
                            @endif
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="attach_copy_me">
                                    <label class="form-check-label" for="attach_copy_me">
                                        <i class="bi bi-person"></i> 
                                        Kopie an mich
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                </div>
                
                {{-- Senden Buttons --}}
                <div class="row g-2 mt-3">
                    <div class="col-md-4">
                        <button type="button" 
                                class="btn btn-primary w-100"
                                onclick="sendEmail('email')">
                            <i class="bi bi-send"></i>
                            Per E-Mail senden
                        </button>
                    </div>
                    @if($defaultPec)
                    <div class="col-md-4">
                        <button type="button" 
                                class="btn btn-success w-100"
                                onclick="sendEmail('pec')">
                            <i class="bi bi-send-check"></i>
                            Per PEC senden
                        </button>
                    </div>
                    @endif
                    <div class="col-md-{{ $defaultPec ? '4' : '4' }}">
                        <button type="button" 
                                class="btn btn-outline-secondary w-100"
                                onclick="previewEmail()">
                            <i class="bi bi-eye"></i>
                            Vorschau
                        </button>
                    </div>
                    @if(!$defaultPec && $istEntwurf)
                    <div class="col-md-4">
                        <button type="button" 
                                class="btn btn-outline-warning w-100"
                                onclick="markAsSent()">
                            <i class="bi bi-check2-circle"></i>
                            Nur markieren
                        </button>
                    </div>
                    @endif
                </div>
                
                {{-- Info-Hinweis --}}
                @if($istEntwurf)
                <div class="alert alert-info mt-3 mb-0 small">
                    <i class="bi bi-info-circle"></i>
                    Nach dem Versenden wird der Status automatisch auf <strong>"Versendet"</strong> gesetzt und das XML generiert (falls noch nicht vorhanden).
                </div>
                @endif
                
                {{-- Versand-Historie --}}
                @if($emailLogs->count() > 0)
                <div class="mt-4 pt-3 border-top">
                    <h6 class="mb-2">
                        <i class="bi bi-clock-history"></i> Letzte Versendungen
                    </h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Datum</th>
                                    <th>Typ</th>
                                    <th>Empfänger</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($emailLogs as $log)
                                <tr>
                                    <td class="small">{{ $log->created_at->format('d.m.Y H:i') }}</td>
                                    <td>
                                        @if($log->typ === RechnungLogTyp::PEC_VERSANDT->value)
                                            <span class="badge bg-success">PEC</span>
                                        @else
                                            <span class="badge bg-primary">E-Mail</span>
                                        @endif
                                    </td>
                                    <td class="small">{{ $log->metadata['empfaenger'] ?? '-' }}</td>
                                    <td>
                                        <span class="badge bg-success">✓ Gesendet</span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif
                
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
        CARD 3: PROFIL-INFO
    ═══════════════════════════════════════════════════════════ --}}
    @if($rechnung->fatturaProfile)
    <div class="col-md-6">
        <div class="card border-info h-100">
            <div class="card-header bg-info text-white py-2">
                <h6 class="mb-0">
                    <i class="bi bi-person-badge"></i> 
                    FatturaPA-Profil
                </h6>
            </div>
            <div class="card-body small">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <th width="40%">Bezeichnung:</th>
                        <td>{{ $rechnung->fatturaProfile->bezeichnung }}</td>
                    </tr>
                    <tr>
                        <th>MwSt-Satz:</th>
                        <td>{{ number_format($rechnung->mwst_satz, 2, ',', '.') }}%</td>
                    </tr>
                    @if($rechnung->split_payment)
                    <tr>
                        <th>Split Payment:</th>
                        <td><span class="badge bg-warning text-dark">Aktiv</span></td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════
        CARD 4: EMPFÄNGER SDI-DATEN
    ═══════════════════════════════════════════════════════════ --}}
    <div class="col-md-6">
        <div class="card border-secondary h-100">
            <div class="card-header bg-secondary text-white py-2">
                <h6 class="mb-0">
                    <i class="bi bi-building"></i> 
                    Empfänger SDI-Daten
                </h6>
            </div>
            <div class="card-body small">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <th width="40%">Codice Univoco:</th>
                        <td>
                            @if($rechnung->re_codice_univoco)
                                <code>{{ $rechnung->re_codice_univoco }}</code>
                            @else
                                <span class="text-danger">⚠ Fehlt!</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>PEC:</th>
                        <td>{{ $rechnung->re_pec ?: '-' }}</td>
                    </tr>
                    <tr>
                        <th>P.IVA:</th>
                        <td>{{ $rechnung->re_mwst_nummer ?: '-' }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

</div>

{{-- ═══════════════════════════════════════════════════════════
    JAVASCRIPT
═══════════════════════════════════════════════════════════ --}}
<script>
const csrfToken = '{{ csrf_token() }}';
const rechnungId = {{ $rechnung->id }};
const sendEmailUrl = '{{ route('rechnung.email.send', $rechnung->id) }}';
const markAsSentUrl = '{{ route('rechnung.mark-sent', $rechnung->id) }}';

function submitDynamicForm(url, method, additionalData = {}) {
    var form = document.createElement('form');
    form.method = 'POST';
    form.action = url;
    form.style.display = 'none';
    
    var csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = csrfToken;
    form.appendChild(csrfInput);
    
    if (method && method !== 'POST') {
        var methodInput = document.createElement('input');
        methodInput.type = 'hidden';
        methodInput.name = '_method';
        methodInput.value = method;
        form.appendChild(methodInput);
    }
    
    for (const [key, value] of Object.entries(additionalData)) {
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = key;
        input.value = value;
        form.appendChild(input);
    }
    
    document.body.appendChild(form);
    form.submit();
}

function generateXml(url) {
    if (!confirm('FatturaPA XML jetzt generieren?')) return;
    submitDynamicForm(url, 'POST');
}

function regenerateXml(url) {
    if (!confirm('XML neu generieren?\n\nDas alte XML wird als "superseded" markiert.')) return;
    submitDynamicForm(url, 'POST');
}

function deleteXml(url) {
    if (!confirm('XML wirklich löschen?')) return;
    submitDynamicForm(url, 'DELETE');
}

// ⭐ NEU: Als versendet markieren (ohne E-Mail)
function markAsSent() {
    if (!confirm('Rechnung als "Versendet" markieren?\n\nDas XML wird automatisch generiert falls noch nicht vorhanden.')) return;
    submitDynamicForm(markAsSentUrl, 'POST');
}

function sendEmail(typ) {
    var empfaenger = document.getElementById('email_empfaenger').value;
    var pec = document.getElementById('email_pec').value;
    var betreff = document.getElementById('email_betreff').value;
    var nachricht = document.getElementById('email_nachricht').value;
    var attachPdf = document.getElementById('attach_pdf').checked;
    var attachXml = document.getElementById('attach_xml') ? document.getElementById('attach_xml').checked : false;
    var copyMe = document.getElementById('attach_copy_me').checked;
    
    // ⭐ NEU: Auch ohne E-Mail-Adresse erlauben (Status wird trotzdem geändert)
    if (typ === 'pec' && !pec) { 
        if (confirm('Keine PEC-Adresse angegeben.\n\nRechnung trotzdem als "Versendet" markieren?')) {
            markAsSent();
        }
        return; 
    }
    if (typ === 'email' && !empfaenger) { 
        if (confirm('Keine E-Mail-Adresse angegeben.\n\nRechnung trotzdem als "Versendet" markieren?')) {
            markAsSent();
        }
        return; 
    }
    if (!betreff) { alert('Bitte Betreff eingeben!'); return; }
    
    var zielAdresse = typ === 'pec' ? pec : empfaenger;
    var typLabel = typ === 'pec' ? 'PEC' : 'E-Mail';
    
    if (!confirm('Rechnung per ' + typLabel + ' senden an:\n\n' + zielAdresse + '\n\nDer Status wird automatisch auf "Versendet" gesetzt.\nFortfahren?')) return;
    
    submitDynamicForm(sendEmailUrl, 'POST', {
        typ: typ,
        empfaenger: empfaenger,
        pec: pec,
        betreff: betreff,
        nachricht: nachricht,
        attach_pdf: attachPdf ? '1' : '0',
        attach_xml: attachXml ? '1' : '0',
        copy_me: copyMe ? '1' : '0',
    });
}

function previewEmail() {
    var betreff = document.getElementById('email_betreff').value;
    var nachricht = document.getElementById('email_nachricht').value;
    var attachPdf = document.getElementById('attach_pdf').checked;
    var attachXml = document.getElementById('attach_xml') ? document.getElementById('attach_xml').checked : false;
    
    var anhaenge = [];
    if (attachPdf) anhaenge.push('PDF');
    if (attachXml) anhaenge.push('XML');
    
    alert('═══════════════════════════════════\nE-MAIL VORSCHAU\n═══════════════════════════════════\n\nBetreff: ' + betreff + '\n\n───────────────────────────────────\n' + nachricht + '\n───────────────────────────────────\n\nAnhänge: ' + (anhaenge.length > 0 ? anhaenge.join(', ') : 'Keine'));
}
</script>

@endif
