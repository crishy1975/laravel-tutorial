{{-- resources/views/adressen/_form.blade.php --}}
<div class="row g-3">

  {{-- Name / Firma --}}
  <div class="col-md-8">
    <div class="form-floating">
      <input type="text" id="name" name="name" placeholder=" "
        class="form-control @error('name') is-invalid @enderror"
        value="{{ session('vies_name', old('name', $adresse->name)) }}" required>
      <label for="name">Name / Firma *</label>
      @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
  </div>

  {{-- Provinz --}}
  <div class="col-md-4">
    <div class="form-floating">
      <input type="text" id="provinz" name="provinz" maxlength="4" placeholder=" "
        class="form-control @error('provinz') is-invalid @enderror"
        value="{{ session('vies_provinz', old('provinz', $adresse->provinz)) }}">
      <label for="provinz">Provinz</label>
      @error('provinz') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
  </div>

  {{-- Straße (mit Vorschlagsliste via <datalist>) --}}
  <div class="col-md-7">
    <div class="form-floating">
      <input
        type="text"
        id="strasse"
        name="strasse"
        placeholder=" "
        class="form-control @error('strasse') is-invalid @enderror"
        value="{{ session('vies_strasse', old('strasse', $adresse->strasse)) }}"
        list="strassenListe"         {{-- verbindet Input mit der Datalist --}}
        autocomplete="off"           {{-- Browser-Autovervollständigung aus, damit die Datalist sauber greift --}}
        spellcheck="false"
      >
      <label for="strasse">Straße</label>
      @error('strasse') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    {{-- 🔽 Vorschläge für Straße: per Controller als $strassen übergeben --}}
    <datalist id="strassenListe">
      @foreach(($strassen ?? []) as $s)
        <option value="{{ $s }}"></option>
      @endforeach
    </datalist>
  </div>

  {{-- Nr. --}}
  <div class="col-md-2">
    <div class="form-floating">
      <input type="text" id="hausnummer" name="hausnummer" placeholder=" "
        class="form-control @error('hausnummer') is-invalid @enderror"
        value="{{ session('vies_hausnr', old('hausnummer', $adresse->hausnummer)) }}">
      <label for="hausnummer">Nr.</label>
      @error('hausnummer') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
  </div>

  {{-- PLZ --}}
  <div class="col-md-3">
    <div class="form-floating">
      <input type="text" id="plz" name="plz" placeholder=" "
        class="form-control @error('plz') is-invalid @enderror"
        value="{{ session('vies_plz', old('plz', $adresse->plz)) }}">
      <label for="plz">PLZ</label>
      @error('plz') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
  </div>

  {{-- Wohnort --}}
  <div class="col-md-6">
    <div class="form-floating">
      <input type="text" id="wohnort" name="wohnort" placeholder=" "
        class="form-control @error('wohnort') is-invalid @enderror"
        value="{{ session('vies_wohnort', old('wohnort', $adresse->wohnort)) }}">
      <label for="wohnort">Wohnort</label>
      @error('wohnort') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
  </div>

  {{-- Land --}}
  <div class="col-md-6">
    <div class="form-floating">
      <input type="text" id="land" name="land" placeholder=" "
        class="form-control @error('land') is-invalid @enderror"
        value="{{ session('vies_land', old('land', $adresse->land)) }}">
      <label for="land">Land</label>
      @error('land') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
  </div>

  {{-- Telefon --}}
  <div class="col-md-6">
    <div class="form-floating">
      <input type="text" id="telefon" name="telefon" placeholder=" "
        class="form-control @error('telefon') is-invalid @enderror"
        value="{{ old('telefon', $adresse->telefon) }}">
      <label for="telefon">Telefon</label>
      @error('telefon') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
  </div>

  {{-- Handy --}}
  <div class="col-md-6">
    <div class="form-floating">
      <input type="text" id="handy" name="handy" placeholder=" "
        class="form-control @error('handy') is-invalid @enderror"
        value="{{ old('handy', $adresse->handy) }}">
      <label for="handy">Handy</label>
      @error('handy') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
  </div>

  {{-- E-Mail --}}
  <div class="col-md-6">
    <div class="form-floating">
      <input type="email" id="email" name="email" placeholder=" "
        class="form-control @error('email') is-invalid @enderror"
        value="{{ old('email', $adresse->email) }}">
      <label for="email">E-Mail</label>
      @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
  </div>

  {{-- Zweit-E-Mail --}}
  <div class="col-md-6">
    <div class="form-floating">
      <input type="email" id="email_zweit" name="email_zweit" placeholder=" "
        class="form-control @error('email_zweit') is-invalid @enderror"
        value="{{ old('email_zweit', $adresse->email_zweit) }}">
      <label for="email_zweit">Zweit-E-Mail</label>
      @error('email_zweit') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
  </div>

  {{-- PEC --}}
  <div class="col-md-6">
    <div class="form-floating">
      <input type="email" id="pec" name="pec" placeholder=" "
        class="form-control @error('pec') is-invalid @enderror"
        value="{{ old('pec', $adresse->pec) }}">
      <label for="pec">PEC</label>
      @error('pec') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
  </div>

  {{-- Steuernummer --}}
  <div class="col-md-6">
    <div class="form-floating">
      <input type="text" id="steuernummer" name="steuernummer" placeholder=" "
        class="form-control @error('steuernummer') is-invalid @enderror"
        value="{{ old('steuernummer', $adresse->steuernummer) }}">
      <label for="steuernummer">Steuernummer</label>
      @error('steuernummer') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
  </div>

  {{-- MwSt-Nummer (Partita IVA) --}}
  <div class="col-md-6">
    <div class="form-floating">
      <input type="text" id="mwst_nummer" name="mwst_nummer" placeholder=" "
        class="form-control @error('mwst_nummer') is-invalid @enderror"
        value="{{ session('vies_mwst', old('mwst_nummer', $adresse->mwst_nummer)) }}">
      <label for="mwst_nummer">MwSt-Nummer (Partita IVA)</label>
      @error('mwst_nummer') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
  </div>

  {{-- Codice Univoco (SDI) mit Vorschlagsliste via <datalist> --}}
  <div class="col-md-6">
    <div class="form-floating">
      <input
        type="text"
        id="codice_univoco"
        name="codice_univoco"
        placeholder=" "
        class="form-control @error('codice_univoco') is-invalid @enderror"
        value="{{ old('codice_univoco', $adresse->codice_univoco ?? '') }}"
        list="codiceUnivocoList"   {{-- verbindet Input mit der Datalist --}}
        autocomplete="off"
        spellcheck="false"
      >
      <label for="codice_univoco">Codice Univoco (SDI)</label>
      @error('codice_univoco') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    {{-- 🔽 Vorschläge für Codice Univoco: per Controller als $codiciUnivoci übergeben --}}
    <datalist id="codiceUnivocoList">
      @foreach(($codiciUnivoci ?? []) as $c)
        <option value="{{ $c }}"></option>
      @endforeach
    </datalist>
  </div>

  {{-- Bemerkung --}}
  <div class="col-12">
    <div class="form-floating">
      <textarea id="bemerkung" name="bemerkung" placeholder=" " style="height:100px"
        class="form-control @error('bemerkung') is-invalid @enderror">{{ old('bemerkung', $adresse->bemerkung) }}</textarea>
      <label for="bemerkung">Bemerkung</label>
      @error('bemerkung') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
  </div>

</div>
