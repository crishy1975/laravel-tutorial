{{-- resources/views/rechnung/partials/_adressen.blade.php --}}

@php
  $readonly = $rechnung->exists && !$rechnung->ist_editierbar;
@endphp

<div class="row g-4">

  {{-- Rechnungsempfänger --}}
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header bg-light">
        <h6 class="mb-0"><i class="bi bi-person-circle"></i> Rechnungsempfänger</h6>
      </div>
      <div class="card-body">
        <div class="row g-3">
          
          <div class="col-12">
            <div class="form-floating">
              <input type="text" name="re_name" 
                     class="form-control @error('re_name') is-invalid @enderror"
                     value="{{ old('re_name', $rechnung->re_name) }}"
                     {{ $readonly ? 'disabled' : '' }}>
              <label>Name / Firma *</label>
              @error('re_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
          </div>

          <div class="col-md-8">
            <div class="form-floating">
              <input type="text" name="re_strasse" 
                     class="form-control @error('re_strasse') is-invalid @enderror"
                     value="{{ old('re_strasse', $rechnung->re_strasse) }}"
                     {{ $readonly ? 'disabled' : '' }}>
              <label>Straße</label>
              @error('re_strasse') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
          </div>

          <div class="col-md-4">
            <div class="form-floating">
              <input type="text" name="re_hausnummer" 
                     class="form-control @error('re_hausnummer') is-invalid @enderror"
                     value="{{ old('re_hausnummer', $rechnung->re_hausnummer) }}"
                     {{ $readonly ? 'disabled' : '' }}>
              <label>Nr.</label>
              @error('re_hausnummer') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
          </div>

          <div class="col-md-4">
            <div class="form-floating">
              <input type="text" name="re_plz" 
                     class="form-control @error('re_plz') is-invalid @enderror"
                     value="{{ old('re_plz', $rechnung->re_plz) }}"
                     {{ $readonly ? 'disabled' : '' }}>
              <label>PLZ</label>
              @error('re_plz') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
          </div>

          <div class="col-md-8">
            <div class="form-floating">
              <input type="text" name="re_wohnort" 
                     class="form-control @error('re_wohnort') is-invalid @enderror"
                     value="{{ old('re_wohnort', $rechnung->re_wohnort) }}"
                     {{ $readonly ? 'disabled' : '' }}>
              <label>Wohnort</label>
              @error('re_wohnort') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
          </div>

          <div class="col-md-6">
            <div class="form-floating">
              <input type="text" name="re_provinz" 
                     class="form-control @error('re_provinz') is-invalid @enderror"
                     value="{{ old('re_provinz', $rechnung->re_provinz) }}"
                     {{ $readonly ? 'disabled' : '' }}>
              <label>Provinz</label>
              @error('re_provinz') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
          </div>

          <div class="col-md-6">
            <div class="form-floating">
              <input type="text" name="re_land" 
                     class="form-control @error('re_land') is-invalid @enderror"
                     value="{{ old('re_land', $rechnung->re_land ?? 'IT') }}"
                     {{ $readonly ? 'disabled' : '' }}>
              <label>Land</label>
              @error('re_land') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
          </div>

          <div class="col-12"><hr class="my-2"></div>

          <div class="col-md-6">
            <div class="form-floating">
              <input type="text" name="re_steuernummer" 
                     class="form-control @error('re_steuernummer') is-invalid @enderror"
                     value="{{ old('re_steuernummer', $rechnung->re_steuernummer) }}"
                     {{ $readonly ? 'disabled' : '' }}>
              <label>Steuernummer (CF)</label>
              @error('re_steuernummer') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
          </div>

          <div class="col-md-6">
            <div class="form-floating">
              <input type="text" name="re_mwst_nummer" 
                     class="form-control @error('re_mwst_nummer') is-invalid @enderror"
                     value="{{ old('re_mwst_nummer', $rechnung->re_mwst_nummer) }}"
                     {{ $readonly ? 'disabled' : '' }}>
              <label>MwSt-Nr. (P.IVA)</label>
              @error('re_mwst_nummer') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
          </div>

          <div class="col-md-6">
            <div class="form-floating">
              <input type="text" name="re_codice_univoco" 
                     class="form-control @error('re_codice_univoco') is-invalid @enderror"
                     value="{{ old('re_codice_univoco', $rechnung->re_codice_univoco) }}"
                     maxlength="7"
                     {{ $readonly ? 'disabled' : '' }}>
              <label>Codice Univoco (SDI)</label>
              @error('re_codice_univoco') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
          </div>

          <div class="col-md-6">
            <div class="form-floating">
              <input type="email" name="re_pec" 
                     class="form-control @error('re_pec') is-invalid @enderror"
                     value="{{ old('re_pec', $rechnung->re_pec) }}"
                     {{ $readonly ? 'disabled' : '' }}>
              <label>PEC</label>
              @error('re_pec') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>

  {{-- Postadresse (Versand) --}}
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header bg-light">
        <h6 class="mb-0"><i class="bi bi-envelope"></i> Postadresse (Versand)</h6>
      </div>
      <div class="card-body">
        <div class="row g-3">
          
          <div class="col-12">
            <div class="form-floating">
              <input type="text" name="post_name" 
                     class="form-control @error('post_name') is-invalid @enderror"
                     value="{{ old('post_name', $rechnung->post_name) }}"
                     {{ $readonly ? 'disabled' : '' }}>
              <label>Name / Firma</label>
              @error('post_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
          </div>

          <div class="col-md-8">
            <div class="form-floating">
              <input type="text" name="post_strasse" 
                     class="form-control @error('post_strasse') is-invalid @enderror"
                     value="{{ old('post_strasse', $rechnung->post_strasse) }}"
                     {{ $readonly ? 'disabled' : '' }}>
              <label>Straße</label>
              @error('post_strasse') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
          </div>

          <div class="col-md-4">
            <div class="form-floating">
              <input type="text" name="post_hausnummer" 
                     class="form-control @error('post_hausnummer') is-invalid @enderror"
                     value="{{ old('post_hausnummer', $rechnung->post_hausnummer) }}"
                     {{ $readonly ? 'disabled' : '' }}>
              <label>Nr.</label>
              @error('post_hausnummer') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
          </div>

          <div class="col-md-4">
            <div class="form-floating">
              <input type="text" name="post_plz" 
                     class="form-control @error('post_plz') is-invalid @enderror"
                     value="{{ old('post_plz', $rechnung->post_plz) }}"
                     {{ $readonly ? 'disabled' : '' }}>
              <label>PLZ</label>
              @error('post_plz') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
          </div>

          <div class="col-md-8">
            <div class="form-floating">
              <input type="text" name="post_wohnort" 
                     class="form-control @error('post_wohnort') is-invalid @enderror"
                     value="{{ old('post_wohnort', $rechnung->post_wohnort) }}"
                     {{ $readonly ? 'disabled' : '' }}>
              <label>Wohnort</label>
              @error('post_wohnort') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
          </div>

          <div class="col-md-6">
            <div class="form-floating">
              <input type="text" name="post_provinz" 
                     class="form-control @error('post_provinz') is-invalid @enderror"
                     value="{{ old('post_provinz', $rechnung->post_provinz) }}"
                     {{ $readonly ? 'disabled' : '' }}>
              <label>Provinz</label>
              @error('post_provinz') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
          </div>

          <div class="col-md-6">
            <div class="form-floating">
              <input type="text" name="post_land" 
                     class="form-control @error('post_land') is-invalid @enderror"
                     value="{{ old('post_land', $rechnung->post_land ?? 'IT') }}"
                     {{ $readonly ? 'disabled' : '' }}>
              <label>Land</label>
              @error('post_land') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
          </div>

          <div class="col-12"><hr class="my-2"></div>

          <div class="col-md-6">
            <div class="form-floating">
              <input type="email" name="post_email" 
                     class="form-control @error('post_email') is-invalid @enderror"
                     value="{{ old('post_email', $rechnung->post_email) }}"
                     {{ $readonly ? 'disabled' : '' }}>
              <label>E-Mail</label>
              @error('post_email') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
          </div>

          <div class="col-md-6">
            <div class="form-floating">
              <input type="email" name="post_pec" 
                     class="form-control @error('post_pec') is-invalid @enderror"
                     value="{{ old('post_pec', $rechnung->post_pec) }}"
                     {{ $readonly ? 'disabled' : '' }}>
              <label>PEC</label>
              @error('post_pec') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>

  {{-- Gebäude-Snapshot --}}
  <div class="col-12">
    <div class="card">
      <div class="card-header bg-light">
        <h6 class="mb-0"><i class="bi bi-building"></i> Gebäude-Informationen</h6>
      </div>
      <div class="card-body">
        <div class="row g-3">
          
          <div class="col-md-4">
            <div class="form-floating">
              <input type="text" name="geb_codex" 
                     class="form-control @error('geb_codex') is-invalid @enderror"
                     value="{{ old('geb_codex', $rechnung->geb_codex) }}"
                     {{ $readonly ? 'disabled' : '' }}>
              <label>Codex</label>
              @error('geb_codex') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
          </div>

          <div class="col-md-4">
            <div class="form-floating">
              <input type="text" name="geb_name" 
                     class="form-control @error('geb_name') is-invalid @enderror"
                     value="{{ old('geb_name', $rechnung->geb_name) }}"
                     {{ $readonly ? 'disabled' : '' }}>
              <label>Gebäudename</label>
              @error('geb_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
          </div>

          <div class="col-md-4">
            <div class="form-floating">
              <input type="text" name="geb_adresse" 
                     class="form-control @error('geb_adresse') is-invalid @enderror"
                     value="{{ old('geb_adresse', $rechnung->geb_adresse) }}"
                     {{ $readonly ? 'disabled' : '' }}>
              <label>Adresse</label>
              @error('geb_adresse') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>

</div>