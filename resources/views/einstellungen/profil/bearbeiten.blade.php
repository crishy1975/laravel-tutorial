@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    
    {{-- Header --}}
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-0">
                <i class="bi bi-building"></i>
                Unternehmensprofil bearbeiten
            </h1>
            <p class="text-muted">Zentrale Verwaltung aller Firmeneinstellungen</p>
        </div>
    </div>

    {{-- Success/Error Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle"></i>
            <strong>Bitte Fehler korrigieren:</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Hauptformular --}}
    <form method="POST" action="{{ route('unternehmensprofil.speichern') }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        {{-- Tab Navigation --}}
        <ul class="nav nav-tabs mb-4" id="profilTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="firma-tab" data-bs-toggle="tab" data-bs-target="#firma" type="button">
                    <i class="bi bi-building"></i> Firmendaten
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="kontakt-tab" data-bs-toggle="tab" data-bs-target="#kontakt" type="button">
                    <i class="bi bi-telephone"></i> Kontakt & Bank
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="email-tab" data-bs-toggle="tab" data-bs-target="#email" type="button">
                    <i class="bi bi-envelope"></i> E-Mail
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="pec-tab" data-bs-toggle="tab" data-bs-target="#pec" type="button">
                    <i class="bi bi-shield-check"></i> PEC
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="design-tab" data-bs-toggle="tab" data-bs-target="#design" type="button">
                    <i class="bi bi-palette"></i> Design
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="rechnung-tab" data-bs-toggle="tab" data-bs-target="#rechnung" type="button">
                    <i class="bi bi-receipt"></i> Rechnungen
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="fatturapa-tab" data-bs-toggle="tab" data-bs-target="#fatturapa" type="button">
                    <i class="bi bi-file-earmark-text"></i> FatturaPA
                </button>
            </li>
        </ul>

        {{-- Tab Content --}}
        <div class="tab-content" id="profilTabsContent">
            
            {{-- TAB 1: FIRMENDATEN --}}
            <div class="tab-pane fade show active" id="firma" role="tabpanel">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-building"></i> Firmendaten</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <label class="form-label">Firmenname <span class="text-danger">*</span></label>
                                <input type="text" name="firmenname" class="form-control @error('firmenname') is-invalid @enderror" 
                                       value="{{ old('firmenname', $profil->firmenname) }}" required>
                                @error('firmenname')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Zusatz (z.B. GmbH, SRL)</label>
                                <input type="text" name="firma_zusatz" class="form-control" 
                                       value="{{ old('firma_zusatz', $profil->firma_zusatz) }}">
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label class="form-label">Geschäftsführer</label>
                                <input type="text" name="geschaeftsfuehrer" class="form-control" 
                                       value="{{ old('geschaeftsfuehrer', $profil->geschaeftsfuehrer) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Handelsregister</label>
                                <input type="text" name="handelsregister" class="form-control" 
                                       value="{{ old('handelsregister', $profil->handelsregister) }}">
                            </div>
                        </div>

                        <hr class="my-4">
                        <h6 class="mb-3"><i class="bi bi-geo-alt"></i> Adresse</h6>

                        <div class="row">
                            <div class="col-md-8">
                                <label class="form-label">Straße <span class="text-danger">*</span></label>
                                <input type="text" name="strasse" class="form-control @error('strasse') is-invalid @enderror" 
                                       value="{{ old('strasse', $profil->strasse) }}" required>
                                @error('strasse')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Hausnummer <span class="text-danger">*</span></label>
                                <input type="text" name="hausnummer" class="form-control @error('hausnummer') is-invalid @enderror" 
                                       value="{{ old('hausnummer', $profil->hausnummer) }}" required>
                                @error('hausnummer')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-3">
                                <label class="form-label">PLZ <span class="text-danger">*</span></label>
                                <input type="text" name="postleitzahl" class="form-control @error('postleitzahl') is-invalid @enderror" 
                                       value="{{ old('postleitzahl', $profil->postleitzahl) }}" required>
                                @error('postleitzahl')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Ort <span class="text-danger">*</span></label>
                                <input type="text" name="ort" class="form-control @error('ort') is-invalid @enderror" 
                                       value="{{ old('ort', $profil->ort) }}" required>
                                @error('ort')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Provinz</label>
                                <input type="text" name="bundesland" class="form-control" maxlength="2"
                                       value="{{ old('bundesland', $profil->bundesland) }}" placeholder="MI">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Land <span class="text-danger">*</span></label>
                                <input type="text" name="land" class="form-control" maxlength="2"
                                       value="{{ old('land', $profil->land ?? 'IT') }}" required>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-12">
                                <label class="form-label">Adresszusatz</label>
                                <input type="text" name="adresszusatz" class="form-control" 
                                       value="{{ old('adresszusatz', $profil->adresszusatz) }}" 
                                       placeholder="z.B. 2. Stock, Hinterhof">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- TAB 2: KONTAKT & BANK --}}
            <div class="tab-pane fade" id="kontakt" role="tabpanel">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-telephone"></i> Kontakt & Bankdaten</h5>
                    </div>
                    <div class="card-body">
                        <h6 class="mb-3"><i class="bi bi-telephone"></i> Kontaktdaten</h6>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <label class="form-label">Telefon</label>
                                <input type="text" name="telefon" class="form-control" 
                                       value="{{ old('telefon', $profil->telefon) }}" placeholder="+39 02 12345678">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Mobil</label>
                                <input type="text" name="telefon_mobil" class="form-control" 
                                       value="{{ old('telefon_mobil', $profil->telefon_mobil) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Fax</label>
                                <input type="text" name="fax" class="form-control" 
                                       value="{{ old('fax', $profil->fax) }}">
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label class="form-label">E-Mail <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" 
                                       value="{{ old('email', $profil->email) }}" required>
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">E-Mail Buchhaltung</label>
                                <input type="email" name="email_buchhaltung" class="form-control" 
                                       value="{{ old('email_buchhaltung', $profil->email_buchhaltung) }}">
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-12">
                                <label class="form-label">Webseite</label>
                                <input type="url" name="website" class="form-control" 
                                       value="{{ old('website', $profil->website) }}" placeholder="https://www.firma.it">
                            </div>
                        </div>

                        <hr class="my-4">
                        <h6 class="mb-3"><i class="bi bi-receipt"></i> Steuerdaten</h6>

                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">Steuernummer</label>
                                <input type="text" name="steuernummer" class="form-control" 
                                       value="{{ old('steuernummer', $profil->steuernummer) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">USt-IdNr. / Partita IVA</label>
                                <input type="text" name="umsatzsteuer_id" class="form-control" 
                                       value="{{ old('umsatzsteuer_id', $profil->umsatzsteuer_id) }}">
                            </div>
                        </div>

                        <hr class="my-4">
                        <h6 class="mb-3"><i class="bi bi-bank"></i> Bankdaten</h6>

                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">IBAN</label>
                                <input type="text" name="iban" class="form-control" 
                                       value="{{ old('iban', $profil->iban) }}" placeholder="IT60X0542811101000000123456">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">BIC/SWIFT</label>
                                <input type="text" name="bic" class="form-control" 
                                       value="{{ old('bic', $profil->bic) }}" placeholder="UNCRITMM">
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label class="form-label">Bank Name</label>
                                <input type="text" name="bank_name" class="form-control" 
                                       value="{{ old('bank_name', $profil->bank_name) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Kontoinhaber (falls abweichend)</label>
                                <input type="text" name="kontoinhaber" class="form-control" 
                                       value="{{ old('kontoinhaber', $profil->kontoinhaber) }}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- TAB 3: E-MAIL (Normal) --}}
            <div class="tab-pane fade" id="email" role="tabpanel">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-envelope"></i> E-Mail Versand (Normal)</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            Für normale E-Mails (Angebote, Bestätigungen, etc.)
                        </div>

                        <h6 class="mb-3">SMTP Server</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">SMTP Host</label>
                                <input type="text" name="smtp_host" class="form-control" 
                                       value="{{ old('smtp_host', $profil->smtp_host) }}" placeholder="smtp.gmail.com">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Port</label>
                                <input type="number" name="smtp_port" class="form-control" 
                                       value="{{ old('smtp_port', $profil->smtp_port ?? 587) }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Verschlüsselung</label>
                                <select name="smtp_verschluesselung" class="form-select">
                                    <option value="tls" {{ old('smtp_verschluesselung', $profil->smtp_verschluesselung) == 'tls' ? 'selected' : '' }}>TLS</option>
                                    <option value="ssl" {{ old('smtp_verschluesselung', $profil->smtp_verschluesselung) == 'ssl' ? 'selected' : '' }}>SSL</option>
                                    <option value="none" {{ old('smtp_verschluesselung', $profil->smtp_verschluesselung) == 'none' ? 'selected' : '' }}>Keine</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label class="form-label">Benutzername</label>
                                <input type="text" name="smtp_benutzername" class="form-control" 
                                       value="{{ old('smtp_benutzername', $profil->smtp_benutzername) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Passwort</label>
                                <input type="password" name="smtp_passwort" class="form-control" 
                                       placeholder="••••••••">
                                <small class="text-muted">Leer lassen, um Passwort nicht zu ändern</small>
                            </div>
                        </div>

                        <hr class="my-4">
                        <h6 class="mb-3">Absender</h6>

                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">Absender E-Mail</label>
                                <input type="email" name="email_absender" class="form-control" 
                                       value="{{ old('email_absender', $profil->email_absender) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Absender Name</label>
                                <input type="text" name="email_absender_name" class="form-control" 
                                       value="{{ old('email_absender_name', $profil->email_absender_name) }}">
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-12">
                                <label class="form-label">E-Mail Signatur</label>
                                <textarea name="email_signatur" class="form-control" rows="4">{{ old('email_signatur', $profil->email_signatur) }}</textarea>
                            </div>
                        </div>

                        <div class="mt-3">
                            <button type="button" class="btn btn-outline-info" onclick="testSmtp('normal')">
                                <i class="bi bi-send"></i> SMTP testen
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- TAB 4: PEC E-MAIL --}}
            <div class="tab-pane fade" id="pec" role="tabpanel">
                <div class="card shadow-sm">
                    <div class="card-header bg-warning">
                        <h5 class="mb-0"><i class="bi bi-shield-check"></i> PEC E-Mail Versand (Zertifiziert)</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i>
                            Für rechtsgültige E-Mails (FatturaPA, offizielle Kommunikation)
                        </div>

                        <div class="form-check form-switch mb-4">
                            <input class="form-check-input" type="checkbox" name="pec_aktiv" id="pec_aktiv" 
                                   value="1" {{ old('pec_aktiv', $profil->pec_aktiv) ? 'checked' : '' }}>
                            <label class="form-check-label" for="pec_aktiv">
                                <strong>PEC E-Mail-Versand aktivieren</strong>
                            </label>
                        </div>

                        <h6 class="mb-3">PEC SMTP Server</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">PEC SMTP Host</label>
                                <input type="text" name="pec_smtp_host" class="form-control" 
                                       value="{{ old('pec_smtp_host', $profil->pec_smtp_host) }}" placeholder="smtp.pec.aruba.it">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Port</label>
                                <input type="number" name="pec_smtp_port" class="form-control" 
                                       value="{{ old('pec_smtp_port', $profil->pec_smtp_port ?? 465) }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Verschlüsselung</label>
                                <select name="pec_smtp_verschluesselung" class="form-select">
                                    <option value="ssl" {{ old('pec_smtp_verschluesselung', $profil->pec_smtp_verschluesselung ?? 'ssl') == 'ssl' ? 'selected' : '' }}>SSL</option>
                                    <option value="tls" {{ old('pec_smtp_verschluesselung', $profil->pec_smtp_verschluesselung) == 'tls' ? 'selected' : '' }}>TLS</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label class="form-label">PEC Benutzername</label>
                                <input type="text" name="pec_smtp_benutzername" class="form-control" 
                                       value="{{ old('pec_smtp_benutzername', $profil->pec_smtp_benutzername) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">PEC Passwort</label>
                                <input type="password" name="pec_smtp_passwort" class="form-control" 
                                       placeholder="••••••••">
                                <small class="text-muted">Leer lassen, um Passwort nicht zu ändern</small>
                            </div>
                        </div>

                        <hr class="my-4">
                        <h6 class="mb-3">PEC Absender</h6>

                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">PEC Absender E-Mail</label>
                                <input type="email" name="pec_email_absender" class="form-control" 
                                       value="{{ old('pec_email_absender', $profil->pec_email_absender) }}" placeholder="firma@pec.it">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">PEC Absender Name</label>
                                <input type="text" name="pec_email_absender_name" class="form-control" 
                                       value="{{ old('pec_email_absender_name', $profil->pec_email_absender_name) }}">
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-12">
                                <label class="form-label">PEC E-Mail Signatur</label>
                                <textarea name="pec_email_signatur" class="form-control" rows="4">{{ old('pec_email_signatur', $profil->pec_email_signatur) }}</textarea>
                            </div>
                        </div>

                        <div class="mt-3">
                            <button type="button" class="btn btn-outline-warning" onclick="testSmtp('pec')">
                                <i class="bi bi-send"></i> PEC SMTP testen
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- TAB 5: DESIGN --}}
            <div class="tab-pane fade" id="design" role="tabpanel">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-palette"></i> PDF & Briefkopf Design</h5>
                    </div>
                    <div class="card-body">
                        <h6 class="mb-3">Logo</h6>
                        
                        @if($profil->hatLogo())
                            <div class="mb-3">
                                <img src="{{ $profil->logo_url }}" alt="Logo" style="max-width: 300px;">
                            </div>
                        @endif

                        <div class="mb-3">
                            <label class="form-label">Logo hochladen</label>
                            <input type="file" name="logo" class="form-control" accept="image/*">
                            <small class="text-muted">PNG, JPG oder SVG</small>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">Logo Breite (px)</label>
                                <input type="number" name="logo_breite" class="form-control" 
                                       value="{{ old('logo_breite', $profil->logo_breite ?? 200) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Logo Höhe (px)</label>
                                <input type="number" name="logo_hoehe" class="form-control" 
                                       value="{{ old('logo_hoehe', $profil->logo_hoehe ?? 80) }}">
                            </div>
                        </div>

                        <hr class="my-4">
                        <h6 class="mb-3">Farben</h6>

                        <div class="row">
                            <div class="col-md-4">
                                <label class="form-label">Primärfarbe</label>
                                <input type="color" name="farbe_primaer" class="form-control form-control-color" 
                                       value="{{ old('farbe_primaer', $profil->farbe_primaer ?? '#003366') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Sekundärfarbe</label>
                                <input type="color" name="farbe_sekundaer" class="form-control form-control-color" 
                                       value="{{ old('farbe_sekundaer', $profil->farbe_sekundaer ?? '#666666') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Akzentfarbe</label>
                                <input type="color" name="farbe_akzent" class="form-control form-control-color" 
                                       value="{{ old('farbe_akzent', $profil->farbe_akzent ?? '#0066CC') }}">
                            </div>
                        </div>

                        <hr class="my-4">
                        <h6 class="mb-3">Briefkopf</h6>

                        <div class="row">
                            <div class="col-md-12">
                                <label class="form-label">Briefkopf Text</label>
                                <textarea name="briefkopf_text" class="form-control" rows="3">{{ old('briefkopf_text', $profil->briefkopf_text) }}</textarea>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-12">
                                <label class="form-label">Fußzeile</label>
                                <textarea name="fusszeile_text" class="form-control" rows="3">{{ old('fusszeile_text', $profil->fusszeile_text) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- TAB 6: RECHNUNGEN --}}
            <div class="tab-pane fade" id="rechnung" role="tabpanel">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-receipt"></i> Rechnungs-Einstellungen</h5>
                    </div>
                    <div class="card-body">
                        <h6 class="mb-3">Nummerierung</h6>

                        <div class="row">
                            <div class="col-md-4">
                                <label class="form-label">Präfix</label>
                                <input type="text" name="rechnungsnummer_praefix" class="form-control" 
                                       value="{{ old('rechnungsnummer_praefix', $profil->rechnungsnummer_praefix ?? 'RE-') }}">
                                <small class="text-muted">z.B. "RE-"</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Startjahr</label>
                                <input type="number" name="rechnungsnummer_startjahr" class="form-control" 
                                       value="{{ old('rechnungsnummer_startjahr', $profil->rechnungsnummer_startjahr ?? date('Y')) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Länge Laufnummer</label>
                                <input type="number" name="rechnungsnummer_laenge" class="form-control" 
                                       value="{{ old('rechnungsnummer_laenge', $profil->rechnungsnummer_laenge ?? 5) }}">
                                <small class="text-muted">5 = 00001</small>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-12">
                                <div class="alert alert-info">
                                    Beispiel: <strong>{{ $profil->rechnungsnummer_praefix ?? 'RE-' }}{{ date('Y') }}/00042</strong>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">
                        <h6 class="mb-3">Zahlungsbedingungen</h6>

                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">Zahlungsziel (Tage)</label>
                                <input type="number" name="zahlungsziel_tage" class="form-control" 
                                       value="{{ old('zahlungsziel_tage', $profil->zahlungsziel_tage ?? 30) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Standard MwSt-Satz (%)</label>
                                <input type="number" step="0.01" name="standard_mwst_satz" class="form-control" 
                                       value="{{ old('standard_mwst_satz', $profil->standard_mwst_satz ?? 22.00) }}">
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-12">
                                <label class="form-label">Zahlungshinweis</label>
                                <textarea name="zahlungshinweis" class="form-control" rows="3">{{ old('zahlungshinweis', $profil->zahlungshinweis) }}</textarea>
                                <small class="text-muted">Erscheint auf der Rechnung</small>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-12">
                                <label class="form-label">Einleitungstext</label>
                                <textarea name="rechnung_einleitung" class="form-control" rows="2">{{ old('rechnung_einleitung', $profil->rechnung_einleitung) }}</textarea>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-12">
                                <label class="form-label">Schlusstext</label>
                                <textarea name="rechnung_schlusstext" class="form-control" rows="2">{{ old('rechnung_schlusstext', $profil->rechnung_schlusstext) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- TAB 7: FATTURAPA --}}
            <div class="tab-pane fade" id="fatturapa" role="tabpanel">
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="bi bi-file-earmark-text"></i> FatturaPA (Italienisch)</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            Für elektronische Rechnungen in Italien (FatturaPA XML)
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">Ragione Sociale</label>
                                <input type="text" name="ragione_sociale" class="form-control" 
                                       value="{{ old('ragione_sociale', $profil->ragione_sociale) }}">
                                <small class="text-muted">Offizielle Firmenbezeichnung (IT)</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Partita IVA</label>
                                <input type="text" name="partita_iva" class="form-control" maxlength="11"
                                       value="{{ old('partita_iva', $profil->partita_iva) }}">
                                <small class="text-muted">11 Ziffern (ohne "IT")</small>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label class="form-label">Codice Fiscale</label>
                                <input type="text" name="codice_fiscale" class="form-control" maxlength="16"
                                       value="{{ old('codice_fiscale', $profil->codice_fiscale) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Regime Fiscale</label>
                                <select name="regime_fiscale" class="form-select">
                                    <option value="">-- Bitte wählen --</option>
                                    @foreach($regimeFiscaleOptionen ?? [] as $code => $label)
                                        <option value="{{ $code }}" {{ old('regime_fiscale', $profil->regime_fiscale) == $code ? 'selected' : '' }}>
                                            {{ $code }} - {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-12">
                                <label class="form-label">PEC E-Mail (zertifiziert)</label>
                                <input type="email" name="pec_email" class="form-control" 
                                       value="{{ old('pec_email', $profil->pec_email) }}" placeholder="firma@pec.it">
                                <small class="text-muted">Zertifizierte E-Mail für FatturaPA</small>
                            </div>
                        </div>

                        <hr class="my-4">
                        <h6 class="mb-3">REA-Daten (Registro Imprese)</h6>

                        <div class="row">
                            <div class="col-md-3">
                                <label class="form-label">REA Ufficio</label>
                                <input type="text" name="rea_ufficio" class="form-control" maxlength="2"
                                       value="{{ old('rea_ufficio', $profil->rea_ufficio) }}" placeholder="MI">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">REA Numero</label>
                                <input type="text" name="rea_numero" class="form-control" 
                                       value="{{ old('rea_numero', $profil->rea_numero) }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Capitale Sociale (€)</label>
                                <input type="number" step="0.01" name="capitale_sociale" class="form-control" 
                                       value="{{ old('capitale_sociale', $profil->capitale_sociale) }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Liquidazione</label>
                                <select name="stato_liquidazione" class="form-select">
                                    <option value="LN" {{ old('stato_liquidazione', $profil->stato_liquidazione ?? 'LN') == 'LN' ? 'selected' : '' }}>
                                        Nicht in Liquidation
                                    </option>
                                    <option value="LS" {{ old('stato_liquidazione', $profil->stato_liquidazione) == 'LS' ? 'selected' : '' }}>
                                        In Liquidation
                                    </option>
                                </select>
                            </div>
                        </div>

                        @if($profil->istFatturapaKonfiguriert())
                            <div class="alert alert-success mt-4">
                                <i class="bi bi-check-circle"></i> FatturaPA ist vollständig konfiguriert!
                            </div>
                        @else
                            <div class="alert alert-warning mt-4">
                                <i class="bi bi-exclamation-triangle"></i>
                                <strong>Fehlende Felder:</strong>
                                <ul class="mb-0 mt-2">
                                    @foreach($profil->fehlendeFelderFatturaPA() as $feld => $label)
                                        <li>{{ $label }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

        </div>

        {{-- Speichern Button (fixiert unten) --}}
        <div class="fixed-bottom bg-white border-top p-3 shadow">
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        @if($profil->istVollstaendig())
                            <span class="badge bg-success">
                                <i class="bi bi-check-circle"></i> Profil vollständig
                            </span>
                        @else
                            <span class="badge bg-warning text-dark">
                                <i class="bi bi-exclamation-triangle"></i> Unvollständig
                            </span>
                        @endif
                    </div>
                    <div>
                        <a href="{{ route('unternehmensprofil.index') }}" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Abbrechen
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Speichern
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Platzhalter für fixierten Button --}}
        <div style="height: 80px;"></div>

    </form>
</div>

@push('scripts')
<script>
function testSmtp(typ) {
    if(confirm('Möchten Sie eine Test-E-Mail versenden?')) {
        fetch('{{ route("unternehmensprofil.smtp.testen") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ typ: typ })
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                alert('Test-E-Mail erfolgreich versendet!');
            } else {
                alert('Fehler: ' + (data.message || 'Unbekannter Fehler'));
            }
        })
        .catch(error => {
            alert('Fehler beim Versenden: ' + error);
        });
    }
}
</script>
@endpush

@endsection