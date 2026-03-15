{{-- resources/views/livewire/messungen/messungen-liste.blade.php --}}
<div class="container-fluid py-2 py-md-4">

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show py-2" role="alert">
            <i class="bi bi-check-circle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 h3-md mb-0">
                <i class="bi bi-speedometer2 text-primary"></i>
                Messungen
            </h1>
            <p class="text-muted mb-0 small d-none d-md-block">
                {{ $statistik['total'] }} Messungen in {{ $filterJahr }}
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('messungen.import') }}" class="btn btn-success">
                <i class="bi bi-file-earmark-arrow-up"></i>
                <span class="d-none d-sm-inline">XML Import</span>
            </a>
        </div>
    </div>

    {{-- Statistik-Karten --}}
    <div class="row g-2 mb-3">
        <div class="col-6 col-md-3">
            <div class="card border-primary h-100 stat-card">
                <div class="card-body text-center py-2">
                    <div class="stat-number text-primary">{{ $statistik['total'] }}</div>
                    <div class="stat-label">Gesamt</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-success h-100 stat-card cursor-pointer"
                 wire:click="$set('filterErgebnis', '1')" role="button">
                <div class="card-body text-center py-2">
                    <div class="stat-number text-success">{{ $statistik['positiv'] }}</div>
                    <div class="stat-label">Positiv</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-danger h-100 stat-card cursor-pointer"
                 wire:click="$set('filterErgebnis', '0')" role="button">
                <div class="card-body text-center py-2">
                    <div class="stat-number text-danger">{{ $statistik['negativ'] }}</div>
                    <div class="stat-label">Negativ</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-warning h-100 stat-card cursor-pointer"
                 wire:click="$set('filterOhneAnlage', '1')" role="button">
                <div class="card-body text-center py-2">
                    <div class="stat-number text-warning">{{ $statistik['ohneAnlage'] }}</div>
                    <div class="stat-label">Ohne Anlage</div>
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
                    $activeFilters = collect([$filterKodex, $filterName, $filterDatumVon, $filterDatumBis, $filterErgebnis, $filterBrennstoff, $filterOhneAnlage])->filter(fn($v) => $v !== '')->count();
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
                    <div class="col-6 col-md-1">
                        <label class="form-label small mb-1">Jahr</label>
                        <input type="number" wire:model.live="filterJahr"
                               class="form-control form-control-sm" min="2020" max="2030">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1">Kodex</label>
                        <input type="text" wire:model.live.debounce.300ms="filterKodex"
                               class="form-control form-control-sm" placeholder="Kodex...">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1">Kunde/Name</label>
                        <input type="text" wire:model.live.debounce.300ms="filterName"
                               class="form-control form-control-sm" placeholder="Name...">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1">Brennstoff</label>
                        <select wire:model.live="filterBrennstoff" class="form-select form-select-sm">
                            <option value="">Alle</option>
                            @foreach($brennstoffe as $key => $value)
                                <option value="{{ $key }}">{{ $value['text'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-4 col-md-1">
                        <label class="form-label small mb-1">Ergebnis</label>
                        <select wire:model.live="filterErgebnis" class="form-select form-select-sm">
                            <option value="">Alle</option>
                            <option value="1">Positiv</option>
                            <option value="0">Negativ</option>
                        </select>
                    </div>
                    <div class="col-4 col-md-1">
                        <label class="form-label small mb-1">Anlage</label>
                        <select wire:model.live="filterOhneAnlage" class="form-select form-select-sm">
                            <option value="">Alle</option>
                            <option value="0">Mit</option>
                            <option value="1">Ohne</option>
                        </select>
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
    @if($messungen->isEmpty())
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-inbox display-4 text-muted"></i>
                <p class="text-muted mt-2 mb-0">Keine Messungen gefunden.</p>
                <a href="{{ route('messungen.import') }}" class="btn btn-success mt-3">
                    <i class="bi bi-file-earmark-arrow-up"></i> XML importieren
                </a>
            </div>
        </div>
    @else
        <div class="card shadow-sm">
            {{-- Card Header --}}
            <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    {{ $messungen->firstItem() }}–{{ $messungen->lastItem() }} von {{ $messungen->total() }}
                </small>
                <div wire:loading class="spinner-border spinner-border-sm text-primary" role="status">
                    <span class="visually-hidden">Laden...</span>
                </div>
            </div>

            {{-- ========== Desktop: Tabelle ========== --}}
            <div class="table-responsive d-none d-lg-block">
                <table class="table table-hover table-sm mb-0" id="messungenTable">
                    <thead class="table-light">
                        <tr>
                            <th wire:click="sortBy('cIM_CODICE')" style="width: 90px;">
                                Kodex
                                @if($sortField === 'cIM_CODICE')
                                    <i class="bi bi-chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                @endif
                            </th>
                            <th wire:click="sortBy('cMIS_DATA')" style="width: 100px;">
                                Datum
                                @if($sortField === 'cMIS_DATA')
                                    <i class="bi bi-chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                @endif
                            </th>
                            <th style="width: 60px;" class="text-center">Stadio</th>
                            <th>Name</th>
                            <th style="width: 120px;">Brennstoff</th>
                            <th style="width: 70px;" class="text-end">O₂ %</th>
                            <th style="width: 70px;" class="text-end">CO mg</th>
                            <th style="width: 70px;" class="text-end">NOx mg</th>
                            <th style="width: 70px;" class="text-center">Ergebnis</th>
                            <th style="width: 60px;" class="text-center">Anlage</th>
                            <th style="width: 100px;" class="text-end">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($messungen as $messung)
                            @php
                                $rowClass = '';
                                if ($messung->strEsito === '0') {
                                    $rowClass = 'table-danger'; // Negativ = rot
                                } elseif ($messung->codeInImpianti === 0) {
                                    $rowClass = 'table-warning'; // Ohne Anlage = gelb
                                }
                            @endphp
                            <tr class="{{ $rowClass }}">
                                <td>
                                    <a href="{{ route('messungen.edit', $messung->id) }}"
                                       class="fw-bold text-decoration-none font-monospace">{{ $messung->cIM_CODICE }}</a>
                                </td>
                                <td>{{ $messung->cMIS_DATA2 }}</td>
                                <td class="text-center">{{ $messung->cMIS_STADIO }}</td>
                                <td>{{ Str::limit($messung->cIM_NAME, 30) }}</td>
                                <td>
                                    <span class="badge bg-secondary">{{ $messung->cMIS_COMBUSTIBILE_P }}</span>
                                </td>
                                <td class="text-end">{{ $messung->o2 }}</td>
                                <td class="text-end {{ $messung->co > 500 ? 'text-danger fw-bold' : '' }}">{{ $messung->co }}</td>
                                <td class="text-end {{ $messung->nox > 200 ? 'text-warning fw-bold' : '' }}">{{ $messung->nox }}</td>
                                <td class="text-center">
                                    @if($messung->strEsito === '1')
                                        <span class="badge bg-success"><i class="bi bi-check-lg"></i> OK</span>
                                    @else
                                        <span class="badge bg-danger"><i class="bi bi-x-lg"></i> NEG</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($messung->codeInImpianti > 0)
                                        <i class="bi bi-check-circle text-success" title="Anlage verknüpft"></i>
                                    @else
                                        <i class="bi bi-question-circle text-warning" title="Keine Anlage"></i>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('messungen.edit', $messung->id) }}"
                                           class="btn btn-outline-primary" title="Bearbeiten">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button wire:click="openAnlageModal({{ $messung->id }})"
                                                class="btn btn-outline-warning" title="Anlage zuordnen">
                                            <i class="bi bi-link-45deg"></i>
                                        </button>
                                        <button wire:click="delete({{ $messung->id }})"
                                                wire:confirm="Messung wirklich löschen?"
                                                class="btn btn-outline-danger" title="Löschen">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- ========== Mobile: Card-Liste ========== --}}
            <div class="d-lg-none">
                @foreach($messungen as $messung)
                    @php
                        $cardClass = '';
                        if ($messung->strEsito === '0') {
                            $cardClass = 'bg-danger bg-opacity-10'; // Negativ = rot
                        } elseif ($messung->codeInImpianti === 0) {
                            $cardClass = 'bg-warning bg-opacity-25'; // Ohne Anlage = gelb
                        }
                    @endphp
                    <div class="messung-card border-bottom {{ $cardClass }}">
                        <div class="p-2">
                            {{-- Zeile 1: Kodex, Name, Ergebnis --}}
                            <div class="d-flex align-items-start gap-2 mb-1">
                                <div class="flex-grow-1 min-width-0">
                                    <a href="{{ route('messungen.edit', $messung->id) }}" class="text-decoration-none">
                                        <span class="fw-bold text-primary font-monospace">{{ $messung->cIM_CODICE }}</span>
                                        <span class="text-muted small">/ {{ $messung->cMIS_STADIO }}</span>
                                        <span class="text-dark">{{ Str::limit($messung->cIM_NAME ?: '(kein Name)', 25) }}</span>
                                    </a>
                                </div>
                                <div class="flex-shrink-0">
                                    @if($messung->strEsito === '1')
                                        <span class="badge bg-success"><i class="bi bi-check-lg"></i></span>
                                    @else
                                        <span class="badge bg-danger"><i class="bi bi-x-lg"></i></span>
                                    @endif
                                </div>
                            </div>

                            {{-- Zeile 2: Datum, Brennstoff, Anlage-Status --}}
                            <div class="small text-muted mb-1 ps-1 d-flex gap-2">
                                <span><i class="bi bi-calendar"></i> {{ $messung->cMIS_DATA2 }}</span>
                                <span class="badge bg-secondary">{{ $messung->cMIS_COMBUSTIBILE_P }}</span>
                                @if($messung->codeInImpianti === 0)
                                    <span class="badge bg-warning text-dark">Ohne Anlage</span>
                                @endif
                            </div>

                            {{-- Zeile 3: Messwerte + Aktionen --}}
                            <div class="d-flex justify-content-between align-items-center ps-1">
                                <div class="small">
                                    <span class="me-2">O₂: {{ $messung->o2 }}%</span>
                                    <span class="me-2 {{ $messung->co > 500 ? 'text-danger fw-bold' : '' }}">CO: {{ $messung->co }}</span>
                                    <span class="{{ $messung->nox > 200 ? 'text-warning fw-bold' : '' }}">NOx: {{ $messung->nox }}</span>
                                </div>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('messungen.edit', $messung->id) }}"
                                       class="btn btn-outline-primary py-0 px-2">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button wire:click="openAnlageModal({{ $messung->id }})"
                                            class="btn btn-outline-warning py-0 px-2">
                                        <i class="bi bi-link-45deg"></i>
                                    </button>
                                    <button wire:click="delete({{ $messung->id }})"
                                            wire:confirm="Messung wirklich löschen?"
                                            class="btn btn-outline-danger py-0 px-2">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            @if($messungen->hasPages())
                <div class="card-footer bg-light py-2">
                    <div class="d-flex justify-content-center">
                        {{ $messungen->links() }}
                    </div>
                </div>
            @endif
        </div>
    @endif

    {{-- ========== Modal: Anlage zuordnen ========== --}}
    @if($showAnlageModal)
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-lg modal-dialog-scrollable modal-fullscreen-sm-down">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white py-2">
                        <h5 class="modal-title">
                            <i class="bi bi-link-45deg"></i> Anlage zuordnen
                        </h5>
                        <button type="button" class="btn-close btn-close-white" wire:click="closeAnlageModal"></button>
                    </div>
                    <div class="modal-body p-2 p-md-3">
                        {{-- Aktuelle Messung Info --}}
                        @if($selectedMessung)
                            <div class="alert alert-info py-2 mb-2">
                                <div class="d-flex flex-wrap gap-2 small">
                                    <span><strong>Kodex:</strong> {{ $selectedMessung->cIM_CODICE }}</span>
                                    <span><strong>Datum:</strong> {{ $selectedMessung->cMIS_DATA2 }}</span>
                                </div>
                                <div class="small mt-1">
                                    <strong>Name:</strong> {{ $selectedMessung->cIM_NAME ?: '(kein Name)' }}
                                </div>
                            </div>
                        @endif

                        {{-- Suchformular --}}
                        <div class="card mb-2">
                            <div class="card-header bg-light py-1 px-2">
                                <h6 class="mb-0 small"><i class="bi bi-search"></i> Anlage suchen</h6>
                            </div>
                            <div class="card-body py-2 px-2">
                                <div class="row g-2">
                                    <div class="col-12 col-md-4">
                                        <input type="text" wire:model.live.debounce.300ms="anlageSearchName"
                                               class="form-control form-control-sm" placeholder="Name/Aufstellungsort">
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <input type="text" wire:model.live.debounce.300ms="anlageSearchOrt"
                                               class="form-control form-control-sm" placeholder="Gemeinde">
                                    </div>
                                    <div class="col-4 col-md-3">
                                        <input type="text" wire:model.live.debounce.300ms="anlageSearchStrasse"
                                               class="form-control form-control-sm" placeholder="Straße">
                                    </div>
                                    <div class="col-2 col-md-2">
                                        <input type="text" wire:model.live.debounce.300ms="anlageSearchNummer"
                                               class="form-control form-control-sm" placeholder="Nr.">
                                    </div>
                                </div>
                                <div class="mt-2 d-flex gap-2">
                                    <button type="button" wire:click="searchAnlagen" class="btn btn-primary btn-sm flex-grow-1 flex-md-grow-0">
                                        <i class="bi bi-search"></i> Suchen
                                    </button>
                                    <button type="button" wire:click="resetAnlageSearch" class="btn btn-outline-secondary btn-sm">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                    <span wire:loading wire:target="searchAnlagen" class="ms-2 align-self-center">
                                        <span class="spinner-border spinner-border-sm"></span>
                                    </span>
                                </div>
                            </div>
                        </div>

                        {{-- Suchergebnisse --}}
                        @if(count($anlageSearchResults) > 0)
                            {{-- Desktop: Tabelle --}}
                            <div class="d-none d-md-block table-responsive" style="max-height: 350px; overflow-y: auto;">
                                <table class="table table-hover table-sm mb-0">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th>Kodex</th>
                                            <th>Aufstellungsort</th>
                                            <th>Ort / Straße</th>
                                            <th>Kessel</th>
                                            <th class="text-center">Bj.</th>
                                            <th class="text-end">kW</th>
                                            <th class="text-end">Aktion</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($anlageSearchResults as $anlage)
                                            <tr>
                                                <td class="font-monospace fw-bold">{{ $anlage->Feld_a }}</td>
                                                <td>{{ Str::limit($anlage->Feld_w, 20) }}</td>
                                                <td>
                                                    <small class="text-muted">{{ $anlage->Feld_i }}</small><br>
                                                    {{ Str::limit($anlage->Feld_m, 15) }} {{ $anlage->Feld_n }}
                                                </td>
                                                <td>{{ Str::limit($anlage->Feld_y, 15) }}</td>
                                                <td class="text-center">{{ $anlage->Feld_z }}</td>
                                                <td class="text-end">{{ $anlage->Feld_ab }}</td>
                                                <td class="text-end">
                                                    <button wire:click="zuordnenAnlage('{{ $anlage->Feld_a }}')"
                                                            class="btn btn-success btn-sm py-0">
                                                        <i class="bi bi-check-lg"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            {{-- Mobile: Card-Liste --}}
                            <div class="d-md-none" style="max-height: calc(100vh - 320px); overflow-y: auto;">
                                @foreach($anlageSearchResults as $anlage)
                                    <div class="card mb-2 anlage-card">
                                        <div class="card-body p-2">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="flex-grow-1 min-width-0">
                                                    <div class="d-flex align-items-center gap-2 mb-1">
                                                        <span class="badge bg-primary font-monospace">{{ $anlage->Feld_a }}</span>
                                                        <span class="fw-bold text-truncate">{{ $anlage->Feld_w }}</span>
                                                    </div>
                                                    <div class="small text-muted mb-1">
                                                        <i class="bi bi-geo-alt"></i> 
                                                        {{ $anlage->Feld_i }}, {{ $anlage->Feld_m }} {{ $anlage->Feld_n }}
                                                    </div>
                                                    <div class="d-flex flex-wrap gap-2 small">
                                                        @if($anlage->Feld_y)
                                                            <span><i class="bi bi-fire"></i> {{ Str::limit($anlage->Feld_y, 15) }}</span>
                                                        @endif
                                                        @if($anlage->Feld_z)
                                                            <span><i class="bi bi-calendar"></i> {{ $anlage->Feld_z }}</span>
                                                        @endif
                                                        @if($anlage->Feld_ab)
                                                            <span><i class="bi bi-lightning"></i> {{ $anlage->Feld_ab }} kW</span>
                                                        @endif
                                                    </div>
                                                </div>
                                                <button wire:click="zuordnenAnlage('{{ $anlage->Feld_a }}')"
                                                        class="btn btn-success btn-sm ms-2 flex-shrink-0">
                                                    <i class="bi bi-check-lg"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            {{-- Pagination --}}
                            @php
                                $perPage = 10;
                                $maxPage = ceil($anlageSearchTotal / $perPage);
                                $von = (($anlageSearchPage - 1) * $perPage) + 1;
                                $bis = min($anlageSearchPage * $perPage, $anlageSearchTotal);
                            @endphp
                            <div class="d-flex flex-wrap justify-content-between align-items-center mt-2 gap-2">
                                <div class="text-muted small">
                                    {{ $von }}-{{ $bis }} von {{ $anlageSearchTotal }} Anlagen
                                </div>
                                @if($maxPage > 1)
                                    <nav>
                                        <ul class="pagination pagination-sm mb-0">
                                            {{-- Zurück --}}
                                            <li class="page-item {{ $anlageSearchPage <= 1 ? 'disabled' : '' }}">
                                                <button class="page-link" wire:click="anlagePagePrev" {{ $anlageSearchPage <= 1 ? 'disabled' : '' }}>
                                                    <i class="bi bi-chevron-left"></i>
                                                </button>
                                            </li>
                                            
                                            {{-- Seiten (max 5 sichtbar) --}}
                                            @php
                                                $start = max(1, $anlageSearchPage - 2);
                                                $end = min($maxPage, $start + 4);
                                                if ($end - $start < 4) {
                                                    $start = max(1, $end - 4);
                                                }
                                            @endphp
                                            
                                            @if($start > 1)
                                                <li class="page-item">
                                                    <button class="page-link" wire:click="anlageGoToPage(1)">1</button>
                                                </li>
                                                @if($start > 2)
                                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                                @endif
                                            @endif
                                            
                                            @for($i = $start; $i <= $end; $i++)
                                                <li class="page-item {{ $i == $anlageSearchPage ? 'active' : '' }}">
                                                    <button class="page-link" wire:click="anlageGoToPage({{ $i }})">{{ $i }}</button>
                                                </li>
                                            @endfor
                                            
                                            @if($end < $maxPage)
                                                @if($end < $maxPage - 1)
                                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                                @endif
                                                <li class="page-item">
                                                    <button class="page-link" wire:click="anlageGoToPage({{ $maxPage }})">{{ $maxPage }}</button>
                                                </li>
                                            @endif
                                            
                                            {{-- Weiter --}}
                                            <li class="page-item {{ $anlageSearchPage >= $maxPage ? 'disabled' : '' }}">
                                                <button class="page-link" wire:click="anlagePageNext" {{ $anlageSearchPage >= $maxPage ? 'disabled' : '' }}>
                                                    <i class="bi bi-chevron-right"></i>
                                                </button>
                                            </li>
                                        </ul>
                                    </nav>
                                @endif
                            </div>
                        @elseif($anlageSearchName || $anlageSearchOrt || $anlageSearchStrasse || $anlageSearchNummer)
                            <div class="text-center text-muted py-3">
                                <i class="bi bi-inbox display-6"></i>
                                <p class="mb-0 mt-2">Keine Anlagen gefunden.</p>
                            </div>
                        @else
                            <div class="text-center text-muted py-3">
                                <i class="bi bi-search display-6"></i>
                                <p class="mb-0 mt-2">Gib Suchkriterien ein und klicke auf "Suchen".</p>
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" wire:click="closeAnlageModal" class="btn btn-secondary btn-sm">
                            <i class="bi bi-x-lg"></i> Schließen
                        </button>
                    </div>
                </div>
            </div>
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
    .messung-card { transition: background-color 0.15s; }
    .messung-card:active { background-color: rgba(0, 123, 255, 0.05); }
    .transition-transform { transition: transform 0.3s; }
    [aria-expanded="false"] .transition-transform { transform: rotate(-90deg); }
    #messungenTable thead th { cursor: pointer; user-select: none; }
    #messungenTable thead th:hover { background-color: rgba(255,255,255,0.1); }
    #messungenTable tbody tr { cursor: pointer; transition: background-color 0.15s; }
    #messungenTable tbody tr:hover { background-color: rgba(0, 123, 255, 0.05); }
    #messungenTable tbody tr.table-danger:hover { background-color: rgba(220, 53, 69, 0.15); }
    #messungenTable tbody tr.table-warning:hover { background-color: rgba(255, 193, 7, 0.25); }
    @media (max-width: 575.98px) { .container-fluid { padding-left: 0.5rem; padding-right: 0.5rem; } .card-body { padding: 0.5rem; } .form-label { font-size: 0.75rem; } .stat-number { font-size: 1.25rem; } }
</style>
@endpush
