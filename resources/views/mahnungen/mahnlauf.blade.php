{{-- resources/views/mahnungen/mahnlauf.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container py-3">
    
    {{-- Kopfzeile --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0"><i class="bi bi-play-circle"></i> Mahnlauf vorbereiten</h4>
            <small class="text-muted">Wählen Sie die zu mahnenden Rechnungen</small>
        </div>
        <div class="btn-group">
            <a href="{{ route('mahnungen.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Zurück
            </a>
            @if(isset($gesperrte) && $gesperrte->count() > 0)
                <a href="{{ route('mahnungen.ausschluesse') }}" class="btn btn-outline-warning">
                    <i class="bi bi-shield-x"></i> Ausschlüsse ({{ $gesperrte->count() }})
                </a>
            @endif
        </div>
    </div>

    {{-- ⭐ FILTER-BOX --}}
    <div class="card mb-4">
        <div class="card-header bg-light">
            <i class="bi bi-funnel"></i> Filter
        </div>
        <div class="card-body py-3">
            <form method="GET" action="{{ route('mahnungen.mahnlauf') }}" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Anzeige</label>
                    <select name="filter" class="form-select" onchange="toggleTageInput(this)">
                        <option value="alle" {{ ($filter ?? 'alle') === 'alle' ? 'selected' : '' }}>
                            Alle überfälligen Rechnungen
                        </option>
                        <option value="wiederholung" {{ ($filter ?? '') === 'wiederholung' ? 'selected' : '' }}>
                            Wiederholung: Letzte Mahnung älter als X Tage
                        </option>
                    </select>
                </div>
                <div class="col-md-3" id="tageInputWrapper" style="{{ ($filter ?? 'alle') !== 'wiederholung' ? 'display:none;' : '' }}">
                    <label class="form-label">Tage seit letzter Mahnung</label>
                    <div class="input-group">
                        <input type="number" name="tage" class="form-control" 
                               value="{{ $tageAlt ?? 14 }}" min="1" max="365">
                        <span class="input-group-text">Tage</span>
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Anwenden
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Bank-Warnung --}}
    @if($bankAktualitaet['warnung'])
        <div class="alert alert-danger d-flex align-items-center mb-4">
            <i class="bi bi-exclamation-octagon-fill me-3 fs-3"></i>
            <div class="flex-grow-1">
                <strong>Wichtig!</strong><br>
                {{ $bankAktualitaet['warnung_text'] }}<br>
                <small>Bitte importieren Sie zuerst aktuelle Bank-Buchungen, um versehentliche Mahnungen bereits bezahlter Rechnungen zu vermeiden.</small>
            </div>
            <a href="{{ route('bank.import') }}" class="btn btn-danger">
                <i class="bi bi-upload"></i> Jetzt importieren
            </a>
        </div>
    @else
        <div class="alert alert-success d-flex align-items-center mb-4">
            <i class="bi bi-check-circle-fill me-2"></i>
            <div>
                Bank-Buchungen sind aktuell (letzter Import: {{ $bankAktualitaet['letzter_import']?->format('d.m.Y H:i') ?? '-' }})
            </div>
        </div>
    @endif

    @if($ueberfaellige->isEmpty())
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i>
            @if(($filter ?? 'alle') === 'wiederholung')
                <strong>Keine Wiederholungs-Mahnungen!</strong> 
                Keine Rechnungen gefunden, deren letzte Mahnung älter als {{ $tageAlt ?? 14 }} Tage ist.
            @else
                <strong>Keine überfälligen Rechnungen!</strong> 
                Alle Rechnungen sind bezahlt oder noch nicht fällig.
            @endif
        </div>
    @else
        {{-- Statistik --}}
        <div class="row mb-4">
            <div class="col-md-2">
                <div class="card bg-light">
                    <div class="card-body text-center py-2">
                        <h3 class="mb-0">{{ $ueberfaellige->count() }}</h3>
                        <small>{{ ($filter ?? 'alle') === 'wiederholung' ? 'Wiederholung' : 'Überfällig' }}</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-light">
                    <div class="card-body text-center py-2">
                        <h3 class="mb-0">{{ number_format($ueberfaellige->sum(fn($r) => (float) ($r->brutto_summe ?? 0)), 2, ',', '.') }} €</h3>
                        <small>Offener Betrag</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-light">
                    <div class="card-body text-center py-2">
                        <h3 class="mb-0 text-primary">{{ $ueberfaellige->filter(fn($r) => $r->naechste_mahnstufe !== null)->count() }}</h3>
                        <small>Mahnbar</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-light">
                    <div class="card-body text-center py-2">
                        <h3 class="mb-0 text-info">{{ $ueberfaellige->filter(fn($r) => $r->hat_offenen_entwurf ?? false)->count() }}</h3>
                        <small>Entwurf offen</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-light">
                    <div class="card-body text-center py-2">
                        <h3 class="mb-0 text-warning">{{ $gesperrte->count() ?? 0 }}</h3>
                        <small>Gesperrt</small>
                    </div>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('mahnungen.erstellen') }}" id="mahnlaufForm">
            @csrf

            {{-- Legende --}}
            <div class="mb-3">
                <div class="d-flex gap-3 flex-wrap mb-2">
                    @foreach($stufen as $stufe)
                        <span class="badge {{ $stufe->badge_class }}">
                            <i class="bi {{ $stufe->icon }}"></i>
                            {{ $stufe->name_de }} (ab {{ $stufe->tage_ueberfaellig }} Tage)
                        </span>
                    @endforeach
                </div>
                
                @if($ueberfaellige->where('hat_offenen_entwurf', true)->count() > 0)
                    <div class="alert alert-info py-2 mb-0">
                        <i class="bi bi-info-circle"></i>
                        <strong>{{ $ueberfaellige->where('hat_offenen_entwurf', true)->count() }} Rechnung(en)</strong> 
                        haben bereits einen Entwurf, der erst versendet werden muss.
                        <a href="{{ route('mahnungen.versand') }}" class="alert-link">→ Zum Versand</a>
                    </div>
                @endif
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <input type="checkbox" id="selectAll" class="form-check-input me-2">
                        <label for="selectAll" class="form-check-label fw-bold">Alle auswählen</label>
                    </div>
                    <button type="submit" class="btn btn-primary" id="btnErstellen" disabled>
                        <i class="bi bi-envelope-plus"></i> 
                        <span id="btnText">Mahnungen erstellen</span>
                    </button>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 40px;"></th>
                                <th>Rechnung</th>
                                <th>Kunde</th>
                                <th>Datum</th>
                                <th>Überfällig</th>
                                @if(($filter ?? 'alle') === 'wiederholung')
                                    <th>Letzte Mahnung</th>
                                @endif
                                <th>Betrag</th>
                                <th>Nächste Stufe</th>
                                <th>E-Mail</th>
                                <th style="width: 80px;">Aktion</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($ueberfaellige as $rechnung)
                                @php
                                    $istBlockiert = ($rechnung->hat_offenen_entwurf ?? false) && !$rechnung->naechste_mahnstufe;
                                @endphp
                                <tr class="{{ $istBlockiert ? 'table-info' : (!$rechnung->hat_email ? 'table-warning' : '') }}">
                                    <td>
                                        @if($istBlockiert)
                                            <span class="text-info" title="Entwurf muss erst versendet werden">
                                                <i class="bi bi-hourglass-split"></i>
                                            </span>
                                        @else
                                            <input type="checkbox" 
                                                   name="rechnung_ids[]" 
                                                   value="{{ $rechnung->id }}"
                                                   class="form-check-input rechnung-checkbox">
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ url('/rechnung/' . $rechnung->id . '/edit') }}" target="_blank">
                                            {{ $rechnung->volle_rechnungsnummer ?? ($rechnung->jahr && $rechnung->laufnummer ? $rechnung->jahr.'/'.$rechnung->laufnummer : $rechnung->laufnummer ?? '-') }}
                                        </a>
                                    </td>
                                    <td>
                                        {{ Str::limit($rechnung->rechnungsempfaenger?->name, 25) }}
                                    </td>
                                    <td>
                                        {{ $rechnung->rechnungsdatum?->format('d.m.Y') }}
                                        <br>
                                        <small class="text-muted">Fällig: {{ $rechnung->faellig_am?->format('d.m.Y') }}</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-danger">{{ $rechnung->tage_ueberfaellig }} Tage</span>
                                    </td>
                                    @if(($filter ?? 'alle') === 'wiederholung')
                                        <td>
                                            @if($rechnung->tage_seit_letzter_mahnung)
                                                <span class="badge bg-warning text-dark">
                                                    vor {{ $rechnung->tage_seit_letzter_mahnung }} Tagen
                                                </span>
                                            @else
                                                -
                                            @endif
                                        </td>
                                    @endif
                                    <td class="text-end">
                                        {{ number_format($rechnung->brutto_summe ?? 0, 2, ',', '.') }} €
                                    </td>
                                    <td>
                                        @if($istBlockiert)
                                            <a href="{{ route('mahnungen.versand') }}" class="badge bg-info text-decoration-none">
                                                <i class="bi bi-hourglass-split"></i>
                                                Stufe {{ $rechnung->offener_entwurf->mahnstufe }} wartet
                                            </a>
                                        @elseif($rechnung->naechste_mahnstufe)
                                            <span class="badge {{ $rechnung->naechste_mahnstufe->badge_class }}">
                                                <i class="bi {{ $rechnung->naechste_mahnstufe->icon }}"></i>
                                                {{ $rechnung->naechste_mahnstufe->name_de }}
                                            </span>
                                            @if($rechnung->naechste_mahnstufe->spesen > 0)
                                                <br>
                                                <small class="text-muted">+{{ number_format($rechnung->naechste_mahnstufe->spesen, 2, ',', '.') }} € Spesen</small>
                                            @endif
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($rechnung->hat_email)
                                            <span class="badge bg-success" title="{{ $rechnung->email_adresse }}">
                                                <i class="bi bi-envelope-check"></i>
                                            </span>
                                            @if($rechnung->email_von_postadresse ?? false)
                                                <span class="badge bg-info text-dark" title="E-Mail von Postadresse">P</span>
                                            @endif
                                        @else
                                            <span class="badge bg-warning text-dark" title="Keine E-Mail - Postversand">
                                                <i class="bi bi-mailbox"></i>
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        {{-- ⭐ Ausschluss/Mahnsperre Button --}}
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-secondary" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#modalMahnsperre"
                                                data-rechnung-id="{{ $rechnung->id }}"
                                                data-rechnung-nr="{{ $rechnung->volle_rechnungsnummer ?? $rechnung->laufnummer }}"
                                                data-kunde="{{ $rechnung->rechnungsempfaenger?->name }}"
                                                title="Vom Mahnwesen ausschließen">
                                            <i class="bi bi-shield-x"></i>
                                        </button>
                                    </td>
                                </tr>
                                @if($rechnung->letzte_mahnung)
                                    <tr class="table-light">
                                        <td></td>
                                        <td colspan="{{ ($filter ?? 'alle') === 'wiederholung' ? 9 : 8 }}">
                                            <small class="text-muted">
                                                <i class="bi bi-info-circle"></i>
                                                Letzte Mahnung: {{ $rechnung->letzte_mahnung->mahndatum->format('d.m.Y') }}
                                                ({{ $rechnung->letzte_mahnung->stufe?->name_de ?? 'Stufe ' . $rechnung->letzte_mahnung->mahnstufe }})
                                                - {!! $rechnung->letzte_mahnung->status_badge !!}
                                                @if($rechnung->letzte_mahnung->gesendet_am)
                                                    - versendet {{ $rechnung->letzte_mahnung->gesendet_am->format('d.m.Y') }}
                                                @endif
                                            </small>
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </form>
    @endif

</div>

{{-- ⭐ MODAL: Mahnsperre setzen --}}
<div class="modal fade" id="modalMahnsperre" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('mahnungen.ausschluss.rechnung') }}">
                @csrf
                <input type="hidden" name="rechnung_id" id="mahnsperreRechnungId">
                
                <div class="modal-header bg-warning">
                    <h5 class="modal-title">
                        <i class="bi bi-shield-x"></i> Vom Mahnwesen ausschließen
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="alert alert-info mb-3">
                        <strong>Rechnung:</strong> <span id="mahnsperreRechnungNr"></span><br>
                        <strong>Kunde:</strong> <span id="mahnsperreKunde"></span>
                    </div>
                    
                    <div class="mb-3">
                        <label for="mahnsperre_grund" class="form-label">Grund (optional)</label>
                        <textarea class="form-control" name="grund" id="mahnsperre_grund" rows="2" 
                                  placeholder="z.B. Ratenzahlung vereinbart, Reklamation offen..."></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="bis_datum" class="form-label">Ausschließen bis (optional)</label>
                        <input type="date" class="form-control" name="bis_datum" id="bis_datum">
                        <small class="text-muted">Leer = permanent ausgeschlossen</small>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-shield-x"></i> Ausschließen
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Checkboxen-Logik
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.rechnung-checkbox');
    const btnErstellen = document.getElementById('btnErstellen');
    const btnText = document.getElementById('btnText');

    function updateButton() {
        const checked = document.querySelectorAll('.rechnung-checkbox:checked').length;
        btnErstellen.disabled = checked === 0;
        btnText.textContent = checked > 0 
            ? `${checked} Mahnung${checked > 1 ? 'en' : ''} erstellen`
            : 'Mahnungen erstellen';
    }

    selectAll?.addEventListener('change', function() {
        checkboxes.forEach(cb => cb.checked = this.checked);
        updateButton();
    });

    checkboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            const allChecked = Array.from(checkboxes).every(c => c.checked);
            const someChecked = Array.from(checkboxes).some(c => c.checked);
            selectAll.checked = allChecked;
            selectAll.indeterminate = someChecked && !allChecked;
            updateButton();
        });
    });

    // Mahnsperre-Modal: Daten übertragen
    const modalMahnsperre = document.getElementById('modalMahnsperre');
    if (modalMahnsperre) {
        modalMahnsperre.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            document.getElementById('mahnsperreRechnungId').value = button.dataset.rechnungId;
            document.getElementById('mahnsperreRechnungNr').textContent = button.dataset.rechnungNr;
            document.getElementById('mahnsperreKunde').textContent = button.dataset.kunde;
        });
    }
});

// Filter: Tage-Input ein/ausblenden
function toggleTageInput(select) {
    const wrapper = document.getElementById('tageInputWrapper');
    wrapper.style.display = select.value === 'wiederholung' ? '' : 'none';
}
</script>
@endpush
@endsection
