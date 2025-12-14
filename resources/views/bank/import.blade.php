{{-- resources/views/bank/import.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">

    {{-- Kopfzeile --}}
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2 mb-3">
        <h5 class="mb-0">
            <i class="bi bi-upload"></i> Bank-Import
        </h5>
        <a href="{{ route('bank.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> <span class="d-none d-sm-inline">Zurück</span>
        </a>
    </div>

    <div class="row">
        <div class="col-lg-6 col-md-8">
            {{-- Upload-Formular --}}
            <div class="card mb-3">
                <div class="card-header py-2">
                    <h6 class="mb-0"><i class="bi bi-file-earmark-code"></i> CBI-XML Import</h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('bank.import.store') }}" enctype="multipart/form-data">
                        @csrf
                        
                        <div class="mb-3">
                            <label for="xml_datei" class="form-label">XML-Datei auswählen</label>
                            <input type="file" 
                                   class="form-control @error('xml_datei') is-invalid @enderror" 
                                   id="xml_datei" 
                                   name="xml_datei" 
                                   accept=".xml"
                                   required>
                            @error('xml_datei')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                CBI-XML Kontoauszug (max. 10 MB)
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-upload"></i> Importieren
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Hinweise --}}
            <div class="card">
                <div class="card-header py-2">
                    <h6 class="mb-0"><i class="bi bi-info-circle"></i> Hinweise</h6>
                </div>
                <div class="card-body small">
                    <ul class="mb-0 ps-3">
                        <li>Unterstützt: CBI-XML (camt.052/053)</li>
                        <li>Duplikate werden automatisch erkannt</li>
                        <li>Nach Import: Auto-Matching wird ausgeführt</li>
                        <li>IBAN wird bei Zuordnung gespeichert</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-lg-6 col-md-4">
            {{-- Letzte Imports --}}
            <div class="card">
                <div class="card-header py-2">
                    <h6 class="mb-0"><i class="bi bi-clock-history"></i> Letzte Imports</h6>
                </div>
                @if($imports->isNotEmpty())
                    {{-- Desktop: Tabelle --}}
                    <div class="table-responsive d-none d-md-block">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Datum</th>
                                    <th>Datei</th>
                                    <th class="text-end">Neu</th>
                                    <th class="text-end">Match</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($imports as $import)
                                    <tr>
                                        <td class="text-nowrap small">{{ $import->created_at->format('d.m. H:i') }}</td>
                                        <td class="text-truncate small" style="max-width: 120px;">{{ $import->dateiname }}</td>
                                        <td class="text-end">{{ $import->anzahl_neu }}</td>
                                        <td class="text-end text-success">{{ $import->anzahl_matched }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Mobile: Cards --}}
                    <div class="list-group list-group-flush d-md-none">
                        @foreach($imports as $import)
                            <div class="list-group-item py-2">
                                <div class="d-flex justify-content-between">
                                    <small class="text-muted">{{ $import->created_at->format('d.m.Y H:i') }}</small>
                                    <div>
                                        <span class="badge bg-primary">{{ $import->anzahl_neu }} neu</span>
                                        <span class="badge bg-success">{{ $import->anzahl_matched }} match</span>
                                    </div>
                                </div>
                                <div class="small text-truncate">{{ $import->dateiname }}</div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="card-body text-muted text-center py-4">
                        <i class="bi bi-inbox fs-1"></i>
                        <p class="mb-0 mt-2">Noch keine Imports</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

</div>
@endsection
