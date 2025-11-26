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

    {{-- CARD: Rechnungsdaten + Typ + Status (KOMPAKT) --}}
    <div class="col-xl-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-primary text-white py-2">
                <h6 class="mb-0"><i class="bi bi-receipt-cutoff"></i> Rechnungsdaten</h6>
            </div>
            <div class="card-body p-3">
                <div class="row g-2">

                    {{-- Erste Zeile: Rechnungsnummer / Datum / Typ --}}
                    @if($rechnung->exists)
                        <div class="col-md-4 col-6">
                            <label class="form-label small mb-1">Rechnungsnummer</label>
                            <input type="text" class="form-control form-control-sm" value="{{ $rechnung->rechnungsnummer }}" disabled>
                        </div>
                    @endif

                    <div class="col-md-4 col-6">
                        <label class="form-label small mb-1">Rechnungsdatum *</label>
                        <input type="date" name="rechnungsdatum" id="rechnungsdatum"
                               class="form-control form-control-sm @error('rechnungsdatum') is-invalid @enderror"
                               value="{{ old('rechnungsdatum', $rechnung->rechnungsdatum?->toDateString() ?? now()->toDateString()) }}"
                               readonly required>
                        @error('rechnungsdatum') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4 col-6">
                        <label class="form-label small mb-1">Typ *</label>
                        <select name="typ_rechnung" class="form-select form-select-sm @error('typ_rechnung') is-invalid @enderror"
                                {{ $readonly ? 'disabled' : '' }} required>
                            <option value="rechnung" {{ old('typ_rechnung', $rechnung->typ_rechnung) === 'rechnung' ? 'selected' : '' }}>Rechnung</option>
                            <option value="gutschrift" {{ old('typ_rechnung', $rechnung->typ_rechnung) === 'gutschrift' ? 'selected' : '' }}>Gutschrift</option>
                        </select>
                        @error('typ_rechnung') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Zweite Zeile: Zahlungsbedingungen / Zahlungsziel / Status --}}
                    <div class="col-md-4 col-6">
                        <label class="form-label small mb-1">Zahlungsbedingungen</label>
                        <select name="zahlungsbedingungen" id="zahlungsbedingungen"
                                class="form-select form-select-sm @error('zahlungsbedingungen') is-invalid @enderror"
                                {{ $readonly ? 'disabled' : '' }}>
                            <option value="">-- Bitte wählen --</option>
                            @foreach($zahlungsbedingungen as $value => $label)
                                <option value="{{ $value }}" 
                                    data-tage="{{ \App\Enums\Zahlungsbedingung::from($value)->tage() }}"
                                    {{ old('zahlungsbedingungen', $rechnung->zahlungsbedingungen?->value) == $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('zahlungsbedingungen') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4 col-6">
                        <label class="form-label small mb-1">
                            Zahlungsziel <small class="text-muted">(auto)</small>
                        </label>
                        <input type="date" name="zahlungsziel" id="zahlungsziel"
                               class="form-control form-control-sm @error('zahlungsziel') is-invalid @enderror"
                               value="{{ old('zahlungsziel', $rechnung->zahlungsziel?->toDateString()) }}"
                               {{ $readonly ? 'disabled' : '' }}>
                        @error('zahlungsziel') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    @if($rechnung->exists)
                        <div class="col-md-4 col-6">
                            <label class="form-label small mb-1">Status</label>
                            <input type="text" class="form-control form-control-sm" value="{{ $rechnung->status }}" disabled>
                        </div>
                    @else
                        <div class="col-md-4 col-6"></div>
                    @endif

                    {{-- Dritte Zeile: Leistungsdaten / Bezahlt am --}}
                    <div class="col-md-8">
                        <label class="form-label small mb-1">Leistungsdaten</label>
                        <input type="text" name="leistungsdaten"
                               class="form-control form-control-sm @error('leistungsdaten') is-invalid @enderror"
                               value="{{ old('leistungsdaten', $rechnung->leistungsdaten) }}"
                               {{ $readonly ? 'disabled' : '' }}
                               placeholder="z.B. Jahr/anno 2025 oder 01.05.2025 - 15.05.2025">
                        @error('leistungsdaten') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    @if($rechnung->exists)
                        <div class="col-md-4">
                            <label class="form-label small mb-1">Bezahlt am</label>
                            <input type="date" name="bezahlt_am"
                                   class="form-control form-control-sm @error('bezahlt_am') is-invalid @enderror"
                                   value="{{ old('bezahlt_am', $rechnung->bezahlt_am?->toDateString()) }}"
                                   {{ $readonly ? 'disabled' : '' }}>
                            @error('bezahlt_am') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    @endif

                    {{-- ⭐ Status-Badges in einer kompakten Zeile --}}
                    @if($rechnung->exists && ($rechnung->zahlungsbedingungen || $rechnung->faelligkeitsdatum))
                        <div class="col-12">
                            <div class="d-flex gap-2 flex-wrap align-items-center mt-1">
                                @if($rechnung->zahlungsbedingungen)
                                    {!! $rechnung->zahlungsbedingungen_badge !!}
                                    @if($rechnung->faelligkeitsdatum)
                                        <small class="text-muted">Fällig: {{ $rechnung->faelligkeitsdatum->format('d.m.Y') }}</small>
                                    @endif
                                @endif
                                @if($rechnung->zahlungsbedingungen && $rechnung->zahlungsbedingungen->value !== 'bezahlt')
                                    {!! $rechnung->faelligkeits_status_badge !!}
                                @endif
                                @if($rechnung->status !== 'paid' && !$rechnung->istAlsBezahltMarkiert())
                                    <button type="button" id="btn-mark-bezahlt" class="btn btn-success btn-sm ms-auto"
                                            data-mark-url="{{ route('rechnung.mark-bezahlt', $rechnung->id) }}">
                                        <i class="bi bi-check-circle"></i> Als bezahlt markieren
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>

    {{-- CARD: FatturaPA-Profil + Daten (KOMPAKT) --}}
    <div class="col-xl-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-warning py-2">
                <h6 class="mb-0"><i class="bi bi-file-earmark-text"></i> FatturaPA-Profil & Daten</h6>
            </div>
            <div class="card-body p-3">
                <div class="row g-2">

                    {{-- FatturaPA-Profil Auswahl --}}
                    <div class="col-12">
                        <label class="form-label small mb-1">FatturaPA-Profil</label>
                        <select name="fattura_profile_id" id="fattura_profile_id"
                                class="form-select form-select-sm @error('fattura_profile_id') is-invalid @enderror"
                                {{ $readonly ? 'disabled' : '' }}>
                            <option value="">-- Kein Profil --</option>
                            @if(isset($profile) && $profile->isNotEmpty())
                                @foreach($profile as $prof)
                                    <option value="{{ $prof->id }}"
                                        {{ old('fattura_profile_id', $rechnung->fattura_profile_id) == $prof->id ? 'selected' : '' }}
                                        data-bezeichnung="{{ $prof->bezeichnung }}"
                                        data-mwst="{{ $prof->mwst_satz }}"
                                        data-split="{{ $prof->split_payment ? 1 : 0 }}"
                                        data-ritenuta="{{ $prof->ritenuta ? 1 : 0 }}"
                                        data-ritenuta-prozent="{{ $prof->ritenuta_prozent }}">
                                        {{ $prof->bezeichnung }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                        @error('fattura_profile_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Profil-Info kompakt (inline badges) --}}
                    <div class="col-12">
                        <div id="profil-info" class="d-flex gap-2 flex-wrap" style="display: none !important;">
                            <small class="badge bg-light text-dark border">
                                <strong>MwSt:</strong> <span id="info-mwst">-</span>%
                            </small>
                            <small class="badge bg-light text-dark border">
                                <strong>Split:</strong> <span id="info-split">-</span>
                            </small>
                            <small class="badge bg-light text-dark border">
                                <strong>Ritenuta:</strong> <span id="info-ritenuta">-</span>
                            </small>
                        </div>
                    </div>

                    {{-- CUP / CIG / Codice Commessa --}}
                    <div class="col-4">
                        <label class="form-label small mb-1">CUP</label>
                        <input type="text" name="cup"
                               class="form-control form-control-sm @error('cup') is-invalid @enderror"
                               value="{{ old('cup', $rechnung->cup) }}"
                               maxlength="20"
                               {{ $readonly ? 'disabled' : '' }}>
                        @error('cup') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-4">
                        <label class="form-label small mb-1">CIG</label>
                        <input type="text" name="cig"
                               class="form-control form-control-sm @error('cig') is-invalid @enderror"
                               value="{{ old('cig', $rechnung->cig) }}"
                               maxlength="10"
                               {{ $readonly ? 'disabled' : '' }}>
                        @error('cig') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-4">
                        <label class="form-label small mb-1">Codice Commessa</label>
                        <input type="text" name="codice_commessa"
                               class="form-control form-control-sm @error('codice_commessa') is-invalid @enderror"
                               value="{{ old('codice_commessa', $rechnung->codice_commessa) }}"
                               maxlength="100"
                               {{ $readonly ? 'disabled' : '' }}>
                        @error('codice_commessa') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Auftrags-ID / Auftrags-Datum --}}
                    <div class="col-6">
                        <label class="form-label small mb-1">Auftrags-ID</label>
                        <input type="text" name="auftrag_id"
                               class="form-control form-control-sm @error('auftrag_id') is-invalid @enderror"
                               value="{{ old('auftrag_id', $rechnung->auftrag_id) }}"
                               maxlength="50"
                               {{ $readonly ? 'disabled' : '' }}>
                        @error('auftrag_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-6">
                        <label class="form-label small mb-1">Auftrags-Datum</label>
                        <input type="date" name="auftrag_datum"
                               class="form-control form-control-sm @error('auftrag_datum') is-invalid @enderror"
                               value="{{ old('auftrag_datum', $rechnung->auftrag_datum?->toDateString()) }}"
                               {{ $readonly ? 'disabled' : '' }}>
                        @error('auftrag_datum') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- JavaScript für Profil-Info und automatische Zahlungsziel-Berechnung --}}
    @if(!$readonly && isset($profile) && $profile->isNotEmpty())
    @verbatim
    <script>
        // FatturaPA-Profil Info anzeigen
        (function () {
            const select = document.getElementById('fattura_profile_id');
            const info = document.getElementById('profil-info');
            const mwstSpan = document.getElementById('info-mwst');
            const splitSpan = document.getElementById('info-split');
            const ritenutaSpan = document.getElementById('info-ritenuta');

            if (!select || !info) return;

            function renderInfo(profileId) {
                const option = select.querySelector(`option[value="${profileId}"]`);
                if (!profileId || !option) {
                    info.style.display = 'none';
                    return;
                }

                mwstSpan.textContent = option.dataset.mwst || '-';
                splitSpan.textContent = option.dataset.split === '1' ? 'Ja' : 'Nein';
                ritenutaSpan.textContent = option.dataset.ritenuta === '1' 
                    ? `Ja (${option.dataset.ritenutaProzent}%)` 
                    : 'Nein';
                
                info.style.display = 'flex'; // ⭐ GEÄNDERT: flex statt block
            }

            // Initial befüllen
            renderInfo(select.value);

            // Live-Update bei Änderung
            select.addEventListener('change', function (e) {
                renderInfo(e.target.value);
            });
        })();

        // ⭐ NEU: Automatische Zahlungsziel-Berechnung
        (function () {
            const rechnungsdatum = document.getElementById('rechnungsdatum');
            const zahlungsbedingungen = document.getElementById('zahlungsbedingungen');
            const zahlungsziel = document.getElementById('zahlungsziel');

            if (!rechnungsdatum || !zahlungsbedingungen || !zahlungsziel) return;

            function calculateZahlungsziel() {
                const datum = rechnungsdatum.value;
                const bedingung = zahlungsbedingungen.value;

                if (!datum || !bedingung) return;

                const selectedOption = zahlungsbedingungen.querySelector(`option[value="${bedingung}"]`);
                if (!selectedOption) return;

                const tage = parseInt(selectedOption.dataset.tage || 0);
                
                // Berechne Zahlungsziel
                const rechnungDate = new Date(datum);
                const zahlungDate = new Date(rechnungDate);
                zahlungDate.setDate(zahlungDate.getDate() + tage);

                // Formatiere als YYYY-MM-DD
                const year = zahlungDate.getFullYear();
                const month = String(zahlungDate.getMonth() + 1).padStart(2, '0');
                const day = String(zahlungDate.getDate()).padStart(2, '0');
                
                zahlungsziel.value = `${year}-${month}-${day}`;
            }

            // Berechne bei Änderung der Zahlungsbedingungen
            zahlungsbedingungen.addEventListener('change', calculateZahlungsziel);

            // Berechne bei Änderung des Rechnungsdatums
            rechnungsdatum.addEventListener('change', calculateZahlungsziel);

            // Initial berechnen
            calculateZahlungsziel();
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

        // ⭐ NEU: JS für "Als bezahlt markieren" Button (OHNE verschachteltes Form!)
        var btnMarkBezahlt = document.getElementById('btn-mark-bezahlt');
        if (btnMarkBezahlt) {
            btnMarkBezahlt.addEventListener('click', function () {
                if (!confirm('Rechnung als bezahlt markieren?')) {
                    return;
                }

                var url = btnMarkBezahlt.getAttribute('data-mark-url');

                fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json, text/plain, */*',
                        'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
                    },
                    body: ''
                })
                .then(function (resp) {
                    if (resp.ok) {
                        // Seite neu laden um Status zu aktualisieren
                        window.location.reload();
                    } else {
                        return resp.text().then(function (t) {
                            alert('Fehler beim Markieren:\n' + t);
                        });
                    }
                })
                .catch(function (err) {
                    alert('Netzwerkfehler: ' + err);
                });
            });
        }
    });
</script>