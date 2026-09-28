<?php $__env->startSection('title', 'Callbacks'); ?>

<?php
  use App\Support\LeadOutcome;

  // The reason shown is only what the agent recorded -- nothing is inferred.
  $reason = function ($customer) {
      $call = $customer->latestCallAttempt;
      $notes = $call ? ((array) ($call->output_agent_variables ?? []))['customer_notes'] ?? null : null;

      if (is_scalar($notes) && trim((string) $notes) !== '') return Str::limit((string) $notes, 90);
      if ($call?->call_disposition === 'callback') return 'Customer asked to be called back';
      return $customer->notes ? Str::limit($customer->notes, 90) : 'Scheduled by your team';
  };
?>

<?php $__env->startSection('content'); ?>

<div class="ph">
  <div class="ph-main">
    <div class="eyebrow">Results</div>
    <h1>Callbacks</h1>
    <p class="ph-sub">Customers who asked to be called back. Overdue callbacks come first — call them, then mark them handled.</p>
  </div>
</div>

<div class="grid cols-4 mb-section">
  <?php $__currentLoopData = [
    'overdue'  => ['Overdue', 'icon-circle-alert', 'red'],
    'today'    => ['Due today', 'icon-calendar-clock', 'amber'],
    'week'     => ['Next 7 days', 'icon-calendar-clock', 'teal'],
    'upcoming' => ['All upcoming', 'icon-clock', 'ink'],
  ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => [$label, $icon, $tone]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <a href="<?php echo e(route('callbacks.index', ['range' => $key])); ?>" class="card card-link stat" style="<?php echo e($range === $key ? 'border-color:var(--green-600);box-shadow:0 0 0 3px rgba(82,151,37,.12)' : ''); ?>">
      <div>
        <div class="stat-label"><?php echo e($label); ?></div>
        <div class="stat-value <?php echo e($key === 'overdue' && $counts[$key] > 0 ? 'bad' : ''); ?>"><?php echo e($counts[$key]); ?></div>
      </div>
      <div class="stat-icon <?php echo e($tone); ?>"><i class="<?php echo e($icon); ?>"></i></div>
    </a>
  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>

<div class="card">
  <div class="card-h">
    <div>
      <h2><?php echo e(['overdue' => 'Overdue callbacks', 'today' => 'Due today', 'week' => 'Due in the next 7 days', 'upcoming' => 'All upcoming callbacks'][$range] ?? 'Callbacks'); ?></h2>
      <p><?php echo e($customers->total()); ?> <?php echo e(Str::plural('customer', $customers->total())); ?></p>
    </div>
  </div>

  <?php if($customers->isEmpty()): ?>
    <?php if (isset($component)) { $__componentOriginal4f22a152e0729cd34293e65bd200d933 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4f22a152e0729cd34293e65bd200d933 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.empty','data' => ['icon' => 'icon-calendar-clock','title' => 'No callbacks in this range']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('empty'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'icon-calendar-clock','title' => 'No callbacks in this range']); ?>
      Callbacks appear when a customer asks the agent to call them later.
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
          <tr><th>Customer</th><th>Callback time</th><th>Reason</th><th>Status</th><th>Previous call</th><th class="end"></th></tr>
        </thead>
        <tbody>
          <?php $__currentLoopData = $customers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $customer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php
              $overdue = $customer->next_callback_at->isPast();
              $dueToday = $customer->next_callback_at->isToday();
              $previous = $customer->latestCallAttempt;
            ?>
            <tr>
              <td>
                <a href="<?php echo e(route('customers.show', $customer)); ?>" class="cell-main"><?php echo e($customer->name ?: 'Unnamed'); ?></a>
                <?php if($customer->do_not_call): ?><span class="badge badge-danger" style="margin-left:4px">DNC</span><?php endif; ?>
                <span class="cell-sub"><?php echo e($customer->display_phone); ?></span>
              </td>
              <td class="nowrap">
                <span class="strong"><?php echo e($customer->next_callback_at->format('d M, g:i A')); ?></span>
                <span class="cell-sub <?php echo e($overdue ? 'text-bad' : ''); ?>"><?php echo e($customer->next_callback_at->diffForHumans()); ?></span>
              </td>
              <td class="text-sm" style="max-width:280px"><?php echo e($reason($customer)); ?></td>
              <td>
                <?php if($overdue): ?>
                  <span class="badge badge-danger"><span class="dot"></span>Overdue</span>
                <?php elseif($dueToday): ?>
                  <span class="badge badge-warning"><span class="dot"></span>Due today</span>
                <?php else: ?>
                  <span class="badge badge-teal"><span class="dot"></span>Scheduled</span>
                <?php endif; ?>
              </td>
              <td class="nowrap">
                <?php if($previous): ?>
                  <a href="<?php echo e(route('calls.show', $previous)); ?>" class="text-sm"><?php echo e($previous->created_at->format('d M')); ?></a>
                  <span class="cell-sub"><?php echo e(LeadOutcome::label($previous->call_disposition)); ?> · <?php echo e($previous->duration_for_humans); ?></span>
                <?php else: ?>
                  <span class="faint">—</span>
                <?php endif; ?>
              </td>
              <td class="end">
                <div class="row-actions">
                  <button type="button" class="btn btn-primary btn-xs" title="Call now"
                          <?php if($customer->do_not_call): echo 'disabled'; endif; ?>
                          data-new-call
                          data-customer-id="<?php echo e($customer->id); ?>"
                          data-name="<?php echo e($customer->name); ?>"
                          data-phone="<?php echo e($customer->phone_number); ?>"
                          data-policy="<?php echo e($customer->policy_number); ?>"
                          data-language="<?php echo e($customer->preferred_language); ?>">
                    <i class="icon-phone"></i> Call
                  </button>
                  <form method="POST" action="<?php echo e(route('callbacks.clear', $customer)); ?>" class="inline-form">
                    <?php echo csrf_field(); ?>
                    <button type="submit" class="btn btn-secondary btn-xs" title="Mark handled"><i class="icon-check"></i> Done</button>
                  </form>
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

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\likhi\Downloads\sarvamaiproject\resources\views/callbacks/index.blade.php ENDPATH**/ ?>