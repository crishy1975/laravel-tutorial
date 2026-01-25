{{-- resources/views/rechnung/partials/_modal_gutschrift.blade.php --}}
{{-- Modal zur Bestätigung der Gutschrift-Erstellung --}}

<div class="modal fade" id="modalGutschrift" tabindex="-1" aria-labelledby="modalGutschriftLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="modalGutschriftLabel">
                    <i class="bi bi-receipt-cutoff"></i> Gutschrift erstellen
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info mb-3">
                    <i class="bi bi-info-circle"></i>
                    <strong>Was passiert?</strong>
                </div>
                
                <p>Es wird eine <strong>neue Gutschrift</strong> mit folgenden Eigenschaften erstellt:</p>
                
                <ul class="mb-3">
                    <li>Neue Rechnungsnummer (nächste freie im aktuellen Jahr)</li>
                    <li>Rechnungsdatum: <strong>Heute</strong></li>
                    <li>Typ: <strong>Gutschrift</strong></li>
                    <li>Status: <strong>Entwurf</strong></li>
                </ul>
                
                <p class="mb-0">Folgende Daten werden <strong>1:1 kopiert</strong>:</p>
                <ul class="mb-3">
                    <li>Alle Positionen (Artikel, Mengen, Preise)</li>
                    <li>Rechnungsempfänger & Postadresse</li>
                    <li>FatturaPA-Einstellungen</li>
                    <li>Gebäude-Referenz</li>
                </ul>

                <div class="card bg-light">
                    <div class="card-body py-2">
                        <div class="row small">
                            <div class="col-6">
                                <strong>Original-Rechnung:</strong><br>
                                {{ $rechnung->rechnungsnummer }}
                            </div>
                            <div class="col-6">
                                <strong>Brutto-Betrag:</strong><br>
                                {{ number_format($rechnung->brutto_summe, 2, ',', '.') }} €
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg"></i> Abbrechen
                </button>
                <form action="{{ route('rechnung.gutschrift', $rechnung->id) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-receipt-cutoff"></i> Gutschrift erstellen
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
