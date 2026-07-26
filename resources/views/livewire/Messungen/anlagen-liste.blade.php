{{-- resources/views/livewire/messungen/anlagen-liste.blade.php --}}
<div class="container-fluid py-2 py-md-4">

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
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 h3-md mb-0">
                <i class="bi bi-building text-primary"></i>
                Anlagen
            </h1>
            <p class="text-muted mb-0 small d-none d-md-block">
                {{ $statistik['total'] }} Anlagen gesamt
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('messungen.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-speedometer2"></i>
                <span class="d-none d-sm-inline">Messungen</span>
            </a>
            <a href="{{ route('messungen.anlagen.import') }}" class="btn btn-success">
                <i class="bi bi-file-earmark-arrow-up"></i>
                <span class="d-none d-sm-inline">CSV Import</span>
            </a>
            <button type="button" class="btn btn-primary position-relative" wire:click="openAmtExport"
                    @if(count($selectedAnlagen) === 0) title="Zuerst Anlagen ankreuzen" @endif>
                <i class="bi bi-envelope-arrow-up"></i>
                <span class="d-none d-sm-inline">Amt-Export</span>
                @if(count($selectedAnlagen) > 0)
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning text-dark">
                        {{ count($selectedAnlagen) }}
                    </span>
                @endif
            </button>
            <a href="{{ route('messungen.amt-import') }}" class="btn btn-outline-primary">
                <i class="bi bi-envelope-arrow-down"></i>
                <span class="d-none d-sm-inline">Amt-Import</span>
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
                    $activeFilters = collect([$filterKodex, $filterBeschreibung, $filterOrt, $filterStrasse, $filterHersteller, $filterGemessen, $filterExportStatus])->filter()->count();
                    // Status nur als "aktiv" zählen wenn vom Default ('1' = Aktiv) abweicht
                    if ($filterStatus !== '1') {
                        $activeFilters++;
                    }
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
                        <input type="text" wire:model="filterKodex"
                               class="form-control form-control-sm" placeholder="Kodex...">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1">Aufstellungsort</label>
                        <input type="text" wire:model="filterBeschreibung"
                               class="form-control form-control-sm" placeholder="Name...">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1">Gemeinde</label>
                        <input type="text" wire:model="filterOrt"
                               class="form-control form-control-sm" placeholder="Gemeinde...">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1">Straße</label>
                        <input type="text" wire:model="filterStrasse"
                               class="form-control form-control-sm" placeholder="Straße...">
                    </div>
                    <div class="col-4 col-md-1">
                        <label class="form-label small mb-1">Messung</label>
                        <select wire:model="filterGemessen" class="form-select form-select-sm">
                            <option value="">Alle</option>
                            <option value="1">Ja</option>
                            <option value="0">Nein</option>
                        </select>
                    </div>
                    <div class="col-4 col-md-1">
                        <label class="form-label small mb-1">Export</label>
                        <select wire:model="filterExportStatus" class="form-select form-select-sm">
                            <option value="">Alle</option>
                            <option value="1">Exportiert</option>
                            <option value="0">Nicht exp.</option>
                        </select>
                    </div>
                    <div class="col-4 col-md-1">
                        <label class="form-label small mb-1">Status</label>
                        <select wire:model="filterStatus" class="form-select form-select-sm">
                            <option value="1">Aktiv</option>
                            <option value="4">Stillgelegt</option>
                            <option value="5">Abmontiert</option>
                            <option value="">Alle</option>
                        </select>
                    </div>
                    <div class="col-4 col-md-1">
                        <label class="form-label small mb-1">Jahr</label>
                        <input type="number" wire:model="filterJahr"
                               class="form-control form-control-sm" min="2020" max="2030">
                    </div>
                    <div class="col-4 col-md-2 d-flex align-items-end gap-1">
                        <button wire:click="applyFilters" class="btn btn-primary btn-sm" title="Filtern">
                            <i class="bi bi-search"></i>
                            <span class="d-none d-md-inline">Filtern</span>
                        </button>
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

    {{-- Auswahl-Bar (sticky, nur sichtbar wenn Auswahl vorhanden) --}}
    @if(count($selectedAnlagen) > 0)
        <div class="alert alert-primary d-flex align-items-center justify-content-between py-2 mb-3 sticky-top"
             style="top: 10px; z-index: 100;">
            <div>
                <i class="bi bi-check2-square me-2"></i>
                <strong>{{ count($selectedAnlagen) }}</strong> Anlage(n) ausgewählt
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="selectAllFiltered">
                    <i class="bi bi-check-all"></i> Alle gefilterten auswählen
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="clearSelection">
                    <i class="bi bi-x-lg"></i> Auswahl leeren
                </button>
                <button type="button" class="btn btn-primary btn-sm" wire:click="openAmtExport">
                    <i class="bi bi-envelope-arrow-up"></i>
                    Amt-Export starten ({{ count($selectedAnlagen) }})
                </button>
            </div>
        </div>
    @endif

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
                            <th style="width: 40px;" class="text-center">
                                <input type="checkbox" class="form-check-input"
                                       wire:model.live="selectAllOnPage"
                                       title="Alle auf dieser Seite auswählen">
                            </th>
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
                            <th style="width: 70px;" class="text-center">Export</th>
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
                            @php 
                                $letzteMessung = $anlage->messungenHeuer()->orderBy('cMIS_DATA', 'desc')->orderBy('cMIS_ORA', 'desc')->first();
                                $hatMessung = $letzteMessung !== null;
                                $istNegativ = $hatMessung && $letzteMessung->strEsito === '0';
                                // Export-Status: irgendeine Messung im Jahr wurde ans Amt geschickt (konsistent zum Filter)
                                $letzteExportierteMessung = $anlage->messungenHeuer()
                                    ->whereNotNull('exported_at')
                                    ->orderBy('exported_at', 'desc')
                                    ->first();
                                $istExportiert = $letzteExportierteMessung !== null;
                                $kodexStr = (string) $anlage->Feld_a;
                            @endphp
                            <tr class="{{ $istNegativ ? 'table-danger' : (!$hatMessung ? 'table-warning' : '') }}">
                                <td class="text-center">
                                    @if($hatMessung)
                                        <input type="checkbox" class="form-check-input"
                                               value="{{ $kodexStr }}"
                                               wire:model.live="selectedAnlagen"
                                               wire:key="check-{{ $kodexStr }}">
                                    @else
                                        <input type="checkbox" class="form-check-input" disabled
                                               title="Keine Messung im Jahr {{ $filterJahr }}">
                                    @endif
                                </td>
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
                                    @if($istNegativ)
                                        <span class="badge bg-danger"><i class="bi bi-x-lg"></i></span>
                                    @elseif($hatMessung)
                                        <span class="badge bg-success"><i class="bi bi-check-lg"></i></span>
                                    @else
                                        <span class="badge bg-warning text-dark"><i class="bi bi-dash"></i></span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($hatMessung && $istExportiert)
                                        <span class="badge bg-success"
                                              title="Exportiert am {{ \Carbon\Carbon::parse($letzteExportierteMessung->exported_at)->format('d.m.Y H:i') }}{{ $letzteExportierteMessung->exported_to_email ? ' an ' . $letzteExportierteMessung->exported_to_email : '' }}">
                                            <i class="bi bi-envelope-check"></i>
                                        </span>
                                    @elseif($hatMessung)
                                        <span class="badge bg-secondary" title="Noch nicht exportiert">
                                            <i class="bi bi-envelope"></i>
                                        </span>
                                    @else
                                        <span class="text-muted small">—</span>
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
                                        @if($hatMessung)
                                            <a href="{{ route('messungen.protokoll', $letzteMessung->id) }}" target="_blank"
                                               class="btn btn-outline-info" title="Protokoll drucken">
                                                <i class="bi bi-printer"></i>
                                            </a>
                                            <button wire:click="openMessungModalMitLetzer('{{ $anlage->Feld_a }}')"
                                               class="btn btn-outline-info" title="Letzte Messung anzeigen">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        @endif
                                        <button wire:click="openMessungModal('{{ $anlage->Feld_a }}')"
                                           class="btn btn-outline-success" title="Neue Messung">
                                            <i class="bi bi-plus-lg"></i>
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
                @foreach($anlagen as $anlage)
                    @php 
                        $letzteMessung = $anlage->messungenHeuer()->orderBy('cMIS_DATA', 'desc')->orderBy('cMIS_ORA', 'desc')->first();
                        $hatMessung = $letzteMessung !== null;
                        $istNegativ = $hatMessung && $letzteMessung->strEsito === '0';
                        // Export-Status: irgendeine Messung im Jahr wurde ans Amt geschickt (konsistent zum Filter)
                        $istExportiert = $anlage->messungenHeuer()
                            ->whereNotNull('exported_at')
                            ->exists();
                        $kodexStr = (string) $anlage->Feld_a;
                    @endphp
                    <div class="anlage-card border-bottom {{ $istNegativ ? 'bg-danger bg-opacity-10' : (!$hatMessung ? 'bg-warning bg-opacity-10' : '') }}">
                        <div class="p-2">
                            {{-- Zeile 1: Checkbox, Kodex, Aufstellungsort, Badges --}}
                            <div class="d-flex align-items-start gap-2 mb-1">
                                <div class="flex-shrink-0 pt-1">
                                    @if($hatMessung)
                                        <input type="checkbox" class="form-check-input"
                                               value="{{ $kodexStr }}"
                                               wire:model.live="selectedAnlagen"
                                               wire:key="mcheck-{{ $kodexStr }}">
                                    @else
                                        <input type="checkbox" class="form-check-input" disabled>
                                    @endif
                                </div>
                                <div class="flex-grow-1 min-width-0">
                                    <a href="{{ route('messungen.anlagen.edit', $anlage->Feld_a) }}" class="text-decoration-none">
                                        <span class="fw-bold text-primary font-monospace">{{ $anlage->Feld_a }}</span>
                                        <span class="text-dark">{{ Str::limit($anlage->Feld_w ?: '(keine Beschreibung)', 30) }}</span>
                                    </a>
                                </div>
                                <div class="flex-shrink-0 d-flex gap-1">
                                    @if($hatMessung && $istExportiert)
                                        <span class="badge bg-success" title="Exportiert">
                                            <i class="bi bi-envelope-check"></i>
                                        </span>
                                    @elseif($hatMessung)
                                        <span class="badge bg-secondary" title="Nicht exportiert">
                                            <i class="bi bi-envelope"></i>
                                        </span>
                                    @endif
                                    @if($istNegativ)
                                        <span class="badge bg-danger"><i class="bi bi-x-lg"></i></span>
                                    @elseif($hatMessung)
                                        <span class="badge bg-success"><i class="bi bi-check-lg"></i></span>
                                    @else
                                        <span class="badge bg-warning text-dark"><i class="bi bi-dash"></i></span>
                                    @endif
                                </div>
                            </div>

                            {{-- Zeile 2: Adresse Aufstellungsort --}}
                            <div class="small text-muted mb-1 ps-4">
                                <i class="bi bi-geo-alt"></i>
                                {{ $anlage->Feld_m }} {{ $anlage->Feld_n }}{{ ($anlage->Feld_m && $anlage->Feld_i) ? ',' : '' }}
                                {{ $anlage->Feld_i }}
                            </div>

                            {{-- Zeile 3: Hersteller/Baujahr + Aktionen --}}
                            <div class="d-flex justify-content-between align-items-center ps-4">
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
                                    @if($hatMessung)
                                        <a href="{{ route('messungen.protokoll', $letzteMessung->id) }}" target="_blank"
                                           class="btn btn-outline-info py-0 px-2">
                                            <i class="bi bi-printer"></i>
                                        </a>
                                        <button wire:click="openMessungModalMitLetzer('{{ $anlage->Feld_a }}')"
                                           class="btn btn-outline-info py-0 px-2">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    @endif
                                    <button wire:click="openMessungModal('{{ $anlage->Feld_a }}')"
                                       class="btn btn-outline-success py-0 px-2">
                                        <i class="bi bi-plus-lg"></i>
                                    </button>
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

    {{-- ========== Modal: Neue Messung ========== --}}
    @if($showMessungModal)
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-lg modal-dialog-scrollable modal-fullscreen-sm-down">
                <div class="modal-content">
                    <div class="modal-header {{ $letzteMessung ? 'bg-info' : 'bg-success' }} text-white py-2">
                        <h5 class="modal-title">
                            @if($letzteMessung)
                                <i class="bi bi-eye"></i> Letzte Messung ({{ $letzteMessung->cMIS_DATA2 }})
                            @else
                                <i class="bi bi-plus-circle"></i> Neue Messung
                            @endif
                        </h5>
                        <button type="button" class="btn-close btn-close-white" wire:click="closeMessungModal"></button>
                    </div>
                    <div class="modal-body p-2 p-md-3">
                        {{-- Anlage Info --}}
                        @if($selectedAnlage)
                            <div class="alert alert-secondary py-2 mb-3">
                                <div class="row small">
                                    <div class="col-4 col-md-2">
                                        <strong>Kodex:</strong><br>
                                        <span class="font-monospace">{{ $selectedAnlage->Feld_a }}</span>
                                    </div>
                                    <div class="col-8 col-md-4">
                                        <strong>Aufstellungsort:</strong><br>
                                        {{ $selectedAnlage->Feld_w }}
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <strong>Kessel:</strong><br>
                                        {{ $selectedAnlage->Feld_y ?? '-' }}
                                    </div>
                                    <div class="col-3 col-md-1">
                                        <strong>Bj.:</strong><br>
                                        {{ $selectedAnlage->Feld_z ?? '-' }}
                                    </div>
                                    <div class="col-3 col-md-2">
                                        <strong>kW:</strong><br>
                                        {{ $selectedAnlage->Feld_ab ?? '-' }}
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Modal-Fehler --}}
                        @if($modalError)
                            <div class="alert alert-danger py-2 mb-2">
                                <i class="bi bi-exclamation-triangle"></i> {{ $modalError }}
                            </div>
                        @endif

                        {{-- Live-Validierung (Pflichtfelder + Bereichs-Checks) --}}
                        @if(!empty($formErrors))
                            <div class="alert alert-warning py-2 mb-2" role="alert">
                                <div class="d-flex align-items-start gap-2">
                                    <i class="bi bi-exclamation-triangle fs-5"></i>
                                    <div class="flex-grow-1 small">
                                        <strong>Speichern nicht möglich.</strong> Folgende Felder sind unvollständig oder außerhalb des gültigen Bereichs:
                                        <ul class="mb-0 mt-1 ps-3">
                                            @foreach($formErrors as $feldKey => $fehler)
                                                <li><strong>{{ $feldKey }}</strong>: {{ $fehler }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <form wire:submit="saveMessung">
                            <div class="row g-3">
                                {{-- Linke Spalte: Grunddaten + Messwerte --}}
                                <div class="col-12 col-md-8">
                                    
                                    {{-- Foto-Upload für OCR --}}
                                    <div class="mb-3" x-data="{ 
                                        loading: false, 
                                        status: '',
                                        async resizeImage(file, maxDim = 2000, quality = 0.85) {
                                            // Bild aus File laden
                                            const img = await new Promise((resolve, reject) => {
                                                const i = new Image();
                                                const url = URL.createObjectURL(file);
                                                i.onload = () => { URL.revokeObjectURL(url); resolve(i); };
                                                i.onerror = (e) => { URL.revokeObjectURL(url); reject(e); };
                                                i.src = url;
                                            });
                                            // Skalieren: längste Seite auf maxDim
                                            let w = img.naturalWidth, h = img.naturalHeight;
                                            if (w > maxDim || h > maxDim) {
                                                if (w >= h) { h = Math.round(h * maxDim / w); w = maxDim; }
                                                else        { w = Math.round(w * maxDim / h); h = maxDim; }
                                            }
                                            const canvas = document.createElement('canvas');
                                            canvas.width = w;
                                            canvas.height = h;
                                            const ctx = canvas.getContext('2d');
                                            ctx.drawImage(img, 0, 0, w, h);
                                            // Als JPEG exportieren → reines Base64 (ohne data:-Prefix)
                                            return canvas.toDataURL('image/jpeg', quality).split(',')[1];
                                        },
                                        async processImage(file, source) {
                                            if (!file) return;
                                            this.loading = true;
                                            this.status = '';
                                            try {
                                                const base64 = await this.resizeImage(file, 2000, 0.85);
                                                const res = await fetch('/messungen/extract-from-photo', {
                                                    method: 'POST',
                                                    headers: {
                                                        'Content-Type': 'application/json',
                                                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                                                    },
                                                    body: JSON.stringify({ image: base64, source: source || 'auto' })
                                                });
                                                const data = await res.json();
                                                if (data.success) {
                                                    if (data.typ === 'protokoll') {
                                                        // Protokoll-Felder mappen
                                                        // Datum/Uhrzeit: aktuelles beibehalten, nicht aus Bild übernehmen
                                                        if (data.brennstoff) $wire.set('messung.cMIS_COMBUSTIBILE', data.brennstoff);
                                                        $wire.set('messung.cMIS_OSSIGENO', data.o2 || '');
                                                        $wire.set('messung.cMIS_ANIDRIDE_CARBONICA', data.co2 || '');
                                                        $wire.set('messung.cMIS_MONOSSSIDO', data.co || '');
                                                        $wire.set('messung.cMIS_BIOSSIDO_AZOTO', data.nox || '');
                                                        $wire.set('messung.cMIS_T_ARIA_COMB', data.t_luft || '');
                                                        $wire.set('messung.cMIS_T_GAS_COMB', data.t_abgas || '');
                                                        $wire.set('messung.cMIS_T_LIQ_CONV', data.t_waerme || '');
                                                        $wire.set('messung.cMIS_IND_OPACITA', data.russ || '0');
                                                        $wire.set('messung.cMIS_TRACCE_OLEO', data.oelderivate === '1' ? '0' : '1');
                                                    } else {
                                                        // Display-Felder mappen
                                                        // Datum/Uhrzeit: aktuelles beibehalten, nicht aus Bild übernehmen
                                                        if (data.brennstoff) $wire.set('messung.cMIS_COMBUSTIBILE', data.brennstoff);
                                                        $wire.set('messung.cMIS_OSSIGENO', data.o2 || '');
                                                        $wire.set('messung.cMIS_ANIDRIDE_CARBONICA', data.co2 || '');
                                                        $wire.set('messung.cMIS_PERD_FUMI', data.qa || '');
                                                        $wire.set('messung.cMIS_MONOSSSIDO', data.co || '');
                                                        $wire.set('messung.cMIS_BIOSSIDO_AZOTO', data.nox || '');
                                                        $wire.set('messung.cMIS_T_ARIA_COMB', data.t_luft || '');
                                                        $wire.set('messung.cMIS_T_GAS_COMB', data.t_abgas || '');
                                                        $wire.set('messung.cMIS_IND_OPACITA', data.russ || '0');
                                                    }
                                                    this.status = 'success';
                                                } else {
                                                    this.status = data.error || 'Fehler';
                                                }
                                            } catch (err) {
                                                this.status = 'Bildverarbeitung fehlgeschlagen';
                                                console.error(err);
                                            }
                                            this.loading = false;
                                        }
                                    }">
                                        <div class="d-flex align-items-center gap-2 flex-wrap">
                                            {{-- Kamera-Button --}}
                                            <label class="btn btn-primary btn-sm mb-0" :class="{ 'disabled': loading }">
                                                <span x-show="!loading"><i class="bi bi-camera-fill me-1"></i> Kamera</span>
                                                <span x-show="loading"><i class="bi bi-hourglass-split me-1"></i> Analysiert...</span>
                                                <input type="file" accept="image/*" capture="environment" 
                                                       class="d-none" x-ref="kameraInput"
                                                       @change="processImage($event.target.files[0], 'auto'); $refs.kameraInput.value = '';">
                                            </label>
                                            {{-- Galerie-Button --}}
                                            <label class="btn btn-outline-primary btn-sm mb-0" :class="{ 'disabled': loading }">
                                                <span x-show="!loading"><i class="bi bi-images me-1"></i> Galerie</span>
                                                <span x-show="loading"><i class="bi bi-hourglass-split me-1"></i> Analysiert...</span>
                                                <input type="file" accept="image/*" 
                                                       class="d-none" x-ref="galerieInput"
                                                       @change="processImage($event.target.files[0], 'auto'); $refs.galerieInput.value = '';">
                                            </label>
                                            <span x-show="status === 'success'" class="badge bg-success"><i class="bi bi-check-lg"></i> Werte übernommen</span>
                                            <span x-show="status && status !== 'success'" class="badge bg-danger" x-text="status"></span>
                                        </div>
                                    </div>

                                    {{-- Grunddaten --}}
                                    <div class="card mb-2">
                                        <div class="card-header bg-light py-1 px-2">
                                            <h6 class="mb-0 small"><i class="bi bi-info-circle"></i> Grunddaten</h6>
                                        </div>
                                        <div class="card-body py-2 px-2">
                                            <div class="row g-2">
                                                <div class="col-7 col-md-4" x-data>
                                                    <label class="form-label small mb-0">Datum</label>
                                                    <div class="input-group input-group-sm">
                                                        <input type="text" wire:model.live.debounce.500ms="messung.cMIS_DATA2"
                                                               class="form-control" placeholder="TT.MM.JJJJ" required>
                                                        <button type="button" class="input-group-text" @click="
                                                            const d = $refs.dp;
                                                            const cur = $wire.get('messung.cMIS_DATA2');
                                                            if (cur) {
                                                                const m = cur.match(/^(\d{2})\.(\d{2})\.(\d{4})$/);
                                                                if (m) d.value = m[3]+'-'+m[2]+'-'+m[1];
                                                            }
                                                            d.showPicker ? d.showPicker() : d.focus();
                                                        ">
                                                            <i class="bi bi-calendar3"></i>
                                                        </button>
                                                        <input type="date" x-ref="dp" class="visually-hidden position-absolute" tabindex="-1"
                                                               @change="if($event.target.value){const[y,m,d]=$event.target.value.split('-');$wire.set('messung.cMIS_DATA2',d+'.'+m+'.'+y)}">
                                                    </div>
                                                </div>
                                                <div class="col-5 col-md-3">
                                                    <label class="form-label small mb-0">Uhrzeit</label>
                                                    <input type="time" wire:model.live.debounce.500ms="messung.cMIS_ORA"
                                                           class="form-control form-control-sm">
                                                </div>
                                                <div class="col-8 col-md-3">
                                                    <label class="form-label small mb-0">Brennstoff</label>
                                                    <select wire:model.live="messung.cMIS_COMBUSTIBILE" class="form-select form-select-sm">
                                                        @foreach($brennstoffe as $key => $info)
                                                            @if(in_array($key, ['FUEL_HEAVY_OIL', 'FUEL_BUTANE'])) @continue @endif
                                                            <option value="{{ $key }}">{{ $info['text'] }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-4 col-md-2">
                                                    <label class="form-label small mb-0">Stadio</label>
                                                    <input type="number" wire:model.live.debounce.500ms="messung.cMIS_STADIO"
                                                           class="form-control form-control-sm" min="1" required>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Messwerte --}}
                                    <div class="card mb-2">
                                        <div class="card-header bg-light py-1 px-2">
                                            <h6 class="mb-0 small"><i class="bi bi-speedometer2"></i> Messwerte</h6>
                                        </div>
                                        <div class="card-body py-2 px-2">
                                            {{-- Zeile 1: Rußzahl-Mittelwert, Ölderivate --}}
                                            <div class="row g-2">
                                                <div class="col-6">
                                                    <label class="form-label small mb-0">Rußzahl-Mittelwert</label>
                                                    <input type="text" wire:model.live.debounce.500ms="messung.cMIS_IND_OPACITA"
                                                           class="form-control form-control-sm">
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small mb-0">Ölderivate?</label>
                                                    <select wire:model.live="messung.cMIS_TRACCE_OLEO" class="form-select form-select-sm">
                                                        <option value="1">NEIN/NO</option>
                                                        <option value="0">JA/SI</option>
                                                    </select>
                                                </div>
                                            </div>
                                            {{-- Zeile 2: T Wärmeträger, T Abgas --}}
                                            <div class="row g-2 mt-1">
                                                <div class="col-6">
                                                    <label class="form-label small mb-0">T Wärmeträger</label>
                                                    <div class="input-group input-group-sm">
                                                        <input type="text" wire:model.live.debounce.500ms="messung.cMIS_T_LIQ_CONV"
                                                               class="form-control">
                                                        <span class="input-group-text">°C</span>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small mb-0">T Abgas</label>
                                                    <div class="input-group input-group-sm">
                                                        <input type="text" wire:model.live.debounce.500ms="messung.cMIS_T_GAS_COMB"
                                                               class="form-control">
                                                        <span class="input-group-text">°C</span>
                                                    </div>
                                                </div>
                                            </div>
                                            {{-- Zeile 3: T Verbrennungsluft, O2 --}}
                                            <div class="row g-2 mt-1">
                                                <div class="col-6">
                                                    <label class="form-label small mb-0">T Verbrennungsluft</label>
                                                    <div class="input-group input-group-sm">
                                                        <input type="text" wire:model.live.debounce.500ms="messung.cMIS_T_ARIA_COMB"
                                                               class="form-control">
                                                        <span class="input-group-text">°C</span>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small mb-0">O₂</label>
                                                    <div class="input-group input-group-sm">
                                                        <input type="text" wire:model.live.debounce.500ms="messung.cMIS_OSSIGENO"
                                                               class="form-control">
                                                        <span class="input-group-text">%</span>
                                                    </div>
                                                </div>
                                            </div>
                                            {{-- Zeile 4: CO2, NOx --}}
                                            <div class="row g-2 mt-1">
                                                <div class="col-6">
                                                    <label class="form-label small mb-0">CO₂</label>
                                                    <div class="input-group input-group-sm">
                                                        <input type="text" wire:model.live.debounce.500ms="messung.cMIS_ANIDRIDE_CARBONICA"
                                                               class="form-control">
                                                        <span class="input-group-text">%</span>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small mb-0">NOx</label>
                                                    <div class="input-group input-group-sm">
                                                        <input type="text" wire:model.live.debounce.500ms="messung.cMIS_BIOSSIDO_AZOTO"
                                                               class="form-control">
                                                        <span class="input-group-text">mg/m³</span>
                                                    </div>
                                                </div>
                                            </div>
                                            {{-- Zeile 5: CO, Qa --}}
                                            <div class="row g-2 mt-1">
                                                <div class="col-6">
                                                    <label class="form-label small mb-0">CO</label>
                                                    <div class="input-group input-group-sm">
                                                        <input type="text" wire:model.live.debounce.500ms="messung.cMIS_MONOSSSIDO"
                                                               class="form-control">
                                                        <span class="input-group-text">mg/m³</span>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small mb-0">Qa</label>
                                                    <div class="input-group input-group-sm">
                                                        <input type="text" wire:model.live.debounce.500ms="messung.cMIS_PERD_FUMI"
                                                               class="form-control">
                                                        <span class="input-group-text">%</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Rechte Spalte: Grenzwert-Ampel --}}
                                <div class="col-12 col-md-4">
                                    <div class="card h-100">
                                        <div class="card-header bg-light py-1 px-2">
                                            <h6 class="mb-0 small"><i class="bi bi-stoplights"></i> Grenzwerte</h6>
                                        </div>
                                        <div class="card-body py-2 px-2">
                                            @if($grenzwerte)
                                                {{-- CO --}}
                                                <div class="d-flex justify-content-between align-items-center mb-2 p-2 rounded
                                                    {{ $grenzwerte['co']['status'] === 'gruen' ? 'bg-success bg-opacity-10' : '' }}
                                                    {{ $grenzwerte['co']['status'] === 'gelb' ? 'bg-warning bg-opacity-25' : '' }}
                                                    {{ $grenzwerte['co']['status'] === 'rot' ? 'bg-danger bg-opacity-25' : '' }}">
                                                    <div>
                                                        <strong>CO</strong>
                                                        <small class="text-muted d-block">max. {{ $grenzwerte['co']['grenzwert'] }} mg/m³</small>
                                                    </div>
                                                    <div class="text-end">
                                                        <span class="fw-bold">{{ $messung['cMIS_MONOSSSIDO'] ?: '-' }}</span>
                                                        @if($grenzwerte['co']['status'] === 'gruen')
                                                            <i class="bi bi-check-circle-fill text-success ms-1"></i>
                                                        @elseif($grenzwerte['co']['status'] === 'gelb')
                                                            <i class="bi bi-exclamation-triangle-fill text-warning ms-1"></i>
                                                        @else
                                                            <i class="bi bi-x-circle-fill text-danger ms-1"></i>
                                                        @endif
                                                    </div>
                                                </div>

                                                {{-- NOx --}}
                                                <div class="d-flex justify-content-between align-items-center mb-2 p-2 rounded
                                                    {{ $grenzwerte['nox']['status'] === 'gruen' ? 'bg-success bg-opacity-10' : '' }}
                                                    {{ $grenzwerte['nox']['status'] === 'gelb' ? 'bg-warning bg-opacity-25' : '' }}
                                                    {{ $grenzwerte['nox']['status'] === 'rot' ? 'bg-danger bg-opacity-25' : '' }}">
                                                    <div>
                                                        <strong>NOx</strong>
                                                        <small class="text-muted d-block">max. {{ $grenzwerte['nox']['grenzwert'] }} mg/m³</small>
                                                    </div>
                                                    <div class="text-end">
                                                        <span class="fw-bold">{{ $messung['cMIS_BIOSSIDO_AZOTO'] ?: '-' }}</span>
                                                        @if($grenzwerte['nox']['status'] === 'gruen')
                                                            <i class="bi bi-check-circle-fill text-success ms-1"></i>
                                                        @elseif($grenzwerte['nox']['status'] === 'gelb')
                                                            <i class="bi bi-exclamation-triangle-fill text-warning ms-1"></i>
                                                        @else
                                                            <i class="bi bi-x-circle-fill text-danger ms-1"></i>
                                                        @endif
                                                    </div>
                                                </div>

                                                {{-- Ruß --}}
                                                <div class="d-flex justify-content-between align-items-center mb-2 p-2 rounded
                                                    {{ $grenzwerte['russ']['status'] === 'gruen' ? 'bg-success bg-opacity-10' : '' }}
                                                    {{ $grenzwerte['russ']['status'] === 'rot' ? 'bg-danger bg-opacity-25' : '' }}">
                                                    <div>
                                                        <strong>Rußzahl</strong>
                                                        <small class="text-muted d-block">max. {{ $grenzwerte['russ']['grenzwert'] }}</small>
                                                    </div>
                                                    <div class="text-end">
                                                        <span class="fw-bold">{{ $messung['cMIS_IND_OPACITA'] ?: '-' }}</span>
                                                        @if($grenzwerte['russ']['status'] === 'gruen')
                                                            <i class="bi bi-check-circle-fill text-success ms-1"></i>
                                                        @else
                                                            <i class="bi bi-x-circle-fill text-danger ms-1"></i>
                                                        @endif
                                                    </div>
                                                </div>

                                                {{-- Ölspuren --}}
                                                <div class="d-flex justify-content-between align-items-center p-2 rounded
                                                    {{ $grenzwerte['oel']['status'] === 'gruen' ? 'bg-success bg-opacity-10' : '' }}
                                                    {{ $grenzwerte['oel']['status'] === 'rot' ? 'bg-danger bg-opacity-25' : '' }}">
                                                    <div>
                                                        <strong>Ölspuren</strong>
                                                        <small class="text-muted d-block">keine erlaubt</small>
                                                    </div>
                                                    <div class="text-end">
                                                        <span class="fw-bold">{{ $messung['cMIS_TRACCE_OLEO'] === '0' ? 'Ja' : 'Nein' }}</span>
                                                        @if($grenzwerte['oel']['status'] === 'gruen')
                                                            <i class="bi bi-check-circle-fill text-success ms-1"></i>
                                                        @else
                                                            <i class="bi bi-x-circle-fill text-danger ms-1"></i>
                                                        @endif
                                                    </div>
                                                </div>
                                            @else
                                                <div class="text-center text-muted py-4">
                                                    <i class="bi bi-speedometer2 display-6"></i>
                                                    <p class="mb-0 mt-2 small">Gib Messwerte ein um die Grenzwertprüfung zu sehen.</p>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Validation Errors --}}
                            @if($errors->any())
                                <div class="alert alert-danger py-2 mt-2">
                                    <ul class="mb-0 small">
                                        @foreach($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </form>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" wire:click="closeMessungModal" class="btn btn-secondary btn-sm">
                            <i class="bi bi-x-lg"></i> Abbrechen
                        </button>
                        <button type="button" wire:click="saveMessung" class="btn btn-success btn-sm"
                                @disabled(!empty($formErrors))
                                title="{{ !empty($formErrors) ? 'Bitte zuerst alle Fehler korrigieren' : 'Speichern' }}">
                            <i class="bi bi-check-lg"></i> Speichern
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Amt-Export-Modal (Livewire-Component) --}}
    <livewire:messungen.amt-export />
</div>

@push('scripts')
<script>
    // Keine Event-Bridge nötig: $this->dispatch(...)->to('messungen.amt-export')
    // läuft direkt server-seitig.
</script>
@endpush

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

@push('scripts')
<script>
    (function() {
        const key = 'scroll_anlagen';

        document.addEventListener('livewire:navigated', restoreScroll);
        document.addEventListener('DOMContentLoaded', restoreScroll);

        function restoreScroll() {
            const saved = sessionStorage.getItem(key);
            if (saved) {
                requestAnimationFrame(() => {
                    window.scrollTo(0, parseInt(saved));
                });
            }
        }

        document.addEventListener('click', function(e) {
            const link = e.target.closest('a[href]');
            if (link && !link.hasAttribute('wire:click')) {
                sessionStorage.setItem(key, window.scrollY);
            }
        });

        document.addEventListener('livewire:morph', function() {
            sessionStorage.setItem(key, window.scrollY);
        });
    })();
</script>
@endpush
