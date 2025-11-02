{{-- resources/views/gebaeude/partials/_timeline.blade.php --}}
{{-- Timeline-Partial ohne verschachtelte <form>-Tags.
     Hinzufügen/Löschen via fetch(), CSRF/Routes kommen über data-* Attribute. --}}

@php
  // Optional: Falls du serverseitige Fehlermeldungen von der letzten Request-Runde anzeigen willst,
  // sind die @error-Abschnitte weiter unten aktiv.
@endphp

<div class="row g-4">

  {{-- Kopf / Titel --}}
  <div class="col-12">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
      <div class="d-flex align-items-center gap-2">
        <i class="bi bi-clock-history text-muted"></i>
        <span class="fw-semibold">Timeline</span>
      </div>
      <div></div>
    </div>
    <hr class="mt-2 mb-0">
  </div>

  {{-- Kompakte Eingabezeile (ohne <form>) --}}
  <div class="col-12">
    <div class="row g-2 align-items-end">
      {{-- Datum --}}
      <div class="col-md-3">
        <label for="tl_datum" class="form-label fw-semibold mb-1">
          <i class="bi bi-calendar-date"></i> Datum
        </label>
        <input
          type="date"
          class="form-control"
          id="tl_datum"
          name="datum"
          value="{{ old('datum', now()->toDateString()) }}">
        {{-- Validierungsfehler des letzten Requests (falls Server-Redirect) --}}
        @error('datum') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
      </div>

      {{-- Bemerkung --}}
      <div class="col-md-7">
        <label for="tl_bem" class="form-label fw-semibold mb-1">
          <i class="bi bi-chat-left-text"></i> Bemerkung (optional)
        </label>
        <input
          type="text"
          class="form-control"
          id="tl_bem"
          name="bemerkung"
          placeholder="Kurze Notiz …"
          value="{{ old('bemerkung') }}">
        @error('bemerkung') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
      </div>

      {{-- Hinzufügen --}}
      <div class="col-md-2 text-end">
        <label class="form-label d-block mb-1">&nbsp;</label>
        <button type="button" id="tl_add_btn" class="btn btn-success w-100">
          <i class="bi bi-plus-circle"></i> Hinzufügen
        </button>
      </div>
    </div>
  </div>

  {{-- Liste der Timeline-Einträge --}}
  <div class="col-12">
    @php
      /** @var \Illuminate\Support\Collection|\Illuminate\Database\Eloquent\Collection $entries */
      // Hinweis: Relation heißt im Model 'timelines()' (Plural)
      $entries = $timelineEntries ?? $gebaeude->timelines()->get();
    @endphp

    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th style="width: 120px;">Datum</th>
            <th>Bemerkung</th>
            <th style="width: 220px;">Person</th>
            <th class="text-end" style="width: 100px;">Aktionen</th>
          </tr>
        </thead>
        <tbody id="tl_tbody">
          @forelse($entries as $e)
            <tr data-id="{{ $e->id }}">
              <td class="text-nowrap">
                {{ optional(\Illuminate\Support\Carbon::parse($e->datum))->format('d.m.Y') }}
              </td>
              <td class="text-wrap" style="white-space: normal;">
                {{ $e->bemerkung ?: '—' }}
              </td>
              <td class="text-nowrap">
                {{ $e->person_name ?: '—' }}
              </td>
              <td class="text-end">
                {{-- Kein verschachteltes <form>: Button triggert fetch(DELETE) --}}
                <button type="button"
                        class="btn btn-sm btn-outline-danger tl-del-btn"
                        data-id="{{ $e->id }}"
                        title="Löschen">
                  <i class="bi bi-trash"></i>
                </button>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="4" class="text-center text-muted py-4">
                Keine Timeline-Einträge vorhanden.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

</div>

<div id="timeline-root"
     data-csrf="{{ csrf_token() }}"
     data-route-store="{{ route('gebaeude.timeline.store', $gebaeude->id) }}"
     data-route-destroy0="{{ route('timeline.destroy', 0) }}"
     data-return-to="{{ url()->current() }}">
</div>

@verbatim
<script>
(function () {
  // --- Werte sicher aus dem DOM holen ---
  var root        = document.getElementById('timeline-root');
  var CSRF        = (root && root.dataset && root.dataset.csrf) || '';
  var ROUTE_STORE = (root && root.dataset && root.dataset.routeStore) || '';
  var ROUTE_DEST0 = (root && root.dataset && root.dataset.routeDestroy0) || ''; // z.B. .../0
  var RETURN_TO   = (root && root.dataset && root.dataset.returnTo) || '';

  var btnAdd   = document.getElementById('tl_add_btn');
  var inputDate   = document.getElementById('tl_datum');
  var inputRemark = document.getElementById('tl_bem');

  // Enter in den Inputs soll NICHT das äußere Formular submitten
  [inputDate, inputRemark].forEach(function (el) {
    if (!el) return;
    el.addEventListener('keydown', function (ev) {
      if (ev.key === 'Enter') {
        ev.preventDefault();
        if (btnAdd) btnAdd.click();
      }
    });
  });

  // ▶ Hinzufügen via fetch(POST)
  if (btnAdd) {
    btnAdd.addEventListener('click', async function () {
      var payload = {
        datum: (inputDate && inputDate.value) ? inputDate.value : null,
        bemerkung: (inputRemark && inputRemark.value) ? inputRemark.value : null,
        returnTo: RETURN_TO
      };

      try {
        var res = await fetch(ROUTE_STORE, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CSRF,
            'Accept': 'application/json'
          },
          body: JSON.stringify(payload)
        });

        // Wenn der Controller JSON liefert, prüfen; sonst (Redirect) einfach reloaden
        var isJson = (res.headers.get('content-type') || '').includes('application/json');
        if (isJson) {
          var json = await res.json();
          if (!res.ok || json.ok === false) {
            throw new Error(json.message || 'Fehler beim Speichern.');
          }
        } else if (!res.ok) {
          throw new Error('Fehler beim Speichern (HTTP ' + res.status + ').');
        }

        // Erfolg → Seite neu laden (Tabelle & Flash aktualisieren)
        window.location.reload();

      } catch (err) {
        console.error(err);
        alert('Netzwerk-/Serverfehler beim Speichern der Timeline: ' + err.message);
      }
    });
  }

  // ▶ Löschen via fetch(DELETE), Delegation an Tabellenkörper
  var tbody = document.getElementById('tl_tbody');
  if (tbody) {
    tbody.addEventListener('click', async function (ev) {
      var target = ev.target;
      var btn = target && target.closest ? target.closest('.tl-del-btn') : null;
      if (!btn) return;

      var id = btn.getAttribute('data-id');
      if (!id) return;

      if (!confirm('Diesen Eintrag wirklich löschen?')) return;

      // Route mit Platzhalter-ID 0 → echte ID einsetzen
      var routeDelete = ROUTE_DEST0.replace(/\/0$/, '/' + String(id));

      try {
        var res = await fetch(routeDelete, {
          method: 'POST', // Method Spoofing für DELETE
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CSRF,
            'Accept': 'application/json'
          },
          body: JSON.stringify({ _method: 'DELETE', returnTo: RETURN_TO })
        });

        var isJson = (res.headers.get('content-type') || '').includes('application/json');
        if (isJson) {
          var json = await res.json();
          if (!res.ok || json.ok === false) {
            throw new Error(json.message || 'Fehler beim Löschen.');
          }
        } else if (!res.ok) {
          throw new Error('Fehler beim Löschen (HTTP ' + res.status + ').');
        }

        // Erfolg → Seite neu laden (einfach & robust)
        window.location.reload();

      } catch (err) {
        console.error(err);
        alert('Netzwerk-/Serverfehler beim Löschen: ' + err.message);
      }
    });
  }
})();
</script>
@endverbatim
