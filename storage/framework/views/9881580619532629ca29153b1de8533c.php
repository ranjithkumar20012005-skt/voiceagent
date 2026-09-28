<?php $__env->startSection('title', 'Import Result'); ?>

<?php
  $running = in_array($batch->status, ['queued', 'processing'], true);
  $tone = match ($batch->status) {
      'completed' => 'badge-success', 'failed' => 'badge-danger',
      'pending_mapping' => 'badge-warning', 'queued', 'processing' => 'badge-teal', default => '',
  };
  $done = $batch->valid_rows + $batch->rejected_rows + $batch->duplicate_rows;
  $pct = $batch->total_rows > 0 ? min(100, round($done / $batch->total_rows * 100)) : ($batch->isFinished() ? 100 : 0);
?>

<?php $__env->startSection('content'); ?>

<div class="ph">
  <div class="ph-main">
    <a href="<?php echo e(route('imports.index')); ?>" class="ph-back"><i class="icon-arrow-left"></i> Imports</a>
    <h1 class="break"><?php echo e($batch->original_filename); ?></h1>
    <div class="ph-meta">
      <span class="badge <?php echo e($tone); ?>" id="batchStatus"><?php echo e($batch->status_label); ?></span>
      <span>Uploaded <?php echo e($batch->created_at->format('d M Y, g:i A')); ?></span>
    </div>
  </div>
  <div class="ph-actions">
    <?php if($batch->valid_rows > 0): ?>
      <a href="<?php echo e(route('campaigns.index', ['import_batch_id' => $batch->id])); ?>" class="btn btn-primary btn-sm"><i class="icon-megaphone"></i> Start campaign</a>
    <?php endif; ?>
    <form method="POST" action="<?php echo e(route('imports.destroy', $batch)); ?>" class="inline-form" data-confirm="Remove this import record? Imported customers are kept.">
      <?php echo csrf_field(); ?>
      <?php echo method_field('DELETE'); ?>
      <button type="submit" class="btn btn-secondary btn-sm" <?php if($batch->status === 'processing'): echo 'disabled'; endif; ?>><i class="icon-trash-2"></i> Remove</button>
    </form>
  </div>
</div>

<div class="steps" aria-label="Import steps">
  <span class="step-chip done"><b><i class="icon-check"></i></b>Upload</span>
  <span class="step-chip done"><b><i class="icon-check"></i></b>Map columns</span>
  <span class="step-chip <?php echo e($running ? 'current' : 'done'); ?>"><b><?php if($running): ?> 3 <?php else: ?> <i class="icon-check"></i> <?php endif; ?></b>Process</span>
  <span class="step-chip <?php echo e($running ? '' : 'current'); ?>"><b>4</b>Review results</span>
</div>

<?php if($batch->error_message): ?>
  <div class="callout callout-danger mb-5"><i class="icon-circle-alert"></i><div><strong>Import failed.</strong> <?php echo e($batch->error_message); ?></div></div>
<?php endif; ?>

<?php if($running): ?>
  <div class="card mb-section">
    <div class="card-b">
      <div class="bar-row-head"><span><i class="icon-loader-circle spin"></i> Processing rows…</span><span id="importPct"><?php echo e($pct); ?>%</span></div>
      <div class="progress"><div class="progress-fill" id="importBar" style="width: <?php echo e($pct); ?>%"></div></div>
      <p class="hint">This page updates automatically. Imports run in the background queue worker.</p>
    </div>
  </div>
<?php endif; ?>

<div class="grid cols-4 mb-section">
  <?php if (isset($component)) { $__componentOriginal3b387acd2c997737a257e1ec014549fd = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3b387acd2c997737a257e1ec014549fd = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat','data' => ['label' => 'Total rows','value' => $batch->total_rows,'icon' => 'icon-file-spreadsheet','tone' => 'ink']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Total rows','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($batch->total_rows),'icon' => 'icon-file-spreadsheet','tone' => 'ink']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3b387acd2c997737a257e1ec014549fd)): ?>
<?php $attributes = $__attributesOriginal3b387acd2c997737a257e1ec014549fd; ?>
<?php unset($__attributesOriginal3b387acd2c997737a257e1ec014549fd); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3b387acd2c997737a257e1ec014549fd)): ?>
<?php $component = $__componentOriginal3b387acd2c997737a257e1ec014549fd; ?>
<?php unset($__componentOriginal3b387acd2c997737a257e1ec014549fd); ?>
<?php endif; ?>
  <?php if (isset($component)) { $__componentOriginal3b387acd2c997737a257e1ec014549fd = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3b387acd2c997737a257e1ec014549fd = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat','data' => ['label' => 'Imported','value' => $batch->valid_rows,'icon' => 'icon-circle-check','valueTone' => 'good']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Imported','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($batch->valid_rows),'icon' => 'icon-circle-check','value-tone' => 'good']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3b387acd2c997737a257e1ec014549fd)): ?>
<?php $attributes = $__attributesOriginal3b387acd2c997737a257e1ec014549fd; ?>
<?php unset($__attributesOriginal3b387acd2c997737a257e1ec014549fd); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3b387acd2c997737a257e1ec014549fd)): ?>
<?php $component = $__componentOriginal3b387acd2c997737a257e1ec014549fd; ?>
<?php unset($__componentOriginal3b387acd2c997737a257e1ec014549fd); ?>
<?php endif; ?>
  <?php if (isset($component)) { $__componentOriginal3b387acd2c997737a257e1ec014549fd = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3b387acd2c997737a257e1ec014549fd = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat','data' => ['label' => 'Rejected','value' => $batch->rejected_rows,'icon' => 'icon-circle-x','tone' => 'red','valueTone' => $batch->rejected_rows ? 'bad' : null]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Rejected','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($batch->rejected_rows),'icon' => 'icon-circle-x','tone' => 'red','value-tone' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($batch->rejected_rows ? 'bad' : null)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3b387acd2c997737a257e1ec014549fd)): ?>
<?php $attributes = $__attributesOriginal3b387acd2c997737a257e1ec014549fd; ?>
<?php unset($__attributesOriginal3b387acd2c997737a257e1ec014549fd); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3b387acd2c997737a257e1ec014549fd)): ?>
<?php $component = $__componentOriginal3b387acd2c997737a257e1ec014549fd; ?>
<?php unset($__componentOriginal3b387acd2c997737a257e1ec014549fd); ?>
<?php endif; ?>
  <?php if (isset($component)) { $__componentOriginal3b387acd2c997737a257e1ec014549fd = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3b387acd2c997737a257e1ec014549fd = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat','data' => ['label' => 'Duplicates','value' => $batch->duplicate_rows,'icon' => 'icon-copy','tone' => 'amber']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Duplicates','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($batch->duplicate_rows),'icon' => 'icon-copy','tone' => 'amber']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3b387acd2c997737a257e1ec014549fd)): ?>
<?php $attributes = $__attributesOriginal3b387acd2c997737a257e1ec014549fd; ?>
<?php unset($__attributesOriginal3b387acd2c997737a257e1ec014549fd); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3b387acd2c997737a257e1ec014549fd)): ?>
<?php $component = $__componentOriginal3b387acd2c997737a257e1ec014549fd; ?>
<?php unset($__componentOriginal3b387acd2c997737a257e1ec014549fd); ?>
<?php endif; ?>
</div>

<div class="<?php echo e(empty($batch->rejection_samples) ? '' : 'split-even'); ?>">
  <?php if(! empty($batch->rejection_samples)): ?>
    <div class="card">
      <div class="card-h">
        <div><h2>Validation errors</h2><p>Rejected and duplicate rows, with the reason</p></div>
        <span class="badge">showing <?php echo e(count($batch->rejection_samples)); ?></span>
      </div>
      <div class="table-wrap">
        <table class="table">
          <thead><tr><th class="num">Line</th><th>Name</th><th>Phone</th><th>Reason</th></tr></thead>
          <tbody>
            <?php $__currentLoopData = $batch->rejection_samples; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sample): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <tr>
                <td class="num muted"><?php echo e($sample['line'] ?? '—'); ?></td>
                <td><?php echo e(($sample['name'] ?? null) ?: '—'); ?></td>
                <td class="muted nowrap"><?php echo e(($sample['phone'] ?? null) ?: '—'); ?></td>
                <td class="text-bad"><?php echo e($sample['reason'] ?? ''); ?></td>
              </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>

  <div class="card">
    <div class="card-h">
      <div><h2>Imported customers</h2><p>The first 25 from this file</p></div>
      <a href="<?php echo e(route('customers.index')); ?>" class="btn btn-secondary btn-sm">All customers</a>
    </div>
    <?php if($customers->isEmpty()): ?>
      <?php if (isset($component)) { $__componentOriginal4f22a152e0729cd34293e65bd200d933 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4f22a152e0729cd34293e65bd200d933 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.empty','data' => ['icon' => 'icon-users','title' => ''.e($running ? 'Import in progress…' : 'No customers imported').'','compact' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('empty'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'icon-users','title' => ''.e($running ? 'Import in progress…' : 'No customers imported').'','compact' => true]); ?>
        <?php echo e($running ? 'Customers appear here as rows are processed.' : 'No customers were imported from this file.'); ?>

       <?php echo $__env->renderComponent(); ?>
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
          <thead><tr><th>Name</th><th>Phone</th><th>Policy</th><th>Expiry</th></tr></thead>
          <tbody>
            <?php $__currentLoopData = $customers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $customer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <tr>
                <td><a href="<?php echo e(route('customers.show', $customer)); ?>" class="cell-main"><?php echo e($customer->name ?: 'Unnamed'); ?></a></td>
                <td class="muted nowrap"><?php echo e($customer->display_phone); ?></td>
                <td class="muted"><?php echo e($customer->policy_number ?: '—'); ?></td>
                <td class="muted nowrap"><?php echo e($customer->policy_expiry_date?->format('d M Y') ?: '—'); ?></td>
              </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<?php if($running): ?>
<script>
(function () {
  var url = <?php echo json_encode(route('imports.status', $batch), 512) ?>;
  var timer = setInterval(async function () {
    try {
      var res = await fetch(url, { headers: { 'Accept': 'application/json' } });
      if (!res.ok) return;
      var data = await res.json();

      var done = (data.valid || 0) + (data.rejected || 0) + (data.duplicates || 0);
      var pct = data.total > 0 ? Math.min(100, Math.round(done / data.total * 100)) : 0;
      document.getElementById('importBar').style.width = pct + '%';
      document.getElementById('importPct').textContent = pct + '%';
      document.getElementById('batchStatus').textContent = data.label;

      if (data.finished) { clearInterval(timer); window.location.reload(); }
    } catch (e) { /* retry on the next tick */ }
  }, 3000);
})();
</script>
<?php endif; ?>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\likhi\Downloads\sarvamaiproject\resources\views/imports/show.blade.php ENDPATH**/ ?>