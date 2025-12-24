{{-- resources/views/adresse/_form.blade.php --}} 
{{-- MOBIL-OPTIMIERT: Cards mit Farben, Touch-freundliche Inputs --}}

{{-- ═══════════════════════════════════════════════════════════ --}}
{{-- GRUNDDATEN --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
<div class="card mb-3">
  <div class="card-header bg-primary text-white py-2">
    <i class="bi bi-person-fill"></i>
    <span class="fw-semibold ms-1">Grunddaten</span>
  </div>
  <div class="card-body p-2 p-md-3">
    <div class="row g-3">
      {{-- Name / Firma --}} 
      <div class="col-12">
        <label for="name" class="form-label small mb-1">Name / Firma <span class="text-danger">*</span></label>
        <input type="text" id="name" name="name"
          class="form-control @error('name') is-invalid @enderror"
          value="{{ session('vies_name', old('name', $adresse->name)) }}" required>
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
    </div>
  </div>
</div>

{{-- ═══════════════════════════════════════════════════════════ --}}
{{-- ANSCHRIFT --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
<div class="card mb-3">
  <div class="card-header bg-success text-white py-2">
    <i class="bi bi-geo-alt-fill"></i>
    <span class="fw-semibold ms-1">Anschrift</span>
  </div>
  <div class="card-body p-2 p-md-3">
    <div class="row g-3">
      {{-- Strasse --}}
      <div class="col-8 col-md-9"> 
        <label for="strasse" class="form-label small mb-1">Strasse</label>
        <input type="text" id="strasse" name="strasse"
          class="form-control @error('strasse') is-invalid @enderror"
          value="{{ session('vies_strasse', old('strasse', $adresse->strasse)) }}"
          list="strassenListe" autocomplete="off">
        @error('strasse') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <datalist id="strassenListe">
          @foreach(($strassen ?? []) as $s)
            <option value="{{ $s }}"></option>
          @endforeach
        </datalist>
      </div>

      {{-- Hausnummer --}}
      <div class="col-4 col-md-3">
        <label for="hausnummer" class="form-label small mb-1">Nr.</label>
        <input type="text" id="hausnummer" name="hausnummer"
          class="form-control @error('hausnummer') is-invalid @enderror"
          value="{{ session('vies_hausnr', old('hausnummer', $adresse->hausnummer)) }}">
        @error('hausnummer') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      {{-- PLZ --}}
      <div class="col-4 col-md-2">
        <label for="plz" class="form-label small mb-1">PLZ</label>
        <input type="text" id="plz" name="plz"
          class="form-control @error('plz') is-invalid @enderror"
          value="{{ session('vies_plz', old('plz', $adresse->plz)) }}">
        @error('plz') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      {{-- Wohnort --}}
      <div class="col-8 col-md-5">
        <label for="wohnort" class="form-label small mb-1">Wohnort</label>
        <input type="text" id="wohnort" name="wohnort"
          class="form-control @error('wohnort') is-invalid @enderror"
          value="{{ session('vies_wohnort', old('wohnort', $adresse->wohnort)) }}">
        @error('wohnort') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      {{-- Provinz --}}
      <div class="col-4 col-md-2">
        <label for="provinz" class="form-label small mb-1">Provinz</label>
        <input type="text" id="provinz" name="provinz" maxlength="4"
          class="form-control text-uppercase @error('provinz') is-invalid @enderror"
          value="{{ session('vies_provinz', old('provinz', $adresse->provinz ?? 'BZ')) }}">
        @error('provinz') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      {{-- Land --}}
      <div class="col-8 col-md-3">
        <label for="land" class="form-label small mb-1">Land</label>
        <input type="text" id="land" name="land"
          class="form-control text-uppercase @error('land') is-invalid @enderror"
          value="{{ session('vies_land', old('land', $adresse->land ?? 'IT')) }}">
        @error('land') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
    </div>
  </div>
</div>

{{-- ═══════════════════════════════════════════════════════════ --}}
{{-- KONTAKT --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
<div class="card mb-3">
  <div class="card-header bg-info text-white py-2">
    <i class="bi bi-telephone-fill"></i>
    <span class="fw-semibold ms-1">Kontakt</span>
  </div>
  <div class="card-body p-2 p-md-3">
    <div class="row g-3">
      {{-- Telefon --}}
      <div class="col-6">
        <label for="telefon" class="form-label small mb-1">
          <i class="bi bi-telephone me-1"></i>Telefon
        </label>
        <input type="tel" id="telefon" name="telefon"
          class="form-control @error('telefon') is-invalid @enderror"
          value="{{ old('telefon', $adresse->telefon) }}">
        @error('telefon') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      {{-- Handy --}}
      <div class="col-6">
        <label for="handy" class="form-label small mb-1">
          <i class="bi bi-phone me-1"></i>Handy
        </label>
        <input type="tel" id="handy" name="handy"
          class="form-control @error('handy') is-invalid @enderror"
          value="{{ old('handy', $adresse->handy) }}">
        @error('handy') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      {{-- E-Mail --}}
      <div class="col-12 col-md-6">
        <label for="email" class="form-label small mb-1">
          <i class="bi bi-envelope me-1"></i>E-Mail
        </label>
        <input type="email" id="email" name="email"
          class="form-control @error('email') is-invalid @enderror"
          value="{{ old('email', $adresse->email) }}">
        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      {{-- Zweit-E-Mail --}}
      <div class="col-12 col-md-6">
        <label for="email_zweit" class="form-label small mb-1">
          <i class="bi bi-envelope me-1"></i>Zweit-E-Mail
        </label>
        <input type="email" id="email_zweit" name="email_zweit"
          class="form-control @error('email_zweit') is-invalid @enderror"
          value="{{ old('email_zweit', $adresse->email_zweit) }}">
        @error('email_zweit') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      {{-- PEC --}}
      <div class="col-12">
        <label for="pec" class="form-label small mb-1">
          <i class="bi bi-envelope-check me-1"></i>PEC (Zertifizierte E-Mail)
        </label>
        <input type="email" id="pec" name="pec"
          class="form-control @error('pec') is-invalid @enderror"
          value="{{ old('pec', $adresse->pec) }}">
        @error('pec') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
    </div>
  </div>
</div>

{{-- ═══════════════════════════════════════════════════════════ --}}
{{-- STEUERDATEN --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
<div class="card mb-3">
  <div class="card-header bg-warning py-2">
    <i class="bi bi-building"></i>
    <span class="fw-semibold ms-1">Steuerdaten</span>
  </div>
  <div class="card-body p-2 p-md-3">
    <div class="row g-3">
      {{-- Steuernummer --}}
      <div class="col-12 col-md-6">
        <label for="steuernummer" class="form-label small mb-1">Steuernummer (Codice Fiscale)</label>
        <input type="text" id="steuernummer" name="steuernummer"
          class="form-control text-uppercase @error('steuernummer') is-invalid @enderror"
          value="{{ old('steuernummer', $adresse->steuernummer) }}"
          maxlength="16">
        @error('steuernummer') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      {{-- MwSt-Nummer --}}
      <div class="col-12 col-md-6">
        <label for="mwst_nummer" class="form-label small mb-1">MwSt-Nr. (Partita IVA)</label>
        <div class="input-group">
          <span class="input-group-text">IT</span>
          <input type="text" id="mwst_nummer" name="mwst_nummer"
            class="form-control @error('mwst_nummer') is-invalid @enderror"
            value="{{ session('vies_mwst', old('mwst_nummer', $adresse->mwst_nummer)) }}"
            maxlength="11">
          @error('mwst_nummer') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
      </div>

      {{-- Codice Univoco (SDI) --}}
      <div class="col-12 col-md-6">
        <label for="codice_univoco" class="form-label small mb-1">Codice Univoco (SDI)</label>
        <input type="text" id="codice_univoco" name="codice_univoco"
          class="form-control text-uppercase font-monospace @error('codice_univoco') is-invalid @enderror"
          value="{{ old('codice_univoco', $adresse->codice_univoco ?? '0000000') }}"
          list="codiceUnivocoList" autocomplete="off"
          maxlength="7" style="letter-spacing: 2px;">
        @error('codice_univoco') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <datalist id="codiceUnivocoList">
          <option value="0000000">Privatkunde</option>
          <option value="XXXXXXX">Ausland</option>
          @foreach(($codiciUnivoci ?? []) as $c)
            <option value="{{ $c }}"></option>
          @endforeach
        </datalist>
        <div class="form-text small">7 Zeichen (0000000 = Privat, XXXXXXX = Ausland)</div>
      </div>
    </div>
  </div>
</div>

{{-- ═══════════════════════════════════════════════════════════ --}}
{{-- BEMERKUNG --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
<div class="card mb-3">
  <div class="card-header bg-secondary text-white py-2">
    <i class="bi bi-chat-text-fill"></i>
    <span class="fw-semibold ms-1">Notizen</span>
  </div>
  <div class="card-body p-2 p-md-3">
    <textarea id="bemerkung" name="bemerkung" rows="3"
      class="form-control @error('bemerkung') is-invalid @enderror"
      placeholder="Interne Bemerkungen...">{{ old('bemerkung', $adresse->bemerkung) }}</textarea>
    @error('bemerkung') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>
</div>

@push('styles')
<style>
@media (max-width: 767.98px) {
  .form-control, .form-select, .input-group-text { 
    min-height: 44px; 
    font-size: 16px !important; 
  }
}
</style>
@endpush
