{{-- resources/views/rechnung/partials/_fattura_xml.blade.php --}}

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

{{-- ⭐ WICHTIG: Diese Komponente wird INNERHALB von _vorschau.blade.php (row g-4) eingebunden! --}}
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
            <button type="button" 
                    id="btn-generate-xml"
                    class="btn btn-primary w-100"
                    data-generate-url="{{ route('rechnung.xml.generate', $rechnung->id) }}">
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
            <button type="button" 
                    class="btn btn-outline-warning w-100" 
                    data-bs-toggle="modal" 
                    data-bs-target="#modalRegenerateXml">
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
              <button type="button" 
                      class="btn btn-outline-danger w-100 btn-sm" 
                      data-bs-toggle="modal" 
                      data-bs-target="#modalDeleteXml">
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
    MODALS
═══════════════════════════════════════════════════════════ --}}

{{-- Modal: Regenerate --}}
<div class="modal fade" id="modalRegenerateXml" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-warning">
        <h5 class="modal-title">XML neu generieren?</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-warning">
          <i class="bi bi-exclamation-triangle"></i>
          Das alte XML wird als "superseded" markiert und ein neues XML mit neuer Progressivo-Nummer generiert.
        </div>
        
        @if($xmlLog)
          <p><strong>Aktuelles XML:</strong></p>
          <ul class="small">
            <li>Progressivo: <code>{{ $xmlLog->progressivo_invio }}</code></li>
            <li>Status: {{ $xmlLog->status_text }}</li>
            <li>Erstellt: {{ $xmlLog->created_at->format('d.m.Y H:i') }}</li>
          </ul>
        @endif
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
        <button type="button" 
                id="btn-regenerate-xml"
                class="btn btn-warning"
                data-regenerate-url="{{ route('rechnung.xml.regenerate', $rechnung->id) }}">
          <i class="bi bi-arrow-clockwise"></i>
          Neu generieren
        </button>
      </div>
    </div>
  </div>
</div>

{{-- Modal: Delete --}}
@if($xmlLog && !$xmlLog->is_abgeschlossen)
<div class="modal fade" id="modalDeleteXml" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title">XML löschen?</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-danger">
          <i class="bi bi-exclamation-triangle"></i>
          Das XML und alle zugehörigen Dateien werden unwiderruflich gelöscht!
        </div>
        
        <p><strong>Zu löschendes XML:</strong></p>
        <ul class="small">
          <li>Progressivo: <code>{{ $xmlLog->progressivo_invio }}</code></li>
          <li>Datei: {{ $xmlLog->xml_filename }}</li>
          <li>Status: {{ $xmlLog->status_text }}</li>
        </ul>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
        <button type="button" 
                id="btn-delete-xml"
                class="btn btn-danger"
                data-delete-url="{{ route('fattura.xml.delete', $xmlLog->id) }}">
          <i class="bi bi-trash"></i>
          Löschen
        </button>
      </div>
    </div>
  </div>
</div>
@endif

{{-- ═══════════════════════════════════════════════════════════
    JAVASCRIPT (ohne verschachtelte Forms!)
═══════════════════════════════════════════════════════════ --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = '{{ csrf_token() }}';

    // XML generieren
    const btnGenerate = document.getElementById('btn-generate-xml');
    if (btnGenerate) {
        btnGenerate.addEventListener('click', function() {
            if (!confirm('FatturaPA XML jetzt generieren?')) return;
            
            const url = this.getAttribute('data-generate-url');
            
            fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                if (response.ok) {
                    window.location.reload();
                } else {
                    return response.json().then(data => {
                        alert('Fehler: ' + (data.message || 'Unbekannter Fehler'));
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Netzwerkfehler beim Generieren');
            });
        });
    }

    // XML regenerieren
    const btnRegenerate = document.getElementById('btn-regenerate-xml');
    if (btnRegenerate) {
        btnRegenerate.addEventListener('click', function() {
            const url = this.getAttribute('data-regenerate-url');
            
            fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                if (response.ok) {
                    window.location.reload();
                } else {
                    return response.json().then(data => {
                        alert('Fehler: ' + (data.message || 'Unbekannter Fehler'));
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Netzwerkfehler beim Regenerieren');
            });
        });
    }

    // XML löschen
    const btnDelete = document.getElementById('btn-delete-xml');
    if (btnDelete) {
        btnDelete.addEventListener('click', function() {
            const url = this.getAttribute('data-delete-url');
            
            fetch(url, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                if (response.ok) {
                    window.location.reload();
                } else {
                    return response.json().then(data => {
                        alert('Fehler: ' + (data.message || 'Unbekannter Fehler'));
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Netzwerkfehler beim Löschen');
            });
        });
    }
});
</script>

@endif {{-- Ende: nur wenn fattura_profile_id --}}