{{-- resources/views/mahnungen/ausschluesse.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container py-3">
    
    {{-- Kopfzeile --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0"><i class="bi bi-shield-x"></i> Mahnungs-Ausschlüsse</h4>
            <small class="text-muted">Kunden und Rechnungen vom Mahnlauf ausschließen</small>
        </div>
        <a href="{{ route('mahnungen.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Zurück
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        {{-- Kunden-Ausschlüsse --}}
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="bi bi-person-x"></i> Kunden-Ausschlüsse</h6>
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalKundeAusschliessen">
                        <i class="bi bi-plus"></i> Hinzufügen
                    </button>
                </div>
                <div class="card-body p-0">
                    @if($kundenAusschluesse->isEmpty())
                        <div class="text-center text-muted py-4">
                            Keine Kunden ausgeschlossen.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Kunde</th>
                                        <th>Grund</th>
                                        <th>Gültigkeit</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($kundenAusschluesse as $ausschluss)
                                        <tr class="{{ !$ausschluss->ist_gueltig ? 'table-secondary' : '' }}">
                                            <td>
                                                <strong>{{ $ausschluss->adresse?->name }}</strong>
                                                <br>
                                                <small class="text-muted">{{ $ausschluss->adresse?->wohnort }}</small>
                                            </td>
                                            <td>{{ $ausschluss->grund ?? '-' }}</td>
                                            <td>{!! $ausschluss->gueltigkeit_badge !!}</td>
                                            <td>
                                                <form method="POST" 
                                                      action="{{ route('mahnungen.kunde.ausschluss.entfernen', $ausschluss->adresse_id) }}"
                                                      class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                                            onclick="return confirm('Ausschluss wirklich entfernen?')">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Rechnungs-Ausschlüsse --}}
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="bi bi-file-earmark-x"></i> Rechnungs-Ausschlüsse</h6>
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalRechnungAusschliessen">
                        <i class="bi bi-plus"></i> Hinzufügen
                    </button>
                </div>
                <div class="card-body p-0">
                    @if($rechnungAusschluesse->isEmpty())
                        <div class="text-center text-muted py-4">
                            Keine Rechnungen ausgeschlossen.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Rechnung</th>
                                        <th>Kunde</th>
                                        <th>Grund</th>
                                        <th>Gültigkeit</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($rechnungAusschluesse as $ausschluss)
                                        <tr class="{{ !$ausschluss->ist_gueltig ? 'table-secondary' : '' }}">
                                            <td>
                                                <a href="{{ url('/rechnung/' . $ausschluss->rechnung_id . '/edit') }}">
                                                    {{ $ausschluss->rechnung?->volle_rechnungsnummer ?? '-' }}
                                                </a>
                                            </td>
                                            <td>{{ Str::limit($ausschluss->rechnung?->rechnungsempfaenger?->name, 20) }}</td>
                                            <td>{{ $ausschluss->grund ?? '-' }}</td>
                                            <td>{!! $ausschluss->gueltigkeit_badge !!}</td>
                                            <td>
                                                <form method="POST" 
                                                      action="{{ route('mahnungen.rechnung.ausschluss.entfernen', $ausschluss->rechnung_id) }}"
                                                      class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                                            onclick="return confirm('Ausschluss wirklich entfernen?')">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

</div>

{{-- Modal: Kunde ausschließen --}}
<div class="modal fade" id="modalKundeAusschliessen" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('mahnungen.kunde.ausschliessen') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Kunde vom Mahnlauf ausschließen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="adresse_id" class="form-label">Kunde <span class="text-danger">*</span></label>
                        <select name="adresse_id" id="adresse_id" class="form-select" required>
                            <option value="">-- Bitte wählen --</option>
                            @foreach(\App\Models\Adresse::orderBy('name')->get() as $adresse)
                                <option value="{{ $adresse->id }}">
                                    {{ $adresse->name }} ({{ $adresse->wohnort }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="grund_kunde" class="form-label">Grund</label>
                        <input type="text" class="form-control" id="grund_kunde" name="grund" maxlength="255"
                               placeholder="z.B. Zahlungsvereinbarung, Stammkunde, etc.">
                    </div>
                    <div class="mb-3">
                        <label for="bis_datum_kunde" class="form-label">Befristet bis</label>
                        <input type="date" class="form-control" id="bis_datum_kunde" name="bis_datum">
                        <small class="text-muted">Leer = unbegrenzt</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-primary">Ausschließen</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal: Rechnung ausschließen --}}
<div class="modal fade" id="modalRechnungAusschliessen" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('mahnungen.rechnung.ausschliessen') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Rechnung vom Mahnlauf ausschließen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="rechnung_id" class="form-label">Rechnung <span class="text-danger">*</span></label>
                        <select name="rechnung_id" id="rechnung_id" class="form-select" required>
                            <option value="">-- Bitte wählen --</option>
                            @foreach(\App\Models\Rechnung::where('status', 'sent')->orderByDesc('rechnungsdatum')->limit(100)->get() as $rechnung)
                                <option value="{{ $rechnung->id }}">
                                    {{ $rechnung->volle_rechnungsnummer }} - {{ Str::limit($rechnung->rechnungsempfaenger?->name, 30) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="grund_rechnung" class="form-label">Grund</label>
                        <input type="text" class="form-control" id="grund_rechnung" name="grund" maxlength="255"
                               placeholder="z.B. Reklamation, Ratenzahlung, etc.">
                    </div>
                    <div class="mb-3">
                        <label for="bis_datum_rechnung" class="form-label">Befristet bis</label>
                        <input type="date" class="form-control" id="bis_datum_rechnung" name="bis_datum">
                        <small class="text-muted">Leer = unbegrenzt</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-primary">Ausschließen</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
