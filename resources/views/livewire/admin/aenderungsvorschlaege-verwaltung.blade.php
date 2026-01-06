{{-- resources/views/livewire/admin/aenderungsvorschlaege-verwaltung.blade.php --}}

<div class="container-fluid py-2 py-md-4">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">
            <i class="bi bi-clipboard-check text-primary"></i>
            Änderungsvorschläge
        </h1>
        <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i>
            <span class="d-none d-md-inline">Dashboard</span>
        </a>
    </div>

    {{-- Success/Error Alerts --}}
    @if(session()->has('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Statistik-Karten --}}
    <div class="row g-2 mb-3">
        <div class="col-4 col-md-4">
            <div class="card border-warning h-100">
                <div class="card-body text-center py-2 py-md-3">
                    <h2 class="h3 h2-md fw-bold text-warning mb-0">{{ $stats['pending'] }}</h2>
                    <small class="text-muted d-none d-md-inline">Ausstehend</small>
                    <small class="text-muted d-md-none" style="font-size: 0.7rem;">Offen</small>
                </div>
            </div>
        </div>
        <div class="col-4 col-md-4">
            <div class="card border-primary h-100">
                <div class="card-body text-center py-2 py-md-3">
                    <h2 class="h3 h2-md fw-bold text-primary mb-0">{{ $stats['neue_gebaeude'] }}</h2>
                    <small class="text-muted d-none d-md-inline">Neue Gebäude</small>
                    <small class="text-muted d-md-none" style="font-size: 0.7rem;">Neu</small>
                </div>
            </div>
        </div>
        <div class="col-4 col-md-4">
            <div class="card border-info h-100">
                <div class="card-body text-center py-2 py-md-3">
                    <h2 class="h3 h2-md fw-bold text-info mb-0">{{ $stats['aenderungen'] }}</h2>
                    <small class="text-muted d-none d-md-inline">Änderungen</small>
                    <small class="text-muted d-md-none" style="font-size: 0.7rem;">Änd.</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Card --}}
    <div class="card mb-3">
        <div class="card-header">
            <i class="bi bi-funnel"></i> Filter
        </div>
        <div class="card-body">
            <div class="row g-2">
                {{-- Typ --}}
                <div class="col-6 col-md-3">
                    <select class="form-select form-select-sm" wire:model.live="filterTyp">
                        <option value="">Alle Typen</option>
                        <option value="neu">Neue Gebäude</option>
                        <option value="aenderung">Änderungen</option>
                    </select>
                </div>

                {{-- Status --}}
                <div class="col-6 col-md-3">
                    <select class="form-select form-select-sm" wire:model.live="filterStatus">
                        <option value="">Alle Status</option>
                        <option value="pending">Ausstehend</option>
                        <option value="approved">Genehmigt</option>
                        <option value="rejected">Abgelehnt</option>
                    </select>
                </div>

                {{-- Mitarbeiter --}}
                <div class="col-9 col-md-5">
                    <select class="form-select form-select-sm" wire:model.live="filterMitarbeiter">
                        <option value="">Alle Mitarbeiter</option>
                        @foreach($mitarbeiter as $ma)
                            <option value="{{ $ma->id }}">{{ $ma->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Reset --}}
                <div class="col-3 col-md-1">
                    <button 
                        type="button" 
                        class="btn btn-sm btn-outline-secondary w-100" 
                        wire:click="filterZuruecksetzen"
                        title="Filter zurücksetzen"
                    >
                        <i class="bi bi-x-circle"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Vorschläge-Liste --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>
                <i class="bi bi-list-ul"></i> Vorschläge
                <span class="badge bg-primary ms-1">{{ $vorschlaege->total() }}</span>
            </span>
        </div>
        <div class="card-body p-0">
            @if($vorschlaege->count() > 0)
                <div class="list-group list-group-flush">
                    @foreach($vorschlaege as $vorschlag)
                        <div class="list-group-item p-2 p-md-3">
                            <div class="d-flex justify-content-between align-items-start">
                                {{-- Vorschlag-Info --}}
                                <div class="flex-grow-1 me-2">
                                    {{-- Typ & Status --}}
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        {!! $vorschlag->typ_badge !!}
                                        {!! $vorschlag->status_badge !!}
                                        
                                        @if($vorschlag->anzahl_aenderungen > 0)
                                            <span class="badge bg-secondary">
                                                {{ $vorschlag->anzahl_aenderungen }} Feld(er)
                                            </span>
                                        @endif
                                    </div>

                                    {{-- Gebäude-Info --}}
                                    <div class="mb-1">
                                        @if($vorschlag->gebaeude)
                                            <strong>{{ $vorschlag->gebaeude->codex }}</strong>
                                            @if($vorschlag->gebaeude->gebaeude_name)
                                                <span class="text-muted">- {{ $vorschlag->gebaeude->gebaeude_name }}</span>
                                            @endif
                                        @else
                                            <strong>{{ $vorschlag->neue_daten['codex'] ?? 'N/A' }}</strong>
                                            @if(!empty($vorschlag->neue_daten['gebaeude_name']))
                                                <span class="text-muted">- {{ $vorschlag->neue_daten['gebaeude_name'] }}</span>
                                            @endif
                                        @endif
                                    </div>

                                    {{-- Ersteller & Datum --}}
                                    <div class="small text-muted">
                                        <i class="bi bi-person"></i>
                                        {{ $vorschlag->ersteller->name }}
                                        <span class="d-none d-md-inline">•</span>
                                        <br class="d-md-none">
                                        <i class="bi bi-calendar"></i>
                                        {{ $vorschlag->erstellt_am_formatiert }}
                                    </div>

                                    {{-- Bemerkung (wenn vorhanden) --}}
                                    @if($vorschlag->bemerkung)
                                        <div class="small text-muted mt-1">
                                            <i class="bi bi-chat-left-text"></i>
                                            {{ Str::limit($vorschlag->bemerkung, 100) }}
                                        </div>
                                    @endif

                                    {{-- Bearbeitet von (wenn nicht pending) --}}
                                    @if(!$vorschlag->istPending() && $vorschlag->bearbeiter)
                                        <div class="small text-muted mt-1">
                                            <i class="bi bi-check2-circle"></i>
                                            Bearbeitet von {{ $vorschlag->bearbeiter->name }}
                                            am {{ $vorschlag->bearbeitet_am_formatiert }}
                                        </div>
                                    @endif

                                    {{-- Ablehnungsgrund --}}
                                    @if($vorschlag->istAbgelehnt() && $vorschlag->ablehnungsgrund)
                                        <div class="alert alert-danger p-2 mt-2 mb-0">
                                            <small>
                                                <i class="bi bi-exclamation-triangle"></i>
                                                <strong>Grund:</strong> {{ $vorschlag->ablehnungsgrund }}
                                            </small>
                                        </div>
                                    @endif
                                </div>

                                {{-- Aktionen --}}
                                <div class="flex-shrink-0">
                                    <div class="d-flex flex-column gap-1">
                                        {{-- Details --}}
                                        <button 
                                            type="button" 
                                            class="btn btn-sm btn-outline-primary"
                                            wire:click="detailsAnzeigen({{ $vorschlag->id }})"
                                            title="Details anzeigen"
                                        >
                                            <i class="bi bi-eye"></i>
                                            <span class="d-none d-md-inline ms-1">Details</span>
                                        </button>

                                        {{-- Genehmigen (nur pending) --}}
                                        @if($vorschlag->istPending())
                                            <button 
                                                type="button" 
                                                class="btn btn-sm btn-success"
                                                wire:click="genehmigen({{ $vorschlag->id }})"
                                                wire:confirm="Vorschlag wirklich genehmigen?"
                                                title="Genehmigen"
                                            >
                                                <i class="bi bi-check-circle"></i>
                                                <span class="d-none d-lg-inline ms-1">OK</span>
                                            </button>

                                            <button 
                                                type="button" 
                                                class="btn btn-sm btn-danger"
                                                wire:click="ablehnenModalOeffnen({{ $vorschlag->id }})"
                                                title="Ablehnen"
                                            >
                                                <i class="bi bi-x-circle"></i>
                                                <span class="d-none d-lg-inline ms-1">Ablehnen</span>
                                            </button>
                                        @endif

                                        {{-- Löschen (nur nicht-pending) --}}
                                        @if(!$vorschlag->istPending())
                                            <button 
                                                type="button" 
                                                class="btn btn-sm btn-outline-danger"
                                                wire:click="loeschen({{ $vorschlag->id }})"
                                                wire:confirm="Vorschlag wirklich löschen?"
                                                title="Löschen"
                                            >
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Pagination --}}
                <div class="p-3">
                    {{ $vorschlaege->links() }}
                </div>
            @else
                <div class="p-4 text-center text-muted">
                    <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                    <p class="mt-2 mb-0">Keine Vorschläge gefunden</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Modal: Details anzeigen --}}
    @if($showDetailModal && $selectedVorschlag)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-scrollable modal-xl">
                <div class="modal-content">
                    {{-- Modal Header --}}
                    <div class="modal-header {{ $selectedVorschlag->istNeuesGebaeude() ? 'bg-primary' : 'bg-info' }} text-white">
                        <h5 class="modal-title">
                            {!! $selectedVorschlag->typ_badge !!}
                            @if($selectedVorschlag->gebaeude)
                                {{ $selectedVorschlag->gebaeude->codex }}
                            @else
                                {{ $selectedVorschlag->neue_daten['codex'] ?? 'Neues Gebäude' }}
                            @endif
                        </h5>
                        <button type="button" class="btn-close btn-close-white" wire:click="detailModalSchliessen"></button>
                    </div>

                    {{-- Modal Body --}}
                    <div class="modal-body">
                        {{-- Meta-Informationen --}}
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="row g-2">
                                    <div class="col-12 col-md-6">
                                        <small class="text-muted d-block">Erstellt von</small>
                                        <strong>{{ $selectedVorschlag->ersteller->name }}</strong>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <small class="text-muted d-block">Erstellt am</small>
                                        <strong>{{ $selectedVorschlag->erstellt_am_formatiert }}</strong>
                                    </div>
                                    @if($selectedVorschlag->bemerkung)
                                        <div class="col-12">
                                            <small class="text-muted d-block">Bemerkung</small>
                                            <p class="mb-0">{{ $selectedVorschlag->bemerkung }}</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Daten-Vergleich --}}
                        @if($selectedVorschlag->istAenderung() && $selectedVorschlag->geaenderte_felder)
                            <h6 class="text-primary mb-3">
                                <i class="bi bi-arrow-left-right"></i>
                                Geänderte Felder ({{ count($selectedVorschlag->geaenderte_felder) }})
                            </h6>

                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th style="width: 30%;">Feld</th>
                                            <th style="width: 35%;">Alt</th>
                                            <th style="width: 35%;">Neu</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($selectedVorschlag->geaenderte_felder as $feld => $werte)
                                            <tr>
                                                <td><strong>{{ ucfirst(str_replace('_', ' ', $feld)) }}</strong></td>
                                                <td>
                                                    @if(is_bool($werte['alt']))
                                                        <span class="badge {{ $werte['alt'] ? 'bg-success' : 'bg-secondary' }}">
                                                            {{ $werte['alt'] ? 'Ja' : 'Nein' }}
                                                        </span>
                                                    @elseif(is_null($werte['alt']))
                                                        <span class="text-muted">-</span>
                                                    @else
                                                        {{ $werte['alt'] }}
                                                    @endif
                                                </td>
                                                <td class="text-primary">
                                                    @if(is_bool($werte['neu']))
                                                        <span class="badge {{ $werte['neu'] ? 'bg-success' : 'bg-secondary' }}">
                                                            {{ $werte['neu'] ? 'Ja' : 'Nein' }}
                                                        </span>
                                                    @elseif(is_null($werte['neu']))
                                                        <span class="text-muted">-</span>
                                                    @else
                                                        <strong>{{ $werte['neu'] }}</strong>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            {{-- Alle Daten bei neuem Gebäude --}}
                            <h6 class="text-primary mb-3">
                                <i class="bi bi-building-add"></i>
                                Gebäude-Daten
                            </h6>

                            <div class="row g-2">
                                @foreach($selectedVorschlag->neue_daten as $feld => $wert)
                                    <div class="col-6 col-md-4">
                                        <small class="text-muted d-block">{{ ucfirst(str_replace('_', ' ', $feld)) }}</small>
                                        @if(is_bool($wert))
                                            <span class="badge {{ $wert ? 'bg-success' : 'bg-secondary' }}">
                                                {{ $wert ? 'Ja' : 'Nein' }}
                                            </span>
                                        @elseif(is_null($wert))
                                            <span class="text-muted">-</span>
                                        @else
                                            <strong>{{ $wert }}</strong>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- Modal Footer --}}
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="detailModalSchliessen">
                            <i class="bi bi-x-circle"></i> Schließen
                        </button>

                        @if($selectedVorschlag->istPending())
                            <button 
                                type="button" 
                                class="btn btn-danger" 
                                wire:click="ablehnenModalOeffnen({{ $selectedVorschlag->id }})"
                            >
                                <i class="bi bi-x-circle"></i> Ablehnen
                            </button>
                            <button 
                                type="button" 
                                class="btn btn-success" 
                                wire:click="genehmigen({{ $selectedVorschlag->id }})"
                                wire:confirm="Vorschlag wirklich genehmigen?"
                            >
                                <i class="bi bi-check-circle"></i> Genehmigen
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal: Ablehnen --}}
    @if($showAblehnenModal)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5); z-index: 1060;">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    {{-- Modal Header --}}
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">
                            <i class="bi bi-x-circle"></i>
                            Vorschlag ablehnen
                        </h5>
                        <button type="button" class="btn-close btn-close-white" wire:click="ablehnenModalSchliessen"></button>
                    </div>

                    {{-- Modal Body --}}
                    <div class="modal-body">
                        <form wire:submit.prevent="ablehnen">
                            <div class="mb-3">
                                <label for="ablehnungsgrund" class="form-label">
                                    Ablehnungsgrund <span class="text-danger">*</span>
                                </label>
                                <textarea 
                                    class="form-control @error('ablehnungsgrund') is-invalid @enderror" 
                                    id="ablehnungsgrund"
                                    wire:model="ablehnungsgrund"
                                    rows="4"
                                    placeholder="Bitte Grund für Ablehnung angeben..."
                                    required
                                ></textarea>
                                @error('ablehnungsgrund')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </form>
                    </div>

                    {{-- Modal Footer --}}
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="ablehnenModalSchliessen">
                            <i class="bi bi-arrow-left"></i> Abbrechen
                        </button>
                        <button type="button" class="btn btn-danger" wire:click="ablehnen" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="ablehnen">
                                <i class="bi bi-x-circle"></i> Ablehnen
                            </span>
                            <span wire:loading wire:target="ablehnen">
                                <span class="spinner-border spinner-border-sm"></span> Ablehnen...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
