{{--
════════════════════════════════════════════════════════════════════════════
DATEI: reinigungsplanung.blade.php
PFAD:  resources/views/livewire/mitarbeiter/reinigungsplanung.blade.php
════════════════════════════════════════════════════════════════════════════
--}}

<div class="container-fluid py-2 py-md-4">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-1">
                <i class="bi bi-calendar-check text-primary"></i>
                Reinigungsplanung
            </h1>
            <p class="text-muted mb-0 small">
                @if(!empty($filterMonat))
                    {{ $monate[$filterMonat] ?? '' }} {{ now()->year }}
                @else
                    Alle Monate
                @endif
            </p>
        </div>
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

    {{-- LISTE (nur wenn kein Modal offen) --}}
    @if(empty($skipList))
    
        {{-- Statistik --}}
        <div class="row g-2 mb-3">
            <div class="col-4">
                <div class="card border-primary h-100">
                    <div class="card-body text-center py-2">
                        <h2 class="h3 fw-bold text-primary mb-0">{{ $stats['gesamt'] }}</h2>
                        <small class="text-muted">Gesamt</small>
                    </div>
                </div>
            </div>
            <div class="col-4">
                <div class="card border-warning h-100">
                    <div class="card-body text-center py-2">
                        <h2 class="h3 fw-bold text-warning mb-0">{{ $stats['offen'] }}</h2>
                        <small class="text-muted">Offen</small>
                    </div>
                </div>
            </div>
            <div class="col-4">
                <div class="card border-success h-100">
                    <div class="card-body text-center py-2">
                        <h2 class="h3 fw-bold text-success mb-0">{{ $stats['erledigt'] }}</h2>
                        <small class="text-muted">Erledigt</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filter --}}
        <div class="card mb-3">
            <div class="card-header py-2">
                <i class="bi bi-funnel"></i> Filter
                <div wire:loading.delay class="spinner-border spinner-border-sm text-primary ms-2"></div>
            </div>
            <div class="card-body py-2">
                <div class="row g-2">
                    <div class="col-6 col-md-2">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><i class="bi bi-upc"></i></span>
                            <input type="text" class="form-control" placeholder="Codex" 
                                   wire:model.live.debounce.500ms="filterCodex">
                            @if($filterCodex)
                                <button class="btn btn-outline-secondary" wire:click="$set('filterCodex', '')">
                                    <i class="bi bi-x"></i>
                                </button>
                            @endif
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control" placeholder="Name, Straße, Ort..." 
                                   wire:model.live.debounce.500ms="suchbegriff">
                            @if($suchbegriff)
                                <button class="btn btn-outline-secondary" wire:click="$set('suchbegriff', '')">
                                    <i class="bi bi-x"></i>
                                </button>
                            @endif
                        </div>
                    </div>
                    <div class="col-4 col-md-2">
                        <select class="form-select form-select-sm" wire:model.live="filterMonat">
                            <option value="">Alle</option>
                            @foreach($monate as $num => $name)
                                <option value="{{ $num }}">{{ substr($name, 0, 3) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-4 col-md-2">
                        <select class="form-select form-select-sm" wire:model.live="filterTour">
                            <option value="">Tour</option>
                            @foreach($touren as $tour)
                                <option value="{{ $tour->id }}">{{ $tour->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-4 col-md-2">
                        <select class="form-select form-select-sm" wire:model.live="filterStatus">
                            <option value="">Status</option>
                            <option value="offen">Offen</option>
                            <option value="erledigt">Erledigt</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-1">
                        <button class="btn btn-sm btn-outline-secondary w-100" wire:click="filterZuruecksetzen">
                            <i class="bi bi-x-circle"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Gebäude-Liste --}}
        <div class="card">
            <div class="card-header py-2">
                <i class="bi bi-list-ul"></i> Gebäude
                <span class="badge bg-primary ms-1">{{ $gebaeude->total() }}</span>
            </div>
            <div class="card-body p-0">
                @if($gebaeude->count() > 0)
                    <div class="list-group list-group-flush">
                        @foreach($gebaeude as $geb)
                            <div class="list-group-item p-2 p-md-3 {{ $geb->ist_erledigt ? 'bg-success bg-opacity-10 border-start border-success border-3' : '' }}">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1 me-2 {{ $geb->ist_erledigt ? 'opacity-75' : '' }}">
                                        {{-- Codex & Status --}}
                                        <div class="d-flex align-items-center mb-1">
                                            <strong class="me-2 {{ $geb->ist_erledigt ? 'text-success' : '' }}">
                                                @if($geb->ist_erledigt)
                                                    <i class="bi bi-check-circle-fill text-success me-1"></i>
                                                @endif
                                                {{ $geb->codex }}
                                            </strong>
                                            @if($geb->ist_erledigt)
                                                <span class="badge bg-success">
                                                    <i class="bi bi-check2"></i> Erledigt
                                                </span>
                                            @else
                                                <span class="badge bg-warning text-dark">
                                                    <i class="bi bi-clock-history"></i> Offen
                                                </span>
                                            @endif
                                        </div>
                                        
                                        @if($geb->gebaeude_name)
                                            <div class="small mb-1 {{ $geb->ist_erledigt ? 'text-success' : 'text-muted' }}">
                                                {{ $geb->gebaeude_name }}
                                            </div>
                                        @endif
                                        
                                        {{-- Adresse mit Maps --}}
                                        @php
                                            $adresse = $geb->strasse . ' ' . $geb->hausnummer . ', ' . $geb->plz . ' ' . $geb->wohnort;
                                            $mapsUrl = 'https://www.google.com/maps/search/?api=1&query=' . urlencode($adresse);
                                        @endphp
                                        <div class="small mb-2">
                                            <a href="{{ $mapsUrl }}" target="_blank" class="text-decoration-none {{ $geb->ist_erledigt ? 'text-success' : 'text-dark' }}">
                                                <i class="bi bi-geo-alt {{ $geb->ist_erledigt ? 'text-success' : 'text-danger' }}"></i> {{ $adresse }}
                                                <i class="bi bi-box-arrow-up-right small text-muted"></i>
                                            </a>
                                        </div>

                                        {{-- Kontakt-Buttons (dezenter bei erledigten) --}}
                                        @if($geb->telefon || $geb->handy)
                                            <div class="d-flex flex-wrap gap-1 mb-2">
                                                @if($geb->telefon)
                                                    <a href="tel:{{ preg_replace('/[^0-9+]/', '', $geb->telefon) }}" 
                                                       class="btn btn-sm {{ $geb->ist_erledigt ? 'btn-outline-success' : 'btn-outline-primary' }}" title="Anrufen">
                                                        <i class="bi bi-telephone"></i>
                                                    </a>
                                                @endif
                                                
                                                @if($geb->handy)
                                                    @php
                                                        $handyClean = preg_replace('/[^0-9+]/', '', $geb->handy);
                                                        $handyWA = ltrim($handyClean, '+');
                                                        if (!str_starts_with($handyClean, '+')) $handyWA = '39' . $handyWA;
                                                    @endphp
                                                    <a href="tel:{{ $handyClean }}" class="btn btn-sm {{ $geb->ist_erledigt ? 'btn-outline-success' : 'btn-outline-success' }}" title="Handy">
                                                        <i class="bi bi-phone"></i>
                                                    </a>
                                                    <a href="https://wa.me/{{ $handyWA }}" target="_blank" class="btn btn-sm btn-success" title="WhatsApp">
                                                        <i class="bi bi-whatsapp"></i>
                                                    </a>
                                                    <a href="sms:{{ $handyClean }}" class="btn btn-sm {{ $geb->ist_erledigt ? 'btn-outline-success' : 'btn-outline-info' }}" title="SMS">
                                                        <i class="bi bi-chat-dots"></i>
                                                    </a>
                                                @endif
                                            </div>
                                        @endif

                                        {{-- Termine --}}
                                        <div class="small {{ $geb->ist_erledigt ? 'text-success' : 'text-muted' }}">
                                            @if($geb->letzte_reinigung_datum)
                                                <i class="bi bi-calendar-event"></i> 
                                                Letzte: <strong>{{ $geb->letzte_reinigung_datum->format('d.m.Y') }}</strong>
                                            @endif
                                            @if($geb->naechste_faelligkeit && !$geb->ist_erledigt)
                                                <span class="ms-2">
                                                    <i class="bi bi-calendar-check"></i> 
                                                    Nächste: {{ $geb->naechste_faelligkeit->format('d.m.Y') }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Aktionen --}}
                                    <div class="d-flex flex-column gap-1">
                                        @if(!$geb->ist_erledigt)
                                            <button class="btn btn-sm btn-success" wire:click="erledigtModalOeffnen({{ $geb->id }})" title="Als erledigt markieren">
                                                <i class="bi bi-check-circle"></i>
                                            </button>
                                        @else
                                            <button class="btn btn-sm btn-outline-success" wire:click="erledigtModalOeffnen({{ $geb->id }})" title="Weitere Reinigung eintragen">
                                                <i class="bi bi-plus-circle"></i>
                                            </button>
                                        @endif
                                        <button class="btn btn-sm btn-outline-primary" wire:click="bearbeitenModalOeffnen({{ $geb->id }})" title="Ändern">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if($gebaeude->hasPages())
                        <div class="p-3">{{ $gebaeude->links() }}</div>
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

    {{-- MODAL: ERLEDIGT --}}
    @if($showErledigtModal)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" wire:keydown.escape="erledigtModalSchliessen">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white py-2">
                        <h5 class="modal-title"><i class="bi bi-check-circle"></i> Reinigung eintragen</h5>
                        <button type="button" class="btn-close btn-close-white" wire:click="erledigtModalSchliessen"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Datum *</label>
                            <input type="date" class="form-control @error('erledigtDatum') is-invalid @enderror" 
                                   wire:model="erledigtDatum" max="{{ today()->format('Y-m-d') }}">
                            @error('erledigtDatum')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Bemerkung</label>
                            <textarea class="form-control" wire:model="erledigtBemerkung" rows="2" placeholder="Optional..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" wire:click="erledigtModalSchliessen">
                            <i class="bi bi-x-circle"></i> Abbrechen
                        </button>
                        <button class="btn btn-success" wire:click="erledigtSpeichern" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="erledigtSpeichern"><i class="bi bi-save"></i> Speichern</span>
                            <span wire:loading wire:target="erledigtSpeichern"><span class="spinner-border spinner-border-sm"></span></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- MODAL: BEARBEITEN --}}
    @if($showBearbeitenModal)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" wire:keydown.escape="bearbeitenModalSchliessen">
            <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white py-2">
                        <h5 class="modal-title">
                            <i class="bi bi-pencil"></i> {{ $bearbeitenGebaeudeName ?: $bearbeitenGebaeudeCodex }} bearbeiten
                        </h5>
                        <button type="button" class="btn-close btn-close-white" wire:click="bearbeitenModalSchliessen"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info py-2 mb-3">
                            <small><i class="bi bi-info-circle"></i> Änderungen müssen von einem Admin genehmigt werden.</small>
                        </div>

                        {{-- Grunddaten --}}
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

                        {{-- Kontakt --}}
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
                                @foreach(['01'=>'Jan','02'=>'Feb','03'=>'Mär','04'=>'Apr','05'=>'Mai','06'=>'Jun','07'=>'Jul','08'=>'Aug','09'=>'Sep','10'=>'Okt','11'=>'Nov','12'=>'Dez'] as $num => $name)
                                    <div class="col-4 col-md-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="m{{ $num }}" wire:model="m{{ $num }}">
                                            <label class="form-check-label small" for="m{{ $num }}">{{ $name }}</label>
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
                                            <input class="form-check-input" type="checkbox" id="tour_{{ $tour->id }}" value="{{ $tour->id }}" wire:model="selectedTouren">
                                            <label class="form-check-label small" for="tour_{{ $tour->id }}">{{ $tour->name }}</label>
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
                    <div class="modal-footer">
                        <button class="btn btn-secondary" wire:click="bearbeitenModalSchliessen">
                            <i class="bi bi-x-circle"></i> Abbrechen
                        </button>
                        <button class="btn btn-primary" wire:click="aenderungVorschlagen" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="aenderungVorschlagen"><i class="bi bi-send"></i> Änderung vorschlagen</span>
                            <span wire:loading wire:target="aenderungVorschlagen"><span class="spinner-border spinner-border-sm"></span></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
