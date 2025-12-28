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

  {{-- ═══════════════════════════════════════════════════════════ --}}
  {{-- ⭐ NEU: Kontaktdaten --}}
  {{-- ═══════════════════════════════════════════════════════════ --}}
  
  <div class="col-12">
    <hr class="my-2">
    <small class="text-muted"><i class="bi bi-telephone me-1"></i>Kontaktdaten</small>
  </div>

  {{-- Telefon --}}
  <div class="col-6 col-md-4">
    <label for="telefon" class="form-label small mb-1">
      <i class="bi bi-telephone d-none d-sm-inline"></i> Telefon
    </label>
    <input type="tel" id="telefon" name="telefon"
      class="form-control @error('telefon') is-invalid @enderror"
      value="{{ old('telefon', $gebaeude->telefon ?? '') }}"
      placeholder="+39 0123 456789">
    @error('telefon') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>

  {{-- Handy --}}
  <div class="col-6 col-md-4">
    <label for="handy" class="form-label small mb-1">
      <i class="bi bi-phone d-none d-sm-inline"></i> Handy
    </label>
    <input type="tel" id="handy" name="handy"
      class="form-control @error('handy') is-invalid @enderror"
      value="{{ old('handy', $gebaeude->handy ?? '') }}"
      placeholder="+39 333 1234567">
    @error('handy') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>

  {{-- Email --}}
  <div class="col-12 col-md-4">
    <label for="email" class="form-label small mb-1">
      <i class="bi bi-envelope d-none d-sm-inline"></i> E-Mail
    </label>
    <input type="email" id="email" name="email"
      class="form-control @error('email') is-invalid @enderror"
      value="{{ old('email', $gebaeude->email ?? '') }}"
      placeholder="kontakt@beispiel.it">
    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>

  {{-- ═══════════════════════════════════════════════════════════ --}}
  {{-- ⭐ NEU: Adresse aus Gebäude erstellen --}}
  {{-- ═══════════════════════════════════════════════════════════ --}}

  @if(isset($gebaeude) && $gebaeude->exists && !$gebaeude->postadresse_id && !$gebaeude->rechnungsempfaenger_id)
    <div class="col-12">
      <div class="alert alert-info py-2 d-flex justify-content-between align-items-center">
        <span class="small">
          <i class="bi bi-info-circle me-1"></i>
          Keine Adresse zugewiesen. Adresse aus Gebäudedaten erstellen?
        </span>
        <form method="POST" action="{{ route('gebaeude.erstelleAdresse', $gebaeude) }}" class="d-inline mb-0">
          @csrf
          <button type="submit" class="btn btn-primary btn-sm" 
                  onclick="return confirm('Adresse aus Gebäudedaten erstellen und als Postadresse + Rechnungsempfänger setzen?')">
            <i class="bi bi-person-plus"></i> Adresse erstellen
          </button>
        </form>
      </div>
    </div>
  @endif

  {{-- ═══════════════════════════════════════════════════════════ --}}

  {{-- Bemerkung --}}
  <div class="col-12">
    <hr class="my-2">
    <label for="bemerkung" class="form-label small mb-1">
      <i class="bi bi-chat-left-text d-none d-sm-inline"></i> Bemerkung
    </label>
    <textarea id="bemerkung" name="bemerkung" rows="2"
      class="form-control @error('bemerkung') is-invalid @enderror">{{ old('bemerkung', $gebaeude->bemerkung ?? '') }}</textarea>
    @error('bemerkung') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>

</div>
