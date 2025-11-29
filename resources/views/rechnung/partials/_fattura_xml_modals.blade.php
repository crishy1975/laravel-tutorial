{{-- resources/views/rechnung/partials/_fattura_xml_modals.blade.php --}}
{{-- ⭐ MODALS UND JAVASCRIPT - werden AUSSERHALB des Hauptformulars eingebunden! --}}

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
@endphp

{{-- ═══════════════════════════════════════════════════════════
    MODAL: XML NEU GENERIEREN
═══════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="modalRegenerateXml" tabindex="-1" aria-labelledby="modalRegenerateXmlLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title" id="modalRegenerateXmlLabel">
                    <i class="bi bi-arrow-clockwise"></i> XML neu generieren?
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle"></i>
                    Das alte XML wird als "superseded" markiert und ein neues XML mit <strong>neuer Progressivo-Nummer</strong> generiert.
                </div>
                
                @if($xmlLog)
                    <p><strong>Aktuelles XML:</strong></p>
                    <table class="table table-sm">
                        <tr>
                            <th>Progressivo:</th>
                            <td><code>{{ $xmlLog->progressivo_invio }}</code></td>
                        </tr>
                        <tr>
                            <th>Status:</th>
                            <td>{!! $xmlLog->status_badge !!}</td>
                        </tr>
                        <tr>
                            <th>Erstellt:</th>
                            <td>{{ $xmlLog->created_at->format('d.m.Y H:i') }}</td>
                        </tr>
                    </table>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x"></i> Abbrechen
                </button>
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

{{-- ═══════════════════════════════════════════════════════════
    MODAL: XML LÖSCHEN
═══════════════════════════════════════════════════════════ --}}
@if($xmlLog && !$xmlLog->is_abgeschlossen)
<div class="modal fade" id="modalDeleteXml" tabindex="-1" aria-labelledby="modalDeleteXmlLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="modalDeleteXmlLabel">
                    <i class="bi bi-trash"></i> XML löschen?
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Schließen"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle"></i>
                    <strong>Achtung!</strong> Das XML und alle zugehörigen Dateien werden <strong>unwiderruflich</strong> gelöscht!
                </div>
                
                <p><strong>Zu löschendes XML:</strong></p>
                <table class="table table-sm">
                    <tr>
                        <th>Progressivo:</th>
                        <td><code>{{ $xmlLog->progressivo_invio }}</code></td>
                    </tr>
                    <tr>
                        <th>Datei:</th>
                        <td class="small text-break">{{ $xmlLog->xml_filename }}</td>
                    </tr>
                    <tr>
                        <th>Status:</th>
                        <td>{!! $xmlLog->status_badge !!}</td>
                    </tr>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x"></i> Abbrechen
                </button>
                <button type="button" 
                        id="btn-delete-xml"
                        class="btn btn-danger"
                        data-delete-url="{{ route('fattura.xml.delete', $xmlLog->id) }}">
                    <i class="bi bi-trash"></i>
                    Endgültig löschen
                </button>
            </div>
        </div>
    </div>
</div>
@endif

{{-- ═══════════════════════════════════════════════════════════
    JAVASCRIPT - Form-Submit statt AJAX (funktioniert mit Redirects!)
═══════════════════════════════════════════════════════════ --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = '{{ csrf_token() }}';

    /**
     * Hilfsfunktion: Erstellt und submittet ein dynamisches Form
     */
    function submitForm(url, method = 'POST') {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = url;
        form.style.display = 'none';
        
        // CSRF Token
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = csrfToken;
        form.appendChild(csrfInput);
        
        // Method Spoofing (für DELETE etc.)
        if (method !== 'POST') {
            const methodInput = document.createElement('input');
            methodInput.type = 'hidden';
            methodInput.name = '_method';
            methodInput.value = method;
            form.appendChild(methodInput);
        }
        
        document.body.appendChild(form);
        form.submit();
    }

    // ═══════════════════════════════════════════════════════════
    // XML GENERIEREN (erstmalig)
    // ═══════════════════════════════════════════════════════════
    const btnGenerate = document.getElementById('btn-generate-xml');
    if (btnGenerate) {
        btnGenerate.addEventListener('click', function(e) {
            e.preventDefault();
            
            if (!confirm('FatturaPA XML jetzt generieren?')) return;
            
            // Button deaktivieren & Spinner anzeigen
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Generiere...';
            
            const url = this.getAttribute('data-generate-url');
            submitForm(url, 'POST');
        });
    }

    // ═══════════════════════════════════════════════════════════
    // XML REGENERIEREN
    // ═══════════════════════════════════════════════════════════
    const btnRegenerate = document.getElementById('btn-regenerate-xml');
    if (btnRegenerate) {
        btnRegenerate.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Button deaktivieren & Spinner anzeigen
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Generiere...';
            
            // Modal schließen
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalRegenerateXml'));
            if (modal) modal.hide();
            
            const url = this.getAttribute('data-regenerate-url');
            submitForm(url, 'POST');
        });
    }

    // ═══════════════════════════════════════════════════════════
    // XML LÖSCHEN
    // ═══════════════════════════════════════════════════════════
    const btnDelete = document.getElementById('btn-delete-xml');
    if (btnDelete) {
        btnDelete.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Button deaktivieren & Spinner anzeigen
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Lösche...';
            
            // Modal schließen
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalDeleteXml'));
            if (modal) modal.hide();
            
            const url = this.getAttribute('data-delete-url');
            submitForm(url, 'DELETE');
        });
    }
});
</script>