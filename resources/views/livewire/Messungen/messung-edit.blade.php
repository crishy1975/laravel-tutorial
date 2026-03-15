{{-- resources/views/livewire/messungen/messung-edit.blade.php --}}
<div class="container-fluid py-2 py-md-4">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 h3-md mb-0">
                <i class="bi bi-speedometer2 text-primary"></i>
                {{ $isNew ? 'Neue Messung' : 'Messung bearbeiten' }}
            </h1>
            @if(!$isNew)
                <p class="text-muted mb-0 small">
                    Kodex {{ $cIM_CODICE }} / {{ $datum }}
                </p>
            @endif
        </div>
        <a href="{{ route('messungen.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i>
            <span class="d-none d-sm-inline">Zurück</span>
        </a>
    </div>

    <form wire:submit="save">
        <div class="row g-3">
            {{-- Linke Spalte: Formular --}}
            <div class="col-lg-8">
                {{-- Grunddaten --}}
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-light py-2">
                        <h6 class="mb-0"><i class="bi bi-info-circle"></i> Grunddaten</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-6 col-md-3">
                                <label class="form-label small mb-1">Anlagen-Kodex *</label>
                                <input type="text" wire:model.live.debounce.500ms="cIM_CODICE"
                                       class="form-control form-control-sm font-monospace @error('cIM_CODICE') is-invalid @enderror"
                                       {{ !$isNew ? 'readonly' : '' }}>
                                @error('cIM_CODICE') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label small mb-1">Stadio</label>
                                <input type="number" wire:model="cMIS_STADIO"
                                       class="form-control form-control-sm" min="1" max="99">
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label small mb-1">Datum *</label>
                                <input type="date" wire:model="datum"
                                       class="form-control form-control-sm @error('datum') is-invalid @enderror">
                                @error('datum') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label small mb-1">Uhrzeit *</label>
                                <input type="time" wire:model="uhrzeit" step="1"
                                       class="form-control form-control-sm @error('uhrzeit') is-invalid @enderror">
                                @error('uhrzeit') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small mb-1">Kunde/Name</label>
                                <input type="text" wire:model="cIM_NAME"
                                       class="form-control form-control-sm">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small mb-1">Brennstoff *</label>
                                <select wire:model.live="cMIS_COMBUSTIBILE" class="form-select form-select-sm">
                                    @foreach($brennstoffe as $key => $value)
                                        <option value="{{ $key }}">{{ $value['text'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Messwerte --}}
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-light py-2">
                        <h6 class="mb-0"><i class="bi bi-thermometer-half"></i> Messwerte</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-4 col-md-2">
                                <label class="form-label small mb-1">O₂ %</label>
                                <input type="number" wire:model.live.debounce.500ms="cMIS_OSSIGENO"
                                       class="form-control form-control-sm" step="0.1" min="0" max="21">
                            </div>
                            <div class="col-4 col-md-2">
                                <label class="form-label small mb-1">CO₂ %</label>
                                <input type="number" wire:model="cMIS_ANIDRIDE_CARBONICA"
                                       class="form-control form-control-sm" step="0.1" min="0" max="20">
                            </div>
                            <div class="col-4 col-md-2">
                                <label class="form-label small mb-1">CO mg/m³</label>
                                <input type="number" wire:model.live.debounce.500ms="cMIS_MONOSSSIDO"
                                       class="form-control form-control-sm @if($cMIS_MONOSSSIDO > 500) is-invalid border-danger @endif"
                                       min="0" max="999999">
                            </div>
                            <div class="col-4 col-md-2">
                                <label class="form-label small mb-1">NOx mg/m³</label>
                                <input type="number" wire:model.live.debounce.500ms="cMIS_BIOSSIDO_AZOTO"
                                       class="form-control form-control-sm @if($cMIS_BIOSSIDO_AZOTO > 200) is-invalid border-warning @endif"
                                       min="0" max="9999">
                            </div>
                            <div class="col-4 col-md-2">
                                <label class="form-label small mb-1">Abg.Verl. %</label>
                                <input type="number" wire:model="cMIS_PERD_FUMI"
                                       class="form-control form-control-sm" step="0.1" min="0" max="100">
                            </div>
                            <div class="col-4 col-md-2">
                                <label class="form-label small mb-1">Wirkungsgrad</label>
                                <input type="text" value="{{ $wirkungsgrad ? $wirkungsgrad . ' %' : '-' }}"
                                       class="form-control form-control-sm bg-light" readonly>
                            </div>
                        </div>
                        <hr class="my-2">
                        <div class="row g-2">
                            <div class="col-4 col-md-2">
                                <label class="form-label small mb-1">Abgastemp. °C</label>
                                <input type="number" wire:model="cMIS_T_GAS_COMB"
                                       class="form-control form-control-sm" min="0" max="999">
                            </div>
                            <div class="col-4 col-md-2">
                                <label class="form-label small mb-1">Lufttemp. °C</label>
                                <input type="number" wire:model="cMIS_T_ARIA_COMB"
                                       class="form-control form-control-sm" min="-20" max="99">
                            </div>
                            <div class="col-4 col-md-2">
                                <label class="form-label small mb-1">Kesseltemp. °C</label>
                                <input type="number" wire:model="cMIS_T_LIQ_CONV"
                                       class="form-control form-control-sm" min="0" max="999">
                            </div>
                            <div class="col-4 col-md-2">
                                <label class="form-label small mb-1">Rußzahl (0-9)</label>
                                <input type="number" wire:model.live.debounce.500ms="cMIS_IND_OPACITA"
                                       class="form-control form-control-sm" min="0" max="9">
                            </div>
                            <div class="col-4 col-md-2">
                                <label class="form-label small mb-1">Ölspuren</label>
                                <select wire:model.live="cMIS_TRACCE_OLEO" class="form-select form-select-sm">
                                    <option value="1">Nein</option>
                                    <option value="0">Ja</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Kessel-Info --}}
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-light py-2">
                        <h6 class="mb-0"><i class="bi bi-fire"></i> Kessel</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-6 col-md-3">
                                <label class="form-label small mb-1">Baujahr</label>
                                <input type="number" wire:model.live.debounce.500ms="boilerYear"
                                       class="form-control form-control-sm" min="1900" max="2100">
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label small mb-1">Leistung kW</label>
                                <input type="number" wire:model.live.debounce.500ms="boilerPower"
                                       class="form-control form-control-sm" min="0" max="9999">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Buttons --}}
                <div class="d-flex gap-2 mb-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg"></i>
                        {{ $isNew ? 'Erstellen' : 'Speichern' }}
                    </button>
                    <button type="button" wire:click="berechneGrenzwerte" class="btn btn-outline-secondary">
                        <i class="bi bi-calculator"></i> Grenzwerte prüfen
                    </button>
                    @if(!$isNew)
                        <button type="button" wire:click="delete" wire:confirm="Messung wirklich löschen?"
                                class="btn btn-outline-danger ms-auto">
                            <i class="bi bi-trash"></i> Löschen
                        </button>
                    @endif
                </div>
            </div>

            {{-- Rechte Spalte: Info --}}
            <div class="col-lg-4">
                {{-- Ergebnis --}}
                <div class="card shadow-sm mb-3 {{ $strEsito === '1' ? 'border-success' : ($strEsito === '0' ? 'border-danger' : '') }}">
                    <div class="card-header py-2 {{ $strEsito === '1' ? 'bg-success text-white' : ($strEsito === '0' ? 'bg-danger text-white' : 'bg-light') }}">
                        <h6 class="mb-0">
                            @if($strEsito === '1')
                                <i class="bi bi-check-circle"></i> Positiv
                            @elseif($strEsito === '0')
                                <i class="bi bi-x-circle"></i> Negativ
                            @else
                                <i class="bi bi-question-circle"></i> Ergebnis
                            @endif
                        </h6>
                    </div>
                    @if(!empty($grenzwertDetails))
                        <div class="card-body py-2">
                            <table class="table table-sm table-borderless mb-0 small">
                                @if(isset($grenzwertDetails['co']))
                                    <tr>
                                        <td>CO</td>
                                        <td class="text-end">{{ $cMIS_MONOSSSIDO }} / {{ $grenzwertDetails['co']['grenzwert'] }} mg/m³</td>
                                        <td class="text-end">
                                            @if($grenzwertDetails['co']['ok'])
                                                <i class="bi bi-check-lg text-success"></i>
                                            @else
                                                <i class="bi bi-x-lg text-danger"></i>
                                            @endif
                                        </td>
                                    </tr>
                                @endif
                                @if(isset($grenzwertDetails['nox']))
                                    <tr>
                                        <td>NOx</td>
                                        <td class="text-end">{{ $cMIS_BIOSSIDO_AZOTO }} / {{ $grenzwertDetails['nox']['grenzwert'] }} mg/m³</td>
                                        <td class="text-end">
                                            @if($grenzwertDetails['nox']['ok'])
                                                <i class="bi bi-check-lg text-success"></i>
                                            @else
                                                <i class="bi bi-x-lg text-danger"></i>
                                            @endif
                                        </td>
                                    </tr>
                                @endif
                                @if(isset($grenzwertDetails['russ']))
                                    <tr>
                                        <td>Rußzahl</td>
                                        <td class="text-end">{{ $cMIS_IND_OPACITA }} / {{ $grenzwertDetails['russ']['grenzwert'] }}</td>
                                        <td class="text-end">
                                            @if($grenzwertDetails['russ']['ok'])
                                                <i class="bi bi-check-lg text-success"></i>
                                            @else
                                                <i class="bi bi-x-lg text-danger"></i>
                                            @endif
                                        </td>
                                    </tr>
                                @endif
                                @if(isset($grenzwertDetails['oel']))
                                    <tr>
                                        <td>Ölspuren</td>
                                        <td class="text-end">{{ $cMIS_TRACCE_OLEO === '0' ? 'Ja' : 'Nein' }}</td>
                                        <td class="text-end">
                                            @if($grenzwertDetails['oel']['ok'])
                                                <i class="bi bi-check-lg text-success"></i>
                                            @else
                                                <i class="bi bi-x-lg text-danger"></i>
                                            @endif
                                        </td>
                                    </tr>
                                @endif
                            </table>
                        </div>
                    @endif
                </div>

                {{-- Anlagen-Info --}}
                @if($anlageInfo)
                    <div class="card shadow-sm mb-3 border-info">
                        <div class="card-header bg-info text-white py-2">
                            <h6 class="mb-0"><i class="bi bi-building"></i> Anlage gefunden</h6>
                        </div>
                        <div class="card-body py-2 small">
                            <div class="mb-1">
                                <strong>{{ $anlageInfo['kodex'] }}</strong>
                                {{ $anlageInfo['name'] }}
                            </div>
                            <div class="text-muted">
                                <i class="bi bi-geo-alt"></i>
                                {{ $anlageInfo['strasse'] }}, {{ $anlageInfo['ort'] }}
                            </div>
                            @if($anlageInfo['hersteller'])
                                <div class="text-muted">
                                    <i class="bi bi-wrench"></i>
                                    {{ $anlageInfo['hersteller'] }}
                                    @if($anlageInfo['baujahr'])
                                        ({{ $anlageInfo['baujahr'] }})
                                    @endif
                                    @if($anlageInfo['leistung'])
                                        · {{ $anlageInfo['leistung'] }} kW
                                    @endif
                                </div>
                            @endif
                            <div class="mt-2">
                                <a href="{{ route('messungen.anlagen.edit', $anlageInfo['kodex']) }}" 
                                   class="btn btn-sm btn-outline-info">
                                    <i class="bi bi-pencil"></i> Anlage bearbeiten
                                </a>
                            </div>
                        </div>
                    </div>
                @elseif($cIM_CODICE)
                    <div class="card shadow-sm mb-3 border-warning">
                        <div class="card-header bg-warning py-2">
                            <h6 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Keine Anlage</h6>
                        </div>
                        <div class="card-body py-2 small">
                            <p class="mb-0">Für den Kodex <strong>{{ $cIM_CODICE }}</strong> wurde keine Anlage gefunden.</p>
                        </div>
                    </div>
                @endif

                {{-- Hilfe --}}
                <div class="card shadow-sm">
                    <div class="card-header bg-light py-2">
                        <h6 class="mb-0"><i class="bi bi-question-circle"></i> Hinweise</h6>
                    </div>
                    <div class="card-body py-2 small text-muted">
                        <p class="mb-1">
                            <strong>Ölspuren:</strong> Bei Gas-Brennern immer "Nein" (invertiert: 0=Ja, 1=Nein in DB)
                        </p>
                        <p class="mb-1">
                            <strong>Rußzahl:</strong> Nur bei Öl-Brennern relevant (0-9)
                        </p>
                        <p class="mb-0">
                            <strong>Grenzwerte:</strong> Werden automatisch anhand Brennstoff, Baujahr und Leistung berechnet
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@push('styles')
<style>
    .form-label { font-weight: 500; }
    @media (max-width: 575.98px) {
        .container-fluid { padding-left: 0.5rem; padding-right: 0.5rem; }
        .card-body { padding: 0.75rem; }
    }
</style>
@endpush
