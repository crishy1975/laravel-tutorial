@extends('layouts.app')

@section('title', $vorschlag->exists ? 'Textvorschlag bearbeiten' : 'Neuer Textvorschlag')

@section('content')
<div class="container py-3" style="max-width: 600px;">
    
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">
            <i class="bi bi-chat-quote text-primary"></i>
            {{ $vorschlag->exists ? 'Textvorschlag bearbeiten' : 'Neuer Textvorschlag' }}
        </h1>
        <a href="{{ route('textvorschlaege.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i>
            <span class="d-none d-sm-inline ms-1">Zurück</span>
        </a>
    </div>

    {{-- Formular --}}
    <div class="card shadow-sm">
        <form method="POST" 
              action="{{ $vorschlag->exists ? route('textvorschlaege.update', $vorschlag) : route('textvorschlaege.store') }}">
            @csrf
            @if($vorschlag->exists)
                @method('PUT')
            @endif

            <div class="card-body">
                
                {{-- Kategorie --}}
                <div class="mb-3">
                    <label for="kategorie" class="form-label">
                        Kategorie <span class="text-danger">*</span>
                    </label>
                    <select name="kategorie" id="kategorie" class="form-select @error('kategorie') is-invalid @enderror" required>
                        <option value="">-- Bitte wählen --</option>
                        @foreach($kategorien as $key => $name)
                            <option value="{{ $key }}" @selected(old('kategorie', $vorschlag->kategorie) == $key)>
                                {{ $name }}
                            </option>
                        @endforeach
                    </select>
                    @error('kategorie')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">
                        Wo soll dieser Vorschlag erscheinen?
                    </div>
                </div>

                {{-- Sprache --}}
                <div class="mb-3">
                    <label class="form-label">
                        Sprache <span class="text-danger">*</span>
                    </label>
                    <div class="d-flex gap-3">
                        <div class="form-check">
                            <input type="radio" name="sprache" value="de" id="sprache_de" 
                                   class="form-check-input @error('sprache') is-invalid @enderror"
                                   @checked(old('sprache', $vorschlag->sprache ?? 'de') == 'de') required>
                            <label for="sprache_de" class="form-check-label">
                                🇩🇪 Deutsch
                            </label>
                        </div>
                        <div class="form-check">
                            <input type="radio" name="sprache" value="it" id="sprache_it" 
                                   class="form-check-input @error('sprache') is-invalid @enderror"
                                   @checked(old('sprache', $vorschlag->sprache) == 'it')>
                            <label for="sprache_it" class="form-check-label">
                                🇮🇹 Italiano
                            </label>
                        </div>
                    </div>
                    @error('sprache')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Text --}}
                <div class="mb-3">
                    <label for="text" class="form-label">
                        Text <span class="text-danger">*</span>
                    </label>
                    <textarea name="text" id="text" rows="3" 
                              class="form-control @error('text') is-invalid @enderror" 
                              required maxlength="1000"
                              placeholder="z.B. Fenster auch gereinigt">{{ old('text', $vorschlag->text) }}</textarea>
                    @error('text')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">
                        Der Text, der als Vorschlag angezeigt wird.
                    </div>
                </div>

                {{-- Sortierung --}}
                <div class="mb-3">
                    <label for="sortierung" class="form-label">
                        Sortierung
                    </label>
                    <input type="number" name="sortierung" id="sortierung" 
                           class="form-control @error('sortierung') is-invalid @enderror" 
                           value="{{ old('sortierung', $vorschlag->sortierung ?? 0) }}"
                           min="0" max="999" style="max-width: 100px;">
                    @error('sortierung')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">
                        Kleinere Zahlen erscheinen zuerst (0 = Standard).
                    </div>
                </div>

                {{-- Aktiv --}}
                <div class="mb-0">
                    <div class="form-check form-switch">
                        <input type="checkbox" name="aktiv" value="1" id="aktiv" 
                               class="form-check-input" role="switch"
                               @checked(old('aktiv', $vorschlag->aktiv ?? true))>
                        <label for="aktiv" class="form-check-label">
                            Aktiv (wird in Dropdowns angezeigt)
                        </label>
                    </div>
                </div>

            </div>

            <div class="card-footer bg-white d-flex justify-content-between">
                <a href="{{ route('textvorschlaege.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-x-lg"></i> Abbrechen
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg"></i> 
                    {{ $vorschlag->exists ? 'Speichern' : 'Erstellen' }}
                </button>
            </div>
        </form>
    </div>

    {{-- Schnell-Buttons für neue Vorschläge --}}
    @if(!$vorschlag->exists)
    <div class="card shadow-sm mt-3">
        <div class="card-header bg-light py-2">
            <i class="bi bi-lightning"></i> Schnell-Vorlagen
        </div>
        <div class="card-body py-2">
            <p class="small text-muted mb-2">Klicken zum Übernehmen:</p>
            
            <div class="mb-2">
                <strong class="small">🇩🇪 Deutsch:</strong>
                <div class="d-flex flex-wrap gap-1 mt-1">
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setzeText('Fenster auch gereinigt')">Fenster gereinigt</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setzeText('Treppenhaus gereinigt')">Treppenhaus</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setzeText('Niemand zu Hause')">Niemand da</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setzeText('Schlüssel nicht vorhanden')">Kein Schlüssel</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setzeText('Garage auch gereinigt')">Garage</button>
                </div>
            </div>
            
            <div>
                <strong class="small">🇮🇹 Italiano:</strong>
                <div class="d-flex flex-wrap gap-1 mt-1">
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setzeText('Finestre anche pulite'); document.getElementById('sprache_it').checked=true;">Finestre pulite</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setzeText('Scale pulite'); document.getElementById('sprache_it').checked=true;">Scale</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setzeText('Nessuno a casa'); document.getElementById('sprache_it').checked=true;">Nessuno</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setzeText('Chiave non disponibile'); document.getElementById('sprache_it').checked=true;">No chiave</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setzeText('Garage anche pulito'); document.getElementById('sprache_it').checked=true;">Garage</button>
                </div>
            </div>
        </div>
    </div>
    @endif

</div>

@push('scripts')
<script>
function setzeText(text) {
    document.getElementById('text').value = text;
    document.getElementById('text').focus();
}
</script>
@endpush
@endsection
