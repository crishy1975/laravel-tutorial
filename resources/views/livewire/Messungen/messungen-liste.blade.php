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
            @if(count($selectedMessungen) > 0)
                <button wire:click="openEmailModal" class="btn btn-info btn-sm">
                    <i class="bi bi-envelope"></i>
                    <span class="d-none d-sm-inline">Email</span>
                    <span class="badge bg-white text-info ms-1">{{ count($selectedMessungen) }}</span>
                </button>
                <button wire:click="openWhatsappModal" class="btn btn-success btn-sm">
                    <i class="bi bi-whatsapp"></i>
                    <span class="d-none d-sm-inline">WhatsApp</span>
                    <span class="badge bg-white text-success ms-1">{{ count($selectedMessungen) }}</span>
                </button>
            @endif
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

    {{-- Filter-Karte - immer offen --}}
    <div class="card shadow-sm mb-3">
        <div class="card-header bg-light py-2">
            <h6 class="mb-0">
                <i class="bi bi-funnel"></i> Filter
                @php
                    $activeFilters = collect([$filterKodex, $filterName, $filterErgebnis, $filterBrennstoff, $filterOhneAnlage])->filter()->count();
                @endphp
                @if($activeFilters > 0)
                    <span class="badge bg-primary ms-1">{{ $activeFilters }}</span>
                @endif
            </h6>
        </div>
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
                            @if(in_array($key, ['FUEL_HEAVY_OIL', 'FUEL_BUTANE'])) @continue @endif
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
                <button wire:click="applyFilters" class="btn btn-sm btn-primary">
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

    {{-- ========== DESKTOP: Tabellen-Ansicht (ab md) ========== --}}
    <div class="card shadow-sm d-none d-md-block">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0" id="messungenTable">
                    <thead class="table-dark">
                        <tr>
                            <th class="text-center" style="width: 40px;">
                                <input type="checkbox" class="form-check-input"
                                       wire:model.live="selectAll"
                                       wire:change="toggleSelectAll"
                                       title="Alle auswählen">
                            </th>
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
                            <tr class="{{ $m->strEsito === '0' ? 'table-danger' : ($m->codeInImpianti == 0 ? 'table-warning' : '') }}">
                                <td class="text-center" onclick="event.stopPropagation()">
                                    <input type="checkbox" class="form-check-input"
                                           wire:model.live="selectedMessungen"
                                           value="{{ $m->id }}">
                                </td>
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
                                    @if($m->codeInImpianti > 0)
                                        <a href="{{ route('messungen.protokoll', $m->id) }}" target="_blank"
                                           class="btn btn-sm btn-outline-info" title="Protokoll drucken">
                                            <i class="bi bi-printer"></i>
                                        </a>
                                    @endif
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
                                <td colspan="8" class="text-center text-muted py-4">
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
            <div class="card mb-2 {{ $m->strEsito === '0' ? 'border-danger' : ($m->codeInImpianti == 0 ? 'border-warning' : '') }}">
                <div class="card-body p-2">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <div class="me-2 pt-1">
                            <input type="checkbox" class="form-check-input"
                                   wire:model.live="selectedMessungen"
                                   value="{{ $m->id }}">
                        </div>
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
                        @if($m->codeInImpianti > 0)
                            <a href="{{ route('messungen.protokoll', $m->id) }}" target="_blank"
                               class="btn btn-sm btn-outline-info">
                                <i class="bi bi-printer"></i>
                            </a>
                        @endif
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
                            {{-- Validierungs-Hinweis (Live + nach Speichern-Versuch) --}}
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
                            @if($modalError)
                                <div class="alert alert-danger py-2 mb-2" role="alert">
                                    <i class="bi bi-x-circle"></i> {{ $modalError }}
                                </div>
                            @endif
                            <div class="row g-2 g-md-3">

                                {{-- Foto-Upload für OCR (über allen Spalten) --}}
                                <div class="col-12" x-data="{ 
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
                                                    if (data.kodex) $wire.set('messung.cIM_CODICE', data.kodex);
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
                                                       class="form-control form-control-sm">
                                            </div>
                                            <div class="mb-2">
                                                <label class="form-label small mb-0">Aufstellungsort</label>
                                                <input type="text" wire:model.live.debounce.500ms="messung.cIM_NAME"
                                                       class="form-control form-control-sm">
                                            </div>
                                            <div class="row g-2">
                                                <div class="col-6" x-data>
                                                    <label class="form-label small mb-0">Datum</label>
                                                    <div class="input-group input-group-sm">
                                                        <input type="text" wire:model.live.debounce.500ms="messung.cMIS_DATA2"
                                                               class="form-control" placeholder="TT.MM.JJJJ">
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
                                                <div class="col-6">
                                                    <label class="form-label small mb-0">Uhrzeit</label>
                                                    <input type="time" wire:model.live.debounce.500ms="messung.cMIS_ORA"
                                                           class="form-control form-control-sm" step="1">
                                                </div>
                                            </div>
                                            <div class="row g-2 mt-1">
                                                <div class="col-6">
                                                    <label class="form-label small mb-0">Stadio</label>
                                                    <input type="text" wire:model.live.debounce.500ms="messung.cMIS_STADIO"
                                                           class="form-control form-control-sm">
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small mb-0">Brennstoff</label>
                                                    <select wire:model.live="messung.cMIS_COMBUSTIBILE" class="form-select form-select-sm">
                                                        <option value="">Wählen...</option>
                                                        @foreach($brennstoffe as $key => $info)
                                                            @if(in_array($key, ['FUEL_HEAVY_OIL', 'FUEL_BUTANE'])) @continue @endif
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
                                                                   class="form-control">
                                                        </div>
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label small mb-0">T Wärmeträger</label>
                                                        <div class="input-group input-group-sm">
                                                            <input type="text" wire:model.live.debounce.500ms="messung.cMIS_T_LIQ_CONV"
                                                                   class="form-control">
                                                            <span class="input-group-text">°C</span>
                                                        </div>
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label small mb-0">T Verbrennungsluft</label>
                                                        <div class="input-group input-group-sm">
                                                            <input type="text" wire:model.live.debounce.500ms="messung.cMIS_T_ARIA_COMB"
                                                                   class="form-control">
                                                            <span class="input-group-text">°C</span>
                                                        </div>
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label small mb-0">CO₂</label>
                                                        <div class="input-group input-group-sm">
                                                            <input type="text" wire:model.live.debounce.500ms="messung.cMIS_ANIDRIDE_CARBONICA"
                                                                   class="form-control">
                                                            <span class="input-group-text">%</span>
                                                        </div>
                                                    </div>
                                                    <div class="mb-0">
                                                        <label class="form-label small mb-0">CO</label>
                                                        <div class="input-group input-group-sm">
                                                            <input type="text" wire:model.live.debounce.500ms="messung.cMIS_MONOSSSIDO"
                                                                   class="form-control">
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
                                                            <input type="text" wire:model.live.debounce.500ms="messung.cMIS_T_GAS_COMB"
                                                                   class="form-control">
                                                            <span class="input-group-text">°C</span>
                                                        </div>
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label small mb-0">O₂</label>
                                                        <div class="input-group input-group-sm">
                                                            <input type="text" wire:model.live.debounce.500ms="messung.cMIS_OSSIGENO"
                                                                   class="form-control">
                                                            <span class="input-group-text">%</span>
                                                        </div>
                                                    </div>
                                                    <div class="mb-0">
                                                        <label class="form-label small mb-0">NOx</label>
                                                        <div class="input-group input-group-sm">
                                                            <input type="text" wire:model.live.debounce.500ms="messung.cMIS_BIOSSIDO_AZOTO"
                                                                   class="form-control">
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
                                <button type="submit" class="btn btn-success"
                                        @disabled(!empty($formErrors))
                                        title="{{ !empty($formErrors) ? 'Bitte zuerst alle Fehler korrigieren' : 'Speichern' }}">
                                    <i class="bi bi-check-lg"></i> Speichern
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ========== Modal: Email versenden ========== --}}
    @if($showEmailModal)
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-lg modal-dialog-scrollable modal-fullscreen-sm-down">
                <div class="modal-content">
                    <div class="modal-header bg-info text-white py-2">
                        <h5 class="modal-title">
                            <i class="bi bi-envelope"></i>
                            Messungen per Email senden
                            <span class="badge bg-white text-info ms-1">{{ count($selectedMessungen) }}</span>
                        </h5>
                        <button type="button" class="btn-close btn-close-white" wire:click="closeEmailModal"></button>
                    </div>
                    <div class="modal-body p-2 p-md-3">

                        @if($emailError)
                            <div class="alert alert-danger py-2 mb-2">
                                <i class="bi bi-exclamation-triangle"></i> {{ $emailError }}
                            </div>
                        @endif

                        {{-- Empfänger-Bereich --}}
                        <div class="mb-3">
                            <label class="form-label small fw-bold mb-1">
                                <i class="bi bi-people"></i> Empfänger
                            </label>

                            {{-- Gewählte Empfänger als Tags --}}
                            @if(!empty($emailEmpfaenger))
                                <div class="d-flex flex-wrap gap-1 mb-2">
                                    @foreach($emailEmpfaenger as $index => $emp)
                                        <span class="badge bg-primary d-flex align-items-center gap-1 py-1 px-2">
                                            {{ $emp['name'] }} &lt;{{ $emp['email'] }}&gt;
                                            <button type="button" class="btn-close btn-close-white ms-1"
                                                    style="font-size: 0.5rem;"
                                                    wire:click="removeEmailEmpfaenger({{ $index }})"></button>
                                        </span>
                                    @endforeach
                                </div>
                            @endif

                            {{-- Suchfeld --}}
                            <div class="position-relative">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                                    <input type="text"
                                           wire:model.live.debounce.300ms="emailSearch"
                                           wire:keydown.enter="addManualEmail"
                                           wire:blur="addManualEmail"
                                           class="form-control"
                                           placeholder="Name oder Email suchen...">
                                </div>

                                {{-- Vorschläge Dropdown --}}
                                @if(!empty($emailSuggestions))
                                    <div class="position-absolute w-100 bg-white border rounded-bottom shadow-sm"
                                         style="z-index: 1050; max-height: 250px; overflow-y: auto;">
                                        @foreach($emailSuggestions as $suggestion)
                                            <div class="border-bottom">
                                                <div class="px-3 pt-2 pb-1">
                                                    <strong class="small">{{ $suggestion['name'] }}</strong>
                                                </div>
                                                @if($suggestion['email'])
                                                    <button type="button"
                                                            class="dropdown-item py-1 ps-4 small"
                                                            wire:click="selectEmailAdresse({{ $suggestion['id'] }}, 'email')">
                                                        <i class="bi bi-envelope text-primary me-1"></i>
                                                        {{ $suggestion['email'] }}
                                                    </button>
                                                @endif
                                                @if($suggestion['email_zweit'])
                                                    <button type="button"
                                                            class="dropdown-item py-1 ps-4 small"
                                                            wire:click="selectEmailAdresse({{ $suggestion['id'] }}, 'email_zweit')">
                                                        <i class="bi bi-envelope text-secondary me-1"></i>
                                                        {{ $suggestion['email_zweit'] }}
                                                        <span class="text-muted">(Zweit)</span>
                                                    </button>
                                                @endif
                                                @if($suggestion['pec'])
                                                    <button type="button"
                                                            class="dropdown-item py-1 ps-4 small"
                                                            wire:click="selectEmailAdresse({{ $suggestion['id'] }}, 'pec')">
                                                        <i class="bi bi-shield-check text-success me-1"></i>
                                                        {{ $suggestion['pec'] }}
                                                        <span class="text-muted">(PEC)</span>
                                                    </button>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Neue Adresse anlegen --}}
                        @if($showNewAdresseForm)
                            <div class="card border-success mb-3">
                                <div class="card-header bg-success bg-opacity-10 py-1 px-2">
                                    <h6 class="mb-0 small text-success">
                                        <i class="bi bi-person-plus"></i>
                                        Neue Adresse anlegen: {{ $newAdresseEmail }}
                                    </h6>
                                </div>
                                <div class="card-body py-2 px-2">
                                    <div class="input-group input-group-sm">
                                        <input type="text"
                                               wire:model.live="newAdresseName"
                                               wire:keydown.enter="saveNewAdresse"
                                               class="form-control"
                                               placeholder="Name eingeben..."
                                               autofocus>
                                        <button wire:click="saveNewAdresse"
                                                class="btn btn-success"
                                                @disabled(empty($newAdresseName))>
                                            <i class="bi bi-check-lg"></i> Speichern
                                        </button>
                                        <button wire:click="cancelNewAdresse"
                                                class="btn btn-outline-secondary">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Betreff --}}
                        <div class="mb-3">
                            <label class="form-label small fw-bold mb-1">Betreff</label>
                            <input type="text" wire:model="emailBetreff"
                                   class="form-control form-control-sm">
                        </div>

                        {{-- Nachricht --}}
                        <div class="mb-3">
                            <label class="form-label small fw-bold mb-1">Nachricht (optional)</label>
                            <textarea wire:model="emailText"
                                      class="form-control form-control-sm" rows="4"
                                      placeholder="Zusätzlicher Text vor den Messdaten..."></textarea>
                        </div>

                        {{-- Vorschau: Selektierte Messungen --}}
                        <div class="card bg-light">
                            <div class="card-header py-1 px-2">
                                <h6 class="mb-0 small">
                                    <i class="bi bi-list-check"></i>
                                    {{ count($selectedMessungen) }} Messungen werden gesendet
                                </h6>
                            </div>
                            <div class="card-body p-0" style="max-height: 200px; overflow-y: auto;">
                                <table class="table table-sm table-striped mb-0 small">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Kodex</th>
                                            <th>Name</th>
                                            <th>Datum</th>
                                            <th class="text-center">Ergebnis</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach(\App\Models\Messung::whereIn('id', $selectedMessungen)->get() as $sm)
                                            <tr>
                                                <td>{{ $sm->cIM_CODICE }}</td>
                                                <td class="text-truncate" style="max-width: 150px;">{{ $sm->cIM_NAME }}</td>
                                                <td>{{ $sm->cMIS_DATA2 }}</td>
                                                <td class="text-center">
                                                    @if($sm->strEsito === '1')
                                                        <span class="badge bg-success">✓</span>
                                                    @elseif($sm->strEsito === '0')
                                                        <span class="badge bg-danger">✗</span>
                                                    @else
                                                        <span class="badge bg-secondary">─</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-secondary" wire:click="closeEmailModal">
                            <i class="bi bi-x-lg"></i> Abbrechen
                        </button>
                        <button type="button" class="btn btn-info text-white" wire:click="sendEmail"
                                @disabled(empty($emailEmpfaenger))>
                            <i class="bi bi-send"></i> Senden
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ========== Modal: WhatsApp versenden ========== --}}
    @if($showWhatsappModal)
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);"
             x-data="{
                openWaLink() {
                    let nummer = $wire.get('waNummer') || $wire.get('waSearch') || '';
                    nummer = nummer.replace(/[^0-9+]/g, '');
                    if (!nummer) {
                        alert('Bitte eine Telefonnummer eingeben oder auswählen.');
                        return;
                    }
                    let phone = nummer;
                    if (!phone.startsWith('+') && !phone.startsWith('39')) {
                        phone = '39' + phone.replace(/^0/, '');
                    }
                    phone = phone.replace('+', '');
                    const text = encodeURIComponent($wire.get('waText') || '');
                    window.location.href = 'https://api.whatsapp.com/send?phone=' + phone + '&text=' + text;
                }
             }">
            <div class="modal-dialog modal-md modal-dialog-scrollable modal-fullscreen-sm-down">
                <div class="modal-content">
                    <div class="modal-header py-2" style="background-color: #25D366; color: white;">
                        <h5 class="modal-title">
                            <i class="bi bi-whatsapp"></i>
                            Messungen per WhatsApp senden
                        </h5>
                        <button type="button" class="btn-close btn-close-white" wire:click="closeWhatsappModal"></button>
                    </div>
                    <div class="modal-body p-2 p-md-3">

                        {{-- Empfänger --}}
                        <div class="mb-3">
                            <label class="form-label small fw-bold mb-1">
                                <i class="bi bi-telephone"></i> Empfänger
                            </label>
                            <div class="position-relative">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">+39</span>
                                    <input type="text"
                                           wire:model.live.debounce.300ms="waSearch"
                                           wire:keydown.enter="addManualWaNummer"
                                           wire:blur="addManualWaNummer"
                                           class="form-control"
                                           placeholder="Name oder Nummer..."
                                           inputmode="text">
                                </div>

                                @if(!empty($waSuggestions))
                                    <div class="position-absolute w-100 bg-white border rounded-bottom shadow-sm"
                                         style="z-index: 1050; max-height: 200px; overflow-y: auto;">
                                        @foreach($waSuggestions as $sug)
                                            <div class="border-bottom">
                                                <div class="px-3 pt-2 pb-1">
                                                    <strong class="small">{{ $sug['name'] }}</strong>
                                                </div>
                                                @if($sug['handy'])
                                                    <button type="button"
                                                            class="dropdown-item py-1 ps-4 small"
                                                            wire:click="selectWaNummer({{ $sug['id'] }}, 'handy')">
                                                        <i class="bi bi-phone text-success me-1"></i>
                                                        {{ $sug['handy'] }}
                                                        <span class="text-muted">(Handy)</span>
                                                    </button>
                                                @endif
                                                @if($sug['telefon'])
                                                    <button type="button"
                                                            class="dropdown-item py-1 ps-4 small"
                                                            wire:click="selectWaNummer({{ $sug['id'] }}, 'telefon')">
                                                        <i class="bi bi-telephone text-primary me-1"></i>
                                                        {{ $sug['telefon'] }}
                                                        <span class="text-muted">(Telefon)</span>
                                                    </button>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            @if($waNummer)
                                <div class="mt-2">
                                    <span class="badge bg-success d-inline-flex align-items-center gap-1 py-1 px-2">
                                        <i class="bi bi-whatsapp"></i>
                                        {{ $waName ? $waName . ' \u2013 ' : '' }}{{ $waNummer }}
                                        <button type="button" class="btn-close btn-close-white ms-1"
                                                style="font-size: 0.5rem;"
                                                wire:click="$set('waNummer', '')"></button>
                                    </span>
                                </div>
                            @endif
                        </div>

                        {{-- Neuen Kontakt anlegen --}}
                        @if($showNewKontaktForm)
                            <div class="card border-success mb-3">
                                <div class="card-header bg-success bg-opacity-10 py-1 px-2">
                                    <h6 class="mb-0 small text-success">
                                        <i class="bi bi-person-plus"></i>
                                        Neuen Kontakt anlegen: {{ $newKontaktNummer }}
                                    </h6>
                                </div>
                                <div class="card-body py-2 px-2">
                                    <div class="row g-2">
                                        <div class="col-12">
                                            <input type="text"
                                                   wire:model.live="newKontaktName"
                                                   wire:keydown.enter="saveNewKontakt"
                                                   class="form-control form-control-sm"
                                                   placeholder="Name *"
                                                   autofocus>
                                        </div>
                                        <div class="col-12">
                                            <input type="email"
                                                   wire:model="newKontaktEmail"
                                                   class="form-control form-control-sm"
                                                   placeholder="Email (optional)">
                                        </div>
                                        <div class="col-12 d-flex gap-1">
                                            <button wire:click="saveNewKontakt"
                                                    class="btn btn-success btn-sm flex-grow-1"
                                                    @disabled(empty($newKontaktName))>
                                                <i class="bi bi-check-lg"></i> Speichern
                                            </button>
                                            <button wire:click="cancelNewKontakt"
                                                    class="btn btn-outline-secondary btn-sm">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Nachricht mit Links --}}
                        <div class="mb-3">
                            <label class="form-label small fw-bold mb-1">Nachricht (mit Download-Links)</label>
                            <textarea wire:model="waText"
                                      class="form-control form-control-sm" rows="8"
                                      style="font-size: 0.8rem;"></textarea>
                        </div>

                        <div class="alert alert-success py-2 small">
                            <i class="bi bi-check-circle"></i>
                            Die Protokolle sind als Download-Links in der Nachricht enthalten.
                            Der Empfänger kann sie direkt im Browser öffnen \u2013 kein Login nötig.
                        </div>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-secondary" wire:click="closeWhatsappModal">
                            <i class="bi bi-x-lg"></i> Abbrechen
                        </button>
                        <button x-on:click="openWaLink()"
                                class="btn text-white"
                                style="background-color: #25D366;">
                            <i class="bi bi-whatsapp"></i> In WhatsApp öffnen
                        </button>
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
    #messungenTable tbody tr.table-warning:hover { background-color: rgba(255, 193, 7, 0.2); }
    
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
    .card.border-warning .card-body { background: rgba(255, 193, 7, 0.08); }
</style>
@endpush

@push('scripts')
<script>
    // Scroll-Position speichern und wiederherstellen
    (function() {
        const key = 'scroll_messungen';

        // Beim Laden: Scroll-Position wiederherstellen
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

        // Beim Wegnavigieren: Scroll-Position speichern
        document.addEventListener('click', function(e) {
            const link = e.target.closest('a[href]');
            if (link && !link.hasAttribute('wire:click')) {
                sessionStorage.setItem(key, window.scrollY);
            }
        });

        // Bei Livewire-Updates (Pagination, Filter): Scroll-Position speichern
        document.addEventListener('livewire:morph', function() {
            sessionStorage.setItem(key, window.scrollY);
        });
    })();
</script>
@endpush
