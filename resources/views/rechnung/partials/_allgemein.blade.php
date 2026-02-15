{{-- resources/views/rechnung/partials/_allgemein.blade.php --}}
{{-- ⭐ MOBILE-OPTIMIERTE VERSION --}}

@php
$readonly = $rechnung->exists && !$rechnung->ist_editierbar;
@endphp

<div class="row g-3 g-md-4">

    {{-- ═══════════════════════════════════════════════════════════
         INFO-CARDS: Gebäude, Rechnungsempfänger, Postadresse
         ═══════════════════════════════════════════════════════════ --}}

    {{-- Gebäude (nur Anzeige) --}}
    <div class="col-12 col-lg-4 mb-3">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-secondary text-white py-2">
                <h6 class="mb-0"><i class="bi bi-building"></i> Gebäude</h6>
            </div>
            <div class="card-body small">
                <div class="mb-1"><strong>Codex:</strong> {{ $rechnung->geb_codex ?: '-' }}</div>
                <div class="mb-1"><strong>Name:</strong> {{ $rechnung->geb_name ?: '-' }}</div>
                <div class="mb-1"><strong>Adresse:</strong> {{ $rechnung->geb_adresse ?: '-' }}</div>
            </div>
            @if($rechnung->gebaeude_id && Route::has('gebaeude.edit'))
            <div class="card-footer bg-transparent border-top-0 pt-0">
                <a href="{{ route('gebaeude.edit', $rechnung->gebaeude_id) }}" class="btn btn-sm btn-outline-secondary w-100">
                    <i class="bi bi-box-arrow-up-right"></i> Gebäude öffnen
                </a>
            </div>
            @endif
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         RECHNUNGSEMPFÄNGER (Dropdown + Vorschau)
         ═══════════════════════════════════════════════════════════ --}}
    <div class="col-12 col-lg-4 mb-3">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-primary text-white py-2">
                <h6 class="mb-0"><i class="bi bi-person-circle"></i> Rechnungsempfänger</h6>
            </div>
            <div class="card-body small p-2 p-md-3">
                {{-- Dropdown zur Adressauswahl (Select2) --}}
                <label class="form-label small mb-1">Adresse wählen</label>
                <select id="rechnungsempfaenger_id" name="rechnungsempfaenger_id"
                    class="form-select form-select-sm js-select2 @error('rechnungsempfaenger_id') is-invalid @enderror"
                    data-placeholder="- Adresse wählen -"
                    {{ $readonly ? 'disabled' : '' }}>
                    <option value=""></option>
                    @foreach($adressen ?? [] as $adresse)
                        <option value="{{ $adresse->id }}"
                            data-name="{{ $adresse->name }}"
                            data-strasse="{{ $adresse->strasse }}"
                            data-hausnummer="{{ $adresse->hausnummer }}"
                            data-plz="{{ $adresse->plz }}"
                            data-wohnort="{{ $adresse->wohnort }}"
                            data-provinz="{{ $adresse->provinz }}"
                            data-land="{{ $adresse->land }}"
                            data-steuernummer="{{ $adresse->steuernummer }}"
                            data-mwst="{{ $adresse->mwst_nummer }}"
                            data-codice="{{ $adresse->codice_univoco }}"
                            data-pec="{{ $adresse->pec }}"
                            data-email="{{ $adresse->email }}"
                            {{ old('rechnungsempfaenger_id', $rechnung->rechnungsempfaenger_id) == $adresse->id ? 'selected' : '' }}>
                            {{ $adresse->name }} - {{ $adresse->wohnort }}
                        </option>
                    @endforeach
                </select>
                @error('rechnungsempfaenger_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror

                {{-- Adress-Vorschau (aktuelle Snapshot-Daten) --}}
                <div id="re-preview" class="mt-3 pt-3 border-top">
                    <div class="mb-1"><strong id="re-preview-name">{{ $rechnung->re_name ?: '(nicht gewählt)' }}</strong></div>
                    <div class="mb-1 text-muted">
                        <i class="bi bi-geo-alt me-1"></i>
                        <span id="re-preview-strasse">{{ $rechnung->re_strasse }}</span>
                        <span id="re-preview-hausnummer">{{ $rechnung->re_hausnummer }}</span>
                    </div>
                    <div class="mb-1 text-muted">
                        <i class="bi bi-signpost me-1"></i>
                        <span id="re-preview-plz">{{ $rechnung->re_plz }}</span>
                        <span id="re-preview-wohnort">{{ $rechnung->re_wohnort }}</span>
                        <span id="re-preview-provinz">{{ $rechnung->re_provinz ? '(' . $rechnung->re_provinz . ')' : '' }}</span>
                    </div>
                    <div class="mb-1 text-muted">
                        <span id="re-preview-land">{{ $rechnung->re_land }}</span>
                    </div>
                    {{-- Steuer-Infos --}}
                    <div class="mt-2 pt-2 border-top small">
                        <div class="row g-1">
                            <div class="col-6"><i class="bi bi-credit-card me-1"></i>CF: <span id="re-preview-cf">{{ $rechnung->re_steuernummer ?: '-' }}</span></div>
                            <div class="col-6"><i class="bi bi-receipt me-1"></i>P.IVA: <span id="re-preview-piva">{{ $rechnung->re_mwst_nummer ?: '-' }}</span></div>
                            <div class="col-6"><i class="bi bi-upc me-1"></i>SDI: <span id="re-preview-sdi">{{ $rechnung->re_codice_univoco ?: '-' }}</span></div>
                            <div class="col-6"><i class="bi bi-envelope me-1"></i>PEC: <span id="re-preview-pec">{{ $rechnung->re_pec ?: '-' }}</span></div>
                        </div>
                    </div>
                </div>
            </div>
            {{-- Button IN Card-Footer --}}
            <div class="card-footer bg-transparent border-top-0 pt-0">
                @php $re_id = old('rechnungsempfaenger_id', $rechnung->rechnungsempfaenger_id); @endphp
                <a id="re_edit_btn"
                   href="{{ $re_id && Route::has('adresse.edit') ? route('adresse.edit', ['id' => $re_id, 'returnTo' => url()->current()]) : '#' }}"
                   class="btn btn-outline-primary btn-sm w-100 {{ $re_id ? '' : 'disabled' }}"
                   {{ $readonly ? 'style=pointer-events:none;opacity:0.5;' : '' }}>
                    <i class="bi bi-pencil-square"></i> Adresse bearbeiten
                </a>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         POSTADRESSE (Dropdown + Vorschau)
         ═══════════════════════════════════════════════════════════ --}}
    <div class="col-12 col-lg-4 mb-3">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-info text-white py-2">
                <h6 class="mb-0"><i class="bi bi-envelope"></i> Postadresse</h6>
            </div>
            <div class="card-body small p-2 p-md-3">
                {{-- Dropdown zur Adressauswahl (Select2) --}}
                <label class="form-label small mb-1">Adresse wählen</label>
                <select id="postadresse_id" name="postadresse_id"
                    class="form-select form-select-sm js-select2 @error('postadresse_id') is-invalid @enderror"
                    data-placeholder="- Adresse wählen -"
                    {{ $readonly ? 'disabled' : '' }}>
                    <option value=""></option>
                    @foreach($adressen ?? [] as $adresse)
                        <option value="{{ $adresse->id }}"
                            data-name="{{ $adresse->name }}"
                            data-strasse="{{ $adresse->strasse }}"
                            data-hausnummer="{{ $adresse->hausnummer }}"
                            data-plz="{{ $adresse->plz }}"
                            data-wohnort="{{ $adresse->wohnort }}"
                            data-provinz="{{ $adresse->provinz }}"
                            data-land="{{ $adresse->land }}"
                            data-email="{{ $adresse->email }}"
                            data-pec="{{ $adresse->pec }}"
                            {{ old('postadresse_id', $rechnung->postadresse_id) == $adresse->id ? 'selected' : '' }}>
                            {{ $adresse->name }} - {{ $adresse->wohnort }}
                        </option>
                    @endforeach
                </select>
                @error('postadresse_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror

                {{-- Adress-Vorschau (aktuelle Snapshot-Daten) --}}
                <div id="post-preview" class="mt-3 pt-3 border-top">
                    <div class="mb-1"><strong id="post-preview-name">{{ $rechnung->post_name ?: '(nicht gewählt)' }}</strong></div>
                    <div class="mb-1 text-muted">
                        <i class="bi bi-geo-alt me-1"></i>
                        <span id="post-preview-strasse">{{ $rechnung->post_strasse }}</span>
                        <span id="post-preview-hausnummer">{{ $rechnung->post_hausnummer }}</span>
                    </div>
                    <div class="mb-1 text-muted">
                        <i class="bi bi-signpost me-1"></i>
                        <span id="post-preview-plz">{{ $rechnung->post_plz }}</span>
                        <span id="post-preview-wohnort">{{ $rechnung->post_wohnort }}</span>
                        <span id="post-preview-provinz">{{ $rechnung->post_provinz ? '(' . $rechnung->post_provinz . ')' : '' }}</span>
                    </div>
                    <div class="mb-1 text-muted">
                        <span id="post-preview-land">{{ $rechnung->post_land }}</span>
                    </div>
                    {{-- E-Mail/PEC --}}
                    <div class="mt-2 pt-2 border-top small">
                        <div class="row g-1">
                            <div class="col-6"><i class="bi bi-envelope me-1"></i>E-Mail: <span id="post-preview-email">{{ $rechnung->post_email ?: '-' }}</span></div>
                            <div class="col-6"><i class="bi bi-envelope-at me-1"></i>PEC: <span id="post-preview-pec">{{ $rechnung->post_pec ?: '-' }}</span></div>
                        </div>
                    </div>
                </div>
            </div>
            {{-- Button IN Card-Footer --}}
            <div class="card-footer bg-transparent border-top-0 pt-0">
                @php $post_id = old('postadresse_id', $rechnung->postadresse_id); @endphp
                <a id="post_edit_btn"
                   href="{{ $post_id && Route::has('adresse.edit') ? route('adresse.edit', ['id' => $post_id, 'returnTo' => url()->current()]) : '#' }}"
                   class="btn btn-outline-info btn-sm w-100 {{ $post_id ? '' : 'disabled' }}"
                   {{ $readonly ? 'style=pointer-events:none;opacity:0.5;' : '' }}>
                    <i class="bi bi-pencil-square"></i> Adresse bearbeiten
                </a>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         RECHNUNGSDATEN
         ═══════════════════════════════════════════════════════════ --}}

    <div class="col-12 col-xl-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-file-text"></i> Rechnungsdaten</h6>
                @if($rechnung->exists)
                <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#editRechnungsnummerModal" title="Rechnungsnummer/Datum ändern">
                    <i class="bi bi-pencil-square"></i> <span class="d-none d-md-inline">Nr/Datum ändern</span>
                </button>
                @endif
            </div>
            <div class="card-body p-2 p-md-3">
                <div class="row g-2 g-md-3">

                    {{-- Rechnungsnummer - READONLY --}}
                    <div class="col-6 col-md-3">
                        <label class="form-label small mb-1">Rechnungsnr.</label>
                        <input type="text" name="rechnungsnummer"
                            class="form-control form-control-sm @error('rechnungsnummer') is-invalid @enderror"
                            value="{{ old('rechnungsnummer', $rechnung->rechnungsnummer) }}"
                            readonly>
                        @error('rechnungsnummer') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Rechnungsdatum - READONLY --}}
                    <div class="col-6 col-md-3">
                        <label class="form-label small mb-1">Rechnungsdatum</label>
                        <input type="date" name="rechnungsdatum"
                            class="form-control form-control-sm @error('rechnungsdatum') is-invalid @enderror"
                            value="{{ old('rechnungsdatum', $rechnung->rechnungsdatum?->format('Y-m-d')) }}"
                            readonly>
                        @error('rechnungsdatum') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- ⭐ Zahlungsbedingungen --}}
                    @php
                    $zbValue = old('zahlungsbedingungen', $rechnung->zahlungsbedingungen?->value ?? $rechnung->zahlungsbedingungen);
                    @endphp
                    <div class="col-6 col-md-3">
                        <label class="form-label small mb-1">Zahlungsbed.</label>
                        <input type="hidden" name="zahlungsbedingungen" id="zahlungsbedingungen_hidden"
                            value="{{ $zbValue }}">
                        <select id="zahlungsbedingungen_select"
                            class="form-select form-select-sm @error('zahlungsbedingungen') is-invalid @enderror"
                            {{ $readonly ? 'disabled' : '' }}
                            onchange="document.getElementById('zahlungsbedingungen_hidden').value = this.value;">
                            <option value="">Keine</option>
                            <option value="netto_7" {{ $zbValue === 'netto_7' ? 'selected' : '' }}>Netto 7 Tage</option>
                            <option value="netto_10" {{ $zbValue === 'netto_10' ? 'selected' : '' }}>Netto 10 Tage</option>
                            <option value="netto_14" {{ $zbValue === 'netto_14' ? 'selected' : '' }}>Netto 14 Tage</option>
                            <option value="netto_30" {{ $zbValue === 'netto_30' ? 'selected' : '' }}>Netto 30 Tage</option>
                            <option value="netto_60" {{ $zbValue === 'netto_60' ? 'selected' : '' }}>Netto 60 Tage</option>
                            <option value="sofort" {{ $zbValue === 'sofort' ? 'selected' : '' }}>Sofort</option>
                            <option value="bezahlt" {{ $zbValue === 'bezahlt' ? 'selected' : '' }}>Bezahlt</option>
                        </select>
                        @error('zahlungsbedingungen') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Zahlungsziel / Fälligkeitsdatum --}}
                    <div class="col-6 col-md-3">
                        <label class="form-label small mb-1">Zahlungsziel</label>
                        <input type="date" name="faelligkeitsdatum"
                            class="form-control form-control-sm @error('faelligkeitsdatum') is-invalid @enderror"
                            value="{{ old('faelligkeitsdatum', $rechnung->faelligkeitsdatum?->format('Y-m-d')) }}">
                        @error('faelligkeitsdatum') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Typ --}}
                    <div class="col-6 col-md-3">
                        <label class="form-label small mb-1">Typ</label>
                        <select name="typ_rechnung" class="form-select form-select-sm @error('typ_rechnung') is-invalid @enderror">
                            <option value="rechnung" {{ old('typ_rechnung', $rechnung->typ_rechnung) == 'rechnung' ? 'selected' : '' }}>Rechnung</option>
                            <option value="gutschrift" {{ old('typ_rechnung', $rechnung->typ_rechnung) == 'gutschrift' ? 'selected' : '' }}>Gutschrift</option>
                        </select>
                        @error('typ_rechnung') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Status --}}
                    @if($rechnung->exists)
                    <div class="col-6 col-md-3">
                        <label class="form-label small mb-1">Status</label>
                        <select name="status" class="form-select form-select-sm @error('status') is-invalid @enderror">
                            <option value="draft" {{ old('status', $rechnung->status) == 'draft' ? 'selected' : '' }}>Entwurf</option>
                            <option value="sent" {{ old('status', $rechnung->status) == 'sent' ? 'selected' : '' }}>Versendet</option>
                            <option value="paid" {{ old('status', $rechnung->status) == 'paid' ? 'selected' : '' }}>Bezahlt</option>
                            <option value="overdue" {{ old('status', $rechnung->status) == 'overdue' ? 'selected' : '' }}>Überfällig</option>
                            <option value="cancelled" {{ old('status', $rechnung->status) == 'cancelled' ? 'selected' : '' }}>Storniert</option>
                        </select>
                        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    @endif
                    
                    {{-- ⭐ Bezahlt am --}}
                    @if($rechnung->exists)
                    <div class="col-6 col-md-3">
                        <label class="form-label small mb-1">Bezahlt am</label>
                        <input type="date"
                            name="bezahlt_am"
                            id="bezahlt_am_display"
                            class="form-control form-control-sm @error('bezahlt_am') is-invalid @enderror"
                            value="{{ old('bezahlt_am', $rechnung->bezahlt_am?->format('Y-m-d')) }}">
                        <input type="hidden" name="bezahlt_am" id="bezahlt_am_hidden" 
                            value="{{ old('bezahlt_am', $rechnung->bezahlt_am?->format('Y-m-d')) }}">
                        @error('bezahlt_am') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    @endif

                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         STEUERN & ABZÜGE - READONLY
         ═══════════════════════════════════════════════════════════ --}}

    <div class="col-12 col-xl-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-success text-white py-2">
                <h6 class="mb-0 small"><i class="bi bi-percent"></i> Steuern & Abzüge</h6>
            </div>
            <div class="card-body p-2 p-md-3">
                <div class="row g-2">

                    {{-- MwSt-Satz - READONLY --}}
                    <div class="col-6 col-sm-4">
                        <label class="form-label small mb-1">MwSt-Satz (%)</label>
                        <input type="number" name="mwst_satz" step="0.01"
                            class="form-control form-control-sm @error('mwst_satz') is-invalid @enderror"
                            value="{{ old('mwst_satz', $rechnung->mwst_satz ?? 22.00) }}"
                            readonly>
                        @error('mwst_satz') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Reverse Charge & Split Payment - READONLY --}}
                    <div class="col-6 col-sm-8">
                        <label class="form-label small mb-1">Sonderregelungen</label>
                        <div class="d-flex flex-wrap gap-2">
                            <div class="form-check form-switch">
                                <input type="hidden" name="reverse_charge" value="0">
                                <input class="form-check-input" type="checkbox" name="reverse_charge" value="1"
                                    id="reverse_charge"
                                    {{ old('reverse_charge', $rechnung->reverse_charge) ? 'checked' : '' }}
                                    disabled>
                                <label class="form-check-label small" for="reverse_charge">
                                    <i class="bi bi-arrow-left-right"></i> RC
                                </label>
                            </div>

                            <div class="form-check form-switch">
                                <input type="hidden" name="split_payment" value="0">
                                <input class="form-check-input" type="checkbox" name="split_payment" value="1"
                                    id="split_payment"
                                    {{ old('split_payment', $rechnung->split_payment) ? 'checked' : '' }}
                                    disabled>
                                <label class="form-check-label small" for="split_payment">
                                    <i class="bi bi-arrows-expand"></i> SP
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- Ritenuta - READONLY --}}
                    <div class="col-6 col-sm-4">
                        <div class="form-check form-switch mt-1">
                            <input type="hidden" name="ritenuta" value="0">
                            <input class="form-check-input" type="checkbox" name="ritenuta" value="1"
                                id="ritenuta"
                                {{ old('ritenuta', $rechnung->ritenuta) ? 'checked' : '' }}
                                disabled>
                            <label class="form-check-label small" for="ritenuta">
                                <i class="bi bi-dash-circle"></i> Ritenuta
                            </label>
                        </div>
                    </div>

                    <div class="col-6 col-sm-3">
                        <label class="form-label small mb-1">% Ritenuta</label>
                        <input type="number" name="ritenuta_prozent" step="0.01"
                            class="form-control form-control-sm @error('ritenuta_prozent') is-invalid @enderror"
                            value="{{ old('ritenuta_prozent', $rechnung->ritenuta_prozent ?? 4.00) }}"
                            readonly>
                        @error('ritenuta_prozent') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Info nur wenn nötig --}}
                    @if($rechnung->reverse_charge || $rechnung->split_payment)
                    <div class="col-12">
                        <div class="alert alert-warning py-1 px-2 mb-0 small">
                            @if($rechnung->reverse_charge)
                            <i class="bi bi-exclamation-triangle"></i> <strong>Reverse Charge:</strong> MwSt vom Empfänger
                            @endif
                            @if($rechnung->split_payment)
                            @if($rechnung->reverse_charge) • @endif
                            <i class="bi bi-info-circle"></i> <strong>Split Payment:</strong> MwSt vom Auftraggeber
                            @endif
                        </div>
                    </div>
                    @endif

                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         FATTURAPA DATEN
         ═══════════════════════════════════════════════════════════ --}}

    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-warning py-2">
                <h6 class="mb-0 small"><i class="bi bi-file-earmark-text"></i> FatturaPA-Daten</h6>
            </div>
            <div class="card-body p-2 p-md-3">
                <div class="row g-2">

                    <div class="col-6 col-md-3">
                        <label class="form-label small mb-1">CUP <span class="d-none d-sm-inline">(optional)</span></label>
                        <input type="text" name="cup" class="form-control form-control-sm @error('cup') is-invalid @enderror"
                            value="{{ old('cup', $rechnung->cup) }}" {{ $readonly ? 'disabled' : '' }}>
                        @error('cup') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-6 col-md-3">
                        <label class="form-label small mb-1">CIG <span class="d-none d-sm-inline">(optional)</span></label>
                        <input type="text" name="cig" class="form-control form-control-sm @error('cig') is-invalid @enderror"
                            value="{{ old('cig', $rechnung->cig) }}" {{ $readonly ? 'disabled' : '' }}>
                        @error('cig') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-6 col-md-3">
                        <label class="form-label small mb-1">Codice Commessa</label>
                        <input type="text" name="codice_commessa" class="form-control form-control-sm @error('codice_commessa') is-invalid @enderror"
                            value="{{ old('codice_commessa', $rechnung->codice_commessa) }}" {{ $readonly ? 'disabled' : '' }}>
                        @error('codice_commessa') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-6 col-md-3">
                        <label class="form-label small mb-1">Auftrags-ID</label>
                        <input type="text" name="auftrag_id" class="form-control form-control-sm @error('auftrag_id') is-invalid @enderror"
                            value="{{ old('auftrag_id', $rechnung->auftrag_id) }}" {{ $readonly ? 'disabled' : '' }}>
                        @error('auftrag_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- ⭐ NEU: Auftragsdatum --}}
                    <div class="col-6 col-md-3">
                        <label class="form-label small mb-1">Auftragsdatum</label>
                        <input type="date" name="auftrag_datum" class="form-control form-control-sm @error('auftrag_datum') is-invalid @enderror"
                            value="{{ old('auftrag_datum', $rechnung->auftrag_datum?->format('Y-m-d')) }}" {{ $readonly ? 'disabled' : '' }}>
                        @error('auftrag_datum') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         CAUSALE
         ═══════════════════════════════════════════════════════════ --}}

    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-light py-2">
                <h6 class="mb-0 small"><i class="bi bi-chat-text"></i> Causale (Rechnungstext)</h6>
            </div>
            <div class="card-body p-2 p-md-3">
                <textarea name="fattura_causale" class="form-control form-control-sm" rows="3"
                    {{ $readonly ? 'disabled' : '' }}>{{ old('fattura_causale', $rechnung->fattura_causale) }}</textarea>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         BEMERKUNG (intern) - ⭐ NEU
         ═══════════════════════════════════════════════════════════ --}}

    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-light py-2">
                <h6 class="mb-0 small"><i class="bi bi-sticky"></i> Bemerkung (intern)</h6>
            </div>
            <div class="card-body p-2 p-md-3">
                <textarea name="bemerkung" 
                    class="form-control form-control-sm @error('bemerkung') is-invalid @enderror" 
                    rows="2"
                    placeholder="Interne Notizen (erscheint nicht auf der Rechnung)"
                    {{ $readonly ? 'disabled' : '' }}>{{ old('bemerkung', $rechnung->bemerkung) }}</textarea>
                @error('bemerkung') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         RECHNUNGSPOSITIONEN (READONLY) - MOBILE-OPTIMIERT
         ═══════════════════════════════════════════════════════════ --}}

    <div class="col-12">
        @if(!$rechnung->exists)
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i>
            Positionen können erst nach dem Anlegen der Rechnung bearbeitet werden.
        </div>
        @else
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-primary text-white py-2">
                <h6 class="mb-0"><i class="bi bi-list-ul"></i> Rechnungspositionen</h6>
            </div>

            {{-- ⭐ DESKTOP/TABLET: Tabelle (ab 768px) --}}
            <div class="table-responsive d-none d-md-block">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th width="40">#</th>
                            <th>Beschreibung</th>
                            <th width="70" class="text-end">Anz.</th>
                            <th width="50">Einh.</th>
                            <th width="90" class="text-end">Einzelpr.</th>
                            <th width="50" class="text-end">MwSt</th>
                            <th width="90" class="text-end">Netto</th>
                            <th width="90" class="text-end">Brutto</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rechnung->positionen as $pos)
                        <tr>
                            <td class="text-muted">{{ $pos->position }}</td>
                            <td>
                                {{ Str::limit($pos->beschreibung, 50) }}
                                @if($pos->artikelGebaeude)
                                <br><small class="text-muted">
                                    <i class="bi bi-link-45deg"></i> #{{ $pos->artikel_gebaeude_id }}
                                </small>
                                @endif
                            </td>
                            <td class="text-end">{{ number_format($pos->anzahl, 2, ',', '.') }}</td>
                            <td>{{ $pos->einheit }}</td>
                            <td class="text-end">{{ number_format($pos->einzelpreis, 2, ',', '.') }}€</td>
                            <td class="text-end">{{ number_format($pos->mwst_satz, 0) }}%</td>
                            <td class="text-end">{{ number_format($pos->netto_gesamt, 2, ',', '.') }}€</td>
                            <td class="text-end"><strong>{{ number_format($pos->brutto_gesamt, 2, ',', '.') }}€</strong></td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                <i class="bi bi-inbox"></i> Keine Positionen
                            </td>
                        </tr>
                        @endforelse
                    </tbody>

                    {{-- SUMMEN --}}
                    @if($rechnung->positionen->isNotEmpty())
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="6" class="text-end fw-bold">Summe:</td>
                            <td class="text-end fw-bold">{{ number_format($rechnung->netto_summe, 2, ',', '.') }}€</td>
                            <td class="text-end fw-bold">{{ number_format($rechnung->brutto_summe, 2, ',', '.') }}€</td>
                        </tr>

                        @if($rechnung->ritenuta && $rechnung->ritenuta_betrag > 0)
                        <tr class="table-warning">
                            <td colspan="7" class="text-end">
                                <i class="bi bi-dash-circle"></i> Ritenuta ({{ $rechnung->ritenuta_prozent }}%):
                            </td>
                            <td class="text-end fw-bold">-{{ number_format($rechnung->ritenuta_betrag, 2, ',', '.') }}€</td>
                        </tr>
                        @endif

                        <tr class="table-success">
                            <td colspan="7" class="text-end fw-bold">
                                <i class="bi bi-cash-coin"></i> Zahlbar:
                            </td>
                            <td class="text-end fw-bold">{{ number_format($rechnung->zahlbar_betrag, 2, ',', '.') }}€</td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>

            {{-- ⭐ MOBILE: Card-Layout (unter 768px) --}}
            <div class="d-md-none">
                @forelse($rechnung->positionen as $pos)
                <div class="border-bottom p-2">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <span class="badge bg-secondary badge-sm">{{ $pos->position }}</span>
                        <strong class="text-primary">{{ number_format($pos->brutto_gesamt, 2, ',', '.') }}€</strong>
                    </div>
                    <p class="mb-2 small text-dark">{{ Str::limit($pos->beschreibung, 80) }}</p>
                    <div class="d-flex justify-content-between small text-muted">
                        <span>{{ number_format($pos->anzahl, 2, ',', '.') }} {{ $pos->einheit }} × {{ number_format($pos->einzelpreis, 2, ',', '.') }}€</span>
                        <span>{{ number_format($pos->mwst_satz, 0) }}% MwSt</span>
                    </div>
                </div>
                @empty
                <div class="text-center text-muted py-4">
                    <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                    <p class="mt-2 mb-0 small">Keine Positionen</p>
                </div>
                @endforelse

                {{-- Mobile Summen - kompakt --}}
                @if($rechnung->positionen->isNotEmpty())
                <div class="bg-light p-2">
                    <div class="d-flex justify-content-between small mb-1">
                        <span>Netto:</span>
                        <span>{{ number_format($rechnung->netto_summe, 2, ',', '.') }}€</span>
                    </div>
                    <div class="d-flex justify-content-between small mb-1">
                        <span>MwSt:</span>
                        <span>{{ number_format($rechnung->mwst_betrag, 2, ',', '.') }}€</span>
                    </div>
                    @if($rechnung->ritenuta && $rechnung->ritenuta_betrag > 0)
                    <div class="d-flex justify-content-between small mb-1 text-warning">
                        <span>Ritenuta ({{ $rechnung->ritenuta_prozent }}%):</span>
                        <span>-{{ number_format($rechnung->ritenuta_betrag, 2, ',', '.') }}€</span>
                    </div>
                    @endif
                    <hr class="my-1">
                    <div class="d-flex justify-content-between">
                        <strong><i class="bi bi-cash-coin"></i> Zahlbar:</strong>
                        <strong class="text-success fs-5">{{ number_format($rechnung->zahlbar_betrag, 2, ',', '.') }}€</strong>
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- Info-Boxen für RC & SP --}}
        @if($rechnung->reverse_charge)
        <div class="alert alert-warning mt-3">
            <i class="bi bi-exclamation-triangle"></i>
            <strong>Reverse Charge aktiv:</strong>
            <span class="d-none d-md-inline">Umkehrung der Steuerschuldnerschaft (Art. 17 DPR 633/72).</span>
            Die MwSt ist vom Leistungsempfänger zu entrichten.
        </div>
        @endif

        @if($rechnung->split_payment)
        <div class="alert alert-info mt-3">
            <i class="bi bi-info-circle"></i>
            <strong>Split Payment aktiv:</strong>
            <span class="d-none d-md-inline">Die MwSt wird separat behandelt und</span>
            <span class="d-md-none">MwSt</span> direkt vom öffentlichen Auftraggeber an das Finanzamt abgeführt.
        </div>
        @endif

        @endif
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         BUTTONS - MOBILE-OPTIMIERT
         ═══════════════════════════════════════════════════════════ --}}

    @if($rechnung->exists)
    <div class="col-12">
        {{-- Desktop: Flex-Layout --}}
        <div class="d-none d-md-flex gap-2 justify-content-between align-items-center">

            {{-- Links: Zurück zum Gebäude --}}
            <div>
                @if($rechnung->gebaeude_id && Route::has('gebaeude.edit'))
                <a href="{{ route('gebaeude.edit', $rechnung->gebaeude_id) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-building"></i> Zurück zum Gebäude
                </a>
                @endif
            </div>

            {{-- Rechts: Bezahlt + Löschen --}}
            <div class="d-flex gap-2">

                {{-- ⭐ Button: Rechnung ist bezahlt (öffnet Modal) --}}
                @if($rechnung->zahlungsbedingungen?->value !== 'bezahlt')
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalBezahlt">
                    <i class="bi bi-check-circle"></i> Rechnung ist bezahlt
                </button>
                @endif

            </div>

        </div>

        {{-- Mobile: Gestapelte Buttons --}}
        <div class="d-md-none d-grid gap-2">
            
            @if($rechnung->zahlungsbedingungen?->value !== 'bezahlt')
            <button type="button" class="btn btn-success btn-lg" data-bs-toggle="modal" data-bs-target="#modalBezahlt">
                <i class="bi bi-check-circle"></i> Rechnung ist bezahlt
            </button>
            @endif

            @if($rechnung->gebaeude_id && Route::has('gebaeude.edit'))
            <a href="{{ route('gebaeude.edit', $rechnung->gebaeude_id) }}" class="btn btn-outline-secondary">
                <i class="bi bi-building"></i> Zurück zum Gebäude
            </a>
            @endif
        </div>
    </div>
    @endif

</div>

{{-- ═══════════════════════════════════════════════════════════
     MODALS (AUSSERHALB DES FORMULARS!)
     ═══════════════════════════════════════════════════════════ --}}


{{-- ⭐ Modal: Rechnungsnummer / Datum ändern (MIT VORSICHT!) --}}
@if($rechnung->exists)
<div class="modal fade" id="editRechnungsnummerModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="bi bi-exclamation-triangle"></i> Rechnungsnummer / Datum ändern</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <strong><i class="bi bi-exclamation-octagon"></i> ACHTUNG – MIT VORSICHT!</strong>
                    <ul class="mb-0 mt-2">
                        <li>Änderungen können zu <strong>Inkonsistenzen</strong> in der Buchhaltung führen</li>
                        <li>Bereits versendete XML-Dateien (FatturaPA) stimmen nicht mehr überein</li>
                        <li>Nur ändern, wenn Sie sich <strong>absolut sicher</strong> sind!</li>
                    </ul>
                </div>
                
                <div class="row mb-3">
                    <div class="col-6">
                        <label class="form-label fw-bold">Jahr</label>
                        <input type="number" class="form-control" id="modal_jahr" 
                               value="{{ $rechnung->jahr }}" min="2000" max="2099">
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-bold">Laufnummer</label>
                        <input type="number" class="form-control" id="modal_laufnummer" 
                               value="{{ $rechnung->laufnummer }}" min="1">
                    </div>
                </div>
                <small class="text-muted d-block mb-3">
                    Aktuell: <strong>{{ $rechnung->rechnungsnummer }}</strong> 
                    → Vorschau: <span id="preview_rechnungsnummer" class="fw-bold text-primary">{{ $rechnung->rechnungsnummer }}</span>
                </small>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Neues Rechnungsdatum</label>
                    <input type="date" class="form-control" id="modal_rechnungsdatum" 
                           value="{{ $rechnung->rechnungsdatum?->format('Y-m-d') }}">
                    <small class="text-muted">Aktuell: {{ $rechnung->rechnungsdatum?->format('d.m.Y') }}</small>
                </div>
                
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="confirmChangeCheckbox">
                    <label class="form-check-label text-danger fw-bold" for="confirmChangeCheckbox">
                        Ich verstehe das Risiko und möchte trotzdem ändern
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                <button type="button" class="btn btn-warning" id="btnApplyRechnungsnummer" disabled>
                    <i class="bi bi-check-lg"></i> Änderungen übernehmen
                </button>
            </div>
        </div>
    </div>
</div>

{{-- JavaScript: Rechnungsnummer/Datum ändern --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkbox = document.getElementById('confirmChangeCheckbox');
    const btnApply = document.getElementById('btnApplyRechnungsnummer');
    const jahrInput = document.getElementById('modal_jahr');
    const laufnummerInput = document.getElementById('modal_laufnummer');
    const previewSpan = document.getElementById('preview_rechnungsnummer');
    
    // Vorschau aktualisieren
    function updatePreview() {
        const jahr = jahrInput.value || '0000';
        const laufnummer = String(laufnummerInput.value || '0').padStart(4, '0');
        previewSpan.textContent = jahr + '/' + laufnummer;
    }
    
    if (jahrInput) jahrInput.addEventListener('input', updatePreview);
    if (laufnummerInput) laufnummerInput.addEventListener('input', updatePreview);
    
    if (checkbox && btnApply) {
        // Button nur aktivieren wenn Checkbox angehakt
        checkbox.addEventListener('change', function() {
            btnApply.disabled = !this.checked;
        });
        
        // Werte übernehmen
        btnApply.addEventListener('click', function() {
            const neuesJahr = jahrInput.value.trim();
            const neueLaufnummer = laufnummerInput.value.trim();
            const neuesDatum = document.getElementById('modal_rechnungsdatum').value;
            
            if (!neuesJahr || !neueLaufnummer) {
                alert('Bitte Jahr und Laufnummer eingeben!');
                return;
            }
            
            // Hidden Inputs für jahr/laufnummer erstellen oder aktualisieren
            let jahrHidden = document.querySelector('input[name="jahr"]');
            let laufnummerHidden = document.querySelector('input[name="laufnummer"]');
            
            if (!jahrHidden) {
                jahrHidden = document.createElement('input');
                jahrHidden.type = 'hidden';
                jahrHidden.name = 'jahr';
                document.getElementById('rechnungForm').appendChild(jahrHidden);
            }
            jahrHidden.value = neuesJahr;
            
            if (!laufnummerHidden) {
                laufnummerHidden = document.createElement('input');
                laufnummerHidden.type = 'hidden';
                laufnummerHidden.name = 'laufnummer';
                document.getElementById('rechnungForm').appendChild(laufnummerHidden);
            }
            laufnummerHidden.value = neueLaufnummer;
            
            // Rechnungsnummer-Anzeige aktualisieren
            const nummerInput = document.querySelector('input[name="rechnungsnummer"]');
            if (nummerInput) {
                nummerInput.value = neuesJahr + '/' + String(neueLaufnummer).padStart(4, '0');
                nummerInput.classList.add('border-warning', 'bg-warning-subtle');
            }
            
            // Datum übertragen
            const datumInput = document.querySelector('input[name="rechnungsdatum"]');
            if (datumInput) {
                datumInput.value = neuesDatum;
                datumInput.removeAttribute('readonly');
                datumInput.classList.add('border-warning', 'bg-warning-subtle');
            }
            
            // Modal schließen
            const modal = bootstrap.Modal.getInstance(document.getElementById('editRechnungsnummerModal'));
            modal.hide();
        });
    }
});
</script>
@endif

{{-- ═══════════════════════════════════════════════════════════
     JavaScript: Adress-Dropdown Funktionalität (Select2 mit Suchfeld)
     ═══════════════════════════════════════════════════════════ --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Elemente
    var reSelect = document.getElementById('rechnungsempfaenger_id');
    var postSelect = document.getElementById('postadresse_id');
    var reBtn = document.getElementById('re_edit_btn');
    var postBtn = document.getElementById('post_edit_btn');
    var returnTo = encodeURIComponent(window.location.href);
    var baseUrl = '{{ url("/adresse") }}';

    // ═══════════════════════════════════════════════════════════════════════════════
    // Select2 Initialisierung MIT Suchfeld (nur wenn noch nicht initialisiert)
    // ═══════════════════════════════════════════════════════════════════════════════
    if (typeof $ !== 'undefined' && $.fn.select2) {
        // Rechnungsempfänger (nur initialisieren wenn noch nicht Select2)
        var $reSelect = $('#rechnungsempfaenger_id');
        if ($reSelect.length && !$reSelect.hasClass('select2-hidden-accessible')) {
            $reSelect.select2({
                theme: 'bootstrap-5',
                placeholder: '- Adresse wählen -',
                allowClear: true,
                width: '100%',
                language: 'de',
                minimumResultsForSearch: 0  // Suchfeld immer anzeigen
            });
        }

        // Postadresse (nur initialisieren wenn noch nicht Select2)
        var $postSelect = $('#postadresse_id');
        if ($postSelect.length && !$postSelect.hasClass('select2-hidden-accessible')) {
            $postSelect.select2({
                theme: 'bootstrap-5',
                placeholder: '- Adresse wählen -',
                allowClear: true,
                width: '100%',
                language: 'de',
                minimumResultsForSearch: 0  // Suchfeld immer anzeigen
            });
        }
    }

    // Button URL aktualisieren
    function updateButton(select, btn) {
        if (!select || !btn) return;
        var id = select.value;
        if (id) {
            btn.href = baseUrl + '/' + id + '/edit?returnTo=' + returnTo;
            btn.classList.remove('disabled');
        } else {
            btn.href = '#';
            btn.classList.add('disabled');
        }
    }

    // Helper: Text in Element setzen
    function setTextIfExists(id, value) {
        var el = document.getElementById(id);
        if (el) el.textContent = value || '';
    }

    // Vorschau für Rechnungsempfänger aktualisieren
    function updateRePreview(option) {
        if (!option || !option.dataset) return;
        
        setTextIfExists('re-preview-name', option.dataset.name || '(nicht gewählt)');
        setTextIfExists('re-preview-strasse', option.dataset.strasse || '');
        setTextIfExists('re-preview-hausnummer', option.dataset.hausnummer || '');
        setTextIfExists('re-preview-plz', option.dataset.plz || '');
        setTextIfExists('re-preview-wohnort', option.dataset.wohnort || '');
        setTextIfExists('re-preview-provinz', option.dataset.provinz ? '(' + option.dataset.provinz + ')' : '');
        setTextIfExists('re-preview-land', option.dataset.land || '');
        setTextIfExists('re-preview-cf', option.dataset.steuernummer || '-');
        setTextIfExists('re-preview-piva', option.dataset.mwst || '-');
        setTextIfExists('re-preview-sdi', option.dataset.codice || '-');
        setTextIfExists('re-preview-pec', option.dataset.pec || '-');
    }

    // Vorschau für Postadresse aktualisieren
    function updatePostPreview(option) {
        if (!option || !option.dataset) return;
        
        setTextIfExists('post-preview-name', option.dataset.name || '(nicht gewählt)');
        setTextIfExists('post-preview-strasse', option.dataset.strasse || '');
        setTextIfExists('post-preview-hausnummer', option.dataset.hausnummer || '');
        setTextIfExists('post-preview-plz', option.dataset.plz || '');
        setTextIfExists('post-preview-wohnort', option.dataset.wohnort || '');
        setTextIfExists('post-preview-provinz', option.dataset.provinz ? '(' + option.dataset.provinz + ')' : '');
        setTextIfExists('post-preview-land', option.dataset.land || '');
        setTextIfExists('post-preview-email', option.dataset.email || '-');
        setTextIfExists('post-preview-pec', option.dataset.pec || '-');
    }

    // Handler für Select-Änderung
    function handleReChange() {
        updateButton(reSelect, reBtn);
        var selectedOption = reSelect.options[reSelect.selectedIndex];
        updateRePreview(selectedOption);
    }

    function handlePostChange() {
        updateButton(postSelect, postBtn);
        var selectedOption = postSelect.options[postSelect.selectedIndex];
        updatePostPreview(selectedOption);
    }

    // Initial Button-Status setzen
    updateButton(reSelect, reBtn);
    updateButton(postSelect, postBtn);

    // ═══════════════════════════════════════════════════════════════════════════════
    // Event-Listener für Select2
    // ═══════════════════════════════════════════════════════════════════════════════
    
    if (typeof $ !== 'undefined' && $.fn.select2) {
        // Rechnungsempfänger
        $('#rechnungsempfaenger_id').on('select2:select select2:clear', handleReChange);
        
        // Postadresse
        $('#postadresse_id').on('select2:select select2:clear', handlePostChange);
    } else {
        // Fallback für native Select
        if (reSelect) reSelect.addEventListener('change', handleReChange);
        if (postSelect) postSelect.addEventListener('change', handlePostChange);
    }

});
</script>
