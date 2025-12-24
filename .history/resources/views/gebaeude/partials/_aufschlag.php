{{-- resources/views/gebaeude/partials/_aufschlag.blade.php --}}

@if(isset($gebaeude) && $gebaeude->id)
    @php
        $jahr = now()->year;
        $aufschlagProzent = $gebaeude->getAufschlagProzent($jahr);
        $hatIndividuell = $gebaeude->hatIndividuellenAufschlag();
        $globalerAufschlag = \App\Models\PreisAufschlag::getGlobalerAufschlag($jahr);
    @endphp

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card {{ $hatIndividuell ? 'border-warning' : 'border-primary' }} h-100">
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

    <div class="alert alert-info">
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

    {{-- Modal: Setzen --}}
    <div class="modal fade" id="modalAufschlagSetzen" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('gebaeude.aufschlag.set', $gebaeude->id) }}">
                    @csrf
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">Individuellen Aufschlag festlegen</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            Aktuell: Globaler Aufschlag {{ number_format($globalerAufschlag, 2, ',', '.') }}%
                        </div>

                        <div class="mb-3">
                            <label for="prozent" class="form-label">Aufschlag in % <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="prozent" name="prozent" 
                                       step="0.01" min="-100" max="100" value="{{ old('prozent', $globalerAufschlag) }}" required>
                                <span class="input-group-text">%</span>
                            </div>
                            <small class="text-muted">Positiv = Aufschlag, Negativ = Rabatt, 0 = Kein Aufschlag</small>
                        </div>

                        <div class="mb-3">
                            <label for="grund" class="form-label">Begruendung</label>
                            <input type="text" class="form-control" id="grund" name="grund" maxlength="255">
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="gueltig_ab" class="form-label">Gueltig ab</label>
                                <input type="date" class="form-control" id="gueltig_ab" name="gueltig_ab" 
                                       value="{{ old('gueltig_ab', now()->format('Y-m-d')) }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="gueltig_bis" class="form-label">Gueltig bis</label>
                                <input type="date" class="form-control" id="gueltig_bis" name="gueltig_bis">
                                <small class="text-muted">Leer = unbegrenzt</small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                        <button type="submit" class="btn btn-primary">Speichern</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal: Bearbeiten --}}
    @if($hatIndividuell && $gebaeude->gebaeudeAufschlag)
    <div class="modal fade" id="modalAufschlagBearbeiten" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('gebaeude.aufschlag.set', $gebaeude->id) }}">
                    @csrf
                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title">Aufschlag bearbeiten</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        @php $aktuellerAufschlag = $gebaeude->gebaeudeAufschlag; @endphp

                        <div class="mb-3">
                            <label for="prozent_edit" class="form-label">Aufschlag in %</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="prozent_edit" name="prozent" 
                                       step="0.01" min="-100" max="100" value="{{ old('prozent', $aktuellerAufschlag->prozent) }}" required>
                                <span class="input-group-text">%</span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="grund_edit" class="form-label">Begruendung</label>
                            <input type="text" class="form-control" id="grund_edit" name="grund" 
                                   value="{{ old('grund', $aktuellerAufschlag->grund) }}" maxlength="255">
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="gueltig_ab_edit" class="form-label">Gueltig ab</label>
                                <input type="date" class="form-control" id="gueltig_ab_edit" name="gueltig_ab" 
                                       value="{{ old('gueltig_ab', $aktuellerAufschlag->gueltig_ab->format('Y-m-d')) }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="gueltig_bis_edit" class="form-label">Gueltig bis</label>
                                <input type="date" class="form-control" id="gueltig_bis_edit" name="gueltig_bis" 
                                       value="{{ old('gueltig_bis', $aktuellerAufschlag->gueltig_bis?->format('Y-m-d')) }}">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                        <button type="submit" class="btn btn-warning">Speichern</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    {{-- Modal: Entfernen --}}
    @if($hatIndividuell)
    <div class="modal fade" id="modalAufschlagEntfernen" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('gebaeude.aufschlag.remove', $gebaeude->id) }}">
                    @csrf
                    @method('DELETE')
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">Aufschlag entfernen?</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            Der individuelle Aufschlag wird entfernt. Es gilt dann wieder der globale Standard.
                        </div>
                        <table class="table table-sm">
                            <tr>
                                <th>Aktuell:</th>
                                <td><span class="badge bg-warning text-dark">{{ number_format($aufschlagProzent, 2, ',', '.') }}%</span></td>
                            </tr>
                            <tr>
                                <th>Danach:</th>
                                <td><span class="badge bg-primary">{{ number_format($globalerAufschlag, 2, ',', '.') }}%</span></td>
                            </tr>
                        </table>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                        <button type="submit" class="btn btn-danger">Entfernen</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    {{-- Modal: Vorschau --}}
    <div class="modal fade" id="modalAufschlagVorschau" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">Preis-Vorschau</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        Aktueller Aufschlag: <strong>{{ number_format($aufschlagProzent, 2, ',', '.') }}%</strong>
                    </div>

                    @if($gebaeude->aktiveArtikel->isNotEmpty())
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Artikel</th>
                                        <th class="text-end">Original</th>
                                        <th class="text-end">Aufschlag</th>
                                        <th class="text-end">Neu</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $summeOriginal = 0; $summeNeu = 0; @endphp
                                    @foreach($gebaeude->aktiveArtikel as $artikel)
                                        @php
                                            $originalPreis = (float) $artikel->einzelpreis;
                                            $aufschlagBetrag = round($originalPreis * ($aufschlagProzent / 100), 2);
                                            $neuerPreis = round($originalPreis + $aufschlagBetrag, 2);
                                            $summeOriginal += $originalPreis;
                                            $summeNeu += $neuerPreis;
                                        @endphp
                                        <tr>
                                            <td>{{ $artikel->beschreibung }}</td>
                                            <td class="text-end">{{ number_format($originalPreis, 2, ',', '.') }} EUR</td>
                                            <td class="text-end">
                                                @if($aufschlagBetrag > 0)
                                                    <span class="text-success">+{{ number_format($aufschlagBetrag, 2, ',', '.') }}</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td class="text-end"><strong>{{ number_format($neuerPreis, 2, ',', '.') }} EUR</strong></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr class="fw-bold">
                                        <th>Summe (Netto)</th>
                                        <th class="text-end">{{ number_format($summeOriginal, 2, ',', '.') }} EUR</th>
                                        <th class="text-end">
                                            @php $differenz = $summeNeu - $summeOriginal; @endphp
                                            <span class="text-success">+{{ number_format($differenz, 2, ',', '.') }}</span>
                                        </th>
                                        <th class="text-end">{{ number_format($summeNeu, 2, ',', '.') }} EUR</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-warning">Keine aktiven Artikel vorhanden.</div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Schliessen</button>
                </div>
            </div>
        </div>
    </div>

@else
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i>
        Der Preis-Aufschlag kann erst nach dem Erstellen des Gebaeudes verwaltet werden.
    </div>
@endif
