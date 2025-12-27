@extends('layouts.app')

@section('title', 'Reinigungsplanung')

@section('content')
<div class="container-fluid py-2 py-md-4">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 h3-md mb-1">
                <i class="bi bi-calendar-check text-primary"></i>
                Reinigungsplanung
            </h1>
            <p class="text-muted mb-0 small">
                @if(!empty($filterMonat))
                    {{ $monate[$filterMonat] }} {{ now()->year }}
                @else
                    Alle Monate
                @endif
            </p>
        </div>
        {{-- Desktop: Buttons --}}
        <div class="d-none d-md-flex gap-2">
            <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#modalVorlage">
                <i class="bi bi-chat-quote"></i> Nachricht-Vorlage
            </button>
            <a href="{{ route('reinigungsplanung.export', request()->query()) }}" class="btn btn-outline-secondary">
                <i class="bi bi-file-earmark-excel"></i> CSV
            </a>
            <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
                <i class="bi bi-printer"></i>
            </button>
        </div>
        {{-- Mobile: Dropdown --}}
        <div class="dropdown d-md-none">
            <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="dropdown">
                <i class="bi bi-three-dots-vertical"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li>
                    <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#modalVorlage">
                        <i class="bi bi-chat-quote"></i> Nachricht-Vorlage
                    </a>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item" href="{{ route('reinigungsplanung.export', request()->query()) }}">
                        <i class="bi bi-file-earmark-excel"></i> CSV Export
                    </a>
                </li>
            </ul>
        </div>
    </div>

    {{-- Aktive Vorlage Anzeige --}}
    <div id="vorlageAktivBox" class="alert alert-success py-2 mb-3 d-none">
        <div class="d-flex justify-content-between align-items-center">
            <div class="flex-grow-1 me-2">
                <i class="bi bi-lightning-charge"></i>
                <strong>Schnellversand aktiv</strong>
                <span class="d-none d-md-inline">–</span>
                <span id="vorlagePreview" class="small d-block d-md-inline"></span>
            </div>
            <div class="flex-shrink-0">
                <button type="button" class="btn btn-sm btn-outline-success me-1" data-bs-toggle="modal" data-bs-target="#modalVorlage">
                    <i class="bi bi-pencil"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="vorlageLoeschen()">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        </div>
    </div>

    {{-- Statistik-Karten --}}
    <div class="row g-2 mb-3">
        <div class="col-4">
            <div class="card border-primary h-100">
                <div class="card-body text-center py-2 py-md-3">
                    <h2 class="h3 h2-md fw-bold text-primary mb-0">{{ $stats['gesamt'] }}</h2>
                    <small class="text-muted d-none d-sm-inline">Gesamt</small>
                    <small class="text-muted d-sm-none" style="font-size: 0.7rem;">Ges.</small>
                </div>
            </div>
        </div>
        <div class="col-4">
            <div class="card border-warning h-100">
                <div class="card-body text-center py-2 py-md-3">
                    <h2 class="h3 h2-md fw-bold text-warning mb-0">{{ $stats['offen'] }}</h2>
                    <small class="text-muted">Offen</small>
                </div>
            </div>
        </div>
        <div class="col-4">
            <div class="card border-success h-100">
                <div class="card-body text-center py-2 py-md-3">
                    <h2 class="h3 h2-md fw-bold text-success mb-0">{{ $stats['erledigt'] }}</h2>
                    <small class="text-muted d-none d-sm-inline">Erledigt</small>
                    <small class="text-muted d-sm-none" style="font-size: 0.7rem;">Erl.</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter-Karte --}}
    <div class="card shadow-sm mb-3">
        <div class="card-header bg-light py-2" 
             data-bs-toggle="collapse" 
             data-bs-target="#filterCollapse" 
             role="button"
             aria-expanded="true">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="mb-0">
                    <i class="bi bi-funnel"></i> Filter
                    @php
                        $activeFilters = collect([$filterCodex, $filterGebaeude, $filterMonat, $filterTour, $filterStatus])->filter()->count();
                    @endphp
                    @if($activeFilters > 0)
                        <span class="badge bg-primary ms-1">{{ $activeFilters }}</span>
                    @endif
                </h6>
                <i class="bi bi-chevron-down d-md-none"></i>
            </div>
        </div>
        <div class="collapse show" id="filterCollapse">
            <div class="card-body py-2 py-md-3">
                <form method="GET" action="{{ route('reinigungsplanung.index') }}" id="filterForm">
                    <div class="row g-2">
                        <div class="col-6 col-md-2">
                            <label for="monat" class="form-label small mb-1">Monat</label>
                            <select name="monat" id="monat" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="" @selected(empty($filterMonat))>Alle</option>
                                @foreach($monate as $num => $name)
                                    <option value="{{ $num }}" @selected($filterMonat == $num)>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 col-md-2">
                            <label for="tour" class="form-label small mb-1">Tour</label>
                            <select name="tour" id="tour" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">Alle</option>
                                @foreach($touren as $t)
                                    <option value="{{ $t->id }}" @selected($filterTour == $t->id)>{{ $t->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 col-md-2">
                            <label for="codex" class="form-label small mb-1">Codex</label>
                            <input type="text" name="codex" id="codex" class="form-control form-control-sm" 
                                   value="{{ $filterCodex }}" placeholder="z.B. gam">
                        </div>
                        <div class="col-6 col-md-3">
                            <label for="gebaeude" class="form-label small mb-1">Gebäude</label>
                            <input type="text" name="gebaeude" id="gebaeude" class="form-control form-control-sm" 
                                   value="{{ $filterGebaeude }}" placeholder="Name, Ort...">
                        </div>
                        <div class="col-6 col-md-2">
                            <label for="status" class="form-label small mb-1">Status</label>
                            <select name="status" id="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="" @selected($filterStatus == '')>Alle</option>
                                <option value="offen" @selected($filterStatus == 'offen')>Offen</option>
                                <option value="erledigt" @selected($filterStatus == 'erledigt')>Erledigt</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-1 d-flex align-items-end gap-1">
                            <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                                <i class="bi bi-search"></i>
                            </button>
                            @if($activeFilters > 0)
                                <a href="{{ route('reinigungsplanung.index', ['clear_filter' => 1]) }}" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-x-lg"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Ergebnis --}}
    <div class="card shadow-sm">
        <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 small">
                <i class="bi bi-building"></i>
                {{ $gebaeude->total() }} Gebäude
            </h6>
            @if($stats['offen'] > 0)
                <span class="badge bg-warning text-dark">{{ $stats['offen'] }} offen</span>
            @else
                <span class="badge bg-success"><i class="bi bi-check"></i> Alle erledigt</span>
            @endif
        </div>

        @if($gebaeude->isEmpty())
            <div class="card-body text-center py-5">
                <i class="bi bi-inbox display-4 text-muted"></i>
                <p class="text-muted mt-2 mb-0">Keine Gebäude gefunden.</p>
            </div>
        @else
            {{-- Desktop: Tabelle --}}
            <div class="table-responsive d-none d-lg-block">
                <table class="table table-hover table-striped mb-0" id="reinigungsTable">
                    <thead class="table-dark">
                        <tr>
                            <th style="width: 90px;">Codex</th>
                            <th>Gebäude</th>
                            <th>Adresse</th>
                            <th style="width: 160px;">Kontakt</th>
                            <th style="width: 90px;">Tour</th>
                            <th style="width: 100px;">Letzte</th>
                            <th style="width: 100px;">Nächste</th>
                            <th style="width: 70px;" class="text-center">Status</th>
                            <th style="width: 70px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($gebaeude as $g)
                            @php
                                $handyClean = $g->handy ? preg_replace('/[^0-9+]/', '', $g->handy) : null;
                                $telefonClean = $g->telefon ? preg_replace('/[^0-9+]/', '', $g->telefon) : null;
                                $anrufNr = $telefonClean ?: $handyClean;
                                $smsNr = $handyClean ?: $telefonClean;
                                $adresseEncoded = urlencode(trim("{$g->strasse} {$g->hausnummer}, {$g->plz} {$g->wohnort}"));
                            @endphp
                            <tr class="{{ $g->ist_erledigt ? 'table-success' : '' }}">
                                <td>
                                    <a href="{{ route('gebaeude.edit', $g->id) }}" class="text-decoration-none fw-bold">
                                        {{ $g->codex ?: '-' }}
                                    </a>
                                </td>
                                <td>{{ $g->gebaeude_name ?: '(kein Name)' }}</td>
                                <td class="small">
                                    {{ $g->strasse }} {{ $g->hausnummer }}
                                    @if($g->wohnort), {{ $g->wohnort }}@endif
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        @if($anrufNr)
                                            <a href="tel:{{ $anrufNr }}" class="btn btn-outline-primary" title="Anrufen">
                                                <i class="bi bi-telephone"></i>
                                            </a>
                                        @endif
                                        @if($smsNr)
                                            <button type="button" class="btn btn-outline-secondary schnell-sms" 
                                                    data-nummer="{{ $smsNr }}" title="SMS senden">
                                                <i class="bi bi-chat-dots"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-success schnell-whatsapp" 
                                                    data-nummer="{{ ltrim($smsNr, '+') }}" title="WhatsApp senden">
                                                <i class="bi bi-whatsapp"></i>
                                            </button>
                                        @endif
                                        @if($adresseEncoded)
                                            <a href="https://maps.google.com/?q={{ $adresseEncoded }}" target="_blank" class="btn btn-outline-dark" title="Maps">
                                                <i class="bi bi-geo-alt"></i>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    @forelse($g->touren as $tour)
                                        <span class="badge bg-info text-dark">{{ $tour->name }}</span>
                                    @empty
                                        -
                                    @endforelse
                                </td>
                                <td>{{ $g->letzte_reinigung_datum?->format('d.m.Y') ?? '-' }}</td>
                                <td>{{ $g->naechste_faelligkeit?->format('d.m.Y') ?? '-' }}</td>
                                <td class="text-center">
                                    @if($g->ist_erledigt)
                                        <span class="badge bg-success"><i class="bi bi-check-lg"></i></span>
                                    @else
                                        <span class="badge bg-warning text-dark">Offen</span>
                                    @endif
                                </td>
                                <td>
                                    @if(!$g->ist_erledigt)
                                        <button type="button" class="btn btn-success btn-sm" 
                                                data-bs-toggle="modal" data-bs-target="#modalErledigt{{ $g->id }}">
                                            <i class="bi bi-check-lg"></i>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Mobile: Card-Liste --}}
            <div class="d-lg-none">
                @foreach($gebaeude as $g)
                    @php
                        $handyClean = $g->handy ? preg_replace('/[^0-9+]/', '', $g->handy) : null;
                        $telefonClean = $g->telefon ? preg_replace('/[^0-9+]/', '', $g->telefon) : null;
                        $anrufNr = $telefonClean ?: $handyClean;
                        $smsNr = $handyClean ?: $telefonClean;
                        $adresseEncoded = urlencode(trim("{$g->strasse} {$g->hausnummer}, {$g->plz} {$g->wohnort}"));
                    @endphp
                    <div class="border-bottom {{ $g->ist_erledigt ? 'bg-success bg-opacity-10' : '' }} p-2">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <div class="flex-grow-1 min-width-0">
                                <a href="{{ route('gebaeude.edit', $g->id) }}" class="text-decoration-none">
                                    <span class="fw-bold text-primary">{{ $g->codex ?: '-' }}</span>
                                    <span class="text-dark">{{ Str::limit($g->gebaeude_name ?: '', 20) }}</span>
                                </a>
                            </div>
                            <div class="flex-shrink-0 ms-2">
                                @if($g->ist_erledigt)
                                    <span class="badge bg-success"><i class="bi bi-check-lg"></i></span>
                                @else
                                    <span class="badge bg-warning text-dark">Offen</span>
                                @endif
                            </div>
                        </div>

                        <div class="small text-muted mb-2">
                            {{ $g->strasse }} {{ $g->hausnummer }}@if($g->wohnort), {{ $g->wohnort }}@endif
                        </div>

                        <div class="d-flex gap-1 mb-2">
                            @if($anrufNr)
                                <a href="tel:{{ $anrufNr }}" class="btn btn-outline-primary btn-sm py-1 px-2">
                                    <i class="bi bi-telephone"></i>
                                </a>
                            @endif
                            @if($smsNr)
                                <button type="button" class="btn btn-outline-secondary btn-sm py-1 px-2 schnell-sms"
                                        data-nummer="{{ $smsNr }}">
                                    <i class="bi bi-chat-dots"></i> SMS
                                </button>
                                <button type="button" class="btn btn-outline-success btn-sm py-1 px-2 schnell-whatsapp"
                                        data-nummer="{{ ltrim($smsNr, '+') }}">
                                    <i class="bi bi-whatsapp"></i> WA
                                </button>
                            @endif
                            @if($adresseEncoded)
                                <a href="https://maps.google.com/?q={{ $adresseEncoded }}" target="_blank" 
                                   class="btn btn-outline-dark btn-sm py-1 px-2">
                                    <i class="bi bi-geo-alt"></i>
                                </a>
                            @endif
                            
                            @if(!$g->ist_erledigt)
                                <button type="button" class="btn btn-success btn-sm py-1 px-2 ms-auto"
                                        data-bs-toggle="modal" data-bs-target="#modalErledigt{{ $g->id }}">
                                    <i class="bi bi-check-lg"></i>
                                </button>
                            @endif
                        </div>

                        <div class="small text-muted">
                            Letzte: {{ $g->letzte_reinigung_datum?->format('d.m.') ?? '-' }}
                            · Nächste: {{ $g->naechste_faelligkeit?->format('d.m.') ?? '-' }}
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Pagination --}}
    @if($gebaeude->hasPages())
        <div class="d-flex justify-content-center mt-3">
            {{ $gebaeude->links() }}
        </div>
    @endif
</div>

{{-- Modal: Nachrichten-Vorlage --}}
<div class="modal fade" id="modalVorlage" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-success text-white py-2">
                <h6 class="modal-title">
                    <i class="bi bi-chat-quote"></i> Nachrichten-Vorlage
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-3">
                
                {{-- Vorlage aus DB wählen --}}
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label fw-bold mb-0">Gespeicherte Vorlage laden:</label>
                        <a href="{{ route('textvorschlaege.index') }}" class="small" target="_blank">
                            <i class="bi bi-gear"></i> Vorlagen verwalten
                        </a>
                    </div>
                    <select id="vorlageSelect" class="form-select" onchange="vorlageAusDbLaden()">
                        <option value="">-- Vorlage wählen --</option>
                        @foreach($nachrichtVorschlaege as $v)
                            <option value="{{ $v->id }}" data-text="{{ $v->text }}">
                                {{ $v->anzeige_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <hr>

                {{-- Datum/Zeit --}}
                <div class="row g-2 mb-3">
                    <div class="col-4">
                        <label class="form-label small">📅 Datum</label>
                        <input type="date" id="vorlageDatum" class="form-control form-control-sm" 
                               value="{{ now()->format('Y-m-d') }}">
                    </div>
                    <div class="col-4">
                        <label class="form-label small">🕐 Von</label>
                        <input type="time" id="vorlageVon" class="form-control form-control-sm" value="09:00">
                    </div>
                    <div class="col-4">
                        <label class="form-label small">🕐 Bis</label>
                        <input type="time" id="vorlageBis" class="form-control form-control-sm" value="12:00">
                    </div>
                </div>

                {{-- Platzhalter einfügen --}}
                <div class="mb-2">
                    <label class="form-label small">Platzhalter einfügen:</label>
                    <div class="btn-group btn-group-sm flex-wrap">
                        <button type="button" class="btn btn-outline-info" onclick="einfuegenPlatzhalter('@{{DATUM}}')">
                            📅 Datum
                        </button>
                        <button type="button" class="btn btn-outline-info" onclick="einfuegenPlatzhalter('@{{VON}}')">
                            Von
                        </button>
                        <button type="button" class="btn btn-outline-info" onclick="einfuegenPlatzhalter('@{{BIS}}')">
                            Bis
                        </button>
                        <button type="button" class="btn btn-outline-info" onclick="einfuegenPlatzhalter('@{{ZEIT}}')">
                            Von-Bis
                        </button>
                    </div>
                </div>

                {{-- Nachricht Text --}}
                <div class="mb-3">
                    <label for="vorlageText" class="form-label fw-bold">Nachricht:</label>
                    <textarea class="form-control" id="vorlageText" rows="5" 
                              placeholder="Text eingeben (DE + IT)..."></textarea>
                </div>

                {{-- Vorschau --}}
                <div class="card bg-light">
                    <div class="card-header py-1">
                        <small class="fw-bold"><i class="bi bi-eye"></i> Vorschau:</small>
                    </div>
                    <div class="card-body py-2">
                        <pre id="vorlageVorschau" class="mb-0 small" style="white-space: pre-wrap; font-family: inherit;"></pre>
                    </div>
                </div>

                {{-- Als neue Vorlage speichern --}}
                <div class="mt-3 p-2 bg-light rounded">
                    <div class="d-flex gap-2 align-items-center">
                        <input type="text" id="vorlageTitel" class="form-control form-control-sm" 
                               placeholder="Titel für neue Vorlage..." style="max-width: 250px;">
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="vorlageInDbSpeichern()">
                            <i class="bi bi-plus-lg"></i> Als Vorlage speichern
                        </button>
                    </div>
                </div>

            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                <button type="button" class="btn btn-success" onclick="vorlageSpeichern()">
                    <i class="bi bi-lightning-charge"></i> Aktivieren
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Modals: Reinigung erledigt --}}
@foreach($gebaeude->getCollection()->filter(fn($g) => !$g->ist_erledigt) as $g)
    <div class="modal fade" id="modalErledigt{{ $g->id }}" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('reinigungsplanung.erledigt', $g->id) }}">
                    @csrf
                    <div class="modal-header bg-success text-white py-2">
                        <h6 class="modal-title">
                            <i class="bi bi-check-circle"></i> Reinigung eintragen
                        </h6>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body py-3">
                        <div class="alert alert-info py-2 mb-3">
                            <strong>{{ $g->codex }}</strong> - {{ $g->gebaeude_name ?: '(kein Name)' }}
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Mitarbeiter <span class="text-danger">*</span></label>
                            <select class="form-select" name="person_id" required>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" @selected(Auth::id() == $user->id)>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Datum</label>
                            <input type="date" class="form-control" name="datum" 
                                   value="{{ now()->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}">
                        </div>

                        <div class="mb-0">
                            <label class="form-label">Bemerkung</label>
                            <input type="text" class="form-control" name="bemerkung" maxlength="500">
                        </div>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-lg"></i> Speichern
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach
@endsection

@push('styles')
<style>
    @media (max-width: 575.98px) {
        .container-fluid { padding-left: 0.5rem; padding-right: 0.5rem; }
        .card-body { padding: 0.5rem; }
    }
    @media print { .no-print, .btn, button { display: none !important; } }
</style>
@endpush

@push('scripts')
<script>
const STORAGE_KEY = 'reinigung_nachricht_vorlage';
const CSRF_TOKEN = '{{ csrf_token() }}';

// Beim Laden
document.addEventListener('DOMContentLoaded', function() {
    vorlageAnzeigen();
    vorlageVorschauAktualisieren();
    
    ['vorlageText', 'vorlageDatum', 'vorlageVon', 'vorlageBis'].forEach(id => {
        document.getElementById(id)?.addEventListener('input', vorlageVorschauAktualisieren);
        document.getElementById(id)?.addEventListener('change', vorlageVorschauAktualisieren);
    });
});

// Vorlage aus DB-Dropdown laden
function vorlageAusDbLaden() {
    const select = document.getElementById('vorlageSelect');
    const option = select.options[select.selectedIndex];
    if (option && option.dataset.text) {
        document.getElementById('vorlageText').value = option.dataset.text;
        vorlageVorschauAktualisieren();
    }
}

// Platzhalter einfügen
function einfuegenPlatzhalter(ph) {
    const ta = document.getElementById('vorlageText');
    const start = ta.selectionStart;
    ta.value = ta.value.substring(0, start) + ph + ta.value.substring(ta.selectionEnd);
    ta.focus();
    ta.selectionStart = ta.selectionEnd = start + ph.length;
    vorlageVorschauAktualisieren();
}

// Vorschau aktualisieren
function vorlageVorschauAktualisieren() {
    const text = document.getElementById('vorlageText')?.value || '';
    document.getElementById('vorlageVorschau').textContent = platzhalterErsetzen(text) || '(Noch keine Nachricht)';
}

// Platzhalter ersetzen
function platzhalterErsetzen(text) {
    const datum = document.getElementById('vorlageDatum')?.value;
    const von = document.getElementById('vorlageVon')?.value;
    const bis = document.getElementById('vorlageBis')?.value;
    
    let datumStr = '';
    if (datum) {
        const d = new Date(datum);
        datumStr = d.toLocaleDateString('de-DE', {day: '2-digit', month: '2-digit', year: 'numeric'});
    }
    
    const vonStr = von?.substring(0, 5) || '';
    const bisStr = bis?.substring(0, 5) || '';
    const zeitStr = vonStr && bisStr ? `${vonStr} - ${bisStr}` : '';
    
    return text
        .replace(/\{\{DATUM\}\}/g, datumStr)
        .replace(/\{\{VON\}\}/g, vonStr)
        .replace(/\{\{BIS\}\}/g, bisStr)
        .replace(/\{\{ZEIT\}\}/g, zeitStr);
}

// Vorlage im Browser speichern (aktivieren)
function vorlageSpeichern() {
    const vorlage = {
        text: document.getElementById('vorlageText').value,
        datum: document.getElementById('vorlageDatum').value,
        von: document.getElementById('vorlageVon').value,
        bis: document.getElementById('vorlageBis').value
    };
    
    if (!vorlage.text.trim()) {
        alert('Bitte Nachricht eingeben!');
        return;
    }
    
    localStorage.setItem(STORAGE_KEY, JSON.stringify(vorlage));
    bootstrap.Modal.getInstance(document.getElementById('modalVorlage')).hide();
    vorlageAnzeigen();
}

// Vorlage in DB speichern (neue Vorlage)
function vorlageInDbSpeichern() {
    const text = document.getElementById('vorlageText').value;
    const titel = document.getElementById('vorlageTitel').value;
    
    if (!text.trim()) {
        alert('Bitte zuerst eine Nachricht eingeben!');
        return;
    }
    
    fetch('{{ route("textvorschlaege.api.store") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CSRF_TOKEN
        },
        body: JSON.stringify({
            kategorie: 'reinigung_nachricht',
            titel: titel || null,
            text: text
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            // Zum Dropdown hinzufügen
            const select = document.getElementById('vorlageSelect');
            const option = document.createElement('option');
            option.value = data.id;
            option.dataset.text = text;
            option.textContent = data.titel;
            select.appendChild(option);
            select.value = data.id;
            
            document.getElementById('vorlageTitel').value = '';
            alert('✅ ' + data.message);
        } else {
            alert('Fehler: ' + (data.message || 'Unbekannt'));
        }
    })
    .catch(err => {
        console.error(err);
        alert('Fehler beim Speichern!');
    });
}

// Vorlage anzeigen
function vorlageAnzeigen() {
    const gespeichert = localStorage.getItem(STORAGE_KEY);
    const box = document.getElementById('vorlageAktivBox');
    
    if (gespeichert) {
        const v = JSON.parse(gespeichert);
        const text = getNachrichtFertig(v);
        box.classList.remove('d-none');
        document.getElementById('vorlagePreview').textContent = text.substring(0, 60) + (text.length > 60 ? '...' : '');
        
        if (document.getElementById('vorlageText')) {
            document.getElementById('vorlageText').value = v.text;
            document.getElementById('vorlageDatum').value = v.datum;
            document.getElementById('vorlageVon').value = v.von;
            document.getElementById('vorlageBis').value = v.bis;
            vorlageVorschauAktualisieren();
        }
    } else {
        box.classList.add('d-none');
    }
}

// Fertige Nachricht
function getNachrichtFertig(v) {
    const d = new Date(v.datum);
    const datumStr = d.toLocaleDateString('de-DE', {day: '2-digit', month: '2-digit', year: 'numeric'});
    const vonStr = v.von?.substring(0, 5) || '';
    const bisStr = v.bis?.substring(0, 5) || '';
    const zeitStr = vonStr && bisStr ? `${vonStr} - ${bisStr}` : '';
    
    return v.text
        .replace(/\{\{DATUM\}\}/g, datumStr)
        .replace(/\{\{VON\}\}/g, vonStr)
        .replace(/\{\{BIS\}\}/g, bisStr)
        .replace(/\{\{ZEIT\}\}/g, zeitStr);
}

// Vorlage löschen
function vorlageLoeschen() {
    localStorage.removeItem(STORAGE_KEY);
    vorlageAnzeigen();
}

// Aktuelle Nachricht
function getAktuelleNachricht() {
    const g = localStorage.getItem(STORAGE_KEY);
    return g ? getNachrichtFertig(JSON.parse(g)) : null;
}

// SMS
document.querySelectorAll('.schnell-sms').forEach(btn => {
    btn.addEventListener('click', function() {
        const nr = this.dataset.nummer;
        const msg = getAktuelleNachricht();
        
        if (!msg) {
            new bootstrap.Modal(document.getElementById('modalVorlage')).show();
            return;
        }
        
        const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent);
        window.location.href = `sms:${nr}${isIOS ? '&' : '?'}body=${encodeURIComponent(msg)}`;
    });
});

// WhatsApp
document.querySelectorAll('.schnell-whatsapp').forEach(btn => {
    btn.addEventListener('click', function() {
        const nr = this.dataset.nummer;
        const msg = getAktuelleNachricht();
        
        if (!msg) {
            new bootstrap.Modal(document.getElementById('modalVorlage')).show();
            return;
        }
        
        window.open(`https://wa.me/${nr}?text=${encodeURIComponent(msg)}`, '_blank');
    });
});

// Filter
document.getElementById('filterForm')?.addEventListener('keypress', e => {
    if (e.key === 'Enter') { e.preventDefault(); e.target.form.submit(); }
});
</script>
@endpush
