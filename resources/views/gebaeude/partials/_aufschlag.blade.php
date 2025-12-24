{{-- resources/views/gebaeude/partials/_aufschlag.blade.php --}}
{{-- MOBIL-OPTIMIERT --}}

@if(isset($gebaeude) && $gebaeude->id)
    @php
        $jahr = now()->year;
        $aufschlagProzent = $gebaeude->getAufschlagProzent($jahr);
        $hatIndividuell = $gebaeude->hatIndividuellenAufschlag();
        $globalerAufschlag = \App\Models\PreisAufschlag::getGlobalerAufschlag($jahr);
    @endphp

    <div class="row g-3">
        {{-- Karten - stacken auf Mobile --}}
        <div class="col-12 col-md-6">
            <div class="card @if($hatIndividuell) border-warning @else border-primary @endif h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-building text-muted"></i>
                        <span class="small text-muted">Aktueller Aufschlag ({{ $jahr }})</span>
                    </div>
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

        <div class="col-12 col-md-6">
            <div class="card border-secondary h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-globe text-muted"></i>
                        <span class="small text-muted">Globaler Standard ({{ $jahr }})</span>
                    </div>
                    <h2 class="mb-2">
                        @if($globalerAufschlag > 0)
                            <span class="text-success">+{{ number_format($globalerAufschlag, 2, ',', '.') }}%</span>
                        @else
                            <span class="text-muted">0,00%</span>
                        @endif
                    </h2>
                    <a href="{{ route('preis-aufschlaege.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-gear"></i> Verwalten
                    </a>
                </div>
            </div>
        </div>

        {{-- Info-Alert --}}
        <div class="col-12">
            <div class="alert @if($hatIndividuell) alert-warning @else alert-info @endif py-2 mb-0 small">
                <i class="bi bi-info-circle"></i>
                @if($hatIndividuell)
                    Individueller Aufschlag <strong>{{ number_format($aufschlagProzent, 2, ',', '.') }}%</strong> aktiv.
                @else
                    Globaler Aufschlag <strong>{{ number_format($globalerAufschlag, 2, ',', '.') }}%</strong> wird verwendet.
                @endif
            </div>
        </div>

        {{-- Buttons - volle Breite auf Mobile --}}
        <div class="col-12">
            <div class="card">
                <div class="card-body p-2 p-md-3">
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
        </div>
    </div>

@else
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i>
        Preis-Aufschlag erst nach Erstellen des Gebaeudes verfuegbar.
    </div>
@endif
