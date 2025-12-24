{{-- resources/views/bank/matching.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">

    {{-- Kopfzeile --}}
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2 mb-3">
        <h5 class="mb-0"><i class="bi bi-link-45deg"></i> Rechnungs-Matching</h5>
        <div class="btn-group">
            <form method="POST" action="{{ route('bank.auto-match') }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-success btn-sm">
                    <i class="bi bi-magic"></i> <span class="d-none d-sm-inline">Auto-Match</span>
                </button>
            </form>
            <a href="{{ route('bank.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i>
            </a>
        </div>
    </div>

    {{-- Filter --}}
    <div class="card mb-3">
        <div class="card-body py-2">
            <form method="GET" action="{{ route('bank.matching') }}" id="matchingFilterForm">
                <div class="row g-2 align-items-center">
                    <div class="col-6 col-md-auto">
                        <select name="jahr" class="form-select form-select-sm">
                            <option value="">Alle Jahre</option>
                            @for($j = now()->year; $j >= now()->year - 5; $j--)
                                <option value="{{ $j }}" {{ ($filter['jahr'] ?? now()->year) == $j ? 'selected' : '' }}>
                                    {{ $j }}
                                </option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-6 col-md-auto">
                        <select name="monate" class="form-select form-select-sm">
                            <option value="12" {{ ($filter['monate'] ?? '12') == '12' ? 'selected' : '' }}>12 Monate</option>
                            <option value="6" {{ ($filter['monate'] ?? '') == '6' ? 'selected' : '' }}>6 Monate</option>
                            <option value="24" {{ ($filter['monate'] ?? '') == '24' ? 'selected' : '' }}>24 Monate</option>
                            <option value="" {{ ($filter['monate'] ?? '12') === '' ? 'selected' : '' }}>Alle</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-auto">
                        <select name="status" class="form-select form-select-sm">
                            <option value="offen" {{ ($filter['status'] ?? 'offen') == 'offen' ? 'selected' : '' }}>Nur offene</option>
                            <option value="alle" {{ ($filter['status'] ?? '') == 'alle' ? 'selected' : '' }}>Alle (+ bezahlte)</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-auto">
                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            <i class="bi bi-funnel"></i> Filtern
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Mobile: Tab-Navigation --}}
    <ul class="nav nav-tabs d-md-none mb-3" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabBuchungen" type="button">
                <i class="bi bi-bank"></i> Buchungen
                <span class="badge bg-warning text-dark">{{ $buchungen->count() }}</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabRechnungen" type="button">
                <i class="bi bi-receipt"></i> Rechnungen
                <span class="badge bg-primary">{{ $rechnungen->count() }}</span>
            </button>
        </li>
    </ul>

    {{-- Mobile: Tab Content --}}
    <div class="tab-content d-md-none">
        {{-- Tab: Buchungen --}}
        <div class="tab-pane fade show active" id="tabBuchungen">
            @forelse($buchungen as $buchung)
                @php 
                    $matchingService = app(\App\Services\BankMatchingService::class);
                    $extracted = $matchingService->extractMatchingData($buchung);
                @endphp
                <div class="card mb-2">
                    <div class="card-body py-2 px-3">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <span class="fw-bold text-success fs-5">+{{ number_format($buchung->betrag, 2, ',', '.') }}€</span>
                            <a href="{{ route('bank.show', $buchung->id) }}" class="btn btn-sm btn-primary">
                                <i class="bi bi-link"></i>
                            </a>
                        </div>
                        <div class="small text-muted">
                            <i class="bi bi-calendar"></i> {{ $buchung->buchungsdatum->format('d.m.Y') }}
                        </div>
                        <div class="small fw-medium text-truncate">
                            {{ Str::limit($buchung->gegenkonto_name, 35) }}
                        </div>
                        <div class="small text-muted text-truncate">
                            {{ Str::limit($buchung->verwendungszweck, 60) }}
                        </div>
                        @if(!empty($extracted['nummern']))
                            <div class="mt-1">
                                @foreach(array_slice($extracted['nummern'], 0, 3) as $num)
                                    <span class="badge bg-info">{{ $num }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="alert alert-success">
                    <i class="bi bi-check-circle"></i> Alle zugeordnet!
                </div>
            @endforelse
        </div>

        {{-- Tab: Rechnungen --}}
        <div class="tab-pane fade" id="tabRechnungen">
            @forelse($rechnungen as $rechnung)
                @php $isPaid = $rechnung->status === 'paid'; @endphp
                <div class="card mb-2 {{ $isPaid ? 'bg-light' : '' }}">
                    <div class="card-body py-2 px-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="fw-bold">
                                    {{ $rechnung->rechnungsnummer }}
                                    @if($isPaid)
                                        <span class="badge bg-secondary">Bezahlt</span>
                                    @endif
                                </div>
                                <div class="small text-muted">{{ $rechnung->rechnungsdatum?->format('d.m.Y') }}</div>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold">{{ number_format($rechnung->erwarteter_zahlbetrag, 2, ',', '.') }}€</div>
                                @if(!$isPaid)
                                    <span class="badge bg-{{ $rechnung->status === 'sent' ? 'primary' : 'warning' }}">
                                        {{ ucfirst($rechnung->status) }}
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="small mt-1 text-truncate">
                            <i class="bi bi-person"></i>
                            {{ $rechnung->re_name ?: ($rechnung->rechnungsempfaenger?->name ?? '–') }}
                        </div>
                        @if($rechnung->geb_name || $rechnung->geb_codex)
                            <div class="small text-muted text-truncate">
                                <i class="bi bi-building"></i>
                                {{ $rechnung->geb_name ?? '' }}
                                @if($rechnung->geb_codex) <code>{{ $rechnung->geb_codex }}</code> @endif
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="alert alert-info">Keine Rechnungen.</div>
            @endforelse
        </div>
    </div>

    {{-- Desktop: Zwei Spalten --}}
    <div class="row d-none d-md-flex">
        {{-- Nicht zugeordnete Buchungen --}}
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-warning text-dark py-2">
                    <h6 class="mb-0">
                        <i class="bi bi-exclamation-circle"></i> 
                        Offene Eingänge 
                        <span class="badge bg-dark">{{ $buchungen->count() }}</span>
                    </h6>
                </div>
                <div class="list-group list-group-flush" style="max-height: 70vh; overflow-y: auto;">
                    @forelse($buchungen as $buchung)
                        @php 
                            $matchingService = app(\App\Services\BankMatchingService::class);
                            $extracted = $matchingService->extractMatchingData($buchung);
                        @endphp
                        <div class="list-group-item py-2">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="fw-bold text-success">+{{ number_format($buchung->betrag, 2, ',', '.') }}€</div>
                                    <small class="text-muted">{{ $buchung->buchungsdatum->format('d.m.Y') }}</small>
                                </div>
                                <a href="{{ route('bank.show', $buchung->id) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-link"></i>
                                </a>
                            </div>
                            <div class="small mt-1 fw-medium text-truncate">
                                {{ Str::limit($buchung->gegenkonto_name, 40) }}
                            </div>
                            <div class="small text-muted text-truncate">
                                {{ Str::limit($buchung->verwendungszweck, 60) }}
                            </div>
                            @if(!empty($extracted['nummern']))
                                <div class="mt-1">
                                    @foreach(array_slice($extracted['nummern'], 0, 3) as $num)
                                        <span class="badge bg-info">{{ $num }}</span>
                                    @endforeach
                                </div>
                            @endif
                            @if(!empty($extracted['tokens']))
                                <div class="mt-1">
                                    @foreach(array_slice($extracted['tokens'], 0, 3) as $token)
                                        <span class="badge bg-secondary" style="font-size: 0.65rem;">{{ $token }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="list-group-item text-muted text-center py-4">
                            <i class="bi bi-check-circle fs-1 text-success"></i>
                            <p class="mb-0 mt-2">Alle zugeordnet!</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Rechnungen --}}
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white py-2">
                    <h6 class="mb-0">
                        <i class="bi bi-receipt"></i> 
                        Rechnungen 
                        <span class="badge bg-light text-dark">{{ $rechnungen->count() }}</span>
                    </h6>
                </div>
                <div class="list-group list-group-flush" style="max-height: 70vh; overflow-y: auto;">
                    @forelse($rechnungen as $rechnung)
                        @php $isPaid = $rechnung->status === 'paid'; @endphp
                        <div class="list-group-item py-2 {{ $isPaid ? 'bg-light' : '' }}">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="fw-bold">
                                        {{ $rechnung->rechnungsnummer }}
                                        @if($isPaid)
                                            <span class="badge bg-secondary">Bezahlt</span>
                                        @endif
                                    </div>
                                    <small class="text-muted">{{ $rechnung->rechnungsdatum?->format('d.m.Y') }}</small>
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold">{{ number_format($rechnung->erwarteter_zahlbetrag, 2, ',', '.') }}€</div>
                                    @if(!$isPaid)
                                        <span class="badge bg-{{ $rechnung->status === 'sent' ? 'primary' : 'warning text-dark' }}">
                                            {{ ucfirst($rechnung->status) }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="small mt-1">
                                <i class="bi bi-person"></i>
                                {{ $rechnung->re_name ?: ($rechnung->rechnungsempfaenger?->name ?? '–') }}
                            </div>
                            @if($rechnung->geb_name || $rechnung->geb_codex)
                                <div class="small text-muted">
                                    <i class="bi bi-building"></i>
                                    {{ $rechnung->geb_name ?? '' }}
                                    @if($rechnung->geb_codex) <code>{{ $rechnung->geb_codex }}</code> @endif
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="list-group-item text-muted text-center py-4">
                            <i class="bi bi-inbox fs-1"></i>
                            <p class="mb-0 mt-2">Keine Rechnungen</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

</div>

@endsection
