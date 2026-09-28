<?php $__env->startSection('title', $campaign->name); ?>

<?php use App\Support\LeadOutcome; ?>

<?php $__env->startSection('content'); ?>

<div class="ph">
  <div class="ph-main">
    <a href="<?php echo e(route('campaigns.index')); ?>" class="ph-back"><i class="icon-arrow-left"></i> Campaigns</a>
    <h1><?php echo e($campaign->name); ?></h1>
    <div class="ph-meta">
      <span class="badge <?php echo e($campaign->status_badge); ?>" id="campaignStatus"><span class="dot"></span><?php echo e(ucfirst($campaign->status)); ?></span>
      <span><?php echo e(number_format($campaign->total_contacts)); ?> contacts</span>
      <span>· Created <?php echo e($campaign->created_at->format('d M Y, g:i A')); ?></span>
      <?php if($campaign->description): ?><span>· <?php echo e($campaign->description); ?></span><?php endif; ?>
    </div>
  </div>

  <?php if($campaign->isRemote()): ?>
    <div class="ph-actions">
      <button type="button" class="btn btn-secondary btn-sm" data-campaign-action="pause"><i class="icon-pause"></i> Pause</button>
      <button type="button" class="btn btn-secondary btn-sm" data-campaign-action="resume"><i class="icon-play"></i> Resume</button>
      <button type="button" class="btn btn-danger btn-sm" data-campaign-action="cancel"><i class="icon-circle-x"></i> Cancel</button>
    </div>
  <?php endif; ?>
</div>

<?php if($campaign->error_message): ?>
  <div class="callout callout-danger mb-5"><i class="icon-circle-alert"></i><div><?php echo e($campaign->error_message); ?></div></div>
<?php endif; ?>

<div id="campaignAlert" hidden></div>

<?php if($stats): ?>
  <?php
    $target    = max((int) $campaign->total_contacts, (int) $stats->total);
    $completed = (int) $stats->completed;
    $failed    = (int) $stats->failed + (int) $stats->no_answer + (int) $stats->busy;
    $percent   = $target > 0 ? round($completed / $target * 100, 1) : 0;
  ?>

  <div class="grid cols-4 mb-section">
    <?php if (isset($component)) { $__componentOriginal3b387acd2c997737a257e1ec014549fd = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3b387acd2c997737a257e1ec014549fd = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat','data' => ['label' => 'Total contacts','value' => number_format($target),'icon' => 'icon-users','tone' => 'ink']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Total contacts','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(number_format($target)),'icon' => 'icon-users','tone' => 'ink']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat','data' => ['label' => 'Completed','value' => number_format($completed),'icon' => 'icon-circle-check','valueTone' => 'good','hint' => $percent . '% of the list']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Completed','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(number_format($completed)),'icon' => 'icon-circle-check','value-tone' => 'good','hint' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($percent . '% of the list')]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat','data' => ['label' => 'Not reached','value' => number_format($failed),'icon' => 'icon-phone-missed','tone' => 'red','valueTone' => $failed ? 'bad' : null,'hint' => (int) $stats->no_answer . ' no answer · ' . (int) $stats->busy . ' busy · ' . (int) $stats->failed . ' failed']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Not reached','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(number_format($failed)),'icon' => 'icon-phone-missed','tone' => 'red','value-tone' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($failed ? 'bad' : null),'hint' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute((int) $stats->no_answer . ' no answer · ' . (int) $stats->busy . ' busy · ' . (int) $stats->failed . ' failed')]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat','data' => ['label' => 'Remaining','value' => number_format(max(0, $target - $completed)),'icon' => 'icon-clock','tone' => 'amber']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Remaining','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(number_format(max(0, $target - $completed))),'icon' => 'icon-clock','tone' => 'amber']); ?>
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

  <div class="card mb-section">
    <div class="card-h">
      <div><h2>Progress</h2><p><?php echo e($completed); ?> of <?php echo e($target); ?> calls completed</p></div>
      <span class="badge badge-teal"><?php echo e($percent); ?>%</span>
    </div>
    <div class="card-b">
      <div class="progress thick"><div class="progress-fill" style="width: <?php echo e($percent); ?>%"></div></div>
      <div class="meter mt-4">
        <div class="meter-cell"><span>Connected</span><strong class="text-good"><?php echo e((int) $stats->connected); ?></strong></div>
        <div class="meter-cell"><span>No answer</span><strong><?php echo e((int) $stats->no_answer); ?></strong></div>
        <div class="meter-cell"><span>Busy</span><strong><?php echo e((int) $stats->busy); ?></strong></div>
        <div class="meter-cell"><span>Failed</span><strong class="<?php echo e($stats->failed ? 'text-bad' : ''); ?>"><?php echo e((int) $stats->failed); ?></strong></div>
      </div>
    </div>
  </div>
<?php else: ?>
  <div class="card mb-section">
    <?php if (isset($component)) { $__componentOriginal4f22a152e0729cd34293e65bd200d933 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4f22a152e0729cd34293e65bd200d933 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.empty','data' => ['icon' => 'icon-loader-circle','title' => 'Not dispatched yet']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('empty'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'icon-loader-circle','title' => 'Not dispatched yet']); ?>
      Progress appears once the contacts have been queued with the agent. Make sure the queue worker is running.
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
<?php endif; ?>

<?php if($attempts): ?>
  <div class="card">
    <div class="card-h"><div><h2>Results</h2><p>Every call attempt in this campaign</p></div></div>
    <?php if($attempts->isEmpty()): ?>
      <?php if (isset($component)) { $__componentOriginal4f22a152e0729cd34293e65bd200d933 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4f22a152e0729cd34293e65bd200d933 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.empty','data' => ['icon' => 'icon-phone','title' => 'No attempts recorded yet','compact' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('empty'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'icon-phone','title' => 'No attempts recorded yet','compact' => true]); ?>Results appear here as the agent places calls. <?php echo $__env->renderComponent(); ?>
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
            <tr><th>Customer</th><th>Status</th><th>Outcome</th><th class="num">Duration</th><th>When</th><th class="end"></th></tr>
          </thead>
          <tbody>
            <?php $__currentLoopData = $attempts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $attempt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <tr>
                <td>
                  <span class="cell-main"><?php echo e($attempt->customer?->name ?: 'Unknown'); ?></span>
                  <span class="cell-sub"><?php echo e($attempt->display_phone); ?></span>
                </td>
                <td><span class="badge <?php echo e(LeadOutcome::connectivityBadge($attempt)); ?>"><?php echo e(LeadOutcome::connectivityLabel($attempt)); ?></span></td>
                <td><span class="badge <?php echo e(LeadOutcome::badge($attempt->call_disposition)); ?>"><?php echo e(LeadOutcome::label($attempt->call_disposition)); ?></span></td>
                <td class="num"><?php echo e($attempt->duration_for_humans); ?></td>
                <td class="nowrap muted"><?php echo e($attempt->created_at->diffForHumans()); ?></td>
                <td class="end"><a href="<?php echo e(route('calls.show', $attempt)); ?>" class="btn btn-secondary btn-xs">View</a></td>
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
<?php endif; ?>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<?php if($campaign->isRemote()): ?>
<script>
(function () {
  var csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
  var url = <?php echo json_encode(route('campaigns.status', $campaign), 512) ?>;
  var box = document.getElementById('campaignAlert');

  function say(ok, message) {
    box.className = 'callout mb-5 callout-' + (ok ? 'success' : 'danger');
    box.textContent = message;
    box.hidden = false;
  }

  document.querySelectorAll('[data-campaign-action]').forEach(function (btn) {
    btn.addEventListener('click', async function () {
      var action = btn.dataset.campaignAction;
      if (action === 'cancel' && !confirm('Cancel this campaign? Remaining calls will not be placed.')) return;

      btn.disabled = true;
      try {
        var res = await fetch(url, {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' },
          body: JSON.stringify({ action: action }),
        });
        var data = await res.json().catch(function () { return {}; });
        say(!!data.ok, data.message || 'Request failed.');

        if (data.ok && data.status) {
          var badge = document.getElementById('campaignStatus');
          if (badge) badge.lastChild.textContent = data.status.charAt(0).toUpperCase() + data.status.slice(1);
        }
      } catch (e) {
        say(false, 'Network error. Please try again.');
      } finally {
        btn.disabled = false;
      }
    });
  });
})();
</script>
<?php endif; ?>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\likhi\Downloads\sarvamaiproject\resources\views/campaigns/show.blade.php ENDPATH**/ ?>