{{-- resources/views/rechnung/partials/_logs.blade.php --}}
{{-- Log-System Tab für Rechnungsformular --}}
{{-- ⭐ WICHTIG: Das Modal wird über @push('modals') AUSSERHALB des Hauptforms eingebunden! --}}

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
        
    // Mahnung-Statistiken
    $mahnungLogs = $logs->filter(fn($l) => str_starts_with($l->typ->value ?? '', 'mahnung'));
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
                    {{-- Kommunikation --}}
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
                    
                    {{-- Notizen --}}
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
                    
                    {{-- Mahnung --}}
                    <div class="col-md-2">
                        <button type="button" class="btn btn-outline-danger w-100"
                                onclick="openLogModal('mahnung_telefonisch')">
                            <i class="bi bi-telephone-x"></i><br>
                            <small>Tel. Mahnung</small>
                        </button>
                    </div>
                    
                    {{-- Zahlung --}}
                    <div class="col-md-2">
                        <button type="button" class="btn btn-outline-success w-100"
                                onclick="openLogModal('zahlung_eingegangen')">
                            <i class="bi bi-currency-euro"></i><br>
                            <small>Zahlung</small>
                        </button>
                    </div>
                </div>
                
                {{-- Link zum Mahnwesen --}}
                @if($rechnung->status === 'sent')
                    <div class="mt-3 pt-3 border-top">
                        <a href="{{ route('mahnungen.index') }}" class="btn btn-sm btn-outline-danger">
                            <i class="bi bi-envelope-exclamation"></i> Zum Mahnwesen
                        </a>
                        <small class="text-muted ms-2">Für automatische Mahnungen per E-Mail/Post</small>
                    </div>
                @endif
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
        <div class="card text-center h-100 {{ $mahnungLogs->count() > 0 ? 'border-danger' : '' }}">
            <div class="card-body">
                <h3 class="mb-0 {{ $mahnungLogs->count() > 0 ? 'text-danger' : '' }}">
                    {{ $mahnungLogs->count() }}
                </h3>
                <small class="text-muted">Mahnungen</small>
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
                                                    <span class="badge bg-success"><i class="bi bi-check"></i> Erledigt</span>
                                                @elseif($log->erinnerung_datum->isPast())
                                                    <span class="badge bg-danger">
                                                        <i class="bi bi-alarm"></i> Überfällig: {{ $log->erinnerung_datum->format('d.m.Y') }}
                                                    </span>
                                                @else
                                                    <span class="badge bg-info">
                                                        <i class="bi bi-bell"></i> {{ $log->erinnerung_datum->format('d.m.Y') }}
                                                    </span>
                                                @endif
                                            </div>
                                        @endif
                                        
                                        {{-- Metadata für Mahnungen --}}
                                        @if($log->metadata && isset($log->metadata['stufe']))
                                            <div class="mt-1">
                                                <span class="badge bg-danger">Stufe {{ $log->metadata['stufe'] }}</span>
                                                @if(isset($log->metadata['betrag']))
                                                    <span class="badge bg-secondary">{{ $log->metadata['betrag'] }}</span>
                                                @endif
                                                @if(isset($log->metadata['versandart']))
                                                    <span class="badge bg-info">{{ $log->metadata['versandart'] }}</span>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                    
                                    {{-- Aktionen --}}
                                    <div class="ms-2">
                                        @if($log->erinnerung_datum && !$log->erinnerung_erledigt)
                                            <button type="button" class="btn btn-sm btn-outline-success mb-1"
                                                    onclick="markErinnerungErledigt({{ $log->id }})" title="Erledigen">
                                                <i class="bi bi-check"></i>
                                            </button>
                                        @endif
                                        @if(in_array($log->typ->value ?? '', ['telefonat', 'telefonat_eingehend', 'telefonat_ausgehend', 'mitteilung_kunde', 'mitteilung_intern', 'notiz', 'erinnerung', 'mahnung_telefonisch']))
                                            <button type="button" class="btn btn-sm btn-outline-danger"
                                                    onclick="deleteLog({{ $log->id }})" title="Löschen">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        @endif
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

{{-- ⭐⭐⭐ MODAL ÜBER @push EINBINDEN - AUSSERHALB DES FORMULARS! ⭐⭐⭐ --}}
@push('modals')
<div class="modal fade" id="modalNeuerLog" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalLogTitel">
                    <i class="bi bi-plus-circle"></i> Neuer Eintrag
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                {{-- Typ (hidden) --}}
                <input type="hidden" id="log_typ" value="">
                
                {{-- Typ-Auswahl (nur bei bestimmten Typen sichtbar) --}}
                <div class="mb-3" id="typAuswahlContainer">
                    <label class="form-label">Typ</label>
                    <select id="log_typ_select" class="form-select">
                        <option value="telefonat">Telefonat (allgemein)</option>
                        <option value="telefonat_eingehend">Anruf erhalten</option>
                        <option value="telefonat_ausgehend">Anruf getätigt</option>
                        <option value="mahnung_telefonisch">Telefonische Mahnung</option>
                        <option value="mitteilung_kunde">Mitteilung vom Kunden</option>
                        <option value="mitteilung_intern">Interne Notiz</option>
                        <option value="notiz">Notiz</option>
                        <option value="erinnerung">Erinnerung</option>
                    </select>
                </div>
                
                {{-- Beschreibung --}}
                <div class="mb-3">
                    <label class="form-label">Beschreibung <span class="text-danger">*</span></label>
                    <textarea id="log_beschreibung" class="form-control" rows="4" 
                              placeholder="Was wurde besprochen / vereinbart?"></textarea>
                </div>
                
                {{-- Kontakt-Person --}}
                <div class="mb-3" id="kontaktContainer">
                    <label class="form-label">Kontakt-Person</label>
                    <input type="text" id="log_kontakt_person" class="form-control" 
                           placeholder="Name des Ansprechpartners">
                </div>
                
                {{-- Telefon --}}
                <div class="mb-3" id="telefonContainer">
                    <label class="form-label">Telefonnummer</label>
                    <input type="text" id="log_kontakt_telefon" class="form-control" 
                           placeholder="+39 ...">
                </div>
                
                {{-- E-Mail --}}
                <div class="mb-3" id="emailContainer" style="display: none;">
                    <label class="form-label">E-Mail</label>
                    <input type="email" id="log_kontakt_email" class="form-control" 
                           placeholder="email@example.com">
                </div>
                
                {{-- Priorität --}}
                <div class="mb-3">
                    <label class="form-label">Priorität</label>
                    <select id="log_prioritaet" class="form-select">
                        <option value="niedrig">Niedrig</option>
                        <option value="normal" selected>Normal</option>
                        <option value="hoch">Hoch</option>
                        <option value="kritisch">Kritisch</option>
                    </select>
                </div>
                
                {{-- Erinnerung --}}
                <div class="mb-3" id="erinnerungContainer">
                    <label class="form-label">Wiedervorlage / Erinnerung</label>
                    <input type="date" id="log_erinnerung_datum" class="form-control">
                    <small class="text-muted">Optional: Datum für Wiedervorlage setzen</small>
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
@endpush

{{-- ⭐⭐⭐ JAVASCRIPT ÜBER @push EINBINDEN ⭐⭐⭐ --}}
@push('scripts')
<script>
const logCsrfToken = '{{ csrf_token() }}';
const logRechnungId = {{ $rechnung->id }};
const logStoreUrl = '{{ route("rechnung.logs.store", $rechnung->id) }}';

/**
 * Modal öffnen und Typ vorbelegen
 */
function openLogModal(typ) {
    console.log('openLogModal aufgerufen mit Typ:', typ);
    
    // Felder zurücksetzen
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
    if (typ.includes('telefonat') || typ === 'mahnung_telefonisch') {
        emailContainer.style.display = 'none';
        typAuswahlContainer.style.display = 'none';
        
        // Bei telefonischer Mahnung: Priorität auf "hoch" setzen
        if (typ === 'mahnung_telefonisch') {
            document.getElementById('log_prioritaet').value = 'hoch';
            document.getElementById('log_beschreibung').placeholder = 
                'Was wurde besprochen? Zahlungszusage? Vereinbarung?';
        }
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
        document.getElementById('log_beschreibung').placeholder = 
            'Betrag, Referenz, Datum der Zahlung...';
    }
    
    // Titel anpassen
    const labels = {
        'telefonat': 'Telefonat dokumentieren',
        'telefonat_eingehend': 'Anruf erhalten',
        'telefonat_ausgehend': 'Anruf getätigt',
        'mitteilung_kunde': 'Kundenmitteilung',
        'notiz': 'Notiz hinzufügen',
        'erinnerung': 'Erinnerung erstellen',
        'mahnung_telefonisch': '📞 Telefonische Mahnung',
        'zahlung_eingegangen': 'Zahlung dokumentieren',
    };
    
    document.getElementById('modalLogTitel').innerHTML = 
        '<i class="bi bi-plus-circle"></i> ' + (labels[typ] || 'Neuer Eintrag');
    
    // Modal öffnen
    const modalElement = document.getElementById('modalNeuerLog');
    if (modalElement) {
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
    } else {
        console.error('Modal #modalNeuerLog nicht gefunden!');
        alert('Fehler: Modal konnte nicht geöffnet werden.');
    }
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
    form.action = logStoreUrl;
    form.style.display = 'none';
    
    // CSRF Token
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = logCsrfToken;
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
    csrfInput.value = logCsrfToken;
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
    csrfInput.value = logCsrfToken;
    form.appendChild(csrfInput);
    
    document.body.appendChild(form);
    form.submit();
}
</script>
@endpush
