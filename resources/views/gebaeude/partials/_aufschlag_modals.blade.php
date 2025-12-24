{{-- ⭐⭐⭐ WICHTIG: Diese Datei wird AUSSERHALB des Hauptformulars eingebunden! ⭐⭐⭐ --}}

@if(isset($gebaeude) && $gebaeude->id)
    @php
        $jahr = now()->year;
        $aufschlagProzent = $gebaeude->getAufschlagProzent($jahr);
        $hatIndividuell = $gebaeude->hatIndividuellenAufschlag();
        $globalerAufschlag = \App\Models\PreisAufschlag::getGlobalerAufschlag($jahr);
        $aktuellerAufschlag = $gebaeude->alleGebaeudeAufschlaege()->latest('gueltig_ab')->first();
    @endphp

    {{-- Modal: Setzen --}}
    <div class="modal fade" id="modalAufschlagSetzen" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('gebaeude.aufschlag.set', $gebaeude->id) }}">
                    @csrf
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">Individuellen Aufschlag festlegen</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            Aktuell: Globaler Aufschlag {{ number_format($globalerAufschlag, 2, ',', '.') }}%
                        </div>

                        <div class="mb-3">
                            <label for="prozent" class="form-label">Aufschlag in % <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="prozent" name="prozent" 
                                       step="0.01" min="-100" max="100" value="{{ old('prozent', $globalerAufschlag) }}" required>
                                <span class="input-group-text">%</span>
                            </div>
                            <small class="text-muted">Positiv = Aufschlag, Negativ = Rabatt</small>
                        </div>

                        <div class="mb-3">
                            <label for="grund" class="form-label">Begründung</label>
                            <input type="text" class="form-control" id="grund" name="grund" maxlength="255">
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="gueltig_ab" class="form-label">Gültig ab</label>
                                <input type="date" class="form-control" id="gueltig_ab" name="gueltig_ab" 
                                       value="{{ now()->format('Y-m-d') }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="gueltig_bis" class="form-label">Gültig bis</label>
                                <input type="date" class="form-control" id="gueltig_bis" name="gueltig_bis">
                                <small class="text-muted">Leer = unbegrenzt</small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                        <button type="submit" class="btn btn-primary">Speichern</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal: Bearbeiten --}}
    @if($hatIndividuell && $aktuellerAufschlag)
    <div class="modal fade" id="modalAufschlagBearbeiten" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('gebaeude.aufschlag.set', $gebaeude->id) }}">
                    @csrf
                    <div class="modal-header bg-warning">
                        <h5 class="modal-title">Aufschlag bearbeiten</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="prozent_edit" class="form-label">Aufschlag in % <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="prozent_edit" name="prozent" 
                                       step="0.01" min="-100" max="100" value="{{ old('prozent', $aktuellerAufschlag->prozent) }}" required>
                                <span class="input-group-text">%</span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="grund_edit" class="form-label">Begründung</label>
                            <input type="text" class="form-control" id="grund_edit" name="grund" 
                                   value="{{ $aktuellerAufschlag->grund }}" maxlength="255">
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="gueltig_ab_edit" class="form-label">Gültig ab</label>
                                <input type="date" class="form-control" id="gueltig_ab_edit" name="gueltig_ab" 
                                       value="{{ $aktuellerAufschlag->gueltig_ab->format('Y-m-d') }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="gueltig_bis_edit" class="form-label">Gültig bis</label>
                                <input type="date" class="form-control" id="gueltig_bis_edit" name="gueltig_bis" 
                                       value="@if($aktuellerAufschlag->gueltig_bis){{ $aktuellerAufschlag->gueltig_bis->format('Y-m-d') }}@endif">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                        <button type="submit" class="btn btn-warning">Speichern</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    {{-- Modal: Entfernen --}}
    @if($hatIndividuell)
    <div class="modal fade" id="modalAufschlagEntfernen" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('gebaeude.aufschlag.remove', $gebaeude->id) }}">
                    @csrf
                    @method('DELETE')
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">Aufschlag entfernen?</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            Der individuelle Aufschlag wird entfernt. Es gilt dann wieder der globale Standard.
                        </div>
                        <table class="table table-sm">
                            <tr>
                                <th>Aktuell:</th>
                                <td><span class="badge bg-warning text-dark">{{ number_format($aufschlagProzent, 2, ',', '.') }}%</span></td>
                            </tr>
                            <tr>
                                <th>Danach:</th>
                                <td><span class="badge bg-primary">{{ number_format($globalerAufschlag, 2, ',', '.') }}%</span></td>
                            </tr>
                        </table>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                        <button type="submit" class="btn btn-danger">Entfernen</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    {{-- Modal: Vorschau mit kumulativer Berechnung --}}
    <div class="modal fade" id="modalAufschlagVorschau" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">Preis-Vorschau mit kumulativen Erhöhungen</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <strong>Aktueller Aufschlag: {{ number_format($aufschlagProzent, 2, ',', '.') }}%</strong><br>
                        <small>Die Vorschau berücksichtigt kumulative Erhöhungen basierend auf basis_preis und basis_jahr jedes Artikels.</small>
                    </div>

                    @if($gebaeude->aktiveArtikel->isNotEmpty())
                        <div class="table-responsive">
                            <table class="table table-hover table-sm">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Artikel</th>
                                        <th class="text-center">Basis Jahr</th>
                                        <th class="text-end">Basis-Preis</th>
                                        <th class="text-end">Aktueller Preis</th>
                                        <th class="text-end">Kum. Faktor</th>
                                        <th class="text-end">Erhöhung</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php 
                                        $summeAktuell = 0;
                                        $aktuellesJahr = now()->year;
                                    @endphp
                                    @foreach($gebaeude->aktiveArtikel as $artikel)
                                        @php
                                            $basisPreis = (float) ($artikel->basis_preis ?? $artikel->einzelpreis);
                                            $basisJahr = (int) ($artikel->basis_jahr ?? $aktuellesJahr);
                                            
                                            // Kumulative Berechnung mit Gebäude-Methode
                                            $aktuellerPreis = $gebaeude->berechnePreisMitKumulativerErhoehung(
                                                $basisPreis, 
                                                $basisJahr, 
                                                $aktuellesJahr
                                            );
                                            
                                            // Faktor berechnen für Anzeige
                                            $faktor = $gebaeude->getKumulativerAufschlagFaktor($basisJahr, $aktuellesJahr);
                                            $prozentErhohung = ($faktor - 1) * 100;
                                            $differenz = $aktuellerPreis - $basisPreis;
                                            
                                            $summeAktuell += $aktuellerPreis * (float)$artikel->anzahl;
                                        @endphp
                                        <tr>
                                            <td>
                                                <strong>{{ $artikel->beschreibung }}</strong><br>
                                                <small class="text-muted">{{ $artikel->anzahl }}x Stück</small>
                                            </td>
                                            <td class="text-center">
                                                @if($basisJahr < $aktuellesJahr)
                                                    <span class="badge bg-warning text-dark">{{ $basisJahr }}</span>
                                                @else
                                                    <span class="badge bg-success">{{ $basisJahr }}</span>
                                                @endif
                                            </td>
                                            <td class="text-end">{{ number_format($basisPreis, 2, ',', '.') }} €</td>
                                            <td class="text-end"><strong>{{ number_format($aktuellerPreis, 2, ',', '.') }} €</strong></td>
                                            <td class="text-end">
                                                @if($faktor > 1)
                                                    <span class="text-success">×{{ number_format($faktor, 4, ',', '.') }}</span>
                                                @else
                                                    <span class="text-muted">×1,0000</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                @if($differenz > 0)
                                                    <span class="text-success">
                                                        +{{ number_format($differenz, 2, ',', '.') }} €
                                                        <small>(+{{ number_format($prozentErhohung, 2, ',', '.') }}%)</small>
                                                    </span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr class="fw-bold">
                                        <th colspan="3">Summe (Netto)</th>
                                        <th class="text-end">{{ number_format($summeAktuell, 2, ',', '.') }} €</th>
                                        <th colspan="2"></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <div class="alert alert-warning mt-3">
                            <i class="bi bi-info-circle"></i>
                            <strong>Legende:</strong>
                            <ul class="mb-0 mt-2">
                                <li><strong>Basis-Preis:</strong> Original-Preis ohne Erhöhungen</li>
                                <li><strong>Basis Jahr:</strong> Ab welchem Jahr dieser Preis gilt</li>
                                <li><strong>Kum. Faktor:</strong> Multiplikator durch alle Erhöhungen seit basis_jahr</li>
                                <li><strong>Aktueller Preis:</strong> Basis-Preis × Kumulativer Faktor</li>
                            </ul>
                        </div>
                    @else
                        <div class="alert alert-warning">Keine aktiven Artikel vorhanden.</div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Schließen</button>
                </div>
            </div>
        </div>
    </div>

@endif