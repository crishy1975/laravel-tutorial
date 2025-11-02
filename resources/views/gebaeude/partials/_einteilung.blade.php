{{-- resources/views/gebaeude/partials/_einteilung.blade.php --}}
{{-- Reines Feld-Partial: wird im Hauptformular (create/edit) eingebunden.
    WICHTIG: Kein eigenes <form> hier! Alle Buttons arbeiten mit fetch().
--}}

<div class="row g-3">

  {{-- Monate m01..m12 (Checkboxen) --}}
  <div class="col-12">
    <label class="form-label fw-semibold">Monate</label>

    @php
      // Hilfsarray: [Feldname, Anzeigename]
      $monate = [
        ['m01', 'Jänner'],   ['m02', 'Februar'],  ['m03', 'März'],
        ['m04', 'April'],    ['m05', 'Mai'],      ['m06', 'Juni'],
        ['m07', 'Juli'],     ['m08', 'August'],   ['m09', 'September'],
        ['m10', 'Oktober'],  ['m11', 'November'], ['m12', 'Dezember'],
      ];
    @endphp

    <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 g-2">
      @foreach($monate as [$feld, $label])
        <div class="col">
          {{-- Hidden 0: sorgt dafür, dass bei "unchecked" eine 0 im Request ankommt --}}
          <input type="hidden" name="{{ $feld }}" value="0">
          <div class="form-check">
            <input
              class="form-check-input"
              type="checkbox"
              id="{{ $feld }}"
              name="{{ $feld }}"
              value="1"
              @checked( (int)old($feld, $gebaeude->{$feld} ?? 0) === 1 )
            >
            <label class="form-check-label" for="{{ $feld }}">{{ $label }}</label>
          </div>
          @error($feld)
            <div class="text-danger small">{{ $message }}</div>
          @enderror
        </div>
      @endforeach
    </div>
  </div>

  {{-- Geplante Reinigungen (Number) --}}
  <div class="col-md-6">
    <div class="form-floating">
      <input
        type="number" min="0"
        id="geplante_reinigungen" name="geplante_reinigungen"
        class="form-control @error('geplante_reinigungen') is-invalid @enderror"
        value="{{ old('geplante_reinigungen', $gebaeude->geplante_reinigungen ?? 1) }}">
      <label for="geplante_reinigungen">Geplante Reinigungen</label>
      @error('geplante_reinigungen') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
  </div>

  {{-- Gemachte Reinigungen (Number) --}}
  <div class="col-md-6">
    <div class="form-floating">
      <input
        type="number" min="0"
        id="gemachte_reinigungen" name="gemachte_reinigungen"
        class="form-control @error('gemachte_reinigungen') is-invalid @enderror"
        value="{{ old('gemachte_reinigungen', $gebaeude->gemachte_reinigungen ?? 0) }}">
      <label for="gemachte_reinigungen">Gemachte Reinigungen</label>
      @error('gemachte_reinigungen') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
  </div>

  {{-- Rechnung schreiben (Switch) --}}
  <div class="col-md-6">
    {{-- Hidden 0: auch wenn Switch aus ist, 0 speichern --}}
    <input type="hidden" name="rechnung_schreiben" value="0">
    <div class="form-check form-switch mt-2">
      <input
        class="form-check-input @error('rechnung_schreiben') is-invalid @enderror"
        type="checkbox" role="switch"
        id="rechnung_schreiben" name="rechnung_schreiben" value="1"
        @checked( (int)old('rechnung_schreiben', $gebaeude->rechnung_schreiben ?? 0) === 1 )
      >
      <label class="form-check-label fw-semibold" for="rechnung_schreiben">
        Rechnung schreiben
      </label>
      @error('rechnung_schreiben') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
  </div>

  {{-- Fällig (Switch) + Live-Badge + „Fälligkeit jetzt prüfen“ (pro Gebäude) --}}
  <div class="col-md-6">
    {{-- Hidden 0: auch wenn Switch aus ist, 0 speichern --}}
    <input type="hidden" name="faellig" value="0">
    <div class="d-flex flex-column gap-2 mt-2">
      <div class="form-check form-switch m-0">
        <input
          class="form-check-input @error('faellig') is-invalid @enderror"
          type="checkbox" role="switch"
          id="faellig" name="faellig" value="1"
          @checked( (int)old('faellig', $gebaeude->faellig ?? 0) === 1 )
        >
        <label class="form-check-label fw-semibold" for="faellig">
          Fällig
        </label>
        @error('faellig') <div class="text-danger small">{{ $message }}</div> @enderror
      </div>

      {{-- Infozeile: farbiges Badge + Button --}}
      <div class="d-flex align-items-center gap-2">
        <span id="faellig-badge"
              class="badge {{ (int)($gebaeude->faellig ?? 0) === 1 ? 'text-bg-danger' : 'text-bg-secondary' }}">
          {{ (int)($gebaeude->faellig ?? 0) === 1 ? 'FÄLLIG' : 'nicht fällig' }}
        </span>

        {{-- Kein <form>, nur Button + fetch(POST) für EIN Gebäude --}}
        <button type="button" id="btn-recalc-faellig" class="btn btn-outline-primary btn-sm">
          <i class="bi bi-arrow-repeat"></i> Fälligkeit jetzt prüfen
        </button>
      </div>
    </div>
  </div>

  {{-- =========================== --}}
  {{-- 🔴 Globaler Button: ALLE gemachte_reinigungen → 0 --}}
  {{-- =========================== --}}
  <div class="col-12">
    <div class="d-flex align-items-center justify-content-end gap-3">
      <button
        type="button"
        id="btn-reset-gemachte"
        class="btn btn-outline-danger btn-sm">
        <i class="bi bi-arrow-counterclockwise"></i>
        Gemachte Reinigungen (ALLE) zurücksetzen
      </button>
    </div>
  </div>

</div>

{{-- Datenträger: CSRF + Routen für JS --}}
<div
  id="einteilung-root"
  data-csrf="{{ csrf_token() }}"
  {{-- Pro-Gebäude-Neuberechnung (JSON): --}}
  data-route-recalc="{{ route('gebaeude.faellig.recalc', $gebaeude->id) }}"
  {{-- Globales Zurücksetzen „gemachte_reinigungen“ (Redirect/Flash): --}}
  data-route-reset="{{ route('gebaeude.resetGemachteReinigungen') }}">
</div>

@verbatim
<script>
(function () {
  var root       = document.getElementById('einteilung-root');
  var btnReset   = document.getElementById('btn-reset-gemachte');
  var btnRecalc  = document.getElementById('btn-recalc-faellig');
  var badge      = document.getElementById('faellig-badge');
  var chkFaellig = document.getElementById('faellig');

  if (!root) return;

  var CSRF         = root.dataset ? (root.dataset.csrf || '') : '';
  var ROUTE_RECALC = root.dataset ? (root.dataset.routeRecalc || '') : '';
  var ROUTE_RESET  = root.dataset ? (root.dataset.routeReset  || '') : '';

  /* ---------------------------------
   * A) Fälligkeit für *dieses* Gebäude prüfen
   *     - erwartet JSON: { ok: true, faellig: 0|1 }
   *     - aktualisiert Badge + Switch live
   * --------------------------------- */
  if (btnRecalc && badge && chkFaellig) {
    btnRecalc.addEventListener('click', async function () {
      if (!ROUTE_RECALC) {
        alert('Route für Fälligkeit nicht gefunden.');
        return;
      }
      btnRecalc.disabled = true;
      var oldHtml = btnRecalc.innerHTML;
      btnRecalc.innerHTML =
        '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Prüfe…';

      try {
        var res = await fetch(ROUTE_RECALC, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CSRF,
            'Accept': 'application/json'
          },
          body: JSON.stringify({})
        });

        var json = {};
        try { json = await res.json(); } catch (e) {}

        if (!res.ok || json.ok === false) {
          throw new Error(json.message || ('HTTP ' + res.status));
        }

        // Live-Update UI
        var isFaellig = !!json.faellig;
        badge.textContent = isFaellig ? 'FÄLLIG' : 'nicht fällig';
        badge.classList.toggle('text-bg-danger', isFaellig);
        badge.classList.toggle('text-bg-secondary', !isFaellig);

        // Switch im Formular mitziehen (damit "Speichern" den Status mitnimmt)
        chkFaellig.checked = isFaellig;
      } catch (err) {
        console.error(err);
        alert('Fälligkeit konnte nicht berechnet werden: ' + err.message);
      } finally {
        btnRecalc.disabled = false;
        btnRecalc.innerHTML = oldHtml;
      }
    });
  }

  /* ---------------------------------
   * B) ALLE „gemachte_reinigungen“ global auf 0 setzen
   *     - klassischer Redirect/Flash: kein JSON notwendig
   * --------------------------------- */
  if (btnReset) {
    btnReset.addEventListener('click', async function () {
      if (!ROUTE_RESET) {
        alert('Route für Reset nicht gefunden.');
        return;
      }
      if (!confirm('Alle „gemachte Reinigungen“ wirklich auf 0 setzen? Diese Aktion betrifft ALLE Gebäude.')) {
        return;
      }

      try {
        var res = await fetch(ROUTE_RESET, {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': CSRF },
          body: new URLSearchParams({ confirm: 'YES' })
        });
        if (!res.ok) throw new Error('HTTP ' + res.status);

        // Nach Redirect/Flash Seite neu laden → Meldung sichtbar, Zahlen frisch
        window.location.reload();
      } catch (err) {
        console.error(err);
        alert('Fehler beim Zurücksetzen: ' + err.message);
      }
    });
  }
})();
</script>
@endverbatim
