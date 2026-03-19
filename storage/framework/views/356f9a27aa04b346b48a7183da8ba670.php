
<div class="container-fluid py-2 py-md-4">

    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('success')): ?>
        <div class="alert alert-success alert-dismissible fade show py-2" role="alert">
            <i class="bi bi-check-circle"></i> <?php echo e(session('success')); ?>

            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show py-2" role="alert">
            <i class="bi bi-exclamation-triangle"></i> <?php echo e(session('error')); ?>

            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 h3-md mb-0">
                <i class="bi bi-building text-primary"></i>
                Anlagen
            </h1>
            <p class="text-muted mb-0 small d-none d-md-block">
                <?php echo e($statistik['total']); ?> Anlagen gesamt
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?php echo e(route('messungen.index')); ?>" class="btn btn-outline-secondary">
                <i class="bi bi-speedometer2"></i>
                <span class="d-none d-sm-inline">Messungen</span>
            </a>
            <a href="<?php echo e(route('messungen.anlagen.import')); ?>" class="btn btn-success">
                <i class="bi bi-file-earmark-arrow-up"></i>
                <span class="d-none d-sm-inline">CSV Import</span>
            </a>
        </div>
    </div>

    
    <div class="row g-2 mb-3">
        <div class="col-4 col-md-4">
            <div class="card border-primary h-100 stat-card">
                <div class="card-body text-center py-2">
                    <div class="stat-number text-primary"><?php echo e($statistik['total']); ?></div>
                    <div class="stat-label">Gesamt</div>
                </div>
            </div>
        </div>
        <div class="col-4 col-md-4">
            <div class="card border-success h-100 stat-card cursor-pointer"
                 wire:click="$set('filterGemessen', '1')" role="button">
                <div class="card-body text-center py-2">
                    <div class="stat-number text-success"><?php echo e($statistik['mitMessung']); ?></div>
                    <div class="stat-label d-none d-sm-block">Mit Messung <?php echo e($filterJahr); ?></div>
                    <div class="stat-label d-sm-none">Mit</div>
                </div>
            </div>
        </div>
        <div class="col-4 col-md-4">
            <div class="card border-danger h-100 stat-card cursor-pointer"
                 wire:click="$set('filterGemessen', '0')" role="button">
                <div class="card-body text-center py-2">
                    <div class="stat-number text-danger"><?php echo e($statistik['ohneMessung']); ?></div>
                    <div class="stat-label d-none d-sm-block">Ohne Messung <?php echo e($filterJahr); ?></div>
                    <div class="stat-label d-sm-none">Ohne</div>
                </div>
            </div>
        </div>
    </div>

    
    <div class="card shadow-sm mb-3">
        <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center"
             data-bs-toggle="collapse" data-bs-target="#filterCollapse" role="button" aria-expanded="true">
            <h6 class="mb-0">
                <i class="bi bi-funnel"></i> Filter
                <?php
                    $activeFilters = collect([$filterKodex, $filterBeschreibung, $filterOrt, $filterStrasse, $filterHersteller, $filterGemessen])->filter()->count();
                ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($activeFilters > 0): ?>
                    <span class="badge bg-primary ms-1"><?php echo e($activeFilters); ?></span>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </h6>
            <i class="bi bi-chevron-down d-md-none transition-transform"></i>
        </div>
        <div class="collapse show" id="filterCollapse">
            <div class="card-body py-2">
                <div class="row g-2">
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1">Kodex</label>
                        <input type="text" wire:model.live.debounce.300ms="filterKodex"
                               class="form-control form-control-sm" placeholder="Kodex...">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1">Aufstellungsort</label>
                        <input type="text" wire:model.live.debounce.300ms="filterBeschreibung"
                               class="form-control form-control-sm" placeholder="Name...">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1">Gemeinde</label>
                        <input type="text" wire:model.live.debounce.300ms="filterOrt"
                               class="form-control form-control-sm" placeholder="Gemeinde...">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1">Straße</label>
                        <input type="text" wire:model.live.debounce.300ms="filterStrasse"
                               class="form-control form-control-sm" placeholder="Straße...">
                    </div>
                    <div class="col-4 col-md-1">
                        <label class="form-label small mb-1">Messung</label>
                        <select wire:model.live="filterGemessen" class="form-select form-select-sm">
                            <option value="">Alle</option>
                            <option value="1">Ja</option>
                            <option value="0">Nein</option>
                        </select>
                    </div>
                    <div class="col-4 col-md-1">
                        <label class="form-label small mb-1">Jahr</label>
                        <input type="number" wire:model.live="filterJahr"
                               class="form-control form-control-sm" min="2020" max="2030">
                    </div>
                    <div class="col-4 col-md-2 d-flex align-items-end gap-1">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($activeFilters > 0): ?>
                            <button wire:click="resetFilters" class="btn btn-outline-secondary btn-sm" title="Filter zurücksetzen">
                                <i class="bi bi-x-lg"></i>
                                <span class="d-none d-md-inline">Reset</span>
                            </button>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($anlagen->isEmpty()): ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-inbox display-4 text-muted"></i>
                <p class="text-muted mt-2 mb-0">Keine Anlagen gefunden.</p>
                <a href="<?php echo e(route('messungen.anlagen.import')); ?>" class="btn btn-success mt-3">
                    <i class="bi bi-file-earmark-arrow-up"></i> CSV importieren
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="card shadow-sm">
            
            <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    <?php echo e($anlagen->firstItem()); ?>–<?php echo e($anlagen->lastItem()); ?> von <?php echo e($anlagen->total()); ?>

                </small>
                <div wire:loading class="spinner-border spinner-border-sm text-primary" role="status">
                    <span class="visually-hidden">Laden...</span>
                </div>
            </div>

            
            <div class="table-responsive d-none d-lg-block">
                <table class="table table-hover align-middle mb-0" id="anlagenTable">
                    <thead class="table-dark">
                        <tr>
                            <th style="width: 90px;" wire:click="sortBy('Feld_a')">
                                Kodex
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sortField === 'Feld_a'): ?>
                                    <i class="bi bi-arrow-<?php echo e($sortDirection === 'asc' ? 'up' : 'down'); ?> ms-1"></i>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </th>
                            <th wire:click="sortBy('Feld_w')">
                                Aufstellungsort
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sortField === 'Feld_w'): ?>
                                    <i class="bi bi-arrow-<?php echo e($sortDirection === 'asc' ? 'up' : 'down'); ?> ms-1"></i>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </th>
                            <th wire:click="sortBy('Feld_i')">
                                Gemeinde
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sortField === 'Feld_i'): ?>
                                    <i class="bi bi-arrow-<?php echo e($sortDirection === 'asc' ? 'up' : 'down'); ?> ms-1"></i>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </th>
                            <th wire:click="sortBy('Feld_m')">
                                Straße
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sortField === 'Feld_m'): ?>
                                    <i class="bi bi-arrow-<?php echo e($sortDirection === 'asc' ? 'up' : 'down'); ?> ms-1"></i>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </th>
                            <th style="width: 80px;" class="text-center">Messung</th>
                            <th wire:click="sortBy('Feld_y')">
                                Hersteller
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sortField === 'Feld_y'): ?>
                                    <i class="bi bi-arrow-<?php echo e($sortDirection === 'asc' ? 'up' : 'down'); ?> ms-1"></i>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </th>
                            <th style="width: 70px;" class="text-center">Baujahr</th>
                            <th style="width: 60px;" class="text-center">kW</th>
                            <th style="width: 100px;" class="text-end">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $anlagen; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $anlage): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php 
                                $letzteMessung = $anlage->messungenHeuer()->orderBy('cMIS_DATA', 'desc')->orderBy('cMIS_ORA', 'desc')->first();
                                $hatMessung = $letzteMessung !== null;
                                $istNegativ = $hatMessung && $letzteMessung->strEsito === '0';
                            ?>
                            <tr class="<?php echo e($istNegativ ? 'table-danger' : (!$hatMessung ? 'table-warning' : '')); ?>">
                                <td>
                                    <a href="<?php echo e(route('messungen.anlagen.edit', $anlage->Feld_a)); ?>"
                                       class="fw-bold text-decoration-none font-monospace"><?php echo e($anlage->Feld_a); ?></a>
                                </td>
                                <td>
                                    <a href="<?php echo e(route('messungen.anlagen.edit', $anlage->Feld_a)); ?>"
                                       class="text-decoration-none text-dark"><?php echo e(Str::limit($anlage->Feld_w, 40) ?: '(keine Beschreibung)'); ?></a>
                                </td>
                                <td><?php echo e($anlage->Feld_i); ?></td>
                                <td><?php echo e($anlage->Feld_m); ?> <?php echo e($anlage->Feld_n); ?></td>
                                <td class="text-center">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($istNegativ): ?>
                                        <span class="badge bg-danger"><i class="bi bi-x-lg"></i></span>
                                    <?php elseif($hatMessung): ?>
                                        <span class="badge bg-success"><i class="bi bi-check-lg"></i></span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark"><i class="bi bi-dash"></i></span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </td>
                                <td><?php echo e($anlage->Feld_y); ?></td>
                                <td class="text-center"><?php echo e($anlage->Feld_z); ?></td>
                                <td class="text-center"><?php echo e($anlage->Feld_ab); ?></td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?php echo e(route('messungen.anlagen.edit', $anlage->Feld_a)); ?>"
                                           class="btn btn-outline-primary" title="Bearbeiten">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($hatMessung): ?>
                                            <a href="<?php echo e(route('messungen.protokoll', $letzteMessung->id)); ?>" target="_blank"
                                               class="btn btn-outline-info" title="Protokoll drucken">
                                                <i class="bi bi-printer"></i>
                                            </a>
                                            <button wire:click="openMessungModalMitLetzer('<?php echo e($anlage->Feld_a); ?>')"
                                               class="btn btn-outline-info" title="Letzte Messung anzeigen">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        <button wire:click="openMessungModal('<?php echo e($anlage->Feld_a); ?>')"
                                           class="btn btn-outline-success" title="Neue Messung">
                                            <i class="bi bi-plus-lg"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </tbody>
                </table>
            </div>

            
            <div class="d-lg-none">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $anlagen; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $anlage): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php 
                        $letzteMessung = $anlage->messungenHeuer()->orderBy('cMIS_DATA', 'desc')->orderBy('cMIS_ORA', 'desc')->first();
                        $hatMessung = $letzteMessung !== null;
                        $istNegativ = $hatMessung && $letzteMessung->strEsito === '0';
                    ?>
                    <div class="anlage-card border-bottom <?php echo e($istNegativ ? 'bg-danger bg-opacity-10' : (!$hatMessung ? 'bg-warning bg-opacity-10' : '')); ?>">
                        <div class="p-2">
                            
                            <div class="d-flex align-items-start gap-2 mb-1">
                                <div class="flex-grow-1 min-width-0">
                                    <a href="<?php echo e(route('messungen.anlagen.edit', $anlage->Feld_a)); ?>" class="text-decoration-none">
                                        <span class="fw-bold text-primary font-monospace"><?php echo e($anlage->Feld_a); ?></span>
                                        <span class="text-dark"><?php echo e(Str::limit($anlage->Feld_w ?: '(keine Beschreibung)', 30)); ?></span>
                                    </a>
                                </div>
                                <div class="flex-shrink-0">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($istNegativ): ?>
                                        <span class="badge bg-danger"><i class="bi bi-x-lg"></i></span>
                                    <?php elseif($hatMessung): ?>
                                        <span class="badge bg-success"><i class="bi bi-check-lg"></i></span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark"><i class="bi bi-dash"></i></span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                            </div>

                            
                            <div class="small text-muted mb-1 ps-1">
                                <i class="bi bi-geo-alt"></i>
                                <?php echo e($anlage->Feld_m); ?> <?php echo e($anlage->Feld_n); ?><?php echo e(($anlage->Feld_m && $anlage->Feld_i) ? ',' : ''); ?>

                                <?php echo e($anlage->Feld_i); ?>

                            </div>

                            
                            <div class="d-flex justify-content-between align-items-center ps-1">
                                <div class="small text-muted">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($anlage->Feld_y): ?>
                                        <i class="bi bi-wrench"></i> <?php echo e($anlage->Feld_y); ?>

                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($anlage->Feld_z): ?>
                                            (<?php echo e($anlage->Feld_z); ?>)
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($anlage->Feld_ab): ?>
                                            &middot; <?php echo e($anlage->Feld_ab); ?> kW
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                                <div class="btn-group btn-group-sm">
                                    <a href="<?php echo e(route('messungen.anlagen.edit', $anlage->Feld_a)); ?>"
                                       class="btn btn-outline-primary py-0 px-2">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($hatMessung): ?>
                                        <a href="<?php echo e(route('messungen.protokoll', $letzteMessung->id)); ?>" target="_blank"
                                           class="btn btn-outline-info py-0 px-2">
                                            <i class="bi bi-printer"></i>
                                        </a>
                                        <button wire:click="openMessungModalMitLetzer('<?php echo e($anlage->Feld_a); ?>')"
                                           class="btn btn-outline-info py-0 px-2">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    <button wire:click="openMessungModal('<?php echo e($anlage->Feld_a); ?>')"
                                       class="btn btn-outline-success py-0 px-2">
                                        <i class="bi bi-plus-lg"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>

            
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($anlagen->hasPages()): ?>
                <div class="card-footer bg-light py-2">
                    <div class="d-flex justify-content-center">
                        <?php echo e($anlagen->links()); ?>

                    </div>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($showMessungModal): ?>
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-lg modal-dialog-scrollable modal-fullscreen-sm-down">
                <div class="modal-content">
                    <div class="modal-header <?php echo e($letzteMessung ? 'bg-info' : 'bg-success'); ?> text-white py-2">
                        <h5 class="modal-title">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($letzteMessung): ?>
                                <i class="bi bi-eye"></i> Letzte Messung (<?php echo e($letzteMessung->cMIS_DATA2); ?>)
                            <?php else: ?>
                                <i class="bi bi-plus-circle"></i> Neue Messung
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </h5>
                        <button type="button" class="btn-close btn-close-white" wire:click="closeMessungModal"></button>
                    </div>
                    <div class="modal-body p-2 p-md-3">
                        
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($selectedAnlage): ?>
                            <div class="alert alert-secondary py-2 mb-3">
                                <div class="row small">
                                    <div class="col-4 col-md-2">
                                        <strong>Kodex:</strong><br>
                                        <span class="font-monospace"><?php echo e($selectedAnlage->Feld_a); ?></span>
                                    </div>
                                    <div class="col-8 col-md-4">
                                        <strong>Aufstellungsort:</strong><br>
                                        <?php echo e($selectedAnlage->Feld_w); ?>

                                    </div>
                                    <div class="col-6 col-md-3">
                                        <strong>Kessel:</strong><br>
                                        <?php echo e($selectedAnlage->Feld_y ?? '-'); ?>

                                    </div>
                                    <div class="col-3 col-md-1">
                                        <strong>Bj.:</strong><br>
                                        <?php echo e($selectedAnlage->Feld_z ?? '-'); ?>

                                    </div>
                                    <div class="col-3 col-md-2">
                                        <strong>kW:</strong><br>
                                        <?php echo e($selectedAnlage->Feld_ab ?? '-'); ?>

                                    </div>
                                </div>
                            </div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                        
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($modalError): ?>
                            <div class="alert alert-danger py-2 mb-2">
                                <i class="bi bi-exclamation-triangle"></i> <?php echo e($modalError); ?>

                            </div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                        <form wire:submit="saveMessung">
                            <div class="row g-3">
                                
                                <div class="col-12 col-md-8">
                                    
                                    
                                    <div class="mb-3" x-data="{ 
                                        loading: false, 
                                        status: '',
                                        processImage(file, source) {
                                            if (!file) return;
                                            this.loading = true;
                                            this.status = '';
                                            const reader = new FileReader();
                                            reader.onload = async (e) => {
                                                try {
                                                    const res = await fetch('/messungen/extract-from-photo', {
                                                        method: 'POST',
                                                        headers: {
                                                            'Content-Type': 'application/json',
                                                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                                                        },
                                                        body: JSON.stringify({ image: e.target.result.split(',')[1], source: source || 'auto' })
                                                    });
                                                    const data = await res.json();
                                                    if (data.success) {
                                                        if (data.typ === 'protokoll') {
                                                            // Protokoll-Felder mappen
                                                            if (data.datum) $wire.set('messung.cMIS_DATA2', data.datum);
                                                            if (data.brennstoff) $wire.set('messung.cMIS_COMBUSTIBILE', data.brennstoff);
                                                            $wire.set('messung.cMIS_OSSIGENO', data.o2 || '');
                                                            $wire.set('messung.cMIS_ANIDRIDE_CARBONICA', data.co2 || '');
                                                            $wire.set('messung.cMIS_MONOSSSIDO', data.co || '');
                                                            $wire.set('messung.cMIS_BIOSSIDO_AZOTO', data.nox || '');
                                                            $wire.set('messung.cMIS_T_ARIA_COMB', data.t_luft || '');
                                                            $wire.set('messung.cMIS_T_GAS_COMB', data.t_abgas || '');
                                                            $wire.set('messung.cMIS_T_LIQ_CONV', data.t_waerme || '');
                                                            $wire.set('messung.cMIS_IND_OPACITA', data.russ || '0');
                                                            $wire.set('messung.cMIS_TRACCE_OLEO', data.oelderivate === '1' ? '0' : '1');
                                                        } else {
                                                            // Display-Felder mappen
                                                            if (data.datum) $wire.set('messung.cMIS_DATA2', data.datum);
                                                            if (data.uhrzeit) $wire.set('messung.cMIS_ORA', data.uhrzeit);
                                                            if (data.brennstoff) $wire.set('messung.cMIS_COMBUSTIBILE', data.brennstoff);
                                                            $wire.set('messung.cMIS_OSSIGENO', data.o2 || '');
                                                            $wire.set('messung.cMIS_ANIDRIDE_CARBONICA', data.co2 || '');
                                                            $wire.set('messung.cMIS_PERD_FUMI', data.qa || '');
                                                            $wire.set('messung.cMIS_MONOSSSIDO', data.co || '');
                                                            $wire.set('messung.cMIS_BIOSSIDO_AZOTO', data.nox || '');
                                                            $wire.set('messung.cMIS_T_ARIA_COMB', data.t_luft || '');
                                                            $wire.set('messung.cMIS_T_GAS_COMB', data.t_abgas || '');
                                                            $wire.set('messung.cMIS_IND_OPACITA', data.russ || '0');
                                                        }
                                                        this.status = 'success';
                                                    } else {
                                                        this.status = data.error || 'Fehler';
                                                    }
                                                } catch (err) {
                                                    this.status = 'Verbindungsfehler';
                                                    console.error(err);
                                                }
                                                this.loading = false;
                                            };
                                            reader.readAsDataURL(file);
                                        }
                                    }">
                                        <div class="d-flex align-items-center gap-2 flex-wrap">
                                            
                                            <label class="btn btn-primary btn-sm mb-0" :class="{ 'disabled': loading }">
                                                <span x-show="!loading"><i class="bi bi-camera-fill me-1"></i> Kamera</span>
                                                <span x-show="loading"><i class="bi bi-hourglass-split me-1"></i> Analysiert...</span>
                                                <input type="file" accept="image/*" capture="environment" 
                                                       class="d-none" x-ref="kameraInput"
                                                       @change="processImage($event.target.files[0], 'auto'); $refs.kameraInput.value = '';">
                                            </label>
                                            
                                            <label class="btn btn-outline-primary btn-sm mb-0" :class="{ 'disabled': loading }">
                                                <span x-show="!loading"><i class="bi bi-images me-1"></i> Galerie</span>
                                                <span x-show="loading"><i class="bi bi-hourglass-split me-1"></i> Analysiert...</span>
                                                <input type="file" accept="image/*" 
                                                       class="d-none" x-ref="galerieInput"
                                                       @change="processImage($event.target.files[0], 'auto'); $refs.galerieInput.value = '';">
                                            </label>
                                            <span x-show="status === 'success'" class="badge bg-success"><i class="bi bi-check-lg"></i> Werte übernommen</span>
                                            <span x-show="status && status !== 'success'" class="badge bg-danger" x-text="status"></span>
                                        </div>
                                    </div>

                                    
                                    <div class="card mb-2">
                                        <div class="card-header bg-light py-1 px-2">
                                            <h6 class="mb-0 small"><i class="bi bi-info-circle"></i> Grunddaten</h6>
                                        </div>
                                        <div class="card-body py-2 px-2">
                                            <div class="row g-2">
                                                <div class="col-4 col-md-2">
                                                    <label class="form-label small mb-0">Stadio</label>
                                                    <input type="text" wire:model="messung.cMIS_STADIO"
                                                           class="form-control form-control-sm" required>
                                                </div>
                                                <div class="col-4 col-md-3">
                                                    <label class="form-label small mb-0">Datum</label>
                                                    <input type="text" wire:model="messung.cMIS_DATA2"
                                                           class="form-control form-control-sm" required>
                                                </div>
                                                <div class="col-4 col-md-2">
                                                    <label class="form-label small mb-0">Uhrzeit</label>
                                                    <input type="time" wire:model="messung.cMIS_ORA"
                                                           class="form-control form-control-sm" step="1">
                                                </div>
                                                <div class="col-12 col-md-5">
                                                    <label class="form-label small mb-0">Brennstoff</label>
                                                    <select wire:model.live="messung.cMIS_COMBUSTIBILE" class="form-select form-select-sm">
                                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $brennstoffe; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $info): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                            <option value="<?php echo e($key); ?>"><?php echo e($info['text']); ?></option>
                                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    
                                    <div class="card mb-2">
                                        <div class="card-header bg-light py-1 px-2">
                                            <h6 class="mb-0 small"><i class="bi bi-speedometer2"></i> Messwerte</h6>
                                        </div>
                                        <div class="card-body py-2 px-2">
                                            
                                            <div class="row g-2">
                                                <div class="col-6">
                                                    <label class="form-label small mb-0">Rußzahl-Mittelwert</label>
                                                    <input type="text" wire:model.live.debounce.500ms="messung.cMIS_IND_OPACITA"
                                                           class="form-control form-control-sm">
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small mb-0">Ölderivate?</label>
                                                    <select wire:model.live="messung.cMIS_TRACCE_OLEO" class="form-select form-select-sm">
                                                        <option value="1">NEIN/NO</option>
                                                        <option value="0">JA/SI</option>
                                                    </select>
                                                </div>
                                            </div>
                                            
                                            <div class="row g-2 mt-1">
                                                <div class="col-6">
                                                    <label class="form-label small mb-0">T Wärmeträger</label>
                                                    <div class="input-group input-group-sm">
                                                        <input type="text" wire:model="messung.cMIS_T_LIQ_CONV"
                                                               class="form-control">
                                                        <span class="input-group-text">°C</span>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small mb-0">T Abgas</label>
                                                    <div class="input-group input-group-sm">
                                                        <input type="text" wire:model="messung.cMIS_T_GAS_COMB"
                                                               class="form-control">
                                                        <span class="input-group-text">°C</span>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="row g-2 mt-1">
                                                <div class="col-6">
                                                    <label class="form-label small mb-0">T Verbrennungsluft</label>
                                                    <div class="input-group input-group-sm">
                                                        <input type="text" wire:model="messung.cMIS_T_ARIA_COMB"
                                                               class="form-control">
                                                        <span class="input-group-text">°C</span>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small mb-0">O₂</label>
                                                    <div class="input-group input-group-sm">
                                                        <input type="text" wire:model="messung.cMIS_OSSIGENO"
                                                               class="form-control">
                                                        <span class="input-group-text">%</span>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="row g-2 mt-1">
                                                <div class="col-6">
                                                    <label class="form-label small mb-0">CO₂</label>
                                                    <div class="input-group input-group-sm">
                                                        <input type="text" wire:model="messung.cMIS_ANIDRIDE_CARBONICA"
                                                               class="form-control">
                                                        <span class="input-group-text">%</span>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small mb-0">NOx</label>
                                                    <div class="input-group input-group-sm">
                                                        <input type="text" wire:model.live.debounce.500ms="messung.cMIS_BIOSSIDO_AZOTO"
                                                               class="form-control">
                                                        <span class="input-group-text">mg/m³</span>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="row g-2 mt-1">
                                                <div class="col-6">
                                                    <label class="form-label small mb-0">CO</label>
                                                    <div class="input-group input-group-sm">
                                                        <input type="text" wire:model.live.debounce.500ms="messung.cMIS_MONOSSSIDO"
                                                               class="form-control">
                                                        <span class="input-group-text">mg/m³</span>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small mb-0">Qa</label>
                                                    <div class="input-group input-group-sm">
                                                        <input type="text" wire:model="messung.cMIS_PERD_FUMI"
                                                               class="form-control">
                                                        <span class="input-group-text">%</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                
                                <div class="col-12 col-md-4">
                                    <div class="card h-100">
                                        <div class="card-header bg-light py-1 px-2">
                                            <h6 class="mb-0 small"><i class="bi bi-stoplights"></i> Grenzwerte</h6>
                                        </div>
                                        <div class="card-body py-2 px-2">
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($grenzwerte): ?>
                                                
                                                <div class="d-flex justify-content-between align-items-center mb-2 p-2 rounded
                                                    <?php echo e($grenzwerte['co']['status'] === 'gruen' ? 'bg-success bg-opacity-10' : ''); ?>

                                                    <?php echo e($grenzwerte['co']['status'] === 'gelb' ? 'bg-warning bg-opacity-25' : ''); ?>

                                                    <?php echo e($grenzwerte['co']['status'] === 'rot' ? 'bg-danger bg-opacity-25' : ''); ?>">
                                                    <div>
                                                        <strong>CO</strong>
                                                        <small class="text-muted d-block">max. <?php echo e($grenzwerte['co']['grenzwert']); ?> mg/m³</small>
                                                    </div>
                                                    <div class="text-end">
                                                        <span class="fw-bold"><?php echo e($messung['cMIS_MONOSSSIDO'] ?: '-'); ?></span>
                                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($grenzwerte['co']['status'] === 'gruen'): ?>
                                                            <i class="bi bi-check-circle-fill text-success ms-1"></i>
                                                        <?php elseif($grenzwerte['co']['status'] === 'gelb'): ?>
                                                            <i class="bi bi-exclamation-triangle-fill text-warning ms-1"></i>
                                                        <?php else: ?>
                                                            <i class="bi bi-x-circle-fill text-danger ms-1"></i>
                                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                    </div>
                                                </div>

                                                
                                                <div class="d-flex justify-content-between align-items-center mb-2 p-2 rounded
                                                    <?php echo e($grenzwerte['nox']['status'] === 'gruen' ? 'bg-success bg-opacity-10' : ''); ?>

                                                    <?php echo e($grenzwerte['nox']['status'] === 'gelb' ? 'bg-warning bg-opacity-25' : ''); ?>

                                                    <?php echo e($grenzwerte['nox']['status'] === 'rot' ? 'bg-danger bg-opacity-25' : ''); ?>">
                                                    <div>
                                                        <strong>NOx</strong>
                                                        <small class="text-muted d-block">max. <?php echo e($grenzwerte['nox']['grenzwert']); ?> mg/m³</small>
                                                    </div>
                                                    <div class="text-end">
                                                        <span class="fw-bold"><?php echo e($messung['cMIS_BIOSSIDO_AZOTO'] ?: '-'); ?></span>
                                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($grenzwerte['nox']['status'] === 'gruen'): ?>
                                                            <i class="bi bi-check-circle-fill text-success ms-1"></i>
                                                        <?php elseif($grenzwerte['nox']['status'] === 'gelb'): ?>
                                                            <i class="bi bi-exclamation-triangle-fill text-warning ms-1"></i>
                                                        <?php else: ?>
                                                            <i class="bi bi-x-circle-fill text-danger ms-1"></i>
                                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                    </div>
                                                </div>

                                                
                                                <div class="d-flex justify-content-between align-items-center mb-2 p-2 rounded
                                                    <?php echo e($grenzwerte['russ']['status'] === 'gruen' ? 'bg-success bg-opacity-10' : ''); ?>

                                                    <?php echo e($grenzwerte['russ']['status'] === 'rot' ? 'bg-danger bg-opacity-25' : ''); ?>">
                                                    <div>
                                                        <strong>Rußzahl</strong>
                                                        <small class="text-muted d-block">max. <?php echo e($grenzwerte['russ']['grenzwert']); ?></small>
                                                    </div>
                                                    <div class="text-end">
                                                        <span class="fw-bold"><?php echo e($messung['cMIS_IND_OPACITA'] ?: '-'); ?></span>
                                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($grenzwerte['russ']['status'] === 'gruen'): ?>
                                                            <i class="bi bi-check-circle-fill text-success ms-1"></i>
                                                        <?php else: ?>
                                                            <i class="bi bi-x-circle-fill text-danger ms-1"></i>
                                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                    </div>
                                                </div>

                                                
                                                <div class="d-flex justify-content-between align-items-center p-2 rounded
                                                    <?php echo e($grenzwerte['oel']['status'] === 'gruen' ? 'bg-success bg-opacity-10' : ''); ?>

                                                    <?php echo e($grenzwerte['oel']['status'] === 'rot' ? 'bg-danger bg-opacity-25' : ''); ?>">
                                                    <div>
                                                        <strong>Ölspuren</strong>
                                                        <small class="text-muted d-block">keine erlaubt</small>
                                                    </div>
                                                    <div class="text-end">
                                                        <span class="fw-bold"><?php echo e($messung['cMIS_TRACCE_OLEO'] === '0' ? 'Ja' : 'Nein'); ?></span>
                                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($grenzwerte['oel']['status'] === 'gruen'): ?>
                                                            <i class="bi bi-check-circle-fill text-success ms-1"></i>
                                                        <?php else: ?>
                                                            <i class="bi bi-x-circle-fill text-danger ms-1"></i>
                                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <div class="text-center text-muted py-4">
                                                    <i class="bi bi-speedometer2 display-6"></i>
                                                    <p class="mb-0 mt-2 small">Gib Messwerte ein um die Grenzwertprüfung zu sehen.</p>
                                                </div>
                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($errors->any()): ?>
                                <div class="alert alert-danger py-2 mt-2">
                                    <ul class="mb-0 small">
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <li><?php echo e($error); ?></li>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </ul>
                                </div>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </form>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" wire:click="closeMessungModal" class="btn btn-secondary btn-sm">
                            <i class="bi bi-x-lg"></i> Abbrechen
                        </button>
                        <button type="button" wire:click="saveMessung" class="btn btn-success btn-sm">
                            <i class="bi bi-check-lg"></i> Speichern
                        </button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</div>

<?php $__env->startPush('styles'); ?>
<style>
    .stat-card { transition: transform 0.2s, box-shadow 0.2s; }
    .stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
    .stat-number { font-size: 1.5rem; font-weight: 700; line-height: 1.2; }
    .stat-label { font-size: 0.7rem; color: #6c757d; text-transform: uppercase; letter-spacing: 0.5px; }
    .cursor-pointer { cursor: pointer; }
    .min-width-0 { min-width: 0; }
    @media (min-width: 768px) { .stat-number { font-size: 2rem; } .stat-label { font-size: 0.75rem; } }
    .anlage-card { transition: background-color 0.15s; }
    .anlage-card:active { background-color: rgba(0, 123, 255, 0.05); }
    .transition-transform { transition: transform 0.3s; }
    [aria-expanded="false"] .transition-transform { transform: rotate(-90deg); }
    #anlagenTable thead th { cursor: pointer; user-select: none; }
    #anlagenTable thead th:hover { background-color: rgba(255,255,255,0.1); }
    #anlagenTable tbody tr { cursor: pointer; transition: background-color 0.15s; }
    #anlagenTable tbody tr:hover { background-color: rgba(0, 123, 255, 0.05); }
    #anlagenTable tbody tr.table-danger:hover { background-color: rgba(220, 53, 69, 0.15); }
    @media (max-width: 575.98px) { .container-fluid { padding-left: 0.5rem; padding-right: 0.5rem; } .card-body { padding: 0.5rem; } .form-label { font-size: 0.75rem; } .stat-number { font-size: 1.25rem; } }
    @media print { .btn, #filterCollapse, .card-header[data-bs-toggle], .pagination { display: none !important; } .d-none.d-lg-block { display: block !important; } .d-lg-none { display: none !important; } }
</style>
<?php $__env->stopPush(); ?>
<?php /**PATH I:\Dokumente\entwicklung\laravel-tutorial\resources\views/livewire/messungen/anlagen-liste.blade.php ENDPATH**/ ?>