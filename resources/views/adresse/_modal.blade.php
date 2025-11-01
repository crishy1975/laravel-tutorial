{{-- 🔹 Modal für Adresse: Neu / Bearbeiten --}}
<div class="modal fade" id="adresseModal" tabindex="-1" aria-labelledby="adresseModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="adresseModalLabel">
          <i class="bi bi-person-plus"></i> Adresse
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Schließen"></button>
      </div>

      <div class="modal-body">
        <form id="adresseModalForm">
          @csrf
          <input type="hidden" name="id" id="adresse_id">

          {{-- Wiederverwende das gleiche Formular --}}
          <div id="adresseModalBody">
            @include('adresse_form', ['adresse' => new \App\Models\Adresse()])
          </div>
        </form>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
          <i class="bi bi-x-lg"></i> Abbrechen
        </button>
        <button type="button" id="adresseModalSpeichernBtn" class="btn btn-primary">
          <i class="bi bi-check2-circle"></i> Speichern
        </button>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('adresseModal');
    const form = document.getElementById('adresseModalForm');
    const btnSpeichern = document.getElementById('adresseModalSpeichernBtn');

    // Öffnen des Modals für "Neu"
    window.neueAdresse = function() {
        form.reset();
        form.dataset.mode = 'create';
        document.getElementById('adresse_id').value = '';
        document.getElementById('adresseModalLabel').innerHTML = '<i class="bi bi-person-plus"></i> Neue Adresse';
        new bootstrap.Modal(modal).show();
    };

    // Öffnen des Modals für "Bearbeiten"
    window.adresseBearbeiten = function(id) {
        form.reset();
        fetch(`/adressen/${id}`)
            .then(res => res.json())
            .then(data => {
                for (const key in data) {
                    if (form.querySelector(`[name="${key}"]`)) {
                        form.querySelector(`[name="${key}"]`).value = data[key] ?? '';
                    }
                }
                form.dataset.mode = 'edit';
                document.getElementById('adresse_id').value = id;
                document.getElementById('adresseModalLabel').innerHTML = '<i class="bi bi-pencil"></i> Adresse bearbeiten';
                new bootstrap.Modal(modal).show();
            })
            .catch(err => alert('Fehler beim Laden der Adresse: ' + err));
    };

    // 🔹 Speichern (neu oder edit)
    btnSpeichern.addEventListener('click', function() {
        const mode = form.dataset.mode;
        const id = document.getElementById('adresse_id').value;
        const formData = new FormData(form);
        const url = mode === 'edit' ? `/adressen/${id}` : '/adressen';
        const method = mode === 'edit' ? 'PUT' : 'POST';

        fetch(url, {
            method: method,
            headers: { 'X-CSRF-TOKEN': form.querySelector('[name=_token]').value },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.errors) {
                alert('Fehler: ' + JSON.stringify(data.errors));
                return;
            }
            bootstrap.Modal.getInstance(modal).hide();
            form.reset();

            // 🔹 Optional: in Select-Felder einfügen
            if (window.postadresseSelect && window.rechnungSelect) {
                const optionText = `${data.name} – ${data.wohnort}`;
                [window.postadresseSelect, window.rechnungSelect].forEach(sel => {
                    sel.addOption({ value: data.id, text: optionText });
                    sel.setValue(data.id);
                });
            }

            // 🔹 Callback erlauben
            if (typeof window.adresseGespeichert === 'function') {
                window.adresseGespeichert(data);
            }

            console.log('✅ Adresse gespeichert:', data);
        })
        .catch(err => {
            console.error('Fehler beim Speichern der Adresse:', err);
            alert('Fehler beim Speichern der Adresse.');
        });
    });
});
</script>
@endpush
