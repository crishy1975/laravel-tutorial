{{-- resources/views/angebote/edit.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">

    {{-- Kopfzeile --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0">
                <i class="bi bi-file-earmark-text"></i> 
                Angebot {{ $angebot->angebotsnummer }}
            </h4>
            <small class="text-muted">
                {!! $angebot->status_badge !!}
                @if($angebot->gebaeude)
                    | Gebäude: {{ $angebot->geb_codex }} - {{ $angebot->geb_name }}
                @endif
            </small>
        </div>
        <div class="d-flex gap-2">
            {{-- PDF --}}
            <a href="{{ route('angebote.pdf', ['angebot' => $angebot, 'preview' => 1]) }}" 
               class="btn btn-outline-secondary" target="_blank">
                <i class="bi bi-file-pdf"></i> PDF
            </a>
            
            {{-- E-Mail --}}
            @if($angebot->status !== 'rechnung')
                <a href="{{ route('angebote.versand', $angebot) }}" class="btn btn-primary">
                    <i class="bi bi-envelope"></i> E-Mail
                </a>
            @endif
            
            {{-- Zu Rechnung --}}
            @if(!$angebot->rechnung_id && in_array($angebot->status, ['angenommen', 'versendet']))
                <form method="POST" action="{{ route('angebote.zu-rechnung', $angebot) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-success" 
                            onclick="return confirm('Angebot in Rechnung umwandeln?')">
                        <i class="bi bi-arrow-right-circle"></i> Zu Rechnung
                    </button>
                </form>
            @endif
            
            {{-- Kopieren --}}
            <form method="POST" action="{{ route('angebote.kopieren', $angebot) }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-primary">
                    <i class="bi bi-copy"></i> Kopieren
                </button>
            </form>
            
            <a href="{{ route('angebote.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Zurück
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Verknüpfte Rechnung --}}
    @if($angebot->rechnung_id)
        <div class="alert alert-info">
            <i class="bi bi-link-45deg"></i>
            Dieses Angebot wurde in Rechnung 
            <a href="{{ route('rechnung.edit', $angebot->rechnung_id) }}" class="alert-link">
                {{ $angebot->rechnung?->volle_rechnungsnummer ?? '#' . $angebot->rechnung_id }}
            </a>
            umgewandelt am {{ $angebot->umgewandelt_am?->format('d.m.Y H:i') }}.
        </div>
    @endif

    <div class="row">
        {{-- Hauptformular --}}
        <div class="col-lg-8">
            <form method="POST" action="{{ route('angebote.update', $angebot) }}">
                @csrf
                @method('PUT')

                {{-- Stammdaten --}}
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <i class="bi bi-info-circle"></i> Angebotsdaten
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label">Titel <span class="text-danger">*</span></label>
                                <input type="text" name="titel" class="form-control" 
                                       value="{{ old('titel', $angebot->titel) }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Fattura-Profil</label>
                                <select name="fattura_profile_id" class="form-select">
                                    <option value="">-- Standard --</option>
                                    @foreach($fatturaProfiles as $fp)
                                        <option value="{{ $fp->id }}" 
                                            {{ $angebot->fattura_profile_id == $fp->id ? 'selected' : '' }}>
                                            {{ $fp->bezeichnung }} ({{ $fp->mwst_satz }}%)
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Datum <span class="text-danger">*</span></label>
                                <input type="date" name="datum" class="form-control" 
                                       value="{{ old('datum', $angebot->datum->format('Y-m-d')) }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Gültig bis</label>
                                <input type="date" name="gueltig_bis" class="form-control" 
                                       value="{{ old('gueltig_bis', $angebot->gueltig_bis?->format('Y-m-d')) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">MwSt-Satz %</label>
                                <input type="number" name="mwst_satz" class="form-control" 
                                       step="0.01" min="0" max="100"
                                       value="{{ old('mwst_satz', $angebot->mwst_satz) }}">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Empfänger --}}
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <i class="bi bi-person"></i> Empfänger / Destinatario
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label">Name / Firma</label>
                                <input type="text" name="empfaenger_name" class="form-control" 
                                       value="{{ old('empfaenger_name', $angebot->empfaenger_name) }}">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Straße</label>
                                <input type="text" name="empfaenger_strasse" class="form-control" 
                                       value="{{ old('empfaenger_strasse', $angebot->empfaenger_strasse) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Hausnr.</label>
                                <input type="text" name="empfaenger_hausnummer" class="form-control" 
                                       value="{{ old('empfaenger_hausnummer', $angebot->empfaenger_hausnummer) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">PLZ</label>
                                <input type="text" name="empfaenger_plz" class="form-control" 
                                       value="{{ old('empfaenger_plz', $angebot->empfaenger_plz) }}">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Ort</label>
                                <input type="text" name="empfaenger_ort" class="form-control" 
                                       value="{{ old('empfaenger_ort', $angebot->empfaenger_ort) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">E-Mail</label>
                                <input type="email" name="empfaenger_email" class="form-control" 
                                       value="{{ old('empfaenger_email', $angebot->empfaenger_email) }}">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Texte --}}
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <i class="bi bi-text-paragraph"></i> Texte
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Einleitung (vor Positionen)</label>
                            <textarea name="einleitung" class="form-control" rows="3">{{ old('einleitung', $angebot->einleitung) }}</textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Bemerkung für Kunde (nach Positionen, auf PDF)</label>
                            <textarea name="bemerkung_kunde" class="form-control" rows="3">{{ old('bemerkung_kunde', $angebot->bemerkung_kunde) }}</textarea>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Interne Bemerkung <span class="badge bg-warning text-dark">nicht auf PDF</span></label>
                            <textarea name="bemerkung_intern" class="form-control" rows="2">{{ old('bemerkung_intern', $angebot->bemerkung_intern) }}</textarea>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> Speichern
                </button>
            </form>

            {{-- Positionen --}}
            <div class="card mt-4">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-list-ol"></i> Positionen</span>
                    <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalNeuePosition">
                        <i class="bi bi-plus"></i> Position
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th>Beschreibung</th>
                                <th class="text-end" style="width: 80px;">Anzahl</th>
                                <th style="width: 80px;">Einheit</th>
                                <th class="text-end" style="width: 100px;">Einzelpreis</th>
                                <th class="text-end" style="width: 100px;">Gesamt</th>
                                <th style="width: 80px;"></th>
                            </tr>
                        </thead>
                        <tbody id="positionenBody">
                            @forelse($angebot->positionen as $pos)
                                <tr>
                                    <td>{{ $pos->position }}</td>
                                    <td>{{ $pos->beschreibung }}</td>
                                    <td class="text-end">{{ number_format($pos->anzahl, 2, ',', '.') }}</td>
                                    <td>{{ $pos->einheit }}</td>
                                    <td class="text-end">{{ $pos->einzelpreis_formatiert }}</td>
                                    <td class="text-end fw-bold">{{ $pos->gesamtpreis_formatiert }}</td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-primary" 
                                                    onclick="editPosition({{ $pos->id }}, '{{ addslashes($pos->beschreibung) }}', {{ $pos->anzahl }}, '{{ $pos->einheit }}', {{ $pos->einzelpreis }})">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <form method="POST" action="{{ route('angebote.position.delete', $pos) }}" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger" 
                                                        onclick="return confirm('Position löschen?')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-3">
                                        Keine Positionen vorhanden
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="table-dark">
                            <tr>
                                <th colspan="5" class="text-end">Netto:</th>
                                <th class="text-end">{{ $angebot->netto_formatiert }}</th>
                                <th></th>
                            </tr>
                            <tr>
                                <th colspan="5" class="text-end">MwSt ({{ number_format($angebot->mwst_satz, 0) }}%):</th>
                                <th class="text-end">{{ number_format($angebot->mwst_betrag, 2, ',', '.') }} €</th>
                                <th></th>
                            </tr>
                            <tr>
                                <th colspan="5" class="text-end">Brutto:</th>
                                <th class="text-end fs-5">{{ $angebot->brutto_formatiert }}</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="col-lg-4">
            {{-- Status ändern --}}
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <i class="bi bi-flag"></i> Status ändern
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('angebote.status', $angebot) }}">
                        @csrf
                        <div class="mb-3">
                            <select name="status" class="form-select">
                                <option value="entwurf" {{ $angebot->status === 'entwurf' ? 'selected' : '' }}>Entwurf</option>
                                <option value="versendet" {{ $angebot->status === 'versendet' ? 'selected' : '' }}>Versendet</option>
                                <option value="angenommen" {{ $angebot->status === 'angenommen' ? 'selected' : '' }}>Angenommen ✓</option>
                                <option value="abgelehnt" {{ $angebot->status === 'abgelehnt' ? 'selected' : '' }}>Abgelehnt ✗</option>
                                <option value="abgelaufen" {{ $angebot->status === 'abgelaufen' ? 'selected' : '' }}>Abgelaufen</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-outline-primary w-100">
                            Status ändern
                        </button>
                    </form>
                </div>
            </div>

            {{-- Info --}}
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <i class="bi bi-info-circle"></i> Info
                </div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tr>
                            <td class="text-muted">Erstellt:</td>
                            <td>{{ $angebot->created_at->format('d.m.Y H:i') }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Geändert:</td>
                            <td>{{ $angebot->updated_at->format('d.m.Y H:i') }}</td>
                        </tr>
                        @if($angebot->versendet_am)
                            <tr>
                                <td class="text-muted">Versendet:</td>
                                <td>
                                    {{ $angebot->versendet_am->format('d.m.Y H:i') }}
                                    <br><small class="text-muted">{{ $angebot->versendet_an_email }}</small>
                                </td>
                            </tr>
                        @endif
                    </table>
                </div>
            </div>

            {{-- Log --}}
            <div class="card">
                <div class="card-header bg-light">
                    <i class="bi bi-clock-history"></i> Verlauf
                </div>
                <div class="card-body p-0" style="max-height: 300px; overflow-y: auto;">
                    <ul class="list-group list-group-flush">
                        @forelse($angebot->logs->take(10) as $log)
                            <li class="list-group-item py-2">
                                <div class="d-flex align-items-start">
                                    <i class="bi {{ $log->icon }} me-2 mt-1"></i>
                                    <div>
                                        <div class="fw-medium">{{ $log->titel }}</div>
                                        @if($log->nachricht)
                                            <small class="text-muted">{{ $log->nachricht }}</small>
                                        @endif
                                        <div class="small text-muted">
                                            {{ $log->created_at->format('d.m.Y H:i') }} - {{ $log->user_name }}
                                        </div>
                                    </div>
                                </div>
                            </li>
                        @empty
                            <li class="list-group-item text-muted text-center py-3">
                                Keine Einträge
                            </li>
                        @endforelse
                    </ul>
                </div>
            </div>

            {{-- Löschen --}}
            @if(!$angebot->rechnung_id)
                <div class="card mt-4 border-danger">
                    <div class="card-body">
                        <form method="POST" action="{{ route('angebote.destroy', $angebot) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger w-100"
                                    onclick="return confirm('Angebot wirklich löschen?')">
                                <i class="bi bi-trash"></i> Angebot löschen
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Modal: Neue Position --}}
<div class="modal fade" id="modalNeuePosition" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('angebote.position.add', $angebot) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Neue Position</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Beschreibung <span class="text-danger">*</span></label>
                        <textarea name="beschreibung" class="form-control" rows="2" required></textarea>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Anzahl</label>
                            <input type="number" name="anzahl" class="form-control" step="0.01" value="1" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Einheit</label>
                            <input type="text" name="einheit" class="form-control" value="Stück">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Einzelpreis</label>
                            <input type="number" name="einzelpreis" class="form-control" step="0.01" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-success">Hinzufügen</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal: Position bearbeiten --}}
<div class="modal fade" id="modalEditPosition" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="formEditPosition" action="">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Position bearbeiten</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Beschreibung <span class="text-danger">*</span></label>
                        <textarea name="beschreibung" id="editBeschreibung" class="form-control" rows="2" required></textarea>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Anzahl</label>
                            <input type="number" name="anzahl" id="editAnzahl" class="form-control" step="0.01" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Einheit</label>
                            <input type="text" name="einheit" id="editEinheit" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Einzelpreis</label>
                            <input type="number" name="einzelpreis" id="editEinzelpreis" class="form-control" step="0.01" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-primary">Speichern</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function editPosition(id, beschreibung, anzahl, einheit, einzelpreis) {
    document.getElementById('formEditPosition').action = '/angebote/position/' + id;
    document.getElementById('editBeschreibung').value = beschreibung;
    document.getElementById('editAnzahl').value = anzahl;
    document.getElementById('editEinheit').value = einheit;
    document.getElementById('editEinzelpreis').value = einzelpreis;
    
    new bootstrap.Modal(document.getElementById('modalEditPosition')).show();
}
</script>
@endpush
@endsection
