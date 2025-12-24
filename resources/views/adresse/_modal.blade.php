{{-- resources/views/adresse/_modal.blade.php --}}
{{-- MOBIL-OPTIMIERT: Fullscreen auf Mobile, AJAX-basiert --}}

<div class="modal fade" id="adresseModal" tabindex="-1" aria-labelledby="adresseModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
    <div class="modal-content border-0 shadow-lg">
      
      {{-- Header --}}
      <div class="modal-header bg-primary text-white py-2">
        <h5 class="modal-title" id="adresseModalLabel">
          <i class="bi bi-person-plus"></i> 
          <span id="adresseModalTitleText">Neue Adresse</span>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Schliessen"></button>
      </div>

      {{-- Body --}}
      <div class="modal-body p-3">
        <form id="adresseModalForm">
          @csrf
          <input type="hidden" name="id" id="adresse_id">

          {{-- Formularfelder inline (vereinfacht fuer Modal) --}}
          <div class="row g-3">
            
            {{-- Name --}}
            <div class="col-12">
              <div class="form-floating">
                <input type="text" id="modal_name" name="name" placeholder=" " class="form-control" required>
                <label for="modal_name">Name / Firma <span class="text-danger">*</span></label>
              </div>
            </div>

            {{-- Strasse + Nr --}}
            <div class="col-8">
              <div class="form-floating">
                <input type="text" id="modal_strasse" name="strasse" placeholder=" " class="form-control">
                <label for="modal_strasse">Strasse</label>
              </div>
            </div>
            <div class="col-4">
              <div class="form-floating">
                <input type="text" id="modal_hausnummer" name="hausnummer" placeholder=" " class="form-control">
                <label for="modal_hausnummer">Nr.</label>
              </div>
            </div>

            {{-- PLZ + Ort --}}
            <div class="col-4">
              <div class="form-floating">
                <input type="text" id="modal_plz" name="plz" placeholder=" " class="form-control">
                <label for="modal_plz">PLZ</label>
              </div>
            </div>
            <div class="col-5">
              <div class="form-floating">
                <input type="text" id="modal_wohnort" name="wohnort" placeholder=" " class="form-control">
                <label for="modal_wohnort">Wohnort</label>
              </div>
            </div>
            <div class="col-3">
              <div class="form-floating">
                <input type="text" id="modal_provinz" name="provinz" placeholder=" " class="form-control text-uppercase" maxlength="4">
                <label for="modal_provinz">Prov.</label>
              </div>
            </div>

            {{-- E-Mail + Telefon --}}
            <div class="col-12 col-md-6">
              <div class="form-floating">
                <input type="email" id="modal_email" name="email" placeholder=" " class="form-control">
                <label for="modal_email"><i class="bi bi-envelope me-1"></i>E-Mail</label>
              </div>
            </div>
            <div class="col-12 col-md-6">
              <div class="form-floating">
                <input type="tel" id="modal_telefon" name="telefon" placeholder=" " class="form-control">
                <label for="modal_telefon"><i class="bi bi-telephone me-1"></i>Telefon</label>
              </div>
            </div>

            {{-- Steuerdaten (zusammenklappbar) --}}
            <div class="col-12">
              <button class="btn btn-sm btn-outline-secondary w-100" type="button" 
                      data-bs-toggle="collapse" data-bs-target="#modalSteuerCollapse">
                <i class="bi bi-chevron-down"></i> Steuerdaten anzeigen
              </button>
            </div>
            
            <div class="collapse col-12" id="modalSteuerCollapse">
              <div class="row g-3 pt-2">
                <div class="col-12 col-md-6">
                  <div class="form-floating">
                    <input type="text" id="modal_steuernummer" name="steuernummer" placeholder=" " 
                           class="form-control text-uppercase" maxlength="16">
                    <label for="modal_steuernummer">Steuernummer</label>
                  </div>
                </div>
                <div class="col-12 col-md-6">
                  <div class="form-floating">
                    <input type="text" id="modal_mwst_nummer" name="mwst_nummer" placeholder=" " 
                           class="form-control" maxlength="13">
                    <label for="modal_mwst_nummer">MwSt-Nummer</label>
                  </div>
                </div>
                <div class="col-12 col-md-6">
                  <div class="form-floating">
                    <input type="text" id="modal_codice_univoco" name="codice_univoco" placeholder=" " 
                           class="form-control text-uppercase" maxlength="7">
                    <label for="modal_codice_univoco">Codice Univoco</label>
                  </div>
                </div>
                <div class="col-12 col-md-6">
                  <div class="form-floating">
                    <input type="email" id="modal_pec" name="pec" placeholder=" " class="form-control">
                    <label for="modal_pec">PEC</label>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </form>

        {{-- Fehleranzeige --}}
        <div id="adresseModalErrors" class="alert alert-danger mt-3 d-none"></div>
      </div>

      {{-- Footer --}}
      <div class="modal-footer py-2">
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
  var modal = document.getElementById('adresseModal');
  var form = document.getElementById('adresseModalForm');
  var btnSpeichern = document.getElementById('adresseModalSpeichernBtn');
  var errorsDiv = document.getElementById('adresseModalErrors');

  if (!modal || !form || !btnSpeichern) return;

  // Modal oeffnen fuer "Neu"
  window.neueAdresse = function(callback) {
    form.reset();
    form.dataset.mode = 'create';
    form.dataset.callback = callback || '';
    document.getElementById('adresse_id').value = '';
    document.getElementById('adresseModalTitleText').textContent = 'Neue Adresse';
    errorsDiv.classList.add('d-none');
    new bootstrap.Modal(modal).show();
  };

  // Modal oeffnen fuer "Bearbeiten"
  window.adresseBearbeiten = function(id, callback) {
    form.reset();
    errorsDiv.classList.add('d-none');
    
    fetch('/adresse/' + id + '/json')
      .then(function(res) { return res.json(); })
      .then(function(data) {
        // Felder befuellen (mit modal_ Prefix)
        var fields = ['name', 'strasse', 'hausnummer', 'plz', 'wohnort', 'provinz', 
                      'email', 'telefon', 'steuernummer', 'mwst_nummer', 'codice_univoco', 'pec'];
        fields.forEach(function(f) {
          var el = document.getElementById('modal_' + f);
          if (el && data[f]) el.value = data[f];
        });
        
        form.dataset.mode = 'edit';
        form.dataset.callback = callback || '';
        document.getElementById('adresse_id').value = id;
        document.getElementById('adresseModalTitleText').textContent = 'Adresse bearbeiten';
        new bootstrap.Modal(modal).show();
      })
      .catch(function(err) {
        alert('Fehler beim Laden der Adresse: ' + err);
      });
  };

  // Speichern
  btnSpeichern.addEventListener('click', function() {
    var mode = form.dataset.mode;
    var id = document.getElementById('adresse_id').value;
    var formData = new FormData(form);
    var url = mode === 'edit' ? '/adresse/' + id : '/adresse';
    
    // Bei Edit: PUT-Method hinzufuegen
    if (mode === 'edit') {
      formData.append('_method', 'PUT');
    }

    btnSpeichern.disabled = true;
    btnSpeichern.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Speichern...';

    fetch(url, {
      method: 'POST',
      headers: { 
        'X-CSRF-TOKEN': form.querySelector('[name=_token]').value,
        'Accept': 'application/json'
      },
      body: formData
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
      btnSpeichern.disabled = false;
      btnSpeichern.innerHTML = '<i class="bi bi-check2-circle"></i> Speichern';

      if (data.errors) {
        var errorHtml = '<ul class="mb-0">';
        for (var key in data.errors) {
          errorHtml += '<li>' + data.errors[key].join(', ') + '</li>';
        }
        errorHtml += '</ul>';
        errorsDiv.innerHTML = errorHtml;
        errorsDiv.classList.remove('d-none');
        return;
      }

      bootstrap.Modal.getInstance(modal).hide();
      form.reset();

      // Select-Felder aktualisieren (falls vorhanden)
      if (window.postadresseSelect && typeof window.postadresseSelect.addOption === 'function') {
        var optionText = data.name + ' - ' + (data.wohnort || '');
        window.postadresseSelect.addOption({ value: data.id, text: optionText });
        window.postadresseSelect.setValue(data.id);
      }
      if (window.rechnungSelect && typeof window.rechnungSelect.addOption === 'function') {
        var optionText = data.name + ' - ' + (data.wohnort || '');
        window.rechnungSelect.addOption({ value: data.id, text: optionText });
      }

      // Callback ausfuehren
      if (typeof window.adresseGespeichert === 'function') {
        window.adresseGespeichert(data);
      }

      console.log('Adresse gespeichert:', data);
    })
    .catch(function(err) {
      btnSpeichern.disabled = false;
      btnSpeichern.innerHTML = '<i class="bi bi-check2-circle"></i> Speichern';
      console.error('Fehler beim Speichern:', err);
      errorsDiv.innerHTML = 'Fehler beim Speichern der Adresse.';
      errorsDiv.classList.remove('d-none');
    });
  });
});
</script>
@endpush

@push('styles')
<style>
@media (max-width: 575.98px) {
  #adresseModal .form-control { 
    min-height: 50px; 
    font-size: 16px !important; 
  }
}
</style>
@endpush
