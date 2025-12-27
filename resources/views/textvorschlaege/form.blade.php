@extends('layouts.app')

@section('title', $vorschlag->exists ? 'Textvorschlag bearbeiten' : 'Neuer Textvorschlag')

@section('content')
<div class="container py-3" style="max-width: 700px;">
    
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
                </div>

                {{-- Titel --}}
                <div class="mb-3">
                    <label for="titel" class="form-label">
                        Titel <small class="text-muted">(für Dropdown-Anzeige)</small>
                    </label>
                    <input type="text" name="titel" id="titel" 
                           class="form-control @error('titel') is-invalid @enderror" 
                           value="{{ old('titel', $vorschlag->titel) }}"
                           placeholder="z.B. Ankündigung Reinigung"
                           maxlength="100">
                    @error('titel')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">
                        Kurzer Name für das Dropdown. Wenn leer, wird der Text gekürzt angezeigt.
                    </div>
                </div>

                {{-- Platzhalter einfügen --}}
                <div class="mb-2">
                    <label class="form-label">Platzhalter einfügen:</label>
                    <div class="btn-group btn-group-sm flex-wrap">
                        <button type="button" class="btn btn-outline-info" onclick="einfuegenPlatzhalter('@{{DATUM}}')">
                            📅 Datum
                        </button>
                        <button type="button" class="btn btn-outline-info" onclick="einfuegenPlatzhalter('@{{VON}}')">
                            🕐 Von
                        </button>
                        <button type="button" class="btn btn-outline-info" onclick="einfuegenPlatzhalter('@{{BIS}}')">
                            🕐 Bis
                        </button>
                        <button type="button" class="btn btn-outline-info" onclick="einfuegenPlatzhalter('@{{ZEIT}}')">
                            🕐 Von-Bis
                        </button>
                    </div>
                </div>

                {{-- Text --}}
                <div class="mb-3">
                    <label for="text" class="form-label">
                        Text (DE + IT) <span class="text-danger">*</span>
                    </label>
                    <textarea name="text" id="text" rows="6" 
                              class="form-control @error('text') is-invalid @enderror" 
                              required maxlength="2000"
                              placeholder="Guten Tag, wir kommen am @{{DATUM}} zwischen @{{VON}} und @{{BIS}} Uhr.

Buongiorno, veniamo il @{{DATUM}} tra le @{{VON}} e le @{{BIS}}.">{{ old('text', $vorschlag->text) }}</textarea>
                    @error('text')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">
                        Zweisprachiger Text. Platzhalter werden beim Versand ersetzt.
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

</div>

@push('scripts')
<script>
function einfuegenPlatzhalter(ph) {
    const ta = document.getElementById('text');
    const start = ta.selectionStart;
    ta.value = ta.value.substring(0, start) + ph + ta.value.substring(ta.selectionEnd);
    ta.focus();
    ta.selectionStart = ta.selectionEnd = start + ph.length;
}
</script>
@endpush
@endsection
