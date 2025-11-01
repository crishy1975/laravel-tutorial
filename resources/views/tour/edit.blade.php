{{-- resources/views/tour/edit.blade.php --}}
{{-- Bearbeiten-View für eine Tour (singular "tour") mit returnTo-Unterstützung --}}

@extends('layouts.app')

@section('content')
<div class="container py-4">

  {{-- Überschrift --}}
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <h3 class="mb-0">
      <i class="bi bi-pencil-square"></i> Tour bearbeiten
      <small class="text-muted">#{{ $tour->id }}</small>
    </h3>

    {{-- Zurück: nutzt returnTo (falls vorhanden), sonst Index --}}
    @php
      $backUrl = request()->query('returnTo') ?: route('tour.index');
    @endphp
    <a href="{{ $backUrl }}" class="btn btn-outline-secondary">
      <i class="bi bi-arrow-left"></i> Zurück
    </a>
  </div>

  {{-- Validierungsfehler anzeigen --}}
  @if($errors->any())
    <div class="alert alert-danger">
      <div class="fw-semibold mb-1"><i class="bi bi-exclamation-triangle"></i> Bitte Eingaben prüfen:</div>
      <ul class="mb-0">
        @foreach($errors->all() as $e)
          <li>{{ $e }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  {{-- Formular: Update der Tour --}}
  <form method="POST" action="{{ route('tour.update', $tour->id) }}" class="card">
    @csrf
    @method('PUT')

    {{-- returnTo aus Query in POST übernehmen, damit Controller zurückleiten kann --}}
    @if(!empty(request()->query('returnTo')))
      <input type="hidden" name="returnTo" value="{{ request()->query('returnTo') }}">
    @endif

    <div class="card-body">
      <div class="row g-3">

        {{-- Name --}}
        <div class="col-md-6">
          <label for="name" class="form-label">Name *</label>
          <input
            type="text"
            id="name"
            name="name"
            class="form-control @error('name') is-invalid @enderror"
            required
            maxlength="255"
            value="{{ old('name', $tour->name) }}"
            placeholder="Bezeichnung der Tour"
            autocomplete="off"
          >
          @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>

        {{-- Reihenfolge (für Jahresplan) --}}
        <div class="col-md-3">
          <label for="reihenfolge" class="form-label">Reihenfolge *</label>
          <input
            type="number"
            id="reihenfolge"
            name="reihenfolge"
            class="form-control @error('reihenfolge') is-invalid @enderror"
            value="{{ old('reihenfolge', $tour->reihenfolge) }}"
            min="0"
            step="1"
            required
          >
          @error('reihenfolge')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>

        {{-- Aktiv (ein-/ausblenden) --}}
        <div class="col-md-3">
          <label class="form-label d-block">Aktiv</label>

          {{-- Hidden 0, damit beim Uncheck eine 0 gesendet wird --}}
          <input type="hidden" name="aktiv" value="0">

          <div class="form-check form-switch">
            <input
              class="form-check-input"
              type="checkbox"
              role="switch"
              id="aktiv"
              name="aktiv"
              value="1"
              @checked(old('aktiv', (int)$tour->aktiv) == 1)
            >
            <label class="form-check-label" for="aktiv">Tour in Listen anzeigen</label>
          </div>
          @error('aktiv')
            <div class="text-danger small mt-1">{{ $message }}</div>
          @enderror
        </div>

        {{-- Beschreibung --}}
        <div class="col-12">
          <label for="beschreibung" class="form-label">Beschreibung</label>
          <textarea
            id="beschreibung"
            name="beschreibung"
            rows="4"
            class="form-control @error('beschreibung') is-invalid @enderror"
            placeholder="Kurzbeschreibung oder Hinweise …"
          >{{ old('beschreibung', $tour->beschreibung) }}</textarea>
          @error('beschreibung')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>

      </div>
    </div>

    <div class="card-footer d-flex gap-2 justify-content-end">
      <a href="{{ $backUrl }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Abbrechen
      </a>
      <button type="submit" class="btn btn-primary">
        <i class="bi bi-save"></i> Speichern
      </button>
    </div>
  </form>

  {{-- Optional: Schnell-Löschen direkt aus Edit (achtet auf returnTo) --}}
  <form method="POST" action="{{ route('tour.destroy', $tour->id) }}"
        class="mt-3"
        onsubmit="return confirm('Diese Tour wirklich löschen?');">
    @csrf
    @method('DELETE')
    <input type="hidden" name="returnTo" value="{{ $backUrl }}">
    <button type="submit" class="btn btn-outline-danger">
      <i class="bi bi-trash"></i> Tour löschen
    </button>
  </form>

</div>
@endsection
