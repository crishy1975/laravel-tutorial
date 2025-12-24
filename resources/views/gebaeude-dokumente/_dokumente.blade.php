{{-- 
    Partial: _dokumente.blade.php
    Einbinden in gebaeude/form.blade.php als Tab oder Section
    
    Benötigt: $gebaeude (mit dokumente-Beziehung geladen)
--}}

@php
    $dokumente = $gebaeude->dokumente ?? collect();
    $kategorien = \App\Models\GebaeudeDocument::KATEGORIEN;
@endphp

<div class="card shadow-sm mb-4">
    <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="mb-0">
            <i class="bi bi-folder2-open me-2"></i>
            Dokumente
            @if($dokumente->count() > 0)
                <span class="badge bg-primary ms-1">{{ $dokumente->count() }}</span>
            @endif
        </h5>
        @if(isset($gebaeude) && $gebaeude->id)
            <div class="btn-group btn-group-sm">
                {{-- Desktop: Hochladen Button --}}
                <button type="button" class="btn btn-primary d-none d-sm-inline-flex" 
                        data-bs-toggle="modal" data-bs-target="#modalDokumentUpload">
                    <i class="bi bi-upload me-1"></i>Hochladen
                </button>
                {{-- Mobile: Kamera + Galerie Buttons --}}
                <button type="button" class="btn btn-success d-sm-none" 
                        data-bs-toggle="modal" data-bs-target="#modalFotoAufnehmen">
                    <i class="bi bi-camera-fill"></i>
                </button>
                <button type="button" class="btn btn-primary d-sm-none" 
                        data-bs-toggle="modal" data-bs-target="#modalDokumentUpload">
                    <i class="bi bi-folder2-open"></i>
                </button>
            </div>
        @endif
    </div>

    @if(!isset($gebaeude) || !$gebaeude->id)
        <div class="card-body">
            <div class="alert alert-info mb-0">
                <i class="bi bi-info-circle me-2"></i>
                Dokumente können erst nach dem Speichern des Gebäudes hochgeladen werden.
            </div>
        </div>
    @elseif($dokumente->isEmpty())
        <div class="card-body text-center py-4">
            <i class="bi bi-folder text-muted" style="font-size: 2.5rem;"></i>
            <h6 class="mt-3 text-muted">Keine Dokumente vorhanden</h6>
            <p class="text-muted small mb-3">Laden Sie Verträge, Fotos oder andere Dokumente hoch.</p>
            
            {{-- Desktop --}}
            <button type="button" class="btn btn-outline-primary d-none d-sm-inline-block" 
                    data-bs-toggle="modal" data-bs-target="#modalDokumentUpload">
                <i class="bi bi-upload me-1"></i>Erstes Dokument hochladen
            </button>
            
            {{-- Mobile: Zwei Buttons --}}
            <div class="d-sm-none d-flex justify-content-center gap-2">
                <button type="button" class="btn btn-success" 
                        data-bs-toggle="modal" data-bs-target="#modalFotoAufnehmen">
                    <i class="bi bi-camera-fill me-1"></i>Foto
                </button>
                <button type="button" class="btn btn-primary" 
                        data-bs-toggle="modal" data-bs-target="#modalDokumentUpload">
                    <i class="bi bi-folder2-open me-1"></i>Datei
                </button>
            </div>
        </div>
    @else
        {{-- Dokumente-Liste --}}
        <div class="list-group list-group-flush" id="dokumenteListe">
            @foreach($dokumente->sortByDesc('created_at')->take(10) as $dok)
                <div class="list-group-item dokument-item" id="dokument-{{ $dok->id }}">
                    <div class="d-flex gap-2 align-items-start">
                        {{-- Icon / Thumbnail --}}
                        <div class="flex-shrink-0">
                            @if($dok->ist_bild)
                                <a href="{{ route('gebaeude.dokumente.preview', $dok->id) }}" target="_blank">
                                    <img src="{{ route('gebaeude.dokumente.thumbnail', $dok->id) }}" 
                                         alt="{{ $dok->titel }}"
                                         class="rounded border"
                                         style="width: 50px; height: 50px; object-fit: cover;">
                                </a>
                            @else
                                <div class="rounded bg-light d-flex align-items-center justify-content-center" 
                                     style="width: 50px; height: 50px;">
                                    <i class="{{ $dok->icon }} fs-4"></i>
                                </div>
                            @endif
                        </div>
                        
                        {{-- Content --}}
                        <div class="flex-grow-1 min-w-0">
                            {{-- Titel + Wichtig --}}
                            <div class="d-flex align-items-center gap-1 mb-1">
                                @if($dok->ist_wichtig)
                                    <i class="bi bi-star-fill text-warning flex-shrink-0"></i>
                                @endif
                                <a href="{{ $dok->download_url }}" 
                                   class="fw-semibold text-decoration-none text-truncate d-block">
                                    {{ $dok->titel }}
                                </a>
                            </div>
                            
                            {{-- Info-Zeile --}}
                            <div class="d-flex flex-wrap align-items-center gap-2 small text-muted">
                                <span class="badge bg-light text-dark">{{ $dok->kategorie_label }}</span>
                                <span>{{ $dok->dateigroesse_formatiert }}</span>
                                <span class="d-none d-sm-inline">{{ $dok->created_at->format('d.m.Y') }}</span>
                            </div>
                        </div>
                        
                        {{-- Aktionen --}}
                        <div class="flex-shrink-0">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary" type="button" 
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    @if($dok->ist_bild || $dok->ist_pdf)
                                        <li>
                                            <a class="dropdown-item" href="{{ route('gebaeude.dokumente.preview', $dok->id) }}" target="_blank">
                                                <i class="bi bi-eye me-2"></i>Vorschau
                                            </a>
                                        </li>
                                    @endif
                                    <li>
                                        <a class="dropdown-item" href="{{ $dok->download_url }}">
                                            <i class="bi bi-download me-2"></i>Download
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <button type="button" class="dropdown-item btn-edit-dokument"
                                                data-id="{{ $dok->id }}"
                                                data-titel="{{ $dok->titel }}"
                                                data-beschreibung="{{ $dok->beschreibung }}"
                                                data-kategorie="{{ $dok->kategorie }}"
                                                data-tags="{{ $dok->tags }}"
                                                data-wichtig="{{ $dok->ist_wichtig ? '1' : '0' }}">
                                            <i class="bi bi-pencil me-2"></i>Bearbeiten
                                        </button>
                                    </li>
                                    <li>
                                        <button type="button" class="dropdown-item text-warning btn-toggle-wichtig"
                                                data-id="{{ $dok->id }}">
                                            <i class="bi bi-star{{ $dok->ist_wichtig ? '-fill' : '' }} me-2"></i>
                                            {{ $dok->ist_wichtig ? 'Nicht mehr wichtig' : 'Als wichtig markieren' }}
                                        </button>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <button type="button" class="dropdown-item text-danger btn-delete-dokument"
                                                data-id="{{ $dok->id }}"
                                                data-titel="{{ $dok->titel }}">
                                            <i class="bi bi-trash me-2"></i>Löschen
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Mehr anzeigen / Mobile Buttons --}}
        <div class="card-footer bg-white">
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
                @if($dokumente->count() > 10)
                    <a href="{{ route('gebaeude.dokumente.index', ['gebaeude_id' => $gebaeude->id]) }}" 
                       class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-folder2-open me-1"></i>Alle {{ $dokumente->count() }} anzeigen
                    </a>
                @else
                    <span></span>
                @endif
                
                {{-- Mobile: Schnell-Upload Buttons --}}
                <div class="d-sm-none d-flex gap-2">
                    <button type="button" class="btn btn-success btn-sm" 
                            data-bs-toggle="modal" data-bs-target="#modalFotoAufnehmen">
                        <i class="bi bi-camera-fill me-1"></i>Foto
                    </button>
                    <button type="button" class="btn btn-primary btn-sm" 
                            data-bs-toggle="modal" data-bs-target="#modalDokumentUpload">
                        <i class="bi bi-plus-lg me-1"></i>Datei
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

<style>
.dokument-item {
    transition: background-color 0.2s ease;
}
.dokument-item:active {
    background-color: #f8f9fa;
}
.min-w-0 { min-width: 0; }

@media (max-width: 575.98px) {
    .dokument-item {
        padding: 0.75rem !important;
    }
}
</style>

