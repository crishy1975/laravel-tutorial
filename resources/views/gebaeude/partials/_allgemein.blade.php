{{-- Reines Feld-Partial für Gebäude: für create & edit nutzbar --}}
<div class="row g-3">

  {{-- Codex + Gebäudename --}}
  {{-- Codex (nur Buchstaben-Präfix auswählen; Vorschläge aus bestehenden Codizes) --}}
  <div class="col-md-3">
    <div class="form-floating">
      <input
        type="text"
        id="codex"
        name="codex"
        placeholder=" "
        class="form-control @error('codex') is-invalid @enderror"
        value="{{ old('codex', $gebaeude->codex) }}"
        list="codexPrefixList" {{-- verbindet mit der Datalist --}}
        autocomplete="off"
        spellcheck="false">
      <label for="codex">Codex</label>
      @error('codex') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    {{-- 🔽 Vorschläge: value = Präfix, label = Hinweis (Straße, Ort) --}}
    <datalist id="codexPrefixList">
      @foreach(($codexPrefixTips ?? []) as $item)
      @php
      $prefix = $item['prefix'];
      $hint = $item['hint'];
      @endphp
      {{-- label wird in vielen Browsern als Beschreibung angezeigt; value landet im Input --}}
      <option value="{{ $prefix }}" label="{{ $hint }}"></option>
      @endforeach
    </datalist>
  </div>


  <div class="col-md-9">
    <div class="form-floating">
      <input type="text" class="form-control @error('gebaeude_name') is-invalid @enderror"
        id="gebaeude_name" name="gebaeude_name" placeholder=" "
        value="{{ old('gebaeude_name', $gebaeude->gebaeude_name ?? '') }}">
      <label for="gebaeude_name">Gebäudename</label>
      @error('gebaeude_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
  </div>

  {{-- Straße + Hausnummer --}}
  <div class="col-md-9">
    <div class="form-floating">
      <input type="text" class="form-control @error('strasse') is-invalid @enderror"
        id="strasse" name="strasse" placeholder=" "
        value="{{ old('strasse', $gebaeude->strasse ?? '') }}">
      <label for="strasse">Straße</label>
      @error('strasse') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
  </div>

  <div class="col-md-3">
    <div class="form-floating">
      <input type="text" class="form-control @error('hausnummer') is-invalid @enderror"
        id="hausnummer" name="hausnummer" placeholder=" "
        value="{{ old('hausnummer', $gebaeude->hausnummer ?? '') }}">
      <label for="hausnummer">Nr.</label>
      @error('hausnummer') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
  </div>

  {{-- Land + PLZ + Ort --}}
  <div class="col-md-4">
    <div class="form-floating">
      <input type="text" class="form-control @error('land') is-invalid @enderror"
        id="land" name="land" placeholder=" "
        value="{{ old('land', $gebaeude->land ?? '') }}">
      <label for="land">Land</label>
      @error('land') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
  </div>

  <div class="col-md-2">
    <div class="form-floating">
      <input type="text" class="form-control @error('plz') is-invalid @enderror"
        id="plz" name="plz" placeholder=" "
        value="{{ old('plz', $gebaeude->plz ?? '') }}">
      <label for="plz">PLZ</label>
      @error('plz') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
  </div>

  <div class="col-md-6">
    <div class="form-floating">
      <input type="text" class="form-control @error('wohnort') is-invalid @enderror"
        id="wohnort" name="wohnort" placeholder=" "
        value="{{ old('wohnort', $gebaeude->wohnort ?? '') }}">
      <label for="wohnort">Ort</label>
      @error('wohnort') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
  </div>

  {{-- Bemerkung --}}
  <div class="col-12">
    <div class="form-floating">
      <textarea class="form-control @error('bemerkung') is-invalid @enderror"
        id="bemerkung" name="bemerkung" placeholder=" "
        style="height: 100px">{{ old('bemerkung', $gebaeude->bemerkung ?? '') }}</textarea>
      <label for="bemerkung">Bemerkung</label>
      @error('bemerkung') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
  </div>

</div>