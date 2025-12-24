@extends('layouts.app')

@section('title', 'Reinigungsplanung')

@section('content')
<div class="container-fluid py-2 py-md-4">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 h3-md mb-1">
                <i class="bi bi-calendar-check text-primary"></i>
                Reinigungsplanung
            </h1>
            <p class="text-muted mb-0 small">
                @if(!empty($filterMonat))
                    {{ $monate[$filterMonat] }} {{ now()->year }}
                @else
                    Alle Monate
                @endif
            </p>
        </div>
        {{-- Desktop: Buttons --}}
        <div class="d-none d-md-flex gap-2">
            <a href="{{ route('reinigungsplanung.export', request()->query()) }}" class="btn btn-outline-success">
                <i class="bi bi-file-earmark-excel"></i> CSV Export
            </a>
            <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
                <i class="bi bi-printer"></i> Drucken
            </button>
        </div>
        {{-- Mobile: Dropdown --}}
        <div class="dropdown d-md-none">
            <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="dropdown">
                <i class="bi bi-three-dots-vertical"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li>
                    <a class="dropdown-item" href="{{ route('reinigungsplanung.export', request()->query()) }}">
                        <i class="bi bi-file-earmark-excel"></i> CSV Export
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="#" onclick="window.print(); return false;">
                        <i class="bi bi-printer"></i> Drucken
                    </a>
                </li>
            </ul>
        </div>
    </div>

    {{-- Statistik-Karten (kompakt auf Mobile) --}}
    <div class="row g-2 mb-3">
        <div class="col-4">
            <div class="card border-primary h-100">
                <div class="card-body text-center py-2 py-md-3">
                    <h2 class="h3 h2-md fw-bold text-primary mb-0">{{ $stats['gesamt'] }}</h2>
                    <small class="text-muted d-none d-sm-inline">Gesamt</small>
                    <small class="text-muted d-sm-none" style="font-size: 0.7rem;">Ges.</small>
                </div>
            </div>
        </div>
        <div class="col-4">
            <div class="card border-warning h-100">
                <div class="card-body text-center py-2 py-md-3">
                    <h2 class="h3 h2-md fw-bold text-warning mb-0">{{ $stats['offen'] }}</h2>
                    <small class="text-muted">Offen</small>
                </div>
            </div>
        </div>
        <div class="col-4">
            <div class="card border-success h-100">
                <div class="card-body text-center py-2 py-md-3">
                    <h2 class="h3 h2-md fw-bold text-success mb-0">{{ $stats['erledigt'] }}</h2>
                    <small class="text-muted d-none d-sm-inline">Erledigt</small>
                    <small class="text-muted d-sm-none" style="font-size: 0.7rem;">Erl.</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter-Karte (Collapsible auf Mobile) --}}
    <div class="card shadow-sm mb-3">
        <div class="card-header bg-light py-2" 
             data-bs-toggle="collapse" 
             data-bs-target="#filterCollapse" 
             role="button"
             aria-expanded="true">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="mb-0">
                    <i class="bi bi-funnel"></i> Filter
                </h6>
                <i class="bi bi-chevron-down d-md-none"></i>
            </div>
        </div>
        <div class="collapse show" id="filterCollapse">
            <div class="card-body py-2 py-md-3">
                <form method="GET" action="{{ route('reinigungsplanung.index') }}" id="filterForm">
                    <div class="row g-2">
                        {{-- Monat & Tour (halbe Breite auf Mobile) --}}
                        <div class="col-6 col-md-2">
                            <label for="monat" class="form-label small mb-1">Monat</label>
                            <select name="monat" id="monat" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="" @selected(empty($filterMonat))>Alle</option>
                                @foreach($monate as $num => $name)
                                    <option value="{{ $num }}" @selected($filterMonat == $num)>
                                        {{ $name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-6 col-md-2">
                            <label for="tour" class="form-label small mb-1">Tour</label>
                            <select name="tour" id="tour" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">Alle</option>
                                @foreach($touren as $t)
                                    <option value="{{ $t->id }}" @selected($filterTour == $t->id)>
                                        {{ $t->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Codex & Gebäude --}}
                        <div class="col-6 col-md-2">
                            <label for="codex" class="form-label small mb-1">Codex</label>
                            <input type="text" name="codex" id="codex" class="form-control form-control-sm" 
                                   value="{{ $filterCodex }}" placeholder="z.B. gam">
                        </div>

                        <div class="col-6 col-md-3">
                            <label for="gebaeude" class="form-label small mb-1">Gebäude</label>
                            <input type="text" name="gebaeude" id="gebaeude" class="form-control form-control-sm" 
                                   value="{{ $filterGebaeude }}" placeholder="Name, Ort...">
                        </div>

                        {{-- Status & Buttons --}}
                        <div class="col-6 col-md-2">
                            <label for="status" class="form-label small mb-1">Status</label>
                            <select name="status" id="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="" @selected($filterStatus == '')>Alle</option>
                                <option value="offen" @selected($filterStatus == 'offen')>Offen</option>
                                <option value="erledigt" @selected($filterStatus == 'erledigt')>Erledigt</option>
                            </select>
                        </div>

                        <div class="col-6 col-md-1 d-flex align-items-end gap-1">
                            <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                                <i class="bi bi-search"></i>
                            </button>
                            <a href="{{ route('reinigungsplanung.index') }}" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-x-lg"></i>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Ergebnis --}}
    <div class="card shadow-sm">
        <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 small">
                <i class="bi bi-building"></i>
                {{ $gebaeude->count() }} Gebäude
            </h6>
            @if($stats['offen'] > 0)
                <span class="badge bg-warning text-dark">{{ $stats['offen'] }} offen</span>
            @else
                <span class="badge bg-success"><i class="bi bi-check"></i> Alle erledigt</span>
            @endif
        </div>

        @if($gebaeude->isEmpty())
            <div class="card-body text-center py-5">
                <i class="bi bi-inbox display-4 text-muted"></i>
                <p class="text-muted mt-2 mb-0">Keine Gebäude gefunden.</p>
            </div>
        @else
            {{-- Desktop: Tabelle --}}
            <div class="table-responsive d-none d-lg-block">
                <table class="table table-hover table-striped mb-0" id="reinigungsTable">
                    <thead class="table-dark">
                        <tr>
                            <th style="width: 100px;">Codex</th>
                            <th>Gebäude</th>
                            <th>Adresse</th>
                            <th>Tour(en)</th>
                            <th style="width: 120px;">Letzte Reinigung</th>
                            <th style="width: 120px;">Nächste Fälligkeit</th>
                            <th style="width: 100px;" class="text-center">Status</th>
                            <th style="width: 120px;" class="text-center no-print">Aktion</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($gebaeude as $g)
                            <tr class="{{ $g->ist_erledigt ? 'table-success' : '' }}">
                                <td>
                                    <a href="{{ route('gebaeude.edit', $g->id) }}" class="fw-bold text-decoration-none">
                                        {{ $g->codex ?: '-' }}
                                    </a>
                                </td>
                                <td>
                                    <a href="{{ route('gebaeude.edit', $g->id) }}" class="text-decoration-none">
                                        {{ $g->gebaeude_name ?: '(kein Name)' }}
                                    </a>
                                </td>
                                <td class="text-muted small">
                                    @if($g->strasse || $g->wohnort)
                                        {{ $g->strasse }} {{ $g->hausnummer }}@if($g->strasse && $g->wohnort),@endif
                                        {{ $g->plz }} {{ $g->wohnort }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @forelse($g->touren as $tour)
                                        <span class="badge bg-secondary">{{ $tour->name }}</span>
                                    @empty
                                        <span class="text-muted small">-</span>
                                    @endforelse
                                </td>
                                <td>
                                    @if($g->letzte_reinigung_datum)
                                        {{ $g->letzte_reinigung_datum->format('d.m.Y') }}
                                        <br><small class="text-muted">{{ $g->letzte_reinigung_datum->diffForHumans() }}</small>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($g->naechste_faelligkeit)
                                        {{ $g->naechste_faelligkeit->format('d.m.Y') }}
                                        @if($g->naechste_faelligkeit->isPast())
                                            <br><small class="text-danger">überfällig</small>
                                        @elseif($g->naechste_faelligkeit->isCurrentMonth())
                                            <br><small class="text-warning">diesen Monat</small>
                                        @endif
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($g->ist_erledigt)
                                        <span class="badge bg-success"><i class="bi bi-check-circle"></i> Erledigt</span>
                                    @else
                                        <span class="badge bg-warning text-dark"><i class="bi bi-clock"></i> Offen</span>
                                    @endif
                                </td>
                                <td class="text-center no-print">
                                    @if(!$g->ist_erledigt)
                                        <button type="button" class="btn btn-sm btn-success" 
                                                data-bs-toggle="modal" data-bs-target="#modalErledigt{{ $g->id }}">
                                            <i class="bi bi-check-lg"></i> Erledigt
                                        </button>
                                    @else
                                        <a href="{{ route('gebaeude.edit', $g->id) }}" class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Mobile: Card-Liste --}}
            <div class="d-lg-none">
                @foreach($gebaeude as $g)
                    <div class="border-bottom {{ $g->ist_erledigt ? 'bg-success bg-opacity-10' : '' }}">
                        <div class="p-2">
                            {{-- Zeile 1: Codex, Name, Status --}}
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <div class="flex-grow-1 min-width-0">
                                    <a href="{{ route('gebaeude.edit', $g->id) }}" class="text-decoration-none">
                                        <span class="fw-bold text-primary">{{ $g->codex ?: '-' }}</span>
                                        <span class="text-dark ms-1">{{ Str::limit($g->gebaeude_name ?: '(kein Name)', 25) }}</span>
                                    </a>
                                </div>
                                <div class="ms-2 flex-shrink-0">
                                    @if($g->ist_erledigt)
                                        <span class="badge bg-success"><i class="bi bi-check"></i></span>
                                    @else
                                        <span class="badge bg-warning text-dark"><i class="bi bi-clock"></i></span>
                                    @endif
                                </div>
                            </div>

                            {{-- Zeile 2: Adresse --}}
                            <div class="small text-muted mb-1">
                                <i class="bi bi-geo-alt"></i>
                                @if($g->strasse || $g->wohnort)
                                    {{ $g->strasse }} {{ $g->hausnummer }}, {{ $g->plz }} {{ $g->wohnort }}
                                @else
                                    Keine Adresse
                                @endif
                            </div>

                            {{-- Zeile 3: Tour, Letzte Reinigung, Nächste Fälligkeit --}}
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <div class="small">
                                    {{-- Tour --}}
                                    @forelse($g->touren as $tour)
                                        <span class="badge bg-secondary me-1">{{ $tour->name }}</span>
                                    @empty
                                        <span class="text-muted">Keine Tour</span>
                                    @endforelse
                                </div>
                                <div class="small text-end">
                                    {{-- Letzte Reinigung --}}
                                    <span class="text-muted">
                                        <i class="bi bi-calendar3"></i>
                                        @if($g->letzte_reinigung_datum)
                                            {{ $g->letzte_reinigung_datum->format('d.m.') }}
                                        @else
                                            -
                                        @endif
                                    </span>
                                    {{-- Nächste Fälligkeit --}}
                                    <span class="ms-2 {{ $g->naechste_faelligkeit && $g->naechste_faelligkeit->isPast() ? 'text-danger' : 'text-info' }}">
                                        <i class="bi bi-arrow-right"></i>
                                        @if($g->naechste_faelligkeit)
                                            {{ $g->naechste_faelligkeit->format('d.m.') }}
                                        @else
                                            -
                                        @endif
                                    </span>
                                </div>
                            </div>

                            {{-- Zeile 4: Button --}}
                            <div class="d-flex justify-content-end">
                                @if(!$g->ist_erledigt)
                                    <button type="button" 
                                            class="btn btn-success btn-sm py-1 px-2" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#modalErledigt{{ $g->id }}">
                                        <i class="bi bi-check-lg"></i> Erledigt
                                    </button>
                                @else
                                    <a href="{{ route('gebaeude.edit', $g->id) }}" 
                                       class="btn btn-outline-secondary btn-sm py-1 px-2">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Legende (kleiner auf Mobile) --}}
    <div class="mt-2 small text-muted" style="font-size: 0.75rem;">
        <i class="bi bi-info-circle"></i>
        Erledigt = Reinigung seit letzter Fälligkeit. Fälligkeit basiert auf aktiven Monaten (m01-m12).
    </div>
</div>

{{-- Modals (außerhalb Container für korrektes Overlay) --}}
@foreach($gebaeude->filter(fn($g) => !$g->ist_erledigt) as $g)
    <div class="modal fade" id="modalErledigt{{ $g->id }}" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('reinigungsplanung.erledigt', $g->id) }}">
                    @csrf
                    <div class="modal-header bg-success text-white py-2">
                        <h6 class="modal-title">
                            <i class="bi bi-check-circle"></i> Reinigung eintragen
                        </h6>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body py-3">
                        <div class="alert alert-info py-2 mb-3">
                            <strong>{{ $g->codex }}</strong> - {{ $g->gebaeude_name ?: '(kein Name)' }}
                            <br>
                            <small>{{ $g->strasse }} {{ $g->hausnummer }}, {{ $g->plz }} {{ $g->wohnort }}</small>
                            @if($g->naechste_faelligkeit)
                                <hr class="my-1">
                                <small>
                                    Nächste Fälligkeit: <strong>{{ $g->naechste_faelligkeit->format('d.m.Y') }}</strong>
                                </small>
                            @endif
                        </div>

                        <div class="mb-3">
                            <label for="person_id{{ $g->id }}" class="form-label">Mitarbeiter <span class="text-danger">*</span></label>
                            <select class="form-select" id="person_id{{ $g->id }}" name="person_id" required>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" @selected(Auth::id() == $user->id)>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="datum{{ $g->id }}" class="form-label">Datum</label>
                            <input type="date" class="form-control" id="datum{{ $g->id }}" name="datum" 
                                   value="{{ now()->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}">
                        </div>

                        <div class="mb-0">
                            <label for="bemerkung{{ $g->id }}" class="form-label">Bemerkung <small class="text-muted">(optional)</small></label>
                            <input type="text" class="form-control" id="bemerkung{{ $g->id }}" name="bemerkung" 
                                   placeholder="z.B. Fenster auch gereinigt" maxlength="500">
                        </div>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-lg"></i> Speichern
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach
@endsection

@push('styles')
<style>
    /* Mobile-Optimierungen */
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
        
        .form-control-sm, .form-select-sm {
            font-size: 0.875rem;
            padding: 0.25rem 0.5rem;
        }
    }

    /* Min-width für Text-Überlauf */
    .min-width-0 {
        min-width: 0;
    }

    /* Druck-Styles */
    @media print {
        .no-print, .d-lg-none {
            display: none !important;
        }
        
        .d-none.d-lg-block {
            display: block !important;
        }
        
        .card {
            border: 1px solid #ddd !important;
            box-shadow: none !important;
        }
        
        .table-dark {
            background-color: #f8f9fa !important;
            color: #000 !important;
        }
        
        .badge {
            border: 1px solid #666;
            color: #000 !important;
            background-color: #fff !important;
        }
        
        .table-success {
            background-color: #d4edda !important;
        }
    }

    /* Desktop: Zeilen-Hover */
    @media (min-width: 992px) {
        #reinigungsTable tbody tr {
            cursor: pointer;
        }
        
        #reinigungsTable tbody tr:hover {
            background-color: rgba(0, 123, 255, 0.05);
        }
    }

    /* Touch-Feedback für Mobile Cards */
    @media (max-width: 991.98px) {
        .border-bottom:active {
            background-color: rgba(0, 123, 255, 0.1);
        }
    }
</style>
@endpush

@push('scripts')
<script>
    // Enter-Taste im Filter
    document.getElementById('filterForm').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            this.submit();
        }
    });

    // Desktop: Zeilen-Klick → Gebäude öffnen
    document.querySelectorAll('#reinigungsTable tbody tr').forEach(row => {
        row.addEventListener('click', function(e) {
            if (e.target.closest('button, a, .btn')) return;
            const link = this.querySelector('a[href*="gebaeude"]');
            if (link) window.location.href = link.href;
        });
    });
</script>
@endpush
