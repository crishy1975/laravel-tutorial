{{-- resources/views/mahnungen/stufe-form.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container py-3">
    
    {{-- Kopfzeile --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0">
                <i class="bi {{ $stufe->icon }}"></i>
                Stufe {{ $stufe->stufe }}: {{ $stufe->name_de }}
            </h4>
            <small class="text-muted">Mahnstufe bearbeiten</small>
        </div>
        <a href="{{ route('mahnungen.stufen') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Zurück
        </a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('mahnungen.stufe.speichern', $stufe->id) }}">
        @csrf
        @method('PUT')

        <div class="row">
            {{-- Linke Spalte: Grunddaten --}}
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">Grundeinstellungen</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Stufe</label>
                            <input type="number" class="form-control" value="{{ $stufe->stufe }}" disabled>
                            <small class="text-muted">Kann nicht geändert werden</small>
                        </div>

                        <div class="mb-3">
                            <label for="tage_ueberfaellig" class="form-label">Tage überfällig <span class="text-danger">*</span></label>
                            <input type="number" 
                                   class="form-control @error('tage_ueberfaellig') is-invalid @enderror" 
                                   id="tage_ueberfaellig" 
                                   name="tage_ueberfaellig" 
                                   value="{{ old('tage_ueberfaellig', $stufe->tage_ueberfaellig) }}"
                                   min="1" max="365" required>
                            <small class="text-muted">Ab wie vielen Tagen überfällig diese Stufe greift</small>
                            @error('tage_ueberfaellig')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="spesen" class="form-label">Mahnspesen (€) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" 
                                       class="form-control @error('spesen') is-invalid @enderror" 
                                       id="spesen" 
                                       name="spesen" 
                                       value="{{ old('spesen', $stufe->spesen) }}"
                                       min="0" max="500" step="0.01" required>
                                <span class="input-group-text">€</span>
                            </div>
                            @error('spesen')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input type="checkbox" 
                                       class="form-check-input" 
                                       id="aktiv" 
                                       name="aktiv"
                                       value="1"
                                       {{ old('aktiv', $stufe->aktiv) ? 'checked' : '' }}>
                                <label class="form-check-label" for="aktiv">Aktiv</label>
                            </div>
                            <small class="text-muted">Inaktive Stufen werden im Mahnlauf übersprungen</small>
                        </div>
                    </div>
                </div>

                {{-- Platzhalter-Info --}}
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bi bi-code"></i> Platzhalter</h6>
                    </div>
                    <div class="card-body">
                        <small>
                            <code>{rechnungsnummer}</code> - Volle Nummer<br>
                            <code>{rechnungsdatum}</code> - Datum der Rechnung<br>
                            <code>{faelligkeitsdatum}</code> - Fälligkeit<br>
                            <code>{betrag}</code> - Rechnungsbetrag<br>
                            <code>{spesen}</code> - Mahnspesen<br>
                            <code>{gesamtbetrag}</code> - Summe<br>
                            <code>{tage_ueberfaellig}</code> - Tage<br>
                            <code>{firma}</code> - Ihr Firmenname<br>
                            <code>{kunde}</code> - Kundenname
                        </small>
                    </div>
                </div>
            </div>

            {{-- Rechte Spalte: Namen, Betreffs, Texte --}}
            <div class="col-lg-8">
                {{-- Namen --}}
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">Bezeichnung</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name_de" class="form-label">🇩🇪 Name (Deutsch) <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control @error('name_de') is-invalid @enderror" 
                                       id="name_de" 
                                       name="name_de" 
                                       value="{{ old('name_de', $stufe->name_de) }}"
                                       maxlength="100" required>
                                @error('name_de')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="name_it" class="form-label">🇮🇹 Name (Italiano) <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control @error('name_it') is-invalid @enderror" 
                                       id="name_it" 
                                       name="name_it" 
                                       value="{{ old('name_it', $stufe->name_it) }}"
                                       maxlength="100" required>
                                @error('name_it')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Betreffs --}}
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">E-Mail Betreff</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="betreff_de" class="form-label">🇩🇪 Betreff (Deutsch) <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control @error('betreff_de') is-invalid @enderror" 
                                   id="betreff_de" 
                                   name="betreff_de" 
                                   value="{{ old('betreff_de', $stufe->betreff_de) }}"
                                   maxlength="255" required>
                            @error('betreff_de')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-0">
                            <label for="betreff_it" class="form-label">🇮🇹 Betreff (Italiano) <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control @error('betreff_it') is-invalid @enderror" 
                                   id="betreff_it" 
                                   name="betreff_it" 
                                   value="{{ old('betreff_it', $stufe->betreff_it) }}"
                                   maxlength="255" required>
                            @error('betreff_it')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Texte --}}
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">Mahntext</h6>
                    </div>
                    <div class="card-body">
                        <ul class="nav nav-tabs" role="tablist">
                            <li class="nav-item">
                                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#textDe" type="button">
                                    🇩🇪 Deutsch
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#textIt" type="button">
                                    🇮🇹 Italiano
                                </button>
                            </li>
                        </ul>
                        <div class="tab-content pt-3">
                            <div class="tab-pane fade show active" id="textDe">
                                <textarea class="form-control font-monospace @error('text_de') is-invalid @enderror" 
                                          name="text_de" 
                                          rows="15"
                                          required>{{ old('text_de', $stufe->text_de) }}</textarea>
                                @error('text_de')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="tab-pane fade" id="textIt">
                                <textarea class="form-control font-monospace @error('text_it') is-invalid @enderror" 
                                          name="text_it" 
                                          rows="15"
                                          required>{{ old('text_it', $stufe->text_it) }}</textarea>
                                @error('text_it')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Buttons --}}
                <div class="d-flex justify-content-between">
                    <a href="{{ route('mahnungen.stufen') }}" class="btn btn-outline-secondary">
                        Abbrechen
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg"></i> Speichern
                    </button>
                </div>
            </div>
        </div>
    </form>

</div>
@endsection
