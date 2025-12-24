{{-- resources/views/gebaeude/partials/_allgemein.blade.php --}}
{{-- MOBIL-OPTIMIERT --}}

<div class="row g-2 g-md-3">

  {{-- Codex + Gebaeudename --}}
  <div class="col-4 col-md-3">
    <label for="codex" class="form-label small mb-1">
      <i class="bi bi-tag d-none d-sm-inline"></i> Codex
    </label>
    <input type="text" id="codex" name="codex"
      class="form-control @error('codex') is-invalid @enderror"
      value="{{ old('codex', $gebaeude->codex ?? '') }}"
      list="codexPrefixList" autocomplete="off">
    @error('codex') <div class="invalid-feedback">{{ $message }}</div> @enderror
    
    <datalist id="codexPrefixList">
      @foreach(($codexPrefixTips ?? []) as $item)
        <option value="{{ $item['prefix'] }}" label="{{ $item['hint'] }}"></option>
      @endforeach
    </datalist>
  </div>

  <div class="col-8 col-md-9">
    <label for="gebaeude_name" class="form-label small mb-1">
      <i class="bi bi-building d-none d-sm-inline"></i> Gebaeudename
    </label>
    <input type="text" id="gebaeude_name" name="gebaeude_name"
      class="form-control @error('gebaeude_name') is-invalid @enderror"
      value="{{ old('gebaeude_name', $gebaeude->gebaeude_name ?? '') }}">
    @error('gebaeude_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>

  {{-- Strasse + Hausnummer --}}
  <div class="col-8 col-md-9">
    <label for="strasse" class="form-label small mb-1">
      <i class="bi bi-signpost-2 d-none d-sm-inline"></i> Strasse
    </label>
    <input type="text" id="strasse" name="strasse"
      class="form-control @error('strasse') is-invalid @enderror"
      value="{{ old('strasse', $gebaeude->strasse ?? '') }}">
    @error('strasse') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>

  <div class="col-4 col-md-3">
    <label for="hausnummer" class="form-label small mb-1">Nr.</label>
    <input type="text" id="hausnummer" name="hausnummer"
      class="form-control @error('hausnummer') is-invalid @enderror"
      value="{{ old('hausnummer', $gebaeude->hausnummer ?? '') }}">
    @error('hausnummer') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>

  {{-- Land + PLZ + Ort --}}
  <div class="col-6 col-md-4">
    <label for="land" class="form-label small mb-1">
      <i class="bi bi-globe d-none d-sm-inline"></i> Land
    </label>
    <input type="text" id="land" name="land"
      class="form-control @error('land') is-invalid @enderror"
      value="{{ old('land', $gebaeude->land ?? '') }}"
      placeholder="z.B. Italien">
    @error('land') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>

  <div class="col-6 col-md-2">
    <label for="plz" class="form-label small mb-1">PLZ</label>
    <input type="text" id="plz" name="plz"
      class="form-control @error('plz') is-invalid @enderror"
      value="{{ old('plz', $gebaeude->plz ?? '') }}">
    @error('plz') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>

  <div class="col-12 col-md-6">
    <label for="wohnort" class="form-label small mb-1">
      <i class="bi bi-geo-alt d-none d-sm-inline"></i> Ort
    </label>
    <input type="text" id="wohnort" name="wohnort"
      class="form-control @error('wohnort') is-invalid @enderror"
      value="{{ old('wohnort', $gebaeude->wohnort ?? '') }}">
    @error('wohnort') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>

  {{-- Bemerkung --}}
  <div class="col-12">
    <label for="bemerkung" class="form-label small mb-1">
      <i class="bi bi-chat-left-text d-none d-sm-inline"></i> Bemerkung
    </label>
    <textarea id="bemerkung" name="bemerkung" rows="2"
      class="form-control @error('bemerkung') is-invalid @enderror">{{ old('bemerkung', $gebaeude->bemerkung ?? '') }}</textarea>
    @error('bemerkung') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>

</div>
