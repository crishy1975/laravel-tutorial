

<?php
    $dokumente = $gebaeude->dokumente ?? collect();
    $kategorien = \App\Models\GebaeudeDocument::KATEGORIEN;
?>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="mb-0">
            <i class="bi bi-folder2-open me-2"></i>
            Dokumente
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($dokumente->count() > 0): ?>
                <span class="badge bg-primary ms-1"><?php echo e($dokumente->count()); ?></span>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </h5>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(isset($gebaeude) && $gebaeude->id): ?>
            <div class="btn-group btn-group-sm">
                
                <button type="button" class="btn btn-primary d-none d-sm-inline-flex" 
                        data-bs-toggle="modal" data-bs-target="#modalDokumentUpload">
                    <i class="bi bi-upload me-1"></i>Hochladen
                </button>
                
                <button type="button" class="btn btn-success d-sm-none" 
                        data-bs-toggle="modal" data-bs-target="#modalFotoAufnehmen">
                    <i class="bi bi-camera-fill"></i>
                </button>
                <button type="button" class="btn btn-primary d-sm-none" 
                        data-bs-toggle="modal" data-bs-target="#modalDokumentUpload">
                    <i class="bi bi-folder2-open"></i>
                </button>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!isset($gebaeude) || !$gebaeude->id): ?>
        <div class="card-body">
            <div class="alert alert-info mb-0">
                <i class="bi bi-info-circle me-2"></i>
                Dokumente können erst nach dem Speichern des Gebäudes hochgeladen werden.
            </div>
        </div>
    <?php elseif($dokumente->isEmpty()): ?>
        <div class="card-body text-center py-4">
            <i class="bi bi-folder text-muted" style="font-size: 2.5rem;"></i>
            <h6 class="mt-3 text-muted">Keine Dokumente vorhanden</h6>
            <p class="text-muted small mb-3">Laden Sie Verträge, Fotos oder andere Dokumente hoch.</p>
            
            
            <button type="button" class="btn btn-outline-primary d-none d-sm-inline-block" 
                    data-bs-toggle="modal" data-bs-target="#modalDokumentUpload">
                <i class="bi bi-upload me-1"></i>Erstes Dokument hochladen
            </button>
            
            
            <div class="d-sm-none d-flex justify-content-center gap-2">
                <button type="button" class="btn btn-success" 
                        data-bs-toggle="modal" data-bs-target="#modalFotoAufnehmen">
                    <i class="bi bi-camera-fill me-1"></i>Foto
                </button>
                <button type="button" class="btn btn-primary" 
                        data-bs-toggle="modal" data-bs-target="#modalDokumentUpload">
                    <i class="bi bi-folder2-open me-1"></i>Datei
                </button>
            </div>
        </div>
    <?php else: ?>
        
        <div class="list-group list-group-flush" id="dokumenteListe">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $dokumente->sortByDesc('created_at')->take(10); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dok): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="list-group-item dokument-item" id="dokument-<?php echo e($dok->id); ?>">
                    <div class="d-flex gap-2 align-items-start">
                        
                        <div class="flex-shrink-0">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($dok->ist_bild): ?>
                                <a href="<?php echo e(route('gebaeude.dokumente.preview', $dok->id)); ?>" target="_blank">
                                    <img src="<?php echo e(route('gebaeude.dokumente.thumbnail', $dok->id)); ?>" 
                                         alt="<?php echo e($dok->titel); ?>"
                                         class="rounded border"
                                         style="width: 50px; height: 50px; object-fit: cover;">
                                </a>
                            <?php else: ?>
                                <div class="rounded bg-light d-flex align-items-center justify-content-center" 
                                     style="width: 50px; height: 50px;">
                                    <i class="<?php echo e($dok->icon); ?> fs-4"></i>
                                </div>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                        
                        
                        <div class="flex-grow-1 min-w-0">
                            
                            <div class="d-flex align-items-center gap-1 mb-1">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($dok->ist_wichtig): ?>
                                    <i class="bi bi-star-fill text-warning flex-shrink-0"></i>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <a href="<?php echo e($dok->download_url); ?>" 
                                   class="fw-semibold text-decoration-none text-truncate d-block">
                                    <?php echo e($dok->titel); ?>

                                </a>
                            </div>
                            
                            
                            <div class="d-flex flex-wrap align-items-center gap-2 small text-muted">
                                <span class="badge bg-light text-dark"><?php echo e($dok->kategorie_label); ?></span>
                                <span><?php echo e($dok->dateigroesse_formatiert); ?></span>
                                <span class="d-none d-sm-inline"><?php echo e($dok->created_at->format('d.m.Y')); ?></span>
                            </div>
                        </div>
                        
                        
                        <div class="flex-shrink-0">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary" type="button" 
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($dok->ist_bild || $dok->ist_pdf): ?>
                                        <li>
                                            <a class="dropdown-item" href="<?php echo e(route('gebaeude.dokumente.preview', $dok->id)); ?>" target="_blank">
                                                <i class="bi bi-eye me-2"></i>Vorschau
                                            </a>
                                        </li>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    <li>
                                        <a class="dropdown-item" href="<?php echo e($dok->download_url); ?>">
                                            <i class="bi bi-download me-2"></i>Download
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <button type="button" class="dropdown-item btn-edit-dokument"
                                                data-id="<?php echo e($dok->id); ?>"
                                                data-titel="<?php echo e($dok->titel); ?>"
                                                data-beschreibung="<?php echo e($dok->beschreibung); ?>"
                                                data-kategorie="<?php echo e($dok->kategorie); ?>"
                                                data-tags="<?php echo e($dok->tags); ?>"
                                                data-wichtig="<?php echo e($dok->ist_wichtig ? '1' : '0'); ?>">
                                            <i class="bi bi-pencil me-2"></i>Bearbeiten
                                        </button>
                                    </li>
                                    <li>
                                        <button type="button" class="dropdown-item text-warning btn-toggle-wichtig"
                                                data-id="<?php echo e($dok->id); ?>">
                                            <i class="bi bi-star<?php echo e($dok->ist_wichtig ? '-fill' : ''); ?> me-2"></i>
                                            <?php echo e($dok->ist_wichtig ? 'Nicht mehr wichtig' : 'Als wichtig markieren'); ?>

                                        </button>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <button type="button" class="dropdown-item text-danger btn-delete-dokument"
                                                data-id="<?php echo e($dok->id); ?>"
                                                data-titel="<?php echo e($dok->titel); ?>">
                                            <i class="bi bi-trash me-2"></i>Löschen
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>

        
        <div class="card-footer bg-white">
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
                <?php if($dokumente->count() > 10): ?>
                    <a href="<?php echo e(route('gebaeude.dokumente.index', ['gebaeude_id' => $gebaeude->id])); ?>" 
                       class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-folder2-open me-1"></i>Alle <?php echo e($dokumente->count()); ?> anzeigen
                    </a>
                <?php else: ?>
                    <span></span>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                
                
                <div class="d-sm-none d-flex gap-2">
                    <button type="button" class="btn btn-success btn-sm" 
                            data-bs-toggle="modal" data-bs-target="#modalFotoAufnehmen">
                        <i class="bi bi-camera-fill me-1"></i>Foto
                    </button>
                    <button type="button" class="btn btn-primary btn-sm" 
                            data-bs-toggle="modal" data-bs-target="#modalDokumentUpload">
                        <i class="bi bi-plus-lg me-1"></i>Datei
                    </button>
                </div>
            </div>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</div>

<style>
.dokument-item {
    transition: background-color 0.2s ease;
}
.dokument-item:active {
    background-color: #f8f9fa;
}
.min-w-0 { min-width: 0; }

@media (max-width: 575.98px) {
    .dokument-item {
        padding: 0.75rem !important;
    }
}
</style>

<?php /**PATH I:\Dokumente\entwicklung\laravel-tutorial\resources\views/gebaeude-dokumente/_dokumente.blade.php ENDPATH**/ ?>