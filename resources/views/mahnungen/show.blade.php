{{-- resources/views/mahnungen/show.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container py-3">
    
    {{-- Kopfzeile --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0">
                <i class="bi {{ $mahnung->stufe?->icon ?? 'bi-envelope' }}"></i>
                Mahnung #{{ $mahnung->id }}
            </h4>
            <small class="text-muted">
                {{ $mahnung->stufe?->name_de ?? 'Stufe ' . $mahnung->mahnstufe }} 
                - Rechnung {{ $mahnung->rechnung?->volle_rechnungsnummer }}
            </small>
        </div>
        <div class="btn-group">
            <a href="{{ route('mahnungen.historie') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Zurück
            </a>
            <a href="{{ route('mahnungen.pdf', [$mahnung->id, 'de']) }}" class="btn btn-outline-primary">
                <i class="bi bi-file-pdf"></i> PDF (DE)
            </a>
            <a href="{{ route('mahnungen.pdf', [$mahnung->id, 'it']) }}" class="btn btn-outline-primary">
                <i class="bi bi-file-pdf"></i> PDF (IT)
            </a>
        </div>
    </div>

    <div class="row">
        {{-- Linke Spalte: Info --}}
        <div class="col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="mb-0">Mahnung-Details</h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless">
                        <tr>
                            <th class="text-muted">Status</th>
                            <td>{!! $mahnung->status_badge !!}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Mahndatum</th>
                            <td>{{ $mahnung->mahndatum->format('d.m.Y') }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Stufe</th>
                            <td>
                                <span class="badge {{ $mahnung->stufe?->badge_class ?? 'bg-secondary' }}">
                                    {{ $mahnung->stufe?->name_de ?? 'Stufe ' . $mahnung->mahnstufe }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th class="text-muted">Tage überfällig</th>
                            <td><span class="badge bg-danger">{{ $mahnung->tage_ueberfaellig }} Tage</span></td>
                        </tr>
                        <tr><td colspan="2"><hr class="my-2"></td></tr>
                        <tr>
                            <th class="text-muted">Rechnungsbetrag</th>
                            <td>{{ number_format($mahnung->rechnungsbetrag, 2, ',', '.') }} €</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Mahnspesen</th>
                            <td>{{ $mahnung->spesen_formatiert }}</td>
                        </tr>
                        <tr class="fw-bold">
                            <th>Gesamtbetrag</th>
                            <td>{{ $mahnung->gesamtbetrag_formatiert }}</td>
                        </tr>
                        <tr><td colspan="2"><hr class="my-2"></td></tr>
                        <tr>
                            <th class="text-muted">Versandart</th>
                            <td>{!! $mahnung->versandart_badge !!}</td>
                        </tr>
                        @if($mahnung->email_gesendet_am)
                            <tr>
                                <th class="text-muted">Gesendet am</th>
                                <td>{{ $mahnung->email_gesendet_am->format('d.m.Y H:i') }}</td>
                            </tr>
                            <tr>
                                <th class="text-muted">An</th>
                                <td><small>{{ $mahnung->email_adresse }}</small></td>
                            </tr>
                        @endif
                        @if($mahnung->email_fehler)
                            <tr>
                                <th class="text-muted">Fehler</th>
                                <td><span class="text-danger">{{ $mahnung->email_fehler_text }}</span></td>
                            </tr>
                        @endif
                    </table>
                </div>
            </div>
        </div>

        {{-- Rechte Spalte: Kunde & Rechnung --}}
        <div class="col-lg-8 mb-4">
            {{-- Kunde --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-person"></i> Kunde</h6>
                </div>
                <div class="card-body">
                    @php $empfaenger = $mahnung->rechnung?->rechnungsempfaenger; @endphp
                    <div class="row">
                        <div class="col-md-6">
                            <strong>{{ $empfaenger?->name }}</strong><br>
                            {{ $empfaenger?->strasse }} {{ $empfaenger?->hausnummer }}<br>
                            {{ $empfaenger?->plz }} {{ $empfaenger?->wohnort }}
                        </div>
                        <div class="col-md-6">
                            @if($empfaenger?->email)
                                <i class="bi bi-envelope"></i> {{ $empfaenger->email }}<br>
                            @else
                                <span class="text-warning"><i class="bi bi-exclamation-triangle"></i> Keine E-Mail</span><br>
                            @endif
                            @if($empfaenger?->telefon)
                                <i class="bi bi-telephone"></i> {{ $empfaenger->telefon }}
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Rechnung --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-file-text"></i> Rechnung</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Rechnungsnummer:</strong> 
                            <a href="{{ url('/rechnung/' . $mahnung->rechnung_id . '/edit') }}">
                                {{ $mahnung->rechnung?->volle_rechnungsnummer }}
                            </a><br>
                            <strong>Rechnungsdatum:</strong> {{ $mahnung->rechnung?->rechnungsdatum?->format('d.m.Y') }}<br>
                            <strong>Fälligkeit:</strong> {{ $mahnung->rechnung?->rechnungsdatum?->addDays(30)->format('d.m.Y') }}
                        </div>
                        <div class="col-md-6">
                            <strong>Gebäude:</strong> {{ $mahnung->rechnung?->gebaeude?->gebaeude_name ?? '-' }}<br>
                            <strong>Status:</strong> {!! $mahnung->rechnung?->status_badge ?? '-' !!}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Mahntext --}}
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-card-text"></i> Mahntext</h6>
                </div>
                <div class="card-body">
                    <ul class="nav nav-tabs" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#previewDe" type="button">
                                🇩🇪 Deutsch
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#previewIt" type="button">
                                🇮🇹 Italiano
                            </button>
                        </li>
                    </ul>
                    <div class="tab-content pt-3">
                        <div class="tab-pane fade show active" id="previewDe">
                            <div class="bg-light p-3 rounded" style="white-space: pre-wrap; font-family: monospace; font-size: 0.85rem;">{{ $textDe }}</div>
                        </div>
                        <div class="tab-pane fade" id="previewIt">
                            <div class="bg-light p-3 rounded" style="white-space: pre-wrap; font-family: monospace; font-size: 0.85rem;">{{ $textIt }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Aktionen --}}
    @if($mahnung->status !== 'storniert')
        <div class="card">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    @if($mahnung->status === 'entwurf')
                        <span class="text-muted">Diese Mahnung wurde noch nicht versendet.</span>
                    @else
                        <span class="text-success"><i class="bi bi-check-circle"></i> Versendet am {{ $mahnung->email_gesendet_am?->format('d.m.Y H:i') ?? $mahnung->updated_at->format('d.m.Y H:i') }}</span>
                    @endif
                </div>
                <form method="POST" action="{{ route('mahnungen.stornieren', $mahnung->id) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger" 
                            onclick="return confirm('Mahnung wirklich stornieren?')">
                        <i class="bi bi-x-circle"></i> Stornieren
                    </button>
                </form>
            </div>
        </div>
    @endif

</div>
@endsection
