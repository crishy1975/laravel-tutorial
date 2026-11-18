{{-- ═══════════════════════════════════════════════════════════════
     ZIEL: resources/views/gemeinden/index.blade.php
     (Ordner "gemeinden" neu anlegen, Datei in index.blade.php umbenennen!)
     Gemeinden: Empfänger (PEC / E-Mail) und Textvorlage für Meldungen
     ═══════════════════════════════════════════════════════════════ --}}
@extends('layouts.app')

@section('title', 'Gemeinden')

@section('content')
<style>
    .vorlage-text { font-size: .85rem; min-height: 300px; }
    .platzhalter code { cursor: pointer; }
</style>

<div class="container-fluid py-2 py-md-4">

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h1 class="h4 mb-0"><i class="bi bi-buildings text-primary"></i> Gemeinden</h1>
        <div class="d-flex gap-2">
            <a href="{{ route('reinigungsplanung.meldungen') }}" class="btn btn-outline-danger btn-sm">
                <i class="bi bi-envelope-exclamation"></i> Meldungen
            </a>
            <button type="button" class="btn btn-primary btn-sm btn-gemeinde-neu">
                <i class="bi bi-plus-lg"></i> Neue Gemeinde
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success py-2">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger py-2">
            @foreach($errors->all() as $e) <div>{{ $e }}</div> @endforeach
        </div>
    @endif

    {{-- Orte ohne Gemeinde --}}
    @if($offeneOrte->isNotEmpty())
        <div class="alert alert-warning py-2 small">
            <i class="bi bi-exclamation-triangle"></i>
            <strong>Orte ohne Gemeinde:</strong>
            @foreach($offeneOrte as $o)
                <a href="#" class="badge bg-warning text-dark text-decoration-none btn-gemeinde-neu" data-name="{{ $o->wohnort }}"
                   title="Neue Gemeinde mit diesem Namen anlegen">{{ $o->wohnort }} ({{ $o->anzahl }})</a>
            @endforeach
            <div class="mt-1">Klick auf einen Ort legt eine Gemeinde an. Gehört er zu einer bestehenden Gemeinde (z. B. „Bolzano“ → Bozen), dort unter „Aliase“ eintragen.</div>
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Gemeinde</th>
                        <th>Aliase</th>
                        <th>Empfänger</th>
                        <th>Vorlage</th>
                        <th class="text-center">Aktiv</th>
                        <th style="width: 90px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($gemeinden as $gm)
                        <tr class="{{ $gm->aktiv ? '' : 'text-muted' }}">
                            <td class="fw-semibold">{{ $gm->name }}</td>
                            <td class="small">{{ $gm->aliase ?: '–' }}</td>
                            <td class="small">
                                @if($gm->pec)
                                    <span class="badge bg-success">PEC</span> {{ $gm->pec }}
                                @elseif($gm->email)
                                    <span class="badge bg-secondary">E-Mail</span> {{ $gm->email }}
                                @else
                                    <span class="text-danger">kein Empfänger</span>
                                @endif
                            </td>
                            <td class="small">{{ $gm->text ? 'eigene' : 'Standard' }}</td>
                            <td class="text-center">{!! $gm->aktiv ? '<i class="bi bi-check-lg text-success"></i>' : '–' !!}</td>
                            <td class="text-end text-nowrap">
                                <button type="button" class="btn btn-outline-primary btn-sm btn-gemeinde-edit"
                                        data-gemeinde="{{ json_encode($gm->only(['id', 'name', 'aliase', 'pec', 'email', 'betreff', 'text', 'aktiv'])) }}">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="POST" action="{{ route('gemeinden.destroy', $gm) }}" class="d-inline"
                                      onsubmit="return confirm('Gemeinde {{ $gm->name }} löschen?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">Noch keine Gemeinden angelegt.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Modal: Gemeinde anlegen / bearbeiten --}}
<div class="modal fade" id="modalGemeinde" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <form method="POST" id="formGemeinde" class="modal-content">
            @csrf
            <input type="hidden" name="_method" id="gmMethod" value="POST">
            <div class="modal-header bg-primary text-white py-2">
                <h6 class="modal-title" id="gmTitel">Gemeinde</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2">
                    <div class="col-md-5">
                        <label class="form-label small fw-bold mb-1">Name *</label>
                        <input type="text" name="name" id="gmName" class="form-control form-control-sm" required maxlength="100">
                    </div>
                    <div class="col-md-7">
                        <label class="form-label small fw-bold mb-1">Aliase <span class="fw-normal text-muted">(weitere Ortsnamen, mit Komma)</span></label>
                        <input type="text" name="aliase" id="gmAliase" class="form-control form-control-sm" placeholder="z. B. Bolzano, Gries">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold mb-1">PEC <span class="fw-normal text-muted">(bevorzugt)</span></label>
                        <input type="email" name="pec" id="gmPec" class="form-control form-control-sm" placeholder="protokoll@pec.gemeinde.it">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold mb-1">E-Mail <span class="fw-normal text-muted">(falls keine PEC)</span></label>
                        <input type="email" name="email" id="gmEmail" class="form-control form-control-sm">
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold mb-1">Betreff <span class="fw-normal text-muted">(leer = Standard)</span></label>
                        <input type="text" name="betreff" id="gmBetreff" class="form-control form-control-sm" placeholder="{{ $standardBetreff }}">
                    </div>
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-end mb-1">
                            <label class="form-label small fw-bold mb-0">Text-Vorlage <span class="fw-normal text-muted">(leer = Standard)</span></label>
                            <a href="#" class="small" id="gmStandardLaden">Standardtext laden</a>
                        </div>
                        <textarea name="text" id="gmText" class="form-control vorlage-text"></textarea>
                        <div class="small text-muted mt-1 platzhalter">
                            Platzhalter (Klick fügt ein):
                            <code data-ph="{GEMEINDE}">{GEMEINDE}</code>
                            <code data-ph="{ANZAHL}">{ANZAHL}</code>
                            <code data-ph="{LISTE}">{LISTE}</code>
                            <code data-ph="{DATUM}">{DATUM}</code>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input type="hidden" name="aktiv" value="0">
                            <input class="form-check-input" type="checkbox" name="aktiv" value="1" id="gmAktiv" checked>
                            <label class="form-check-label small" for="gmAktiv">Aktiv</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Abbrechen</button>
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-check-lg"></i> Speichern</button>
            </div>
        </form>
    </div>
</div>

<script>
(function() {
    const STANDARD_TEXT = @json($standardText);
    const URL_STORE  = '{{ route('gemeinden.store') }}';
    const URL_UPDATE = '{{ url('gemeinden') }}/';

    const modalEl = document.getElementById('modalGemeinde');
    const form    = document.getElementById('formGemeinde');
    const f = id => document.getElementById(id);

    function oeffnen(gm) {
        gm = gm || {};
        f('gmTitel').textContent = gm.id ? 'Gemeinde bearbeiten' : 'Neue Gemeinde';
        form.action = gm.id ? URL_UPDATE + gm.id : URL_STORE;
        f('gmMethod').value = gm.id ? 'PUT' : 'POST';
        f('gmName').value    = gm.name || '';
        f('gmAliase').value  = gm.aliase || '';
        f('gmPec').value     = gm.pec || '';
        f('gmEmail').value   = gm.email || '';
        f('gmBetreff').value = gm.betreff || '';
        f('gmText').value    = gm.text || '';
        f('gmAktiv').checked = gm.id ? !!gm.aktiv : true;
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }

    document.querySelectorAll('.btn-gemeinde-edit').forEach(b =>
        b.addEventListener('click', () => oeffnen(JSON.parse(b.dataset.gemeinde))));

    document.querySelectorAll('.btn-gemeinde-neu').forEach(b =>
        b.addEventListener('click', e => { e.preventDefault(); oeffnen({ name: b.dataset.name || '' }); }));

    f('gmStandardLaden').addEventListener('click', e => {
        e.preventDefault();
        if (!f('gmText').value.trim() || confirm('Aktuellen Text durch Standardtext ersetzen?')) {
            f('gmText').value = STANDARD_TEXT;
        }
    });

    document.querySelectorAll('.platzhalter code').forEach(c => c.addEventListener('click', () => {
        const ta = f('gmText'), ph = c.dataset.ph, pos = ta.selectionStart;
        ta.value = ta.value.slice(0, pos) + ph + ta.value.slice(ta.selectionEnd);
        ta.focus();
        ta.selectionStart = ta.selectionEnd = pos + ph.length;
    }));
})();
</script>
@endsection
