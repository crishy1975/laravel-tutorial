{{-- resources/views/gebaeude/partials/_touren_multi.blade.php --}}
<div class="row g-4">

  {{-- Kopf / Titel + Bearbeiten-Schalter --}}
  <div class="col-12">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
      <div class="d-flex align-items-center gap-2">
        <i class="bi bi-calendar-week text-muted"></i>
        <span class="fw-semibold">Touren-Zuordnung</span>
      </div>

      {{-- 🧰 Bearbeiten (Ja/Nein) als Bootstrap Switch --}}
      <div class="form-check form-switch m-0">
        @php
          // Default: Bearbeiten = NEIN (read-only)
          $defaultEditable = false;
          $editable = old('edit_mode', $defaultEditable) ? true : false;
        @endphp
        <input class="form-check-input" type="checkbox" role="switch"
               id="edit_mode_switch"
               name="edit_mode" value="1"
               {{ $editable ? 'checked' : '' }}>
        <label class="form-check-label" for="edit_mode_switch">
          Bearbeiten: <strong>{{ $editable ? 'Ja' : 'Nein' }}</strong>
        </label>
      </div>
    </div>
    <hr class="mt-2 mb-0">
  </div>

  {{-- Einzige Spalte: native Multi-Select (ohne Plugins) --}}
  <div class="col-12">
    <div id="tourenSelectWrap" class="{{ $editable ? '' : 'is-readonly' }}">
      <label for="tour_ids" class="form-label fw-semibold d-flex align-items-center gap-2">
        <span><i class="bi bi-route"></i> Touren auswählen</span>
        <span id="ro_badge" class="badge bg-secondary-subtle text-secondary border align-middle {{ $editable ? 'd-none' : '' }}">
          Nur Lesen
        </span>
      </label>

      @php
        // bereits zugeordnete IDs (alte Eingabe oder Relation)
        $assigned = collect(old('tour_ids', ($gebaeude->touren ?? collect())->pluck('id')->all()));
      @endphp

      {{-- 🔒 Read-only: disabled (Fokus-/Leuchtfarben per CSS neutralisiert) --}}
      <select id="tour_ids"
              name="tour_ids[]"
              class="form-select"
              multiple
              size="8"
              aria-describedby="tourHelp"
              {{ $editable ? '' : 'disabled' }}>
        @foreach(($tourenAlle ?? []) as $tour)
          <option value="{{ $tour->id }}" {{ $assigned->contains($tour->id) ? 'selected' : '' }}>
            {{ $tour->name }} @if(!$tour->aktiv) (inaktiv) @endif
          </option>
        @endforeach
      </select>

      {{-- 📥 Hidden-Mirror: bei disabled, damit Werte gesendet werden --}}
      <div id="tour_ids_mirror">
        @unless($editable)
          @foreach($assigned as $id)
            <input type="hidden" name="tour_ids[]" value="{{ $id }}">
          @endforeach
        @endunless
      </div>
    </div>

    <div id="tourHelp" class="form-text">
      Halte <strong>Strg/Cmd</strong> (einzeln) oder <strong>Shift</strong> (Bereich) gedrückt, um mehrere Touren zu wählen.
      <br>Bei „Bearbeiten: Nein“ ist die Liste gesperrt und dezent dargestellt (ohne Fokus-Leuchte).
    </div>

    {{-- Validierungsfehler --}}
    @error('tour_ids')   <div class="text-danger small mt-1">{{ $message }}</div> @enderror
    @error('tour_ids.*') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
  </div>

</div>


@push('scripts')
<script>
/**
 * Schalter-Logik:
 * - Schaltet disabled für das Select.
 * - Setzt/entfernt .is-readonly-Klasse (macht Fokus/Leuchte dezent).
 * - Spiegelt Auswahl in Hidden-Inputs, wenn disabled.
 */
document.addEventListener('DOMContentLoaded', function () {
  const switchEl   = document.getElementById('edit_mode_switch');
  const wrapEl     = document.getElementById('tourenSelectWrap');
  const selectEl   = document.getElementById('tour_ids');
  const mirrorBox  = document.getElementById('tour_ids_mirror');
  const roBadge    = document.getElementById('ro_badge');

  function buildMirrorInputs() {
    mirrorBox.innerHTML = '';
    const selected = Array.from(selectEl.options).filter(o => o.selected).map(o => o.value);
    selected.forEach(id => {
      const input = document.createElement('input');
      input.type  = 'hidden';
      input.name  = 'tour_ids[]';
      input.value = id;
      mirrorBox.appendChild(input);
    });
  }

  function setReadOnly(readOnly) {
    if (readOnly) {
      // UI-Optik & Verhalten
      wrapEl.classList.add('is-readonly');
      selectEl.setAttribute('disabled', 'disabled');
      roBadge?.classList.remove('d-none');
      // Hidden-Inputs erzeugen
      buildMirrorInputs();
      // Sicherheitsnetz: Fokus entfernen, falls gesetzt → keine Leuchte
      try { selectEl.blur(); } catch(e) {}
    } else {
      wrapEl.classList.remove('is-readonly');
      selectEl.removeAttribute('disabled');
      roBadge?.classList.add('d-none');
      mirrorBox.innerHTML = '';
    }
    // Label-Text aktualisieren
    const label = document.querySelector('label[for="edit_mode_switch"] strong');
    if (label) label.textContent = readOnly ? 'Nein' : 'Ja';
  }

  // Umschalten per Switch
  switchEl?.addEventListener('change', function () {
    setReadOnly(!this.checked);
  });

  // Initialzustand absichern: wenn disabled geliefert, Mirror bauen
  if (selectEl.hasAttribute('disabled')) {
    buildMirrorInputs();
    // Kein Fokus-Glow, falls Browser/AutoFocus doch gesetzt hat
    try { selectEl.blur(); } catch(e) {}
  }
});
</script>
@endpush
