{{-- resources/views/tour/show.blade.php --}}
{{-- Detailansicht einer Tour mit Checkboxen und Löschen-Buttons,
     wobei NUR die Pivot-Verknüpfung (tourgebaeude) gelöscht wird.
     Vollständige Datei. Keine verschachtelten <form>-Tags. --}}

@extends('layouts.app')

@section('content')
<div class="container py-4">

  {{-- Kopfzeile + Zurück --}}
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <div>
      <h3 class="mb-1">
        <i class="bi bi-map"></i>
        Tour: {{ $tour->name }}
        @if(!$tour->aktiv)
          <span class="badge bg-secondary align-middle">inaktiv</span>
        @endif
      </h3>
      <div class="text-muted small">
        ID: {{ $tour->id }}
        @if(!is_null($tour->reihenfolge))
          · Reihenfolge: {{ $tour->reihenfolge }}
        @endif
        · Angelegt: {{ $tour->created_at?->format('d.m.Y H:i') }}
      </div>
    </div>

    @php
      $backUrl = request()->query('returnTo') ?: route('tour.index');
    @endphp
    <div class="d-flex gap-2">
      {{-- ✏️ Bearbeiten inkl. returnTo --}}
      <a href="{{ route('tour.edit', ['tour' => $tour->id, 'returnTo' => url()->full()]) }}"
         class="btn btn-outline-primary">
        <i class="bi bi-pencil"></i> Bearbeiten
      </a>
      {{-- ↩️ Zurück --}}
      <a href="{{ $backUrl }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Zurück
      </a>
    </div>
  </div>

  {{-- Beschreibung --}}
  <div class="card mb-4">
    <div class="card-header">
      <i class="bi bi-card-text"></i> Beschreibung
    </div>
    <div class="card-body">
      @if(filled($tour->beschreibung))
        <div class="text-wrap" style="white-space:pre-wrap;">{{ $tour->beschreibung }}</div>
      @else
        <span class="text-muted">Keine Beschreibung hinterlegt.</span>
      @endif
    </div>
  </div>

  {{-- Verknüpfte Anlagen (Gebäude) mit Checkboxen + Löschen (nur Pivot) --}}
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span>
        <i class="bi bi-buildings"></i>
        Verknüpfte Anlagen ({{ $tour->gebaeude->count() }})
      </span>

      {{-- 🔘 Bulk-Verknüpfung löschen (nur Pivot) --}}
      @if($tour->gebaeude->isNotEmpty())
        <form id="bulk-detach-form"
              method="POST"
              action="{{ route('tour.gebaeude.bulkDetach', $tour->id) }}"
              onsubmit="return confirm('Ausgewählte Verknüpfung(en) wirklich löschen?');">
          @csrf
          @method('DELETE')
          {{-- returnTo für sauberes Zurück --}}
          <input type="hidden" name="returnTo" value="{{ url()->full() }}">
          <button type="submit" class="btn btn-sm btn-outline-danger" id="bulk-detach-btn" disabled>
            <i class="bi bi-trash"></i> Ausgewählte entfernen
          </button>
        </form>
      @endif
    </div>

    <div class="table-responsive">
      <table class="table align-middle mb-0">
        <thead class="table-light">
          <tr>
            {{-- Master-Checkbox zum Markieren aller Zeilen --}}
            <th style="width:48px;">
              <input type="checkbox" id="check-all">
            </th>
            {{-- Gewünschte Spaltenreihenfolge: Codex, Gebäudename, Straße, Nr., Wohnort --}}
            <th>Codex</th>
            <th>Gebäudename</th>
            <th>Straße</th>
            <th>Nr.</th>
            <th>Wohnort</th>
            <th class="text-end" style="width:160px;">Aktionen</th>
          </tr>
        </thead>
        <tbody>
          @forelse($tour->gebaeude as $g)
            <tr>
              {{-- ✅ Einzel-Checkbox. Per form-Attribut der Bulk-Form zugeordnet --}}
              <td>
                <input type="checkbox"
                       class="row-check"
                       name="gebaeude_ids[]"
                       value="{{ $g->id }}"
                       form="bulk-detach-form">
              </td>

              <td>{{ $g->codex }}</td>
              <td>{{ $g->gebaeude_name ?? ('Gebäude #'.$g->id) }}</td>
              <td>{{ $g->strasse }}</td>
              <td>{{ $g->hausnummer }}</td>
              <td>{{ $g->wohnort }}</td>

              <td class="text-end">
                <div class="btn-group" role="group">
                  {{-- ✏️ Gebäude bearbeiten --}}
                  <a href="{{ route('gebaeude.edit', ['id' => $g->id]) }}"
                     class="btn btn-sm btn-outline-primary"
                     title="Gebäude bearbeiten" aria-label="Gebäude bearbeiten">
                    <i class="bi bi-pencil"></i>
                  </a>

                  {{-- 🗑️ Nur Verknüpfung (Pivot) dieser EINEN Zeile löschen --}}
                  <form method="POST"
                        action="{{ route('tour.gebaeude.detach', ['tour' => $tour->id, 'gebaeude' => $g->id]) }}"
                        class="d-inline"
                        onsubmit="return confirm('Diese Verknüpfung wirklich löschen?');">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="returnTo" value="{{ url()->full() }}">
                    <button type="submit"
                            class="btn btn-sm btn-outline-danger"
                            title="Verknüpfung entfernen"
                            aria-label="Verknüpfung entfernen">
                      <i class="bi bi-trash"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center text-muted py-4">
                Keine Anlagen verknüpft.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

</div>
@endsection

@push('scripts')
<script>
  // Master-Checkbox & Bulk-Button-Logik (ohne Abhängigkeiten)
  document.addEventListener('DOMContentLoaded', function () {
    const master   = document.getElementById('check-all');
    const bulkBtn  = document.getElementById('bulk-detach-btn');
    const getChecks = () => Array.from(document.querySelectorAll('.row-check'));

    function updateBulkState() {
      if (!bulkBtn) return;
      const any = getChecks().some(ch => ch.checked);
      bulkBtn.disabled = !any;
    }

    if (master) {
      master.addEventListener('change', () => {
        getChecks().forEach(ch => ch.checked = master.checked);
        updateBulkState();
      });
    }

    getChecks().forEach(ch => {
      ch.addEventListener('change', () => {
        if (master) {
          const all = getChecks();
          const on  = all.filter(c => c.checked).length;
          master.checked = (on === all.length);
          master.indeterminate = (on > 0 && on < all.length);
        }
        updateBulkState();
      });
    });

    updateBulkState();
  });
</script>
@endpush
