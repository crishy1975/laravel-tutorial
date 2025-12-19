{{-- resources/views/mahnungen/show.blade.php --}}
{{-- ZWEISPRACHIG: Deutsch / Italiano --}}
@extends('layouts.app')

@section('content')
<div class="container py-3">
    
    {{-- Kopfzeile --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0">
                <i class="bi {{ $mahnung->stufe?->icon ?? 'bi-envelope' }}"></i>
                Mahnung / Sollecito #{{ $mahnung->id }}
            </h4>
            <small class="text-muted">
                {{ $mahnung->stufe?->name_de ?? 'Stufe ' . $mahnung->mahnstufe }} / 
                {{ $mahnung->stufe?->name_it ?? 'Livello ' . $mahnung->mahnstufe }}
                - Rechnung/Fattura {{ $mahnung->rechnungsnummer_anzeige }}
            </small>
        </div>
        <div class="btn-group">
            <a href="{{ route('mahnungen.historie') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Zurück
            </a>
        </div>
    </div>

    {{-- ⭐ PDF-VORSCHAU CARD --}}
    <div class="card mb-4 border-primary">
        <div class="card-body py-3">
            <div class="row align-items-center">
                <div class="col-md-7">
                    <h5 class="mb-1">
                        <i class="bi bi-file-pdf text-danger"></i>
                        PDF Mahnung / Sollecito PDF
                    </h5>
                    <p class="text-muted mb-0">
                        Zweisprachiges Mahnschreiben (Deutsch + Italienisch)
                    </p>
                </div>
                <div class="col-md-5 text-end">
                    <div class="btn-group">
                        {{-- Im Browser anzeigen --}}
                        <a href="{{ route('mahnungen.pdf', ['mahnung' => $mahnung->id, 'preview' => 1]) }}" 
                           class="btn btn-outline-primary" 
                           target="_blank"
                           title="Im Browser anzeigen">
                            <i class="bi bi-eye"></i> Vorschau
                        </a>
                        {{-- Herunterladen --}}
                        <a href="{{ route('mahnungen.pdf', $mahnung->id) }}" 
                           class="btn btn-primary"
                           title="PDF herunterladen">
                            <i class="bi bi-download"></i> Download
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Linke Spalte: Info --}}
        <div class="col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="mb-0">Mahnung-Details / Dettagli sollecito</h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless">
                        <tr>
                            <th class="text-muted">Status / Stato</th>
                            <td>{!! $mahnung->status_badge !!}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Mahndatum / Data sollecito</th>
                            <td>{{ $mahnung->mahndatum->format('d.m.Y') }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Stufe / Livello</th>
                            <td>
                                <span class="badge {{ $mahnung->stufe?->badge_class ?? 'bg-secondary' }}">
                                    {{ $mahnung->mahnstufe }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th class="text-muted">Tage überfällig / Giorni scaduti</th>
                            <td><span class="badge bg-danger">{{ $mahnung->tage_ueberfaellig }}</span></td>
                        </tr>
                        <tr><td colspan="2"><hr class="my-2"></td></tr>
                        <tr>
                            <th class="text-muted">Rechnungsbetrag / Importo fattura</th>
                            <td>{{ number_format($mahnung->rechnungsbetrag, 2, ',', '.') }} €</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Mahnspesen / Spese sollecito</th>
                            <td>{{ $mahnung->spesen_formatiert }}</td>
                        </tr>
                        <tr class="fw-bold">
                            <th>Gesamtbetrag / Totale</th>
                            <td>{{ $mahnung->gesamtbetrag_formatiert }}</td>
                        </tr>
                        <tr><td colspan="2"><hr class="my-2"></td></tr>
                        <tr>
                            <th class="text-muted">Versandart / Tipo invio</th>
                            <td>{!! $mahnung->versandart_badge !!}</td>
                        </tr>
                        @if($mahnung->email_gesendet_am)
                            <tr>
                                <th class="text-muted">Gesendet am / Inviato il</th>
                                <td>{{ $mahnung->email_gesendet_am->format('d.m.Y H:i') }}</td>
                            </tr>
                            <tr>
                                <th class="text-muted">An / A</th>
                                <td><small>{{ $mahnung->email_adresse }}</small></td>
                            </tr>
                        @endif
                        @if($mahnung->email_fehler)
                            <tr>
                                <th class="text-muted">Fehler / Errore</th>
                                <td><span class="text-danger">{{ $mahnung->email_fehler_text }}</span></td>
                            </tr>
                        @endif
                    </table>
                </div>
            </div>
        </div>

        {{-- Rechte Spalte: Kunde & Rechnung --}}
        <div class="col-lg-8 mb-4">
            {{-- Kunde --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-person"></i> Kunde / Cliente</h6>
                </div>
                <div class="card-body">
                    @php 
                        $empfaenger = $mahnung->rechnung?->rechnungsempfaenger; 
                        $postadresse = $mahnung->rechnung?->gebaeude?->postadresse;
                        // ⭐ E-Mail-Priorität: Postadresse → Rechnungsempfänger
                        $emailAdresse = $postadresse?->email ?: $empfaenger?->email;
                        $emailQuelle = $postadresse?->email ? 'Postadresse' : 'Rechnungsempfänger';
                    @endphp
                    <div class="row">
                        <div class="col-md-6">
                            <strong>{{ $empfaenger?->name }}</strong><br>
                            {{ $empfaenger?->strasse }} {{ $empfaenger?->hausnummer }}<br>
                            {{ $empfaenger?->plz }} {{ $empfaenger?->wohnort }}
                        </div>
                        <div class="col-md-6">
                            @if($emailAdresse)
                                <i class="bi bi-envelope"></i> {{ $emailAdresse }}
                                @if($postadresse?->email)
                                    <span class="badge bg-info text-dark" title="E-Mail aus Postadresse">Postadresse</span>
                                @endif
                                <br>
                            @else
                                <span class="text-warning"><i class="bi bi-exclamation-triangle"></i> Keine E-Mail / Nessuna e-mail</span><br>
                            @endif
                            @if($empfaenger?->telefon)
                                <i class="bi bi-telephone"></i> {{ $empfaenger->telefon }}
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Rechnung --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-file-text"></i> Rechnung / Fattura</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Rechnungsnummer / N. fattura:</strong> 
                            <a href="{{ url('/rechnung/' . $mahnung->rechnung_id . '/edit') }}">
                                {{ $mahnung->rechnung?->volle_rechnungsnummer }}
                            </a><br>
                            <strong>Rechnungsdatum / Data fattura:</strong> {{ $mahnung->rechnung?->rechnungsdatum?->format('d.m.Y') }}<br>
                            <strong>Fälligkeit / Scadenza:</strong> {{ $mahnung->rechnung?->faelligkeitsdatum?->format('d.m.Y') ?? $mahnung->rechnung?->rechnungsdatum?->addDays(30)->format('d.m.Y') }}
                        </div>
                        <div class="col-md-6">
                            <strong>Gebäude / Edificio:</strong> {{ $mahnung->rechnung?->gebaeude?->gebaeude_name ?? '-' }}<br>
                            <strong>Status / Stato:</strong> {!! $mahnung->rechnung?->status_badge ?? '-' !!}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Mahntext ZWEISPRACHIG --}}
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0">
                        <i class="bi bi-card-text"></i> 
                        Mahntext / Testo sollecito
                        <span class="badge bg-light text-primary ms-2">🇩🇪 + 🇮🇹</span>
                    </h6>
                </div>
                <div class="card-body">
                    {{-- Deutscher Text --}}
                    <div class="mb-4">
                        <h6 class="text-muted border-bottom pb-2">
                            🇩🇪 <strong>DEUTSCH</strong>
                        </h6>
                        <div class="bg-light p-3 rounded" style="white-space: pre-wrap; font-size: 0.9rem; line-height: 1.6;">{{ $textDe }}</div>
                    </div>

                    {{-- Trennlinie --}}
                    <hr class="my-4" style="border-style: dashed;">

                    {{-- Italienischer Text --}}
                    <div>
                        <h6 class="text-muted border-bottom pb-2">
                            🇮🇹 <strong>ITALIANO</strong>
                        </h6>
                        <div class="bg-light p-3 rounded" style="white-space: pre-wrap; font-size: 0.9rem; line-height: 1.6;">{{ $textIt }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Aktionen --}}
    @if($mahnung->status !== 'storniert')
        <div class="card mb-4">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    @if($mahnung->status === 'entwurf')
                        <span class="text-muted">
                            <i class="bi bi-hourglass"></i>
                            Diese Mahnung wurde noch nicht versendet. / 
                            Questo sollecito non è ancora stato inviato.
                        </span>
                    @else
                        <span class="text-success">
                            <i class="bi bi-check-circle"></i> 
                            Versendet am / Inviato il {{ $mahnung->email_gesendet_am?->format('d.m.Y H:i') ?? $mahnung->updated_at->format('d.m.Y H:i') }}
                        </span>
                    @endif
                </div>
                <form method="POST" action="{{ route('mahnungen.stornieren', $mahnung->id) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger" 
                            onclick="return confirm('Mahnung wirklich stornieren? / Annullare davvero il sollecito?')">
                        <i class="bi bi-x-circle"></i> Stornieren / Annullare
                    </button>
                </form>
            </div>
        </div>
    @else
        <div class="alert alert-secondary mb-4">
            <i class="bi bi-x-octagon"></i>
            <strong>Storniert / Annullato</strong>
            @if($mahnung->bemerkung)
                - {{ $mahnung->bemerkung }}
            @endif
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════════════
        RECHNUNGS-LOGS
    ═══════════════════════════════════════════════════════════════════════ --}}
    @if($mahnung->rechnung)
        @php
            $rechnung = $mahnung->rechnung;
            $logs = \App\Models\RechnungLog::where('rechnung_id', $rechnung->id)
                ->with('user')
                ->chronologisch()
                ->limit(50)
                ->get();
        @endphp

        {{-- Quick Actions --}}
        <div class="card mb-4">
            <div class="card-header bg-primary text-white py-2">
                <h6 class="mb-0"><i class="bi bi-plus-circle"></i> Neuer Log-Eintrag</h6>
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
            </div>
        </div>

        {{-- Log-Timeline --}}
        <div class="card">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h6 class="mb-0">
                    <i class="bi bi-clock-history"></i> 
                    Rechnungs-Log / Registro fattura
                </h6>
                <a href="{{ url('/rechnung/' . $rechnung->id . '/edit') }}#logs" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-pencil"></i> Zur Rechnung
                </a>
            </div>
            <div class="card-body p-0">
                @if($logs->isEmpty())
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-inbox"></i>
                        Noch keine Log-Einträge vorhanden.
                    </div>
                @else
                    <div class="timeline-container" style="max-height: 400px; overflow-y: auto;">
                        @foreach($logs as $log)
                            <div class="timeline-item border-bottom p-3 {{ $log->prioritaet === 'kritisch' ? 'bg-danger bg-opacity-10' : ($log->prioritaet === 'hoch' ? 'bg-warning bg-opacity-10' : '') }}">
                                <div class="d-flex">
                                    {{-- Icon --}}
                                    <div class="me-3">
                                        <span class="badge bg-{{ $log->farbe ?? 'secondary' }} rounded-circle p-2">
                                            <i class="{{ $log->icon ?? 'bi-journal' }}"></i>
                                        </span>
                                    </div>
                                    
                                    {{-- Content --}}
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <strong>{{ $log->titel ?? $log->typ?->value ?? 'Eintrag' }}</strong>
                                                @if($log->typ)
                                                    {!! $log->typ_badge ?? '' !!}
                                                @endif
                                                @if($log->prioritaet && $log->prioritaet !== 'normal')
                                                    {!! $log->prioritaet_badge ?? '' !!}
                                                @endif
                                            </div>
                                            <small class="text-muted">
                                                {{ $log->created_at->format('d.m.Y H:i') }}
                                                <span class="d-none d-md-inline">({{ $log->created_at->diffForHumans() }})</span>
                                            </small>
                                        </div>
                                        
                                        @if($log->beschreibung)
                                            <p class="mb-1 mt-1 text-secondary">
                                                {{ Str::limit($log->beschreibung, 200) }}
                                            </p>
                                        @endif
                                        
                                        {{-- Kontaktinfo --}}
                                        @if($log->kontakt_person || $log->kontakt_telefon)
                                            <small class="text-muted">
                                                @if($log->kontakt_person)
                                                    <i class="bi bi-person"></i> {{ $log->kontakt_person }}
                                                @endif
                                                @if($log->kontakt_telefon)
                                                    <i class="bi bi-telephone ms-2"></i> {{ $log->kontakt_telefon }}
                                                @endif
                                            </small>
                                        @endif
                                        
                                        {{-- User --}}
                                        @if($log->user)
                                            <div class="mt-1">
                                                <small class="text-muted">
                                                    <i class="bi bi-person-badge"></i> {{ $log->user->name }}
                                                </small>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    @endif

</div>

{{-- ⭐⭐⭐ MODAL AUSSERHALB DES CONTAINERS ⭐⭐⭐ --}}
@if($mahnung->rechnung)
<div class="modal fade" id="modalNeuerLog" tabindex="-1" aria-labelledby="modalLogTitel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalLogTitel">
                    <i class="bi bi-plus-circle"></i> Neuer Eintrag
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Schließen"></button>
            </div>
            <div class="modal-body">
                {{-- Hidden Type --}}
                <input type="hidden" id="log_typ" value="notiz">
                
                {{-- Typ-Auswahl (optional sichtbar) --}}
                <div class="mb-3" id="typAuswahlContainer">
                    <label for="log_typ_select" class="form-label">Typ</label>
                    <select id="log_typ_select" class="form-select">
                        <option value="notiz">Notiz</option>
                        <option value="telefonat">Telefonat</option>
                        <option value="telefonat_eingehend">Telefonat (eingehend)</option>
                        <option value="telefonat_ausgehend">Telefonat (ausgehend)</option>
                        <option value="mitteilung_kunde">Kundenmitteilung</option>
                        <option value="erinnerung">Erinnerung</option>
                        <option value="mahnung_telefonisch">Telefonische Mahnung</option>
                        <option value="zahlung_eingegangen">Zahlung eingegangen</option>
                    </select>
                </div>
                
                {{-- Beschreibung --}}
                <div class="mb-3">
                    <label for="log_beschreibung" class="form-label">Beschreibung <span class="text-danger">*</span></label>
                    <textarea id="log_beschreibung" class="form-control" rows="4" 
                              placeholder="Was ist passiert? Details..."></textarea>
                </div>
                
                {{-- Kontakt-Container --}}
                <div id="kontaktContainer">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="log_kontakt_person" class="form-label">Kontaktperson</label>
                            <input type="text" id="log_kontakt_person" class="form-control" 
                                   placeholder="Name">
                        </div>
                        <div class="col-md-4 mb-3" id="telefonContainer">
                            <label for="log_kontakt_telefon" class="form-label">Telefon</label>
                            <input type="text" id="log_kontakt_telefon" class="form-control" 
                                   placeholder="+39 ...">
                        </div>
                        <div class="col-md-4 mb-3" id="emailContainer" style="display: none;">
                            <label for="log_kontakt_email" class="form-label">E-Mail</label>
                            <input type="email" id="log_kontakt_email" class="form-control" 
                                   placeholder="email@example.com">
                        </div>
                    </div>
                </div>
                
                {{-- Priorität & Erinnerung --}}
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="log_prioritaet" class="form-label">Priorität</label>
                        <select id="log_prioritaet" class="form-select">
                            <option value="normal">Normal</option>
                            <option value="hoch">Hoch</option>
                            <option value="kritisch">Kritisch</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3" id="erinnerungContainer">
                        <label for="log_erinnerung_datum" class="form-label">Erinnerung am</label>
                        <input type="datetime-local" id="log_erinnerung_datum" class="form-control">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                <button type="button" class="btn btn-primary" onclick="saveLog()">
                    <i class="bi bi-check-lg"></i> Speichern
                </button>
            </div>
        </div>
    </div>
</div>
@endif

@endsection

{{-- ⭐⭐⭐ JAVASCRIPT ⭐⭐⭐ --}}
@if($mahnung->rechnung)
@push('scripts')
<script>
const logCsrfToken = '{{ csrf_token() }}';
const logRechnungId = {{ $mahnung->rechnung->id }};
const logStoreUrl = '{{ route("rechnung.logs.store", $mahnung->rechnung->id) }}';
const mahnungShowUrl = '{{ route("mahnungen.show", $mahnung->id) }}';

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
        redirect_url: mahnungShowUrl, // Zurück zur Mahnung nach Speichern
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
</script>
@endpush
@endif
