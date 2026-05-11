{{-- resources/views/rechnung/partials/_modal_gutschrift.blade.php --}}
{{-- Modal zur Bestätigung der Gutschrift-Erstellung (Voll-Storno-Workflow) --}}

<div class="modal fade" id="modalGutschrift" tabindex="-1" aria-labelledby="modalGutschriftLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="modalGutschriftLabel">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    Rechnung stornieren (Voll-Gutschrift)
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Schließen"></button>
            </div>
            <div class="modal-body">

                {{-- ⭐ Achtung-Hinweis ganz oben --}}
                <div class="alert alert-danger mb-3">
                    <i class="bi bi-exclamation-octagon-fill"></i>
                    <strong>Achtung:</strong> Dies erzeugt eine <strong>Voll-Gutschrift</strong>
                    und <strong>storniert die Originalrechnung vollständig</strong>.
                    Dieser Vorgang kann nicht rückgängig gemacht werden.
                </div>

                {{-- Rechnungs-Info --}}
                <div class="card bg-light mb-3">
                    <div class="card-body py-2">
                        <div class="row small">
                            <div class="col-sm-6">
                                <strong>Original-Rechnung:</strong><br>
                                {{ $rechnung->rechnungsnummer }}
                                @if($rechnung->rechnungsdatum)
                                    <span class="text-muted">vom {{ $rechnung->rechnungsdatum->format('d.m.Y') }}</span>
                                @endif
                            </div>
                            <div class="col-sm-6">
                                <strong>Brutto-Betrag:</strong><br>
                                <span class="fw-bold">{{ number_format($rechnung->brutto_summe, 2, ',', '.') }} €</span>
                            </div>
                        </div>
                        @if($rechnung->geb_name)
                        <div class="mt-2 small text-muted">
                            <i class="bi bi-building"></i>
                            {{ $rechnung->geb_codex }} – {{ $rechnung->geb_name }}
                        </div>
                        @endif
                    </div>
                </div>

                <p class="fw-bold mb-2">Was passiert genau:</p>

                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <div class="card border-warning h-100">
                            <div class="card-header bg-warning-subtle py-1 small fw-bold">
                                <i class="bi bi-receipt-cutoff"></i> Neue Gutschrift
                            </div>
                            <div class="card-body py-2 small">
                                <ul class="mb-0 ps-3">
                                    <li>Neue Nummer (nächste freie {{ now()->year }})</li>
                                    <li>Datum: <strong>Heute</strong></li>
                                    <li>Typ: <strong>Gutschrift</strong> (TD04)</li>
                                    <li>Status: <strong class="text-success">bezahlt</strong> (verrechnet)</li>
                                    <li>Alle Positionen 1:1 kopiert</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-danger h-100">
                            <div class="card-header bg-danger-subtle py-1 small fw-bold">
                                <i class="bi bi-x-octagon"></i> Originalrechnung
                            </div>
                            <div class="card-body py-2 small">
                                <ul class="mb-0 ps-3">
                                    <li>Status: <strong class="text-danger">storniert</strong></li>
                                    <li>bezahlt_am: <strong>Heute</strong></li>
                                    <li>Offene Mahn-Entwürfe werden <strong>gelöscht</strong></li>
                                    <li>Versendete Mahnungen bleiben (Audit)</li>
                                    <li>Nicht mehr im Mahnlauf</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- FatturaPA-Hinweis --}}
                @if($rechnung->fattura_profile_id)
                <div class="alert alert-info py-2 small mb-3">
                    <i class="bi bi-info-circle"></i>
                    <strong>FatturaPA:</strong> Die Gutschrift wird als <code>TipoDocumento TD04</code>
                    (Nota di Credito) generiert und muss separat ans SdI übermittelt werden.
                    @if($rechnung->fattura_xml_status ?? null)
                    Die Originalrechnung wurde bereits übermittelt – das SdI behält den ursprünglichen Eingang;
                    die TD04 ist die buchhalterische Korrektur.
                    @endif
                </div>
                @endif

                {{-- Bestätigung per Checkbox --}}
                <div class="form-check border-top pt-3">
                    <input class="form-check-input" type="checkbox" id="gutschriftBestaetigung">
                    <label class="form-check-label" for="gutschriftBestaetigung">
                        Ich bestätige, dass die Rechnung
                        <strong>{{ $rechnung->rechnungsnummer }}</strong>
                        storniert und eine Voll-Gutschrift erstellt werden soll.
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg"></i> Abbrechen
                </button>
                <form action="{{ route('rechnung.gutschrift', $rechnung->id) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-danger" id="btnGutschriftBestaetigen" disabled>
                        <i class="bi bi-receipt-cutoff"></i> Rechnung stornieren &amp; Gutschrift erstellen
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkbox = document.getElementById('gutschriftBestaetigung');
    const btn      = document.getElementById('btnGutschriftBestaetigen');
    const modal    = document.getElementById('modalGutschrift');

    if (checkbox && btn) {
        checkbox.addEventListener('change', function() {
            btn.disabled = !this.checked;
        });
    }

    // Checkbox beim Schließen zurücksetzen
    if (modal) {
        modal.addEventListener('hidden.bs.modal', function() {
            if (checkbox) checkbox.checked = false;
            if (btn) btn.disabled = true;
        });
    }
});
</script>
@endpush
