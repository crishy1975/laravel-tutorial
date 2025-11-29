{{-- resources/views/rechnung/index.blade.php --}}
@extends('layouts.app')

@section('content')
@php
// Standardwerte für Datum von/bis:
// - wenn vom Controller $datumVon / $datumBis gesetzt wurden, diese verwenden
// - sonst: 01.01.dieses Jahres bzw. 31.12.dieses Jahres
$year = now()->year;
$defaultDatumVon = $datumVon ?? \Illuminate\Support\Carbon::create($year, 1, 1)->format('Y-m-d');
$defaultDatumBis = $datumBis ?? \Illuminate\Support\Carbon::create($year, 12, 31)->format('Y-m-d');
@endphp

<div class="container py-4">

    {{-- Kopfzeile --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3><i class="bi bi-receipt"></i> Rechnungen</h3>
        <div class="d-flex gap-2">
            <a href="{{ route('rechnung.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Neue Rechnung
            </a>
        </div>
    </div>

    {{-- Filterbereich:
         - Rechnungsnummer
         - Codex
         - Suche (Gebäudename ODER Rechnungsempfänger ODER Postadresse)
         - Datum von / bis (Standard: 01.01.dieses Jahres bis 31.12.dieses Jahres)
         KEIN <form>, nur JS-Redirect mit Query-Parametern.
    --}}
    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">

                {{-- Rechnungsnummer (Format: 2025/0001 usw.) --}}
                <div class="col-md-3">
                    <div class="form-floating">
                        <input type="text"
                            class="form-control"
                            id="filter-nummer"
                            name="nummer"
                            value="{{ $nummer ?? '' }}"
                            placeholder="Rechnungsnummer">
                        <label for="filter-nummer">Rechnungsnummer</label>
                    </div>
                </div>

                {{-- Codex --}}
                <div class="col-md-3">
                    <div class="form-floating">
                        <input type="text"
                            class="form-control"
                            id="filter-codex"
                            name="codex"
                            value="{{ $codex ?? '' }}"
                            placeholder="Codex">
                        <label for="filter-codex">Codex</label>
                    </div>
                </div>

                {{-- Suche in Gebäudename ODER Rechnungsempfänger ODER Postadresse --}}
                <div class="col-md-6">
                    <div class="form-floating">
                        <input type="text"
                            class="form-control"
                            id="filter-suche"
                            name="suche"
                            value="{{ $suche ?? '' }}"
                            placeholder="Suche">
                        <label for="filter-suche">
                            Suche (Gebäude, Empfänger, Postadresse)
                        </label>
                    </div>
                </div>
            </div>

            <div class="row g-2 align-items-end mt-2">
                {{-- Datum von (Standard: 01.01.dieses Jahres) --}}
                <div class="col-md-3">
                    <div class="form-floating">
                        <input type="date"
                            class="form-control"
                            id="filter-datum-von"
                            name="datum_von"
                            value="{{ $defaultDatumVon }}"
                            placeholder="Datum von">
                        <label for="filter-datum-von">Datum von</label>
                    </div>
                </div>

                {{-- Datum bis (Standard: 31.12.dieses Jahres) --}}
                <div class="col-md-3">
                    <div class="form-floating">
                        <input type="date"
                            class="form-control"
                            id="filter-datum-bis"
                            name="datum_bis"
                            value="{{ $defaultDatumBis }}"
                            placeholder="Datum bis">
                        <label for="filter-datum-bis">Datum bis</label>
                    </div>
                </div>

                <div class="col-md-6 text-end">
                    {{-- Filter-Button --}}
                    <button type="button"
                        class="btn btn-outline-primary mt-2 mt-md-0"
                        id="btnFilterRechnungen"
                        title="Filter anwenden">
                        <i class="bi bi-funnel"></i> Filtern
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabellenbereich --}}
    @if($rechnungen->isEmpty())
    <div class="alert alert-info">Keine Rechnungen gefunden.</div>
    @else
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Nummer</th>
                        <th>Typ</th>
                        <th>Datum</th>
                        <th>Codex</th>
                        <th>Gebäude</th>
                        <th class="text-end">Zahlbar</th>
                        <th>Status</th>
                        <th class="text-end">Aktion</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rechnungen as $rechnung)
                    <tr data-rechnung-id="{{ $rechnung->id }}">
                        {{-- Nummer (Accessor: rechnungsnummer => "jahr/laufnummer") --}}
                        <td>
                            <span class="fw-semibold">
                                {{ $rechnung->rechnungsnummer }}
                            </span>
                        </td>

                        {{-- Typ (Rechnung oder Gutschrift) --}}
                        <td>
                            @if($rechnung->typ_rechnung === 'gutschrift')
                                <span class="badge bg-danger">Gutschrift</span>
                            @else
                                <span class="badge bg-primary">Rechnung</span>
                            @endif
                        </td>

                        {{-- Datum (Rechnungsdatum) --}}
                        <td>
                            {{ optional($rechnung->rechnungsdatum)->format('d.m.Y') }}
                        </td>

                        {{-- Codex (Snapshot oder Relation) --}}
                        <td>
                            {{ $rechnung->geb_codex ?? $rechnung->gebaeude?->codex ?? '–' }}
                        </td>

                        {{-- Gebäudename (Snapshot oder Relation) als Link zum Gebäude (Edit-Seite) --}}
                        <td>
                            @php
                            // Bevorzugt den Snapshot-Namen, sonst den aktuellen Namen aus der Relation
                            $gebName = $rechnung->geb_name ?? $rechnung->gebaeude?->gebaeude_name ?? '–';
                            @endphp

                            @if($rechnung->gebaeude_id && $gebName !== '–')
                            {{-- Link zur Bearbeitung des Gebäudes
             Route laut web.php: gebaeude.edit
             link-primary = blau, text-decoration-none = nicht unterstrichen --}}
                            <a href="{{ route('gebaeude.edit', $rechnung->gebaeude_id) }}"
                                class="link-primary text-decoration-none">
                                {{ $gebName }}
                            </a>
                            @else
                            {{ $gebName }}
                            @endif
                        </td>


                        {{-- Zahlbar / Betrag (Snapshot-Feld) - NEGATIV bei Gutschrift --}}
                        <td class="text-end">
                            @php
                                $betrag = $rechnung->zahlbar_betrag ?? 0;
                                if ($rechnung->typ_rechnung === 'gutschrift') {
                                    $betrag = -1 * abs($betrag);
                                }
                            @endphp
                            <span class="{{ $rechnung->typ_rechnung === 'gutschrift' ? 'text-danger' : '' }}">
                                {{ number_format($betrag, 2, ',', '.') }} €
                            </span>
                        </td>

                        {{-- Status --}}
                        <td>
                            @php
                            $status = $rechnung->status ?? 'draft';
                            $badgeClass = match ($status) {
                            'paid' => 'bg-success',
                            'cancelled' => 'bg-secondary',
                            'draft' => 'bg-warning text-dark',
                            'sent' => 'bg-primary',
                            'overdue' => 'bg-danger',
                            default => 'bg-light text-dark',
                            };
                            @endphp
                            <span class="badge {{ $badgeClass }}">
                                {{ ucfirst($status) }}
                            </span>
                        </td>

                        {{-- Aktionen (vollständig, ohne Form, Delete via JS/fetch) --}}
                        <td class="text-end">
                            <div class="btn-group btn-group-sm" role="group">
                                {{-- Anzeigen --}}
                                <a href="{{ route('rechnung.edit', $rechnung->id) }}"
                                    class="btn btn-outline-secondary"
                                    title="Details anzeigen">
                                    <i class="bi bi-eye"></i>
                                </a>

                                {{-- Bearbeiten --}}
                                <a href="{{ route('rechnung.edit', $rechnung->id) }}"
                                    class="btn btn-outline-primary"
                                    title="Bearbeiten">
                                    <i class="bi bi-pencil"></i>
                                </a>

                                {{-- Löschen: Button + JS, KEIN <form>, KEIN GET-Delete --}}
                                <button type="button"
                                    class="btn btn-outline-danger btn-delete-rechnung"
                                    title="Löschen"
                                    data-delete-url="{{ route('rechnung.destroy', $rechnung->id) }}">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="card-footer d-flex justify-content-between align-items-center">
            <div class="text-muted small">
                {{ $rechnungen->total() }} Rechnungen gefunden
            </div>
            <div>
                {{-- alle Filter als Query-Parameter erhalten --}}
                {{ $rechnungen->appends([
                        'nummer'    => $nummer ?? null,
                        'codex'     => $codex ?? null,
                        'suche'     => $suche ?? null,
                        'datum_von' => $datumVon ?? $defaultDatumVon,
                        'datum_bis' => $datumBis ?? $defaultDatumBis,
                    ])->links() }}
            </div>
        </div>
    </div>
    @endif

</div>
@endsection

@push('scripts')
<script>
    // Voraussetzung im Layout:
    // <meta name="csrf-token" content="{{ csrf_token() }}">

    document.addEventListener('DOMContentLoaded', function() {
        const baseIndexUrl = "{{ route('rechnung.index') }}";

        // Filter-Felder aus dem DOM holen
        const nummerInput = document.getElementById('filter-nummer');
        const codexInput = document.getElementById('filter-codex');
        const sucheInput = document.getElementById('filter-suche');
        const datumVonInput = document.getElementById('filter-datum-von');
        const datumBisInput = document.getElementById('filter-datum-bis');
        const btnFilter = document.getElementById('btnFilterRechnungen');

        // ---------------------------------
        // Filter-Logik: Nummer, Codex, Suche, Datum von/bis
        // ---------------------------------
        function applyFilter() {
            const nummer = nummerInput?.value ?? '';
            const codex = codexInput?.value ?? '';
            const suche = sucheInput?.value ?? '';
            const datumVon = datumVonInput?.value ?? '';
            const datumBis = datumBisInput?.value ?? '';

            const params = new URLSearchParams();

            if (nummer) params.append('nummer', nummer);
            if (codex) params.append('codex', codex);
            if (suche) params.append('suche', suche);
            if (datumVon) params.append('datum_von', datumVon);
            if (datumBis) params.append('datum_bis', datumBis);

            const targetUrl = params.toString() ?
                baseIndexUrl + '?' + params.toString() :
                baseIndexUrl;

            // klassischer GET-Redirect mit Query-Parametern
            window.location.href = targetUrl;
        }

        // Klick auf Filter-Button
        if (btnFilter) {
            btnFilter.addEventListener('click', function() {
                applyFilter();
            });
        }

        // Enter in einem der Felder soll auch filtern
        [nummerInput, codexInput, sucheInput, datumVonInput, datumBisInput].forEach(function(input) {
            if (!input) return;
            input.addEventListener('keydown', function(event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    applyFilter();
                }
            });
        });

        // ---------------------------------
        // Löschen-Logik via fetch(), ohne <form>
        // ---------------------------------
        const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = csrfTokenMeta ? csrfTokenMeta.getAttribute('content') : null;

        document.querySelectorAll('.btn-delete-rechnung').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const url = this.getAttribute('data-delete-url');
                const row = this.closest('tr');

                if (!url) {
                    console.error('Keine data-delete-url am Button gesetzt.');
                    return;
                }

                if (!csrfToken) {
                    alert('CSRF-Token nicht gefunden. Löschen nicht möglich.');
                    return;
                }

                if (!confirm('Diese Rechnung wirklich löschen?')) {
                    return;
                }

                // POST + _method=DELETE für Laravel
                fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        _method: 'DELETE'
                    })
                }).then(function(response) {
                    if (response.ok) {
                        if (row) {
                            row.remove();
                        }
                    } else {
                        alert('Fehler beim Löschen der Rechnung.');
                    }
                }).catch(function(error) {
                    console.error(error);
                    alert('Netzwerkfehler beim Löschen der Rechnung.');
                });
            });
        });
    });
</script>
@endpush