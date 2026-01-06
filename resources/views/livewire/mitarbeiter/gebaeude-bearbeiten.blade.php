{{-- resources/views/livewire/mitarbeiter/gebaeude-bearbeiten.blade.php --}}

<div class="container-fluid py-2 py-md-4">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">
            <i class="bi bi-building text-primary"></i>
            Gebäude bearbeiten
        </h1>
        <a href="{{ route('mitarbeiter.dashboard') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i>
            <span class="d-none d-md-inline">Zurück</span>
        </a>
    </div>

    {{-- Success Alert --}}
    @if(session()->has('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Info-Box --}}
    <div class="alert alert-info mb-3" role="alert">
        <i class="bi bi-info-circle"></i>
        <small>
            <strong>Hinweis:</strong> Änderungen an Gebäuden müssen von einem Admin freigegeben werden.
        </small>
    </div>

    {{-- Filter Card --}}
    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-2">
                {{-- Suchbegriff --}}
                <div class="col-12 col-md-6">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-search"></i>
                        </span>
                        <input 
                            type="text" 
                            class="form-control" 
                            placeholder="Suche nach Codex, Name, Straße..." 
                            wire:model.live.debounce.300ms="suchbegriff"
                        >
                    </div>
                </div>

                {{-- Tour-Filter --}}
                <div class="col-9 col-md-4">
                    <select class="form-select" wire:model.live="filterTour">
                        <option value="">Alle Touren</option>
                        @foreach($touren as $tour)
                            <option value="{{ $tour->id }}">{{ $tour->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Filter zurücksetzen --}}
                <div class="col-3 col-md-2">
                    <button 
                        type="button" 
                        class="btn btn-outline-secondary w-100" 
                        wire:click="filterZuruecksetzen"
                        title="Filter zurücksetzen"
                    >
                        <i class="bi bi-x-circle"></i>
                        <span class="d-none d-md-inline">Reset</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Gebäude-Liste --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>
                <i class="bi bi-list-ul"></i> Gebäude
                <span class="badge bg-primary ms-1">{{ $gebaeudeListe->total() }}</span>
            </span>
        </div>
        <div class="card-body p-0">
            @if($gebaeudeListe->count() > 0)
                <div class="list-group list-group-flush">
                    @foreach($gebaeudeListe as $geb)
                        <div class="list-group-item list-group-item-action p-2 p-md-3">
                            <div class="d-flex justify-content-between align-items-start">
                                {{-- Gebäude-Info --}}
                                <div class="flex-grow-1 me-2">
                                    <div class="d-flex align-items-center mb-1">
                                        <strong class="me-2">{{ $geb->codex }}</strong>
                                        @if($geb->hatAusstehendeAenderungen())
                                            <span class="badge bg-warning text-dark" title="Ausstehende Änderungen">
                                                <i class="bi bi-clock-history"></i>
                                            </span>
                                        @endif
                                    </div>
                                    
                                    @if($geb->gebaeude_name)
                                        <div class="text-muted small mb-1">{{ $geb->gebaeude_name }}</div>
                                    @endif
                                    
                                    <div class="small">
                                        <i class="bi bi-geo-alt"></i>
                                        {{ $geb->strasse }} {{ $geb->hausnummer }}, 
                                        {{ $geb->plz }} {{ $geb->wohnort }}
                                    </div>

                                    {{-- Touren (nur Desktop) --}}
                                    @if($geb->touren->count() > 0)
                                        <div class="small text-muted mt-1 d-none d-md-block">
                                            <i class="bi bi-map"></i>
                                            {{ $geb->touren->pluck('name')->implode(', ') }}
                                        </div>
                                    @endif

                                    {{-- Kontaktdaten (wenn vorhanden) --}}
                                    @if($geb->hatKontaktdaten())
                                        <div class="small text-muted mt-1">
                                            {{ $geb->kontaktdaten_formatiert }}
                                        </div>
                                    @endif
                                </div>

                                {{-- Bearbeiten Button --}}
                                <div class="flex-shrink-0">
                                    <button 
                                        type="button" 
                                        class="btn btn-sm btn-outline-primary"
                                        wire:click="bearbeiten({{ $geb->id }})"
                                        title="Bearbeiten"
                                    >
                                        <i class="bi bi-pencil"></i>
                                        <span class="d-none d-md-inline ms-1">Bearbeiten</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Pagination --}}
                <div class="p-3">
                    {{ $gebaeudeListe->links() }}
                </div>
            @else
                <div class="p-4 text-center text-muted">
                    <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                    <p class="mt-2 mb-0">Keine Gebäude gefunden</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Modal: Gebäude bearbeiten --}}
    @if($showModal && $gebaeude)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-scrollable modal-lg">
                <div class="modal-content">
                    {{-- Modal Header --}}
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">
                            <i class="bi bi-pencil"></i>
                            Änderung vorschlagen: {{ $gebaeude->codex }}
                        </h5>
                        <button type="button" class="btn-close btn-close-white" wire:click="modalSchliessen"></button>
                    </div>

                    {{-- Modal Body --}}
                    <div class="modal-body">
                        <form wire:submit.prevent="aenderungVorschlagen">
                            {{-- Basis-Daten --}}
                            <div class="mb-3">
                                <h6 class="text-primary"><i class="bi bi-building"></i> Basis-Daten</h6>
                                <div class="row g-2">
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small">Codex *</label>
                                        <input type="text" class="form-control form-control-sm @error('codex') is-invalid @enderror" wire:model="codex">
                                        @error('codex')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small">Gebäude-Name</label>
                                        <input type="text" class="form-control form-control-sm" wire:model="gebaeude_name">
                                    </div>
                                    <div class="col-8">
                                        <label class="form-label small">Straße *</label>
                                        <input type="text" class="form-control form-control-sm @error('strasse') is-invalid @enderror" wire:model="strasse">
                                        @error('strasse')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-4">
                                        <label class="form-label small">Nr. *</label>
                                        <input type="text" class="form-control form-control-sm @error('hausnummer') is-invalid @enderror" wire:model="hausnummer">
                                        @error('hausnummer')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-4">
                                        <label class="form-label small">PLZ *</label>
                                        <input type="text" class="form-control form-control-sm @error('plz') is-invalid @enderror" wire:model="plz">
                                        @error('plz')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-5">
                                        <label class="form-label small">Wohnort *</label>
                                        <input type="text" class="form-control form-control-sm @error('wohnort') is-invalid @enderror" wire:model="wohnort">
                                        @error('wohnort')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-3">
                                        <label class="form-label small">Land *</label>
                                        <select class="form-select form-select-sm" wire:model="land">
                                            <option value="IT">IT</option>
                                            <option value="AT">AT</option>
                                            <option value="DE">DE</option>
                                            <option value="CH">CH</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            {{-- Kontakt-Daten --}}
                            <div class="mb-3">
                                <h6 class="text-secondary"><i class="bi bi-telephone"></i> Kontakt-Daten</h6>
                                <div class="row g-2">
                                    <div class="col-12 col-md-4">
                                        <label class="form-label small">Telefon</label>
                                        <input type="text" class="form-control form-control-sm" wire:model="telefon">
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label class="form-label small">Handy</label>
                                        <input type="text" class="form-control form-control-sm" wire:model="handy">
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label class="form-label small">E-Mail</label>
                                        <input type="email" class="form-control form-control-sm @error('email') is-invalid @enderror" wire:model="email">
                                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                            </div>

                            {{-- Reinigung --}}
                            <div class="mb-3">
                                <h6 class="text-success"><i class="bi bi-calendar-check"></i> Reinigungsplan</h6>
                                <div class="mb-2">
                                    <label class="form-label small">Geplante Reinigungen/Jahr</label>
                                    <input type="number" class="form-control form-control-sm" wire:model="geplante_reinigungen" min="0" max="365">
                                </div>
                                <div>
                                    <label class="form-label small d-block">Aktive Monate</label>
                                    <div class="row g-1">
                                        @foreach($monate as $num => $name)
                                            <div class="col-6 col-md-3 col-lg-2">
                                                <div class="form-check form-check-inline">
                                                    <input 
                                                        class="form-check-input" 
                                                        type="checkbox" 
                                                        id="modal_m{{ str_pad($num, 2, '0', STR_PAD_LEFT) }}"
                                                        wire:model="m{{ str_pad($num, 2, '0', STR_PAD_LEFT) }}"
                                                    >
                                                    <label class="form-check-label small" for="modal_m{{ str_pad($num, 2, '0', STR_PAD_LEFT) }}">
                                                        {{ $name }}
                                                    </label>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            {{-- Touren --}}
                            <div class="mb-3">
                                <h6 class="text-warning"><i class="bi bi-map"></i> Touren</h6>
                                @if($touren->count() > 0)
                                    <div class="row g-2">
                                        @foreach($touren as $tour)
                                            <div class="col-6 col-md-4">
                                                <div class="form-check">
                                                    <input 
                                                        class="form-check-input" 
                                                        type="checkbox" 
                                                        id="modal_tour_{{ $tour->id }}"
                                                        value="{{ $tour->id }}"
                                                        wire:model="selectedTouren"
                                                    >
                                                    <label class="form-check-label small" for="modal_tour_{{ $tour->id }}">
                                                        {{ $tour->name }}
                                                    </label>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            {{-- Bemerkungen --}}
                            <div class="mb-3">
                                <h6 class="text-info"><i class="bi bi-chat-left-text"></i> Bemerkungen</h6>
                                <div class="mb-2">
                                    <label class="form-label small">Bemerkung zum Gebäude</label>
                                    <textarea class="form-control form-control-sm" wire:model="bemerkung" rows="2"></textarea>
                                </div>
                                <div>
                                    <label class="form-label small">Nachricht an Admin</label>
                                    <textarea class="form-control form-control-sm" wire:model="bemerkung_mitarbeiter" rows="2" placeholder="Begründung der Änderung..."></textarea>
                                </div>
                            </div>
                        </form>
                    </div>

                    {{-- Modal Footer --}}
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="modalSchliessen">
                            <i class="bi bi-x-circle"></i> Abbrechen
                        </button>
                        <button type="button" class="btn btn-primary" wire:click="aenderungVorschlagen" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="aenderungVorschlagen">
                                <i class="bi bi-send"></i> Änderung vorschlagen
                            </span>
                            <span wire:loading wire:target="aenderungVorschlagen">
                                <span class="spinner-border spinner-border-sm"></span> Speichern...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
