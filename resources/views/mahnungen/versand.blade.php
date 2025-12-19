{{-- resources/views/mahnungen/versand.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container py-3">
    
    {{-- Kopfzeile --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0"><i class="bi bi-send"></i> Mahnungen versenden / Inviare solleciti</h4>
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

            {{-- Info-Box (ersetzt Sprachauswahl) --}}
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="d-flex align-items-center">
                                <span class="me-3 fs-4">🇩🇪 🇮🇹</span>
                                <div>
                                    <strong>Zweisprachiger Versand / Invio bilingue</strong>
                                    <div class="text-muted small">
                                        Alle Mahnungen werden automatisch auf Deutsch und Italienisch erstellt.
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 text-end">
                            <button type="submit" class="btn btn-success" id="btnVersenden" disabled>
                                <i class="bi bi-send"></i>
                                <span id="btnText">Versenden / Inviare</span>
                            </button>
                        </div>
                    </div>
                    <div class="mt-3 pt-3 border-top">
                        <small class="text-muted">
                            <i class="bi bi-paperclip"></i> 
                            <strong>Anhänge / Allegati:</strong> 
                            Mahnungs-PDF (DE/IT) + Original-Rechnung (PDF)
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
                                    <th>Rechnung / Fattura</th>
                                    <th>Kunde / Cliente</th>
                                    <th>E-Mail</th>
                                    <th>Stufe / Livello</th>
                                    <th class="text-end">Betrag</th>
                                    <th class="text-end">Spesen</th>
                                    <th class="text-end">Gesamt</th>
                                    <th>Vorschau</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($mitEmail as $mahnung)
                                    @php
                                        // E-Mail-Priorität: Postadresse → Rechnungsempfänger
                                        $postEmail = $mahnung->rechnung?->gebaeude?->postadresse?->email;
                                        $rechnungEmail = $mahnung->rechnung?->rechnungsempfaenger?->email;
                                        $emailAdresse = $postEmail ?: $rechnungEmail;
                                    @endphp
                                    <tr>
                                        <td>
                                            <input type="checkbox" 
                                                   name="mahnung_ids[]" 
                                                   value="{{ $mahnung->id }}"
                                                   class="form-check-input mahnung-checkbox email-checkbox">
                                        </td>
                                        <td>
                                            <a href="{{ url('/rechnung/' . $mahnung->rechnung_id . '/edit') }}" target="_blank">
                                                {{ $mahnung->rechnungsnummer_anzeige }}
                                            </a>
                                        </td>
                                        <td>{{ Str::limit($mahnung->rechnung?->rechnungsempfaenger?->name, 25) }}</td>
                                        <td>
                                            <small>{{ Str::limit($emailAdresse, 25) }}</small>
                                            @if($postEmail)
                                                <span class="badge bg-info text-dark" title="E-Mail aus Postadresse">P</span>
                                            @endif
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
                                            <div class="btn-group btn-group-sm">
                                                {{-- PDF Vorschau (im Browser) --}}
                                                <a href="{{ route('mahnungen.pdf', ['mahnung' => $mahnung->id, 'preview' => 1]) }}" 
                                                   class="btn btn-outline-primary"
                                                   title="PDF im Browser anzeigen"
                                                   target="_blank">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                {{-- PDF Download --}}
                                                <a href="{{ route('mahnungen.pdf', $mahnung->id) }}" 
                                                   class="btn btn-outline-secondary"
                                                   title="PDF herunterladen">
                                                    <i class="bi bi-download"></i>
                                                </a>
                                                {{-- Details --}}
                                                <a href="{{ route('mahnungen.show', $mahnung->id) }}" 
                                                   class="btn btn-outline-secondary"
                                                   title="Details anzeigen">
                                                    <i class="bi bi-info-circle"></i>
                                                </a>
                                            </div>
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
                            <strong>Postversand erforderlich / Invio postale</strong> ({{ $ohneEmail->count() }})
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 40px;"></th>
                                    <th>Rechnung / Fattura</th>
                                    <th>Kunde / Cliente</th>
                                    <th>Adresse / Indirizzo</th>
                                    <th>Stufe / Livello</th>
                                    <th class="text-end">Betrag</th>
                                    <th class="text-end">Spesen</th>
                                    <th class="text-end">Gesamt</th>
                                    <th>Vorschau</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($ohneEmail as $mahnung)
                                    @php
                                        $empfaenger = $mahnung->rechnung?->rechnungsempfaenger;
                                    @endphp
                                    <tr>
                                        <td>
                                            {{-- Kein Checkbox - muss per Post versendet werden --}}
                                            <span class="text-warning" title="Postversand">
                                                <i class="bi bi-mailbox"></i>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="{{ url('/rechnung/' . $mahnung->rechnung_id . '/edit') }}" target="_blank">
                                                {{ $mahnung->rechnungsnummer_anzeige }}
                                            </a>
                                        </td>
                                        <td>{{ Str::limit($empfaenger?->name, 25) }}</td>
                                        <td>
                                            <small class="text-muted">
                                                {{ $empfaenger?->strasse }} {{ $empfaenger?->hausnummer }},
                                                {{ $empfaenger?->plz }} {{ $empfaenger?->wohnort }}
                                            </small>
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
                                            <div class="btn-group btn-group-sm">
                                                {{-- PDF Vorschau (im Browser) --}}
                                                <a href="{{ route('mahnungen.pdf', ['mahnung' => $mahnung->id, 'preview' => 1]) }}" 
                                                   class="btn btn-outline-primary" 
                                                   title="PDF im Browser anzeigen"
                                                   target="_blank">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                {{-- PDF Download --}}
                                                <a href="{{ route('mahnungen.pdf', $mahnung->id) }}" 
                                                   class="btn btn-outline-secondary"
                                                   title="PDF herunterladen">
                                                    <i class="bi bi-download"></i>
                                                </a>
                                                {{-- Details --}}
                                                <a href="{{ route('mahnungen.show', $mahnung->id) }}" 
                                                   class="btn btn-outline-secondary"
                                                   title="Details anzeigen">
                                                    <i class="bi bi-info-circle"></i>
                                                </a>
                                                {{-- Als versendet markieren --}}
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
            : 'Versenden / Inviare';
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
