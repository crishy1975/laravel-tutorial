@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-0">
                    <i class="bi bi-pencil"></i> Arbeitsbericht bearbeiten / Modifica rapporto
                </h4>
                <a href="{{ route('arbeitsbericht.show', $arbeitsbericht) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Zurück
                </a>
            </div>

            <form action="{{ route('arbeitsbericht.update', $arbeitsbericht) }}" method="POST">
                @csrf
                @method('PUT')

                <!-- Gebäude Info (readonly) -->
                <div class="card mb-4">
                    <div class="card-header bg-secondary text-white">
                        <i class="bi bi-building"></i> Kunde / Cliente
                    </div>
                    <div class="card-body">
                        <strong class="fs-5">{{ $arbeitsbericht->adresse_name }}</strong>
                        <div class="text-muted mt-2">
                            {{ $arbeitsbericht->adresse_strasse }} {{ $arbeitsbericht->adresse_hausnummer }}<br>
                            {{ $arbeitsbericht->adresse_plz }} {{ $arbeitsbericht->adresse_wohnort }}
                        </div>
                    </div>
                </div>

                <!-- Datum -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="bi bi-calendar"></i> Datum / Data
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <label for="arbeitsdatum" class="form-label small text-muted">
                                    Arbeitsdatum / Data lavoro *
                                </label>
                                <input type="date" 
                                       class="form-control form-control-lg @error('arbeitsdatum') is-invalid @enderror" 
                                       id="arbeitsdatum" 
                                       name="arbeitsdatum" 
                                       value="{{ old('arbeitsdatum', $arbeitsbericht->arbeitsdatum->format('Y-m-d')) }}"
                                       required>
                                @error('arbeitsdatum')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="naechste_faelligkeit" class="form-label small text-muted">
                                    Nächster Termin / Prossimo appuntamento
                                </label>
                                <input type="date" 
                                       class="form-control form-control-lg" 
                                       id="naechste_faelligkeit" 
                                       name="naechste_faelligkeit" 
                                       value="{{ old('naechste_faelligkeit', $arbeitsbericht->naechste_faelligkeit?->format('Y-m-d')) }}">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Positionen (readonly) -->
                @if(!empty($arbeitsbericht->positionen))
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="bi bi-list-check"></i> Positionen / Lavori
                        <span class="badge bg-secondary">{{ count($arbeitsbericht->positionen) }}</span>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Beschreibung / Descrizione</th>
                                    <th class="text-center">Menge / Qtà</th>
                                    <th class="text-end">Gesamt / Totale</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $summe = 0; @endphp
                                @foreach($arbeitsbericht->positionen as $position)
                                @php 
                                    $gesamt = (float) ($position['gesamtpreis'] ?? 0);
                                    $summe += $gesamt;
                                @endphp
                                <tr>
                                    <td>{{ $position['bezeichnung'] ?? '-' }}</td>
                                    <td class="text-center">{{ $position['anzahl'] ?? 1 }}</td>
                                    <td class="text-end">{{ number_format($gesamt, 2, ',', '.') }} €</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="2">Summe / Totale</th>
                                    <th class="text-end">{{ number_format($summe, 2, ',', '.') }} €</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                @endif

                <!-- Bemerkung -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="bi bi-chat-text"></i> Bemerkung / Note
                    </div>
                    <div class="card-body">
                        <textarea class="form-control" 
                                  id="bemerkung" 
                                  name="bemerkung" 
                                  rows="3"
                                  placeholder="Optionale Bemerkungen / Note opzionali...">{{ old('bemerkung', $arbeitsbericht->bemerkung) }}</textarea>
                    </div>
                </div>

                <!-- Unterschriften (readonly) -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card border-success">
                            <div class="card-header bg-success text-white">
                                <i class="bi bi-pen"></i> Unterschrift Kunde / Firma cliente
                            </div>
                            <div class="card-body text-center">
                                @if($arbeitsbericht->unterschrift_kunde)
                                <img src="{{ $arbeitsbericht->unterschrift_kunde }}" 
                                     alt="Unterschrift Kunde" 
                                     style="max-height: 60px;">
                                @endif
                                <div class="mt-2">
                                    <strong>{{ $arbeitsbericht->unterschrift_name }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-primary">
                            <div class="card-header bg-primary text-white">
                                <i class="bi bi-pen"></i> Unterschrift Mitarbeiter / Firma operatore
                            </div>
                            <div class="card-body text-center">
                                @if($arbeitsbericht->unterschrift_mitarbeiter)
                                <img src="{{ $arbeitsbericht->unterschrift_mitarbeiter }}" 
                                     alt="Unterschrift Mitarbeiter" 
                                     style="max-height: 60px;">
                                @endif
                                <div class="mt-2">
                                    <strong>{{ $arbeitsbericht->mitarbeiter_name }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-lg flex-grow-1">
                        <i class="bi bi-check-lg"></i> Speichern / Salva
                    </button>
                    <a href="{{ route('arbeitsbericht.show', $arbeitsbericht) }}" class="btn btn-outline-secondary btn-lg">
                        Abbrechen / Annulla
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
