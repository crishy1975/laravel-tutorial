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
  $codiceCommessaVal     = old('codice_commessa', $gebaeude->codice_commessa ?? '');  // ⭐ NEU
  $auftragIdVal          = old('auftrag_id', $gebaeude->auftrag_id ?? '');
  $bankMatchTplVal       = old('bank_match_text_template', $gebaeude->bank_match_text_template ?? '');
  $fatturaProfileSel     = (string) old('fattura_profile_id', $gebaeude->fattura_profile_id ?? '');
@endphp

<div class="row g-3">

  {{-- 📋 Buchhaltungs-Bemerkung (kompakter) --}}
  <div class="col-12">
    <label for="bemerkung_buchhaltung" class="form-label small mb-1 fw-semibold">
      <i class="bi bi-journal-text"></i> Buchhaltungs-Bemerkung
    </label>
    <textarea
      class="form-control form-control-sm @error('bemerkung_buchhaltung') is-invalid @enderror"
      id="bemerkung_buchhaltung"
      name="bemerkung_buchhaltung"
      rows="3">{{ $bemerkungBuchhaltung }}</textarea>
    @error('bemerkung_buchhaltung') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>

  {{-- 🧾 Fattura-Profil mit Live-Info --}}
  <div class="col-md-12">
    <label for="fattura_profile_id" class="form-label small mb-1 fw-semibold">
      <i class="bi bi-file-earmark-text"></i> FatturaPA-Profil
    </label>
    <select
      class="form-select form-select-sm @error('fattura_profile_id') is-invalid @enderror"
      id="fattura_profile_id"
      name="fattura_profile_id">
      <option value="">— Kein Profil —</option>
      @foreach(($fatturaProfiles ?? []) as $p)
        <option value="{{ $p->id }}"
          {{ $fatturaProfileSel === (string) $p->id ? 'selected' : '' }}>
          {{ $p->bezeichnung }}
        </option>
      @endforeach
    </select>
    @error('fattura_profile_id') <div class="invalid-feedback">{{ $message }}</div> @enderror

    {{-- 📊 Live-Info als Badge-Leiste (kompakt & modern) --}}
    <div id="fattura_profile_info" class="mt-2 d-flex flex-wrap gap-2">
      {{-- Wird per JS befüllt --}}
    </div>

    {{-- 🔒 Datenquelle für JS --}}
    <script type="application/json" id="fattura_profiles_data">
      {!! json_encode(
            ($fatturaProfiles ?? collect())->map(function($p){
              return [
                'id'            => (string)$p->id,
                'bezeichnung'   => $p->bezeichnung,
                'mwst_satz'     => $p->mwst_satz,
                'split_payment' => (bool)$p->split_payment,
                'ritenuta'      => (bool)$p->ritenuta,
              ];
            })->values(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
          )
      !!}
    </script>
  </div>

  {{-- 🏛️ CUP / CIG / Codice Commessa (3 Spalten) --}}
  <div class="col-md-4">
    <label for="cup" class="form-label small mb-1 fw-semibold">
      <i class="bi bi-building-gear"></i> CUP
    </label>
    <input
      type="text"
      class="form-control form-control-sm @error('cup') is-invalid @enderror"
      id="cup"
      name="cup"
      maxlength="20"
      value="{{ $cupVal }}">
    @error('cup') <div class="invalid-feedback">{{ $message }}</div> @enderror
    <small class="text-muted">Codice Unico Progetto</small>
  </div>

  <div class="col-md-4">
    <label for="cig" class="form-label small mb-1 fw-semibold">
      <i class="bi bi-building-check"></i> CIG
    </label>
    <input
      type="text"
      class="form-control form-control-sm @error('cig') is-invalid @enderror"
      id="cig"
      name="cig"
      maxlength="10"
      value="{{ $cigVal }}">
    @error('cig') <div class="invalid-feedback">{{ $message }}</div> @enderror
    <small class="text-muted">Codice Identificativo Gara</small>
  </div>

  <div class="col-md-4">
    <label for="codice_commessa" class="form-label small mb-1 fw-semibold">
      <i class="bi bi-clipboard-check"></i> Codice Commessa
    </label>
    <input
      type="text"
      class="form-control form-control-sm @error('codice_commessa') is-invalid @enderror"
      id="codice_commessa"
      name="codice_commessa"
      maxlength="100"
      value="{{ $codiceCommessaVal }}">
    @error('codice_commessa') <div class="invalid-feedback">{{ $message }}</div> @enderror
    <small class="text-muted">Commessa/Convenzione</small>
  </div>

  {{-- 📝 Auftrags-ID & Datum --}}
  <div class="col-md-8">
    <label for="auftrag_id" class="form-label small mb-1 fw-semibold">
      <i class="bi bi-hash"></i> Auftrags-ID
    </label>
    <input
      type="text"
      class="form-control form-control-sm @error('auftrag_id') is-invalid @enderror"
      id="auftrag_id"
      name="auftrag_id"
      maxlength="50"
      value="{{ $auftragIdVal }}">
    @error('auftrag_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>

  <div class="col-md-4">
    <label for="auftrag_datum" class="form-label small mb-1 fw-semibold">
      <i class="bi bi-calendar-event"></i> Auftrags-Datum
    </label>
    <input
      type="date"
      class="form-control form-control-sm @error('auftrag_datum') is-invalid @enderror"
      id="auftrag_datum"
      name="auftrag_datum"
      value="{{ $auftragDatumValue }}">
    @error('auftrag_datum') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>

  {{-- 🏦 Bank-Erkennungstext (Template) --}}
  <div class="col-12">
    <label for="bank_match_text_template" class="form-label small mb-1 fw-semibold">
      <i class="bi bi-bank"></i> Bank-Erkennungstext (Template)
    </label>
    <textarea
      class="form-control form-control-sm font-monospace @error('bank_match_text_template') is-invalid @enderror"
      id="bank_match_text_template"
      name="bank_match_text_template"
      rows="2">{{ $bankMatchTplVal }}</textarea>
    @error('bank_match_text_template') <div class="invalid-feedback">{{ $message }}</div> @enderror
    <small class="text-muted">
      <i class="bi bi-info-circle"></i> 
      Platzhalter: <code>{invoice_number}</code>, <code>{invoice_year}</code>, <code>{building_codex}</code>, <code>{building_name}</code>
    </small>
  </div>

</div>

@verbatim
<script>
(function () {
  // 🎨 Hilfsformatierer: Zahl → "22,00 %" (de-DE)
  function formatPercent(val) {
    var n = Number(val);
    if (!isFinite(n)) return '—';
    return n.toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' %';
  }

  // ✅ "Ja/Nein" aus Boolean
  function jaNein(b) { return b ? 'Ja' : 'Nein'; }

  // 🎯 Badge-Helper
  function badge(label, value, color) {
    color = color || 'secondary';
    return '<span class="badge bg-' + color + '">' + label + ': ' + value + '</span>';
  }

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

  // 🎨 Renderer für die Infozeile (moderne Badge-Leiste)
  function renderInfo(profileId) {
    var p = byId[String(profileId)];
    if (!p) {
      infoLine.innerHTML = '<small class="text-muted">Kein Profil ausgewählt</small>';
      return;
    }

    // Badges mit Farben
    var badges = [];
    badges.push(badge('MwSt', formatPercent(p.mwst_satz), 'primary'));
    badges.push(badge('Split Payment', jaNein(!!p.split_payment), p.split_payment ? 'warning' : 'secondary'));
    badges.push(badge('Ritenuta', jaNein(!!p.ritenuta), p.ritenuta ? 'danger' : 'secondary'));

    infoLine.innerHTML = badges.join(' ');
  }

  // Initial befüllen (bei edit mit vorausgewähltem Profil)
  renderInfo(select.value);

  // 🔄 Live-Update bei Änderung
  select.addEventListener('change', function (e) {
    renderInfo(e.target.value);
  });
})();
</script>
@endverbatim