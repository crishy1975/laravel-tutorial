{{-- ═══════════════════════════════════════════════════════════
     LOGO-VERWALTUNG
     ⭐ IMAGE FIX: asset() statt Storage::url() verwenden!
═══════════════════════════════════════════════════════════ --}}

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="bi bi-image"></i> Logo-Verwaltung
        </h5>
    </div>
    <div class="card-body">
        
        {{-- Haupt-Logo --}}
        <div class="mb-4 pb-4 border-bottom">
            <h6 class="fw-bold mb-3">Haupt-Logo</h6>
            
            <div class="row">
                <div class="col-md-6">
                    @if($profil && $profil->logo_pfad && Storage::disk('public')->exists($profil->logo_pfad))
                        <div class="mb-3">
                            <label class="form-label">Aktuelles Logo:</label>
                            <div class="border p-3 bg-light text-center">
                                {{-- ⭐ FIX: asset('storage/...') verwenden --}}
                                <img src="{{ asset('storage/' . $profil->logo_pfad) }}" 
                                     alt="Logo" 
                                     style="max-width: 200px; max-height: 100px;"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                <div style="display:none; color:red;">
                                    Fehler beim Laden. Pfad: {{ asset('storage/' . $profil->logo_pfad) }}
                                </div>
                            </div>
                            <small class="text-muted d-block mt-2">
                                Pfad: {{ $profil->logo_pfad }}
                            </small>
                        </div>
                        
                        <form method="POST" action="{{ route('unternehmensprofil.logo.loeschen') }}">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="typ" value="haupt">
                            <button type="submit" class="btn btn-sm btn-danger" 
                                    onclick="return confirm('Logo wirklich löschen?')">
                                <i class="bi bi-trash"></i> Logo löschen
                            </button>
                        </form>
                    @else
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> Kein Logo hochgeladen
                        </div>
                    @endif
                </div>
                
                <div class="col-md-6">
                    <form method="POST" 
                          action="{{ route('unternehmensprofil.logo.hochladen') }}" 
                          enctype="multipart/form-data">
                        @csrf
                        
                        <input type="hidden" name="typ" value="haupt">
                        
                        <div class="mb-3">
                            <label for="logo_haupt" class="form-label">
                                Neues Logo hochladen
                            </label>
                            <input type="file" 
                                   class="form-control @error('logo') is-invalid @enderror" 
                                   id="logo_haupt" 
                                   name="logo" 
                                   accept="image/*"
                                   required>
                            @error('logo')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">
                                Erlaubt: JPG, PNG, GIF, SVG | Max: 2 MB
                            </small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-upload"></i> Hochladen
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        {{-- Rechnungs-Logo (optional) --}}
        <div class="mb-4 pb-4 border-bottom">
            <h6 class="fw-bold mb-3">Rechnungs-Logo (optional)</h6>
            <p class="text-muted small">Separates Logo für Rechnungen. Falls leer, wird Haupt-Logo verwendet.</p>
            
            <div class="row">
                <div class="col-md-6">
                    @if($profil && $profil->logo_rechnung_pfad && Storage::disk('public')->exists($profil->logo_rechnung_pfad))
                        <div class="mb-3">
                            <label class="form-label">Aktuelles Rechnungs-Logo:</label>
                            <div class="border p-3 bg-light text-center">
                                <img src="{{ asset('storage/' . $profil->logo_rechnung_pfad) }}" 
                                     alt="Rechnungs-Logo" 
                                     style="max-width: 200px; max-height: 100px;">
                            </div>
                            <small class="text-muted d-block mt-2">
                                Pfad: {{ $profil->logo_rechnung_pfad }}
                            </small>
                        </div>
                        
                        <form method="POST" action="{{ route('unternehmensprofil.logo.loeschen') }}">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="typ" value="rechnung">
                            <button type="submit" class="btn btn-sm btn-danger" 
                                    onclick="return confirm('Rechnungs-Logo wirklich löschen?')">
                                <i class="bi bi-trash"></i> Logo löschen
                            </button>
                        </form>
                    @else
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> Kein Rechnungs-Logo hochgeladen
                        </div>
                    @endif
                </div>
                
                <div class="col-md-6">
                    <form method="POST" 
                          action="{{ route('unternehmensprofil.logo.hochladen') }}" 
                          enctype="multipart/form-data">
                        @csrf
                        
                        <input type="hidden" name="typ" value="rechnung">
                        
                        <div class="mb-3">
                            <label for="logo_rechnung" class="form-label">
                                Rechnungs-Logo hochladen
                            </label>
                            <input type="file" 
                                   class="form-control @error('logo') is-invalid @enderror" 
                                   id="logo_rechnung" 
                                   name="logo" 
                                   accept="image/*"
                                   required>
                            @error('logo')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-upload"></i> Hochladen
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        {{-- Email-Logo (optional) --}}
        <div class="mb-3">
            <h6 class="fw-bold mb-3">Email-Logo (optional)</h6>
            <p class="text-muted small">Separates Logo für E-Mails. Falls leer, wird Haupt-Logo verwendet.</p>
            
            <div class="row">
                <div class="col-md-6">
                    @if($profil && $profil->logo_email_pfad && Storage::disk('public')->exists($profil->logo_email_pfad))
                        <div class="mb-3">
                            <label class="form-label">Aktuelles Email-Logo:</label>
                            <div class="border p-3 bg-light text-center">
                                <img src="{{ asset('storage/' . $profil->logo_email_pfad) }}" 
                                     alt="Email-Logo" 
                                     style="max-width: 200px; max-height: 100px;">
                            </div>
                            <small class="text-muted d-block mt-2">
                                Pfad: {{ $profil->logo_email_pfad }}
                            </small>
                        </div>
                        
                        <form method="POST" action="{{ route('unternehmensprofil.logo.loeschen') }}">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="typ" value="email">
                            <button type="submit" class="btn btn-sm btn-danger" 
                                    onclick="return confirm('Email-Logo wirklich löschen?')">
                                <i class="bi bi-trash"></i> Logo löschen
                            </button>
                        </form>
                    @else
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> Kein Email-Logo hochgeladen
                        </div>
                    @endif
                </div>
                
                <div class="col-md-6">
                    <form method="POST" 
                          action="{{ route('unternehmensprofil.logo.hochladen') }}" 
                          enctype="multipart/form-data">
                        @csrf
                        
                        <input type="hidden" name="typ" value="email">
                        
                        <div class="mb-3">
                            <label for="logo_email" class="form-label">
                                Email-Logo hochladen
                            </label>
                            <input type="file" 
                                   class="form-control @error('logo') is-invalid @enderror" 
                                   id="logo_email" 
                                   name="logo" 
                                   accept="image/*"
                                   required>
                            @error('logo')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-upload"></i> Hochladen
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        {{-- Hinweise --}}
        <div class="alert alert-info mt-4">
            <h6 class="alert-heading">
                <i class="bi bi-info-circle"></i> Hinweise
            </h6>
            <ul class="mb-0 small">
                <li><strong>Format:</strong> JPG, PNG, GIF oder SVG</li>
                <li><strong>Größe:</strong> Maximal 2 MB</li>
                <li><strong>Empfehlung:</strong> PNG mit transparentem Hintergrund</li>
                <li><strong>Maße:</strong> Ca. 400x200 Pixel (wird automatisch skaliert)</li>
            </ul>
        </div>
        
    </div>
</div>