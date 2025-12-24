{{-- resources/views/adresse/create.blade.php --}}
{{-- MOBIL-OPTIMIERT: Sticky Footer, Touch-freundlich --}}
@extends('layouts.app')

@section('content')
<div class="container-fluid px-2 px-md-4 py-3">

  {{-- Header --}}
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h4 class="mb-0">
      <i class="bi bi-person-plus text-primary"></i>
      <span class="d-none d-sm-inline">Neue Adresse</span>
      <span class="d-sm-none">Neu</span>
    </h4>
    <a href="{{ route('adresse.index') }}" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-arrow-left"></i>
      <span class="d-none d-sm-inline ms-1">Zurueck</span>
    </a>
  </div>

  {{-- VIES-Check Card --}}
  <div class="card mb-3 border-info">
    <div class="card-header bg-info text-white py-2">
      <i class="bi bi-search"></i>
      <span class="fw-semibold ms-1">VIES-Abfrage (EU MwSt-Pruefung)</span>
    </div>
    <div class="card-body p-2 p-md-3">
      <form method="POST" action="{{ route('tools.viesLookup') }}">
        @csrf
        <input type="hidden" name="country" value="IT">
        <div class="row g-2 align-items-end">
          <div class="col">
            <div class="input-group">
              <span class="input-group-text bg-light">IT</span>
              <input name="vat" class="form-control" placeholder="Partita IVA eingeben..."
                     value="{{ session('vies_mwst', old('mwst_nummer', $adresse->mwst_nummer ?? '')) }}">
            </div>
          </div>
          <div class="col-auto">
            <button class="btn btn-info text-white">
              <i class="bi bi-search"></i>
              <span class="d-none d-sm-inline ms-1">VIES pruefen</span>
            </button>
          </div>
        </div>
      </form>

      {{-- VIES Ergebnis --}}
      @if(session('vies_valid') !== null)
        @if(session('vies_valid'))
          <div class="alert alert-success mt-2 mb-0 py-2 small">
            <i class="bi bi-check-circle-fill me-1"></i>
            <strong>Gueltig!</strong>
            {{ session('vies_name') }}
            @if(session('vies_address'))
              <br><span class="text-muted">{!! nl2br(e(session('vies_address'))) !!}</span>
            @endif
          </div>
        @else
          <div class="alert alert-warning mt-2 mb-0 py-2 small">
            <i class="bi bi-exclamation-triangle me-1"></i>
            USt-ID ungueltig oder keine Details verfuegbar.
          </div>
        @endif
      @endif

      @error('vies')
        <div class="alert alert-danger mt-2 mb-0 py-2 small">{{ $message }}</div>
      @enderror
    </div>
  </div>

  {{-- Hauptformular --}}
  <form method="POST" action="{{ route('adresse.store') }}" id="adresseForm">
    @csrf

    @if(!empty(request()->query('returnTo')))
      <input type="hidden" name="returnTo" value="{{ request()->query('returnTo') }}">
    @endif

    {{-- Formularfelder (Cards) --}}
    @include('adresse._form', ['adresse' => $adresse])

    {{-- Sticky Footer fuer Mobile --}}
    <div class="sticky-bottom-bar d-md-none">
      <div class="d-flex gap-2">
        <a href="{{ route('adresse.index') }}" class="btn btn-outline-secondary flex-fill">
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
            <a href="{{ route('adresse.index') }}" class="btn btn-outline-secondary">
              <i class="bi bi-arrow-left"></i> Zurueck
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
}
</style>
@endpush
@endsection
