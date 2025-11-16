{{-- resources/views/rechnung/partials/_allgemein.blade.php --}}

@php
  $readonly = $rechnung->exists && !$rechnung->ist_editierbar;
@endphp

<div class="row g-3">

  {{-- Rechnungsnummer (readonly bei edit) --}}
  @if($rechnung->exists)
    <div class="col-md-3">
      <div class="form-floating">
        <input type="text" class="form-control" value="{{ $rechnung->nummern }}" disabled>
        <label>Rechnungsnummer</label>
      </div>
    </div>
  @endif

  {{-- Gebäude --}}
  <div class="col-md-{{ $rechnung->exists ? '9' : '12' }}">
    <div class="form-floating">
      <select name="gebaeude_id" 
              class="form-select @error('gebaeude_id') is-invalid @enderror"
              {{ $readonly ? 'disabled' : '' }}
              required>
        <option value="">-- Gebäude wählen --</option>
        @foreach($gebaeude_liste as $g)
          <option value="{{ $g->id }}" 
                  {{ old('gebaeude_id', $rechnung->gebaeude_id) == $g->id ? 'selected' : '' }}>
            {{ $g->codex }} - {{ $g->gebaeude_name }}
          </option>
        @endforeach
      </select>
      <label for="gebaeude_id">Gebäude *</label>
      @error('gebaeude_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
  </div>

  {{-- Rechnungsdatum --}}
  <div class="col-md-4">
    <div class="form-floating">
      <input type="date" name="rechnungsdatum" 
             class="form-control @error('rechnungsdatum') is-invalid @enderror"
             value="{{ old('rechnungsdatum', $rechnung->rechnungsdatum?->toDateString() ?? now()->toDateString()) }}"
             {{ $readonly ? 'disabled' : '' }}
             required>
      <label>Rechnungsdatum *</label>
      @error('rechnungsdatum') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
  </div>

  {{-- Leistungsdatum --}}
  <div class="col-md-4">
    <div class="form-floating">
      <input type="date" name="leistungsdatum" 
             class="form-control @error('leistungsdatum') is-invalid @enderror"
             value="{{ old('leistungsdatum', $rechnung->leistungsdatum?->toDateString()) }}"
             {{ $readonly ? 'disabled' : '' }}>
      <label>Leistungsdatum</label>
      @error('leistungsdatum') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
  </div>

  {{-- Zahlungsziel --}}
  <div class="col-md-4">
    <div class="form-floating">
      <input type="date" name="zahlungsziel" 
             class="form-control @error('zahlungsziel') is-invalid @enderror"
             value="{{ old('zahlungsziel', $rechnung->zahlungsziel?->toDateString()) }}"
             {{ $readonly ? 'disabled' : '' }}>
      <label>Zahlungsziel</label>
      @error('zahlungsziel') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
  </div>

  {{-- Status --}}
  <div class="col-md-4">
    <div class="form-floating">
      <select name="status" 
              class="form-select @error('status') is-invalid @enderror"
              {{ $readonly ? 'disabled' : '' }}>
        <option value="draft" {{ old('status', $rechnung->status) === 'draft' ? 'selected' : '' }}>
          Entwurf
        </option>
        <option value="sent" {{ old('status', $rechnung->status) === 'sent' ? 'selected' : '' }}>
          Versendet
        </option>
        <option value="paid" {{ old('status', $rechnung->status) === 'paid' ? 'selected' : '' }}>
          Bezahlt
        </option>
        <option value="overdue" {{ old('status', $rechnung->status) === 'overdue' ? 'selected' : '' }}>
          Überfällig
        </option>
        <option value="cancelled" {{ old('status', $rechnung->status) === 'cancelled' ? 'selected' : '' }}>
          Storniert
        </option>
      </select>
      <label>Status</label>
      @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
  </div>

  {{-- Bezahlt am (nur bei Status "paid") --}}
  <div class="col-md-4">
    <div class="form-floating">
      <input type="date" name="bezahlt_am" 
             class="form-control @error('bezahlt_am') is-invalid @enderror"
             value="{{ old('bezahlt_am', $rechnung->bezahlt_am?->toDateString()) }}"
             {{ $readonly ? 'disabled' : '' }}>
      <label>Bezahlt am</label>
      @error('bezahlt_am') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
  </div>

  {{-- Fattura Profile --}}
  <div class="col-md-4">
    <div class="form-floating">
      <select name="fattura_profile_id" 
              class="form-select @error('fattura_profile_id') is-invalid @enderror"
              {{ $readonly ? 'disabled' : '' }}>
        <option value="">-- Kein Profil --</option>
        @foreach($profile as $p)
          <option value="{{ $p->id }}" 
                  {{ old('fattura_profile_id', $rechnung->fattura_profile_id) == $p->id ? 'selected' : '' }}>
            {{ $p->bezeichnung }} ({{ $p->mwst_satz }}%)
          </option>
        @endforeach
      </select>
      <label>Fattura-Profil</label>
      @error('fattura_profile_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
  </div>

  {{-- Separator --}}
  <div class="col-12"><hr></div>

  {{-- FatturaPA Felder --}}
  <div class="col-md-6">
    <div class="form-floating">
      <input type="text" name="cup" 
             class="form-control @error('cup') is-invalid @enderror"
             value="{{ old('cup', $rechnung->cup) }}"
             maxlength="20"
             {{ $readonly ? 'disabled' : '' }}>
      <label>CUP</label>
      @error('cup') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
  </div>

  <div class="col-md-6">
    <div class="form-floating">
      <input type="text" name="cig" 
             class="form-control @error('cig') is-invalid @enderror"
             value="{{ old('cig', $rechnung->cig) }}"
             maxlength="10"
             {{ $readonly ? 'disabled' : '' }}>
      <label>CIG</label>
      @error('cig') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
  </div>

  <div class="col-md-6">
    <div class="form-floating">
      <input type="text" name="auftrag_id" 
             class="form-control @error('auftrag_id') is-invalid @enderror"
             value="{{ old('auftrag_id', $rechnung->auftrag_id) }}"
             maxlength="50"
             {{ $readonly ? 'disabled' : '' }}>
      <label>Auftrags-ID</label>
      @error('auftrag_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
  </div>

  <div class="col-md-6">
    <div class="form-floating">
      <input type="date" name="auftrag_datum" 
             class="form-control @error('auftrag_datum') is-invalid @enderror"
             value="{{ old('auftrag_datum', $rechnung->auftrag_datum?->toDateString()) }}"
             {{ $readonly ? 'disabled' : '' }}>
      <label>Auftrags-Datum</label>
      @error('auftrag_datum') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
  </div>

  {{-- Separator --}}
  <div class="col-12"><hr></div>

  {{-- Beträge (readonly, werden automatisch berechnet) --}}
  <div class="col-md-3">
    <div class="form-floating">
      <input type="text" class="form-control" 
             value="{{ number_format($rechnung->netto_summe ?? 0, 2, ',', '.') }}" disabled>
      <label>Netto-Summe (€)</label>
    </div>
  </div>

  <div class="col-md-3">
    <div class="form-floating">
      <input type="text" class="form-control" 
             value="{{ number_format($rechnung->mwst_betrag ?? 0, 2, ',', '.') }}" disabled>
      <label>MwSt-Betrag (€)</label>
    </div>
  </div>

  <div class="col-md-3">
    <div class="form-floating">
      <input type="text" class="form-control" 
             value="{{ number_format($rechnung->brutto_summe ?? 0, 2, ',', '.') }}" disabled>
      <label>Brutto-Summe (€)</label>
    </div>
  </div>

  <div class="col-md-3">
    <div class="form-floating">
      <input type="text" class="form-control fw-bold" 
             value="{{ number_format($rechnung->zahlbar_betrag ?? 0, 2, ',', '.') }}" disabled>
      <label>Zahlbar (€)</label>
    </div>
  </div>

  @if($rechnung->ritenuta && $rechnung->ritenuta_betrag > 0)
    <div class="col-md-6">
      <div class="alert alert-info py-2 mb-0">
        <i class="bi bi-info-circle"></i>
        <strong>Ritenuta:</strong> 
        {{ number_format($rechnung->ritenuta_betrag, 2, ',', '.') }} € 
        ({{ $rechnung->ritenuta_prozent }}% auf Netto)
      </div>
    </div>
  @endif

  {{-- Bemerkungen --}}
  <div class="col-12">
    <div class="form-floating">
      <textarea name="bemerkung" 
                class="form-control @error('bemerkung') is-invalid @enderror"
                style="height: 80px"
                {{ $readonly ? 'disabled' : '' }}>{{ old('bemerkung', $rechnung->bemerkung) }}</textarea>
      <label>Interne Bemerkung</label>
      @error('bemerkung') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
  </div>

  <div class="col-12">
    <div class="form-floating">
      <textarea name="bemerkung_kunde" 
                class="form-control @error('bemerkung_kunde') is-invalid @enderror"
                style="height: 80px"
                {{ $readonly ? 'disabled' : '' }}>{{ old('bemerkung_kunde', $rechnung->bemerkung_kunde) }}</textarea>
      <label>Bemerkung auf Rechnung (für Kunde sichtbar)</label>
      @error('bemerkung_kunde') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
  </div>

</div>