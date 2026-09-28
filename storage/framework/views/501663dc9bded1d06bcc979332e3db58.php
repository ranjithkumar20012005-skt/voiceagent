<?php $__env->startSection('title', $customer->name ?: 'Customer'); ?>

<?php
  use App\Support\LeadOutcome;
  $attempts = $customer->callAttempts;
  $connected = $attempts->where('connectivity_status', 'connected')->count();
?>

<?php $__env->startSection('content'); ?>

<div class="ph">
  <div class="ph-main">
    <a href="<?php echo e(route('customers.index')); ?>" class="ph-back"><i class="icon-arrow-left"></i> Customers</a>
    <h1><?php echo e($customer->name ?: 'Unnamed customer'); ?></h1>
    <div class="ph-meta">
      <span><?php echo e($customer->display_phone); ?></span>
      <span class="badge"><?php echo e(ucfirst(str_replace('_', ' ', $customer->customer_status))); ?></span>
      <?php if($customer->last_outcome): ?>
        <span class="badge <?php echo e(LeadOutcome::badge($customer->last_outcome)); ?>"><?php echo e(LeadOutcome::label($customer->last_outcome)); ?></span>
      <?php endif; ?>
      <?php if($customer->do_not_call): ?>
        <span class="badge badge-danger">Do not call</span>
      <?php endif; ?>
    </div>
  </div>
  <div class="ph-actions">
    <button type="button" class="btn btn-primary btn-sm"
            <?php if($customer->do_not_call): echo 'disabled'; endif; ?>
            data-new-call
            data-customer-id="<?php echo e($customer->id); ?>"
            data-name="<?php echo e($customer->name); ?>"
            data-phone="<?php echo e($customer->phone_number); ?>"
            data-policy="<?php echo e($customer->policy_number); ?>"
            data-registered-mobile="<?php echo e($customer->registered_mobile); ?>"
            data-language="<?php echo e($customer->preferred_language); ?>">
      <i class="icon-phone"></i> Call Now
    </button>
  </div>
</div>

<div class="grid cols-4 mb-section">
  <?php if (isset($component)) { $__componentOriginal3b387acd2c997737a257e1ec014549fd = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3b387acd2c997737a257e1ec014549fd = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat','data' => ['label' => 'Total calls','value' => $customer->call_count,'icon' => 'icon-phone','tone' => 'ink']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Total calls','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($customer->call_count),'icon' => 'icon-phone','tone' => 'ink']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat','data' => ['label' => 'Connected','value' => $connected,'icon' => 'icon-phone-call','tone' => 'teal','hint' => 'of the last ' . $attempts->count() . ' attempts']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Connected','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($connected),'icon' => 'icon-phone-call','tone' => 'teal','hint' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('of the last ' . $attempts->count() . ' attempts')]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat','data' => ['label' => 'Last call','value' => $customer->last_call_at?->format('d M') ?? 'Never','icon' => 'icon-clock','tone' => 'ink','hint' => $customer->last_call_at?->diffForHumans()]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Last call','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($customer->last_call_at?->format('d M') ?? 'Never'),'icon' => 'icon-clock','tone' => 'ink','hint' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($customer->last_call_at?->diffForHumans())]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat','data' => ['label' => 'Next callback','value' => $customer->next_callback_at?->format('d M, H:i') ?? 'None','icon' => 'icon-calendar-clock','tone' => $customer->next_callback_at?->isPast() ? 'red' : 'amber','valueTone' => $customer->next_callback_at?->isPast() ? 'bad' : null,'hint' => $customer->next_callback_at ? ($customer->next_callback_at->isPast() ? 'Overdue' : $customer->next_callback_at->diffForHumans()) : null]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Next callback','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($customer->next_callback_at?->format('d M, H:i') ?? 'None'),'icon' => 'icon-calendar-clock','tone' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($customer->next_callback_at?->isPast() ? 'red' : 'amber'),'value-tone' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($customer->next_callback_at?->isPast() ? 'bad' : null),'hint' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($customer->next_callback_at ? ($customer->next_callback_at->isPast() ? 'Overdue' : $customer->next_callback_at->diffForHumans()) : null)]); ?>
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

<div class="split-left">
  <div class="stack">
    
    <form method="POST" action="<?php echo e(route('customers.update', $customer)); ?>" class="card">
      <?php echo csrf_field(); ?>
      <?php echo method_field('PUT'); ?>
      <div class="card-h"><div><h2>Customer information</h2><p>Changes apply to future calls.</p></div></div>
      <div class="card-b">
        <div class="form-grid">
          <div class="field full">
            <label class="label" for="name">Name</label>
            <input type="text" id="name" name="name" value="<?php echo e(old('name', $customer->name)); ?>" class="input" maxlength="120">
          </div>
          <div class="field">
            <label class="label" for="policy_number">Policy number</label>
            <input type="text" id="policy_number" name="policy_number" value="<?php echo e(old('policy_number', $customer->policy_number)); ?>" class="input" maxlength="64">
          </div>
          <div class="field">
            <label class="label" for="preferred_language">Language</label>
            <select id="preferred_language" name="preferred_language" class="select">
              <option value="">Agent default</option>
              <?php $__currentLoopData = config('sarvam.languages', []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $language): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($language); ?>" <?php if(old('preferred_language', $customer->preferred_language) === $language): echo 'selected'; endif; ?>><?php echo e($language); ?></option>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
          </div>
          <div class="field">
            <label class="label" for="customer_status">Status</label>
            <select id="customer_status" name="customer_status" class="select">
              <?php $__currentLoopData = ['pending', 'queued', 'in_progress', 'contacted', 'closed']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($status); ?>" <?php if(old('customer_status', $customer->customer_status) === $status): echo 'selected'; endif; ?>><?php echo e(ucfirst(str_replace('_', ' ', $status))); ?></option>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
          </div>
          <div class="field">
            <label class="label" for="next_callback_at">Next callback</label>
            <input type="datetime-local" id="next_callback_at" name="next_callback_at" class="input"
                   value="<?php echo e(old('next_callback_at', $customer->next_callback_at?->format('Y-m-d\TH:i'))); ?>">
          </div>
          <div class="field full">
            <label class="label" for="notes">Notes</label>
            <textarea id="notes" name="notes" rows="3" class="textarea" maxlength="5000"><?php echo e(old('notes', $customer->notes)); ?></textarea>
          </div>
          <div class="field full">
            <label class="check">
              <input type="checkbox" name="do_not_call" value="1" <?php if(old('do_not_call', $customer->do_not_call)): echo 'checked'; endif; ?>>
              <span>Do not call this customer <span class="muted">— excluded from calls, campaigns and automations</span></span>
            </label>
          </div>
        </div>
      </div>
      <div class="card-f"><button type="submit" class="btn btn-primary btn-sm">Save changes</button></div>
    </form>

    <div class="card">
      <div class="card-h"><div><h2>Policy</h2></div></div>
      <div class="card-b">
        <dl class="kv">
          <dt>Customer ID</dt><dd><?php echo e($customer->customer_identifier ?: '—'); ?></dd>
          <dt>Registered mobile</dt><dd><?php echo e($customer->registered_mobile ?: '—'); ?></dd>
          <dt>Policy expiry</dt><dd><?php echo e($customer->policy_expiry_date?->format('d M Y') ?: '—'); ?></dd>
          <dt>Renewal premium</dt><dd><?php echo e($customer->renewal_premium !== null ? number_format((float) $customer->renewal_premium, 2) : '—'); ?></dd>
          <dt>Added</dt>
          <dd>
            <?php echo e($customer->created_at->format('d M Y')); ?>

            <?php if($customer->importBatch): ?>
              · <a href="<?php echo e(route('imports.show', $customer->importBatch)); ?>"><?php echo e(Str::limit($customer->importBatch->original_filename, 28)); ?></a>
            <?php endif; ?>
          </dd>
        </dl>
      </div>
    </div>
  </div>

  <div class="stack">
    
    <div class="card">
      <div class="card-h"><div><h2>Call history</h2><p>Most recent first</p></div></div>
      <?php if($attempts->isEmpty()): ?>
        <?php if (isset($component)) { $__componentOriginal4f22a152e0729cd34293e65bd200d933 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4f22a152e0729cd34293e65bd200d933 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.empty','data' => ['icon' => 'icon-phone','title' => 'No calls yet','compact' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('empty'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'icon-phone','title' => 'No calls yet','compact' => true]); ?>No calls recorded for this customer. <?php echo $__env->renderComponent(); ?>
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
              <tr><th>Date</th><th>Agent</th><th>Status</th><th>Outcome</th><th class="num">Duration</th><th class="end"></th></tr>
            </thead>
            <tbody>
              <?php $__currentLoopData = $attempts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $attempt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                  <td class="nowrap"><?php echo e($attempt->created_at->format('d M Y')); ?><span class="cell-sub"><?php echo e($attempt->created_at->format('g:i A')); ?></span></td>
                  <td class="muted nowrap"><?php echo e($attempt->agent?->name ?? 'Workspace agent'); ?></td>
                  <td><span class="badge <?php echo e(LeadOutcome::connectivityBadge($attempt)); ?>"><?php echo e(LeadOutcome::connectivityLabel($attempt)); ?></span></td>
                  <td><span class="badge <?php echo e(LeadOutcome::badge($attempt->call_disposition)); ?>"><?php echo e(LeadOutcome::label($attempt->call_disposition)); ?></span></td>
                  <td class="num"><?php echo e($attempt->duration_for_humans); ?></td>
                  <td class="end"><a href="<?php echo e(route('calls.show', $attempt)); ?>" class="btn btn-secondary btn-xs">View</a></td>
                </tr>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <div class="grid cols-2">
      
      <div class="card">
        <div class="card-h"><div><h3>Campaigns</h3><p>Bulk runs that called this customer</p></div></div>
        <?php $__empty_1 = true; $__currentLoopData = $campaigns; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $campaign): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
          <div class="list-row">
            <div class="list-main">
              <a href="<?php echo e(route('campaigns.show', $campaign)); ?>" class="list-title cell-main"><?php echo e($campaign->name); ?></a>
              <div class="list-meta"><?php echo e($campaign->created_at->format('d M Y')); ?></div>
            </div>
            <span class="badge <?php echo e($campaign->status_badge); ?>"><?php echo e(ucfirst($campaign->status)); ?></span>
          </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
          <?php if (isset($component)) { $__componentOriginal4f22a152e0729cd34293e65bd200d933 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4f22a152e0729cd34293e65bd200d933 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.empty','data' => ['icon' => 'icon-megaphone','title' => 'Not in any campaign','compact' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('empty'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'icon-megaphone','title' => 'Not in any campaign','compact' => true]); ?>
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
        <?php endif; ?>
      </div>

      
      <div class="card">
        <div class="card-h"><div><h3>Callback</h3><p>Requested by the customer</p></div></div>
        <?php if($customer->next_callback_at): ?>
          <div class="card-b">
            <div class="strong"><?php echo e($customer->next_callback_at->format('l, d M Y')); ?></div>
            <div class="text-sm <?php echo e($customer->next_callback_at->isPast() ? 'text-bad' : 'muted'); ?>">
              <?php echo e($customer->next_callback_at->format('g:i A')); ?> · <?php echo e($customer->next_callback_at->diffForHumans()); ?>

            </div>
          </div>
          <div class="card-f">
            <form method="POST" action="<?php echo e(route('callbacks.clear', $customer)); ?>" class="inline-form">
              <?php echo csrf_field(); ?>
              <button type="submit" class="btn btn-secondary btn-sm"><i class="icon-check"></i> Mark handled</button>
            </form>
          </div>
        <?php else: ?>
          <?php if (isset($component)) { $__componentOriginal4f22a152e0729cd34293e65bd200d933 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4f22a152e0729cd34293e65bd200d933 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.empty','data' => ['icon' => 'icon-calendar-clock','title' => 'No callback scheduled','compact' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('empty'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'icon-calendar-clock','title' => 'No callback scheduled','compact' => true]); ?>
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
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\likhi\Downloads\sarvamaiproject\resources\views/customers/show.blade.php ENDPATH**/ ?>