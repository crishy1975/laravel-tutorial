{{-- resources/views/livewire/messungen/anlagen-liste.blade.php --}}
<div class="container-fluid py-2 py-md-4">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 h3-md mb-0">
                <i class="bi bi-gear text-primary"></i>
                Anlagen
            </h1>
            <p class="text-muted mb-0 small d-none d-md-block">
                {{ $statistik['total'] }} Anlagen gesamt
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('messungen.anlagen.import') }}" class="btn btn-success">
                <i class="bi bi-file-earmark-arrow-up"></i>
                <span class="d-none d-sm-inline">CSV Import</span>
            </a>
        </div>
    </div>

    {{-- Statistik-Karten --}}
    <div class="row g-2 mb-3">
        <div class="col-4 col-md-4">
            <div class="card border-primary h-100 stat-card">
                <div class="card-body text-center py-2">
                    <div class="stat-number text-primary">{{ $statistik['total'] }}</div>
                    <div class="stat-label">Gesamt</div>
                </div>
            </div>
        </div>
        <div class="col-4 col-md-4">
            <div class="card border-success h-100 stat-card cursor-pointer"
                 wire:click="$set('filterGemessen', '1')" role="button">
                <div class="card-body text-center py-2">
                    <div class="stat-number text-success">{{ $statistik['mitMessung'] }}</div>
                    <div class="stat-label d-none d-sm-block">Mit Messung {{ $filterJahr }}</div>
                    <div class="stat-label d-sm-none">Mit</div>
                </div>
            </div>
        </div>
        <div class="col-4 col-md-4">
            <div class="card border-danger h-100 stat-card cursor-pointer"
                 wire:click="$set('filterGemessen', '0')" role="button">
                <div class="card-body text-center py-2">
                    <div class="stat-number text-danger">{{ $statistik['ohneMessung'] }}</div>
                    <div class="stat-label d-none d-sm-block">Ohne Messung {{ $filterJahr }}</div>
                    <div class="stat-label d-sm-none">Ohne</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter-Karte --}}
    <div class="card shadow-sm mb-3">
        <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center"
             data-bs-toggle="collapse" data-bs-target="#filterCollapse" role="button" aria-expanded="true">
            <h6 class="mb-0">
                <i class="bi bi-funnel"></i> Filter
                @php
                    $activeFilters = collect([$filterKodex, $filterBeschreibung, $filterOrt, $filterStrasse, $filterHersteller, $filterGemessen])->filter()->count();
                @endphp
                @if($activeFilters > 0)
                    <span class="badge bg-primary ms-1">{{ $activeFilters }}</span>
                @endif
            </h6>
            <i class="bi bi-chevron-down d-md-none transition-transform"></i>
        </div>
        <div class="collapse show" id="filterCollapse">
            <div class="card-body py-2">
                <div class="row g-2">
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1">Kodex</label>
                        <input type="text" wire:model.live.debounce.300ms="filterKodex"
                               class="form-control form-control-sm" placeholder="Kodex...">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1">Aufstellungsort</label>
                        <input type="text" wire:model.live.debounce.300ms="filterBeschreibung"
                               class="form-control form-control-sm" placeholder="Name...">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1">Gemeinde</label>
                        <input type="text" wire:model.live.debounce.300ms="filterOrt"
                               class="form-control form-control-sm" placeholder="Gemeinde...">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1">Straße</label>
                        <input type="text" wire:model.live.debounce.300ms="filterStrasse"
                               class="form-control form-control-sm" placeholder="Straße...">
                    </div>
                    <div class="col-4 col-md-1">
                        <label class="form-label small mb-1">Messung</label>
                        <select wire:model.live="filterGemessen" class="form-select form-select-sm">
                            <option value="">Alle</option>
                            <option value="1">Ja</option>
                            <option value="0">Nein</option>
                        </select>
                    </div>
                    <div class="col-4 col-md-1">
                        <label class="form-label small mb-1">Jahr</label>
                        <input type="number" wire:model.live="filterJahr"
                               class="form-control form-control-sm" min="2020" max="2030">
                    </div>
                    <div class="col-4 col-md-2 d-flex align-items-end gap-1">
                        @if($activeFilters > 0)
                            <button wire:click="resetFilters" class="btn btn-outline-secondary btn-sm" title="Filter zurücksetzen">
                                <i class="bi bi-x-lg"></i>
                                <span class="d-none d-md-inline">Reset</span>
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Ergebnis --}}
    @if($anlagen->isEmpty())
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-inbox display-4 text-muted"></i>
                <p class="text-muted mt-2 mb-0">Keine Anlagen gefunden.</p>
                <a href="{{ route('messungen.anlagen.import') }}" class="btn btn-success mt-3">
                    <i class="bi bi-file-earmark-arrow-up"></i> CSV importieren
                </a>
            </div>
        </div>
    @else
        <div class="card shadow-sm">
            {{-- Card Header --}}
            <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    {{ $anlagen->firstItem() }}–{{ $anlagen->lastItem() }} von {{ $anlagen->total() }}
                </small>
                <div wire:loading class="spinner-border spinner-border-sm text-primary" role="status">
                    <span class="visually-hidden">Laden...</span>
                </div>
            </div>

            {{-- ========== Desktop: Tabelle ========== --}}
            <div class="table-responsive d-none d-lg-block">
                <table class="table table-hover align-middle mb-0" id="anlagenTable">
                    <thead class="table-dark">
                        <tr>
                            <th style="width: 90px;" wire:click="sortBy('Feld_a')">
                                Kodex
                                @if($sortField === 'Feld_a')
                                    <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                @endif
                            </th>
                            <th wire:click="sortBy('Feld_w')">
                                Aufstellungsort
                                @if($sortField === 'Feld_w')
                                    <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                @endif
                            </th>
                            <th wire:click="sortBy('Feld_i')">
                                Gemeinde
                                @if($sortField === 'Feld_i')
                                    <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                @endif
                            </th>
                            <th wire:click="sortBy('Feld_m')">
                                Straße
                                @if($sortField === 'Feld_m')
                                    <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                @endif
                            </th>
                            <th style="width: 80px;" class="text-center">Messung</th>
                            <th wire:click="sortBy('Feld_y')">
                                Hersteller
                                @if($sortField === 'Feld_y')
                                    <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                @endif
                            </th>
                            <th style="width: 70px;" class="text-center">Baujahr</th>
                            <th style="width: 60px;" class="text-center">kW</th>
                            <th style="width: 100px;" class="text-end">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($anlagen as $anlage)
                            @php $hatMessung = $anlage->messungenHeuer()->exists(); @endphp
                            <tr class="{{ !$hatMessung ? 'table-danger bg-opacity-25' : '' }}">
                                <td>
                                    <a href="{{ route('messungen.anlagen.edit', $anlage->Feld_a) }}"
                                       class="fw-bold text-decoration-none font-monospace">{{ $anlage->Feld_a }}</a>
                                </td>
                                <td>
                                    <a href="{{ route('messungen.anlagen.edit', $anlage->Feld_a) }}"
                                       class="text-decoration-none text-dark">{{ Str::limit($anlage->Feld_w, 40) ?: '(keine Beschreibung)' }}</a>
                                </td>
                                <td>{{ $anlage->Feld_i }}</td>
                                <td>{{ $anlage->Feld_m }} {{ $anlage->Feld_n }}</td>
                                <td class="text-center">
                                    @if($hatMessung)
                                        <span class="badge bg-success"><i class="bi bi-check-lg"></i></span>
                                    @else
                                        <span class="badge bg-danger"><i class="bi bi-x-lg"></i></span>
                                    @endif
                                </td>
                                <td>{{ $anlage->Feld_y }}</td>
                                <td class="text-center">{{ $anlage->Feld_z }}</td>
                                <td class="text-center">{{ $anlage->Feld_ab }}</td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('messungen.anlagen.edit', $anlage->Feld_a) }}"
                                           class="btn btn-outline-primary" title="Bearbeiten">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        {{-- TODO: route('messungen.neu', $anlage->Feld_a) --}}
                                        <a href="{{ route('messungen.anlagen.edit', $anlage->Feld_a) }}"
                                           class="btn btn-outline-success" title="Neue Messung">
                                            <i class="bi bi-plus-lg"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- ========== Mobile: Card-Liste ========== --}}
            <div class="d-lg-none">
                @foreach($anlagen as $anlage)
                    @php $hatMessung = $anlage->messungenHeuer()->exists(); @endphp
                    <div class="anlage-card border-bottom {{ !$hatMessung ? 'bg-danger bg-opacity-10' : '' }}">
                        <div class="p-2">
                            {{-- Zeile 1: Kodex, Aufstellungsort, Messung-Badge --}}
                            <div class="d-flex align-items-start gap-2 mb-1">
                                <div class="flex-grow-1 min-width-0">
                                    <a href="{{ route('messungen.anlagen.edit', $anlage->Feld_a) }}" class="text-decoration-none">
                                        <span class="fw-bold text-primary font-monospace">{{ $anlage->Feld_a }}</span>
                                        <span class="text-dark">{{ Str::limit($anlage->Feld_w ?: '(keine Beschreibung)', 30) }}</span>
                                    </a>
                                </div>
                                <div class="flex-shrink-0">
                                    @if($hatMessung)
                                        <span class="badge bg-success"><i class="bi bi-check-lg"></i></span>
                                    @else
                                        <span class="badge bg-danger"><i class="bi bi-x-lg"></i></span>
                                    @endif
                                </div>
                            </div>

                            {{-- Zeile 2: Adresse Aufstellungsort --}}
                            <div class="small text-muted mb-1 ps-1">
                                <i class="bi bi-geo-alt"></i>
                                {{ $anlage->Feld_m }} {{ $anlage->Feld_n }}{{ ($anlage->Feld_m && $anlage->Feld_i) ? ',' : '' }}
                                {{ $anlage->Feld_i }}
                            </div>

                            {{-- Zeile 3: Hersteller/Baujahr + Aktionen --}}
                            <div class="d-flex justify-content-between align-items-center ps-1">
                                <div class="small text-muted">
                                    @if($anlage->Feld_y)
                                        <i class="bi bi-wrench"></i> {{ $anlage->Feld_y }}
                                        @if($anlage->Feld_z)
                                            ({{ $anlage->Feld_z }})
                                        @endif
                                        @if($anlage->Feld_ab)
                                            &middot; {{ $anlage->Feld_ab }} kW
                                        @endif
                                    @endif
                                </div>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('messungen.anlagen.edit', $anlage->Feld_a) }}"
                                       class="btn btn-outline-primary py-0 px-2">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    {{-- TODO: route('messungen.neu', $anlage->Feld_a) --}}
                                    <a href="{{ route('messungen.anlagen.edit', $anlage->Feld_a) }}"
                                       class="btn btn-outline-success py-0 px-2">
                                        <i class="bi bi-plus-lg"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            @if($anlagen->hasPages())
                <div class="card-footer bg-light py-2">
                    <div class="d-flex justify-content-center">
                        {{ $anlagen->links() }}
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>

@push('styles')
<style>
    .stat-card { transition: transform 0.2s, box-shadow 0.2s; }
    .stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
    .stat-number { font-size: 1.5rem; font-weight: 700; line-height: 1.2; }
    .stat-label { font-size: 0.7rem; color: #6c757d; text-transform: uppercase; letter-spacing: 0.5px; }
    .cursor-pointer { cursor: pointer; }
    .min-width-0 { min-width: 0; }
    @media (min-width: 768px) { .stat-number { font-size: 2rem; } .stat-label { font-size: 0.75rem; } }
    .anlage-card { transition: background-color 0.15s; }
    .anlage-card:active { background-color: rgba(0, 123, 255, 0.05); }
    .transition-transform { transition: transform 0.3s; }
    [aria-expanded="false"] .transition-transform { transform: rotate(-90deg); }
    #anlagenTable thead th { cursor: pointer; user-select: none; }
    #anlagenTable thead th:hover { background-color: rgba(255,255,255,0.1); }
    #anlagenTable tbody tr { cursor: pointer; transition: background-color 0.15s; }
    #anlagenTable tbody tr:hover { background-color: rgba(0, 123, 255, 0.05); }
    #anlagenTable tbody tr.table-danger:hover { background-color: rgba(220, 53, 69, 0.15); }
    @media (max-width: 575.98px) { .container-fluid { padding-left: 0.5rem; padding-right: 0.5rem; } .card-body { padding: 0.5rem; } .form-label { font-size: 0.75rem; } .stat-number { font-size: 1.25rem; } }
    @media print { .btn, #filterCollapse, .card-header[data-bs-toggle], .pagination { display: none !important; } .d-none.d-lg-block { display: block !important; } .d-lg-none { display: none !important; } }
</style>
@endpush
