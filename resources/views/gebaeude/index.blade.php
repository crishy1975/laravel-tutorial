{{-- resources/views/gebaeude/index.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container-fluid py-2 py-md-4">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 h3-md mb-0">
                <i class="bi bi-building text-primary"></i>
                Gebäude
            </h1>
            <p class="text-muted mb-0 small d-none d-md-block">
                {{ $gebaeude->total() }} Einträge
            </p>
        </div>
        <div class="d-flex gap-2">
            {{-- Desktop Buttons --}}
            <button type="button" class="btn btn-success d-none d-md-inline-flex" id="open-bulk-modal">
                <i class="bi bi-link-45deg"></i> Mit Tour verknüpfen
            </button>
            <a href="{{ route('gebaeude.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i>
                <span class="d-none d-sm-inline">Neu</span>
            </a>
            {{-- Mobile Dropdown --}}
            <div class="dropdown d-md-none">
                <button class="btn btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-three-dots-vertical"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item" href="#" id="open-bulk-modal-mobile">
                            <i class="bi bi-link-45deg"></i> Mit Tour verknüpfen
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Flash-Meldungen --}}
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

    {{-- Statistik-Karten --}}
    <div class="row g-2 mb-3">
        <div class="col-4 col-md-3">
            <div class="card border-primary h-100 stat-card">
                <div class="card-body text-center py-2">
                    <div class="stat-number text-primary">{{ $stats['gesamt'] ?? $gebaeude->total() }}</div>
                    <div class="stat-label">Gesamt</div>
                </div>
            </div>
        </div>
        <div class="col-4 col-md-3">
            <div class="card border-warning h-100 stat-card">
                <div class="card-body text-center py-2">
                    <div class="stat-number text-warning">{{ $stats['rechnung_offen'] ?? 0 }}</div>
                    <div class="stat-label d-none d-sm-block">Rechnung offen</div>
                    <div class="stat-label d-sm-none">Rechn.</div>
                </div>
            </div>
        </div>
        <div class="col-4 col-md-3">
            <div class="card border-success h-100 stat-card">
                <div class="card-body text-center py-2">
                    <div class="stat-number text-success">{{ $stats['mit_tour'] ?? 0 }}</div>
                    <div class="stat-label d-none d-sm-block">Mit Tour</div>
                    <div class="stat-label d-sm-none">Tour</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3 d-none d-md-block">
            <div class="card border-info h-100 stat-card">
                <div class="card-body text-center py-2">
                    <div class="stat-number text-info">{{ $stats['ohne_tour'] ?? 0 }}</div>
                    <div class="stat-label">Ohne Tour</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter-Karte (Collapsible auf Mobile) --}}
    <div class="card shadow-sm mb-3">
        <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center" 
             data-bs-toggle="collapse" 
             data-bs-target="#filterCollapse" 
             role="button"
             aria-expanded="true">
            <h6 class="mb-0">
                <i class="bi bi-funnel"></i> Filter
                @php
                    $activeFilters = collect([$codex ?? '', $gebaeude_name ?? '', $strasse ?? '', $hausnummer ?? '', $filterTour ?? '', $filterRechnung ?? ''])->filter()->count();
                @endphp
                @if($activeFilters > 0)
                    <span class="badge bg-primary ms-1">{{ $activeFilters }}</span>
                @endif
            </h6>
            <i class="bi bi-chevron-down d-md-none transition-transform"></i>
        </div>
        <div class="collapse show" id="filterCollapse">
            <div class="card-body py-2">
                <form method="GET" action="{{ route('gebaeude.index') }}" id="filterForm">
                    <div class="row g-2">
                        {{-- Zeile 1: Codex, Gebäudename --}}
                        <div class="col-6 col-md-2">
                            <label class="form-label small mb-1">Codex</label>
                            <input type="text" name="codex" class="form-control form-control-sm" 
                                   value="{{ $codex ?? '' }}" placeholder="z.B. gam">
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label small mb-1">Gebäudename</label>
                            <input type="text" name="gebaeude_name" class="form-control form-control-sm" 
                                   value="{{ $gebaeude_name ?? '' }}" placeholder="Name...">
                        </div>

                        {{-- Zeile 2: Straße, Hausnummer --}}
                        <div class="col-8 col-md-3">
                            <label class="form-label small mb-1">Straße</label>
                            <input type="text" name="strasse" class="form-control form-control-sm" 
                                   value="{{ $strasse ?? '' }}" placeholder="Straße...">
                        </div>
                        <div class="col-4 col-md-1">
                            <label class="form-label small mb-1">Nr.</label>
                            <input type="text" name="hausnummer" class="form-control form-control-sm" 
                                   value="{{ $hausnummer ?? '' }}" placeholder="Nr.">
                        </div>

                        {{-- Zeile 3: Tour, Rechnung --}}
                        <div class="col-6 col-md-2">
                            <label class="form-label small mb-1">Tour</label>
                            <select name="tour" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">Alle</option>
                                <option value="ohne" @selected(($filterTour ?? '') === 'ohne')>❌ Ohne Tour</option>
                                <option value="mit" @selected(($filterTour ?? '') === 'mit')>✅ Mit Tour</option>
                                @foreach($touren ?? [] as $t)
                                    <option value="{{ $t->id }}" @selected(($filterTour ?? '') == $t->id)>
                                        {{ $t->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label small mb-1">Rechnung</label>
                            <select name="rechnung" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">Alle</option>
                                <option value="1" @selected(($filterRechnung ?? '') === '1')>📝 Zu schreiben</option>
                                <option value="0" @selected(($filterRechnung ?? '') === '0')>✓ Erledigt</option>
                            </select>
                        </div>

                        {{-- Buttons --}}
                        <div class="col-12 col-md-2 d-flex align-items-end gap-1">
                            <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                                <i class="bi bi-search"></i>
                                <span class="d-none d-md-inline">Suchen</span>
                            </button>
                            @if($activeFilters > 0)
                                <a href="{{ route('gebaeude.index') }}" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-x-lg"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Ergebnis --}}
    @if($gebaeude->isEmpty())
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-inbox display-4 text-muted"></i>
                <p class="text-muted mt-2 mb-0">Keine Gebäude gefunden.</p>
                <a href="{{ route('gebaeude.create') }}" class="btn btn-primary mt-3">
                    <i class="bi bi-plus-circle"></i> Erstes Gebäude anlegen
                </a>
            </div>
        </div>
    @else
        <div class="card shadow-sm">
            {{-- Card Header mit Auswahl-Info --}}
            <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <div class="form-check mb-0">
                        <input type="checkbox" class="form-check-input" id="check-all">
                        <label class="form-check-label small" for="check-all">Alle</label>
                    </div>
                    <span class="badge bg-secondary" id="selection-count" style="display: none;">
                        <span id="count-number">0</span> ausgewählt
                    </span>
                </div>
                <small class="text-muted">
                    {{ $gebaeude->firstItem() }}–{{ $gebaeude->lastItem() }} von {{ $gebaeude->total() }}
                </small>
            </div>

            {{-- Desktop: Tabelle --}}
            <div class="table-responsive d-none d-lg-block">
                <table class="table table-hover align-middle mb-0" id="gebaeudeTable">
                    <thead class="table-dark">
                        <tr>
                            <th style="width: 40px;"></th>
                            <th style="width: 100px;">Codex</th>
                            <th>Gebäudename</th>
                            <th>Straße</th>
                            <th style="width: 60px;">Nr.</th>
                            <th>Wohnort</th>
                            <th style="width: 120px;">Tour(en)</th>
                            <th style="width: 80px;" class="text-center">Rechnung</th>
                            <th style="width: 100px;" class="text-end">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($gebaeude as $g)
                            <tr class="{{ $g->rechnung_schreiben ? 'table-warning' : '' }}">
                                <td>
                                    <input type="checkbox" class="form-check-input row-check" value="{{ $g->id }}">
                                </td>
                                <td>
                                    <a href="{{ route('gebaeude.edit', $g->id) }}" class="fw-bold text-decoration-none">
                                        {{ $g->codex ?: '-' }}
                                    </a>
                                </td>
                                <td>
                                    <a href="{{ route('gebaeude.edit', $g->id) }}" class="text-decoration-none text-dark">
                                        {{ $g->gebaeude_name ?: '(kein Name)' }}
                                    </a>
                                </td>
                                <td>{{ $g->strasse }}</td>
                                <td>{{ $g->hausnummer }}</td>
                                <td>{{ $g->wohnort }}</td>
                                <td>
                                    @forelse($g->touren ?? [] as $tour)
                                        <span class="badge bg-info text-dark">{{ $tour->name }}</span>
                                    @empty
                                        <span class="text-muted small">-</span>
                                    @endforelse
                                </td>
                                <td class="text-center">
                                    @if($g->rechnung_schreiben)
                                        <span class="badge bg-warning text-dark" title="Rechnung zu schreiben">
                                            <i class="bi bi-pencil-square"></i>
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('gebaeude.edit', $g->id) }}" 
                                           class="btn btn-outline-primary" title="Bearbeiten">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('gebaeude.destroy', $g->id) }}"
                                              method="POST" class="d-inline"
                                              onsubmit="return confirm('Gebäude wirklich löschen?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger" title="Löschen">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Mobile: Card-Liste --}}
            <div class="d-lg-none">
                @foreach($gebaeude as $g)
                    <div class="gebaeude-card border-bottom {{ $g->rechnung_schreiben ? 'bg-warning bg-opacity-10' : '' }}"
                         data-id="{{ $g->id }}">
                        <div class="p-2">
                            {{-- Zeile 1: Checkbox, Codex, Name, Rechnung-Badge --}}
                            <div class="d-flex align-items-start gap-2 mb-1">
                                <input type="checkbox" class="form-check-input row-check mt-1" value="{{ $g->id }}">
                                <div class="flex-grow-1 min-width-0">
                                    <a href="{{ route('gebaeude.edit', $g->id) }}" class="text-decoration-none">
                                        <span class="fw-bold text-primary">{{ $g->codex ?: '-' }}</span>
                                        <span class="text-dark">{{ Str::limit($g->gebaeude_name ?: '(kein Name)', 30) }}</span>
                                    </a>
                                </div>
                                <div class="flex-shrink-0">
                                    @if($g->rechnung_schreiben)
                                        <span class="badge bg-warning text-dark">
                                            <i class="bi bi-pencil-square"></i>
                                        </span>
                                    @endif
                                </div>
                            </div>

                            {{-- Zeile 2: Adresse --}}
                            <div class="small text-muted mb-1 ps-4">
                                <i class="bi bi-geo-alt"></i>
                                @if($g->strasse || $g->wohnort)
                                    {{ $g->strasse }} {{ $g->hausnummer }}@if($g->strasse && $g->wohnort),@endif
                                    {{ $g->plz }} {{ $g->wohnort }}
                                @else
                                    Keine Adresse
                                @endif
                            </div>

                            {{-- Zeile 3: Tour + Aktionen --}}
                            <div class="d-flex justify-content-between align-items-center ps-4">
                                <div>
                                    @forelse($g->touren ?? [] as $tour)
                                        <span class="badge bg-info text-dark me-1">{{ $tour->name }}</span>
                                    @empty
                                        <span class="text-muted small">Keine Tour</span>
                                    @endforelse
                                </div>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('gebaeude.edit', $g->id) }}" 
                                       class="btn btn-outline-primary py-0 px-2">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('gebaeude.destroy', $g->id) }}"
                                          method="POST" class="d-inline"
                                          onsubmit="return confirm('Löschen?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger py-0 px-2">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            @if($gebaeude->hasPages())
                <div class="card-footer bg-light py-2">
                    <div class="d-flex justify-content-center">
                        {{ $gebaeude->appends(request()->query())->links() }}
                    </div>
                </div>
            @endif
        </div>
    @endif

</div>

{{-- Modal für Bulk-Verknüpfung --}}
<div class="modal fade" id="bulkAttachModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="bulk-modal-form" method="POST" action="{{ route('gebaeude.touren.bulkAttach') }}">
                @csrf
                <div class="modal-header bg-success text-white py-2">
                    <h6 class="modal-title">
                        <i class="bi bi-link-45deg"></i> Mit Tour verknüpfen
                    </h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2 mb-3">
                        <i class="bi bi-info-circle"></i>
                        <span id="modal-selection-info">0 Gebäude ausgewählt</span>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tour auswählen <span class="text-danger">*</span></label>
                        <select class="form-select" name="tour_id" required>
                            <option value="">— Bitte wählen —</option>
                            @foreach($touren ?? [] as $t)
                                <option value="{{ $t->id }}">
                                    {{ $t->name }}
                                    @if(!$t->aktiv) (inaktiv) @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <input type="hidden" name="returnTo" value="{{ url()->full() }}">
                    <div id="selected-ids-container"></div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Abbrechen
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-lg"></i> Verknüpfen
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    /* Statistik-Karten */
    .stat-card {
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .stat-number {
        font-size: 1.5rem;
        font-weight: 700;
        line-height: 1.2;
    }
    .stat-label {
        font-size: 0.7rem;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    @media (min-width: 768px) {
        .stat-number {
            font-size: 2rem;
        }
        .stat-label {
            font-size: 0.75rem;
        }
    }

    /* Mobile Cards */
    .gebaeude-card {
        transition: background-color 0.15s;
    }
    .gebaeude-card:active {
        background-color: rgba(0, 123, 255, 0.05);
    }
    .gebaeude-card.selected {
        background-color: rgba(13, 110, 253, 0.1) !important;
        border-left: 3px solid #0d6efd;
    }
    
    .min-width-0 {
        min-width: 0;
    }

    /* Filter Collapse Animation */
    .transition-transform {
        transition: transform 0.3s;
    }
    [aria-expanded="false"] .transition-transform {
        transform: rotate(-90deg);
    }

    /* Tabellen-Hover */
    #gebaeudeTable tbody tr {
        cursor: pointer;
        transition: background-color 0.15s;
    }
    #gebaeudeTable tbody tr:hover {
        background-color: rgba(0, 123, 255, 0.05);
    }
    #gebaeudeTable tbody tr.table-warning:hover {
        background-color: rgba(255, 193, 7, 0.2);
    }

    /* Selection Badge Animation */
    #selection-count {
        animation: fadeIn 0.2s;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: scale(0.8); }
        to { opacity: 1; transform: scale(1); }
    }

    /* Mobile Optimierungen */
    @media (max-width: 575.98px) {
        .container-fluid {
            padding-left: 0.5rem;
            padding-right: 0.5rem;
        }
        .card-body {
            padding: 0.5rem;
        }
        .form-label {
            font-size: 0.75rem;
        }
        .stat-number {
            font-size: 1.25rem;
        }
    }

    /* Print */
    @media print {
        .btn, .form-check, #filterCollapse, .card-header[data-bs-toggle], .pagination {
            display: none !important;
        }
        .d-none.d-lg-block {
            display: block !important;
        }
        .d-lg-none {
            display: none !important;
        }
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Elemente
    const master = document.getElementById('check-all');
    const allChecks = () => Array.from(document.querySelectorAll('.row-check'));
    const selectionBadge = document.getElementById('selection-count');
    const countNumber = document.getElementById('count-number');
    const modalInfo = document.getElementById('modal-selection-info');

    // Auswahl-Zähler aktualisieren
    function updateSelectionCount() {
        const count = allChecks().filter(ch => ch.checked).length;
        if (count > 0) {
            selectionBadge.style.display = 'inline-flex';
            countNumber.textContent = count;
        } else {
            selectionBadge.style.display = 'none';
        }
        
        // Mobile Cards visuell markieren
        document.querySelectorAll('.gebaeude-card').forEach(card => {
            const checkbox = card.querySelector('.row-check');
            if (checkbox && checkbox.checked) {
                card.classList.add('selected');
            } else {
                card.classList.remove('selected');
            }
        });
        
        return count;
    }

    // Master-Checkbox
    if (master) {
        master.addEventListener('change', () => {
            allChecks().forEach(ch => ch.checked = master.checked);
            updateSelectionCount();
        });
    }

    // Einzelne Checkboxen
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('row-check')) {
            updateSelectionCount();
            // Master-Checkbox Zustand aktualisieren
            const all = allChecks();
            const checked = all.filter(ch => ch.checked).length;
            if (master) {
                master.checked = checked === all.length && all.length > 0;
                master.indeterminate = checked > 0 && checked < all.length;
            }
        }
    });

    // Modal
    const modalEl = document.getElementById('bulkAttachModal');
    const bsModal = modalEl ? new bootstrap.Modal(modalEl) : null;

    function openBulkModal() {
        const selected = allChecks().filter(ch => ch.checked).map(ch => parseInt(ch.value, 10));
        
        if (selected.length === 0) {
            alert('Bitte mindestens ein Gebäude auswählen.');
            return;
        }

        // IDs einfügen
        const container = document.getElementById('selected-ids-container');
        container.innerHTML = '';
        selected.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ids[]';
            input.value = id;
            container.appendChild(input);
        });

        // Info aktualisieren
        if (modalInfo) {
            modalInfo.textContent = selected.length + ' Gebäude ausgewählt';
        }

        bsModal.show();
    }

    // Modal Buttons
    const btnOpen = document.getElementById('open-bulk-modal');
    const btnOpenMobile = document.getElementById('open-bulk-modal-mobile');
    
    if (btnOpen) btnOpen.addEventListener('click', openBulkModal);
    if (btnOpenMobile) btnOpenMobile.addEventListener('click', openBulkModal);

    // Modal Reset
    if (modalEl) {
        modalEl.addEventListener('hidden.bs.modal', () => {
            document.getElementById('selected-ids-container').innerHTML = '';
            document.getElementById('bulk-modal-form').reset();
        });
    }

    // Desktop: Zeilen-Klick (außer Checkbox/Buttons)
    document.querySelectorAll('#gebaeudeTable tbody tr').forEach(row => {
        row.addEventListener('click', function(e) {
            if (e.target.closest('input, button, a, form')) return;
            const link = this.querySelector('a[href*="gebaeude"]');
            if (link) window.location.href = link.href;
        });
    });

    // Enter-Taste im Filter
    document.getElementById('filterForm')?.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            this.submit();
        }
    });

    // Initial
    updateSelectionCount();
});
</script>
@endpush
