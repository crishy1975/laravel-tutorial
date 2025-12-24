{{-- resources/views/angebote/versand.blade.php --}}
{{-- MOBIL-OPTIMIERT: E-Mail Versand View --}}

@extends('layouts.app')

@section('content')
<div class="container-fluid px-2 px-md-4 py-3">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0">
                <i class="bi bi-envelope text-primary"></i>
                <span class="d-none d-sm-inline">Angebot versenden</span>
                <span class="d-sm-none">Versenden</span>
            </h4>
            <small class="text-muted">{{ $angebot->angebotsnummer }}</small>
        </div>
        <a href="{{ route('angebote.edit', $angebot) }}" class="btn btn-outline-secondary btn-sm">
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

    {{-- Bereits versendet Warnung --}}
    @if($angebot->versendet_am)
        <div class="alert alert-warning py-2">
            <i class="bi bi-exclamation-triangle me-1"></i>
            <strong>Bereits versendet!</strong><br>
            {{ $angebot->versendet_am->format('d.m.Y H:i') }} an {{ $angebot->versendet_an_email }}
        </div>
    @endif

    <div class="row">
        {{-- Hauptformular --}}
        <div class="col-lg-8 order-2 order-lg-1">
            <form method="POST" action="{{ route('angebote.versenden', $angebot) }}" id="versandForm">
                @csrf

                <div class="card mb-3">
                    <div class="card-header bg-primary text-white py-2">
                        <i class="bi bi-envelope-fill"></i>
                        <span class="fw-semibold ms-1">E-Mail Versand / Invio E-Mail</span>
                    </div>
                    <div class="card-body p-2 p-md-3">
                        <div class="mb-3">
                            <label class="form-label small mb-1">
                                Empfaenger E-Mail <span class="text-danger">*</span>
                            </label>
                            <input type="email" name="email" class="form-control" 
                                   value="{{ old('email', $email) }}" required>
                            @if(!$email)
                                <div class="form-text text-warning">
                                    <i class="bi bi-exclamation-triangle"></i> Keine E-Mail hinterlegt!
                                </div>
                            @endif
                        </div>

                        <div class="mb-3">
                            <label class="form-label small mb-1">
                                Betreff <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="betreff" class="form-control" 
                                   value="{{ old('betreff', $betreff) }}" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small mb-1">
                                Nachricht <span class="text-danger">*</span>
                            </label>
                            <textarea name="text" class="form-control" rows="12" required>{{ old('text', $text) }}</textarea>
                        </div>

                        <div class="alert alert-info mb-0 py-2">
                            <i class="bi bi-paperclip me-1"></i>
                            <strong>Anhang:</strong> Angebot_{{ $angebot->angebotsnummer }}.pdf
                        </div>
                    </div>
                </div>

                {{-- Desktop Buttons --}}
                <div class="d-none d-md-flex gap-2">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-send"></i> Jetzt versenden
                    </button>
                    <a href="{{ route('angebote.pdf', ['angebot' => $angebot, 'preview' => 1]) }}" 
                       class="btn btn-outline-secondary" target="_blank">
                        <i class="bi bi-eye"></i> PDF Vorschau
                    </a>
                </div>
            </form>
        </div>

        {{-- Sidebar --}}
        <div class="col-lg-4 order-1 order-lg-2 mb-3 mb-lg-0">
            {{-- Angebot-Info --}}
            <div class="card mb-3">
                <div class="card-header bg-light py-2">
                    <i class="bi bi-file-earmark-text"></i>
                    <span class="fw-semibold ms-1">Angebot</span>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush small">
                        <li class="list-group-item d-flex justify-content-between py-2">
                            <span class="text-muted">Nr.:</span>
                            <span class="fw-bold">{{ $angebot->angebotsnummer }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between py-2">
                            <span class="text-muted">Datum:</span>
                            <span>{{ $angebot->datum->format('d.m.Y') }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between py-2">
                            <span class="text-muted">Gueltig bis:</span>
                            <span>
                                @if($angebot->gueltig_bis)
                                    {{ $angebot->gueltig_bis->format('d.m.Y') }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between py-2">
                            <span class="text-muted">Betrag:</span>
                            <span class="fw-bold text-primary">{{ $angebot->brutto_formatiert }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between py-2">
                            <span class="text-muted">Status:</span>
                            <span>{!! $angebot->status_badge !!}</span>
                        </li>
                    </ul>
                </div>
            </div>

            {{-- Empfaenger-Info --}}
            <div class="card mb-3">
                <div class="card-header bg-light py-2">
                    <i class="bi bi-person"></i>
                    <span class="fw-semibold ms-1">Empfaenger</span>
                </div>
                <div class="card-body p-3">
                    <div class="fw-semibold">{{ $angebot->empfaenger_name }}</div>
                    <div class="small text-muted">
                        {{ $angebot->empfaenger_strasse }} {{ $angebot->empfaenger_hausnummer }}<br>
                        {{ $angebot->empfaenger_plz }} {{ $angebot->empfaenger_ort }}
                    </div>
                    @if($angebot->empfaenger_email)
                        <hr class="my-2">
                        <a href="mailto:{{ $angebot->empfaenger_email }}" class="text-decoration-none">
                            <i class="bi bi-envelope me-1"></i>{{ $angebot->empfaenger_email }}
                        </a>
                    @endif
                </div>
            </div>

            {{-- PDF Vorschau Button Mobile --}}
            <div class="d-lg-none">
                <a href="{{ route('angebote.pdf', ['angebot' => $angebot, 'preview' => 1]) }}" 
                   class="btn btn-outline-secondary w-100" target="_blank">
                    <i class="bi bi-eye"></i> PDF Vorschau
                </a>
            </div>
        </div>
    </div>

    {{-- Sticky Footer Mobile --}}
    <div class="sticky-bottom-bar d-md-none">
        <button type="submit" form="versandForm" class="btn btn-success w-100">
            <i class="bi bi-send"></i> Jetzt versenden
        </button>
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
    .form-control, .form-select, .btn { min-height: 44px; font-size: 16px !important; }
}
</style>
@endpush
@endsection
