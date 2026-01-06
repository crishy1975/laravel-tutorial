{{-- resources/views/livewire/mitarbeiter/gebaeude-erstellen.blade.php --}}

<div class="container-fluid py-2 py-md-4">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">
            <i class="bi bi-building-add text-primary"></i>
            Neues Gebäude vorschlagen
        </h1>
        <a href="{{ route('mitarbeiter.dashboard') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i>
            <span class="d-none d-md-inline">Zurück</span>
        </a>
    </div>

    {{-- Success Alert --}}
    @if($showSuccess)
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle"></i>
            <strong>Erfolgreich!</strong> Ihr Vorschlag wurde gespeichert und wartet auf Freigabe durch einen Admin.
            <button type="button" class="btn-close" wire:click="closeSuccess"></button>
        </div>
    @endif

    {{-- Info-Box --}}
    <div class="alert alert-info mb-3" role="alert">
        <i class="bi bi-info-circle"></i>
        <small>
            <strong>Hinweis:</strong> Neue Gebäude müssen von einem Admin freigegeben werden, bevor sie aktiv sind.
        </small>
    </div>

    {{-- Formular --}}
    <form wire:submit.prevent="speichern">
        
        {{-- Basis-Daten Card --}}
        <div class="card mb-3">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-building"></i> Basis-Daten
            </div>
            <div class="card-body">
                <div class="row g-2">
                    {{-- Codex --}}
                    <div class="col-12 col-md-6">
                        <label for="codex" class="form-label">
                            Codex <span class="text-danger">*</span>
                        </label>
                        <input 
                            type="text" 
                            class="form-control @error('codex') is-invalid @enderror" 
                            id="codex"
                            wire:model="codex"
                            placeholder="z.B. BZ-001"
                        >
                        @error('codex')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Gebäude-Name --}}
                    <div class="col-12 col-md-6">
                        <label for="gebaeude_name" class="form-label">Gebäude-Name</label>
                        <input 
                            type="text" 
                            class="form-control @error('gebaeude_name') is-invalid @enderror" 
                            id="gebaeude_name"
                            wire:model="gebaeude_name"
                            placeholder="z.B. Verwaltungsgebäude"
                        >
                        @error('gebaeude_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Straße --}}
                    <div class="col-8 col-md-8">
                        <label for="strasse" class="form-label">
                            Straße <span class="text-danger">*</span>
                        </label>
                        <input 
                            type="text" 
                            class="form-control @error('strasse') is-invalid @enderror" 
                            id="strasse"
                            wire:model="strasse"
                            placeholder="z.B. Bozner Straße"
                        >
                        @error('strasse')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Hausnummer --}}
                    <div class="col-4 col-md-4">
                        <label for="hausnummer" class="form-label">
                            Nr. <span class="text-danger">*</span>
                        </label>
                        <input 
                            type="text" 
                            class="form-control @error('hausnummer') is-invalid @enderror" 
                            id="hausnummer"
                            wire:model="hausnummer"
                            placeholder="1"
                        >
                        @error('hausnummer')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- PLZ --}}
                    <div class="col-4 col-md-3">
                        <label for="plz" class="form-label">
                            PLZ <span class="text-danger">*</span>
                        </label>
                        <input 
                            type="text" 
                            class="form-control @error('plz') is-invalid @enderror" 
                            id="plz"
                            wire:model="plz"
                            placeholder="39100"
                        >
                        @error('plz')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Wohnort --}}
                    <div class="col-5 col-md-6">
                        <label for="wohnort" class="form-label">
                            Wohnort <span class="text-danger">*</span>
                        </label>
                        <input 
                            type="text" 
                            class="form-control @error('wohnort') is-invalid @enderror" 
                            id="wohnort"
                            wire:model="wohnort"
                            placeholder="Bozen"
                        >
                        @error('wohnort')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Land --}}
                    <div class="col-3 col-md-3">
                        <label for="land" class="form-label">
                            Land <span class="text-danger">*</span>
                        </label>
                        <select 
                            class="form-select @error('land') is-invalid @enderror" 
                            id="land"
                            wire:model="land"
                        >
                            <option value="IT">IT</option>
                            <option value="AT">AT</option>
                            <option value="DE">DE</option>
                            <option value="CH">CH</option>
                        </select>
                        @error('land')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Kontakt-Daten Card --}}
        <div class="card mb-3">
            <div class="card-header bg-secondary text-white">
                <i class="bi bi-telephone"></i> Kontakt-Daten
            </div>
            <div class="card-body">
                <div class="row g-2">
                    {{-- Telefon --}}
                    <div class="col-12 col-md-4">
                        <label for="telefon" class="form-label">
                            <i class="bi bi-telephone"></i> Telefon
                        </label>
                        <input 
                            type="text" 
                            class="form-control @error('telefon') is-invalid @enderror" 
                            id="telefon"
                            wire:model="telefon"
                            placeholder="+39 0471 123456"
                        >
                        @error('telefon')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Handy --}}
                    <div class="col-12 col-md-4">
                        <label for="handy" class="form-label">
                            <i class="bi bi-phone"></i> Handy
                        </label>
                        <input 
                            type="text" 
                            class="form-control @error('handy') is-invalid @enderror" 
                            id="handy"
                            wire:model="handy"
                            placeholder="+39 333 1234567"
                        >
                        @error('handy')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- E-Mail --}}
                    <div class="col-12 col-md-4">
                        <label for="email" class="form-label">
                            <i class="bi bi-envelope"></i> E-Mail
                        </label>
                        <input 
                            type="email" 
                            class="form-control @error('email') is-invalid @enderror" 
                            id="email"
                            wire:model="email"
                            placeholder="info@example.com"
                        >
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Reinigungsplan Card --}}
        <div class="card mb-3">
            <div class="card-header bg-success text-white">
                <i class="bi bi-calendar-check"></i> Reinigungsplan
            </div>
            <div class="card-body">
                {{-- Geplante Reinigungen --}}
                <div class="mb-3">
                    <label for="geplante_reinigungen" class="form-label">Geplante Reinigungen pro Jahr</label>
                    <input 
                        type="number" 
                        class="form-control @error('geplante_reinigungen') is-invalid @enderror" 
                        id="geplante_reinigungen"
                        wire:model="geplante_reinigungen"
                        min="0"
                        max="365"
                        placeholder="12"
                    >
                    @error('geplante_reinigungen')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Aktive Monate --}}
                <div>
                    <label class="form-label d-block">Aktive Monate</label>
                    <div class="d-flex gap-2 mb-2">
                        <button 
                            type="button" 
                            class="btn btn-sm btn-outline-primary" 
                            wire:click="alleMonateMarkieren"
                        >
                            <i class="bi bi-check-all"></i> Alle
                        </button>
                        <button 
                            type="button" 
                            class="btn btn-sm btn-outline-secondary" 
                            wire:click="keineMonateMarkieren"
                        >
                            <i class="bi bi-x"></i> Keine
                        </button>
                    </div>

                    <div class="row g-2">
                        @foreach($monate as $num => $name)
                            <div class="col-6 col-md-3 col-lg-2">
                                <div class="form-check">
                                    <input 
                                        class="form-check-input" 
                                        type="checkbox" 
                                        id="m{{ str_pad($num, 2, '0', STR_PAD_LEFT) }}"
                                        wire:model="m{{ str_pad($num, 2, '0', STR_PAD_LEFT) }}"
                                    >
                                    <label class="form-check-label small" for="m{{ str_pad($num, 2, '0', STR_PAD_LEFT) }}">
                                        {{ $name }}
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Touren Card --}}
        <div class="card mb-3">
            <div class="card-header bg-warning">
                <i class="bi bi-map"></i> Touren-Zuordnung
            </div>
            <div class="card-body">
                <label class="form-label">Touren auswählen (optional)</label>
                @if($touren->count() > 0)
                    <div class="row g-2">
                        @foreach($touren as $tour)
                            <div class="col-12 col-md-6 col-lg-4">
                                <div class="form-check">
                                    <input 
                                        class="form-check-input" 
                                        type="checkbox" 
                                        id="tour_{{ $tour->id }}"
                                        value="{{ $tour->id }}"
                                        wire:model="selectedTouren"
                                    >
                                    <label class="form-check-label" for="tour_{{ $tour->id }}">
                                        {{ $tour->name }}
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="alert alert-info mb-0">
                        <i class="bi bi-info-circle"></i>
                        Keine aktiven Touren verfügbar.
                    </div>
                @endif
            </div>
        </div>

        {{-- Bemerkungen Card --}}
        <div class="card mb-3">
            <div class="card-header bg-info text-white">
                <i class="bi bi-chat-left-text"></i> Bemerkungen
            </div>
            <div class="card-body">
                {{-- Bemerkung (wird ins Gebäude übernommen) --}}
                <div class="mb-3">
                    <label for="bemerkung" class="form-label">Bemerkung zum Gebäude</label>
                    <textarea 
                        class="form-control @error('bemerkung') is-invalid @enderror" 
                        id="bemerkung"
                        wire:model="bemerkung"
                        rows="3"
                        placeholder="Allgemeine Bemerkungen zum Gebäude..."
                    ></textarea>
                    @error('bemerkung')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Bemerkung für Admin --}}
                <div>
                    <label for="bemerkung_mitarbeiter" class="form-label">
                        Nachricht an Admin <small class="text-muted">(nur intern)</small>
                    </label>
                    <textarea 
                        class="form-control @error('bemerkung_mitarbeiter') is-invalid @enderror" 
                        id="bemerkung_mitarbeiter"
                        wire:model="bemerkung_mitarbeiter"
                        rows="2"
                        placeholder="Hinweise für den Admin..."
                    ></textarea>
                    @error('bemerkung_mitarbeiter')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Submit Button --}}
        <div class="d-grid gap-2 d-md-flex justify-content-md-end mb-4">
            <a href="{{ route('mitarbeiter.dashboard') }}" class="btn btn-outline-secondary">
                <i class="bi bi-x-circle"></i> Abbrechen
            </a>
            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="speichern">
                    <i class="bi bi-send"></i> Vorschlag einreichen
                </span>
                <span wire:loading wire:target="speichern">
                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                    Wird gespeichert...
                </span>
            </button>
        </div>
    </form>
</div>
