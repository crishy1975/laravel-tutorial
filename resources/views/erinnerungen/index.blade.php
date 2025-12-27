{{-- resources/views/erinnerungen/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Erinnerungen')

@section('content')
<div class="container-fluid px-2 px-md-4 py-3">

    {{-- Header --}}
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-2">
        <div>
            <h4 class="mb-1">
                <i class="bi bi-bell-fill text-warning me-2"></i>
                Erinnerungen
            </h4>
            <p class="text-muted mb-0 small">
                Alle Erinnerungen aus Gebäuden und Rechnungen
            </p>
        </div>
        <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>
            <span class="d-none d-sm-inline">Zurück zum Dashboard</span>
            <span class="d-sm-none">Zurück</span>
        </a>
    </div>

    {{-- Statistik-Cards --}}
    <div class="row g-2 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100 {{ $filter === 'offen' ? 'border-start border-primary border-4' : '' }}">
                <a href="{{ route('erinnerungen.index', ['status' => 'offen', 'typ' => $typ]) }}" class="card-body p-2 p-md-3 text-decoration-none">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small">Offen</div>
                            <div class="fs-3 fw-bold text-primary">{{ $stats['offen'] }}</div>
                        </div>
                        <i class="bi bi-hourglass-split text-primary fs-4 d-none d-sm-block"></i>
                    </div>
                </a>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100 {{ $stats['ueberfaellig'] > 0 ? 'border-start border-danger border-4' : '' }}">
                <div class="card-body p-2 p-md-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small">Überfällig</div>
                            <div class="fs-3 fw-bold {{ $stats['ueberfaellig'] > 0 ? 'text-danger' : 'text-muted' }}">{{ $stats['ueberfaellig'] }}</div>
                        </div>
                        <i class="bi bi-exclamation-triangle text-danger fs-4 d-none d-sm-block"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100 {{ $stats['heute'] > 0 ? 'border-start border-warning border-4' : '' }}">
                <div class="card-body p-2 p-md-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small">Heute fällig</div>
                            <div class="fs-3 fw-bold {{ $stats['heute'] > 0 ? 'text-warning' : 'text-muted' }}">{{ $stats['heute'] }}</div>
                        </div>
                        <i class="bi bi-calendar-event text-warning fs-4 d-none d-sm-block"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100 {{ $filter === 'erledigt' ? 'border-start border-success border-4' : '' }}">
                <a href="{{ route('erinnerungen.index', ['status' => 'erledigt', 'typ' => $typ]) }}" class="card-body p-2 p-md-3 text-decoration-none">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small">Erledigt</div>
                            <div class="fs-3 fw-bold text-success">{{ $stats['erledigt'] }}</div>
                        </div>
                        <i class="bi bi-check-circle text-success fs-4 d-none d-sm-block"></i>
                    </div>
                </a>
            </div>
        </div>
    </div>

    {{-- Filter & Suche --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-2 p-md-3">
            <form method="GET" action="{{ route('erinnerungen.index') }}" class="row g-2 align-items-end">
                {{-- Status-Tabs --}}
                <div class="col-12 col-md-auto">
                    <div class="btn-group w-100" role="group">
                        <a href="{{ route('erinnerungen.index', ['status' => 'offen', 'typ' => $typ, 'suche' => $suche]) }}" 
                           class="btn {{ $filter === 'offen' ? 'btn-primary' : 'btn-outline-primary' }}">
                            <i class="bi bi-hourglass-split d-md-none"></i>
                            <span class="d-none d-md-inline">Offen</span>
                            <span class="badge bg-white text-primary ms-1">{{ $stats['offen'] }}</span>
                        </a>
                        <a href="{{ route('erinnerungen.index', ['status' => 'erledigt', 'typ' => $typ, 'suche' => $suche]) }}" 
                           class="btn {{ $filter === 'erledigt' ? 'btn-success' : 'btn-outline-success' }}">
                            <i class="bi bi-check-circle d-md-none"></i>
                            <span class="d-none d-md-inline">Erledigt</span>
                            <span class="badge bg-white text-success ms-1">{{ $stats['erledigt'] }}</span>
                        </a>
                        <a href="{{ route('erinnerungen.index', ['status' => 'alle', 'typ' => $typ, 'suche' => $suche]) }}" 
                           class="btn {{ $filter === 'alle' ? 'btn-secondary' : 'btn-outline-secondary' }}">
                            <i class="bi bi-list d-md-none"></i>
                            <span class="d-none d-md-inline">Alle</span>
                        </a>
                    </div>
                </div>

                {{-- Typ-Filter --}}
                <div class="col-6 col-md-auto">
                    <select name="typ" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Alle Typen</option>
                        <option value="gebaeude" {{ $typ === 'gebaeude' ? 'selected' : '' }}>🏢 Gebäude</option>
                        <option value="rechnung" {{ $typ === 'rechnung' ? 'selected' : '' }}>🧾 Rechnungen</option>
                    </select>
                </div>
                
                {{-- Suche --}}
                <div class="col-6 col-md">
                    <div class="input-group input-group-sm">
                        <input type="text" name="suche" class="form-control" placeholder="Suche..." value="{{ $suche }}">
                        <button type="submit" class="btn btn-outline-secondary">
                            <i class="bi bi-search"></i>
                        </button>
                        @if($suche)
                            <a href="{{ route('erinnerungen.index', ['status' => $filter, 'typ' => $typ]) }}" class="btn btn-outline-danger">
                                <i class="bi bi-x"></i>
                            </a>
                        @endif
                    </div>
                </div>
                
                <input type="hidden" name="status" value="{{ $filter }}">
            </form>
        </div>
    </div>

    {{-- Erinnerungen-Liste --}}
    <div class="card border-0 shadow-sm">
        @if($alleErinnerungen->isEmpty())
            <div class="card-body text-center py-5">
                @if($filter === 'offen')
                    <i class="bi bi-check-circle text-success" style="font-size: 3rem;"></i>
                    <h5 class="mt-3 text-muted">Alles erledigt!</h5>
                    <p class="text-muted mb-0">Keine offenen Erinnerungen. Zeit für Kaffee! ☕</p>
                @elseif($filter === 'erledigt')
                    <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                    <h5 class="mt-3 text-muted">Noch keine erledigten Erinnerungen</h5>
                    <p class="text-muted mb-0">Hier erscheinen abgehakte Erinnerungen.</p>
                @else
                    <i class="bi bi-bell-slash text-muted" style="font-size: 3rem;"></i>
                    <h5 class="mt-3 text-muted">Keine Erinnerungen gefunden</h5>
                    @if($suche)
                        <p class="text-muted mb-0">Keine Treffer für "{{ $suche }}"</p>
                    @endif
                @endif
            </div>
        @else
            <div class="list-group list-group-flush" id="erinnerungen-liste">
                @foreach($alleErinnerungen as $erinnerung)
                    @php
                        $datum = \Carbon\Carbon::parse($erinnerung->erinnerung_datum);
                        $istHeute = $datum->isToday();
                        $istUeberfaellig = $datum->isPast() && !$istHeute && !$erinnerung->erledigt;
                        $istZukunft = $datum->isFuture();
                    @endphp
                    <div class="list-group-item erinnerung-item p-2 p-md-3 
                         @if($erinnerung->erledigt) bg-light text-muted 
                         @elseif($istUeberfaellig) border-start border-danger border-3 
                         @elseif($istHeute) border-start border-warning border-3 
                         @endif" 
                         id="erinnerung-{{ $erinnerung->typ }}-{{ $erinnerung->id }}">
                         
                        <div class="d-flex align-items-start gap-2">
                            {{-- Icon --}}
                            <div class="flex-shrink-0">
                                <span class="badge bg-{{ $erinnerung->farbe }} {{ $erinnerung->erledigt ? 'opacity-50' : '' }} rounded-circle p-2">
                                    <i class="bi {{ $erinnerung->icon }}"></i>
                                </span>
                            </div>
                            
                            {{-- Content --}}
                            <div class="flex-grow-1 min-w-0">
                                <div class="d-flex justify-content-between align-items-start flex-wrap gap-1">
                                    <div class="min-w-0">
                                        <a href="{{ $erinnerung->link }}" class="text-decoration-none fw-semibold {{ $erinnerung->erledigt ? 'text-muted' : 'text-dark' }} d-block text-truncate">
                                            @if($erinnerung->erledigt)
                                                <del>{{ $erinnerung->titel }}</del>
                                            @else
                                                {{ $erinnerung->titel }}
                                            @endif
                                        </a>
                                        @if($erinnerung->codex)
                                            <small class="text-muted font-monospace">{{ $erinnerung->codex }}</small>
                                            @if($erinnerung->name)
                                                <small class="text-muted"> • {{ Str::limit($erinnerung->name, 30) }}</small>
                                            @endif
                                        @endif
                                    </div>
                                    <div class="flex-shrink-0 text-end">
                                        {{-- Datum-Badge --}}
                                        <span class="badge 
                                            @if($erinnerung->erledigt) bg-success
                                            @elseif($istUeberfaellig) bg-danger
                                            @elseif($istHeute) bg-warning text-dark
                                            @elseif($istZukunft) bg-info
                                            @else bg-light text-dark
                                            @endif">
                                            @if($erinnerung->erledigt)
                                                <i class="bi bi-check"></i>
                                            @elseif($istUeberfaellig)
                                                <i class="bi bi-exclamation-triangle me-1"></i>
                                            @endif
                                            {{ $datum->format('d.m.Y') }}
                                        </span>
                                        
                                        {{-- Relative Zeit --}}
                                        <div class="small text-muted mt-1 d-none d-md-block">
                                            @if($istHeute)
                                                Heute
                                            @elseif($datum->isYesterday())
                                                Gestern
                                            @elseif($datum->isTomorrow())
                                                Morgen
                                            @elseif($istUeberfaellig)
                                                {{ $datum->diffForHumans() }}
                                            @elseif($istZukunft)
                                                in {{ $datum->diffInDays(today()) }} Tagen
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                
                                @if($erinnerung->beschreibung)
                                    <p class="mb-0 small {{ $erinnerung->erledigt ? 'text-muted' : '' }} mt-1">
                                        {{ Str::limit($erinnerung->beschreibung, 150) }}
                                    </p>
                                @endif
                                
                                {{-- Tags --}}
                                <div class="mt-1">
                                    <span class="badge bg-{{ $erinnerung->farbe }} bg-opacity-10 text-{{ $erinnerung->farbe }} small">
                                        {{ $erinnerung->typ === 'gebaeude' ? 'Gebäude' : 'Rechnung' }}
                                    </span>
                                    @if($erinnerung->prioritaet === 'hoch')
                                        <span class="badge bg-danger bg-opacity-10 text-danger small">
                                            <i class="bi bi-flag-fill"></i> Priorität
                                        </span>
                                    @endif
                                </div>
                            </div>
                            
                            {{-- Action-Buttons --}}
                            <div class="flex-shrink-0 d-flex flex-column gap-1">
                                {{-- Erledigt/Wiederherstellen Toggle --}}
                                <button type="button" 
                                        class="btn btn-sm {{ $erinnerung->erledigt ? 'btn-outline-secondary' : 'btn-outline-success' }} toggle-btn rounded-circle d-flex align-items-center justify-content-center"
                                        style="width: 36px; height: 36px;"
                                        data-typ="{{ $erinnerung->typ }}"
                                        data-id="{{ $erinnerung->id }}"
                                        title="{{ $erinnerung->erledigt ? 'Wieder öffnen' : 'Als erledigt markieren' }}">
                                    <i class="bi {{ $erinnerung->erledigt ? 'bi-arrow-counterclockwise' : 'bi-check-lg' }}"></i>
                                </button>
                                
                                {{-- Link zum Detail --}}
                                <a href="{{ $erinnerung->link }}" 
                                   class="btn btn-sm btn-outline-primary rounded-circle d-flex align-items-center justify-content-center"
                                   style="width: 36px; height: 36px;"
                                   title="Öffnen">
                                    <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            
            {{-- Info: Anzahl --}}
            <div class="card-footer bg-light py-2 text-center small text-muted">
                {{ $alleErinnerungen->count() }} Erinnerung(en) angezeigt
                @if($alleErinnerungen->count() >= 100)
                    <span class="text-warning">(max. 100)</span>
                @endif
            </div>
        @endif
    </div>

</div>

@push('styles')
<style>
.erinnerung-item {
    transition: all 0.3s ease;
}

.erinnerung-item:hover {
    background-color: rgba(0,0,0,0.02);
}

.erinnerung-item.updating {
    opacity: 0.5;
    pointer-events: none;
}

.min-w-0 { min-width: 0; }

.font-monospace {
    font-family: SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 0.85em;
}

.toggle-btn:hover {
    transform: scale(1.1);
}

/* Mobile */
@media (max-width: 575.98px) {
    .toggle-btn, .btn-outline-primary {
        width: 32px !important;
        height: 32px !important;
    }
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle-Buttons (Erledigt/Offen)
    document.querySelectorAll('.toggle-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const typ = this.dataset.typ;
            const id = this.dataset.id;
            const item = document.getElementById(`erinnerung-${typ}-${id}`);
            const icon = this.querySelector('i');
            
            // UI Feedback
            item.classList.add('updating');
            this.disabled = true;
            
            // AJAX Request
            fetch('{{ route("erinnerungen.toggle") }}', {
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
                    // Seite neu laden um korrekten Status zu zeigen
                    location.reload();
                } else {
                    alert(data.message || 'Fehler beim Aktualisieren');
                    item.classList.remove('updating');
                    this.disabled = false;
                }
            })
            .catch(error => {
                console.error('Fehler:', error);
                item.classList.remove('updating');
                this.disabled = false;
                alert('Fehler beim Aktualisieren');
            });
        });
    });
});
</script>
@endpush
@endsection
