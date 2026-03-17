

<?php
    // Prüfen ob das Profil "Kondominium" enthält (case-insensitive)
    $hatKondominium = $gebaeude->fatturaProfile && 
                      stripos($gebaeude->fatturaProfile->bezeichnung, 'kondominium') !== false;
    $profilBezeichnung = $gebaeude->fatturaProfile->bezeichnung ?? 'Kein Profil';
    
    // Offene Timeline-Einträge zählen
    $offeneTimeline = $gebaeude->timelines->where('verrechnen', true)->count();
    
    // Prüfen ob Rechnung erstellt werden kann
    $kannRechnung = $gebaeude->rechnungsempfaenger_id 
                 && $gebaeude->postadresse_id 
                 && $gebaeude->fattura_profile_id
                 && $gebaeude->aktiveArtikel->count() > 0;
    
    // Fehlende Voraussetzungen sammeln
    $fehlend = [];
    if (!$gebaeude->rechnungsempfaenger_id) $fehlend[] = 'Rechnungsempfänger';
    if (!$gebaeude->postadresse_id) $fehlend[] = 'Postadresse';
    if (!$gebaeude->fattura_profile_id) $fehlend[] = 'FatturaPA-Profil';
    if ($gebaeude->aktiveArtikel->count() == 0) $fehlend[] = 'Aktive Artikel';
    
    // ⭐ KORRIGIERT: Aktuelle Summe berechnen (wie in _artikel.blade.php)
    $aktuellesJahr = now()->year;
    $aktuellerBetrag = 0.0;
    
    foreach (($gebaeude->aktiveArtikel ?? []) as $artikel) {
        $basisJahr = $artikel->basis_jahr ?? $aktuellesJahr;
        $basisPreis = $artikel->basis_preis ?? $artikel->einzelpreis;
        
        $preis = $gebaeude->berechnePreisMitKumulativerErhoehung(
            $basisPreis,
            $basisJahr,
            $aktuellesJahr
        );
        
        $aktuellerBetrag += (float)$artikel->anzahl * $preis;
    }
    
    // ⭐ Letzte Rechnung laden und Betrag vergleichen
    $letzteRechnung = $gebaeude->rechnungen()
        ->whereIn('status', ['sent', 'paid']) // Nur versendete/bezahlte Rechnungen
        ->orderByDesc('rechnungsdatum')
        ->first();
    
    // ⭐ Netto-Summe der letzten Rechnung (verschiedene mögliche Feldnamen)
    $letzterBetrag = 0;
    if ($letzteRechnung) {
        $letzterBetrag = $letzteRechnung->summe_netto 
                      ?? $letzteRechnung->netto_summe 
                      ?? $letzteRechnung->netto 
                      ?? $letzteRechnung->betrag_netto 
                      ?? 0;
    }
    
    // Prozentuale Änderung berechnen
    $prozentAenderung = 0;
    $hatAenderung = false;
    $warnungAnzeigen = false;
    
    if ($letzterBetrag > 0 && $aktuellerBetrag > 0) {
        $prozentAenderung = (($aktuellerBetrag - $letzterBetrag) / $letzterBetrag) * 100;
        $hatAenderung = abs($prozentAenderung) > 0.01; // Nur bei merklicher Änderung
        $warnungAnzeigen = $prozentAenderung > 10; // Warnung bei >10% Erhöhung
    }
?>


<div class="modal fade" id="modalRechnungErstellen" tabindex="-1" aria-labelledby="modalRechnungErstellenLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="modalRechnungErstellenLabel">
                    <i class="bi bi-receipt"></i> Neue Rechnung erstellen
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Schließen"></button>
            </div>
            
            <form id="formRechnungErstellen" method="POST" action="<?php echo e(route('gebaeude.rechnung.create', $gebaeude->id)); ?>">
                <?php echo csrf_field(); ?>
                
                <div class="modal-body">
                    
                    <div class="alert alert-light border mb-3">
                        <div class="row">
                            <div class="col-6">
                                <small class="text-muted">Gebäude</small><br>
                                <strong><?php echo e($gebaeude->codex); ?></strong>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($gebaeude->gebaeude_name): ?>
                                    <br><small class="text-truncate d-block" style="max-width: 150px;"><?php echo e($gebaeude->gebaeude_name); ?></small>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">FatturaPA-Profil</small><br>
                                <strong><?php echo e($profilBezeichnung); ?></strong>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($hatKondominium): ?>
                                    <br><span class="badge bg-warning text-dark"><i class="bi bi-building"></i> Kondominium</span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </div>
                    </div>

                    
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!$kannRechnung && count($fehlend) > 0): ?>
                        <div class="alert alert-danger mb-3">
                            <i class="bi bi-exclamation-triangle"></i>
                            <strong>Rechnung kann nicht erstellt werden!</strong>
                            <br>
                            <small>Fehlt: <?php echo e(implode(', ', $fehlend)); ?></small>
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                    
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($warnungAnzeigen && $kannRechnung): ?>
                        <div class="alert alert-warning mb-3">
                            <div class="d-flex align-items-start">
                                <i class="bi bi-exclamation-triangle-fill fs-4 me-2 text-warning"></i>
                                <div>
                                    <strong>Achtung: Preiserhöhung!</strong>
                                    <br>
                                    <small>
                                        Der aktuelle Betrag ist <strong><?php echo e(number_format($prozentAenderung, 1, ',', '.')); ?>%</strong> höher als die letzte Rechnung.
                                    </small>
                                    <div class="mt-2 small">
                                        <table class="table table-sm table-borderless mb-0" style="font-size: 0.85em;">
                                            <tr>
                                                <td class="py-0">Letzte Rechnung (<?php echo e($letzteRechnung->rechnungsnummer); ?>):</td>
                                                <td class="py-0 text-end">€ <?php echo e(number_format($letzterBetrag, 2, ',', '.')); ?></td>
                                            </tr>
                                            <tr>
                                                <td class="py-0">Aktuell:</td>
                                                <td class="py-0 text-end"><strong>€ <?php echo e(number_format($aktuellerBetrag, 2, ',', '.')); ?></strong></td>
                                            </tr>
                                            <tr class="text-danger">
                                                <td class="py-0">Differenz:</td>
                                                <td class="py-0 text-end">
                                                    <strong>+ € <?php echo e(number_format($aktuellerBetrag - $letzterBetrag, 2, ',', '.')); ?></strong>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                    
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($hatKondominium && $kannRechnung): ?>
                    <div id="jahresrechnungOption">
                        <div class="card border-warning mb-3">
                            <div class="card-header bg-warning bg-opacity-25 py-2">
                                <i class="bi bi-calendar-year"></i>
                                <strong>Art der Rechnung</strong>
                            </div>
                            <div class="card-body">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="radio" name="ist_jahresrechnung" 
                                           id="rechnungNormal" value="0" checked>
                                    <label class="form-check-label" for="rechnungNormal">
                                        <strong>Normale Rechnung</strong>
                                        <br>
                                        <small class="text-muted">
                                            Leistungsdaten aus Timeline-Einträgen 
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($offeneTimeline > 0): ?>
                                                <span class="badge bg-info"><?php echo e($offeneTimeline); ?> Einträge</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">keine offenen Einträge</span>
                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        </small>
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="ist_jahresrechnung" 
                                           id="rechnungJahr" value="1">
                                    <label class="form-check-label" for="rechnungJahr">
                                        <strong>Jahresrechnung</strong>
                                        <br>
                                        <small class="text-muted">
                                            Causale: <code>Zeitraum/Periodo: Jahr/anno <?php echo e(now()->year); ?></code>
                                        </small>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                    
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!$hatKondominium && $kannRechnung): ?>
                    <div class="alert alert-info mb-3">
                        <i class="bi bi-info-circle"></i>
                        Rechnung wird mit den aktuellen Timeline-Einträgen erstellt.
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($offeneTimeline > 0): ?>
                            <br><small><strong><?php echo e($offeneTimeline); ?></strong> offene Einträge werden verrechnet.</small>
                        <?php else: ?>
                            <br><small class="text-muted">Keine offenen Timeline-Einträge vorhanden.</small>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                    
                    <input type="hidden" name="ist_jahresrechnung" value="0">
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                    
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($gebaeude->aktiveArtikel->count() > 0): ?>
                    <div class="border rounded p-2 <?php echo e($warnungAnzeigen ? 'border-warning' : 'bg-light'); ?>">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted">
                                    <i class="bi bi-list-check"></i> Positionen:
                                </small>
                                <br>
                                <strong><?php echo e($gebaeude->aktiveArtikel->count()); ?> aktive Artikel</strong>
                            </div>
                            <div class="text-end">
                                <small class="text-muted">Summe (netto):</small>
                                <br>
                                <strong class="<?php echo e($warnungAnzeigen ? 'text-warning' : 'text-success'); ?>">
                                    € <?php echo e(number_format($aktuellerBetrag, 2, ',', '.')); ?>

                                </strong>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($letzteRechnung && $hatAenderung): ?>
                                    <br>
                                    <small class="text-<?php echo e($warnungAnzeigen ? 'danger' : ($prozentAenderung > 0 ? 'warning' : 'success')); ?>">
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($prozentAenderung > 0): ?>
                                            <i class="bi bi-arrow-up"></i> +<?php echo e(number_format($prozentAenderung, 1, ',', '.')); ?>%
                                        <?php elseif($prozentAenderung < 0): ?>
                                            <i class="bi bi-arrow-down"></i> <?php echo e(number_format($prozentAenderung, 1, ',', '.')); ?>%
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </small>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </div>
                        
                        
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($letzteRechnung && !$warnungAnzeigen): ?>
                        <div class="mt-2 pt-2 border-top small text-muted">
                            <i class="bi bi-clock-history"></i>
                            Letzte: <?php echo e($letzteRechnung->rechnungsnummer); ?> 
                            (<?php echo e($letzteRechnung->rechnungsdatum?->format('d.m.Y')); ?>) 
                            – € <?php echo e(number_format($letzterBetrag, 2, ',', '.')); ?>

                        </div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-warning mb-0">
                        <i class="bi bi-exclamation-triangle"></i>
                        <strong>Achtung:</strong> Keine aktiven Artikel vorhanden!
                    </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x"></i> Abbrechen
                    </button>
                    <button type="submit" class="btn <?php echo e($warnungAnzeigen ? 'btn-warning' : 'btn-success'); ?>" 
                            <?php echo e(!$kannRechnung ? 'disabled' : ''); ?>>
                        <i class="bi bi-plus-circle"></i> 
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($warnungAnzeigen): ?>
                            Trotzdem erstellen
                        <?php else: ?>
                            Rechnung erstellen
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php /**PATH I:\Dokumente\entwicklung\laravel-tutorial\resources\views/gebaeude/partials/_modal_rechnung_erstellen.blade.php ENDPATH**/ ?>