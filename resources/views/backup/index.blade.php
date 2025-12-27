{{-- resources/views/backup/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Backups')

@section('content')
<div class="container-fluid px-2 px-md-4 py-3">

    {{-- Header --}}
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-2">
        <div>
            <h4 class="mb-1">
                <i class="bi bi-database-fill-down text-primary me-2"></i>
                Datenbank-Backups
            </h4>
            <p class="text-muted mb-0 small">
                Wöchentliche Sicherungen deiner Datenbank
            </p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-success btn-sm" id="btnBackupErstellen">
                <i class="bi bi-plus-circle me-1"></i>
                <span class="d-none d-sm-inline">Backup jetzt erstellen</span>
                <span class="d-sm-none">Erstellen</span>
            </button>
            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>
                <span class="d-none d-sm-inline">Dashboard</span>
            </a>
        </div>
    </div>

    {{-- Statistik-Cards --}}
    <div class="row g-2 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-2 p-md-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small">Gesamt</div>
                            <div class="fs-3 fw-bold text-primary">{{ $stats['gesamt'] }}</div>
                        </div>
                        <i class="bi bi-database text-primary fs-4 d-none d-sm-block"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100 {{ $stats['nicht_heruntergeladen'] > 0 ? 'border-start border-warning border-4' : '' }}">
                <div class="card-body p-2 p-md-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small">Nicht geladen</div>
                            <div class="fs-3 fw-bold {{ $stats['nicht_heruntergeladen'] > 0 ? 'text-warning' : 'text-success' }}">
                                {{ $stats['nicht_heruntergeladen'] }}
                            </div>
                        </div>
                        <i class="bi bi-cloud-arrow-down text-warning fs-4 d-none d-sm-block"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100 {{ $stats['tage_seit_download'] !== null && $stats['tage_seit_download'] >= 7 ? 'border-start border-danger border-4' : '' }}">
                <div class="card-body p-2 p-md-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small">Letzter Download</div>
                            @if($stats['tage_seit_download'] !== null)
                                <div class="fs-3 fw-bold {{ $stats['tage_seit_download'] >= 7 ? 'text-danger' : 'text-dark' }}">
                                    {{ $stats['tage_seit_download'] }}
                                </div>
                                <span class="small text-muted">Tage</span>
                            @else
                                <div class="fs-5 text-muted">Noch nie</div>
                            @endif
                        </div>
                        <i class="bi bi-calendar-check text-secondary fs-4 d-none d-sm-block"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-2 p-md-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small">Speicherplatz</div>
                            <div class="fs-4 fw-bold text-dark">
                                {{ number_format($stats['speicherplatz'] / 1048576, 1, ',', '.') }} MB
                            </div>
                        </div>
                        <i class="bi bi-hdd text-secondary fs-4 d-none d-sm-block"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Hinweis wenn Download überfällig --}}
    @if($stats['tage_seit_download'] !== null && $stats['tage_seit_download'] >= 7)
        <div class="alert alert-warning d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
            <div>
                <strong>Backup-Download überfällig!</strong><br>
                <span class="small">Der letzte Download war vor {{ $stats['tage_seit_download'] }} Tagen. Bitte lade ein aktuelles Backup auf deine Festplatte herunter.</span>
            </div>
        </div>
    @elseif($stats['tage_seit_download'] === null && $stats['gesamt'] > 0)
        <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-exclamation-octagon-fill fs-4 me-3"></i>
            <div>
                <strong>Noch kein Backup heruntergeladen!</strong><br>
                <span class="small">Du hast noch nie ein Backup auf deine Festplatte gesichert. Bitte lade jetzt eines herunter.</span>
            </div>
        </div>
    @endif

    {{-- Backup-Liste --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
            <h6 class="mb-0">
                <i class="bi bi-list-ul me-2"></i>
                Verfügbare Backups
            </h6>
            @if($backups->where('status', 'heruntergeladen')->count() > 0)
                <button type="button" class="btn btn-outline-danger btn-sm" id="btnCleanup" title="Alle heruntergeladenen Backups vom Server löschen">
                    <i class="bi bi-trash me-1"></i>
                    <span class="d-none d-sm-inline">Aufräumen</span>
                </button>
            @endif
        </div>

        @if($backups->isEmpty())
            <div class="card-body text-center py-5">
                <i class="bi bi-database-x text-muted" style="font-size: 3rem;"></i>
                <h5 class="mt-3 text-muted">Keine Backups vorhanden</h5>
                <p class="text-muted mb-3">Erstelle jetzt dein erstes Backup!</p>
                <button type="button" class="btn btn-success" id="btnBackupErstellenEmpty">
                    <i class="bi bi-plus-circle me-1"></i> Backup erstellen
                </button>
            </div>
        @else
            {{-- Desktop: Tabelle --}}
            <div class="table-responsive d-none d-md-block">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Datum</th>
                            <th>Dateiname</th>
                            <th class="text-end">Größe</th>
                            <th>Status</th>
                            <th>Heruntergeladen</th>
                            <th style="width: 120px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($backups as $backup)
                            <tr class="{{ $backup->status === 'fehlgeschlagen' ? 'table-danger' : '' }}">
                                <td class="text-nowrap">
                                    <i class="bi bi-calendar3 text-muted me-1"></i>
                                    {{ $backup->erstellt_am->format('d.m.Y H:i') }}
                                </td>
                                <td>
                                    <code class="small">{{ $backup->dateiname }}</code>
                                </td>
                                <td class="text-end text-nowrap">
                                    {{ $backup->groesse_formatiert }}
                                </td>
                                <td>
                                    @if($backup->status === 'erstellt')
                                        <span class="badge bg-warning text-dark">
                                            <i class="bi bi-hourglass-split me-1"></i>Ausstehend
                                        </span>
                                    @elseif($backup->status === 'heruntergeladen')
                                        <span class="badge bg-success">
                                            <i class="bi bi-check-circle me-1"></i>Gesichert
                                        </span>
                                    @else
                                        <span class="badge bg-danger">
                                            <i class="bi bi-x-circle me-1"></i>Fehler
                                        </span>
                                    @endif
                                </td>
                                <td class="text-nowrap">
                                    @if($backup->heruntergeladen_am)
                                        {{ $backup->heruntergeladen_am->format('d.m.Y H:i') }}
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        @if($backup->existiert() && $backup->status !== 'fehlgeschlagen')
                                            <a href="{{ route('backup.download', $backup) }}" class="btn btn-primary" title="Herunterladen">
                                                <i class="bi bi-download"></i>
                                            </a>
                                        @endif
                                        <button type="button" class="btn btn-outline-secondary btn-log" data-id="{{ $backup->id }}" title="Log anzeigen">
                                            <i class="bi bi-journal-text"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-danger btn-delete" data-id="{{ $backup->id }}" data-name="{{ $backup->dateiname }}" title="Löschen">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Mobile: Cards --}}
            <div class="d-md-none">
                @foreach($backups as $backup)
                    <div class="border-bottom p-3 {{ $backup->status === 'fehlgeschlagen' ? 'bg-danger bg-opacity-10' : '' }}">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                @if($backup->status === 'erstellt')
                                    <span class="badge bg-warning text-dark">Ausstehend</span>
                                @elseif($backup->status === 'heruntergeladen')
                                    <span class="badge bg-success">Gesichert</span>
                                @else
                                    <span class="badge bg-danger">Fehler</span>
                                @endif
                            </div>
                            <small class="text-muted">{{ $backup->erstellt_am->format('d.m.Y H:i') }}</small>
                        </div>
                        <div class="small mb-2">
                            <code>{{ $backup->dateiname }}</code>
                            <span class="text-muted ms-2">{{ $backup->groesse_formatiert }}</span>
                        </div>
                        @if($backup->heruntergeladen_am)
                            <div class="small text-muted mb-2">
                                <i class="bi bi-check-circle text-success me-1"></i>
                                Geladen: {{ $backup->heruntergeladen_am->format('d.m.Y H:i') }}
                            </div>
                        @endif
                        <div class="d-flex gap-2">
                            @if($backup->existiert() && $backup->status !== 'fehlgeschlagen')
                                <a href="{{ route('backup.download', $backup) }}" class="btn btn-primary btn-sm flex-grow-1">
                                    <i class="bi bi-download me-1"></i> Download
                                </a>
                            @endif
                            <button type="button" class="btn btn-outline-danger btn-sm btn-delete" data-id="{{ $backup->id }}" data-name="{{ $backup->dateiname }}">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

            @if($backups->hasPages())
                <div class="card-footer bg-white">
                    {{ $backups->links() }}
                </div>
            @endif
        @endif
    </div>

    {{-- Info-Box --}}
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-body">
            <h6 class="card-title">
                <i class="bi bi-info-circle text-info me-2"></i>
                So funktioniert's
            </h6>
            <ul class="mb-0 small text-muted">
                <li><strong>Automatisch:</strong> Jeden Sonntag um 03:00 Uhr wird ein Backup erstellt</li>
                <li><strong>Manuell:</strong> Du kannst jederzeit ein zusätzliches Backup erstellen</li>
                <li><strong>Download:</strong> Lade Backups auf deine Festplatte herunter (Status "Gesichert")</li>
                <li><strong>Aufräumen:</strong> Bereits heruntergeladene Backups können vom Server gelöscht werden</li>
                <li><strong>Rotation:</strong> Backups älter als 30 Tage werden automatisch gelöscht</li>
            </ul>
        </div>
    </div>

</div>

{{-- Log Modal --}}
<div class="modal fade" id="logModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-journal-text me-2"></i>Backup-Log
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="logContent">
                    <div class="text-center py-3">
                        <div class="spinner-border spinner-border-sm"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Toast für Feedback --}}
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="backupToast" class="toast" role="alert">
        <div class="toast-body" id="backupToastBody"></div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = '{{ csrf_token() }}';
    
    // Toast Helper
    function showToast(message, success = true) {
        const toast = document.getElementById('backupToast');
        const body = document.getElementById('backupToastBody');
        body.innerHTML = message;
        toast.classList.remove('bg-success', 'bg-danger', 'text-white');
        toast.classList.add(success ? 'bg-success' : 'bg-danger', 'text-white');
        new bootstrap.Toast(toast, { delay: 4000 }).show();
    }

    // Backup erstellen
    function createBackup(btn) {
        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Erstelle...';
        
        fetch('{{ route("backup.create") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        })
        .then(r => r.json())
        .then(data => {
            if (data.ok) {
                showToast('<i class="bi bi-check-circle me-1"></i> ' + data.message);
                setTimeout(() => location.reload(), 1500);
            } else {
                showToast('<i class="bi bi-x-circle me-1"></i> ' + data.message, false);
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
        })
        .catch(err => {
            showToast('<i class="bi bi-x-circle me-1"></i> Fehler beim Erstellen', false);
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        });
    }

    document.getElementById('btnBackupErstellen')?.addEventListener('click', function() {
        createBackup(this);
    });
    document.getElementById('btnBackupErstellenEmpty')?.addEventListener('click', function() {
        createBackup(this);
    });

    // Log anzeigen
    document.querySelectorAll('.btn-log').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const modal = new bootstrap.Modal(document.getElementById('logModal'));
            const content = document.getElementById('logContent');
            
            content.innerHTML = '<div class="text-center py-3"><div class="spinner-border spinner-border-sm"></div></div>';
            modal.show();
            
            fetch(`/backup/${id}/log`)
                .then(r => r.json())
                .then(data => {
                    if (data.log && data.log.length > 0) {
                        let html = '<ul class="list-unstyled mb-0">';
                        data.log.forEach(entry => {
                            const isError = entry.aktion.includes('FEHLER');
                            html += `<li class="mb-2 ${isError ? 'text-danger' : ''}">
                                <small class="text-muted">${entry.zeit}</small>
                                <br>${entry.aktion}
                            </li>`;
                        });
                        html += '</ul>';
                        if (data.fehler) {
                            html += `<div class="alert alert-danger mt-3 mb-0 small">${data.fehler}</div>`;
                        }
                        content.innerHTML = html;
                    } else {
                        content.innerHTML = '<p class="text-muted mb-0">Kein Log verfügbar</p>';
                    }
                });
        });
    });

    // Löschen
    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const name = this.dataset.name;
            
            if (!confirm(`Backup "${name}" wirklich löschen?`)) return;
            
            fetch(`/backup/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            })
            .then(r => r.json())
            .then(data => {
                if (data.ok) {
                    showToast('<i class="bi bi-check-circle me-1"></i> ' + data.message);
                    setTimeout(() => location.reload(), 1000);
                }
            });
        });
    });

    // Cleanup
    document.getElementById('btnCleanup')?.addEventListener('click', function() {
        if (!confirm('Alle bereits heruntergeladenen Backups vom Server löschen?')) return;
        
        const btn = this;
        btn.disabled = true;
        
        fetch('{{ route("backup.cleanup") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        })
        .then(r => r.json())
        .then(data => {
            if (data.ok) {
                showToast('<i class="bi bi-check-circle me-1"></i> ' + data.message);
                setTimeout(() => location.reload(), 1000);
            }
        });
    });
});
</script>
@endpush
