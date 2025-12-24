{{-- resources/views/gebaeude/partials/_log_modals.blade.php --}}
{{-- WICHTIG: Diese Datei AUSSERHALB des Hauptformulars einbinden! --}}
{{-- Einbinden am Ende der Seite: @include('gebaeude.partials._log_modals', ['gebaeude' => $gebaeude]) --}}

@push('styles')
<style>
/* Mobile-Optimierung fuer Log-Modals */
@media (max-width: 767.98px) {
    #modalNotiz .form-control,
    #modalNotiz .form-select,
    #modalTelefonat .form-control,
    #modalTelefonat .form-select,
    #modalProblem .form-control,
    #modalProblem .form-select,
    #modalNeuerLog .form-control,
    #modalNeuerLog .form-select {
        min-height: 44px;
        font-size: 16px !important;
    }
    
    #modalNotiz .btn,
    #modalTelefonat .btn,
    #modalProblem .btn,
    #modalNeuerLog .btn {
        min-height: 44px;
        font-size: 16px !important;
    }
    
    .modal-fullscreen-sm-down .modal-body {
        padding: 1rem;
    }
    
    .modal-fullscreen-sm-down .modal-footer {
        position: sticky;
        bottom: 0;
        background: #fff;
        border-top: 1px solid #dee2e6;
        padding: 1rem;
        margin-top: auto;
    }
}
</style>
@endpush

@if(isset($gebaeude) && $gebaeude->id)

{{-- ═══════════════════════════════════════════════════════════ --}}
{{-- MODAL: Notiz --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="modalNotiz" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
        <div class="modal-content">
            <form method="POST" action="{{ route('gebaeude.logs.notiz', $gebaeude->id) }}">
                @csrf
                <div class="modal-header bg-secondary text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-sticky-fill me-2"></i>Notiz hinzufuegen
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="notiz_beschreibung" class="form-label">
                            Notiz <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control" id="notiz_beschreibung" name="beschreibung" 
                                  rows="5" required maxlength="2000"
                                  placeholder="Was soll festgehalten werden?"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notiz_prioritaet" class="form-label">Prioritaet</label>
                        <select class="form-select" id="notiz_prioritaet" name="prioritaet">
                            <option value="niedrig">Niedrig</option>
                            <option value="normal" selected>Normal</option>
                            <option value="hoch">Hoch</option>
                            <option value="kritisch">Kritisch</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Abbrechen
                    </button>
                    <button type="submit" class="btn btn-secondary">
                        <i class="bi bi-check2 me-1"></i> Speichern
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════ --}}
{{-- MODAL: Telefonat --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="modalTelefonat" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
        <div class="modal-content">
            <form method="POST" action="{{ route('gebaeude.logs.telefonat', $gebaeude->id) }}">
                @csrf
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-telephone-fill me-2"></i>Telefonat protokollieren
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label for="tel_kontakt_person" class="form-label">Gespraechspartner</label>
                            <input type="text" class="form-control" id="tel_kontakt_person" 
                                   name="kontakt_person" maxlength="100"
                                   placeholder="Name der Person">
                        </div>
                        <div class="col-sm-6">
                            <label for="tel_kontakt_telefon" class="form-label">Telefonnummer</label>
                            <input type="text" class="form-control" id="tel_kontakt_telefon" 
                                   name="kontakt_telefon" maxlength="50"
                                   placeholder="+39 ...">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="tel_beschreibung" class="form-label">
                            Gespraechsinhalt <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control" id="tel_beschreibung" name="beschreibung" 
                                  rows="5" required maxlength="2000"
                                  placeholder="Was wurde besprochen?"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Abbrechen
                    </button>
                    <button type="submit" class="btn btn-info">
                        <i class="bi bi-check2 me-1"></i> Speichern
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════ --}}
{{-- MODAL: Problem / Reklamation --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="modalProblem" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
        <div class="modal-content">
            <form method="POST" action="{{ route('gebaeude.logs.problem', $gebaeude->id) }}">
                @csrf
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>Problem melden
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label for="prob_typ" class="form-label">Art des Problems <span class="text-danger">*</span></label>
                            <select class="form-select" id="prob_typ" name="typ" required>
                                <option value="">-- Bitte waehlen --</option>
                                <option value="reklamation">Reklamation</option>
                                <option value="problem">Problem</option>
                                <option value="mangel">Mangel</option>
                                <option value="schadensmeldung">Schadensmeldung</option>
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label for="prob_prioritaet" class="form-label">Prioritaet</label>
                            <select class="form-select" id="prob_prioritaet" name="prioritaet">
                                <option value="normal">Normal</option>
                                <option value="hoch" selected>Hoch</option>
                                <option value="kritisch">Kritisch</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="prob_kontakt_person" class="form-label">Gemeldet von</label>
                        <input type="text" class="form-control" id="prob_kontakt_person" 
                               name="kontakt_person" maxlength="100"
                               placeholder="Name der Person (Hausmeister, Mieter, etc.)">
                    </div>
                    
                    <div class="mb-3">
                        <label for="prob_beschreibung" class="form-label">
                            Beschreibung <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control" id="prob_beschreibung" name="beschreibung" 
                                  rows="5" required maxlength="2000"
                                  placeholder="Was ist passiert? Was wurde beanstandet?"></textarea>
                    </div>
                    
                    <div class="alert alert-warning py-2 small mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        Probleme erscheinen in der Uebersicht und koennen spaeter als erledigt markiert werden.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Abbrechen
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-exclamation-triangle me-1"></i> Problem melden
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════ --}}
{{-- MODAL: Neuer Log-Eintrag (erweitert) --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="modalNeuerLog" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-fullscreen-sm-down">
        <div class="modal-content">
            <form method="POST" action="{{ route('gebaeude.logs.store', $gebaeude->id) }}">
                @csrf
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-plus-circle me-2"></i>Neuer Eintrag
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label for="log_typ" class="form-label">Typ <span class="text-danger">*</span></label>
                            <select class="form-select" id="log_typ" name="typ" required>
                                <option value="">-- Bitte waehlen --</option>
                                <optgroup label="Kommunikation">
                                    <option value="notiz">Notiz</option>
                                    <option value="telefonat">Telefonat</option>
                                    <option value="email_versandt">E-Mail versandt</option>
                                    <option value="email_empfangen">E-Mail empfangen</option>
                                    <option value="besichtigung">Besichtigung</option>
                                    <option value="kundenkontakt">Kundenkontakt</option>
                                </optgroup>
                                <optgroup label="Probleme">
                                    <option value="reklamation">Reklamation</option>
                                    <option value="problem">Problem</option>
                                    <option value="mangel">Mangel</option>
                                    <option value="schadensmeldung">Schadensmeldung</option>
                                </optgroup>
                                <optgroup label="Erinnerungen">
                                    <option value="erinnerung">Erinnerung</option>
                                    <option value="wiedervorlage">Wiedervorlage</option>
                                </optgroup>
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label for="log_prioritaet" class="form-label">Prioritaet</label>
                            <select class="form-select" id="log_prioritaet" name="prioritaet">
                                <option value="niedrig">Niedrig</option>
                                <option value="normal" selected>Normal</option>
                                <option value="hoch">Hoch</option>
                                <option value="kritisch">Kritisch</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3 mt-3">
                        <label for="log_beschreibung" class="form-label">
                            Beschreibung <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control" id="log_beschreibung" name="beschreibung" 
                                  rows="4" required maxlength="5000"></textarea>
                    </div>
                    
                    <hr>
                    <h6 class="text-muted mb-3">
                        <i class="bi bi-person me-1"></i> Kontakt (optional)
                    </h6>
                    
                    <div class="row g-3">
                        <div class="col-sm-4">
                            <label for="log_kontakt_person" class="form-label">Person</label>
                            <input type="text" class="form-control" id="log_kontakt_person" 
                                   name="kontakt_person" maxlength="100">
                        </div>
                        <div class="col-sm-4">
                            <label for="log_kontakt_telefon" class="form-label">Telefon</label>
                            <input type="text" class="form-control" id="log_kontakt_telefon" 
                                   name="kontakt_telefon" maxlength="50">
                        </div>
                        <div class="col-sm-4">
                            <label for="log_kontakt_email" class="form-label">E-Mail</label>
                            <input type="email" class="form-control" id="log_kontakt_email" 
                                   name="kontakt_email" maxlength="100">
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <label for="log_erinnerung_datum" class="form-label">
                            <i class="bi bi-bell me-1"></i> Erinnerung am
                        </label>
                        <input type="date" class="form-control" id="log_erinnerung_datum" 
                               name="erinnerung_datum" style="max-width: 200px;">
                        <small class="text-muted">Optional: Wiedervorlage-Datum</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Abbrechen
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check2 me-1"></i> Speichern
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endif
