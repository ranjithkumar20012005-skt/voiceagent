<?php $__env->startSection('title', 'Call Details'); ?>

<?php
  use App\Support\CallStatus;
  use App\Support\LeadOutcome;

  $turns = $call->transcriptTurns();

  /*
   | Technical detail -- platform identifiers and the raw agent variables --
   | is for internal operators only. In a normal client build (APP_DEBUG off)
   | none of it is rendered at all.
   */
  $internal = (bool) config('app.debug');

  $output = (array) ($call->output_agent_variables ?? []);
  $summary = $output['customer_notes'] ?? $output['summary'] ?? null;
  $back = url()->previous() === url()->current() ? route('calls.index') : url()->previous();
?>

<?php $__env->startSection('content'); ?>

<div class="ph">
  <div class="ph-main">
    <a href="<?php echo e($back); ?>" class="ph-back"><i class="icon-arrow-left"></i> Back</a>
    <h1><?php echo e($call->customer?->name ?: 'Unknown customer'); ?></h1>
    <div class="ph-meta">
      <span class="badge <?php echo e(LeadOutcome::connectivityBadge($call)); ?>"><?php echo e(LeadOutcome::connectivityLabel($call)); ?></span>
      <span class="badge <?php echo e(LeadOutcome::badge($call->call_disposition)); ?>"><?php echo e(LeadOutcome::label($call->call_disposition)); ?></span>
      <span><?php echo e($call->display_phone); ?> · <?php echo e($call->created_at->format('d M Y, g:i A')); ?></span>
    </div>
  </div>

  <div class="ph-actions">
    <?php if($call->customer): ?>
      <a href="<?php echo e(route('customers.show', $call->customer)); ?>" class="btn btn-secondary btn-sm"><i class="icon-circle-user"></i> Customer</a>
    <?php endif; ?>
    <?php if($call->customer && ! $call->customer->do_not_call): ?>
      <button type="button" class="btn btn-primary btn-sm"
              data-new-call
              data-customer-id="<?php echo e($call->customer->id); ?>"
              data-name="<?php echo e($call->customer->name); ?>"
              data-phone="<?php echo e($call->customer->phone_number); ?>"
              data-policy="<?php echo e($call->customer->policy_number); ?>"
              <?php if($call->agent_id): ?> data-agent-id="<?php echo e($call->agent_id); ?>" <?php endif; ?>>
        <i class="icon-phone"></i> Call Again
      </button>
    <?php endif; ?>
  </div>
</div>

<div class="split">

  
  <div class="card">
    <div class="card-h">
      <div><h2>Conversation Transcript</h2><p>As returned by the agent when the call ended.</p></div>
      <?php if($turns): ?>
        <span class="badge"><?php echo e(count($turns)); ?> turns</span>
      <?php endif; ?>
    </div>

    <div class="card-b">
      <?php $__empty_1 = true; $__currentLoopData = $turns; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $turn): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        
        <div class="turn <?php echo e($turn['role']); ?>">
          <div class="turn-who">
            <?php if($turn['role'] === 'agent'): ?>
              <i class="icon-bot"></i> Agent
            <?php else: ?>
              <i class="icon-circle-user"></i> Customer
            <?php endif; ?>
          </div>
          <div class="turn-text"><?php echo e($turn['text']); ?></div>
        </div>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <?php if (isset($component)) { $__componentOriginal4f22a152e0729cd34293e65bd200d933 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4f22a152e0729cd34293e65bd200d933 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.empty','data' => ['icon' => 'icon-message-square-text','title' => ''.e($call->status === CallStatus::COMPLETED ? 'No transcript' : 'Transcript pending').'','compact' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('empty'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'icon-message-square-text','title' => ''.e($call->status === CallStatus::COMPLETED ? 'No transcript' : 'Transcript pending').'','compact' => true]); ?>
          <?php if($call->status === CallStatus::COMPLETED): ?>
            No transcript was returned for this call.
          <?php elseif($call->status === CallStatus::FAILED): ?>
            The call did not go through, so there is no conversation to show.
          <?php else: ?>
            Transcript pending — it appears once the call completes.
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
      <?php endif; ?>
    </div>
  </div>

  
  <div class="stack">
    <div class="card">
      <div class="card-h"><div><h3>Call Details</h3></div></div>
      <div class="card-b">
        <dl class="kv">
          <dt>Customer</dt><dd><?php echo e($call->customer?->name ?: 'Unknown'); ?></dd>
          <dt>Phone</dt><dd><?php echo e($call->display_phone); ?></dd>
          <?php if($call->customer?->policy_number): ?>
            <dt>Policy</dt><dd><?php echo e($call->customer->policy_number); ?></dd>
          <?php endif; ?>
          <dt>Agent</dt><dd><?php echo e($call->agent?->name ?? 'Workspace agent'); ?></dd>
          <dt>Source</dt><dd><?php echo e($call->campaign_id ? 'Campaign' : 'Instant call'); ?></dd>
          <dt>Call time</dt><dd><?php echo e($call->started_at?->format('d M Y, g:i A') ?: $call->created_at->format('d M Y, g:i A')); ?></dd>
          <dt>Duration</dt><dd><?php echo e($call->duration_for_humans); ?></dd>
          <dt>Connectivity</dt><dd><?php echo e(LeadOutcome::connectivityLabel($call)); ?></dd>
          <dt>Outcome</dt><dd><?php echo e(LeadOutcome::label($call->call_disposition)); ?></dd>
        </dl>
      </div>
    </div>

    
    <?php if($call->callback_at || $call->lead_generated): ?>
      <div class="card">
        <div class="card-h"><div><h3>Next Action</h3></div></div>
        <div class="card-b stack" style="gap:10px">
          <?php if($call->lead_generated): ?>
            <div class="row"><span class="badge badge-success">Hot Lead</span><span class="text-sm muted">Worth a follow-up from your team.</span></div>
          <?php endif; ?>
          <?php if($call->callback_at): ?>
            <div class="row"><span class="badge badge-teal">Callback</span><span class="text-sm muted"><?php echo e($call->callback_at->format('d M Y, g:i A')); ?></span></div>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>

    <?php if(filled($summary)): ?>
      <div class="card">
        <div class="card-h"><div><h3>Summary</h3><p>Notes recorded by the agent</p></div></div>
        <div class="card-b"><p class="text-sm"><?php echo e(is_scalar($summary) ? $summary : json_encode($summary)); ?></p></div>
      </div>
    <?php endif; ?>

    
    <?php if($internal): ?>
      <div class="card">
        <div class="card-h"><div><h3>Internal Detail</h3></div><span class="badge badge-warning">Debug build</span></div>
        <div class="card-b">
          <dl class="kv">
            <dt>Local status</dt><dd><?php echo e(ucfirst($call->status)); ?></dd>
            <dt>Attempt ID</dt><dd><code><?php echo e($call->attempt_id ?: 'Pending'); ?></code></dd>
            <?php if($call->failure_reason): ?>
              <dt>Failure reason</dt><dd class="text-bad"><?php echo e($call->failure_reason); ?></dd>
            <?php endif; ?>
            <?php $__currentLoopData = $output; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <dt class="break"><?php echo e($key); ?></dt>
              <dd>
                <?php if(is_scalar($value) || $value === null): ?>
                  <?php echo e($value === null ? '--' : (is_bool($value) ? ($value ? 'true' : 'false') : $value)); ?>

                <?php else: ?>
                  <code><?php echo e(json_encode($value, JSON_UNESCAPED_UNICODE)); ?></code>
                <?php endif; ?>
              </dd>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </dl>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\likhi\Downloads\sarvamaiproject\resources\views/calls/show.blade.php ENDPATH**/ ?>