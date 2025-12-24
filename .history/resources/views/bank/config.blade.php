{{-- resources/views/bank/config.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container py-3">
    
    {{-- Kopfzeile --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0"><i class="bi bi-sliders"></i> Matching-Konfiguration</h4>
            <small class="text-muted">Score-Punkte und Schwellenwerte für automatisches Matching</small>
        </div>
        <a href="{{ route('bank.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Zurück
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form method="POST" action="{{ route('bank.config.update') }}">
        @csrf
        @method('PUT')

        <div class="row">
            {{-- Score-Punkte --}}
            <div class="col-lg-6 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0"><i class="bi bi-star-fill"></i> Score-Punkte</h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">
                            Punkte die bei verschiedenen Übereinstimmungen vergeben werden.
                            Höhere Werte = wichtiger für das Matching.
                        </p>

                        @foreach($descriptions as $field => $info)
                            @if($info['group'] === 'scores')
                                <div class="mb-3">
                                    <label for="{{ $field }}" class="form-label">
                                        <i class="bi {{ $info['icon'] }}"></i>
                                        {{ $info['label'] }}
                                    </label>
                                    <input type="number" 
                                           class="form-control @error($field) is-invalid @enderror" 
                                           id="{{ $field }}" 
                                           name="{{ $field }}" 
                                           value="{{ old($field, $config->{$field}) }}"
                                           step="1"
                                           @if($field === 'score_betrag_abweichung') max="0" @else min="0" @endif>
                                    <small class="text-muted">{{ $info['description'] }}</small>
                                    @error($field)
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Schwellenwerte & Toleranzen --}}
            <div class="col-lg-6 mb-4">
                {{-- Schwellenwerte --}}
                <div class="card mb-4">
                    <div class="card-header bg-warning text-dark">
                        <h6 class="mb-0"><i class="bi bi-speedometer2"></i> Schwellenwerte</h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">
                            Grenzwerte für automatisches Matching und Betrags-Prüfung.
                        </p>

                        @foreach($descriptions as $field => $info)
                            @if($info['group'] === 'thresholds')
                                <div class="mb-3">
                                    <label for="{{ $field }}" class="form-label">
                                        <i class="bi {{ $info['icon'] }}"></i>
                                        {{ $info['label'] }}
                                    </label>
                                    <input type="number" 
                                           class="form-control @error($field) is-invalid @enderror" 
                                           id="{{ $field }}" 
                                           name="{{ $field }}" 
                                           value="{{ old($field, $config->{$field}) }}"
                                           min="0"
                                           step="1">
                                    <small class="text-muted">{{ $info['description'] }}</small>
                                    @error($field)
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>

                {{-- Toleranzen --}}
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0"><i class="bi bi-rulers"></i> Betrags-Toleranzen</h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">
                            Maximale Abweichung in Euro für Betrags-Matches.
                        </p>

                        @foreach($descriptions as $field => $info)
                            @if($info['group'] === 'tolerances')
                                <div class="mb-3">
                                    <label for="{{ $field }}" class="form-label">
                                        <i class="bi {{ $info['icon'] }}"></i>
                                        {{ $info['label'] }}
                                    </label>
                                    <div class="input-group">
                                        <input type="number" 
                                               class="form-control @error($field) is-invalid @enderror" 
                                               id="{{ $field }}" 
                                               name="{{ $field }}" 
                                               value="{{ old($field, $config->{$field}) }}"
                                               min="0"
                                               step="0.01">
                                        <span class="input-group-text">€</span>
                                    </div>
                                    <small class="text-muted">{{ $info['description'] }}</small>
                                    @error($field)
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Beispiel-Berechnung --}}
        <div class="card mb-4">
            <div class="card-header bg-secondary text-white">
                <h6 class="mb-0"><i class="bi bi-calculator"></i> Beispiel-Berechnung</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Typisches Auto-Match:</h6>
                        <table class="table table-sm">
                            <tr>
                                <td>Rechnungsnummer gefunden</td>
                                <td class="text-end fw-bold text-success">+{{ $config->score_rechnungsnr_match }}</td>
                            </tr>
                            <tr>
                                <td>Betrag exakt</td>
                                <td class="text-end fw-bold text-success">+{{ $config->score_betrag_exakt }}</td>
                            </tr>
                            <tr>
                                <td>2 Name-Tokens</td>
                                <td class="text-end fw-bold text-success">+{{ $config->score_name_token_exact * 2 }}</td>
                            </tr>
                            <tr class="table-primary">
                                <td><strong>Gesamt</strong></td>
                                <td class="text-end"><strong>{{ $config->score_rechnungsnr_match + $config->score_betrag_exakt + ($config->score_name_token_exact * 2) }}</strong></td>
                            </tr>
                        </table>
                        <small class="text-muted">
                            @if($config->score_rechnungsnr_match + $config->score_betrag_exakt + ($config->score_name_token_exact * 2) >= $config->auto_match_threshold)
                                <i class="bi bi-check-circle text-success"></i> → Auto-Match (≥ {{ $config->auto_match_threshold }})
                            @else
                                <i class="bi bi-x-circle text-danger"></i> → Kein Auto-Match (< {{ $config->auto_match_threshold }})
                            @endif
                        </small>
                    </div>
                    <div class="col-md-6">
                        <h6>Perfektes Match (IBAN):</h6>
                        <table class="table table-sm">
                            <tr>
                                <td>IBAN gefunden</td>
                                <td class="text-end fw-bold text-success">+{{ $config->score_iban_match }}</td>
                            </tr>
                            <tr>
                                <td>Betrag exakt</td>
                                <td class="text-end fw-bold text-success">+{{ $config->score_betrag_exakt }}</td>
                            </tr>
                            <tr class="table-primary">
                                <td><strong>Gesamt</strong></td>
                                <td class="text-end"><strong>{{ $config->score_iban_match + $config->score_betrag_exakt }}</strong></td>
                            </tr>
                        </table>
                        <small class="text-muted">
                            <i class="bi bi-check-circle text-success"></i> → Auto-Match (≥ {{ $config->auto_match_threshold }})
                        </small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Buttons --}}
        <div class="d-flex justify-content-between">
            <button type="button" class="btn btn-outline-secondary" onclick="resetToDefaults()">
                <i class="bi bi-arrow-counterclockwise"></i> Standard-Werte
            </button>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-lg"></i> Speichern
            </button>
        </div>
    </form>

</div>

{{-- Modal: Standard-Werte --}}
<div class="modal fade" id="resetModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Standard-Werte wiederherstellen?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Alle Werte werden auf die Standard-Einstellungen zurückgesetzt:</p>
                <ul class="small">
                    <li>IBAN-Match: 100</li>
                    <li>CIG-Match: 80</li>
                    <li>Rechnungsnummer: 50</li>
                    <li>Betrag exakt: 30</li>
                    <li>Auto-Match Schwelle: 80</li>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                <form method="POST" action="{{ route('bank.config.reset') }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-arrow-counterclockwise"></i> Zurücksetzen
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function resetToDefaults() {
    new bootstrap.Modal(document.getElementById('resetModal')).show();
}
</script>
@endsection
