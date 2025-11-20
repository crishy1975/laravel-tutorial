{{-- resources/views/rechnung/partials/_allgemein.blade.php --}}

@php
// Formular ist nur bearbeitbar, wenn Rechnung noch editierbar ist
$readonly = $rechnung->exists && !$rechnung->ist_editierbar;
@endphp

<div class="row g-4">

  {{-- INFO-CARDS: Gebäude (nur Anzeige) --}}
  <div class="col-xl-4 col-lg-6">
    <div class="card h-100 border-0 shadow-sm">
      <div class="card-header bg-secondary text-white">
        <h6 class="mb-0">
          <i class="bi bi-building"></i> Gebäude
        </h6>
      </div>
      <div class="card-body small">
        <div class="mb-1">
          <strong>Codex:</strong>
          {{ $rechnung->geb_codex ?: '-' }}
        </div>
        <div class="mb-1">
          <strong>Name:</strong>
          {{ $rechnung->geb_name ?: '-' }}
        </div>
        <div class="mb-1">
          <strong>Adresse:</strong>
          {{ $rechnung->geb_adresse ?: '-' }}
        </div>

        {{-- Gebäude-Link nur anzeigen, wenn eine passende Route existiert --}}
        @if($rechnung->gebaeude_id && Route::has('gebaeude.show'))
        <a href="{{ route('gebaeude.show', $rechnung->gebaeude_id) }}"
          class="btn btn-sm btn-outline-light mt-3">
          <i class="bi bi-box-arrow-up-right"></i> Gebäude öffnen
        </a>
        @endif
      </div>
    </div>
  </div>


  {{-- Adresse 1: Rechnungsempfänger (nur Info, editierbar im Tab "Adressen") --}}
  <div class="col-xl-4 col-lg-6">
    <div class="card h-100 border-0 shadow-sm">
      <div class="card-header bg-primary text-white">
        <h6 class="mb-0">
          <i class="bi bi-person-circle"></i> Rechnungsempfänger
        </h6>
      </div>
      <div class="card-body small">
        <address class="mb-0">
          <strong>{{ $rechnung->re_name ?: '(noch nicht angegeben)' }}</strong><br>
          @if($rechnung->re_strasse)
          {{ $rechnung->re_strasse }} {{ $rechnung->re_hausnummer }}<br>
          @endif
          @if($rechnung->re_plz || $rechnung->re_wohnort)
          {{ $rechnung->re_plz }} {{ $rechnung->re_wohnort }}
          @if($rechnung->re_provinz)
          ({{ $rechnung->re_provinz }})
          @endif
          <br>
          @endif
          @if($rechnung->re_land)
          {{ $rechnung->re_land }}<br>
          @endif
        </address>

        @if($rechnung->re_steuernummer || $rechnung->re_mwst_nummer || $rechnung->re_codice_univoco || $rechnung->re_pec)
        <hr class="my-3">
        <div class="small text-muted">
          @if($rechnung->re_steuernummer)
          <div><strong>Steuernr.:</strong> {{ $rechnung->re_steuernummer }}</div>
          @endif
          @if($rechnung->re_mwst_nummer)
          <div><strong>MwSt-Nr.:</strong> {{ $rechnung->re_mwst_nummer }}</div>
          @endif
          @if($rechnung->re_codice_univoco)
          <div><strong>Codice Univoco:</strong> {{ $rechnung->re_codice_univoco }}</div>
          @endif
          @if($rechnung->re_pec)
          <div><strong>PEC:</strong> {{ $rechnung->re_pec }}</div>
          @endif
        </div>
        @endif
      </div>
    </div>
  </div>

  {{-- Adresse 2: Postadresse --}}
  <div class="col-xl-4 col-lg-12">
    <div class="card h-100 border-0 shadow-sm">
      <div class="card-header bg-info text-white">
        <h6 class="mb-0">
          <i class="bi bi-envelope"></i> Postadresse
        </h6>
      </div>
      <div class="card-body small">
        <address class="mb-0">
          <strong>{{ $rechnung->post_name ?: '(noch nicht angegeben)' }}</strong><br>
          @if($rechnung->post_strasse)
          {{ $rechnung->post_strasse }} {{ $rechnung->post_hausnummer }}<br>
          @endif
          @if($rechnung->post_plz || $rechnung->post_wohnort)
          {{ $rechnung->post_plz }} {{ $rechnung->post_wohnort }}
          @if($rechnung->post_provinz)
          ({{ $rechnung->post_provinz }})
          @endif
          <br>
          @endif
          @if($rechnung->post_land)
          {{ $rechnung->post_land }}<br>
          @endif
        </address>

        @if($rechnung->post_email || $rechnung->post_pec)
        <hr class="my-3">
        <div class="small text-muted">
          @if($rechnung->post_email)
          <div><strong>E-Mail:</strong> {{ $rechnung->post_email }}</div>
          @endif
          @if($rechnung->post_pec)
          <div><strong>PEC:</strong> {{ $rechnung->post_pec }}</div>
          @endif
        </div>
        @endif
      </div>
    </div>
  </div>

  {{-- CARD: Rechnungsdaten + Typ + Status --}}
  <div class="col-xl-7">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-primary text-white">
        <h6 class="mb-0">
          <i class="bi bi-receipt-cutoff"></i> Rechnungsdaten
        </h6>
      </div>
      <div class="card-body">
        <div class="row g-3">

          {{-- Rechnungsnummer (readonly bei bestehender Rechnung) --}}
          @if($rechnung->exists)
          <div class="col-md-4">
            <div class="form-floating">
              <input type="text"
                class="form-control"
                value="{{ $rechnung->nummern }}"
                disabled>
              <label>Rechnungsnummer</label>
            </div>
          </div>
          @endif

          {{-- Rechnungsdatum --}}
          <div class="col-md-4">
            <div class="form-floating">
              <input type="date"
                name="rechnungsdatum"
                class="form-control @error('rechnungsdatum') is-invalid @enderror"
                value="{{ old('rechnungsdatum', $rechnung->rechnungsdatum?->toDateString() ?? now()->toDateString()) }}"
                {{ $readonly ? 'disabled' : '' }}
                required>
              <label>Rechnungsdatum *</label>
              @error('rechnungsdatum') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
          </div>

          {{-- Zahlungsziel --}}
          <div class="col-md-4">
            <div class="form-floating">
              <input type="date"
                name="zahlungsziel"
                class="form-control @error('zahlungsziel') is-invalid @enderror"
                value="{{ old('zahlungsziel', $rechnung->zahlungsziel?->toDateString()) }}"
                {{ $readonly ? 'disabled' : '' }}>
              <label>Zahlungsziel</label>
              @error('zahlungsziel') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
          </div>

          {{-- Leistungs- / Rechnungsdaten (als Textfeld, statt Leistungsdatum) --}}
          <div class="col-md-12">
            <div class="form-floating">
              <input type="text"
                name="rechnungsdaten"
                class="form-control @error('rechnungsdaten') is-invalid @enderror"
                value="{{ old('rechnungsdaten', $rechnung->rechnungsdaten ?? '') }}"
                {{ $readonly ? 'disabled' : '' }}>
              <label>Rechnungsdaten / Leistungsdaten (Text)</label>
              @error('rechnungsdaten') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
          </div>

          {{-- Typ Rechnung (Rechnung / Gutschrift) --}}
          <div class="col-md-4">
            <div class="form-floating">
              <select name="typ_rechnung"
                class="form-select @error('typ_rechnung') is-invalid @enderror"
                {{ $readonly ? 'disabled' : '' }}>
                @php
                $typ = old('typ_rechnung', $rechnung->typ_rechnung ?? 'rechnung');
                @endphp
                <option value="rechnung" {{ $typ === 'rechnung' ? 'selected' : '' }}>
                  Rechnung
                </option>
                <option value="gutschrift" {{ $typ === 'gutschrift' ? 'selected' : '' }}>
                  Gutschrift
                </option>
              </select>
              <label>Typ</label>
              @error('typ_rechnung') <div class="invalid-feedback">{{ $message }}</div> @enderror
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

          {{-- Bezahlt am --}}
          <div class="col-md-4">
            <div class="form-floating">
              <input type="date"
                name="bezahlt_am"
                class="form-control @error('bezahlt_am') is-invalid @enderror"
                value="{{ old('bezahlt_am', $rechnung->bezahlt_am?->toDateString()) }}"
                {{ $readonly ? 'disabled' : '' }}>
              <label>Bezahlt am</label>
              @error('bezahlt_am') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>

  {{-- CARD: Fattura-Profil + CUP/CIG/ID --}}
  <div class="col-xl-5">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-light">
        <h6 class="mb-0">
          <i class="bi bi-file-earmark-text"></i> Fattura & Auftrag
        </h6>
      </div>
      <div class="card-body">
        <div class="row g-3">

          {{-- Fattura-Profil --}}
          <div class="col-12">
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

            {{-- Kurze Erklärung zum gewählten Profil --}}
            @php
            $profil = $rechnung->fatturaProfile ?? null;
            @endphp
            <p class="small text-muted mt-2 mb-0">
              @if($profil)
              <i class="bi bi-info-circle"></i>
              {{ $profil->bezeichnung }} –
              MwSt: {{ number_format($profil->mwst_satz, 0) }}%.
              @if($profil->split_payment)
              <br><i class="bi bi-arrow-left-right"></i>
              Split Payment aktiv: MwSt wird separat abgeführt.
              @endif
              @if($profil->ritenuta)
              <br><i class="bi bi-percent"></i>
              Ritenuta d'acconto aktiv: Quellensteuer wird von der Zahlung einbehalten.
              @endif
              @else
              <i class="bi bi-info-circle"></i>
              Kein spezielles Profil gewählt – Standard-Verhalten (z. B. normale MwSt ohne Split/Ritenuta).
              @endif
            </p>
          </div>

          {{-- CUP --}}
          <div class="col-md-6">
            <div class="form-floating">
              <input type="text"
                name="cup"
                class="form-control @error('cup') is-invalid @enderror"
                value="{{ old('cup', $rechnung->cup) }}"
                maxlength="20"
                {{ $readonly ? 'disabled' : '' }}>
              <label>CUP</label>
              @error('cup') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
          </div>

          {{-- CIG --}}
          <div class="col-md-6">
            <div class="form-floating">
              <input type="text"
                name="cig"
                class="form-control @error('cig') is-invalid @enderror"
                value="{{ old('cig', $rechnung->cig) }}"
                maxlength="10"
                {{ $readonly ? 'disabled' : '' }}>
              <label>CIG</label>
              @error('cig') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
          </div>

          {{-- ID / Auftrags-ID --}}
          <div class="col-md-6">
            <div class="form-floating">
              <input type="text"
                name="auftrag_id"
                class="form-control @error('auftrag_id') is-invalid @enderror"
                value="{{ old('auftrag_id', $rechnung->auftrag_id) }}"
                maxlength="50"
                {{ $readonly ? 'disabled' : '' }}>
              <label>ID / Auftrags-ID</label>
              @error('auftrag_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
          </div>

          {{-- Auftrags-/Rechnungsdatum für FatturaPA (optional) --}}
          <div class="col-md-6">
            <div class="form-floating">
              <input type="date"
                name="auftrag_datum"
                class="form-control @error('auftrag_datum') is-invalid @enderror"
                value="{{ old('auftrag_datum', $rechnung->auftrag_datum?->toDateString()) }}"
                {{ $readonly ? 'disabled' : '' }}>
              <label>Auftrags-Datum</label>
              @error('auftrag_datum') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>

  {{-- CARD: Summen & Ritenuta + Bemerkungen --}}
  <div class="col-12">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-secondary text-white">
        <h6 class="mb-0">
          <i class="bi bi-calculator"></i> Summen & Bemerkungen
        </h6>
      </div>
      <div class="card-body">
        <div class="row g-3 align-items-center">

          {{-- Summen (readonly, werden automatisch berechnet) --}}
          <div class="col-md-3">
            <div class="form-floating">
              <input type="text"
                class="form-control"
                value="{{ number_format($rechnung->netto_summe ?? 0, 2, ',', '.') }}"
                disabled>
              <label>Netto-Summe (€)</label>
            </div>
          </div>

          <div class="col-md-3">
            <div class="form-floating">
              <input type="text"
                class="form-control"
                value="{{ number_format($rechnung->mwst_betrag ?? 0, 2, ',', '.') }}"
                disabled>
              <label>MwSt-Betrag (€)</label>
            </div>
          </div>

          <div class="col-md-3">
            <div class="form-floating">
              <input type="text"
                class="form-control"
                value="{{ number_format($rechnung->brutto_summe ?? 0, 2, ',', '.') }}"
                disabled>
              <label>Brutto-Summe (€)</label>
            </div>
          </div>

          <div class="col-md-3">
            <div class="form-floating">
              <input type="text"
                class="form-control fw-bold"
                value="{{ number_format($rechnung->zahlbar_betrag ?? 0, 2, ',', '.') }}"
                disabled>
              <label>Zahlbar (€)</label>
            </div>
          </div>

          {{-- Hinweis zu Ritenuta, falls aktiv --}}
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

          {{-- Interne Bemerkung --}}
          <div class="col-12">
            <div class="form-floating">
              <textarea name="bemerkung"
                class="form-control @error('bemerkung') is-invalid @enderror"
                style="height: 80px"
                {{ $readonly ? 'disabled' : '' }}>{{ old('bemerkung', $rechnung->bemerkung) }}</textarea>
              <label>Interne Bemerkung (nur intern)</label>
              @error('bemerkung') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
          </div>

          {{-- Bemerkung für Kunde --}}
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
      </div>
    </div>
  </div>

</div>