{{-- resources/views/livewire/messungen/amt-export.blade.php --}}
<div>
    @if($show)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,.5);" wire:key="amt-export-modal">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">

                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-envelope-arrow-up"></i>
                        Amt-Export — Messungen
                    </h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="close"></button>
                </div>

                <div class="modal-body">

                    {{-- Kontrolleur-Info --}}
                    <div class="alert alert-info d-flex align-items-center py-2 mb-3">
                        <i class="bi bi-person-badge me-2 fs-4"></i>
                        <div>
                            <strong>Kontrolleur-ID:</strong>
                            <code>{{ config('messungen.kontrolleur_id') ?: '— nicht konfiguriert —' }}</code>
                        </div>
                    </div>

                    {{-- Filter --}}
                    <div class="card mb-3">
                        <div class="card-header py-2"><strong>Filter</strong></div>
                        <div class="card-body">
                            <div class="row g-2 align-items-end">
                                <div class="col-md-2">
                                    <label class="form-label small mb-1">Jahr</label>
                                    <input type="number" class="form-control form-control-sm"
                                           wire:model.live.debounce.400ms="jahr" min="2000" max="2099">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small mb-1">Von (optional)</label>
                                    <input type="date" class="form-control form-control-sm"
                                           wire:model.live.debounce.400ms="vonDatum">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small mb-1">Bis (optional)</label>
                                    <input type="date" class="form-control form-control-sm"
                                           wire:model.live.debounce.400ms="bisDatum">
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" id="nurNichtExportiert"
                                               wire:model.live="nurNichtExportiert">
                                        <label class="form-check-label" for="nurNichtExportiert">
                                            Nur noch nicht exportierte
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Fehler --}}
                    @if($errorMessage)
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle"></i> {{ $errorMessage }}
                        </div>
                    @endif

                    {{-- Validierungs-Report --}}
                    @if($validationReport)
                        <div class="row g-2 mb-3">
                            <div class="col-md-4">
                                <div class="card border-secondary">
                                    <div class="card-body py-2 text-center">
                                        <div class="text-muted small">Gesamt</div>
                                        <div class="fs-3 fw-bold">{{ $validationReport['total'] }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border-success">
                                    <div class="card-body py-2 text-center">
                                        <div class="text-success small">Gültig</div>
                                        <div class="fs-3 fw-bold text-success">{{ $validationReport['valid_count'] }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card {{ $validationReport['invalid_count'] > 0 ? 'border-danger' : 'border-secondary' }}">
                                    <div class="card-body py-2 text-center">
                                        <div class="{{ $validationReport['invalid_count'] > 0 ? 'text-danger' : 'text-muted' }} small">Ungültig</div>
                                        <div class="fs-3 fw-bold {{ $validationReport['invalid_count'] > 0 ? 'text-danger' : '' }}">
                                            {{ $validationReport['invalid_count'] }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Ungültige Messungen (wenn vorhanden) --}}
                        @if($validationReport['invalid_count'] > 0)
                            <div class="alert alert-warning">
                                <strong><i class="bi bi-exclamation-triangle"></i>
                                    {{ $validationReport['invalid_count'] }} Messungen sind ungültig</strong>
                                und werden <u>nicht exportiert</u>. Bitte korrigieren:
                            </div>
                            <div class="table-responsive mb-3" style="max-height: 250px;">
                                <table class="table table-sm table-striped">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th>Kodex</th><th>Datum</th><th>Stadio</th><th>Fehler</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($validationReport['invalid_preview'] as $row)
                                            <tr>
                                                <td><code>{{ $row['kodex'] }}</code></td>
                                                <td>{{ $row['datum'] }}</td>
                                                <td>{{ $row['stadio'] }}</td>
                                                <td>
                                                    <ul class="mb-0 small">
                                                        @foreach($row['errors'] as $err)
                                                            <li class="text-danger">{{ $err }}</li>
                                                        @endforeach
                                                    </ul>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                @if($validationReport['invalid_truncated'])
                                    <p class="small text-muted">… weitere ausgeblendet.</p>
                                @endif
                            </div>
                        @endif

                        {{-- Preview der Export-Zeilen --}}
                        @if($previewText)
                            <div class="mb-3">
                                <label class="form-label small mb-1">Vorschau (Header + erste 5 Zeilen)</label>
                                <pre class="bg-dark text-light p-2 rounded small mb-0" style="font-size: 11px; max-height: 150px; overflow: auto;"><code>{{ $previewText }}</code></pre>
                            </div>
                        @endif

                        {{-- E-Mail --}}
                        <div class="card">
                            <div class="card-header py-2"><strong>Versand</strong></div>
                            <div class="card-body">
                                <div class="mb-2">
                                    <label class="form-label small mb-1">E-Mail-Empfänger (Amt)</label>
                                    <input type="email" class="form-control form-control-sm"
                                           wire:model="empfaenger"
                                           placeholder="amt@example.com">
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="close">Abbrechen</button>

                    @if($validationReport && $validationReport['valid_count'] > 0)
                        <button type="button" class="btn btn-outline-primary" wire:click="download">
                            <i class="bi bi-download"></i> Datei herunterladen
                        </button>
                        <button type="button" class="btn btn-primary" wire:click="send">
                            <i class="bi bi-envelope-check"></i>
                            {{ $validationReport['valid_count'] }} Messungen versenden
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
