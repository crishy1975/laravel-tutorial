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
    <div class="col-12 col-lg-4">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-header bg-secondary text-white py-2">
                <h6 class="mb-0"><i class="bi bi-building"></i> Gebäude</h6>
            </div>
            <div class="card-body small">
                <div class="mb-1"><strong>Codex:</strong> {{ $rechnung->geb_codex ?: '-' }}</div>
                <div class="mb-1"><strong>Name:</strong> {{ $rechnung->geb_name ?: '-' }}</div>
                <div class="mb-1"><strong>Adresse:</strong> {{ $rechnung->geb_adresse ?: '-' }}</div>
                @if($rechnung->gebaeude_id && Route::has('gebaeude.edit'))
                <a href="{{ route('gebaeude.edit', $rechnung->gebaeude_id) }}" class="btn btn-sm btn-outline-secondary mt-2">
                    <i class="bi bi-box-arrow-up-right"></i> <span class="d-none d-sm-inline">Gebäude</span> öffnen
                </a>
                @endif
            </div>
        </div>
    </div>

    {{-- Rechnungsempfänger (nur Anzeige) --}}
    <div class="col-12 col-lg-4">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-header bg-primary text-white py-2">
                <h6 class="mb-0"><i class="bi bi-person-circle"></i> Rechnungsempfänger</h6>
            </div>
            <div class="card-body small">
                <address class="mb-0">
                    <strong>{{ $rechnung->re_name ?: '(noch nicht angegeben)' }}</strong><br>
                    @if($rechnung->re_strasse){{ $rechnung->re_strasse }} {{ $rechnung->re_hausnummer }}<br>@endif
                    @if($rechnung->re_plz || $rechnung->re_wohnort){{ $rechnung->re_plz }} {{ $rechnung->re_wohnort }}<br>@endif
                    @if($rechnung->re_land){{ $rechnung->re_land }}<br>@endif
                    @if($rechnung->re_mwst_nummer)<small>MwSt: {{ $rechnung->re_mwst_nummer }}</small>@endif
                </address>
            </div>
        </div>
    </div>

    {{-- Postadresse (nur Anzeige) --}}
    <div class="col-12 col-lg-4">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-header bg-info text-white py-2">
                <h6 class="mb-0"><i class="bi bi-envelope"></i> Postadresse</h6>
            </div>
            <div class="card-body small">
                <address class="mb-0">
                    <strong>{{ $rechnung->post_name ?: '(noch nicht angegeben)' }}</strong><br>
                    @if($rechnung->post_strasse){{ $rechnung->post_strasse }} {{ $rechnung->post_hausnummer }}<br>@endif
                    @if($rechnung->post_plz || $rechnung->post_wohnort){{ $rechnung->post_plz }} {{ $rechnung->post_wohnort }}<br>@endif
                    @if($rechnung->post_land){{ $rechnung->post_land }}<br>@endif
                    @if($rechnung->post_email)<small>E-Mail: {{ $rechnung->post_email }}</small>@endif
                </address>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         RECHNUNGSDATEN
         ═══════════════════════════════════════════════════════════ --}}

    <div class="col-12 col-xl-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-light py-2">
                <h6 class="mb-0"><i class="bi bi-file-text"></i> Rechnungsdaten</h6>
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

                {{-- ⭐ Button: Rechnung ist bezahlt --}}
                @if($rechnung->zahlungsbedingungen?->value !== 'bezahlt')
                <button type="button" class="btn btn-success" id="btnMarkPaid">
                    <i class="bi bi-check-circle"></i> Rechnung ist bezahlt
                </button>
                @endif

                {{-- Button: Löschen --}}
                @if(!$readonly)
                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteRechnungModal">
                    <i class="bi bi-trash"></i> Rechnung löschen
                </button>
                @endif

            </div>

        </div>

        {{-- Mobile: Gestapelte Buttons --}}
        <div class="d-md-none d-grid gap-2">
            
            @if($rechnung->zahlungsbedingungen?->value !== 'bezahlt')
            <button type="button" class="btn btn-success btn-lg" id="btnMarkPaidMobile">
                <i class="bi bi-check-circle"></i> Rechnung ist bezahlt
            </button>
            @endif

            @if($rechnung->gebaeude_id && Route::has('gebaeude.edit'))
            <a href="{{ route('gebaeude.edit', $rechnung->gebaeude_id) }}" class="btn btn-outline-secondary">
                <i class="bi bi-building"></i> Zurück zum Gebäude
            </a>
            @endif

            @if(!$readonly)
            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteRechnungModal">
                <i class="bi bi-trash"></i> Rechnung löschen
            </button>
            @endif
        </div>
    </div>
    @endif

</div>

{{-- ═══════════════════════════════════════════════════════════
     MODALS (AUSSERHALB DES FORMULARS!)
     ═══════════════════════════════════════════════════════════ --}}

{{-- Modal: Rechnung löschen --}}
@if($rechnung->exists && !$readonly)
<div class="modal fade" id="deleteRechnungModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Rechnung löschen?</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle"></i>
                    Diese Aktion kann nicht rückgängig gemacht werden!
                </div>
                <p>Möchten Sie die Rechnung <strong>{{ $rechnung->rechnungsnummer }}</strong> wirklich löschen?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                <button type="button" class="btn btn-danger" id="btnConfirmDelete"
                    data-delete-url="{{ route('rechnung.destroy', $rechnung->id) }}">
                    Ja, löschen
                </button>
            </div>
        </div>
    </div>
</div>

{{-- JavaScript: Löschen --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const btnDelete = document.getElementById('btnConfirmDelete');
        if (btnDelete) {
            btnDelete.addEventListener('click', function() {
                const deleteUrl = this.dataset.deleteUrl;

                const form = document.createElement('form');
                form.method = 'POST';
                form.action = deleteUrl;

                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '_token';
                csrfInput.value = '{{ csrf_token() }}';
                form.appendChild(csrfInput);

                const methodInput = document.createElement('input');
                methodInput.type = 'hidden';
                methodInput.name = '_method';
                methodInput.value = 'DELETE';
                form.appendChild(methodInput);

                document.body.appendChild(form);
                form.submit();
            });
        }
    });
</script>
@endif

{{-- ⭐ JavaScript: Button "Rechnung ist bezahlt" (für Desktop UND Mobile) --}}
@if($rechnung->exists && $rechnung->zahlungsbedingungen?->value !== 'bezahlt')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Funktion für beide Buttons (Desktop + Mobile)
        function handleMarkPaid(e) {
            e.preventDefault();

            if (!confirm('Rechnung als bezahlt markieren?')) {
                return;
            }

            // Hidden Input für Zahlungsbedingungen setzen
            const zahlungsbedingungenHidden = document.getElementById('zahlungsbedingungen_hidden');
            if (zahlungsbedingungenHidden) {
                zahlungsbedingungenHidden.value = 'bezahlt';
            }

            // Select visuell aktualisieren
            const zahlungsbedingungenSelect = document.getElementById('zahlungsbedingungen_select');
            if (zahlungsbedingungenSelect) {
                zahlungsbedingungenSelect.value = 'bezahlt';
            }

            // Hidden Input für bezahlt_am setzen
            const bezahltAmHidden = document.getElementById('bezahlt_am_hidden');
            if (bezahltAmHidden) {
                const heute = new Date().toISOString().split('T')[0];
                bezahltAmHidden.value = heute;
            }

            // Display-Feld visuell aktualisieren
            const bezahltAmDisplay = document.getElementById('bezahlt_am_display');
            if (bezahltAmDisplay) {
                const heute = new Date().toISOString().split('T')[0];
                bezahltAmDisplay.value = heute;
            }

            // Hauptformular submitten
            const mainForm = document.getElementById('rechnungForm');
            if (mainForm) {
                mainForm.submit();
            } else {
                alert('Formular nicht gefunden!');
            }
        }

        // Desktop Button
        const btn = document.getElementById('btnMarkPaid');
        if (btn) {
            btn.addEventListener('click', handleMarkPaid);
        }

        // Mobile Button
        const btnMobile = document.getElementById('btnMarkPaidMobile');
        if (btnMobile) {
            btnMobile.addEventListener('click', handleMarkPaid);
        }
    });
</script>
@endif
