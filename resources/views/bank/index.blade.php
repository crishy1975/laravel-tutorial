{{-- resources/views/bank/index.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container-fluid py-3" id="bankIndexContainer">

    {{-- Kopfzeile --}}
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2 mb-3">
        <h4 class="mb-0">
            <i class="bi bi-bank"></i> Bank-Buchungen
        </h4>
        <div class="btn-group">
            <a href="{{ route('bank.import') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-upload"></i> <span class="d-none d-sm-inline">Import</span>
            </a>
            <a href="{{ route('bank.matched') }}" class="btn btn-success btn-sm">
                <i class="bi bi-check2-all"></i> <span class="d-none d-sm-inline">Zugeordnet</span>
            </a>
            <a href="{{ route('bank.config') }}" class="btn btn-outline-secondary btn-sm" title="Konfiguration">
                <i class="bi bi-sliders"></i>
            </a>
        </div>
    </div>

    {{-- Statistik-Cards --}}
    <div class="row g-2 mb-3">
        <div class="col-6 col-md-3 col-lg">
            <div class="card text-center h-100">
                <div class="card-body py-2 px-2">
                    <div class="text-muted small">Gesamt</div>
                    <div class="fs-5 fw-bold">{{ number_format($stats['gesamt']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 col-lg">
            <div class="card text-center border-warning h-100">
                <div class="card-body py-2 px-2">
                    <div class="text-muted small">Offen</div>
                    <div class="fs-5 fw-bold text-warning">{{ number_format($stats['unmatched']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 col-lg">
            <div class="card text-center border-success h-100">
                <div class="card-body py-2 px-2">
                    <div class="text-muted small">Zugeordnet</div>
                    <div class="fs-5 fw-bold text-success">{{ number_format($stats['matched']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 col-lg">
            <div class="card text-center h-100">
                <div class="card-body py-2 px-2">
                    <div class="text-muted small">Eingänge</div>
                    <div class="fs-6 fw-bold text-success">+{{ number_format($stats['eingaenge'], 0, ',', '.') }}€</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter (Collapsible auf Mobile) --}}
    <div class="card mb-3">
        <div class="card-header py-2 d-flex justify-content-between align-items-center" 
             data-bs-toggle="collapse" data-bs-target="#filterCollapse" role="button">
            <span><i class="bi bi-funnel"></i> Filter</span>
            <div>
                <button type="button" class="btn btn-outline-secondary btn-sm me-1" onclick="resetFilter()" title="Filter zurücksetzen">
                    <i class="bi bi-x-circle"></i>
                </button>
                <i class="bi bi-chevron-down d-md-none"></i>
            </div>
        </div>
        <div class="collapse show" id="filterCollapse">
            <div class="card-body py-2">
                <form method="GET" action="{{ route('bank.index') }}" id="bankFilterForm">
                    <div class="row g-2">
                        <div class="col-6 col-md-2">
                            <select name="typ" class="form-select form-select-sm">
                                <option value="">Alle Typen</option>
                                <option value="CRDT" {{ ($filter['typ'] ?? '') === 'CRDT' ? 'selected' : '' }}>Eingänge</option>
                                <option value="DBIT" {{ ($filter['typ'] ?? '') === 'DBIT' ? 'selected' : '' }}>Ausgänge</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-2">
                            <select name="status" class="form-select form-select-sm">
                                <option value="">Alle Status</option>
                                <option value="unmatched" {{ ($filter['status'] ?? '') === 'unmatched' ? 'selected' : '' }}>Offen</option>
                                <option value="matched" {{ ($filter['status'] ?? '') === 'matched' ? 'selected' : '' }}>Zugeordnet</option>
                                <option value="manual" {{ ($filter['status'] ?? '') === 'manual' ? 'selected' : '' }}>Manuell</option>
                                <option value="ignored" {{ ($filter['status'] ?? '') === 'ignored' ? 'selected' : '' }}>Ignoriert</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-2">
                            <input type="date" name="von" class="form-control form-control-sm" 
                                   placeholder="Von" value="{{ $filter['von'] ?? '' }}">
                        </div>
                        <div class="col-6 col-md-2">
                            <input type="date" name="bis" class="form-control form-control-sm" 
                                   placeholder="Bis" value="{{ $filter['bis'] ?? '' }}">
                        </div>
                        <div class="col-12 col-md-3">
                            <input type="text" name="suche" class="form-control form-control-sm" 
                                   placeholder="Suche..." value="{{ $filter['suche'] ?? '' }}">
                        </div>
                        <div class="col-12 col-md-1">
                            <button type="submit" class="btn btn-primary btn-sm w-100">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if($buchungen->isEmpty())
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> Keine Buchungen gefunden.
        </div>
    @else
        {{-- Desktop: Tabelle --}}
        <div class="card d-none d-md-block">
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0 align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Datum</th>
                            <th>Typ</th>
                            <th>Gegenkonto</th>
                            <th>Verwendungszweck</th>
                            <th class="text-end">Betrag</th>
                            <th>Status</th>
                            <th>Rechnung</th>
                            <th style="width: 50px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($buchungen as $buchung)
                            <tr>
                                <td class="text-nowrap">{{ $buchung->buchungsdatum->format('d.m.Y') }}</td>
                                <td>{!! $buchung->typ_badge !!}</td>
                                <td>
                                    <div class="text-truncate" style="max-width: 150px;">
                                        {{ $buchung->gegenkonto_name ?: '–' }}
                                    </div>
                                </td>
                                <td>
                                    <div class="text-truncate" style="max-width: 200px;" 
                                         title="{{ $buchung->verwendungszweck }}">
                                        {{ Str::limit($buchung->verwendungszweck, 40) }}
                                    </div>
                                </td>
                                <td class="text-end text-nowrap fw-bold {{ $buchung->typ === 'CRDT' ? 'text-success' : 'text-danger' }}">
                                    {{ $buchung->betrag_format }}
                                </td>
                                <td>{!! $buchung->match_status_badge !!}</td>
                                <td>
                                    @if($buchung->rechnung)
                                        <a href="{{ route('rechnung.edit', $buchung->rechnung_id) }}" class="small">
                                            {{ $buchung->rechnung->rechnungsnummer }}
                                        </a>
                                    @else
                                        <span class="text-muted">–</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('bank.show', $buchung->id) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                {{ $buchungen->withQueryString()->links() }}
            </div>
        </div>

        {{-- Mobile: Cards --}}
        <div class="d-md-none">
            @foreach($buchungen as $buchung)
                <div class="card mb-2">
                    <div class="card-body py-2 px-3">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <div>
                                <span class="fw-bold {{ $buchung->typ === 'CRDT' ? 'text-success' : 'text-danger' }} fs-5">
                                    {{ $buchung->betrag_format }}
                                </span>
                                {!! $buchung->typ_badge !!}
                            </div>
                            <a href="{{ route('bank.show', $buchung->id) }}" class="btn btn-sm btn-primary">
                                <i class="bi bi-eye"></i>
                            </a>
                        </div>
                        <div class="small text-muted mb-1">
                            <i class="bi bi-calendar"></i> {{ $buchung->buchungsdatum->format('d.m.Y') }}
                            {!! $buchung->match_status_badge !!}
                        </div>
                        <div class="small fw-medium mb-1">
                            {{ Str::limit($buchung->gegenkonto_name, 35) ?: '–' }}
                        </div>
                        <div class="small text-muted text-truncate">
                            {{ Str::limit($buchung->verwendungszweck, 60) }}
                        </div>
                        @if($buchung->rechnung)
                            <div class="mt-1">
                                <a href="{{ route('rechnung.edit', $buchung->rechnung_id) }}" class="badge bg-success text-decoration-none">
                                    <i class="bi bi-receipt"></i> {{ $buchung->rechnung->rechnungsnummer }}
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach

            <div class="mt-3">
                {{ $buchungen->withQueryString()->links() }}
            </div>
        </div>
    @endif

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const STORAGE_KEY_FILTER = 'bank_index_filter';
    const STORAGE_KEY_SCROLL = 'bank_index_scroll';
    const form = document.getElementById('bankFilterForm');
    
    // ═══════════════════════════════════════════════════════════════════════
    // SCROLL-POSITION WIEDERHERSTELLEN
    // ═══════════════════════════════════════════════════════════════════════
    
    const savedScroll = sessionStorage.getItem(STORAGE_KEY_SCROLL);
    if (savedScroll) {
        // Kurze Verzögerung damit die Seite fertig gerendert ist
        setTimeout(() => {
            window.scrollTo(0, parseInt(savedScroll, 10));
        }, 100);
        // Nach Wiederherstellung löschen (nur einmalig)
        sessionStorage.removeItem(STORAGE_KEY_SCROLL);
    }
    
    // ═══════════════════════════════════════════════════════════════════════
    // SCROLL-POSITION SPEICHERN BEI KLICK AUF DETAILS
    // ═══════════════════════════════════════════════════════════════════════
    
    document.querySelectorAll('a[href*="/bank/"]').forEach(link => {
        // Nur Detail-Links (nicht Index selbst)
        if (link.href.match(/\/bank\/\d+$/)) {
            link.addEventListener('click', () => {
                sessionStorage.setItem(STORAGE_KEY_SCROLL, window.scrollY);
            });
        }
    });
    
    // ═══════════════════════════════════════════════════════════════════════
    // FILTER SPEICHERN
    // ═══════════════════════════════════════════════════════════════════════
    
    // Aktuellen Filter aus URL speichern
    const currentParams = new URLSearchParams(window.location.search);
    if (currentParams.toString()) {
        localStorage.setItem(STORAGE_KEY_FILTER, currentParams.toString());
    }
    
    // ═══════════════════════════════════════════════════════════════════════
    // FILTER WIEDERHERSTELLEN (nur wenn keine Parameter in URL)
    // ═══════════════════════════════════════════════════════════════════════
    
    if (!window.location.search && localStorage.getItem(STORAGE_KEY_FILTER)) {
        const savedFilter = localStorage.getItem(STORAGE_KEY_FILTER);
        // Nur wiederherstellen wenn nicht leer
        if (savedFilter && savedFilter !== '') {
            // Redirect mit gespeicherten Filtern
            window.location.search = savedFilter;
        }
    }
});

// ═══════════════════════════════════════════════════════════════════════
// FILTER ZURÜCKSETZEN
// ═══════════════════════════════════════════════════════════════════════

function resetFilter() {
    localStorage.removeItem('bank_index_filter');
    window.location.href = '{{ route("bank.index") }}';
}
</script>
@endsection
