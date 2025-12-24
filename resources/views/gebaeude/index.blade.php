@extends('layouts.app')

@section('title', 'Dokumente')

@section('content')
<div class="container-fluid py-3">
    
    {{-- Header --}}
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
        <div>
            <h1 class="h3 mb-1">
                <i class="bi bi-folder2-open me-2"></i>Dokumente
            </h1>
            <p class="text-muted mb-0">
                {{ $stats['gesamt'] }} Dokumente
                ({{ number_format($stats['speicher'] / 1024 / 1024, 1, ',', '.') }} MB)
            </p>
        </div>
    </div>

    {{-- Statistik-Karten --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="bi bi-files text-primary fs-3"></i>
                    <div class="fs-4 fw-bold">{{ $stats['gesamt'] }}</div>
                    <small class="text-muted">Gesamt</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="bi bi-file-earmark-image text-info fs-3"></i>
                    <div class="fs-4 fw-bold">{{ $stats['bilder'] }}</div>
                    <small class="text-muted">Bilder</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="bi bi-file-earmark-pdf text-danger fs-3"></i>
                    <div class="fs-4 fw-bold">{{ $stats['pdfs'] }}</div>
                    <small class="text-muted">PDFs</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="bi bi-star-fill text-warning fs-3"></i>
                    <div class="fs-4 fw-bold">{{ $stats['wichtig'] }}</div>
                    <small class="text-muted">Wichtig</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter-Karte --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white">
            <button class="btn btn-link text-decoration-none p-0 w-100 text-start d-flex justify-content-between align-items-center"
                    type="button" 
                    data-bs-toggle="collapse" 
                    data-bs-target="#filterCollapse">
                <span><i class="bi bi-funnel me-2"></i>Filter</span>
                <i class="bi bi-chevron-down"></i>
            </button>
        </div>
        <div class="collapse @if(request()->hasAny(['gebaeude_id', 'kategorie', 'typ', 'suche', 'wichtig'])) show @endif" id="filterCollapse">
            <div class="card-body">
                <form method="GET" action="{{ route('gebaeude.dokumente.index') }}">
                    <div class="row g-3">
                        {{-- Suche --}}
                        <div class="col-12 col-md-4">
                            <label class="form-label small">Suche</label>
                            <input type="text" name="suche" class="form-control" 
                                   value="{{ request('suche') }}" 
                                   placeholder="Titel, Beschreibung, Tags...">
                        </div>

                        {{-- Gebäude --}}
                        <div class="col-6 col-md-4">
                            <label class="form-label small">Gebäude</label>
                            <select name="gebaeude_id" class="form-select">
                                <option value="">Alle Gebäude</option>
                                @foreach($gebaeudeListe as $geb)
                                    <option value="{{ $geb->id }}" @selected(request('gebaeude_id') == $geb->id)>
                                        {{ $geb->codex ?? $geb->gebaeude_name ?? $geb->strasse }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Kategorie --}}
                        <div class="col-6 col-md-4">
                            <label class="form-label small">Kategorie</label>
                            <select name="kategorie" class="form-select">
                                <option value="">Alle Kategorien</option>
                                @foreach($kategorien as $key => $label)
                                    <option value="{{ $key }}" @selected(request('kategorie') == $key)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Dateityp --}}
                        <div class="col-6 col-md-3">
                            <label class="form-label small">Dateityp</label>
                            <select name="typ" class="form-select">
                                <option value="">Alle Typen</option>
                                <option value="bild" @selected(request('typ') == 'bild')>Bilder</option>
                                <option value="pdf" @selected(request('typ') == 'pdf')>PDFs</option>
                                <option value="office" @selected(request('typ') == 'office')>Office</option>
                                <option value="sonstige" @selected(request('typ') == 'sonstige')>Sonstige</option>
                            </select>
                        </div>

                        {{-- Datum von --}}
                        <div class="col-6 col-md-3">
                            <label class="form-label small">Von</label>
                            <input type="date" name="von" class="form-control" value="{{ request('von') }}">
                        </div>

                        {{-- Datum bis --}}
                        <div class="col-6 col-md-3">
                            <label class="form-label small">Bis</label>
                            <input type="date" name="bis" class="form-control" value="{{ request('bis') }}">
                        </div>

                        {{-- Checkboxen --}}
                        <div class="col-6 col-md-3 d-flex align-items-end gap-3">
                            <div class="form-check">
                                <input type="checkbox" name="wichtig" value="1" class="form-check-input" 
                                       id="filterWichtig" @checked(request('wichtig'))>
                                <label class="form-check-label" for="filterWichtig">
                                    <i class="bi bi-star-fill text-warning"></i> Wichtig
                                </label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" name="archiviert" value="1" class="form-check-input" 
                                       id="filterArchiviert" @checked(request('archiviert'))>
                                <label class="form-check-label" for="filterArchiviert">
                                    <i class="bi bi-archive"></i> Archiv
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search me-1"></i>Filtern
                        </button>
                        <a href="{{ route('gebaeude.dokumente.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-x-lg me-1"></i>Zurücksetzen
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Dokumente-Liste --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <span>{{ $dokumente->total() }} Dokumente gefunden</span>
        </div>

        @if($dokumente->isEmpty())
            <div class="card-body text-center py-5">
                <i class="bi bi-folder2 text-muted" style="font-size: 4rem;"></i>
                <h5 class="mt-3 text-muted">Keine Dokumente gefunden</h5>
                <p class="text-muted">Passen Sie die Filter an oder laden Sie neue Dokumente hoch.</p>
            </div>
        @else
            {{-- Desktop Tabelle --}}
            <div class="table-responsive d-none d-md-block">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 40px;"></th>
                            <th>Dokument</th>
                            <th>Gebäude</th>
                            <th>Kategorie</th>
                            <th class="text-end">Größe</th>
                            <th>Datum</th>
                            <th style="width: 120px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($dokumente as $dok)
                            <tr @if($dok->ist_archiviert) class="table-secondary" @endif>
                                {{-- Icon --}}
                                <td class="text-center">
                                    <i class="{{ $dok->icon }} fs-4"></i>
                                </td>
                                
                                {{-- Titel + Original-Name --}}
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        @if($dok->ist_wichtig)
                                            <i class="bi bi-star-fill text-warning"></i>
                                        @endif
                                        <div>
                                            <a href="{{ $dok->download_url }}" class="fw-bold text-decoration-none">
                                                {{ Str::limit($dok->titel, 40) }}
                                            </a>
                                            <div class="small text-muted">{{ Str::limit($dok->original_name, 50) }}</div>
                                        </div>
                                    </div>
                                </td>
                                
                                {{-- Gebäude --}}
                                <td>
                                    @if($dok->gebaeude)
                                        <a href="{{ route('gebaeude.edit', $dok->gebaeude_id) }}" class="text-decoration-none">
                                            <span class="badge bg-dark">{{ $dok->gebaeude->codex ?? '-' }}</span>
                                            <span class="small">{{ Str::limit($dok->gebaeude->gebaeude_name ?? $dok->gebaeude->strasse, 20) }}</span>
                                        </a>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                
                                {{-- Kategorie --}}
                                <td>
                                    <span class="badge bg-secondary">{{ $dok->kategorie_label }}</span>
                                </td>
                                
                                {{-- Größe --}}
                                <td class="text-end small">
                                    {{ $dok->dateigroesse_formatiert }}
                                </td>
                                
                                {{-- Datum --}}
                                <td class="small">
                                    {{ $dok->created_at->format('d.m.Y') }}
                                </td>
                                
                                {{-- Aktionen --}}
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        @if($dok->ist_bild || $dok->ist_pdf)
                                            <a href="{{ route('gebaeude.dokumente.preview', $dok->id) }}" 
                                               class="btn btn-outline-secondary" target="_blank" title="Vorschau">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        @endif
                                        <a href="{{ $dok->download_url }}" class="btn btn-outline-primary" title="Download">
                                            <i class="bi bi-download"></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-warning toggle-wichtig" 
                                                data-id="{{ $dok->id }}" title="Wichtig markieren">
                                            <i class="bi bi-star{{ $dok->ist_wichtig ? '-fill' : '' }}"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-danger delete-dokument" 
                                                data-id="{{ $dok->id }}" data-titel="{{ $dok->titel }}" title="Löschen">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Mobile Cards --}}
            <div class="d-md-none">
                @foreach($dokumente as $dok)
                    <div class="card mx-2 my-2 @if($dok->ist_archiviert) bg-light @endif">
                        <div class="card-body p-3">
                            <div class="d-flex gap-2">
                                {{-- Icon / Thumbnail --}}
                                <div class="flex-shrink-0">
                                    @if($dok->ist_bild)
                                        <a href="{{ route('gebaeude.dokumente.preview', $dok->id) }}" target="_blank">
                                            <img src="{{ route('gebaeude.dokumente.thumbnail', $dok->id) }}" 
                                                 alt="{{ $dok->titel }}"
                                                 class="rounded border"
                                                 style="width: 60px; height: 60px; object-fit: cover;">
                                        </a>
                                    @else
                                        <div class="rounded bg-light d-flex align-items-center justify-content-center" 
                                             style="width: 60px; height: 60px;">
                                            <i class="{{ $dok->icon }} fs-2"></i>
                                        </div>
                                    @endif
                                </div>
                                
                                {{-- Content --}}
                                <div class="flex-grow-1 min-w-0">
                                    {{-- Titel --}}
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <div class="fw-semibold text-truncate pe-2">
                                            @if($dok->ist_wichtig)
                                                <i class="bi bi-star-fill text-warning me-1"></i>
                                            @endif
                                            {{ $dok->titel }}
                                        </div>
                                    </div>
                                    
                                    {{-- Gebäude --}}
                                    @if($dok->gebaeude)
                                        <div class="small mb-1">
                                            <span class="badge bg-dark">{{ $dok->gebaeude->codex ?? '-' }}</span>
                                            <span class="text-muted">{{ Str::limit($dok->gebaeude->gebaeude_name ?? $dok->gebaeude->strasse, 20) }}</span>
                                        </div>
                                    @endif
                                    
                                    {{-- Info-Zeile --}}
                                    <div class="d-flex flex-wrap align-items-center gap-2 small text-muted mb-2">
                                        <span class="badge bg-light text-dark">{{ $dok->kategorie_label }}</span>
                                        <span>{{ $dok->dateigroesse_formatiert }}</span>
                                        <span>{{ $dok->created_at->format('d.m.Y') }}</span>
                                    </div>
                                    
                                    {{-- Aktionen --}}
                                    <div class="d-flex gap-2">
                                        <a href="{{ $dok->download_url }}" class="btn btn-sm btn-primary flex-grow-1">
                                            <i class="bi bi-download me-1"></i>Download
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-warning toggle-wichtig" 
                                                data-id="{{ $dok->id }}">
                                            <i class="bi bi-star{{ $dok->ist_wichtig ? '-fill' : '' }}"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger delete-dokument" 
                                                data-id="{{ $dok->id }}" data-titel="{{ $dok->titel }}">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            @if($dokumente->hasPages())
                <div class="card-footer bg-white">
                    {{ $dokumente->links() }}
                </div>
            @endif
        @endif
    </div>
</div>

{{-- Delete Confirm Modal --}}
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Dokument löschen?</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Möchten Sie das Dokument <strong id="deleteTitel"></strong> wirklich löschen?</p>
                <p class="text-danger mb-0">Diese Aktion kann nicht rückgängig gemacht werden!</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                <form id="deleteForm" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Löschen</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Delete Modal
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    
    document.querySelectorAll('.delete-dokument').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const titel = this.dataset.titel;
            
            document.getElementById('deleteTitel').textContent = titel;
            document.getElementById('deleteForm').action = `/gebaeude/dokumente/${id}`;
            
            deleteModal.show();
        });
    });

    // Toggle Wichtig
    document.querySelectorAll('.toggle-wichtig').forEach(btn => {
        btn.addEventListener('click', async function() {
            const id = this.dataset.id;
            const icon = this.querySelector('i');
            
            try {
                const res = await fetch(`/gebaeude/dokumente/${id}/wichtig`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });
                
                const data = await res.json();
                
                if (data.ok) {
                    icon.classList.toggle('bi-star');
                    icon.classList.toggle('bi-star-fill');
                }
            } catch (e) {
                console.error('Fehler:', e);
            }
        });
    });
});
</script>
@endpush

@push('styles')
<style>
.min-w-0 { min-width: 0; }

@media (max-width: 767.98px) {
    .card-body { padding: 0.75rem; }
}
</style>
@endpush
