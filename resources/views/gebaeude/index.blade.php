{{-- resources/views/gebaeude/index.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container py-4">

  {{-- Kopfzeile + Aktionen --}}
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0"><i class="bi bi-building"></i> Gebäude</h3>
    <div class="d-flex gap-2">
      {{-- 🔗 Modal-Button: Öffnet kleines Modal zum Verknüpfen --}}
      <button type="button" class="btn btn-success" id="open-bulk-modal">
        <i class="bi bi-link-45deg"></i> Mit Tour verknüpfen
      </button>

      <a href="{{ route('gebaeude.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Neu
      </a>
    </div>
  </div>

  {{-- Flash-Meldungen --}}
  @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
  @if(session('error'))   <div class="alert alert-danger">{{ session('error') }}</div> @endif

  {{-- Filter --}}
  <form method="GET" action="{{ route('gebaeude.index') }}" class="card card-body mb-3">
    <div class="row g-2 align-items-end">
      <div class="col-md-2">
        <label class="form-label mb-1">Codex</label>
        <input type="text" name="codex" class="form-control" value="{{ $codex ?? '' }}">
      </div>
      <div class="col-md-3">
        <label class="form-label mb-1">Gebäudename</label>
        <input type="text" name="gebaeude_name" class="form-control" value="{{ $gebaeude_name ?? '' }}">
      </div>
      <div class="col-md-3">
        <label class="form-label mb-1">Straße</label>
        <input type="text" name="strasse" class="form-control" value="{{ $strasse ?? '' }}">
      </div>
      <div class="col-md-2">
        <label class="form-label mb-1">Hausnummer</label>
        <input type="text" name="hausnummer" class="form-control" value="{{ $hausnummer ?? '' }}">
      </div>
      <div class="col-md-2">
        <button class="btn btn-outline-secondary w-100" type="submit">
          <i class="bi bi-search"></i> Suchen
        </button>
      </div>

      @if(($codex ?? '')!=='' || ($gebaeude_name ?? '')!=='' || ($strasse ?? '')!=='' || ($hausnummer ?? '')!=='' || ($wohnort ?? '')!=='')
      <div class="col-md-2">
        <a href="{{ route('gebaeude.index') }}" class="btn btn-outline-dark w-100">
          <i class="bi bi-x-circle"></i> Reset
        </a>
      </div>
      @endif
    </div>
  </form>

  @if($gebaeude->isEmpty())
    <div class="alert alert-info">Keine Gebäude gefunden.</div>
  @else
  {{-- Tabelle (KEIN Formular drumherum) --}}
  <div class="card">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th style="width:48px;">
              <input type="checkbox" id="check-all">
            </th>
            <th>Codex</th>
            <th>Gebäudename</th>
            <th>Straße</th>
            <th>Nr.</th>
            <th>Wohnort</th>
            <th class="text-end">Aktionen</th>
          </tr>
        </thead>
        <tbody>
          @foreach($gebaeude as $g)
          <tr>
            {{-- ✅ Checkbox nur markieren; Verknüpfung passiert über Modal-Form --}}
            <td>
              <input type="checkbox" class="row-check" value="{{ $g->id }}">
            </td>
            <td>{{ $g->codex }}</td>
            <td>{{ $g->gebaeude_name }}</td>
            <td>{{ $g->strasse }}</td>
            <td>{{ $g->hausnummer }}</td>
            <td>{{ $g->wohnort }}</td>
            <td class="text-end">
              <a href="{{ route('gebaeude.edit', ['id' => $g->id]) }}"
                 class="btn btn-sm btn-outline-primary" title="Bearbeiten" aria-label="Bearbeiten">
                <i class="bi bi-pencil"></i>
              </a>

              {{-- 🗑️ Löschen: eigenes Form (unabhängig) --}}
              <form action="{{ route('gebaeude.destroy', $g->id) }}"
                    method="POST"
                    class="d-inline"
                    onsubmit="return confirm('Eintrag wirklich löschen?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger" title="Löschen" aria-label="Löschen">
                  <i class="bi bi-trash"></i>
                </button>
              </form>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    <div class="card-footer d-flex justify-content-end">
      {{ $gebaeude->appends(compact('codex','gebaeude_name','strasse','hausnummer','wohnort'))->links() }}
    </div>
  </div>
  @endif

</div>

{{-- 🔹 Kleines Modal für die Verknüpfung (OHNE Reihenfolge) --}}
<div class="modal fade" id="bulkAttachModal" tabindex="-1" aria-labelledby="bulkAttachLabel" aria-hidden="true">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <form id="bulk-modal-form" method="POST" action="{{ route('gebaeude.touren.bulkAttach') }}">
        @csrf
        <div class="modal-header py-2">
          <h6 class="modal-title" id="bulkAttachLabel"><i class="bi bi-link-45deg"></i> Mit Tour verknüpfen</h6>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
        </div>
        <div class="modal-body">
          {{-- Tour-Auswahl --}}
          <div class="mb-2">
            <label class="form-label small">Tour</label>
            <select class="form-select form-select-sm js-select2" name="tour_id" data-placeholder="Tour wählen …" required>
              <option></option>
              @foreach(\App\Models\Tour::orderBy('aktiv','desc')->orderBy('name')->get(['id','name','aktiv']) as $t)
                <option value="{{ $t->id }}">{{ $t->name }} @if(!$t->aktiv) (inaktiv) @endif</option>
              @endforeach
            </select>
          </div>

          {{-- Hidden-Felder: Rücksprung & Filter --}}
          <input type="hidden" name="returnTo" value="{{ url()->full() }}">
          <input type="hidden" name="codex" value="{{ $codex ?? '' }}">
          <input type="hidden" name="gebaeude_name" value="{{ $gebaeude_name ?? '' }}">
          <input type="hidden" name="strasse" value="{{ $strasse ?? '' }}">
          <input type="hidden" name="hausnummer" value="{{ $hausnummer ?? '' }}">
          <input type="hidden" name="wohnort" value="{{ $wohnort ?? '' }}">

          {{-- Dynamisch eingefügte ids[] kommen hier rein --}}
          <div id="selected-ids-container"></div>
        </div>
        <div class="modal-footer py-2">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">
            <i class="bi bi-x-lg"></i> Abbrechen
          </button>
          <button type="submit" class="btn btn-success btn-sm" id="bulk-modal-submit">
            <i class="bi bi-check-lg"></i> Verknüpfen
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    // ✅ Master-Checkbox
    const master = document.getElementById('check-all');
    const allChecks = () => Array.from(document.querySelectorAll('.row-check'));
    if (master) {
      master.addEventListener('change', () => {
        allChecks().forEach(ch => ch.checked = master.checked);
      });
    }

    // ✅ Modal-Button
    const btnOpen  = document.getElementById('open-bulk-modal');
    const modalEl  = document.getElementById('bulkAttachModal');
    const bsModal  = modalEl ? new bootstrap.Modal(modalEl) : null;

    // ✅ Beim Klick prüfen, ob Selektion vorhanden; dann Modal öffnen + IDs injizieren
    if (btnOpen && bsModal) {
      btnOpen.addEventListener('click', () => {
        const selected = allChecks().filter(ch => ch.checked).map(ch => parseInt(ch.value, 10));
        if (selected.length === 0) {
          alert('Bitte mindestens ein Gebäude auswählen.');
          return;
        }

        // Hidden-IDs in Modal-Form einfügen
        const container = document.getElementById('selected-ids-container');
        container.innerHTML = '';
        selected.forEach(id => {
          const input = document.createElement('input');
          input.type  = 'hidden';
          input.name  = 'ids[]';
          input.value = id;
          container.appendChild(input);
        });

        // Select2 im Modal initialisieren: dein Layout ruft initSelect2 auf shown.bs.modal
        bsModal.show();
      });
    }

    // ✅ Safety: entferne ggf. _method im Modal-Form (falls mal ein Partial Unsinn injiziert)
    const modalForm = document.getElementById('bulk-modal-form');
    if (modalForm) {
      modalForm.addEventListener('submit', function (e) {
        // Keine Method-Spoofs zulassen
        modalForm.querySelectorAll('input[name="_method"]').forEach(el => el.remove());

        // Simple Validierung
        const anyIds = modalForm.querySelectorAll('input[name="ids[]"]').length > 0;
        const tourSel = modalForm.querySelector('select[name="tour_id"]');
        if (!anyIds) {
          e.preventDefault();
          alert('Es wurden keine Gebäude ausgewählt.');
          return false;
        }
        if (!tourSel || !tourSel.value) {
          e.preventDefault();
          alert('Bitte eine Tour auswählen.');
          return false;
        }
      });
    }

    // ✅ Modal resetten beim Schließen
    if (modalEl) {
      modalEl.addEventListener('hidden.bs.modal', () => {
        const container = document.getElementById('selected-ids-container');
        if (container) container.innerHTML = '';
        const form = document.getElementById('bulk-modal-form');
        if (form) form.reset();
      });
    }
  });
</script>
@endpush
