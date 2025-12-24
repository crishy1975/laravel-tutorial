{{-- resources/views/rechnung/xml-logs.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card shadow-sm border-0">

        {{-- Header --}}
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h4 class="mb-0">
                <i class="bi bi-clock-history"></i>
                XML-Logs für Rechnung {{ $rechnung->rechnungsnummer }}
            </h4>
            <a href="{{ route('rechnung.edit', $rechnung->id) }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Zurück zur Rechnung
            </a>
        </div>

        <div class="card-body">

            {{-- Rechnung Info --}}
            <div class="alert alert-light border mb-4">
                <div class="row">
                    <div class="col-md-3">
                        <strong>Rechnungsnummer:</strong><br>
                        {{ $rechnung->rechnungsnummer }}
                    </div>
                    <div class="col-md-3">
                        <strong>Empfänger:</strong><br>
                        {{ $rechnung->re_name ?? '-' }}
                    </div>
                    <div class="col-md-3">
                        <strong>Betrag:</strong><br>
                        {{ number_format($rechnung->gesamtbetrag_brutto, 2, ',', '.') }} €
                    </div>
                    <div class="col-md-3">
                        <strong>Status:</strong><br>
                        {!! $rechnung->status_badge !!}
                    </div>
                </div>
            </div>

            {{-- Logs Tabelle --}}
            @if($logs->isEmpty())
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    Keine XML-Logs für diese Rechnung vorhanden.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th width="5%">#</th>
                                <th width="15%">Progressivo</th>
                                <th width="12%">Status</th>
                                <th width="20%">Dateiname</th>
                                <th width="8%">Größe</th>
                                <th width="12%">Erstellt</th>
                                <th width="12%">Aktualisiert</th>
                                <th width="16%" class="text-end">Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($logs as $log)
                                <tr class="{{ $log->status === 'superseded' ? 'table-secondary text-muted' : '' }}">
                                    <td>{{ $log->id }}</td>
                                    <td>
                                        <code class="{{ $log->status === 'superseded' ? 'text-muted' : '' }}">
                                            {{ $log->progressivo_invio }}
                                        </code>
                                    </td>
                                    <td>{!! $log->status_badge !!}</td>
                                    <td>
                                        <span class="small text-break">
                                            {{ $log->xml_filename ?? '-' }}
                                        </span>
                                    </td>
                                    <td>{{ $log->file_size_formatted ?? '-' }}</td>
                                    <td>{{ $log->created_at->format('d.m.Y H:i') }}</td>
                                    <td>{{ $log->updated_at->format('d.m.Y H:i') }}</td>
                                    <td class="text-end">
                                        {{-- Download nur für das aktuellste aktive Log --}}
                                        @if($log->xml_file_path && $loop->first && $log->status !== 'superseded')
                                            <a href="{{ route('rechnung.xml.download', $rechnung->id) }}" 
                                               class="btn btn-sm btn-outline-success"
                                               title="XML herunterladen">
                                                <i class="bi bi-download"></i>
                                            </a>
                                        @elseif($log->xml_file_path && $log->status === 'superseded')
                                            <span class="text-muted" title="Ersetztes XML">
                                                <i class="bi bi-file-earmark-x"></i>
                                            </span>
                                        @endif
                                        
                                        @if($log->is_valid)
                                            <span class="badge bg-success" title="Validiert">
                                                <i class="bi bi-check-circle"></i>
                                            </span>
                                        @endif
                                        
                                        @if(!$log->is_abgeschlossen && $log->status !== 'superseded')
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-danger"
                                                    onclick="deleteLog('{{ route('fattura.xml.delete', $log->id) }}')"
                                                    title="Löschen">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                                
                                {{-- Details-Zeile bei Fehlern --}}
                                @if($log->status_detail || ($log->validation_errors && count($log->validation_errors) > 0))
                                    <tr class="table-light">
                                        <td></td>
                                        <td colspan="7" class="small">
                                            @if($log->status_detail)
                                                <strong>Detail:</strong> {{ $log->status_detail }}<br>
                                            @endif
                                            @if($log->validation_errors && count($log->validation_errors) > 0)
                                                <strong class="text-danger">Validierungs-Fehler:</strong>
                                                <ul class="mb-0 mt-1">
                                                    @foreach($log->validation_errors as $error)
                                                        <li>{{ $error }}</li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Legende --}}
                <div class="mt-4 small text-muted">
                    <strong>Status-Legende:</strong>
                    <span class="badge bg-secondary ms-2">pending</span> Ausstehend
                    <span class="badge bg-info ms-2">generated</span> Generiert
                    <span class="badge bg-primary ms-2">signed</span> Signiert
                    <span class="badge bg-warning text-dark ms-2">sent</span> Gesendet
                    <span class="badge bg-success ms-2">delivered</span> Zugestellt
                    <span class="badge bg-success ms-2">accepted</span> Akzeptiert
                    <span class="badge bg-danger ms-2">rejected</span> Abgelehnt
                    <span class="badge bg-dark ms-2">superseded</span> Ersetzt
                </div>
            @endif

        </div>

        {{-- Footer --}}
        <div class="card-footer bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <span class="text-muted">
                    {{ $logs->count() }} Log(s) insgesamt
                </span>
                <a href="{{ route('rechnung.edit', $rechnung->id) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Zurück zur Rechnung
                </a>
            </div>
        </div>

    </div>
</div>

{{-- JavaScript für Löschen --}}
<script>
function deleteLog(url) {
    if (!confirm('Diesen XML-Log wirklich löschen?\n\nDas XML und alle zugehörigen Dateien werden unwiderruflich gelöscht!')) {
        return;
    }
    
    var form = document.createElement('form');
    form.method = 'POST';
    form.action = url;
    form.style.display = 'none';
    
    var csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = '{{ csrf_token() }}';
    form.appendChild(csrfInput);
    
    var methodInput = document.createElement('input');
    methodInput.type = 'hidden';
    methodInput.name = '_method';
    methodInput.value = 'DELETE';
    form.appendChild(methodInput);
    
    document.body.appendChild(form);
    form.submit();
}
</script>
@endsection