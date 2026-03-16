
<div class="container-fluid py-2 py-md-4">

    
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 h3-md mb-0">
                <i class="bi bi-speedometer2 text-primary"></i>
                <?php echo e($isNew ? 'Neue Messung' : 'Messung bearbeiten'); ?>

            </h1>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!$isNew): ?>
                <p class="text-muted mb-0 small">
                    Kodex <?php echo e($cIM_CODICE); ?> / <?php echo e($datum); ?>

                </p>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
        <a href="<?php echo e(route('messungen.index')); ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i>
            <span class="d-none d-sm-inline">Zurück</span>
        </a>
    </div>

    <form wire:submit="save">
        <div class="row g-3">
            
            <div class="col-lg-8">
                
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-light py-2">
                        <h6 class="mb-0"><i class="bi bi-info-circle"></i> Grunddaten</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-6 col-md-3">
                                <label class="form-label small mb-1">Anlagen-Kodex *</label>
                                <input type="text" wire:model.live.debounce.500ms="cIM_CODICE"
                                       class="form-control form-control-sm font-monospace <?php $__errorArgs = ['cIM_CODICE'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                       <?php echo e(!$isNew ? 'readonly' : ''); ?>>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['cIM_CODICE'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label small mb-1">Stadio</label>
                                <input type="number" wire:model="cMIS_STADIO"
                                       class="form-control form-control-sm" min="1" max="99">
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label small mb-1">Datum *</label>
                                <input type="date" wire:model="datum"
                                       class="form-control form-control-sm <?php $__errorArgs = ['datum'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['datum'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label small mb-1">Uhrzeit *</label>
                                <input type="time" wire:model="uhrzeit" step="1"
                                       class="form-control form-control-sm <?php $__errorArgs = ['uhrzeit'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['uhrzeit'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small mb-1">Kunde/Name</label>
                                <input type="text" wire:model="cIM_NAME"
                                       class="form-control form-control-sm">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small mb-1">Brennstoff *</label>
                                <select wire:model.live="cMIS_COMBUSTIBILE" class="form-select form-select-sm">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $brennstoffe; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($key); ?>"><?php echo e($value['text']); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-light py-2">
                        <h6 class="mb-0"><i class="bi bi-thermometer-half"></i> Messwerte</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-4 col-md-2">
                                <label class="form-label small mb-1">O₂ %</label>
                                <input type="number" wire:model.live.debounce.500ms="cMIS_OSSIGENO"
                                       class="form-control form-control-sm" step="0.1" min="0" max="21">
                            </div>
                            <div class="col-4 col-md-2">
                                <label class="form-label small mb-1">CO₂ %</label>
                                <input type="number" wire:model="cMIS_ANIDRIDE_CARBONICA"
                                       class="form-control form-control-sm" step="0.1" min="0" max="20">
                            </div>
                            <div class="col-4 col-md-2">
                                <label class="form-label small mb-1">CO mg/m³</label>
                                <input type="number" wire:model.live.debounce.500ms="cMIS_MONOSSSIDO"
                                       class="form-control form-control-sm <?php if($cMIS_MONOSSSIDO > 500): ?> is-invalid border-danger <?php endif; ?>"
                                       min="0" max="999999">
                            </div>
                            <div class="col-4 col-md-2">
                                <label class="form-label small mb-1">NOx mg/m³</label>
                                <input type="number" wire:model.live.debounce.500ms="cMIS_BIOSSIDO_AZOTO"
                                       class="form-control form-control-sm <?php if($cMIS_BIOSSIDO_AZOTO > 200): ?> is-invalid border-warning <?php endif; ?>"
                                       min="0" max="9999">
                            </div>
                            <div class="col-4 col-md-2">
                                <label class="form-label small mb-1">Abg.Verl. %</label>
                                <input type="number" wire:model="cMIS_PERD_FUMI"
                                       class="form-control form-control-sm" step="0.1" min="0" max="100">
                            </div>
                            <div class="col-4 col-md-2">
                                <label class="form-label small mb-1">Wirkungsgrad</label>
                                <input type="text" value="<?php echo e($wirkungsgrad ? $wirkungsgrad . ' %' : '-'); ?>"
                                       class="form-control form-control-sm bg-light" readonly>
                            </div>
                        </div>
                        <hr class="my-2">
                        <div class="row g-2">
                            <div class="col-4 col-md-2">
                                <label class="form-label small mb-1">Abgastemp. °C</label>
                                <input type="number" wire:model="cMIS_T_GAS_COMB"
                                       class="form-control form-control-sm" min="0" max="999">
                            </div>
                            <div class="col-4 col-md-2">
                                <label class="form-label small mb-1">Lufttemp. °C</label>
                                <input type="number" wire:model="cMIS_T_ARIA_COMB"
                                       class="form-control form-control-sm" min="-20" max="99">
                            </div>
                            <div class="col-4 col-md-2">
                                <label class="form-label small mb-1">Kesseltemp. °C</label>
                                <input type="number" wire:model="cMIS_T_LIQ_CONV"
                                       class="form-control form-control-sm" min="0" max="999">
                            </div>
                            <div class="col-4 col-md-2">
                                <label class="form-label small mb-1">Rußzahl (0-9)</label>
                                <input type="number" wire:model.live.debounce.500ms="cMIS_IND_OPACITA"
                                       class="form-control form-control-sm" min="0" max="9">
                            </div>
                            <div class="col-4 col-md-2">
                                <label class="form-label small mb-1">Ölspuren</label>
                                <select wire:model.live="cMIS_TRACCE_OLEO" class="form-select form-select-sm">
                                    <option value="1">Nein</option>
                                    <option value="0">Ja</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-light py-2">
                        <h6 class="mb-0"><i class="bi bi-fire"></i> Kessel</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-6 col-md-3">
                                <label class="form-label small mb-1">Baujahr</label>
                                <input type="number" wire:model.live.debounce.500ms="boilerYear"
                                       class="form-control form-control-sm" min="1900" max="2100">
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label small mb-1">Leistung kW</label>
                                <input type="number" wire:model.live.debounce.500ms="boilerPower"
                                       class="form-control form-control-sm" min="0" max="9999">
                            </div>
                        </div>
                    </div>
                </div>

                
                <div class="d-flex gap-2 mb-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg"></i>
                        <?php echo e($isNew ? 'Erstellen' : 'Speichern'); ?>

                    </button>
                    <button type="button" wire:click="berechneGrenzwerte" class="btn btn-outline-secondary">
                        <i class="bi bi-calculator"></i> Grenzwerte prüfen
                    </button>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!$isNew): ?>
                        <button type="button" wire:click="delete" wire:confirm="Messung wirklich löschen?"
                                class="btn btn-outline-danger ms-auto">
                            <i class="bi bi-trash"></i> Löschen
                        </button>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </div>

            
            <div class="col-lg-4">
                
                <div class="card shadow-sm mb-3 <?php echo e($strEsito === '1' ? 'border-success' : ($strEsito === '0' ? 'border-danger' : '')); ?>">
                    <div class="card-header py-2 <?php echo e($strEsito === '1' ? 'bg-success text-white' : ($strEsito === '0' ? 'bg-danger text-white' : 'bg-light')); ?>">
                        <h6 class="mb-0">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($strEsito === '1'): ?>
                                <i class="bi bi-check-circle"></i> Positiv
                            <?php elseif($strEsito === '0'): ?>
                                <i class="bi bi-x-circle"></i> Negativ
                            <?php else: ?>
                                <i class="bi bi-question-circle"></i> Ergebnis
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </h6>
                    </div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($grenzwertDetails)): ?>
                        <div class="card-body py-2">
                            <table class="table table-sm table-borderless mb-0 small">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(isset($grenzwertDetails['co'])): ?>
                                    <tr>
                                        <td>CO</td>
                                        <td class="text-end"><?php echo e($cMIS_MONOSSSIDO); ?> / <?php echo e($grenzwertDetails['co']['grenzwert']); ?> mg/m³</td>
                                        <td class="text-end">
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($grenzwertDetails['co']['ok']): ?>
                                                <i class="bi bi-check-lg text-success"></i>
                                            <?php else: ?>
                                                <i class="bi bi-x-lg text-danger"></i>
                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(isset($grenzwertDetails['nox'])): ?>
                                    <tr>
                                        <td>NOx</td>
                                        <td class="text-end"><?php echo e($cMIS_BIOSSIDO_AZOTO); ?> / <?php echo e($grenzwertDetails['nox']['grenzwert']); ?> mg/m³</td>
                                        <td class="text-end">
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($grenzwertDetails['nox']['ok']): ?>
                                                <i class="bi bi-check-lg text-success"></i>
                                            <?php else: ?>
                                                <i class="bi bi-x-lg text-danger"></i>
                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(isset($grenzwertDetails['russ'])): ?>
                                    <tr>
                                        <td>Rußzahl</td>
                                        <td class="text-end"><?php echo e($cMIS_IND_OPACITA); ?> / <?php echo e($grenzwertDetails['russ']['grenzwert']); ?></td>
                                        <td class="text-end">
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($grenzwertDetails['russ']['ok']): ?>
                                                <i class="bi bi-check-lg text-success"></i>
                                            <?php else: ?>
                                                <i class="bi bi-x-lg text-danger"></i>
                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(isset($grenzwertDetails['oel'])): ?>
                                    <tr>
                                        <td>Ölspuren</td>
                                        <td class="text-end"><?php echo e($cMIS_TRACCE_OLEO === '0' ? 'Ja' : 'Nein'); ?></td>
                                        <td class="text-end">
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($grenzwertDetails['oel']['ok']): ?>
                                                <i class="bi bi-check-lg text-success"></i>
                                            <?php else: ?>
                                                <i class="bi bi-x-lg text-danger"></i>
                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </table>
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($anlageInfo): ?>
                    <div class="card shadow-sm mb-3 border-info">
                        <div class="card-header bg-info text-white py-2">
                            <h6 class="mb-0"><i class="bi bi-building"></i> Anlage gefunden</h6>
                        </div>
                        <div class="card-body py-2 small">
                            <div class="mb-1">
                                <strong><?php echo e($anlageInfo['kodex']); ?></strong>
                                <?php echo e($anlageInfo['name']); ?>

                            </div>
                            <div class="text-muted">
                                <i class="bi bi-geo-alt"></i>
                                <?php echo e($anlageInfo['strasse']); ?>, <?php echo e($anlageInfo['ort']); ?>

                            </div>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($anlageInfo['hersteller']): ?>
                                <div class="text-muted">
                                    <i class="bi bi-wrench"></i>
                                    <?php echo e($anlageInfo['hersteller']); ?>

                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($anlageInfo['baujahr']): ?>
                                        (<?php echo e($anlageInfo['baujahr']); ?>)
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($anlageInfo['leistung']): ?>
                                        · <?php echo e($anlageInfo['leistung']); ?> kW
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            <div class="mt-2">
                                <a href="<?php echo e(route('messungen.anlagen.edit', $anlageInfo['kodex'])); ?>" 
                                   class="btn btn-sm btn-outline-info">
                                    <i class="bi bi-pencil"></i> Anlage bearbeiten
                                </a>
                            </div>
                        </div>
                    </div>
                <?php elseif($cIM_CODICE): ?>
                    <div class="card shadow-sm mb-3 border-warning">
                        <div class="card-header bg-warning py-2">
                            <h6 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Keine Anlage</h6>
                        </div>
                        <div class="card-body py-2 small">
                            <p class="mb-0">Für den Kodex <strong><?php echo e($cIM_CODICE); ?></strong> wurde keine Anlage gefunden.</p>
                        </div>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                
                <div class="card shadow-sm">
                    <div class="card-header bg-light py-2">
                        <h6 class="mb-0"><i class="bi bi-question-circle"></i> Hinweise</h6>
                    </div>
                    <div class="card-body py-2 small text-muted">
                        <p class="mb-1">
                            <strong>Ölspuren:</strong> Bei Gas-Brennern immer "Nein" (invertiert: 0=Ja, 1=Nein in DB)
                        </p>
                        <p class="mb-1">
                            <strong>Rußzahl:</strong> Nur bei Öl-Brennern relevant (0-9)
                        </p>
                        <p class="mb-0">
                            <strong>Grenzwerte:</strong> Werden automatisch anhand Brennstoff, Baujahr und Leistung berechnet
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<?php $__env->startPush('styles'); ?>
<style>
    .form-label { font-weight: 500; }
    @media (max-width: 575.98px) {
        .container-fluid { padding-left: 0.5rem; padding-right: 0.5rem; }
        .card-body { padding: 0.75rem; }
    }
</style>
<?php $__env->stopPush(); ?>
<?php /**PATH I:\Dokumente\entwicklung\laravel-tutorial\resources\views/livewire/messungen/messung-edit.blade.php ENDPATH**/ ?>