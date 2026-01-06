<div>
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4 mb-0">
            <i class="bi bi-clock-history text-success"></i>
            <span class="d-none d-sm-inline">Lohnstundenerfassung / Registrazione ore</span>
            <span class="d-sm-none">Stunden / Ore</span>
        </h2>
        <button wire:click="neu" class="btn btn-success">
            <i class="bi bi-plus-circle"></i> <span class="d-none d-sm-inline">Neu / Nuovo</span>
        </button>
    </div>

    {{-- Formular / Modulo --}}
    @if($showForm)
        <div class="card mb-4">
            <div class="card-header bg-success text-white py-2">
                <i class="bi bi-pencil"></i>
                {{ $editId ? 'Bearbeiten / Modifica' : 'Neue Stunden / Nuove ore' }}
            </div>
            <div class="card-body">
                <form wire:submit="speichern">
                    <div class="row g-3">
                        {{-- Datum / Data --}}
                        <div class="col-6 col-md-3">
                            <label class="form-label small">Datum / Data *</label>
                            <input type="date" wire:model="datum" class="form-control @error('datum') is-invalid @enderror">
                            @error('datum') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Stunden / Ore --}}
                        <div class="col-6 col-md-2">
                            <label class="form-label small">Stunden / Ore *</label>
                            <input type="number" step="0.25" wire:model="stunden" 
                                   class="form-control @error('stunden') is-invalid @enderror"
                                   placeholder="8">
                            @error('stunden') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Typ / Tipo --}}
                        <div class="col-12 col-md-4">
                            <label class="form-label small">Typ / Tipo *</label>
                            <select wire:model="typ" class="form-select @error('typ') is-invalid @enderror">
                                @foreach($typen as $kuerzel => $bezeichnung)
                                    <option value="{{ $kuerzel }}">{{ $kuerzel }} - {{ $bezeichnung }}</option>
                                @endforeach
                            </select>
                            @error('typ') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Notizen / Note --}}
                        <div class="col-12 col-md-3">
                            <label class="form-label small">Notizen / Note</label>
                            <input type="text" wire:model="notizen" class="form-control"
                                   placeholder="Opzionale...">
                        </div>
                    </div>

                    <div class="mt-3 d-flex gap-2">
                        <button type="submit" class="btn btn-success flex-grow-1 flex-md-grow-0">
                            <i class="bi bi-check-lg"></i> Speichern / Salva
                        </button>
                        <button type="button" wire:click="abbrechen" class="btn btn-secondary flex-grow-1 flex-md-grow-0">
                            <i class="bi bi-x-lg"></i> Abbrechen / Annulla
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Filter / Filtro --}}
    <div class="card mb-3">
        <div class="card-body py-2">
            <div class="row g-2 align-items-center">
                <div class="col-5 col-sm-auto">
                    <select wire:model.live="filterMonat" class="form-select form-select-sm">
                        @php
                            $monate = [
                                1 => ['Jan', 'Gen'],
                                2 => ['Feb', 'Feb'],
                                3 => ['Mär', 'Mar'],
                                4 => ['Apr', 'Apr'],
                                5 => ['Mai', 'Mag'],
                                6 => ['Jun', 'Giu'],
                                7 => ['Jul', 'Lug'],
                                8 => ['Aug', 'Ago'],
                                9 => ['Sep', 'Set'],
                                10 => ['Okt', 'Ott'],
                                11 => ['Nov', 'Nov'],
                                12 => ['Dez', 'Dic'],
                            ];
                        @endphp
                        @foreach($monate as $num => $names)
                            <option value="{{ $num }}">{{ $names[0] }} / {{ $names[1] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-4 col-sm-auto">
                    <select wire:model.live="filterJahr" class="form-select form-select-sm">
                        @for($j = now()->year; $j >= now()->year - 2; $j--)
                            <option value="{{ $j }}">{{ $j }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-3 col-sm-auto">
                    <span class="badge bg-info fs-6 w-100 py-2">
                        {{ number_format($gesamtStunden, 1, ',', '.') }} h
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- Zusammenfassung nach Typ / Riepilogo per tipo --}}
    @if(count($stundenNachTyp) > 0)
        <div class="mb-3 overflow-auto">
            <div class="d-flex gap-2 pb-2" style="min-width: max-content;">
                @foreach($stundenNachTyp as $typ => $summe)
                    <span class="badge bg-{{ $typ === 'No' ? 'primary' : ($typ === 'Üb' ? 'warning text-dark' : 'secondary') }} py-2 px-3">
                        {{ $typ }}: {{ number_format($summe, 1, ',', '.') }} h
                    </span>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Mobile Karten-Ansicht --}}
    <div class="d-md-none">
        @forelse($eintraege as $eintrag)
            <div class="card mb-2">
                <div class="card-body py-2 px-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <strong>{{ $eintrag->datum->format('d.m.Y') }}</strong>
                            <small class="text-muted ms-1">{{ $eintrag->datum->translatedFormat('D') }}</small>
                            <br>
                            <span class="badge bg-{{ $eintrag->typ === 'No' ? 'primary' : ($eintrag->typ === 'Üb' ? 'warning text-dark' : 'secondary') }} me-1">
                                {{ $eintrag->typ }}
                            </span>
                            <span class="badge bg-dark">{{ number_format($eintrag->stunden, 2, ',', '.') }} h</span>
                            @if($eintrag->notizen)
                                <br><small class="text-muted">{{ Str::limit($eintrag->notizen, 30) }}</small>
                            @endif
                        </div>
                        <div class="btn-group btn-group-sm">
                            <button wire:click="bearbeiten({{ $eintrag->id }})" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button wire:click="loeschen({{ $eintrag->id }})"
                                    wire:confirm="Wirklich löschen? / Eliminare davvero?"
                                    class="btn btn-outline-danger btn-sm">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center text-muted py-5">
                <i class="bi bi-inbox fs-1"></i>
                <p class="mb-0 mt-2">Keine Einträge / Nessuna voce</p>
            </div>
        @endforelse
    </div>

    {{-- Desktop Tabelle --}}
    <div class="card d-none d-md-block">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Datum / Data</th>
                            <th>Typ / Tipo</th>
                            <th class="text-center">Stunden / Ore</th>
                            <th>Notizen / Note</th>
                            <th class="text-end">Aktionen / Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($eintraege as $eintrag)
                            <tr>
                                <td>
                                    <strong>{{ $eintrag->datum->format('d.m.Y') }}</strong>
                                    <br><small class="text-muted">{{ $eintrag->datum->translatedFormat('l') }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $eintrag->typ === 'No' ? 'primary' : ($eintrag->typ === 'Üb' ? 'warning text-dark' : 'secondary') }}">
                                        {{ $eintrag->typ }}
                                    </span>
                                    <br><small class="text-muted">{{ $typen[$eintrag->typ] ?? '' }}</small>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-dark fs-6">{{ number_format($eintrag->stunden, 2, ',', '.') }} h</span>
                                </td>
                                <td>
                                    {{ $eintrag->notizen ?: '-' }}
                                </td>
                                <td class="text-end">
                                    <button wire:click="bearbeiten({{ $eintrag->id }})" 
                                            class="btn btn-sm btn-outline-primary" title="Bearbeiten / Modifica">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button wire:click="loeschen({{ $eintrag->id }})"
                                            wire:confirm="Wirklich löschen? / Eliminare davvero?"
                                            class="btn btn-sm btn-outline-danger" title="Löschen / Elimina">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox fs-1"></i>
                                    <p class="mb-0 mt-2">Keine Einträge / Nessuna voce</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($eintraege->hasPages())
            <div class="card-footer">
                {{ $eintraege->links() }}
            </div>
        @endif
    </div>

    {{-- Mobile Pagination --}}
    @if($eintraege->hasPages())
        <div class="d-md-none mt-3">
            {{ $eintraege->links() }}
        </div>
    @endif

    {{-- Legende / Legenda (Collapsible auf Mobile) --}}
    <div class="card mt-4">
        <div class="card-header py-2" data-bs-toggle="collapse" data-bs-target="#legendeCollapse" style="cursor: pointer;">
            <i class="bi bi-info-circle"></i> Legende / Legenda
            <i class="bi bi-chevron-down float-end d-md-none"></i>
        </div>
        <div class="collapse d-md-block" id="legendeCollapse">
            <div class="card-body py-2">
                <div class="row g-1">
                    @foreach($typen as $kuerzel => $bezeichnung)
                        <div class="col-6 col-md-4 col-lg-3">
                            <small><strong>{{ $kuerzel }}</strong> = {{ $bezeichnung }}</small>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
