
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
                <i class="bi bi-speedometer2 text-primary"></i>
                Messungen
            </h1>
            <p class="text-muted mb-0 small d-none d-md-block">
                <?php echo e($statistik['total']); ?> Messungen in <?php echo e($filterJahr); ?>

            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?php echo e(route('messungen.anlagen.index')); ?>" class="btn btn-outline-secondary">
                <i class="bi bi-building"></i>
                <span class="d-none d-sm-inline">Anlagen</span>
            </a>
            <button wire:click="openMessungModal" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i>
                <span class="d-none d-sm-inline">Neue Messung</span>
            </button>
            <a href="<?php echo e(route('messungen.import')); ?>" class="btn btn-success">
                <i class="bi bi-file-earmark-arrow-up"></i>
                <span class="d-none d-sm-inline">XML Import</span>
            </a>
        </div>
    </div>

    
    <div class="row g-2 mb-3">
        <div class="col-6 col-md-3">
            <div class="card border-primary h-100 stat-card">
                <div class="card-body text-center py-2">
                    <div class="stat-number text-primary"><?php echo e($statistik['total']); ?></div>
                    <div class="stat-label">Gesamt</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-success h-100 stat-card cursor-pointer"
                 wire:click="$set('filterErgebnis', '1')" role="button">
                <div class="card-body text-center py-2">
                    <div class="stat-number text-success"><?php echo e($statistik['positiv']); ?></div>
                    <div class="stat-label">Positiv</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-danger h-100 stat-card cursor-pointer"
                 wire:click="$set('filterErgebnis', '0')" role="button">
                <div class="card-body text-center py-2">
                    <div class="stat-number text-danger"><?php echo e($statistik['negativ']); ?></div>
                    <div class="stat-label">Negativ</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-warning h-100 stat-card cursor-pointer"
                 wire:click="$set('filterOhneAnlage', '1')" role="button">
                <div class="card-body text-center py-2">
                    <div class="stat-number text-warning"><?php echo e($statistik['ohneAnlage']); ?></div>
                    <div class="stat-label">Ohne Anlage</div>
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
                    $activeFilters = collect([$filterKodex, $filterName, $filterErgebnis, $filterBrennstoff, $filterOhneAnlage])->filter()->count();
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
                        <label class="form-label small mb-1">Jahr</label>
                        <select wire:model.live="filterJahr" class="form-select form-select-sm">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php for($y = date('Y'); $y >= 2020; $y--): ?>
                                <option value="<?php echo e($y); ?>"><?php echo e($y); ?></option>
                            <?php endfor; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1">Kodex</label>
                        <input type="text" wire:model.live.debounce.300ms="filterKodex"
                               class="form-control form-control-sm" placeholder="Kodex...">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1">Name</label>
                        <input type="text" wire:model.live.debounce.300ms="filterName"
                               class="form-control form-control-sm" placeholder="Name...">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1">Ergebnis</label>
                        <select wire:model.live="filterErgebnis" class="form-select form-select-sm">
                            <option value="">Alle</option>
                            <option value="1">✓ Positiv</option>
                            <option value="0">✗ Negativ</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1">Brennstoff</label>
                        <select wire:model.live="filterBrennstoff" class="form-select form-select-sm">
                            <option value="">Alle</option>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $brennstoffe; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $info): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($key); ?>"><?php echo e($info['text']); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1">Anlage</label>
                        <select wire:model.live="filterOhneAnlage" class="form-select form-select-sm">
                            <option value="">Alle</option>
                            <option value="0">Mit Anlage</option>
                            <option value="1">Ohne Anlage</option>
                        </select>
                    </div>
                </div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($activeFilters > 0): ?>
                    <div class="mt-2">
                        <button wire:click="resetFilters" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-x-lg"></i> Filter zurücksetzen
                        </button>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </div>
    </div>

    
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0" id="messungenTable">
                    <thead class="table-dark">
                        <tr>
                            <th wire:click="sortBy('cIM_CODICE')" class="text-nowrap">
                                Kodex
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sortField === 'cIM_CODICE'): ?>
                                    <i class="bi bi-chevron-<?php echo e($sortDirection === 'asc' ? 'up' : 'down'); ?>"></i>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </th>
                            <th wire:click="sortBy('cIM_NAME')" class="text-nowrap">
                                Name
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sortField === 'cIM_NAME'): ?>
                                    <i class="bi bi-chevron-<?php echo e($sortDirection === 'asc' ? 'up' : 'down'); ?>"></i>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </th>
                            <th wire:click="sortBy('cMIS_DATA')" class="text-nowrap">
                                Datum
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sortField === 'cMIS_DATA'): ?>
                                    <i class="bi bi-chevron-<?php echo e($sortDirection === 'asc' ? 'up' : 'down'); ?>"></i>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </th>
                            <th class="text-center d-none d-md-table-cell">Stadio</th>
                            <th class="text-center d-none d-md-table-cell">Brennstoff</th>
                            <th class="text-center">Ergebnis</th>
                            <th class="text-center" style="width: 80px;">Aktion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $messungen; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr class="<?php echo e($m->strEsito === '0' ? 'table-danger' : ''); ?>">
                                <td class="text-nowrap">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($m->codeInImpianti == 0): ?>
                                        <span class="text-muted"><?php echo e($m->cIM_CODICE ?: '─'); ?></span>
                                    <?php else: ?>
                                        <?php echo e($m->cIM_CODICE); ?>

                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </td>
                                <td class="text-truncate" style="max-width: 200px;">
                                    <?php echo e($m->cIM_NAME); ?>

                                </td>
                                <td class="text-nowrap">
                                    <?php echo e($m->cMIS_DATA2); ?>

                                    <small class="text-muted"><?php echo e($m->cMIS_ORA); ?></small>
                                </td>
                                <td class="text-center d-none d-md-table-cell"><?php echo e($m->cMIS_STADIO); ?></td>
                                <td class="text-center d-none d-md-table-cell">
                                    <small><?php echo e($m->cMIS_COMBUSTIBILE_P); ?></small>
                                </td>
                                <td class="text-center">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($m->strEsito === '1'): ?>
                                        <span class="badge bg-success">✓</span>
                                    <?php elseif($m->strEsito === '0'): ?>
                                        <span class="badge bg-danger">✗</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">─</span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </td>
                                <td class="text-center text-nowrap">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($m->codeInImpianti == 0): ?>
                                        <button wire:click="openAnlageModal(<?php echo e($m->id); ?>)"
                                                class="btn btn-sm btn-outline-primary" title="Anlage zuordnen">
                                            <i class="bi bi-link"></i>
                                        </button>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    <button wire:click="delete(<?php echo e($m->id); ?>)"
                                            wire:confirm="Messung wirklich löschen?"
                                            class="btn btn-sm btn-outline-danger" title="Löschen">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox fs-1 d-block mb-2 opacity-25"></i>
                                    Keine Messungen gefunden
                                </td>
                            </tr>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($messungen->hasPages()): ?>
            <div class="card-footer bg-light py-2">
                <?php echo e($messungen->links()); ?>

            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>

    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($showAnlageModal): ?>
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-lg modal-dialog-scrollable modal-fullscreen-sm-down">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white py-2">
                        <h5 class="modal-title">
                            <i class="bi bi-link"></i> Anlage zuordnen
                        </h5>
                        <button type="button" class="btn-close btn-close-white" wire:click="closeAnlageModal"></button>
                    </div>
                    <div class="modal-body p-2 p-md-3">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($selectedMessung): ?>
                            <div class="alert alert-info py-2 mb-3">
                                <strong>Messung:</strong> <?php echo e($selectedMessung->cIM_NAME); ?>

                                <br><small>Datum: <?php echo e($selectedMessung->cMIS_DATA2); ?> <?php echo e($selectedMessung->cMIS_ORA); ?></small>
                            </div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                        
                        <div class="row g-2 mb-3">
                            <div class="col-6 col-md-3">
                                <label class="form-label small mb-1">Aufstellungsort</label>
                                <input type="text" wire:model.live.debounce.300ms="anlageSearchName"
                                       class="form-control form-control-sm" placeholder="Name...">
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label small mb-1">Gemeinde</label>
                                <input type="text" wire:model.live.debounce.300ms="anlageSearchOrt"
                                       class="form-control form-control-sm" placeholder="Ort...">
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label small mb-1">Straße</label>
                                <input type="text" wire:model.live.debounce.300ms="anlageSearchStrasse"
                                       class="form-control form-control-sm" placeholder="Straße...">
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label small mb-1">Hausnr.</label>
                                <input type="text" wire:model.live.debounce.300ms="anlageSearchNummer"
                                       class="form-control form-control-sm" placeholder="Nr...">
                            </div>
                        </div>

                        
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(count($anlageSearchResults) > 0): ?>
                            <div class="table-responsive" style="max-height: 300px;">
                                <table class="table table-sm table-hover mb-0">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th>Kodex</th>
                                            <th>Aufstellungsort</th>
                                            <th>Adresse</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $anlageSearchResults; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $anlage): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <tr>
                                                <td class="text-nowrap"><?php echo e($anlage->Feld_a); ?></td>
                                                <td><?php echo e($anlage->Feld_w); ?></td>
                                                <td class="small">
                                                    <?php echo e($anlage->Feld_m); ?> <?php echo e($anlage->Feld_n); ?>,
                                                    <?php echo e($anlage->Feld_i); ?>

                                                </td>
                                                <td class="text-end">
                                                    <button wire:click="zuordnenAnlage('<?php echo e($anlage->Feld_a); ?>')"
                                                            class="btn btn-sm btn-success">
                                                        <i class="bi bi-check-lg"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($anlageSearchTotal > 10): ?>
                                <div class="d-flex justify-content-between align-items-center mt-2 small">
                                    <span><?php echo e($anlageSearchTotal); ?> Ergebnisse</span>
                                    <div class="btn-group btn-group-sm">
                                        <button wire:click="anlagePagePrev" class="btn btn-outline-secondary"
                                                <?php if($anlageSearchPage <= 1): ?> disabled <?php endif; ?>>
                                            <i class="bi bi-chevron-left"></i>
                                        </button>
                                        <span class="btn btn-outline-secondary disabled">
                                            <?php echo e($anlageSearchPage); ?> / <?php echo e(ceil($anlageSearchTotal / 10)); ?>

                                        </span>
                                        <button wire:click="anlagePageNext" class="btn btn-outline-secondary"
                                                <?php if($anlageSearchPage >= ceil($anlageSearchTotal / 10)): ?> disabled <?php endif; ?>>
                                            <i class="bi bi-chevron-right"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <?php elseif($anlageSearchName || $anlageSearchOrt || $anlageSearchStrasse): ?>
                            <div class="text-center text-muted py-4">
                                <i class="bi bi-search fs-1 d-block mb-2 opacity-25"></i>
                                Keine Anlagen gefunden
                            </div>
                        <?php else: ?>
                            <div class="text-center text-muted py-4">
                                <i class="bi bi-building fs-1 d-block mb-2 opacity-25"></i>
                                Gib einen Suchbegriff ein
                            </div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-secondary" wire:click="closeAnlageModal">
                            <i class="bi bi-x-lg"></i> Schließen
                        </button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($showMessungModal): ?>
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-lg modal-dialog-scrollable modal-fullscreen-sm-down">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white py-2">
                        <h5 class="modal-title">
                            <i class="bi bi-plus-circle"></i> Neue Messung (ohne Anlage)
                        </h5>
                        <button type="button" class="btn-close btn-close-white" wire:click="closeMessungModal"></button>
                    </div>
                    <div class="modal-body p-2 p-md-3">
                        
                        
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
                                        processImage(file) {
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
                                                        body: JSON.stringify({ image: e.target.result.split(',')[1] })
                                                    });
                                                    const data = await res.json();
                                                    if (data.success) {
                                                        $wire.set('messung.cMIS_DATA2', data.datum || '');
                                                        $wire.set('messung.cMIS_ORA', data.uhrzeit || '');
                                                        $wire.set('messung.cMIS_OSSIGENO', data.o2 || '');
                                                        $wire.set('messung.cMIS_ANIDRIDE_CARBONICA', data.co2 || '');
                                                        $wire.set('messung.cMIS_PERD_FUMI', data.qa || '');
                                                        $wire.set('messung.cMIS_MONOSSSIDO', data.co || '');
                                                        $wire.set('messung.cMIS_BIOSSIDO_AZOTO', data.nox || '');
                                                        $wire.set('messung.cMIS_T_ARIA_COMB', data.t_luft || '');
                                                        $wire.set('messung.cMIS_T_GAS_COMB', data.t_abgas || '');
                                                        $wire.set('messung.cMIS_IND_OPACITA', data.russ || '0');
                                                        if (data.brennstoff) $wire.set('messung.cMIS_COMBUSTIBILE', data.brennstoff);
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
                                                       @change="processImage($event.target.files[0]); $refs.kameraInput.value = '';">
                                            </label>
                                            
                                            <label class="btn btn-outline-primary btn-sm mb-0" :class="{ 'disabled': loading }">
                                                <span x-show="!loading"><i class="bi bi-images me-1"></i> Galerie</span>
                                                <span x-show="loading"><i class="bi bi-hourglass-split me-1"></i> Analysiert...</span>
                                                <input type="file" accept="image/*" 
                                                       class="d-none" x-ref="galerieInput"
                                                       @change="processImage($event.target.files[0]); $refs.galerieInput.value = '';">
                                            </label>
                                            <span x-show="status === 'success'" class="badge bg-success"><i class="bi bi-check-lg"></i> Werte übernommen</span>
                                            <span x-show="status && status !== 'success'" class="badge bg-danger" x-text="status"></span>
                                        </div>
                                    </div>

                                    
                                    <div class="card mb-2">
                                        <div class="card-header bg-light py-1 px-2">
                                            <h6 class="mb-0 small"><i class="bi bi-person"></i> Kunde / Anlage</h6>
                                        </div>
                                        <div class="card-body py-2 px-2">
                                            <div class="row g-2">
                                                <div class="col-4 col-md-3">
                                                    <label class="form-label small mb-0">Kodex <span class="text-danger">*</span></label>
                                                    <input type="text" wire:model="messung.cIM_CODICE"
                                                           class="form-control form-control-sm" required>
                                                </div>
                                                <div class="col-8 col-md-9">
                                                    <label class="form-label small mb-0">Name / Aufstellungsort <span class="text-danger">*</span></label>
                                                    <input type="text" wire:model="messung.cIM_NAME"
                                                           class="form-control form-control-sm" placeholder="z.B. Mustermann GmbH" required>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small mb-0">Baujahr</label>
                                                    <input type="text" wire:model.live="messung.boilerYear"
                                                           class="form-control form-control-sm" placeholder="2010">
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small mb-0">Leistung kW</label>
                                                    <input type="text" wire:model.live="messung.boilerPower"
                                                           class="form-control form-control-sm" placeholder="50">
                                                </div>
                                            </div>
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
                                                           class="form-control form-control-sm" placeholder="TT.MM.JJJJ" required>
                                                </div>
                                                <div class="col-4 col-md-2">
                                                    <label class="form-label small mb-0">Uhrzeit</label>
                                                    <input type="text" wire:model="messung.cMIS_ORA"
                                                           class="form-control form-control-sm" placeholder="HH:MM">
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
                                                <div class="col-4">
                                                    <label class="form-label small mb-0">O₂ %</label>
                                                    <input type="text" wire:model="messung.cMIS_OSSIGENO"
                                                           class="form-control form-control-sm" placeholder="8.3">
                                                </div>
                                                <div class="col-4">
                                                    <label class="form-label small mb-0">CO₂ %</label>
                                                    <input type="text" wire:model="messung.cMIS_ANIDRIDE_CARBONICA"
                                                           class="form-control form-control-sm" placeholder="7.1">
                                                </div>
                                                <div class="col-4">
                                                    <label class="form-label small mb-0">Qa %</label>
                                                    <input type="text" wire:model="messung.cMIS_PERD_FUMI"
                                                           class="form-control form-control-sm" placeholder="2.7">
                                                </div>
                                            </div>
                                            <div class="row g-2 mt-1">
                                                <div class="col-6">
                                                    <label class="form-label small mb-0">CO mg/m³</label>
                                                    <input type="text" wire:model.live.debounce.500ms="messung.cMIS_MONOSSSIDO"
                                                           class="form-control form-control-sm" placeholder="31">
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small mb-0">NOx mg/m³</label>
                                                    <input type="text" wire:model.live.debounce.500ms="messung.cMIS_BIOSSIDO_AZOTO"
                                                           class="form-control form-control-sm" placeholder="82">
                                                </div>
                                            </div>
                                            <div class="row g-2 mt-1">
                                                <div class="col-4">
                                                    <label class="form-label small mb-0">T Luft °C</label>
                                                    <input type="text" wire:model="messung.cMIS_T_ARIA_COMB"
                                                           class="form-control form-control-sm" placeholder="18">
                                                </div>
                                                <div class="col-4">
                                                    <label class="form-label small mb-0">T Abgas °C</label>
                                                    <input type="text" wire:model="messung.cMIS_T_GAS_COMB"
                                                           class="form-control form-control-sm" placeholder="61">
                                                </div>
                                                <div class="col-4">
                                                    <label class="form-label small mb-0">T Wärmetr. °C</label>
                                                    <input type="text" wire:model="messung.cMIS_T_LIQ_CONV"
                                                           class="form-control form-control-sm" placeholder="51">
                                                </div>
                                            </div>
                                            <div class="row g-2 mt-1">
                                                <div class="col-6">
                                                    <label class="form-label small mb-0">Rußzahl</label>
                                                    <input type="text" wire:model.live.debounce.500ms="messung.cMIS_IND_OPACITA"
                                                           class="form-control form-control-sm" placeholder="0">
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small mb-0">Ölspuren</label>
                                                    <select wire:model.live="messung.cMIS_TRACCE_OLEO" class="form-select form-select-sm">
                                                        <option value="1">Nein</option>
                                                        <option value="0">Ja</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                
                                <div class="col-12 col-md-4">
                                    <div class="card h-100">
                                        <div class="card-header bg-light py-1 px-2">
                                            <h6 class="mb-0 small"><i class="bi bi-shield-check"></i> Grenzwerte</h6>
                                        </div>
                                        <div class="card-body py-2 px-2">
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($grenzwerte): ?>
                                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = ['co' => 'CO', 'nox' => 'NOx', 'russ' => 'Rußzahl', 'oel' => 'Ölspuren']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <?php
                                                        $g = $grenzwerte[$key] ?? [];
                                                        $status = $g['status'] ?? 'gruen';
                                                        $grenzwert = $g['grenzwert'] ?? '-';
                                                        $wert = match($key) {
                                                            'co' => $messung['cMIS_MONOSSSIDO'] ?? '-',
                                                            'nox' => $messung['cMIS_BIOSSIDO_AZOTO'] ?? '-',
                                                            'russ' => $messung['cMIS_IND_OPACITA'] ?? '-',
                                                            'oel' => ($messung['cMIS_TRACCE_OLEO'] ?? '1') === '0' ? 'Ja' : 'Nein',
                                                            default => '-'
                                                        };
                                                        $bgClass = match($status) {
                                                            'rot' => 'bg-danger bg-opacity-10 border-danger',
                                                            'gelb' => 'bg-warning bg-opacity-10 border-warning',
                                                            default => 'bg-success bg-opacity-10 border-success'
                                                        };
                                                    ?>
                                                    <div class="d-flex justify-content-between align-items-center p-2 mb-1 rounded border <?php echo e($bgClass); ?>">
                                                        <div>
                                                            <strong><?php echo e($label); ?></strong><br>
                                                            <small class="text-muted">
                                                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($key === 'oel'): ?>
                                                                    keine erlaubt
                                                                <?php else: ?>
                                                                    max. <?php echo e($grenzwert); ?> <?php echo e($key === 'russ' ? '' : 'mg/m³'); ?>

                                                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                            </small>
                                                        </div>
                                                        <div class="text-end">
                                                            <span class="h5 mb-0"><?php echo e($wert); ?></span>
                                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($status === 'gruen'): ?>
                                                                <span class="text-success"><i class="bi bi-check-circle-fill"></i></span>
                                                            <?php elseif($status === 'gelb'): ?>
                                                                <span class="text-warning"><i class="bi bi-exclamation-triangle-fill"></i></span>
                                                            <?php else: ?>
                                                                <span class="text-danger"><i class="bi bi-x-circle-fill"></i></span>
                                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                        </div>
                                                    </div>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                            <?php else: ?>
                                                <div class="text-center text-muted py-4">
                                                    <i class="bi bi-speedometer2 fs-1 opacity-25"></i>
                                                    <p class="mb-0 small mt-2">Gib Messwerte ein um die Grenzwertprüfung zu sehen.</p>
                                                </div>
                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            
                            <div class="modal-footer border-top mt-3 pt-2 pb-0 px-0">
                                <button type="button" class="btn btn-secondary" wire:click="closeMessungModal">
                                    <i class="bi bi-x-lg"></i> Abbrechen
                                </button>
                                <button type="submit" class="btn btn-success">
                                    <i class="bi bi-check-lg"></i> Speichern
                                </button>
                            </div>
                        </form>
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
    @media (min-width: 768px) { .stat-number { font-size: 2rem; } .stat-label { font-size: 0.75rem; } }
    .transition-transform { transition: transform 0.3s; }
    [aria-expanded="false"] .transition-transform { transform: rotate(-90deg); }
    #messungenTable thead th { cursor: pointer; user-select: none; }
    #messungenTable thead th:hover { background-color: rgba(255,255,255,0.1); }
    #messungenTable tbody tr { cursor: pointer; transition: background-color 0.15s; }
    #messungenTable tbody tr:hover { background-color: rgba(0, 123, 255, 0.05); }
    #messungenTable tbody tr.table-danger:hover { background-color: rgba(220, 53, 69, 0.15); }
    @media (max-width: 575.98px) { .container-fluid { padding-left: 0.5rem; padding-right: 0.5rem; } .card-body { padding: 0.5rem; } .form-label { font-size: 0.75rem; } .stat-number { font-size: 1.25rem; } }
</style>
<?php $__env->stopPush(); ?>
<?php /**PATH I:\Dokumente\entwicklung\laravel-tutorial\resources\views/livewire/messungen/messungen-liste.blade.php ENDPATH**/ ?>