{{-- resources/views/mahnungen/mahnlauf.blade.php --}}
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
                        <br><small class="text-muted">Kunden erhalten KEINE E-Mails.</small>
                    </p>
                </div>
            </div>
        </div>
    @endif

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
            @else
                <a href="{{ route('mahnungen.ausschluesse') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-shield-x"></i> Ausschlüsse
                </a>
            @endif
        </div>
    </div>

    {{-- ⭐ Einstellungen Info-Box --}}
    <div class="alert alert-secondary mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <i class="bi bi-gear"></i>
                <strong>Einstellungen:</strong>
                Zahlungsfrist: <span class="badge bg-secondary">{{ $einstellungen['zahlungsfrist_tage'] }} Tage</span>
                &nbsp;|&nbsp;
                Wartezeit zwischen Mahnungen: <span class="badge bg-secondary">{{ $einstellungen['wartezeit_zwischen_mahnungen'] }} Tage</span>
            </div>
            <a href="{{ route('mahnungen.stufen') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-sliders"></i> Konfiguration
            </a>
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

    @php
        // ⭐ Statistiken berechnen
        $mahnbar = $ueberfaellige->filter(fn($r) => $r->ist_mahnbar ?? false);
        $inWartezeit = $ueberfaellige->filter(fn($r) => !($r->ist_mahnbar ?? false) && !($r->hat_offenen_entwurf ?? false));
        $mitEntwurf = $ueberfaellige->filter(fn($r) => $r->hat_offenen_entwurf ?? false);
    @endphp

    @if($ueberfaellige->isEmpty())
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i>
            <strong>Keine überfälligen Rechnungen!</strong> 
            Alle Rechnungen sind bezahlt oder noch nicht fällig.
        </div>
    @else
        {{-- Statistik --}}
        <div class="row mb-4">
            <div class="col-md-2">
                <div class="card bg-light">
                    <div class="card-body text-center py-2">
                        <h3 class="mb-0">{{ $ueberfaellige->count() }}</h3>
                        <small>Überfällig</small>
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
                <div class="card border-success">
                    <div class="card-body text-center py-2">
                        <h3 class="mb-0 text-success">{{ $mahnbar->count() }}</h3>
                        <small>Jetzt mahnbar</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-light">
                    <div class="card-body text-center py-2">
                        <h3 class="mb-0 text-secondary">{{ $inWartezeit->count() }}</h3>
                        <small>In Wartezeit</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-light">
                    <div class="card-body text-center py-2">
                        <h3 class="mb-0 text-info">{{ $mitEntwurf->count() }}</h3>
                        <small>Entwurf offen</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-light">
                    <div class="card-body text-center py-2">
                        <h3 class="mb-0 text-warning">{{ $gesperrte->count() ?? 0 }}</h3>
                        <small>Ausgeschlossen</small>
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
                
                @if($mitEntwurf->count() > 0)
                    <div class="alert alert-info py-2 mb-2">
                        <i class="bi bi-info-circle"></i>
                        <strong>{{ $mitEntwurf->count() }} Rechnung(en)</strong> 
                        haben bereits einen Entwurf, der erst versendet werden muss.
                        <a href="{{ route('mahnungen.versand') }}" class="alert-link">→ Zum Versand</a>
                    </div>
                @endif

                @if($inWartezeit->count() > 0)
                    <div class="alert alert-secondary py-2 mb-0">
                        <i class="bi bi-clock-history"></i>
                        <strong>{{ $inWartezeit->count() }} Rechnung(en)</strong> 
                        wurden kürzlich gemahnt und sind noch in der Wartezeit 
                        ({{ $einstellungen['wartezeit_zwischen_mahnungen'] }} Tage zwischen Mahnungen).
                    </div>
                @endif
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <input type="checkbox" id="selectAll" class="form-check-input me-2">
                        <label for="selectAll" class="form-check-label fw-bold">Alle mahnbaren auswählen</label>
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
                                <th>Letzte Mahnung</th>
                                <th>Betrag</th>
                                <th>Nächste Stufe</th>
                                <th>E-Mail</th>
                                <th style="width: 80px;">Aktion</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($ueberfaellige as $rechnung)
                                @php
                                    $istMahnbar = $rechnung->ist_mahnbar ?? false;
                                    $hatEntwurf = $rechnung->hat_offenen_entwurf ?? false;
                                    $grundNichtMahnbar = $rechnung->grund_nicht_mahnbar ?? null;
                                @endphp
                                <tr class="{{ !$istMahnbar ? 'table-secondary opacity-75' : '' }}">
                                    <td>
                                        @if($istMahnbar && $rechnung->naechste_mahnstufe)
                                            <input type="checkbox" 
                                                   name="rechnung_ids[]" 
                                                   value="{{ $rechnung->id }}" 
                                                   class="form-check-input rechnung-checkbox">
                                        @elseif($hatEntwurf)
                                            <span class="text-info" title="Offener Entwurf vorhanden">
                                                <i class="bi bi-pencil-square"></i>
                                            </span>
                                        @else
                                            <span class="text-muted" title="{{ $grundNichtMahnbar }}">
                                                <i class="bi bi-hourglass-split"></i>
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('rechnung.edit', $rechnung->id) }}" class="fw-semibold">
                                            {{ $rechnung->volle_rechnungsnummer ?? $rechnung->laufnummer }}
                                        </a>
                                    </td>
                                    <td>
                                        {{ Str::limit($rechnung->rechnungsempfaenger?->name ?? '-', 30) }}
                                    </td>
                                    <td>{{ $rechnung->rechnungsdatum?->format('d.m.Y') }}</td>
                                    <td>
                                        <span class="badge bg-danger">
                                            {{ $rechnung->tage_ueberfaellig }} Tage
                                        </span>
                                    </td>
                                    <td>
                                        @if($rechnung->letzte_mahnung)
                                            <span class="badge bg-secondary">
                                                Stufe {{ $rechnung->letzte_mahnung->mahnstufe }}
                                            </span>
                                            @if($rechnung->letzte_mahnung->mahndatum)
                                                <small class="text-muted d-block">
                                                    {{ $rechnung->letzte_mahnung->mahndatum->format('d.m.Y') }}
                                                    (vor {{ $rechnung->tage_seit_letzter_mahnung }} T.)
                                                </small>
                                            @endif
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        {{ number_format($rechnung->brutto_summe ?? 0, 2, ',', '.') }} €
                                    </td>
                                    <td>
                                        @if($hatEntwurf)
                                            <a href="{{ route('mahnungen.show', $rechnung->offener_entwurf->id) }}" 
                                               class="badge bg-info text-decoration-none">
                                                <i class="bi bi-pencil"></i> Entwurf
                                            </a>
                                        @elseif($rechnung->naechste_mahnstufe)
                                            <span class="badge {{ $rechnung->naechste_mahnstufe->badge_class ?? 'bg-secondary' }}">
                                                <i class="bi {{ $rechnung->naechste_mahnstufe->icon ?? 'bi-envelope' }}"></i>
                                                Stufe {{ $rechnung->naechste_mahnstufe->stufe }}
                                            </span>
                                        @elseif($grundNichtMahnbar)
                                            <span class="badge bg-secondary" title="{{ $grundNichtMahnbar }}">
                                                <i class="bi bi-clock"></i> {{ $grundNichtMahnbar }}
                                            </span>
                                        @else
                                            <span class="badge bg-dark">Max. erreicht</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($rechnung->hat_email)
                                            <span class="text-success" title="{{ $rechnung->email_adresse }}{{ $rechnung->email_von_postadresse ? ' (Postadresse)' : '' }}">
                                                <i class="bi bi-envelope-check"></i>
                                            </span>
                                        @else
                                            <span class="text-danger" title="Keine E-Mail hinterlegt">
                                                <i class="bi bi-envelope-x"></i>
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        {{-- Ausschluss Button mit Modal --}}
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-secondary" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#modalAusschluss"
                                                data-rechnung-id="{{ $rechnung->id }}"
                                                data-rechnung-nr="{{ $rechnung->volle_rechnungsnummer ?? $rechnung->laufnummer }}"
                                                data-kunde="{{ $rechnung->rechnungsempfaenger?->name }}"
                                                title="Vom Mahnwesen ausschließen">
                                            <i class="bi bi-shield-x"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        </form>

    @endif

</div>

{{-- ⭐ MODAL: Rechnung vom Mahnwesen ausschließen --}}
<div class="modal fade" id="modalAusschluss" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('mahnungen.rechnung.ausschliessen') }}">
                @csrf
                <input type="hidden" name="rechnung_id" id="ausschlussRechnungId">
                
                <div class="modal-header bg-warning">
                    <h5 class="modal-title">
                        <i class="bi bi-shield-x"></i> Vom Mahnwesen ausschließen
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="alert alert-info mb-3">
                        <strong>Rechnung:</strong> <span id="ausschlussRechnungNr"></span><br>
                        <strong>Kunde:</strong> <span id="ausschlussKunde"></span>
                    </div>
                    
                    <div class="mb-3">
                        <label for="ausschluss_grund" class="form-label">Grund (optional)</label>
                        <textarea class="form-control" name="grund" id="ausschluss_grund" rows="2" 
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
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.rechnung-checkbox');
    const btnErstellen = document.getElementById('btnErstellen');
    const btnText = document.getElementById('btnText');

    function updateButton() {
        const checked = document.querySelectorAll('.rechnung-checkbox:checked');
        const count = checked.length;
        
        btnErstellen.disabled = count === 0;
        btnText.textContent = count > 0 
            ? `${count} Mahnung${count > 1 ? 'en' : ''} erstellen` 
            : 'Mahnungen erstellen';
    }

    if (selectAll) {
        selectAll.addEventListener('change', function() {
            checkboxes.forEach(cb => cb.checked = this.checked);
            updateButton();
        });
    }

    checkboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            const allChecked = Array.from(checkboxes).every(c => c.checked);
            const someChecked = Array.from(checkboxes).some(c => c.checked);
            selectAll.checked = allChecked;
            selectAll.indeterminate = someChecked && !allChecked;
            updateButton();
        });
    });

    // Ausschluss-Modal: Daten übertragen
    const modalAusschluss = document.getElementById('modalAusschluss');
    if (modalAusschluss) {
        modalAusschluss.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            document.getElementById('ausschlussRechnungId').value = button.dataset.rechnungId;
            document.getElementById('ausschlussRechnungNr').textContent = button.dataset.rechnungNr;
            document.getElementById('ausschlussKunde').textContent = button.dataset.kunde;
        });
    }
});
</script>
@endpush
@endsection
