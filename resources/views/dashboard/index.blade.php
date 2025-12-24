{{-- resources/views/dashboard/index.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container-fluid px-2 px-md-4 py-3">

    {{-- Begrüßung --}}
    <div class="card border-0 shadow-sm mb-4" style="background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 50%, #3a7ca5 100%);">
        <div class="card-body text-white py-4">
            <div class="row align-items-center">
                <div class="col-auto">
                    <span style="font-size: 3rem;">{{ $begruessung['emoji'] }}</span>
                </div>
                <div class="col">
                    <h4 class="mb-1 fw-bold text-white">{{ $begruessung['spruch'] }}</h4>
                    <p class="mb-0" style="color: rgba(255,255,255,0.85);">
                        <i class="bi bi-calendar3 me-1"></i>{{ $begruessung['datum'] }}
                        <span class="mx-2">•</span>
                        <i class="bi bi-clock me-1"></i>{{ $begruessung['uhrzeit'] }} Uhr
                    </p>
                </div>
                <div class="col-auto d-none d-md-block">
                    <button class="btn btn-outline-light btn-sm" onclick="location.reload();" title="Neuer Spruch">
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Statistik-Karten --}}
    <div class="row g-3 mb-4">
        {{-- Offene Rechnungen --}}
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100 @if($stats['ueberfaellige_rechnungen'] > 0) border-start border-danger border-4 @endif">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small mb-1">Offene Rechnungen</div>
                            <div class="fs-3 fw-bold text-dark">{{ $stats['offene_rechnungen'] }}</div>
                            @if($stats['ueberfaellige_rechnungen'] > 0)
                                <span class="badge bg-danger">{{ $stats['ueberfaellige_rechnungen'] }} überfällig</span>
                            @endif
                        </div>
                        <div class="bg-primary bg-opacity-10 rounded-circle p-2">
                            <i class="bi bi-receipt text-primary fs-4"></i>
                        </div>
                    </div>
                    <div class="mt-2 small text-muted">
                        {{ number_format($stats['offener_betrag'], 2, ',', '.') }} € offen
                    </div>
                </div>
                <a href="{{ route('rechnung.index', ['status' => 'sent']) }}" class="stretched-link"></a>
            </div>
        </div>

        {{-- Erinnerungen --}}
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100 @if($stats['ueberfaellige_erinnerungen'] > 0) border-start border-warning border-4 @endif">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small mb-1">Erinnerungen</div>
                            <div class="fs-3 fw-bold text-dark">{{ $stats['offene_erinnerungen'] }}</div>
                            @if($stats['heute_faellig'] > 0)
                                <span class="badge bg-warning text-dark">{{ $stats['heute_faellig'] }} heute</span>
                            @endif
                            @if($stats['ueberfaellige_erinnerungen'] > 0)
                                <span class="badge bg-danger">{{ $stats['ueberfaellige_erinnerungen'] }} überfällig</span>
                            @endif
                        </div>
                        <div class="bg-warning bg-opacity-10 rounded-circle p-2">
                            <i class="bi bi-bell text-warning fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Mahnungen --}}
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100 @if($stats['tage_seit_mahnung'] !== null && $stats['tage_seit_mahnung'] > 14) border-start border-info border-4 @endif">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small mb-1">Letzter Mahnlauf</div>
                            @if($stats['tage_seit_mahnung'] !== null)
                                <div class="fs-3 fw-bold text-dark">{{ $stats['tage_seit_mahnung'] }}</div>
                                <span class="small text-muted">Tage her</span>
                            @else
                                <div class="fs-5 text-muted">Noch nie</div>
                            @endif
                        </div>
                        <div class="bg-info bg-opacity-10 rounded-circle p-2">
                            <i class="bi bi-envelope-exclamation text-info fs-4"></i>
                        </div>
                    </div>
                </div>
                <a href="{{ route('mahnungen.index') }}" class="stretched-link"></a>
            </div>
        </div>

        {{-- Bank-Matching --}}
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100 @if($stats['unmatched_buchungen'] > 10) border-start border-secondary border-4 @endif">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small mb-1">Bank-Matching</div>
                            @if($stats['tage_seit_match'] !== null)
                                <div class="fs-3 fw-bold text-dark">{{ $stats['tage_seit_match'] }}</div>
                                <span class="small text-muted">Tage seit Match</span>
                            @else
                                <div class="fs-5 text-muted">Noch nie</div>
                            @endif
                            @if($stats['unmatched_buchungen'] > 0)
                                <span class="badge bg-secondary">{{ $stats['unmatched_buchungen'] }} offen</span>
                            @endif
                        </div>
                        <div class="bg-secondary bg-opacity-10 rounded-circle p-2">
                            <i class="bi bi-bank text-secondary fs-4"></i>
                        </div>
                    </div>
                </div>
                <a href="{{ route('bank.unmatched') }}" class="stretched-link"></a>
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body py-2">
                    <div class="d-flex flex-wrap gap-2 justify-content-center">
                        <a href="{{ route('rechnung.create') }}" class="btn btn-outline-primary">
                            <i class="bi bi-plus-circle me-1"></i>Neue Rechnung
                        </a>
                        <a href="{{ route('gebaeude.create') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-building-add me-1"></i>Neues Gebäude
                        </a>
                        <a href="{{ route('reinigungsplanung.index') }}" class="btn btn-outline-success">
                            <i class="bi bi-calendar-check me-1"></i>Reinigungsplanung
                        </a>
                        <a href="{{ route('mahnungen.mahnlauf') }}" class="btn btn-outline-warning">
                            <i class="bi bi-envelope-exclamation me-1"></i>Mahnlauf
                        </a>
                        <a href="{{ route('bank.autoMatchProgress') }}" class="btn btn-outline-info">
                            <i class="bi bi-arrow-left-right me-1"></i>Auto-Match
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Erinnerungen --}}
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-bell-fill text-warning me-2"></i>
                        Offene Erinnerungen
                        @if($alleErinnerungen->count() > 0)
                            <span class="badge bg-warning text-dark ms-2">{{ $alleErinnerungen->count() }}</span>
                        @endif
                    </h5>
                    <div class="btn-group btn-group-sm" role="group">
                        <a href="{{ route('gebaeude.erinnerungen') }}" class="btn btn-outline-primary">
                            <i class="bi bi-building"></i>
                            <span class="d-none d-sm-inline ms-1">Gebäude</span>
                        </a>
                        <a href="{{ route('rechnung.logs.dashboard') }}" class="btn btn-outline-success">
                            <i class="bi bi-receipt"></i>
                            <span class="d-none d-sm-inline ms-1">Rechnungen</span>
                        </a>
                    </div>
                </div>
                
                @if($alleErinnerungen->isEmpty())
                    <div class="card-body text-center py-5">
                        <i class="bi bi-check-circle text-success" style="font-size: 3rem;"></i>
                        <h5 class="mt-3 text-muted">Alles erledigt!</h5>
                        <p class="text-muted mb-0">Keine offenen Erinnerungen. Zeit für Kaffee! ☕</p>
                    </div>
                @else
                    <div class="list-group list-group-flush" id="erinnerungen-liste">
                        @foreach($alleErinnerungen as $erinnerung)
                            @php
                                $istUeberfaellig = \Carbon\Carbon::parse($erinnerung->erinnerung_datum)->isPast() && !\Carbon\Carbon::parse($erinnerung->erinnerung_datum)->isToday();
                                $istHeute = \Carbon\Carbon::parse($erinnerung->erinnerung_datum)->isToday();
                            @endphp
                            <div class="list-group-item erinnerung-item @if($istUeberfaellig) bg-danger bg-opacity-10 @elseif($istHeute) bg-warning bg-opacity-10 @endif"
                                 id="erinnerung-{{ $erinnerung->typ }}-{{ $erinnerung->id }}">
                                <div class="d-flex align-items-center gap-3">
                                    {{-- Checkbox --}}
                                    <div class="flex-shrink-0">
                                        <button type="button" 
                                                class="btn btn-outline-success btn-sm rounded-circle erledigt-btn"
                                                data-typ="{{ $erinnerung->typ }}"
                                                data-id="{{ $erinnerung->id }}"
                                                title="Als erledigt markieren"
                                                style="width: 38px; height: 38px;">
                                            <i class="bi bi-check-lg"></i>
                                        </button>
                                    </div>
                                    
                                    {{-- Datum --}}
                                    <div class="flex-shrink-0 text-center" style="width: 55px;">
                                        <div class="badge @if($istUeberfaellig) bg-danger @elseif($istHeute) bg-warning text-dark @else bg-secondary @endif p-2 w-100">
                                            <div class="small fw-bold">{{ \Carbon\Carbon::parse($erinnerung->erinnerung_datum)->format('d.m.') }}</div>
                                            <div style="font-size: 0.65rem;">
                                                @if($istUeberfaellig)
                                                    {{ (int) \Carbon\Carbon::parse($erinnerung->erinnerung_datum)->diffInDays(now()) }}d
                                                @elseif($istHeute)
                                                    Heute
                                                @else
                                                    {{ \Carbon\Carbon::parse($erinnerung->erinnerung_datum)->locale('de')->shortDayName }}
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    
                                    {{-- Icon --}}
                                    <div class="flex-shrink-0">
                                        <span class="badge bg-{{ $erinnerung->farbe }} rounded-circle p-2">
                                            <i class="{{ $erinnerung->icon }}"></i>
                                        </span>
                                    </div>
                                    
                                    {{-- Inhalt --}}
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <a href="{{ $erinnerung->link }}" class="fw-bold text-decoration-none text-truncate">
                                                {{ $erinnerung->titel }}
                                            </a>
                                            @if($erinnerung->prioritaet !== 'normal')
                                                <span class="badge @if($erinnerung->prioritaet === 'kritisch') bg-danger @elseif($erinnerung->prioritaet === 'hoch') bg-warning text-dark @else bg-secondary @endif">
                                                    {{ ucfirst($erinnerung->prioritaet) }}
                                                </span>
                                            @endif
                                        </div>
                                        <p class="mb-0 small text-muted text-truncate">
                                            {{ Str::limit($erinnerung->beschreibung, 80) }}
                                        </p>
                                    </div>
                                    
                                    {{-- Link --}}
                                    <div class="flex-shrink-0 d-none d-md-block">
                                        <a href="{{ $erinnerung->link }}" class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-arrow-right"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

</div>

@push('styles')
<style>
.erinnerung-item {
    transition: all 0.3s ease;
}

.erinnerung-item.erledigt {
    opacity: 0.5;
    text-decoration: line-through;
    background-color: #e8f5e9 !important;
}

.erinnerung-item.removing {
    transform: translateX(100%);
    opacity: 0;
}

.min-w-0 { min-width: 0; }

.erledigt-btn:hover {
    background-color: #198754 !important;
    color: white !important;
}

/* Mobile Optimierungen */
@media (max-width: 767.98px) {
    .card-body { padding: 0.75rem; }
    
    .fs-3 { font-size: 1.5rem !important; }
    
    .erledigt-btn {
        width: 44px !important;
        height: 44px !important;
    }
    
    .list-group-item {
        padding: 0.75rem !important;
    }
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Erledigt-Buttons
    document.querySelectorAll('.erledigt-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const typ = this.dataset.typ;
            const id = this.dataset.id;
            const item = document.getElementById(`erinnerung-${typ}-${id}`);
            
            // Button deaktivieren
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
            
            // AJAX Request
            fetch('{{ route("dashboard.erledigt") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ typ, id })
            })
            .then(response => response.json())
            .then(data => {
                if (data.ok) {
                    // Animation
                    item.classList.add('erledigt');
                    setTimeout(() => {
                        item.classList.add('removing');
                        setTimeout(() => {
                            item.remove();
                            
                            // Prüfen ob Liste leer
                            const liste = document.getElementById('erinnerungen-liste');
                            if (liste && liste.children.length === 0) {
                                location.reload();
                            }
                        }, 300);
                    }, 500);
                }
            })
            .catch(error => {
                console.error('Fehler:', error);
                this.disabled = false;
                this.innerHTML = '<i class="bi bi-check-lg"></i>';
            });
        });
    });
});
</script>
@endpush
@endsection
