{{-- resources/views/adresse/show.blade.php --}}
{{-- MOBIL-OPTIMIERT: Card-basiertes Detail-Layout --}}
@extends('layouts.app')

@section('content')
<div class="container-fluid px-2 px-md-4 py-3">

  {{-- Header --}}
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h4 class="mb-0">
      <i class="bi bi-person text-primary"></i>
      <span class="d-none d-sm-inline">Adresse</span>
    </h4>
    <div class="d-flex gap-2">
      <a href="{{ route('adresse.edit', $adresse->id) }}" class="btn btn-primary btn-sm">
        <i class="bi bi-pencil"></i>
        <span class="d-none d-sm-inline ms-1">Bearbeiten</span>
      </a>
      <a href="{{ route('adresse.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i>
        <span class="d-none d-sm-inline ms-1">Zurueck</span>
      </a>
    </div>
  </div>

  {{-- Hauptinfo-Card --}}
  <div class="card shadow-sm mb-3">
    <div class="card-header bg-primary text-white py-2">
      <div class="d-flex align-items-center gap-2">
        <i class="bi bi-building"></i>
        <span class="fw-semibold">{{ $adresse->name }}</span>
      </div>
    </div>
    <div class="card-body p-0">
      <ul class="list-group list-group-flush">
        {{-- Anschrift --}}
        @if($adresse->strasse || $adresse->wohnort)
        <li class="list-group-item">
          <div class="row">
            <div class="col-auto">
              <i class="bi bi-geo-alt text-primary"></i>
            </div>
            <div class="col">
              <div class="small text-muted">Anschrift</div>
              <div>
                {{ $adresse->strasse }} {{ $adresse->hausnummer }}<br>
                {{ $adresse->plz }} {{ $adresse->wohnort }}
                @if($adresse->provinz) ({{ $adresse->provinz }}) @endif
                @if($adresse->land)<br><span class="text-muted">{{ $adresse->land }}</span>@endif
              </div>
            </div>
            @if($adresse->strasse && $adresse->wohnort)
            <div class="col-auto">
              <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($adresse->strasse . ' ' . $adresse->hausnummer . ', ' . $adresse->plz . ' ' . $adresse->wohnort) }}" 
                 target="_blank" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-map"></i>
              </a>
            </div>
            @endif
          </div>
        </li>
        @endif
      </ul>
    </div>
  </div>

  {{-- Kontakt-Card --}}
  <div class="card shadow-sm mb-3">
    <div class="card-header bg-light py-2">
      <div class="d-flex align-items-center gap-2">
        <i class="bi bi-telephone text-primary"></i>
        <span class="fw-semibold small">Kontakt</span>
      </div>
    </div>
    <div class="card-body p-0">
      <ul class="list-group list-group-flush">
        @if($adresse->telefon)
        <li class="list-group-item">
          <div class="row align-items-center">
            <div class="col-auto"><i class="bi bi-telephone text-muted"></i></div>
            <div class="col">
              <div class="small text-muted">Telefon</div>
              <a href="tel:{{ $adresse->telefon }}" class="text-decoration-none">{{ $adresse->telefon }}</a>
            </div>
            <div class="col-auto">
              <a href="tel:{{ $adresse->telefon }}" class="btn btn-sm btn-outline-success">
                <i class="bi bi-telephone-fill"></i>
              </a>
            </div>
          </div>
        </li>
        @endif

        @if($adresse->handy)
        <li class="list-group-item">
          <div class="row align-items-center">
            <div class="col-auto"><i class="bi bi-phone text-muted"></i></div>
            <div class="col">
              <div class="small text-muted">Handy</div>
              <a href="tel:{{ $adresse->handy }}" class="text-decoration-none">{{ $adresse->handy }}</a>
            </div>
            <div class="col-auto">
              <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $adresse->handy) }}" 
                 target="_blank" class="btn btn-sm btn-outline-success">
                <i class="bi bi-whatsapp"></i>
              </a>
            </div>
          </div>
        </li>
        @endif

        @if($adresse->email)
        <li class="list-group-item">
          <div class="row align-items-center">
            <div class="col-auto"><i class="bi bi-envelope text-muted"></i></div>
            <div class="col min-w-0">
              <div class="small text-muted">E-Mail</div>
              <a href="mailto:{{ $adresse->email }}" class="text-decoration-none text-truncate d-block">{{ $adresse->email }}</a>
            </div>
            <div class="col-auto">
              <a href="mailto:{{ $adresse->email }}" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-envelope-fill"></i>
              </a>
            </div>
          </div>
        </li>
        @endif

        @if($adresse->email_zweit)
        <li class="list-group-item">
          <div class="row align-items-center">
            <div class="col-auto"><i class="bi bi-envelope text-muted"></i></div>
            <div class="col min-w-0">
              <div class="small text-muted">Zweit-E-Mail</div>
              <a href="mailto:{{ $adresse->email_zweit }}" class="text-decoration-none text-truncate d-block">{{ $adresse->email_zweit }}</a>
            </div>
          </div>
        </li>
        @endif

        @if($adresse->pec)
        <li class="list-group-item">
          <div class="row align-items-center">
            <div class="col-auto"><i class="bi bi-envelope-check text-muted"></i></div>
            <div class="col min-w-0">
              <div class="small text-muted">PEC</div>
              <a href="mailto:{{ $adresse->pec }}" class="text-decoration-none text-truncate d-block">{{ $adresse->pec }}</a>
            </div>
            <div class="col-auto">
              <span class="badge bg-info">Zertifiziert</span>
            </div>
          </div>
        </li>
        @endif

        @if(!$adresse->telefon && !$adresse->handy && !$adresse->email && !$adresse->pec)
        <li class="list-group-item text-center text-muted py-3">
          <i class="bi bi-info-circle"></i> Keine Kontaktdaten hinterlegt
        </li>
        @endif
      </ul>
    </div>
  </div>

  {{-- Steuerdaten-Card --}}
  @if($adresse->steuernummer || $adresse->mwst_nummer || $adresse->codice_univoco)
  <div class="card shadow-sm mb-3">
    <div class="card-header bg-light py-2">
      <div class="d-flex align-items-center gap-2">
        <i class="bi bi-building text-primary"></i>
        <span class="fw-semibold small">Steuerdaten</span>
      </div>
    </div>
    <div class="card-body p-0">
      <ul class="list-group list-group-flush">
        @if($adresse->steuernummer)
        <li class="list-group-item">
          <div class="row">
            <div class="col-auto"><i class="bi bi-person-badge text-muted"></i></div>
            <div class="col">
              <div class="small text-muted">Steuernummer (Codice Fiscale)</div>
              <code class="fs-6">{{ $adresse->steuernummer }}</code>
            </div>
          </div>
        </li>
        @endif

        @if($adresse->mwst_nummer)
        <li class="list-group-item">
          <div class="row">
            <div class="col-auto"><i class="bi bi-receipt text-muted"></i></div>
            <div class="col">
              <div class="small text-muted">MwSt-Nummer (Partita IVA)</div>
              <code class="fs-6">IT{{ $adresse->mwst_nummer }}</code>
            </div>
          </div>
        </li>
        @endif

        @if($adresse->codice_univoco)
        <li class="list-group-item">
          <div class="row">
            <div class="col-auto"><i class="bi bi-upc-scan text-muted"></i></div>
            <div class="col">
              <div class="small text-muted">Codice Univoco (SDI)</div>
              <code class="fs-6">{{ $adresse->codice_univoco }}</code>
            </div>
          </div>
        </li>
        @endif
      </ul>
    </div>
  </div>
  @endif

  {{-- Bemerkung-Card --}}
  @if($adresse->bemerkung)
  <div class="card shadow-sm mb-3">
    <div class="card-header bg-light py-2">
      <div class="d-flex align-items-center gap-2">
        <i class="bi bi-chat-text text-primary"></i>
        <span class="fw-semibold small">Bemerkung</span>
      </div>
    </div>
    <div class="card-body">
      <p class="mb-0">{{ $adresse->bemerkung }}</p>
    </div>
  </div>
  @endif

  {{-- Verknuepfte Gebaeude --}}
  @php
    $gebaeudeAlsPost = \App\Models\Gebaeude::where('postadresse_id', $adresse->id)->get();
    $gebaeudeAlsRe = \App\Models\Gebaeude::where('rechnungsempfaenger_id', $adresse->id)->get();
  @endphp

  @if($gebaeudeAlsPost->isNotEmpty() || $gebaeudeAlsRe->isNotEmpty())
  <div class="card shadow-sm mb-3">
    <div class="card-header bg-light py-2">
      <div class="d-flex align-items-center gap-2">
        <i class="bi bi-houses text-primary"></i>
        <span class="fw-semibold small">Verknuepfte Gebaeude</span>
      </div>
    </div>
    <div class="card-body p-0">
      <ul class="list-group list-group-flush">
        @foreach($gebaeudeAlsPost as $g)
        <li class="list-group-item">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <span class="badge bg-primary me-2">Postadresse</span>
              {{ $g->gebaeude_name ?: $g->strasse }}
            </div>
            <a href="{{ route('gebaeude.edit', $g->id) }}" class="btn btn-sm btn-outline-secondary">
              <i class="bi bi-arrow-right"></i>
            </a>
          </div>
        </li>
        @endforeach
        @foreach($gebaeudeAlsRe as $g)
        @if(!$gebaeudeAlsPost->contains('id', $g->id))
        <li class="list-group-item">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <span class="badge bg-warning text-dark me-2">Rechnungsempf.</span>
              {{ $g->gebaeude_name ?: $g->strasse }}
            </div>
            <a href="{{ route('gebaeude.edit', $g->id) }}" class="btn btn-sm btn-outline-secondary">
              <i class="bi bi-arrow-right"></i>
            </a>
          </div>
        </li>
        @endif
        @endforeach
      </ul>
    </div>
  </div>
  @endif

  {{-- Meta-Info --}}
  <div class="card bg-light">
    <div class="card-body py-2 px-3">
      <div class="row text-muted small">
        <div class="col-6">
          <i class="bi bi-calendar-plus me-1"></i>
          Erstellt: {{ $adresse->created_at?->format('d.m.Y H:i') ?? '-' }}
        </div>
        <div class="col-6 text-end">
          <i class="bi bi-calendar-check me-1"></i>
          Geaendert: {{ $adresse->updated_at?->format('d.m.Y H:i') ?? '-' }}
        </div>
      </div>
    </div>
  </div>

  {{-- Aktions-Footer (nur Desktop) --}}
  <div class="d-none d-md-flex gap-2 mt-4">
    <a href="{{ route('adresse.index') }}" class="btn btn-outline-secondary">
      <i class="bi bi-arrow-left"></i> Zur Liste
    </a>
    <a href="{{ route('adresse.edit', $adresse->id) }}" class="btn btn-primary">
      <i class="bi bi-pencil"></i> Bearbeiten
    </a>
    <form method="POST" action="{{ route('adresse.destroy', $adresse->id) }}" class="ms-auto"
          onsubmit="return confirm('Diese Adresse wirklich loeschen?')">
      @csrf
      @method('DELETE')
      <button type="submit" class="btn btn-outline-danger">
        <i class="bi bi-trash"></i> Loeschen
      </button>
    </form>
  </div>

</div>

@push('styles')
<style>
.min-w-0 { min-width: 0; }
</style>
@endpush
@endsection
