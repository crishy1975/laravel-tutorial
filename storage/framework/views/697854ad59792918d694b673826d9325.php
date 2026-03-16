

<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(isset($gebaeude) && $gebaeude->id): ?>
    <?php
        $jahr = now()->year;
        $aufschlagProzent = $gebaeude->getAufschlagProzent($jahr);
        $hatIndividuell = $gebaeude->hatIndividuellenAufschlag();
        $globalerAufschlag = \App\Models\PreisAufschlag::getGlobalerAufschlag($jahr);
        $aktuellerAufschlag = $gebaeude->alleGebaeudeAufschlaege()->latest('gueltig_ab')->first();
    ?>

    
    <div class="modal fade" id="modalAufschlagSetzen" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="<?php echo e(route('gebaeude.aufschlag.set', $gebaeude->id)); ?>">
                    <?php echo csrf_field(); ?>
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">Individuellen Aufschlag festlegen</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            Aktuell: Globaler Aufschlag <?php echo e(number_format($globalerAufschlag, 2, ',', '.')); ?>%
                        </div>

                        <div class="mb-3">
                            <label for="prozent" class="form-label">Aufschlag in % <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="prozent" name="prozent" 
                                       step="0.01" min="-100" max="100" value="<?php echo e(old('prozent', $globalerAufschlag)); ?>" required>
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
                                       value="<?php echo e(now()->format('Y-m-d')); ?>">
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

    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($hatIndividuell && $aktuellerAufschlag): ?>
    <div class="modal fade" id="modalAufschlagBearbeiten" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="<?php echo e(route('gebaeude.aufschlag.set', $gebaeude->id)); ?>">
                    <?php echo csrf_field(); ?>
                    <div class="modal-header bg-warning">
                        <h5 class="modal-title">Aufschlag bearbeiten</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="prozent_edit" class="form-label">Aufschlag in % <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="prozent_edit" name="prozent" 
                                       step="0.01" min="-100" max="100" value="<?php echo e(old('prozent', $aktuellerAufschlag->prozent)); ?>" required>
                                <span class="input-group-text">%</span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="grund_edit" class="form-label">Begründung</label>
                            <input type="text" class="form-control" id="grund_edit" name="grund" 
                                   value="<?php echo e($aktuellerAufschlag->grund); ?>" maxlength="255">
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="gueltig_ab_edit" class="form-label">Gültig ab</label>
                                <input type="date" class="form-control" id="gueltig_ab_edit" name="gueltig_ab" 
                                       value="<?php echo e($aktuellerAufschlag->gueltig_ab->format('Y-m-d')); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="gueltig_bis_edit" class="form-label">Gültig bis</label>
                                <input type="date" class="form-control" id="gueltig_bis_edit" name="gueltig_bis" 
                                       value="<?php if($aktuellerAufschlag->gueltig_bis): ?><?php echo e($aktuellerAufschlag->gueltig_bis->format('Y-m-d')); ?><?php endif; ?>">
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
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($hatIndividuell): ?>
    <div class="modal fade" id="modalAufschlagEntfernen" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="<?php echo e(route('gebaeude.aufschlag.remove', $gebaeude->id)); ?>">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('DELETE'); ?>
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
                                <td><span class="badge bg-warning text-dark"><?php echo e(number_format($aufschlagProzent, 2, ',', '.')); ?>%</span></td>
                            </tr>
                            <tr>
                                <th>Danach:</th>
                                <td><span class="badge bg-primary"><?php echo e(number_format($globalerAufschlag, 2, ',', '.')); ?>%</span></td>
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
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    
    <div class="modal fade" id="modalAufschlagVorschau" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">Preis-Vorschau mit kumulativen Erhöhungen</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <strong>Aktueller Aufschlag: <?php echo e(number_format($aufschlagProzent, 2, ',', '.')); ?>%</strong><br>
                        <small>Die Vorschau berücksichtigt kumulative Erhöhungen basierend auf basis_preis und basis_jahr jedes Artikels.</small>
                    </div>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($gebaeude->aktiveArtikel->isNotEmpty()): ?>
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
                                    <?php 
                                        $summeAktuell = 0;
                                        $aktuellesJahr = now()->year;
                                    ?>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $gebaeude->aktiveArtikel; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $artikel): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <?php
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
                                        ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo e($artikel->beschreibung); ?></strong><br>
                                                <small class="text-muted"><?php echo e($artikel->anzahl); ?>x Stück</small>
                                            </td>
                                            <td class="text-center">
                                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($basisJahr < $aktuellesJahr): ?>
                                                    <span class="badge bg-warning text-dark"><?php echo e($basisJahr); ?></span>
                                                <?php else: ?>
                                                    <span class="badge bg-success"><?php echo e($basisJahr); ?></span>
                                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                            </td>
                                            <td class="text-end"><?php echo e(number_format($basisPreis, 2, ',', '.')); ?> €</td>
                                            <td class="text-end"><strong><?php echo e(number_format($aktuellerPreis, 2, ',', '.')); ?> €</strong></td>
                                            <td class="text-end">
                                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($faktor > 1): ?>
                                                    <span class="text-success">×<?php echo e(number_format($faktor, 4, ',', '.')); ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">×1,0000</span>
                                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                            </td>
                                            <td class="text-end">
                                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($differenz > 0): ?>
                                                    <span class="text-success">
                                                        +<?php echo e(number_format($differenz, 2, ',', '.')); ?> €
                                                        <small>(+<?php echo e(number_format($prozentErhohung, 2, ',', '.')); ?>%)</small>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </tbody>
                                <tfoot class="table-light">
                                    <tr class="fw-bold">
                                        <th colspan="3">Summe (Netto)</th>
                                        <th class="text-end"><?php echo e(number_format($summeAktuell, 2, ',', '.')); ?> €</th>
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
                    <?php else: ?>
                        <div class="alert alert-warning">Keine aktiven Artikel vorhanden.</div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Schließen</button>
                </div>
            </div>
        </div>
    </div>

<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?><?php /**PATH I:\Dokumente\entwicklung\laravel-tutorial\resources\views/gebaeude/partials/_aufschlag_modals.blade.php ENDPATH**/ ?>