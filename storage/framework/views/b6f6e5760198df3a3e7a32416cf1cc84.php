<?php $__env->startSection('title', 'Automations'); ?>

<?php $__env->startSection('content'); ?>

<div class="ph">
  <div class="ph-main">
    <div class="eyebrow">Automation</div>
    <h1>Automations</h1>
    <p class="ph-sub">Recurring calling rules. Each day at its set time, an automation finds matching customers and queues calls for them.</p>
  </div>
  <div class="ph-actions">
    <button type="button" class="btn btn-primary btn-sm" data-modal-open="automationNew"><i class="icon-plus"></i> New automation</button>
  </div>
</div>

<div class="callout callout-info mb-5">
  <i class="icon-info"></i>
  <div>Automations run through the server's task scheduler. Production needs the cron entry <code>* * * * * php artisan schedule:run</code> and a running queue worker, otherwise nothing fires automatically.</div>
</div>

<div class="card">
  <?php if($automations->isEmpty()): ?>
    <?php if (isset($component)) { $__componentOriginal4f22a152e0729cd34293e65bd200d933 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4f22a152e0729cd34293e65bd200d933 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.empty','data' => ['icon' => 'icon-zap','title' => 'No automations yet']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('empty'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'icon-zap','title' => 'No automations yet']); ?>
      Create a rule such as “call customers whose policy expires within 30 days, every morning at 8”.
       <?php $__env->slot('action', null, []); ?> 
        <button type="button" class="btn btn-primary btn-sm" data-modal-open="automationNew">New automation</button>
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
          <tr><th>Automation</th><th>Trigger</th><th>Action</th><th class="num">Matching now</th><th>Status</th><th>Last run</th><th class="end"></th></tr>
        </thead>
        <tbody>
          <?php $__currentLoopData = $automations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $automation): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr>
              <td><span class="cell-main"><?php echo e($automation->name); ?></span></td>
              <td class="nowrap">
                Daily at <?php echo e($automation->run_at); ?>

                <span class="cell-sub"><?php echo e($automation->timezone); ?> · calls <?php echo e($automation->window_start); ?>–<?php echo e($automation->window_end); ?></span>
              </td>
              <td class="text-sm" style="min-width:220px">
                Call customers
                <?php if($automation->expiry_within_days > 0): ?> with a policy due within <?php echo e($automation->expiry_within_days); ?> days <?php endif; ?>
                <?php if(array_filter((array) $automation->customer_statuses)): ?> · status <?php echo e(implode(', ', array_map(fn ($s) => str_replace('_', ' ', $s), (array) $automation->customer_statuses))); ?> <?php endif; ?>
                <span class="cell-sub">Up to <?php echo e(number_format($automation->max_calls_per_run)); ?> per run · <?php echo e($automation->max_retries); ?> retries</span>
              </td>
              <td class="num strong"><?php echo e(number_format($eligible[$automation->id] ?? 0)); ?></td>
              <td>
                <?php if($automation->enabled): ?>
                  <span class="badge badge-success"><span class="dot"></span>Enabled</span>
                <?php else: ?>
                  <span class="badge"><span class="dot"></span>Disabled</span>
                <?php endif; ?>
              </td>
              <td class="nowrap">
                <?php if($automation->last_run_at): ?>
                  <?php echo e($automation->last_run_at->diffForHumans()); ?>

                  <span class="cell-sub"><?php echo e($automation->last_run_count); ?> queued<?php echo e($automation->last_run_status ? ' · ' . str_replace('_', ' ', $automation->last_run_status) : ''); ?></span>
                <?php else: ?>
                  <span class="faint">Never run</span>
                <?php endif; ?>
              </td>
              <td class="end">
                <div class="row-actions">
                  <form method="POST" action="<?php echo e(route('automations.run', $automation)); ?>" class="inline-form">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="dry_run" value="1">
                    <button type="submit" class="btn btn-secondary btn-xs" title="Count who would be called — places no calls">Preview</button>
                  </form>
                  <button type="button" class="btn btn-secondary btn-xs" data-modal-open="automation<?php echo e($automation->id); ?>"><i class="icon-pencil"></i> Edit</button>
                </div>
              </td>
            </tr>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<p class="text-xs faint mt-3">Do-not-call customers are never included, and a customer with a callback still scheduled is left alone until it is due.</p>


<?php $__currentLoopData = $automations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $automation): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
  <div class="modal" id="automation<?php echo e($automation->id); ?>" role="dialog" aria-modal="true" aria-labelledby="automation<?php echo e($automation->id); ?>Title" aria-hidden="true">
    <div class="modal-scrim" data-modal-close></div>
    <div class="modal-panel" style="max-width:760px">
      <form method="POST" action="<?php echo e(route('automations.update', $automation)); ?>">
        <?php echo csrf_field(); ?>
        <?php echo method_field('PUT'); ?>
        <div class="modal-h">
          <div><h2 id="automation<?php echo e($automation->id); ?>Title">Edit automation</h2><p><?php echo e(number_format($eligible[$automation->id] ?? 0)); ?> customers currently match this rule.</p></div>
          <button type="button" class="btn btn-ghost btn-icon btn-sm" data-modal-close aria-label="Close"><i class="icon-x"></i></button>
        </div>
        <div class="modal-b">
          <?php echo $__env->make('automations._form', ['a' => $automation, 'p' => 'a' . $automation->id . '_'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </div>
        <div class="modal-f" style="justify-content:space-between">
          <button type="submit" form="runNow<?php echo e($automation->id); ?>" class="btn btn-danger btn-sm"><i class="icon-play"></i> Run now</button>
          <div class="row">
            <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
            <button type="submit" class="btn btn-primary">Save</button>
          </div>
        </div>
      </form>
      <form method="POST" action="<?php echo e(route('automations.run', $automation)); ?>" id="runNow<?php echo e($automation->id); ?>"
            data-confirm="Place real calls now to all <?php echo e($eligible[$automation->id] ?? 0); ?> matching customers?">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="dry_run" value="0">
        <input type="hidden" name="force" value="1">
      </form>
    </div>
  </div>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

<div class="modal" id="automationNew" role="dialog" aria-modal="true" aria-labelledby="automationNewTitle" aria-hidden="true">
  <div class="modal-scrim" data-modal-close></div>
  <div class="modal-panel" style="max-width:760px">
    <form method="POST" action="<?php echo e(route('automations.store')); ?>">
      <?php echo csrf_field(); ?>
      <div class="modal-h">
        <div><h2 id="automationNewTitle">New automation</h2><p>Created disabled unless you switch it on. Use Preview to check who matches first.</p></div>
        <button type="button" class="btn btn-ghost btn-icon btn-sm" data-modal-close aria-label="Close"><i class="icon-x"></i></button>
      </div>
      <div class="modal-b">
        <?php echo $__env->make('automations._form', ['a' => new \App\Models\Automation(['name' => 'Daily renewal calls', 'enabled' => false]), 'p' => 'new_'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
      </div>
      <div class="modal-f">
        <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
        <button type="submit" class="btn btn-primary">Create automation</button>
      </div>
    </form>
  </div>
</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\likhi\Downloads\sarvamaiproject\resources\views/automations/index.blade.php ENDPATH**/ ?>