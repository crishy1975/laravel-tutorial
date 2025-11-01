{{-- resources/views/gebaeude/partials/_timeline.blade.php --}}
{{-- Timeline: Formular (Datum + Bemerkung + Hinzufügen) und darunter die Liste der Einträge. --}}
{{-- Passt sich optisch an _touren_multi an (Kopf mit Icon + hr, kompakte Inputs). --}}

<div class="row g-4">

  {{-- Kopf / Titel --}}
  <div class="col-12">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
      <div class="d-flex align-items-center gap-2">
        <i class="bi bi-clock-history text-muted"></i>
        <span class="fw-semibold">Timeline</span>
      </div>
      {{-- optionaler Platz für spätere Schalter/Filter --}}
      <div></div>
    </div>
    <hr class="mt-2 mb-0">
  </div>

  {{-- Formular: eine Zeile, kompakt --}}
  <div class="col-12">
    {{-- WICHTIG: Grid-Klassen (row g-2) auf das <form>, sonst greifen die col-md-* nicht --}}
    <form method="POST"
          action="{{ route('gebaeude.timeline.store', $gebaeude->id) }}"
          class="row g-2 align-items-end">
      @csrf
      <input type="hidden" name="gebaeude_id" value="{{ $gebaeude->id }}">
      <input type="hidden" name="returnTo" value="{{ url()->current() }}">

      {{-- Datum --}}
      <div class="col-md-3">
        <label for="tl_datum" class="form-label fw-semibold mb-1">
          <i class="bi bi-calendar-date"></i> Datum
        </label>
        <input
          type="date"
          class="form-control @error('datum') is-invalid @enderror"
          id="tl_datum"
          name="datum"
          value="{{ old('datum', now()->toDateString()) }}">
        @error('datum') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      {{-- Bemerkung (optional) --}}
      <div class="col-md-7">
        <label for="tl_bem" class="form-label fw-semibold mb-1">
          <i class="bi bi-chat-left-text"></i> Bemerkung (optional)
        </label>
        <input
          type="text"
          class="form-control @error('bemerkung') is-invalid @enderror"
          id="tl_bem"
          name="bemerkung"
          placeholder="Kurze Notiz …"
          value="{{ old('bemerkung') }}">
        @error('bemerkung') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      {{-- Hinzufügen --}}
      <div class="col-md-2 text-end">
        <label class="form-label d-block mb-1">&nbsp;</label>
        <button type="submit" class="btn btn-success w-100">
          <i class="bi bi-plus-circle"></i> Hinzufügen
        </button>
      </div>

      {{-- Person wird im Controller automatisch aus Login / ggf. Adresse gesetzt --}}
    </form>
  </div>

  {{-- Liste der Timeline-Einträge --}}
  <div class="col-12">
    @php
      /** @var \Illuminate\Support\Collection|\Illuminate\Database\Eloquent\Collection $entries */
      $entries = $timelineEntries
        ?? ($gebaeude->timeline()->orderByDesc('datum')->orderByDesc('id')->get());
    @endphp

    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th style="width: 120px;">Datum</th>
            <th>Bemerkung</th>
            <th style="width: 220px;">Person</th>
            <th class="text-end" style="width: 100px;">Aktionen</th>
          </tr>
        </thead>
        <tbody>
          @forelse($entries as $e)
            <tr>
              <td class="text-nowrap">
                {{-- optional() verhindert Crash bei NULL-Datum --}}
                {{ optional(\Illuminate\Support\Carbon::parse($e->datum))->format('d.m.Y') }}
              </td>
              <td class="text-wrap" style="white-space: normal;">
                {{ $e->bemerkung ?: '—' }}
              </td>
              <td class="text-nowrap">
                {{ $e->person_name ?: '—' }}
              </td>
              <td class="text-end">
                <form action="{{ route('timeline.destroy', $e->id) }}"
                      method="POST"
                      onsubmit="return confirm('Diesen Eintrag wirklich löschen?')">
                  @csrf
                  @method('DELETE')
                  <input type="hidden" name="returnTo" value="{{ url()->current() }}">
                  <button type="submit" class="btn btn-sm btn-outline-danger" title="Löschen">
                    <i class="bi bi-trash"></i>
                  </button>
                </form>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="4" class="text-center text-muted py-4">
                Keine Timeline-Einträge vorhanden.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

</div>
