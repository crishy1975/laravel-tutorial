{{-- resources/views/adresse/index.blade.php --}}
{{-- MOBIL-OPTIMIERT: Cards auf Mobile, Tabelle auf Desktop --}}
@extends('layouts.app')

@section('content')
<div class="container-fluid px-2 px-md-4 py-3">

  {{-- Header --}}
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">
      <i class="bi bi-people text-primary"></i>
      <span class="d-none d-sm-inline">Adressen</span>
    </h4>
    <a href="{{ route('adresse.create') }}" class="btn btn-primary">
      <i class="bi bi-plus-lg"></i>
      <span class="d-none d-sm-inline ms-1">Neu</span>
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
  <form method="GET" action="{{ route('adresse.index') }}" class="card mb-3">
    <div class="card-body p-2 p-md-3">
      <div class="row g-2 align-items-end">
        <div class="col">
          <div class="input-group">
            <span class="input-group-text bg-light border-end-0">
              <i class="bi bi-search"></i>
            </span>
            <input type="text" name="name" value="{{ $name ?? '' }}" 
                   class="form-control border-start-0" 
                   placeholder="Name suchen...">
          </div>
        </div>
        <div class="col-auto">
          <button class="btn btn-primary" type="submit">
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
      </div>
    </div>
  </form>

  @if($adressen->isEmpty())
    <div class="alert alert-info">
      <i class="bi bi-info-circle me-1"></i>Keine Adressen gefunden.
    </div>
  @else

  {{-- Bulk-Delete Form --}}
  <form id="bulkForm" method="POST" action="{{ route('adresse.bulkDestroy') }}">
    @csrf

    {{-- MOBILE: Card-Layout --}}
    <div class="d-md-none">
      @foreach($adressen as $adr)
      <div class="card mb-2 shadow-sm">
        <div class="card-body p-3">
          <div class="d-flex align-items-start">
            <div class="form-check me-2 mt-1">
              <input type="checkbox" name="ids[]" value="{{ $adr->id }}" class="form-check-input row-check">
            </div>
            <div class="flex-grow-1 min-w-0">
              <div class="d-flex justify-content-between align-items-start">
                <div class="min-w-0">
                  <h6 class="mb-1 text-truncate fw-semibold">{{ $adr->name }}</h6>
                  <div class="small text-muted">
                    <i class="bi bi-geo-alt me-1"></i>{{ $adr->plz }} {{ $adr->wohnort }}
                    @if($adr->provinz) ({{ $adr->provinz }}) @endif
                  </div>
                  @if($adr->telefon)
                  <div class="small text-muted">
                    <i class="bi bi-telephone me-1"></i>{{ $adr->telefon }}
                  </div>
                  @endif
                  @if($adr->email)
                  <div class="small text-muted text-truncate">
                    <i class="bi bi-envelope me-1"></i>{{ $adr->email }}
                  </div>
                  @endif
                </div>
              </div>
            </div>
          </div>
          
          {{-- Aktionen --}}
          <div class="d-flex gap-2 mt-2 pt-2 border-top">
            <a href="{{ route('adresse.show', ['id'=>$adr->id]) }}" class="btn btn-sm btn-outline-secondary flex-fill">
              <i class="bi bi-eye"></i> Details
            </a>
            <a href="{{ route('adresse.edit', ['id'=>$adr->id]) }}" class="btn btn-sm btn-outline-primary flex-fill">
              <i class="bi bi-pencil"></i> Bearbeiten
            </a>
            <button type="submit" form="del-{{ $adr->id }}" 
                    class="btn btn-sm btn-outline-danger"
                    onclick="return confirm('Eintrag loeschen?')">
              <i class="bi bi-trash"></i>
            </button>
          </div>
        </div>
      </div>
      @endforeach
    </div>

    {{-- DESKTOP: Tabelle --}}
    <div class="d-none d-md-block">
      <div class="card shadow-sm">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th style="width:40px;">
                  <input type="checkbox" id="checkAll" class="form-check-input">
                </th>
                <th>Name</th>
                <th>Ort</th>
                <th>Telefon</th>
                <th>E-Mail</th>
                <th class="text-end" style="width:140px;">Aktionen</th>
              </tr>
            </thead>
            <tbody>
              @foreach($adressen as $adr)
              <tr>
                <td>
                  <input type="checkbox" name="ids[]" value="{{ $adr->id }}" class="form-check-input row-check">
                </td>
                <td>
                  <div class="fw-semibold">{{ $adr->name }}</div>
                </td>
                <td>
                  <span class="text-muted">{{ $adr->plz }}</span> {{ $adr->wohnort }}
                  @if($adr->provinz) <span class="text-muted">({{ $adr->provinz }})</span> @endif
                </td>
                <td>{{ $adr->telefon }}</td>
                <td>
                  @if($adr->email)
                  <a href="mailto:{{ $adr->email }}" class="text-decoration-none">{{ $adr->email }}</a>
                  @endif
                </td>
                <td class="text-end">
                  <div class="btn-group btn-group-sm">
                    <a href="{{ route('adresse.show', ['id'=>$adr->id]) }}" class="btn btn-outline-secondary" title="Details">
                      <i class="bi bi-eye"></i>
                    </a>
                    <a href="{{ route('adresse.edit', ['id'=>$adr->id]) }}" class="btn btn-outline-primary" title="Bearbeiten">
                      <i class="bi bi-pencil"></i>
                    </a>
                    <button type="submit" form="del-{{ $adr->id }}" 
                            class="btn btn-outline-danger" title="Loeschen"
                            onclick="return confirm('Eintrag loeschen?')">
                      <i class="bi bi-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>

    {{-- Footer: Bulk-Delete & Pagination --}}
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2 mt-3">
      <button type="submit" class="btn btn-outline-danger btn-sm"
              onclick="return confirm('Markierte Adressen wirklich loeschen?')">
        <i class="bi bi-trash"></i> Markierte loeschen
      </button>
      <div>
        {{ $adressen->appends(['name' => $name])->links() }}
      </div>
    </div>
  </form>

  {{-- Externe Formulare fuer Einzel-Loeschen --}}
  @foreach($adressen as $adr)
  <form id="del-{{ $adr->id }}" method="POST" action="{{ route('adresse.destroy', ['id'=>$adr->id]) }}" class="d-none">
    @csrf
    @method('DELETE')
  </form>
  @endforeach

  @endif
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
  var checkAll = document.getElementById('checkAll');
  var boxes = document.querySelectorAll('.row-check');
  
  if (checkAll) {
    checkAll.addEventListener('change', function() {
      boxes.forEach(function(b) { b.checked = checkAll.checked; });
    });
  }
});
</script>
@endpush

@push('styles')
<style>
@media (max-width: 767.98px) {
  .form-control, .form-select, .btn { min-height: 44px; }
  .form-check-input { width: 1.25em; height: 1.25em; }
}
.min-w-0 { min-width: 0; }
</style>
@endpush
@endsection
