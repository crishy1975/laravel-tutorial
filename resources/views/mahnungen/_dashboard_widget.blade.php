{{-- resources/views/mahnungen/_dashboard_widget.blade.php --}}
{{-- 
    Dieses Widget in das Dashboard einbinden:
    @include('mahnungen._dashboard_widget')
    
    Benötigt in Controller/ViewComposer:
    $mahnungStatistiken = app(App\Services\MahnungService::class)->getStatistiken();
    $bankAktualitaet = app(App\Services\MahnungService::class)->getBankAktualitaet();
--}}

@php
    // Falls nicht übergeben, selbst laden
    if (!isset($mahnungStatistiken)) {
        $mahnungService = app(\App\Services\MahnungService::class);
        $mahnungStatistiken = $mahnungService->getStatistiken();
        $bankAktualitaet = $mahnungService->getBankAktualitaet();
    }
    
    $hatHandlungsbedarf = $mahnungStatistiken['ueberfaellig_gesamt'] > 0;
    $hatEntwuerfe = $mahnungStatistiken['mahnungen_entwurf'] > 0;
@endphp

@if($hatHandlungsbedarf || $hatEntwuerfe)
    <div class="card border-danger mb-4">
        <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
            <div>
                <i class="bi bi-exclamation-triangle-fill"></i>
                <strong>Handlungsbedarf: Mahnwesen</strong>
            </div>
            <a href="{{ route('mahnungen.index') }}" class="btn btn-sm btn-light">
                <i class="bi bi-arrow-right"></i> Zum Mahnwesen
            </a>
        </div>
        <div class="card-body">
            {{-- Bank-Aktualitäts-Warnung --}}
            @if($bankAktualitaet['warnung'] ?? false)
                <div class="alert alert-warning mb-3">
                    <i class="bi bi-bank"></i>
                    <strong>Bank-Buchungen veraltet!</strong>
                    <span class="text-muted">({{ $bankAktualitaet['tage_alt'] }} Tage alt)</span>
                    <br>
                    <small>Bitte aktualisieren Sie die Buchungen vor dem Mahnlauf.</small>
                    <a href="{{ route('bank.import') }}" class="btn btn-sm btn-warning ms-2">
                        <i class="bi bi-upload"></i> Importieren
                    </a>
                </div>
            @endif

            <div class="row">
                {{-- Überfällige Rechnungen --}}
                <div class="col-md-6 mb-3">
                    <div class="d-flex align-items-center">
                        <div class="bg-danger bg-opacity-10 rounded-circle p-3 me-3">
                            <i class="bi bi-file-earmark-x fs-4 text-danger"></i>
                        </div>
                        <div>
                            <h4 class="mb-0 text-danger">{{ $mahnungStatistiken['ueberfaellig_gesamt'] }}</h4>
                            <small class="text-muted">Überfällige Rechnungen</small>
                            <div>
                                <strong>{{ number_format($mahnungStatistiken['ueberfaellig_betrag'], 2, ',', '.') }} €</strong>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Entwürfe bereit --}}
                @if($hatEntwuerfe)
                    <div class="col-md-6 mb-3">
                        <div class="d-flex align-items-center">
                            <div class="bg-warning bg-opacity-25 rounded-circle p-3 me-3">
                                <i class="bi bi-envelope fs-4 text-warning"></i>
                            </div>
                            <div>
                                <h4 class="mb-0 text-warning">{{ $mahnungStatistiken['mahnungen_entwurf'] }}</h4>
                                <small class="text-muted">Mahnungen bereit zum Versand</small>
                                <div>
                                    <a href="{{ route('mahnungen.versand') }}" class="btn btn-sm btn-warning">
                                        Jetzt versenden
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Nach Mahnstufe aufschlüsseln --}}
            @if($hatHandlungsbedarf)
                <div class="mt-3 pt-3 border-top">
                    <small class="text-muted d-block mb-2">Überfällig nach Stufe:</small>
                    <div class="d-flex gap-2 flex-wrap">
                        @if(($mahnungStatistiken['nach_stufe'][0] ?? 0) > 0)
                            <span class="badge bg-info">
                                <i class="bi bi-bell"></i> Erinnerung: {{ $mahnungStatistiken['nach_stufe'][0] }}
                            </span>
                        @endif
                        @if(($mahnungStatistiken['nach_stufe'][1] ?? 0) > 0)
                            <span class="badge bg-warning text-dark">
                                <i class="bi bi-exclamation-circle"></i> 1. Mahnung: {{ $mahnungStatistiken['nach_stufe'][1] }}
                            </span>
                        @endif
                        @if(($mahnungStatistiken['nach_stufe'][2] ?? 0) > 0)
                            <span class="badge" style="background-color: #fd7e14;">
                                <i class="bi bi-exclamation-triangle"></i> 2. Mahnung: {{ $mahnungStatistiken['nach_stufe'][2] }}
                            </span>
                        @endif
                        @if(($mahnungStatistiken['nach_stufe'][3] ?? 0) > 0)
                            <span class="badge bg-danger">
                                <i class="bi bi-exclamation-octagon"></i> Letzte: {{ $mahnungStatistiken['nach_stufe'][3] }}
                            </span>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Warnung: Keine E-Mail --}}
            @if(($mahnungStatistiken['ohne_email'] ?? 0) > 0)
                <div class="mt-3 pt-3 border-top">
                    <span class="badge bg-secondary">
                        <i class="bi bi-mailbox"></i> {{ $mahnungStatistiken['ohne_email'] }} ohne E-Mail-Adresse (Postversand nötig)
                    </span>
                </div>
            @endif

            {{-- Aktions-Buttons --}}
            <div class="mt-3 pt-3 border-top d-flex gap-2">
                @if(!($bankAktualitaet['warnung'] ?? false))
                    <a href="{{ route('mahnungen.mahnlauf') }}" class="btn btn-danger">
                        <i class="bi bi-play-circle"></i> Mahnlauf starten
                    </a>
                @endif
                <a href="{{ route('mahnungen.historie') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-clock-history"></i> Historie
                </a>
            </div>
        </div>
    </div>
@endif
