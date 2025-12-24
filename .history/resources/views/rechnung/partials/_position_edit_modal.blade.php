{{-- resources/views/rechnung/partials/_position_edit_modal.blade.php --}}
{{-- Modal zum Bearbeiten einer Rechnungsposition OHNE <form>-Tag und OHNE Submit-Button.
     Speichern soll später per JavaScript (AJAX oder ähnliches) erfolgen. --}}

@php
    // Readonly-Logik: falls von außen kein $readonly übergeben wurde, hier ableiten
    if (!isset($readonly)) {
        // Beispiel: Rechnung existiert und ist nicht mehr editierbar
        $readonly = isset($rechnung) && $rechnung->exists && !$rechnung->ist_editierbar;
    }
@endphp

<div class="modal fade position-edit-modal"
     id="editPositionModal{{ $position->id }}"
     tabindex="-1"
     aria-labelledby="editPositionModalLabel{{ $position->id }}"
     aria-hidden="true"
     {{-- URL für das Update – kann von JS verwendet werden --}}
     data-update-url="{{ route('rechnung.position.update', $position->id) }}"
     data-position-id="{{ $position->id }}">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            {{-- HEADER --}}
            <div class="modal-header">
                <h5 class="modal-title" id="editPositionModalLabel{{ $position->id }}">
                    <i class="bi bi-pencil"></i>
                    Position {{ $position->position }} bearbeiten
                </h5>
                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Schließen"></button>
            </div>

            {{-- BODY --}}
            <div class="modal-body">
                <div class="row g-3">

                    {{-- Positionsnummer --}}
                    <div class="col-md-3">
                        <div class="form-floating">
                            <input type="number"
                                   class="form-control js-pos-position"
                                   id="position-{{ $position->id }}"
                                   min="1"
                                   value="{{ old('position', $position->position) }}"
                                   placeholder="Position"
                                   {{ $readonly ? 'readonly' : '' }}>
                            <label for="position-{{ $position->id }}">Position</label>
                        </div>
                    </div>

                    {{-- Anzahl --}}
                    <div class="col-md-3">
                        <div class="form-floating">
                            <input type="number"
                                   step="0.01"
                                   class="form-control js-pos-anzahl"
                                   id="anzahl-{{ $position->id }}"
                                   value="{{ old('anzahl', $position->anzahl) }}"
                                   placeholder="Anzahl"
                                   {{ $readonly ? 'readonly' : '' }}>
                            <label for="anzahl-{{ $position->id }}">Anzahl</label>
                        </div>
                    </div>

                    {{-- Einheit --}}
                    <div class="col-md-3">
                        <div class="form-floating">
                            <input type="text"
                                   class="form-control js-pos-einheit"
                                   id="einheit-{{ $position->id }}"
                                   value="{{ old('einheit', $position->einheit) }}"
                                   placeholder="Einheit"
                                   maxlength="10"
                                   {{ $readonly ? 'readonly' : '' }}>
                            <label for="einheit-{{ $position->id }}">Einheit</label>
                        </div>
                    </div>

                    {{-- Einzelpreis (netto) --}}
                    <div class="col-md-3">
                        <div class="form-floating">
                            <input type="number"
                                   step="0.01"
                                   class="form-control js-pos-einzelpreis"
                                   id="einzelpreis-{{ $position->id }}"
                                   value="{{ old('einzelpreis', $position->einzelpreis) }}"
                                   placeholder="Einzelpreis (netto)"
                                   {{ $readonly ? 'readonly' : '' }}>
                            <label for="einzelpreis-{{ $position->id }}">Einzelpreis (netto)</label>
                        </div>
                    </div>

                    {{-- MwSt-Satz --}}
                    <div class="col-md-3">
                        <div class="form-floating">
                            <input type="number"
                                   step="0.01"
                                   class="form-control js-pos-mwst-satz"
                                   id="mwst_satz-{{ $position->id }}"
                                   value="{{ old('mwst_satz', $position->mwst_satz) }}"
                                   placeholder="MwSt-Satz"
                                   {{ $readonly ? 'readonly' : '' }}>
                            <label for="mwst_satz-{{ $position->id }}">MwSt-Satz (%)</label>
                        </div>
                    </div>

                    {{-- Beschreibung / Artikeltext --}}
                    <div class="col-12">
                        <div class="form-floating">
                            <textarea class="form-control js-pos-beschreibung"
                                      id="beschreibung-{{ $position->id }}"
                                      style="height: 120px"
                                      placeholder="Beschreibung / Artikeltext"
                                      {{ $readonly ? 'readonly' : '' }}>{{ old('beschreibung', $position->beschreibung) }}</textarea>
                            <label for="beschreibung-{{ $position->id }}">Beschreibung / Artikeltext</label>
                        </div>
                    </div>

                </div> {{-- /.row --}}
            </div> {{-- /.modal-body --}}

            {{-- FOOTER --}}
            <div class="modal-footer">
                <button type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal">
                    Abbrechen
                </button>

                @if(!$readonly)
                    {{-- Kein type="submit", kein <form>.
                         JS kann auf .btn-save-position klicken hören,
                         Daten aus den .js-pos-* Feldern lesen
                         und per fetch()/AJAX an data-update-url schicken. --}}
                    <button type="button"
                            class="btn btn-primary btn-save-position"
                            data-position-id="{{ $position->id }}">
                        <i class="bi bi-save"></i>
                        Änderungen speichern
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>
