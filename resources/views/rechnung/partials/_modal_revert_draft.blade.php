{{-- resources/views/rechnung/partials/_modal_revert_draft.blade.php --}}
{{-- ⭐ Modal: Rechnung zurück auf Entwurf setzen --}}

<div class="modal fade" id="modalRevertDraft" tabindex="-1" aria-labelledby="modalRevertDraftLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">

            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="modalRevertDraftLabel">
                    <i class="bi bi-arrow-counterclockwise"></i>
                    Zurück auf Entwurf setzen
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
            </div>

            <div class="modal-body">
                <div class="alert alert-warning py-2 mb-3">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <strong>Achtung:</strong> Rechnung <strong>{{ $rechnung->rechnungsnummer }}</strong>
                    wird von <strong>„{{ match($rechnung->status) {
                        'sent'      => 'Versendet',
                        'paid'      => 'Bezahlt',
                        'overdue'   => 'Überfällig',
                        'cancelled' => 'Storniert',
                        default     => $rechnung->status,
                    } }}"</strong> zurück auf <strong>„Entwurf"</strong> gesetzt.
                </div>

                <p class="small text-muted mb-3">
                    Bitte gib einen Grund an. Dieser wird im Rechnungs-Log gespeichert.
                </p>

                <form id="formRevertDraft" method="POST"
                      action="{{ route('rechnung.revertToDraft', $rechnung->id) }}">
                    @csrf
                    @method('PATCH')

                    <div class="mb-3">
                        <label for="revert_grund" class="form-label fw-semibold">
                            Grund <span class="text-danger">*</span>
                        </label>
                        <textarea
                            id="revert_grund"
                            name="grund"
                            class="form-control"
                            rows="3"
                            maxlength="500"
                            placeholder="z.B. Falscher Betrag, Empfänger korrigieren, ..."
                            required></textarea>
                        <div class="form-text text-end">
                            <span id="revert_grund_count">0</span>/500
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-lg"></i> Abbrechen
                        </button>
                        <button type="submit" class="btn btn-warning text-dark">
                            <i class="bi bi-arrow-counterclockwise"></i> Auf Entwurf setzen
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const textarea = document.getElementById('revert_grund');
    const counter  = document.getElementById('revert_grund_count');

    if (textarea && counter) {
        textarea.addEventListener('input', function () {
            counter.textContent = this.value.length;
        });

        // Textarea leeren wenn Modal geschlossen wird
        document.getElementById('modalRevertDraft')?.addEventListener('hidden.bs.modal', function () {
            textarea.value = '';
            counter.textContent = '0';
        });
    }
});
</script>
@endpush
