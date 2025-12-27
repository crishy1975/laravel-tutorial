{{-- resources/views/rechnung/form.blade.php --}}
{{-- ⭐ MOBILE-OPTIMIERTE VERSION --}}
@extends('layouts.app')

@section('content')
<div class="container-fluid px-2 px-md-3">
    <div class="card shadow-sm border-0">

        {{-- Header --}}
        <div class="card-header bg-white py-2 py-md-3">
            <h4 class="mb-0 fs-5 fs-md-4">
                <i class="bi bi-receipt"></i>
                @if($rechnung->exists)
                    @if($rechnung->typ_rechnung === 'gutschrift')
                        <span class="d-none d-sm-inline">Gutschrift</span>
                        <span class="d-sm-none">GS</span>
                        {{ $rechnung->rechnungsnummer }}
                        <span class="d-none d-md-inline">bearbeiten</span>
                    @else
                        <span class="d-none d-sm-inline">Rechnung</span>
                        <span class="d-sm-none">RE</span>
                        {{ $rechnung->rechnungsnummer }}
                        <span class="d-none d-md-inline">bearbeiten</span>
                    @endif
                @else
                    Neue Rechnung
                @endif

                @if($rechnung->exists)
                <span class="ms-2">{!! $rechnung->status_badge !!}</span>
                @endif
            </h4>
        </div>

        {{-- Success/Error Messages --}}
        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mt-3 mx-2 mx-md-3">
            <i class="bi bi-check-circle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mt-3 mx-2 mx-md-3">
            <i class="bi bi-exclamation-triangle"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif
        
        @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show mt-3 mx-2 mx-md-3">
            <i class="bi bi-exclamation-circle"></i> {{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        {{-- Validierungsfehler --}}
        @if($errors->any())
        <div class="alert alert-warning alert-dismissible fade show mt-3 mx-2 mx-md-3">
            <strong>Bitte Eingaben prüfen:</strong>
            <ul class="mb-0 mt-2 small">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        {{-- Tabs-Navigation - MOBILE-OPTIMIERT --}}
        <div class="card-body border-bottom pb-0 px-2 px-md-3">
            <ul class="nav nav-tabs flex-nowrap overflow-auto" id="rechnungTabs" role="tablist" style="-webkit-overflow-scrolling: touch;">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active text-nowrap px-2 px-md-3" id="tab-allgemein"
                        data-bs-toggle="tab" data-bs-target="#content-allgemein"
                        type="button" role="tab">
                        <i class="bi bi-file-text"></i>
                        <span class="d-none d-sm-inline">Allgemein</span>
                    </button>
                </li>
                {{-- ⭐ FatturaPA Tab (nur wenn Rechnung existiert) --}}
                @if($rechnung->exists && $rechnung->fattura_profile_id)
                <li class="nav-item" role="presentation">
                    <button class="nav-link text-nowrap px-2 px-md-3" id="tab-fattura"
                        data-bs-toggle="tab" data-bs-target="#content-fattura"
                        type="button" role="tab">
                        <i class="bi bi-file-earmark-code"></i>
                        <span class="d-none d-sm-inline">FatturaPA</span>
                        @php
                            $xmlLogCount = \App\Models\FatturaXmlLog::where('rechnung_id', $rechnung->id)->count();
                        @endphp
                        @if($xmlLogCount > 0)
                            <span class="badge bg-success ms-1">{{ $xmlLogCount }}</span>
                        @endif
                    </button>
                </li>
                @endif
                {{-- ⭐ Log-Tab (nur wenn Rechnung existiert) --}}
                @if($rechnung->exists)
                <li class="nav-item" role="presentation">
                    <button class="nav-link text-nowrap px-2 px-md-3" id="tab-logs"
                        data-bs-toggle="tab" data-bs-target="#content-logs"
                        type="button" role="tab">
                        <i class="bi bi-clock-history"></i>
                        <span class="d-none d-sm-inline">Log</span>
                        @php
                            $logCount = \App\Models\RechnungLog::where('rechnung_id', $rechnung->id)->count();
                            $offeneErinnerungen = \App\Models\RechnungLog::where('rechnung_id', $rechnung->id)
                                ->offeneErinnerungen()->count();
                        @endphp
                        @if($logCount > 0)
                            <span class="badge bg-secondary ms-1">{{ $logCount }}</span>
                        @endif
                        @if($offeneErinnerungen > 0)
                            <span class="badge bg-warning ms-1" title="Offene Erinnerungen">
                                <i class="bi bi-bell"></i> {{ $offeneErinnerungen }}
                            </span>
                        @endif
                    </button>
                </li>
                @endif
            </ul>
        </div>

        {{-- Form (umschließt alle Tabs) --}}
        <form id="rechnungForm" method="POST"
            action="{{ $rechnung->exists ? route('rechnung.update', $rechnung->id) : route('rechnung.store') }}">
            @csrf
            @if($rechnung->exists)
            @method('PUT')
            @endif

            {{-- Nur editierbar wenn draft --}}
            @if($rechnung->exists && !$rechnung->ist_editierbar)
            <div class="alert alert-warning mx-2 mx-md-3 mt-3">
                <i class="bi bi-lock"></i>
                <span class="d-none d-md-inline">Diese Rechnung kann nicht mehr bearbeitet werden</span>
                <span class="d-md-none">Nicht editierbar</span>
                (Status: {{ $rechnung->status }}).
            </div>
            @endif

            <div class="tab-content p-2 p-md-4">

                {{-- Tab 1: Allgemein --}}
                <div class="tab-pane fade show active" id="content-allgemein" role="tabpanel">
                    @include('rechnung.partials._allgemein')
                </div>

                {{-- ⭐ Tab 2: FatturaPA (nur wenn Rechnung existiert) --}}
                @if($rechnung->exists)
                <div class="tab-pane fade" id="content-fattura" role="tabpanel">
                    @include('rechnung.partials._fattura_xml')
                </div>
                @endif

                {{-- ⭐ Tab 3: Log-System (nur wenn Rechnung existiert) --}}
                @if($rechnung->exists)
                <div class="tab-pane fade" id="content-logs" role="tabpanel">
                    @include('rechnung.partials._logs')
                </div>
                @endif

            </div>

            {{-- Footer - MOBILE-OPTIMIERT --}}
            <div class="card-footer bg-white p-2 p-md-3">
                {{-- Desktop Layout --}}
                <div class="d-none d-md-flex justify-content-between align-items-center">
                    <div>
                        {{-- PDF Buttons (links) --}}
                        @if($rechnung->exists)
                            <a href="{{ route('rechnung.pdf.download', $rechnung->id) }}" class="btn btn-outline-danger">
                                <i class="bi bi-file-earmark-pdf"></i> PDF
                            </a>
                            <a href="{{ route('rechnung.pdf.preview', $rechnung->id) }}" class="btn btn-outline-secondary" target="_blank">
                                <i class="bi bi-eye"></i> PDF Vorschau
                            </a>
                        @endif
                    </div>
                    
                    <div>
                        {{-- Speichern & Navigation (rechts) --}}
                        @if(!$rechnung->exists || $rechnung->ist_editierbar)
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i>
                            {{ $rechnung->exists ? 'Änderungen speichern' : 'Rechnung anlegen' }}
                        </button>
                        @endif

                        <a href="{{ route('rechnung.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Zurück
                        </a>
                    </div>
                </div>

                {{-- Mobile Layout - Gestapelt --}}
                <div class="d-md-none d-grid gap-2">
                    {{-- Primäre Aktion zuerst --}}
                    @if(!$rechnung->exists || $rechnung->ist_editierbar)
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-save"></i>
                        {{ $rechnung->exists ? 'Speichern' : 'Rechnung anlegen' }}
                    </button>
                    @endif

                    {{-- PDF Buttons --}}
                    @if($rechnung->exists)
                    <div class="d-flex gap-2">
                        <a href="{{ route('rechnung.pdf.download', $rechnung->id) }}" class="btn btn-outline-danger flex-fill">
                            <i class="bi bi-file-earmark-pdf"></i> PDF
                        </a>
                        <a href="{{ route('rechnung.pdf.preview', $rechnung->id) }}" class="btn btn-outline-secondary flex-fill" target="_blank">
                            <i class="bi bi-eye"></i> Vorschau
                        </a>
                    </div>
                    @endif

                    {{-- Zurück-Button --}}
                    <a href="{{ route('rechnung.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Zurück zur Übersicht
                    </a>
                </div>
            </div>
        </form>
        {{-- ═══════════════════════════════════════════════════════════
             ENDE DES HAUPTFORMULARS
        ═══════════════════════════════════════════════════════════ --}}

    </div>
</div>

{{-- ⭐⭐⭐ KRITISCH: Modals AUSSERHALB des Hauptformulars einbinden! ⭐⭐⭐ --}}
@stack('modals')

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const tabs = document.querySelectorAll('#rechnungTabs button[data-bs-toggle="tab"]');

        tabs.forEach(tab => {
            tab.addEventListener('shown.bs.tab', function(event) {
                localStorage.setItem('activeRechnungTab', event.target.dataset.bsTarget);
            });
        });

        // ⭐ Tab wiederherstellen (auch nach Success-Message)
        const lastTab = localStorage.getItem('activeRechnungTab');
        if (lastTab) {
            const triggerEl = document.querySelector(`#rechnungTabs button[data-bs-target="${lastTab}"]`);
            if (triggerEl) new bootstrap.Tab(triggerEl).show();
        }
    });
</script>
@endpush
