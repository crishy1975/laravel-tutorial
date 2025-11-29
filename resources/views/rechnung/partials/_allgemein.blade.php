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
                        <select name="zahlungsbedingungen" class="form-select form-select-sm @error('zahlungsbedingungen') is-invalid @enderror"
                                {{ $readonly ? 'disabled' : '' }}>
                            <option value="">Keine</option>
                            <option value="netto_7" {{ old('zahlungsbedingungen', $rechnung->zahlungsbedingungen) === 'netto_7' ? 'selected' : '' }}>Netto 7 Tage</option>
                            <option value="netto_10" {{ old('zahlungsbedingungen', $rechnung->zahlungsbedingungen) === 'netto_10' ? 'selected' : '' }}>Netto 10 Tage</option>
                            <option value="netto_14" {{ old('zahlungsbedingungen', $rechnung->zahlungsbedingungen) === 'netto_14' ? 'selected' : '' }}>Netto 14 Tage</option>
                            <option value="netto_30" {{ old('zahlungsbedingungen', $rechnung->zahlungsbedingungen) === 'netto_30' ? 'selected' : '' }}>Netto 30 Tage</option>
                            <option value="sofort" {{ old('zahlungsbedingungen', $rechnung->zahlungsbedingungen) === 'sofort' ? 'selected' : '' }}>Sofort</option>
                        </select>
                        @error('zahlungsbedingungen') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4 col-6">
                        <label class="form-label small mb-1">Zahlungsziel</label>
                        <input type="date" name="zahlungsziel"
                               class="form-control form-control-sm @error('zahlungsziel') is-invalid @enderror"
                               value="{{ old('zahlungsziel', $rechnung->zahlungsziel?->toDateString()) }}"
                               {{ $readonly ? 'disabled' : '' }}>
                        @error('zahlungsziel') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4 col-6">
                        <label class="form-label small mb-1">Status</label>
                        <select name="status" class="form-select form-select-sm @error('status') is-invalid @enderror"
                                {{ $readonly ? 'disabled' : '' }}>
                            <option value="entwurf" {{ old('status', $rechnung->status) === 'entwurf' ? 'selected' : '' }}>Entwurf</option>
                            <option value="offen" {{ old('status', $rechnung->status) === 'offen' ? 'selected' : '' }}>Offen</option>
                            <option value="versendet" {{ old('status', $rechnung->status) === 'versendet' ? 'selected' : '' }}>Versendet</option>
                            <option value="bezahlt" {{ old('status', $rechnung->status) === 'bezahlt' ? 'selected' : '' }}>Bezahlt</option>
                            <option value="storniert" {{ old('status', $rechnung->status) === 'storniert' ? 'selected' : '' }}>Storniert</option>
                        </select>
                        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Dritte Zeile: Leistungsdaten --}}
                    <div class="col-12">
                        <label class="form-label small mb-1">Leistungsdaten / Leistungszeitraum</label>
                        <input type="text" name="leistungsdaten"
                               class="form-control form-control-sm @error('leistungsdaten') is-invalid @enderror"
                               value="{{ old('leistungsdaten', $rechnung->leistungsdaten) }}"
                               placeholder="z.B. Januar 2025, Jahr/anno 2025"
                               {{ $readonly ? 'disabled' : '' }}>
                        @error('leistungsdaten') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- CARD: MwSt, Split Payment, Ritenuta (KOMPAKT) --}}
    <div class="col-xl-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-success text-white py-2">
                <h6 class="mb-0"><i class="bi bi-percent"></i> Steuern & Abzüge</h6>
            </div>
            <div class="card-body p-3">
                <div class="row g-2">

                    <div class="col-6">
                        <label class="form-label small mb-1">MwSt-Satz (%)</label>
                        <input type="number" name="mwst_satz" step="0.01"
                               class="form-control form-control-sm @error('mwst_satz') is-invalid @enderror"
                               value="{{ old('mwst_satz', $rechnung->mwst_satz ?? 22.00) }}"
                               {{ $readonly ? 'disabled' : '' }}>
                        @error('mwst_satz') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-6">
                        <label class="form-label small mb-1">&nbsp;</label>
                        <div class="form-check form-switch">
                            <input type="hidden" name="split_payment" value="0">
                            <input class="form-check-input" type="checkbox" name="split_payment" value="1"
                                   id="split_payment"
                                   {{ old('split_payment', $rechnung->split_payment) ? 'checked' : '' }}
                                   {{ $readonly ? 'disabled' : '' }}>
                            <label class="form-check-label small" for="split_payment">
                                Split Payment
                            </label>
                        </div>
                    </div>

                    <div class="col-12">
                        <hr class="my-2">
                    </div>

                    <div class="col-6">
                        <div class="form-check form-switch">
                            <input type="hidden" name="ritenuta" value="0">
                            <input class="form-check-input" type="checkbox" name="ritenuta" value="1"
                                   id="ritenuta"
                                   {{ old('ritenuta', $rechnung->ritenuta) ? 'checked' : '' }}
                                   {{ $readonly ? 'disabled' : '' }}>
                            <label class="form-check-label small" for="ritenuta">
                                Ritenuta aktiv
                            </label>
                        </div>
                    </div>

                    <div class="col-6">
                        <label class="form-label small mb-1">Ritenuta (%)</label>
                        <input type="number" name="ritenuta_prozent" step="0.01"
                               class="form-control form-control-sm @error('ritenuta_prozent') is-invalid @enderror"
                               value="{{ old('ritenuta_prozent', $rechnung->ritenuta_prozent ?? 4.00) }}"
                               {{ $readonly ? 'disabled' : '' }}>
                        @error('ritenuta_prozent') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- CARD: FatturaPA (KOMPAKT) --}}
    <div class="col-12">
        <div class="card border-warning border-0 shadow-sm">
            <div class="card-header bg-warning py-2">
                <h6 class="mb-0"><i class="bi bi-file-earmark-text"></i> FatturaPA-Daten</h6>
            </div>
            <div class="card-body p-3">
                <div class="row g-2">

                    <div class="col-md-3">
                        <div class="form-floating">
                            <input type="text" name="cup"
                                   class="form-control @error('cup') is-invalid @enderror"
                                   value="{{ old('cup', $rechnung->cup) }}"
                                   placeholder="CUP"
                                   {{ $readonly ? 'disabled' : '' }}>
                            <label>CUP (optional)</label>
                            @error('cup') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-floating">
                            <input type="text" name="cig"
                                   class="form-control @error('cig') is-invalid @enderror"
                                   value="{{ old('cig', $rechnung->cig) }}"
                                   placeholder="CIG"
                                   {{ $readonly ? 'disabled' : '' }}>
                            <label>CIG (optional)</label>
                            @error('cig') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-floating">
                            <input type="text" name="codice_commessa"
                                   class="form-control @error('codice_commessa') is-invalid @enderror"
                                   value="{{ old('codice_commessa', $rechnung->codice_commessa) }}"
                                   placeholder="Codice Commessa"
                                   {{ $readonly ? 'disabled' : '' }}>
                            <label>Codice Commessa</label>
                            @error('codice_commessa') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-floating">
                            <input type="text" name="auftrag_id"
                                   class="form-control @error('auftrag_id') is-invalid @enderror"
                                   value="{{ old('auftrag_id', $rechnung->auftrag_id) }}"
                                   placeholder="Auftrags-ID"
                                   {{ $readonly ? 'disabled' : '' }}>
                            <label>Auftrags-ID</label>
                            @error('auftrag_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- CARD: Summen (nur Anzeige) --}}
    <div class="col-12">
        <div class="card border-primary border-0 shadow-sm">
            <div class="card-header bg-primary text-white py-2">
                <h6 class="mb-0"><i class="bi bi-calculator"></i> Summen (Berechnet)</h6>
            </div>
            <div class="card-body p-3">
                <div class="row g-2">

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

                    {{-- ⭐ NEU: FatturaPA Causale --}}
                    <div class="col-12">
                        <div class="card border-info">
                            <div class="card-header bg-info text-white py-2">
                                <h6 class="mb-0">
                                    <i class="bi bi-file-text"></i> 
                                    FatturaPA Causale (Leistungsbeschreibung)
                                </h6>
                            </div>
                            <div class="card-body">
                                
                                {{-- Vorschau der automatischen Causale --}}
                                @if(!old('fattura_causale', $rechnung->fattura_causale ?? null))
                                    <div class="alert alert-light border mb-3">
                                        <small class="text-muted d-block mb-2">
                                            <i class="bi bi-robot"></i> 
                                            <strong>Wird automatisch generiert:</strong>
                                        </small>
                                        <div class="font-monospace small">
                                            @if($rechnung->exists)
                                                {{ \App\Models\Rechnung::generateCausaleStatic($rechnung) }}
                                            @else
                                                <span class="text-muted">Causale wird nach dem Speichern generiert...</span>
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                {{-- Manuelle Eingabe --}}
                                <div class="form-floating mb-2">
                                    <textarea 
                                        name="fattura_causale" 
                                        id="fattura_causale"
                                        class="form-control font-monospace @error('fattura_causale') is-invalid @enderror" 
                                        style="height: 100px"
                                        maxlength="200"
                                        placeholder="Leer lassen für automatische Generierung..."
                                        {{ $readonly ? 'disabled' : '' }}
                                    >{{ old('fattura_causale', $rechnung->fattura_causale ?? '') }}</textarea>
                                    <label for="fattura_causale">
                                        <i class="bi bi-pencil"></i>
                                        Eigene Beschreibung (optional)
                                    </label>
                                    @error('fattura_causale')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <i class="bi bi-info-circle"></i>
                                        Max. 200 Zeichen. Leer = automatisch aus Leistungsdaten + Gebäude
                                    </small>
                                    <small class="text-muted">
                                        <span id="causale-counter">{{ strlen(old('fattura_causale', $rechnung->fattura_causale ?? '')) }}</span> / 200
                                    </small>
                                </div>
                                
                                {{-- Aktionen --}}
                                @if(!$readonly)
                                <div class="d-flex gap-2 mt-3">
                                    @if($rechnung->exists && $rechnung->fattura_causale)
                                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-reset-causale">
                                            <i class="bi bi-arrow-counterclockwise"></i>
                                            Auf automatisch zurücksetzen
                                        </button>
                                    @endif
                                </div>
                                @endif
                            </div>
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

        // ⭐ NEU: Causale - Zeichenzähler
        var causaleTextarea = document.getElementById('fattura_causale');
        var causaleCounter = document.getElementById('causale-counter');
        if (causaleTextarea && causaleCounter) {
            function updateCausaleCounter() {
                var length = causaleTextarea.value.length;
                causaleCounter.textContent = length;
                
                // Farbe ändern
                causaleCounter.parentElement.classList.remove('text-danger', 'text-warning');
                if (length > 180) {
                    causaleCounter.parentElement.classList.add('text-danger', 'fw-bold');
                } else if (length > 150) {
                    causaleCounter.parentElement.classList.add('text-warning', 'fw-bold');
                }
            }
            
            causaleTextarea.addEventListener('input', updateCausaleCounter);
            updateCausaleCounter();
        }
        
        // ⭐ NEU: Causale - Zurücksetzen-Button
        var btnResetCausale = document.getElementById('btn-reset-causale');
        if (btnResetCausale && causaleTextarea) {
            btnResetCausale.addEventListener('click', function() {
                if (confirm('Manuelle Causale löschen und automatisch generieren lassen?')) {
                    causaleTextarea.value = '';
                    if (typeof updateCausaleCounter === 'function') {
                        updateCausaleCounter();
                    }
                }
            });
        }
    });
</script>