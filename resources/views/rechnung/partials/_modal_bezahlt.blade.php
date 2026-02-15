{{-- resources/views/rechnung/partials/_modal_bezahlt.blade.php --}}
{{-- ⭐ Modal für "Rechnung ist bezahlt" mit Pflicht-Begründung und Vorschlägen --}}

<div class="modal fade" id="modalBezahlt" tabindex="-1" aria-labelledby="modalBezahltLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('rechnung.update', $rechnung->id) }}" id="formBezahlt">
                @csrf
                @method('PUT')
                
                {{-- Hidden Inputs für Zahlungsstatus --}}
                <input type="hidden" name="zahlungsbedingungen" value="bezahlt">
                <input type="hidden" name="status" value="paid">
                <input type="hidden" name="_bezahlt_aktion" value="1">
                
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="modalBezahltLabel">
                        <i class="bi bi-check-circle-fill"></i>
                        Rechnung als bezahlt markieren
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Schließen"></button>
                </div>
                
                <div class="modal-body">
                    {{-- Rechnungsinfo --}}
                    <div class="card bg-light mb-3">
                        <div class="card-body py-2">
                            <div class="row small">
                                <div class="col-6">
                                    <strong>Rechnungsnummer:</strong><br>
                                    {{ $rechnung->rechnungsnummer }}
                                </div>
                                <div class="col-6 text-end">
                                    <strong>Zahlbar:</strong><br>
                                    <span class="text-success fw-bold">{{ number_format($rechnung->zahlbar_betrag, 2, ',', '.') }} €</span>
                                </div>
                            </div>
                            @if($rechnung->gebaeude)
                            <div class="mt-2 small text-muted">
                                <i class="bi bi-building"></i>
                                {{ $rechnung->geb_codex }} - {{ $rechnung->geb_name }}
                            </div>
                            @endif
                        </div>
                    </div>
                    
                    {{-- Bezahlt am (Datum) --}}
                    <div class="mb-3">
                        <label for="bezahlt_am_modal" class="form-label">
                            <strong>Bezahlt am <span class="text-danger">*</span></strong>
                        </label>
                        <input type="date" 
                               class="form-control" 
                               id="bezahlt_am_modal" 
                               name="bezahlt_am" 
                               value="{{ now()->format('Y-m-d') }}"
                               required>
                    </div>
                    
                    {{-- ⭐ Schnellauswahl für häufige Zahlungsarten --}}
                    <div class="mb-3">
                        <label class="form-label">
                            <strong>Zahlungsart wählen:</strong>
                        </label>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-outline-primary btn-sm btn-zahlungsart" data-text="Banküberweisung erhalten">
                                <i class="bi bi-bank"></i> Überweisung
                            </button>
                            <button type="button" class="btn btn-outline-success btn-sm btn-zahlungsart" data-text="Barzahlung erhalten">
                                <i class="bi bi-cash-stack"></i> Bar
                            </button>
                            <button type="button" class="btn btn-outline-info btn-sm btn-zahlungsart" data-text="PayPal-Zahlung erhalten">
                                <i class="bi bi-paypal"></i> PayPal
                            </button>
                            <button type="button" class="btn btn-outline-warning btn-sm btn-zahlungsart" data-text="Kartenzahlung erhalten">
                                <i class="bi bi-credit-card"></i> Karte
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm btn-zahlungsart" data-text="Verrechnung mit Guthaben">
                                <i class="bi bi-arrow-left-right"></i> Verrechnung
                            </button>
                            <button type="button" class="btn btn-outline-dark btn-sm btn-zahlungsart" data-text="SEPA-Lastschrift eingezogen">
                                <i class="bi bi-arrow-repeat"></i> Lastschrift
                            </button>
                        </div>
                    </div>
                    
                    {{-- Begründung (Pflichtfeld) --}}
                    <div class="mb-3">
                        <label for="bezahlt_grund" class="form-label">
                            <strong>Zahlungsdetails <span class="text-danger">*</span></strong>
                        </label>
                        <textarea 
                            class="form-control" 
                            id="bezahlt_grund" 
                            name="bezahlt_grund" 
                            rows="3" 
                            required 
                            minlength="5"
                            maxlength="500"
                            placeholder="z.B. Überweisung vom 15.02.2026, Ref: ABC123..."
                        ></textarea>
                        <div class="form-text">
                            <span id="bezahltZeichenZaehler">0</span>/500 Zeichen (mind. 5)
                        </div>
                    </div>
                    
                    {{-- Zusätzliche Optionen --}}
                    <div class="card bg-light">
                        <div class="card-body py-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="logEintrag" name="log_eintrag" value="1" checked>
                                <label class="form-check-label small" for="logEintrag">
                                    <i class="bi bi-journal-text"></i> Log-Eintrag erstellen
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x"></i> Abbrechen
                    </button>
                    <button type="submit" class="btn btn-success" id="btnBezahltBestaetigen" disabled>
                        <i class="bi bi-check-circle"></i> Als bezahlt markieren
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const bezahltGrund = document.getElementById('bezahlt_grund');
    const btnBestaetigen = document.getElementById('btnBezahltBestaetigen');
    const zeichenZaehler = document.getElementById('bezahltZeichenZaehler');
    const zahlungsartButtons = document.querySelectorAll('.btn-zahlungsart');
    
    // Zeichenzähler und Button-Aktivierung
    if (bezahltGrund && btnBestaetigen && zeichenZaehler) {
        bezahltGrund.addEventListener('input', function() {
            const laenge = this.value.length;
            zeichenZaehler.textContent = laenge;
            
            // Button nur aktivieren wenn mindestens 5 Zeichen
            btnBestaetigen.disabled = laenge < 5;
            
            // Visuelles Feedback
            if (laenge < 5) {
                zeichenZaehler.classList.add('text-danger');
                zeichenZaehler.classList.remove('text-success');
            } else {
                zeichenZaehler.classList.remove('text-danger');
                zeichenZaehler.classList.add('text-success');
            }
        });
    }
    
    // Schnellauswahl-Buttons
    zahlungsartButtons.forEach(function(btn) {
        btn.addEventListener('click', function() {
            const text = this.dataset.text;
            
            // Alle Buttons zurücksetzen
            zahlungsartButtons.forEach(b => b.classList.remove('active'));
            
            // Diesen Button aktivieren
            this.classList.add('active');
            
            // Text ins Textarea einfügen (am Anfang, bestehender Text bleibt)
            if (bezahltGrund) {
                const bestehenderText = bezahltGrund.value.trim();
                if (bestehenderText && !bestehenderText.startsWith(text)) {
                    bezahltGrund.value = text + '\n' + bestehenderText;
                } else if (!bestehenderText) {
                    bezahltGrund.value = text;
                }
                
                // Trigger input event für Zeichenzähler
                bezahltGrund.dispatchEvent(new Event('input'));
                bezahltGrund.focus();
            }
        });
    });
    
    // Modal zurücksetzen wenn geschlossen
    const modal = document.getElementById('modalBezahlt');
    if (modal) {
        modal.addEventListener('hidden.bs.modal', function() {
            if (bezahltGrund) {
                bezahltGrund.value = '';
                zeichenZaehler.textContent = '0';
                zeichenZaehler.classList.remove('text-success', 'text-danger');
            }
            if (btnBestaetigen) {
                btnBestaetigen.disabled = true;
            }
            // Alle Zahlungsart-Buttons zurücksetzen
            zahlungsartButtons.forEach(b => b.classList.remove('active'));
            
            // Datum auf heute zurücksetzen
            const datumInput = document.getElementById('bezahlt_am_modal');
            if (datumInput) {
                datumInput.value = new Date().toISOString().split('T')[0];
            }
        });
    }
});
</script>
@endpush

<style>
/* Aktiver Zahlungsart-Button */
.btn-zahlungsart.active {
    box-shadow: 0 0 0 3px rgba(var(--bs-primary-rgb), 0.25);
    transform: scale(1.05);
}
</style>
