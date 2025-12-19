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
        <a href="{{ route('mahnungen.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Zurück
        </a>
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
                        {{-- ⭐ brutto_summe statt brutto --}}
                        <h3 class="mb-0">{{ number_format($ueberfaellige->sum(fn($r) => (float) ($r->brutto_summe ?? 0)), 2, ',', '.') }} €</h3>
                        <small>Offener Betrag</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-light">
                    <div class="card-body text-center py-2">
                        {{-- ⭐ Nur mahnbare (ohne blockierte) --}}
                        <h3 class="mb-0 text-primary">{{ $ueberfaellige->filter(fn($r) => $r->naechste_mahnstufe !== null)->count() }}</h3>
                        <small>Mahnbar</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-light">
                    <div class="card-body text-center py-2">
                        {{-- ⭐ Blockierte (Entwurf offen) --}}
                        <h3 class="mb-0 text-info">{{ $ueberfaellige->filter(fn($r) => $r->hat_offenen_entwurf)->count() }}</h3>
                        <small>Entwurf offen</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-light">
                    <div class="card-body text-center py-2">
                        <h3 class="mb-0 text-success">{{ $ueberfaellige->filter(fn($r) => $r->hat_email && $r->naechste_mahnstufe)->count() }}</h3>
                        <small>Mit E-Mail</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-light">
                    <div class="card-body text-center py-2">
                        <h3 class="mb-0 text-warning">{{ $ueberfaellige->filter(fn($r) => !$r->hat_email && $r->naechste_mahnstufe)->count() }}</h3>
                        <small>Post nötig</small>
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
                
                {{-- ⭐ Hinweis für blockierte Rechnungen --}}
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
                                <th>Rechnungsdatum</th>
                                <th>Überfällig</th>
                                <th>Betrag</th>
                                <th>Nächste Stufe</th>
                                <th>E-Mail</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($ueberfaellige as $rechnung)
                                @php
                                    $istBlockiert = $rechnung->hat_offenen_entwurf && !$rechnung->naechste_mahnstufe;
                                @endphp
                                <tr class="{{ $istBlockiert ? 'table-info' : (!$rechnung->hat_email ? 'table-warning' : '') }}">
                                    <td>
                                        @if($istBlockiert)
                                            {{-- ⭐ Blockiert: Entwurf muss erst versendet werden --}}
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
                                            {{-- ⭐ Robuste Rechnungsnummer --}}
                                            {{ $rechnung->volle_rechnungsnummer ?? ($rechnung->jahr && $rechnung->laufnummer ? $rechnung->jahr.'/'.$rechnung->laufnummer : $rechnung->laufnummer ?? '-') }}
                                        </a>
                                    </td>
                                    <td>
                                        {{ Str::limit($rechnung->rechnungsempfaenger?->name, 30) }}
                                    </td>
                                    <td>
                                        {{ $rechnung->rechnungsdatum?->format('d.m.Y') }}
                                        <br>
                                        <small class="text-muted">Fällig: {{ $rechnung->faellig_am?->format('d.m.Y') }}</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-danger">{{ $rechnung->tage_ueberfaellig }} Tage</span>
                                    </td>
                                    <td class="text-end">
                                        {{-- ⭐ brutto_summe statt brutto --}}
                                        {{ number_format($rechnung->brutto_summe ?? 0, 2, ',', '.') }} €
                                    </td>
                                    <td>
                                        @if($istBlockiert)
                                            {{-- ⭐ Entwurf existiert - zeige Link zum Versand --}}
                                            <a href="{{ route('mahnungen.versand') }}" class="badge bg-info text-decoration-none">
                                                <i class="bi bi-hourglass-split"></i>
                                                Stufe {{ $rechnung->offener_entwurf->mahnstufe }} wartet
                                            </a>
                                            <br>
                                            <small class="text-muted">Erst versenden!</small>
                                        @elseif($rechnung->naechste_mahnstufe)
                                            <span class="badge {{ $rechnung->naechste_mahnstufe->badge_class }}">
                                                <i class="bi {{ $rechnung->naechste_mahnstufe->icon }}"></i>
                                                {{ $rechnung->naechste_mahnstufe->name_de }}
                                            </span>
                                            @if($rechnung->naechste_mahnstufe->spesen > 0)
                                                <br>
                                                <small class="text-muted">
                                                    +{{ number_format($rechnung->naechste_mahnstufe->spesen, 2, ',', '.') }} € Spesen
                                                </small>
                                            @endif
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{-- ⭐ E-Mail mit Postadresse-Priorität --}}
                                        @if($rechnung->hat_email)
                                            <span class="badge bg-success" title="{{ $rechnung->email_adresse }}">
                                                <i class="bi bi-envelope-check"></i>
                                            </span>
                                            @if($rechnung->email_von_postadresse ?? false)
                                                <span class="badge bg-info text-dark" title="E-Mail von Postadresse">P</span>
                                            @endif
                                        @else
                                            <span class="badge bg-warning text-dark" title="Keine E-Mail - Postversand nötig">
                                                <i class="bi bi-mailbox"></i> Post
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                                @if($rechnung->letzte_mahnung)
                                    <tr class="table-light">
                                        <td></td>
                                        <td colspan="7">
                                            <small class="text-muted">
                                                <i class="bi bi-info-circle"></i>
                                                Letzte Mahnung: {{ $rechnung->letzte_mahnung->mahndatum->format('d.m.Y') }}
                                                ({{ $rechnung->letzte_mahnung->stufe?->name_de ?? 'Stufe ' . $rechnung->letzte_mahnung->mahnstufe }})
                                                - {!! $rechnung->letzte_mahnung->status_badge !!}
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
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
});
</script>
@endpush
@endsection
