{{-- resources/views/tour/index.blade.php --}}
{{-- Touren-Übersicht mit Drag&Drop-Reorder (ohne ID/Reihenfolge-Spalten),
     Aktiv-Toggle (AJAX), Filter, Pagination und Icon-Aktionen (Auge, Stift, Müll).
     Kein Code weggelassen. --}}

@extends('layouts.app')

@section('content')
<div class="container py-4">

  {{-- Kopfzeile --}}
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <h3 class="mb-0"><i class="bi bi-map"></i> Touren</h3>

    <div class="d-flex gap-2">
      {{-- Neu anlegen: returnTo = aktuelle URL inkl. Filter/Pagination --}}
      <a href="{{ route('tour.create', ['returnTo' => url()->full()]) }}" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Neue Tour
      </a>

      {{-- Manueller Save (zusätzlich zum Auto-Save nach Drag) --}}
      <button id="btn-reorder-save" type="button" class="btn btn-success">
        <i class="bi bi-arrow-down-up"></i> Reihenfolge speichern
      </button>
    </div>
  </div>

  {{-- Flash-Meldung --}}
  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
  @endif

  {{-- Filter --}}
  <form method="GET" action="{{ route('tour.index') }}\" class="card mb-3">
    <div class="card-body row g-2 align-items-end">
      <div class="col-md-6">
        <label for="q" class="form-label">Suche</label>
        <input type="text" class="form-control" id="q" name="q" value="{{ request('q') }}" placeholder="Name oder Beschreibung …">
      </div>
      <div class="col-md-3">
        <label for="aktiv" class="form-label">Status</label>
        <select id="aktiv" name="aktiv" class="form-select">
          <option value="">Alle</option>
          <option value="1" @selected(request('aktiv')==='1')>Aktiv</option>
          <option value="0" @selected(request('aktiv')==='0')>Inaktiv</option>
        </select>
      </div>
      <div class="col-md-3 text-end">
        <button type="submit" class="btn btn-outline-secondary">
          <i class="bi bi-search"></i> Filtern
        </button>
      </div>
    </div>
  </form>

  {{-- Tabelle ohne ID & Reihenfolge-Spalten --}}
  <div class="card">
    <div class="table-responsive">
      <table class="table align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th style="width:56px;"></th> {{-- Drag-Handle --}}
            <th>Name</th>
            <th>Beschreibung</th>
            <th style="width:120px;">Aktiv</th>
            <th style="width:180px;" class="text-end">Aktionen</th>
          </tr>
        </thead>
        <tbody id="touren-tbody">
          @forelse($touren as $t)
            {{-- data-id bleibt im DOM (für Reorder) --}}
            <tr data-id="{{ $t->id }}">
              {{-- Drag-Handle (greifbar) --}}
              <td class="drag-handle text-muted" title="Ziehen zum Sortieren" style="cursor:grab;">
                <i class="bi bi-grip-vertical fs-5"></i>
              </td>

              <td class="fw-semibold">{{ $t->name }}</td>

              <td class="text-wrap" style="white-space:normal; max-width: 520px;">
                {{ $t->beschreibung }}
              </td>

              {{-- Aktiv per AJAX an/aus --}}
              <td>
                <div class="form-check form-switch m-0">
                  <input class="form-check-input toggle-aktiv" type="checkbox" role="switch"
                         @checked((int)$t->aktiv === 1)>
                </div>
              </td>

              <td class="text-end">
                <div class="btn-group" role="group">
                  {{-- 👁️ Anzeigen (Show) inkl. returnTo --}}
                  <a href="{{ route('tour.show', ['id' => $t->id, 'returnTo' => url()->full()]) }}"
                     class="btn btn-sm btn-outline-secondary"
                     title="Anzeigen" aria-label="Anzeigen">
                    <i class="bi bi-eye"></i>
                  </a>

                  {{-- ✏️ Bearbeiten inkl. returnTo --}}
                  <a href="{{ route('tour.edit', ['tour' => $t->id, 'returnTo' => url()->full()]) }}"
                     class="btn btn-sm btn-outline-primary"
                     title="Bearbeiten" aria-label="Bearbeiten">
                    <i class="bi bi-pencil"></i>
                  </a>

                  {{-- 🗑️ Löschen inkl. returnTo --}}
                  <form method="POST" action="{{ route('tour.destroy', $t->id) }}"
                        class="d-inline"
                        onsubmit="return confirm('Diese Tour wirklich löschen?');">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="returnTo" value="{{ url()->full() }}">
                    <button type="submit" class="btn btn-sm btn-outline-danger"
                            title="Löschen" aria-label="Löschen">
                      <i class="bi bi-trash"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="text-center text-muted py-4">Keine Touren gefunden.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    {{-- Pagination --}}
    @if(method_exists($touren, 'links'))
      <div class="card-footer">
        {{ $touren->withQueryString()->links() }}
      </div>
    @endif
  </div>
</div>
@endsection

@push('styles')
<style>
  /* Drag-Feedback + Selektion aus */
  #touren-tbody .sortable-ghost { opacity: 0.5; }
  #touren-tbody .sortable-chosen { background-color: #fff8e1; } /* zartes Gelb */

  /* Standardmäßig keine Textselektion in der Tabelle/Handle */
  #touren-tbody tr,
  #touren-tbody td,
  #touren-tbody .drag-handle {
    -webkit-user-select: none;
    -moz-user-select: none;
    user-select: none;
  }

  /* Während aktivem Drag global keine Auswahl */
  body.dnd-active {
    -webkit-user-select: none !important;
    -moz-user-select: none !important;
    user-select: none !important;
    cursor: grabbing !important;
  }

  .drag-handle { cursor: grab; }
  .drag-handle:active { cursor: grabbing; }
</style>
@endpush

@push('scripts')
{{-- 🔧 SortableJS robust laden (ohne SRI) + erst danach initialisieren --}}
<script>
(function loadSortableAndInit(){
  const urls = [
    'https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js',
    'https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.2/Sortable.min.js',
    'https://unpkg.com/sortablejs@1.15.2/Sortable.min.js'
  ];

  function inject(url, cb){
    const s = document.createElement('script');
    s.src = url;
    s.async = true;
    s.crossOrigin = 'anonymous'; // kein integrity -> keine SRI-Probleme
    s.onload = () => cb(true);
    s.onerror = () => cb(false);
    document.head.appendChild(s);
  }

  (function tryNext(i){
    if (window.Sortable) return initDnD();       // bereits vorhanden
    if (i >= urls.length) {
      console.error('SortableJS konnte nicht geladen werden.');
      return;
    }
    inject(urls[i], ok => ok ? initDnD() : tryNext(i+1));
  })(0);

  // ======= Initialisierung NACH erfolgreichem Laden =======
  function initDnD(){
    if (!window.Sortable) { console.error('Sortable nicht verfügbar'); return; }

    const tbody = document.getElementById('touren-tbody');
    const btnReorderSave = document.getElementById('btn-reorder-save');
    if (!tbody) { console.error('#touren-tbody fehlt'); return; }

    // Basis-URLs
    const BASE_URL    = "{{ url('tour') }}";              // PATCH /tour/{id}/toggle
    const REORDER_URL = "{{ route('tour.reorder') }}";    // PATCH /tour/reorder

    // CSRF
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    // === Aktiv-Checkbox toggeln (AJAX) ===
    tbody.addEventListener('change', async (e) => {
      const el = e.target;
      if (!el.classList.contains('toggle-aktiv')) return;
      const tr = el.closest('tr'); const id = tr?.dataset.id;
      if (!id) return;
      try {
        const res = await fetch(`${BASE_URL}/${id}/toggle`, {
          method: 'PATCH',
          headers: {'Content-Type':'application/json','X-CSRF-TOKEN':csrf,'Accept':'application/json'},
          body: JSON.stringify({ aktiv: el.checked ? 1 : 0 }),
        });
        if (!res.ok) throw 0;
        // Optional: wenn auf aktiv=1 gefiltert, und jetzt deaktiviert → optisch abdunkeln
        const url = new URL(window.location.href);
        if (url.searchParams.get('aktiv') === '1' && !el.checked) {
          tr.style.transition='opacity .2s'; tr.style.opacity='0.5';
        }
      } catch {
        el.checked = !el.checked;
        alert('Aktiv-Status konnte nicht gespeichert werden.');
      }
    });

    // === Text-Selektion zusätzlich auf dem Handle verhindern ===
    tbody.addEventListener('selectstart', (e) => {
      if (e.target.closest('.drag-handle')) e.preventDefault();
    });

    // === Drag & Drop ===
    const sortable = new Sortable(tbody, {
      animation: 150,
      handle: '.drag-handle',
      ghostClass: 'sortable-ghost',
      chosenClass: 'sortable-chosen',

      // Robust für Mobile/Edge-Cases
      forceFallback: true,
      fallbackOnBody: true,
      fallbackTolerance: 5,
      delayOnTouchOnly: true,
      delay: 100,

      // Klickbare Controls nicht als Drag starten
      filter: 'a,button,input,select,textarea,label',
      preventOnFilter: true,

      onStart: () => document.body.classList.add('dnd-active'),

      onEnd: async () => {
        document.body.classList.remove('dnd-active');
        await saveOrder(); // direkt speichern nach Drag
      },

      // Verhindert, dass Browser Text „mitzieht“
      setData: (dt) => { try { dt.setData('text/plain',''); } catch(_){} },
    });

    // Items aus DOM einsammeln (id + Position 1..N)
    function collectItems(){
      return Array.from(tbody.querySelectorAll('tr[data-id]'))
        .map((tr, idx) => ({ id: parseInt(tr.dataset.id,10), reihenfolge: idx+1 }));
    }

    // Reihenfolge speichern (AJAX) – mit Debug bei Fehlern
    async function saveOrder(){
      const items = collectItems();
      if (!items.length) return;

      if (btnReorderSave) {
        const t = btnReorderSave.innerHTML;
        btnReorderSave.disabled = true;
        btnReorderSave.innerHTML = '<i class="bi bi-hourglass-split"></i> Speichern…';
        try {
          const res = await fetch(REORDER_URL, {
            method:'PATCH',
            headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf,'Accept':'application/json'},
            body: JSON.stringify({ items }),
          });
          if (!res.ok) {
            const txt = await res.text();
            console.error('Reorder failed', res.status, txt);
            throw 0;
          }
          btnReorderSave.classList.replace('btn-success','btn-outline-success');
          btnReorderSave.innerHTML = '<i class="bi bi-check2-circle"></i> Gespeichert';
          setTimeout(()=>{ btnReorderSave.classList.replace('btn-outline-success','btn-success'); btnReorderSave.innerHTML=t; btnReorderSave.disabled=false; location.reload(); }, 500);
        } catch {
          alert('Reihenfolge konnte nicht gespeichert werden.');
          btnReorderSave.disabled=false; btnReorderSave.innerHTML=t;
        }
      } else {
        try {
          const res = await fetch(REORDER_URL, {
            method:'PATCH',
            headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf,'Accept':'application/json'},
            body: JSON.stringify({ items }),
          });
          if (!res.ok) {
            const txt = await res.text();
            console.error('Reorder failed', res.status, txt);
            throw 0;
          }
          location.reload();
        } catch {
          alert('Reihenfolge konnte nicht gespeichert werden.');
        }
      }
    }

    // Manueller Speichern-Button
    if (btnReorderSave) btnReorderSave.addEventListener('click', saveOrder);
  }
})();
</script>
@endpush
