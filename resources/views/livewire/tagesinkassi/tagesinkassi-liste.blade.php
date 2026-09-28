{{--
════════════════════════════════════════════════════════════════════════════
DATEI: tagesinkassi-liste.blade.php
PFAD:  resources/views/livewire/tagesinkassi/tagesinkassi-liste.blade.php
════════════════════════════════════════════════════════════════════════════
--}}
@php
    $eur = fn($v) => number_format((float) $v, 2, ',', '.') . ' €';
@endphp

<div class="container-fluid py-2 py-md-4"
     x-data
     x-on:fokus-betrag.window="$nextTick(() => { $refs.betrag10?.focus(); })">

    <style>
        .inkasso-tabelle td, .inkasso-tabelle th { vertical-align: middle; }
        .inkasso-tabelle .betrag { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
        .inkasso-tabelle tr.wochenende td:first-child { color: #6c757d; }
        .inkasso-tabelle tfoot td { font-weight: 700; background: #f1f3f5; }
        .inkasso-tabelle input.betrag { text-align: right; }
        .stat-number { font-size: 1.25rem; font-weight: 700; line-height: 1.2; font-variant-numeric: tabular-nums; }
        .stat-label  { font-size: 0.75rem; color: #6c757d; }
        @media (min-width: 768px) { .stat-number { font-size: 1.6rem; } }
    </style>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show py-2" role="alert">
            <i class="bi bi-check-circle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show py-2" role="alert">
            <i class="bi bi-exclamation-triangle"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h1 class="h5 mb-0">
                <i class="bi bi-cash-coin text-primary"></i> Tagesinkassi
            </h1>
            <p class="text-muted mb-0 small">Registro dei corrispettivi – {{ $monatLabel }}</p>
        </div>
        <div class="d-flex gap-1 gap-sm-2 flex-wrap">
            <a href="{{ route('tagesinkassi.pdf.monat', [$jahr, $monat]) }}" target="_blank"
               class="btn btn-outline-danger btn-sm {{ $eintraege->isEmpty() ? 'disabled' : '' }}"
               title="Registro für {{ $monatLabel }} als PDF">
                <i class="bi bi-file-earmark-pdf"></i>
                <span class="d-none d-sm-inline">PDF Monat</span>
            </a>
            <a href="{{ route('tagesinkassi.pdf.jahr', $jahr) }}" target="_blank"
               class="btn btn-outline-danger btn-sm {{ $tageJahr === 0 ? 'disabled' : '' }}"
               title="Alle Monate {{ $jahr }} + Jahresübersicht als PDF">
                <i class="bi bi-file-earmark-pdf-fill"></i>
                <span class="d-none d-sm-inline">PDF Jahr {{ $jahr }}</span>
            </a>
        </div>
    </div>

    {{-- Monatsauswahl --}}
    <div class="card shadow-sm mb-3">
        <div class="card-body py-2">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <button wire:click="monatWechseln(-1)" class="btn btn-sm btn-outline-secondary" title="Vormonat">
                    <i class="bi bi-chevron-left"></i>
                </button>
                <select wire:model.live="monat" class="form-select form-select-sm" style="width: auto;">
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}">{{ \Carbon\Carbon::create(2000, $m, 1)->locale('de')->isoFormat('MMMM') }}</option>
                    @endfor
                </select>
                <select wire:model.live="jahr" class="form-select form-select-sm" style="width: auto;">
                    @for($y = date('Y') + 1; $y >= 2020; $y--)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endfor
                </select>
                <button wire:click="monatWechseln(1)" class="btn btn-sm btn-outline-secondary" title="Nächster Monat">
                    <i class="bi bi-chevron-right"></i>
                </button>
                <button wire:click="aktuellerMonat" class="btn btn-sm btn-link text-decoration-none">
                    Aktueller Monat
                </button>
            </div>
        </div>
    </div>

    {{-- Summen --}}
    <div class="row g-2 mb-3">
        <div class="col-6 col-md-3">
            <div class="card h-100"><div class="card-body text-center py-2 px-1">
                <div class="stat-number">{{ $eur($summe10) }}</div>
                <div class="stat-label">Monat 10 %</div>
            </div></div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100"><div class="card-body text-center py-2 px-1">
                <div class="stat-number">{{ $eur($summe22) }}</div>
                <div class="stat-label">Monat 22 %</div>
            </div></div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-primary h-100"><div class="card-body text-center py-2 px-1">
                <div class="stat-number text-primary">{{ $eur($summeMonat) }}</div>
                <div class="stat-label">Summe {{ $monatLabel }} ({{ $eintraege->count() }} Tage)</div>
            </div></div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100"><div class="card-body text-center py-2 px-1">
                <div class="stat-number">{{ $eur($summeJahr) }}</div>
                <div class="stat-label">Summe Jahr {{ $jahr }} ({{ $tageJahr }} Tage)</div>
            </div></div>
        </div>
    </div>

    {{-- Neuer Eintrag --}}
    <div class="card shadow-sm mb-3 border-success">
        <div class="card-header bg-success bg-opacity-10 py-2">
            <h6 class="mb-0"><i class="bi bi-plus-circle text-success"></i> Tagesinkasso erfassen</h6>
        </div>
        <div class="card-body py-2">
            <form wire:submit="speichern">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-sm-6 col-md-3">
                        <label class="form-label small mb-1">
                            Datum
                            @if($neuWochentag)
                                <span class="text-muted">– {{ $neuWochentag }}</span>
                            @endif
                        </label>
                        <input type="date" wire:model.live="neuDatum"
                               class="form-control form-control-sm @error('neuDatum') is-invalid @enderror">
                        @error('neuDatum') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1">Betrag 10 % (brutto)</label>
                        <div class="input-group input-group-sm has-validation">
                            <input type="text" inputmode="decimal" wire:model="neuBetrag10" x-ref="betrag10"
                                   class="form-control betrag text-end @error('neuBetrag10') is-invalid @enderror"
                                   placeholder="0,00" autofocus>
                            <span class="input-group-text">€</span>
                            @error('neuBetrag10') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1">Betrag 22 % (brutto)</label>
                        <div class="input-group input-group-sm has-validation">
                            <input type="text" inputmode="decimal" wire:model="neuBetrag22"
                                   class="form-control text-end @error('neuBetrag22') is-invalid @enderror"
                                   placeholder="0,00">
                            <span class="input-group-text">€</span>
                            @error('neuBetrag22') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label small mb-1">Bemerkung</label>
                        <input type="text" wire:model="neuBemerkung" maxlength="255"
                               class="form-control form-control-sm" placeholder="optional">
                    </div>
                    <div class="col-12 col-md-2 d-grid">
                        <button type="submit" class="btn btn-success btn-sm">
                            <span wire:loading.remove wire:target="speichern"><i class="bi bi-check-lg"></i> Speichern</span>
                            <span wire:loading wire:target="speichern"><span class="spinner-border spinner-border-sm"></span></span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Tabelle --}}
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0 inkasso-tabelle">
                    <thead class="table-dark">
                        <tr>
                            <th style="min-width: 220px;">Datum</th>
                            <th class="betrag" style="min-width: 120px;">Betrag 10 %</th>
                            <th class="betrag" style="min-width: 120px;">Betrag 22 %</th>
                            <th class="betrag" style="min-width: 110px;">Totale</th>
                            <th style="min-width: 160px;">Bemerkung</th>
                            <th class="text-center" style="width: 100px;">Aktion</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($eintraege as $e)
                            @if($editId === $e->id)
                                {{-- Bearbeitungszeile --}}
                                <tr class="table-warning" wire:key="edit-{{ $e->id }}"
                                    wire:keydown.enter.prevent="aktualisieren"
                                    wire:keydown.escape="abbrechen">
                                    <td>
                                        <input type="date" wire:model="editDatum"
                                               class="form-control form-control-sm @error('editDatum') is-invalid @enderror">
                                        @error('editDatum') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </td>
                                    <td>
                                        <input type="text" inputmode="decimal" wire:model="editBetrag10"
                                               class="form-control form-control-sm betrag @error('editBetrag10') is-invalid @enderror">
                                        @error('editBetrag10') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </td>
                                    <td>
                                        <input type="text" inputmode="decimal" wire:model="editBetrag22"
                                               class="form-control form-control-sm betrag @error('editBetrag22') is-invalid @enderror">
                                        @error('editBetrag22') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </td>
                                    <td class="betrag text-muted">–</td>
                                    <td>
                                        <input type="text" wire:model="editBemerkung" maxlength="255"
                                               class="form-control form-control-sm">
                                    </td>
                                    <td class="text-center text-nowrap">
                                        <button wire:click="aktualisieren" class="btn btn-sm btn-success" title="Speichern">
                                            <i class="bi bi-check-lg"></i>
                                        </button>
                                        <button wire:click="abbrechen" class="btn btn-sm btn-outline-secondary" title="Abbrechen">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </td>
                                </tr>
                            @else
                                <tr wire:key="row-{{ $e->id }}" class="{{ $e->datum->isWeekend() ? 'wochenende' : '' }}"
                                    wire:dblclick="bearbeiten({{ $e->id }})">
                                    <td class="text-nowrap">{{ $e->datum->locale('de')->isoFormat('dddd, D. MMMM YYYY') }}</td>
                                    <td class="betrag">{{ $eur($e->betrag_10) }}</td>
                                    <td class="betrag">{{ $eur($e->betrag_22) }}</td>
                                    <td class="betrag fw-semibold">{{ $eur($e->gesamt) }}</td>
                                    <td class="small">{{ $e->bemerkung }}</td>
                                    <td class="text-center text-nowrap">
                                        <button wire:click="bearbeiten({{ $e->id }})"
                                                class="btn btn-sm btn-outline-secondary" title="Bearbeiten">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button wire:click="loeschen({{ $e->id }})"
                                                wire:confirm="Eintrag vom {{ $e->datum->format('d.m.Y') }} wirklich löschen?"
                                                class="btn btn-sm btn-outline-danger" title="Löschen">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox fs-1 d-block mb-2 opacity-25"></i>
                                    Für {{ $monatLabel }} ist noch nichts erfasst. Oben den ersten Betrag eingeben.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($eintraege->isNotEmpty())
                        <tfoot>
                            <tr>
                                <td>Summe {{ $monatLabel }}</td>
                                <td class="betrag">{{ $eur($summe10) }}</td>
                                <td class="betrag">{{ $eur($summe22) }}</td>
                                <td class="betrag">{{ $eur($summeMonat) }}</td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

</div>
