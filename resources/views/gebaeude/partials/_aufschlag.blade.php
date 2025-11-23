@if(isset($gebaeude) && $gebaeude->id)
    @php
        $jahr = now()->year;
        $aufschlagProzent = $gebaeude->getAufschlagProzent($jahr);
        $hatIndividuell = $gebaeude->hatIndividuellenAufschlag();
        $globalerAufschlag = \App\Models\PreisAufschlag::getGlobalerAufschlag($jahr);
    @endphp

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card @if($hatIndividuell) border-warning @else border-primary @endif h-100">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">
                        <i class="bi bi-building"></i>
                        Aktueller Aufschlag ({{ $jahr }})
                    </h6>
                    <h2 class="mb-2">
                        @if($aufschlagProzent > 0)
                            <span class="text-success">+{{ number_format($aufschlagProzent, 2, ',', '.') }}%</span>
                        @elseif($aufschlagProzent < 0)
                            <span class="text-danger">{{ number_format($aufschlagProzent, 2, ',', '.') }}%</span>
                        @else
                            <span class="text-muted">0,00%</span>
                        @endif
                    </h2>
                    
                    @if($hatIndividuell)
                        <span class="badge bg-warning text-dark">
                            <i class="bi bi-star-fill"></i> Individuell
                        </span>
                    @else
                        <span class="badge bg-primary">
                            <i class="bi bi-globe"></i> Global
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card border-secondary h-100">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">
                        <i class="bi bi-globe"></i>
                        Globaler Standard ({{ $jahr }})
                    </h6>
                    <h2 class="mb-2">
                        @if($globalerAufschlag > 0)
                            <span class="text-success">+{{ number_format($globalerAufschlag, 2, ',', '.') }}%</span>
                        @else
                            <span class="text-muted">0,00%</span>
                        @endif
                    </h2>
                    <a href="{{ route('preis-aufschlaege.index') }}" class="btn btn-sm btn-outline-secondary mt-2">
                        <i class="bi bi-gear"></i> Verwalten
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="alert @if($hatIndividuell) alert-warning @else alert-info @endif">
        <i class="bi bi-info-circle"></i>
        @if($hatIndividuell)
            Individueller Aufschlag von <strong>{{ number_format($aufschlagProzent, 2, ',', '.') }}%</strong> aktiv.
        @else
            Globaler Aufschlag von <strong>{{ number_format($globalerAufschlag, 2, ',', '.') }}%</strong> wird verwendet.
        @endif
        Bei neuen Rechnungen werden die Artikel-Preise automatisch angepasst.
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light">
            <h6 class="mb-0">
                <i class="bi bi-sliders"></i>
                Aufschlag verwalten
            </h6>
        </div>
        <div class="card-body">
            <div class="d-grid gap-2 d-md-flex">
                @if($hatIndividuell)
                    <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#modalAufschlagBearbeiten">
                        <i class="bi bi-pencil"></i> Bearbeiten
                    </button>
                    <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalAufschlagEntfernen">
                        <i class="bi bi-x-circle"></i> Entfernen
                    </button>
                @else
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAufschlagSetzen">
                        <i class="bi bi-star"></i> Individuellen Aufschlag festlegen
                    </button>
                @endif
                
                <button type="button" class="btn btn-outline-info ms-md-auto" data-bs-toggle="modal" data-bs-target="#modalAufschlagVorschau">
                    <i class="bi bi-eye"></i> Vorschau
                </button>
            </div>
        </div>
    </div>

@else
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i>
        Der Preis-Aufschlag kann erst nach dem Erstellen des Gebaeudes verwaltet werden.
    </div>
@endif