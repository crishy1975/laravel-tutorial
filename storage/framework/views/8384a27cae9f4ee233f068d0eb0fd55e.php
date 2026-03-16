


<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(isset($gebaeude) && $gebaeude->id): ?>
    <?php
        $jahr = now()->year;
        $aufschlagProzent = $gebaeude->getAufschlagProzent($jahr);
        $hatIndividuell = $gebaeude->hatIndividuellenAufschlag();
        $globalerAufschlag = \App\Models\PreisAufschlag::getGlobalerAufschlag($jahr);
    ?>

    <div class="row g-3">
        
        <div class="col-12 col-md-6">
            <div class="card <?php if($hatIndividuell): ?> border-warning <?php else: ?> border-primary <?php endif; ?> h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-building text-muted"></i>
                        <span class="small text-muted">Aktueller Aufschlag (<?php echo e($jahr); ?>)</span>
                    </div>
                    <h2 class="mb-2">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($aufschlagProzent > 0): ?>
                            <span class="text-success">+<?php echo e(number_format($aufschlagProzent, 2, ',', '.')); ?>%</span>
                        <?php elseif($aufschlagProzent < 0): ?>
                            <span class="text-danger"><?php echo e(number_format($aufschlagProzent, 2, ',', '.')); ?>%</span>
                        <?php else: ?>
                            <span class="text-muted">0,00%</span>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </h2>
                    
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($hatIndividuell): ?>
                        <span class="badge bg-warning text-dark">
                            <i class="bi bi-star-fill"></i> Individuell
                        </span>
                    <?php else: ?>
                        <span class="badge bg-primary">
                            <i class="bi bi-globe"></i> Global
                        </span>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6">
            <div class="card border-secondary h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-globe text-muted"></i>
                        <span class="small text-muted">Globaler Standard (<?php echo e($jahr); ?>)</span>
                    </div>
                    <h2 class="mb-2">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($globalerAufschlag > 0): ?>
                            <span class="text-success">+<?php echo e(number_format($globalerAufschlag, 2, ',', '.')); ?>%</span>
                        <?php else: ?>
                            <span class="text-muted">0,00%</span>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </h2>
                    <a href="<?php echo e(route('preis-aufschlaege.index')); ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-gear"></i> Verwalten
                    </a>
                </div>
            </div>
        </div>

        
        <div class="col-12">
            <div class="alert <?php if($hatIndividuell): ?> alert-warning <?php else: ?> alert-info <?php endif; ?> py-2 mb-0 small">
                <i class="bi bi-info-circle"></i>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($hatIndividuell): ?>
                    Individueller Aufschlag <strong><?php echo e(number_format($aufschlagProzent, 2, ',', '.')); ?>%</strong> aktiv.
                <?php else: ?>
                    Globaler Aufschlag <strong><?php echo e(number_format($globalerAufschlag, 2, ',', '.')); ?>%</strong> wird verwendet.
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </div>

        
        <div class="col-12">
            <div class="card">
                <div class="card-body p-2 p-md-3">
                    <div class="d-grid gap-2 d-md-flex">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($hatIndividuell): ?>
                            <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#modalAufschlagBearbeiten">
                                <i class="bi bi-pencil"></i> Bearbeiten
                            </button>
                            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalAufschlagEntfernen">
                                <i class="bi bi-x-circle"></i> Entfernen
                            </button>
                        <?php else: ?>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAufschlagSetzen">
                                <i class="bi bi-star"></i> Individuellen Aufschlag festlegen
                            </button>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        
                        <button type="button" class="btn btn-outline-info ms-md-auto" data-bs-toggle="modal" data-bs-target="#modalAufschlagVorschau">
                            <i class="bi bi-eye"></i> Vorschau
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php else: ?>
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i>
        Preis-Aufschlag erst nach Erstellen des Gebaeudes verfuegbar.
    </div>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<?php /**PATH I:\Dokumente\entwicklung\laravel-tutorial\resources\views/gebaeude/partials/_aufschlag.blade.php ENDPATH**/ ?>