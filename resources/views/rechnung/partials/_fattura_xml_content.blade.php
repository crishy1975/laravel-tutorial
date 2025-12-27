{{-- resources/views/rechnung/partials/_fattura_xml_content.blade.php --}}
{{-- ⭐ MOBILE-OPTIMIERTE VERSION --}}
{{-- ⭐ NUR DER INHALT - Modals sind in _fattura_xml_modals.blade.php --}}

@php
    use App\Models\FatturaXmlLog;
    
    // Neuestes erfolgreiches XML-Log
    $xmlLog = FatturaXmlLog::where('rechnung_id', $rechnung->id)
        ->whereIn('status', [
            FatturaXmlLog::STATUS_GENERATED,
            FatturaXmlLog::STATUS_SIGNED,
            FatturaXmlLog::STATUS_SENT,
            FatturaXmlLog::STATUS_DELIVERED,
            FatturaXmlLog::STATUS_ACCEPTED,
        ])
        ->latest()
        ->first();
    
    // Alle Logs zählen
    $logsCount = FatturaXmlLog::where('rechnung_id', $rechnung->id)->count();
@endphp

<div class="row g-3 g-md-4">

    {{-- Status-Übersicht --}}
    <div class="col-12">
        <div class="card {{ $xmlLog ? 'border-success' : 'border-warning' }}">
            <div class="card-header {{ $xmlLog ? 'bg-success' : 'bg-warning' }} {{ $xmlLog ? 'text-white' : '' }} py-2">
                <h6 class="mb-0">
                    <i class="bi bi-file-earmark-code"></i> 
                    FatturaPA <span class="d-none d-sm-inline">XML</span>
                    @if($logsCount > 0)
                        <span class="badge bg-light text-dark ms-2">{{ $logsCount }}</span>
                    @endif
                </h6>
            </div>
            <div class="card-body p-2 p-md-3">
                
                {{-- ═══════════════════════════════════════════════════════════
                    KEIN XML VORHANDEN
                ═══════════════════════════════════════════════════════════ --}}
                @if(!$xmlLog)
                    
                    <div class="alert alert-info mb-3 small">
                        <i class="bi bi-info-circle"></i>
                        Noch kein FatturaPA XML generiert. 
                        <span class="d-none d-md-inline">Klicken Sie auf "XML generieren" um die elektronische Rechnung zu erstellen.</span>
                    </div>
                    
                    {{-- Mobile: Gestapelte Buttons --}}
                    <div class="d-grid gap-2 d-md-none">
                        <button type="button" 
                                id="btn-generate-xml"
                                class="btn btn-primary btn-lg"
                                data-generate-url="{{ route('rechnung.xml.generate', $rechnung->id) }}">
                            <i class="bi bi-file-earmark-plus"></i>
                            XML generieren
                        </button>
                        <div class="d-flex gap-2">
                            <a href="{{ route('rechnung.xml.preview', $rechnung->id) }}" 
                               class="btn btn-outline-secondary flex-fill"
                               target="_blank">
                                <i class="bi bi-eye"></i> Preview
                            </a>
                            <a href="{{ route('rechnung.xml.debug', $rechnung->id) }}" 
                               class="btn btn-outline-info flex-fill"
                               target="_blank">
                                <i class="bi bi-bug"></i> Debug
                            </a>
                        </div>
                    </div>

                    {{-- Desktop: Buttons nebeneinander --}}
                    <div class="row g-2 d-none d-md-flex">
                        <div class="col-md-6">
                            <button type="button" 
                                    id="btn-generate-xml-desktop"
                                    class="btn btn-primary w-100"
                                    data-generate-url="{{ route('rechnung.xml.generate', $rechnung->id) }}">
                                <i class="bi bi-file-earmark-plus"></i>
                                XML generieren
                            </button>
                        </div>
                        <div class="col-md-3">
                            <a href="{{ route('rechnung.xml.preview', $rechnung->id) }}" 
                               class="btn btn-outline-secondary w-100"
                               target="_blank">
                                <i class="bi bi-eye"></i>
                                Preview
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="{{ route('rechnung.xml.debug', $rechnung->id) }}" 
                               class="btn btn-outline-info w-100"
                               target="_blank"
                               title="Debug-Informationen anzeigen">
                                <i class="bi bi-bug"></i>
                                Debug
                            </a>
                        </div>
                    </div>
                
                {{-- ═══════════════════════════════════════════════════════════
                    XML VORHANDEN
                ═══════════════════════════════════════════════════════════ --}}
                @else
                    
                    {{-- Status & Progressivo --}}
                    <div class="row g-2 g-md-3 mb-3">
                        <div class="col-6">
                            <div class="p-2 p-md-3 bg-light rounded">
                                <strong class="text-muted d-block mb-1 small">Status</strong>
                                {!! $xmlLog->status_badge !!}
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 p-md-3 bg-light rounded">
                                <strong class="text-muted d-block mb-1 small">Progressivo</strong>
                                <code class="fs-6 fs-md-5">{{ $xmlLog->progressivo_invio }}</code>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Details --}}
                    <div class="row g-2 g-md-3 mb-3 small">
                        <div class="col-12 col-md-4">
                            <strong class="text-muted d-block">Dateiname</strong>
                            <span class="text-break font-monospace" style="font-size: 0.75rem;">{{ $xmlLog->xml_filename }}</span>
                        </div>
                        <div class="col-4 col-md-2">
                            <strong class="text-muted d-block">Größe</strong>
                            {{ $xmlLog->file_size_formatted }}
                        </div>
                        <div class="col-4 col-md-3">
                            <strong class="text-muted d-block">Erstellt</strong>
                            {{ $xmlLog->created_at->format('d.m.Y') }}
                            <span class="d-none d-lg-inline">{{ $xmlLog->created_at->format('H:i') }}</span>
                        </div>
                        <div class="col-4 col-md-3">
                            <strong class="text-muted d-block">Validierung</strong>
                            @if($xmlLog->is_valid)
                                <span class="badge bg-success"><i class="bi bi-check-circle"></i> <span class="d-none d-sm-inline">Gültig</span></span>
                            @else
                                <span class="badge bg-warning"><i class="bi bi-exclamation-triangle"></i></span>
                            @endif
                        </div>
                    </div>
                    
                    {{-- Validation Errors --}}
                    @if(!$xmlLog->is_valid && $xmlLog->validation_errors)
                        <div class="alert alert-warning mb-3 small">
                            <strong><i class="bi bi-exclamation-triangle"></i> Validierungs-Fehler:</strong>
                            <ul class="mb-0 mt-2">
                                @foreach($xmlLog->validation_errors as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    
                    {{-- Haupt-Aktionen - Mobile gestapelt --}}
                    <div class="d-grid gap-2 d-md-none">
                        <a href="{{ route('rechnung.xml.download', $rechnung->id) }}" 
                           class="btn btn-success btn-lg">
                            <i class="bi bi-download"></i>
                            XML herunterladen
                        </a>
                        <div class="d-flex gap-2">
                            <a href="{{ route('rechnung.xml.preview', $rechnung->id) }}" 
                               class="btn btn-outline-secondary flex-fill"
                               target="_blank">
                                <i class="bi bi-eye"></i> Preview
                            </a>
                            <button type="button" 
                                    class="btn btn-outline-warning flex-fill" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#modalRegenerateXml">
                                <i class="bi bi-arrow-clockwise"></i> Neu
                            </button>
                        </div>
                    </div>

                    {{-- Haupt-Aktionen - Desktop nebeneinander --}}
                    <div class="row g-2 d-none d-md-flex">
                        <div class="col-md-4">
                            <a href="{{ route('rechnung.xml.download', $rechnung->id) }}" 
                               class="btn btn-success w-100">
                                <i class="bi bi-download"></i>
                                XML herunterladen
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="{{ route('rechnung.xml.preview', $rechnung->id) }}" 
                               class="btn btn-outline-secondary w-100"
                               target="_blank">
                                <i class="bi bi-eye"></i>
                                Preview anzeigen
                            </a>
                        </div>
                        <div class="col-md-4">
                            <button type="button" 
                                    class="btn btn-outline-warning w-100" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#modalRegenerateXml">
                                <i class="bi bi-arrow-clockwise"></i>
                                Neu generieren
                            </button>
                        </div>
                    </div>
                    
                    {{-- Zusätzliche Aktionen --}}
                    <div class="row g-2 mt-2">
                        @if($logsCount > 1)
                            <div class="col-6 col-md-6">
                                <a href="{{ route('rechnung.xml.logs', $rechnung->id) }}" 
                                   class="btn btn-outline-info w-100 btn-sm">
                                    <i class="bi bi-clock-history"></i>
                                    <span class="d-none d-sm-inline">Alle</span> Logs ({{ $logsCount }})
                                </a>
                            </div>
                        @endif
                        
                        @if(!$xmlLog->is_abgeschlossen)
                            <div class="col-{{ $logsCount > 1 ? '6' : '12' }} col-md-{{ $logsCount > 1 ? '6' : '12' }}">
                                <button type="button" 
                                        class="btn btn-outline-danger w-100 btn-sm" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#modalDeleteXml">
                                    <i class="bi bi-trash"></i>
                                    XML löschen
                                </button>
                            </div>
                        @endif
                    </div>
                    
                @endif
                
            </div>
        </div>
    </div>

    {{-- Profil-Info --}}
    @if($rechnung->fatturaProfile)
    <div class="col-12 col-md-6">
        <div class="card border-info h-100">
            <div class="card-header bg-info text-white py-2">
                <h6 class="mb-0 small"><i class="bi bi-person-badge"></i> FatturaPA-Profil</h6>
            </div>
            <div class="card-body small p-2 p-md-3">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <th width="40%">Bezeichnung:</th>
                        <td>{{ $rechnung->fatturaProfile->bezeichnung }}</td>
                    </tr>
                    <tr>
                        <th>MwSt-Satz:</th>
                        <td>{{ number_format($rechnung->mwst_satz, 2, ',', '.') }}%</td>
                    </tr>
                    @if($rechnung->split_payment)
                    <tr>
                        <th>Split Payment:</th>
                        <td><span class="badge bg-warning text-dark">Aktiv</span></td>
                    </tr>
                    @endif
                    @if($rechnung->ritenuta)
                    <tr>
                        <th>Ritenuta:</th>
                        <td><span class="badge bg-info">{{ number_format($rechnung->ritenuta_prozent, 2, ',', '.') }}%</span></td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- Empfänger-Info für FatturaPA --}}
    <div class="col-12 col-md-6">
        <div class="card border-secondary h-100">
            <div class="card-header bg-secondary text-white py-2">
                <h6 class="mb-0 small"><i class="bi bi-building"></i> Empfänger SDI-Daten</h6>
            </div>
            <div class="card-body small p-2 p-md-3">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <th width="40%">Codice Univoco:</th>
                        <td>
                            @if($rechnung->re_codice_univoco)
                                <code>{{ $rechnung->re_codice_univoco }}</code>
                            @else
                                <span class="text-danger">⚠ Fehlt!</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>PEC:</th>
                        <td class="text-truncate" style="max-width: 150px;">{{ $rechnung->re_pec ?: '-' }}</td>
                    </tr>
                    <tr>
                        <th>MwSt-Nummer:</th>
                        <td>{{ $rechnung->re_mwst_nummer ?: '-' }}</td>
                    </tr>
                    @if($rechnung->cup)
                    <tr>
                        <th>CUP:</th>
                        <td><code>{{ $rechnung->cup }}</code></td>
                    </tr>
                    @endif
                    @if($rechnung->cig)
                    <tr>
                        <th>CIG:</th>
                        <td><code>{{ $rechnung->cig }}</code></td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>
    </div>

</div>
