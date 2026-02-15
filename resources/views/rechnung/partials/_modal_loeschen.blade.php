{{-- resources/views/rechnung/partials/_modal_loeschen.blade.php --}}
{{-- ⭐ Lösch-Modal mit Begründungsfeld --}}

<div class="modal fade" id="modalLoeschen" tabindex="-1" aria-labelledby="modalLoeschenLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-danger">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="modalLoeschenLabel">
                    <i class="bi bi-trash3"></i> Rechnung löschen
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Schließen"></button>
            </div>
            
            <form id="formLoeschen" method="POST" action="{{ route('rechnung.destroy', $rechnung->id) }}">
                @csrf
                @method('DELETE')
                
                <div class="modal-body">
                    {{-- Warnung --}}
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Achtung!</strong> Diese Aktion kann nicht rückgängig gemacht werden.
                    </div>
                    
                    {{-- Rechnungsinfo --}}
                    <div class="card bg-light mb-3">
                        <div class="card-body py-2">
                            <div class="row small">
                                <div class="col-6">
                                    <strong>Rechnungsnummer:</strong><br>
                                    {{ $rechnung->rechnungsnummer }}
                                </div>
                                <div class="col-6">
                                    <strong>Betrag:</strong><br>
                                    {{ number_format($rechnung->brutto_summe, 2, ',', '.') }} €
                                </div>
                                <div class="col-6 mt-2">
                                    <strong>Kunde:</strong><br>
                                    {{ $rechnung->re_name ?: '-' }}
                                </div>
                                <div class="col-6 mt-2">
                                    <strong>Status:</strong><br>
                                    {!! $rechnung->status_badge !!}
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Begründung (Pflichtfeld) --}}
                    <div class="mb-3">
                        <label for="loeschgrund" class="form-label">
                            <strong>Begründung für die Löschung</strong> <span class="text-danger">*</span>
                        </label>
                        <textarea 
                            id="loeschgrund" 
                            name="loeschgrund" 
                            class="form-control" 
                            rows="3" 
                            required
                            minlength="10"
                            placeholder="z.B. Duplikat, Testrechnung, Fehlerhafte Eingabe..."
                        ></textarea>
                        <div class="form-text">
                            <i class="bi bi-info-circle me-1"></i>
                            Mindestens 10 Zeichen. Die Begründung wird im Log gespeichert.
                        </div>
                    </div>

                    {{-- Schnellauswahl-Buttons --}}
                    <div class="mb-3">
                        <label class="form-label small text-muted">Schnellauswahl:</label>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm quick-reason" data-reason="Duplikat - Rechnung wurde doppelt erstellt">
                                Duplikat
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm quick-reason" data-reason="Testrechnung - Wurde nur zu Testzwecken erstellt">
                                Testrechnung
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm quick-reason" data-reason="Fehlerhafte Daten - Rechnungsdaten sind falsch und müssen neu erstellt werden">
                                Fehlerhafte Daten
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm quick-reason" data-reason="Kundenauftrag storniert - Leistung wird nicht mehr erbracht">
                                Storniert
                            </button>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg"></i> Abbrechen
                    </button>
                    <button type="submit" class="btn btn-danger" id="btnLoeschenConfirm">
                        <i class="bi bi-trash3"></i> Endgültig löschen
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- JavaScript für Schnellauswahl --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Schnellauswahl-Buttons
    const quickButtons = document.querySelectorAll('.quick-reason');
    const textarea = document.getElementById('loeschgrund');
    
    quickButtons.forEach(function(btn) {
        btn.addEventListener('click', function() {
            textarea.value = this.dataset.reason;
            textarea.focus();
            
            // Button visuell hervorheben
            quickButtons.forEach(b => b.classList.remove('btn-secondary', 'active'));
            quickButtons.forEach(b => b.classList.add('btn-outline-secondary'));
            this.classList.remove('btn-outline-secondary');
            this.classList.add('btn-secondary', 'active');
        });
    });

    // Formular-Validierung
    const form = document.getElementById('formLoeschen');
    const submitBtn = document.getElementById('btnLoeschenConfirm');
    
    form.addEventListener('submit', function(e) {
        if (textarea.value.trim().length < 10) {
            e.preventDefault();
            textarea.classList.add('is-invalid');
            alert('Bitte geben Sie eine Begründung mit mindestens 10 Zeichen ein.');
            return false;
        }
        
        // Doppelklick verhindern
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Wird gelöscht...';
    });

    // Validierungs-Feedback entfernen bei Eingabe
    textarea.addEventListener('input', function() {
        if (this.value.trim().length >= 10) {
            this.classList.remove('is-invalid');
        }
    });
});
</script>
