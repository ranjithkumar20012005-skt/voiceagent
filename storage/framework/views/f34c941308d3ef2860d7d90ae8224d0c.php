<?php $__env->startSection('title', 'Customers'); ?>

<?php use App\Support\LeadOutcome; ?>

<?php $__env->startSection('content'); ?>

<div class="ph">
  <div class="ph-main">
    <div class="eyebrow">Calling</div>
    <h1>Customers</h1>
    <p class="ph-sub"><?php echo e(number_format($customers->total())); ?> <?php echo e(Str::plural('record', $customers->total())); ?><?php echo e(array_filter($filters) ? ' match these filters' : ''); ?>. Everyone the agent can call, with their latest result.</p>
  </div>
  <div class="ph-actions">
    <a href="<?php echo e(route('customers.export', request()->query())); ?>" class="btn btn-secondary btn-sm"><i class="icon-download"></i> Export CSV</a>
    <a href="<?php echo e(route('imports.index')); ?>" class="btn btn-secondary btn-sm"><i class="icon-upload"></i> Import</a>
    <button type="button" class="btn btn-primary btn-sm" data-new-call><i class="icon-phone"></i> New Call</button>
  </div>
</div>

<div class="card mb-4">
  <div class="card-b">
    <form method="GET" action="<?php echo e(route('customers.index')); ?>" class="filter-bar">
      <div class="field grow">
        <label class="label" for="q">Search</label>
        <div class="search">
          <i class="icon-search"></i>
          <input type="search" id="q" name="q" value="<?php echo e($filters['q'] ?? ''); ?>" class="input" placeholder="Name, phone or policy number">
        </div>
      </div>
      <div class="field fixed">
        <label class="label" for="status">Status</label>
        <select id="status" name="status" class="select">
          <option value="">All statuses</option>
          <?php $__currentLoopData = $statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($status); ?>" <?php if(($filters['status'] ?? '') === $status): echo 'selected'; endif; ?>><?php echo e(ucfirst(str_replace('_', ' ', $status))); ?></option>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
      </div>
      <div class="field fixed">
        <label class="label" for="outcome">Last outcome</label>
        <select id="outcome" name="outcome" class="select">
          <option value="">All outcomes</option>
          <?php $__currentLoopData = $outcomes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($key); ?>" <?php if(($filters['outcome'] ?? '') === $key): echo 'selected'; endif; ?>><?php echo e(LeadOutcome::label($key)); ?></option>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
      </div>
      <div class="field fixed">
        <label class="label" for="callback_from">Callback from</label>
        <input type="date" id="callback_from" name="callback_from" value="<?php echo e($filters['callback_from'] ?? ''); ?>" class="input">
      </div>
      <div class="field fixed">
        <label class="label" for="callback_to">Callback to</label>
        <input type="date" id="callback_to" name="callback_to" value="<?php echo e($filters['callback_to'] ?? ''); ?>" class="input">
      </div>
      <div class="row">
        <button type="submit" class="btn btn-primary"><i class="icon-filter"></i> Apply</button>
        <?php if(array_filter($filters)): ?>
          <a href="<?php echo e(route('customers.index')); ?>" class="btn btn-ghost">Reset</a>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <?php if($customers->isEmpty()): ?>
    <?php if (isset($component)) { $__componentOriginal4f22a152e0729cd34293e65bd200d933 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4f22a152e0729cd34293e65bd200d933 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.empty','data' => ['icon' => 'icon-users','title' => ''.e(array_filter($filters) ? 'No customers match these filters' : 'No customers yet').'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('empty'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'icon-users','title' => ''.e(array_filter($filters) ? 'No customers match these filters' : 'No customers yet').'']); ?>
      Upload a customer list to get started, or place a call — callers with a name are added automatically.
       <?php $__env->slot('action', null, []); ?> 
        <a href="<?php echo e(route('imports.index')); ?>" class="btn btn-primary btn-sm"><i class="icon-upload"></i> Upload a customer list</a>
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
            <th>Customer</th>
            <th>Status</th>
            <th>Latest call</th>
            <th>Outcome</th>
            <th>Policy expiry</th>
            <th>Callback</th>
            <th class="end"></th>
          </tr>
        </thead>
        <tbody>
          <?php $__currentLoopData = $customers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $customer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php $latest = $customer->latestCallAttempt; ?>
            <tr>
              <td>
                <a href="<?php echo e(route('customers.show', $customer)); ?>" class="cell-main"><?php echo e($customer->name ?: 'Unnamed'); ?></a>
                <?php if($customer->do_not_call): ?>
                  <span class="badge badge-danger" style="margin-left:4px">DNC</span>
                <?php endif; ?>
                <span class="cell-sub"><?php echo e($customer->display_phone); ?><?php echo e($customer->policy_number ? ' · ' . $customer->policy_number : ''); ?></span>
              </td>
              <td><span class="badge"><?php echo e(ucfirst(str_replace('_', ' ', $customer->customer_status))); ?></span></td>
              <td class="nowrap">
                <?php if($latest): ?>
                  <a href="<?php echo e(route('calls.show', $latest)); ?>" class="text-sm"><?php echo e($latest->created_at->diffForHumans()); ?></a>
                  <span class="cell-sub"><?php echo e(\App\Support\LeadOutcome::connectivityLabel($latest)); ?> · <?php echo e($latest->duration_for_humans); ?></span>
                <?php else: ?>
                  <span class="faint text-sm">Not called yet</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if($customer->last_outcome): ?>
                  <span class="badge <?php echo e(LeadOutcome::badge($customer->last_outcome)); ?>"><?php echo e(LeadOutcome::label($customer->last_outcome)); ?></span>
                <?php else: ?>
                  <span class="faint">—</span>
                <?php endif; ?>
              </td>
              <td class="nowrap muted"><?php echo e($customer->policy_expiry_date?->format('d M Y') ?: '—'); ?></td>
              <td class="nowrap">
                <?php if($customer->next_callback_at): ?>
                  <span class="<?php echo e($customer->next_callback_at->isPast() ? 'text-bad' : ''); ?>"><?php echo e($customer->next_callback_at->format('d M, g:i A')); ?></span>
                <?php else: ?>
                  <span class="faint">—</span>
                <?php endif; ?>
              </td>
              <td class="end">
                <div class="row-actions">
                  <a href="<?php echo e(route('customers.show', $customer)); ?>" class="btn btn-secondary btn-xs">Open</a>
                  <button type="button" class="btn btn-subtle btn-xs btn-icon"
                          title="<?php echo e($customer->do_not_call ? 'Marked Do Not Call' : 'Call now'); ?>"
                          aria-label="Call <?php echo e($customer->name); ?>"
                          <?php if($customer->do_not_call): echo 'disabled'; endif; ?>
                          data-new-call
                          data-customer-id="<?php echo e($customer->id); ?>"
                          data-name="<?php echo e($customer->name); ?>"
                          data-phone="<?php echo e($customer->phone_number); ?>"
                          data-policy="<?php echo e($customer->policy_number); ?>"
                          data-registered-mobile="<?php echo e($customer->registered_mobile); ?>"
                          data-language="<?php echo e($customer->preferred_language); ?>">
                    <i class="icon-phone"></i>
                  </button>
                </div>
              </td>
            </tr>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
      </table>
    </div>
    <?php if($customers->hasPages()): ?>
      <div class="card-f"><?php echo e($customers->links()); ?></div>
    <?php endif; ?>
  <?php endif; ?>
</div>

<p class="text-xs faint mt-3">Need the column layout? <a href="<?php echo e(route('customers.template')); ?>">Download the sample template</a>.</p>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\likhi\Downloads\sarvamaiproject\resources\views/customers/index.blade.php ENDPATH**/ ?>