{{-- resources/views/angebote/edit.blade.php --}}
{{-- MOBIL-OPTIMIERT: Cards, responsive Layout, Sticky Footer --}}
{{-- MIT TEXTVORSCHLÄGEN --}}

@extends('layouts.app')

@section('content')
<div class="container-fluid px-2 px-md-4 py-3">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h4 class="mb-0">
                <i class="bi bi-file-earmark-text text-primary"></i>
                {{ $angebot->angebotsnummer }}
            </h4>
            <div class="small">
                {!! $angebot->status_badge !!}
                @if($angebot->gebaeude)
                    <span class="text-muted ms-1">| {{ $angebot->geb_codex }}</span>
                @endif
            </div>
        </div>
        <a href="{{ route('angebote.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i>
            <span class="d-none d-sm-inline ms-1">Zurueck</span>
        </a>
    </div>

    {{-- Mobile Quick Actions --}}
    <div class="d-md-none mb-3">
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('angebote.pdf', ['angebot' => $angebot, 'preview' => 1]) }}" 
               class="btn btn-outline-secondary btn-sm flex-fill" target="_blank">
                <i class="bi bi-file-pdf"></i> PDF
            </a>
            @if($angebot->status !== 'rechnung')
            <a href="{{ route('angebote.versand', $angebot) }}" class="btn btn-primary btn-sm flex-fill">
                <i class="bi bi-envelope"></i> E-Mail
            </a>
            @endif
            @if(!$angebot->rechnung_id && in_array($angebot->status, ['angenommen', 'versendet']))
            <form method="POST" action="{{ route('angebote.zu-rechnung', $angebot) }}" class="flex-fill">
                @csrf
                <button type="submit" class="btn btn-success btn-sm w-100" 
                        onclick="return confirm('Zu Rechnung umwandeln?')">
                    <i class="bi bi-arrow-right-circle"></i> Rechnung
                </button>
            </form>
            @endif
        </div>
    </div>

    {{-- Desktop Action Buttons --}}
    <div class="d-none d-md-flex gap-2 mb-3">
        <a href="{{ route('angebote.pdf', ['angebot' => $angebot, 'preview' => 1]) }}" 
           class="btn btn-outline-secondary" target="_blank">
            <i class="bi bi-file-pdf"></i> PDF
        </a>
        @if($angebot->status !== 'rechnung')
        <a href="{{ route('angebote.versand', $angebot) }}" class="btn btn-primary">
            <i class="bi bi-envelope"></i> E-Mail
        </a>
        @endif
        @if(!$angebot->rechnung_id && in_array($angebot->status, ['angenommen', 'versendet']))
        <form method="POST" action="{{ route('angebote.zu-rechnung', $angebot) }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-success" 
                    onclick="return confirm('Angebot in Rechnung umwandeln?')">
                <i class="bi bi-arrow-right-circle"></i> Zu Rechnung
            </button>
        </form>
        @endif
        <form method="POST" action="{{ route('angebote.kopieren', $angebot) }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-outline-primary">
                <i class="bi bi-copy"></i> Kopieren
            </button>
        </form>
    </div>

    {{-- Alerts --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show py-2">
            <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show py-2">
            <i class="bi bi-exclamation-triangle me-1"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Verknuepfte Rechnung --}}
    @if($angebot->rechnung_id)
        <div class="alert alert-info py-2">
            <i class="bi bi-link-45deg me-1"></i>
            Umgewandelt in 
            <a href="{{ route('rechnung.edit', $angebot->rechnung_id) }}" class="alert-link">
                {{ $angebot->rechnung?->volle_rechnungsnummer ?? '#' . $angebot->rechnung_id }}
            </a>
            am {{ $angebot->umgewandelt_am?->format('d.m.Y') }}
        </div>
    @endif

    <div class="row">
        {{-- Hauptformular --}}
        <div class="col-lg-8">
            <form method="POST" action="{{ route('angebote.update', $angebot) }}" id="mainForm">
                @csrf
                @method('PUT')

                {{-- Angebotsdaten Card --}}
                <div class="card mb-3">
                    <div class="card-header bg-primary text-white py-2">
                        <i class="bi bi-info-circle-fill"></i>
                        <span class="fw-semibold ms-1">Angebotsdaten</span>
                    </div>
                    <div class="card-body p-2 p-md-3">
                        <div class="row g-2 g-md-3">
                            <div class="col-12 col-md-8">
                                <label class="form-label small mb-1">Titel <span class="text-danger">*</span></label>
                                <input type="text" name="titel" class="form-control" 
                                       value="{{ old('titel', $angebot->titel) }}" required>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label small mb-1">Fattura-Profil</label>
                                <select name="fattura_profile_id" class="form-select">
                                    <option value="">-- Standard --</option>
                                    @foreach($fatturaProfiles as $fp)
                                        <option value="{{ $fp->id }}" 
                                            {{ $angebot->fattura_profile_id == $fp->id ? 'selected' : '' }}>
                                            {{ $fp->bezeichnung }} ({{ $fp->mwst_satz }}%)
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6 col-md-4">
                                <label class="form-label small mb-1">Datum <span class="text-danger">*</span></label>
                                <input type="date" name="datum" class="form-control" 
                                       value="{{ old('datum', $angebot->datum->format('Y-m-d')) }}" required>
                            </div>
                            <div class="col-6 col-md-4">
                                <label class="form-label small mb-1">Gueltig bis</label>
                                <input type="date" name="gueltig_bis" class="form-control" 
                                       value="{{ old('gueltig_bis', $angebot->gueltig_bis?->format('Y-m-d')) }}">
                            </div>
                            <div class="col-6 col-md-4">
                                <label class="form-label small mb-1">MwSt %</label>
                                <input type="number" name="mwst_satz" class="form-control" 
                                       step="0.01" min="0" max="100"
                                       value="{{ old('mwst_satz', $angebot->mwst_satz) }}">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Empfaenger Card --}}
                <div class="card mb-3">
                    <div class="card-header bg-success text-white py-2">
                        <i class="bi bi-person-fill"></i>
                        <span class="fw-semibold ms-1">Empfaenger / Destinatario</span>
                    </div>
                    <div class="card-body p-2 p-md-3">
                        <div class="row g-2 g-md-3">
                            <div class="col-12">
                                <label class="form-label small mb-1">Name / Firma</label>
                                <input type="text" name="empfaenger_name" class="form-control" 
                                       value="{{ old('empfaenger_name', $angebot->empfaenger_name) }}">
                            </div>
                            <div class="col-8 col-md-9">
                                <label class="form-label small mb-1">Strasse</label>
                                <input type="text" name="empfaenger_strasse" class="form-control" 
                                       value="{{ old('empfaenger_strasse', $angebot->empfaenger_strasse) }}">
                            </div>
                            <div class="col-4 col-md-3">
                                <label class="form-label small mb-1">Nr.</label>
                                <input type="text" name="empfaenger_hausnummer" class="form-control" 
                                       value="{{ old('empfaenger_hausnummer', $angebot->empfaenger_hausnummer) }}">
                            </div>
                            <div class="col-4 col-md-3">
                                <label class="form-label small mb-1">PLZ</label>
                                <input type="text" name="empfaenger_plz" class="form-control" 
                                       value="{{ old('empfaenger_plz', $angebot->empfaenger_plz) }}">
                            </div>
                            <div class="col-8 col-md-5">
                                <label class="form-label small mb-1">Ort</label>
                                <input type="text" name="empfaenger_ort" class="form-control" 
                                       value="{{ old('empfaenger_ort', $angebot->empfaenger_ort) }}">
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label small mb-1">E-Mail</label>
                                <input type="email" name="empfaenger_email" class="form-control" 
                                       value="{{ old('empfaenger_email', $angebot->empfaenger_email) }}">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Texte Card MIT VORSCHLÄGEN --}}
                <div class="card mb-3">
                    <div class="card-header bg-secondary text-white py-2">
                        <i class="bi bi-text-paragraph"></i>
                        <span class="fw-semibold ms-1">Texte</span>
                    </div>
                    <div class="card-body p-2 p-md-3">
                        
                        {{-- Einleitung --}}
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label small mb-0">Einleitung (vor Positionen)</label>
                                <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" 
                                        onclick="toggleVorschlaege('einleitung')" title="Vorschlaege anzeigen">
                                    <i class="bi bi-lightbulb"></i>
                                    <span class="d-none d-sm-inline ms-1">Vorschlaege</span>
                                </button>
                            </div>
                            <div id="vorschlaege-einleitung" class="mb-2 d-none vorschlaege-container">
                                <div class="d-flex flex-wrap gap-1" id="vorschlaege-einleitung-liste">
                                    <span class="text-muted small">Lade...</span>
                                </div>
                            </div>
                            <textarea name="einleitung" id="einleitung" class="form-control" rows="2" 
                                      placeholder="Optional: Text vor den Positionen">{{ old('einleitung', $angebot->einleitung) }}</textarea>
                        </div>

                        {{-- Bemerkung für Kunde --}}
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label small mb-0">Bemerkung fuer Kunde (auf PDF)</label>
                                <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" 
                                        onclick="toggleVorschlaege('bemerkung_kunde')" title="Vorschlaege anzeigen">
                                    <i class="bi bi-lightbulb"></i>
                                    <span class="d-none d-sm-inline ms-1">Vorschlaege</span>
                                </button>
                            </div>
                            <div id="vorschlaege-bemerkung_kunde" class="mb-2 d-none vorschlaege-container">
                                <div class="d-flex flex-wrap gap-1" id="vorschlaege-bemerkung_kunde-liste">
                                    <span class="text-muted small">Lade...</span>
                                </div>
                            </div>
                            <textarea name="bemerkung_kunde" id="bemerkung_kunde" class="form-control" rows="2" 
                                      placeholder="Erscheint auf dem PDF">{{ old('bemerkung_kunde', $angebot->bemerkung_kunde) }}</textarea>
                        </div>

                        {{-- Interne Bemerkung --}}
                        <div class="mb-0">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label small mb-0">
                                    Interne Bemerkung 
                                    <span class="badge bg-warning text-dark">nicht auf PDF</span>
                                </label>
                                <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" 
                                        onclick="toggleVorschlaege('bemerkung_intern')" title="Vorschlaege anzeigen">
                                    <i class="bi bi-lightbulb"></i>
                                    <span class="d-none d-sm-inline ms-1">Vorschlaege</span>
                                </button>
                            </div>
                            <div id="vorschlaege-bemerkung_intern" class="mb-2 d-none vorschlaege-container">
                                <div class="d-flex flex-wrap gap-1" id="vorschlaege-bemerkung_intern-liste">
                                    <span class="text-muted small">Lade...</span>
                                </div>
                            </div>
                            <textarea name="bemerkung_intern" id="bemerkung_intern" class="form-control" rows="2" 
                                      placeholder="Nur intern sichtbar">{{ old('bemerkung_intern', $angebot->bemerkung_intern) }}</textarea>
                        </div>

                    </div>
                </div>

                {{-- Desktop Save Button --}}
                <div class="d-none d-md-block mb-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check2-circle"></i> Speichern
                    </button>
                </div>
            </form>

            {{-- Positionen Card --}}
            <div class="card mb-3">
                <div class="card-header bg-info text-white py-2 d-flex justify-content-between align-items-center">
                    <div>
                        <i class="bi bi-list-ol"></i>
                        <span class="fw-semibold ms-1">Positionen</span>
                    </div>
                    <button type="button" class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#modalNeuePosition">
                        <i class="bi bi-plus-lg"></i>
                        <span class="d-none d-sm-inline ms-1">Position</span>
                    </button>
                </div>

                {{-- MOBILE: Position Cards --}}
                <div class="d-md-none">
                    @forelse($angebot->positionen as $pos)
                    <div class="border-bottom p-3">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <span class="badge bg-light text-dark me-1">#{{ $pos->position }}</span>
                                <span class="fw-semibold">{{ Str::limit($pos->beschreibung, 40) }}</span>
                            </div>
                            <div class="fw-bold text-primary">{{ $pos->gesamtpreis_formatiert }}</div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="small text-muted">
                                {{ number_format($pos->anzahl, 2, ',', '.') }} {{ $pos->einheit }} 
                                x {{ $pos->einzelpreis_formatiert }}
                            </div>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-primary" 
                                        onclick="editPosition({{ $pos->id }}, '{{ addslashes($pos->beschreibung) }}', {{ $pos->anzahl }}, '{{ $pos->einheit }}', {{ $pos->einzelpreis }})">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="POST" action="{{ route('angebote.position.delete', $pos) }}" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger" 
                                            onclick="return confirm('Loeschen?')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="p-4 text-center text-muted">
                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                        Keine Positionen
                    </div>
                    @endforelse

                    {{-- Mobile Summen --}}
                    @if($angebot->positionen->isNotEmpty())
                    <div class="bg-dark text-white p-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Netto:</span>
                            <span>{{ $angebot->netto_formatiert }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1 small">
                            <span>MwSt ({{ number_format($angebot->mwst_satz, 0) }}%):</span>
                            <span>{{ number_format($angebot->mwst_betrag, 2, ',', '.') }} EUR</span>
                        </div>
                        <div class="d-flex justify-content-between fw-bold fs-5 pt-2 border-top border-secondary">
                            <span>Brutto:</span>
                            <span>{{ $angebot->brutto_formatiert }}</span>
                        </div>
                    </div>
                    @endif
                </div>

                {{-- DESKTOP: Position Table --}}
                <div class="d-none d-md-block table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th>Beschreibung</th>
                                <th class="text-end" style="width: 80px;">Anzahl</th>
                                <th style="width: 80px;">Einheit</th>
                                <th class="text-end" style="width: 100px;">Einzelpreis</th>
                                <th class="text-end" style="width: 100px;">Gesamt</th>
                                <th style="width: 80px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($angebot->positionen as $pos)
                            <tr>
                                <td>{{ $pos->position }}</td>
                                <td>{{ $pos->beschreibung }}</td>
                                <td class="text-end">{{ number_format($pos->anzahl, 2, ',', '.') }}</td>
                                <td>{{ $pos->einheit }}</td>
                                <td class="text-end">{{ $pos->einzelpreis_formatiert }}</td>
                                <td class="text-end fw-bold">{{ $pos->gesamtpreis_formatiert }}</td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-primary" 
                                                onclick="editPosition({{ $pos->id }}, '{{ addslashes($pos->beschreibung) }}', {{ $pos->anzahl }}, '{{ $pos->einheit }}', {{ $pos->einzelpreis }})">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form method="POST" action="{{ route('angebote.position.delete', $pos) }}" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger" 
                                                    onclick="return confirm('Position loeschen?')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-3">
                                    Keine Positionen vorhanden
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="table-dark">
                            <tr>
                                <th colspan="5" class="text-end">Netto:</th>
                                <th class="text-end">{{ $angebot->netto_formatiert }}</th>
                                <th></th>
                            </tr>
                            <tr>
                                <th colspan="5" class="text-end">MwSt ({{ number_format($angebot->mwst_satz, 0) }}%):</th>
                                <th class="text-end">{{ number_format($angebot->mwst_betrag, 2, ',', '.') }} EUR</th>
                                <th></th>
                            </tr>
                            <tr>
                                <th colspan="5" class="text-end">Brutto:</th>
                                <th class="text-end fs-5">{{ $angebot->brutto_formatiert }}</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="col-lg-4">
            {{-- Status aendern --}}
            <div class="card mb-3">
                <div class="card-header bg-warning py-2">
                    <i class="bi bi-flag-fill"></i>
                    <span class="fw-semibold ms-1">Status</span>
                </div>
                <div class="card-body p-2 p-md-3">
                    <form method="POST" action="{{ route('angebote.status', $angebot) }}">
                        @csrf
                        <div class="mb-2">
                            <select name="status" class="form-select">
                                <option value="entwurf" {{ $angebot->status === 'entwurf' ? 'selected' : '' }}>Entwurf</option>
                                <option value="versendet" {{ $angebot->status === 'versendet' ? 'selected' : '' }}>Versendet</option>
                                <option value="angenommen" {{ $angebot->status === 'angenommen' ? 'selected' : '' }}>Angenommen</option>
                                <option value="abgelehnt" {{ $angebot->status === 'abgelehnt' ? 'selected' : '' }}>Abgelehnt</option>
                                <option value="abgelaufen" {{ $angebot->status === 'abgelaufen' ? 'selected' : '' }}>Abgelaufen</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-outline-primary w-100">
                            <i class="bi bi-check"></i> Status aendern
                        </button>
                    </form>
                </div>
            </div>

            {{-- Info --}}
            <div class="card mb-3">
                <div class="card-header bg-light py-2">
                    <i class="bi bi-info-circle"></i>
                    <span class="fw-semibold ms-1">Info</span>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush small">
                        <li class="list-group-item d-flex justify-content-between py-2">
                            <span class="text-muted">Erstellt:</span>
                            <span>{{ $angebot->created_at->format('d.m.Y H:i') }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between py-2">
                            <span class="text-muted">Geaendert:</span>
                            <span>{{ $angebot->updated_at->format('d.m.Y H:i') }}</span>
                        </li>
                        @if($angebot->versendet_am)
                        <li class="list-group-item py-2">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Versendet:</span>
                                <span>{{ $angebot->versendet_am->format('d.m.Y H:i') }}</span>
                            </div>
                            <div class="text-muted text-end">{{ $angebot->versendet_an_email }}</div>
                        </li>
                        @endif
                    </ul>
                </div>
            </div>

            {{-- Log --}}
            <div class="card mb-3">
                <div class="card-header bg-light py-2">
                    <i class="bi bi-clock-history"></i>
                    <span class="fw-semibold ms-1">Verlauf</span>
                </div>
                <div class="card-body p-0" style="max-height: 250px; overflow-y: auto;">
                    <ul class="list-group list-group-flush small">
                        @forelse($angebot->logs->take(10) as $log)
                        <li class="list-group-item py-2">
                            <div class="d-flex align-items-start gap-2">
                                <i class="bi {{ $log->icon }} mt-1"></i>
                                <div class="flex-grow-1">
                                    <div class="fw-medium">{{ $log->titel }}</div>
                                    @if($log->nachricht)
                                    <div class="text-muted">{{ Str::limit($log->nachricht, 50) }}</div>
                                    @endif
                                    <div class="text-muted">
                                        {{ $log->created_at->format('d.m.Y H:i') }}
                                    </div>
                                </div>
                            </div>
                        </li>
                        @empty
                        <li class="list-group-item text-muted text-center py-3">
                            Keine Eintraege
                        </li>
                        @endforelse
                    </ul>
                </div>
            </div>

            {{-- Loeschen --}}
            @if(!$angebot->rechnung_id)
            <div class="card border-danger">
                <div class="card-body p-2">
                    <form method="POST" action="{{ route('angebote.destroy', $angebot) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger w-100"
                                onclick="return confirm('Angebot wirklich loeschen?')">
                            <i class="bi bi-trash"></i> Angebot loeschen
                        </button>
                    </form>
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- Sticky Footer Mobile --}}
    <div class="sticky-bottom-bar d-md-none">
        <button type="submit" form="mainForm" class="btn btn-primary w-100">
            <i class="bi bi-check2-circle"></i> Speichern
        </button>
    </div>
</div>

{{-- Modal: Neue Position --}}
<div class="modal fade" id="modalNeuePosition" tabindex="-1">
    <div class="modal-dialog modal-fullscreen-sm-down">
        <div class="modal-content">
            <form method="POST" action="{{ route('angebote.position.add', $angebot) }}">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="bi bi-plus-lg"></i> Neue Position</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Beschreibung <span class="text-danger">*</span></label>
                        <textarea name="beschreibung" class="form-control" rows="2" required></textarea>
                    </div>
                    <div class="row g-3">
                        <div class="col-4">
                            <label class="form-label">Anzahl</label>
                            <input type="number" name="anzahl" class="form-control" step="0.01" value="1" required>
                        </div>
                        <div class="col-4">
                            <label class="form-label">Einheit</label>
                            <input type="text" name="einheit" class="form-control" value="Stueck">
                        </div>
                        <div class="col-4">
                            <label class="form-label">Einzelpreis</label>
                            <input type="number" name="einzelpreis" class="form-control" step="0.01" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-plus-lg"></i> Hinzufuegen
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal: Position bearbeiten --}}
<div class="modal fade" id="modalEditPosition" tabindex="-1">
    <div class="modal-dialog modal-fullscreen-sm-down">
        <div class="modal-content">
            <form method="POST" id="formEditPosition" action="">
                @csrf
                @method('PUT')
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-pencil"></i> Position bearbeiten</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Beschreibung <span class="text-danger">*</span></label>
                        <textarea name="beschreibung" id="editBeschreibung" class="form-control" rows="2" required></textarea>
                    </div>
                    <div class="row g-3">
                        <div class="col-4">
                            <label class="form-label">Anzahl</label>
                            <input type="number" name="anzahl" id="editAnzahl" class="form-control" step="0.01" required>
                        </div>
                        <div class="col-4">
                            <label class="form-label">Einheit</label>
                            <input type="text" name="einheit" id="editEinheit" class="form-control">
                        </div>
                        <div class="col-4">
                            <label class="form-label">Einzelpreis</label>
                            <input type="number" name="einzelpreis" id="editEinzelpreis" class="form-control" step="0.01" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check"></i> Speichern
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('styles')
<style>
.sticky-bottom-bar {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: #fff;
    border-top: 1px solid #dee2e6;
    padding: 12px 16px;
    z-index: 1030;
    box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
}
@media (max-width: 767.98px) {
    .container-fluid { padding-bottom: 80px; }
    .form-control, .form-select, .btn { min-height: 44px; font-size: 16px !important; }
}

/* Textvorschläge Styling */
.vorschlaege-container {
    background: #f8f9fa;
    border-radius: 0.375rem;
    padding: 0.5rem;
    border: 1px dashed #dee2e6;
}
.vorschlag-btn {
    font-size: 0.75rem;
    padding: 0.2rem 0.5rem;
    max-width: 250px;
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
</style>
@endpush

@push('scripts')
<script>
// Position bearbeiten Modal
function editPosition(id, beschreibung, anzahl, einheit, einzelpreis) {
    document.getElementById('formEditPosition').action = '/angebote/position/' + id;
    document.getElementById('editBeschreibung').value = beschreibung;
    document.getElementById('editAnzahl').value = anzahl;
    document.getElementById('editEinheit').value = einheit;
    document.getElementById('editEinzelpreis').value = einzelpreis;
    
    new bootstrap.Modal(document.getElementById('modalEditPosition')).show();
}

// ==========================================
// TEXTVORSCHLÄGE FUNKTIONEN
// ==========================================

// Cache für Vorschläge
let textvorschlaegeCache = null;

// Vorschläge laden
async function ladeTextvorschlaege() {
    if (textvorschlaegeCache) return textvorschlaegeCache;
    
    try {
        const response = await fetch('{{ route("angebote.textvorschlaege") }}');
        textvorschlaegeCache = await response.json();
        return textvorschlaegeCache;
    } catch (error) {
        console.error('Fehler beim Laden der Vorschlaege:', error);
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
            liste.innerHTML = '<span class="text-muted small"><i class="bi bi-info-circle me-1"></i>Keine Vorschlaege vorhanden</span>';
        } else {
            liste.innerHTML = feldVorschlaege.map(text => {
                // Text kürzen für Button-Anzeige
                const kurztext = text.length > 40 ? text.substring(0, 40) + '...' : text;
                // Text escapen für onclick
                const escapedText = text.replace(/'/g, "\\'").replace(/\n/g, "\\n").replace(/\r/g, "");
                return `<button type="button" class="btn btn-outline-secondary vorschlag-btn" 
                                onclick="setzeVorschlag('${feld}', '${escapedText}')" 
                                title="${text.replace(/"/g, '&quot;').replace(/\n/g, ' ')}">
                            ${kurztext}
                        </button>`;
            }).join('');
        }
        
        container.classList.remove('d-none');
    } else {
        container.classList.add('d-none');
    }
}

// Vorschlag in Textfeld einfügen
function setzeVorschlag(feld, text) {
    const textarea = document.getElementById(feld);
    // Umwandlung von escaped newlines zurück
    text = text.replace(/\\n/g, "\n");
    
    if (textarea.value && textarea.value.trim() !== '') {
        // Bestehenden Text ergänzen oder ersetzen?
        if (confirm('Bestehenden Text ersetzen?\n\nOK = Ersetzen\nAbbrechen = Anhaengen')) {
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
@endsection
