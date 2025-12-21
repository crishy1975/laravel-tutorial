{{-- resources/views/angebote/create.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container py-3">

    {{-- Kopfzeile --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0"><i class="bi bi-plus-circle"></i> Neues Angebot erstellen</h4>
            <small class="text-muted">Angebot aus Gebäude mit aktiven Artikeln erstellen</small>
        </div>
        <a href="{{ route('angebote.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Zurück
        </a>
    </div>

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-success">
                <div class="card-header bg-success text-white">
                    <i class="bi bi-building"></i> Angebot aus Gebäude erstellen
                </div>
                <div class="card-body">
                    <p class="text-muted mb-4">
                        Wählen Sie ein Gebäude und das Angebot wird automatisch mit allen aktiven Artikeln erstellt.
                    </p>
                    
                    <form method="POST" action="" id="formFromGebaeude">
                        @csrf
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold">
                                Gebäude auswählen <span class="text-danger">*</span>
                            </label>
                            <select id="gebaeudeSelect" class="form-select form-select-lg js-select2" 
                                    data-placeholder="Gebäude suchen..." required>
                                <option value="">-- Bitte wählen --</option>
                                @foreach($gebaeude as $g)
                                    <option value="{{ $g->id }}">
                                        {{ $g->codex ?: '(kein Codex)' }} - {{ $g->gebaeude_name ?: $g->wohnort }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">
                                <i class="bi bi-info-circle"></i> 
                                Es werden alle aktiven Artikel des Gebäudes übernommen.
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="row g-3 mb-4">
                            <div class="col-md-8">
                                <label class="form-label">Titel (optional)</label>
                                <input type="text" name="titel" class="form-control" 
                                       placeholder="Automatisch aus Gebäude-Name">
                                <div class="form-text">Leer lassen für automatischen Titel</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Gültig bis</label>
                                <input type="date" name="gueltig_bis" class="form-control" 
                                       value="{{ now()->addDays(30)->format('Y-m-d') }}">
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-success btn-lg" id="btnFromGebaeude" disabled>
                                <i class="bi bi-plus-lg"></i> Angebot erstellen
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Info-Box --}}
            <div class="card mt-4 bg-light">
                <div class="card-body">
                    <h6 class="card-title"><i class="bi bi-lightbulb"></i> Hinweis</h6>
                    <ul class="mb-0 small text-muted">
                        <li>Das Angebot erhält automatisch die nächste freie Nummer (A{{ now()->year }}/XXXX)</li>
                        <li>Empfänger-Adresse wird aus dem Rechnungsempfänger des Gebäudes übernommen</li>
                        <li>MwSt-Satz wird aus dem Fattura-Profil des Gebäudes übernommen</li>
                        <li>Nach dem Erstellen können Sie das Angebot noch bearbeiten</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const gebaeudeSelect = document.getElementById('gebaeudeSelect');
    const form = document.getElementById('formFromGebaeude');
    const btn = document.getElementById('btnFromGebaeude');

    // Bei Select2 Change-Event
    $(gebaeudeSelect).on('change', function() {
        const gebaeudeId = this.value;
        btn.disabled = !gebaeudeId;
        
        if (gebaeudeId) {
            form.action = '{{ url("/angebote/from-gebaeude") }}/' + gebaeudeId;
        }
    });

    // Auch für normales Change-Event (falls Select2 nicht geladen)
    gebaeudeSelect.addEventListener('change', function() {
        const gebaeudeId = this.value;
        btn.disabled = !gebaeudeId;
        
        if (gebaeudeId) {
            form.action = '{{ url("/angebote/from-gebaeude") }}/' + gebaeudeId;
        }
    });
});
</script>
@endpush
@endsection
