{{-- resources/views/tour/show.blade.php --}}
{{-- MOBIL-OPTIMIERT: Card-basiertes Detail-Layout --}}

@extends('layouts.app')

@section('content')
<div class="container-fluid px-2 px-md-4 py-3">

  {{-- Header --}}
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h4 class="mb-0">
      <i class="bi bi-signpost-2 text-primary"></i>
      <span class="d-none d-sm-inline">Tour Details</span>
    </h4>
    @php
      $backUrl = request()->query('returnTo') ?: route('tour.index');
    @endphp
    <div class="d-flex gap-2">
      <a href="{{ route('tour.edit', ['tour' => $tour->id, 'returnTo' => url()->full()]) }}" 
         class="btn btn-primary btn-sm">
        <i class="bi bi-pencil"></i>
        <span class="d-none d-sm-inline ms-1">Bearbeiten</span>
      </a>
      <a href="{{ $backUrl }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i>
        <span class="d-none d-sm-inline ms-1">Zurueck</span>
      </a>
    </div>
  </div>

  {{-- Hauptinfo Card --}}
  <div class="card shadow-sm mb-3">
    <div class="card-header bg-primary text-white py-2">
      <div class="d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
          <i class="bi bi-signpost-2-fill"></i>
          <span class="fw-semibold">{{ $tour->name }}</span>
        </div>
        @if($tour->aktiv)
          <span class="badge bg-light text-success">
            <i class="bi bi-check-circle-fill"></i> Aktiv
          </span>
        @else
          <span class="badge bg-light text-secondary">
            <i class="bi bi-pause-circle-fill"></i> Inaktiv
          </span>
        @endif
      </div>
    </div>
    <div class="card-body p-0">
      <ul class="list-group list-group-flush">
        <li class="list-group-item">
          <div class="row">
            <div class="col-auto"><i class="bi bi-hash text-primary"></i></div>
            <div class="col">
              <div class="small text-muted">ID</div>
              <div>{{ $tour->id }}</div>
            </div>
          </div>
        </li>
        @if(!is_null($tour->reihenfolge))
        <li class="list-group-item">
          <div class="row">
            <div class="col-auto"><i class="bi bi-sort-numeric-down text-primary"></i></div>
            <div class="col">
              <div class="small text-muted">Reihenfolge</div>
              <div>{{ $tour->reihenfolge }}</div>
            </div>
          </div>
        </li>
        @endif
        <li class="list-group-item">
          <div class="row">
            <div class="col-auto"><i class="bi bi-buildings text-primary"></i></div>
            <div class="col">
              <div class="small text-muted">Verknuepfte Gebaeude</div>
              <div class="fw-semibold">{{ $tour->gebaeude->count() }}</div>
            </div>
          </div>
        </li>
      </ul>
    </div>
  </div>

  {{-- Beschreibung Card --}}
  <div class="card shadow-sm mb-3">
    <div class="card-header bg-secondary text-white py-2">
      <i class="bi bi-card-text"></i>
      <span class="fw-semibold ms-1">Beschreibung</span>
    </div>
    <div class="card-body">
      @if(filled($tour->beschreibung))
        <p class="mb-0" style="white-space: pre-wrap;">{{ $tour->beschreibung }}</p>
      @else
        <span class="text-muted"><i class="bi bi-dash"></i> Keine Beschreibung hinterlegt.</span>
      @endif
    </div>
  </div>

  {{-- Verknuepfte Gebaeude Card --}}
  <div class="card shadow-sm mb-3">
    <div class="card-header bg-info text-white py-2 d-flex justify-content-between align-items-center">
      <div>
        <i class="bi bi-buildings"></i>
        <span class="fw-semibold ms-1">Verknuepfte Gebaeude ({{ $tour->gebaeude->count() }})</span>
      </div>
      @if($tour->gebaeude->isNotEmpty())
      <button type="submit" form="bulk-detach-form" class="btn btn-sm btn-outline-light" 
              id="bulk-detach-btn" disabled
              onclick="return confirm('Ausgewaehlte Verknuepfungen wirklich loeschen?')">
        <i class="bi bi-trash"></i>
        <span class="d-none d-sm-inline ms-1">Ausgewaehlte entfernen</span>
      </button>
      @endif
    </div>

    {{-- Bulk Form --}}
    <form id="bulk-detach-form" method="POST" action="{{ route('tour.gebaeude.bulkDetach', $tour->id) }}">
      @csrf
      @method('DELETE')
      <input type="hidden" name="returnTo" value="{{ url()->full() }}">
    </form>

    @if($tour->gebaeude->isEmpty())
      <div class="card-body text-center text-muted py-4">
        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
        Keine Gebaeude verknuepft.
      </div>
    @else

    {{-- MOBILE: Card-Layout --}}
    <div class="d-md-none">
      @foreach($tour->gebaeude as $g)
      <div class="border-bottom p-3">
        <div class="d-flex align-items-start gap-2">
          <input type="checkbox" class="form-check-input row-check mt-1"
                 name="gebaeude_ids[]" value="{{ $g->id }}" form="bulk-detach-form">
          <div class="flex-grow-1 min-w-0">
            <div class="fw-semibold">{{ $g->gebaeude_name ?: 'Gebaeude #'.$g->id }}</div>
            <div class="small text-muted">
              @if($g->codex)<span class="badge bg-light text-dark me-1">{{ $g->codex }}</span>@endif
              {{ $g->strasse }} {{ $g->hausnummer }}, {{ $g->wohnort }}
            </div>
          </div>
        </div>
        <div class="d-flex gap-2 mt-2 ps-4">
          <a href="{{ route('gebaeude.edit', ['id' => $g->id]) }}" 
             class="btn btn-sm btn-outline-primary flex-fill">
            <i class="bi bi-pencil"></i> Bearbeiten
          </a>
          <form method="POST" action="{{ route('tour.gebaeude.detach', ['tour' => $tour->id, 'gebaeude' => $g->id]) }}"
                onsubmit="return confirm('Verknuepfung entfernen?')">
            @csrf
            @method('DELETE')
            <input type="hidden" name="returnTo" value="{{ url()->full() }}">
            <button type="submit" class="btn btn-sm btn-outline-danger">
              <i class="bi bi-x-lg"></i>
            </button>
          </form>
        </div>
      </div>
      @endforeach
    </div>

    {{-- DESKTOP: Tabelle --}}
    <div class="d-none d-md-block">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th style="width:40px;">
                <input type="checkbox" id="check-all" class="form-check-input">
              </th>
              <th>Codex</th>
              <th>Gebaeudeame</th>
              <th>Strasse</th>
              <th>Nr.</th>
              <th>Wohnort</th>
              <th class="text-end" style="width:120px;">Aktionen</th>
            </tr>
          </thead>
          <tbody>
            @foreach($tour->gebaeude as $g)
            <tr>
              <td>
                <input type="checkbox" class="form-check-input row-check"
                       name="gebaeude_ids[]" value="{{ $g->id }}" form="bulk-detach-form">
              </td>
              <td><code>{{ $g->codex }}</code></td>
              <td class="fw-semibold">{{ $g->gebaeude_name ?: 'Gebaeude #'.$g->id }}</td>
              <td>{{ $g->strasse }}</td>
              <td>{{ $g->hausnummer }}</td>
              <td>{{ $g->wohnort }}</td>
              <td class="text-end">
                <div class="btn-group btn-group-sm">
                  <a href="{{ route('gebaeude.edit', ['id' => $g->id]) }}"
                     class="btn btn-outline-primary" title="Gebaeude bearbeiten">
                    <i class="bi bi-pencil"></i>
                  </a>
                  <form method="POST" 
                        action="{{ route('tour.gebaeude.detach', ['tour' => $tour->id, 'gebaeude' => $g->id]) }}"
                        class="d-inline"
                        onsubmit="return confirm('Verknuepfung entfernen?')">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="returnTo" value="{{ url()->full() }}">
                    <button type="submit" class="btn btn-outline-danger" title="Verknuepfung entfernen">
                      <i class="bi bi-x-lg"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
    @endif
  </div>

  {{-- Meta-Info --}}
  <div class="card bg-light">
    <div class="card-body py-2 px-3">
      <div class="row text-muted small">
        <div class="col-6">
          <i class="bi bi-calendar-plus me-1"></i>
          Erstellt: {{ $tour->created_at?->format('d.m.Y H:i') ?? '-' }}
        </div>
        <div class="col-6 text-end">
          <i class="bi bi-calendar-check me-1"></i>
          Geaendert: {{ $tour->updated_at?->format('d.m.Y H:i') ?? '-' }}
        </div>
      </div>
    </div>
  </div>

  {{-- Desktop Aktions-Footer --}}
  <div class="d-none d-md-flex gap-2 mt-4">
    <a href="{{ $backUrl }}" class="btn btn-outline-secondary">
      <i class="bi bi-arrow-left"></i> Zur Liste
    </a>
    <a href="{{ route('tour.edit', ['tour' => $tour->id, 'returnTo' => url()->full()]) }}" 
       class="btn btn-primary">
      <i class="bi bi-pencil"></i> Bearbeiten
    </a>
    <form method="POST" action="{{ route('tour.destroy', $tour->id) }}" class="ms-auto"
          onsubmit="return confirm('Tour wirklich loeschen?')">
      @csrf
      @method('DELETE')
      <input type="hidden" name="returnTo" value="{{ route('tour.index') }}">
      <button type="submit" class="btn btn-outline-danger">
        <i class="bi bi-trash"></i> Loeschen
      </button>
    </form>
  </div>

</div>

@push('styles')
<style>
.min-w-0 { min-width: 0; }
@media (max-width: 767.98px) {
  .form-check-input { width: 1.25em; height: 1.25em; }
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
  var master = document.getElementById('check-all');
  var bulkBtn = document.getElementById('bulk-detach-btn');
  
  function getChecks() {
    return Array.from(document.querySelectorAll('.row-check'));
  }

  function updateBulkState() {
    if (!bulkBtn) return;
    var any = getChecks().some(function(ch) { return ch.checked; });
    bulkBtn.disabled = !any;
  }

  if (master) {
    master.addEventListener('change', function() {
      getChecks().forEach(function(ch) { ch.checked = master.checked; });
      updateBulkState();
    });
  }

  getChecks().forEach(function(ch) {
    ch.addEventListener('change', function() {
      if (master) {
        var all = getChecks();
        var on = all.filter(function(c) { return c.checked; }).length;
        master.checked = (on === all.length);
        master.indeterminate = (on > 0 && on < all.length);
      }
      updateBulkState();
    });
  });

  updateBulkState();
});
</script>
@endpush
@endsection
