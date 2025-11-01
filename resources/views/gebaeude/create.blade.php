{{-- resources/views/gebaeude/create.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container-fluid">
  <div class="card shadow-sm border-0">

    {{-- 🔹 Header --}}
    <div class="card-header bg-white">
      <h4 class="mb-0">
        <i class="bi bi-building"></i> Gebäude anlegen
      </h4>
    </div>

    {{-- 🔹 Tabs-Navigation --}}
    <div class="card-body border-bottom pb-0">
      <ul class="nav nav-tabs" id="gebaeudeTabs" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active" id="tab-allgemein" data-bs-toggle="tab"
                  data-bs-target="#content-allgemein" type="button" role="tab">
            <i class="bi bi-house"></i> Allgemein
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="tab-adressen" data-bs-toggle="tab"
                  data-bs-target="#content-adressen" type="button" role="tab">
            <i class="bi bi-person-lines-fill"></i> Adressen
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="tab-monate" data-bs-toggle="tab"
                  data-bs-target="#content-monate" type="button" role="tab">
            <i class="bi bi-calendar2-month"></i> Monate
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="tab-timeline" data-bs-toggle="tab"
                  data-bs-target="#content-timeline" type="button" role="tab">
            <i class="bi bi-clock-history"></i> Timeline
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="tab-einteilung" data-bs-toggle="tab"
                  data-bs-target="#content-einteilung" type="button" role="tab">
            <i class="bi bi-diagram-3"></i> Einteilung
          </button>
        </li>
      </ul>
    </div>

    {{-- 🔹 EIN einziges Formular um ALLE Tabs --}}
    <form id="gebaeudeForm" method="POST" action="{{ route('gebaeude.store') }}">
      @csrf

      <div class="tab-content p-4">
        <div class="tab-pane fade show active" id="content-allgemein" role="tabpanel">
          @include('gebaeude.partials._allgemein', ['gebaeude' => $gebaeude])
        </div>
        <div class="tab-pane fade" id="content-adressen" role="tabpanel">
          @include('gebaeude.partials._adressen', ['gebaeude' => $gebaeude])
        </div>
        <div class="tab-pane fade" id="content-monate" role="tabpanel">
          @include('gebaeude.partials._adressen', ['gebaeude' => $gebaeude]) {{-- Platzhalter --}}
        </div>
        <div class="tab-pane fade" id="content-timeline" role="tabpanel">
          @include('gebaeude.partials._adressen', ['gebaeude' => $gebaeude]) {{-- Platzhalter --}}
        </div>
        <div class="tab-pane fade" id="content-einteilung" role="tabpanel">
          @include('gebaeude.partials._adressen', ['gebaeude' => $gebaeude]) {{-- Platzhalter --}}
        </div>
      </div>

      {{-- 🔹 Footer --}}
      <div class="card-footer bg-white text-end">
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-plus-circle"></i> Anlegen
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
document.addEventListener('DOMContentLoaded', function () {
  const tabs = document.querySelectorAll('#gebaeudeTabs button[data-bs-toggle="tab"]');
  tabs.forEach(tab => {
    tab.addEventListener('shown.bs.tab', function (event) {
      localStorage.setItem('activeGebaeudeTab', event.target.dataset.bsTarget);
    });
  });
  const lastTab = localStorage.getItem('activeGebaeudeTab');
  if (lastTab) {
    const triggerEl = document.querySelector(`#gebaeudeTabs button[data-bs-target="${lastTab}"]`);
    if (triggerEl) new bootstrap.Tab(triggerEl).show();
  }
  const form = document.getElementById('gebaeudeForm');
  if (form) {
    form.addEventListener('submit', function () {
      const active = document.querySelector('#gebaeudeTabs .nav-link.active');
      if (active) localStorage.setItem('activeGebaeudeTab', active.dataset.bsTarget);
    });
  }
});
</script>
<script>
  /**
   * Initialisiert alle Select2-Felder mit Bootstrap-5-Theme
   * und unterstützt Tabs & Modals.
   */
  function initSelect2(scope) {
    const $root = scope ? $(scope) : $(document);

    $root.find('select.js-select2').each(function () {
      const $el = $(this);

      // Falls bereits initialisiert → zerstören, um doppelte Instanzen zu vermeiden
      if ($el.hasClass('select2-hidden-accessible')) {
        $el.select2('destroy');
      }

      // Falls in einem Modal → dieses als Dropdown-Parent verwenden
      const $modalParent = $el.closest('.modal');
      const dropdownParent = $modalParent.length ? $modalParent : $(document.body);

      // Select2 initialisieren
      $el.select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: $el.data('placeholder') || 'Bitte wählen…',
        allowClear: true,
        dropdownParent: dropdownParent,
        language: {
          noResults: function () { return 'Keine Treffer gefunden'; },
          searching: function () { return 'Suche…'; }
        }
      });
    });
  }

  // DOM geladen → Select2 starten
  document.addEventListener('DOMContentLoaded', function () {
    initSelect2();
  });

  // Tabs oder Modals → neu initialisieren
  document.addEventListener('shown.bs.modal', e => initSelect2(e.target));
  document.addEventListener('shown.bs.tab', e => initSelect2(document));
</script>
@endpush
