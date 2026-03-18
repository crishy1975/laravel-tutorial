
<div class="container-fluid py-2 py-md-4">

    
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 h3-md mb-0">
                <i class="bi bi-gear text-primary"></i>
                Anlage bearbeiten
            </h1>
            <p class="text-muted mb-0 small">
                Kodex: <strong class="font-monospace"><?php echo e($Feld_a); ?></strong>
            </p>
        </div>
        <a href="<?php echo e(route('messungen.anlagen.index')); ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i>
            <span class="d-none d-sm-inline">Zurück</span>
        </a>
    </div>

    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($saved): ?>
        <div class="alert alert-success alert-dismissible fade show py-2" role="alert">
            <i class="bi bi-check-circle"></i> Änderungen gespeichert.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <form wire:submit="save">
        <div class="row g-3">

            
            <div class="col-lg-8">

                
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-light py-2">
                        <h6 class="mb-0"><i class="bi bi-hash"></i> Identifikation</h6>
                    </div>
                    <div class="card-body py-2">
                        <div class="row g-2">
                            <div class="col-4">
                                <label class="form-label small mb-1">Anlagen-Kodex</label>
                                <input type="text" wire:model="Feld_a" class="form-control form-control-sm bg-light" readonly>
                            </div>
                            <div class="col-4">
                                <label class="form-label small mb-1">Kaminkehrer-Kodex 1</label>
                                <input type="text" wire:model="Feld_b" class="form-control form-control-sm">
                            </div>
                            <div class="col-4">
                                <label class="form-label small mb-1">Kaminkehrer-Kodex 2</label>
                                <input type="text" wire:model="Feld_c" class="form-control form-control-sm">
                            </div>
                        </div>
                    </div>
                </div>

                
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-light py-2">
                        <h6 class="mb-0"><i class="bi bi-geo-alt"></i> Aufstellungsort</h6>
                    </div>
                    <div class="card-body py-2">
                        <div class="row g-2">
                            <div class="col-12">
                                <label class="form-label small mb-1">Name Aufstellungsort</label>
                                <input type="text" wire:model="Feld_w" class="form-control form-control-sm">
                            </div>
                            <div class="col-6">
                                <label class="form-label small mb-1">Gemeinde (IT)</label>
                                <input type="text" wire:model="Feld_h" class="form-control form-control-sm">
                            </div>
                            <div class="col-6">
                                <label class="form-label small mb-1">Gemeinde (DE)</label>
                                <input type="text" wire:model="Feld_i" class="form-control form-control-sm">
                            </div>
                            <div class="col-6">
                                <label class="form-label small mb-1">Fraktion (IT)</label>
                                <input type="text" wire:model="Feld_J" class="form-control form-control-sm">
                            </div>
                            <div class="col-6">
                                <label class="form-label small mb-1">Fraktion (DE)</label>
                                <input type="text" wire:model="Feld_K" class="form-control form-control-sm">
                            </div>
                            <div class="col-5">
                                <label class="form-label small mb-1">Straße (IT)</label>
                                <input type="text" wire:model="Feld_l" class="form-control form-control-sm">
                            </div>
                            <div class="col-5">
                                <label class="form-label small mb-1">Straße (DE)</label>
                                <input type="text" wire:model="Feld_m" class="form-control form-control-sm">
                            </div>
                            <div class="col-2">
                                <label class="form-label small mb-1">Nr.</label>
                                <input type="text" wire:model="Feld_n" class="form-control form-control-sm">
                            </div>
                        </div>
                    </div>
                </div>

                
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-light py-2">
                        <h6 class="mb-0"><i class="bi bi-person"></i> Betreiber</h6>
                    </div>
                    <div class="card-body py-2">
                        <div class="row g-2">
                            <div class="col-12">
                                <label class="form-label small mb-1">Name Betreiber</label>
                                <input type="text" wire:model="Feld_o" class="form-control form-control-sm">
                            </div>
                            <div class="col-6">
                                <label class="form-label small mb-1">Gemeinde (IT)</label>
                                <input type="text" wire:model="Feld_p" class="form-control form-control-sm">
                            </div>
                            <div class="col-6">
                                <label class="form-label small mb-1">Gemeinde (DE)</label>
                                <input type="text" wire:model="Feld_q" class="form-control form-control-sm">
                            </div>
                            <div class="col-6">
                                <label class="form-label small mb-1">Fraktion (IT)</label>
                                <input type="text" wire:model="Feld_r" class="form-control form-control-sm">
                            </div>
                            <div class="col-6">
                                <label class="form-label small mb-1">Fraktion (DE)</label>
                                <input type="text" wire:model="Feld_s" class="form-control form-control-sm">
                            </div>
                            <div class="col-5">
                                <label class="form-label small mb-1">Straße (IT)</label>
                                <input type="text" wire:model="Feld_t" class="form-control form-control-sm">
                            </div>
                            <div class="col-5">
                                <label class="form-label small mb-1">Straße (DE)</label>
                                <input type="text" wire:model="Feld_u" class="form-control form-control-sm">
                            </div>
                            <div class="col-2">
                                <label class="form-label small mb-1">Nr.</label>
                                <input type="text" wire:model="Feld_v" class="form-control form-control-sm">
                            </div>
                        </div>
                    </div>
                </div>

                
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-light py-2">
                        <h6 class="mb-0"><i class="bi bi-fire"></i> Kessel</h6>
                    </div>
                    <div class="card-body py-2">
                        <div class="row g-2">
                            <div class="col-6 col-md-3">
                                <label class="form-label small mb-1">Hersteller</label>
                                <input type="text" wire:model="Feld_y" class="form-control form-control-sm">
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label small mb-1">Baujahr</label>
                                <input type="text" wire:model="Feld_z" class="form-control form-control-sm" maxlength="4">
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label small mb-1">Leistung (kW)</label>
                                <input type="text" wire:model="Feld_ab" class="form-control form-control-sm">
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label small mb-1">Status</label>
                                <input type="text" wire:model="Feld_x" class="form-control form-control-sm">
                            </div>
                        </div>
                    </div>
                </div>

                
                <div class="d-flex gap-2 mb-3">
                    <button type="submit" class="btn btn-primary"
                            wire:loading.attr="disabled" wire:target="save">
                        <span wire:loading.remove wire:target="save">
                            <i class="bi bi-check-lg"></i> Speichern
                        </span>
                        <span wire:loading wire:target="save">
                            <span class="spinner-border spinner-border-sm"></span> Speichere...
                        </span>
                    </button>
                    <a href="<?php echo e(route('messungen.anlagen.index')); ?>" class="btn btn-outline-secondary">
                        Abbrechen
                    </a>
                </div>
            </div>

            
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="bi bi-speedometer2"></i> Messungen</h6>
                        
                        <a href="<?php echo e(route('messungen.anlagen.edit', $Feld_a)); ?>" class="btn btn-success btn-sm">
                            <i class="bi bi-plus-lg"></i>
                        </a>
                    </div>
                    <?php
                        $messungen = $anlage->messungen()->orderByDesc('cMIS_DATA')->take(10)->get();
                    ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($messungen->isEmpty()): ?>
                        <div class="card-body text-center py-4">
                            <i class="bi bi-inbox display-6 text-muted"></i>
                            <p class="text-muted small mt-2 mb-0">Keine Messungen vorhanden.</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $messungen; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $messung): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="list-group-item px-3 py-2 <?php echo e($messung->strEsito === '0' ? 'list-group-item-danger' : ''); ?>">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="small fw-semibold">
                                            <i class="bi bi-calendar3"></i> <?php echo e($messung->cMIS_DATA2); ?>

                                        </span>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($messung->strEsito === '1'): ?>
                                            <span class="badge bg-success">positiv</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">negativ</span>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </div>
                                    <div class="d-flex justify-content-between small text-muted">
                                        <span><?php echo e($messung->cMIS_COMBUSTIBILE_P); ?></span>
                                        <span>
                                            CO: <strong><?php echo e($messung->cMIS_MONOSSSIDO ?: '-'); ?></strong>
                                            NOx: <strong><?php echo e($messung->cMIS_BIOSSIDO_AZOTO ?: '-'); ?></strong>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </div>

        </div>
    </form>
</div>
<?php /**PATH I:\Dokumente\entwicklung\laravel-tutorial\resources\views/livewire/messungen/anlagen-edit.blade.php ENDPATH**/ ?>