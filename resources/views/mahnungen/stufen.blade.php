{{-- resources/views/mahnungen/stufen.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container py-3">
    
    {{-- Kopfzeile --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0"><i class="bi bi-sliders"></i> Mahnstufen konfigurieren</h4>
            <small class="text-muted">Texte, Spesen und Fristen anpassen</small>
        </div>
        <a href="{{ route('mahnungen.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Zurück
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Info-Box --}}
    <div class="alert alert-info mb-4">
        <i class="bi bi-info-circle"></i>
        <strong>Verfügbare Platzhalter:</strong>
        <code>{rechnungsnummer}</code>, <code>{rechnungsdatum}</code>, <code>{faelligkeitsdatum}</code>, 
        <code>{betrag}</code>, <code>{spesen}</code>, <code>{gesamtbetrag}</code>, 
        <code>{tage_ueberfaellig}</code>, <code>{firma}</code>, <code>{kunde}</code>
    </div>

    <div class="row">
        @foreach($stufen as $stufe)
            <div class="col-lg-6 mb-4">
                <div class="card h-100">
                    <div class="card-header {{ $stufe->badge_class }} {{ in_array($stufe->stufe, [1, 2]) ? 'text-dark' : 'text-white' }}">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <i class="bi {{ $stufe->icon }}"></i>
                                <strong>Stufe {{ $stufe->stufe }}: {{ $stufe->name_de }}</strong>
                            </div>
                            @if(!$stufe->aktiv)
                                <span class="badge bg-dark">Inaktiv</span>
                            @endif
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-6">
                                <small class="text-muted">Ab Tage überfällig</small>
                                <div class="fw-bold">{{ $stufe->tage_ueberfaellig }} Tage</div>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Mahnspesen</small>
                                <div class="fw-bold">{{ $stufe->spesen_formatiert }}</div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <small class="text-muted">Name (IT)</small>
                            <div>{{ $stufe->name_it }}</div>
                        </div>

                        <div class="mb-3">
                            <small class="text-muted">Betreff (DE / IT)</small>
                            <div class="small">
                                🇩🇪 {{ Str::limit($stufe->betreff_de, 50) }}<br>
                                🇮🇹 {{ Str::limit($stufe->betreff_it, 50) }}
                            </div>
                        </div>

                        <div class="mb-3">
                            <small class="text-muted">Text-Vorschau</small>
                            <div class="small text-muted bg-light p-2 rounded" style="max-height: 100px; overflow: auto;">
                                {{ Str::limit($stufe->text_de, 200) }}
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent">
                        <a href="{{ route('mahnungen.stufe.bearbeiten', $stufe->id) }}" class="btn btn-primary w-100">
                            <i class="bi bi-pencil"></i> Bearbeiten
                        </a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

</div>
@endsection
