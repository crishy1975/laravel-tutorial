{{-- resources/views/gebaeude/partials/_einteilung.blade.php --}}
{{-- MOBIL-OPTIMIERT --}}

<div class="row g-3">

  {{-- Monate - 3er Grid auf Mobile, 4er auf Desktop --}}
  <div class="col-12">
    <label class="form-label small fw-semibold mb-2">Monate</label>

    @php
      $monate = [
        ['m01', 'Jan'], ['m02', 'Feb'], ['m03', 'Maer'],
        ['m04', 'Apr'], ['m05', 'Mai'], ['m06', 'Jun'],
        ['m07', 'Jul'], ['m08', 'Aug'], ['m09', 'Sep'],
        ['m10', 'Okt'], ['m11', 'Nov'], ['m12', 'Dez'],
      ];
    @endphp

    <div class="row row-cols-3 row-cols-sm-4 row-cols-md-6 g-2">
      @foreach($monate as [$feld, $label])
        <div class="col">
          <input type="hidden" name="{{ $feld }}" value="0">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="{{ $feld }}" name="{{ $feld }}" value="1"
              @checked((int)old($feld, $gebaeude->{$feld} ?? 0) === 1)>
            <label class="form-check-label small" for="{{ $feld }}">{{ $label }}</label>
          </div>
        </div>
      @endforeach
    </div>
  </div>

  {{-- Geplante / Gemachte Reinigungen --}}
  <div class="col-6">
    <label for="geplante_reinigungen" class="form-label small mb-1">Geplante</label>
    <input type="number" min="0" id="geplante_reinigungen" name="geplante_reinigungen"
      class="form-control @error('geplante_reinigungen') is-invalid @enderror"
      value="{{ old('geplante_reinigungen', $gebaeude->geplante_reinigungen ?? 1) }}">
    @error('geplante_reinigungen') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>

  <div class="col-6">
    <label for="gemachte_reinigungen" class="form-label small mb-1">Gemachte</label>
    <input type="number" min="0" id="gemachte_reinigungen" name="gemachte_reinigungen"
      class="form-control @error('gemachte_reinigungen') is-invalid @enderror"
      value="{{ old('gemachte_reinigungen', $gebaeude->gemachte_reinigungen ?? 0) }}">
    @error('gemachte_reinigungen') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>

  {{-- Switches --}}
  <div class="col-6">
    <input type="hidden" name="rechnung_schreiben" value="0">
    <div class="form-check form-switch">
      <input class="form-check-input" type="checkbox" id="rechnung_schreiben" name="rechnung_schreiben" value="1"
        @checked((int)old('rechnung_schreiben', $gebaeude->rechnung_schreiben ?? 0) === 1)>
      <label class="form-check-label small" for="rechnung_schreiben">Rechnung schreiben</label>
    </div>
  </div>

  <div class="col-6">
    <input type="hidden" name="faellig" value="0">
    <div class="form-check form-switch">
      <input class="form-check-input" type="checkbox" id="faellig" name="faellig" value="1"
        @checked((int)old('faellig', $gebaeude->faellig ?? 0) === 1)>
      <label class="form-check-label small" for="faellig">Faellig</label>
    </div>
  </div>

  {{-- Aktionen - nur wenn Gebaeude existiert --}}
  @if(!empty($gebaeude->id))
  <div class="col-12">
    <div class="d-flex flex-column flex-sm-row gap-2 justify-content-end">
      <button type="button" id="btn-recalc-faellig" class="btn btn-outline-primary btn-sm">
        <i class="bi bi-arrow-repeat"></i> Faelligkeit pruefen
      </button>
      <button type="button" id="btn-reset-gemachte" class="btn btn-outline-danger btn-sm">
        <i class="bi bi-arrow-counterclockwise"></i> Gemachte zuruecksetzen
      </button>
    </div>
  </div>
  @else
  <div class="col-12">
    <div class="alert alert-info py-2 mb-0 small">
      <i class="bi bi-info-circle"></i> Aktionen erst nach Speichern verfuegbar.
    </div>
  </div>
  @endif

</div>

@if(!empty($gebaeude->id))
<div id="einteilung-root"
  data-csrf="{{ csrf_token() }}"
  data-route-recalc="{{ route('gebaeude.faellig.recalc', $gebaeude->id) }}"
  data-route-reset="{{ route('gebaeude.resetGemachteReinigungen') }}">
</div>

<script>
(function() {
  var root = document.getElementById('einteilung-root');
  var btnReset = document.getElementById('btn-reset-gemachte');
  var btnRecalc = document.getElementById('btn-recalc-faellig');
  var chkFaellig = document.getElementById('faellig');

  if (!root) return;

  var CSRF = root.dataset.csrf || '';
  var ROUTE_RECALC = root.dataset.routeRecalc || '';
  var ROUTE_RESET = root.dataset.routeReset || '';

  if (btnRecalc) {
    btnRecalc.addEventListener('click', async function() {
      if (!ROUTE_RECALC) return;
      btnRecalc.disabled = true;
      var oldHtml = btnRecalc.innerHTML;
      btnRecalc.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

      try {
        var res = await fetch(ROUTE_RECALC, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
          body: JSON.stringify({})
        });
        var json = await res.json();
        if (!res.ok || json.ok === false) throw new Error(json.message || 'Fehler');
        if (chkFaellig) chkFaellig.checked = !!json.faellig;
      } catch (err) {
        alert('Fehler: ' + err.message);
      } finally {
        btnRecalc.disabled = false;
        btnRecalc.innerHTML = oldHtml;
      }
    });
  }

  if (btnReset) {
    btnReset.addEventListener('click', async function() {
      if (!ROUTE_RESET) return;
      if (!confirm('Alle gemachten Reinigungen auf 0 setzen?')) return;

      try {
        var res = await fetch(ROUTE_RESET, {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': CSRF },
          body: new URLSearchParams({ confirm: 'YES' })
        });
        if (!res.ok) throw new Error('HTTP ' + res.status);
        window.location.reload();
      } catch (err) {
        alert('Fehler: ' + err.message);
      }
    });
  }
})();
</script>
@endif
