<?php $__env->startSection('title', 'Phone Numbers'); ?>

<?php $__env->startSection('content'); ?>

<div class="ph">
  <div class="ph-main">
    <div class="eyebrow">Deploy</div>
    <h1>Phone Numbers</h1>
    <p class="ph-sub">The line your agents call from. Numbers are provisioned in your Sarvam workspace; this app uses the one configured on the server.</p>
  </div>
</div>

<div class="card mb-section">
  <div class="card-h"><div><h2>Numbers</h2><p><?php echo e($status['caller_number'] ? '1 number configured' : 'No number configured'); ?></p></div></div>
  <?php if($status['caller_number']): ?>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>Number</th><th>Provider</th><th>Assigned agents</th><th>Direction</th><th class="num">Calls placed</th><th>Last used</th><th>Status</th></tr></thead>
        <tbody>
          <tr>
            <td class="cell-main mono"><?php echo e(\App\Support\PhoneNumber::display($status['caller_number'])); ?></td>
            <td>Sarvam</td>
            <td>
              <div class="chips">
                <span class="chip">Workspace agent</span>
                <?php $__currentLoopData = $agents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $agent): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><span class="chip"><?php echo e($agent->name); ?></span><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
              </div>
            </td>
            <td><span class="badge badge-teal">Outbound</span></td>
            <td class="num"><?php echo e(number_format($calls)); ?></td>
            <td class="nowrap muted"><?php echo e($lastCall ? \Illuminate\Support\Carbon::parse($lastCall)->diffForHumans() : 'Never'); ?></td>
            <td><span class="badge <?php echo e($status['configured'] ? 'badge-success' : 'badge-warning'); ?>"><span class="dot"></span><?php echo e($status['configured'] ? 'Active' : 'Incomplete setup'); ?></span></td>
          </tr>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <?php if (isset($component)) { $__componentOriginal4f22a152e0729cd34293e65bd200d933 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4f22a152e0729cd34293e65bd200d933 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.empty','data' => ['icon' => 'icon-hash','tone' => 'amber','title' => 'No phone number configured']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('empty'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'icon-hash','tone' => 'amber','title' => 'No phone number configured']); ?>
      An administrator needs to set the agent's phone number and connection on the server before calls can be placed.
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

<div class="grid cols-2">
  <div class="card">
    <div class="card-b">
      <div class="row mb-2"><i class="icon-phone-incoming muted"></i><strong>Inbound calling</strong><span class="badge">Not connected</span></div>
      <p class="text-sm muted">Answering calls to this number with the AI agent is not set up in this application yet.</p>
    </div>
  </div>
  <div class="card">
    <div class="card-b">
      <div class="row mb-2"><i class="icon-plus muted"></i><strong>Adding numbers</strong></div>
      <p class="text-sm muted">Numbers are bought and connected in your Sarvam workspace. This app does not purchase or provision numbers.</p>
    </div>
  </div>
</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\likhi\Downloads\sarvamaiproject\resources\views/insights/phone-numbers.blade.php ENDPATH**/ ?>