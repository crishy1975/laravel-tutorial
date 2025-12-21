{{-- resources/views/angebote/index.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">

    {{-- Kopfzeile --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0"><i class="bi bi-file-earmark-text"></i> Angebote / Offerte</h4>
            <small class="text-muted">{{ $statistik['gesamt'] }} Angebote im Jahr {{ request('jahr', now()->year) }}</small>
        </div>
        <div>
            <a href="{{ route('angebote.create') }}" class="btn btn-success">
                <i class="bi bi-plus-lg"></i> Neues Angebot
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Statistik-Karten --}}
    <div class="row g-3 mb-4">
        <div class="col-md-2">
            <div class="card text-center h-100">
                <div class="card-body">
                    <div class="fs-2 fw-bold">{{ $statistik['gesamt'] }}</div>
                    <div class="text-muted small">Gesamt</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center h-100 border-secondary">
                <div class="card-body">
                    <div class="fs-2 fw-bold text-secondary">{{ $statistik['entwurf'] }}</div>
                    <div class="text-muted small">Entwurf</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center h-100 border-primary">
                <div class="card-body">
                    <div class="fs-2 fw-bold text-primary">{{ $statistik['versendet'] }}</div>
                    <div class="text-muted small">Versendet</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center h-100 border-success">
                <div class="card-body">
                    <div class="fs-2 fw-bold text-success">{{ $statistik['angenommen'] }}</div>
                    <div class="text-muted small">Angenommen</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center h-100 border-danger">
                <div class="card-body">
                    <div class="fs-2 fw-bold text-danger">{{ $statistik['abgelehnt'] }}</div>
                    <div class="text-muted small">Abgelehnt</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="form-label small">Jahr</label>
                    <select name="jahr" class="form-select form-select-sm">
                        @foreach($jahre as $j)
                            <option value="{{ $j }}" {{ request('jahr', now()->year) == $j ? 'selected' : '' }}>
                                {{ $j }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Status</label>
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
                <div class="col-md-4">
                    <label class="form-label small">Suche</label>
                    <input type="text" name="suche" class="form-control form-control-sm" 
                           value="{{ request('suche') }}" placeholder="Nr, Titel, Kunde, Codex...">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-search"></i> Filtern
                    </button>
                    <a href="{{ route('angebote.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-x"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Tabelle --}}
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Nr.</th>
                        <th>Datum</th>
                        <th>Kunde / Cliente</th>
                        <th>Gebäude</th>
                        <th>Titel</th>
                        <th class="text-end">Netto</th>
                        <th class="text-end">Brutto</th>
                        <th>Status</th>
                        <th>Gültig bis</th>
                        <th style="width: 120px;">Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($angebote as $angebot)
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
                                            <button type="submit" class="btn btn-outline-success" title="Zu Rechnung">
                                                <i class="bi bi-arrow-right-circle"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">
                                Keine Angebote gefunden.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    @if($angebote->hasPages())
        <div class="mt-3">
            {{ $angebote->links() }}
        </div>
    @endif

</div>
@endsection
