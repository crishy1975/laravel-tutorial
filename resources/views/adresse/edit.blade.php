{{-- resources/views/adresse/edit.blade.php --}}
{{-- MOBIL-OPTIMIERT: Cards mit Farben, Sticky Footer --}}
@extends('layouts.app')

@section('content')
<div class="container-fluid px-2 px-md-4 py-3">

  {{-- Header --}}
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h4 class="mb-0">
      <i class="bi bi-pencil-square text-primary"></i>
      <span class="d-none d-sm-inline">Adresse bearbeiten</span>
      <span class="d-sm-none">Bearbeiten</span>
    </h4>
    @php
      $returnToVal = old('returnTo', ($returnTo ?? request()->query('returnTo')));
      $backHref = !empty($returnToVal) ? $returnToVal : route('adresse.show', $adresse->id);
    @endphp
    <a href="{{ $backHref }}" class="btn btn-outline-secondary btn-sm">
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
            <i class="bi bi-building me-1"></i>{{ $adresse->name }}
          </div>
          <div class="small text-muted">
            <i class="bi bi-geo-alt me-1"></i>
            {{ $adresse->strasse }} {{ $adresse->hausnummer }}, 
            {{ $adresse->plz }} {{ $adresse->wohnort }}
            @if($adresse->provinz) ({{ $adresse->provinz }}) @endif
          </div>
        </div>
        <div class="col-auto">
          <span class="badge bg-primary">ID: {{ $adresse->id }}</span>
        </div>
      </div>
    </div>
  </div>

  {{-- Hauptformular --}}
  <form method="POST" action="{{ route('adresse.update', $adresse->id) }}" id="adresseForm">
    @csrf
    @method('PUT')

    @if(!empty($returnToVal))
      <input type="hidden" name="returnTo" value="{{ $returnToVal }}">
    @endif

    {{-- Formularfelder (Cards) --}}
    @include('adresse._form', ['adresse' => $adresse])

    {{-- Sticky Footer fuer Mobile --}}
    <div class="sticky-bottom-bar d-md-none">
      <div class="d-flex gap-2">
        <a href="{{ $backHref }}" class="btn btn-outline-secondary flex-fill">
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
              <a href="{{ $backHref }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Abbrechen
              </a>
              <button type="submit" class="btn btn-primary">
                <i class="bi bi-check2-circle"></i> Speichern
              </button>
            </div>
            <div class="d-flex gap-2">
              <a href="{{ route('adresse.show', $adresse->id) }}" class="btn btn-outline-info">
                <i class="bi bi-eye"></i> Details
              </a>
              <button type="button" class="btn btn-outline-danger" 
                      onclick="if(confirm('Adresse wirklich loeschen?')) document.getElementById('deleteForm').submit()">
                <i class="bi bi-trash"></i> Loeschen
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </form>

  {{-- Loeschen-Form (separat) --}}
  <form id="deleteForm" method="POST" action="{{ route('adresse.destroy', $adresse->id) }}" class="d-none">
    @csrf
    @method('DELETE')
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
