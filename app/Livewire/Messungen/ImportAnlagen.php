{{-- resources/views/livewire/messungen/import-anlagen.blade.php --}}
<div class="container-fluid py-2 py-md-4">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 h3-md mb-0">
                <i class="bi bi-file-earmark-arrow-up text-success"></i>
                Anlagen importieren
            </h1>
            <p class="text-muted mb-0 small d-none d-md-block">CSV-Import</p>
        </div>
        <a href="{{ route('messungen.anlagen.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i>
            <span class="d-none d-sm-inline">Zurück</span>
        </a>
    </div>

    {{-- Upload-Karte --}}
    <div class="card shadow-sm mb-3">
        <div class="card-header bg-light py-2">
            <h6 class="mb-0"><i class="bi bi-upload"></i> CSV-Datei hochladen</h6>
        </div>
        <div class="card-body">
            <form wire:submit="import">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-md-6">
                        <label for="csvFile" class="form-label small mb-1">CSV-Datei auswählen</label>
                        <input type="file" id="csvFile" wire:model="csvFile" accept=".csv,.txt"
                               class="form-control form-control-sm">
                        @error('csvFile')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                        {{-- Loading während Datei-Upload --}}
                        <div wire:loading wire:target="csvFile" class="text-muted small mt-1">
                            <span class="spinner-border spinner-border-sm"></span> Datei wird hochgeladen...
                        </div>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary btn-sm"
                                wire:loading.attr="disabled" wire:target="import">
                            <span wire:loading.remove wire:target="import">
                                <i class="bi bi-upload"></i> Importieren
                            </span>
                            <span wire:loading wire:target="import">
                                <span class="spinner-border spinner-border-sm"></span> Importiere...
                            </span>
                        </button>
                    </div>
                    @if($importResult || count($errors ?? []) > 0)
                        <div class="col-auto">
                            <button type="button" wire:click="resetImport" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-arrow-counterclockwise"></i> Zurücksetzen
                            </button>
                        </div>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- Ergebnis --}}
    @if($importResult)
        <div class="alert alert-success py-2" role="alert">
            <h6 class="alert-heading mb-2">
                <i class="bi bi-check-circle"></i> Import abgeschlossen
            </h6>
            <div class="row g-2">
                <div class="col-auto">
                    <span class="badge bg-secondary">Verarbeitet: <strong>{{ $importResult['total'] }}</strong></span>
                </div>
                <div class="col-auto">
                    <span class="badge bg-success">Neu importiert: <strong>{{ $importResult['imported'] }}</strong></span>
                </div>
                <div class="col-auto">
                    <span class="badge bg-warning text-dark">Übersprungen: <strong>{{ $importResult['skipped'] }}</strong></span>
                </div>
                @if($importResult['errors'] > 0)
                    <div class="col-auto">
                        <span class="badge bg-danger">Fehler: <strong>{{ $importResult['errors'] }}</strong></span>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Fehler --}}
    @if(count($errors ?? []) > 0)
        <div class="alert alert-danger py-2" role="alert">
            <h6 class="alert-heading mb-2">
                <i class="bi bi-exclamation-triangle"></i> Fehler beim Import
            </h6>
            <div style="max-height: 200px; overflow-y: auto;">
                @foreach($errors as $error)
                    <div class="small">{{ $error }}</div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Info-Box --}}
    <div class="card shadow-sm">
        <div class="card-header bg-light py-2">
            <h6 class="mb-0"><i class="bi bi-info-circle"></i> Hinweise</h6>
        </div>
        <div class="card-body py-2">
            <div class="small text-muted">
                <p class="mb-1"><i class="bi bi-dot"></i> CSV-Datei mit Semikolon (<code>;</code>) als Trennzeichen</p>
                <p class="mb-1"><i class="bi bi-dot"></i> Nur neue Anlagen werden importiert (Kodex noch nicht vorhanden)</p>
                <p class="mb-0"><i class="bi bi-dot"></i> Bestehende Anlagen werden übersprungen, nicht aktualisiert</p>
            </div>
        </div>
    </div>
</div>
