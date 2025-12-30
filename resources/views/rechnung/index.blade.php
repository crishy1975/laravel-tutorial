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

    {{-- ══════════════════════════════════════════════════════════════════════════ --}}
    {{-- STATISTIK-CARDS --}}
    {{-- ══════════════════════════════════════════════════════════════════════════ --}}
    @if(isset($stats))
    <div class="row g-2 g-md-3 mb-3">
        {{-- Umsatz aktuelles Jahr --}}
        <div class="col-6 col-md-3">
            <div class="card border-0 bg-primary text-white h-100">
                <div class="card-body py-2 py-md-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-white-50 small">Umsatz {{ $stats['aktuelles_jahr'] ?? now()->year }}</div>
                            <div class="fs-5 fs-md-4 fw-bold">{{ number_format($stats['umsatz_aktuell'] ?? 0, 0, ',', '.') }} €</div>
                            <div class="small">
                                <span class="text-white-50">{{ $stats['anzahl_aktuell'] ?? 0 }} Rechnungen</span>
                            </div>
                        </div>
                        <i class="bi bi-graph-up-arrow fs-3 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- Vergleich Vorjahr --}}
        <div class="col-6 col-md-3">
            <div class="card border-0 bg-secondary text-white h-100">
                <div class="card-body py-2 py-md-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-white-50 small">Umsatz {{ $stats['vorjahr'] ?? (now()->year - 1) }}</div>
                            <div class="fs-5 fs-md-4 fw-bold">{{ number_format($stats['umsatz_vorjahr'] ?? 0, 0, ',', '.') }} €</div>
                            <div class="small">
                                @php
                                    $diff = ($stats['umsatz_aktuell'] ?? 0) - ($stats['umsatz_vorjahr'] ?? 0);
                                    $prozent = ($stats['umsatz_vorjahr'] ?? 0) > 0 
                                        ? (($diff / ($stats['umsatz_vorjahr'] ?? 1)) * 100) 
                                        : 0;
                                @endphp
                                @if($diff >= 0)
                                    <span class="text-success"><i class="bi bi-arrow-up"></i> +{{ number_format($prozent, 1, ',', '.') }}%</span>
                                @else
                                    <span class="text-danger"><i class="bi bi-arrow-down"></i> {{ number_format($prozent, 1, ',', '.') }}%</span>
                                @endif
                            </div>
                        </div>
                        <i class="bi bi-calendar-check fs-3 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- Offene Rechnungen --}}
        <div class="col-6 col-md-3">
            <div class="card border-0 {{ ($stats['unbezahlt_anzahl'] ?? 0) > 0 ? 'bg-warning' : 'bg-success' }} h-100">
                <div class="card-body py-2 py-md-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-dark small opacity-75">Offen / Unbezahlt</div>
                            <div class="fs-5 fs-md-4 fw-bold text-dark">{{ number_format($stats['unbezahlt_summe'] ?? 0, 0, ',', '.') }} €</div>
                            <div class="small text-dark">
                                <a href="{{ route('rechnung.index', ['status_filter' => 'unbezahlt']) }}" class="text-dark">
                                    {{ $stats['unbezahlt_anzahl'] ?? 0 }} Rechnungen <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                        <i class="bi bi-clock-history fs-3 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- Überfällige Rechnungen --}}
        <div class="col-6 col-md-3">
            <div class="card border-0 {{ ($stats['ueberfaellig_anzahl'] ?? 0) > 0 ? 'bg-danger text-white' : 'bg-light' }} h-100">
                <div class="card-body py-2 py-md-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="small {{ ($stats['ueberfaellig_anzahl'] ?? 0) > 0 ? 'text-white-50' : 'text-muted' }}">Überfällig</div>
                            <div class="fs-5 fs-md-4 fw-bold">{{ number_format($stats['ueberfaellig_summe'] ?? 0, 0, ',', '.') }} €</div>
                            <div class="small">
                                @if(($stats['ueberfaellig_anzahl'] ?? 0) > 0)
                                    <a href="{{ route('rechnung.index', ['status_filter' => 'ueberfaellig']) }}" class="text-white">
                                        {{ $stats['ueberfaellig_anzahl'] }} Rechnungen <i class="bi bi-exclamation-triangle"></i>
                                    </a>
                                @else
                                    <span class="text-success"><i class="bi bi-check-circle"></i> Keine</span>
                                @endif
                            </div>
                        </div>
                        <i class="bi bi-exclamation-diamond fs-3 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Jahresvergleich-Tabelle (nur Desktop) --}}
    <div class="card mb-3 d-none d-lg-block">
        <div class="card-header py-2 bg-light">
            <i class="bi bi-bar-chart"></i> <strong>Jahresvergleich</strong>
        </div>
        <div class="card-body p-0">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Jahr</th>
                        <th class="text-center">Rechnungen</th>
                        <th class="text-end">Netto</th>
                        <th class="text-end">Brutto</th>
                        <th class="text-end">Bezahlt</th>
                        <th class="text-end">Offen</th>
                        <th class="text-center">Veränderung</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach([$stats['aktuelles_jahr'], $stats['vorjahr'], $stats['vorvorjahr']] as $i => $statJahr)
                    @php
                        $jahresStats = $stats['jahresvergleich'][$statJahr] ?? [];
                        $vorjahresStats = $stats['jahresvergleich'][$statJahr - 1] ?? [];
                        $veraenderung = ($vorjahresStats['brutto'] ?? 0) > 0 
                            ? ((($jahresStats['brutto'] ?? 0) - ($vorjahresStats['brutto'] ?? 0)) / ($vorjahresStats['brutto'] ?? 1)) * 100
                            : 0;
                    @endphp
                    <tr class="{{ $i === 0 ? 'table-primary' : '' }}">
                        <td class="fw-bold">{{ $statJahr }}</td>
                        <td class="text-center">{{ $jahresStats['anzahl'] ?? 0 }}</td>
                        <td class="text-end">{{ number_format($jahresStats['netto'] ?? 0, 2, ',', '.') }} €</td>
                        <td class="text-end fw-semibold">{{ number_format($jahresStats['brutto'] ?? 0, 2, ',', '.') }} €</td>
                        <td class="text-end text-success">{{ number_format($jahresStats['bezahlt'] ?? 0, 2, ',', '.') }} €</td>
                        <td class="text-end {{ ($jahresStats['offen'] ?? 0) > 0 ? 'text-warning' : '' }}">
                            {{ number_format($jahresStats['offen'] ?? 0, 2, ',', '.') }} €
                        </td>
                        <td class="text-center">
                            @if($i > 0 || $veraenderung != 0)
                                @if($veraenderung >= 0)
                                    <span class="badge bg-success"><i class="bi bi-arrow-up"></i> +{{ number_format($veraenderung, 1) }}%</span>
                                @else
                                    <span class="badge bg-danger"><i class="bi bi-arrow-down"></i> {{ number_format($veraenderung, 1) }}%</span>
                                @endif
                            @else
                                <span class="text-muted">–</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Filterbereich - Mobile optimiert --}}
    <div class="card mb-3">
        <div class="card-body py-2">
            {{-- Erste Zeile: Nummer, Codex --}}
            <div class="row g-2">
                <div class="col-6 col-md-2">
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
                <div class="col-6 col-md-2">
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
                <div class="col-12 col-md-4">
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
                <div class="col-12 col-md-4">
                    <div class="form-floating">
                        <select class="form-select form-select-sm" id="filter-status" name="status_filter">
                            <option value="">Alle Status</option>
                            <option value="unbezahlt" {{ ($statusFilter ?? '') === 'unbezahlt' ? 'selected' : '' }}>Unbezahlt</option>
                            <option value="bezahlt" {{ ($statusFilter ?? '') === 'bezahlt' ? 'selected' : '' }}>Bezahlt</option>
                            <option value="ueberfaellig" {{ ($statusFilter ?? '') === 'ueberfaellig' ? 'selected' : '' }}>Überfällig</option>
                            <option value="bald_faellig" {{ ($statusFilter ?? '') === 'bald_faellig' ? 'selected' : '' }}>Bald fällig (7 Tage)</option>
                            <option value="offen" {{ ($statusFilter ?? '') === 'offen' ? 'selected' : '' }}>Offen (versendet)</option>
                        </select>
                        <label for="filter-status">Status</label>
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
                <div class="col-2 col-md-6 d-flex align-items-center justify-content-end gap-2">
                    @if($statusFilter ?? false)
                    <a href="{{ route('rechnung.index') }}" class="btn btn-outline-secondary" title="Filter zurücksetzen">
                        <i class="bi bi-x-lg"></i>
                    </a>
                    @endif
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
                                <span class="badge {{ $badgeClass }}">{{ ucfirst($status) }}</span>
                                @if($rechnung->istUeberfaellig())
                                    <span class="badge bg-danger"><i class="bi bi-exclamation-triangle"></i></span>
                                @endif
                            </td>
                            <td class="text-center">
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
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer d-flex justify-content-between align-items-center py-2">
                <small class="text-muted">
                    {{ $rechnungen->total() }} Rechnungen
                </small>
                <div>
                    {{ $rechnungen->appends([
                        'nummer'        => $nummer ?? null,
                        'codex'         => $codex ?? null,
                        'suche'         => $suche ?? null,
                        'datum_von'     => $datumVon ?? $defaultDatumVon,
                        'datum_bis'     => $datumBis ?? $defaultDatumBis,
                        'status_filter' => $statusFilter ?? null,
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
                    $rCodex = $rechnung->geb_codex ?? $rechnung->gebaeude?->codex ?? '–';
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

                <div class="card mb-2 {{ $rechnung->typ_rechnung === 'gutschrift' ? 'border-danger' : '' }} {{ $rechnung->istUeberfaellig() ? 'border-warning border-2' : '' }}">
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
                                @if($rechnung->istUeberfaellig())
                                    <span class="badge bg-danger"><i class="bi bi-exclamation-triangle"></i></span>
                                @endif
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
                            <code>{{ $rCodex }}</code>
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
                    'nummer'        => $nummer ?? null,
                    'codex'         => $codex ?? null,
                    'suche'         => $suche ?? null,
                    'datum_von'     => $datumVon ?? $defaultDatumVon,
                    'datum_bis'     => $datumBis ?? $defaultDatumBis,
                    'status_filter' => $statusFilter ?? null,
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
    const statusInput = document.getElementById('filter-status');
    const btnFilter = document.getElementById('btnFilterRechnungen');

    function applyFilter() {
        const nummer = nummerInput?.value ?? '';
        const codex = codexInput?.value ?? '';
        const suche = sucheInput?.value ?? '';
        const datumVon = datumVonInput?.value ?? '';
        const datumBis = datumBisInput?.value ?? '';
        const status = statusInput?.value ?? '';

        const params = new URLSearchParams();

        if (nummer) params.append('nummer', nummer);
        if (codex) params.append('codex', codex);
        if (suche) params.append('suche', suche);
        if (datumVon) params.append('datum_von', datumVon);
        if (datumBis) params.append('datum_bis', datumBis);
        if (status) params.append('status_filter', status);

        const targetUrl = params.toString()
            ? baseIndexUrl + '?' + params.toString()
            : baseIndexUrl;

        window.location.href = targetUrl;
    }

    if (btnFilter) {
        btnFilter.addEventListener('click', applyFilter);
    }

    // Status-Select: Sofort filtern bei Änderung
    if (statusInput) {
        statusInput.addEventListener('change', applyFilter);
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
