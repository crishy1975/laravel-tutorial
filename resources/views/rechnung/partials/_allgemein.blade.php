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

                    {{-- Rechnungsnummer (nur bei bestehender Rechnung anzeigen) --}}
                    @if($rechnung->exists)
                        <div class="col-md-4">
                            <div class="form-floating">
                                <input type="text"
                                       class="form-control"
                                       value="{{ $rechnung->rechnungsnummer }}"
                                       disabled>
                                <label>Rechnungsnummer</label>
                            </div>
                        </div>
                    @endif

                    {{-- Rechnungsdatum (immer readonly) --}}
                    <div class="col-md-4">
                        <div class="form-floating">
                            <input type="date"
                                   name="rechnungsdatum"
                                   class="form-control @error('rechnungsdatum') is-invalid @enderror"
                                   value="{{ old('rechnungsdatum', $rechnung->rechnungsdatum?->toDateString() ?? now()->toDateString()) }}"
                                   readonly
                                   required>
                            <label>Rechnungsdatum *</label>
                            @error('rechnungsdatum') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    {{-- Typ (Rechnung oder Gutschrift) --}}
                    <div class="col-md-4">
                        <div class="form-floating">
                            <select name="typ_rechnung"
                                    class="form-select @error('typ_rechnung') is-invalid @enderror"
                                    {{ $readonly ? 'disabled' : '' }}
                                    required>
                                <option value="rechnung" @selected(old('typ_rechnung', $rechnung->typ_rechnung ?? 'rechnung') === 'rechnung')>
                                    Rechnung
                                </option>
                                <option value="gutschrift" @selected(old('typ_rechnung', $rechnung->typ_rechnung) === 'gutschrift')>
                                    Gutschrift
                                </option>
                            </select>
                            <label>Typ *</label>
                            @error('typ_rechnung') <div class="invalid-feedback">{{ $message }}</div> @enderror
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

                    {{-- Leistungsdaten (breiter wegen mehrerer Daten) --}}
                    <div class="col-md-8">
                        <div class="form-floating">
                            <input type="text"
                                   name="leistungsdaten"
                                   class="form-control @error('leistungsdaten') is-invalid @enderror"
                                   value="{{ old('leistungsdaten', $rechnung->leistungsdaten) }}"
                                   {{ $readonly ? 'disabled' : '' }}
                                   placeholder="z.B. Jahr/anno 2025 oder 01.05.2025 - 15.05.2025 oder 01.05.2025, 05.05.2025, 10.05.2025">
                            <label>Leistungsdaten</label>
                            @error('leistungsdaten') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    {{-- Status (nur bei bestehender Rechnung) --}}
                    @if($rechnung->exists)
                        <div class="col-md-4">
                            <div class="form-floating">
                                <input type="text"
                                       class="form-control"
                                       value="{{ ucfirst($rechnung->status) }}"
                                       disabled>
                                <label>Status</label>
                            </div>
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>

    {{-- CARD: FatturaPA-Daten (CUP, CIG, usw.) --}}
    <div class="col-xl-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-warning">
                <h6 class="mb-0">
                    <i class="bi bi-file-earmark-text"></i> FatturaPA-Daten (optional)
                </h6>
            </div>
            <div class="card-body">
                <div class="row g-3">

                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="text"
                                   name="cup"
                                   class="form-control @error('cup') is-invalid @enderror"
                                   value="{{ old('cup', $rechnung->cup) }}"
                                   {{ $readonly ? 'disabled' : '' }}>
                            <label>CUP</label>
                            @error('cup') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="text"
                                   name="cig"
                                   class="form-control @error('cig') is-invalid @enderror"
                                   value="{{ old('cig', $rechnung->cig) }}"
                                   {{ $readonly ? 'disabled' : '' }}>
                            <label>CIG</label>
                            @error('cig') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="text"
                                   name="auftrag_id"
                                   class="form-control @error('auftrag_id') is-invalid @enderror"
                                   value="{{ old('auftrag_id', $rechnung->auftrag_id) }}"
                                   {{ $readonly ? 'disabled' : '' }}>
                            <label>Auftrags-ID</label>
                            @error('auftrag_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

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

    {{-- CARD: Optionen (Ritenuta, Split Payment) --}}
    <div class="col-xl-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-success text-white">
                <h6 class="mb-0">
                    <i class="bi bi-toggles"></i> Optionen
                </h6>
            </div>
            <div class="card-body">
                <div class="row g-3 align-items-center">

                    {{-- Ritenuta Checkbox --}}
                    <div class="col-md-3">
                        <div class="form-check">
                            <input type="hidden" name="ritenuta" value="0">
                            <input class="form-check-input"
                                   type="checkbox"
                                   name="ritenuta"
                                   id="ritenuta"
                                   value="1"
                                   {{ old('ritenuta', $rechnung->ritenuta) ? 'checked' : '' }}
                                   {{ $readonly ? 'disabled' : '' }}>
                            <label class="form-check-label" for="ritenuta">
                                <i class="bi bi-percent"></i> Ritenuta aktiv
                            </label>
                        </div>
                    </div>

                    {{-- Ritenuta Prozent --}}
                    <div class="col-md-3">
                        <div class="form-floating">
                            <input type="number"
                                   name="ritenuta_prozent"
                                   class="form-control @error('ritenuta_prozent') is-invalid @enderror"
                                   step="0.01"
                                   value="{{ old('ritenuta_prozent', $rechnung->ritenuta_prozent ?? 20.00) }}"
                                   {{ $readonly ? 'disabled' : '' }}>
                            <label>Ritenuta %</label>
                            @error('ritenuta_prozent') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    {{-- Split Payment --}}
                    <div class="col-md-3">
                        <div class="form-check">
                            <input type="hidden" name="split_payment" value="0">
                            <input class="form-check-input"
                                   type="checkbox"
                                   name="split_payment"
                                   id="split_payment"
                                   value="1"
                                   {{ old('split_payment', $rechnung->split_payment) ? 'checked' : '' }}
                                   {{ $readonly ? 'disabled' : '' }}>
                            <label class="form-check-label" for="split_payment">
                                <i class="bi bi-cash-stack"></i> Split Payment
                            </label>
                        </div>
                    </div>

                    {{-- MwSt-Satz --}}
                    <div class="col-md-3">
                        <div class="form-floating">
                            <input type="number"
                                   name="mwst_satz"
                                   class="form-control @error('mwst_satz') is-invalid @enderror"
                                   step="0.01"
                                   value="{{ old('mwst_satz', $rechnung->mwst_satz ?? 22.00) }}"
                                   {{ $readonly ? 'disabled' : '' }}
                                   required>
                            <label>MwSt-Satz (%) *</label>
                            @error('mwst_satz') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- CARD: Preisprofil (optional, falls implementiert) --}}
    @if(isset($preisprofile) && $preisprofile->isNotEmpty())
    <div class="col-xl-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-info text-white">
                <h6 class="mb-0">
                    <i class="bi bi-tag"></i> Preisprofil (optional)
                </h6>
            </div>
            <div class="card-body">
                <div class="form-floating">
                    <select name="preisprofil_id"
                            id="preisprofil_id"
                            class="form-select @error('preisprofil_id') is-invalid @enderror"
                            {{ $readonly ? 'disabled' : '' }}>
                        <option value="">- kein Preisprofil -</option>
                        @foreach($preisprofile as $profil)
                            <option value="{{ $profil->id }}"
                                    @selected(old('preisprofil_id', $rechnung->preisprofil_id) == $profil->id)
                                    data-info="{{ $profil->bezeichnung }}">
                                {{ $profil->bezeichnung }}
                            </option>
                        @endforeach
                    </select>
                    <label>Preisprofil</label>
                    @error('preisprofil_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div id="preisprofil-info" class="alert alert-light mt-3 mb-0 small" style="display:none;">
                    <strong>Profil:</strong> <span id="preisprofil-info-text">-</span>
                </div>
            </div>
        </div>
    </div>

    @verbatim
    <script>
        (function () {
            const select = document.getElementById('preisprofil_id');
            const info   = document.getElementById('preisprofil-info');
            const text   = document.getElementById('preisprofil-info-text');

            if (!select || !info || !text) return;

            function renderInfo(value) {
                if (!value) {
                    info.style.display = 'none';
                    return;
                }
                const opt = select.querySelector(`option[value="${value}"]`);
                const bezeichnung = opt ? opt.getAttribute('data-info') : '';
                text.textContent = bezeichnung || '-';
                info.style.display = 'block';
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
    @endif

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

    {{-- BUTTONS: Zurück zum Gebäude & Löschen --}}
    @if($rechnung->exists)
        <div class="col-12 d-flex justify-content-between align-items-center mt-3">
            <div>
                @if($rechnung->gebaeude_id && Route::has('gebaeude.edit'))
                    <a href="{{ route('gebaeude.edit', $rechnung->gebaeude_id) }}"
                       class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left-circle"></i> Zurück zum Gebäude
                    </a>
                @endif
            </div>
            <div>
                @if($rechnung->ist_editierbar)
                    <button type="button"
                            id="btn-delete-rechnung"
                            class="btn btn-outline-danger"
                            data-delete-url="{{ route('rechnung.destroy', $rechnung->id) }}"
                            data-redirect-url="{{ $rechnung->gebaeude_id && Route::has('gebaeude.edit')
                                ? route('gebaeude.edit', $rechnung->gebaeude_id)
                                : route('rechnung.index') }}">
                        <i class="bi bi-trash"></i> Rechnung löschen
                    </button>
                @endif
            </div>
        </div>
    @endif

</div>

<script>
    // JS für den Lösch-Button (ohne verschachtelte Formulare)
    document.addEventListener('DOMContentLoaded', function () {
        var btn = document.getElementById('btn-delete-rechnung');
        if (!btn) return;

        btn.addEventListener('click', function () {
            if (!confirm('Rechnung wirklich löschen? Dieser Vorgang kann nicht rückgängig gemacht werden.')) {
                return;
            }

            var url          = btn.getAttribute('data-delete-url');
            var redirectUrl  = btn.getAttribute('data-redirect-url');

            fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json, text/plain, */*',
                    'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
                },
                body: new URLSearchParams({ _method: 'DELETE' })
            })
            .then(function (resp) {
                if (resp.ok) {
                    window.location.href = redirectUrl;
                } else {
                    return resp.text().then(function (t) {
                        alert('Löschen fehlgeschlagen:\n' + t);
                    });
                }
            })
            .catch(function (err) {
                alert('Netzwerkfehler beim Löschen: ' + err);
            });
        });
    });
</script>