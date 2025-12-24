{{-- resources/views/tour/edit.blade.php --}}
{{-- MOBIL-OPTIMIERT: Cards mit Farben, Sticky Footer --}}

@extends('layouts.app')

@section('content')
<div class="container-fluid px-2 px-md-4 py-3">

  {{-- Header --}}
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h4 class="mb-0">
      <i class="bi bi-pencil-square text-primary"></i>
      <span class="d-none d-sm-inline">Tour bearbeiten</span>
      <span class="d-sm-none">Bearbeiten</span>
    </h4>
    @php
      $backUrl = request()->query('returnTo') ?: route('tour.index');
    @endphp
    <a href="{{ $backUrl }}" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-arrow-left"></i>
      <span class="d-none d-sm-inline ms-1">Zurueck</span>
    </a>
  </div>

  {{-- Validierungsfehler --}}
  @if($errors->any())
    <div class="alert alert-danger py-2">
      <div class="d-flex align-items-center gap-2">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <strong>Bitte korrigieren:</strong>
      </div>
      <ul class="mb-0 mt-1 small">
        @foreach($errors->all() as $e)
          <li>{{ $e }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  {{-- Kurzinfo-Card --}}
  <div class="card border-primary mb-3">
    <div class="card-body py-2 px-3">
      <div class="row align-items-center">
        <div class="col">
          <div class="fw-semibold text-primary">
            <i class="bi bi-signpost-2 me-1"></i>{{ $tour->name }}
          </div>
          <div class="small text-muted">
            <i class="bi bi-buildings me-1"></i>{{ $tour->gebaeude->count() }} Gebaeude verknuepft
            @if($tour->reihenfolge)
              <span class="mx-1">|</span>
              <i class="bi bi-sort-numeric-down me-1"></i>Position: {{ $tour->reihenfolge }}
            @endif
          </div>
        </div>
        <div class="col-auto">
          @if($tour->aktiv)
            <span class="badge bg-success">Aktiv</span>
          @else
            <span class="badge bg-secondary">Inaktiv</span>
          @endif
        </div>
      </div>
    </div>
  </div>

  {{-- Formular --}}
  <form method="POST" action="{{ route('tour.update', $tour->id) }}" id="tourForm">
    @csrf
    @method('PUT')

    @if(!empty(request()->query('returnTo')))
      <input type="hidden" name="returnTo" value="{{ request()->query('returnTo') }}">
    @endif

    {{-- Grunddaten Card --}}
    <div class="card mb-3">
      <div class="card-header bg-primary text-white py-2">
        <i class="bi bi-signpost-2-fill"></i>
        <span class="fw-semibold ms-1">Grunddaten</span>
      </div>
      <div class="card-body p-2 p-md-3">
        <div class="row g-3">
          {{-- Name --}}
          <div class="col-12 col-md-6">
            <label for="name" class="form-label small mb-1">Name <span class="text-danger">*</span></label>
            <input type="text" id="name" name="name"
              class="form-control @error('name') is-invalid @enderror"
              value="{{ old('name', $tour->name) }}"
              placeholder="Bezeichnung der Tour"
              required maxlength="255" autocomplete="off">
            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

          {{-- Reihenfolge --}}
          <div class="col-6 col-md-3">
            <label for="reihenfolge" class="form-label small mb-1">Reihenfolge</label>
            <input type="number" id="reihenfolge" name="reihenfolge"
              class="form-control @error('reihenfolge') is-invalid @enderror"
              value="{{ old('reihenfolge', $tour->reihenfolge) }}"
              min="0" step="1">
            @error('reihenfolge') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

          {{-- Aktiv --}}
          <div class="col-6 col-md-3">
            <label class="form-label small mb-1">Status</label>
            <div class="form-control bg-light d-flex align-items-center">
              <input type="hidden" name="aktiv" value="0">
              <div class="form-check form-switch m-0">
                <input class="form-check-input" type="checkbox" role="switch"
                       id="aktiv" name="aktiv" value="1"
                       @checked(old('aktiv', (int)$tour->aktiv) == 1)>
                <label class="form-check-label" for="aktiv">Aktiv</label>
              </div>
            </div>
            @error('aktiv') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
          </div>
        </div>
      </div>
    </div>

    {{-- Beschreibung Card --}}
    <div class="card mb-3">
      <div class="card-header bg-secondary text-white py-2">
        <i class="bi bi-card-text"></i>
        <span class="fw-semibold ms-1">Beschreibung</span>
      </div>
      <div class="card-body p-2 p-md-3">
        <textarea id="beschreibung" name="beschreibung" rows="4"
          class="form-control @error('beschreibung') is-invalid @enderror"
          placeholder="Kurzbeschreibung oder Hinweise...">{{ old('beschreibung', $tour->beschreibung) }}</textarea>
        @error('beschreibung') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
    </div>

    {{-- Verknuepfte Gebaeude Info --}}
    @if($tour->gebaeude->isNotEmpty())
    <div class="card bg-info bg-opacity-10 border-info mb-3">
      <div class="card-body py-2 px-3">
        <div class="d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center gap-2">
            <i class="bi bi-buildings text-info"></i>
            <span class="small">{{ $tour->gebaeude->count() }} Gebaeude verknuepft</span>
          </div>
          <a href="{{ route('tour.show', ['id' => $tour->id, 'returnTo' => url()->full()]) }}" 
             class="btn btn-sm btn-outline-info">
            <i class="bi bi-eye"></i> Anzeigen
          </a>
        </div>
      </div>
    </div>
    @endif

    {{-- Sticky Footer Mobile --}}
    <div class="sticky-bottom-bar d-md-none">
      <div class="d-flex gap-2">
        <a href="{{ $backUrl }}" class="btn btn-outline-secondary flex-fill">
          <i class="bi bi-x-lg"></i> Abbrechen
        </a>
        <button type="submit" class="btn btn-primary flex-fill">
          <i class="bi bi-check-lg"></i> Speichern
        </button>
      </div>
    </div>

    {{-- Desktop Footer --}}
    <div class="d-none d-md-block">
      <div class="card bg-light">
        <div class="card-body py-2 px-3">
          <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex gap-2">
              <a href="{{ $backUrl }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Abbrechen
              </a>
              <button type="submit" class="btn btn-primary">
                <i class="bi bi-check2-circle"></i> Speichern
              </button>
            </div>
            <div class="d-flex gap-2">
              <a href="{{ route('tour.show', ['id' => $tour->id]) }}" class="btn btn-outline-info">
                <i class="bi bi-eye"></i> Details
              </a>
              <button type="button" class="btn btn-outline-danger" 
                      onclick="if(confirm('Tour wirklich loeschen?')) document.getElementById('deleteForm').submit()">
                <i class="bi bi-trash"></i> Loeschen
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </form>

  {{-- Loeschen-Form (separat) --}}
  <form id="deleteForm" method="POST" action="{{ route('tour.destroy', $tour->id) }}" class="d-none">
    @csrf
    @method('DELETE')
    <input type="hidden" name="returnTo" value="{{ route('tour.index') }}">
  </form>

</div>

@push('styles')
<style>
.sticky-bottom-bar {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  background: #fff;
  border-top: 1px solid #dee2e6;
  padding: 12px 16px;
  z-index: 1030;
  box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
}
@media (max-width: 767.98px) {
  .container-fluid { padding-bottom: 80px; }
  .form-control, .form-select { min-height: 44px; font-size: 16px !important; }
}
</style>
@endpush
@endsection
