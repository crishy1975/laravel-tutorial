@extends('layouts.app', ['title' => 'Globale Preis-Aufschläge - UschiWeb'])

@section('content')
<div class="container-fluid py-4">
    {{-- Header --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-1">
                        <i class="bi bi-percent"></i>
                        Globale Preis-Aufschläge
                    </h1>
                    <p class="text-muted mb-0">
                        Standard-Aufschlag für alle Gebäude (kann pro Gebäude überschrieben werden)
                    </p>
                </div>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNeuerAufschlag">
                    <i class="bi bi-plus-circle"></i>
                    Neuer Aufschlag
                </button>
            </div>
        </div>
    </div>

    {{-- Success/Error Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Info Card --}}
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card border-info">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-info-circle text-info"></i>
                        Wie funktionieren Aufschläge?
                    </h5>
                    <ul class="mb-0">
                        <li>Der <strong>globale Aufschlag</strong> gilt automatisch für alle Gebäude</li>
                        <li>Beim Erstellen einer Rechnung werden die Artikel-Preise direkt angepasst</li>
                        <li>Einzelne Gebäude können einen <strong>individuellen Aufschlag</strong> erhalten (z.B. 0% für Langzeitverträge)</li>
                        <li>Alte Rechnungen bleiben unverändert - nur neue Rechnungen nutzen den aktuellen Aufschlag</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    {{-- Aufschläge Tabelle --}}
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="bi bi-table"></i>
                        Aufschläge nach Jahr
                    </h5>
                </div>
                <div class="card-body">
                    @if($aufschlaege->isEmpty())
                        <div class="text-center py-5">
                            <i class="bi bi-inbox display-1 text-muted"></i>
                            <p class="text-muted mt-3">Noch keine Aufschläge definiert</p>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNeuerAufschlag">
                                Ersten Aufschlag erstellen
                            </button>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Jahr</th>
                                        <th>Aufschlag</th>
                                        <th>Beschreibung</th>
                                        <th>Erstellt</th>
                                        <th>Aktualisiert</th>
                                        <th class="text-end">Aktionen</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($aufschlaege as $aufschlag)
                                        <tr class="{{ $aufschlag->jahr == now()->year ? 'table-info' : '' }}">
                                            <td>
                                                <strong>{{ $aufschlag->jahr }}</strong>
                                                @if($aufschlag->jahr == now()->year)
                                                    <span class="badge bg-info ms-2">Aktuell</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge {{ $aufschlag->prozent > 0 ? 'bg-success' : ($aufschlag->prozent < 0 ? 'bg-danger' : 'bg-secondary') }} fs-6">
                                                    {{ $aufschlag->prozent > 0 ? '+' : '' }}{{ number_format($aufschlag->prozent, 2, ',', '.') }}%
                                                </span>
                                            </td>
                                            <td>{{ $aufschlag->beschreibung ?? '-' }}</td>
                                            <td>
                                                <small class="text-muted">
                                                    {{ $aufschlag->created_at->format('d.m.Y H:i') }}
                                                </small>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    {{ $aufschlag->updated_at->format('d.m.Y H:i') }}
                                                </small>
                                            </td>
                                            <td class="text-end">
                                                <div class="btn-group btn-group-sm" role="group">
                                                    {{-- Bearbeiten --}}
                                                    <button type="button" 
                                                            class="btn btn-outline-primary"
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#modalBearbeiten{{ $aufschlag->id }}"
                                                            title="Bearbeiten">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    
                                                    {{-- Löschen --}}
                                                    <button type="button" 
                                                            class="btn btn-outline-danger"
                                                            onclick="confirmDelete({{ $aufschlag->id }}, '{{ $aufschlag->jahr }}')"
                                                            title="Löschen">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>

                                        {{-- Modal: Bearbeiten --}}
                                        <div class="modal fade" id="modalBearbeiten{{ $aufschlag->id }}" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form action="{{ route('preis-aufschlaege.store-global') }}" method="POST">
                                                        @csrf
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Aufschlag {{ $aufschlag->jahr }} bearbeiten</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <input type="hidden" name="jahr" value="{{ $aufschlag->jahr }}">
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label">Jahr</label>
                                                                <input type="text" class="form-control" value="{{ $aufschlag->jahr }}" disabled>
                                                            </div>

                                                            <div class="mb-3">
                                                                <label for="prozent{{ $aufschlag->id }}" class="form-label">
                                                                    Aufschlag in %
                                                                    <span class="text-danger">*</span>
                                                                </label>
                                                                <div class="input-group">
                                                                    <input type="number" 
                                                                           class="form-control" 
                                                                           id="prozent{{ $aufschlag->id }}"
                                                                           name="prozent" 
                                                                           step="0.01" 
                                                                           min="-100" 
                                                                           max="100"
                                                                           value="{{ $aufschlag->prozent }}"
                                                                           required>
                                                                    <span class="input-group-text">%</span>
                                                                </div>
                                                                <small class="form-text text-muted">
                                                                    Positiv = Erhöhung, Negativ = Rabatt
                                                                </small>
                                                            </div>

                                                            <div class="mb-3">
                                                                <label for="beschreibung{{ $aufschlag->id }}" class="form-label">
                                                                    Beschreibung
                                                                </label>
                                                                <input type="text" 
                                                                       class="form-control" 
                                                                       id="beschreibung{{ $aufschlag->id }}"
                                                                       name="beschreibung" 
                                                                       value="{{ $aufschlag->beschreibung }}"
                                                                       maxlength="500">
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                                                            <button type="submit" class="btn btn-primary">
                                                                <i class="bi bi-check-circle"></i>
                                                                Speichern
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Statistik Cards --}}
    @if($aufschlaege->isNotEmpty())
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <h6 class="text-muted">Definierte Jahre</h6>
                        <h2 class="mb-0">{{ $aufschlaege->count() }}</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <h6 class="text-muted">Aktueller Aufschlag ({{ now()->year }})</h6>
                        <h2 class="mb-0">
                            @php
                                $aktuell = $aufschlaege->firstWhere('jahr', now()->year);
                            @endphp
                            @if($aktuell)
                                <span class="text-{{ $aktuell->prozent > 0 ? 'success' : ($aktuell->prozent < 0 ? 'danger' : 'secondary') }}">
                                    {{ $aktuell->prozent > 0 ? '+' : '' }}{{ number_format($aktuell->prozent, 2, ',', '.') }}%
                                </span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <h6 class="text-muted">Durchschnitt</h6>
                        <h2 class="mb-0">
                            {{ number_format($aufschlaege->avg('prozent'), 2, ',', '.') }}%
                        </h2>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

{{-- Modal: Neuer Aufschlag --}}
<div class="modal fade" id="modalNeuerAufschlag" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('preis-aufschlaege.store-global') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Neuer globaler Aufschlag</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="jahr" class="form-label">
                            Jahr
                            <span class="text-danger">*</span>
                        </label>
                        <input type="number" 
                               class="form-control" 
                               id="jahr" 
                               name="jahr" 
                               min="2020" 
                               max="2099" 
                               value="{{ now()->year }}"
                               required>
                    </div>

                    <div class="mb-3">
                        <label for="prozent_neu" class="form-label">
                            Aufschlag in %
                            <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <input type="number" 
                                   class="form-control" 
                                   id="prozent_neu"
                                   name="prozent" 
                                   step="0.01" 
                                   min="-100" 
                                   max="100"
                                   value="0.00"
                                   required>
                            <span class="input-group-text">%</span>
                        </div>
                        <small class="form-text text-muted">
                            Positiv = Erhöhung (z.B. 3.5), Negativ = Rabatt (z.B. -5.0)
                        </small>
                    </div>

                    <div class="mb-3">
                        <label for="beschreibung_neu" class="form-label">
                            Beschreibung
                        </label>
                        <input type="text" 
                               class="form-control" 
                               id="beschreibung_neu"
                               name="beschreibung" 
                               placeholder="z.B. Inflation 2025"
                               maxlength="500">
                    </div>

                    <div class="alert alert-info mb-0">
                        <i class="bi bi-info-circle"></i>
                        <strong>Hinweis:</strong> Dieser Aufschlag gilt für alle Gebäude, die keinen individuellen Aufschlag haben.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i>
                        Erstellen
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Hidden Form für Löschen --}}
<form id="deleteForm" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

@endsection

@push('scripts')
<script>
function confirmDelete(id, jahr) {
    if (confirm(`Möchten Sie den Aufschlag für ${jahr} wirklich löschen?\n\nAchtung: Gebäude ohne individuellen Aufschlag haben dann möglicherweise keinen Aufschlag mehr!`)) {
        const form = document.getElementById('deleteForm');
        form.action = `/preis-aufschlaege/global/${id}`;
        form.submit();
    }
}

// Auto-hide alerts nach 5 Sekunden
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert-dismissible');
        alerts.forEach(function(alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
});
</script>
@endpush