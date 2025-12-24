@extends('layouts.app')

@section('content')
<div class="container-fluid px-2 px-md-3">
  <div class="card shadow-sm border-0">

    {{-- Header - kompakter auf Mobile --}}
    <div class="card-header bg-white py-2 py-md-3">
      <h4 class="mb-0 fs-5 fs-md-4">
        <i class="bi bi-building"></i>
        <span class="d-none d-sm-inline">{{ isset($gebaeude) ? 'Gebaeude bearbeiten' : 'Neues Gebaeude anlegen' }}</span>
        <span class="d-sm-none">{{ isset($gebaeude) ? 'Bearbeiten' : 'Neu' }}</span>
        @if(isset($gebaeude))
        <small class="text-muted">#{{ $gebaeude->id }}</small>
        @endif
      </h4>
    </div>

    {{-- Meldungen - kompakter auf Mobile --}}
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show mt-2 mx-2 mx-md-3 py-2" role="alert">
      <i class="bi bi-check-circle-fill me-1"></i>
      <span class="small">{{ session('success') }}</span>
      <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(session('warning'))
    <div class="alert alert-warning alert-dismissible fade show mt-2 mx-2 mx-md-3 py-2" role="alert">
      <i class="bi bi-exclamation-triangle-fill me-1"></i>
      <span class="small">{{ session('warning') }}</span>
      <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show mt-2 mx-2 mx-md-3 py-2" role="alert">
      <i class="bi bi-x-circle-fill me-1"></i>
      <span class="small">{{ session('error') }}</span>
      <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(session('error_detail'))
    <div class="alert alert-warning alert-dismissible fade show mt-1 mx-2 mx-md-3 py-2" role="alert">
      <i class="bi bi-info-circle"></i>
      <span class="small">{{ session('error_detail') }}</span>
      <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
    </div>
    @endif

    {{-- Tabs-Navigation - SCROLLBAR auf Mobile --}}
    <div class="card-body border-bottom pb-0 pt-2 px-0 px-md-3">
      <div class="nav-tabs-wrapper">
        <ul class="nav nav-tabs flex-nowrap overflow-auto scrollbar-hide" id="gebaeudeTabs" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active px-2 px-md-3" id="tab-allgemein" data-bs-toggle="tab"
              data-bs-target="#content-allgemein" type="button" role="tab">
              <i class="bi bi-house"></i>
              <span class="d-none d-md-inline ms-1">Allgemein</span>
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link px-2 px-md-3" id="tab-fatturapa" data-bs-toggle="tab"
              data-bs-target="#content-fatturapa" type="button" role="tab">
              <i class="bi bi-file-earmark-text"></i>
              <span class="d-none d-md-inline ms-1">FatturaPA</span>
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link px-2 px-md-3" id="tab-adressen" data-bs-toggle="tab"
              data-bs-target="#content-adressen" type="button" role="tab">
              <i class="bi bi-person-lines-fill"></i>
              <span class="d-none d-md-inline ms-1">Adressen</span>
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link px-2 px-md-3" id="tab-einteilung" data-bs-toggle="tab"
              data-bs-target="#content-einteilung" type="button" role="tab">
              <i class="bi bi-calendar-week"></i>
              <span class="d-none d-md-inline ms-1">Einteilung</span>
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link px-2 px-md-3" id="tab-touren" data-bs-toggle="tab"
              data-bs-target="#content-touren" type="button" role="tab">
              <i class="bi bi-map"></i>
              <span class="d-none d-md-inline ms-1">Touren</span>
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link px-2 px-md-3" id="tab-timeline" data-bs-toggle="tab"
              data-bs-target="#content-timeline" type="button" role="tab">
              <i class="bi bi-clock-history"></i>
              <span class="d-none d-md-inline ms-1">Timeline</span>
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link px-2 px-md-3" id="tab-artikel" data-bs-toggle="tab"
              data-bs-target="#content-artikel" type="button" role="tab">
              <i class="bi bi-receipt"></i>
              <span class="d-none d-md-inline ms-1">Artikel</span>
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link px-2 px-md-3" id="tab-aufschlag" data-bs-toggle="tab"
              data-bs-target="#content-aufschlag" type="button" role="tab">
              <i class="bi bi-percent"></i>
              <span class="d-none d-md-inline ms-1">Aufschlag</span>
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link px-2 px-md-3" id="tab-rechnungen" data-bs-toggle="tab"
              data-bs-target="#content-rechnungen" type="button" role="tab">
              <i class="bi bi-receipt"></i>
              <span class="d-none d-md-inline ms-1">Rechnungen</span>
              @if(!empty($gebaeude->id))
              <span class="badge bg-success ms-1">{{ $gebaeude->rechnungen->count() }}</span>
              @endif
            </button>
          </li>
          {{-- ⭐ NEU: Protokoll-Tab --}}
          <li class="nav-item" role="presentation">
            <button class="nav-link px-2 px-md-3" id="tab-protokoll" data-bs-toggle="tab"
              data-bs-target="#content-protokoll" type="button" role="tab">
              <i class="bi bi-journal-text"></i>
              <span class="d-none d-md-inline ms-1">Protokoll</span>
              @if(!empty($gebaeude->id))
                @php
                  $offeneErinnerungen = $gebaeude->offeneErinnerungen()->count();
                  $offeneProbleme = $gebaeude->offeneProbleme()->count();
                @endphp
                @if($offeneErinnerungen > 0 || $offeneProbleme > 0)
                  <span class="badge bg-warning text-dark ms-1">
                    {{ $offeneErinnerungen + $offeneProbleme }}
                  </span>
                @endif
              @endif
            </button>
          </li>
        </ul>
      </div>
    </div>

    {{-- Formular --}}
    <form id="gebaeudeForm"
      method="POST"
      action="{{ !empty($gebaeude->id) ? route('gebaeude.update', $gebaeude->id) : route('gebaeude.store') }}">
      @csrf
      @if(!empty($gebaeude->id))
      @method('PUT')
      @endif

      @if(!empty($returnTo))
      <input type="hidden" name="returnTo" value="{{ $returnTo }}">
      @endif

      <div class="tab-content p-2 p-md-4">
        <div class="tab-pane fade show active" id="content-allgemein" role="tabpanel">
          @include('gebaeude.partials._allgemein')
        </div>
        <div class="tab-pane fade" id="content-fatturapa" role="tabpanel">
          @include('gebaeude.partials._fatturapa')
        </div>
        <div class="tab-pane fade" id="content-adressen" role="tabpanel">
          @include('gebaeude.partials._adressen')
        </div>
        <div class="tab-pane fade" id="content-einteilung" role="tabpanel">
          @include('gebaeude.partials._einteilung')
        </div>
        <div class="tab-pane fade" id="content-touren" role="tabpanel">
          @include('gebaeude.partials._touren')
        </div>
        <div class="tab-pane fade" id="content-timeline" role="tabpanel">
          @include('gebaeude.partials._timeline', ['gebaeude' => $gebaeude])
        </div>
        <div class="tab-pane fade" id="content-artikel" role="tabpanel">
          @include('gebaeude.partials._artikel', ['gebaeude' => $gebaeude])
        </div>
        <div class="tab-pane fade" id="content-aufschlag" role="tabpanel">
          @include('gebaeude.partials._aufschlag', ['gebaeude' => $gebaeude])
        </div>
        <div class="tab-pane fade" id="content-rechnungen" role="tabpanel">
          @include('gebaeude.partials._rechnungen', ['gebaeude' => $gebaeude])
        </div>
        {{-- ⭐ NEU: Protokoll Tab-Content --}}
        <div class="tab-pane fade" id="content-protokoll" role="tabpanel">
          @include('gebaeude.partials._log_timeline', ['gebaeude' => $gebaeude])
        </div>
      </div>

      {{-- Footer - Sticky auf Mobile --}}
      <div class="card-footer bg-white sticky-bottom-mobile py-2 py-md-3">
        <div class="d-flex flex-column flex-sm-row gap-2 justify-content-sm-end">
          <button type="submit" class="btn btn-primary order-1 order-sm-2">
            <i class="bi bi-save"></i>
            <span class="d-none d-sm-inline">{{ isset($gebaeude) ? 'Aenderungen speichern' : 'Gebaeude anlegen' }}</span>
            <span class="d-sm-none">Speichern</span>
          </button>

          @if(!empty($gebaeude?->id))
          <a href="{{ route('rechnung.create', ['gebaeude_id' => $gebaeude->id]) }}#content-vorschau"
            class="btn btn-success order-2 order-sm-3">
            <i class="bi bi-plus-circle"></i>
            <span class="d-none d-sm-inline">Neue Rechnung</span>
            <span class="d-sm-none">Rechnung</span>
          </a>
          @endif

          <a href="{{ route('gebaeude.index') }}" class="btn btn-outline-secondary order-3 order-sm-1">
            <i class="bi bi-arrow-left"></i>
            <span class="d-none d-sm-inline">Zurueck</span>
          </a>
        </div>
      </div>
    </form>
  </div>
</div>

{{-- Modals AUSSERHALB des Formulars --}}
@if(!empty($gebaeude->id))
  @include('gebaeude.partials._aufschlag_modals', ['gebaeude' => $gebaeude])
  {{-- ⭐ NEU: Log-Modals --}}
  @include('gebaeude.partials._log_modals', ['gebaeude' => $gebaeude])
@endif

@endsection

@push('styles')
<style>
/* Scrollbare Tabs auf Mobile */
.nav-tabs-wrapper {
  position: relative;
}

.scrollbar-hide {
  -ms-overflow-style: none;
  scrollbar-width: none;
}

.scrollbar-hide::-webkit-scrollbar {
  display: none;
}

/* Tab-Styling */
.nav-tabs .nav-link {
  white-space: nowrap;
  border-radius: 0;
  border-bottom: 2px solid transparent;
  color: #6c757d;
  font-size: 0.875rem;
}

.nav-tabs .nav-link.active {
  border-bottom-color: #0d6efd;
  color: #0d6efd;
  font-weight: 500;
}

.nav-tabs .nav-link:hover:not(.active) {
  border-bottom-color: #dee2e6;
}

/* Sticky Footer auf Mobile */
@media (max-width: 575.98px) {
  .sticky-bottom-mobile {
    position: sticky;
    bottom: 0;
    z-index: 100;
    box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
  }
  
  .sticky-bottom-mobile .btn {
    padding: 0.625rem 1rem;
    font-size: 0.9375rem;
  }
}

/* Touch-freundliche Inputs */
@media (max-width: 767.98px) {
  .form-control, .form-select {
    min-height: 44px;
    font-size: 16px !important; /* Verhindert Zoom auf iOS */
  }
  
  .btn {
    min-height: 44px;
  }
  
  .form-check-input {
    width: 1.25em;
    height: 1.25em;
  }
}

/* Kompaktere Alerts auf Mobile */
.btn-close-sm {
  padding: 0.5rem;
  font-size: 0.75rem;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Tabs speichern und wiederherstellen
  const tabs = document.querySelectorAll('#gebaeudeTabs button[data-bs-toggle="tab"]');
  tabs.forEach(tab => {
    tab.addEventListener('shown.bs.tab', function(event) {
      localStorage.setItem('activeGebaeudeTab', event.target.dataset.bsTarget);
      
      // Tab in Sichtbereich scrollen (Mobile)
      event.target.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
    });
  });

  const lastTab = localStorage.getItem('activeGebaeudeTab');
  if (lastTab) {
    const triggerEl = document.querySelector(`#gebaeudeTabs button[data-bs-target="${lastTab}"]`);
    if (triggerEl) {
      const tab = new bootstrap.Tab(triggerEl);
      tab.show();
    }
  }

  const form = document.getElementById('gebaeudeForm');
  if (form) {
    form.addEventListener('submit', function() {
      const active = document.querySelector('#gebaeudeTabs .nav-link.active');
      if (active) {
        localStorage.setItem('activeGebaeudeTab', active.dataset.bsTarget);
      }
    });
  }
});
</script>
@endpush
