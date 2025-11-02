{{-- resources/views/gebaeude/partials/_fatturapa.blade.php --}}
{{-- Dieses Partial liegt IM Hauptformular (create/edit) – KEIN eigenes <form> hier! --}}

<div class="row g-3">

  {{-- Buchhaltungs-Bemerkung (intern) --}}
  <div class="col-12">
    <div class="form-floating">
      <textarea
        class="form-control @error('bemerkung_buchhaltung') is-invalid @enderror"
        id="bemerkung_buchhaltung"
        name="bemerkung_buchhaltung"
        placeholder=" "
        style="height: 110px">{{ old('bemerkung_buchhaltung', $gebaeude->bemerkung_buchhaltung) }}</textarea>
      <label for="bemerkung_buchhaltung">Buchhaltungs-Bemerkung</label>
      @error('bemerkung_buchhaltung') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
  </div>

  {{-- Fattura-Profil --}}
  <div class="col-md-6">
    <div class="form-floating">
      <select
        class="form-select @error('fattura_profile_id') is-invalid @enderror"
        id="fattura_profile_id"
        name="fattura_profile_id"
        aria-label="Fattura-Profil">
        <option value="">– Kein Profil –</option>
        @foreach(($fatturaProfiles ?? []) as $p)
          <option value="{{ $p->id }}"
            {{ (string)old('fattura_profile_id', $gebaeude->fattura_profile_id) === (string)$p->id ? 'selected' : '' }}>
            {{ $p->name }}
          </option>
        @endforeach
      </select>
      <label for="fattura_profile_id">Fattura-Profil</label>
      @error('fattura_profile_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
  </div>

  {{-- CUP --}}
  <div class="col-md-3">
    <div class="form-floating">
      <input
        type="text"
        class="form-control @error('cup') is-invalid @enderror"
        id="cup"
        name="cup"
        placeholder=" "
        maxlength="20"
        value="{{ old('cup', $gebaeude->cup) }}">
      <label for="cup">CUP</label>
      @error('cup') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
  </div>

  {{-- CIG --}}
  <div class="col-md-3">
    <div class="form-floating">
      <input
        type="text"
        class="form-control @error('cig') is-invalid @enderror"
        id="cig"
        name="cig"
        placeholder=" "
        maxlength="10"
        value="{{ old('cig', $gebaeude->cig) }}">
      <label for="cig">CIG</label>
      @error('cig') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
  </div>

  {{-- Auftrags-ID --}}
  <div class="col-md-6">
    <div class="form-floating">
      <input
        type="text"
        class="form-control @error('auftrag_id') is-invalid @enderror"
        id="auftrag_id"
        name="auftrag_id"
        placeholder=" "
        maxlength="50"
        value="{{ old('auftrag_id', $gebaeude->auftrag_id) }}">
      <label for="auftrag_id">Auftrags-ID</label>
      @error('auftrag_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
  </div>

  {{-- Auftrags-Datum --}}
  <div class="col-md-3">
    <div class="form-floating">
      <input
        type="date"
        class="form-control @error('auftrag_datum') is-invalid @enderror"
        id="auftrag_datum"
        name="auftrag_datum"
        placeholder=" "
        value="{{ old('auftrag_datum', optional($gebaeude->auftrag_datum)->toDateString()) }}">
      <label for="auftrag_datum">Auftrags-Datum</label>
      @error('auftrag_datum') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
  </div>

  {{-- Bank-Erkennungstext (Template) --}}
  <div class="col-md-9">
    <div class="form-floating">
      <textarea
        class="form-control @error('bank_match_text_template') is-invalid @enderror"
        id="bank_match_text_template"
        name="bank_match_text_template"
        placeholder=" "
        style="height: 90px">{{ old('bank_match_text_template', $gebaeude->bank_match_text_template) }}</textarea>
      <label for="bank_match_text_template">Bank-Erkennungstext (Template)</label>
      @error('bank_match_text_template') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="form-text mt-1">
      Platzhalter: <code>{invoice_number}</code>, <code>{invoice_year}</code>, <code>{building_codex}</code>, <code>{building_name}</code>
    </div>
  </div>

</div>
