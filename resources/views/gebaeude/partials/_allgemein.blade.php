{{-- Reines Feld-Partial für Gebäude: für create & edit nutzbar --}}
<div class="row g-3">

  {{-- 🏢 Codex + Gebäudename --}}
  <div class="col-md-3">
    <label for="codex" class="form-label small mb-1 fw-semibold">
      <i class="bi bi-tag"></i> Codex
    </label>
    <input
      type="text"
      id="codex"
      name="codex"
      class="form-control form-control-sm @error('codex') is-invalid @enderror"
      value="{{ old('codex', $gebaeude->codex) }}"
      list="codexPrefixList"
      autocomplete="off"
      spellcheck="false">
    @error('codex') <div class="invalid-feedback">{{ $message }}</div> @enderror

    {{-- 📽 Vorschläge: value = Präfix, label = Hinweis (Straße, Ort) --}}
    <datalist id="codexPrefixList">
      @foreach(($codexPrefixTips ?? []) as $item)
        @php
          $prefix = $item['prefix'];
          $hint = $item['hint'];
        @endphp
        <option value="{{ $prefix }}" label="{{ $hint }}"></option>
      @endforeach
    </datalist>
    <small class="text-muted">z.B. gam, via, etc.</small>
  </div>

  <div class="col-md-9">
    <label for="gebaeude_name" class="form-label small mb-1 fw-semibold">
      <i class="bi bi-building"></i> Gebäudename
    </label>
    <input 
      type="text" 
      class="form-control form-control-sm @error('gebaeude_name') is-invalid @enderror"
      id="gebaeude_name" 
      name="gebaeude_name"
      value="{{ old('gebaeude_name', $gebaeude->gebaeude_name ?? '') }}">
    @error('gebaeude_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>

  {{-- 📍 Straße + Hausnummer --}}
  <div class="col-md-9">
    <label for="strasse" class="form-label small mb-1 fw-semibold">
      <i class="bi bi-signpost-2"></i> Straße
    </label>
    <input 
      type="text" 
      class="form-control form-control-sm @error('strasse') is-invalid @enderror"
      id="strasse" 
      name="strasse"
      value="{{ old('strasse', $gebaeude->strasse ?? '') }}">
    @error('strasse') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>

  <div class="col-md-3">
    <label for="hausnummer" class="form-label small mb-1 fw-semibold">
      <i class="bi bi-123"></i> Nr.
    </label>
    <input 
      type="text" 
      class="form-control form-control-sm @error('hausnummer') is-invalid @enderror"
      id="hausnummer" 
      name="hausnummer"
      value="{{ old('hausnummer', $gebaeude->hausnummer ?? '') }}">
    @error('hausnummer') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>

  {{-- 🌍 Land + PLZ + Ort --}}
  <div class="col-md-4">
    <label for="land" class="form-label small mb-1 fw-semibold">
      <i class="bi bi-globe-europe-africa"></i> Land
    </label>
    <input 
      type="text" 
      class="form-control form-control-sm @error('land') is-invalid @enderror"
      id="land" 
      name="land"
      value="{{ old('land', $gebaeude->land ?? '') }}">
    @error('land') <div class="invalid-feedback">{{ $message }}</div> @enderror
    <small class="text-muted">z.B. Italien, Deutschland</small>
  </div>

  <div class="col-md-2">
    <label for="plz" class="form-label small mb-1 fw-semibold">
      <i class="bi bi-mailbox"></i> PLZ
    </label>
    <input 
      type="text" 
      class="form-control form-control-sm @error('plz') is-invalid @enderror"
      id="plz" 
      name="plz"
      value="{{ old('plz', $gebaeude->plz ?? '') }}">
    @error('plz') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>

  <div class="col-md-6">
    <label for="wohnort" class="form-label small mb-1 fw-semibold">
      <i class="bi bi-geo-alt"></i> Ort
    </label>
    <input 
      type="text" 
      class="form-control form-control-sm @error('wohnort') is-invalid @enderror"
      id="wohnort" 
      name="wohnort"
      value="{{ old('wohnort', $gebaeude->wohnort ?? '') }}">
    @error('wohnort') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>

  {{-- 📝 Bemerkung --}}
  <div class="col-12">
    <label for="bemerkung" class="form-label small mb-1 fw-semibold">
      <i class="bi bi-chat-left-text"></i> Bemerkung
    </label>
    <textarea 
      class="form-control form-control-sm @error('bemerkung') is-invalid @enderror"
      id="bemerkung" 
      name="bemerkung"
      rows="3">{{ old('bemerkung', $gebaeude->bemerkung ?? '') }}</textarea>
    @error('bemerkung') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>

</div>