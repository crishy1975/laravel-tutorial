{{-- resources/views/rechnung/partials/_fattura_xml.blade.php --}}
{{-- ⭐ MIT E-MAIL-VERSAND FUNKTION --}}

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
@endphp

{{-- Nur anzeigen wenn FatturaPA-Profil zugeordnet ist --}}
@if($rechnung->fattura_profile_id)

<div class="row g-4">

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
                                Preview
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
                                XML herunterladen
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="{{ route('rechnung.xml.preview', $rechnung->id) }}" 
                               class="btn btn-outline-secondary w-100"
                               target="_blank">
                                <i class="bi bi-eye"></i>
                                Preview
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
                               value="Rechnung {{ $rechnung->rechnungsnummer }} - {{ $rechnung->re_name }}">
                    </div>
                    
                    {{-- Nachricht --}}
                    <div class="col-12">
                        <label class="form-label">Nachricht</label>
                        <textarea class="form-control" 
                                  id="email_nachricht" 
                                  rows="4"
                                  placeholder="Optionale Nachricht an den Empfänger...">Sehr geehrte Damen und Herren,

anbei erhalten Sie unsere Rechnung Nr. {{ $rechnung->rechnungsnummer }}.

Mit freundlichen Grüßen</textarea>
                    </div>
                    
                    {{-- Anhänge auswählen --}}
                    <div class="col-12">
                        <label class="form-label">Anhänge</label>
                        <div class="row g-2">
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="attach_pdf" checked>
                                    <label class="form-check-label" for="attach_pdf">
                                        <i class="bi bi-file-earmark-pdf text-danger"></i> PDF-Rechnung
                                    </label>
                                </div>
                            </div>
                            @if($xmlLog)
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="attach_xml">
                                    <label class="form-check-label" for="attach_xml">
                                        <i class="bi bi-file-earmark-code text-success"></i> FatturaPA XML
                                    </label>
                                </div>
                            </div>
                            @endif
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="attach_copy_me">
                                    <label class="form-check-label" for="attach_copy_me">
                                        <i class="bi bi-person"></i> Kopie an mich
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
                    <div class="col-md-4">
                        <button type="button" 
                                class="btn btn-outline-secondary w-100"
                                onclick="previewEmail()">
                            <i class="bi bi-eye"></i>
                            Vorschau
                        </button>
                    </div>
                </div>
                
                {{-- Letzte Versendungen --}}
                @if($emailLogs->count() > 0)
                    <hr class="my-4">
                    <h6 class="text-muted mb-3">
                        <i class="bi bi-clock-history"></i> Letzte Versendungen
                    </h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Datum</th>
                                    <th>Typ</th>
                                    <th>Empfänger</th>
                                    <th>Benutzer</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($emailLogs as $log)
                                    <tr>
                                        <td class="small">{{ $log->created_at->format('d.m.Y H:i') }}</td>
                                        <td>{!! $log->typ_badge !!}</td>
                                        <td class="small">
                                            {{ $log->metadata['empfaenger'] ?? $log->kontakt_email ?? '-' }}
                                        </td>
                                        <td class="small">{{ $log->benutzer_name }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
                
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
        CARD 3: PROFIL & EMPFÄNGER INFO (Kompakt)
    ═══════════════════════════════════════════════════════════ --}}
    @if($rechnung->fatturaProfile)
    <div class="col-md-6">
        <div class="card border-info h-100">
            <div class="card-header bg-info text-white py-2">
                <h6 class="mb-0"><i class="bi bi-person-badge"></i> FatturaPA-Profil</h6>
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

    <div class="col-md-6">
        <div class="card border-secondary h-100">
            <div class="card-header bg-secondary text-white py-2">
                <h6 class="mb-0"><i class="bi bi-building"></i> Empfänger SDI-Daten</h6>
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
                        <th>MwSt-Nummer:</th>
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

/**
 * Hilfsfunktion: Erstellt und submittet ein dynamisches Form
 */
function submitDynamicForm(url, method, additionalData = {}) {
    var form = document.createElement('form');
    form.method = 'POST';
    form.action = url;
    form.style.display = 'none';
    
    // CSRF Token
    var csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = csrfToken;
    form.appendChild(csrfInput);
    
    // Method Spoofing
    if (method && method !== 'POST') {
        var methodInput = document.createElement('input');
        methodInput.type = 'hidden';
        methodInput.name = '_method';
        methodInput.value = method;
        form.appendChild(methodInput);
    }
    
    // Zusätzliche Daten
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

/**
 * XML generieren (erstmalig)
 */
function generateXml(url) {
    if (!confirm('FatturaPA XML jetzt generieren?')) {
        return;
    }
    submitDynamicForm(url, 'POST');
}

/**
 * XML neu generieren
 */
function regenerateXml(url) {
    if (!confirm('XML neu generieren?\n\nDas alte XML wird als "superseded" markiert und ein neues XML mit neuer Progressivo-Nummer generiert.')) {
        return;
    }
    submitDynamicForm(url, 'POST');
}

/**
 * XML löschen
 */
function deleteXml(url) {
    if (!confirm('XML wirklich löschen?\n\nDas XML und alle zugehörigen Dateien werden unwiderruflich gelöscht!')) {
        return;
    }
    submitDynamicForm(url, 'DELETE');
}

/**
 * E-Mail senden
 */
function sendEmail(typ) {
    var empfaenger = document.getElementById('email_empfaenger').value;
    var pec = document.getElementById('email_pec').value;
    var betreff = document.getElementById('email_betreff').value;
    var nachricht = document.getElementById('email_nachricht').value;
    var attachPdf = document.getElementById('attach_pdf').checked;
    var attachXml = document.getElementById('attach_xml') ? document.getElementById('attach_xml').checked : false;
    var copyMe = document.getElementById('attach_copy_me').checked;
    
    // Validierung
    if (typ === 'pec' && !pec) {
        alert('Bitte PEC-Adresse eingeben!');
        return;
    }
    if (typ === 'email' && !empfaenger) {
        alert('Bitte E-Mail-Adresse eingeben!');
        return;
    }
    if (!betreff) {
        alert('Bitte Betreff eingeben!');
        return;
    }
    
    var zielAdresse = typ === 'pec' ? pec : empfaenger;
    var typLabel = typ === 'pec' ? 'PEC' : 'E-Mail';
    
    if (!confirm('Rechnung per ' + typLabel + ' senden an:\n\n' + zielAdresse + '\n\nFortfahren?')) {
        return;
    }
    
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

/**
 * E-Mail Vorschau
 */
function previewEmail() {
    var betreff = document.getElementById('email_betreff').value;
    var nachricht = document.getElementById('email_nachricht').value;
    var attachPdf = document.getElementById('attach_pdf').checked;
    var attachXml = document.getElementById('attach_xml') ? document.getElementById('attach_xml').checked : false;
    
    var anhaenge = [];
    if (attachPdf) anhaenge.push('PDF-Rechnung');
    if (attachXml) anhaenge.push('FatturaPA XML');
    
    var preview = '═══════════════════════════════════\n';
    preview += 'E-MAIL VORSCHAU\n';
    preview += '═══════════════════════════════════\n\n';
    preview += 'Betreff: ' + betreff + '\n\n';
    preview += '───────────────────────────────────\n';
    preview += nachricht + '\n';
    preview += '───────────────────────────────────\n\n';
    preview += 'Anhänge: ' + (anhaenge.length > 0 ? anhaenge.join(', ') : 'Keine');
    
    alert(preview);
}
</script>

@endif {{-- Ende: nur wenn fattura_profile_id --}}