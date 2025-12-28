@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-0">
                    <i class="bi bi-file-plus"></i> Neuer Arbeitsbericht / Nuovo rapporto di lavoro
                </h4>
                <a href="{{ route('arbeitsbericht.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Zurück
                </a>
            </div>

            <form action="{{ route('arbeitsbericht.store') }}" method="POST" id="arbeitsberichtForm">
                @csrf
                <input type="hidden" name="gebaeude_id" value="{{ $gebaeude->id }}">
                <input type="hidden" name="unterschrift" id="unterschriftKundeInput">
                <input type="hidden" name="unterschrift_mitarbeiter" id="unterschriftMitarbeiterInput">

                <div class="row">
                    <div class="col-lg-6">
                        <!-- Gebäude Info -->
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <i class="bi bi-building"></i> Objekt / Edificio
                            </div>
                            <div class="card-body">
                                <strong class="fs-5">{{ $gebaeude->gebaeude_name }}</strong>
                                @if($gebaeude->codex)
                                    <span class="badge bg-secondary ms-2">{{ $gebaeude->codex }}</span>
                                @endif
                                <div class="text-muted mt-2">
                                    {{ $gebaeude->strasse }} {{ $gebaeude->hausnummer }}<br>
                                    {{ $gebaeude->plz }} {{ $gebaeude->wohnort }}
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
                                    <div class="col-6">
                                        <label for="arbeitsdatum" class="form-label small text-muted">
                                            Arbeitsdatum / Data lavoro *
                                        </label>
                                        <input type="date" 
                                               class="form-control form-control-lg @error('arbeitsdatum') is-invalid @enderror" 
                                               id="arbeitsdatum" 
                                               name="arbeitsdatum" 
                                               value="{{ old('arbeitsdatum', now()->format('Y-m-d')) }}"
                                               required>
                                    </div>
                                    <div class="col-6">
                                        <label for="naechste_faelligkeit" class="form-label small text-muted">
                                            Nächster Termin / Prossimo appuntamento
                                        </label>
                                        <input type="date" 
                                               class="form-control form-control-lg" 
                                               id="naechste_faelligkeit" 
                                               name="naechste_faelligkeit" 
                                               value="{{ old('naechste_faelligkeit', $gebaeude->datum_faelligkeit?->format('Y-m-d')) }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Positionen -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="bi bi-list-check"></i> Arbeiten / Lavori eseguiti
                                <span class="badge bg-primary">{{ $gebaeude->aktiveArtikel->count() }}</span>
                            </div>
                            @if($gebaeude->aktiveArtikel->count() > 0)
                            <div class="card-body p-0">
                                <table class="table table-sm mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Bezeichnung / Descrizione</th>
                                            <th class="text-center">Menge / Qtà</th>
                                            <th class="text-end">Preis / Prezzo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $summe = 0; @endphp
                                        @foreach($gebaeude->aktiveArtikel as $artikel)
                                        @php 
                                            $einzelpreis = (float) ($artikel->einzelpreis ?? 0);
                                            $anzahl = (float) ($artikel->anzahl ?? 1);
                                            $gesamt = $einzelpreis * $anzahl;
                                            $summe += $gesamt;
                                        @endphp
                                        <tr>
                                            <td>{{ $artikel->beschreibung ?? '-' }}</td>
                                            <td class="text-center">{{ $anzahl }}</td>
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
                            @else
                            <div class="card-body text-center text-muted">
                                <i class="bi bi-exclamation-circle"></i> Keine Artikel
                            </div>
                            @endif
                        </div>

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
                                          placeholder="Optionale Bemerkungen / Note opzionali...">{{ old('bemerkung') }}</textarea>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <!-- Unterschrift KUNDE -->
                        <div class="card mb-4 border-success">
                            <div class="card-header bg-success text-white">
                                <i class="bi bi-pen"></i> Unterschrift Kunde / Firma cliente
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <input type="text" 
                                           class="form-control form-control-lg" 
                                           id="unterschrift_name" 
                                           name="unterschrift_name" 
                                           placeholder="Name / Nome *"
                                           value="{{ old('unterschrift_name', $gebaeude->rechnungsempfaenger?->name ?? $gebaeude->gebaeude_name) }}"
                                           required>
                                </div>
                                <div class="signature-container border rounded" style="background: #fafafa;">
                                    <canvas id="signatureKunde" style="width: 100%; height: 150px; touch-action: none;"></canvas>
                                    <div class="signature-hint" id="hintKunde">
                                        <i class="bi bi-pen"></i> Hier unterschreiben / Firmare qui
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-secondary mt-2" onclick="clearSignature('Kunde')">
                                    <i class="bi bi-eraser"></i> Löschen
                                </button>
                            </div>
                        </div>

                        <!-- Unterschrift MITARBEITER -->
                        <div class="card mb-4 border-primary">
                            <div class="card-header bg-primary text-white">
                                <i class="bi bi-pen"></i> Unterschrift Mitarbeiter / Firma operatore
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <input type="text" 
                                           class="form-control form-control-lg" 
                                           id="mitarbeiter_name" 
                                           name="mitarbeiter_name" 
                                           placeholder="Name Mitarbeiter / Nome operatore *"
                                           value="{{ old('mitarbeiter_name', auth()->user()?->name) }}"
                                           required>
                                </div>
                                <div class="signature-container border rounded" style="background: #fafafa;">
                                    <canvas id="signatureMitarbeiter" style="width: 100%; height: 150px; touch-action: none;"></canvas>
                                    <div class="signature-hint" id="hintMitarbeiter">
                                        <i class="bi bi-pen"></i> Hier unterschreiben / Firmare qui
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-secondary mt-2" onclick="clearSignature('Mitarbeiter')">
                                    <i class="bi bi-eraser"></i> Löschen
                                </button>
                            </div>
                        </div>

                        <!-- Submit -->
                        <div class="d-grid">
                            <button type="submit" class="btn btn-success btn-lg" id="submitBtn">
                                <i class="bi bi-check-lg"></i> Speichern & Link erstellen / Salva e crea link
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .signature-container {
        position: relative;
    }
    .signature-hint {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        color: #aaa;
        pointer-events: none;
        text-align: center;
    }
    .signature-hint.hidden {
        display: none;
    }
    canvas {
        cursor: crosshair;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('arbeitsberichtForm');
    
    // Signature Setup für beide Canvas
    const signatures = {
        Kunde: { canvas: null, ctx: null, drawing: false, hasData: false },
        Mitarbeiter: { canvas: null, ctx: null, drawing: false, hasData: false }
    };

    ['Kunde', 'Mitarbeiter'].forEach(type => {
        const canvas = document.getElementById('signature' + type);
        const ctx = canvas.getContext('2d');
        const hint = document.getElementById('hint' + type);
        
        signatures[type].canvas = canvas;
        signatures[type].ctx = ctx;

        // Canvas für Retina optimieren
        function resize() {
            const rect = canvas.getBoundingClientRect();
            const dpr = window.devicePixelRatio || 1;
            canvas.width = rect.width * dpr;
            canvas.height = rect.height * dpr;
            ctx.scale(dpr, dpr);
            canvas.style.width = rect.width + 'px';
            canvas.style.height = rect.height + 'px';
            ctx.strokeStyle = '#000';
            ctx.lineWidth = 2;
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
        }
        resize();
        window.addEventListener('resize', resize);

        function getPos(e) {
            const rect = canvas.getBoundingClientRect();
            const touch = e.touches ? e.touches[0] : e;
            return { x: touch.clientX - rect.left, y: touch.clientY - rect.top };
        }

        function start(e) {
            e.preventDefault();
            signatures[type].drawing = true;
            signatures[type].hasData = true;
            hint.classList.add('hidden');
            const pos = getPos(e);
            ctx.beginPath();
            ctx.moveTo(pos.x, pos.y);
        }

        function draw(e) {
            if (!signatures[type].drawing) return;
            e.preventDefault();
            const pos = getPos(e);
            ctx.lineTo(pos.x, pos.y);
            ctx.stroke();
        }

        function stop(e) {
            if (!signatures[type].drawing) return;
            e.preventDefault();
            signatures[type].drawing = false;
        }

        canvas.addEventListener('mousedown', start);
        canvas.addEventListener('mousemove', draw);
        canvas.addEventListener('mouseup', stop);
        canvas.addEventListener('mouseout', stop);
        canvas.addEventListener('touchstart', start, { passive: false });
        canvas.addEventListener('touchmove', draw, { passive: false });
        canvas.addEventListener('touchend', stop, { passive: false });
    });

    // Clear Funktion
    window.clearSignature = function(type) {
        const sig = signatures[type];
        sig.ctx.clearRect(0, 0, sig.canvas.width, sig.canvas.height);
        sig.hasData = false;
        document.getElementById('hint' + type).classList.remove('hidden');
    };

    // Form Submit
    form.addEventListener('submit', function(e) {
        if (!signatures.Kunde.hasData) {
            e.preventDefault();
            alert('Bitte Kunde unterschreiben lassen. / Il cliente deve firmare.');
            return false;
        }
        if (!signatures.Mitarbeiter.hasData) {
            e.preventDefault();
            alert('Bitte als Mitarbeiter unterschreiben. / L\'operatore deve firmare.');
            return false;
        }

        document.getElementById('unterschriftKundeInput').value = signatures.Kunde.canvas.toDataURL('image/png');
        document.getElementById('unterschriftMitarbeiterInput').value = signatures.Mitarbeiter.canvas.toDataURL('image/png');
    });
});
</script>
@endsection
