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
        {{-- Rechnung zu schreiben --}}
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100 @if($stats['rechnung_zu_schreiben'] > 0) border-start border-primary border-4 @endif">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small mb-1">Rechnung zu schreiben</div>
                            <div class="fs-3 fw-bold text-dark">{{ $stats['rechnung_zu_schreiben'] }}</div>
                            <span class="small text-muted">Gebäude</span>
                        </div>
                        <div class="bg-primary bg-opacity-10 rounded-circle p-2">
                            <i class="bi bi-pencil-square text-primary fs-4"></i>
                        </div>
                    </div>
                </div>
                <a href="{{ route('gebaeude.index', ['rechnung_schreiben' => 1]) }}" class="stretched-link"></a>
            </div>
        </div>

        {{-- ⭐ NEU: Fällige Reinigungen --}}
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100 @if($stats['faellige_reinigungen'] > 0) border-start border-danger border-4 @endif">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small mb-1">Fällige Reinigungen</div>
                            <div class="fs-3 fw-bold text-dark">{{ $stats['faellige_reinigungen'] }}</div>
                            <span class="small text-muted">Gebäude</span>
                        </div>
                        <div class="bg-danger bg-opacity-10 rounded-circle p-2">
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
                        <a href="{{ route('rechnung.index') }}" class="btn btn-outline-primary">
                            <i class="bi bi-receipt me-1"></i>Rechnungen
                        </a>
                        <a href="{{ route('gebaeude.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-building me-1"></i>Gebäude
                        </a>
                        <a href="{{ route('reinigungsplanung.index') }}" class="btn btn-outline-success">
                            <i class="bi bi-calendar-check me-1"></i>Reinigungsplanung
                        </a>
                        <a href="{{ route('mahnungen.mahnlauf') }}" class="btn btn-outline-warning">
                            <i class="bi bi-envelope-exclamation me-1"></i>Mahnlauf
                        </a>
                        <a href="{{ route('bank.index') }}" class="btn btn-outline-info">
                            <i class="bi bi-bank me-1"></i>Bankbuchungen
                        </a>
                        
                        {{-- ⭐ NEU: Fälligkeiten aktualisieren --}}
                        <button type="button" class="btn btn-outline-danger" id="btnFaelligkeitUpdate" title="Fälligkeits-Flags aller Gebäude aktualisieren">
                            <i class="bi bi-arrow-repeat me-1"></i>Fälligkeiten
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Erinnerungen --}}
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="bi bi-bell-fill text-warning me-2"></i>
                        Offene Erinnerungen
                        @if($alleErinnerungen->count() > 0)
                            <span class="badge bg-warning text-dark ms-2">{{ $alleErinnerungen->count() }}</span>
                        @endif
                    </h5>
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
                                $datum = \Carbon\Carbon::parse($erinnerung->erinnerung_datum);
                                $istHeute = $datum->isToday();
                                $istUeberfaellig = $datum->isPast() && !$istHeute;
                            @endphp
                            <div class="list-group-item erinnerung-item @if($istUeberfaellig) border-start border-danger border-3 @elseif($istHeute) border-start border-warning border-3 @endif" 
                                 id="erinnerung-{{ $erinnerung->typ }}-{{ $erinnerung->id }}">
                                 
                                {{-- Desktop Layout --}}
                                <div class="d-none d-md-flex align-items-center gap-3">
                                    {{-- Erledigt-Button --}}
                                    <button type="button" 
                                            class="btn btn-outline-success erledigt-btn flex-shrink-0" 
                                            title="Als erledigt markieren"
                                            data-typ="{{ $erinnerung->typ }}"
                                            data-id="{{ $erinnerung->id }}"
                                            style="width: 40px; height: 40px;">
                                        <i class="bi bi-check-lg"></i>
                                    </button>
                                    
                                    {{-- Typ-Badge --}}
                                    <span class="badge bg-{{ $erinnerung->farbe }} flex-shrink-0" style="width: 100px;">
                                        <i class="{{ $erinnerung->icon }} me-1"></i>
                                        @if($erinnerung->codex)
                                            {{ $erinnerung->codex }}
                                        @elseif($erinnerung->typ === 'rechnung' && isset($erinnerung->rechnungsnummer))
                                            {{ $erinnerung->rechnungsnummer }}
                                        @else
                                            {{ $erinnerung->typ === 'gebaeude' ? 'Gebäude' : 'Rechnung' }}
                                        @endif
                                    </span>
                                    
                                    {{-- Priorität --}}
                                    @if($erinnerung->prioritaet !== 'normal')
                                        <span class="badge @if($erinnerung->prioritaet === 'kritisch') bg-danger @elseif($erinnerung->prioritaet === 'hoch') bg-warning text-dark @else bg-secondary @endif">
                                            !
                                        </span>
                                    @endif
                                    
                                    {{-- Content --}}
                                    <div class="flex-grow-1 min-w-0">
                                        <a href="{{ $erinnerung->link }}" class="fw-bold text-decoration-none">
                                            {{ $erinnerung->name ?? $erinnerung->titel }}
                                        </a>
                                        @if($erinnerung->beschreibung)
                                            <span class="text-muted ms-2">{{ Str::limit($erinnerung->beschreibung, 80) }}</span>
                                        @endif
                                    </div>
                                    
                                    {{-- Datum --}}
                                    <span class="badge @if($istUeberfaellig) bg-danger @elseif($istHeute) bg-warning text-dark @else bg-secondary @endif flex-shrink-0">
                                        {{ $datum->format('d.m.Y') }}
                                    </span>
                                </div>
                                
                                {{-- Mobile Layout --}}
                                <div class="d-md-none">
                                    <div class="d-flex align-items-start gap-2">
                                        {{-- Erledigt-Button --}}
                                        <button type="button" 
                                                class="btn btn-outline-success erledigt-btn flex-shrink-0" 
                                                title="Als erledigt markieren"
                                                data-typ="{{ $erinnerung->typ }}"
                                                data-id="{{ $erinnerung->id }}"
                                                style="width: 40px; height: 40px;">
                                            <i class="bi bi-check-lg"></i>
                                        </button>
                                        
                                        {{-- Content --}}
                                        <div class="flex-grow-1 min-w-0">
                                            {{-- Erste Zeile: Codex/Nummer + Datum --}}
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="badge bg-{{ $erinnerung->farbe }}">
                                                        <i class="{{ $erinnerung->icon }} me-1"></i>
                                                        @if($erinnerung->codex)
                                                            {{ $erinnerung->codex }}
                                                        @elseif($erinnerung->typ === 'rechnung' && isset($erinnerung->rechnungsnummer))
                                                            {{ $erinnerung->rechnungsnummer }}
                                                        @else
                                                            {{ $erinnerung->typ === 'gebaeude' ? 'Gebäude' : 'Rechnung' }}
                                                        @endif
                                                    </span>
                                                    @if($erinnerung->prioritaet !== 'normal')
                                                        <span class="badge @if($erinnerung->prioritaet === 'kritisch') bg-danger @elseif($erinnerung->prioritaet === 'hoch') bg-warning text-dark @else bg-secondary @endif">
                                                            !
                                                        </span>
                                                    @endif
                                                </div>
                                                <span class="badge @if($istUeberfaellig) bg-danger @elseif($istHeute) bg-warning text-dark @else bg-secondary @endif">
                                                    {{ \Carbon\Carbon::parse($erinnerung->erinnerung_datum)->format('d.m.') }}
                                                </span>
                                            </div>
                                            
                                            {{-- Zweite Zeile: Name --}}
                                            <a href="{{ $erinnerung->link }}" class="fw-bold text-decoration-none d-block text-truncate mb-1">
                                                {{ $erinnerung->name ?? $erinnerung->titel }}
                                            </a>
                                            
                                            {{-- Dritte Zeile: Beschreibung --}}
                                            @if($erinnerung->beschreibung)
                                                <p class="mb-0 small text-muted text-truncate">
                                                    {{ Str::limit($erinnerung->beschreibung, 60) }}
                                                </p>
                                            @endif
                                        </div>
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

{{-- ⭐ Toast für Fälligkeits-Feedback --}}
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="faelligkeitToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
            <i class="bi bi-calendar-check text-success me-2"></i>
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
@media (max-width: 767.98px) {
    .card-body { padding: 0.75rem; }
    
    .fs-3 { font-size: 1.5rem !important; }
    
    .list-group-item {
        padding: 0.75rem !important;
    }
    
    .erledigt-btn {
        width: 40px !important;
        height: 40px !important;
    }
    
    /* Quick Actions auf Mobile kompakter */
    .btn-group-sm .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }
}

/* Tablet */
@media (min-width: 768px) and (max-width: 991.98px) {
    .list-group-item {
        padding: 0.75rem 1rem !important;
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

    // ⭐ Fälligkeiten aktualisieren Button
    const btnFaelligkeit = document.getElementById('btnFaelligkeitUpdate');
    if (btnFaelligkeit) {
        btnFaelligkeit.addEventListener('click', function() {
            const btn = this;
            const originalHtml = btn.innerHTML;
            
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Läuft...';
            
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
                btn.innerHTML = originalHtml;
                
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
                
                // Nach Erfolg: Statistik-Karte aktualisieren
                if (data.ok) {
                    setTimeout(() => location.reload(), 2000);
                }
            })
            .catch(error => {
                console.error('Fehler:', error);
                btn.disabled = false;
                btn.innerHTML = originalHtml;
                alert('Fehler beim Aktualisieren der Fälligkeiten');
            });
        });
    }
});
</script>
@endpush
@endsection
