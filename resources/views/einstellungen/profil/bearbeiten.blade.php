@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    
    {{-- Header --}}
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3 mb-0">
                <i class="bi bi-building"></i>
                Unternehmensprofil bearbeiten
            </h1>
            <p class="text-muted">Zentrale Verwaltung aller Firmeneinstellungen</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('unternehmensprofil.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Zurück
            </a>
        </div>
    </div>

    {{-- Success/Error Messages --}}
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

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            <h6 class="alert-heading">Fehler beim Speichern:</h6>
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════
         ⭐ LOGO-VERWALTUNG (AUSSERHALB DES HAUPTFORMULARS!)
    ═══════════════════════════════════════════════════════════ --}}
    @include('einstellungen.profil._logo_upload')

    {{-- ═══════════════════════════════════════════════════════════
         HAUPTFORMULAR (Firmendaten, Bank, FatturaPA, etc.)
    ═══════════════════════════════════════════════════════════ --}}
    <form method="POST" action="{{ route('unternehmensprofil.speichern') }}">
        @csrf

        {{-- =================================================================
             FIRMENDATEN
        ================================================================= --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-building"></i> Firmendaten</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="firmenname" class="form-label">
                            Firmenname <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               class="form-control @error('firmenname') is-invalid @enderror" 
                               id="firmenname" 
                               name="firmenname" 
                               value="{{ old('firmenname', $profil->firmenname) }}"
                               required>
                        @error('firmenname')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="firma_zusatz" class="form-label">Firma Zusatz</label>
                        <input type="text" 
                               class="form-control" 
                               id="firma_zusatz" 
                               name="firma_zusatz" 
                               value="{{ old('firma_zusatz', $profil->firma_zusatz) }}">
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="strasse" class="form-label">
                            Straße <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               class="form-control @error('strasse') is-invalid @enderror" 
                               id="strasse" 
                               name="strasse" 
                               value="{{ old('strasse', $profil->strasse) }}"
                               required>
                        @error('strasse')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-2 mb-3">
                        <label for="hausnummer" class="form-label">
                            Nr. <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               class="form-control @error('hausnummer') is-invalid @enderror" 
                               id="hausnummer" 
                               name="hausnummer" 
                               value="{{ old('hausnummer', $profil->hausnummer) }}"
                               required>
                        @error('hausnummer')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-2 mb-3">
                        <label for="postleitzahl" class="form-label">
                            PLZ <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               class="form-control @error('postleitzahl') is-invalid @enderror" 
                               id="postleitzahl" 
                               name="postleitzahl" 
                               value="{{ old('postleitzahl', $profil->postleitzahl) }}"
                               required>
                        @error('postleitzahl')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="ort" class="form-label">
                            Ort <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               class="form-control @error('ort') is-invalid @enderror" 
                               id="ort" 
                               name="ort" 
                               value="{{ old('ort', $profil->ort) }}"
                               required>
                        @error('ort')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="bundesland" class="form-label">Bundesland/Provinz</label>
                        <input type="text" 
                               class="form-control" 
                               id="bundesland" 
                               name="bundesland" 
                               value="{{ old('bundesland', $profil->bundesland) }}"
                               placeholder="z.B. BZ, Südtirol">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="land" class="form-label">
                            Land <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               class="form-control @error('land') is-invalid @enderror" 
                               id="land" 
                               name="land" 
                               value="{{ old('land', $profil->land ?? 'IT') }}"
                               maxlength="2"
                               placeholder="IT"
                               required>
                        @error('land')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">ISO-Code (2 Buchstaben, z.B. IT, DE, AT)</small>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="telefon" class="form-label">Telefon</label>
                        <input type="text" 
                               class="form-control" 
                               id="telefon" 
                               name="telefon" 
                               value="{{ old('telefon', $profil->telefon) }}"
                               placeholder="+39 0471 123456">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">
                            E-Mail <span class="text-danger">*</span>
                        </label>
                        <input type="email" 
                               class="form-control @error('email') is-invalid @enderror" 
                               id="email" 
                               name="email" 
                               value="{{ old('email', $profil->email) }}"
                               required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="website" class="form-label">Website</label>
                        <input type="url" 
                               class="form-control" 
                               id="website" 
                               name="website" 
                               value="{{ old('website', $profil->website) }}"
                               placeholder="https://www.beispiel.de">
                    </div>
                </div>
            </div>
        </div>

        {{-- =================================================================
             BANKDATEN
        ================================================================= --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-bank"></i> Bankdaten</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="bank_name" class="form-label">Bank</label>
                        <input type="text" 
                               class="form-control" 
                               id="bank_name" 
                               name="bank_name" 
                               value="{{ old('bank_name', $profil->bank_name) }}"
                               placeholder="Südtiroler Sparkasse">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="kontoinhaber" class="form-label">Kontoinhaber</label>
                        <input type="text" 
                               class="form-control" 
                               id="kontoinhaber" 
                               name="kontoinhaber" 
                               value="{{ old('kontoinhaber', $profil->kontoinhaber) }}">
                    </div>

                    <div class="col-md-8 mb-3">
                        <label for="iban" class="form-label">IBAN</label>
                        <input type="text" 
                               class="form-control" 
                               id="iban" 
                               name="iban" 
                               value="{{ old('iban', $profil->iban) }}"
                               placeholder="IT00 X000 0000 0000 0000 0000 000"
                               maxlength="34">
                        <small class="text-muted">Mit oder ohne Leerzeichen</small>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="bic" class="form-label">BIC/SWIFT</label>
                        <input type="text" 
                               class="form-control" 
                               id="bic" 
                               name="bic" 
                               value="{{ old('bic', $profil->bic) }}"
                               placeholder="XXXXXXXX"
                               maxlength="11">
                    </div>
                </div>
            </div>
        </div>

        {{-- =================================================================
             FATTURAPA (ITALIENISCH)
        ================================================================= --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-file-earmark-text"></i> FatturaPA (Italien)</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    <strong>Pflichtfelder für FatturaPA:</strong>
                    Ragione Sociale, Partita IVA (11 Ziffern), Codice Fiscale, Regime Fiscale, PEC E-Mail
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="ragione_sociale" class="form-label">Ragione Sociale</label>
                        <input type="text" 
                               class="form-control" 
                               id="ragione_sociale" 
                               name="ragione_sociale" 
                               value="{{ old('ragione_sociale', $profil->ragione_sociale) }}"
                               placeholder="Italienischer Firmenname">
                        <small class="text-muted">Offizieller Firmenname für Italien</small>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label for="partita_iva" class="form-label">Partita IVA</label>
                        <input type="text" 
                               class="form-control" 
                               id="partita_iva" 
                               name="partita_iva" 
                               value="{{ old('partita_iva', $profil->partita_iva) }}"
                               placeholder="12345678901"
                               maxlength="11">
                        <small class="text-muted">11 Ziffern (ohne IT)</small>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label for="codice_fiscale" class="form-label">Codice Fiscale</label>
                        <input type="text" 
                               class="form-control" 
                               id="codice_fiscale" 
                               name="codice_fiscale" 
                               value="{{ old('codice_fiscale', $profil->codice_fiscale) }}"
                               maxlength="16">
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="regime_fiscale" class="form-label">Regime Fiscale</label>
                        <select class="form-select" id="regime_fiscale" name="regime_fiscale">
                            <option value="">-- Bitte wählen --</option>
                            @foreach($regimeFiscaleOptionen as $code => $label)
                                <option value="{{ $code }}" 
                                        {{ old('regime_fiscale', $profil->regime_fiscale) == $code ? 'selected' : '' }}>
                                    {{ $code }} - {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-8 mb-3">
                        <label for="pec_email" class="form-label">PEC E-Mail</label>
                        <input type="email" 
                               class="form-control" 
                               id="pec_email" 
                               name="pec_email" 
                               value="{{ old('pec_email', $profil->pec_email) }}"
                               placeholder="firma@pec.it">
                        <small class="text-muted">Zertifizierte E-Mail-Adresse (PEC)</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- =================================================================
             RECHNUNGS-EINSTELLUNGEN
        ================================================================= --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-receipt"></i> Rechnungs-Einstellungen</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="rechnungsnummer_praefix" class="form-label">Rechnungsnummer Präfix</label>
                        <input type="text" 
                               class="form-control" 
                               id="rechnungsnummer_praefix" 
                               name="rechnungsnummer_praefix" 
                               value="{{ old('rechnungsnummer_praefix', $profil->rechnungsnummer_praefix ?? 'RE-') }}"
                               placeholder="RE-"
                               maxlength="10">
                        <small class="text-muted">z.B. RE-, FATT-, INV-</small>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="rechnungsnummer_laenge" class="form-label">Nummern-Länge</label>
                        <input type="number" 
                               class="form-control" 
                               id="rechnungsnummer_laenge" 
                               name="rechnungsnummer_laenge" 
                               value="{{ old('rechnungsnummer_laenge', $profil->rechnungsnummer_laenge ?? 5) }}"
                               min="1" 
                               max="10">
                        <small class="text-muted">Anzahl Stellen (z.B. 5 → 00001)</small>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="zahlungsziel_tage" class="form-label">Zahlungsziel (Tage)</label>
                        <input type="number" 
                               class="form-control" 
                               id="zahlungsziel_tage" 
                               name="zahlungsziel_tage" 
                               value="{{ old('zahlungsziel_tage', $profil->zahlungsziel_tage ?? 30) }}"
                               min="1" 
                               max="365">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="standard_mwst_satz" class="form-label">Standard MwSt-Satz (%)</label>
                        <input type="number" 
                               class="form-control" 
                               id="standard_mwst_satz" 
                               name="standard_mwst_satz" 
                               value="{{ old('standard_mwst_satz', $profil->standard_mwst_satz ?? 22) }}"
                               step="0.01" 
                               min="0" 
                               max="100">
                        <small class="text-muted">Italien: 22% (Standard), 10% (ermäßigt), 4% (super-ermäßigt)</small>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="waehrung" class="form-label">Währung</label>
                        <select class="form-select" id="waehrung" name="waehrung">
                            @foreach($waehrungen as $code => $label)
                                <option value="{{ $code }}" 
                                        {{ old('waehrung', $profil->waehrung ?? 'EUR') == $code ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- =================================================================
             SPEICHERN BUTTON
        ================================================================= --}}
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-danger">*</span> 
                        <small class="text-muted">Pflichtfelder</small>
                    </div>
                    <div>
                        <a href="{{ route('unternehmensprofil.index') }}" class="btn btn-secondary me-2">
                            <i class="bi bi-x-circle"></i> Abbrechen
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Speichern
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </form>

</div>
@endsection