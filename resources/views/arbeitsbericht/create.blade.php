@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-0">
                    <i class="bi bi-file-plus"></i> Neuer Arbeitsbericht
                </h4>
                <a href="{{ route('arbeitsbericht.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Zurück
                </a>
            </div>

            <form action="{{ route('arbeitsbericht.store') }}" method="POST" id="arbeitsberichtForm">
                @csrf
                <input type="hidden" name="gebaeude_id" value="{{ $gebaeude->id }}">
                <input type="hidden" name="unterschrift" id="unterschriftInput">

                <!-- Gebäude Info -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <i class="bi bi-building"></i> Gebäude
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <strong class="fs-5">{{ $gebaeude->gebaeude_name }}</strong>
                                @if($gebaeude->codex)
                                    <span class="badge bg-secondary ms-2">{{ $gebaeude->codex }}</span>
                                @endif
                                <div class="text-muted mt-2">
                                    {{ $gebaeude->strasse }} {{ $gebaeude->hausnummer }}<br>
                                    {{ $gebaeude->plz }} {{ $gebaeude->wohnort }}
                                </div>
                            </div>
                            <div class="col-md-6">
                                @if($gebaeude->rechnungsempfaenger)
                                <div class="small">
                                    <strong>Rechnungsempfänger:</strong><br>
                                    {{ $gebaeude->rechnungsempfaenger->name }}<br>
                                    {{ $gebaeude->rechnungsempfaenger->strasse }} {{ $gebaeude->rechnungsempfaenger->hausnummer }}<br>
                                    {{ $gebaeude->rechnungsempfaenger->plz }} {{ $gebaeude->rechnungsempfaenger->wohnort }}
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Datum -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="bi bi-calendar"></i> Datum
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <label for="arbeitsdatum" class="form-label">Arbeitsdatum *</label>
                                <input type="date" 
                                       class="form-control form-control-lg @error('arbeitsdatum') is-invalid @enderror" 
                                       id="arbeitsdatum" 
                                       name="arbeitsdatum" 
                                       value="{{ old('arbeitsdatum', now()->format('Y-m-d')) }}"
                                       required>
                                @error('arbeitsdatum')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="naechste_faelligkeit" class="form-label">Nächste Reinigung fällig</label>
                                <input type="date" 
                                       class="form-control form-control-lg @error('naechste_faelligkeit') is-invalid @enderror" 
                                       id="naechste_faelligkeit" 
                                       name="naechste_faelligkeit" 
                                       value="{{ old('naechste_faelligkeit', $gebaeude->datum_faelligkeit?->format('Y-m-d')) }}">
                                @error('naechste_faelligkeit')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Positionen -->
                @if($gebaeude->aktiveArtikel->count() > 0)
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="bi bi-list-check"></i> Durchgeführte Arbeiten
                        <span class="badge bg-primary">{{ $gebaeude->aktiveArtikel->count() }}</span>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                            <tbody>
                                @foreach($gebaeude->aktiveArtikel as $artikel)
                                <tr>
                                    <td class="ps-3">{{ $artikel->bezeichnung ?? $artikel->artikel?->bezeichnung ?? 'Unbekannt' }}</td>
                                    <td class="text-end pe-3">{{ $artikel->anzahl ?? 1 }} {{ $artikel->einheit ?? '' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif

                <!-- Bemerkung -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="bi bi-chat-text"></i> Bemerkung
                    </div>
                    <div class="card-body">
                        <textarea class="form-control @error('bemerkung') is-invalid @enderror" 
                                  id="bemerkung" 
                                  name="bemerkung" 
                                  rows="3"
                                  placeholder="Optionale Bemerkungen...">{{ old('bemerkung') }}</textarea>
                        @error('bemerkung')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- ═══════════════════════════════════════════════════════════════ -->
                <!-- UNTERSCHRIFT - Kunde unterschreibt hier auf deinem Gerät! -->
                <!-- ═══════════════════════════════════════════════════════════════ -->
                <div class="card mb-4 border-primary">
                    <div class="card-header bg-primary text-white">
                        <i class="bi bi-pen"></i> Unterschrift Kunde
                    </div>
                    <div class="card-body">
                        <!-- Name des Kunden -->
                        <div class="mb-3">
                            <label for="unterschrift_name" class="form-label">Name des Unterzeichners *</label>
                            <input type="text" 
                                   class="form-control form-control-lg @error('unterschrift_name') is-invalid @enderror" 
                                   id="unterschrift_name" 
                                   name="unterschrift_name" 
                                   placeholder="Vor- und Nachname"
                                   value="{{ old('unterschrift_name') }}"
                                   required>
                            @error('unterschrift_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Unterschrift Canvas -->
                        <div class="mb-3">
                            <label class="form-label">Unterschrift *</label>
                            <div class="signature-container border rounded position-relative" style="background: #fafafa;">
                                <canvas id="signatureCanvas" style="width: 100%; height: 200px; touch-action: none;"></canvas>
                                <div class="signature-hint position-absolute text-muted" id="signatureHint" 
                                     style="top: 50%; left: 50%; transform: translate(-50%, -50%); pointer-events: none;">
                                    <i class="bi bi-pen" style="font-size: 24px;"></i><br>
                                    Hier unterschreiben
                                </div>
                            </div>
                            @error('unterschrift')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Löschen Button -->
                        <button type="button" class="btn btn-outline-secondary" onclick="clearSignature()">
                            <i class="bi bi-eraser"></i> Unterschrift löschen
                        </button>
                    </div>
                </div>

                <!-- Submit -->
                <div class="d-grid gap-2 mb-4">
                    <button type="submit" class="btn btn-success btn-lg" id="submitBtn">
                        <i class="bi bi-check-lg"></i> Arbeitsbericht speichern & Link erstellen
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .signature-container {
        background: linear-gradient(to bottom, #ffffff 0%, #f8f9fa 100%);
    }
    
    .signature-hint {
        text-align: center;
        font-size: 14px;
    }
    
    .signature-hint.hidden {
        display: none;
    }
    
    #signatureCanvas {
        cursor: crosshair;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const canvas = document.getElementById('signatureCanvas');
    const ctx = canvas.getContext('2d');
    const hint = document.getElementById('signatureHint');
    const form = document.getElementById('arbeitsberichtForm');
    const unterschriftInput = document.getElementById('unterschriftInput');
    
    let isDrawing = false;
    let hasSignature = false;

    // Canvas für Retina-Displays optimieren
    function resizeCanvas() {
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

    resizeCanvas();
    window.addEventListener('resize', resizeCanvas);

    // Position ermitteln
    function getPosition(e) {
        const rect = canvas.getBoundingClientRect();
        const touch = e.touches ? e.touches[0] : e;
        return {
            x: touch.clientX - rect.left,
            y: touch.clientY - rect.top
        };
    }

    function startDrawing(e) {
        e.preventDefault();
        isDrawing = true;
        hasSignature = true;
        hint.classList.add('hidden');
        
        const pos = getPosition(e);
        ctx.beginPath();
        ctx.moveTo(pos.x, pos.y);
    }

    function draw(e) {
        if (!isDrawing) return;
        e.preventDefault();
        
        const pos = getPosition(e);
        ctx.lineTo(pos.x, pos.y);
        ctx.stroke();
    }

    function stopDrawing(e) {
        if (!isDrawing) return;
        e.preventDefault();
        isDrawing = false;
    }

    // Event Listeners für Maus
    canvas.addEventListener('mousedown', startDrawing);
    canvas.addEventListener('mousemove', draw);
    canvas.addEventListener('mouseup', stopDrawing);
    canvas.addEventListener('mouseout', stopDrawing);

    // Event Listeners für Touch (Smartphone/Tablet)
    canvas.addEventListener('touchstart', startDrawing, { passive: false });
    canvas.addEventListener('touchmove', draw, { passive: false });
    canvas.addEventListener('touchend', stopDrawing, { passive: false });

    // Unterschrift löschen
    window.clearSignature = function() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        hasSignature = false;
        hint.classList.remove('hidden');
    };

    // Form Submit - Unterschrift als Base64 speichern
    form.addEventListener('submit', function(e) {
        if (!hasSignature) {
            e.preventDefault();
            alert('Bitte lassen Sie den Kunden unterschreiben.');
            return false;
        }

        // Canvas als Base64 in hidden input speichern
        unterschriftInput.value = canvas.toDataURL('image/png');
    });
});
</script>
@endsection
