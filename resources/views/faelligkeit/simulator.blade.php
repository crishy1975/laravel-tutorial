@extends('layouts.app')

@section('title', 'Fälligkeits-Simulator')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-1">
                <i class="bi bi-calculator text-primary"></i>
                Fälligkeits-Simulator
            </h1>
            <p class="text-muted mb-0 small">Testen Sie die Fälligkeitslogik mit verschiedenen Szenarien</p>
        </div>
        <div>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalBatchUpdate">
                <i class="bi bi-arrow-repeat"></i> Alle aktualisieren
            </button>
        </div>
    </div>

    <div class="row">
        {{-- Linke Seite: Eingabe --}}
        <div class="col-lg-5">
            {{-- Simulator-Karte --}}
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="bi bi-sliders"></i> Parameter</h6>
                </div>
                <div class="card-body">
                    <form id="simulatorForm">
                        {{-- Stichtag --}}
                        <div class="mb-3">
                            <label for="stichtag" class="form-label">
                                <i class="bi bi-calendar-date"></i> Stichtag (heute simulieren)
                            </label>
                            <input type="date" class="form-control" id="stichtag" name="stichtag" 
                                   value="{{ now()->format('Y-m-d') }}">
                            <small class="text-muted">An welchem Tag soll die Prüfung stattfinden?</small>
                        </div>

                        {{-- Letzte Reinigung --}}
                        <div class="mb-3">
                            <label for="reinigung" class="form-label">
                                <i class="bi bi-brush"></i> Letzte Reinigung
                            </label>
                            <div class="input-group">
                                <input type="date" class="form-control" id="reinigung" name="reinigung">
                                <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('reinigung').value=''">
                                    <i class="bi bi-x"></i>
                                </button>
                            </div>
                            <small class="text-muted">Leer = keine Reinigung eingetragen</small>
                        </div>

                        {{-- Aktive Monate --}}
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="bi bi-calendar-month"></i> Aktive Monate
                            </label>
                            <div class="row g-2">
                                @foreach(['Jan' => 1, 'Feb' => 2, 'Mär' => 3, 'Apr' => 4, 'Mai' => 5, 'Jun' => 6, 'Jul' => 7, 'Aug' => 8, 'Sep' => 9, 'Okt' => 10, 'Nov' => 11, 'Dez' => 12] as $name => $num)
                                    <div class="col-3">
                                        <div class="form-check">
                                            <input class="form-check-input monat-checkbox" type="checkbox" 
                                                   name="monate[]" value="{{ $num }}" id="monat{{ $num }}">
                                            <label class="form-check-label small" for="monat{{ $num }}">
                                                {{ $name }}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="mt-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setMonate([])">Keine</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setMonate([1,2,3,4,5,6,7,8,9,10,11,12])">Alle</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setMonate([1,4,7,10])">Quartalsweise</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setMonate([2,8])">Halbjährlich</button>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-play-fill"></i> Simulieren
                        </button>
                    </form>
                </div>
            </div>

            {{-- Gebäude-Auswahl --}}
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-secondary text-white">
                    <h6 class="mb-0"><i class="bi bi-building"></i> Echtes Gebäude testen</h6>
                </div>
                <div class="card-body">
                    <form id="gebaeudeForm">
                        <div class="mb-3">
                            <label for="gebaeude_id" class="form-label">Gebäude auswählen</label>
                            <select class="form-select" id="gebaeude_id" name="gebaeude_id">
                                <option value="">-- Gebäude wählen --</option>
                                @foreach($gebaeude as $g)
                                    <option value="{{ $g->id }}" 
                                            data-monate="{{ json_encode(collect(range(1,12))->filter(fn($m) => $g->{'m'.str_pad($m,2,'0',STR_PAD_LEFT)})->values()) }}">
                                        {{ $g->codex }} - {{ $g->gebaeude_name ?: '(kein Name)' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-secondary w-100">
                            <i class="bi bi-search"></i> Gebäude prüfen
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Rechte Seite: Ergebnis --}}
        <div class="col-lg-7">
            {{-- Ergebnis-Karte --}}
            <div class="card shadow-sm mb-3" id="resultCard" style="display: none;">
                <div class="card-header" id="resultHeader">
                    <h6 class="mb-0"><i class="bi bi-clipboard-check"></i> Ergebnis</h6>
                </div>
                <div class="card-body">
                    {{-- Status --}}
                    <div class="text-center mb-4" id="resultStatus">
                        <!-- Wird per JS gefüllt -->
                    </div>

                    {{-- Details --}}
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-muted">Eingabe</h6>
                            <table class="table table-sm" id="resultEingabe">
                                <!-- Wird per JS gefüllt -->
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Berechnung</h6>
                            <table class="table table-sm" id="resultBerechnung">
                                <!-- Wird per JS gefüllt -->
                            </table>
                        </div>
                    </div>

                    {{-- Erklärung --}}
                    <div class="alert alert-light border mt-3">
                        <h6 class="alert-heading"><i class="bi bi-info-circle"></i> Erklärung</h6>
                        <pre class="mb-0 small" id="resultErklaerung" style="white-space: pre-wrap;"></pre>
                    </div>

                    {{-- Termine im Jahr --}}
                    <div id="resultTermine" class="mt-3">
                        <!-- Wird per JS gefüllt -->
                    </div>
                </div>
            </div>

            {{-- Beispiele --}}
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="bi bi-lightbulb"></i> Beispiel-Szenarien</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Szenario</th>
                                    <th>Erwartet</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($beispiele as $i => $b)
                                    <tr>
                                        <td>
                                            <strong>{{ $b['name'] }}</strong>
                                            <br>
                                            <small class="text-muted">
                                                Monate: {{ empty($b['monate']) ? 'keine' : implode(', ', $b['monate']) }} |
                                                Reinigung: {{ $b['reinigung'] ?? 'nie' }} |
                                                Stichtag: {{ $b['stichtag'] }}
                                            </small>
                                        </td>
                                        <td><span class="badge bg-secondary">{{ $b['erwartet'] }}</span></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                    onclick="ladeBeispiel({{ json_encode($b) }})">
                                                <i class="bi bi-play"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Batch-Update Modal --}}
<div class="modal fade" id="modalBatchUpdate" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Alle Gebäude aktualisieren</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    Aktualisiert das <code>faellig</code>-Flag aller Gebäude basierend auf der aktuellen Fälligkeitslogik.
                </div>
                <div class="mb-3">
                    <label for="batchStichtag" class="form-label">Stichtag</label>
                    <input type="date" class="form-control" id="batchStichtag" value="{{ now()->format('Y-m-d') }}">
                </div>
                <div id="batchResult" style="display: none;">
                    <hr>
                    <div class="alert" id="batchResultAlert"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Schließen</button>
                <button type="button" class="btn btn-success" id="btnBatchUpdate">
                    <i class="bi bi-arrow-repeat"></i> Jetzt aktualisieren
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Monate setzen (Schnellauswahl)
function setMonate(monate) {
    document.querySelectorAll('.monat-checkbox').forEach(cb => {
        cb.checked = monate.includes(parseInt(cb.value));
    });
}

// Beispiel laden
function ladeBeispiel(beispiel) {
    // Stichtag
    if (beispiel.stichtag) {
        const [tag, monat, jahr] = beispiel.stichtag.split('.');
        document.getElementById('stichtag').value = `${jahr}-${monat.padStart(2, '0')}-${tag.padStart(2, '0')}`;
    }
    
    // Reinigung
    if (beispiel.reinigung) {
        const [tag, monat, jahr] = beispiel.reinigung.split('.');
        document.getElementById('reinigung').value = `${jahr}-${monat.padStart(2, '0')}-${tag.padStart(2, '0')}`;
    } else {
        document.getElementById('reinigung').value = '';
    }
    
    // Monate
    setMonate(beispiel.monate || []);
    
    // Automatisch simulieren
    document.getElementById('simulatorForm').dispatchEvent(new Event('submit'));
}

// Ergebnis anzeigen
function zeigeErgebnis(data) {
    const card = document.getElementById('resultCard');
    const header = document.getElementById('resultHeader');
    const status = document.getElementById('resultStatus');
    
    card.style.display = 'block';
    
    // Header-Farbe basierend auf Fälligkeit
    if (data.ergebnis.ist_faellig) {
        header.className = 'card-header bg-danger text-white';
        status.innerHTML = `
            <div class="display-4 text-danger"><i class="bi bi-exclamation-triangle-fill"></i></div>
            <h3 class="text-danger">FÄLLIG</h3>
            <p class="text-muted">seit ${data.ergebnis.tage_ueberfaellig} Tag(en) überfällig</p>
        `;
    } else {
        header.className = 'card-header bg-success text-white';
        status.innerHTML = `
            <div class="display-4 text-success"><i class="bi bi-check-circle-fill"></i></div>
            <h3 class="text-success">NICHT FÄLLIG</h3>
            <p class="text-muted">noch ${data.ergebnis.tage_bis_faellig} Tag(e) bis zur nächsten Fälligkeit</p>
        `;
    }
    
    // Eingabe-Tabelle
    document.getElementById('resultEingabe').innerHTML = `
        <tr><th>Letzte Reinigung</th><td>${data.eingabe.letzte_reinigung}</td></tr>
        <tr><th>Aktive Monate</th><td>${data.eingabe.aktive_monate_namen.join(', ') || 'keine'}</td></tr>
        <tr><th>Stichtag</th><td>${data.eingabe.stichtag}</td></tr>
    `;
    
    // Berechnung-Tabelle
    document.getElementById('resultBerechnung').innerHTML = `
        <tr><th>Nächste Fälligkeit</th><td><strong>${data.ergebnis.naechste_faelligkeit}</strong></td></tr>
        <tr><th>Status</th><td>${data.ergebnis.ist_faellig ? '<span class="badge bg-danger">Fällig</span>' : '<span class="badge bg-success">OK</span>'}</td></tr>
    `;
    
    // Erklärung
    document.getElementById('resultErklaerung').textContent = data.erklaerung;
    
    // Termine im Jahr
    const termine = data.alle_termine_im_jahr || [];
    if (termine.length > 0) {
        document.getElementById('resultTermine').innerHTML = `
            <h6 class="text-muted">Fälligkeitstermine im Jahr</h6>
            <div class="d-flex flex-wrap gap-2">
                ${termine.map(t => `<span class="badge bg-info">${t}</span>`).join('')}
            </div>
        `;
    }
    
    // Gebäude-Info (falls vorhanden)
    if (data.gebaeude) {
        status.innerHTML += `
            <hr>
            <p class="mb-0">
                <strong>${data.gebaeude.codex}</strong> - ${data.gebaeude.name || '(kein Name)'}
                <br>
                <small class="text-muted">
                    DB-Flag: ${data.gebaeude.aktuell_faellig_flag ? '<span class="badge bg-warning">fällig</span>' : '<span class="badge bg-secondary">nicht fällig</span>'}
                </small>
            </p>
        `;
    }
    
    // Zum Ergebnis scrollen
    card.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// Simulator-Formular
document.getElementById('simulatorForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const monate = [];
    document.querySelectorAll('.monat-checkbox:checked').forEach(cb => {
        monate.push(parseInt(cb.value));
    });
    
    fetch('{{ route("faelligkeit.simuliere") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            monate: monate,
            reinigung: formData.get('reinigung') || null,
            stichtag: formData.get('stichtag') || null
        })
    })
    .then(r => r.json())
    .then(data => zeigeErgebnis(data))
    .catch(err => {
        console.error(err);
        alert('Fehler bei der Simulation');
    });
});

// Gebäude-Formular
document.getElementById('gebaeudeForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const gebaeudeId = document.getElementById('gebaeude_id').value;
    if (!gebaeudeId) {
        alert('Bitte Gebäude auswählen');
        return;
    }
    
    const stichtag = document.getElementById('stichtag').value;
    
    fetch('{{ route("faelligkeit.pruefeGebaeude") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            gebaeude_id: gebaeudeId,
            stichtag: stichtag || null
        })
    })
    .then(r => r.json())
    .then(data => zeigeErgebnis(data))
    .catch(err => {
        console.error(err);
        alert('Fehler beim Prüfen');
    });
});

// Gebäude-Auswahl: Monate übernehmen
document.getElementById('gebaeude_id').addEventListener('change', function() {
    const option = this.options[this.selectedIndex];
    if (option.value) {
        const monate = JSON.parse(option.dataset.monate || '[]');
        setMonate(monate);
    }
});

// Batch-Update
document.getElementById('btnBatchUpdate').addEventListener('click', function() {
    const btn = this;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Läuft...';
    
    const stichtag = document.getElementById('batchStichtag').value;
    
    fetch('{{ route("faelligkeit.batchUpdate") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ stichtag: stichtag })
    })
    .then(r => r.json())
    .then(data => {
        const resultDiv = document.getElementById('batchResult');
        const alertDiv = document.getElementById('batchResultAlert');
        
        resultDiv.style.display = 'block';
        
        if (data.ok) {
            alertDiv.className = 'alert alert-success';
            alertDiv.innerHTML = `
                <strong><i class="bi bi-check-circle"></i> ${data.message}</strong>
                <hr>
                <ul class="mb-0">
                    <li>Geprüft: ${data.stats.gesamt}</li>
                    <li>Fällig: ${data.stats.faellig}</li>
                    <li>Nicht fällig: ${data.stats.nicht_faellig}</li>
                    <li><strong>Geändert: ${data.stats.geaendert}</strong></li>
                </ul>
            `;
        } else {
            alertDiv.className = 'alert alert-danger';
            alertDiv.textContent = data.message || 'Fehler beim Aktualisieren';
        }
        
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Jetzt aktualisieren';
    })
    .catch(err => {
        console.error(err);
        alert('Fehler beim Batch-Update');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Jetzt aktualisieren';
    });
});
</script>
@endpush
