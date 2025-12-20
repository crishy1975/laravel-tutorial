{{-- resources/views/mahnungen/versand.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container py-3">

    {{-- ⚠️ DEBUG-MODUS WARNUNG --}}
    @if(config('app.mahnung_debug_mode'))
        <div class="alert alert-danger border-danger mb-4">
            <div class="d-flex align-items-center">
                <i class="bi bi-exclamation-triangle-fill fs-2 me-3"></i>
                <div>
                    <h5 class="mb-1">⚠️ TEST-MODUS AKTIV</h5>
                    <p class="mb-0">
                        Alle E-Mails werden an <strong>{{ config('app.mahnung_debug_email') }}</strong> umgeleitet!
                        <br><small class="text-muted">Kunden erhalten KEINE E-Mails. Für Produktivbetrieb: <code>MAHNUNG_DEBUG_MODE=false</code> in .env</small>
                    </p>
                </div>
            </div>
        </div>
    @endif

    {{-- Kopfzeile --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0"><i class="bi bi-send"></i> Mahnungen versenden / Inviare solleciti</h4>
            <small class="text-muted">{{ $entwuerfe->count() }} Mahnungen bereit zum Versand</small>
        </div>
        <a href="{{ route('mahnungen.index') }}" class="btn btn-outline-secondary" id="btnZurueck">
            <i class="bi bi-arrow-left"></i> Zurück
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle"></i> {{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($entwuerfe->isEmpty())
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i>
            <strong>Keine Entwürfe vorhanden.</strong>
            <a href="{{ route('mahnungen.mahnlauf') }}">Mahnlauf starten</a> um neue Mahnungen zu erstellen.
        </div>
    @else
        {{-- Warnung: Ohne E-Mail --}}
        @if($ohneEmail->isNotEmpty())
            <div class="alert alert-warning">
                <i class="bi bi-mailbox"></i>
                <strong>{{ $ohneEmail->count() }} Mahnung(en) ohne E-Mail-Adresse!</strong>
                Diese müssen per Post versendet werden.
            </div>
        @endif

        {{-- ⭐ PROGRESS-BOX (versteckt bis Versand startet) --}}
        <div id="progressBox" class="card mb-4" style="display: none;">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-hourglass-split"></i>
                <strong>Versand läuft... / Invio in corso...</strong>
            </div>
            <div class="card-body">
                <div class="progress mb-3" style="height: 25px;">
                    <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated" 
                         role="progressbar" style="width: 0%;">
                        0%
                    </div>
                </div>
                <div class="d-flex justify-content-between text-muted mb-2">
                    <span id="progressText">Vorbereitung...</span>
                    <span id="progressCount">0 / 0</span>
                </div>
                <div id="progressLog" class="small" style="max-height: 200px; overflow-y: auto;">
                    {{-- Live-Log hier --}}
                </div>
            </div>
        </div>

        {{-- ⭐ ERGEBNIS-BOX (versteckt bis Versand fertig) --}}
        <div id="resultBox" class="card mb-4" style="display: none;">
            <div class="card-header" id="resultHeader">
                <i class="bi bi-check-circle"></i>
                <strong>Versand abgeschlossen / Invio completato</strong>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-4">
                        <div class="fs-2 text-success" id="resultErfolg">0</div>
                        <div class="text-muted">Erfolgreich</div>
                    </div>
                    <div class="col-md-4">
                        <div class="fs-2 text-danger" id="resultFehler">0</div>
                        <div class="text-muted">Fehler</div>
                    </div>
                    <div class="col-md-4">
                        <div class="fs-2 text-warning" id="resultPost">0</div>
                        <div class="text-muted">Postversand</div>
                    </div>
                </div>
                <div class="mt-3 text-center">
                    <a href="{{ route('mahnungen.historie') }}" class="btn btn-primary">
                        <i class="bi bi-clock-history"></i> Zur Historie
                    </a>
                    <a href="{{ route('mahnungen.versand') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-clockwise"></i> Seite neu laden
                    </a>
                </div>
            </div>
        </div>

        {{-- Info-Box --}}
        <div class="card mb-4" id="infoBox">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="d-flex align-items-center">
                            <span class="me-3 fs-4">🇩🇪 🇮🇹</span>
                            <div>
                                <strong>Zweisprachiger Versand / Invio bilingue</strong>
                                <div class="text-muted small">
                                    Alle Mahnungen werden automatisch auf Deutsch und Italienisch erstellt.
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 text-end">
                        <button type="button" class="btn btn-success" id="btnVersenden" disabled onclick="starteVersand()">
                            <i class="bi bi-send"></i>
                            <span id="btnText">Versenden / Inviare</span>
                        </button>
                    </div>
                </div>
                <div class="mt-3 pt-3 border-top">
                    <small class="text-muted">
                        <i class="bi bi-paperclip"></i> 
                        <strong>Anhänge / Allegati:</strong> 
                        Mahnungs-PDF (DE/IT) + Original-Rechnung (PDF)
                    </small>
                </div>
            </div>
        </div>

        {{-- Mit E-Mail --}}
        @if($mitEmail->isNotEmpty())
            <div class="card mb-4" id="emailCard">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <div>
                        <i class="bi bi-envelope-check"></i>
                        <strong>E-Mail-Versand möglich</strong> ({{ $mitEmail->count() }})
                    </div>
                    <div>
                        <input type="checkbox" id="selectAllEmail" class="form-check-input">
                        <label for="selectAllEmail" class="form-check-label text-white">Alle</label>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 40px;"></th>
                                <th>Rechnung / Fattura</th>
                                <th>Kunde / Cliente</th>
                                <th>E-Mail</th>
                                <th>Stufe / Livello</th>
                                <th class="text-end">Betrag</th>
                                <th class="text-end">Spesen</th>
                                <th class="text-end">Gesamt</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($mitEmail as $mahnung)
                                @php
                                    $postEmail = $mahnung->rechnung?->gebaeude?->postadresse?->email;
                                    $rechnungEmail = $mahnung->rechnung?->rechnungsempfaenger?->email;
                                    $emailAdresse = $postEmail ?: $rechnungEmail;
                                @endphp
                                <tr id="row-{{ $mahnung->id }}" data-mahnung-id="{{ $mahnung->id }}">
                                    <td>
                                        <input type="checkbox" 
                                               value="{{ $mahnung->id }}"
                                               data-email="{{ $emailAdresse }}"
                                               data-kunde="{{ $mahnung->rechnung?->rechnungsempfaenger?->name }}"
                                               class="form-check-input mahnung-checkbox email-checkbox">
                                    </td>
                                    <td>
                                        <a href="{{ url('/rechnung/' . $mahnung->rechnung_id . '/edit') }}" target="_blank">
                                            {{ $mahnung->rechnungsnummer_anzeige }}
                                        </a>
                                    </td>
                                    <td>{{ Str::limit($mahnung->rechnung?->rechnungsempfaenger?->name, 25) }}</td>
                                    <td>
                                        <small>{{ Str::limit($emailAdresse, 25) }}</small>
                                        @if($postEmail)
                                            <span class="badge bg-info text-dark" title="E-Mail aus Postadresse">P</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge {{ $mahnung->stufe?->badge_class ?? 'bg-secondary' }}">
                                            {{ $mahnung->stufe?->name_de ?? 'Stufe ' . $mahnung->mahnstufe }}
                                        </span>
                                    </td>
                                    <td class="text-end">{{ number_format($mahnung->rechnungsbetrag, 2, ',', '.') }} €</td>
                                    <td class="text-end">{{ number_format($mahnung->spesen, 2, ',', '.') }} €</td>
                                    <td class="text-end fw-bold">{{ $mahnung->gesamtbetrag_formatiert }}</td>
                                    <td class="status-cell">
                                        <span class="badge bg-secondary">Bereit</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- Ohne E-Mail (Postversand) --}}
        @if($ohneEmail->isNotEmpty())
            <div class="card" id="postCard">
                <div class="card-header bg-warning d-flex justify-content-between align-items-center">
                    <div>
                        <i class="bi bi-mailbox"></i>
                        <strong>Postversand erforderlich / Invio postale</strong> ({{ $ohneEmail->count() }})
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 40px;"></th>
                                <th>Rechnung / Fattura</th>
                                <th>Kunde / Cliente</th>
                                <th>Adresse / Indirizzo</th>
                                <th>Stufe / Livello</th>
                                <th class="text-end">Betrag</th>
                                <th class="text-end">Spesen</th>
                                <th class="text-end">Gesamt</th>
                                <th>Aktion</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($ohneEmail as $mahnung)
                                @php
                                    $adresse = $mahnung->rechnung?->gebaeude?->postadresse 
                                             ?? $mahnung->rechnung?->rechnungsempfaenger;
                                @endphp
                                <tr>
                                    <td>
                                        <i class="bi bi-envelope-x text-warning"></i>
                                    </td>
                                    <td>
                                        <a href="{{ url('/rechnung/' . $mahnung->rechnung_id . '/edit') }}" target="_blank">
                                            {{ $mahnung->rechnungsnummer_anzeige }}
                                        </a>
                                    </td>
                                    <td>{{ Str::limit($mahnung->rechnung?->rechnungsempfaenger?->name, 25) }}</td>
                                    <td>
                                        <small class="text-muted">
                                            {{ $adresse?->strasse }} {{ $adresse?->hausnummer }}<br>
                                            {{ $adresse?->plz }} {{ $adresse?->wohnort }}
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge {{ $mahnung->stufe?->badge_class ?? 'bg-secondary' }}">
                                            {{ $mahnung->stufe?->name_de ?? 'Stufe ' . $mahnung->mahnstufe }}
                                        </span>
                                    </td>
                                    <td class="text-end">{{ number_format($mahnung->rechnungsbetrag, 2, ',', '.') }} €</td>
                                    <td class="text-end">{{ number_format($mahnung->spesen, 2, ',', '.') }} €</td>
                                    <td class="text-end fw-bold">{{ $mahnung->gesamtbetrag_formatiert }}</td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('mahnungen.pdf', ['mahnung' => $mahnung->id, 'preview' => 1]) }}" 
                                               class="btn btn-outline-primary" title="PDF anzeigen" target="_blank">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="{{ route('mahnungen.pdf', $mahnung->id) }}" 
                                               class="btn btn-outline-secondary" title="PDF herunterladen">
                                                <i class="bi bi-download"></i>
                                            </a>
                                            <form method="POST" action="{{ route('mahnungen.als-post-versendet', $mahnung->id) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-success" title="Als versendet markieren">
                                                    <i class="bi bi-check"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    @endif

</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAllEmail = document.getElementById('selectAllEmail');
    const emailCheckboxes = document.querySelectorAll('.email-checkbox');
    const btnVersenden = document.getElementById('btnVersenden');
    const btnText = document.getElementById('btnText');

    function updateButton() {
        const checked = document.querySelectorAll('.mahnung-checkbox:checked').length;
        btnVersenden.disabled = checked === 0;
        btnText.textContent = checked > 0 
            ? `${checked} Mahnung${checked > 1 ? 'en' : ''} versenden`
            : 'Versenden / Inviare';
    }

    selectAllEmail?.addEventListener('change', function() {
        emailCheckboxes.forEach(cb => cb.checked = this.checked);
        updateButton();
    });

    document.querySelectorAll('.mahnung-checkbox').forEach(cb => {
        cb.addEventListener('change', updateButton);
    });
});

// ════════════════════════════════════════════════════════════════════════════
// ⭐ AJAX VERSAND MIT PROGRESS
// ════════════════════════════════════════════════════════════════════════════

let versandLaeuft = false;

async function starteVersand() {
    if (versandLaeuft) return;
    
    const checkboxes = document.querySelectorAll('.mahnung-checkbox:checked');
    if (checkboxes.length === 0) {
        alert('Bitte mindestens eine Mahnung auswählen.');
        return;
    }

    // Mahnungs-IDs sammeln
    const mahnungen = Array.from(checkboxes).map(cb => ({
        id: cb.value,
        email: cb.dataset.email,
        kunde: cb.dataset.kunde
    }));

    versandLaeuft = true;

    // UI umschalten
    document.getElementById('infoBox').style.display = 'none';
    document.getElementById('emailCard')?.style.setProperty('display', 'none');
    document.getElementById('postCard')?.style.setProperty('display', 'none');
    document.getElementById('btnZurueck').style.display = 'none';
    document.getElementById('progressBox').style.display = 'block';

    const progressBar = document.getElementById('progressBar');
    const progressText = document.getElementById('progressText');
    const progressCount = document.getElementById('progressCount');
    const progressLog = document.getElementById('progressLog');

    let erfolg = 0;
    let fehler = 0;
    let postNoetig = 0;

    // Mahnungen einzeln versenden
    for (let i = 0; i < mahnungen.length; i++) {
        const mahnung = mahnungen[i];
        const prozent = Math.round(((i + 1) / mahnungen.length) * 100);

        progressText.textContent = `Versende an ${mahnung.kunde || 'Unbekannt'}...`;
        progressCount.textContent = `${i + 1} / ${mahnungen.length}`;
        progressBar.style.width = prozent + '%';
        progressBar.textContent = prozent + '%';

        try {
            const response = await fetch(`/mahnungen/${mahnung.id}/versende-einzeln`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            });

            const result = await response.json();

            if (result.ok) {
                erfolg++;
                addLogEntry(progressLog, 'success', 
                    `✓ ${mahnung.kunde}: ${result.email || 'versendet'}`);
                updateRowStatus(mahnung.id, 'success', 'Versendet');
            } else if (result.post_noetig) {
                postNoetig++;
                addLogEntry(progressLog, 'warning', 
                    `✉ ${mahnung.kunde}: Postversand erforderlich`);
                updateRowStatus(mahnung.id, 'warning', 'Post');
            } else {
                fehler++;
                addLogEntry(progressLog, 'danger', 
                    `✗ ${mahnung.kunde}: ${result.message || 'Fehler'}`);
                updateRowStatus(mahnung.id, 'danger', 'Fehler');
            }
        } catch (error) {
            fehler++;
            addLogEntry(progressLog, 'danger', 
                `✗ ${mahnung.kunde}: ${error.message}`);
            updateRowStatus(mahnung.id, 'danger', 'Fehler');
        }

        // Kurze Pause für bessere UX
        await sleep(100);
    }

    // Fertig - Ergebnis anzeigen
    progressBar.classList.remove('progress-bar-animated');
    progressBar.style.width = '100%';
    progressBar.textContent = 'Fertig!';
    progressText.textContent = 'Versand abgeschlossen';

    // Ergebnis-Box anzeigen
    setTimeout(() => {
        document.getElementById('progressBox').style.display = 'none';
        
        const resultBox = document.getElementById('resultBox');
        const resultHeader = document.getElementById('resultHeader');
        
        document.getElementById('resultErfolg').textContent = erfolg;
        document.getElementById('resultFehler').textContent = fehler;
        document.getElementById('resultPost').textContent = postNoetig;
        
        if (fehler === 0) {
            resultHeader.className = 'card-header bg-success text-white';
        } else if (erfolg === 0) {
            resultHeader.className = 'card-header bg-danger text-white';
        } else {
            resultHeader.className = 'card-header bg-warning';
        }
        
        resultBox.style.display = 'block';
        document.getElementById('btnZurueck').style.display = 'inline-block';
    }, 500);

    versandLaeuft = false;
}

function addLogEntry(container, type, message) {
    const entry = document.createElement('div');
    entry.className = `text-${type} mb-1`;
    entry.textContent = message;
    container.appendChild(entry);
    container.scrollTop = container.scrollHeight;
}

function updateRowStatus(mahnungId, type, text) {
    const row = document.getElementById(`row-${mahnungId}`);
    if (row) {
        const statusCell = row.querySelector('.status-cell');
        if (statusCell) {
            const badgeClass = type === 'success' ? 'bg-success' : 
                              type === 'warning' ? 'bg-warning text-dark' : 'bg-danger';
            statusCell.innerHTML = `<span class="badge ${badgeClass}">${text}</span>`;
        }
    }
}

function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}
</script>
@endpush
@endsection
