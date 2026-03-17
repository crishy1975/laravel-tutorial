



<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!isset($gebaeude) || !$gebaeude->id): ?>
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i>
        Rechnungen erst nach Erstellen des Gebaeudes verfuegbar.
    </div>
<?php else: ?>
    <?php
        $rechnungen = $gebaeude->rechnungen()->orderByDesc('rechnungsdatum')->orderByDesc('id')->get();
        $statusConfig = [
            'draft' => ['class' => 'secondary', 'icon' => 'pencil-square', 'label' => 'Entwurf'],
            'sent' => ['class' => 'info', 'icon' => 'send', 'label' => 'Versendet'],
            'paid' => ['class' => 'success', 'icon' => 'check-circle-fill', 'label' => 'Bezahlt'],
            'cancelled' => ['class' => 'danger', 'icon' => 'x-circle', 'label' => 'Storniert'],
            'overdue' => ['class' => 'warning', 'icon' => 'exclamation-triangle', 'label' => 'Ueberfaellig'],
        ];

        // ⭐ Separate Zählung für Statistiken
        $nurRechnungen = $rechnungen->where('typ_rechnung', '!=', 'gutschrift');
        $nurGutschriften = $rechnungen->where('typ_rechnung', 'gutschrift');
        
        // ⭐ Effektiver Umsatz (Rechnungen minus Gutschriften)
        $bruttoRechnungen = $nurRechnungen->sum('brutto_summe');
        $bruttoGutschriften = $nurGutschriften->sum('brutto_summe');
        $effektivBrutto = $bruttoRechnungen - $bruttoGutschriften;
        
        $nettoRechnungen = $nurRechnungen->sum('netto_summe');
        $nettoGutschriften = $nurGutschriften->sum('netto_summe');
        $effektivNetto = $nettoRechnungen - $nettoGutschriften;
    ?>

    <div class="row g-3">
        
        <div class="col-12">
            <div>
                <h6 class="mb-0">
                    <i class="bi bi-receipt"></i> Rechnungen
                </h6>
                <small class="text-muted">
                    <?php echo e($nurRechnungen->count()); ?> Rechnungen
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($nurGutschriften->count() > 0): ?>
                        <span class="text-danger">/ <?php echo e($nurGutschriften->count()); ?> Gutschriften</span>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </small>
            </div>
        </div>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($rechnungen->isEmpty()): ?>
            <div class="col-12">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> Noch keine Rechnungen erstellt.
                </div>
            </div>
        <?php else: ?>
            
            <div class="col-12 d-md-none">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $rechnungen; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rechnung): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php 
                        $config = $statusConfig[$rechnung->status] ?? ['class' => 'secondary', 'icon' => 'question', 'label' => $rechnung->status];
                        $istGutschrift = $rechnung->typ_rechnung === 'gutschrift';
                    ?>
                    <div class="card mb-2 <?php echo e($istGutschrift ? 'border-danger' : ''); ?>">
                        <div class="card-body p-2">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <strong class="<?php echo e($istGutschrift ? 'text-danger' : ''); ?>">
                                        <?php echo e($rechnung->rechnungsnummer ?? '-'); ?>

                                    </strong>
                                    
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($istGutschrift): ?>
                                        <span class="badge bg-danger ms-1">Gutschrift</span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    <div class="text-muted small"><?php echo e($rechnung->rechnungsdatum?->format('d.m.Y')); ?></div>
                                </div>
                                <span class="badge bg-<?php echo e($config['class']); ?>">
                                    <i class="bi bi-<?php echo e($config['icon']); ?>"></i> <?php echo e($config['label']); ?>

                                </span>
                            </div>
                            <div class="small text-muted mb-2"><?php echo e(Str::limit($rechnung->re_name ?? '-', 30)); ?></div>
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    
                                    <span class="fw-bold <?php echo e($istGutschrift ? 'text-danger' : ''); ?>">
                                        <?php echo e($istGutschrift ? '-' : ''); ?><?php echo e(number_format($rechnung->brutto_summe ?? 0, 2, ',', '.')); ?> EUR
                                    </span>
                                    <span class="text-muted small">
                                        (netto: <?php echo e($istGutschrift ? '-' : ''); ?><?php echo e(number_format($rechnung->netto_summe ?? 0, 2, ',', '.')); ?>)
                                    </span>
                                </div>
                                <div class="btn-group btn-group-sm">
                                    <a href="<?php echo e(route('rechnung.edit', $rechnung->id)); ?>" class="btn btn-outline-primary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($rechnung->status !== 'draft'): ?>
                                    <a href="<?php echo e(route('rechnung.pdf', $rechnung->id)); ?>" class="btn btn-outline-secondary" target="_blank">
                                        <i class="bi bi-file-pdf"></i>
                                    </a>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>

            
            <div class="col-12 d-none d-md-block">
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Nr.</th>
                                <th>Typ</th>
                                <th>Datum</th>
                                <th>Status</th>
                                <th>Empfaenger</th>
                                <th class="text-end">Netto</th>
                                <th class="text-end">Brutto</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $rechnungen; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rechnung): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php 
                                    $config = $statusConfig[$rechnung->status] ?? ['class' => 'secondary', 'icon' => 'question', 'label' => $rechnung->status];
                                    $istGutschrift = $rechnung->typ_rechnung === 'gutschrift';
                                ?>
                                <tr class="<?php echo e($istGutschrift ? 'table-danger' : ''); ?>">
                                    <td>
                                        <strong class="<?php echo e($istGutschrift ? 'text-danger' : ''); ?>">
                                            <?php echo e($rechnung->rechnungsnummer ?? '-'); ?>

                                        </strong>
                                    </td>
                                    <td>
                                        
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($istGutschrift): ?>
                                            <span class="badge bg-danger">
                                                <i class="bi bi-dash-circle"></i> Gutschrift
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-primary">
                                                <i class="bi bi-receipt"></i> Rechnung
                                            </span>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </td>
                                    <td><?php echo e($rechnung->rechnungsdatum?->format('d.m.Y')); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo e($config['class']); ?>">
                                            <i class="bi bi-<?php echo e($config['icon']); ?>"></i> <?php echo e($config['label']); ?>

                                        </span>
                                    </td>
                                    <td><?php echo e(Str::limit($rechnung->re_name ?? '-', 25)); ?></td>
                                    
                                    <td class="text-end <?php echo e($istGutschrift ? 'text-danger fw-bold' : ''); ?>">
                                        <?php echo e($istGutschrift ? '-' : ''); ?><?php echo e(number_format($rechnung->netto_summe ?? 0, 2, ',', '.')); ?>

                                    </td>
                                    <td class="text-end <?php echo e($istGutschrift ? 'text-danger fw-bold' : ''); ?>">
                                        <strong><?php echo e($istGutschrift ? '-' : ''); ?><?php echo e(number_format($rechnung->brutto_summe ?? 0, 2, ',', '.')); ?></strong>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?php echo e(route('rechnung.edit', $rechnung->id)); ?>" class="btn btn-outline-primary">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($rechnung->status !== 'draft'): ?>
                                            <a href="<?php echo e(route('rechnung.pdf', $rechnung->id)); ?>" class="btn btn-outline-secondary" target="_blank">
                                                <i class="bi bi-file-pdf"></i>
                                            </a>
                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </tbody>
                        <tfoot class="table-light">
                            
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($nurGutschriften->count() > 0): ?>
                            <tr>
                                <td colspan="5" class="text-end">Summe Rechnungen</td>
                                <td class="text-end"><?php echo e(number_format($nettoRechnungen, 2, ',', '.')); ?></td>
                                <td class="text-end"><?php echo e(number_format($bruttoRechnungen, 2, ',', '.')); ?></td>
                                <td></td>
                            </tr>
                            <tr class="text-danger">
                                <td colspan="5" class="text-end">Summe Gutschriften</td>
                                <td class="text-end">-<?php echo e(number_format($nettoGutschriften, 2, ',', '.')); ?></td>
                                <td class="text-end">-<?php echo e(number_format($bruttoGutschriften, 2, ',', '.')); ?></td>
                                <td></td>
                            </tr>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            <tr class="fw-bold">
                                <td colspan="5">Effektiv Gesamt</td>
                                <td class="text-end"><?php echo e(number_format($effektivNetto, 2, ',', '.')); ?></td>
                                <td class="text-end"><?php echo e(number_format($effektivBrutto, 2, ',', '.')); ?></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            
            <div class="col-12">
                <div class="row g-2">
                    <div class="col-6 col-md-3">
                        <div class="card border-secondary h-100">
                            <div class="card-body text-center p-2">
                                <i class="bi bi-pencil-square text-secondary"></i>
                                <div class="small text-muted">Entwuerfe</div>
                                <strong><?php echo e($rechnungen->where('status', 'draft')->count()); ?></strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card border-info h-100">
                            <div class="card-body text-center p-2">
                                <i class="bi bi-send text-info"></i>
                                <div class="small text-muted">Versendet</div>
                                <strong><?php echo e($rechnungen->where('status', 'sent')->count()); ?></strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card border-success h-100">
                            <div class="card-body text-center p-2">
                                <i class="bi bi-check-circle text-success"></i>
                                <div class="small text-muted">Bezahlt</div>
                                <strong class="text-success"><?php echo e($rechnungen->where('status', 'paid')->count()); ?></strong>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-6 col-md-3">
                        <div class="card border-danger h-100">
                            <div class="card-body text-center p-2">
                                <i class="bi bi-dash-circle text-danger"></i>
                                <div class="small text-muted">Gutschriften</div>
                                <strong class="text-danger"><?php echo e($nurGutschriften->count()); ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            
            <div class="col-12">
                <div class="card border-primary">
                    <div class="card-body p-2">
                        <div class="row text-center">
                            <div class="col-3 border-end">
                                <small class="text-muted d-block">Rechnungen</small>
                                <strong><?php echo e(number_format($bruttoRechnungen, 2, ',', '.')); ?></strong>
                            </div>
                            <div class="col-3 border-end">
                                <small class="text-muted d-block">Gutschriften</small>
                                <strong class="text-danger">-<?php echo e(number_format($bruttoGutschriften, 2, ',', '.')); ?></strong>
                            </div>
                            <div class="col-3 border-end">
                                <small class="text-muted d-block">Effektiv</small>
                                <strong class="<?php echo e($effektivBrutto < 0 ? 'text-danger' : 'text-primary'); ?>">
                                    <?php echo e(number_format($effektivBrutto, 2, ',', '.')); ?>

                                </strong>
                            </div>
                            <div class="col-3">
                                <small class="text-muted d-block">Offen</small>
                                <strong class="text-warning">
                                    <?php echo e(number_format($nurRechnungen->whereIn('status', ['sent', 'overdue'])->sum('brutto_summe'), 2, ',', '.')); ?>

                                </strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<?php /**PATH I:\Dokumente\entwicklung\laravel-tutorial\resources\views/gebaeude/partials/_rechnungen.blade.php ENDPATH**/ ?>