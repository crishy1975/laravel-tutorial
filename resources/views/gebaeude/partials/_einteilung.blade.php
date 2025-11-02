{{-- resources/views/gebaeude/partials/_einteilung.blade.php --}}
{{-- Reines Feld-Partial für Gebäude: für create & edit nutzbar (ohne verschachtelte <form>-Tags) --}}
<div class="row g-3">

  {{-- Monate m01..m12 (Checkboxen) --}}
  <div class="col-12">
    <label class="form-label fw-semibold">Monate</label>

    @php
      // Hilfsarray: [Feldname, Anzeigename]
      $monate = [
        ['m01', 'Jänner'], ['m02', 'Februar'], ['m03', 'März'],
        ['m04', 'April'],  ['m05', 'Mai'],     ['m06', 'Juni'],
        ['m07', 'Juli'],   ['m08', 'August'],  ['m09', 'September'],
        ['m10', 'Oktober'],['m11', 'November'],['m12', 'Dezember'],
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
        value="{{ old('gemachte_reinigungen', $gebaeude->gemachte_reinigungen ?? 1) }}">
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

  {{-- Fällig (Switch) --}}
  <div class="col-md-6">
    {{-- Hidden 0: auch wenn Switch aus ist, 0 speichern --}}
    <input type="hidden" name="faellig" value="0">
    <div class="form-check form-switch mt-2">
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
  </div>

  {{-- =========================== --}}
  {{-- 🔴 Button: alle gemachte_reinigungen → 0 --}}
  {{-- =========================== --}}
  <div class="col-12">
    <div class="d-flex align-items-center justify-content-end gap-3">
      

      {{-- Kein <form>, nur Button + fetch(POST) --}}
      <button
        type="button"
        id="btn-reset-gemachte"
        class="btn btn-outline-danger btn-sm">
        <i class="bi bi-arrow-counterclockwise"></i>
        Gemachte Reinigungen zurücksetzen
      </button>
    </div>
  </div>

</div>

{{-- Datenträger: CSRF + Route für JS --}}
<div
  id="einteilung-root"
  data-csrf="{{ csrf_token() }}"
  data-route-reset="{{ route('gebaeude.resetGemachteReinigungen') }}">
</div>

@verbatim
<script>
(function () {
  var root   = document.getElementById('einteilung-root');
  var btn    = document.getElementById('btn-reset-gemachte');

  if (!root || !btn) return;

  var CSRF   = root.dataset ? (root.dataset.csrf || '') : '';
  var ROUTE  = root.dataset ? (root.dataset.routeReset || '') : '';

  btn.addEventListener('click', async function () {
    if (!ROUTE) {
      alert('Route für Reset nicht gefunden.');
      return;
    }
    if (!confirm('Alle „gemachte Reinigungen“ wirklich auf 0 setzen? Diese Aktion betrifft ALLE Gebäude.')) {
      return;
    }

    try {
      // Wir senden KEIN 'Accept: application/json', damit Laravel sauber redirectet
      // und wir anschließend einfach neu laden können.
      var res = await fetch(ROUTE, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': CSRF
        },
        body: new URLSearchParams({ confirm: 'YES' })
      });

      if (!res.ok) {
        throw new Error('HTTP ' + res.status);
      }

      // Redirect/Flash vom Server → Seite neu laden, damit Flash sichtbar wird.
      window.location.reload();

    } catch (err) {
      console.error(err);
      alert('Fehler beim Zurücksetzen: ' + err.message);
    }
  });
})();
</script>
@endverbatim
