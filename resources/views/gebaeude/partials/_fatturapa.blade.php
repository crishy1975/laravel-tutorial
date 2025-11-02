{{-- resources/views/gebaeude/partials/_fatturapa.blade.php --}}
{{-- Dieses Partial liegt IM Hauptformular (create/edit) – KEIN eigenes <form> hier! --}}

@php
  // Sicheres Datum (funktioniert bei Carbon, DateTime oder String)
  $auftragDatumValue = old('auftrag_datum',
    isset($gebaeude->auftrag_datum) && $gebaeude->auftrag_datum
      ? \Illuminate\Support\Carbon::parse($gebaeude->auftrag_datum)->toDateString()
      : ''
  );

  // Fallbacks für create()
  $bemerkungBuchhaltung = old('bemerkung_buchhaltung', $gebaeude->bemerkung_buchhaltung ?? '');
  $cupVal                = old('cup', $gebaeude->cup ?? '');
  $cigVal                = old('cig', $gebaeude->cig ?? '');
  $auftragIdVal          = old('auftrag_id', $gebaeude->auftrag_id ?? '');
  $bankMatchTplVal       = old('bank_match_text_template', $gebaeude->bank_match_text_template ?? '');
  $fatturaProfileSel     = (string) old('fattura_profile_id', $gebaeude->fattura_profile_id ?? '');
@endphp

<div class="row g-3">

  {{-- Buchhaltungs-Bemerkung (intern) --}}
  <div class="col-12">
    <div class="form-floating">
      <textarea
        class="form-control @error('bemerkung_buchhaltung') is-invalid @enderror"
        id="bemerkung_buchhaltung"
        name="bemerkung_buchhaltung"
        placeholder=" "
        style="height: 110px">{{ $bemerkungBuchhaltung }}</textarea>
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
            {{ $fatturaProfileSel === (string) $p->id ? 'selected' : '' }}>
            {{ $p->bezeichnung }}
          </option>
        @endforeach
      </select>
      <label for="fattura_profile_id">Fattura-Profil</label>
      @error('fattura_profile_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    {{-- 🔎 Dynamische Infozeile zum gewählten Profil --}}
    <div id="fattura_profile_info" class="form-text mt-1">
      {{-- Wird per JS befüllt --}}
    </div>

    {{-- 🔒 Datenquelle für JS (sauber serialisiert, keine Inline-Objekte im DOM) --}}
    <script type="application/json" id="fattura_profiles_data">
      {!! json_encode(
            ($fatturaProfiles ?? collect())->map(function($p){
              return [
                'id'            => (string)$p->id,
                'bezeichnung'   => $p->bezeichnung,
                'mwst_satz'     => $p->mwst_satz,     // Zahl oder String, wird im JS formatiert
                'split_payment' => (bool)$p->split_payment,
                'ritenuta'      => (bool)$p->ritenuta,
              ];
            })->values(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
          )
      !!}
    </script>
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
        value="{{ $cupVal }}">
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
        value="{{ $cigVal }}">
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
        value="{{ $auftragIdVal }}">
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
        value="{{ $auftragDatumValue }}">
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
        style="height: 90px">{{ $bankMatchTplVal }}</textarea>
      <label for="bank_match_text_template">Bank-Erkennungstext (Template)</label>
      @error('bank_match_text_template') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="form-text mt-1">
      Platzhalter: <code>{invoice_number}</code>, <code>{invoice_year}</code>, <code>{building_codex}</code>, <code>{building_name}</code>
    </div>
  </div>

</div>

@verbatim
<script>
(function () {
  // Hilfsformatierer: Zahl → "22,00 %" (de-DE)
  function formatPercent(val) {
    var n = Number(val);
    if (!isFinite(n)) return '–';
    return n.toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' %';
  }

  // "Ja/Nein" aus Boolean
  function jaNein(b) { return b ? 'Ja' : 'Nein'; }

  // DOM-Elemente
  var select   = document.getElementById('fattura_profile_id');
  var infoLine = document.getElementById('fattura_profile_info');
  var dataEl   = document.getElementById('fattura_profiles_data');

  // Ohne Daten nicht fortfahren (robust in create/edit)
  if (!select || !infoLine || !dataEl) return;

  // JSON aus dem Script-Tag laden
  var profiles = [];
  try {
    profiles = JSON.parse(dataEl.textContent || '[]');
  } catch (e) {
    profiles = [];
  }

  // Index per ID für O(1)-Lookup
  var byId = {};
  profiles.forEach(function (p) { byId[String(p.id)] = p; });

  // Renderer für die Infozeile
  function renderInfo(profileId) {
    var p = byId[String(profileId)];
    if (!p) {
      infoLine.innerHTML = 'Kein Profil ausgewählt.';
      return;
    }

    // Text kompakt + eindeutig
    var parts = [];
    parts.push('<strong>' + (p.bezeichnung || 'Profil') + '</strong>');
    parts.push('MwSt: ' + formatPercent(p.mwst_satz));
    parts.push('Split Payment: ' + jaNein(!!p.split_payment));
    parts.push('Ritenuta: ' + jaNein(!!p.ritenuta));

    infoLine.innerHTML = parts.join(' &nbsp;•&nbsp; ');
  }

  // Initial befüllen (bei edit mit vorausgewähltem Profil)
  renderInfo(select.value);

  // Live-Update bei Änderung
  select.addEventListener('change', function (e) {
    renderInfo(e.target.value);
  });
})();
</script>
@endverbatim
