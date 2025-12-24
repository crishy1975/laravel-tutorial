{{-- resources/views/adresse/index.blade.php --}}
{{-- MOBIL-OPTIMIERT: Modernes Card-Design, keine Checkboxen --}}
@extends('layouts.app')

@section('content')
<div class="container-fluid px-2 px-md-4 py-3">

  {{-- Header --}}
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h4 class="mb-0">
        <i class="bi bi-people-fill text-primary"></i>
        <span class="d-none d-sm-inline">Adressen / Indirizzi</span>
        <span class="d-sm-none">Adressen</span>
      </h4>
      <small class="text-muted">{{ $adressen->total() }} Eintraege</small>
    </div>
    <a href="{{ route('adresse.create') }}" class="btn btn-success">
      <i class="bi bi-plus-lg"></i>
      <span class="d-none d-sm-inline ms-1">Neue Adresse</span>
    </a>
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

  {{-- Filterleiste --}}
  <div class="card shadow-sm mb-3 border-0">
    <div class="card-body p-2 p-md-3">
      <form method="GET" action="{{ route('adresse.index') }}" class="row g-2 align-items-end">
        <div class="col">
          <div class="input-group">
            <span class="input-group-text bg-primary text-white border-0">
              <i class="bi bi-search"></i>
            </span>
            <input type="text" name="name" value="{{ $name ?? '' }}" 
                   class="form-control" 
                   placeholder="Name, Ort oder E-Mail suchen...">
          </div>
        </div>
        <div class="col-auto">
          <button class="btn btn-primary px-4" type="submit">
            <i class="bi bi-search d-sm-none"></i>
            <span class="d-none d-sm-inline">Suchen</span>
          </button>
        </div>
        @if(($name ?? '') !== '')
        <div class="col-auto">
          <a href="{{ route('adresse.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-x-lg"></i>
          </a>
        </div>
        @endif
      </form>
    </div>
  </div>

  @if($adressen->isEmpty())
    <div class="card border-0 shadow-sm">
      <div class="card-body text-center py-5">
        <i class="bi bi-inbox text-muted" style="font-size: 4rem;"></i>
        <h5 class="mt-3 text-muted">Keine Adressen gefunden</h5>
        <p class="text-muted mb-4">Erstellen Sie eine neue Adresse oder aendern Sie die Suche.</p>
        <a href="{{ route('adresse.create') }}" class="btn btn-success">
          <i class="bi bi-plus-lg me-1"></i> Neue Adresse erstellen
        </a>
      </div>
    </div>
  @else

  {{-- MOBILE: Card-Layout --}}
  <div class="d-md-none">
    @foreach($adressen as $adr)
    <div class="card mb-2 shadow-sm border-0 overflow-hidden">
      {{-- Farbiger Akzent-Streifen oben --}}
      <div class="bg-primary" style="height: 4px;"></div>
      <div class="card-body p-3">
        {{-- Header --}}
        <div class="d-flex justify-content-between align-items-start mb-2">
          <div class="flex-grow-1 min-w-0">
            <h6 class="mb-0 fw-bold text-truncate">
              <i class="bi bi-person-fill text-primary me-1"></i>
              {{ $adr->name }}
            </h6>
            @if($adr->firma && $adr->firma !== $adr->name)
              <small class="text-muted">{{ $adr->firma }}</small>
            @endif
          </div>
          @if($adr->mwst_nummer)
            <span class="badge bg-success ms-2 flex-shrink-0">
              <i class="bi bi-building"></i> MwSt
            </span>
          @endif
        </div>
        
        {{-- Details --}}
        <div class="small mb-2">
          @if($adr->strasse || $adr->wohnort)
          <div class="d-flex align-items-start text-muted mb-1">
            <i class="bi bi-geo-alt-fill text-success me-2 mt-1"></i>
            <span>
              @if($adr->strasse){{ $adr->strasse }} {{ $adr->hausnummer }}<br>@endif
              {{ $adr->plz }} {{ $adr->wohnort }}
              @if($adr->provinz) <span class="badge bg-light text-dark">{{ $adr->provinz }}</span> @endif
            </span>
          </div>
          @endif
          
          @if($adr->telefon || $adr->handy)
          <div class="d-flex align-items-center text-muted mb-1">
            <i class="bi bi-telephone-fill text-info me-2"></i>
            <a href="tel:{{ $adr->telefon ?: $adr->handy }}" class="text-decoration-none text-dark">
              {{ $adr->telefon ?: $adr->handy }}
            </a>
          </div>
          @endif
          
          @if($adr->email)
          <div class="d-flex align-items-center text-muted">
            <i class="bi bi-envelope-fill text-warning me-2"></i>
            <a href="mailto:{{ $adr->email }}" class="text-decoration-none text-dark text-truncate">
              {{ $adr->email }}
            </a>
          </div>
          @endif
        </div>
        
        {{-- Aktionen --}}
        <div class="d-flex gap-2 pt-2 border-top">
          <a href="{{ route('adresse.show', ['id'=>$adr->id]) }}" 
             class="btn btn-sm btn-outline-secondary flex-fill">
            <i class="bi bi-eye"></i> Details
          </a>
          <a href="{{ route('adresse.edit', ['id'=>$adr->id]) }}" 
             class="btn btn-sm btn-primary flex-fill">
            <i class="bi bi-pencil"></i> Bearbeiten
          </a>
        </div>
      </div>
    </div>
    @endforeach

    {{-- Mobile Pagination --}}
    @if($adressen->hasPages())
    <div class="mt-3">
      {{ $adressen->appends(['name' => $name])->links() }}
    </div>
    @endif
  </div>

  {{-- DESKTOP: Tabelle --}}
  <div class="d-none d-md-block">
    <div class="card shadow-sm border-0 overflow-hidden">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead>
            <tr class="bg-primary text-white">
              <th class="border-0 py-3">Name</th>
              <th class="border-0 py-3">Adresse</th>
              <th class="border-0 py-3">Kontakt</th>
              <th class="border-0 py-3">Steuerdaten</th>
              <th class="border-0 py-3 text-end" style="width:120px;">Aktionen</th>
            </tr>
          </thead>
          <tbody>
            @foreach($adressen as $adr)
            <tr>
              <td>
                <div class="d-flex align-items-center">
                  <div class="bg-primary bg-opacity-10 rounded-circle p-2 me-3">
                    <i class="bi bi-person-fill text-primary"></i>
                  </div>
                  <div>
                    <div class="fw-semibold">{{ $adr->name }}</div>
                    @if($adr->firma && $adr->firma !== $adr->name)
                      <small class="text-muted">{{ Str::limit($adr->firma, 30) }}</small>
                    @endif
                  </div>
                </div>
              </td>
              <td>
                <div class="small">
                  @if($adr->strasse)
                    <i class="bi bi-geo-alt text-success me-1"></i>
                    {{ $adr->strasse }} {{ $adr->hausnummer }}<br>
                  @endif
                  <span class="text-muted ps-3">{{ $adr->plz }}</span> {{ $adr->wohnort }}
                  @if($adr->provinz) 
                    <span class="badge bg-light text-dark ms-1">{{ $adr->provinz }}</span> 
                  @endif
                </div>
              </td>
              <td>
                @if($adr->telefon)
                  <div class="small mb-1">
                    <i class="bi bi-telephone text-info me-1"></i>
                    <a href="tel:{{ $adr->telefon }}" class="text-decoration-none">{{ $adr->telefon }}</a>
                  </div>
                @endif
                @if($adr->email)
                  <div class="small text-truncate" style="max-width: 200px;">
                    <i class="bi bi-envelope text-warning me-1"></i>
                    <a href="mailto:{{ $adr->email }}" class="text-decoration-none">{{ $adr->email }}</a>
                  </div>
                @endif
                @if(!$adr->telefon && !$adr->email)
                  <span class="text-muted small">-</span>
                @endif
              </td>
              <td>
                @if($adr->mwst_nummer)
                  <div class="mb-1">
                    <span class="badge bg-success">
                      <i class="bi bi-building me-1"></i>P.IVA
                    </span>
                  </div>
                  <small class="text-muted font-monospace">{{ $adr->mwst_nummer }}</small>
                @elseif($adr->steuernummer)
                  <div class="mb-1">
                    <span class="badge bg-secondary">
                      <i class="bi bi-person me-1"></i>CF
                    </span>
                  </div>
                  <small class="text-muted font-monospace">{{ Str::limit($adr->steuernummer, 16) }}</small>
                @else
                  <span class="text-muted">-</span>
                @endif
              </td>
              <td class="text-end">
                <div class="btn-group btn-group-sm">
                  <a href="{{ route('adresse.show', ['id'=>$adr->id]) }}" 
                     class="btn btn-outline-secondary" title="Details">
                    <i class="bi bi-eye"></i>
                  </a>
                  <a href="{{ route('adresse.edit', ['id'=>$adr->id]) }}" 
                     class="btn btn-primary" title="Bearbeiten">
                    <i class="bi bi-pencil"></i>
                  </a>
                </div>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      {{-- Pagination --}}
      @if($adressen->hasPages())
      <div class="card-footer bg-light border-0 py-3">
        <div class="d-flex justify-content-between align-items-center">
          <small class="text-muted">
            Zeige {{ $adressen->firstItem() }}-{{ $adressen->lastItem() }} von {{ $adressen->total() }}
          </small>
          {{ $adressen->appends(['name' => $name])->links() }}
        </div>
      </div>
      @endif
    </div>
  </div>

  @endif
</div>

@push('styles')
<style>
/* Hover-Effekt auf Tabellenzeilen */
.table-hover tbody tr {
  transition: background-color 0.15s ease;
}
.table-hover tbody tr:hover {
  background-color: rgba(13, 110, 253, 0.05) !important;
}

/* Mobile Optimierungen */
@media (max-width: 767.98px) {
  .form-control, .form-select, .btn { min-height: 44px; font-size: 16px !important; }
}

.min-w-0 { min-width: 0; }

/* Card Animationen */
.card {
  transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.d-md-none .card:active {
  transform: scale(0.98);
}

/* Badge Styling */
.badge {
  font-weight: 500;
}

/* Monospace fuer Steuernummern */
.font-monospace {
  font-family: SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  font-size: 0.85em;
}

/* Tabellen-Header */
.table thead th {
  font-weight: 600;
  letter-spacing: 0.025em;
}

/* Leere-Zustand Icon */
.bi-inbox {
  opacity: 0.3;
}
</style>
@endpush
@endsection
