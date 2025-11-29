{{-- resources/views/rechnung/form.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card shadow-sm border-0">

        {{-- Header --}}
        <div class="card-header bg-white">
            <h4 class="mb-0">
                <i class="bi bi-receipt"></i>
                @if($rechnung->exists)
                    @if($rechnung->typ_rechnung === 'gutschrift')
                        Gutschrift {{ $rechnung->rechnungsnummer }} bearbeiten
                    @else
                        Rechnung {{ $rechnung->rechnungsnummer }} bearbeiten
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
        <div class="alert alert-success alert-dismissible fade show mt-3 mx-3">
            <i class="bi bi-check-circle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mt-3 mx-3">
            <i class="bi bi-exclamation-triangle"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        {{-- Validierungsfehler --}}
        @if($errors->any())
        <div class="alert alert-warning alert-dismissible fade show mt-3 mx-3">
            <strong>Bitte Eingaben prüfen:</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        {{-- Tabs-Navigation --}}
        <div class="card-body border-bottom pb-0">
            <ul class="nav nav-tabs" id="rechnungTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="tab-allgemein"
                        data-bs-toggle="tab" data-bs-target="#content-allgemein"
                        type="button" role="tab">
                        <i class="bi bi-file-text"></i> Allgemein
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-vorschau"
                        data-bs-toggle="tab" data-bs-target="#content-vorschau"
                        type="button" role="tab">
                        <i class="bi bi-eye"></i> Vorschau
                    </button>
                </li>
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
            <div class="alert alert-warning mx-3 mt-3">
                <i class="bi bi-lock"></i>
                Diese Rechnung kann nicht mehr bearbeitet werden (Status: {{ $rechnung->status }}).
            </div>
            @endif

            <div class="tab-content p-4">

                {{-- Tab 1: Allgemein --}}
                <div class="tab-pane fade show active" id="content-allgemein" role="tabpanel">
                    @include('rechnung.partials._allgemein')
                </div>

                {{-- Tab 2: Vorschau --}}
                <div class="tab-pane fade" id="content-vorschau" role="tabpanel">
                    @include('rechnung.partials._vorschau')
                </div>

            </div>

            {{-- Footer --}}
            <div class="card-footer bg-white text-end">
                @if(!$rechnung->exists || $rechnung->ist_editierbar)
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i>
                    {{ $rechnung->exists ? 'Änderungen speichern' : 'Rechnung anlegen' }}
                </button>
                @endif

                <a href="{{ route('rechnung.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Zurück
                </a>

                @if($rechnung->exists)
                <a href="{{ route('rechnung.edit', $rechnung->id) }}" class="btn btn-outline-info">
                    <i class="bi bi-eye"></i> Ansehen
                </a>
                @endif
            </div>
        </form>

    </div>
</div>
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

        // Tab wiederherstellen NUR wenn KEINE Success-Message
        const hasSuccessMessage = document.querySelector('.alert-success');

        if (!hasSuccessMessage) {
            // Normaler Seitenaufruf: Tab wiederherstellen
            const lastTab = localStorage.getItem('activeRechnungTab');
            if (lastTab) {
                const triggerEl = document.querySelector(`#rechnungTabs button[data-bs-target="${lastTab}"]`);
                if (triggerEl) new bootstrap.Tab(triggerEl).show();
            }
        } else {
            // Nach Speichern: Immer zum ersten Tab (Allgemein)
            localStorage.setItem('activeRechnungTab', '#content-allgemein');
        }
    });
</script>
@endpush