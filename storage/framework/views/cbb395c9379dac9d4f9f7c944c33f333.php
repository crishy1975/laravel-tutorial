


<div class="row g-3">

  
  <div class="col-12">
    <label class="form-label small fw-semibold mb-2">Monate</label>

    <?php
      $monate = [
        ['m01', 'Jan'], ['m02', 'Feb'], ['m03', 'Maer'],
        ['m04', 'Apr'], ['m05', 'Mai'], ['m06', 'Jun'],
        ['m07', 'Jul'], ['m08', 'Aug'], ['m09', 'Sep'],
        ['m10', 'Okt'], ['m11', 'Nov'], ['m12', 'Dez'],
      ];
    ?>

    <div class="row row-cols-3 row-cols-sm-4 row-cols-md-6 g-2">
      <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $monate; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$feld, $label]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="col">
          <input type="hidden" name="<?php echo e($feld); ?>" value="0">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="<?php echo e($feld); ?>" name="<?php echo e($feld); ?>" value="1"
              <?php if((int)old($feld, $gebaeude->{$feld} ?? 0) === 1): echo 'checked'; endif; ?>>
            <label class="form-check-label small" for="<?php echo e($feld); ?>"><?php echo e($label); ?></label>
          </div>
        </div>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>
  </div>

  
  <div class="col-6">
    <label for="geplante_reinigungen" class="form-label small mb-1">Geplante</label>
    <input type="number" min="0" id="geplante_reinigungen" name="geplante_reinigungen"
      class="form-control <?php $__errorArgs = ['geplante_reinigungen'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
      value="<?php echo e(old('geplante_reinigungen', $gebaeude->geplante_reinigungen ?? 1)); ?>">
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['geplante_reinigungen'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
  </div>

  <div class="col-6">
    <label for="gemachte_reinigungen" class="form-label small mb-1">Gemachte</label>
    <input type="number" min="0" id="gemachte_reinigungen" name="gemachte_reinigungen"
      class="form-control <?php $__errorArgs = ['gemachte_reinigungen'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
      value="<?php echo e(old('gemachte_reinigungen', $gebaeude->gemachte_reinigungen ?? 0)); ?>">
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['gemachte_reinigungen'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
  </div>

  
  <div class="col-6">
    <input type="hidden" name="rechnung_schreiben" value="0">
    <div class="form-check form-switch">
      <input class="form-check-input" type="checkbox" id="rechnung_schreiben" name="rechnung_schreiben" value="1"
        <?php if((int)old('rechnung_schreiben', $gebaeude->rechnung_schreiben ?? 0) === 1): echo 'checked'; endif; ?>>
      <label class="form-check-label small" for="rechnung_schreiben">Rechnung schreiben</label>
    </div>
  </div>

  <div class="col-6">
    <input type="hidden" name="faellig" value="0">
    <div class="form-check form-switch">
      <input class="form-check-input" type="checkbox" id="faellig" name="faellig" value="1"
        <?php if((int)old('faellig', $gebaeude->faellig ?? 0) === 1): echo 'checked'; endif; ?>>
      <label class="form-check-label small" for="faellig">Faellig</label>
    </div>
  </div>

  
  <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($gebaeude->id)): ?>
  <div class="col-12">
    <div class="d-flex flex-column flex-sm-row gap-2 justify-content-end">
      <button type="button" id="btn-recalc-faellig" class="btn btn-outline-primary btn-sm">
        <i class="bi bi-arrow-repeat"></i> Faelligkeit pruefen
      </button>
      <button type="button" id="btn-reset-gemachte" class="btn btn-outline-danger btn-sm">
        <i class="bi bi-arrow-counterclockwise"></i> Gemachte zuruecksetzen
      </button>
    </div>
  </div>
  <?php else: ?>
  <div class="col-12">
    <div class="alert alert-info py-2 mb-0 small">
      <i class="bi bi-info-circle"></i> Aktionen erst nach Speichern verfuegbar.
    </div>
  </div>
  <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

</div>

<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($gebaeude->id)): ?>
<div id="einteilung-root"
  data-csrf="<?php echo e(csrf_token()); ?>"
  data-route-recalc="<?php echo e(route('gebaeude.faellig.recalc', $gebaeude->id)); ?>"
  data-route-reset="<?php echo e(route('gebaeude.resetGemachteReinigungen')); ?>">
</div>

<script>
(function() {
  var root = document.getElementById('einteilung-root');
  var btnReset = document.getElementById('btn-reset-gemachte');
  var btnRecalc = document.getElementById('btn-recalc-faellig');
  var chkFaellig = document.getElementById('faellig');

  if (!root) return;

  var CSRF = root.dataset.csrf || '';
  var ROUTE_RECALC = root.dataset.routeRecalc || '';
  var ROUTE_RESET = root.dataset.routeReset || '';

  if (btnRecalc) {
    btnRecalc.addEventListener('click', async function() {
      if (!ROUTE_RECALC) return;
      btnRecalc.disabled = true;
      var oldHtml = btnRecalc.innerHTML;
      btnRecalc.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

      try {
        var res = await fetch(ROUTE_RECALC, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
          body: JSON.stringify({})
        });
        var json = await res.json();
        if (!res.ok || json.ok === false) throw new Error(json.message || 'Fehler');
        if (chkFaellig) chkFaellig.checked = !!json.faellig;
      } catch (err) {
        alert('Fehler: ' + err.message);
      } finally {
        btnRecalc.disabled = false;
        btnRecalc.innerHTML = oldHtml;
      }
    });
  }

  if (btnReset) {
    btnReset.addEventListener('click', async function() {
      if (!ROUTE_RESET) return;
      if (!confirm('Alle gemachten Reinigungen auf 0 setzen?')) return;

      try {
        var res = await fetch(ROUTE_RESET, {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': CSRF },
          body: new URLSearchParams({ confirm: 'YES' })
        });
        if (!res.ok) throw new Error('HTTP ' + res.status);
        window.location.reload();
      } catch (err) {
        alert('Fehler: ' + err.message);
      }
    });
  }
})();
</script>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<?php /**PATH I:\Dokumente\entwicklung\laravel-tutorial\resources\views/gebaeude/partials/_einteilung.blade.php ENDPATH**/ ?>