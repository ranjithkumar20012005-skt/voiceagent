<?php $__env->startSection('title', 'Call Logs'); ?>

<?php
  use App\Support\LeadOutcome;

  // Client-facing wording throughout; the values posted stay canonical.
  $tabs = ['all' => 'All']
      + LeadOutcome::connectivityLabels()
      + array_intersect_key(LeadOutcome::labels(), array_flip(LeadOutcome::headlineOutcomes()));
?>

<?php $__env->startSection('content'); ?>

<div class="ph">
  <div class="ph-main">
    <div class="eyebrow">Results</div>
    <h1>Call Logs</h1>
    <p class="ph-sub"><?php echo e(number_format($attempts->total())); ?> calls<?php echo e($filter !== 'all' || $search || request('from') || request('to') || request('agent') ? ' match these filters' : ' in total'); ?>. Open any call for its details and transcript.</p>
  </div>
</div>

<div class="card mb-4">
  <div class="card-b">
    <div class="tabs-pill mb-4" role="tablist">
      <?php $__currentLoopData = $tabs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <a href="<?php echo e(route('calls.index', array_merge(request()->except('page', 'filter'), ['filter' => $key]))); ?>"
           class="<?php echo e($filter === $key ? 'active' : ''); ?>" role="tab" aria-selected="<?php echo e($filter === $key ? 'true' : 'false'); ?>"><?php echo e($label); ?></a>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    <form method="GET" action="<?php echo e(route('calls.index')); ?>" class="filter-bar">
      <input type="hidden" name="filter" value="<?php echo e($filter); ?>">
      <div class="field grow">
        <label class="label" for="q">Search</label>
        <div class="search">
          <i class="icon-search"></i>
          <input type="search" id="q" name="q" value="<?php echo e($search); ?>" class="input" placeholder="Customer, phone or policy">
        </div>
      </div>
      <?php if($agents->isNotEmpty()): ?>
        <div class="field fixed">
          <label class="label" for="agent">Agent</label>
          <select id="agent" name="agent" class="select">
            <option value="">All agents</option>
            <option value="workspace" <?php if(request('agent') === 'workspace'): echo 'selected'; endif; ?>>Workspace agent</option>
            <?php $__currentLoopData = $agents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $agent): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <option value="<?php echo e($agent->id); ?>" <?php if(request('agent') == $agent->id): echo 'selected'; endif; ?>><?php echo e($agent->name); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </select>
        </div>
      <?php endif; ?>
      <div class="field fixed">
        <label class="label" for="from">From</label>
        <input type="date" id="from" name="from" value="<?php echo e(request('from')); ?>" class="input">
      </div>
      <div class="field fixed">
        <label class="label" for="to">To</label>
        <input type="date" id="to" name="to" value="<?php echo e(request('to')); ?>" class="input">
      </div>
      <div class="row">
        <button type="submit" class="btn btn-primary"><i class="icon-filter"></i> Apply</button>
        <?php if($search || request('from') || request('to') || request('agent')): ?>
          <a href="<?php echo e(route('calls.index', ['filter' => $filter])); ?>" class="btn btn-ghost">Reset</a>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <?php if($attempts->isEmpty()): ?>
    <?php if (isset($component)) { $__componentOriginal4f22a152e0729cd34293e65bd200d933 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4f22a152e0729cd34293e65bd200d933 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.empty','data' => ['icon' => 'icon-phone-call','title' => 'No calls match this filter']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('empty'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'icon-phone-call','title' => 'No calls match this filter']); ?>
      Try another filter, or place a call with New Call.
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
            <th>Agent</th>
            <th>Status</th>
            <th>Outcome</th>
            <th class="num">Duration</th>
            <th>Date &amp; time</th>
            <th class="end"></th>
          </tr>
        </thead>
        <tbody>
          <?php $__currentLoopData = $attempts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $attempt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr>
              <td>
                <span class="cell-main"><?php echo e($attempt->customer?->name ?: 'Unknown'); ?></span>
                <span class="cell-sub"><?php echo e($attempt->display_phone); ?></span>
              </td>
              <td class="nowrap muted">
                <?php echo e($attempt->agent?->name ?? 'Workspace agent'); ?>

                <?php if($attempt->campaign_id): ?><span class="cell-sub">Campaign</span><?php endif; ?>
              </td>
              <td><span class="badge <?php echo e(LeadOutcome::connectivityBadge($attempt)); ?>"><?php echo e(LeadOutcome::connectivityLabel($attempt)); ?></span></td>
              <td><span class="badge <?php echo e(LeadOutcome::badge($attempt->call_disposition)); ?>"><?php echo e(LeadOutcome::label($attempt->call_disposition)); ?></span></td>
              <td class="num"><?php echo e($attempt->duration_for_humans); ?></td>
              <td class="nowrap">
                <?php echo e($attempt->created_at->format('d M Y')); ?>

                <span class="cell-sub"><?php echo e($attempt->created_at->format('g:i A')); ?></span>
              </td>
              <td class="end">
                <div class="row-actions">
                  <a href="<?php echo e(route('calls.show', $attempt)); ?>" class="btn btn-secondary btn-xs">View</a>
                  <?php if($attempt->customer && ! $attempt->customer->do_not_call): ?>
                    <button type="button" class="btn btn-subtle btn-xs btn-icon" title="Call again" aria-label="Call again"
                            data-new-call
                            data-customer-id="<?php echo e($attempt->customer->id); ?>"
                            data-name="<?php echo e($attempt->customer->name); ?>"
                            data-phone="<?php echo e($attempt->customer->phone_number); ?>"
                            data-policy="<?php echo e($attempt->customer->policy_number); ?>">
                      <i class="icon-phone"></i>
                    </button>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
      </table>
    </div>
    <?php if($attempts->hasPages()): ?>
      <div class="card-f"><?php echo e($attempts->links()); ?></div>
    <?php endif; ?>
  <?php endif; ?>
</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\likhi\Downloads\sarvamaiproject\resources\views/calls/index.blade.php ENDPATH**/ ?>