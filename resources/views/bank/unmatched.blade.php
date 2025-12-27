{{-- resources/views/bank/unmatched.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">

    {{-- Kopfzeile --}}
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2 mb-3">
        <h5 class="mb-0">
            <i class="bi bi-exclamation-circle text-warning"></i> 
            Offene Eingänge
            <span class="badge bg-warning text-dark">{{ $buchungen->total() }}</span>
        </h5>
        <a href="{{ route('bank.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> <span class="d-none d-sm-inline">Zurück</span>
        </a>
    </div>

    @if($buchungen->isEmpty())
        <div class="alert alert-success">
            <i class="bi bi-check-circle"></i> Alle Eingänge sind zugeordnet!
        </div>
    @else
        {{-- Desktop: Tabelle --}}
        <div class="card d-none d-md-block">
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0 align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Datum</th>
                            <th>Gegenkonto</th>
                            <th>Verwendungszweck</th>
                            <th class="text-end">Betrag</th>
                            <th>Erkannt</th>
                            <th style="width: 60px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $matchingService = app(\App\Services\BankMatchingService::class); @endphp
                        @foreach($buchungen as $buchung)
                            @php $extracted = $matchingService->extractMatchingData($buchung); @endphp
                            <tr>
                                <td class="text-nowrap">{{ $buchung->buchungsdatum->format('d.m.Y') }}</td>
                                <td>
                                    <div class="text-truncate" style="max-width: 150px;">
                                        {{ $buchung->gegenkonto_name ?: '–' }}
                                    </div>
                                </td>
                                <td>
                                    <div class="text-truncate" style="max-width: 250px;" title="{{ $buchung->verwendungszweck }}">
                                        {{ Str::limit($buchung->verwendungszweck, 50) }}
                                    </div>
                                </td>
                                <td class="text-end text-nowrap fw-bold text-success">
                                    +{{ number_format($buchung->betrag, 2, ',', '.') }} €
                                </td>
                                <td>
                                    @if(!empty($extracted['nummern']))
                                        @foreach(array_slice($extracted['nummern'], 0, 2) as $num)
                                            <span class="badge bg-info">{{ $num }}</span>
                                        @endforeach
                                    @else
                                        <span class="text-muted">–</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('bank.show', $buchung->id) }}" class="btn btn-sm btn-primary">
                                        <i class="bi bi-link"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                {{ $buchungen->links() }}
            </div>
        </div>

        {{-- Mobile: Cards --}}
        <div class="d-md-none">
            @php $matchingService = app(\App\Services\BankMatchingService::class); @endphp
            @foreach($buchungen as $buchung)
                @php $extracted = $matchingService->extractMatchingData($buchung); @endphp
                <div class="card mb-2">
                    <div class="card-body py-2 px-3">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <div>
                                <span class="fw-bold text-success fs-5">+{{ number_format($buchung->betrag, 2, ',', '.') }}€</span>
                            </div>
                            <a href="{{ route('bank.show', $buchung->id) }}" class="btn btn-sm btn-primary">
                                <i class="bi bi-link"></i> Zuordnen
                            </a>
                        </div>
                        <div class="small text-muted mb-1">
                            <i class="bi bi-calendar"></i> {{ $buchung->buchungsdatum->format('d.m.Y') }}
                        </div>
                        <div class="small fw-medium mb-1 text-truncate">
                            {{ $buchung->gegenkonto_name ?: '–' }}
                        </div>
                        <div class="small text-muted text-truncate mb-1">
                            {{ Str::limit($buchung->verwendungszweck, 80) }}
                        </div>
                        @if(!empty($extracted['nummern']))
                            <div class="d-flex flex-wrap gap-1">
                                @foreach(array_slice($extracted['nummern'], 0, 3) as $num)
                                    <span class="badge bg-info">RN {{ $num }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach

            <div class="mt-3">
                {{ $buchungen->links() }}
            </div>
        </div>
    @endif

</div>
@endsection
