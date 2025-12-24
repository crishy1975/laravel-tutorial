{{-- resources/views/rechnung/index.blade.php --}}
{{-- Smartphone-optimiert: Cards auf Mobile, Tabelle auf Desktop --}}
@extends('layouts.app')

@section('content')
@php
    $year = now()->year;
    $defaultDatumVon = $datumVon ?? \Illuminate\Support\Carbon::create($year, 1, 1)->format('Y-m-d');
    $defaultDatumBis = $datumBis ?? \Illuminate\Support\Carbon::create($year, 12, 31)->format('Y-m-d');
@endphp

<div class="container-fluid py-3">

    {{-- Kopfzeile --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0"><i class="bi bi-receipt"></i> Rechnungen</h4>
    </div>

    {{-- Filterbereich - Mobile optimiert --}}
    <div class="card mb-3">
        <div class="card-body py-2">
            {{-- Erste Zeile: Nummer, Codex --}}
            <div class="row g-2">
                <div class="col-6 col-md-3">
                    <div class="form-floating">
                        <input type="text"
                            class="form-control form-control-sm"
                            id="filter-nummer"
                            name="nummer"
                            value="{{ $nummer ?? '' }}"
                            placeholder="Nr.">
                        <label for="filter-nummer">Nummer</label>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="form-floating">
                        <input type="text"
                            class="form-control form-control-sm"
                            id="filter-codex"
                            name="codex"
                            value="{{ $codex ?? '' }}"
                            placeholder="Codex">
                        <label for="filter-codex">Codex</label>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="form-floating">
                        <input type="text"
                            class="form-control form-control-sm"
                            id="filter-suche"
                            name="suche"
                            value="{{ $suche ?? '' }}"
                            placeholder="Suche">
                        <label for="filter-suche">Suche (Gebäude, Empfänger)</label>
                    </div>
                </div>
            </div>

            {{-- Zweite Zeile: Datum von/bis, Filter-Button --}}
            <div class="row g-2 mt-1">
                <div class="col-5 col-md-3">
                    <div class="form-floating">
                        <input type="date"
                            class="form-control form-control-sm"
                            id="filter-datum-von"
                            name="datum_von"
                            value="{{ $defaultDatumVon }}">
                        <label for="filter-datum-von">Von</label>
                    </div>
                </div>
                <div class="col-5 col-md-3">
                    <div class="form-floating">
                        <input type="date"
                            class="form-control form-control-sm"
                            id="filter-datum-bis"
                            name="datum_bis"
                            value="{{ $defaultDatumBis }}">
                        <label for="filter-datum-bis">Bis</label>
                    </div>
                </div>
                <div class="col-2 col-md-6 d-flex align-items-center justify-content-end">
                    <button type="button"
                        class="btn btn-primary"
                        id="btnFilterRechnungen"
                        title="Filtern">
                        <i class="bi bi-funnel"></i>
                        <span class="d-none d-md-inline ms-1">Filtern</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Ergebnisse --}}
    @if($rechnungen->isEmpty())
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> Keine Rechnungen gefunden.
        </div>
    @else

        {{-- ══════════════════════════════════════════════════════════════ --}}
        {{-- DESKTOP: Tabelle (ab md sichtbar) --}}
        {{-- ══════════════════════════════════════════════════════════════ --}}
        <div class="card d-none d-md-block">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 table-sm">
                    <thead class="table-dark">
                        <tr>
                            <th>Nummer ↓</th>
                            <th>Typ</th>
                            <th>Datum</th>
                            <th>Codex</th>
                            <th>Gebäude</th>
                            <th class="text-end">Zahlbar</th>
                            <th>Status</th>
                            <th class="text-center" style="width: 100px;">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rechnungen as $rechnung)
                        <tr>
                            <td>
                                <span class="fw-semibold font-monospace">
                                    {{ $rechnung->rechnungsnummer }}
                                </span>
                            </td>
                            <td>
                                @if($rechnung->typ_rechnung === 'gutschrift')
                                    <span class="badge bg-danger">GS</span>
                                @else
                                    <span class="badge bg-primary">RE</span>
                                @endif
                            </td>
                            <td>{{ optional($rechnung->rechnungsdatum)->format('d.m.Y') }}</td>
                            <td>
                                <code class="text-muted">{{ $rechnung->geb_codex ?? $rechnung->gebaeude?->codex ?? '–' }}</code>
                            </td>
                            <td>
                                @php
                                    $gebName = $rechnung->geb_name ?? $rechnung->gebaeude?->gebaeude_name ?? '–';
                                @endphp
                                @if($rechnung->gebaeude_id && $gebName !== '–')
                                    <a href="{{ route('gebaeude.edit', $rechnung->gebaeude_id) }}"
                                       class="text-decoration-none">
                                        {{ Str::limit($gebName, 30) }}
                                    </a>
                                @else
                                    {{ Str::limit($gebName, 30) }}
                                @endif
                            </td>
                            <td class="text-end">
                                @php
                                    $zahlbar = $rechnung->zahlbetrag ?? $rechnung->brutto_summe ?? 0;
                                    if ($rechnung->typ_rechnung === 'gutschrift') {
                                        $zahlbar = -abs($zahlbar);
                                    }
                                @endphp
                                <span class="fw-semibold {{ $zahlbar < 0 ? 'text-danger' : '' }}">
                                    {{ number_format($zahlbar, 2, ',', '.') }} €
                                </span>
                            </td>
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
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('rechnung.edit', $rechnung->id) }}"
                                       class="btn btn-outline-primary"
                                       title="Details anzeigen">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @if($rechnung->hat_xml)
                                    <a href="{{ route('rechnung.xml.download', $rechnung->id) }}"
                                       class="btn btn-outline-secondary"
                                       title="XML herunterladen">
                                        <i class="bi bi-file-earmark-code"></i>
                                    </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination Desktop --}}
            <div class="card-footer d-flex justify-content-between align-items-center py-2">
                <small class="text-muted">
                    {{ $rechnungen->total() }} Rechnungen
                </small>
                <div>
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

        {{-- ══════════════════════════════════════════════════════════════ --}}
        {{-- MOBILE: Card-Layout (nur auf sm sichtbar) --}}
        {{-- ══════════════════════════════════════════════════════════════ --}}
        <div class="d-md-none">
            {{-- Anzahl --}}
            <div class="d-flex justify-content-between align-items-center mb-2">
                <small class="text-muted">{{ $rechnungen->total() }} Rechnungen</small>
                <small class="text-muted">Sortiert nach Nr. ↓</small>
            </div>

            {{-- Cards --}}
            @foreach($rechnungen as $rechnung)
                @php
                    $gebName = $rechnung->geb_name ?? $rechnung->gebaeude?->gebaeude_name ?? '–';
                    $codex = $rechnung->geb_codex ?? $rechnung->gebaeude?->codex ?? '–';
                    $zahlbar = $rechnung->zahlbetrag ?? $rechnung->brutto_summe ?? 0;
                    if ($rechnung->typ_rechnung === 'gutschrift') {
                        $zahlbar = -abs($zahlbar);
                    }
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

                <div class="card mb-2 {{ $rechnung->typ_rechnung === 'gutschrift' ? 'border-danger' : '' }}">
                    <div class="card-body py-2 px-3">
                        {{-- Zeile 1: Nummer, Typ, Status, Buttons --}}
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <div class="d-flex align-items-center gap-2">
                                <span class="fw-bold font-monospace">{{ $rechnung->rechnungsnummer }}</span>
                                @if($rechnung->typ_rechnung === 'gutschrift')
                                    <span class="badge bg-danger">GS</span>
                                @else
                                    <span class="badge bg-primary">RE</span>
                                @endif
                                <span class="badge {{ $badgeClass }}">{{ ucfirst($status) }}</span>
                            </div>
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('rechnung.edit', $rechnung->id) }}"
                                   class="btn btn-outline-primary"
                                   title="Details">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @if($rechnung->hat_xml)
                                <a href="{{ route('rechnung.xml.download', $rechnung->id) }}"
                                   class="btn btn-outline-secondary"
                                   title="XML">
                                    <i class="bi bi-file-earmark-code"></i>
                                </a>
                                @endif
                            </div>
                        </div>

                        {{-- Zeile 2: Datum, Codex --}}
                        <div class="d-flex justify-content-between text-muted small mb-1">
                            <span>
                                <i class="bi bi-calendar3"></i>
                                {{ optional($rechnung->rechnungsdatum)->format('d.m.Y') }}
                            </span>
                            <code>{{ $codex }}</code>
                        </div>

                        {{-- Zeile 3: Gebäude, Betrag --}}
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-truncate me-2" style="max-width: 60%;">
                                @if($rechnung->gebaeude_id && $gebName !== '–')
                                    <a href="{{ route('gebaeude.edit', $rechnung->gebaeude_id) }}"
                                       class="text-decoration-none">
                                        {{ Str::limit($gebName, 25) }}
                                    </a>
                                @else
                                    {{ Str::limit($gebName, 25) }}
                                @endif
                            </span>
                            <span class="fw-bold {{ $zahlbar < 0 ? 'text-danger' : 'text-success' }}">
                                {{ number_format($zahlbar, 2, ',', '.') }} €
                            </span>
                        </div>
                    </div>
                </div>
            @endforeach

            {{-- Pagination Mobile --}}
            <div class="d-flex justify-content-center mt-3">
                {{ $rechnungen->appends([
                    'nummer'    => $nummer ?? null,
                    'codex'     => $codex ?? null,
                    'suche'     => $suche ?? null,
                    'datum_von' => $datumVon ?? $defaultDatumVon,
                    'datum_bis' => $datumBis ?? $defaultDatumBis,
                ])->links() }}
            </div>
        </div>

    @endif
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const baseIndexUrl = "{{ route('rechnung.index') }}";

    const nummerInput = document.getElementById('filter-nummer');
    const codexInput = document.getElementById('filter-codex');
    const sucheInput = document.getElementById('filter-suche');
    const datumVonInput = document.getElementById('filter-datum-von');
    const datumBisInput = document.getElementById('filter-datum-bis');
    const btnFilter = document.getElementById('btnFilterRechnungen');

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

        const targetUrl = params.toString()
            ? baseIndexUrl + '?' + params.toString()
            : baseIndexUrl;

        window.location.href = targetUrl;
    }

    if (btnFilter) {
        btnFilter.addEventListener('click', applyFilter);
    }

    [nummerInput, codexInput, sucheInput, datumVonInput, datumBisInput].forEach(function(input) {
        if (!input) return;
        input.addEventListener('keydown', function(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                applyFilter();
            }
        });
    });
});
</script>
@endpush
