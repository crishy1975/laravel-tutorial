{{-- resources/views/gebaeude/logs/erinnerungen.blade.php --}}
{{-- Dashboard: Alle offenen Erinnerungen --}}
@extends('layouts.app')

@section('content')
<div class="container-fluid px-2 px-md-4 py-3">

    {{-- Header --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <h4 class="mb-0">
                <i class="bi bi-bell-fill text-warning me-2"></i>
                Offene Erinnerungen
            </h4>
            <small class="text-muted">Alle faelligen Wiedervorlagen</small>
        </div>
        <a href="{{ route('gebaeude.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i>
            <span class="d-none d-sm-inline ms-1">Zurueck</span>
        </a>
    </div>

    {{-- Alerts --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show py-2" role="alert">
            <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Statistik-Karten --}}
    @php
        $heute = $erinnerungen->filter(fn($e) => $e->erinnerung_datum->isToday())->count();
        $ueberfaellig = $erinnerungen->filter(fn($e) => $e->erinnerung_datum->isPast() && !$e->erinnerung_datum->isToday())->count();
        $dieseWoche = $erinnerungen->filter(fn($e) => $e->erinnerung_datum->isBetween(now(), now()->endOfWeek()))->count();
    @endphp

    <div class="row g-3 mb-4">
        <div class="col-4">
            <div class="card border-0 shadow-sm h-100 @if($ueberfaellig > 0) border-start border-danger border-4 @endif">
                <div class="card-body text-center py-3">
                    <div class="fs-2 fw-bold text-danger">{{ $ueberfaellig }}</div>
                    <small class="text-muted">Ueberfaellig</small>
                </div>
            </div>
        </div>
        <div class="col-4">
            <div class="card border-0 shadow-sm h-100 @if($heute > 0) border-start border-warning border-4 @endif">
                <div class="card-body text-center py-3">
                    <div class="fs-2 fw-bold text-warning">{{ $heute }}</div>
                    <small class="text-muted">Heute</small>
                </div>
            </div>
        </div>
        <div class="col-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-3">
                    <div class="fs-2 fw-bold text-info">{{ $dieseWoche }}</div>
                    <small class="text-muted">Diese Woche</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Erinnerungsliste --}}
    @if($erinnerungen->isEmpty())
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="bi bi-bell-slash text-muted" style="font-size: 3rem;"></i>
                <h5 class="mt-3 text-muted">Keine offenen Erinnerungen</h5>
                <p class="text-muted mb-0">Alle Wiedervorlagen sind erledigt!</p>
            </div>
        </div>
    @else
        <div class="card border-0 shadow-sm overflow-hidden">
            @foreach($erinnerungen as $erinnerung)
                @php
                    $istUeberfaellig = $erinnerung->erinnerung_datum->isPast() && !$erinnerung->erinnerung_datum->isToday();
                    $istHeute = $erinnerung->erinnerung_datum->isToday();
                @endphp
                <div class="erinnerung-item d-flex p-3 border-bottom @if($istUeberfaellig) bg-danger bg-opacity-10 @elseif($istHeute) bg-warning bg-opacity-10 @endif">
                    {{-- Datum-Badge --}}
                    <div class="erinnerung-datum me-3 flex-shrink-0 text-center" style="width: 60px;">
                        <div class="badge @if($istUeberfaellig) bg-danger @elseif($istHeute) bg-warning text-dark @else bg-secondary @endif p-2 w-100">
                            <div class="small">{{ $erinnerung->erinnerung_datum->format('d.m.') }}</div>
                            <div class="fw-normal" style="font-size: 0.7rem;">
                                @if($istUeberfaellig)
                                    {{ $erinnerung->erinnerung_datum->diffInDays(now()) }} Tage
                                @elseif($istHeute)
                                    Heute
                                @else
                                    {{ $erinnerung->erinnerung_datum->format('D') }}
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    {{-- Inhalt --}}
                    <div class="erinnerung-content flex-grow-1 min-w-0">
                        {{-- Gebäude-Link --}}
                        <div class="mb-1">
                            <a href="{{ route('gebaeude.edit', $erinnerung->gebaeude_id) }}#content-protokoll" 
                               class="fw-bold text-decoration-none">
                                <i class="bi bi-building me-1"></i>
                                {{ $erinnerung->gebaeude->codex ?? $erinnerung->gebaeude->gebaeude_name ?? 'Gebaeude #'.$erinnerung->gebaeude_id }}
                            </a>
                            @if($erinnerung->prioritaet !== 'normal')
                                {!! $erinnerung->prioritaet_badge !!}
                            @endif
                        </div>
                        
                        {{-- Beschreibung --}}
                        <p class="mb-2 text-truncate-2">{{ $erinnerung->beschreibung }}</p>
                        
                        {{-- Footer --}}
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 small text-muted">
                            <span>
                                <i class="bi bi-person-circle"></i> {{ $erinnerung->benutzer_name }}
                                &middot;
                                Erstellt {{ $erinnerung->created_at->diffForHumans() }}
                            </span>
                        </div>
                    </div>
                    
                    {{-- Aktionen --}}
                    <div class="erinnerung-actions ms-2 flex-shrink-0">
                        <form method="POST" action="{{ route('gebaeude.logs.erledigt', $erinnerung->id) }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-success" title="Als erledigt markieren">
                                <i class="bi bi-check-lg"></i>
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

@push('styles')
<style>
.erinnerung-item:last-child { border-bottom: none !important; }
.erinnerung-item:hover { background-color: rgba(0,0,0,0.02); }
.min-w-0 { min-width: 0; }
.text-truncate-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

@media (max-width: 767.98px) {
    .erinnerung-item { padding: 0.75rem !important; }
    .erinnerung-datum { width: 50px !important; }
    .btn { min-height: 44px; }
}
</style>
@endpush
@endsection
