<?php $__env->startSection('title', 'Leads'); ?>

<?php
  use App\Support\LeadOutcome;

  /*
   | Next action is read from the agent's structured output only -- never
   | inferred from transcript text.
   */
  $nextAction = function ($lead) {
      $out = (array) ($lead->output_agent_variables ?? []);
      $truthy = fn ($v) => in_array(strtolower((string) $v), ['1', 'true', 'yes'], true);

      if ($lead->customer?->do_not_call) return ['Do not contact', 'badge-red'];
      if ($lead->callback_at) return ['Call back ' . $lead->callback_at->format('d M, g:i A'), 'badge-cyan'];
      if (isset($out['payment_link_requested']) && $truthy($out['payment_link_requested'])) return ['Send payment link', 'badge-green'];
      if ($lead->lead_generated || $lead->call_disposition === 'interested') return ['Follow up', 'badge-green'];
      return ['Review call', 'badge-gray'];
  };
?>

<?php $__env->startSection('content'); ?>

<div class="ph">
  <div class="ph-main">
    <div class="eyebrow">Results</div>
    <h1>Leads</h1>
    <p class="ph-sub">Customers worth following up, straight from the agent's own call outcomes.</p>
  </div>
</div>

<div class="card mb-4">
  <div class="card-b">
    <div class="filter-bar">
      <div class="tabs-pill" role="tablist">
        <?php $__currentLoopData = ['hot' => 'Hot Leads', 'qualified' => 'Qualified', 'follow_up' => 'Follow-ups']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <a href="<?php echo e(route('leads.index', array_merge(request()->except('page', 'filter'), ['filter' => $key]))); ?>"
             class="<?php echo e($filter === $key ? 'active' : ''); ?>" role="tab" aria-selected="<?php echo e($filter === $key ? 'true' : 'false'); ?>">
            <?php echo e($label); ?> <span class="tab-count"><?php echo e($counts[$key]); ?></span>
          </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </div>

      <form method="GET" action="<?php echo e(route('leads.index')); ?>" class="row ml-auto" style="flex:1 1 280px; max-width:420px">
        <input type="hidden" name="filter" value="<?php echo e($filter); ?>">
        <div class="search" style="flex:1">
          <i class="icon-search"></i>
          <input type="search" name="q" value="<?php echo e($search); ?>" class="input input-sm" placeholder="Search name, phone or policy" aria-label="Search leads">
        </div>
        <?php if($search): ?>
          <a href="<?php echo e(route('leads.index', ['filter' => $filter])); ?>" class="btn btn-ghost btn-sm">Clear</a>
        <?php endif; ?>
      </form>
    </div>
  </div>
</div>

<div class="card">
  <?php if($leads->isEmpty()): ?>
    <?php if (isset($component)) { $__componentOriginal4f22a152e0729cd34293e65bd200d933 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4f22a152e0729cd34293e65bd200d933 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.empty','data' => ['icon' => 'icon-user-check','title' => ''.e($search ? 'No leads match your search' : 'No leads yet').'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('empty'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'icon-user-check','title' => ''.e($search ? 'No leads match your search' : 'No leads yet').'']); ?>
      <?php if($filter === 'follow_up'): ?>
        No follow-ups yet. They appear when a customer asks to be called back.
      <?php elseif($filter === 'qualified'): ?>
        Qualified leads appear when the agent marks a call as a generated lead.
      <?php else: ?>
        No leads yet. Interested customers appear here after a call.
      <?php endif; ?>
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
            <th>Outcome</th>
            <th>Qualification</th>
            <th>Last call</th>
            <th>Next action</th>
            <th>Callback</th>
            <th class="end"></th>
          </tr>
        </thead>
        <tbody>
          <?php $__currentLoopData = $leads; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $lead): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php [$action, $actionTone] = $nextAction($lead); ?>
            <tr>
              <td>
                <?php if($lead->customer): ?>
                  <a href="<?php echo e(route('customers.show', $lead->customer)); ?>" class="cell-main"><?php echo e($lead->customer->name ?: 'Unnamed'); ?></a>
                <?php else: ?>
                  <span class="cell-main">Unknown</span>
                <?php endif; ?>
                <span class="cell-sub"><?php echo e($lead->display_phone); ?></span>
              </td>
              <td><span class="badge <?php echo e(LeadOutcome::badge($lead->call_disposition)); ?>"><?php echo e(LeadOutcome::hotHeadline($lead)); ?></span></td>
              <td>
                <?php if($lead->lead_generated): ?>
                  <span class="badge badge-success"><i class="icon-circle-check"></i> Qualified</span>
                <?php else: ?>
                  <span class="muted text-sm">Not flagged</span>
                <?php endif; ?>
              </td>
              <td class="nowrap">
                <?php echo e($lead->created_at->format('d M Y')); ?>

                <span class="cell-sub"><?php echo e($lead->created_at->format('g:i A')); ?> · <?php echo e($lead->duration_for_humans); ?></span>
              </td>
              <td><span class="badge <?php echo e($actionTone); ?>"><?php echo e($action); ?></span></td>
              <td class="nowrap">
                <?php if($lead->customer?->next_callback_at): ?>
                  <span class="<?php echo e($lead->customer->next_callback_at->isPast() ? 'text-bad' : ''); ?>"><?php echo e($lead->customer->next_callback_at->format('d M, g:i A')); ?></span>
                <?php else: ?>
                  <span class="faint">—</span>
                <?php endif; ?>
              </td>
              <td class="end">
                <div class="row-actions">
                  <a href="<?php echo e(route('calls.show', $lead)); ?>" class="btn btn-secondary btn-xs">View Call</a>
                  <?php if($lead->customer && ! $lead->customer->do_not_call): ?>
                    <button type="button" class="btn btn-subtle btn-xs btn-icon" title="Call again" aria-label="Call again"
                            data-new-call
                            data-customer-id="<?php echo e($lead->customer->id); ?>"
                            data-name="<?php echo e($lead->customer->name); ?>"
                            data-phone="<?php echo e($lead->customer->phone_number); ?>"
                            data-policy="<?php echo e($lead->customer->policy_number); ?>">
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
    <?php if($leads->hasPages()): ?>
      <div class="card-f"><?php echo e($leads->links()); ?></div>
    <?php endif; ?>
  <?php endif; ?>
</div>

<p class="text-xs faint mt-3">Qualification comes from the agent's <em>lead generated</em> output. Leads are never scored from transcript text.</p>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\likhi\Downloads\sarvamaiproject\resources\views/leads/index.blade.php ENDPATH**/ ?>