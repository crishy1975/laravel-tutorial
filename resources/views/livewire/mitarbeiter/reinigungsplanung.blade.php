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

    {{-- Statistik-Karten --}}
    <div class="row g-2 mb-3">
        <div class="col-4">
            <div class="card border-primary h-100">
                <div class="card-body text-center py-2 py-md-3">
                    <h2 class="h3 h2-md fw-bold text-primary mb-0">{{ $stats['gesamt'] }}</h2>
                    <small class="text-muted d-none d-sm-inline">Gesamt</small>
                    <small class="text-muted d-sm-none" style="font-size: 0.7rem;">Ges.</small>
                </div>
            </div>
        </div>
        <div class="col-4">
            <div class="card border-warning h-100">
                <div class="card-body text-center py-2 py-md-3">
                    <h2 class="h3 h2-md fw-bold text-warning mb-0">{{ $stats['offen'] }}</h2>
                    <small class="text-muted">Offen</small>
                </div>
            </div>
        </div>
        <div class="col-4">
            <div class="card border-success h-100">
                <div class="card-body text-center py-2 py-md-3">
                    <h2 class="h3 h2-md fw-bold text-success mb-0">{{ $stats['erledigt'] }}</h2>
                    <small class="text-muted d-none d-sm-inline">Erledigt</small>
                    <small class="text-muted d-sm-none" style="font-size: 0.7rem;">Erl.</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Card --}}
    <div class="card mb-3">
        <div class="card-header py-2">
            <i class="bi bi-funnel"></i> Filter
        </div>
        <div class="card-body py-2">
            <div class="row g-2">
                {{-- Suchbegriff --}}
                <div class="col-12 col-md-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">
                            <i class="bi bi-search"></i>
                        </span>
                        <input 
                            type="text" 
                            class="form-control" 
                            placeholder="Suche..." 
                            wire:model.live.debounce.500ms="suchbegriff"
                        >
                        @if($suchbegriff)
                            <button class="btn btn-outline-secondary" type="button" wire:click="$set('suchbegriff', '')">
                                <i class="bi bi-x"></i>
                            </button>
                        @endif
                    </div>
                </div>

                {{-- Monat --}}
                <div class="col-4 col-md-2">
                    <select class="form-select form-select-sm" wire:model.live="filterMonat">
                        <option value="">Alle</option>
                        @foreach($monate as $num => $name)
                            <option value="{{ $num }}">{{ substr($name, 0, 3) }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Tour --}}
                <div class="col-4 col-md-2">
                    <select class="form-select form-select-sm" wire:model.live="filterTour">
                        <option value="">Tour</option>
                        @foreach($touren as $tour)
                            <option value="{{ $tour->id }}">{{ $tour->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Status --}}
                <div class="col-4 col-md-2">
                    <select class="form-select form-select-sm" wire:model.live="filterStatus">
                        <option value="">Status</option>
                        <option value="offen">Offen</option>
                        <option value="erledigt">Erledigt</option>
                    </select>
                </div>

                {{-- Reset --}}
                <div class="col-12 col-md-1">
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
        <div class="card-header d-flex justify-content-between align-items-center py-2">
            <span>
                <i class="bi bi-list-ul"></i> Gebäude
                <span class="badge bg-primary ms-1">{{ $gebaeude->total() }}</span>
            </span>
            <div wire:loading class="spinner-border spinner-border-sm text-primary" role="status">
                <span class="visually-hidden">Laden...</span>
            </div>
        </div>
        <div class="card-body p-0">
            @if($gebaeude->count() > 0)
                <div class="list-group list-group-flush">
                    @foreach($gebaeude as $geb)
                        <div class="list-group-item p-2 p-md-3">
                            <div class="d-flex justify-content-between align-items-start">
                                {{-- Gebäude-Info --}}
                                <div class="flex-grow-1 me-2">
                                    {{-- Codex & Status --}}
                                    <div class="d-flex align-items-center mb-1">
                                        <strong class="me-2">{{ $geb->codex }}</strong>
                                        
                                        @if($geb->ist_erledigt)
                                            <span class="badge bg-success" title="Erledigt">
                                                <i class="bi bi-check-circle"></i>
                                            </span>
                                        @else
                                            <span class="badge bg-warning text-dark" title="Offen">
                                                <i class="bi bi-clock-history"></i>
                                            </span>
                                        @endif
                                    </div>
                                    
                                    {{-- Gebäude-Name --}}
                                    @if($geb->gebaeude_name)
                                        <div class="text-muted small mb-1">{{ $geb->gebaeude_name }}</div>
                                    @endif
                                    
                                    {{-- Adresse mit Maps-Link --}}
                                    <div class="small mb-2">
                                        @php
                                            $adresse = $geb->strasse . ' ' . $geb->hausnummer . ', ' . $geb->plz . ' ' . $geb->wohnort;
                                            $mapsUrl = 'https://www.google.com/maps/search/?api=1&query=' . urlencode($adresse);
                                        @endphp
                                        <a href="{{ $mapsUrl }}" target="_blank" class="text-decoration-none text-dark">
                                            <i class="bi bi-geo-alt text-danger"></i>
                                            {{ $adresse }}
                                            <i class="bi bi-box-arrow-up-right small text-muted"></i>
                                        </a>
                                    </div>

                                    {{-- Kontakt-Buttons --}}
                                    @if($geb->telefon || $geb->handy)
                                        <div class="d-flex flex-wrap gap-1 mb-2">
                                            {{-- Telefon --}}
                                            @if($geb->telefon)
                                                <a href="tel:{{ preg_replace('/[^0-9+]/', '', $geb->telefon) }}" 
                                                   class="btn btn-sm btn-outline-primary" title="Anrufen: {{ $geb->telefon }}">
                                                    <i class="bi bi-telephone"></i>
                                                    <span class="d-none d-md-inline ms-1">{{ $geb->telefon }}</span>
                                                </a>
                                            @endif
                                            
                                            {{-- Handy mit allen Optionen --}}
                                            @if($geb->handy)
                                                @php
                                                    $handyClean = preg_replace('/[^0-9+]/', '', $geb->handy);
                                                    // Für WhatsApp: + am Anfang, keine Leerzeichen
                                                    $handyWhatsApp = ltrim($handyClean, '+');
                                                    if (!str_starts_with($handyClean, '+')) {
                                                        // Italienische Nummer ohne Vorwahl -> 39 hinzufügen
                                                        $handyWhatsApp = '39' . $handyWhatsApp;
                                                    }
                                                @endphp
                                                
                                                {{-- Anruf --}}
                                                <a href="tel:{{ $handyClean }}" 
                                                   class="btn btn-sm btn-outline-success" title="Handy anrufen">
                                                    <i class="bi bi-phone"></i>
                                                </a>
                                                
                                                {{-- WhatsApp --}}
                                                <a href="https://wa.me/{{ $handyWhatsApp }}" 
                                                   target="_blank"
                                                   class="btn btn-sm btn-success" title="WhatsApp">
                                                    <i class="bi bi-whatsapp"></i>
                                                </a>
                                                
                                                {{-- SMS --}}
                                                <a href="sms:{{ $handyClean }}" 
                                                   class="btn btn-sm btn-outline-info" title="SMS senden">
                                                    <i class="bi bi-chat-dots"></i>
                                                </a>
                                            @endif
                                        </div>
                                    @endif

                                    {{-- Letzte Reinigung --}}
                                    @if($geb->letzte_reinigung_datum)
                                        <div class="small text-muted">
                                            <i class="bi bi-calendar-event"></i>
                                            Letzte: {{ $geb->letzte_reinigung_datum->format('d.m.Y') }}
                                        </div>
                                    @endif

                                    {{-- Nächste Fälligkeit --}}
                                    @if($geb->naechste_faelligkeit)
                                        <div class="small text-muted">
                                            <i class="bi bi-calendar-check"></i>
                                            Nächste: {{ $geb->naechste_faelligkeit->format('d.m.Y') }}
                                        </div>
                                    @endif

                                    {{-- Touren (nur Desktop) --}}
                                    @if($geb->touren->count() > 0)
                                        <div class="small text-muted mt-1 d-none d-md-block">
                                            <i class="bi bi-map"></i>
                                            {{ $geb->touren->pluck('name')->implode(', ') }}
                                        </div>
                                    @endif
                                </div>

                                {{-- Aktionen --}}
                                <div class="flex-shrink-0 d-flex flex-column gap-1">
                                    {{-- Erledigt Button --}}
                                    <button 
                                        type="button" 
                                        class="btn btn-sm btn-outline-success"
                                        wire:click="erledigtModalOeffnen({{ $geb->id }})"
                                        title="Als erledigt markieren"
                                    >
                                        <i class="bi bi-check-circle"></i>
                                        <span class="d-none d-md-inline ms-1">Erledigt</span>
                                    </button>
                                    
                                    {{-- Bearbeiten Link --}}
                                    <a href="{{ route('mitarbeiter.gebaeude.bearbeiten') }}" 
                                       class="btn btn-sm btn-outline-primary"
                                       title="Gebäude bearbeiten">
                                        <i class="bi bi-pencil"></i>
                                        <span class="d-none d-md-inline ms-1">Ändern</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Pagination --}}
                @if($gebaeude->hasPages())
                    <div class="p-3">
                        {{ $gebaeude->links() }}
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

    {{-- Modal: Als erledigt markieren --}}
    @if($showErledigtModal)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    {{-- Modal Header --}}
                    <div class="modal-header bg-success text-white py-2">
                        <h5 class="modal-title">
                            <i class="bi bi-check-circle"></i>
                            Reinigung eintragen
                        </h5>
                        <button type="button" class="btn-close btn-close-white" wire:click="erledigtModalSchliessen"></button>
                    </div>

                    {{-- Modal Body --}}
                    <div class="modal-body">
                        <form wire:submit.prevent="erledigtSpeichern">
                            {{-- Datum --}}
                            <div class="mb-3">
                                <label for="erledigtDatum" class="form-label">
                                    Datum <span class="text-danger">*</span>
                                </label>
                                <input 
                                    type="date" 
                                    class="form-control @error('erledigtDatum') is-invalid @enderror" 
                                    id="erledigtDatum"
                                    wire:model="erledigtDatum"
                                    max="{{ today()->format('Y-m-d') }}"
                                >
                                @error('erledigtDatum')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Bemerkung --}}
                            <div class="mb-3">
                                <label for="erledigtBemerkung" class="form-label">Bemerkung</label>
                                <textarea 
                                    class="form-control @error('erledigtBemerkung') is-invalid @enderror" 
                                    id="erledigtBemerkung"
                                    wire:model="erledigtBemerkung"
                                    rows="3"
                                    placeholder="Optional: Bemerkung zur Reinigung..."
                                ></textarea>
                                @error('erledigtBemerkung')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </form>
                    </div>

                    {{-- Modal Footer --}}
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="erledigtModalSchliessen">
                            <i class="bi bi-x-circle"></i> Abbrechen
                        </button>
                        <button type="button" class="btn btn-success" wire:click="erledigtSpeichern" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="erledigtSpeichern">
                                <i class="bi bi-save"></i> Speichern
                            </span>
                            <span wire:loading wire:target="erledigtSpeichern">
                                <span class="spinner-border spinner-border-sm"></span> Speichern...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
