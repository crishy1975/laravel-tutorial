{{-- 🔹 Adressen-Partial – kompatibel mit Select2, dynamischem Edit-Link & Prefix --}}
<div class="row g-4">

  {{-- ✅ Toolbar: Titel links, "Adresse anlegen" rechts --}}
  <div class="col-12">
    <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-2">
      <div class="d-flex align-items-center gap-2">
        <i class="bi bi-people text-muted"></i>
        <span class="fw-semibold">Adressen</span>
      </div>

      {{-- ➕ Neue Adresse anlegen (immer sichtbar, führt in Create mit returnTo) --}}
      <a href="{{ route('adresse.create', ['returnTo' => url()->current()]) }}"
         class="btn btn-outline-success btn-sm">
        <i class="bi bi-person-plus"></i> Adresse anlegen
      </a>
    </div>
    
  </div>

  {{-- Wir geben den Rücksprung mit (falls in Edit/Create genutzt) --}}
  <input type="hidden" name="returnTo" value="{{ $returnTo ?? url()->current() }}">

  {{-- ✉️ Postadresse --}}
  <div class="col-md-6">
    <label for="{{ $prefix ?? 'adr' }}_postadresse_id" class="form-label fw-semibold">
      <i class="bi bi-envelope"></i> Postadresse
    </label>

    <select id="{{ $prefix ?? 'adr' }}_postadresse_id"
            name="postadresse_id"
            class="form-select js-select2"
            data-placeholder="— bitte wählen —"
            data-allow-clear="false"
            data-edit-link-id="{{ $prefix ?? 'adr' }}_postadresse_id_editlink"
            data-edit-base="{{ url('/adresse') }}">
      <option value=""></option>
      @foreach($adressen as $adresse)
        <option value="{{ $adresse->id }}"
          {{ old('postadresse_id', $gebaeude->postadresse_id ?? null) == $adresse->id ? 'selected' : '' }}>
          {{ $adresse->name }} – {{ $adresse->wohnort }}
        </option>
      @endforeach
    </select>

    {{-- 🔗 Edit-Link (nur sichtbar, wenn ausgewählt) --}}
    @php $postadresse_id = old('postadresse_id', $gebaeude->postadresse_id ?? null); @endphp
    <a id="{{ $prefix ?? 'adr' }}_postadresse_id_editlink"
       href="{{ $postadresse_id ? route('adresse.edit', ['id' => $postadresse_id, 'returnTo' => url()->current()]) : '#' }}"
       class="btn btn-sm btn-outline-primary mt-2 {{ $postadresse_id ? '' : 'd-none' }}">
      <i class="bi bi-pencil-square"></i> Adresse bearbeiten
    </a>
  </div>

  {{-- 💰 Rechnungsempfänger --}}
  <div class="col-md-6">
    <label for="{{ $prefix ?? 'adr' }}_rechnungsempfaenger_id" class="form-label fw-semibold">
      <i class="bi bi-receipt"></i> Rechnungsempfänger
    </label>

    <select id="{{ $prefix ?? 'adr' }}_rechnungsempfaenger_id"
            name="rechnungsempfaenger_id"
            class="form-select js-select2"
            data-placeholder="— bitte wählen —"
            data-allow-clear="false"
            data-edit-link-id="{{ $prefix ?? 'adr' }}_rechnungsempfaenger_id_editlink"
            data-edit-base="{{ url('/adresse') }}">
      <option value=""></option>
      @foreach($adressen as $adresse)
        <option value="{{ $adresse->id }}"
          {{ old('rechnungsempfaenger_id', $gebaeude->rechnungsempfaenger_id ?? null) == $adresse->id ? 'selected' : '' }}>
          {{ $adresse->name }} – {{ $adresse->wohnort }}
        </option>
      @endforeach
    </select>

    {{-- 🔗 Edit-Link (nur sichtbar, wenn ausgewählt) --}}
    @php $rechnungsempfaenger_id = old('rechnungsempfaenger_id', $gebaeude->rechnungsempfaenger_id ?? null); @endphp
    <a id="{{ $prefix ?? 'adr' }}_rechnungsempfaenger_id_editlink"
       href="{{ $rechnungsempfaenger_id ? route('adresse.edit', ['id' => $rechnungsempfaenger_id, 'returnTo' => url()->current()]) : '#' }}"
       class="btn btn-sm btn-outline-primary mt-2 {{ $rechnungsempfaenger_id ? '' : 'd-none' }}">
      <i class="bi bi-pencil-square"></i> Adresse bearbeiten
    </a>
  </div>

</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  // 🔧 Aktualisiert den Edit-Link passend zur Auswahl
  function updateEditLink(selectEl) {
    const selectedId = selectEl.value;
    const linkId     = selectEl.dataset.editLinkId;
    const baseUrl    = selectEl.dataset.editBase;
    const link       = document.getElementById(linkId);
    if (!link) return;

    if (selectedId) {
      const returnTo = encodeURIComponent(window.location.href);
      link.href = `${baseUrl}/${selectedId}/edit?returnTo=${returnTo}`;
      link.classList.remove('d-none');
    } else {
      link.href = '#';
      link.classList.add('d-none');
    }
  }

  // 🔸 alle relevanten Selects initialisieren
  const selects = document.querySelectorAll('select[data-edit-link-id][data-edit-base]');
  selects.forEach(selectEl => {
    updateEditLink(selectEl); // initial
    selectEl.addEventListener('change', () => updateEditLink(selectEl));

    // Support für Select2-Events (falls aktiv)
    if (typeof $ !== 'undefined' && $(selectEl).data('select2')) {
      $(selectEl).on('select2:select select2:clear', () => updateEditLink(selectEl));
    }
  });
});
</script>
@endpush
