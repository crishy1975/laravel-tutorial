{{-- resources/views/dashboard/index.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container-fluid px-2 px-md-4 py-3">

    {{-- Begrüßung --}}
    <div class="card border-0 shadow-sm mb-4" style="background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 50%, #3a7ca5 100%);">
        <div class="card-body text-white py-3 py-md-4">
            <div class="row align-items-center">
                <div class="col-auto">
                    <span style="font-size: 2.5rem;" class="d-md-none">{{ $begruessung['emoji'] }}</span>
                    <span style="font-size: 3rem;" class="d-none d-md-inline">{{ $begruessung['emoji'] }}</span>
                </div>
                <div class="col">
                    <h4 class="mb-1 fw-bold text-white fs-6 fs-md-4">{{ $begruessung['spruch'] }}</h4>
                    <p class="mb-0 small" style="color: rgba(255,255,255,0.85);">
                        <i class="bi bi-calendar3 me-1"></i>{{ $begruessung['datum'] }}
                        <span class="mx-2 d-none d-sm-inline">•</span>
                        <br class="d-sm-none">
                        <i class="bi bi-clock me-1"></i>{{ $begruessung['uhrzeit'] }} Uhr
                    </p>
                </div>
                <div class="col-auto">
                    <button class="btn btn-outline-light btn-sm" onclick="location.reload();" title="Neuer Spruch">
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Statistik-Karten --}}
    <div class="row g-2 g-md-3 mb-4">
        {{-- Rechnung zu schreiben --}}
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100 @if($stats['rechnung_zu_schreiben'] > 0) border-start border-primary border-4 @endif">
                <div class="card-body p-2 p-md-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small mb-1">
                                <span class="d-none d-sm-inline">Rechnung zu schreiben</span>
                                <span class="d-sm-none">RE schreiben</span>
                            </div>
                            <div class="fs-3 fw-bold text-dark">{{ $stats['rechnung_zu_schreiben'] }}</div>
                            <span class="small text-muted">Gebäude</span>
                        </div>
                        <div class="bg-primary bg-opacity-10 rounded-circle p-2 d-none d-sm-flex">
                            <i class="bi bi-pencil-square text-primary fs-4"></i>
                        </div>
                    </div>
                </div>
                <a href="{{ route('gebaeude.index', ['rechnung_schreiben' => 1]) }}" class="stretched-link"></a>
            </div>
        </div>

        {{-- Fällige Reinigungen --}}
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100 @if($stats['faellige_reinigungen'] > 0) border-start border-danger border-4 @endif">
                <div class="card-body p-2 p-md-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small mb-1">
                                <span class="d-none d-sm-inline">Fällige Reinigungen</span>
                                <span class="d-sm-none">Reinigung fällig</span>
                            </div>
                            <div class="fs-3 fw-bold text-dark">{{ $stats['faellige_reinigungen'] }}</div>
                            <span class="small text-muted">Gebäude</span>
                        </div>
                        <div class="bg-danger bg-opacity-10 rounded-circle p-2 d-none d-sm-flex">
                            <i class="bi bi-calendar-x text-danger fs-4"></i>
                        </div>
                    </div>
                </div>
                <a href="{{ route('reinigungsplanung.index', ['status' => 'offen']) }}" class="stretched-link"></a>
            </div>
        </div>

        {{-- Erinnerungen --}}
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100 @if($stats['ueberfaellige_erinnerungen'] > 0) border-start border-warning border-4 @endif">
                <div class="card-body p-2 p-md-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small mb-1">Erinnerungen</div>
                            <div class="fs-3 fw-bold text-dark">{{ $stats['offene_erinnerungen'] }}</div>
                            @if($stats['heute_faellig'] > 0)
                                <span class="badge bg-warning text-dark">{{ $stats['heute_faellig'] }} heute</span>
                            @endif
                            @if($stats['ueberfaellige_erinnerungen'] > 0)
                                <span class="badge bg-danger">{{ $stats['ueberfaellige_erinnerungen'] }} überf.</span>
                            @endif
                        </div>
                        <div class="bg-warning bg-opacity-10 rounded-circle p-2 d-none d-sm-flex">
                            <i class="bi bi-bell text-warning fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Bank-Matching --}}
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100 @if($stats['unmatched_buchungen'] > 10) border-start border-secondary border-4 @endif">
                <div class="card-body p-2 p-md-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small mb-1">Bank-Matching</div>
                            @if($stats['tage_seit_match'] !== null)
                                <div class="fs-3 fw-bold text-dark">{{ $stats['tage_seit_match'] }}</div>
                                <span class="small text-muted">Tage</span>
                            @else
                                <div class="fs-5 text-muted">Noch nie</div>
                            @endif
                            @if($stats['unmatched_buchungen'] > 0)
                                <span class="badge bg-secondary">{{ $stats['unmatched_buchungen'] }} offen</span>
                            @endif
                        </div>
                        <div class="bg-secondary bg-opacity-10 rounded-circle p-2 d-none d-sm-flex">
                            <i class="bi bi-bank text-secondary fs-4"></i>
                        </div>
                    </div>
                </div>
                <a href="{{ route('bank.unmatched') }}" class="stretched-link"></a>
            </div>
        </div>
    </div>

    {{-- ⭐ Quick Actions - VERBESSERT --}}
    <div class="row g-2 mb-4">
        <div class="col-4 col-sm-4 col-md-2">
            <a href="{{ route('rechnung.index') }}" class="btn btn-outline-primary w-100 h-100 d-flex flex-column align-items-center justify-content-center py-2 py-md-3 quick-action-btn">
                <i class="bi bi-receipt fs-4 mb-1"></i>
                <span class="small d-none d-sm-block">Rechnungen</span>
                <span class="small d-sm-none">RE</span>
            </a>
        </div>
        <div class="col-4 col-sm-4 col-md-2">
            <a href="{{ route('gebaeude.index') }}" class="btn btn-outline-secondary w-100 h-100 d-flex flex-column align-items-center justify-content-center py-2 py-md-3 quick-action-btn">
                <i class="bi bi-building fs-4 mb-1"></i>
                <span class="small d-none d-sm-block">Gebäude</span>
                <span class="small d-sm-none">Geb.</span>
            </a>
        </div>
        <div class="col-4 col-sm-4 col-md-2">
            <a href="{{ route('reinigungsplanung.index') }}" class="btn btn-outline-success w-100 h-100 d-flex flex-column align-items-center justify-content-center py-2 py-md-3 quick-action-btn">
                <i class="bi bi-calendar-check fs-4 mb-1"></i>
                <span class="small d-none d-sm-block">Reinigung</span>
                <span class="small d-sm-none">Rein.</span>
            </a>
        </div>
        <div class="col-4 col-sm-4 col-md-2">
            <a href="{{ route('mahnungen.mahnlauf') }}" class="btn btn-outline-warning w-100 h-100 d-flex flex-column align-items-center justify-content-center py-2 py-md-3 quick-action-btn">
                <i class="bi bi-envelope-exclamation fs-4 mb-1"></i>
                <span class="small d-none d-sm-block">Mahnlauf</span>
                <span class="small d-sm-none">Mahn.</span>
            </a>
        </div>
        <div class="col-4 col-sm-4 col-md-2">
            <a href="{{ route('bank.index') }}" class="btn btn-outline-info w-100 h-100 d-flex flex-column align-items-center justify-content-center py-2 py-md-3 quick-action-btn">
                <i class="bi bi-bank fs-4 mb-1"></i>
                <span class="small d-none d-sm-block">Bank</span>
                <span class="small d-sm-none">Bank</span>
            </a>
        </div>
        <div class="col-4 col-sm-4 col-md-2">
            <button type="button" class="btn btn-outline-danger w-100 h-100 d-flex flex-column align-items-center justify-content-center py-2 py-md-3 quick-action-btn" id="btnFaelligkeitUpdate" title="Fälligkeits-Flags aktualisieren">
                <i class="bi bi-arrow-repeat fs-4 mb-1"></i>
                <span class="small d-none d-sm-block">Fälligkeiten</span>
                <span class="small d-sm-none">Fällig.</span>
            </button>
        </div>
    </div>

    {{-- Erinnerungen --}}
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-2">
                    <h5 class="mb-0 fs-6">
                        <i class="bi bi-bell-fill text-warning me-2"></i>
                        <span class="d-none d-sm-inline">Offene Erinnerungen</span>
                        <span class="d-sm-none">Erinnerungen</span>
                        @if($alleErinnerungen->count() > 0)
                            <span class="badge bg-warning text-dark ms-2">{{ $alleErinnerungen->count() }}</span>
                        @endif
                    </h5>
                </div>
                
                @if($alleErinnerungen->isEmpty())
                    <div class="card-body text-center py-4 py-md-5">
                        <i class="bi bi-check-circle text-success" style="font-size: 2.5rem;"></i>
                        <h5 class="mt-3 text-muted fs-6">Alles erledigt!</h5>
                        <p class="text-muted mb-0 small">Keine offenen Erinnerungen. Zeit für Kaffee! ☕</p>
                    </div>
                @else
                    <div class="list-group list-group-flush" id="erinnerungen-liste">
                        @foreach($alleErinnerungen as $erinnerung)
                            @php
                                $datum = \Carbon\Carbon::parse($erinnerung->erinnerung_datum);
                                $istHeute = $datum->isToday();
                                $istUeberfaellig = $datum->isPast() && !$istHeute;
                            @endphp
                            <div class="list-group-item erinnerung-item p-2 p-md-3 @if($istUeberfaellig) border-start border-danger border-3 @elseif($istHeute) border-start border-warning border-3 @endif" 
                                 id="erinnerung-{{ $erinnerung->typ }}-{{ $erinnerung->id }}">
                                 
                                <div class="d-flex align-items-start gap-2">
                                    {{-- Icon --}}
                                    <div class="flex-shrink-0">
                                        <span class="badge bg-{{ $erinnerung->farbe }} rounded-circle p-2">
                                            <i class="bi {{ $erinnerung->icon }}"></i>
                                        </span>
                                    </div>
                                    
                                    {{-- Content --}}
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="min-w-0">
                                                <a href="{{ $erinnerung->link }}" class="text-decoration-none fw-semibold text-dark d-block text-truncate">
                                                    {{ $erinnerung->titel }}
                                                </a>
                                                @if($erinnerung->codex)
                                                    <small class="text-muted font-monospace">{{ $erinnerung->codex }}</small>
                                                @endif
                                            </div>
                                            <div class="flex-shrink-0 ms-2 text-end">
                                                <span class="badge @if($istUeberfaellig) bg-danger @elseif($istHeute) bg-warning text-dark @else bg-light text-dark @endif">
                                                    {{ $datum->format('d.m.') }}
                                                </span>
                                            </div>
                                        </div>
                                        
                                        @if($erinnerung->beschreibung)
                                            <p class="mb-1 small text-muted text-truncate">{{ Str::limit($erinnerung->beschreibung, 80) }}</p>
                                        @endif
                                    </div>
                                    
                                    {{-- Erledigt-Button --}}
                                    <div class="flex-shrink-0">
                                        <button type="button" 
                                                class="btn btn-outline-success btn-sm erledigt-btn rounded-circle d-flex align-items-center justify-content-center"
                                                style="width: 36px; height: 36px;"
                                                data-typ="{{ $erinnerung->typ }}"
                                                data-id="{{ $erinnerung->id }}"
                                                title="Als erledigt markieren">
                                            <i class="bi bi-check-lg"></i>
                                        </button>
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

{{-- Toast für Fälligkeits-Update --}}
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="faelligkeitToast" class="toast" role="alert">
        <div class="toast-header">
            <i class="bi bi-arrow-repeat me-2"></i>
            <strong class="me-auto">Fälligkeiten</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body" id="faelligkeitToastBody">
            <!-- Dynamisch -->
        </div>
    </div>
</div>

@push('styles')
<style>
/* Quick Action Buttons */
.quick-action-btn {
    min-height: 60px;
    border-radius: 12px !important;
    transition: all 0.2s ease;
}

.quick-action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.quick-action-btn i {
    transition: transform 0.2s ease;
}

.quick-action-btn:hover i {
    transform: scale(1.1);
}

/* Erinnerungen */
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

.font-monospace {
    font-family: SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 0.85em;
}

/* Mobile Optimierungen */
@media (max-width: 575.98px) {
    .quick-action-btn {
        min-height: 50px;
        padding: 0.5rem !important;
        border-radius: 8px !important;
    }
    
    .quick-action-btn i {
        font-size: 1.25rem !important;
    }
    
    .fs-3 { font-size: 1.5rem !important; }
    
    .erledigt-btn {
        width: 32px !important;
        height: 32px !important;
    }
}

/* Tablet */
@media (min-width: 576px) and (max-width: 767.98px) {
    .quick-action-btn {
        min-height: 70px;
    }
}

/* Desktop */
@media (min-width: 768px) {
    .quick-action-btn {
        min-height: 80px;
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

    // Fälligkeiten aktualisieren Button
    const btnFaelligkeit = document.getElementById('btnFaelligkeitUpdate');
    if (btnFaelligkeit) {
        btnFaelligkeit.addEventListener('click', function() {
            const btn = this;
            const icon = btn.querySelector('i');
            
            btn.disabled = true;
            icon.classList.add('spin');
            
            fetch('{{ route("dashboard.faelligkeit-update") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                btn.disabled = false;
                icon.classList.remove('spin');
                
                // Toast anzeigen
                const toastBody = document.getElementById('faelligkeitToastBody');
                const toastEl = document.getElementById('faelligkeitToast');
                
                if (data.ok) {
                    toastBody.innerHTML = `
                        <i class="bi bi-check-circle text-success me-1"></i>
                        ${data.message}
                        <div class="mt-2 small">
                            <span class="badge bg-primary">${data.stats.faellig} fällig</span>
                            <span class="badge bg-secondary">${data.stats.nicht_faellig} OK</span>
                            <span class="badge bg-warning text-dark">${data.stats.geaendert} geändert</span>
                        </div>
                    `;
                    toastEl.classList.remove('bg-danger');
                    toastEl.classList.add('bg-success', 'text-white');
                } else {
                    toastBody.innerHTML = `<i class="bi bi-x-circle text-danger me-1"></i> ${data.message}`;
                    toastEl.classList.remove('bg-success');
                    toastEl.classList.add('bg-danger', 'text-white');
                }
                
                const toast = new bootstrap.Toast(toastEl, { delay: 5000 });
                toast.show();
                
                // Nach Erfolg: Seite neu laden
                if (data.ok) {
                    setTimeout(() => location.reload(), 2000);
                }
            })
            .catch(error => {
                console.error('Fehler:', error);
                btn.disabled = false;
                icon.classList.remove('spin');
                alert('Fehler beim Aktualisieren der Fälligkeiten');
            });
        });
    }
});
</script>

<style>
/* Spin Animation für Reload-Button */
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
.spin {
    animation: spin 1s linear infinite;
}
</style>
@endpush
@endsection
