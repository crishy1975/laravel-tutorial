


<?php
  $auftragDatumValue = old('auftrag_datum',
    isset($gebaeude->auftrag_datum) && $gebaeude->auftrag_datum
      ? \Illuminate\Support\Carbon::parse($gebaeude->auftrag_datum)->toDateString()
      : ''
  );
  $bemerkungBuchhaltung = old('bemerkung_buchhaltung', $gebaeude->bemerkung_buchhaltung ?? '');
  $cupVal = old('cup', $gebaeude->cup ?? '');
  $cigVal = old('cig', $gebaeude->cig ?? '');
  $codiceCommessaVal = old('codice_commessa', $gebaeude->codice_commessa ?? '');
  $auftragIdVal = old('auftrag_id', $gebaeude->auftrag_id ?? '');
  $bankMatchTplVal = old('bank_match_text_template', $gebaeude->bank_match_text_template ?? '');
  $fatturaProfileSel = (string) old('fattura_profile_id', $gebaeude->fattura_profile_id ?? '');
?>

<div class="row g-2 g-md-3">

  
  <div class="col-12">
    <label for="bemerkung_buchhaltung" class="form-label small mb-1">
      <i class="bi bi-journal-text"></i> Buchhaltungs-Bemerkung
    </label>
    <textarea id="bemerkung_buchhaltung" name="bemerkung_buchhaltung" rows="2"
      class="form-control <?php $__errorArgs = ['bemerkung_buchhaltung'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"><?php echo e($bemerkungBuchhaltung); ?></textarea>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['bemerkung_buchhaltung'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
  </div>

  
  <div class="col-12">
    <label for="fattura_profile_id" class="form-label small mb-1">
      <i class="bi bi-file-earmark-text"></i> FatturaPA-Profil
    </label>
    <select id="fattura_profile_id" name="fattura_profile_id"
      class="form-select <?php $__errorArgs = ['fattura_profile_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
      <option value="">- Kein Profil -</option>
      <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = ($fatturaProfiles ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <option value="<?php echo e($p->id); ?>" <?php echo e($fatturaProfileSel === (string) $p->id ? 'selected' : ''); ?>>
          <?php echo e($p->bezeichnung); ?>

        </option>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </select>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['fattura_profile_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <div id="fattura_profile_info" class="mt-2 d-flex flex-wrap gap-1"></div>

    <script type="application/json" id="fattura_profiles_data">
      <?php echo json_encode(
            ($fatturaProfiles ?? collect())->map(function($p){
              return [
                'id' => (string)$p->id,
                'bezeichnung' => $p->bezeichnung,
                'mwst_satz' => $p->mwst_satz,
                'split_payment' => (bool)$p->split_payment,
                'ritenuta' => (bool)$p->ritenuta,
              ];
            })->values(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
          ); ?>

    </script>
  </div>

  
  <div class="col-4">
    <label for="cup" class="form-label small mb-1">CUP</label>
    <input type="text" id="cup" name="cup" maxlength="20"
      class="form-control <?php $__errorArgs = ['cup'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" value="<?php echo e($cupVal); ?>">
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['cup'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
  </div>

  <div class="col-4">
    <label for="cig" class="form-label small mb-1">CIG</label>
    <input type="text" id="cig" name="cig" maxlength="10"
      class="form-control <?php $__errorArgs = ['cig'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" value="<?php echo e($cigVal); ?>">
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['cig'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
  </div>

  <div class="col-4">
    <label for="codice_commessa" class="form-label small mb-1">Commessa</label>
    <input type="text" id="codice_commessa" name="codice_commessa" maxlength="100"
      class="form-control <?php $__errorArgs = ['codice_commessa'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" value="<?php echo e($codiceCommessaVal); ?>">
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['codice_commessa'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
  </div>

  
  <div class="col-8">
    <label for="auftrag_id" class="form-label small mb-1">
      <i class="bi bi-hash"></i> Auftrags-ID
    </label>
    <input type="text" id="auftrag_id" name="auftrag_id" maxlength="50"
      class="form-control <?php $__errorArgs = ['auftrag_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" value="<?php echo e($auftragIdVal); ?>">
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['auftrag_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
  </div>

  <div class="col-4">
    <label for="auftrag_datum" class="form-label small mb-1">Datum</label>
    <input type="date" id="auftrag_datum" name="auftrag_datum"
      class="form-control <?php $__errorArgs = ['auftrag_datum'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" value="<?php echo e($auftragDatumValue); ?>">
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['auftrag_datum'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
  </div>

  
  <div class="col-12">
    <label for="bank_match_text_template" class="form-label small mb-1">
      <i class="bi bi-bank"></i> Bank-Erkennungstext
    </label>
    <textarea id="bank_match_text_template" name="bank_match_text_template" rows="2"
      class="form-control font-monospace small <?php $__errorArgs = ['bank_match_text_template'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"><?php echo e($bankMatchTplVal); ?></textarea>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['bank_match_text_template'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    <small class="text-muted">
      Platzhalter: <code>{invoice_number}</code>, <code>{invoice_year}</code>, <code>{building_codex}</code>
    </small>
  </div>

</div>

<script>
(function() {
  function formatPercent(val) {
    var n = Number(val);
    if (!isFinite(n)) return '-';
    return n.toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' %';
  }

  function jaNein(b) { return b ? 'Ja' : 'Nein'; }

  function badge(label, value, color) {
    return '<span class="badge bg-' + (color || 'secondary') + ' small">' + label + ': ' + value + '</span>';
  }

  var select = document.getElementById('fattura_profile_id');
  var infoLine = document.getElementById('fattura_profile_info');
  var dataEl = document.getElementById('fattura_profiles_data');

  if (!select || !infoLine || !dataEl) return;

  var profiles = [];
  try { profiles = JSON.parse(dataEl.textContent || '[]'); } catch(e) {}

  var byId = {};
  profiles.forEach(function(p) { byId[String(p.id)] = p; });

  function renderInfo(profileId) {
    var p = byId[String(profileId)];
    if (!p) {
      infoLine.innerHTML = '<small class="text-muted">Kein Profil</small>';
      return;
    }
    var badges = [];
    badges.push(badge('MwSt', formatPercent(p.mwst_satz), 'primary'));
    badges.push(badge('Split', jaNein(p.split_payment), p.split_payment ? 'warning' : 'secondary'));
    badges.push(badge('Ritenuta', jaNein(p.ritenuta), p.ritenuta ? 'danger' : 'secondary'));
    infoLine.innerHTML = badges.join(' ');
  }

  renderInfo(select.value);
  select.addEventListener('change', function(e) { renderInfo(e.target.value); });
})();
</script>
<?php /**PATH I:\Dokumente\entwicklung\laravel-tutorial\resources\views/gebaeude/partials/_fatturapa.blade.php ENDPATH**/ ?>