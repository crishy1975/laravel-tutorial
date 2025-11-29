{{-- resources/views/rechnung/partials/_logs.blade.php --}}
{{-- Log-System Tab für Rechnungsformular --}}

@php
    use App\Models\RechnungLog;
    use App\Enums\RechnungLogTyp;
    
    $logs = RechnungLog::where('rechnung_id', $rechnung->id)
        ->with('user')
        ->chronologisch()
        ->limit(100)
        ->get();
    
    $offeneErinnerungen = RechnungLog::where('rechnung_id', $rechnung->id)
        ->offeneErinnerungen()
        ->count();
@endphp

<div class="row g-4">

    {{-- ═══════════════════════════════════════════════════════════
        QUICK ACTIONS
    ═══════════════════════════════════════════════════════════ --}}
    <div class="col-12">
        <div class="card border-primary">
            <div class="card-header bg-primary text-white py-2">
                <h6 class="mb-0"><i class="bi bi-plus-circle"></i> Neuer Eintrag</h6>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-md-2">
                        <button type="button" class="btn btn-outline-secondary w-100" 
                                onclick="openLogModal('telefonat')">
                            <i class="bi bi-telephone"></i><br>
                            <small>Telefonat</small>
                        </button>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-outline-secondary w-100"
                                onclick="openLogModal('mitteilung_kunde')">
                            <i class="bi bi-chat-left-text"></i><br>
                            <small>Kundenmitteilung</small>
                        </button>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-outline-secondary w-100"
                                onclick="openLogModal('notiz')">
                            <i class="bi bi-sticky"></i><br>
                            <small>Notiz</small>
                        </button>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-outline-warning w-100"
                                onclick="openLogModal('erinnerung')">
                            <i class="bi bi-bell"></i><br>
                            <small>Erinnerung</small>
                        </button>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-outline-danger w-100"
                                onclick="openLogModal('mahnung_erstellt')">
                            <i class="bi bi-exclamation-triangle"></i><br>
                            <small>Mahnung</small>
                        </button>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-outline-success w-100"
                                onclick="openLogModal('zahlung_eingegangen')">
                            <i class="bi bi-currency-euro"></i><br>
                            <small>Zahlung</small>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
        STATISTIKEN
    ═══════════════════════════════════════════════════════════ --}}
    <div class="col-md-3">
        <div class="card text-center h-100">
            <div class="card-body">
                <h3 class="mb-0">{{ $logs->count() }}</h3>
                <small class="text-muted">Einträge gesamt</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center h-100 {{ $offeneErinnerungen > 0 ? 'border-warning' : '' }}">
            <div class="card-body">
                <h3 class="mb-0 {{ $offeneErinnerungen > 0 ? 'text-warning' : '' }}">
                    {{ $offeneErinnerungen }}
                </h3>
                <small class="text-muted">Offene Erinnerungen</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center h-100">
            <div class="card-body">
                <h3 class="mb-0">{{ $logs->where('kategorie', 'kommunikation')->count() }}</h3>
                <small class="text-muted">Kommunikation</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center h-100">
            <div class="card-body">
                <h3 class="mb-0">{{ $logs->where('kategorie', 'dokument')->count() }}</h3>
                <small class="text-muted">Dokumente</small>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
        LOG-TIMELINE
    ═══════════════════════════════════════════════════════════ --}}
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-clock-history"></i> Aktivitäten</h6>
                @if($logs->count() > 50)
                    <a href="{{ route('rechnung.logs.index', $rechnung->id) }}" class="btn btn-sm btn-outline-primary">
                        Alle anzeigen
                    </a>
                @endif
            </div>
            <div class="card-body p-0">
                @if($logs->isEmpty())
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                        <p class="mt-2">Noch keine Log-Einträge vorhanden.</p>
                    </div>
                @else
                    <div class="timeline-container" style="max-height: 600px; overflow-y: auto;">
                        @foreach($logs as $log)
                            <div class="timeline-item border-bottom p-3 {{ $log->prioritaet === 'kritisch' ? 'bg-danger bg-opacity-10' : ($log->prioritaet === 'hoch' ? 'bg-warning bg-opacity-10' : '') }}">
                                <div class="d-flex">
                                    {{-- Icon --}}
                                    <div class="me-3">
                                        <span class="badge bg-{{ $log->farbe }} rounded-circle p-2">
                                            <i class="{{ $log->icon }}"></i>
                                        </span>
                                    </div>
                                    
                                    {{-- Content --}}
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <strong>{{ $log->titel }}</strong>
                                                {!! $log->typ_badge !!}
                                                @if($log->prioritaet !== 'normal')
                                                    {!! $log->prioritaet_badge !!}
                                                @endif
                                            </div>
                                            <small class="text-muted">
                                                {{ $log->datum_formatiert }}
                                                <span class="d-none d-md-inline">({{ $log->zeit_relativ }})</span>
                                            </small>
                                        </div>
                                        
                                        @if($log->beschreibung)
                                            <p class="mb-1 mt-1 text-secondary">
                                                {{ Str::limit($log->beschreibung, 200) }}
                                            </p>
                                        @endif
                                        
                                        {{-- Kontakt-Info --}}
                                        @if($log->kontakt_person || $log->kontakt_telefon || $log->kontakt_email)
                                            <div class="small text-muted mt-1">
                                                @if($log->kontakt_person)
                                                    <i class="bi bi-person"></i> {{ $log->kontakt_person }}
                                                @endif
                                                @if($log->kontakt_telefon)
                                                    <i class="bi bi-telephone ms-2"></i> {{ $log->kontakt_telefon }}
                                                @endif
                                                @if($log->kontakt_email)
                                                    <i class="bi bi-envelope ms-2"></i> {{ $log->kontakt_email }}
                                                @endif
                                            </div>
                                        @endif
                                        
                                        {{-- Erinnerung --}}
                                        @if($log->erinnerung_datum)
                                            <div class="mt-1">
                                                @if($log->erinnerung_erledigt)
                                                    <span class="badge bg-success">
                                                        <i class="bi bi-check"></i> Erledigt
                                                    </span>
                                                @elseif($log->erinnerung_datum->isPast())
                                                    <span class="badge bg-danger">
                                                        <i class="bi bi-alarm"></i> Überfällig: {{ $log->erinnerung_datum->format('d.m.Y') }}
                                                    </span>
                                                    <button type="button" class="btn btn-sm btn-outline-success ms-1"
                                                            onclick="markErinnerungErledigt({{ $log->id }})">
                                                        <i class="bi bi-check"></i> Erledigen
                                                    </button>
                                                @else
                                                    <span class="badge bg-info">
                                                        <i class="bi bi-bell"></i> {{ $log->erinnerung_datum->format('d.m.Y') }}
                                                    </span>
                                                @endif
                                            </div>
                                        @endif
                                        
                                        {{-- Meta --}}
                                        <div class="small text-muted mt-1">
                                            <i class="bi bi-person-circle"></i> {{ $log->benutzer_name }}
                                            
                                            {{-- Löschen-Button (nur für manuelle Einträge) --}}
                                            @if(in_array($log->typ->value, ['telefonat', 'telefonat_eingehend', 'telefonat_ausgehend', 'mitteilung_kunde', 'mitteilung_intern', 'notiz', 'erinnerung']))
                                                <button type="button" class="btn btn-sm btn-link text-danger p-0 ms-2"
                                                        onclick="deleteLog({{ $log->id }})"
                                                        title="Löschen">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

</div>

{{-- ═══════════════════════════════════════════════════════════
    MODAL: Neuer Log-Eintrag
═══════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="modalNeuerLog" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalLogTitel">
                    <i class="bi bi-plus-circle"></i> Neuer Eintrag
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="log_typ" value="">
                
                {{-- Typ-Auswahl (wird je nach Quick-Action vorbelegt) --}}
                <div class="mb-3" id="typAuswahlContainer">
                    <label class="form-label">Typ</label>
                    <select class="form-select" id="log_typ_select" onchange="document.getElementById('log_typ').value = this.value;">
                        <optgroup label="Kommunikation">
                            <option value="telefonat">Telefonat</option>
                            <option value="telefonat_eingehend">Anruf erhalten</option>
                            <option value="telefonat_ausgehend">Anruf getätigt</option>
                            <option value="mitteilung_kunde">Mitteilung vom Kunden</option>
                            <option value="mitteilung_intern">Interne Notiz</option>
                        </optgroup>
                        <optgroup label="Dokumente">
                            <option value="pdf_erstellt">PDF erstellt</option>
                            <option value="pdf_versandt">PDF versandt</option>
                            <option value="mahnung_erstellt">Mahnung erstellt</option>
                            <option value="mahnung_versandt">Mahnung versandt</option>
                        </optgroup>
                        <optgroup label="Zahlungen">
                            <option value="zahlung_eingegangen">Zahlung eingegangen</option>
                            <option value="zahlung_teilweise">Teilzahlung</option>
                            <option value="zahlung_erinnerung">Zahlungserinnerung</option>
                        </optgroup>
                        <optgroup label="Sonstiges">
                            <option value="notiz">Notiz</option>
                            <option value="erinnerung">Erinnerung</option>
                            <option value="wiedervorlage">Wiedervorlage</option>
                        </optgroup>
                    </select>
                </div>
                
                {{-- Beschreibung --}}
                <div class="mb-3">
                    <label class="form-label">Beschreibung <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="log_beschreibung" rows="4" 
                              placeholder="Details eingeben..." required></textarea>
                </div>
                
                {{-- Kontakt-Person --}}
                <div class="mb-3" id="kontaktContainer">
                    <label class="form-label">Kontaktperson</label>
                    <input type="text" class="form-control" id="log_kontakt_person" 
                           placeholder="Name der Kontaktperson">
                </div>
                
                {{-- Kontakt-Telefon --}}
                <div class="mb-3" id="telefonContainer">
                    <label class="form-label">Telefonnummer</label>
                    <input type="text" class="form-control" id="log_kontakt_telefon" 
                           placeholder="+39 ...">
                </div>
                
                {{-- Kontakt-Email --}}
                <div class="mb-3" id="emailContainer" style="display:none;">
                    <label class="form-label">E-Mail</label>
                    <input type="email" class="form-control" id="log_kontakt_email" 
                           placeholder="email@example.com">
                </div>
                
                {{-- Priorität --}}
                <div class="mb-3">
                    <label class="form-label">Priorität</label>
                    <select class="form-select" id="log_prioritaet">
                        <option value="niedrig">Niedrig</option>
                        <option value="normal" selected>Normal</option>
                        <option value="hoch">Hoch</option>
                        <option value="kritisch">Kritisch</option>
                    </select>
                </div>
                
                {{-- Erinnerung --}}
                <div class="mb-3" id="erinnerungContainer">
                    <label class="form-label">Erinnerung am</label>
                    <input type="date" class="form-control" id="log_erinnerung_datum">
                    <small class="text-muted">Optional: Für Wiedervorlage</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                <button type="button" class="btn btn-primary" onclick="saveLog()">
                    <i class="bi bi-save"></i> Speichern
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════
    JAVASCRIPT
═══════════════════════════════════════════════════════════ --}}
<script>
const csrfToken = '{{ csrf_token() }}';
const rechnungId = {{ $rechnung->id }};
const storeUrl = '{{ route("rechnung.logs.store", $rechnung->id) }}';

/**
 * Modal öffnen und Typ vorbelegen
 */
function openLogModal(typ) {
    document.getElementById('log_typ').value = typ;
    document.getElementById('log_typ_select').value = typ;
    document.getElementById('log_beschreibung').value = '';
    document.getElementById('log_kontakt_person').value = '';
    document.getElementById('log_kontakt_telefon').value = '';
    document.getElementById('log_kontakt_email').value = '';
    document.getElementById('log_prioritaet').value = 'normal';
    document.getElementById('log_erinnerung_datum').value = '';
    
    // UI anpassen basierend auf Typ
    const telefonContainer = document.getElementById('telefonContainer');
    const emailContainer = document.getElementById('emailContainer');
    const kontaktContainer = document.getElementById('kontaktContainer');
    const erinnerungContainer = document.getElementById('erinnerungContainer');
    const typAuswahlContainer = document.getElementById('typAuswahlContainer');
    
    // Standardmäßig alles anzeigen
    telefonContainer.style.display = 'block';
    emailContainer.style.display = 'none';
    kontaktContainer.style.display = 'block';
    erinnerungContainer.style.display = 'block';
    typAuswahlContainer.style.display = 'block';
    
    // Typ-spezifische Anpassungen
    if (typ.includes('telefonat')) {
        emailContainer.style.display = 'none';
        typAuswahlContainer.style.display = 'none';
    } else if (typ === 'mitteilung_kunde') {
        emailContainer.style.display = 'block';
        telefonContainer.style.display = 'none';
        typAuswahlContainer.style.display = 'none';
    } else if (typ === 'notiz' || typ === 'erinnerung') {
        kontaktContainer.style.display = 'none';
        telefonContainer.style.display = 'none';
        emailContainer.style.display = 'none';
        typAuswahlContainer.style.display = 'none';
    } else if (typ === 'zahlung_eingegangen') {
        kontaktContainer.style.display = 'none';
        telefonContainer.style.display = 'none';
        erinnerungContainer.style.display = 'none';
        typAuswahlContainer.style.display = 'none';
    }
    
    // Titel anpassen
    const labels = {
        'telefonat': 'Telefonat dokumentieren',
        'telefonat_eingehend': 'Anruf erhalten',
        'telefonat_ausgehend': 'Anruf getätigt',
        'mitteilung_kunde': 'Kundenmitteilung',
        'notiz': 'Notiz hinzufügen',
        'erinnerung': 'Erinnerung erstellen',
        'mahnung_erstellt': 'Mahnung dokumentieren',
        'zahlung_eingegangen': 'Zahlung dokumentieren',
    };
    
    document.getElementById('modalLogTitel').innerHTML = 
        '<i class="bi bi-plus-circle"></i> ' + (labels[typ] || 'Neuer Eintrag');
    
    // Modal öffnen
    const modal = new bootstrap.Modal(document.getElementById('modalNeuerLog'));
    modal.show();
}

/**
 * Log speichern
 */
function saveLog() {
    const typ = document.getElementById('log_typ').value || document.getElementById('log_typ_select').value;
    const beschreibung = document.getElementById('log_beschreibung').value;
    
    if (!beschreibung.trim()) {
        alert('Bitte Beschreibung eingeben.');
        return;
    }
    
    const data = {
        typ: typ,
        beschreibung: beschreibung,
        kontakt_person: document.getElementById('log_kontakt_person').value || null,
        kontakt_telefon: document.getElementById('log_kontakt_telefon').value || null,
        kontakt_email: document.getElementById('log_kontakt_email').value || null,
        prioritaet: document.getElementById('log_prioritaet').value,
        erinnerung_datum: document.getElementById('log_erinnerung_datum').value || null,
    };
    
    // Form erstellen und submitten
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = storeUrl;
    form.style.display = 'none';
    
    // CSRF Token
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = csrfToken;
    form.appendChild(csrfInput);
    
    // Daten
    for (const [key, value] of Object.entries(data)) {
        if (value !== null && value !== '') {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = value;
            form.appendChild(input);
        }
    }
    
    document.body.appendChild(form);
    form.submit();
}

/**
 * Log löschen
 */
function deleteLog(logId) {
    if (!confirm('Diesen Eintrag wirklich löschen?')) {
        return;
    }
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/rechnung/logs/' + logId;
    form.style.display = 'none';
    
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = csrfToken;
    form.appendChild(csrfInput);
    
    const methodInput = document.createElement('input');
    methodInput.type = 'hidden';
    methodInput.name = '_method';
    methodInput.value = 'DELETE';
    form.appendChild(methodInput);
    
    document.body.appendChild(form);
    form.submit();
}

/**
 * Erinnerung als erledigt markieren
 */
function markErinnerungErledigt(logId) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/rechnung/logs/' + logId + '/erledigt';
    form.style.display = 'none';
    
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = csrfToken;
    form.appendChild(csrfInput);
    
    document.body.appendChild(form);
    form.submit();
}
</script>