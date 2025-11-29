{{-- resources/views/rechnung/logs/index.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card shadow-sm border-0">

        {{-- Header --}}
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h4 class="mb-0">
                <i class="bi bi-clock-history"></i>
                Log-Historie: Rechnung {{ $rechnung->rechnungsnummer }}
            </h4>
            <a href="{{ route('rechnung.edit', $rechnung->id) }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Zurück zur Rechnung
            </a>
        </div>

        {{-- Rechnung-Info --}}
        <div class="card-body border-bottom bg-light">
            <div class="row">
                <div class="col-md-3">
                    <strong>Empfänger:</strong><br>
                    {{ $rechnung->re_name ?? '-' }}
                </div>
                <div class="col-md-3">
                    <strong>Betrag:</strong><br>
                    {{ number_format($rechnung->gesamtbetrag_brutto, 2, ',', '.') }} €
                </div>
                <div class="col-md-3">
                    <strong>Status:</strong><br>
                    {!! $rechnung->status_badge !!}
                </div>
                <div class="col-md-3">
                    <strong>Datum:</strong><br>
                    {{ $rechnung->rechnungsdatum?->format('d.m.Y') ?? '-' }}
                </div>
            </div>
        </div>

        {{-- Filter --}}
        <div class="card-body border-bottom">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small">Kategorie</label>
                    <select name="kategorie" class="form-select form-select-sm">
                        <option value="">Alle Kategorien</option>
                        @foreach($kategorien as $key => $label)
                            <option value="{{ $key }}" {{ ($filter['kategorie'] ?? '') === $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Von</label>
                    <input type="date" name="von" class="form-control form-control-sm" 
                           value="{{ $filter['von'] ?? '' }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Bis</label>
                    <input type="date" name="bis" class="form-control form-control-sm" 
                           value="{{ $filter['bis'] ?? '' }}">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="bi bi-filter"></i> Filtern
                    </button>
                    <a href="{{ route('rechnung.logs.index', $rechnung->id) }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-x"></i> Reset
                    </a>
                </div>
                <div class="col-md-2 text-end">
                    <span class="badge bg-secondary">{{ $stats['gesamt'] }} Einträge</span>
                </div>
            </form>
        </div>

        {{-- Statistik-Karten --}}
        <div class="card-body border-bottom">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="card text-center bg-light">
                        <div class="card-body py-2">
                            <h5 class="mb-0">{{ $stats['gesamt'] }}</h5>
                            <small class="text-muted">Gesamt</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center bg-light">
                        <div class="card-body py-2">
                            <h5 class="mb-0">{{ $stats['dokumente'] }}</h5>
                            <small class="text-muted">Dokumente</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center bg-light">
                        <div class="card-body py-2">
                            <h5 class="mb-0">{{ $stats['kommunikation'] }}</h5>
                            <small class="text-muted">Kommunikation</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center {{ $stats['offene_erinnerungen'] > 0 ? 'bg-warning' : 'bg-light' }}">
                        <div class="card-body py-2">
                            <h5 class="mb-0">{{ $stats['offene_erinnerungen'] }}</h5>
                            <small>Offene Erinnerungen</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Log-Tabelle --}}
        <div class="card-body p-0">
            @if($logs->isEmpty())
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                    <p class="mt-2">Keine Log-Einträge gefunden.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="15%">Datum</th>
                                <th width="15%">Typ</th>
                                <th>Beschreibung</th>
                                <th width="12%">Kontakt</th>
                                <th width="10%">Benutzer</th>
                                <th width="8%" class="text-end">Aktion</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($logs as $log)
                                <tr class="{{ $log->prioritaet === 'kritisch' ? 'table-danger' : ($log->prioritaet === 'hoch' ? 'table-warning' : '') }}">
                                    <td>
                                        <span class="small">{{ $log->created_at->format('d.m.Y') }}</span><br>
                                        <span class="text-muted small">{{ $log->created_at->format('H:i') }}</span>
                                    </td>
                                    <td>
                                        {!! $log->typ_badge !!}
                                        @if($log->prioritaet !== 'normal')
                                            <br>{!! $log->prioritaet_badge !!}
                                        @endif
                                    </td>
                                    <td>
                                        <strong>{{ $log->titel }}</strong>
                                        @if($log->beschreibung)
                                            <p class="mb-0 small text-muted">{{ Str::limit($log->beschreibung, 150) }}</p>
                                        @endif
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
                                    </td>
                                    <td class="small">
                                        @if($log->kontakt_person)
                                            {{ $log->kontakt_person }}<br>
                                        @endif
                                        @if($log->kontakt_telefon)
                                            <i class="bi bi-telephone"></i> {{ $log->kontakt_telefon }}
                                        @endif
                                    </td>
                                    <td class="small">{{ $log->benutzer_name }}</td>
                                    <td class="text-end">
                                        @if($log->erinnerung_datum && !$log->erinnerung_erledigt)
                                            <button type="button" class="btn btn-sm btn-outline-success"
                                                    onclick="markErledigt({{ $log->id }})" title="Erledigen">
                                                <i class="bi bi-check"></i>
                                            </button>
                                        @endif
                                        @if(in_array($log->typ->value, ['telefonat', 'telefonat_eingehend', 'telefonat_ausgehend', 'mitteilung_kunde', 'mitteilung_intern', 'notiz', 'erinnerung']))
                                            <button type="button" class="btn btn-sm btn-outline-danger"
                                                    onclick="deleteLog({{ $log->id }})" title="Löschen">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                @if($logs->hasPages())
                    <div class="card-footer">
                        {{ $logs->withQueryString()->links() }}
                    </div>
                @endif
            @endif
        </div>

    </div>
</div>

<script>
function deleteLog(logId) {
    if (!confirm('Diesen Eintrag wirklich löschen?')) return;
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/rechnung/logs/' + logId;
    form.innerHTML = `
        <input type="hidden" name="_token" value="{{ csrf_token() }}">
        <input type="hidden" name="_method" value="DELETE">
    `;
    document.body.appendChild(form);
    form.submit();
}

function markErledigt(logId) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/rechnung/logs/' + logId + '/erledigt';
    form.innerHTML = `<input type="hidden" name="_token" value="{{ csrf_token() }}">`;
    document.body.appendChild(form);
    form.submit();
}
</script>
@endsection