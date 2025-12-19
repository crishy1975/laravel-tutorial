{{-- resources/views/mahnungen/historie.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container py-3">
    
    {{-- Kopfzeile --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0"><i class="bi bi-clock-history"></i> Mahnungs-Historie</h4>
            <small class="text-muted">Alle erstellten Mahnungen</small>
        </div>
        <a href="{{ route('mahnungen.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Zurück
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Filter --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-2">
                    <label class="form-label small">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Alle</option>
                        <option value="entwurf" {{ request('status') === 'entwurf' ? 'selected' : '' }}>Entwurf</option>
                        <option value="gesendet" {{ request('status') === 'gesendet' ? 'selected' : '' }}>Gesendet</option>
                        <option value="storniert" {{ request('status') === 'storniert' ? 'selected' : '' }}>Storniert</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Stufe</label>
                    <select name="stufe" class="form-select form-select-sm">
                        <option value="">Alle</option>
                        @foreach($stufen as $stufe)
                            <option value="{{ $stufe->stufe }}" {{ request('stufe') == $stufe->stufe ? 'selected' : '' }}>
                                {{ $stufe->name_de }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Von</label>
                    <input type="date" name="von" class="form-control form-control-sm" value="{{ request('von') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Bis</label>
                    <input type="date" name="bis" class="form-control form-control-sm" value="{{ request('bis') }}">
                </div>
                <div class="col-md-4 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-search"></i> Filtern
                    </button>
                    <a href="{{ route('mahnungen.historie') }}" class="btn btn-outline-secondary btn-sm">
                        Zurücksetzen
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Tabelle --}}
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Datum</th>
                        <th>Rechnung</th>
                        <th>Kunde</th>
                        <th>Stufe</th>
                        <th class="text-end">Betrag</th>
                        <th class="text-end">Spesen</th>
                        <th class="text-end">Gesamt</th>
                        <th>Versand</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($mahnungen as $mahnung)
                        <tr class="{{ $mahnung->status === 'storniert' ? 'table-secondary text-muted' : '' }}">
                            <td>{{ $mahnung->mahndatum->format('d.m.Y') }}</td>
                            <td>
                                <a href="{{ url('/rechnung/' . $mahnung->rechnung_id . '/edit') }}">
                                    {{ $mahnung->rechnungsnummer_anzeige }}
                                </a>
                            </td>
                            <td>{{ Str::limit($mahnung->rechnung?->rechnungsempfaenger?->name, 25) }}</td>
                            <td>
                                <span class="badge {{ $mahnung->stufe?->badge_class ?? 'bg-secondary' }}">
                                    <i class="bi {{ $mahnung->stufe?->icon ?? 'bi-envelope' }}"></i>
                                    {{ $mahnung->mahnstufe }}
                                </span>
                            </td>
                            <td class="text-end">{{ number_format($mahnung->rechnungsbetrag, 2, ',', '.') }} €</td>
                            <td class="text-end">{{ number_format($mahnung->spesen, 2, ',', '.') }} €</td>
                            <td class="text-end fw-bold">{{ $mahnung->gesamtbetrag_formatiert }}</td>
                            <td>
                                {!! $mahnung->versandart_badge !!}
                                @if($mahnung->email_fehler)
                                    <span class="badge bg-danger" title="{{ $mahnung->email_fehler_text }}">
                                        <i class="bi bi-exclamation-triangle"></i>
                                    </span>
                                @endif
                            </td>
                            <td>{!! $mahnung->status_badge !!}</td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                            data-bs-toggle="dropdown">
                                        <i class="bi bi-three-dots"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('mahnungen.show', $mahnung->id) }}">
                                                <i class="bi bi-info-circle"></i> Details
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('mahnungen.pdf', ['mahnung' => $mahnung->id, 'preview' => 1]) }}" target="_blank">
                                                <i class="bi bi-eye"></i> PDF Vorschau
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('mahnungen.pdf', $mahnung->id) }}">
                                                <i class="bi bi-download"></i> PDF Download
                                            </a>
                                        </li>
                                        @if($mahnung->status !== 'storniert')
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <form method="POST" action="{{ route('mahnungen.stornieren', $mahnung->id) }}">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item text-danger" 
                                                            onclick="return confirm('Mahnung wirklich stornieren?')">
                                                        <i class="bi bi-x-circle"></i> Stornieren
                                                    </button>
                                                </form>
                                            </li>
                                        @endif
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">
                                Keine Mahnungen gefunden.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($mahnungen->hasPages())
            <div class="card-footer">
                {{ $mahnungen->withQueryString()->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
