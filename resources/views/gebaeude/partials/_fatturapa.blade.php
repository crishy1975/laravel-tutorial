{{-- resources/views/gebaeude/partials/_fatturapa.blade.php --}}
{{-- MOBIL-OPTIMIERT --}}

@php
  $auftragDatumValue = old('auftrag_datum',
    isset($gebaeude->auftrag_datum) && $gebaeude->auftrag_datum
      ? \Illuminate\Support\Carbon::parse($gebaeude->auftrag_datum)->toDateString()
      : ''
  );
  $bemerkungBuchhaltung = old('bemerkung_buchhaltung', $gebaeude->bemerkung_buchhaltung ?? '');
  $cupVal = old('cup', $gebaeude->cup ?? '');
  $cigVal = old('cig', $gebaeude->cig ?? '');
  $codiceCommessaVal = old('codice_commessa', $gebaeude->codice_commessa ?? '');
  $auftragIdVal = old('auftrag_id', $gebaeude->auftrag_id ?? '');
  $bankMatchTplVal = old('bank_match_text_template', $gebaeude->bank_match_text_template ?? '');
  $fatturaProfileSel = (string) old('fattura_profile_id', $gebaeude->fattura_profile_id ?? '');
@endphp

<div class="row g-2 g-md-3">

  {{-- Buchhaltungs-Bemerkung --}}
  <div class="col-12">
    <label for="bemerkung_buchhaltung" class="form-label small mb-1">
      <i class="bi bi-journal-text"></i> Buchhaltungs-Bemerkung
    </label>
    <textarea id="bemerkung_buchhaltung" name="bemerkung_buchhaltung" rows="2"
      class="form-control @error('bemerkung_buchhaltung') is-invalid @enderror">{{ $bemerkungBuchhaltung }}</textarea>
    @error('bemerkung_buchhaltung') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>

  {{-- Fattura-Profil --}}
  <div class="col-12">
    <label for="fattura_profile_id" class="form-label small mb-1">
      <i class="bi bi-file-earmark-text"></i> FatturaPA-Profil
    </label>
    <select id="fattura_profile_id" name="fattura_profile_id"
      class="form-select @error('fattura_profile_id') is-invalid @enderror">
      <option value="">- Kein Profil -</option>
      @foreach(($fatturaProfiles ?? []) as $p)
        <option value="{{ $p->id }}" {{ $fatturaProfileSel === (string) $p->id ? 'selected' : '' }}>
          {{ $p->bezeichnung }}
        </option>
      @endforeach
    </select>
    @error('fattura_profile_id') <div class="invalid-feedback">{{ $message }}</div> @enderror

    <div id="fattura_profile_info" class="mt-2 d-flex flex-wrap gap-1"></div>

    <script type="application/json" id="fattura_profiles_data">
      {!! json_encode(
            ($fatturaProfiles ?? collect())->map(function($p){
              return [
                'id' => (string)$p->id,
                'bezeichnung' => $p->bezeichnung,
                'mwst_satz' => $p->mwst_satz,
                'split_payment' => (bool)$p->split_payment,
                'ritenuta' => (bool)$p->ritenuta,
              ];
            })->values(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
          )
      !!}
    </script>
  </div>

  {{-- CUP / CIG / Codice Commessa --}}
  <div class="col-4">
    <label for="cup" class="form-label small mb-1">CUP</label>
    <input type="text" id="cup" name="cup" maxlength="20"
      class="form-control @error('cup') is-invalid @enderror" value="{{ $cupVal }}">
    @error('cup') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>

  <div class="col-4">
    <label for="cig" class="form-label small mb-1">CIG</label>
    <input type="text" id="cig" name="cig" maxlength="10"
      class="form-control @error('cig') is-invalid @enderror" value="{{ $cigVal }}">
    @error('cig') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>

  <div class="col-4">
    <label for="codice_commessa" class="form-label small mb-1">Commessa</label>
    <input type="text" id="codice_commessa" name="codice_commessa" maxlength="100"
      class="form-control @error('codice_commessa') is-invalid @enderror" value="{{ $codiceCommessaVal }}">
    @error('codice_commessa') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>

  {{-- Auftrags-ID & Datum --}}
  <div class="col-8">
    <label for="auftrag_id" class="form-label small mb-1">
      <i class="bi bi-hash"></i> Auftrags-ID
    </label>
    <input type="text" id="auftrag_id" name="auftrag_id" maxlength="50"
      class="form-control @error('auftrag_id') is-invalid @enderror" value="{{ $auftragIdVal }}">
    @error('auftrag_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>

  <div class="col-4">
    <label for="auftrag_datum" class="form-label small mb-1">Datum</label>
    <input type="date" id="auftrag_datum" name="auftrag_datum"
      class="form-control @error('auftrag_datum') is-invalid @enderror" value="{{ $auftragDatumValue }}">
    @error('auftrag_datum') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>

  {{-- Bank-Erkennungstext --}}
  <div class="col-12">
    <label for="bank_match_text_template" class="form-label small mb-1">
      <i class="bi bi-bank"></i> Bank-Erkennungstext
    </label>
    <textarea id="bank_match_text_template" name="bank_match_text_template" rows="2"
      class="form-control font-monospace small @error('bank_match_text_template') is-invalid @enderror">{{ $bankMatchTplVal }}</textarea>
    @error('bank_match_text_template') <div class="invalid-feedback">{{ $message }}</div> @enderror
    <small class="text-muted">
      Platzhalter: <code>{invoice_number}</code>, <code>{invoice_year}</code>, <code>{building_codex}</code>
    </small>
  </div>

</div>

<script>
(function() {
  function formatPercent(val) {
    var n = Number(val);
    if (!isFinite(n)) return '-';
    return n.toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' %';
  }

  function jaNein(b) { return b ? 'Ja' : 'Nein'; }

  function badge(label, value, color) {
    return '<span class="badge bg-' + (color || 'secondary') + ' small">' + label + ': ' + value + '</span>';
  }

  var select = document.getElementById('fattura_profile_id');
  var infoLine = document.getElementById('fattura_profile_info');
  var dataEl = document.getElementById('fattura_profiles_data');

  if (!select || !infoLine || !dataEl) return;

  var profiles = [];
  try { profiles = JSON.parse(dataEl.textContent || '[]'); } catch(e) {}

  var byId = {};
  profiles.forEach(function(p) { byId[String(p.id)] = p; });

  function renderInfo(profileId) {
    var p = byId[String(profileId)];
    if (!p) {
      infoLine.innerHTML = '<small class="text-muted">Kein Profil</small>';
      return;
    }
    var badges = [];
    badges.push(badge('MwSt', formatPercent(p.mwst_satz), 'primary'));
    badges.push(badge('Split', jaNein(p.split_payment), p.split_payment ? 'warning' : 'secondary'));
    badges.push(badge('Ritenuta', jaNein(p.ritenuta), p.ritenuta ? 'danger' : 'secondary'));
    infoLine.innerHTML = badges.join(' ');
  }

  renderInfo(select.value);
  select.addEventListener('change', function(e) { renderInfo(e.target.value); });
})();
</script>
