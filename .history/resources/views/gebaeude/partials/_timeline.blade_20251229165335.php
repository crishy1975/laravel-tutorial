{{-- resources/views/gebaeude/partials/_timeline.blade.php --}}
{{-- MOBIL-OPTIMIERT: Card-Layout auf Smartphones --}}

@php
$hasId = isset($gebaeude) && $gebaeude?->exists;
$csrf = csrf_token();
$routeStore = $hasId ? route('gebaeude.timeline.store', $gebaeude->id) : '';
$routeDestroy0 = route('timeline.destroy', 0);
$routeToggle0 = route('timeline.toggleVerrechnen', 0);
$entries = $timelineEntries ?? ($hasId ? $gebaeude->timelines()->get() : collect());
@endphp

<div class="row g-3">

  {{-- Header --}}
  <div class="col-12">
    <div class="d-flex align-items-center gap-2">
      <i class="bi bi-clock-history text-muted"></i>
      <span class="fw-semibold">Timeline</span>
      <span class="badge bg-secondary ms-auto">{{ $entries->count() }}</span>
    </div>
    <hr class="mt-2 mb-0">
  </div>

  {{-- Eingabe - kompakt auf Mobile --}}
  <div class="col-12">
    <div class="card bg-light">
      <div class="card-body p-2">
        <div class="row g-2">
          <div class="col-12 col-sm-4">
            <label class="form-label small mb-1 d-sm-none">Datum</label>
            <input type="date" class="form-control form-control-sm" id="tl_datum" name="datum"
                   value="{{ old('datum', now()->toDateString()) }}" {{ $hasId ? '' : 'disabled' }}>
          </div>
          <div class="col-12 col-sm-5">
            <label class="form-label small mb-1 d-sm-none">Bemerkung</label>
            <input type="text" class="form-control form-control-sm" id="tl_bem" name="bemerkung"
                   placeholder="Bemerkung (optional)" {{ $hasId ? '' : 'disabled' }}>
            <input type="text" class="form-control form-control-sm" id="tl_bem"
       placeholder="Bemerkung (optional)" {{ $hasId ? '' : 'disabled' }}>
          </div>
          <div class="col-12 col-sm-3">
            <button type="button" id="tl_add_btn" class="btn btn-success btn-sm w-100" {{ $hasId ? '' : 'disabled' }}>
              <i class="bi bi-plus-circle"></i>
              <span class="d-sm-none ms-1">Hinzufuegen</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    @unless($hasId)
    <div class="alert alert-info py-2 mt-2 mb-0 small">
      <i class="bi bi-info-circle"></i> Timeline erst nach Speichern verfuegbar.
    </div>
    @endunless
  </div>

  {{-- MOBILE: Cards --}}
  <div class="col-12 d-md-none" id="tl-cards-mobile">
    @forelse($entries as $e)
    <div class="card mb-2 tl-card-mobile" data-id="{{ $e->id }}">
      <div class="card-body p-2">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="fw-semibold">
              {{ optional(\Illuminate\Support\Carbon::parse($e->datum))->format('d.m.Y') }}
            </div>
            <div class="text-muted small">{{ $e->bemerkung ?: '-' }}</div>
            @if($e->person_name)
            <div class="text-muted small"><i class="bi bi-person"></i> {{ $e->person_name }}</div>
            @endif
          </div>
          <div class="d-flex flex-column align-items-end gap-1">
            <div class="form-check form-switch m-0">
              <input class="form-check-input tl-toggle-verrechnen" type="checkbox" 
                     data-id="{{ $e->id }}" @checked($e->verrechnen ?? false)>
              <label class="form-check-label small">Verr.</label>
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger tl-del-btn" data-id="{{ $e->id }}">
              <i class="bi bi-trash"></i>
            </button>
          </div>
        </div>
        @if($e->verrechnet_mit_rn_nummer)
        <div class="mt-1 pt-1 border-top">
          <small class="text-success"><i class="bi bi-check-circle"></i> RN: {{ $e->verrechnet_mit_rn_nummer }}</small>
        </div>
        @endif
      </div>
    </div>
    @empty
    <div class="text-center text-muted py-4">
      <i class="bi bi-clock fs-1"></i>
      <p class="mb-0 mt-2">Keine Eintraege</p>
    </div>
    @endforelse
  </div>

  {{-- DESKTOP: Tabelle --}}
  <div class="col-12 d-none d-md-block">
    <div class="table-responsive">
      <table class="table table-hover table-sm align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th style="width: 100px;">Datum</th>
            <th>Bemerkung</th>
            <th style="width: 150px;">Person</th>
            <th style="width: 80px;">Verr.</th>
            <th style="width: 120px;">RN</th>
            <th class="text-end" style="width: 60px;"></th>
          </tr>
        </thead>
        <tbody id="tl_tbody">
          @forelse($entries as $e)
          <tr data-id="{{ $e->id }}">
            <td class="text-nowrap">{{ optional(\Illuminate\Support\Carbon::parse($e->datum))->format('d.m.Y') }}</td>
            <td>{{ $e->bemerkung ?: '-' }}</td>
            <td>{{ $e->person_name ?: '-' }}</td>
            <td>
              <div class="form-check form-switch m-0">
                <input class="form-check-input tl-toggle-verrechnen" type="checkbox" 
                       data-id="{{ $e->id }}" @checked($e->verrechnen ?? false)>
              </div>
            </td>
            <td>{{ $e->verrechnet_mit_rn_nummer ?: '-' }}</td>
            <td class="text-end">
              <button type="button" class="btn btn-sm btn-outline-danger tl-del-btn" data-id="{{ $e->id }}">
                <i class="bi bi-trash"></i>
              </button>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="6" class="text-center text-muted py-4">Keine Eintraege</td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

</div>

<div id="timeline-root"
  data-csrf="{{ $csrf }}"
  data-route-store="{{ $routeStore }}"
  data-route-destroy0="{{ $routeDestroy0 }}"
  data-route-toggle-base="{{ url('/timeline') }}"
  data-return-to="{{ url()->current() }}">
</div>

<script>
(function() {
  var root = document.getElementById('timeline-root');
  var CSRF = (root && root.dataset.csrf) || '';
  var ROUTE_STORE = (root && root.dataset.routeStore) || '';
  var ROUTE_DEST0 = (root && root.dataset.routeDestroy0) || '';
  var ROUTE_TOGGLE_BASE = (root && root.dataset.routeToggleBase) || '';
  var RETURN_TO = (root && root.dataset.returnTo) || '';

  var btnAdd = document.getElementById('tl_add_btn');
  var inputDate = document.getElementById('tl_datum');
  var inputRemark = document.getElementById('tl_bem');

  if (!ROUTE_STORE) return;

  // Enter -> Hinzufuegen
  [inputDate, inputRemark].forEach(function(el) {
    if (!el) return;
    el.addEventListener('keydown', function(ev) {
      if (ev.key === 'Enter') {
        ev.preventDefault();
        if (btnAdd) btnAdd.click();
      }
    });
  });

  // Hinzufuegen
  if (btnAdd) {
    btnAdd.addEventListener('click', async function() {
      var payload = {
        datum: inputDate ? inputDate.value : null,
        bemerkung: inputRemark ? inputRemark.value : null,
        returnTo: RETURN_TO
      };

      try {
        var res = await fetch(ROUTE_STORE, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
          body: JSON.stringify(payload)
        });
        var json = await res.json();
        if (!res.ok || json.ok === false) throw new Error(json.message || 'Fehler');
        window.location.reload();
      } catch (err) {
        alert('Fehler: ' + err.message);
      }
    });
  }

  // Loeschen (Desktop + Mobile)
  document.addEventListener('click', async function(ev) {
    var btn = ev.target.closest('.tl-del-btn');
    if (!btn) return;

    var id = btn.getAttribute('data-id');
    if (!id || !confirm('Eintrag loeschen?')) return;

    var routeDelete = ROUTE_DEST0.replace(/\/0$/, '/' + id);

    try {
      var res = await fetch(routeDelete, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: JSON.stringify({ _method: 'DELETE', returnTo: RETURN_TO })
      });
      if (!res.ok) throw new Error('HTTP ' + res.status);
      window.location.reload();
    } catch (err) {
      alert('Fehler: ' + err.message);
    }
  });

  // Verrechnen Toggle (Desktop + Mobile)
  document.addEventListener('change', async function(ev) {
    var input = ev.target.closest('.tl-toggle-verrechnen');
    if (!input) return;

    var id = input.getAttribute('data-id');
    if (!id || !ROUTE_TOGGLE_BASE) return;

    var isOn = input.checked;
    var routeToggle = ROUTE_TOGGLE_BASE.replace(/\/$/, '') + '/' + id + '/verrechnen';

    try {
      var res = await fetch(routeToggle, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: JSON.stringify({ _method: 'PATCH', verrechnen: isOn ? 1 : 0, returnTo: RETURN_TO })
      });
      if (!res.ok) throw new Error('HTTP ' + res.status);
    } catch (err) {
      alert('Fehler: ' + err.message);
      input.checked = !isOn;
    }
  });
})();
</script>
