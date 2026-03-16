




<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!isset($gebaeude) || !$gebaeude->id): ?>
    <div class="alert alert-info mb-0">
        <i class="bi bi-info-circle me-2"></i>
        Das Protokoll ist erst nach dem Speichern des Gebäudes verfügbar.
    </div>
<?php else: ?>

<?php
    $logs = $gebaeude->logs()->limit(10)->get();
    $offeneErinnerungen = $gebaeude->offeneErinnerungen()->count();
    $offeneProbleme = $gebaeude->offeneProbleme()->count();
    
    // ⭐ NEU: Zähle wiederherstellbare Rechnungen
    $geloeschteRechnungen = $gebaeude->logs()
        ->where('typ', 'rechnung_geloescht')
        ->whereJsonContains('metadata->kann_wiederhergestellt_werden', true)
        ->count();
?>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
        <h6 class="mb-0">
            <i class="bi bi-clock-history me-2"></i>
            Aktivitäten
        </h6>
        <div class="d-flex gap-2">
            
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($geloeschteRechnungen > 0): ?>
                <span class="badge bg-danger" title="Gelöschte Rechnungen (wiederherstellbar)">
                    <i class="bi bi-trash"></i> <?php echo e($geloeschteRechnungen); ?>

                </span>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($offeneErinnerungen > 0): ?>
                <span class="badge bg-warning text-dark">
                    <i class="bi bi-bell-fill"></i> <?php echo e($offeneErinnerungen); ?>

                </span>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($offeneProbleme > 0): ?>
                <span class="badge bg-danger">
                    <i class="bi bi-exclamation-triangle-fill"></i> <?php echo e($offeneProbleme); ?>

                </span>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    </div>
    
    <div class="card-body p-0">
        
        <div class="p-3 border-bottom bg-light log-quick-actions">
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-sm btn-outline-secondary flex-fill flex-md-grow-0" 
                        data-bs-toggle="modal" data-bs-target="#modalNotiz">
                    <i class="bi bi-sticky"></i>
                    <span class="ms-1">Notiz</span>
                </button>
                <button type="button" class="btn btn-sm btn-outline-info flex-fill flex-md-grow-0" 
                        data-bs-toggle="modal" data-bs-target="#modalTelefonat">
                    <i class="bi bi-telephone"></i>
                    <span class="ms-1">Telefonat</span>
                </button>
                <button type="button" class="btn btn-sm btn-outline-warning flex-fill flex-md-grow-0" 
                        data-bs-toggle="modal" data-bs-target="#modalErinnerung">
                    <i class="bi bi-bell"></i>
                    <span class="ms-1">Erinnerung</span>
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger flex-fill flex-md-grow-0" 
                        data-bs-toggle="modal" data-bs-target="#modalProblem">
                    <i class="bi bi-exclamation-triangle"></i>
                    <span class="ms-1">Problem</span>
                </button>
                <a href="<?php echo e(route('gebaeude.logs.index', $gebaeude->id)); ?>" 
                   class="btn btn-sm btn-outline-primary ms-md-auto">
                    <i class="bi bi-list-ul"></i>
                    <span class="d-none d-sm-inline ms-1">Alle</span>
                </a>
            </div>
        </div>
        
        
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($logs->isEmpty()): ?>
            <div class="p-4 text-center text-muted">
                <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                <p class="mb-0 mt-2">Noch keine Einträge</p>
            </div>
        <?php else: ?>
            <div class="log-timeline">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $logs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                    // ⭐ Prüfen ob dies eine wiederherstellbare gelöschte Rechnung ist
                    $istGeloeschteRechnung = $log->typ->value === 'rechnung_geloescht' 
                        && ($log->metadata['kann_wiederhergestellt_werden'] ?? false);
                    $rechnungId = $log->metadata['rechnung_id'] ?? null;
                ?>
                <div class="log-item d-flex p-3 border-bottom 
                    <?php if($log->prioritaet === 'kritisch'): ?> bg-danger bg-opacity-10 
                    <?php elseif($log->prioritaet === 'hoch'): ?> bg-warning bg-opacity-10 
                    <?php elseif($istGeloeschteRechnung): ?> bg-danger bg-opacity-10 
                    <?php endif; ?>">
                    
                    <div class="log-icon me-3">
                        <span class="badge rounded-circle bg-<?php echo e($log->farbe); ?> p-2">
                            <i class="<?php echo e($log->icon); ?>"></i>
                        </span>
                    </div>
                    
                    
                    <div class="log-content flex-grow-1 min-w-0">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <strong class="text-truncate"><?php echo e($log->titel); ?></strong>
                            <small class="text-muted ms-2 flex-shrink-0" title="<?php echo e($log->datum_formatiert); ?>">
                                <?php echo e($log->zeit_relativ); ?>

                            </small>
                        </div>
                        
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($log->beschreibung): ?>
                            <p class="mb-1 small text-muted text-truncate-2">
                                <?php echo e(Str::limit($log->beschreibung, 120)); ?>

                            </p>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        
                        
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($istGeloeschteRechnung): ?>
                            <div class="mt-2 p-2 bg-white rounded border small">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="bi bi-receipt text-danger me-1"></i>
                                        <strong><?php echo e($log->metadata['rechnungsnummer'] ?? 'N/A'); ?></strong>
                                        <span class="text-muted ms-2">
                                            <?php echo e(number_format($log->metadata['betrag'] ?? 0, 2, ',', '.')); ?> €
                                        </span>
                                    </div>
                                    
                                    
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($rechnungId): ?>
                                    <button type="button" 
                                            class="btn btn-sm btn-success btn-restore-rechnung"
                                            data-url="<?php echo e(route('rechnung.restore', $rechnungId)); ?>"
                                            data-log-id="<?php echo e($log->id); ?>"
                                            data-nummer="<?php echo e($log->metadata['rechnungsnummer'] ?? ''); ?>"
                                            title="Rechnung wiederherstellen">
                                        <i class="bi bi-arrow-counterclockwise"></i>
                                        <span class="d-none d-sm-inline ms-1">Wiederherstellen</span>
                                    </button>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                                <div class="text-muted mt-1" style="font-size: 0.75rem;">
                                    Gelöscht von <?php echo e($log->metadata['geloescht_von'] ?? 'Unbekannt'); ?>

                                </div>
                            </div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        
                        <div class="d-flex align-items-center gap-2 small mt-2">
                            <span class="text-muted">
                                <i class="bi bi-person"></i> <?php echo e($log->benutzer_name); ?>

                            </span>
                            
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($log->erinnerung_datum && !$log->erinnerung_erledigt): ?>
                                <span class="badge bg-warning text-dark">
                                    <i class="bi bi-bell"></i> 
                                    <?php echo e($log->erinnerung_datum->format('d.m.')); ?>

                                </span>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($log->prioritaet !== 'normal'): ?>
                                <?php echo $log->prioritaet_badge; ?>

                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
            
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($gebaeude->logs()->count() > 10): ?>
            <div class="p-2 text-center border-top">
                <a href="<?php echo e(route('gebaeude.logs.index', $gebaeude->id)); ?>" class="btn btn-sm btn-link">
                    Alle <?php echo e($gebaeude->logs()->count()); ?> Einträge anzeigen
                    <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>
</div>

<style>
.log-timeline .log-item:last-child { border-bottom: none !important; }
.log-icon .badge { width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; }
.text-truncate-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.min-w-0 { min-width: 0; }

/* ⭐ Hervorhebung für gelöschte Rechnungen */
.log-item.bg-danger.bg-opacity-10 {
    border-left: 3px solid var(--bs-danger) !important;
}

/* Mobile Optimierungen */
@media (max-width: 767.98px) {
    .log-timeline .log-item {
        padding: 0.75rem !important;
    }
    
    .log-timeline .log-icon {
        margin-right: 0.5rem !important;
    }
    
    .log-timeline .log-icon .badge {
        width: 32px !important;
        height: 32px !important;
        padding: 0.35rem !important;
    }
    
    .log-timeline .log-icon .badge i {
        font-size: 0.75rem;
    }
    
    /* Quick Actions als grosse Touch-Buttons */
    .log-quick-actions .btn {
        min-height: 44px;
        font-size: 16px !important;
    }
    
    /* ⭐ Wiederherstellen-Button auf Mobile */
    .btn-restore-rechnung {
        min-height: 38px;
    }
}
</style>


<script>
document.addEventListener('DOMContentLoaded', function() {
    // Alle Wiederherstellen-Buttons
    document.querySelectorAll('.btn-restore-rechnung').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const url = this.dataset.url;
            const logId = this.dataset.logId;
            const nummer = this.dataset.nummer;
            
            if (confirm('Rechnung ' + nummer + ' wirklich wiederherstellen?')) {
                // Dynamisches Formular erstellen und absenden
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = url;
                form.style.display = 'none';
                
                // CSRF Token
                const csrf = document.createElement('input');
                csrf.type = 'hidden';
                csrf.name = '_token';
                csrf.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                form.appendChild(csrf);
                
                // Log ID
                const logInput = document.createElement('input');
                logInput.type = 'hidden';
                logInput.name = 'log_id';
                logInput.value = logId;
                form.appendChild(logInput);
                
                // Formular zum Body hinzufügen und absenden
                document.body.appendChild(form);
                form.submit();
            }
        });
    });
});
</script>

<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<?php /**PATH I:\Dokumente\entwicklung\laravel-tutorial\resources\views/gebaeude/partials/_log_timeline.blade.php ENDPATH**/ ?>