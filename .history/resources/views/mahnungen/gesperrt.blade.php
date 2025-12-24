{{-- resources/views/mahnungen/gesperrt.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container py-3">
    
    {{-- Kopfzeile --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0">
                <i class="bi bi-shield-x text-warning"></i> 
                Vom Mahnwesen ausgeschlossene Rechnungen
            </h4>
            <small class="text-muted">Rechnungen mit aktiver Mahnsperre</small>
        </div>
        <div class="btn-group">
            <a href="{{ route('mahnungen.mahnlauf') }}" class="btn btn-outline-primary">
                <i class="bi bi-play-circle"></i> Mahnlauf
            </a>
            <a href="{{ route('mahnungen.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Übersicht
            </a>
        </div>
    </div>

    @if($gesperrte->isEmpty())
        <div class="alert alert-success">
            <i class="bi bi-check-circle"></i>
            <strong>Keine gesperrten Rechnungen.</strong>
            Alle offenen Rechnungen können gemahnt werden.
        </div>
    @else
        {{-- Statistik --}}
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-warning text-dark">
                    <div class="card-body text-center py-2">
                        <h3 class="mb-0">{{ $gesperrte->count() }}</h3>
                        <small>Gesperrte Rechnungen</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-light">
                    <div class="card-body text-center py-2">
                        <h3 class="mb-0">{{ $gesperrte->whereNull('bis_datum')->count() }}</h3>
                        <small>Permanent</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-light">
                    <div class="card-body text-center py-2">
                        <h3 class="mb-0">{{ $gesperrte->whereNotNull('bis_datum')->count() }}</h3>
                        <small>Temporär</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-light">
                    <div class="card-body text-center py-2">
                        <h3 class="mb-0">{{ number_format($gesperrte->sum(fn($a) => (float) ($a->rechnung?->brutto_summe ?? 0)), 2, ',', '.') }} €</h3>
                        <small>Gesperrter Betrag</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-warning text-dark">
                <i class="bi bi-list"></i> Gesperrte Rechnungen
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Rechnung</th>
                            <th>Kunde</th>
                            <th>Betrag</th>
                            <th>Sperre</th>
                            <th>Grund</th>
                            <th>Gesperrt am</th>
                            <th style="width: 100px;">Aktion</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($gesperrte as $ausschluss)
                            @php $rechnung = $ausschluss->rechnung; @endphp
                            <tr>
                                <td>
                                    @if($rechnung)
                                        <a href="{{ url('/rechnung/' . $rechnung->id . '/edit') }}" target="_blank">
                                            {{ $rechnung->volle_rechnungsnummer ?? ($rechnung->jahr && $rechnung->laufnummer ? $rechnung->jahr.'/'.$rechnung->laufnummer : $rechnung->laufnummer ?? '-') }}
                                        </a>
                                    @else
                                        <span class="text-muted">Gelöscht</span>
                                    @endif
                                </td>
                                <td>{{ Str::limit($rechnung?->rechnungsempfaenger?->name, 30) }}</td>
                                <td class="text-end">{{ number_format($rechnung?->brutto_summe ?? 0, 2, ',', '.') }} €</td>
                                <td>
                                    {!! $ausschluss->gueltigkeitBadge !!}
                                    @if($ausschluss->bis_datum)
                                        @php
                                            $tageBis = now()->diffInDays($ausschluss->bis_datum, false);
                                        @endphp
                                        @if($tageBis > 0)
                                            <br><small class="text-muted">noch {{ $tageBis }} Tage</small>
                                        @elseif($tageBis == 0)
                                            <br><small class="text-success">Läuft heute ab!</small>
                                        @endif
                                    @endif
                                </td>
                                <td>
                                    @if($ausschluss->grund)
                                        <small>{{ Str::limit($ausschluss->grund, 40) }}</small>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    {{ $ausschluss->created_at?->format('d.m.Y') }}
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('mahnungen.mahnsperre.entfernen', $ausschluss->rechnung_id) }}" 
                                          onsubmit="return confirm('Mahnsperre wirklich aufheben?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-success" title="Sperre aufheben">
                                            <i class="bi bi-shield-check"></i> Freigeben
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

</div>
@endsection
