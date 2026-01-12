{{--
════════════════════════════════════════════════════════════════════════════
DATEI: gebaeude-bearbeiten.blade.php
PFAD:  resources/views/livewire/mitarbeiter/gebaeude-bearbeiten.blade.php
════════════════════════════════════════════════════════════════════════════
--}}

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

    {{-- Error Alert --}}
    @if(session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle"></i>
            {{ session('error') }}
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

    {{-- LISTE (nur wenn kein Modal offen) --}}
    @if(empty($skipList))

        {{-- Filter Card --}}
        <div class="card mb-3">
            <div class="card-body py-2">
                <div class="row g-2">
                    {{-- Suchbegriff --}}
                    <div class="col-12 col-md-6">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">
                                <i class="bi bi-search"></i>
                            </span>
                            <input 
                                type="text" 
                                class="form-control" 
                                placeholder="Codex, Name, Straße, Ort..." 
                                wire:model.live.debounce.500ms="suchbegriff"
                            >
                            @if($suchbegriff)
                                <button class="btn btn-outline-secondary" wire:click="$set('suchbegriff', '')">
                                    <i class="bi bi-x"></i>
                                </button>
                            @endif
                        </div>
                    </div>

                    {{-- Tour-Filter --}}
                    <div class="col-9 col-md-4">
                        <select class="form-select form-select-sm" wire:model.live="filterTour">
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

        {{-- Gebäude-Liste --}}
        <div class="card">
            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                <span>
                    <i class="bi bi-list-ul"></i> Gebäude
                    <span class="badge bg-primary ms-1">{{ $gebaeudeListe->total() }}</span>
                </span>
                <div wire:loading.delay class="spinner-border spinner-border-sm text-primary"></div>
            </div>
            <div class="card-body p-0">
                @if($gebaeudeListe->count() > 0)
                    <div class="list-group list-group-flush">
                        @foreach($gebaeudeListe as $geb)
                            <div class="list-group-item p-2 p-md-3">
                                <div class="d-flex justify-content-between align-items-start">
                                    {{-- Gebäude-Info --}}
                                    <div class="flex-grow-1 me-2">
                                        <div class="d-flex align-items-center mb-1">
                                            <strong class="me-2">{{ $geb->codex }}</strong>
                                            @if($geb->hat_ausstehende ?? false)
                                                <span class="badge bg-warning text-dark" title="Ausstehende Änderungen">
                                                    <i class="bi bi-clock-history"></i>
                                                </span>
                                            @endif
                                        </div>
                                        
                                        @if($geb->gebaeude_name)
                                            <div class="text-muted small mb-1">{{ $geb->gebaeude_name }}</div>
                                        @endif
                                        
                                        {{-- Adresse mit Maps-Link --}}
                                        @php
                                            $adresse = $geb->strasse . ' ' . $geb->hausnummer . ', ' . $geb->plz . ' ' . $geb->wohnort;
                                            $mapsUrl = 'https://www.google.com/maps/search/?api=1&query=' . urlencode($adresse);
                                        @endphp
                                        <div class="small mb-1">
                                            <a href="{{ $mapsUrl }}" target="_blank" class="text-decoration-none text-dark">
                                                <i class="bi bi-geo-alt text-danger"></i> {{ $adresse }}
                                                <i class="bi bi-box-arrow-up-right small text-muted"></i>
                                            </a>
                                        </div>

                                        {{-- Touren --}}
                                        @if($geb->touren->count() > 0)
                                            <div class="small text-muted">
                                                <i class="bi bi-map"></i>
                                                {{ $geb->touren->pluck('name')->implode(', ') }}
                                            </div>
                                        @endif

                                        {{-- Kontaktdaten --}}
                                        @if($geb->telefon || $geb->handy || $geb->email)
                                            <div class="small text-muted mt-1">
                                                @if($geb->telefon)
                                                    <i class="bi bi-telephone"></i> {{ $geb->telefon }}
                                                @endif
                                                @if($geb->handy)
                                                    <i class="bi bi-phone ms-2"></i> {{ $geb->handy }}
                                                @endif
                                                @if($geb->email)
                                                    <br><i class="bi bi-envelope"></i> {{ $geb->email }}
                                                @endif
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
                    @if($gebaeudeListe->hasPages())
                        <div class="p-3">
                            {{ $gebaeudeListe->links() }}
                        </div>
                    @endif
                @else
                    <div class="p-4 text-center text-muted">
                        <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                        <p class="mt-2 mb-0">Keine Gebäude gefunden</p>
                    </div>
                @endif
            </div>
        </div>

    @endif

    {{-- Modal: Gebäude bearbeiten --}}
    @if($showModal && $gebaeude)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" wire:keydown.escape="modalSchliessen">
            <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    {{-- Modal Header --}}
                    <div class="modal-header bg-primary text-white py-2">
                        <h5 class="modal-title">
                            <i class="bi bi-pencil"></i>
                            {{ $gebaeude->gebaeude_name ?: $gebaeude->codex }} bearbeiten
                        </h5>
                        <button type="button" class="btn-close btn-close-white" wire:click="modalSchliessen"></button>
                    </div>

                    {{-- Modal Body --}}
                    <div class="modal-body">
                        <div class="alert alert-info py-2 mb-3">
                            <small><i class="bi bi-info-circle"></i> Änderungen müssen von einem Admin genehmigt werden.</small>
                        </div>

                        {{-- Basis-Daten --}}
                        <h6 class="text-primary mb-2"><i class="bi bi-building"></i> Grunddaten</h6>
                        <div class="row g-2 mb-3">
                            <div class="col-4">
                                <label class="form-label small">Codex *</label>
                                <input type="text" class="form-control form-control-sm @error('codex') is-invalid @enderror" wire:model="codex">
                                @error('codex')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-8">
                                <label class="form-label small">Gebäudename</label>
                                <input type="text" class="form-control form-control-sm" wire:model="gebaeude_name">
                            </div>
                        </div>

                        {{-- Adresse --}}
                        <h6 class="text-primary mb-2"><i class="bi bi-geo-alt"></i> Adresse</h6>
                        <div class="row g-2 mb-3">
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
                                <label class="form-label small">Ort *</label>
                                <input type="text" class="form-control form-control-sm @error('wohnort') is-invalid @enderror" wire:model="wohnort">
                                @error('wohnort')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-3">
                                <label class="form-label small">Land</label>
                                <select class="form-select form-select-sm" wire:model="land">
                                    <option value="IT">IT</option>
                                    <option value="AT">AT</option>
                                    <option value="DE">DE</option>
                                    <option value="CH">CH</option>
                                </select>
                            </div>
                        </div>

                        {{-- Kontakt-Daten --}}
                        <h6 class="text-primary mb-2"><i class="bi bi-telephone"></i> Kontakt</h6>
                        <div class="row g-2 mb-3">
                            <div class="col-6 col-md-4">
                                <label class="form-label small">Telefon</label>
                                <input type="text" class="form-control form-control-sm" wire:model="telefon">
                            </div>
                            <div class="col-6 col-md-4">
                                <label class="form-label small">Handy</label>
                                <input type="text" class="form-control form-control-sm" wire:model="handy">
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label small">E-Mail</label>
                                <input type="email" class="form-control form-control-sm @error('email') is-invalid @enderror" wire:model="email">
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        {{-- Reinigung --}}
                        <h6 class="text-success mb-2"><i class="bi bi-calendar-check"></i> Reinigungsplan</h6>
                        <div class="mb-2">
                            <label class="form-label small">Geplante Reinigungen/Jahr</label>
                            <input type="number" class="form-control form-control-sm" wire:model="geplante_reinigungen" min="0" max="365" style="width: 100px;">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small d-block">Aktive Monate</label>
                            <div class="row g-1">
                                @foreach($monate as $num => $name)
                                    @php $monatKey = 'm' . str_pad($num, 2, '0', STR_PAD_LEFT); @endphp
                                    <div class="col-4 col-md-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="modal_{{ $monatKey }}" wire:model="{{ $monatKey }}">
                                            <label class="form-check-label small" for="modal_{{ $monatKey }}">{{ $name }}</label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- Touren --}}
                        @if($touren->count() > 0)
                            <h6 class="text-warning mb-2"><i class="bi bi-map"></i> Touren</h6>
                            <div class="row g-1 mb-3">
                                @foreach($touren as $tour)
                                    <div class="col-6 col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="modal_tour_{{ $tour->id }}" value="{{ $tour->id }}" wire:model="selectedTouren">
                                            <label class="form-check-label small" for="modal_tour_{{ $tour->id }}">{{ $tour->name }}</label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        {{-- Bemerkungen --}}
                        <h6 class="text-info mb-2"><i class="bi bi-chat-left-text"></i> Bemerkungen</h6>
                        <div class="mb-2">
                            <label class="form-label small">Bemerkung zum Gebäude</label>
                            <textarea class="form-control form-control-sm" wire:model="bemerkung" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Nachricht an Admin</label>
                            <textarea class="form-control form-control-sm" wire:model="bemerkung_mitarbeiter" rows="2" placeholder="Begründung der Änderung..."></textarea>
                        </div>
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
                                <span class="spinner-border spinner-border-sm"></span>
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
