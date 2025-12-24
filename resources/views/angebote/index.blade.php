{{-- resources/views/angebote/index.blade.php --}}
{{-- MOBIL-OPTIMIERT: Cards auf Mobile, Tabelle auf Desktop --}}

@extends('layouts.app')

@section('content')
<div class="container-fluid px-2 px-md-4 py-3">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0">
                <i class="bi bi-file-earmark-text text-primary"></i>
                <span class="d-none d-sm-inline">Angebote / Offerte</span>
                <span class="d-sm-none">Angebote</span>
            </h4>
            <small class="text-muted d-none d-sm-inline">
                {{ $statistik['gesamt'] }} Angebote in {{ request('jahr', now()->year) }}
            </small>
        </div>
        <a href="{{ route('angebote.create') }}" class="btn btn-success">
            <i class="bi bi-plus-lg"></i>
            <span class="d-none d-sm-inline ms-1">Neues Angebot</span>
        </a>
    </div>

    {{-- Alerts --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show py-2" role="alert">
            <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show py-2" role="alert">
            <i class="bi bi-exclamation-triangle me-1"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Statistik-Karten - horizontal scrollbar auf Mobile --}}
    <div class="d-flex gap-2 mb-3 overflow-auto pb-2">
        <div class="card flex-shrink-0" style="min-width: 80px;">
            <div class="card-body text-center py-2 px-3">
                <div class="fs-4 fw-bold">{{ $statistik['gesamt'] }}</div>
                <div class="text-muted small">Gesamt</div>
            </div>
        </div>
        <div class="card flex-shrink-0 border-secondary" style="min-width: 80px;">
            <div class="card-body text-center py-2 px-3">
                <div class="fs-4 fw-bold text-secondary">{{ $statistik['entwurf'] }}</div>
                <div class="text-muted small">Entwurf</div>
            </div>
        </div>
        <div class="card flex-shrink-0 border-primary" style="min-width: 80px;">
            <div class="card-body text-center py-2 px-3">
                <div class="fs-4 fw-bold text-primary">{{ $statistik['versendet'] }}</div>
                <div class="text-muted small">Versendet</div>
            </div>
        </div>
        <div class="card flex-shrink-0 border-success" style="min-width: 80px;">
            <div class="card-body text-center py-2 px-3">
                <div class="fs-4 fw-bold text-success">{{ $statistik['angenommen'] }}</div>
                <div class="text-muted small">Angenommen</div>
            </div>
        </div>
        <div class="card flex-shrink-0 border-danger" style="min-width: 80px;">
            <div class="card-body text-center py-2 px-3">
                <div class="fs-4 fw-bold text-danger">{{ $statistik['abgelehnt'] }}</div>
                <div class="text-muted small">Abgelehnt</div>
            </div>
        </div>
    </div>

    {{-- Filter --}}
    <div class="card mb-3">
        <div class="card-body p-2 p-md-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Jahr</label>
                    <select name="jahr" class="form-select form-select-sm">
                        @foreach($jahre as $j)
                            <option value="{{ $j }}" {{ request('jahr', now()->year) == $j ? 'selected' : '' }}>
                                {{ $j }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Alle</option>
                        <option value="entwurf" {{ request('status') === 'entwurf' ? 'selected' : '' }}>Entwurf</option>
                        <option value="versendet" {{ request('status') === 'versendet' ? 'selected' : '' }}>Versendet</option>
                        <option value="angenommen" {{ request('status') === 'angenommen' ? 'selected' : '' }}>Angenommen</option>
                        <option value="abgelehnt" {{ request('status') === 'abgelehnt' ? 'selected' : '' }}>Abgelehnt</option>
                        <option value="abgelaufen" {{ request('status') === 'abgelaufen' ? 'selected' : '' }}>Abgelaufen</option>
                        <option value="rechnung" {{ request('status') === 'rechnung' ? 'selected' : '' }}>→ Rechnung</option>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label small mb-1 d-none d-md-block">Suche</label>
                    <input type="text" name="suche" class="form-control form-control-sm" 
                           value="{{ request('suche') }}" placeholder="Nr, Titel, Kunde...">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-search"></i>
                    </button>
                    @if(request()->hasAny(['suche', 'status', 'jahr']))
                    <a href="{{ route('angebote.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-x-lg"></i>
                    </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    @if($angebote->isEmpty())
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-1"></i>Keine Angebote gefunden.
        </div>
    @else

    {{-- MOBILE: Card-Layout --}}
    <div class="d-md-none">
        @foreach($angebote as $angebot)
        <div class="card mb-2 shadow-sm {{ $angebot->ist_abgelaufen ? 'border-warning' : '' }}">
            <div class="card-body p-3">
                {{-- Header-Zeile --}}
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <a href="{{ route('angebote.edit', $angebot) }}" class="fw-bold text-decoration-none">
                            {{ $angebot->angebotsnummer }}
                        </a>
                        <div class="small text-muted">{{ $angebot->datum->format('d.m.Y') }}</div>
                    </div>
                    <div class="text-end">
                        {!! $angebot->status_badge !!}
                        <div class="fw-bold mt-1">{{ $angebot->brutto_formatiert }}</div>
                    </div>
                </div>
                
                {{-- Kunde & Titel --}}
                <div class="mb-2">
                    <div class="fw-semibold">{{ Str::limit($angebot->empfaenger_name, 35) }}</div>
                    @if($angebot->titel)
                    <div class="small text-muted">{{ Str::limit($angebot->titel, 40) }}</div>
                    @endif
                </div>

                {{-- Gebaeude --}}
                @if($angebot->geb_codex || $angebot->geb_name)
                <div class="small text-muted mb-2">
                    @if($angebot->geb_codex)
                        <span class="badge bg-light text-dark">{{ $angebot->geb_codex }}</span>
                    @endif
                    {{ Str::limit($angebot->geb_name, 25) }}
                </div>
                @endif

                {{-- Gueltig bis Warnung --}}
                @if($angebot->gueltig_bis && $angebot->gueltig_bis->isPast())
                <div class="small text-danger mb-2">
                    <i class="bi bi-exclamation-triangle"></i> Abgelaufen: {{ $angebot->gueltig_bis->format('d.m.Y') }}
                </div>
                @endif

                {{-- Aktionen --}}
                <div class="d-flex gap-2 pt-2 border-top">
                    <a href="{{ route('angebote.edit', $angebot) }}" class="btn btn-sm btn-outline-primary flex-fill">
                        <i class="bi bi-pencil"></i> Bearbeiten
                    </a>
                    <a href="{{ route('angebote.pdf', ['angebot' => $angebot, 'preview' => 1]) }}" 
                       class="btn btn-sm btn-outline-secondary" target="_blank">
                        <i class="bi bi-file-pdf"></i>
                    </a>
                    @if(!$angebot->rechnung_id && $angebot->status === 'angenommen')
                    <form method="POST" action="{{ route('angebote.zu-rechnung', $angebot) }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-success" 
                                onclick="return confirm('Zu Rechnung umwandeln?')">
                            <i class="bi bi-arrow-right-circle"></i>
                        </button>
                    </form>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- DESKTOP: Tabelle --}}
    <div class="d-none d-md-block">
        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Nr.</th>
                            <th>Datum</th>
                            <th>Kunde</th>
                            <th>Gebaeude</th>
                            <th>Titel</th>
                            <th class="text-end">Netto</th>
                            <th class="text-end">Brutto</th>
                            <th>Status</th>
                            <th>Gueltig bis</th>
                            <th style="width: 120px;">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($angebote as $angebot)
                        <tr class="{{ $angebot->ist_abgelaufen ? 'table-warning' : '' }}">
                            <td>
                                <a href="{{ route('angebote.edit', $angebot) }}" class="fw-bold text-decoration-none">
                                    {{ $angebot->angebotsnummer }}
                                </a>
                            </td>
                            <td>{{ $angebot->datum->format('d.m.Y') }}</td>
                            <td>
                                {{ Str::limit($angebot->empfaenger_name, 30) }}
                                @if($angebot->empfaenger_email)
                                    <br><small class="text-muted">{{ $angebot->empfaenger_email }}</small>
                                @endif
                            </td>
                            <td>
                                @if($angebot->geb_codex)
                                    <span class="badge bg-light text-dark">{{ $angebot->geb_codex }}</span>
                                @endif
                                {{ Str::limit($angebot->geb_name, 20) }}
                            </td>
                            <td>{{ Str::limit($angebot->titel, 40) }}</td>
                            <td class="text-end">{{ $angebot->netto_formatiert }}</td>
                            <td class="text-end fw-bold">{{ $angebot->brutto_formatiert }}</td>
                            <td>{!! $angebot->status_badge !!}</td>
                            <td>
                                @if($angebot->gueltig_bis)
                                    <span class="{{ $angebot->gueltig_bis->isPast() ? 'text-danger' : '' }}">
                                        {{ $angebot->gueltig_bis->format('d.m.Y') }}
                                    </span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('angebote.edit', $angebot) }}" 
                                       class="btn btn-outline-primary" title="Bearbeiten">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="{{ route('angebote.pdf', ['angebot' => $angebot, 'preview' => 1]) }}" 
                                       class="btn btn-outline-secondary" title="PDF" target="_blank">
                                        <i class="bi bi-file-pdf"></i>
                                    </a>
                                    @if(!$angebot->rechnung_id && $angebot->status === 'angenommen')
                                    <form method="POST" action="{{ route('angebote.zu-rechnung', $angebot) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-success" title="Zu Rechnung"
                                                onclick="return confirm('Zu Rechnung umwandeln?')">
                                            <i class="bi bi-arrow-right-circle"></i>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Pagination --}}
    @if($angebote->hasPages())
        <div class="mt-3">
            {{ $angebote->links() }}
        </div>
    @endif

    @endif
</div>

@push('styles')
<style>
@media (max-width: 767.98px) {
    .form-control, .form-select, .btn { min-height: 44px; font-size: 16px !important; }
}
</style>
@endpush
@endsection
