@extends('layouts.app')

@section('content')
<div class="container-fluid">
  <div class="card shadow-sm border-0">

    {{-- 🔹 Header --}}
    <div class="card-header bg-white">
      <h4 class="mb-0">
        <i class="bi bi-building"></i>
        {{ isset($gebaeude) ? 'Gebäude bearbeiten' : 'Neues Gebäude anlegen' }}
        @if(isset($gebaeude))
        <small class="text-muted">#{{ $gebaeude->id }}</small>
        @endif
      </h4>
    </div>

    {{-- ✅ Erfolgsmeldung anzeigen --}}
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
      <i class="bi bi-check-circle"></i>
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @if(session('error_detail'))
    <div class="alert alert-warning alert-dismissible fade show mt-2" role="alert">
      <i class="bi bi-info-circle"></i>
      {{ session('error_detail') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif


    {{-- 🔹 Tabs-Navigation --}}
    <div class="card-body border-bottom pb-0">
      <ul class="nav nav-tabs" id="gebaeudeTabs" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active" id="tab-allgemein" data-bs-toggle="tab"
            data-bs-target="#content-allgemein" type="button" role="tab">
            <i class="bi bi-house"></i> Allgemein
          </button>
        </li>
        {{-- 🔹 Tabs-Header: neuen Tab „FatturaPA“ ergänzen --}}
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="tab-fatturapa" data-bs-toggle="tab"
            data-bs-target="#content-fatturapa" type="button" role="tab"
            aria-controls="content-fatturapa" aria-selected="false">
            <i class="bi bi-file-earmark-text"></i> FatturaPA
          </button>
        </li>

        <li class="nav-item" role="presentation">
          <button class="nav-link" id="tab-adressen" data-bs-toggle="tab"
            data-bs-target="#content-adressen" type="button" role="tab">
            <i class="bi bi-person-lines-fill"></i> Adressen
          </button>
        </li>
        {{-- Einteilung --}}
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="tab-einteilung" data-bs-toggle="tab"
            data-bs-target="#content-einteilung" type="button" role="tab">
            <i class="bi bi-calendar-week"></i> Einteilung
          </button>
        </li>
        {{-- Touren --}}
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="tab-touren" data-bs-toggle="tab"
            data-bs-target="#content-touren" type="button" role="tab">
            <i class="bi bi-map"></i> Touren
          </button>
        </li>
        {{-- Timeline --}}
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="tab-timeline" data-bs-toggle="tab"
            data-bs-target="#content-timeline" type="button" role="tab" aria-controls="content-timeline" aria-selected="false">
            <i class="bi bi-clock-history"></i> Timeline
          </button>
        </li>
        {{-- 🔹 Tabs-Navigation: neuer Tab "Artikel" mit Icon --}}
        <li class="nav-item" role="presentation">
          <button
            class="nav-link" {{-- "active" hinzufügen, falls dieser Tab standardmäßig aktiv sein soll --}}
            id="tab-artikel"
            data-bs-toggle="tab"
            data-bs-target="#content-artikel"
            type="button"
            role="tab"
            aria-controls="content-artikel"
            aria-selected="false">
            <i class="bi bi-receipt"></i> Artikel
          </button>
        </li>

      </ul>
    </div>

    {{-- 🔹 EIN einziges Formular um ALLE Tabs --}}

    <form id="gebaeudeForm"
      method="POST"
      action="{{ !empty($gebaeude->id) ? route('gebaeude.update', $gebaeude->id) : route('gebaeude.store') }}">
      @csrf
      @if(!empty($gebaeude->id))
      @method('PUT')
      @endif

      {{-- 🔹 Hier: Rücksprungziel mitgeben --}}
      @if(!empty($returnTo))
      <input type="hidden" name="returnTo" value="{{ $returnTo }}">
      @endif

      <div class="tab-content p-4">
        <div class="tab-pane fade show active" id="content-allgemein" role="tabpanel">
          @include('gebaeude.partials._allgemein')
        </div>
        {{-- 🔹 Tabs-Content: Pane für „FatturaPA“ --}}
        <div class="tab-pane fade" id="content-fatturapa" role="tabpanel" aria-labelledby="tab-fatturapa">
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
        <div class="tab-pane fade" id="content-timeline" role="tabpanel" aria-labelledby="tab-timeline">
          {{-- Inhalt der Timeline, z. B.: --}}

          @include('gebaeude.partials._timeline', ['gebaeude' => $gebaeude])
        </div>
        {{-- 🔹 Inhalt der Tabs: Pane für "Artikel"
     Hinweis: Kein verschachteltes <form> – das Partial arbeitet per fetch/AJAX. --}}
        <div
          class="tab-pane fade" {{-- "show active" ergänzen, wenn dieser Tab aktiv sein soll --}}
          id="content-artikel"
          role="tabpanel"
          aria-labelledby="tab-artikel">

          {{-- Artikel-Partial einbinden (editierbare Tabelle mit Add/Update/Delete Icons) --}}
          @include('gebaeude.partials._artikel', ['gebaeude' => $gebaeude])
        </div>
      </div>

      {{-- 🔹 Footer --}}
      <div class="card-footer bg-white text-end">
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-save"></i>
          {{ isset($gebaeude) ? 'Änderungen speichern' : 'Gebäude anlegen' }}
        </button>

        <a href="{{ route('gebaeude.index') }}" class="btn btn-outline-secondary">
          <i class="bi bi-arrow-left"></i> Zurück
        </a>
      </div>
    </form>
  </div>
</div>

@endsection

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function() {

    // 🔸 Tabs beobachten und speichern
    const tabs = document.querySelectorAll('#gebaeudeTabs button[data-bs-toggle="tab"]');
    tabs.forEach(tab => {
      tab.addEventListener('shown.bs.tab', function(event) {
        localStorage.setItem('activeGebaeudeTab', event.target.dataset.bsTarget);
      });
    });

    // 🔸 Beim Laden letzten Tab wieder aktivieren
    const lastTab = localStorage.getItem('activeGebaeudeTab');
    if (lastTab) {
      const triggerEl = document.querySelector(`#gebaeudeTabs button[data-bs-target="${lastTab}"]`);
      if (triggerEl) {
        const tab = new bootstrap.Tab(triggerEl);
        tab.show();
      }
    }

    // 🔸 Beim Absenden Tab speichern (zur Sicherheit)
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