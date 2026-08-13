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
                @if(!empty($filterDatum))
                    Reinigungen vom {{ \Carbon\Carbon::parse($filterDatum)->format('d.m.Y') }}
                    @if(!empty($filterPerson))
                        ({{ $users->firstWhere('id', $filterPerson)?->name ?? 'Unbekannt' }})
                    @endif
                @elseif(!empty($filterMonat))
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
                        $activeFilters = collect([$filterCodex, $filterGebaeude, $filterMonat, $filterTour, $filterStatus, $filterDatum, $filterPerson])->filter()->count();
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
                        {{-- Zeile 1: Datum, Person, Monat, Tour --}}
                        <div class="col-6 col-md-2">
                            <label for="datum" class="form-label small mb-1">Datum</label>
                            <input type="date" name="datum" id="datum" class="form-control form-control-sm" 
                                   value="{{ $filterDatum }}" onchange="this.form.submit()">
                        </div>
                        <div class="col-6 col-md-2">
                            <label for="person" class="form-label small mb-1">Wer</label>
                            <select name="person" id="person" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">Alle</option>
                                @foreach($users as $u)
                                    <option value="{{ $u->id }}" @selected($filterPerson == $u->id)>{{ $u->name }}</option>
                                @endforeach
                            </select>
                        </div>
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
                        {{-- Zeile 2: Codex, Gebäude, Status, Buttons --}}
                        <div class="col-6 col-md-1">
                            <label for="codex" class="form-label small mb-1">Codex</label>
                            <input type="text" name="codex" id="codex" class="form-control form-control-sm" 
                                   value="{{ $filterCodex }}" placeholder="z.B. gam">
                        </div>
                        <div class="col-6 col-md-2">
                            <label for="gebaeude" class="form-label small mb-1">Gebäude</label>
                            <input type="text" name="gebaeude" id="gebaeude" class="form-control form-control-sm" 
                                   value="{{ $filterGebaeude }}" placeholder="Name, Ort...">
                        </div>
                        <div class="col-6 col-md-auto">
                            <label for="status" class="form-label small mb-1">Status</label>
                            <select name="status" id="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="" @selected($filterStatus == '')>Alle</option>
                                <option value="offen" @selected($filterStatus == 'offen')>Offen</option>
                                <option value="erledigt" @selected($filterStatus == 'erledigt')>Erledigt</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-auto d-flex align-items-end gap-1">
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
                            <th>Bemerkung</th>
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
                                <td class="small text-muted">{{ Str::limit($g->bemerkung, 40) }}</td>
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
                                    <div class="d-flex gap-1">
                                        @if(!$g->ist_erledigt)
                                            <button type="button" class="btn btn-success btn-sm" 
                                                    data-bs-toggle="modal" data-bs-target="#modalErledigt{{ $g->id }}">
                                                <i class="bi bi-check-lg"></i>
                                            </button>
                                        @endif
                                        <div class="dropdown">
                                            <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="dropdown">
                                                <i class="bi bi-three-dots-vertical"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <button type="button" class="dropdown-item btn-monate"
                                                            data-id="{{ $g->id }}"
                                                            data-codex="{{ $g->codex }}"
                                                            data-name="{{ $g->gebaeude_name }}"
                                                            data-bemerkung="{{ $g->bemerkung }}"
                                                            @for($mi = 1; $mi <= 12; $mi++) data-m{{ str_pad($mi, 2, '0', STR_PAD_LEFT) }}="{{ $g->{'m'.str_pad($mi, 2, '0', STR_PAD_LEFT)} ?? 0 }}" @endfor>
                                                        <i class="bi bi-calendar-month text-primary"></i> Monate
                                                    </button>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <button type="button" class="dropdown-item text-danger btn-loeschen"
                                                            data-id="{{ $g->id }}"
                                                            data-codex="{{ $g->codex }}"
                                                            data-name="{{ $g->gebaeude_name }}">
                                                        <i class="bi bi-trash"></i> Löschen
                                                    </button>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
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
                            <div class="dropdown {{ $g->ist_erledigt ? 'ms-auto' : '' }}">
                                <button class="btn btn-outline-secondary btn-sm py-1 px-2" type="button" data-bs-toggle="dropdown">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <button type="button" class="dropdown-item btn-monate"
                                                data-id="{{ $g->id }}"
                                                data-codex="{{ $g->codex }}"
                                                data-name="{{ $g->gebaeude_name }}"
                                                data-bemerkung="{{ $g->bemerkung }}"
                                                @for($mi = 1; $mi <= 12; $mi++) data-m{{ str_pad($mi, 2, '0', STR_PAD_LEFT) }}="{{ $g->{'m'.str_pad($mi, 2, '0', STR_PAD_LEFT)} ?? 0 }}" @endfor>
                                            <i class="bi bi-calendar-month text-primary"></i> Monate
                                        </button>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <button type="button" class="dropdown-item text-danger btn-loeschen"
                                                data-id="{{ $g->id }}"
                                                data-codex="{{ $g->codex }}"
                                                data-name="{{ $g->gebaeude_name }}">
                                            <i class="bi bi-trash"></i> Löschen
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        @if($g->bemerkung)
                            <div class="small text-muted fst-italic mb-1">
                                <i class="bi bi-sticky"></i> {{ Str::limit($g->bemerkung, 80) }}
                            </div>
                        @endif

                        <div class="small text-muted">
                            Letzte: {{ $g->letzte_reinigung_datum?->format('d.m.Y') ?? '-' }}
                            · Nächste: {{ $g->naechste_faelligkeit?->format('d.m.Y') ?? '-' }}
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

{{-- Modal: Monate bearbeiten (geteilt) --}}
<div class="modal fade" id="modalMonate" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white py-2">
                <h6 class="modal-title">
                    <i class="bi bi-calendar-month"></i> Reinigungsmonate
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-3">
                <div class="alert alert-info py-2 mb-3 small">
                    <strong id="monateCodex"></strong> – <span id="monateName"></span>
                </div>
                <input type="hidden" id="monateGebaeudeId">

                <label class="form-label small fw-bold mb-1">Reinigungsmonate</label>
                <div class="row g-2 mb-2">
                    @php
                        $monatNamen = ['Jan','Feb','Mär','Apr','Mai','Jun','Jul','Aug','Sep','Okt','Nov','Dez'];
                    @endphp
                    @for($mi = 1; $mi <= 12; $mi++)
                        @php $mKey = 'm' . str_pad($mi, 2, '0', STR_PAD_LEFT); @endphp
                        <div class="col-4">
                            <div class="form-check">
                                <input class="form-check-input monat-check" type="checkbox" 
                                       id="check_{{ $mKey }}" data-field="{{ $mKey }}">
                                <label class="form-check-label small" for="check_{{ $mKey }}">
                                    {{ $monatNamen[$mi - 1] }}
                                </label>
                            </div>
                        </div>
                    @endfor
                </div>
                <div class="d-flex gap-1 mb-3">
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="monateAlleAn()">Alle</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="monateAlleAus()">Keine</button>
                </div>

                <label for="monateBemerkung" class="form-label small fw-bold mb-1">Bemerkung</label>
                <textarea id="monateBemerkung" class="form-control form-control-sm" rows="3"></textarea>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Abbrechen</button>
                <button type="button" class="btn btn-primary btn-sm" onclick="monateSpeichern()">
                    <i class="bi bi-check-lg"></i> Speichern
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Modal: Löschen bestätigen (geteilt) --}}
<div class="modal fade" id="modalLoeschen" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white py-2">
                <h6 class="modal-title">
                    <i class="bi bi-exclamation-triangle"></i> Gebäude löschen
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-3">
                <p class="mb-2">Dieses Gebäude wirklich löschen?</p>
                <div class="alert alert-warning py-2 small">
                    <strong id="loeschenCodex"></strong> – <span id="loeschenName"></span>
                </div>
                <input type="hidden" id="loeschenGebaeudeId">
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Abbrechen</button>
                <button type="button" class="btn btn-danger btn-sm" onclick="gebaeudeLoeschen()">
                    <i class="bi bi-trash"></i> Endgültig löschen
                </button>
            </div>
        </div>
    </div>
</div>

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

// ========== Monate Modal ==========
document.querySelectorAll('.btn-monate').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('monateGebaeudeId').value = this.dataset.id;
        document.getElementById('monateCodex').textContent = this.dataset.codex || '-';
        document.getElementById('monateName').textContent = this.dataset.name || '';
        document.getElementById('monateBemerkung').value = this.dataset.bemerkung || '';

        for (var mi = 1; mi <= 12; mi++) {
            var key = 'm' + String(mi).padStart(2, '0');
            var check = document.getElementById('check_' + key);
            if (check) check.checked = this.dataset[key] == '1';
        }

        new bootstrap.Modal(document.getElementById('modalMonate')).show();
    });
});

function monateAlleAn() {
    document.querySelectorAll('.monat-check').forEach(c => c.checked = true);
}

function monateAlleAus() {
    document.querySelectorAll('.monat-check').forEach(c => c.checked = false);
}

function monateSpeichern() {
    var id = document.getElementById('monateGebaeudeId').value;
    var data = { bemerkung: document.getElementById('monateBemerkung').value };

    document.querySelectorAll('.monat-check').forEach(function(c) {
        data[c.dataset.field] = c.checked ? 1 : 0;
    });

    var btn = document.querySelector('#modalMonate .btn-primary');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Speichern...';

    fetch('/reinigungsplanung/' + id + '/monate', {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CSRF_TOKEN,
            'Accept': 'application/json'
        },
        body: JSON.stringify(data),
        credentials: 'same-origin'
    }).then(function(response) {
        if (response.ok) {
            bootstrap.Modal.getInstance(document.getElementById('modalMonate')).hide();
            window.location.reload();
        } else {
            response.text().then(function(t) { alert('Fehler: ' + t); });
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-lg"></i> Speichern';
        }
    }).catch(function(err) {
        alert('Netzwerkfehler: ' + err.message);
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-lg"></i> Speichern';
    });
}

// ========== Löschen Modal ==========
document.querySelectorAll('.btn-loeschen').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('loeschenCodex').textContent = this.dataset.codex || '-';
        document.getElementById('loeschenName').textContent = this.dataset.name || '';
        document.getElementById('loeschenGebaeudeId').value = this.dataset.id;
        new bootstrap.Modal(document.getElementById('modalLoeschen')).show();
    });
});

function gebaeudeLoeschen() {
    var id = document.getElementById('loeschenGebaeudeId').value;
    var btn = document.querySelector('#modalLoeschen .btn-danger');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Löschen...';

    fetch('/gebaeude/' + id, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CSRF_TOKEN,
            'Accept': 'application/json'
        },
        body: JSON.stringify({_method: 'DELETE'}),
        credentials: 'same-origin'
    }).then(function(response) {
        if (response.ok || response.type === 'opaqueredirect' || response.status === 0) {
            bootstrap.Modal.getInstance(document.getElementById('modalLoeschen')).hide();
            window.location.reload();
        } else {
            response.text().then(function(t) { alert('Fehler: ' + t); });
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-trash"></i> Endgültig löschen';
        }
    }).catch(function(err) {
        alert('Netzwerkfehler: ' + err.message);
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-trash"></i> Endgültig löschen';
    });
}
</script>
@endpush
