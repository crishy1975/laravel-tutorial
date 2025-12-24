{{-- resources/views/gebaeude/partials/_log_timeline.blade.php --}}
{{-- Zeigt die letzten Aktivitäten als kompakte Timeline --}}
{{-- Einbinden: @include('gebaeude.partials._log_timeline', ['gebaeude' => $gebaeude]) --}}

@php
    $logs = $gebaeude->logs()->limit(10)->get();
    $offeneErinnerungen = $gebaeude->offeneErinnerungen()->count();
    $offeneProbleme = $gebaeude->offeneProbleme()->count();
@endphp

<div class="card shadow-sm mb-4">
    <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
        <h6 class="mb-0">
            <i class="bi bi-clock-history me-2"></i>
            Aktivitaeten
        </h6>
        <div class="d-flex gap-2">
            @if($offeneErinnerungen > 0)
                <span class="badge bg-warning text-dark">
                    <i class="bi bi-bell-fill"></i> {{ $offeneErinnerungen }}
                </span>
            @endif
            @if($offeneProbleme > 0)
                <span class="badge bg-danger">
                    <i class="bi bi-exclamation-triangle-fill"></i> {{ $offeneProbleme }}
                </span>
            @endif
        </div>
    </div>
    
    <div class="card-body p-0">
        {{-- Quick Actions --}}
        <div class="p-3 border-bottom bg-light log-quick-actions">
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-sm btn-outline-secondary flex-fill flex-md-grow-0" 
                        data-bs-toggle="modal" data-bs-target="#modalNotiz">
                    <i class="bi bi-sticky"></i>
                    <span class="ms-1">Notiz</span>
                </button>
                <button type="button" class="btn btn-sm btn-outline-info flex-fill flex-md-grow-0" 
                        data-bs-toggle="modal" data-bs-target="#modalTelefonat">
                    <i class="bi bi-telephone"></i>
                    <span class="ms-1">Telefonat</span>
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger flex-fill flex-md-grow-0" 
                        data-bs-toggle="modal" data-bs-target="#modalProblem">
                    <i class="bi bi-exclamation-triangle"></i>
                    <span class="ms-1">Problem</span>
                </button>
                <a href="{{ route('gebaeude.logs.index', $gebaeude->id) }}" 
                   class="btn btn-sm btn-outline-primary ms-md-auto">
                    <i class="bi bi-list-ul"></i>
                    <span class="d-none d-sm-inline ms-1">Alle</span>
                </a>
            </div>
        </div>
        
        {{-- Timeline --}}
        @if($logs->isEmpty())
            <div class="p-4 text-center text-muted">
                <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                <p class="mb-0 mt-2">Noch keine Eintraege</p>
            </div>
        @else
            <div class="log-timeline">
                @foreach($logs as $log)
                <div class="log-item d-flex p-3 border-bottom @if($log->prioritaet === 'kritisch') bg-danger bg-opacity-10 @elseif($log->prioritaet === 'hoch') bg-warning bg-opacity-10 @endif">
                    {{-- Icon --}}
                    <div class="log-icon me-3">
                        <span class="badge rounded-circle bg-{{ $log->farbe }} p-2">
                            <i class="{{ $log->icon }}"></i>
                        </span>
                    </div>
                    
                    {{-- Content --}}
                    <div class="log-content flex-grow-1 min-w-0">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <strong class="text-truncate">{{ $log->titel }}</strong>
                            <small class="text-muted ms-2 flex-shrink-0" title="{{ $log->datum_formatiert }}">
                                {{ $log->zeit_relativ }}
                            </small>
                        </div>
                        
                        @if($log->beschreibung)
                            <p class="mb-1 small text-muted text-truncate-2">
                                {{ Str::limit($log->beschreibung, 120) }}
                            </p>
                        @endif
                        
                        <div class="d-flex align-items-center gap-2 small">
                            <span class="text-muted">
                                <i class="bi bi-person"></i> {{ $log->benutzer_name }}
                            </span>
                            
                            @if($log->erinnerung_datum && !$log->erinnerung_erledigt)
                                <span class="badge bg-warning text-dark">
                                    <i class="bi bi-bell"></i> 
                                    {{ $log->erinnerung_datum->format('d.m.') }}
                                </span>
                            @endif
                            
                            @if($log->prioritaet !== 'normal')
                                {!! $log->prioritaet_badge !!}
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            
            @if($gebaeude->logs()->count() > 10)
            <div class="p-2 text-center border-top">
                <a href="{{ route('gebaeude.logs.index', $gebaeude->id) }}" class="btn btn-sm btn-link">
                    Alle {{ $gebaeude->logs()->count() }} Eintraege anzeigen
                    <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            @endif
        @endif
    </div>
</div>

<style>
.log-timeline .log-item:last-child { border-bottom: none !important; }
.log-icon .badge { width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; }
.text-truncate-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.min-w-0 { min-width: 0; }

/* Mobile Optimierungen */
@media (max-width: 767.98px) {
    .log-timeline .log-item {
        padding: 0.75rem !important;
    }
    
    .log-timeline .log-icon {
        margin-right: 0.5rem !important;
    }
    
    .log-timeline .log-icon .badge {
        width: 32px !important;
        height: 32px !important;
        padding: 0.35rem !important;
    }
    
    .log-timeline .log-icon .badge i {
        font-size: 0.75rem;
    }
    
    /* Quick Actions als grosse Touch-Buttons */
    .log-quick-actions .btn {
        min-height: 44px;
        font-size: 16px !important;
    }
}
</style>
