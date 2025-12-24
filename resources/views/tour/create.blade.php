{{-- resources/views/tour/create.blade.php --}}
{{-- MOBIL-OPTIMIERT: Cards mit Farben, Sticky Footer --}}

@extends('layouts.app')

@section('content')
<div class="container-fluid px-2 px-md-4 py-3">

  {{-- Header --}}
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h4 class="mb-0">
      <i class="bi bi-plus-circle text-primary"></i>
      <span class="d-none d-sm-inline">Neue Tour</span>
      <span class="d-sm-none">Neu</span>
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

  {{-- Formular --}}
  <form method="POST" action="{{ route('tour.store') }}" id="tourForm">
    @csrf

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
          <div class="col-12 col-md-8">
            <label for="name" class="form-label small mb-1">Name <span class="text-danger">*</span></label>
            <input type="text" id="name" name="name"
              class="form-control @error('name') is-invalid @enderror"
              value="{{ old('name') }}"
              placeholder="Bezeichnung der Tour"
              required maxlength="255" autocomplete="off">
            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

          {{-- Aktiv --}}
          <div class="col-12 col-md-4">
            <label class="form-label small mb-1">Status</label>
            <div class="form-control bg-light d-flex align-items-center">
              <input type="hidden" name="aktiv" value="0">
              <div class="form-check form-switch m-0">
                <input class="form-check-input" type="checkbox" role="switch"
                       id="aktiv" name="aktiv" value="1"
                       @checked(old('aktiv', 1) == 1)>
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
          placeholder="Kurzbeschreibung oder Hinweise...">{{ old('beschreibung') }}</textarea>
        @error('beschreibung') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
    </div>

    {{-- Info Card --}}
    <div class="card bg-light mb-3">
      <div class="card-body py-2 px-3">
        <div class="d-flex align-items-center gap-2 text-muted small">
          <i class="bi bi-info-circle"></i>
          <span>Nach dem Speichern koennen Gebaeude zur Tour hinzugefuegt werden.</span>
        </div>
      </div>
    </div>

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
          <div class="d-flex gap-2">
            <a href="{{ $backUrl }}" class="btn btn-outline-secondary">
              <i class="bi bi-arrow-left"></i> Abbrechen
            </a>
            <button type="submit" class="btn btn-primary">
              <i class="bi bi-check2-circle"></i> Speichern
            </button>
          </div>
        </div>
      </div>
    </div>
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
