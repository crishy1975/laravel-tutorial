{{-- resources/views/livewire/messungen/messungen-liste.blade.php --}}
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

    {{-- Header - Mobile optimiert --}}
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h1 class="h5 h4-md mb-0">
                <i class="bi bi-speedometer2 text-primary"></i>
                Messungen
            </h1>
            <p class="text-muted mb-0 small">
                {{ $statistik['total'] }} in {{ $filterJahr }}
            </p>
        </div>
        <div class="d-flex gap-1 gap-sm-2 flex-wrap">
            <a href="{{ route('messungen.anlagen.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-building"></i>
                <span class="d-none d-sm-inline">Anlagen</span>
            </a>
            <button wire:click="openMessungModal" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg"></i>
                <span class="d-none d-sm-inline">Neue Messung</span>
            </button>
            <a href="{{ route('messungen.import') }}" class="btn btn-success btn-sm">
                <i class="bi bi-file-earmark-arrow-up"></i>
                <span class="d-none d-sm-inline">XML</span>
            </a>
        </div>
    </div>

    {{-- Statistik-Karten - kompakter auf Mobile --}}
    <div class="row g-2 mb-3">
        <div class="col-3">
            <div class="card border-primary h-100 stat-card">
                <div class="card-body text-center py-2 px-1">
                    <div class="stat-number text-primary">{{ $statistik['total'] }}</div>
                    <div class="stat-label">Gesamt</div>
                </div>
            </div>
        </div>
        <div class="col-3">
            <div class="card border-success h-100 stat-card cursor-pointer"
                 wire:click="$set('filterErgebnis', '1')" role="button">
                <div class="card-body text-center py-2 px-1">
                    <div class="stat-number text-success">{{ $statistik['positiv'] }}</div>
                    <div class="stat-label">Positiv</div>
                </div>
            </div>
        </div>
        <div class="col-3">
            <div class="card border-danger h-100 stat-card cursor-pointer"
                 wire:click="$set('filterErgebnis', '0')" role="button">
                <div class="card-body text-center py-2 px-1">
                    <div class="stat-number text-danger">{{ $statistik['negativ'] }}</div>
                    <div class="stat-label">Negativ</div>
                </div>
            </div>
        </div>
        <div class="col-3">
            <div class="card border-warning h-100 stat-card cursor-pointer"
                 wire:click="$set('filterOhneAnlage', '1')" role="button">
                <div class="card-body text-center py-2 px-1">
                    <div class="stat-number text-warning">{{ $statistik['ohneAnlage'] }}</div>
                    <div class="stat-label d-none d-sm-block">Ohne Anlage</div>
                    <div class="stat-label d-sm-none">o. Anl.</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter-Karte - collapsed auf Mobile --}}
    <div class="card shadow-sm mb-3">
        <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center"
             data-bs-toggle="collapse" data-bs-target="#filterCollapse" role="button" aria-expanded="false">
            <h6 class="mb-0">
                <i class="bi bi-funnel"></i> Filter
                @php
                    $activeFilters = collect([$filterKodex, $filterName, $filterErgebnis, $filterBrennstoff, $filterOhneAnlage])->filter()->count();
                @endphp
                @if($activeFilters > 0)
                    <span class="badge bg-primary ms-1">{{ $activeFilters }}</span>
                @endif
            </h6>
            <i class="bi bi-chevron-down transition-transform"></i>
        </div>
        <div class="collapse" id="filterCollapse">
            <div class="card-body py-2">
                <div class="row g-2">
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1">Jahr</label>
                        <select wire:model="filterJahr" class="form-select form-select-sm">
                            @for($y = date('Y'); $y >= 2020; $y--)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1">Kodex</label>
                        <input type="text" wire:model="filterKodex"
                               class="form-control form-control-sm" placeholder="Kodex...">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1">Name</label>
                        <input type="text" wire:model="filterName"
                               class="form-control form-control-sm" placeholder="Name...">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1">Ergebnis</label>
                        <select wire:model="filterErgebnis" class="form-select form-select-sm">
                            <option value="">Alle</option>
                            <option value="1">✓ Positiv</option>
                            <option value="0">✗ Negativ</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1">Brennstoff</label>
                        <select wire:model="filterBrennstoff" class="form-select form-select-sm">
                            <option value="">Alle</option>
                            @foreach($brennstoffe as $key => $info)
                                <option value="{{ $key }}">{{ $info['text'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1">Anlage</label>
                        <select wire:model="filterOhneAnlage" class="form-select form-select-sm">
                            <option value="">Alle</option>
                            <option value="0">Mit Anlage</option>
                            <option value="1">Ohne Anlage</option>
                        </select>
                    </div>
                </div>
                <div class="mt-2 d-flex gap-2">
                    <button wire:click="$refresh" class="btn btn-sm btn-primary">
                        <i class="bi bi-search"></i> Filtern
                    </button>
                    @if($activeFilters > 0)
                        <button wire:click="resetFilters" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-x-lg"></i> Zurücksetzen
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ========== DESKTOP: Tabellen-Ansicht (ab md) ========== --}}
    <div class="card shadow-sm d-none d-md-block">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0" id="messungenTable">
                    <thead class="table-dark">
                        <tr>
                            <th wire:click="sortBy('cIM_CODICE')" class="text-nowrap">
                                Kodex
                                @if($sortField === 'cIM_CODICE')
                                    <i class="bi bi-chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                @endif
                            </th>
                            <th wire:click="sortBy('cIM_NAME')" class="text-nowrap">
                                Name
                                @if($sortField === 'cIM_NAME')
                                    <i class="bi bi-chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                @endif
                            </th>
                            <th wire:click="sortBy('cMIS_DATA')" class="text-nowrap">
                                Datum
                                @if($sortField === 'cMIS_DATA')
                                    <i class="bi bi-chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                @endif
                            </th>
                            <th class="text-center">Stadio</th>
                            <th class="text-center">Brennstoff</th>
                            <th class="text-center">Ergebnis</th>
                            <th class="text-center" style="width: 100px;">Aktion</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($messungen as $m)
                            <tr class="{{ $m->strEsito === '0' ? 'table-danger' : '' }}">
                                <td class="text-nowrap">
                                    @if($m->codeInImpianti == 0)
                                        <span class="text-muted">{{ $m->cIM_CODICE ?: '─' }}</span>
                                    @else
                                        {{ $m->cIM_CODICE }}
                                    @endif
                                </td>
                                <td class="text-truncate" style="max-width: 200px;">
                                    {{ $m->cIM_NAME }}
                                </td>
                                <td class="text-nowrap">
                                    {{ $m->cMIS_DATA2 }}
                                    <small class="text-muted">{{ $m->cMIS_ORA }}</small>
                                </td>
                                <td class="text-center">{{ $m->cMIS_STADIO }}</td>
                                <td class="text-center">
                                    <small>{{ $m->cMIS_COMBUSTIBILE_P }}</small>
                                </td>
                                <td class="text-center">
                                    @if($m->strEsito === '1')
                                        <span class="badge bg-success">✓</span>
                                    @elseif($m->strEsito === '0')
                                        <span class="badge bg-danger">✗</span>
                                    @else
                                        <span class="badge bg-secondary">─</span>
                                    @endif
                                </td>
                                <td class="text-center text-nowrap">
                                    <button wire:click="editMessung({{ $m->id }})"
                                            class="btn btn-sm btn-outline-secondary" title="Bearbeiten">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    @if($m->codeInImpianti == 0)
                                        <button wire:click="openAnlageModal({{ $m->id }})"
                                                class="btn btn-sm btn-outline-primary" title="Anlage zuordnen">
                                            <i class="bi bi-link"></i>
                                        </button>
                                    @endif
                                    <button wire:click="delete({{ $m->id }})"
                                            wire:confirm="Messung wirklich löschen?"
                                            class="btn btn-sm btn-outline-danger" title="Löschen">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox fs-1 d-block mb-2 opacity-25"></i>
                                    Keine Messungen gefunden
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($messungen->hasPages())
            <div class="card-footer bg-light py-2">
                {{ $messungen->links() }}
            </div>
        @endif
    </div>

    {{-- ========== MOBILE: Card-Ansicht (nur xs/sm) ========== --}}
    <div class="d-md-none">
        {{-- Sortierung für Mobile --}}
        <div class="d-flex justify-content-between align-items-center mb-2">
            <small class="text-muted">{{ $messungen->total() }} Ergebnisse</small>
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-sort-down"></i> Sortieren
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item {{ $sortField === 'cMIS_DATA' ? 'active' : '' }}" href="#" wire:click.prevent="sortBy('cMIS_DATA')">Datum</a></li>
                    <li><a class="dropdown-item {{ $sortField === 'cIM_NAME' ? 'active' : '' }}" href="#" wire:click.prevent="sortBy('cIM_NAME')">Name</a></li>
                    <li><a class="dropdown-item {{ $sortField === 'cIM_CODICE' ? 'active' : '' }}" href="#" wire:click.prevent="sortBy('cIM_CODICE')">Kodex</a></li>
                </ul>
            </div>
        </div>

        {{-- Mobile Cards --}}
        @forelse($messungen as $m)
            <div class="card mb-2 {{ $m->strEsito === '0' ? 'border-danger' : '' }}">
                <div class="card-body p-2">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <div class="flex-grow-1 min-width-0">
                            <h6 class="mb-0 text-truncate">{{ $m->cIM_NAME }}</h6>
                            <small class="text-muted">
                                @if($m->codeInImpianti == 0)
                                    <span class="text-warning"><i class="bi bi-exclamation-circle"></i> Ohne Anlage</span>
                                @else
                                    {{ $m->cIM_CODICE }}
                                @endif
                            </small>
                        </div>
                        <div class="ms-2">
                            @if($m->strEsito === '1')
                                <span class="badge bg-success fs-6">✓</span>
                            @elseif($m->strEsito === '0')
                                <span class="badge bg-danger fs-6">✗</span>
                            @else
                                <span class="badge bg-secondary fs-6">─</span>
                            @endif
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center text-muted small mb-2">
                        <span>
                            <i class="bi bi-calendar"></i> {{ $m->cMIS_DATA2 }} {{ $m->cMIS_ORA }}
                        </span>
                        <span>
                            <i class="bi bi-fire"></i> {{ $m->cMIS_COMBUSTIBILE_P ?: '─' }}
                        </span>
                        <span>
                            St. {{ $m->cMIS_STADIO ?: '─' }}
                        </span>
                    </div>
                    
                    <div class="d-flex gap-1">
                        <button wire:click="editMessung({{ $m->id }})"
                                class="btn btn-sm btn-outline-secondary flex-grow-1">
                            <i class="bi bi-pencil"></i> Bearbeiten
                        </button>
                        @if($m->codeInImpianti == 0)
                            <button wire:click="openAnlageModal({{ $m->id }})"
                                    class="btn btn-sm btn-outline-primary flex-grow-1">
                                <i class="bi bi-link"></i> Zuordnen
                            </button>
                        @endif
                        <button wire:click="delete({{ $m->id }})"
                                wire:confirm="Messung wirklich löschen?"
                                class="btn btn-sm btn-outline-danger">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="card">
                <div class="card-body text-center text-muted py-4">
                    <i class="bi bi-inbox fs-1 d-block mb-2 opacity-25"></i>
                    Keine Messungen gefunden
                </div>
            </div>
        @endforelse

        {{-- Mobile Pagination --}}
        @if($messungen->hasPages())
            <div class="d-flex justify-content-center mt-3">
                {{ $messungen->links('pagination::simple-bootstrap-5') }}
            </div>
        @endif
    </div>

    {{-- ========== Modal: Anlage zuordnen ========== --}}
    @if($showAnlageModal)
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-lg modal-dialog-scrollable modal-fullscreen-sm-down">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white py-2">
                        <h5 class="modal-title">
                            <i class="bi bi-link"></i> Anlage zuordnen
                        </h5>
                        <button type="button" class="btn-close btn-close-white" wire:click="closeAnlageModal"></button>
                    </div>
                    <div class="modal-body p-2 p-md-3">
                        @if($selectedMessung)
                            <div class="alert alert-info py-2 mb-3">
                                <strong>Messung:</strong> {{ $selectedMessung->cIM_NAME }}
                                <br><small>Datum: {{ $selectedMessung->cMIS_DATA2 }} {{ $selectedMessung->cMIS_ORA }}</small>
                            </div>
                        @endif

                        {{-- Suchfelder mit Button --}}
                        <div class="row g-2 mb-2">
                            <div class="col-6 col-md-3">
                                <label class="form-label small mb-1">Aufstellungsort</label>
                                <input type="text" wire:model="anlageSearchName"
                                       wire:keydown.enter="searchAnlagen"
                                       class="form-control form-control-sm" placeholder="Name...">
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label small mb-1">Gemeinde</label>
                                <input type="text" wire:model="anlageSearchOrt"
                                       wire:keydown.enter="searchAnlagen"
                                       class="form-control form-control-sm" placeholder="Ort...">
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label small mb-1">Straße</label>
                                <input type="text" wire:model="anlageSearchStrasse"
                                       wire:keydown.enter="searchAnlagen"
                                       class="form-control form-control-sm" placeholder="Straße...">
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label small mb-1">Hausnr.</label>
                                <input type="text" wire:model="anlageSearchNummer"
                                       wire:keydown.enter="searchAnlagen"
                                       class="form-control form-control-sm" placeholder="Nr...">
                            </div>
                        </div>
                        <div class="mb-3">
                            <button type="button" wire:click="searchAnlagen" class="btn btn-primary btn-sm">
                                <i class="bi bi-search"></i> Suchen
                            </button>
                        </div>

                        {{-- Desktop: Tabelle für Suchergebnisse --}}
                        @if(count($anlageSearchResults) > 0)
                            <div class="table-responsive d-none d-md-block" style="max-height: 300px;">
                                <table class="table table-sm table-hover mb-0">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th>Kodex</th>
                                            <th>Aufstellungsort</th>
                                            <th>Adresse</th>
                                            <th>Hersteller</th>
                                            <th class="text-center">BJ</th>
                                            <th class="text-center">kW</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($anlageSearchResults as $anlage)
                                            <tr class="{{ $anlage->hatMessung ? 'table-success' : '' }}">
                                                <td class="text-nowrap">
                                                    {{ $anlage->Feld_a }}
                                                    @if($anlage->hatMessung)
                                                        <i class="bi bi-check-circle-fill text-success" title="Hat Messungen"></i>
                                                    @endif
                                                </td>
                                                <td>{{ $anlage->Feld_w }}</td>
                                                <td class="small">
                                                    {{ $anlage->Feld_m }} {{ $anlage->Feld_n }},
                                                    {{ $anlage->Feld_i }}
                                                </td>
                                                <td>{{ $anlage->Feld_y ?: '─' }}</td>
                                                <td class="text-center">{{ $anlage->Feld_z ?: '─' }}</td>
                                                <td class="text-center">{{ $anlage->Feld_ab ?: '─' }}</td>
                                                <td class="text-end">
                                                    <button wire:click="zuordnenAnlage('{{ $anlage->Feld_a }}')"
                                                            class="btn btn-sm btn-success">
                                                        <i class="bi bi-check-lg"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            {{-- Mobile: Cards für Suchergebnisse --}}
                            <div class="d-md-none" style="max-height: 300px; overflow-y: auto;">
                                @foreach($anlageSearchResults as $anlage)
                                    <div class="card mb-2 {{ $anlage->hatMessung ? 'border-success' : '' }}">
                                        <div class="card-body p-2 d-flex justify-content-between align-items-center {{ $anlage->hatMessung ? 'bg-success bg-opacity-10' : '' }}">
                                            <div class="min-width-0 flex-grow-1">
                                                <div class="fw-bold text-truncate">
                                                    {{ $anlage->Feld_w }}
                                                    @if($anlage->hatMessung)
                                                        <i class="bi bi-check-circle-fill text-success"></i>
                                                    @endif
                                                </div>
                                                <small class="text-muted d-block">{{ $anlage->Feld_a }}</small>
                                                <small class="text-muted d-block text-truncate">
                                                    {{ $anlage->Feld_m }} {{ $anlage->Feld_n }}, {{ $anlage->Feld_i }}
                                                </small>
                                                <small class="d-block">
                                                    {{ $anlage->Feld_y ?: '─' }}
                                                    <span class="text-muted ms-2">BJ:</span> {{ $anlage->Feld_z ?: '─' }}
                                                    <span class="text-muted ms-1">kW:</span> {{ $anlage->Feld_ab ?: '─' }}
                                                </small>
                                            </div>
                                            <button wire:click="zuordnenAnlage('{{ $anlage->Feld_a }}')"
                                                    class="btn btn-success btn-sm ms-2">
                                                <i class="bi bi-check-lg"></i>
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            @if($anlageSearchTotal > 10)
                                <div class="d-flex justify-content-between align-items-center mt-2 small">
                                    <span>{{ $anlageSearchTotal }} Ergebnisse</span>
                                    <div class="btn-group btn-group-sm">
                                        <button wire:click="anlagePagePrev" class="btn btn-outline-secondary"
                                                @if($anlageSearchPage <= 1) disabled @endif>
                                            <i class="bi bi-chevron-left"></i>
                                        </button>
                                        <span class="btn btn-outline-secondary disabled">
                                            {{ $anlageSearchPage }} / {{ ceil($anlageSearchTotal / 10) }}
                                        </span>
                                        <button wire:click="anlagePageNext" class="btn btn-outline-secondary"
                                                @if($anlageSearchPage >= ceil($anlageSearchTotal / 10)) disabled @endif>
                                            <i class="bi bi-chevron-right"></i>
                                        </button>
                                    </div>
                                </div>
                            @endif
                        @elseif($anlageSearchName || $anlageSearchOrt || $anlageSearchStrasse)
                            <div class="text-center text-muted py-4">
                                <i class="bi bi-search fs-1 d-block mb-2 opacity-25"></i>
                                Keine Anlagen gefunden
                            </div>
                        @else
                            <div class="text-center text-muted py-4">
                                <i class="bi bi-building fs-1 d-block mb-2 opacity-25"></i>
                                Suchbegriff eingeben
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-secondary" wire:click="closeAnlageModal">
                            Abbrechen
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ========== Modal: Messung erstellen/bearbeiten ========== --}}
    @if($showMessungModal)
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-xl modal-dialog-scrollable modal-fullscreen-lg-down">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white py-2">
                        <h5 class="modal-title">
                            <i class="bi bi-speedometer2"></i>
                            {{ $editMessungId ? 'Messung bearbeiten' : 'Neue Messung' }}
                        </h5>
                        <button type="button" class="btn-close btn-close-white" wire:click="closeMessungModal"></button>
                    </div>
                    <div class="modal-body p-2 p-md-3">
                        <form wire:submit.prevent="saveMessung">
                            <div class="row g-2 g-md-3">
                                {{-- Linke Spalte: Stammdaten --}}
                                <div class="col-12 col-lg-4">
                                    <div class="card h-100">
                                        <div class="card-header bg-light py-1 px-2">
                                            <h6 class="mb-0 small"><i class="bi bi-building"></i> Stammdaten</h6>
                                        </div>
                                        <div class="card-body py-2 px-2">
                                            <div class="mb-2">
                                                <label class="form-label small mb-0">Kodex Anlage</label>
                                                <input type="text" wire:model.live.debounce.500ms="messung.cIM_CODICE"
                                                       class="form-control form-control-sm" placeholder="z.B. 02100000001">
                                            </div>
                                            <div class="mb-2">
                                                <label class="form-label small mb-0">Aufstellungsort</label>
                                                <input type="text" wire:model="messung.cIM_NAME"
                                                       class="form-control form-control-sm" placeholder="Name">
                                            </div>
                                            <div class="row g-2">
                                                <div class="col-6">
                                                    <label class="form-label small mb-0">Datum</label>
                                                    <input type="text" wire:model="messung.cMIS_DATA2"
                                                           class="form-control form-control-sm" placeholder="tt.mm.jjjj">
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small mb-0">Uhrzeit</label>
                                                    <input type="time" wire:model="messung.cMIS_ORA"
                                                           class="form-control form-control-sm">
                                                </div>
                                            </div>
                                            <div class="row g-2 mt-1">
                                                <div class="col-6">
                                                    <label class="form-label small mb-0">Stadio</label>
                                                    <input type="text" wire:model="messung.cMIS_STADIO"
                                                           class="form-control form-control-sm" placeholder="z.B. 1">
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small mb-0">Brennstoff</label>
                                                    <select wire:model.live="messung.cMIS_COMBUSTIBILE" class="form-select form-select-sm">
                                                        <option value="">Wählen...</option>
                                                        @foreach($brennstoffe as $key => $info)
                                                            <option value="{{ $key }}">{{ $info['text'] }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Mittlere Spalte: Messwerte --}}
                                <div class="col-12 col-lg-4">
                                    <div class="card h-100">
                                        <div class="card-header bg-light py-1 px-2">
                                            <h6 class="mb-0 small"><i class="bi bi-graph-up"></i> Messwerte</h6>
                                        </div>
                                        <div class="card-body py-2 px-2">
                                            {{-- Zweispaltiges Layout wie im Bild --}}
                                            <div class="row g-2">
                                                {{-- LINKE SPALTE --}}
                                                <div class="col-6">
                                                    <div class="mb-2">
                                                        <label class="form-label small mb-0">Rußzahl-Mittelwert</label>
                                                        <div class="input-group input-group-sm">
                                                            <input type="text" wire:model.live.debounce.500ms="messung.cMIS_IND_OPACITA"
                                                                   class="form-control" placeholder="0">
                                                        </div>
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label small mb-0">T Wärmeträger</label>
                                                        <div class="input-group input-group-sm">
                                                            <input type="text" wire:model="messung.cMIS_T_LIQ_CONV"
                                                                   class="form-control" placeholder="70">
                                                            <span class="input-group-text">°C</span>
                                                        </div>
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label small mb-0">T Verbrennungsluft</label>
                                                        <div class="input-group input-group-sm">
                                                            <input type="text" wire:model="messung.cMIS_T_ARIA_COMB"
                                                                   class="form-control" placeholder="33">
                                                            <span class="input-group-text">°C</span>
                                                        </div>
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label small mb-0">CO₂</label>
                                                        <div class="input-group input-group-sm">
                                                            <input type="text" wire:model="messung.cMIS_ANIDRIDE_CARBONICA"
                                                                   class="form-control" placeholder="11.4">
                                                            <span class="input-group-text">%</span>
                                                        </div>
                                                    </div>
                                                    <div class="mb-0">
                                                        <label class="form-label small mb-0">CO</label>
                                                        <div class="input-group input-group-sm">
                                                            <input type="text" wire:model.live.debounce.500ms="messung.cMIS_MONOSSSIDO"
                                                                   class="form-control" placeholder="268">
                                                            <span class="input-group-text">mg/m³</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                {{-- RECHTE SPALTE --}}
                                                <div class="col-6">
                                                    <div class="mb-2">
                                                        <label class="form-label small mb-0">Ölderivate?</label>
                                                        <select wire:model.live="messung.cMIS_TRACCE_OLEO" class="form-select form-select-sm">
                                                            <option value="1">NEIN/NO</option>
                                                            <option value="0">JA/SI</option>
                                                        </select>
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label small mb-0">T Abgas</label>
                                                        <div class="input-group input-group-sm">
                                                            <input type="text" wire:model="messung.cMIS_T_GAS_COMB"
                                                                   class="form-control" placeholder="143">
                                                            <span class="input-group-text">°C</span>
                                                        </div>
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label small mb-0">O₂</label>
                                                        <div class="input-group input-group-sm">
                                                            <input type="text" wire:model="messung.cMIS_OSSIGENO"
                                                                   class="form-control" placeholder="9.2">
                                                            <span class="input-group-text">%</span>
                                                        </div>
                                                    </div>
                                                    <div class="mb-0">
                                                        <label class="form-label small mb-0">NOx</label>
                                                        <div class="input-group input-group-sm">
                                                            <input type="text" wire:model.live.debounce.500ms="messung.cMIS_BIOSSIDO_AZOTO"
                                                                   class="form-control" placeholder="237">
                                                            <span class="input-group-text">mg/m³</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Rechte Spalte: Grenzwerte --}}
                                <div class="col-12 col-lg-4">
                                    <div class="card h-100">
                                        <div class="card-header bg-light py-1 px-2">
                                            <h6 class="mb-0 small"><i class="bi bi-shield-check"></i> Grenzwerte</h6>
                                        </div>
                                        <div class="card-body py-2 px-2">
                                            @if($grenzwerte)
                                                @foreach(['co' => 'CO', 'nox' => 'NOx', 'russ' => 'Rußzahl', 'oel' => 'Ölspuren'] as $key => $label)
                                                    @php
                                                        $g = $grenzwerte[$key] ?? [];
                                                        $status = $g['status'] ?? 'gruen';
                                                        $grenzwert = $g['grenzwert'] ?? '-';
                                                        $wert = match($key) {
                                                            'co' => $messung['cMIS_MONOSSSIDO'] ?? '-',
                                                            'nox' => $messung['cMIS_BIOSSIDO_AZOTO'] ?? '-',
                                                            'russ' => $messung['cMIS_IND_OPACITA'] ?? '-',
                                                            'oel' => ($messung['cMIS_TRACCE_OLEO'] ?? '1') === '0' ? 'Ja' : 'Nein',
                                                            default => '-'
                                                        };
                                                        $bgClass = match($status) {
                                                            'rot' => 'bg-danger bg-opacity-10 border-danger',
                                                            'gelb' => 'bg-warning bg-opacity-10 border-warning',
                                                            default => 'bg-success bg-opacity-10 border-success'
                                                        };
                                                    @endphp
                                                    <div class="d-flex justify-content-between align-items-center p-2 mb-1 rounded border {{ $bgClass }}">
                                                        <div>
                                                            <strong>{{ $label }}</strong><br>
                                                            <small class="text-muted">
                                                                @if($key === 'oel')
                                                                    keine erlaubt
                                                                @else
                                                                    max. {{ $grenzwert }} {{ $key === 'russ' ? '' : 'mg/m³' }}
                                                                @endif
                                                            </small>
                                                        </div>
                                                        <div class="text-end">
                                                            <span class="h5 mb-0">{{ $wert }}</span>
                                                            @if($status === 'gruen')
                                                                <span class="text-success"><i class="bi bi-check-circle-fill"></i></span>
                                                            @elseif($status === 'gelb')
                                                                <span class="text-warning"><i class="bi bi-exclamation-triangle-fill"></i></span>
                                                            @else
                                                                <span class="text-danger"><i class="bi bi-x-circle-fill"></i></span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endforeach
                                            @else
                                                <div class="text-center text-muted py-4">
                                                    <i class="bi bi-speedometer2 fs-1 opacity-25"></i>
                                                    <p class="mb-0 small mt-2">Gib Messwerte ein um die Grenzwertprüfung zu sehen.</p>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Footer --}}
                            <div class="modal-footer border-top mt-3 pt-2 pb-0 px-0">
                                <button type="button" class="btn btn-secondary" wire:click="closeMessungModal">
                                    <i class="bi bi-x-lg"></i> Abbrechen
                                </button>
                                <button type="submit" class="btn btn-success">
                                    <i class="bi bi-check-lg"></i> Speichern
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif

</div>

@push('styles')
<style>
    /* Statistik-Cards */
    .stat-card { transition: transform 0.2s, box-shadow 0.2s; }
    .stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
    .stat-number { font-size: 1.25rem; font-weight: 700; line-height: 1.2; }
    .stat-label { font-size: 0.65rem; color: #6c757d; text-transform: uppercase; letter-spacing: 0.3px; }
    .cursor-pointer { cursor: pointer; }
    
    @media (min-width: 576px) {
        .stat-number { font-size: 1.5rem; }
        .stat-label { font-size: 0.7rem; }
    }
    @media (min-width: 768px) {
        .stat-number { font-size: 2rem; }
        .stat-label { font-size: 0.75rem; letter-spacing: 0.5px; }
    }
    
    /* Filter Toggle */
    .transition-transform { transition: transform 0.3s; }
    [aria-expanded="false"] .transition-transform { transform: rotate(-90deg); }
    
    /* Desktop Tabelle */
    #messungenTable thead th { cursor: pointer; user-select: none; }
    #messungenTable thead th:hover { background-color: rgba(255,255,255,0.1); }
    #messungenTable tbody tr { cursor: pointer; transition: background-color 0.15s; }
    #messungenTable tbody tr:hover { background-color: rgba(0, 123, 255, 0.05); }
    #messungenTable tbody tr.table-danger:hover { background-color: rgba(220, 53, 69, 0.15); }
    
    /* Mobile Optimierungen */
    @media (max-width: 575.98px) {
        .container-fluid { padding-left: 0.5rem; padding-right: 0.5rem; }
        .card-body { padding: 0.5rem; }
        .form-label { font-size: 0.75rem; }
        .min-width-0 { min-width: 0; }
    }
    
    /* Mobile Cards */
    .card.mb-2 .card-body { background: #fff; }
    .card.border-danger .card-body { background: rgba(220, 53, 69, 0.05); }
</style>
@endpush
