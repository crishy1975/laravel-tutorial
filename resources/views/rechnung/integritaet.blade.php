{{-- resources/views/rechnung/integritaet.blade.php --}}
@extends('layouts.app')

@section('title', 'Rechnungs-Integritätsprüfung')

@section('content')
<div class="container-fluid py-4">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">
                <i class="bi bi-shield-check text-primary"></i> 
                Rechnungs-Integritätsprüfung
            </h1>
            <p class="text-muted mb-0">Prüft fortlaufende Nummerierung und mögliche Duplikate</p>
        </div>
        
        {{-- Jahr-Auswahl --}}
        <form class="d-flex gap-2" method="GET">
            <select name="jahr" class="form-select" style="width: auto;">
                @foreach($verfuegbareJahre as $j)
                    <option value="{{ $j }}" {{ $jahr == $j ? 'selected' : '' }}>{{ $j }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-search"></i> Prüfen
            </button>
        </form>
    </div>

    {{-- Status-Übersicht --}}
    @if($report['hat_probleme'])
        <div class="alert alert-warning d-flex align-items-center">
            <i class="bi bi-exclamation-triangle-fill me-2 fs-4"></i>
            <div>
                <strong>Es wurden Probleme gefunden!</strong>
                <br>
                <small>Bitte prüfen Sie die Details unten.</small>
            </div>
        </div>
    @else
        <div class="alert alert-success d-flex align-items-center">
            <i class="bi bi-check-circle-fill me-2 fs-4"></i>
            <div>
                <strong>Keine Probleme gefunden.</strong>
                <br>
                <small>Alle Rechnungsnummern sind fortlaufend und es wurden keine Duplikate erkannt.</small>
            </div>
        </div>
    @endif

    <div class="row">
        {{-- Lücken in Rechnungsnummern --}}
        <div class="col-lg-6 mb-4">
            <div class="card h-100 shadow-sm">
                <div class="card-header d-flex align-items-center {{ $report['luecken']['has_gaps'] ? 'bg-warning bg-opacity-25' : 'bg-success bg-opacity-25' }}">
                    @if($report['luecken']['has_gaps'])
                        <i class="bi bi-exclamation-triangle text-warning me-2 fs-5"></i>
                    @else
                        <i class="bi bi-check-circle text-success me-2 fs-5"></i>
                    @endif
                    <h5 class="mb-0">Fortlaufende Nummerierung</h5>
                </div>
                <div class="card-body">
                    @if($report['luecken']['has_gaps'])
                        <div class="alert alert-warning mb-3">
                            <i class="bi bi-exclamation-circle me-1"></i>
                            {{ $report['luecken']['message'] }}
                        </div>
                        
                        <h6 class="text-muted mb-2">Fehlende Nummern:</h6>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($report['luecken']['missing'] as $nummer)
                                <span class="badge bg-danger fs-6">
                                    {{ $jahr }}/{{ str_pad($nummer, 4, '0', STR_PAD_LEFT) }}
                                </span>
                            @endforeach
                        </div>
                        
                        <hr>
                        <p class="text-muted small mb-0">
                            <i class="bi bi-info-circle me-1"></i>
                            Fehlende Nummern können durch gelöschte Entwürfe entstehen. 
                            Bei Buchprüfungen kann dies zu Rückfragen führen.
                        </p>
                    @else
                        <div class="text-center py-4">
                            <i class="bi bi-check-circle text-success display-1 mb-3"></i>
                            <p class="text-success mb-0">{{ $report['luecken']['message'] }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Mögliche Duplikate --}}
        <div class="col-lg-6 mb-4">
            <div class="card h-100 shadow-sm">
                <div class="card-header d-flex align-items-center {{ count($report['duplikate']) > 0 ? 'bg-warning bg-opacity-25' : 'bg-success bg-opacity-25' }}">
                    @if(count($report['duplikate']) > 0)
                        <i class="bi bi-files text-warning me-2 fs-5"></i>
                    @else
                        <i class="bi bi-check-circle text-success me-2 fs-5"></i>
                    @endif
                    <h5 class="mb-0">Mögliche Duplikate</h5>
                </div>
                <div class="card-body">
                    @if(count($report['duplikate']) > 0)
                        <div class="alert alert-warning mb-3">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            Es wurden Rechnungen mit gleichem Betrag auf dasselbe Gebäude gefunden.
                        </div>
                        
                        @foreach($report['duplikate'] as $gebaeudeId => $data)
                            <div class="border rounded p-3 mb-3">
                                <h6 class="text-primary mb-2">
                                    <i class="bi bi-building me-1"></i> 
                                    {{ $data['gebaeude_name'] }}
                                </h6>
                                
                                @foreach($data['duplikate'] as $betragGroup)
                                    <div class="ms-3 mb-2 p-2 bg-light rounded">
                                        <strong class="text-danger">
                                            {{ number_format($betragGroup['betrag'], 2, ',', '.') }} €
                                        </strong>
                                        <span class="badge bg-secondary ms-2">{{ $betragGroup['anzahl'] }}x vorhanden</span>
                                        
                                        <ul class="list-unstyled ms-3 mt-2 mb-0">
                                            @foreach($betragGroup['rechnungen'] as $rechnung)
                                                <li class="mb-1">
                                                    <a href="{{ route('rechnung.edit', $rechnung['id']) }}" class="text-decoration-none">
                                                        <i class="bi bi-file-text me-1"></i>
                                                        {{ $rechnung['nummer'] }}
                                                    </a>
                                                    <small class="text-muted">
                                                        vom {{ $rechnung['datum'] }}
                                                        @if($rechnung['status'] === 'paid')
                                                            <span class="badge bg-success">Bezahlt</span>
                                                        @elseif($rechnung['status'] === 'cancelled')
                                                            <span class="badge bg-danger">Storniert</span>
                                                        @elseif($rechnung['status'] === 'sent')
                                                            <span class="badge bg-info">Versendet</span>
                                                        @else
                                                            <span class="badge bg-secondary">Entwurf</span>
                                                        @endif
                                                    </small>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                        
                        <hr>
                        <p class="text-muted small mb-0">
                            <i class="bi bi-info-circle me-1"></i>
                            Duplikate müssen nicht zwingend Fehler sein (z.B. monatliche Pauschalbeträge), 
                            sollten aber überprüft werden.
                        </p>
                    @else
                        <div class="text-center py-4">
                            <i class="bi bi-check-circle text-success display-1 mb-3"></i>
                            <p class="text-success mb-0">Keine Duplikate gefunden.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Zurück-Button --}}
    <div class="mt-3">
        <a href="{{ route('rechnung.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Zurück zur Rechnungsliste
        </a>
    </div>
</div>
@endsection
