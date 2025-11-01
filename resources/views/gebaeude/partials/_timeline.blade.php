{{-- resources/views/gebaeude/partials/_timeline.blade.php --}}
{{-- Kein verschachteltes <form>! Wir nutzen fetch() für POST/DELETE. --}}

@php
  // CSRF-Token für JS
  $csrf = csrf_token();
  // Zielrouten
  $routeStore = route('gebaeude.timeline.store', $gebaeude->id);
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
      // Hinweis: Relation heißt bei dir 'timelines()' (Plural)
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

{{-- JS: fetch() für Hinzufügen/Löschen, kein jQuery nötig --}}
<script>
  (function() {
    // CSRF-Token & Routen aus Blade
    const CSRF     = @json($csrf);
    const ROUTE_STORE = @json($routeStore);
    const RETURN_TO   = @json(url()->current());

    const $btnAdd  = document.getElementById('tl_add_btn');
    const $date    = document.getElementById('tl_datum');
    const $remark  = document.getElementById('tl_bem');

    // Enter in den Inputs soll nicht das äußere Formular submitten
    [$date, $remark].forEach(el => {
      el?.addEventListener('keydown', (ev) => {
        if (ev.key === 'Enter') {
          ev.preventDefault();
          $btnAdd?.click();
        }
      });
    });

    // ▶ Hinzufügen via fetch(POST)
    $btnAdd?.addEventListener('click', async () => {
      const payload = {
        datum: ($date?.value || null),
        bemerkung: ($remark?.value || null),
        returnTo: RETURN_TO
      };

      try {
        const res = await fetch(ROUTE_STORE, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CSRF,
            'Accept': 'application/json'
          },
          body: JSON.stringify(payload)
        });

        // Wenn der Controller bei Nicht-JSON redirectet, kann res.ok trotzdem true sein.
        // Wir versuchen JSON zu lesen; falls es scheitert, reloaden wir.
        let data = null;
        try { data = await res.json(); } catch (e) {}

        if (res.ok) {
          // easy way: Seite neu laden, damit Tabelle & Flash aktualisiert werden
          window.location.reload();
          return;
        }

        alert('Fehler beim Speichern der Timeline (HTTP ' + res.status + ').');
      } catch (err) {
        console.error(err);
        alert('Netzwerkfehler beim Speichern der Timeline.');
      }
    });

    // ▶ Löschen via fetch(DELETE), Delegation an Tabelle
    document.getElementById('tl_tbody')?.addEventListener('click', async (ev) => {
      const btn = ev.target.closest('.tl-del-btn');
      if (!btn) return;

      const id = btn.getAttribute('data-id');
      if (!id) return;

      if (!confirm('Diesen Eintrag wirklich löschen?')) return;

      // Route für DELETE (wir bauen sie wie im Blade-Form vorher)
      const routeDelete = @json(route('timeline.destroy', 0)).replace(/0$/, String(id));

      try {
        const res = await fetch(routeDelete, {
          method: 'POST', // Laravel braucht POST mit _method=DELETE (bei fetch ohne Form)
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CSRF,
            'Accept': 'application/json'
          },
          body: JSON.stringify({ _method: 'DELETE', returnTo: RETURN_TO })
        });

        // Erfolgreich? Dann reload
        if (res.ok) {
          window.location.reload();
          return;
        }

        alert('Fehler beim Löschen (HTTP ' + res.status + ').');
      } catch (err) {
        console.error(err);
        alert('Netzwerkfehler beim Löschen.');
      }
    });
  })();
</script>
