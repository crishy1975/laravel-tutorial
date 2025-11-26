@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    
    {{-- Header --}}
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3 mb-0">
                <i class="bi bi-building"></i>
                Unternehmensprofil
            </h1>
            <p class="text-muted">{{ $profil->vollstaendiger_firmenname ?? 'Kein Profil vorhanden' }}</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('unternehmensprofil.bearbeiten') }}" class="btn btn-primary">
                <i class="bi bi-pencil"></i> Bearbeiten
            </a>
        </div>
    </div>

    {{-- Success/Error Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(!$profil->id)
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle"></i>
            <strong>Kein Profil vorhanden!</strong>
            Bitte erstellen Sie zuerst ein Unternehmensprofil.
            <a href="{{ route('unternehmensprofil.bearbeiten') }}" class="alert-link">Jetzt erstellen</a>
        </div>
    @else

    {{-- Status-Karten --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Status</h6>
                    {!! $profil->status_badge !!}
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="text-muted mb-2">E-Mail</h6>
                    @if($profil->hatSmtpKonfiguration())
                        <span class="badge bg-success">✓ Konfiguriert</span>
                    @else
                        <span class="badge bg-secondary">Nicht konfiguriert</span>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="text-muted mb-2">PEC</h6>
                    @if($profil->pec_aktiv && $profil->hatPecSmtpKonfiguration())
                        <span class="badge bg-success">✓ Aktiv</span>
                    @else
                        <span class="badge bg-secondary">Inaktiv</span>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="text-muted mb-2">FatturaPA</h6>
                    {!! $profil->fatturapa_status_badge !!}
                </div>
            </div>
        </div>
    </div>

    {{-- Firmendaten --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-building"></i> Firmendaten</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-sm">
                        <tr>
                            <th width="40%">Firmenname:</th>
                            <td>{{ $profil->vollstaendiger_firmenname }}</td>
                        </tr>
                        @if($profil->geschaeftsfuehrer)
                        <tr>
                            <th>Geschäftsführer:</th>
                            <td>{{ $profil->geschaeftsfuehrer }}</td>
                        </tr>
                        @endif
                        <tr>
                            <th>Adresse:</th>
                            <td>{!! nl2br(e($profil->vollstaendige_adresse)) !!}</td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-sm">
                        <tr>
                            <th width="40%">E-Mail:</th>
                            <td><a href="mailto:{{ $profil->email }}">{{ $profil->email }}</a></td>
                        </tr>
                        @if($profil->telefon)
                        <tr>
                            <th>Telefon:</th>
                            <td>{{ $profil->telefon }}</td>
                        </tr>
                        @endif
                        @if($profil->website)
                        <tr>
                            <th>Webseite:</th>
                            <td><a href="{{ $profil->website }}" target="_blank">{{ $profil->website }}</a></td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Bankdaten --}}
    @if($profil->iban)
    <div class="card shadow-sm mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-bank"></i> Bankdaten</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-sm">
                        <tr>
                            <th width="30%">IBAN:</th>
                            <td><code>{{ $profil->iban_formatiert }}</code></td>
                        </tr>
                        @if($profil->bic)
                        <tr>
                            <th>BIC:</th>
                            <td>{{ $profil->bic }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
                <div class="col-md-6">
                    @if($profil->bank_name)
                    <table class="table table-sm">
                        <tr>
                            <th width="30%">Bank:</th>
                            <td>{{ $profil->bank_name }}</td>
                        </tr>
                    </table>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- E-Mail Konfiguration --}}
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-envelope"></i> E-Mail (Normal)</h5>
                </div>
                <div class="card-body">
                    @if($profil->hatSmtpKonfiguration())
                        <table class="table table-sm mb-0">
                            <tr>
                                <th width="40%">Server:</th>
                                <td>{{ $profil->smtp_host }}</td>
                            </tr>
                            <tr>
                                <th>Port:</th>
                                <td>{{ $profil->smtp_port }} ({{ strtoupper($profil->smtp_verschluesselung) }})</td>
                            </tr>
                            <tr>
                                <th>Absender:</th>
                                <td>{{ $profil->email_absender ?? $profil->email }}</td>
                            </tr>
                        </table>
                    @else
                        <div class="alert alert-warning mb-0">
                            <i class="bi bi-exclamation-triangle"></i> Nicht konfiguriert
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-shield-check"></i> PEC E-Mail</h5>
                </div>
                <div class="card-body">
                    @if($profil->pec_aktiv)
                        @if($profil->hatPecSmtpKonfiguration())
                            <table class="table table-sm mb-0">
                                <tr>
                                    <th width="40%">Server:</th>
                                    <td>{{ $profil->pec_smtp_host }}</td>
                                </tr>
                                <tr>
                                    <th>Port:</th>
                                    <td>{{ $profil->pec_smtp_port }} ({{ strtoupper($profil->pec_smtp_verschluesselung) }})</td>
                                </tr>
                                <tr>
                                    <th>Absender:</th>
                                    <td>{{ $profil->pec_email_absender ?? $profil->pec_email }}</td>
                                </tr>
                            </table>
                        @else
                            <div class="alert alert-warning mb-0">
                                <i class="bi bi-exclamation-triangle"></i> Aktiviert aber nicht konfiguriert
                            </div>
                        @endif
                    @else
                        <div class="alert alert-secondary mb-0">
                            <i class="bi bi-x-circle"></i> PEC ist deaktiviert
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Logo --}}
    @if($profil->hatLogo())
    <div class="card shadow-sm mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-image"></i> Logo</h5>
        </div>
        <div class="card-body text-center">
            <img src="{{ $profil->logo_url }}" alt="Logo" class="img-fluid" style="max-width: 400px;">
        </div>
    </div>
    @endif

    {{-- FatturaPA --}}
    @if($profil->ragione_sociale || $profil->partita_iva)
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0"><i class="bi bi-file-earmark-text"></i> FatturaPA</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-sm">
                        @if($profil->ragione_sociale)
                        <tr>
                            <th width="40%">Ragione Sociale:</th>
                            <td>{{ $profil->ragione_sociale }}</td>
                        </tr>
                        @endif
                        @if($profil->partita_iva)
                        <tr>
                            <th>Partita IVA:</th>
                            <td>{{ $profil->partita_iva_formatiert }}</td>
                        </tr>
                        @endif
                        @if($profil->codice_fiscale)
                        <tr>
                            <th>Codice Fiscale:</th>
                            <td>{{ $profil->codice_fiscale }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-sm">
                        @if($profil->regime_fiscale)
                        <tr>
                            <th width="40%">Regime Fiscale:</th>
                            <td>{{ $profil->regime_fiscale }}</td>
                        </tr>
                        @endif
                        @if($profil->pec_email)
                        <tr>
                            <th>PEC:</th>
                            <td>{{ $profil->pec_email }}</td>
                        </tr>
                        @endif
                        @if($profil->rea_numero)
                        <tr>
                            <th>REA:</th>
                            <td>{{ $profil->rea_ufficio }} - {{ $profil->rea_numero }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>

            @if(!$profil->istFatturapaKonfiguriert())
                <div class="alert alert-warning mb-0">
                    <i class="bi bi-exclamation-triangle"></i>
                    <strong>FatturaPA unvollständig!</strong> Fehlende Felder:
                    {{ implode(', ', $profil->fehlendeFelderFatturaPA()) }}
                </div>
            @endif
        </div>
    </div>
    @endif

    {{-- Rechnungs-Einstellungen --}}
    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-receipt"></i> Rechnungs-Einstellungen</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-sm">
                        <tr>
                            <th width="40%">Nummerierung:</th>
                            <td>
                                {{ $profil->rechnungsnummer_praefix ?? 'RE-' }}{{ date('Y') }}/{{ str_pad('1', $profil->rechnungsnummer_laenge ?? 5, '0', STR_PAD_LEFT) }}
                            </td>
                        </tr>
                        <tr>
                            <th>Zahlungsziel:</th>
                            <td>{{ $profil->zahlungsziel_tage ?? 30 }} Tage</td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-sm">
                        <tr>
                            <th width="40%">MwSt-Satz:</th>
                            <td>{{ number_format($profil->standard_mwst_satz ?? 22, 2, ',', '.') }}%</td>
                        </tr>
                        <tr>
                            <th>Währung:</th>
                            <td>{{ $profil->waehrung ?? 'EUR' }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @endif
</div>
@endsection