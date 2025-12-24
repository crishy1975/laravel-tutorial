{{-- resources/views/tour/index.blade.php --}}
{{-- MOBIL-OPTIMIERT: Cards auf Mobile, Tabelle mit Drag&Drop auf Desktop --}}

@extends('layouts.app')

@section('content')
<div class="container-fluid px-2 px-md-4 py-3">

  {{-- Header --}}
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">
      <i class="bi bi-signpost-2 text-primary"></i>
      <span class="d-none d-sm-inline">Touren</span>
    </h4>
    <div class="d-flex gap-2">
      <a href="{{ route('tour.create', ['returnTo' => url()->full()]) }}" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i>
        <span class="d-none d-sm-inline ms-1">Neue Tour</span>
      </a>
      {{-- Reorder-Button nur auf Desktop --}}
      <button id="btn-reorder-save" type="button" class="btn btn-success d-none d-md-inline-flex">
        <i class="bi bi-arrow-down-up"></i>
        <span class="ms-1">Reihenfolge speichern</span>
      </button>
    </div>
  </div>

  {{-- Alerts --}}
  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show py-2" role="alert">
      <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show py-2" role="alert">
      <i class="bi bi-exclamation-triangle me-1"></i>{{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  {{-- Filter --}}
  <form method="GET" action="{{ route('tour.index') }}" class="card mb-3">
    <div class="card-body p-2 p-md-3">
      <div class="row g-2 align-items-end">
        <div class="col">
          <div class="input-group">
            <span class="input-group-text bg-light border-end-0">
              <i class="bi bi-search"></i>
            </span>
            <input type="text" class="form-control border-start-0" name="q" 
                   value="{{ request('q') }}" placeholder="Name oder Beschreibung...">
          </div>
        </div>
        <div class="col-auto">
          <select name="aktiv" class="form-select">
            <option value="">Alle</option>
            <option value="1" @selected(request('aktiv')==='1')>Aktiv</option>
            <option value="0" @selected(request('aktiv')==='0')>Inaktiv</option>
          </select>
        </div>
        <div class="col-auto">
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-search d-sm-none"></i>
            <span class="d-none d-sm-inline">Filtern</span>
          </button>
        </div>
        @if(request('q') || request('aktiv') !== null)
        <div class="col-auto">
          <a href="{{ route('tour.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-x-lg"></i>
          </a>
        </div>
        @endif
      </div>
    </div>
  </form>

  @if($touren->isEmpty())
    <div class="alert alert-info">
      <i class="bi bi-info-circle me-1"></i>Keine Touren gefunden.
    </div>
  @else

  {{-- MOBILE: Card-Layout (kein Drag&Drop) --}}
  <div class="d-md-none">
    @foreach($touren as $t)
    <div class="card mb-2 shadow-sm @if(!$t->aktiv) border-secondary @else border-start border-primary border-3 @endif">
      <div class="card-body p-3">
        <div class="d-flex justify-content-between align-items-start mb-2">
          <div>
            <h6 class="mb-1 fw-semibold">
              {{ $t->name }}
            </h6>
            @if($t->beschreibung)
            <div class="small text-muted text-truncate" style="max-width: 250px;">
              {{ Str::limit($t->beschreibung, 60) }}
            </div>
            @endif
          </div>
          <div class="form-check form-switch m-0">
            <input class="form-check-input toggle-aktiv-mobile" type="checkbox" role="switch"
                   data-id="{{ $t->id }}" @checked((int)$t->aktiv === 1)>
          </div>
        </div>
        
        <div class="d-flex gap-2 pt-2 border-top">
          <a href="{{ route('tour.show', ['id' => $t->id, 'returnTo' => url()->full()]) }}" 
             class="btn btn-sm btn-outline-secondary flex-fill">
            <i class="bi bi-eye"></i> Details
          </a>
          <a href="{{ route('tour.edit', ['tour' => $t->id, 'returnTo' => url()->full()]) }}" 
             class="btn btn-sm btn-outline-primary flex-fill">
            <i class="bi bi-pencil"></i> Bearbeiten
          </a>
          <button type="submit" form="del-mobile-{{ $t->id }}" 
                  class="btn btn-sm btn-outline-danger"
                  onclick="return confirm('Tour wirklich loeschen?')">
            <i class="bi bi-trash"></i>
          </button>
        </div>
      </div>
    </div>
    @endforeach

    {{-- Mobile Delete Forms --}}
    @foreach($touren as $t)
    <form id="del-mobile-{{ $t->id }}" method="POST" action="{{ route('tour.destroy', $t->id) }}" class="d-none">
      @csrf
      @method('DELETE')
      <input type="hidden" name="returnTo" value="{{ url()->full() }}">
    </form>
    @endforeach
  </div>

  {{-- DESKTOP: Tabelle mit Drag&Drop --}}
  <div class="d-none d-md-block">
    <div class="card shadow-sm">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th style="width:50px;"></th>
              <th>Name</th>
              <th>Beschreibung</th>
              <th style="width:100px;" class="text-center">Aktiv</th>
              <th style="width:140px;" class="text-end">Aktionen</th>
            </tr>
          </thead>
          <tbody id="touren-tbody">
            @foreach($touren as $t)
            <tr data-id="{{ $t->id }}" class="@if(!$t->aktiv) table-secondary @endif">
              <td class="drag-handle text-muted" title="Ziehen zum Sortieren">
                <i class="bi bi-grip-vertical fs-5"></i>
              </td>
              <td>
                <div class="fw-semibold">{{ $t->name }}</div>
                <div class="small text-muted">
                  {{ $t->gebaeude->count() }} Gebaeude
                </div>
              </td>
              <td class="text-muted" style="max-width: 400px;">
                {{ Str::limit($t->beschreibung, 80) }}
              </td>
              <td class="text-center">
                <div class="form-check form-switch d-inline-block m-0">
                  <input class="form-check-input toggle-aktiv" type="checkbox" role="switch"
                         @checked((int)$t->aktiv === 1)>
                </div>
              </td>
              <td class="text-end">
                <div class="btn-group btn-group-sm">
                  <a href="{{ route('tour.show', ['id' => $t->id, 'returnTo' => url()->full()]) }}"
                     class="btn btn-outline-secondary" title="Details">
                    <i class="bi bi-eye"></i>
                  </a>
                  <a href="{{ route('tour.edit', ['tour' => $t->id, 'returnTo' => url()->full()]) }}"
                     class="btn btn-outline-primary" title="Bearbeiten">
                    <i class="bi bi-pencil"></i>
                  </a>
                  <form method="POST" action="{{ route('tour.destroy', $t->id) }}" class="d-inline"
                        onsubmit="return confirm('Tour wirklich loeschen?');">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="returnTo" value="{{ url()->full() }}">
                    <button type="submit" class="btn btn-outline-danger" title="Loeschen">
                      <i class="bi bi-trash"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      {{-- Pagination --}}
      @if(method_exists($touren, 'links'))
      <div class="card-footer bg-light">
        {{ $touren->withQueryString()->links() }}
      </div>
      @endif
    </div>
  </div>

  {{-- Mobile Pagination --}}
  @if(method_exists($touren, 'links'))
  <div class="d-md-none mt-3">
    {{ $touren->withQueryString()->links() }}
  </div>
  @endif

  @endif
</div>
@endsection

@push('styles')
<style>
/* Drag-Feedback */
#touren-tbody .sortable-ghost { opacity: 0.4; }
#touren-tbody .sortable-chosen { background-color: #e3f2fd !important; }

/* Keine Textselektion beim Drag */
#touren-tbody tr,
#touren-tbody td,
#touren-tbody .drag-handle {
  -webkit-user-select: none;
  -moz-user-select: none;
  user-select: none;
}

body.dnd-active {
  -webkit-user-select: none !important;
  -moz-user-select: none !important;
  user-select: none !important;
  cursor: grabbing !important;
}

.drag-handle { cursor: grab; }
.drag-handle:active { cursor: grabbing; }

@media (max-width: 767.98px) {
  .form-control, .form-select, .btn { min-height: 44px; }
  .form-check-input { width: 1.25em; height: 1.25em; }
}
</style>
@endpush

@push('scripts')
<script>
(function loadSortableAndInit(){
  var urls = [
    'https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js',
    'https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.2/Sortable.min.js',
    'https://unpkg.com/sortablejs@1.15.2/Sortable.min.js'
  ];

  function inject(url, cb){
    var s = document.createElement('script');
    s.src = url;
    s.async = true;
    s.crossOrigin = 'anonymous';
    s.onload = function() { cb(true); };
    s.onerror = function() { cb(false); };
    document.head.appendChild(s);
  }

  (function tryNext(i){
    if (window.Sortable) return initDnD();
    if (i >= urls.length) {
      console.error('SortableJS konnte nicht geladen werden.');
      return;
    }
    inject(urls[i], function(ok) { ok ? initDnD() : tryNext(i+1); });
  })(0);

  function initDnD(){
    if (!window.Sortable) { console.error('Sortable nicht verfuegbar'); return; }

    var tbody = document.getElementById('touren-tbody');
    var btnReorderSave = document.getElementById('btn-reorder-save');
    if (!tbody) return;

    var BASE_URL = "{{ url('tour') }}";
    var REORDER_URL = "{{ route('tour.reorder') }}";
    var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    // Aktiv-Toggle Desktop
    tbody.addEventListener('change', function(e) {
      var el = e.target;
      if (!el.classList.contains('toggle-aktiv')) return;
      var tr = el.closest('tr');
      var id = tr?.dataset.id;
      if (!id) return;

      fetch(BASE_URL + '/' + id + '/toggle', {
        method: 'PATCH',
        headers: {'Content-Type':'application/json','X-CSRF-TOKEN':csrf,'Accept':'application/json'},
        body: JSON.stringify({ aktiv: el.checked ? 1 : 0 })
      })
      .then(function(res) {
        if (!res.ok) throw new Error();
        tr.classList.toggle('table-secondary', !el.checked);
      })
      .catch(function() {
        el.checked = !el.checked;
        alert('Status konnte nicht gespeichert werden.');
      });
    });

    // Aktiv-Toggle Mobile
    document.querySelectorAll('.toggle-aktiv-mobile').forEach(function(el) {
      el.addEventListener('change', function() {
        var id = el.dataset.id;
        fetch(BASE_URL + '/' + id + '/toggle', {
          method: 'PATCH',
          headers: {'Content-Type':'application/json','X-CSRF-TOKEN':csrf,'Accept':'application/json'},
          body: JSON.stringify({ aktiv: el.checked ? 1 : 0 })
        })
        .catch(function() {
          el.checked = !el.checked;
          alert('Status konnte nicht gespeichert werden.');
        });
      });
    });

    // Selectstart verhindern
    tbody.addEventListener('selectstart', function(e) {
      if (e.target.closest('.drag-handle')) e.preventDefault();
    });

    // Drag & Drop
    new Sortable(tbody, {
      animation: 150,
      handle: '.drag-handle',
      ghostClass: 'sortable-ghost',
      chosenClass: 'sortable-chosen',
      forceFallback: true,
      fallbackOnBody: true,
      fallbackTolerance: 5,
      delayOnTouchOnly: true,
      delay: 100,
      filter: 'a,button,input,select,textarea,label',
      preventOnFilter: true,
      onStart: function() { document.body.classList.add('dnd-active'); },
      onEnd: function() {
        document.body.classList.remove('dnd-active');
        saveOrder();
      },
      setData: function(dt) { try { dt.setData('text/plain',''); } catch(e){} }
    });

    function collectItems(){
      return Array.from(tbody.querySelectorAll('tr[data-id]'))
        .map(function(tr, idx) { return { id: parseInt(tr.dataset.id,10), reihenfolge: idx+1 }; });
    }

    function saveOrder(){
      var items = collectItems();
      if (!items.length) return;

      if (btnReorderSave) {
        var origText = btnReorderSave.innerHTML;
        btnReorderSave.disabled = true;
        btnReorderSave.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Speichern...';

        fetch(REORDER_URL, {
          method:'PATCH',
          headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf,'Accept':'application/json'},
          body: JSON.stringify({ items: items })
        })
        .then(function(res) {
          if (!res.ok) throw new Error();
          btnReorderSave.classList.replace('btn-success','btn-outline-success');
          btnReorderSave.innerHTML = '<i class="bi bi-check2-circle"></i> Gespeichert';
          setTimeout(function() {
            btnReorderSave.classList.replace('btn-outline-success','btn-success');
            btnReorderSave.innerHTML = origText;
            btnReorderSave.disabled = false;
          }, 1500);
        })
        .catch(function() {
          alert('Reihenfolge konnte nicht gespeichert werden.');
          btnReorderSave.disabled = false;
          btnReorderSave.innerHTML = origText;
        });
      }
    }

    if (btnReorderSave) {
      btnReorderSave.addEventListener('click', saveOrder);
    }
  }
})();
</script>
@endpush
