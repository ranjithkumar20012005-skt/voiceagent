<?php $__env->startSection('title', 'Imports'); ?>

<?php
  $tone = fn ($status) => match ($status) {
      'completed' => 'badge-success', 'failed' => 'badge-danger',
      'pending_mapping' => 'badge-warning', 'queued', 'processing' => 'badge-teal', default => '',
  };
?>

<?php $__env->startPush('head'); ?>
<style>
  .dropzone { position: relative; display: block; border: 2px dashed var(--border-strong); border-radius: var(--radius-lg); padding: 30px 20px; text-align: center; cursor: pointer; transition: background .15s, border-color .15s; background: var(--ink-50); }
  .dropzone:hover, .dropzone.drag { border-color: var(--green-500); background: var(--green-50); }
  .dropzone input { position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; }
  .dropzone .empty-icon { margin-bottom: 10px; }
  .dropzone strong { display: block; color: var(--ink-900); font-size: 14px; }
  .dropzone span { display: block; font-size: 12.5px; color: var(--ink-500); margin-top: 4px; }
  .dropzone.has-file { border-style: solid; border-color: var(--green-500); background: var(--green-50); }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>

<div class="ph">
  <div class="ph-main">
    <div class="eyebrow">Calling</div>
    <h1>Imports</h1>
    <p class="ph-sub">Bring a customer list in from a spreadsheet. You match your columns to ours before anything is saved.</p>
  </div>
  <div class="ph-actions">
    <a href="<?php echo e(route('customers.template')); ?>" class="btn btn-secondary btn-sm"><i class="icon-download"></i> Sample template</a>
  </div>
</div>

<div class="steps" aria-label="Import steps">
  <span class="step-chip current"><b>1</b>Upload</span>
  <span class="step-chip"><b>2</b>Map columns</span>
  <span class="step-chip"><b>3</b>Process</span>
  <span class="step-chip"><b>4</b>Review results</span>
</div>

<div class="split-left">
  <div class="stack">
    <form method="POST" action="<?php echo e(route('imports.store')); ?>" enctype="multipart/form-data" class="card">
      <?php echo csrf_field(); ?>
      <div class="card-h">
        <div class="card-h-title">
          <div class="card-icon"><i class="icon-upload"></i></div>
          <div><h2>Upload a file</h2><p>.csv, .xlsx or .xls — up to <?php echo e(round(config('sarvam.upload.max_file_kb') / 1024)); ?> MB</p></div>
        </div>
      </div>
      <div class="card-b">
        <label class="dropzone" id="dropzone">
          <input type="file" name="file" id="fileInput" required
                 accept=".csv,.xlsx,.xls,.zip,text/csv,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/zip">
          <div class="empty-icon green"><i class="icon-file-spreadsheet"></i></div>
          <strong id="dropLabel">Drop your file here, or click to browse</strong>
          <span>A .zip is accepted when it contains exactly one spreadsheet.</span>
        </label>
        <?php $__errorArgs = ['file'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="error-text"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>

        <div class="callout mt-4">
          <i class="icon-shield-check"></i>
          <div>Your file stays on this server. Phone numbers are normalised to international format, duplicates are skipped and every rejected row is listed with a reason.</div>
        </div>
      </div>
      <div class="card-f"><button type="submit" class="btn btn-primary btn-sm btn-block"><i class="icon-upload"></i> Upload &amp; preview</button></div>
    </form>

    <div class="card">
      <div class="card-h"><div><h3>Expected columns</h3><p>Headings don't need to match — you map them next.</p></div></div>
      <div class="card-b">
        <div class="chips">
          <?php $__currentLoopData = ['customer_id', 'user_name', 'policy_number', 'registered_mobile', 'policy_expiry_date', 'renewal_premium', 'preferred_language']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $col): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <span class="chip mono"><?php echo e($col); ?></span>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          <span class="chip mono" style="border-color:var(--green-200);background:var(--green-50);color:var(--green-800)">phone_number · required</span>
        </div>
      </div>
    </div>
  </div>

  
  <div class="card">
    <div class="card-h"><div><h2>Import history</h2><p>Every file uploaded, newest first</p></div></div>
    <?php if($batches->isEmpty()): ?>
      <?php if (isset($component)) { $__componentOriginal4f22a152e0729cd34293e65bd200d933 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4f22a152e0729cd34293e65bd200d933 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.empty','data' => ['icon' => 'icon-file-spreadsheet','title' => 'No imports yet']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('empty'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'icon-file-spreadsheet','title' => 'No imports yet']); ?>Upload your first customer list to start calling. <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal4f22a152e0729cd34293e65bd200d933)): ?>
<?php $attributes = $__attributesOriginal4f22a152e0729cd34293e65bd200d933; ?>
<?php unset($__attributesOriginal4f22a152e0729cd34293e65bd200d933); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal4f22a152e0729cd34293e65bd200d933)): ?>
<?php $component = $__componentOriginal4f22a152e0729cd34293e65bd200d933; ?>
<?php unset($__componentOriginal4f22a152e0729cd34293e65bd200d933); ?>
<?php endif; ?>
    <?php else: ?>
      <div class="table-wrap">
        <table class="table">
          <thead>
            <tr><th>File</th><th>Status</th><th class="num">Rows</th><th class="num">Valid</th><th class="num">Rejected</th><th class="num">Duplicates</th><th class="end"></th></tr>
          </thead>
          <tbody>
            <?php $__currentLoopData = $batches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $batch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <tr>
                <td>
                  <span class="cell-main break"><?php echo e($batch->original_filename); ?></span>
                  <span class="cell-sub"><?php echo e($batch->created_at->diffForHumans()); ?></span>
                </td>
                <td><span class="badge <?php echo e($tone($batch->status)); ?>"><?php echo e($batch->status_label); ?></span></td>
                <td class="num"><?php echo e(number_format($batch->total_rows)); ?></td>
                <td class="num text-good"><?php echo e(number_format($batch->valid_rows)); ?></td>
                <td class="num <?php echo e($batch->rejected_rows ? 'text-bad' : 'muted'); ?>"><?php echo e(number_format($batch->rejected_rows)); ?></td>
                <td class="num muted"><?php echo e(number_format($batch->duplicate_rows)); ?></td>
                <td class="end">
                  <?php if($batch->status === 'pending_mapping'): ?>
                    <a href="<?php echo e(route('imports.map', $batch)); ?>" class="btn btn-primary btn-xs">Map columns</a>
                  <?php else: ?>
                    <a href="<?php echo e(route('imports.show', $batch)); ?>" class="btn btn-secondary btn-xs">Open</a>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </tbody>
        </table>
      </div>
      <?php if($batches->hasPages()): ?>
        <div class="card-f"><?php echo e($batches->links()); ?></div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
(function () {
  var zone = document.getElementById('dropzone');
  var input = document.getElementById('fileInput');
  var label = document.getElementById('dropLabel');

  input.addEventListener('change', function () {
    var f = input.files && input.files[0];
    zone.classList.toggle('has-file', !!f);
    label.textContent = f ? f.name + ' (' + Math.max(1, Math.round(f.size / 1024)) + ' KB)' : 'Drop your file here, or click to browse';
  });
  ['dragenter', 'dragover'].forEach(function (ev) { zone.addEventListener(ev, function () { zone.classList.add('drag'); }); });
  ['dragleave', 'drop'].forEach(function (ev) { zone.addEventListener(ev, function () { zone.classList.remove('drag'); }); });
})();
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\likhi\Downloads\sarvamaiproject\resources\views/imports/index.blade.php ENDPATH**/ ?>