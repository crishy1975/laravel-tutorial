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
         HAUPTFORMULAR (Firmendaten, Bank, E-Mail, FatturaPA, etc.)
    ═══════════════════════════════════════════════════════════ --}}
    <form method="POST" action="{{ route('unternehmensprofil.speichern') }}">
        @csrf

        {{-- =================================================================
             1. FIRMENDATEN
        ================================================================= --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-building"></i> Firmendaten</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    {{-- Firmenname --}}
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

                    {{-- Firma Zusatz --}}
                    <div class="col-md-6 mb-3">
                        <label for="firma_zusatz" class="form-label">Firma Zusatz</label>
                        <input type="text" 
                               class="form-control" 
                               id="firma_zusatz" 
                               name="firma_zusatz" 
                               value="{{ old('firma_zusatz', $profil->firma_zusatz) }}"
                               placeholder="z.B. GmbH, S.r.l.">
                    </div>

                    {{-- Geschäftsführer --}}
                    <div class="col-md-4 mb-3">
                        <label for="geschaeftsfuehrer" class="form-label">Geschäftsführer</label>
                        <input type="text" 
                               class="form-control" 
                               id="geschaeftsfuehrer" 
                               name="geschaeftsfuehrer" 
                               value="{{ old('geschaeftsfuehrer', $profil->geschaeftsfuehrer) }}">
                    </div>

                    {{-- Handelsregister --}}
                    <div class="col-md-4 mb-3">
                        <label for="handelsregister" class="form-label">Handelsregister</label>
                        <input type="text" 
                               class="form-control" 
                               id="handelsregister" 
                               name="handelsregister" 
                               value="{{ old('handelsregister', $profil->handelsregister) }}"
                               placeholder="z.B. HRB 12345">
                    </div>

                    {{-- Registergericht --}}
                    <div class="col-md-4 mb-3">
                        <label for="registergericht" class="form-label">Registergericht</label>
                        <input type="text" 
                               class="form-control" 
                               id="registergericht" 
                               name="registergericht" 
                               value="{{ old('registergericht', $profil->registergericht) }}"
                               placeholder="z.B. Bozen">
                    </div>
                </div>

                <hr class="my-4">
                <h6 class="text-muted mb-3"><i class="bi bi-geo-alt"></i> Adresse</h6>

                <div class="row">
                    {{-- Straße --}}
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

                    {{-- Hausnummer --}}
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

                    {{-- Adresszusatz --}}
                    <div class="col-md-6 mb-3">
                        <label for="adresszusatz" class="form-label">Adresszusatz</label>
                        <input type="text" 
                               class="form-control" 
                               id="adresszusatz" 
                               name="adresszusatz" 
                               value="{{ old('adresszusatz', $profil->adresszusatz) }}"
                               placeholder="z.B. 2. Stock, Gebäude B">
                    </div>

                    {{-- PLZ --}}
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

                    {{-- Ort --}}
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

                    {{-- Bundesland/Provinz --}}
                    <div class="col-md-3 mb-3">
                        <label for="bundesland" class="form-label">Bundesland/Provinz</label>
                        <input type="text" 
                               class="form-control" 
                               id="bundesland" 
                               name="bundesland" 
                               value="{{ old('bundesland', $profil->bundesland) }}"
                               placeholder="z.B. BZ">
                    </div>

                    {{-- Land --}}
                    <div class="col-md-3 mb-3">
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
                        <small class="text-muted">ISO-Code (2 Buchstaben)</small>
                    </div>
                </div>

                <hr class="my-4">
                <h6 class="text-muted mb-3"><i class="bi bi-telephone"></i> Kontakt</h6>

                <div class="row">
                    {{-- Telefon --}}
                    <div class="col-md-4 mb-3">
                        <label for="telefon" class="form-label">Telefon</label>
                        <input type="text" 
                               class="form-control" 
                               id="telefon" 
                               name="telefon" 
                               value="{{ old('telefon', $profil->telefon) }}"
                               placeholder="+39 0471 123456">
                    </div>

                    {{-- Telefon Mobil --}}
                    <div class="col-md-4 mb-3">
                        <label for="telefon_mobil" class="form-label">Mobil</label>
                        <input type="text" 
                               class="form-control" 
                               id="telefon_mobil" 
                               name="telefon_mobil" 
                               value="{{ old('telefon_mobil', $profil->telefon_mobil) }}"
                               placeholder="+39 333 1234567">
                    </div>

                    {{-- Fax --}}
                    <div class="col-md-4 mb-3">
                        <label for="fax" class="form-label">Fax</label>
                        <input type="text" 
                               class="form-control" 
                               id="fax" 
                               name="fax" 
                               value="{{ old('fax', $profil->fax) }}">
                    </div>

                    {{-- E-Mail --}}
                    <div class="col-md-4 mb-3">
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

                    {{-- E-Mail Buchhaltung --}}
                    <div class="col-md-4 mb-3">
                        <label for="email_buchhaltung" class="form-label">E-Mail Buchhaltung</label>
                        <input type="email" 
                               class="form-control" 
                               id="email_buchhaltung" 
                               name="email_buchhaltung" 
                               value="{{ old('email_buchhaltung', $profil->email_buchhaltung) }}">
                    </div>

                    {{-- Website --}}
                    <div class="col-md-4 mb-3">
                        <label for="website" class="form-label">Website</label>
                        <input type="url" 
                               class="form-control" 
                               id="website" 
                               name="website" 
                               value="{{ old('website', $profil->website) }}"
                               placeholder="https://www.beispiel.it">
                    </div>
                </div>
            </div>
        </div>

        {{-- =================================================================
             2. STEUERDATEN
        ================================================================= --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-percent"></i> Steuerdaten</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    {{-- Steuernummer --}}
                    <div class="col-md-6 mb-3">
                        <label for="steuernummer" class="form-label">Steuernummer</label>
                        <input type="text" 
                               class="form-control" 
                               id="steuernummer" 
                               name="steuernummer" 
                               value="{{ old('steuernummer', $profil->steuernummer) }}">
                    </div>

                    {{-- USt-ID --}}
                    <div class="col-md-6 mb-3">
                        <label for="umsatzsteuer_id" class="form-label">USt-IdNr. / P.IVA</label>
                        <input type="text" 
                               class="form-control" 
                               id="umsatzsteuer_id" 
                               name="umsatzsteuer_id" 
                               value="{{ old('umsatzsteuer_id', $profil->umsatzsteuer_id) }}"
                               placeholder="IT12345678901">
                    </div>
                </div>
            </div>
        </div>

        {{-- =================================================================
             3. BANKDATEN
        ================================================================= --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-bank"></i> Bankdaten</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    {{-- Bank Name --}}
                    <div class="col-md-6 mb-3">
                        <label for="bank_name" class="form-label">Bank</label>
                        <input type="text" 
                               class="form-control" 
                               id="bank_name" 
                               name="bank_name" 
                               value="{{ old('bank_name', $profil->bank_name) }}"
                               placeholder="Südtiroler Sparkasse">
                    </div>

                    {{-- Kontoinhaber --}}
                    <div class="col-md-6 mb-3">
                        <label for="kontoinhaber" class="form-label">Kontoinhaber</label>
                        <input type="text" 
                               class="form-control" 
                               id="kontoinhaber" 
                               name="kontoinhaber" 
                               value="{{ old('kontoinhaber', $profil->kontoinhaber) }}">
                    </div>

                    {{-- IBAN --}}
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

                    {{-- BIC --}}
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
             4. E-MAIL KONFIGURATION (SMTP)
        ================================================================= --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="bi bi-envelope"></i> E-Mail Versand (SMTP)</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    Konfigurieren Sie hier Ihren SMTP-Server für den normalen E-Mail-Versand.
                </div>

                <div class="row">
                    {{-- SMTP Host --}}
                    <div class="col-md-6 mb-3">
                        <label for="smtp_host" class="form-label">SMTP Server</label>
                        <input type="text" 
                               class="form-control" 
                               id="smtp_host" 
                               name="smtp_host" 
                               value="{{ old('smtp_host', $profil->smtp_host) }}"
                               placeholder="smtp.example.com">
                    </div>

                    {{-- SMTP Port --}}
                    <div class="col-md-3 mb-3">
                        <label for="smtp_port" class="form-label">Port</label>
                        <input type="number" 
                               class="form-control" 
                               id="smtp_port" 
                               name="smtp_port" 
                               value="{{ old('smtp_port', $profil->smtp_port ?? 587) }}"
                               placeholder="587">
                        <small class="text-muted">587 (TLS) oder 465 (SSL)</small>
                    </div>

                    {{-- SMTP Verschlüsselung --}}
                    <div class="col-md-3 mb-3">
                        <label for="smtp_verschluesselung" class="form-label">Verschlüsselung</label>
                        <select class="form-select" id="smtp_verschluesselung" name="smtp_verschluesselung">
                            @foreach($verschluesselungOptionen as $code => $label)
                                <option value="{{ $code }}" 
                                        {{ old('smtp_verschluesselung', $profil->smtp_verschluesselung ?? 'tls') == $code ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- SMTP Benutzername --}}
                    <div class="col-md-6 mb-3">
                        <label for="smtp_benutzername" class="form-label">Benutzername</label>
                        <input type="text" 
                               class="form-control" 
                               id="smtp_benutzername" 
                               name="smtp_benutzername" 
                               value="{{ old('smtp_benutzername', $profil->smtp_benutzername) }}"
                               placeholder="Meist die E-Mail-Adresse">
                    </div>

                    {{-- SMTP Passwort --}}
                    <div class="col-md-6 mb-3">
                        <label for="smtp_passwort" class="form-label">Passwort</label>
                        <input type="password" 
                               class="form-control" 
                               id="smtp_passwort" 
                               name="smtp_passwort" 
                               value="{{ old('smtp_passwort', $profil->smtp_passwort) }}"
                               placeholder="••••••••">
                        <small class="text-muted">Wird verschlüsselt gespeichert</small>
                    </div>
                </div>

                <hr class="my-4">
                <h6 class="text-muted mb-3"><i class="bi bi-send"></i> Absender-Einstellungen</h6>

                <div class="row">
                    {{-- Absender E-Mail --}}
                    <div class="col-md-6 mb-3">
                        <label for="email_absender" class="form-label">Absender E-Mail</label>
                        <input type="email" 
                               class="form-control" 
                               id="email_absender" 
                               name="email_absender" 
                               value="{{ old('email_absender', $profil->email_absender) }}"
                               placeholder="Falls anders als Haupt-E-Mail">
                    </div>

                    {{-- Absender Name --}}
                    <div class="col-md-6 mb-3">
                        <label for="email_absender_name" class="form-label">Absender Name</label>
                        <input type="text" 
                               class="form-control" 
                               id="email_absender_name" 
                               name="email_absender_name" 
                               value="{{ old('email_absender_name', $profil->email_absender_name) }}"
                               placeholder="Falls anders als Firmenname">
                    </div>

                    {{-- Antwort an --}}
                    <div class="col-md-4 mb-3">
                        <label for="email_antwort_an" class="form-label">Antwort an (Reply-To)</label>
                        <input type="email" 
                               class="form-control" 
                               id="email_antwort_an" 
                               name="email_antwort_an" 
                               value="{{ old('email_antwort_an', $profil->email_antwort_an) }}">
                    </div>

                    {{-- CC --}}
                    <div class="col-md-4 mb-3">
                        <label for="email_cc" class="form-label">CC (Kopie)</label>
                        <input type="text" 
                               class="form-control" 
                               id="email_cc" 
                               name="email_cc" 
                               value="{{ old('email_cc', $profil->email_cc) }}"
                               placeholder="Mehrere mit Komma trennen">
                    </div>

                    {{-- BCC --}}
                    <div class="col-md-4 mb-3">
                        <label for="email_bcc" class="form-label">BCC (Blindkopie)</label>
                        <input type="text" 
                               class="form-control" 
                               id="email_bcc" 
                               name="email_bcc" 
                               value="{{ old('email_bcc', $profil->email_bcc) }}"
                               placeholder="Mehrere mit Komma trennen">
                    </div>

                    {{-- E-Mail Signatur --}}
                    <div class="col-md-6 mb-3">
                        <label for="email_signatur" class="form-label">E-Mail Signatur</label>
                        <textarea class="form-control" 
                                  id="email_signatur" 
                                  name="email_signatur" 
                                  rows="4"
                                  placeholder="Wird unter jede E-Mail angehängt">{{ old('email_signatur', $profil->email_signatur) }}</textarea>
                    </div>

                    {{-- E-Mail Fußzeile --}}
                    <div class="col-md-6 mb-3">
                        <label for="email_fusszeile" class="form-label">E-Mail Fußzeile</label>
                        <textarea class="form-control" 
                                  id="email_fusszeile" 
                                  name="email_fusszeile" 
                                  rows="4"
                                  placeholder="Rechtliche Hinweise etc.">{{ old('email_fusszeile', $profil->email_fusszeile) }}</textarea>
                    </div>
                </div>

                {{-- SMTP Test Button --}}
                @if($profil->id && $profil->hatSmtpKonfiguration())
                <div class="mt-3">
                    <a href="{{ route('unternehmensprofil.smtp.testen') }}" 
                       class="btn btn-outline-info"
                       onclick="return confirm('Test-E-Mail an {{ $profil->email }} senden?')">
                        <i class="bi bi-send-check"></i> SMTP-Verbindung testen
                    </a>
                </div>
                @endif
            </div>
        </div>

        {{-- =================================================================
             5. PEC E-MAIL KONFIGURATION (Italien)
        ================================================================= --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">
                    <i class="bi bi-shield-check"></i> 
                    PEC E-Mail (Zertifizierte E-Mail Italien)
                </h5>
            </div>
            <div class="card-body">
                <div class="alert alert-success">
                    <i class="bi bi-info-circle"></i>
                    <strong>PEC (Posta Elettronica Certificata)</strong> ist die zertifizierte E-Mail für Italien.
                    Sie hat rechtliche Beweiskraft und wird für FatturaPA-Kommunikation verwendet.
                </div>

                {{-- PEC Aktivieren --}}
                <div class="mb-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" 
                               type="checkbox" 
                               id="pec_aktiv" 
                               name="pec_aktiv" 
                               value="1"
                               {{ old('pec_aktiv', $profil->pec_aktiv) ? 'checked' : '' }}>
                        <label class="form-check-label fw-bold" for="pec_aktiv">
                            PEC-Versand aktivieren
                        </label>
                    </div>
                </div>

                <div id="pec_config_fields">
                    <div class="row">
                        {{-- PEC SMTP Host --}}
                        <div class="col-md-6 mb-3">
                            <label for="pec_smtp_host" class="form-label">PEC SMTP Server</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="pec_smtp_host" 
                                   name="pec_smtp_host" 
                                   value="{{ old('pec_smtp_host', $profil->pec_smtp_host ?? 'smtps.pec.aruba.it') }}"
                                   placeholder="smtps.pec.aruba.it">
                            <small class="text-muted">Aruba: smtps.pec.aruba.it | Legalmail: sendm.cert.legalmail.it</small>
                        </div>

                        {{-- PEC SMTP Port --}}
                        <div class="col-md-3 mb-3">
                            <label for="pec_smtp_port" class="form-label">Port</label>
                            <input type="number" 
                                   class="form-control" 
                                   id="pec_smtp_port" 
                                   name="pec_smtp_port" 
                                   value="{{ old('pec_smtp_port', $profil->pec_smtp_port ?? 465) }}"
                                   placeholder="465">
                            <small class="text-muted">Meist 465 (SSL)</small>
                        </div>

                        {{-- PEC SMTP Verschlüsselung --}}
                        <div class="col-md-3 mb-3">
                            <label for="pec_smtp_verschluesselung" class="form-label">Verschlüsselung</label>
                            <select class="form-select" id="pec_smtp_verschluesselung" name="pec_smtp_verschluesselung">
                                @foreach($verschluesselungOptionen as $code => $label)
                                    <option value="{{ $code }}" 
                                            {{ old('pec_smtp_verschluesselung', $profil->pec_smtp_verschluesselung ?? 'ssl') == $code ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- PEC Benutzername --}}
                        <div class="col-md-6 mb-3">
                            <label for="pec_smtp_benutzername" class="form-label">PEC Benutzername</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="pec_smtp_benutzername" 
                                   name="pec_smtp_benutzername" 
                                   value="{{ old('pec_smtp_benutzername', $profil->pec_smtp_benutzername) }}"
                                   placeholder="Ihre PEC-Adresse">
                        </div>

                        {{-- PEC Passwort --}}
                        <div class="col-md-6 mb-3">
                            <label for="pec_smtp_passwort" class="form-label">PEC Passwort</label>
                            <input type="password" 
                                   class="form-control" 
                                   id="pec_smtp_passwort" 
                                   name="pec_smtp_passwort" 
                                   value="{{ old('pec_smtp_passwort', $profil->pec_smtp_passwort) }}"
                                   placeholder="••••••••">
                        </div>

                        {{-- PEC Absender E-Mail --}}
                        <div class="col-md-6 mb-3">
                            <label for="pec_email_absender" class="form-label">PEC Absender-Adresse</label>
                            <input type="email" 
                                   class="form-control" 
                                   id="pec_email_absender" 
                                   name="pec_email_absender" 
                                   value="{{ old('pec_email_absender', $profil->pec_email_absender) }}"
                                   placeholder="firma@pec.it">
                        </div>

                        {{-- PEC Absender Name --}}
                        <div class="col-md-6 mb-3">
                            <label for="pec_email_absender_name" class="form-label">PEC Absender Name</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="pec_email_absender_name" 
                                   name="pec_email_absender_name" 
                                   value="{{ old('pec_email_absender_name', $profil->pec_email_absender_name) }}">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- =================================================================
             6. FATTURAPA (ITALIENISCH)
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
                    {{-- Ragione Sociale --}}
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

                    {{-- Partita IVA --}}
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

                    {{-- Codice Fiscale --}}
                    <div class="col-md-3 mb-3">
                        <label for="codice_fiscale" class="form-label">Codice Fiscale</label>
                        <input type="text" 
                               class="form-control" 
                               id="codice_fiscale" 
                               name="codice_fiscale" 
                               value="{{ old('codice_fiscale', $profil->codice_fiscale) }}"
                               maxlength="16">
                    </div>

                    {{-- Regime Fiscale --}}
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

                    {{-- PEC E-Mail --}}
                    <div class="col-md-8 mb-3">
                        <label for="pec_email" class="form-label">PEC E-Mail (für FatturaPA)</label>
                        <input type="email" 
                               class="form-control" 
                               id="pec_email" 
                               name="pec_email" 
                               value="{{ old('pec_email', $profil->pec_email) }}"
                               placeholder="firma@pec.it">
                        <small class="text-muted">Zertifizierte E-Mail-Adresse (PEC) für SDI-Kommunikation</small>
                    </div>
                </div>

                <hr class="my-4">
                <h6 class="text-muted mb-3"><i class="bi bi-building"></i> REA & Gesellschaftsdaten</h6>

                <div class="row">
                    {{-- REA Ufficio --}}
                    <div class="col-md-2 mb-3">
                        <label for="rea_ufficio" class="form-label">REA Ufficio</label>
                        <input type="text" 
                               class="form-control" 
                               id="rea_ufficio" 
                               name="rea_ufficio" 
                               value="{{ old('rea_ufficio', $profil->rea_ufficio) }}"
                               placeholder="BZ"
                               maxlength="2">
                        <small class="text-muted">Provinzkürzel</small>
                    </div>

                    {{-- REA Numero --}}
                    <div class="col-md-4 mb-3">
                        <label for="rea_numero" class="form-label">REA Nummer</label>
                        <input type="text" 
                               class="form-control" 
                               id="rea_numero" 
                               name="rea_numero" 
                               value="{{ old('rea_numero', $profil->rea_numero) }}"
                               placeholder="123456"
                               maxlength="20">
                    </div>

                    {{-- Capitale Sociale --}}
                    <div class="col-md-3 mb-3">
                        <label for="capitale_sociale" class="form-label">Capitale Sociale (€)</label>
                        <input type="number" 
                               class="form-control" 
                               id="capitale_sociale" 
                               name="capitale_sociale" 
                               value="{{ old('capitale_sociale', $profil->capitale_sociale) }}"
                               step="0.01"
                               min="0"
                               placeholder="10000.00">
                    </div>

                    {{-- Stato Liquidazione --}}
                    <div class="col-md-3 mb-3">
                        <label for="stato_liquidazione" class="form-label">Stato Liquidazione</label>
                        <select class="form-select" id="stato_liquidazione" name="stato_liquidazione">
                            <option value="LN" {{ old('stato_liquidazione', $profil->stato_liquidazione ?? 'LN') == 'LN' ? 'selected' : '' }}>
                                LN - Non in liquidazione
                            </option>
                            <option value="LS" {{ old('stato_liquidazione', $profil->stato_liquidazione) == 'LS' ? 'selected' : '' }}>
                                LS - In liquidazione
                            </option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- =================================================================
             7. RECHNUNGS-EINSTELLUNGEN
        ================================================================= --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-receipt"></i> Rechnungs-Einstellungen</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    {{-- Rechnungsnummer Präfix --}}
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

                    {{-- Nummern-Länge --}}
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

                    {{-- Zahlungsziel --}}
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

                    {{-- Standard MwSt-Satz --}}
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

                    {{-- Währung --}}
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

                <hr class="my-4">
                <h6 class="text-muted mb-3"><i class="bi bi-file-text"></i> Rechnungstexte</h6>

                <div class="row">
                    {{-- Zahlungshinweis --}}
                    <div class="col-md-12 mb-3">
                        <label for="zahlungshinweis" class="form-label">Zahlungshinweis</label>
                        <textarea class="form-control" 
                                  id="zahlungshinweis" 
                                  name="zahlungshinweis" 
                                  rows="2"
                                  placeholder="z.B. Bitte überweisen Sie den Betrag innerhalb von 30 Tagen...">{{ old('zahlungshinweis', $profil->zahlungshinweis) }}</textarea>
                    </div>

                    {{-- Rechnung Einleitung --}}
                    <div class="col-md-6 mb-3">
                        <label for="rechnung_einleitung" class="form-label">Einleitungstext</label>
                        <textarea class="form-control" 
                                  id="rechnung_einleitung" 
                                  name="rechnung_einleitung" 
                                  rows="3"
                                  placeholder="Text am Anfang der Rechnung">{{ old('rechnung_einleitung', $profil->rechnung_einleitung) }}</textarea>
                    </div>

                    {{-- Rechnung Schlusstext --}}
                    <div class="col-md-6 mb-3">
                        <label for="rechnung_schlusstext" class="form-label">Schlusstext</label>
                        <textarea class="form-control" 
                                  id="rechnung_schlusstext" 
                                  name="rechnung_schlusstext" 
                                  rows="3"
                                  placeholder="Text am Ende der Rechnung">{{ old('rechnung_schlusstext', $profil->rechnung_schlusstext) }}</textarea>
                    </div>

                    {{-- Rechnung AGB --}}
                    <div class="col-md-12 mb-3">
                        <label for="rechnung_agb_text" class="form-label">AGB / Allgemeine Geschäftsbedingungen</label>
                        <textarea class="form-control" 
                                  id="rechnung_agb_text" 
                                  name="rechnung_agb_text" 
                                  rows="4"
                                  placeholder="AGB-Text für Rechnungen">{{ old('rechnung_agb_text', $profil->rechnung_agb_text) }}</textarea>
                    </div>
                </div>

                <hr class="my-4">
                <h6 class="text-muted mb-3"><i class="bi bi-shop"></i> Kleinunternehmer</h6>

                <div class="row">
                    {{-- Ist Kleinunternehmer --}}
                    <div class="col-md-6 mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   id="ist_kleinunternehmer" 
                                   name="ist_kleinunternehmer" 
                                   value="1"
                                   {{ old('ist_kleinunternehmer', $profil->ist_kleinunternehmer) ? 'checked' : '' }}>
                            <label class="form-check-label" for="ist_kleinunternehmer">
                                Kleinunternehmerregelung (§19 UStG / Regime Forfettario)
                            </label>
                        </div>
                    </div>

                    {{-- MwSt ausweisen --}}
                    <div class="col-md-6 mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   id="mwst_ausweisen" 
                                   name="mwst_ausweisen" 
                                   value="1"
                                   {{ old('mwst_ausweisen', $profil->mwst_ausweisen ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="mwst_ausweisen">
                                MwSt auf Rechnungen ausweisen
                            </label>
                        </div>
                    </div>

                    {{-- Kleinunternehmer Hinweis --}}
                    <div class="col-md-12 mb-3">
                        <label for="kleinunternehmer_hinweis" class="form-label">Kleinunternehmer-Hinweis</label>
                        <textarea class="form-control" 
                                  id="kleinunternehmer_hinweis" 
                                  name="kleinunternehmer_hinweis" 
                                  rows="2"
                                  placeholder="z.B. Kein Ausweis von Umsatzsteuer aufgrund der Anwendung der Kleinunternehmerregelung...">{{ old('kleinunternehmer_hinweis', $profil->kleinunternehmer_hinweis) }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- =================================================================
             8. SYSTEM-EINSTELLUNGEN
        ================================================================= --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-gear"></i> System-Einstellungen</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    {{-- Sprache --}}
                    <div class="col-md-4 mb-3">
                        <label for="sprache" class="form-label">Sprache</label>
                        <select class="form-select" id="sprache" name="sprache">
                            @foreach($spraachen as $code => $label)
                                <option value="{{ $code }}" 
                                        {{ old('sprache', $profil->sprache ?? 'de') == $code ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Zeitzone --}}
                    <div class="col-md-4 mb-3">
                        <label for="zeitzone" class="form-label">Zeitzone</label>
                        <input type="text" 
                               class="form-control" 
                               id="zeitzone" 
                               name="zeitzone" 
                               value="{{ old('zeitzone', $profil->zeitzone ?? 'Europe/Rome') }}"
                               placeholder="Europe/Rome">
                    </div>

                    {{-- Datumsformat --}}
                    <div class="col-md-4 mb-3">
                        <label for="datumsformat" class="form-label">Datumsformat</label>
                        <input type="text" 
                               class="form-control" 
                               id="datumsformat" 
                               name="datumsformat" 
                               value="{{ old('datumsformat', $profil->datumsformat ?? 'd.m.Y') }}"
                               placeholder="d.m.Y">
                        <small class="text-muted">d.m.Y = 31.12.2024</small>
                    </div>

                    {{-- Notizen --}}
                    <div class="col-md-12 mb-3">
                        <label for="notizen" class="form-label">Interne Notizen</label>
                        <textarea class="form-control" 
                                  id="notizen" 
                                  name="notizen" 
                                  rows="3"
                                  placeholder="Interne Notizen (werden nicht auf Dokumenten angezeigt)">{{ old('notizen', $profil->notizen) }}</textarea>
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
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-save"></i> Speichern
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </form>

</div>

{{-- JavaScript für PEC-Felder ein-/ausblenden --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    const pecAktivCheckbox = document.getElementById('pec_aktiv');
    const pecConfigFields = document.getElementById('pec_config_fields');
    
    function togglePecFields() {
        if (pecAktivCheckbox.checked) {
            pecConfigFields.style.display = 'block';
        } else {
            pecConfigFields.style.display = 'none';
        }
    }
    
    // Initial ausführen
    togglePecFields();
    
    // Bei Änderung
    pecAktivCheckbox.addEventListener('change', togglePecFields);
});
</script>
@endsection