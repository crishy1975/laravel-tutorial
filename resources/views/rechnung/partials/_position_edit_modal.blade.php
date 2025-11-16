{{-- resources/views/rechnung/partials/_position_edit_modal.blade.php --}}

<div class="modal fade" id="editPositionModal{{ $position->id }}" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form action="{{ route('rechnung.position.update', $position->id) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="modal-header">
          <h5 class="modal-title">
            <i class="bi bi-pencil"></i> Position {{ $position->position }} bearbeiten
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        
        <div class="modal-body">
          <div class="row g-3">
            
            <div class="col-12">
              <label class="form-label">Beschreibung *</label>
              <textarea name="beschreibung" class="form-control" rows="2" required>{{ $position->beschreibung }}</textarea>
            </div>

            <div class="col-md-4">
              <label class="form-label">Anzahl *</label>
              <input type="number" name="anzahl" class="form-control" step="0.01" value="{{ $position->anzahl }}" required>
            </div>

            <div class="col-md-4">
              <label class="form-label">Einheit</label>
              <input type="text" name="einheit" class="form-control" value="{{ $position->einheit }}" maxlength="10">
            </div>

            <div class="col-md-4">
              <label class="form-label">Einzelpreis (€) *</label>
              <input type="number" name="einzelpreis" class="form-control" step="0.01" value="{{ $position->einzelpreis }}" required>
            </div>

            <div class="col-md-4">
              <label class="form-label">MwSt-Satz (%) *</label>
              <input type="number" name="mwst_satz" class="form-control" step="0.01" value="{{ $position->mwst_satz }}" required>
            </div>

            <div class="col-md-4">
              <label class="form-label">Position</label>
              <input type="number" name="position" class="form-control" min="1" value="{{ $position->position }}" required>
            </div>

            <div class="col-md-4">
              <div class="pt-4">
                <div class="form-check form-switch">
                  <input type="hidden" name="artikel_gebaeude_id" value="{{ $position->artikel_gebaeude_id }}">
                  <label class="form-check-label small text-muted">
                    @if($position->artikel_gebaeude_id)
                      <i class="bi bi-link-45deg"></i> Verknüpft mit Artikel #{{ $position->artikel_gebaeude_id }}
                    @else
                      <i class="bi bi-dash"></i> Keine Artikelverknüpfung
                    @endif
                  </label>
                </div>
              </div>
            </div>

          </div>
        </div>
        
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-save"></i> Änderungen speichern
          </button>
        </div>
      </form>
    </div>
  </div>
</div>