{{-- 
    Partial: _dokumente_modals.blade.php
    AUSSERHALB des Hauptformulars einbinden!
    
    Benötigt: $gebaeude
--}}

<!-- DEBUG: Dokumente-Modals geladen für Gebäude #{{ $gebaeude->id ?? 'NICHT GESETZT' }} -->

@if(isset($gebaeude) && $gebaeude->id)
    @php
        $kategorien = \App\Models\GebaeudeDocument::KATEGORIEN;
        $erlaubteEndungen = \App\Models\GebaeudeDocument::erlaubteEndungen();
        $maxSize = \App\Models\GebaeudeDocument::maxDateigroesse() / 1024 / 1024; // MB
    @endphp

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- 📷 Modal: Foto aufnehmen (Mobile) --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    <div class="modal fade" id="modalFotoAufnehmen" tabindex="-1">
        <div class="modal-dialog modal-fullscreen-sm-down">
            <div class="modal-content">
                <form method="POST" 
                      action="{{ route('gebaeude.dokumente.store', $gebaeude->id) }}" 
                      enctype="multipart/form-data"
                      id="formFotoAufnehmen">
                    @csrf
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title">
                            <i class="bi bi-camera-fill me-2"></i>Foto aufnehmen
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        {{-- Kamera-Auswahl --}}
                        <div class="mb-4">
                            <div class="d-grid gap-2">
                                {{-- Kamera Button --}}
                                <label class="btn btn-lg btn-outline-success d-flex align-items-center justify-content-center gap-2 py-4" 
                                       for="fotoKamera">
                                    <i class="bi bi-camera-fill fs-1"></i>
                                    <span>Kamera öffnen</span>
                                </label>
                                <input type="file" 
                                       class="d-none" 
                                       id="fotoKamera" 
                                       name="datei"
                                       accept="image/*"
                                       capture="environment">
                                
                                {{-- Galerie Button --}}
                                <label class="btn btn-lg btn-outline-primary d-flex align-items-center justify-content-center gap-2 py-4" 
                                       for="fotoGalerie">
                                    <i class="bi bi-images fs-1"></i>
                                    <span>Aus Galerie wählen</span>
                                </label>
                                <input type="file" 
                                       class="d-none" 
                                       id="fotoGalerie" 
                                       name="datei_galerie"
                                       accept="image/*">
                            </div>
                        </div>

                        {{-- Vorschau --}}
                        <div class="mb-3 text-center d-none" id="fotoPreview">
                            <img src="" alt="Vorschau" class="img-fluid rounded border" style="max-height: 250px;">
                        </div>

                        {{-- Titel (optional) --}}
                        <div class="mb-3">
                            <label for="fotoTitel" class="form-label">Titel (optional)</label>
                            <input type="text" class="form-control" id="fotoTitel" name="titel" 
                                   placeholder="z.B. Eingangsbereich, Schaden Treppe...">
                        </div>

                        {{-- Kategorie --}}
                        <div class="mb-3">
                            <label for="fotoKategorie" class="form-label">Kategorie</label>
                            <select class="form-select" id="fotoKategorie" name="kategorie">
                                <option value="foto" selected>Foto</option>
                                <option value="protokoll">Protokoll</option>
                                <option value="sonstiges">Sonstiges</option>
                            </select>
                        </div>

                        {{-- Wichtig --}}
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="fotoWichtig" name="ist_wichtig" value="1">
                            <label class="form-check-label" for="fotoWichtig">
                                <i class="bi bi-star-fill text-warning"></i> Als wichtig markieren
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                        <button type="submit" class="btn btn-success" id="btnFotoSpeichern" disabled>
                            <i class="bi bi-check-lg me-1"></i>Speichern
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- 📁 Modal: Datei Upload --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    <div class="modal fade" id="modalDokumentUpload" tabindex="-1">
        <div class="modal-dialog modal-fullscreen-sm-down modal-lg">
            <div class="modal-content">
                <form method="POST" 
                      action="{{ route('gebaeude.dokumente.store', $gebaeude->id) }}" 
                      enctype="multipart/form-data"
                      id="formDokumentUpload"
                      onsubmit="return validateUploadForm()">
                    @csrf
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">
                            <i class="bi bi-upload me-2"></i>Dokument hochladen
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        {{-- Mobile: Auswahl-Buttons --}}
                        <div class="d-sm-none mb-4">
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="btn btn-outline-success w-100 py-3" for="uploadDateiKamera">
                                        <i class="bi bi-camera-fill d-block fs-3 mb-1"></i>
                                        <small>Kamera</small>
                                    </label>
                                    <input type="file" class="d-none upload-datei-input" id="uploadDateiKamera" 
                                           accept="image/*" capture="environment">
                                </div>
                                <div class="col-6">
                                    <label class="btn btn-outline-info w-100 py-3" for="uploadDateiGalerie">
                                        <i class="bi bi-images d-block fs-3 mb-1"></i>
                                        <small>Galerie</small>
                                    </label>
                                    <input type="file" class="d-none upload-datei-input" id="uploadDateiGalerie" 
                                           accept="image/*">
                                </div>
                                <div class="col-12">
                                    <label class="btn btn-outline-primary w-100 py-3" for="uploadDateiDatei">
                                        <i class="bi bi-file-earmark d-block fs-3 mb-1"></i>
                                        <small>Datei wählen (PDF, DOC, XLS...)</small>
                                    </label>
                                    <input type="file" class="d-none upload-datei-input" id="uploadDateiDatei"
                                           accept=".{{ implode(',.', $erlaubteEndungen) }}">
                                </div>
                            </div>
                        </div>

                        {{-- Desktop: Standard File Input --}}
                        <div class="d-none d-sm-block mb-4">
                            <label for="uploadDatei" class="form-label">
                                Datei auswählen <span class="text-danger">*</span>
                            </label>
                            <input type="file" 
                                   class="form-control form-control-lg upload-datei-input" 
                                   id="uploadDatei" 
                                   accept=".{{ implode(',.', $erlaubteEndungen) }}">
                            <div class="form-text">
                                Erlaubt: PDF, DOC(X), XLS(X), Bilder, TXT (max. {{ $maxSize }} MB)
                            </div>
                        </div>

                        {{-- HAUPT-INPUT für Form-Submit (wird per JS befüllt) --}}
                        <input type="file" class="d-none" id="uploadDateiHidden" name="datei">

                        {{-- Vorschau --}}
                        <div class="mb-3 text-center d-none" id="uploadPreview">
                            <img src="" alt="Vorschau" class="img-thumbnail" style="max-height: 200px;">
                        </div>

                        {{-- Ausgewählte Datei anzeigen --}}
                        <div class="alert alert-info d-none mb-3" id="uploadDateiInfo">
                            <i class="bi bi-file-earmark me-2"></i>
                            <span id="uploadDateiName"></span>
                            <span class="badge bg-secondary ms-2" id="uploadDateiGroesse"></span>
                        </div>

                        {{-- Fehler-Meldung --}}
                        <div class="alert alert-danger d-none mb-3" id="uploadFehler">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            Bitte wählen Sie eine Datei aus.
                        </div>

                        <div class="row g-3">
                            {{-- Titel --}}
                            <div class="col-12">
                                <label for="uploadTitel" class="form-label">Titel</label>
                                <input type="text" class="form-control" id="uploadTitel" name="titel" 
                                       placeholder="Wird aus Dateinamen generiert, falls leer">
                            </div>

                            {{-- Kategorie --}}
                            <div class="col-12 col-sm-6">
                                <label for="uploadKategorie" class="form-label">Kategorie</label>
                                <select class="form-select" id="uploadKategorie" name="kategorie">
                                    <option value="">Automatisch erkennen</option>
                                    @foreach($kategorien as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Datum --}}
                            <div class="col-12 col-sm-6">
                                <label for="uploadDatum" class="form-label">Dokument-Datum</label>
                                <input type="date" class="form-control" id="uploadDatum" name="dokument_datum">
                            </div>

                            {{-- Beschreibung --}}
                            <div class="col-12">
                                <label for="uploadBeschreibung" class="form-label">Beschreibung</label>
                                <textarea class="form-control" id="uploadBeschreibung" name="beschreibung" 
                                          rows="2" placeholder="Optional"></textarea>
                            </div>

                            {{-- Tags --}}
                            <div class="col-12 col-sm-8">
                                <label for="uploadTags" class="form-label">Tags</label>
                                <input type="text" class="form-control" id="uploadTags" name="tags" 
                                       placeholder="z.B. wichtig, 2024, wartung">
                            </div>

                            {{-- Wichtig --}}
                            <div class="col-12 col-sm-4 d-flex align-items-end">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="uploadWichtig" name="ist_wichtig" value="1">
                                    <label class="form-check-label" for="uploadWichtig">
                                        <i class="bi bi-star-fill text-warning"></i> Wichtig
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                        <button type="submit" class="btn btn-primary" id="btnUpload">
                            <i class="bi bi-upload me-1"></i>Hochladen
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- ✏️ Modal: Bearbeiten --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    <div class="modal fade" id="modalDokumentEdit" tabindex="-1">
        <div class="modal-dialog modal-fullscreen-sm-down">
            <div class="modal-content">
                <form method="POST" id="formDokumentEdit">
                    @csrf
                    @method('PUT')
                    <div class="modal-header bg-secondary text-white">
                        <h5 class="modal-title">
                            <i class="bi bi-pencil me-2"></i>Dokument bearbeiten
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            {{-- Titel --}}
                            <div class="col-12">
                                <label for="editTitel" class="form-label">Titel <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="editTitel" name="titel" required>
                            </div>

                            {{-- Kategorie --}}
                            <div class="col-12 col-sm-6">
                                <label for="editKategorie" class="form-label">Kategorie</label>
                                <select class="form-select" id="editKategorie" name="kategorie">
                                    <option value="">Keine</option>
                                    @foreach($kategorien as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Datum --}}
                            <div class="col-12 col-sm-6">
                                <label for="editDatum" class="form-label">Dokument-Datum</label>
                                <input type="date" class="form-control" id="editDatum" name="dokument_datum">
                            </div>

                            {{-- Beschreibung --}}
                            <div class="col-12">
                                <label for="editBeschreibung" class="form-label">Beschreibung</label>
                                <textarea class="form-control" id="editBeschreibung" name="beschreibung" rows="3"></textarea>
                            </div>

                            {{-- Tags --}}
                            <div class="col-12">
                                <label for="editTags" class="form-label">Tags</label>
                                <input type="text" class="form-control" id="editTags" name="tags" 
                                       placeholder="Kommagetrennt">
                            </div>

                            {{-- Wichtig --}}
                            <div class="col-12">
                                <div class="form-check form-check-lg py-2">
                                    <input type="checkbox" class="form-check-input" id="editWichtig" name="ist_wichtig" value="1" style="width: 1.5em; height: 1.5em;">
                                    <label class="form-check-label ms-2" for="editWichtig">
                                        <i class="bi bi-star-fill text-warning"></i> Als wichtig markieren
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i>Speichern
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- 🗑️ Modal: Löschen --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    <div class="modal fade" id="modalDokumentDelete" tabindex="-1">
        <div class="modal-dialog modal-fullscreen-sm-down">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-trash me-2"></i>Dokument löschen?
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="fs-5">Möchten Sie das Dokument <strong id="deleteDokTitel"></strong> wirklich löschen?</p>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Diese Aktion kann nicht rückgängig gemacht werden!
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <form method="POST" id="formDokumentDelete">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-trash me-1"></i>Löschen
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- 📜 JavaScript --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    <script>
    // Validierung für Upload-Form
    function validateUploadForm() {
        const hiddenInput = document.getElementById('uploadDateiHidden');
        const fehlerDiv = document.getElementById('uploadFehler');
        
        if (!hiddenInput || !hiddenInput.files || hiddenInput.files.length === 0) {
            fehlerDiv.classList.remove('d-none');
            return false;
        }
        
        fehlerDiv.classList.add('d-none');
        return true;
    }

    document.addEventListener('DOMContentLoaded', function() {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

        // ═══════════════════════════════════════════════════════════
        // 📷 Foto aufnehmen Modal
        // ═══════════════════════════════════════════════════════════
        const fotoKamera = document.getElementById('fotoKamera');
        const fotoGalerie = document.getElementById('fotoGalerie');
        const fotoPreview = document.getElementById('fotoPreview');
        const btnFotoSpeichern = document.getElementById('btnFotoSpeichern');
        const formFoto = document.getElementById('formFotoAufnehmen');

        function handleFotoSelect(input) {
            const file = input.files[0];
            if (!file) return;

            // Vorschau anzeigen
            const reader = new FileReader();
            reader.onload = function(e) {
                fotoPreview.querySelector('img').src = e.target.result;
                fotoPreview.classList.remove('d-none');
            };
            reader.readAsDataURL(file);

            // Speichern aktivieren
            btnFotoSpeichern.disabled = false;

            // Datei in Hauptinput kopieren (für Form-Submit)
            const mainInput = formFoto.querySelector('input[name="datei"]');
            const dt = new DataTransfer();
            dt.items.add(file);
            mainInput.files = dt.files;
        }

        if (fotoKamera) {
            fotoKamera.addEventListener('change', function() { handleFotoSelect(this); });
        }
        if (fotoGalerie) {
            fotoGalerie.addEventListener('change', function() {
                // Galerie-Datei in Kamera-Input kopieren (verwendet dasselbe name-Attribut)
                if (this.files[0]) {
                    const dt = new DataTransfer();
                    dt.items.add(this.files[0]);
                    fotoKamera.files = dt.files;
                    handleFotoSelect(fotoKamera);
                }
            });
        }

        // Reset bei Modal-Schließen
        document.getElementById('modalFotoAufnehmen')?.addEventListener('hidden.bs.modal', function() {
            formFoto.reset();
            fotoPreview.classList.add('d-none');
            btnFotoSpeichern.disabled = true;
        });

        // ═══════════════════════════════════════════════════════════
        // 📁 Datei Upload Modal
        // ═══════════════════════════════════════════════════════════
        const uploadPreview = document.getElementById('uploadPreview');
        const uploadDateiInfo = document.getElementById('uploadDateiInfo');
        const uploadFehler = document.getElementById('uploadFehler');
        const uploadTitel = document.getElementById('uploadTitel');
        const uploadDateiHidden = document.getElementById('uploadDateiHidden');
        const formUpload = document.getElementById('formDokumentUpload');

        // Alle Datei-Inputs (Mobile + Desktop)
        document.querySelectorAll('.upload-datei-input').forEach(input => {
            input.addEventListener('change', function() {
                const file = this.files[0];
                if (!file) return;

                // In Hidden-Input kopieren für Form-Submit
                const dt = new DataTransfer();
                dt.items.add(file);
                uploadDateiHidden.files = dt.files;

                handleFileSelected(file);
            });
        });

        function handleFileSelected(file) {
            // Fehler ausblenden
            uploadFehler.classList.add('d-none');
            
            // Titel vorschlagen
            if (!uploadTitel.value) {
                uploadTitel.value = file.name.replace(/\.[^/.]+$/, '');
            }

            // Datei-Info anzeigen
            document.getElementById('uploadDateiName').textContent = file.name;
            document.getElementById('uploadDateiGroesse').textContent = formatFileSize(file.size);
            uploadDateiInfo.classList.remove('d-none');

            // Bild-Vorschau
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    uploadPreview.querySelector('img').src = e.target.result;
                    uploadPreview.classList.remove('d-none');
                };
                reader.readAsDataURL(file);
            } else {
                uploadPreview.classList.add('d-none');
            }
        }

        function formatFileSize(bytes) {
            if (bytes >= 1048576) return (bytes / 1048576).toFixed(1) + ' MB';
            if (bytes >= 1024) return (bytes / 1024).toFixed(0) + ' KB';
            return bytes + ' B';
        }

        // Reset bei Modal-Schließen
        document.getElementById('modalDokumentUpload')?.addEventListener('hidden.bs.modal', function() {
            formUpload.reset();
            uploadPreview.classList.add('d-none');
            uploadDateiInfo.classList.add('d-none');
            uploadFehler.classList.add('d-none');
            // Hidden Input leeren
            uploadDateiHidden.value = '';
        });

        // ═══════════════════════════════════════════════════════════
        // ✏️ Edit Modal
        // ═══════════════════════════════════════════════════════════
        const modalEdit = document.getElementById('modalDokumentEdit');
        const formEdit = document.getElementById('formDokumentEdit');

        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.btn-edit-dokument');
            if (!btn) return;

            const id = btn.dataset.id;
            formEdit.action = `/gebaeude/dokumente/${id}`;
            document.getElementById('editTitel').value = btn.dataset.titel || '';
            document.getElementById('editBeschreibung').value = btn.dataset.beschreibung || '';
            document.getElementById('editKategorie').value = btn.dataset.kategorie || '';
            document.getElementById('editTags').value = btn.dataset.tags || '';
            document.getElementById('editWichtig').checked = btn.dataset.wichtig === '1';

            new bootstrap.Modal(modalEdit).show();
        });

        // ═══════════════════════════════════════════════════════════
        // 🗑️ Delete Modal
        // ═══════════════════════════════════════════════════════════
        const formDelete = document.getElementById('formDokumentDelete');

        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.btn-delete-dokument');
            if (!btn) return;

            const id = btn.dataset.id;
            const titel = btn.dataset.titel;

            document.getElementById('deleteDokTitel').textContent = titel;
            formDelete.action = `/gebaeude/dokumente/${id}`;

            new bootstrap.Modal(document.getElementById('modalDokumentDelete')).show();
        });

        // ═══════════════════════════════════════════════════════════
        // ⭐ Toggle Wichtig (AJAX)
        // ═══════════════════════════════════════════════════════════
        document.addEventListener('click', async function(e) {
            const btn = e.target.closest('.btn-toggle-wichtig');
            if (!btn) return;

            const id = btn.dataset.id;
            
            try {
                const res = await fetch(`/gebaeude/dokumente/${id}/wichtig`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                });
                
                const data = await res.json();
                if (data.ok) {
                    // Seite neu laden für korrekte Anzeige
                    location.reload();
                }
            } catch (e) {
                console.error('Fehler:', e);
            }
        });
    });
    </script>
@endif
