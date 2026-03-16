


<div class="row g-3">

  
  <div class="col-12">
    <div class="d-flex align-items-center justify-content-between">
      <div class="d-flex align-items-center gap-2">
        <i class="bi bi-people text-muted"></i>
        <span class="fw-semibold">Adressen</span>
      </div>
      <a href="<?php echo e(route('adresse.create', ['returnTo' => url()->current()])); ?>"
         class="btn btn-success btn-sm">
        <i class="bi bi-person-plus"></i>
        <span class="d-none d-sm-inline ms-1">Neue Adresse</span>
      </a>
    </div>
    <hr class="mt-2 mb-0">
  </div>

  <input type="hidden" name="returnTo" value="<?php echo e($returnTo ?? url()->current()); ?>">

  
  <div class="col-12 col-md-6">
    <div class="card">
      <div class="card-header bg-primary text-white py-2">
        <i class="bi bi-envelope-fill"></i>
        <span class="fw-semibold ms-1">Postadresse</span>
      </div>
      <div class="card-body p-2 p-md-3">
        <select id="postadresse_id" name="postadresse_id"
                class="form-select js-select2"
                data-placeholder="- Adresse waehlen -">
          <option value=""></option>
          <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $adressen; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $adresse): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($adresse->id); ?>"
              <?php echo e(old('postadresse_id', $gebaeude->postadresse_id ?? null) == $adresse->id ? 'selected' : ''); ?>>
              <?php echo e($adresse->name); ?> - <?php echo e($adresse->wohnort); ?>

            </option>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </select>

        
        <?php $postadresse = $gebaeude->postadresse ?? null; ?>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($postadresse): ?>
        <div id="postadresse-preview" class="small text-muted mt-4 pt-3 border-top">
          <div class="mb-1"><i class="bi bi-geo-alt me-1"></i><?php echo e($postadresse->strasse ?? ''); ?> <?php echo e($postadresse->hausnummer ?? ''); ?></div>
          <div class="mb-1"><i class="bi bi-signpost me-1"></i><?php echo e($postadresse->plz ?? ''); ?> <?php echo e($postadresse->wohnort ?? ''); ?></div>
          <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($postadresse->email): ?>
            <div><i class="bi bi-envelope me-1"></i><?php echo e($postadresse->email); ?></div>
          <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
      </div>
    </div>
    
    <?php $postadresse_id = old('postadresse_id', $gebaeude->postadresse_id ?? null); ?>
    <a id="postadresse_edit_btn"
       href="<?php echo e($postadresse_id ? route('adresse.edit', ['id' => $postadresse_id, 'returnTo' => url()->current()]) : '#'); ?>"
       class="btn btn-outline-primary btn-sm w-100 mt-2 <?php echo e($postadresse_id ? '' : 'disabled'); ?>">
      <i class="bi bi-pencil-square"></i> Adresse bearbeiten
    </a>
  </div>

  
  <div class="col-12 col-md-6">
    <div class="card">
      <div class="card-header bg-warning py-2">
        <i class="bi bi-receipt"></i>
        <span class="fw-semibold ms-1">Rechnungsempfaenger</span>
      </div>
      <div class="card-body p-2 p-md-3">
        <select id="rechnungsempfaenger_id" name="rechnungsempfaenger_id"
                class="form-select js-select2"
                data-placeholder="- Adresse waehlen -">
          <option value=""></option>
          <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $adressen; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $adresse): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($adresse->id); ?>"
              <?php echo e(old('rechnungsempfaenger_id', $gebaeude->rechnungsempfaenger_id ?? null) == $adresse->id ? 'selected' : ''); ?>>
              <?php echo e($adresse->name); ?> - <?php echo e($adresse->wohnort); ?>

            </option>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </select>

        
        <?php $rechnungsempfaenger = $gebaeude->rechnungsempfaenger ?? null; ?>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($rechnungsempfaenger): ?>
        <div id="rechnungsempfaenger-preview" class="small text-muted mt-4 pt-3 border-top">
          <div class="mb-1"><i class="bi bi-geo-alt me-1"></i><?php echo e($rechnungsempfaenger->strasse ?? ''); ?> <?php echo e($rechnungsempfaenger->hausnummer ?? ''); ?></div>
          <div class="mb-1"><i class="bi bi-signpost me-1"></i><?php echo e($rechnungsempfaenger->plz ?? ''); ?> <?php echo e($rechnungsempfaenger->wohnort ?? ''); ?></div>
          <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($rechnungsempfaenger->email): ?>
            <div><i class="bi bi-envelope me-1"></i><?php echo e($rechnungsempfaenger->email); ?></div>
          <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
      </div>
    </div>
    
    <?php $rechnungsempfaenger_id = old('rechnungsempfaenger_id', $gebaeude->rechnungsempfaenger_id ?? null); ?>
    <a id="rechnungsempfaenger_edit_btn"
       href="<?php echo e($rechnungsempfaenger_id ? route('adresse.edit', ['id' => $rechnungsempfaenger_id, 'returnTo' => url()->current()]) : '#'); ?>"
       class="btn btn-outline-warning btn-sm w-100 mt-2 <?php echo e($rechnungsempfaenger_id ? '' : 'disabled'); ?>">
      <i class="bi bi-pencil-square"></i> Adresse bearbeiten
    </a>
  </div>

  
  <div class="col-12">
    <button type="button" id="btn-copy-address" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-copy"></i> Rechnungsempfaenger = Postadresse
    </button>
  </div>

</div>

<?php $__env->startPush('scripts'); ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
  var postSelect = document.getElementById('postadresse_id');
  var reSelect = document.getElementById('rechnungsempfaenger_id');
  var postBtn = document.getElementById('postadresse_edit_btn');
  var reBtn = document.getElementById('rechnungsempfaenger_edit_btn');
  var copyBtn = document.getElementById('btn-copy-address');
  var returnTo = encodeURIComponent(window.location.href);
  var baseUrl = '<?php echo e(url("/adresse")); ?>';

  function updateButton(select, btn) {
    var id = select.value;
    if (id) {
      btn.href = baseUrl + '/' + id + '/edit?returnTo=' + returnTo;
      btn.classList.remove('disabled');
    } else {
      btn.href = '#';
      btn.classList.add('disabled');
    }
  }

  // Initial
  updateButton(postSelect, postBtn);
  updateButton(reSelect, reBtn);

  // Bei Aenderung
  postSelect.addEventListener('change', function() {
    updateButton(postSelect, postBtn);
  });

  reSelect.addEventListener('change', function() {
    updateButton(reSelect, reBtn);
  });

  // Select2 Support
  if (typeof $ !== 'undefined') {
    $(postSelect).on('select2:select select2:clear', function() {
      updateButton(postSelect, postBtn);
    });
    $(reSelect).on('select2:select select2:clear', function() {
      updateButton(reSelect, reBtn);
    });
  }

  // Kopieren-Button
  if (copyBtn) {
    copyBtn.addEventListener('click', function() {
      var postVal = postSelect.value;
      if (!postVal) {
        alert('Bitte zuerst eine Postadresse auswaehlen.');
        return;
      }
      
      // Native Select
      reSelect.value = postVal;
      
      // Select2 aktualisieren falls vorhanden
      if (typeof $ !== 'undefined' && $(reSelect).data('select2')) {
        $(reSelect).trigger('change');
      }
      
      updateButton(reSelect, reBtn);
    });
  }
});
</script>
<?php $__env->stopPush(); ?>
<?php /**PATH I:\Dokumente\entwicklung\laravel-tutorial\resources\views/gebaeude/partials/_adressen.blade.php ENDPATH**/ ?>