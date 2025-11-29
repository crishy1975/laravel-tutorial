{{-- resources/views/rechnung/partials/_fattura_xml.blade.php --}}
{{-- ⭐ KORRIGIERT: Form-Submit statt fetch() für Laravel Redirects --}}

@php
    use App\Models\FatturaXmlLog;
    
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
@endphp

{{-- Nur anzeigen wenn FatturaPA-Profil zugeordnet ist --}}
@if($rechnung->fattura_profile_id)

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
      
      {{-- ═══════════════════════════════════════════════════════════
          KEIN XML VORHANDEN
      ═══════════════════════════════════════════════════════════ --}}
      @if(!$xmlLog)
        
        <div class="alert alert-info mb-3">
          <i class="bi bi-info-circle"></i>
          Noch kein FatturaPA XML generiert.
        </div>
        
        <div class="row g-2">
          <div class="col-md-6">
            {{-- ⭐ KORRIGIERT: onclick mit Form-Submit --}}
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
               target="_blank"
               title="Debug-Informationen">
              <i class="bi bi-bug"></i>
              Debug
            </a>
          </div>
        </div>
      
      {{-- ═══════════════════════════════════════════════════════════
          XML VORHANDEN
      ═══════════════════════════════════════════════════════════ --}}
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
            {{-- ⭐ KORRIGIERT: confirm() statt Modal --}}
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
            <div class="col-md-6">
              {{-- ⭐ KORRIGIERT: confirm() statt Modal --}}
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

{{-- ═══════════════════════════════════════════════════════════
    JAVASCRIPT - Einfache Funktionen mit Form-Submit und confirm()
═══════════════════════════════════════════════════════════ --}}
<script>
/**
 * Hilfsfunktion: Erstellt und submittet ein dynamisches Form
 */
function submitDynamicForm(url, method) {
    var form = document.createElement('form');
    form.method = 'POST';
    form.action = url;
    form.style.display = 'none';
    
    // CSRF Token
    var csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = '{{ csrf_token() }}';
    form.appendChild(csrfInput);
    
    // Method Spoofing (für DELETE etc.)
    if (method && method !== 'POST') {
        var methodInput = document.createElement('input');
        methodInput.type = 'hidden';
        methodInput.name = '_method';
        methodInput.value = method;
        form.appendChild(methodInput);
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
</script>

@endif {{-- Ende: nur wenn fattura_profile_id --}}