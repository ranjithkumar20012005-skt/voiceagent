<?php
  $copy = [
      'knowledge' => [
          'title' => 'Knowledge Base',
          'sub'   => 'Documents your agent can answer questions from.',
          'icon'  => 'icon-book-open',
          'what'  => 'Documents uploaded here are not connected to the voice agent, so this app does not accept uploads yet. Your agent\'s knowledge is managed in the Sarvam agent builder.',
          'next'  => ['Add or update documents on the agent in Sarvam.', 'Publish a new agent version.', 'Update the version on the agent in My Agents so calls use it.'],
      ],
      'tools' => [
          'title' => 'Tools',
          'sub'   => 'Actions your agent can take during a call — webhooks, CRM updates, bookings.',
          'icon'  => 'icon-wrench',
          'what'  => 'No tools are connected to the voice agent from this application, so none are listed as available. Tools such as webhooks or booking actions are configured on the agent in Sarvam.',
          'next'  => ['Configure the tool on the agent in Sarvam.', 'Have the agent return its result as an output variable.', 'Results then appear on the call detail page here.'],
      ],
  ][$feature];
?>

<?php $__env->startSection('title', $copy['title']); ?>

<?php $__env->startSection('content'); ?>

<div class="ph">
  <div class="ph-main">
    <div class="eyebrow">Build</div>
    <h1><?php echo e($copy['title']); ?></h1>
    <p class="ph-sub"><?php echo e($copy['sub']); ?></p>
  </div>
  <span class="badge badge-lg"><span class="dot"></span>Not connected</span>
</div>

<div class="split-even">
  <div class="card">
    <?php if (isset($component)) { $__componentOriginal4f22a152e0729cd34293e65bd200d933 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4f22a152e0729cd34293e65bd200d933 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.empty','data' => ['icon' => $copy['icon'],'tone' => 'amber','title' => 'Not connected in this application']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('empty'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($copy['icon']),'tone' => 'amber','title' => 'Not connected in this application']); ?>
      <?php echo e($copy['what']); ?>

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
  </div>

  <div class="card">
    <div class="card-h"><div><h3>How to do this today</h3></div></div>
    <div class="card-b">
      <ul class="feature-list">
        <?php $__currentLoopData = $copy['next']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $step): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <li><i class="icon-check"></i><span><?php echo e($step); ?></span></li>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </ul>
    </div>
    <div class="card-f"><a href="<?php echo e(route('agents.index')); ?>" class="btn btn-secondary btn-sm">Go to My Agents</a></div>
  </div>
</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\likhi\Downloads\sarvamaiproject\resources\views/insights/unavailable.blade.php ENDPATH**/ ?>