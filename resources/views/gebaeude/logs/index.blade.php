{{-- resources/views/gebaeude/logs/index.blade.php --}}
{{-- Vollständige Log-Übersicht für ein Gebäude --}}
@extends('layouts.app')

@section('content')
<div class="container-fluid px-2 px-md-4 py-3">

    {{-- Breadcrumb / Header --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item">
                        <a href="{{ route('gebaeude.index') }}" class="text-decoration-none">Gebaeude</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="{{ route('gebaeude.edit', $gebaeude->id) }}" class="text-decoration-none">
                            {{ $gebaeude->codex ?: $gebaeude->gebaeude_name }}
                        </a>
                    </li>
                    <li class="breadcrumb-item active">Protokoll</li>
                </ol>
            </nav>
            <h4 class="mb-0">
                <i class="bi bi-clock-history text-primary me-2"></i>
                Aktivitaets-Protokoll
            </h4>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNeuerLog">
                <i class="bi bi-plus-lg"></i>
                <span class="d-none d-sm-inline ms-1">Neuer Eintrag</span>
            </button>
            <a href="{{ route('gebaeude.edit', $gebaeude->id) }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i>
                <span class="d-none d-sm-inline ms-1">Zurueck</span>
            </a>
        </div>
    </div>

    {{-- Alerts --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show py-2" role="alert">
            <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        {{-- Hauptbereich: Log-Liste --}}
        <div class="col-lg-9 order-2 order-lg-1">
            
            {{-- Filter --}}
            <div class="card shadow-sm mb-3 border-0">
                <div class="card-body p-2 p-md-3">
                    <form method="GET" action="{{ route('gebaeude.logs.index', $gebaeude->id) }}" class="row g-2 align-items-end">
                        <div class="col-6 col-md-3">
                            <label class="form-label small mb-1">Kategorie</label>
                            <select name="kategorie" class="form-select form-select-sm">
                                <option value="">Alle</option>
                                @foreach($kategorien as $key => $label)
                                    <option value="{{ $key }}" @selected(request('kategorie') === $key)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label small mb-1">Prioritaet</label>
                            <select name="prioritaet" class="form-select form-select-sm">
                                <option value="">Alle</option>
                                <option value="kritisch" @selected(request('prioritaet') === 'kritisch')>Kritisch</option>
                                <option value="hoch" @selected(request('prioritaet') === 'hoch')>Hoch</option>
                                <option value="normal" @selected(request('prioritaet') === 'normal')>Normal</option>
                                <option value="niedrig" @selected(request('prioritaet') === 'niedrig')>Niedrig</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label small mb-1">Suche</label>
                            <input type="text" name="suche" value="{{ request('suche') }}" 
                                   class="form-control form-control-sm" placeholder="Text suchen...">
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                        @if(request()->hasAny(['kategorie', 'prioritaet', 'suche', 'erinnerungen']))
                        <div class="col-auto">
                            <a href="{{ route('gebaeude.logs.index', $gebaeude->id) }}" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-x-lg"></i>
                            </a>
                        </div>
                        @endif
                    </form>
                </div>
            </div>

            {{-- Log-Einträge --}}
            @if($logs->isEmpty())
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                        <h5 class="mt-3 text-muted">Keine Eintraege gefunden</h5>
                        <button type="button" class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#modalNeuerLog">
                            <i class="bi bi-plus-lg me-1"></i> Ersten Eintrag erstellen
                        </button>
                    </div>
                </div>
            @else
                <div class="card border-0 shadow-sm overflow-hidden">
                    @foreach($logs as $log)
                    <div class="log-entry d-flex p-3 border-bottom @if($log->prioritaet === 'kritisch') bg-danger bg-opacity-10 @elseif($log->prioritaet === 'hoch') bg-warning bg-opacity-10 @endif">
                        {{-- Icon --}}
                        <div class="log-icon me-3 flex-shrink-0">
                            <span class="badge rounded-circle bg-{{ $log->farbe }} p-2" 
                                  style="width: 42px; height: 42px; display: flex; align-items: center; justify-content: center;">
                                <i class="{{ $log->icon }}" style="font-size: 1rem;"></i>
                            </span>
                        </div>
                        
                        {{-- Content --}}
                        <div class="log-content flex-grow-1 min-w-0">
                            {{-- Header --}}
                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-1">
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    {!! $log->typ_badge !!}
                                    @if($log->prioritaet !== 'normal')
                                        {!! $log->prioritaet_badge !!}
                                    @endif
                                    @if($log->erinnerung_datum && !$log->erinnerung_erledigt)
                                        <span class="badge bg-warning text-dark">
                                            <i class="bi bi-bell-fill"></i>
                                            {{ $log->erinnerung_datum->format('d.m.Y') }}
                                        </span>
                                    @endif
                                </div>
                                <small class="text-muted" title="{{ $log->datum_formatiert }}">
                                    {{ $log->zeit_relativ }}
                                </small>
                            </div>
                            
                            {{-- Beschreibung --}}
                            @if($log->beschreibung)
                                <p class="mb-2">{{ $log->beschreibung }}</p>
                            @endif
                            
                            {{-- Kontakt-Info --}}
                            @if($log->kontakt_person || $log->kontakt_telefon || $log->kontakt_email)
                            <div class="small text-muted mb-2">
                                @if($log->kontakt_person)
                                    <span class="me-3">
                                        <i class="bi bi-person"></i> {{ $log->kontakt_person }}
                                    </span>
                                @endif
                                @if($log->kontakt_telefon)
                                    <span class="me-3">
                                        <a href="tel:{{ $log->kontakt_telefon }}" class="text-decoration-none">
                                            <i class="bi bi-telephone"></i> {{ $log->kontakt_telefon }}
                                        </a>
                                    </span>
                                @endif
                                @if($log->kontakt_email)
                                    <span>
                                        <a href="mailto:{{ $log->kontakt_email }}" class="text-decoration-none">
                                            <i class="bi bi-envelope"></i> {{ $log->kontakt_email }}
                                        </a>
                                    </span>
                                @endif
                            </div>
                            @endif
                            
                            {{-- Footer --}}
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 small">
                                <span class="text-muted">
                                    <i class="bi bi-person-circle"></i> {{ $log->benutzer_name }}
                                    &middot;
                                    {{ $log->datum_formatiert }}
                                </span>
                                
                                <div class="btn-group btn-group-sm">
                                    @if($log->erinnerung_datum && !$log->erinnerung_erledigt)
                                        <form method="POST" action="{{ route('gebaeude.logs.erledigt', $log->id) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-success" title="Als erledigt markieren">
                                                <i class="bi bi-check-lg"></i>
                                            </button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('gebaeude.logs.destroy', $log->id) }}" 
                                          class="d-inline" 
                                          onsubmit="return confirm('Eintrag wirklich loeschen?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Loeschen">
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
                @if($logs->hasPages())
                <div class="mt-3">
                    {{ $logs->links() }}
                </div>
                @endif
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="col-lg-3 order-1 order-lg-2 mb-3 mb-lg-0">
            {{-- Gebäude-Info --}}
            <div class="card shadow-sm mb-3 border-0">
                <div class="card-header bg-primary text-white">
                    <i class="bi bi-building me-2"></i>Gebaeude
                </div>
                <div class="card-body">
                    <h6 class="mb-1">{{ $gebaeude->gebaeude_name ?: '-' }}</h6>
                    @if($gebaeude->codex)
                        <span class="badge bg-dark font-monospace">{{ $gebaeude->codex }}</span>
                    @endif
                    <div class="small text-muted mt-2">
                        @if($gebaeude->strasse)
                            {{ $gebaeude->strasse }} {{ $gebaeude->hausnummer }}<br>
                        @endif
                        {{ $gebaeude->plz }} {{ $gebaeude->wohnort }}
                    </div>
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="card shadow-sm mb-3 border-0">
                <div class="card-header bg-light">
                    <i class="bi bi-lightning me-2"></i>Schnellaktionen
                </div>
                <div class="card-body p-2">
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm text-start" 
                                data-bs-toggle="modal" data-bs-target="#modalNotiz">
                            <i class="bi bi-sticky me-2"></i>Notiz
                        </button>
                        <button type="button" class="btn btn-outline-info btn-sm text-start" 
                                data-bs-toggle="modal" data-bs-target="#modalTelefonat">
                            <i class="bi bi-telephone me-2"></i>Telefonat
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm text-start" 
                                data-bs-toggle="modal" data-bs-target="#modalProblem">
                            <i class="bi bi-exclamation-triangle me-2"></i>Problem
                        </button>
                    </div>
                </div>
            </div>

            {{-- Statistik --}}
            <div class="card shadow-sm border-0">
                <div class="card-header bg-light">
                    <i class="bi bi-bar-chart me-2"></i>Statistik
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                        <span class="small">Gesamt</span>
                        <span class="badge bg-secondary">{{ $gebaeude->logs()->count() }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                        <span class="small">Diesen Monat</span>
                        <span class="badge bg-primary">
                            {{ $gebaeude->logs()->whereMonth('created_at', now()->month)->count() }}
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                        <span class="small">Offene Erinnerungen</span>
                        <span class="badge bg-warning text-dark">
                            {{ $gebaeude->offeneErinnerungen()->count() }}
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                        <span class="small">Offene Probleme</span>
                        <span class="badge bg-danger">
                            {{ $gebaeude->offeneProbleme()->count() }}
                        </span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

{{-- Modals (außerhalb des Formulars) --}}
@include('gebaeude.partials._log_modals', ['gebaeude' => $gebaeude])

{{-- Mobile Sticky Footer --}}
<div class="d-md-none mobile-sticky-bar">
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-secondary flex-fill" 
                data-bs-toggle="modal" data-bs-target="#modalNotiz">
            <i class="bi bi-sticky"></i>
        </button>
        <button type="button" class="btn btn-outline-info flex-fill" 
                data-bs-toggle="modal" data-bs-target="#modalTelefonat">
            <i class="bi bi-telephone"></i>
        </button>
        <button type="button" class="btn btn-outline-danger flex-fill" 
                data-bs-toggle="modal" data-bs-target="#modalProblem">
            <i class="bi bi-exclamation-triangle"></i>
        </button>
        <button type="button" class="btn btn-primary flex-fill" 
                data-bs-toggle="modal" data-bs-target="#modalNeuerLog">
            <i class="bi bi-plus-lg"></i>
        </button>
    </div>
</div>

@push('styles')
<style>
.log-entry:last-child { border-bottom: none !important; }
.log-entry:hover { background-color: rgba(0,0,0,0.02); }
.min-w-0 { min-width: 0; }

/* Mobile Optimierungen */
@media (max-width: 767.98px) {
    .form-control, .form-select, .btn { 
        min-height: 44px; 
        font-size: 16px !important; 
    }
    
    /* Log-Eintraege kompakter auf Mobile */
    .log-entry {
        padding: 0.75rem !important;
    }
    
    .log-icon .badge {
        width: 36px !important;
        height: 36px !important;
    }
    
    .log-icon .badge i {
        font-size: 0.875rem !important;
    }
    
    /* Sticky Bottom Bar fuer Mobile */
    .mobile-sticky-bar {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: #fff;
        border-top: 1px solid #dee2e6;
        padding: 0.75rem 1rem;
        z-index: 1030;
        box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
    }
    
    .container-fluid {
        padding-bottom: 80px;
    }
    
    /* Sidebar oben auf Mobile */
    .order-1 { order: 1 !important; }
    .order-2 { order: 2 !important; }
}

/* Desktop: Sidebar rechts */
@media (min-width: 992px) {
    .order-lg-1 { order: 1 !important; }
    .order-lg-2 { order: 2 !important; }
}
</style>
@endpush
@endsection
