{{-- resources/views/bank/auto-match-progress.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            
            {{-- Header --}}
            <div class="text-center mb-4">
                <h4><i class="bi bi-arrow-repeat"></i> Auto-Matching</h4>
                <p class="text-muted">
                    {{ $total }} Buchungen werden mit {{ $rechnungenCount }} offenen Rechnungen ({{ $jahr }}) abgeglichen
                </p>
            </div>

            {{-- Jahr-Auswahl --}}
            <div class="card mb-3">
                <div class="card-body py-2">
                    <form method="GET" action="{{ route('bank.autoMatchProgress') }}" class="row g-2 align-items-center">
                        <div class="col-auto">
                            <label class="form-label mb-0 small">Rechnungen aus Jahr:</label>
                        </div>
                        <div class="col-auto">
                            <select name="jahr" class="form-select form-select-sm" onchange="this.form.submit()">
                                @for($y = now()->year; $y >= now()->year - 5; $y--)
                                    <option value="{{ $y }}" {{ $jahr == $y ? 'selected' : '' }}>{{ $y }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-auto">
                            <span class="badge bg-info">{{ $rechnungenCount }} Rechnungen</span>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Progress Card --}}
            <div class="card shadow">
                <div class="card-body p-4">
                    
                    {{-- Progress Bar --}}
                    <div class="mb-4">
                        <div class="d-flex justify-content-between mb-2">
                            <span id="statusText">Starte...</span>
                            <span id="progressPercent">0%</span>
                        </div>
                        <div class="progress" style="height: 25px;">
                            <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated" 
                                 role="progressbar" style="width: 0%"></div>
                        </div>
                    </div>

                    {{-- Stats --}}
                    <div class="row text-center mb-4">
                        <div class="col-4">
                            <div class="border rounded p-3">
                                <div class="fs-3 fw-bold text-primary" id="processedCount">0</div>
                                <div class="small text-muted">Verarbeitet</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border rounded p-3">
                                <div class="fs-3 fw-bold text-success" id="matchedCount">0</div>
                                <div class="small text-muted">Zugeordnet</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border rounded p-3">
                                <div class="fs-3 fw-bold text-secondary" id="remainingCount">{{ $total }}</div>
                                <div class="small text-muted">Verbleibend</div>
                            </div>
                        </div>
                    </div>

                    {{-- Live Log --}}
                    <div class="mb-3">
                        <label class="form-label small text-muted">
                            <i class="bi bi-terminal"></i> Live-Log (letzte Zuordnungen)
                        </label>
                        <div id="logContainer" class="bg-dark text-light p-3 rounded" 
                             style="height: 200px; overflow-y: auto; font-family: monospace; font-size: 0.85rem;">
                            <div class="text-muted">Warte auf Start...</div>
                        </div>
                    </div>

                    {{-- Buttons --}}
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('bank.index') }}" class="btn btn-outline-secondary" id="btnBack">
                            <i class="bi bi-arrow-left"></i> Zurück
                        </a>
                        <a href="{{ route('bank.matched') }}" class="btn btn-success d-none" id="btnResults">
                            <i class="bi bi-check2-all"></i> Ergebnisse anzeigen
                        </a>
                    </div>
                </div>
            </div>

            {{-- Info --}}
            <div class="alert alert-info mt-3">
                <i class="bi bi-info-circle"></i>
                <strong>Hinweis:</strong> Es werden nur offene Rechnungen aus {{ now()->year }} verglichen.
                Buchungen mit Score ≥ 80 werden automatisch zugeordnet.
            </div>

        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const total = {{ $total }};
    const batchSize = 10;
    const jahr = {{ $jahr }};  // Gewähltes Jahr
    let processed = 0;
    let matched = 0;
    let lastId = 0;  // Letzte geprüfte ID
    
    const progressBar = document.getElementById('progressBar');
    const progressPercent = document.getElementById('progressPercent');
    const statusText = document.getElementById('statusText');
    const processedCount = document.getElementById('processedCount');
    const matchedCount = document.getElementById('matchedCount');
    const remainingCount = document.getElementById('remainingCount');
    const logContainer = document.getElementById('logContainer');
    const btnBack = document.getElementById('btnBack');
    const btnResults = document.getElementById('btnResults');

    function addLog(message, type = 'info') {
        const colors = {
            'success': '#28a745',
            'info': '#6c757d',
            'warning': '#ffc107',
            'error': '#dc3545'
        };
        const line = document.createElement('div');
        line.style.color = colors[type] || '#6c757d';
        line.textContent = `[${new Date().toLocaleTimeString()}] ${message}`;
        logContainer.appendChild(line);
        logContainer.scrollTop = logContainer.scrollHeight;
    }

    function updateProgress() {
        const percent = total > 0 ? Math.round((processed / total) * 100) : 100;
        progressBar.style.width = percent + '%';
        progressPercent.textContent = percent + '%';
        processedCount.textContent = processed;
        matchedCount.textContent = matched;
        remainingCount.textContent = total - processed;
    }

    function processBatch() {
        statusText.textContent = `Verarbeite nächste ${batchSize} Buchungen...`;
        
        fetch('{{ route("bank.autoMatchBatch") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                batch_size: batchSize,
                last_id: lastId,
                jahr: jahr
            })
        })
        .then(response => response.json())
        .then(data => {
            processed += data.processed;
            matched += data.matched;
            lastId = data.last_id || lastId;  // Letzte ID speichern
            
            // Log matches
            if (data.results && data.results.length > 0) {
                data.results.forEach(r => {
                    const betrag = parseFloat(r.betrag) || 0;
                    addLog(`✓ ${r.rechnungsnummer} → ${betrag.toFixed(2)}€ (Score: ${r.score})`, 'success');
                });
            } else if (data.processed > 0) {
                addLog(`${data.processed} geprüft, keine Matches (Score < 80)`, 'info');
            }
            
            updateProgress();
            
            if (data.done) {
                // Fertig!
                progressBar.classList.remove('progress-bar-animated');
                progressBar.classList.add('bg-success');
                statusText.innerHTML = '<i class="bi bi-check-circle text-success"></i> Fertig!';
                
                const noMatch = processed - matched;
                addLog(`Abgeschlossen: ${matched} zugeordnet, ${noMatch} ohne Match (Score < 80)`, 'success');
                
                btnBack.classList.add('d-none');
                btnResults.classList.remove('d-none');
            } else {
                // Nächster Batch
                setTimeout(processBatch, 100);
            }
        })
        .catch(error => {
            addLog('Fehler: ' + error.message, 'error');
            statusText.innerHTML = '<i class="bi bi-exclamation-triangle text-danger"></i> Fehler aufgetreten';
            progressBar.classList.remove('progress-bar-animated');
            progressBar.classList.add('bg-danger');
        });
    }

    // Start
    if (total > 0) {
        logContainer.innerHTML = '';
        addLog(`Starte Auto-Matching für ${total} Buchungen...`, 'info');
        processBatch();
    } else {
        statusText.innerHTML = '<i class="bi bi-check-circle text-success"></i> Keine offenen Buchungen';
        progressBar.style.width = '100%';
        progressBar.classList.remove('progress-bar-animated');
        progressBar.classList.add('bg-success');
        progressPercent.textContent = '100%';
        addLog('Keine unzugeordneten Buchungen vorhanden.', 'info');
        btnResults.classList.remove('d-none');
    }
});
</script>
@endpush
