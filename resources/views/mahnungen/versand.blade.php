{{-- resources/views/mahnungen/versand.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container py-3">
    
    {{-- Kopfzeile --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0"><i class="bi bi-send"></i> Mahnungen versenden</h4>
            <small class="text-muted">{{ $entwuerfe->count() }} Mahnungen bereit zum Versand</small>
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

    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle"></i> {{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($entwuerfe->isEmpty())
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i>
            <strong>Keine Entwürfe vorhanden.</strong>
            <a href="{{ route('mahnungen.mahnlauf') }}">Mahnlauf starten</a> um neue Mahnungen zu erstellen.
        </div>
    @else
        {{-- Warnung: Ohne E-Mail --}}
        @if($ohneEmail->isNotEmpty())
            <div class="alert alert-warning">
                <i class="bi bi-mailbox"></i>
                <strong>{{ $ohneEmail->count() }} Mahnung(en) ohne E-Mail-Adresse!</strong>
                Diese müssen per Post versendet werden.
            </div>
        @endif

        <form method="POST" action="{{ route('mahnungen.versenden') }}" id="versandForm">
            @csrf

            {{-- Sprache wählen --}}
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <label class="form-label fw-bold mb-0">Sprache der Mahnungen:</label>
                        </div>
                        <div class="col-md-4">
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="sprache" id="spracheDe" value="de" checked>
                                <label class="btn btn-outline-primary" for="spracheDe">
                                    🇩🇪 Deutsch
                                </label>
                                <input type="radio" class="btn-check" name="sprache" id="spracheIt" value="it">
                                <label class="btn btn-outline-primary" for="spracheIt">
                                    🇮🇹 Italiano
                                </label>
                            </div>
                        </div>
                        <div class="col-md-4 text-end">
                            <button type="submit" class="btn btn-success" id="btnVersenden" disabled>
                                <i class="bi bi-send"></i>
                                <span id="btnText">Versenden</span>
                            </button>
                        </div>
                    </div>
                    <div class="mt-3 pt-3 border-top">
                        <small class="text-muted">
                            <i class="bi bi-paperclip"></i> 
                            <strong>Anhänge:</strong> Mahnungs-PDF + Original-Rechnung (PDF)
                        </small>
                    </div>
                </div>
            </div>

            {{-- Mit E-Mail --}}
            @if($mitEmail->isNotEmpty())
                <div class="card mb-4">
                    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                        <div>
                            <i class="bi bi-envelope-check"></i>
                            <strong>E-Mail-Versand möglich</strong> ({{ $mitEmail->count() }})
                        </div>
                        <div>
                            <input type="checkbox" id="selectAllEmail" class="form-check-input">
                            <label for="selectAllEmail" class="form-check-label text-white">Alle</label>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 40px;"></th>
                                    <th>Rechnung</th>
                                    <th>Kunde</th>
                                    <th>E-Mail</th>
                                    <th>Stufe</th>
                                    <th>Betrag</th>
                                    <th>Spesen</th>
                                    <th>Gesamt</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($mitEmail as $mahnung)
                                    <tr>
                                        <td>
                                            <input type="checkbox" 
                                                   name="mahnung_ids[]" 
                                                   value="{{ $mahnung->id }}"
                                                   class="form-check-input mahnung-checkbox email-checkbox">
                                        </td>
                                        <td>
                                            {{ $mahnung->rechnung?->volle_rechnungsnummer ?? '-' }}
                                        </td>
                                        <td>{{ Str::limit($mahnung->rechnung?->rechnungsempfaenger?->name, 25) }}</td>
                                        <td>
                                            <small>{{ $mahnung->rechnung?->rechnungsempfaenger?->email }}</small>
                                        </td>
                                        <td>
                                            <span class="badge {{ $mahnung->stufe?->badge_class ?? 'bg-secondary' }}">
                                                {{ $mahnung->stufe?->name_de ?? 'Stufe ' . $mahnung->mahnstufe }}
                                            </span>
                                        </td>
                                        <td class="text-end">{{ number_format($mahnung->rechnungsbetrag, 2, ',', '.') }} €</td>
                                        <td class="text-end">{{ number_format($mahnung->spesen, 2, ',', '.') }} €</td>
                                        <td class="text-end fw-bold">{{ $mahnung->gesamtbetrag_formatiert }}</td>
                                        <td>
                                            <a href="{{ route('mahnungen.show', $mahnung->id) }}" 
                                               class="btn btn-sm btn-outline-secondary"
                                               title="Vorschau">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            {{-- Ohne E-Mail (Postversand) --}}
            @if($ohneEmail->isNotEmpty())
                <div class="card">
                    <div class="card-header bg-warning d-flex justify-content-between align-items-center">
                        <div>
                            <i class="bi bi-mailbox"></i>
                            <strong>Postversand erforderlich</strong> ({{ $ohneEmail->count() }})
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Rechnung</th>
                                    <th>Kunde</th>
                                    <th>Adresse</th>
                                    <th>Stufe</th>
                                    <th>Gesamt</th>
                                    <th>Aktionen</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($ohneEmail as $mahnung)
                                    @php
                                        $empfaenger = $mahnung->rechnung?->rechnungsempfaenger;
                                    @endphp
                                    <tr>
                                        <td>{{ $mahnung->rechnung?->volle_rechnungsnummer ?? '-' }}</td>
                                        <td>{{ Str::limit($empfaenger?->name, 25) }}</td>
                                        <td>
                                            <small class="text-muted">
                                                {{ $empfaenger?->strasse }} {{ $empfaenger?->hausnummer }}<br>
                                                {{ $empfaenger?->plz }} {{ $empfaenger?->wohnort }}
                                            </small>
                                        </td>
                                        <td>
                                            <span class="badge {{ $mahnung->stufe?->badge_class ?? 'bg-secondary' }}">
                                                {{ $mahnung->stufe?->name_de ?? 'Stufe ' . $mahnung->mahnstufe }}
                                            </span>
                                        </td>
                                        <td class="text-end fw-bold">{{ $mahnung->gesamtbetrag_formatiert }}</td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="{{ route('mahnungen.pdf', [$mahnung->id, 'de']) }}" 
                                                   class="btn btn-outline-primary" title="PDF (DE)">
                                                    <i class="bi bi-file-pdf"></i> DE
                                                </a>
                                                <a href="{{ route('mahnungen.pdf', [$mahnung->id, 'it']) }}" 
                                                   class="btn btn-outline-primary" title="PDF (IT)">
                                                    <i class="bi bi-file-pdf"></i> IT
                                                </a>
                                                <form method="POST" action="{{ route('mahnungen.als-post-versendet', $mahnung->id) }}" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-outline-success" title="Als versendet markieren">
                                                        <i class="bi bi-check"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </form>
    @endif

</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAllEmail = document.getElementById('selectAllEmail');
    const emailCheckboxes = document.querySelectorAll('.email-checkbox');
    const btnVersenden = document.getElementById('btnVersenden');
    const btnText = document.getElementById('btnText');

    function updateButton() {
        const checked = document.querySelectorAll('.mahnung-checkbox:checked').length;
        btnVersenden.disabled = checked === 0;
        btnText.textContent = checked > 0 
            ? `${checked} Mahnung${checked > 1 ? 'en' : ''} versenden`
            : 'Versenden';
    }

    selectAllEmail?.addEventListener('change', function() {
        emailCheckboxes.forEach(cb => cb.checked = this.checked);
        updateButton();
    });

    document.querySelectorAll('.mahnung-checkbox').forEach(cb => {
        cb.addEventListener('change', updateButton);
    });
});
</script>
@endpush
@endsection
