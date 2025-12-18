{{-- resources/views/mahnungen/index.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container py-3">
    
    {{-- Kopfzeile --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0"><i class="bi bi-envelope-exclamation"></i> Mahnwesen</h4>
            <small class="text-muted">Übersicht und Mahnlauf</small>
        </div>
        <div class="btn-group">
            <a href="{{ route('mahnungen.mahnlauf') }}" class="btn btn-primary">
                <i class="bi bi-play-circle"></i> Mahnlauf starten
            </a>
            <a href="{{ route('mahnungen.historie') }}" class="btn btn-outline-secondary">
                <i class="bi bi-clock-history"></i> Historie
            </a>
            <a href="{{ route('mahnungen.stufen') }}" class="btn btn-outline-secondary">
                <i class="bi bi-sliders"></i> Stufen
            </a>
        </div>
    </div>

    {{-- Bank-Aktualitäts-Warnung --}}
    @if($bankAktualitaet['warnung'])
        <div class="alert alert-warning d-flex align-items-center">
            <i class="bi bi-exclamation-triangle-fill me-2 fs-4"></i>
            <div class="flex-grow-1">
                <strong>Achtung!</strong> {{ $bankAktualitaet['warnung_text'] }}
            </div>
            <a href="{{ route('bank.import') }}" class="btn btn-warning btn-sm">
                <i class="bi bi-upload"></i> Buchungen importieren
            </a>
        </div>
    @endif

    {{-- Statistik-Karten --}}
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card border-danger h-100">
                <div class="card-body text-center">
                    <h2 class="text-danger mb-0">{{ $statistiken['ueberfaellig_gesamt'] }}</h2>
                    <small class="text-muted">Überfällige Rechnungen</small>
                    <div class="mt-2">
                        <span class="badge bg-danger">
                            {{ number_format($statistiken['ueberfaellig_betrag'], 2, ',', '.') }} €
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card border-warning h-100">
                <div class="card-body text-center">
                    <h2 class="text-warning mb-0">{{ $statistiken['mahnungen_entwurf'] }}</h2>
                    <small class="text-muted">Entwürfe bereit</small>
                    @if($statistiken['mahnungen_entwurf'] > 0)
                        <div class="mt-2">
                            <a href="{{ route('mahnungen.versand') }}" class="btn btn-sm btn-warning">
                                Versenden
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card border-success h-100">
                <div class="card-body text-center">
                    <h2 class="text-success mb-0">{{ $statistiken['mahnungen_gesendet'] }}</h2>
                    <small class="text-muted">Mahnungen gesendet</small>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card border-secondary h-100">
                <div class="card-body text-center">
                    <h2 class="text-secondary mb-0">{{ $statistiken['ohne_email'] }}</h2>
                    <small class="text-muted">Ohne E-Mail-Adresse</small>
                    @if($statistiken['ohne_email'] > 0)
                        <div class="mt-2">
                            <span class="badge bg-secondary">
                                <i class="bi bi-mailbox"></i> Postversand nötig
                            </span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Nach Mahnstufe --}}
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-bar-chart"></i> Überfällig nach Stufe</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-column gap-2">
                        <div class="d-flex justify-content-between align-items-center p-2 bg-info bg-opacity-10 rounded">
                            <span><i class="bi bi-bell"></i> Zahlungserinnerung</span>
                            <span class="badge bg-info">{{ $statistiken['nach_stufe'][0] ?? 0 }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center p-2 bg-warning bg-opacity-10 rounded">
                            <span><i class="bi bi-exclamation-circle"></i> 1. Mahnung</span>
                            <span class="badge bg-warning text-dark">{{ $statistiken['nach_stufe'][1] ?? 0 }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center p-2 bg-orange bg-opacity-10 rounded" style="background-color: rgba(255, 165, 0, 0.1);">
                            <span><i class="bi bi-exclamation-triangle"></i> 2. Mahnung</span>
                            <span class="badge" style="background-color: #fd7e14;">{{ $statistiken['nach_stufe'][2] ?? 0 }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center p-2 bg-danger bg-opacity-10 rounded">
                            <span><i class="bi bi-exclamation-octagon"></i> Letzte Mahnung</span>
                            <span class="badge bg-danger">{{ $statistiken['nach_stufe'][3] ?? 0 }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Bank-Buchungen Status --}}
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-bank"></i> Bank-Buchungen Status</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="text-muted small">Letzter Import</label>
                        <div class="fw-bold">
                            @if($bankAktualitaet['letzter_import'])
                                {{ $bankAktualitaet['letzter_import']->format('d.m.Y H:i') }}
                                <small class="text-muted">(vor {{ $bankAktualitaet['tage_alt'] }} Tagen)</small>
                            @else
                                <span class="text-danger">Noch kein Import</span>
                            @endif
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="text-muted small">Status</label>
                        <div>
                            @if($bankAktualitaet['ist_aktuell'])
                                <span class="badge bg-success"><i class="bi bi-check-circle"></i> Aktuell</span>
                            @else
                                <span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle"></i> Veraltet</span>
                            @endif
                        </div>
                    </div>

                    <a href="{{ route('bank.import') }}" class="btn btn-outline-primary w-100">
                        <i class="bi bi-upload"></i> Neue Buchungen importieren
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Letzte Mahnungen --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="bi bi-clock-history"></i> Letzte Mahnungen</h6>
            <a href="{{ route('mahnungen.historie') }}" class="btn btn-sm btn-outline-secondary">
                Alle anzeigen
            </a>
        </div>
        <div class="card-body p-0">
            @if($letzteMahnungen->isEmpty())
                <div class="text-center text-muted py-4">
                    Noch keine Mahnungen erstellt.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Datum</th>
                                <th>Rechnung</th>
                                <th>Kunde</th>
                                <th>Stufe</th>
                                <th>Betrag</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($letzteMahnungen as $mahnung)
                                <tr>
                                    <td>{{ $mahnung->mahndatum->format('d.m.Y') }}</td>
                                    <td>
                                        <a href="{{ url('/rechnung/' . $mahnung->rechnung_id . '/edit') }}">
                                            {{ $mahnung->rechnung?->volle_rechnungsnummer ?? '-' }}
                                        </a>
                                    </td>
                                    <td>{{ Str::limit($mahnung->rechnung?->rechnungsempfaenger?->name, 25) }}</td>
                                    <td>
                                        <span class="badge {{ $mahnung->stufe?->badge_class ?? 'bg-secondary' }}">
                                            <i class="bi {{ $mahnung->stufe?->icon ?? 'bi-envelope' }}"></i>
                                            {{ $mahnung->stufe?->name_de ?? 'Stufe ' . $mahnung->mahnstufe }}
                                        </span>
                                    </td>
                                    <td>{{ $mahnung->gesamtbetrag_formatiert }}</td>
                                    <td>{!! $mahnung->status_badge !!}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

</div>
@endsection
