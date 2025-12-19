{{-- resources/views/mahnungen/index.blade.php --}}
{{-- ZWEISPRACHIG: Deutsch / Italiano --}}
@extends('layouts.app')

@section('content')
<div class="container py-3">
    
    {{-- Kopfzeile --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0">
                <i class="bi bi-envelope-exclamation"></i> 
                Mahnwesen / Gestione solleciti
            </h4>
            <small class="text-muted">Übersicht und Mahnlauf / Panoramica e gestione solleciti</small>
        </div>
        <div class="btn-group">
            <a href="{{ route('mahnungen.mahnlauf') }}" class="btn btn-primary">
                <i class="bi bi-play-circle"></i> Mahnlauf / Avvia solleciti
            </a>
            <a href="{{ route('mahnungen.historie') }}" class="btn btn-outline-secondary">
                <i class="bi bi-clock-history"></i> Historie / Storico
            </a>
            <a href="{{ route('mahnungen.stufen') }}" class="btn btn-outline-secondary">
                <i class="bi bi-sliders"></i> Stufen / Livelli
            </a>
        </div>
    </div>

    {{-- Bank-Aktualitäts-Warnung --}}
    @if($bankAktualitaet['warnung'])
        <div class="alert alert-warning d-flex align-items-center">
            <i class="bi bi-exclamation-triangle-fill me-2 fs-4"></i>
            <div class="flex-grow-1">
                <strong>Achtung! / Attenzione!</strong> {{ $bankAktualitaet['warnung_text'] }}
            </div>
            <a href="{{ route('bank.import') }}" class="btn btn-warning btn-sm">
                <i class="bi bi-upload"></i> Buchungen importieren / Importa movimenti
            </a>
        </div>
    @endif

    {{-- Statistik-Karten --}}
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card border-danger h-100">
                <div class="card-body text-center">
                    <h2 class="text-danger mb-0">{{ $statistiken['ueberfaellig_gesamt'] }}</h2>
                    <small class="text-muted">Überfällige Rechnungen<br>Fatture scadute</small>
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
                    <small class="text-muted">Entwürfe bereit<br>Bozze pronte</small>
                    @if($statistiken['mahnungen_entwurf'] > 0)
                        <div class="mt-2">
                            <a href="{{ route('mahnungen.versand') }}" class="btn btn-sm btn-warning">
                                Versenden / Inviare
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
                    <small class="text-muted">Mahnungen gesendet<br>Solleciti inviati</small>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card border-secondary h-100">
                <div class="card-body text-center">
                    <h2 class="text-secondary mb-0">{{ $statistiken['ohne_email'] }}</h2>
                    <small class="text-muted">Ohne E-Mail-Adresse<br>Senza indirizzo e-mail</small>
                    @if($statistiken['ohne_email'] > 0)
                        <div class="mt-2">
                            <span class="badge bg-secondary">
                                <i class="bi bi-mailbox"></i> Postversand / Posta
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
                    <h6 class="mb-0">
                        <i class="bi bi-bar-chart"></i> 
                        Überfällig nach Stufe / Scadute per livello
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-column gap-2">
                        <div class="d-flex justify-content-between align-items-center p-2 bg-info bg-opacity-10 rounded">
                            <span>
                                <i class="bi bi-bell"></i> 
                                Zahlungserinnerung / Promemoria
                            </span>
                            <span class="badge bg-info">{{ $statistiken['nach_stufe'][0] ?? 0 }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center p-2 bg-warning bg-opacity-10 rounded">
                            <span>
                                <i class="bi bi-exclamation-circle"></i> 
                                1. Mahnung / 1° Sollecito
                            </span>
                            <span class="badge bg-warning text-dark">{{ $statistiken['nach_stufe'][1] ?? 0 }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center p-2 bg-orange bg-opacity-10 rounded" style="background-color: rgba(255, 165, 0, 0.1);">
                            <span>
                                <i class="bi bi-exclamation-triangle"></i> 
                                2. Mahnung / 2° Sollecito
                            </span>
                            <span class="badge" style="background-color: #fd7e14;">{{ $statistiken['nach_stufe'][2] ?? 0 }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center p-2 bg-danger bg-opacity-10 rounded">
                            <span>
                                <i class="bi bi-exclamation-octagon"></i> 
                                Letzte Mahnung / Ultimo sollecito
                            </span>
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
                    <h6 class="mb-0">
                        <i class="bi bi-bank"></i> 
                        Bank-Buchungen Status / Stato movimenti bancari
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="text-muted small">Letzter Import / Ultimo import</label>
                        <div class="fw-bold">
                            @if($bankAktualitaet['letzter_import'])
                                {{ $bankAktualitaet['letzter_import']->format('d.m.Y H:i') }}
                                <small class="text-muted">(vor {{ $bankAktualitaet['tage_alt'] }} Tagen / {{ $bankAktualitaet['tage_alt'] }} giorni fa)</small>
                            @else
                                <span class="text-danger">Noch kein Import / Nessun import</span>
                            @endif
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="text-muted small">Status / Stato</label>
                        <div>
                            @if($bankAktualitaet['ist_aktuell'])
                                <span class="badge bg-success"><i class="bi bi-check-circle"></i> Aktuell / Aggiornato</span>
                            @else
                                <span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle"></i> Veraltet / Obsoleto</span>
                            @endif
                        </div>
                    </div>

                    <a href="{{ route('bank.import') }}" class="btn btn-outline-primary w-100">
                        <i class="bi bi-upload"></i> Neue Buchungen importieren / Importa nuovi movimenti
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Letzte Mahnungen --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">
                <i class="bi bi-clock-history"></i> 
                Letzte Mahnungen / Ultimi solleciti
            </h6>
            <a href="{{ route('mahnungen.historie') }}" class="btn btn-sm btn-outline-secondary">
                Alle anzeigen / Mostra tutti
            </a>
        </div>
        <div class="card-body p-0">
            @if($letzteMahnungen->isEmpty())
                <div class="text-center text-muted py-4">
                    Noch keine Mahnungen erstellt. / Nessun sollecito creato.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Datum / Data</th>
                                <th>Rechnung / Fattura</th>
                                <th>Kunde / Cliente</th>
                                <th>E-Mail</th>
                                <th>Stufe / Livello</th>
                                <th>Betrag / Importo</th>
                                <th>Status / Stato</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($letzteMahnungen as $mahnung)
                                @php
                                    // E-Mail-Priorität: Postadresse → Rechnungsempfänger
                                    $postEmail = $mahnung->rechnung?->gebaeude?->postadresse?->email;
                                    $rechnungEmail = $mahnung->rechnung?->rechnungsempfaenger?->email;
                                    $emailAdresse = $postEmail ?: $rechnungEmail;
                                @endphp
                                <tr>
                                    <td>
                                        {{-- Datum klickbar → Show --}}
                                        <a href="{{ route('mahnungen.show', $mahnung->id) }}" class="text-decoration-none">
                                            {{ $mahnung->mahndatum->format('d.m.Y') }}
                                        </a>
                                    </td>
                                    <td>
                                        <a href="{{ route('mahnungen.show', $mahnung->id) }}">
                                            {{ $mahnung->rechnungsnummer_anzeige }}
                                        </a>
                                    </td>
                                    <td>{{ Str::limit($mahnung->rechnung?->rechnungsempfaenger?->name, 20) }}</td>
                                    <td>
                                        @if($emailAdresse)
                                            <small>{{ Str::limit($emailAdresse, 20) }}</small>
                                            @if($postEmail)
                                                <span class="badge bg-info text-dark" title="Postadresse">P</span>
                                            @endif
                                        @else
                                            <span class="text-warning" title="Postversand"><i class="bi bi-mailbox"></i></span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge {{ $mahnung->stufe?->badge_class ?? 'bg-secondary' }}">
                                            {{ $mahnung->mahnstufe }}
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
