<?php $__env->startSection('title', 'Campaigns'); ?>

<?php $__env->startSection('content'); ?>

<div class="ph">
  <div class="ph-main">
    <div class="eyebrow">Calling</div>
    <h1>Campaigns</h1>
    <p class="ph-sub">Bulk calling runs. The agent works through a customer list at a controlled pace and records every result.</p>
  </div>
  <div class="ph-actions">
    <a href="<?php echo e(route('imports.index')); ?>" class="btn btn-secondary btn-sm"><i class="icon-upload"></i> Import customers</a>
    <button type="button" class="btn btn-primary btn-sm" data-modal-open="campaignModal" <?php if(! $configured): echo 'disabled'; endif; ?>><i class="icon-plus"></i> New Campaign</button>
  </div>
</div>

<?php if (! ($configured)): ?>
  <div class="callout callout-warning mb-5">
    <i class="icon-circle-alert"></i><div>The voice service is not configured on the server, so campaigns cannot be dispatched yet.</div>
  </div>
<?php endif; ?>

<div class="card">
  <?php if($campaigns->isEmpty()): ?>
    <?php if (isset($component)) { $__componentOriginal4f22a152e0729cd34293e65bd200d933 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4f22a152e0729cd34293e65bd200d933 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.empty','data' => ['icon' => 'icon-megaphone','title' => 'No campaigns yet']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('empty'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'icon-megaphone','title' => 'No campaigns yet']); ?>
      Import a customer list, then create a campaign to call everyone on it.
       <?php $__env->slot('action', null, []); ?> 
        <div class="row" style="justify-content:center">
          <a href="<?php echo e(route('imports.index')); ?>" class="btn btn-secondary btn-sm">Import customers</a>
          <button type="button" class="btn btn-primary btn-sm" data-modal-open="campaignModal" <?php if(! $configured): echo 'disabled'; endif; ?>>New Campaign</button>
        </div>
       <?php $__env->endSlot(); ?>
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
        <thead>
          <tr>
            <th>Campaign</th>
            <th>Status</th>
            <th class="num">Contacts</th>
            <th class="num">Completed</th>
            <th class="num">Not reached</th>
            <th class="num">Remaining</th>
            <th style="min-width:160px">Progress</th>
            <th class="end"></th>
          </tr>
        </thead>
        <tbody>
          <?php $__currentLoopData = $campaigns; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $campaign): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php
              $s = $stats->get($campaign->sarvam_campaign_id);
              $completed = (int) ($s->completed ?? 0);
              $failed = (int) ($s->failed ?? 0);
              $target = (int) $campaign->total_contacts;
              $pct = $target > 0 ? min(100, round($completed / $target * 100)) : 0;
            ?>
            <tr>
              <td>
                <a href="<?php echo e(route('campaigns.show', $campaign)); ?>" class="cell-main"><?php echo e($campaign->name); ?></a>
                <span class="cell-sub">
                  Created <?php echo e($campaign->created_at->diffForHumans()); ?>

                  <?php if($campaign->automation_id): ?> · <i class="icon-zap"></i> Automation <?php endif; ?>
                </span>
                <?php if($campaign->error_message): ?>
                  <span class="cell-sub text-bad"><?php echo e(Str::limit($campaign->error_message, 80)); ?></span>
                <?php endif; ?>
              </td>
              <td><span class="badge <?php echo e($campaign->status_badge); ?>"><span class="dot"></span><?php echo e(ucfirst($campaign->status)); ?></span></td>
              <td class="num"><?php echo e(number_format($target)); ?></td>
              <td class="num"><?php echo e(number_format($completed)); ?></td>
              <td class="num <?php echo e($failed ? 'text-bad' : ''); ?>"><?php echo e(number_format($failed)); ?></td>
              <td class="num"><?php echo e(number_format(max(0, $target - $completed))); ?></td>
              <td>
                <div class="row">
                  <div class="progress thin" style="flex:1"><div class="progress-fill" style="width: <?php echo e($pct); ?>%"></div></div>
                  <span class="text-xs muted" style="width:34px;text-align:right"><?php echo e($pct); ?>%</span>
                </div>
              </td>
              <td class="end"><a href="<?php echo e(route('campaigns.show', $campaign)); ?>" class="btn btn-secondary btn-xs">Open</a></td>
            </tr>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
      </table>
    </div>
    <?php if($campaigns->hasPages()): ?>
      <div class="card-f"><?php echo e($campaigns->links()); ?></div>
    <?php endif; ?>
  <?php endif; ?>
</div>


<div class="modal" id="campaignModal" role="dialog" aria-modal="true" aria-labelledby="campaignModalTitle" aria-hidden="true">
  <div class="modal-scrim" data-modal-close></div>
  <div class="modal-panel">
    <form method="POST" action="<?php echo e(route('campaigns.store')); ?>">
      <?php echo csrf_field(); ?>
      <div class="modal-h">
        <div>
          <h2 id="campaignModalTitle">New campaign</h2>
          <p>Contacts are sent to the agent as soon as you create it.</p>
        </div>
        <button type="button" class="btn btn-ghost btn-icon btn-sm" data-modal-close aria-label="Close"><i class="icon-x"></i></button>
      </div>

      <div class="modal-b">
        <div class="field">
          <label class="label" for="cName">Name <span class="req">*</span></label>
          <input type="text" name="name" id="cName" class="input" maxlength="50" required value="<?php echo e(old('name')); ?>" placeholder="October renewals">
        </div>

        <div class="field">
          <label class="label" for="cDesc">Description</label>
          <input type="text" name="description" id="cDesc" class="input" maxlength="150" value="<?php echo e(old('description')); ?>">
        </div>

        <div class="field">
          <label class="label" for="campaignSource">Who to call</label>
          <select name="source" class="select" id="campaignSource">
            <option value="import_batch" <?php if(old('source', 'import_batch') === 'import_batch'): echo 'selected'; endif; ?>>Customers from an import</option>
            <option value="filtered" <?php if(old('source') === 'filtered'): echo 'selected'; endif; ?>>Policies expiring soon</option>
          </select>
        </div>

        <div class="field" id="batchField">
          <label class="label" for="cBatch">Import</label>
          <select name="import_batch_id" id="cBatch" class="select">
            <option value="">— Select an import —</option>
            <?php $__currentLoopData = $batches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $batch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <option value="<?php echo e($batch->id); ?>" <?php if((old('import_batch_id') ?? request('import_batch_id')) == $batch->id): echo 'selected'; endif; ?>>
                <?php echo e($batch->original_filename); ?> (<?php echo e($batch->valid_rows); ?> valid)
              </option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </select>
          <?php if($batches->isEmpty()): ?>
            <div class="hint">No completed imports yet — <a href="<?php echo e(route('imports.index')); ?>">import customers</a> first.</div>
          <?php endif; ?>
        </div>

        <div class="field" id="expiryField" hidden>
          <label class="label" for="cExpiry">Expiring within (days)</label>
          <input type="number" name="expiry_within_days" id="cExpiry" class="input" value="<?php echo e(old('expiry_within_days', 30)); ?>" min="0" max="365">
          <div class="hint">Only pending, callable customers are included.</div>
        </div>

        <div class="field">
          <label class="label" for="cRate">Calls per second</label>
          <input type="number" name="attempts_per_second" id="cRate" class="input"
                 value="<?php echo e(old('attempts_per_second', config('sarvam.campaign.attempts_per_second'))); ?>" step="0.1" min="0.1" max="500">
          <div class="hint">Kept low by default to protect answer rates. Do-not-call customers are always skipped.</div>
        </div>
      </div>

      <div class="modal-f">
        <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
        <button type="submit" class="btn btn-primary" <?php if(! $configured): echo 'disabled'; endif; ?>><i class="icon-play"></i> Create &amp; dispatch</button>
      </div>
    </form>
  </div>
</div>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
(function () {
  var source = document.getElementById('campaignSource');
  function sync() {
    var filtered = source.value === 'filtered';
    document.getElementById('batchField').hidden = filtered;
    document.getElementById('expiryField').hidden = !filtered;
  }
  source.addEventListener('change', sync);
  sync();

  // Arriving from an import ("Start campaign") or after a validation error: open the form.
  <?php if(request('import_batch_id') || $errors->any()): ?>
    AppModal.open(document.getElementById('campaignModal'));
  <?php endif; ?>
})();
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\likhi\Downloads\sarvamaiproject\resources\views/campaigns/index.blade.php ENDPATH**/ ?>