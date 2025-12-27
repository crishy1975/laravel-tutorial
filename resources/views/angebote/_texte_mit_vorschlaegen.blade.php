{{-- 
    resources/views/angebote/_texte_mit_vorschlaegen.blade.php
    
    Diesen Block in die edit.blade.php im Bereich "Texte" einfügen
    
    Benötigt: $textvorschlaege (aus Controller) oder lädt per AJAX
--}}

{{-- Texte Card --}}
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-secondary text-white py-2">
        <i class="bi bi-card-text me-2"></i>
        <span class="fw-semibold">Texte</span>
    </div>
    <div class="card-body">
        
        {{-- Einleitung --}}
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <label class="form-label fw-semibold mb-0">
                    <i class="bi bi-text-paragraph text-secondary me-1"></i>
                    Einleitung (vor Positionen)
                </label>
                <button type="button" class="btn btn-outline-secondary btn-sm" 
                        onclick="toggleVorschlaege('einleitung')" title="Vorschläge anzeigen">
                    <i class="bi bi-lightbulb"></i>
                    <span class="d-none d-sm-inline ms-1">Vorschläge</span>
                </button>
            </div>
            <div id="vorschlaege-einleitung" class="mb-2 d-none">
                <div class="d-flex flex-wrap gap-1" id="vorschlaege-einleitung-liste">
                    <span class="text-muted small">Lade...</span>
                </div>
            </div>
            <textarea name="einleitung" id="einleitung" class="form-control" rows="3" 
                      placeholder="Optional: Text vor den Positionen">{{ old('einleitung', $angebot->einleitung) }}</textarea>
        </div>

        {{-- Bemerkung für Kunde --}}
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <label class="form-label fw-semibold mb-0">
                    <i class="bi bi-chat-left-text text-secondary me-1"></i>
                    Bemerkung für Kunde (auf PDF)
                </label>
                <button type="button" class="btn btn-outline-secondary btn-sm" 
                        onclick="toggleVorschlaege('bemerkung_kunde')" title="Vorschläge anzeigen">
                    <i class="bi bi-lightbulb"></i>
                    <span class="d-none d-sm-inline ms-1">Vorschläge</span>
                </button>
            </div>
            <div id="vorschlaege-bemerkung_kunde" class="mb-2 d-none">
                <div class="d-flex flex-wrap gap-1" id="vorschlaege-bemerkung_kunde-liste">
                    <span class="text-muted small">Lade...</span>
                </div>
            </div>
            <textarea name="bemerkung_kunde" id="bemerkung_kunde" class="form-control" rows="3" 
                      placeholder="Erscheint auf dem PDF">{{ old('bemerkung_kunde', $angebot->bemerkung_kunde) }}</textarea>
        </div>

        {{-- Interne Bemerkung --}}
        <div class="mb-0">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <label class="form-label fw-semibold mb-0">
                    <i class="bi bi-lock text-warning me-1"></i>
                    Interne Bemerkung 
                    <span class="badge bg-warning text-dark ms-1">nicht auf PDF</span>
                </label>
                <button type="button" class="btn btn-outline-secondary btn-sm" 
                        onclick="toggleVorschlaege('bemerkung_intern')" title="Vorschläge anzeigen">
                    <i class="bi bi-lightbulb"></i>
                    <span class="d-none d-sm-inline ms-1">Vorschläge</span>
                </button>
            </div>
            <div id="vorschlaege-bemerkung_intern" class="mb-2 d-none">
                <div class="d-flex flex-wrap gap-1" id="vorschlaege-bemerkung_intern-liste">
                    <span class="text-muted small">Lade...</span>
                </div>
            </div>
            <textarea name="bemerkung_intern" id="bemerkung_intern" class="form-control" rows="2" 
                      placeholder="Nur intern sichtbar">{{ old('bemerkung_intern', $angebot->bemerkung_intern) }}</textarea>
        </div>

    </div>
</div>

@push('styles')
<style>
.vorschlag-btn {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
    max-width: 300px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    cursor: pointer;
    transition: all 0.2s ease;
}
.vorschlag-btn:hover {
    background-color: #198754 !important;
    color: white !important;
    border-color: #198754 !important;
}
.vorschlaege-container {
    background: #f8f9fa;
    border-radius: 0.375rem;
    padding: 0.75rem;
    border: 1px dashed #dee2e6;
}
</style>
@endpush

@push('scripts')
<script>
// Textvorschläge Cache
let textvorschlaegeCache = null;

// Vorschläge laden
async function ladeTextvorschlaege() {
    if (textvorschlaegeCache) return textvorschlaegeCache;
    
    try {
        const response = await fetch('{{ route("angebote.textvorschlaege") }}');
        textvorschlaegeCache = await response.json();
        return textvorschlaegeCache;
    } catch (error) {
        console.error('Fehler beim Laden der Vorschläge:', error);
        return { einleitung: [], bemerkung_kunde: [], bemerkung_intern: [] };
    }
}

// Vorschläge anzeigen/verstecken
async function toggleVorschlaege(feld) {
    const container = document.getElementById('vorschlaege-' + feld);
    const liste = document.getElementById('vorschlaege-' + feld + '-liste');
    
    if (container.classList.contains('d-none')) {
        // Vorschläge laden und anzeigen
        const vorschlaege = await ladeTextvorschlaege();
        const feldVorschlaege = vorschlaege[feld] || [];
        
        if (feldVorschlaege.length === 0) {
            liste.innerHTML = '<span class="text-muted small"><i class="bi bi-info-circle me-1"></i>Keine Vorschläge vorhanden</span>';
        } else {
            liste.innerHTML = feldVorschlaege.map(text => {
                // Text kürzen für Button-Anzeige
                const kurztext = text.length > 50 ? text.substring(0, 50) + '...' : text;
                // Text escapen für onclick
                const escapedText = text.replace(/'/g, "\\'").replace(/\n/g, "\\n");
                return `<button type="button" class="btn btn-outline-secondary vorschlag-btn" 
                                onclick="setzeVorschlag('${feld}', '${escapedText}')" 
                                title="${text.replace(/"/g, '&quot;')}">
                            ${kurztext}
                        </button>`;
            }).join('');
        }
        
        container.classList.remove('d-none');
        container.classList.add('vorschlaege-container');
    } else {
        container.classList.add('d-none');
        container.classList.remove('vorschlaege-container');
    }
}

// Vorschlag in Textfeld einfügen
function setzeVorschlag(feld, text) {
    const textarea = document.getElementById(feld);
    // Umwandlung von escaped newlines zurück
    text = text.replace(/\\n/g, "\n");
    
    if (textarea.value && textarea.value.trim() !== '') {
        // Bestehenden Text ergänzen oder ersetzen?
        if (confirm('Bestehenden Text ersetzen?\n\nOK = Ersetzen\nAbbrechen = Anhängen')) {
            textarea.value = text;
        } else {
            textarea.value = textarea.value + '\n\n' + text;
        }
    } else {
        textarea.value = text;
    }
    
    // Vorschläge schließen
    document.getElementById('vorschlaege-' + feld).classList.add('d-none');
    
    // Focus auf Textfeld
    textarea.focus();
}
</script>
@endpush
