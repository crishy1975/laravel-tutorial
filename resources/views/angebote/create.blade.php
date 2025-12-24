{{-- resources/views/angebote/create.blade.php --}}
{{-- MOBIL-OPTIMIERT: Cards mit Farben, Sticky Footer --}}

@extends('layouts.app')

@section('content')
<div class="container-fluid px-2 px-md-4 py-3">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0">
                <i class="bi bi-plus-circle text-success"></i>
                <span class="d-none d-sm-inline">Neues Angebot</span>
                <span class="d-sm-none">Neu</span>
            </h4>
            <small class="text-muted d-none d-sm-inline">Angebot aus Gebaeude erstellen</small>
        </div>
        <a href="{{ route('angebote.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i>
            <span class="d-none d-sm-inline ms-1">Zurueck</span>
        </a>
    </div>

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show py-2">
            <i class="bi bi-exclamation-triangle me-1"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row justify-content-center">
        <div class="col-lg-8">
            
            {{-- Hauptformular --}}
            <form method="POST" action="" id="formFromGebaeude">
                @csrf

                {{-- Gebaeude-Auswahl Card --}}
                <div class="card mb-3 border-success">
                    <div class="card-header bg-success text-white py-2">
                        <i class="bi bi-building"></i>
                        <span class="fw-semibold ms-1">Gebaeude auswaehlen</span>
                    </div>
                    <div class="card-body p-2 p-md-3">
                        <div class="mb-3">
                            <label class="form-label small mb-1">
                                Gebaeude <span class="text-danger">*</span>
                            </label>
                            <select id="gebaeudeSelect" class="form-select js-select2" 
                                    data-placeholder="Gebaeude suchen..." required>
                                <option value="">-- Bitte waehlen --</option>
                                @foreach($gebaeude as $g)
                                    <option value="{{ $g->id }}">
                                        {{ $g->codex ?: '(kein Codex)' }} - {{ $g->gebaeude_name ?: $g->wohnort }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text small">
                                <i class="bi bi-info-circle"></i> 
                                Alle aktiven Artikel werden uebernommen.
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Optionen Card --}}
                <div class="card mb-3">
                    <div class="card-header bg-secondary text-white py-2">
                        <i class="bi bi-gear"></i>
                        <span class="fw-semibold ms-1">Optionen</span>
                    </div>
                    <div class="card-body p-2 p-md-3">
                        <div class="row g-3">
                            <div class="col-12 col-md-8">
                                <label class="form-label small mb-1">Titel (optional)</label>
                                <input type="text" name="titel" class="form-control" 
                                       placeholder="Automatisch aus Gebaeude-Name">
                                <div class="form-text small">Leer = automatischer Titel</div>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label small mb-1">Gueltig bis</label>
                                <input type="date" name="gueltig_bis" class="form-control" 
                                       value="{{ now()->addDays(30)->format('Y-m-d') }}">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Sticky Footer Mobile --}}
                <div class="sticky-bottom-bar d-md-none">
                    <button type="submit" class="btn btn-success w-100" id="btnFromGebaeudeMobile" disabled>
                        <i class="bi bi-plus-lg"></i> Angebot erstellen
                    </button>
                </div>

                {{-- Desktop Button --}}
                <div class="d-none d-md-block">
                    <div class="d-grid">
                        <button type="submit" class="btn btn-success btn-lg" id="btnFromGebaeude" disabled>
                            <i class="bi bi-plus-lg"></i> Angebot erstellen
                        </button>
                    </div>
                </div>
            </form>

            {{-- Info-Box --}}
            <div class="card mt-4 bg-light">
                <div class="card-body py-2 px-3">
                    <h6 class="mb-2"><i class="bi bi-lightbulb text-warning"></i> Hinweis</h6>
                    <ul class="mb-0 small text-muted ps-3">
                        <li>Automatische Nummer: A{{ now()->year }}/XXXX</li>
                        <li>Empfaenger aus Rechnungsempfaenger</li>
                        <li>MwSt aus Fattura-Profil</li>
                        <li>Nach Erstellen noch bearbeitbar</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.sticky-bottom-bar {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: #fff;
    border-top: 1px solid #dee2e6;
    padding: 12px 16px;
    z-index: 1030;
    box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
}
@media (max-width: 767.98px) {
    .container-fluid { padding-bottom: 80px; }
    .form-control, .form-select { min-height: 44px; font-size: 16px !important; }
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var gebaeudeSelect = document.getElementById('gebaeudeSelect');
    var form = document.getElementById('formFromGebaeude');
    var btnDesktop = document.getElementById('btnFromGebaeude');
    var btnMobile = document.getElementById('btnFromGebaeudeMobile');

    function updateButtons() {
        var gebaeudeId = gebaeudeSelect.value;
        var hasValue = !!gebaeudeId;
        
        if (btnDesktop) btnDesktop.disabled = !hasValue;
        if (btnMobile) btnMobile.disabled = !hasValue;
        
        if (gebaeudeId) {
            form.action = '{{ url("/angebote/from-gebaeude") }}/' + gebaeudeId;
        }
    }

    // Select2 Change
    if (typeof $ !== 'undefined' && $.fn.select2) {
        $(gebaeudeSelect).on('change', updateButtons);
    }
    
    // Normales Change
    gebaeudeSelect.addEventListener('change', updateButtons);
});
</script>
@endpush
@endsection
