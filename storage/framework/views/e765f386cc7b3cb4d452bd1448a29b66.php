<?php $__env->startSection('title', 'My Agents'); ?>

<?php $__env->startSection('content'); ?>

<div class="ph">
  <div class="ph-main">
    <div class="eyebrow">Agents</div>
    <h1>My Agents</h1>
    <p class="ph-sub">The voice agents your team can call with. Each one points at an agent built in Sarvam; calls use the workspace phone line.</p>
  </div>
  <div class="ph-actions">
    <a href="<?php echo e(route('agents.create')); ?>" class="btn btn-primary btn-sm"><i class="icon-plus"></i> Create Agent</a>
  </div>
</div>

<div class="grid cols-3">

  
  <div class="card agent-card">
    <div class="card-b">
      <div class="agent-top">
        <div class="agent-avatar env"><i class="icon-bot"></i></div>
        <div class="row wrap" style="justify-content:flex-end">
          <span class="badge <?php echo e($workspace['configured'] ? 'badge-success' : 'badge-warning'); ?>">
            <span class="dot"></span><?php echo e($workspace['configured'] ? 'Configured' : 'Not configured'); ?>

          </span>
          <?php if($agents->where('is_default', true)->isEmpty()): ?>
            <span class="badge badge-brand">Default</span>
          <?php endif; ?>
        </div>
      </div>
      <h3>Workspace agent</h3>
      <p class="agent-desc">Set in the server environment. Used whenever no other agent is selected, and by campaigns and automations.</p>
      <div class="agent-meta">
        <span><i class="icon-hash"></i><?php echo e($workspace['agent_name'] ?: 'No agent ID'); ?></span>
        <span><i class="icon-layers"></i>v<?php echo e($workspace['agent_version'] ?: '—'); ?></span>
        <span><i class="icon-languages"></i><?php echo e($workspace['default_language'] ?: config('sarvam.default_language')); ?></span>
        <span><i class="icon-phone-call"></i><?php echo e(number_format($unassigned)); ?> calls</span>
      </div>
    </div>
    <div class="card-f">
      <a href="<?php echo e(route('providers.index')); ?>" class="btn btn-secondary btn-sm btn-block">View configuration</a>
    </div>
  </div>

  <?php $__currentLoopData = $agents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $agent): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <div class="card agent-card">
      <div class="card-b">
        <div class="agent-top">
          <div class="agent-avatar"><i class="icon-bot"></i></div>
          <div class="row wrap" style="justify-content:flex-end">
            <span class="badge <?php echo e($agent->isActive() ? 'badge-success' : ''); ?>">
              <span class="dot"></span><?php echo e($agent->isActive() ? 'Active' : 'Inactive'); ?>

            </span>
            <?php if($agent->is_default): ?>
              <span class="badge badge-brand">Default</span>
            <?php endif; ?>
          </div>
        </div>
        <h3><?php echo e($agent->name); ?></h3>
        <p class="agent-desc"><?php echo e($agent->description ?: 'No description.'); ?></p>
        <div class="agent-meta">
          <span title="Sarvam agent ID"><i class="icon-hash"></i><?php echo e($agent->platform_app_id ?: 'Uses workspace agent'); ?></span>
          <?php if($agent->platform_app_version): ?>
            <span><i class="icon-layers"></i>v<?php echo e($agent->platform_app_version); ?></span>
          <?php endif; ?>
          <span><i class="icon-languages"></i><?php echo e($agent->default_language ?: 'Default language'); ?></span>
          <span><i class="icon-phone-call"></i><?php echo e(number_format($agent->call_attempts_count)); ?> calls</span>
        </div>
        <div class="text-xs faint mt-3">Updated <?php echo e($agent->updated_at->diffForHumans()); ?></div>
      </div>
      <div class="card-f">
        <a href="<?php echo e(route('agents.edit', $agent)); ?>" class="btn btn-secondary btn-sm" style="flex:1">Open</a>
        <?php if($agent->isActive() && ! $agent->is_default): ?>
          <form method="POST" action="<?php echo e(route('agents.default', $agent)); ?>" class="inline-form">
            <?php echo csrf_field(); ?>
            <button type="submit" class="btn btn-ghost btn-sm" title="Make default"><i class="icon-star"></i></button>
          </form>
        <?php endif; ?>
        <form method="POST" action="<?php echo e(route('agents.destroy', $agent)); ?>" class="inline-form"
              data-confirm="<?php echo e($agent->call_attempts_count ? 'This agent has call history, so it will be deactivated rather than deleted. Continue?' : 'Delete this agent?'); ?>">
          <?php echo csrf_field(); ?>
          <?php echo method_field('DELETE'); ?>
          <button type="submit" class="btn btn-ghost btn-sm text-bad" title="Delete" aria-label="Delete <?php echo e($agent->name); ?>"><i class="icon-trash-2"></i></button>
        </form>
      </div>
    </div>
  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

  <a href="<?php echo e(route('agents.create')); ?>" class="card card-link" style="border-style:dashed; box-shadow:none">
    <?php if (isset($component)) { $__componentOriginal4f22a152e0729cd34293e65bd200d933 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4f22a152e0729cd34293e65bd200d933 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.empty','data' => ['icon' => 'icon-plus','tone' => 'green','title' => 'Add another agent']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('empty'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'icon-plus','tone' => 'green','title' => 'Add another agent']); ?>
      Register another agent from your Sarvam workspace — for a different script, product or language.
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
  </a>
</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\likhi\Downloads\sarvamaiproject\resources\views/agents/index.blade.php ENDPATH**/ ?>