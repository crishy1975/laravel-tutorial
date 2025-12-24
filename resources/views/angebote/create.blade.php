{{-- resources/views/angebote/create.blade.php --}}
{{-- MOBIL-OPTIMIERT: Verbessertes Design, Dropdown-Fix --}}

@extends('layouts.app')

@section('content')
<div class="container-fluid px-2 px-md-4 py-3">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0">
                <i class="bi bi-plus-circle text-success"></i>
                <span class="d-none d-sm-inline">Neues Angebot erstellen</span>
                <span class="d-sm-none">Neues Angebot</span>
            </h4>
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
        <div class="col-lg-7 col-xl-6">
            
            {{-- Hauptformular --}}
            <form method="POST" action="" id="formFromGebaeude">
                @csrf

                {{-- Gebaeude-Auswahl Card --}}
                <div class="card mb-4 shadow-sm border-0 card-select2">
                    <div class="card-header bg-success text-white py-3">
                        <div class="d-flex align-items-center">
                            <div class="bg-white bg-opacity-25 rounded-circle p-2 me-3">
                                <i class="bi bi-building fs-4"></i>
                            </div>
                            <div>
                                <h5 class="mb-0 fw-bold">Gebaeude auswaehlen</h5>
                                <small class="opacity-75">Schritt 1 von 2</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-3 p-md-4">
                        <label class="form-label fw-semibold mb-2">
                            <i class="bi bi-search me-1 text-success"></i>
                            Gebaeude suchen <span class="text-danger">*</span>
                        </label>
                        <select id="gebaeudeSelect" class="form-select form-select-lg js-select2" 
                                data-placeholder="Tippen zum Suchen..." required>
                            <option value="">-- Gebaeude waehlen --</option>
                            @foreach($gebaeude as $g)
                                <option value="{{ $g->id }}" 
                                        data-codex="{{ $g->codex }}"
                                        data-name="{{ $g->gebaeude_name }}"
                                        data-ort="{{ $g->wohnort }}">
                                    {{ $g->codex ?: '(kein Codex)' }} - {{ $g->gebaeude_name ?: $g->wohnort }}
                                </option>
                            @endforeach
                        </select>
                        
                        {{-- Info mit mehr Abstand --}}
                        <div class="alert alert-light border mt-4 mb-0">
                            <div class="d-flex align-items-start">
                                <i class="bi bi-info-circle text-success fs-5 me-2 mt-1"></i>
                                <div>
                                    <strong>Was passiert?</strong>
                                    <ul class="mb-0 mt-2 ps-3 text-muted">
                                        <li class="mb-1">Alle aktiven Artikel werden uebernommen</li>
                                        <li class="mb-1">Empfaenger aus Rechnungsempfaenger</li>
                                        <li>MwSt-Satz aus Fattura-Profil</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Optionen Card --}}
                <div class="card mb-4 shadow-sm border-0">
                    <div class="card-header bg-secondary bg-opacity-75 text-white py-3">
                        <div class="d-flex align-items-center">
                            <div class="bg-white bg-opacity-25 rounded-circle p-2 me-3">
                                <i class="bi bi-sliders fs-4"></i>
                            </div>
                            <div>
                                <h5 class="mb-0 fw-bold">Optionen</h5>
                                <small class="opacity-75">Schritt 2 von 2 (optional)</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-3 p-md-4">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-semibold mb-2">
                                    <i class="bi bi-fonts me-1 text-secondary"></i>
                                    Titel
                                </label>
                                <input type="text" name="titel" class="form-control form-control-lg" 
                                       placeholder="Wird automatisch aus Gebaeude-Name generiert">
                                <div class="form-text mt-2">
                                    <i class="bi bi-magic me-1"></i>
                                    Leer lassen fuer automatischen Titel
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold mb-2">
                                    <i class="bi bi-calendar-check me-1 text-secondary"></i>
                                    Gueltig bis
                                </label>
                                <input type="date" name="gueltig_bis" class="form-control form-control-lg" 
                                       value="{{ now()->addDays(30)->format('Y-m-d') }}">
                                <div class="form-text mt-2">
                                    <i class="bi bi-clock me-1"></i>
                                    Standard: 30 Tage ab heute
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Vorschau Card (erscheint nach Auswahl) --}}
                <div class="card mb-4 shadow-sm border-success d-none" id="vorschauCard">
                    <div class="card-header bg-success bg-opacity-10 py-2">
                        <i class="bi bi-eye text-success me-1"></i>
                        <span class="fw-semibold text-success">Ausgewaehltes Gebaeude</span>
                    </div>
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center">
                            <div class="bg-success bg-opacity-10 rounded p-3 me-3">
                                <i class="bi bi-building text-success fs-3"></i>
                            </div>
                            <div>
                                <div class="fw-bold fs-5" id="vorschauCodex">-</div>
                                <div class="text-muted" id="vorschauName">-</div>
                                <div class="small text-muted" id="vorschauOrt">-</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Submit Button Desktop --}}
                <div class="d-none d-md-block">
                    <div class="d-grid">
                        <button type="submit" class="btn btn-success btn-lg py-3" id="btnFromGebaeude" disabled>
                            <i class="bi bi-plus-circle me-2"></i>
                            <span class="fw-bold">Angebot erstellen</span>
                        </button>
                    </div>
                </div>

                {{-- Sticky Footer Mobile --}}
                <div class="sticky-bottom-bar d-md-none">
                    <button type="submit" class="btn btn-success w-100 py-3" id="btnFromGebaeudeMobile" disabled>
                        <i class="bi bi-plus-circle me-2"></i>
                        <span class="fw-bold">Angebot erstellen</span>
                    </button>
                </div>
            </form>

            {{-- Hinweis-Box --}}
            <div class="card bg-light border-0 shadow-sm">
                <div class="card-body py-3">
                    <div class="d-flex align-items-start">
                        <i class="bi bi-lightbulb text-warning fs-4 me-3"></i>
                        <div class="small">
                            <strong class="d-block mb-1">Gut zu wissen</strong>
                            <span class="text-muted">
                                Das Angebot erhaelt automatisch die naechste freie Nummer 
                                <code class="bg-white px-1">A{{ now()->year }}/XXXX</code>. 
                                Nach dem Erstellen koennen Sie alles noch bearbeiten.
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
/* Sticky Footer */
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

/* ============================================
   Select2 Dropdown Fix - WICHTIG!
   ============================================ */
.card-select2 {
    overflow: visible !important;
}
.card-select2 .card-body {
    overflow: visible !important;
}
.select2-container--open {
    z-index: 9999 !important;
}
.select2-dropdown {
    z-index: 9999 !important;
    border: 1px solid #198754 !important;
    border-radius: 0.375rem !important;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
}

/* Select2 Styling */
.select2-container--default .select2-selection--single {
    height: 52px !important;
    padding: 10px 14px !important;
    border: 2px solid #dee2e6 !important;
    border-radius: 0.5rem !important;
    transition: border-color 0.2s ease;
}
.select2-container--default .select2-selection--single:hover {
    border-color: #198754 !important;
}
.select2-container--default.select2-container--open .select2-selection--single {
    border-color: #198754 !important;
    box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.25) !important;
}
.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 30px !important;
    padding-left: 0 !important;
    font-size: 16px !important;
}
.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 50px !important;
    right: 10px !important;
}
.select2-container--default .select2-results__option--highlighted[aria-selected] {
    background-color: #198754 !important;
}
.select2-search--dropdown .select2-search__field {
    padding: 12px !important;
    font-size: 16px !important;
    border: 1px solid #dee2e6 !important;
    border-radius: 0.375rem !important;
}
.select2-results__option {
    padding: 10px 14px !important;
}

/* Mobile Optimierungen */
@media (max-width: 767.98px) {
    .container-fluid { padding-bottom: 100px; }
    .form-control, .form-select, .btn { min-height: 48px; font-size: 16px !important; }
    .form-control-lg, .form-select-lg { min-height: 52px; }
    
    /* Select2 auf Mobile */
    .select2-container--default .select2-selection--single {
        height: 52px !important;
    }
}

/* Card Hover Effekt */
.card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.card:hover {
    transform: translateY(-2px);
}

/* Disabled Button Styling */
.btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Info Alert Styling */
.alert-light {
    background-color: #f8f9fa;
}
.alert-light ul li {
    line-height: 1.6;
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
    var vorschauCard = document.getElementById('vorschauCard');
    var vorschauCodex = document.getElementById('vorschauCodex');
    var vorschauName = document.getElementById('vorschauName');
    var vorschauOrt = document.getElementById('vorschauOrt');

    function updateUI() {
        var gebaeudeId = gebaeudeSelect.value;
        var hasValue = !!gebaeudeId;
        var selectedOption = gebaeudeSelect.options[gebaeudeSelect.selectedIndex];
        
        // Buttons aktivieren/deaktivieren
        if (btnDesktop) btnDesktop.disabled = !hasValue;
        if (btnMobile) btnMobile.disabled = !hasValue;
        
        // Form Action setzen
        if (gebaeudeId) {
            form.action = '{{ url("/angebote/from-gebaeude") }}/' + gebaeudeId;
        }

        // Vorschau aktualisieren
        if (hasValue && selectedOption) {
            var codex = selectedOption.getAttribute('data-codex') || '(kein Codex)';
            var name = selectedOption.getAttribute('data-name') || '-';
            var ort = selectedOption.getAttribute('data-ort') || '-';
            
            vorschauCodex.textContent = codex;
            vorschauName.textContent = name;
            vorschauOrt.textContent = ort;
            vorschauCard.classList.remove('d-none');
        } else {
            vorschauCard.classList.add('d-none');
        }
    }

    // Select2 initialisieren (falls vorhanden)
    if (typeof $ !== 'undefined' && $.fn.select2) {
        $(gebaeudeSelect).select2({
            theme: 'default',
            placeholder: 'Tippen zum Suchen...',
            allowClear: true,
            width: '100%',
            dropdownParent: $(gebaeudeSelect).closest('.card-body')
        }).on('change', updateUI);
    }
    
    // Fallback: normales Change Event
    gebaeudeSelect.addEventListener('change', updateUI);
});
</script>
@endpush
@endsection
