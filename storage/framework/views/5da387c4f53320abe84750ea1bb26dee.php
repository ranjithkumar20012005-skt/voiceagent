<?php $__env->startSection('title', 'Analytics'); ?>

<?php $__env->startSection('content'); ?>

<div class="ph">
  <div class="ph-main">
    <div class="eyebrow">Insights</div>
    <h1>Analytics</h1>
    <p class="ph-sub">How your calling is performing. Every figure is counted from recorded calls — nothing is estimated.</p>
  </div>
  <div class="tabs-pill" role="tablist" aria-label="Date range">
    <?php $__currentLoopData = $ranges; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => [$label]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <a href="<?php echo e(route('analytics.index', ['range' => $key])); ?>" class="<?php echo e($range === $key ? 'active' : ''); ?>" role="tab" aria-selected="<?php echo e($range === $key ? 'true' : 'false'); ?>"><?php echo e($label); ?></a>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
  </div>
</div>

<?php if($totals['total_calls'] === 0): ?>
  <div class="card">
    <?php if (isset($component)) { $__componentOriginal4f22a152e0729cd34293e65bd200d933 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4f22a152e0729cd34293e65bd200d933 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.empty','data' => ['icon' => 'icon-chart-column','title' => 'No calls in this period']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('empty'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'icon-chart-column','title' => 'No calls in this period']); ?>
      Analytics fill in as calls are placed. Try a longer range, or place a call.
       <?php $__env->slot('action', null, []); ?> 
        <button type="button" class="btn btn-primary btn-sm" data-new-call><i class="icon-phone"></i> New Call</button>
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
  </div>
<?php else: ?>

<div class="grid cols-4 mb-4">
  <?php if (isset($component)) { $__componentOriginal3b387acd2c997737a257e1ec014549fd = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3b387acd2c997737a257e1ec014549fd = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat','data' => ['label' => 'Total calls','value' => number_format($totals['total_calls']),'icon' => 'icon-phone','hint' => $totals['in_flight'] ? $totals['in_flight'] . ' in progress' : null]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Total calls','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(number_format($totals['total_calls'])),'icon' => 'icon-phone','hint' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($totals['in_flight'] ? $totals['in_flight'] . ' in progress' : null)]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat','data' => ['label' => 'Connected','value' => number_format($totals['connected']),'icon' => 'icon-phone-call','tone' => 'teal','valueTone' => 'good','hint' => ($totals['connect_rate'] ?? 0) . '% connect rate']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Connected','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(number_format($totals['connected'])),'icon' => 'icon-phone-call','tone' => 'teal','value-tone' => 'good','hint' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(($totals['connect_rate'] ?? 0) . '% connect rate')]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat','data' => ['label' => 'Failed / not reached','value' => number_format($totals['failed'] + $totals['no_answer'] + $totals['busy']),'icon' => 'icon-phone-missed','tone' => 'red','hint' => $totals['failed'] . ' failed · ' . $totals['no_answer'] . ' no answer · ' . $totals['busy'] . ' busy']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Failed / not reached','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(number_format($totals['failed'] + $totals['no_answer'] + $totals['busy'])),'icon' => 'icon-phone-missed','tone' => 'red','hint' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($totals['failed'] . ' failed · ' . $totals['no_answer'] . ' no answer · ' . $totals['busy'] . ' busy')]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat','data' => ['label' => 'Avg. duration','value' => $totals['avg_duration_human'] ?? '—','icon' => 'icon-timer','tone' => 'ink','hint' => $totals['talk_time_human'] . ' total']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Avg. duration','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($totals['avg_duration_human'] ?? '—'),'icon' => 'icon-timer','tone' => 'ink','hint' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($totals['talk_time_human'] . ' total')]); ?>
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
<div class="grid cols-4 mb-section">
  <?php if (isset($component)) { $__componentOriginal3b387acd2c997737a257e1ec014549fd = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3b387acd2c997737a257e1ec014549fd = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat','data' => ['label' => 'Leads','value' => number_format($totals['leads']),'icon' => 'icon-user-check','valueTone' => 'good','hint' => $totals['lead_rate'] !== null ? $totals['lead_rate'] . '% of connected calls' : null]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Leads','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(number_format($totals['leads'])),'icon' => 'icon-user-check','value-tone' => 'good','hint' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($totals['lead_rate'] !== null ? $totals['lead_rate'] . '% of connected calls' : null)]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat','data' => ['label' => 'Qualified leads','value' => number_format($totals['qualified']),'icon' => 'icon-circle-check','hint' => 'Flagged by the agent']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Qualified leads','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(number_format($totals['qualified'])),'icon' => 'icon-circle-check','hint' => 'Flagged by the agent']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat','data' => ['label' => 'Callbacks requested','value' => number_format($totals['callbacks_requested']),'icon' => 'icon-calendar-clock','tone' => 'amber']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Callbacks requested','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(number_format($totals['callbacks_requested'])),'icon' => 'icon-calendar-clock','tone' => 'amber']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat','data' => ['label' => 'Talk time','value' => number_format($totals['minutes']),'icon' => 'icon-clock','tone' => 'ink','hint' => 'minutes, rounded up']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Talk time','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(number_format($totals['minutes'])),'icon' => 'icon-clock','tone' => 'ink','hint' => 'minutes, rounded up']); ?>
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

<div class="split-even mb-section">
  <div class="card">
    <div class="card-h">
      <div><h2>Calls over time</h2><p>Last <?php echo e(count($series['labels'])); ?> days</p></div>
      <div class="legend">
        <span><i style="background:#65B82E"></i>Connected</span>
        <span><i style="background:#f4a3a2"></i>Not reached</span>
        <span><i style="background:#cfd6d2"></i>Other</span>
      </div>
    </div>
    <div class="card-b"><div class="chart-box"><canvas id="seriesChart" role="img" aria-label="Calls per day"></canvas></div></div>
  </div>

  <div class="card">
    <div class="card-h"><div><h2>Call outcomes</h2><p>As reported by the agent</p></div></div>
    <div class="card-b">
      <?php if($outcomes): ?>
        <div class="chart-box sm"><canvas id="outcomeChart" role="img" aria-label="Call outcomes"></canvas></div>
        <div class="legend mt-4" id="outcomeLegend"></div>
      <?php else: ?>
        <?php if (isset($component)) { $__componentOriginal4f22a152e0729cd34293e65bd200d933 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4f22a152e0729cd34293e65bd200d933 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.empty','data' => ['icon' => 'icon-list-checks','title' => 'No outcomes yet','compact' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('empty'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'icon-list-checks','title' => 'No outcomes yet','compact' => true]); ?>Outcomes appear once conversations finish. <?php echo $__env->renderComponent(); ?>
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

<div class="split-even">
  <div class="card">
    <div class="card-h"><div><h2>Campaign performance</h2><p>Most recent campaigns</p></div><a href="<?php echo e(route('campaigns.index')); ?>" class="btn btn-ghost btn-xs">All →</a></div>
    <?php if($campaigns->isEmpty()): ?>
      <?php if (isset($component)) { $__componentOriginal4f22a152e0729cd34293e65bd200d933 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4f22a152e0729cd34293e65bd200d933 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.empty','data' => ['icon' => 'icon-megaphone','title' => 'No campaigns yet','compact' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('empty'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'icon-megaphone','title' => 'No campaigns yet','compact' => true]); ?>Campaign results appear here once a bulk run is dispatched. <?php echo $__env->renderComponent(); ?>
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
          <thead><tr><th>Campaign</th><th class="num">Contacts</th><th class="num">Connected</th><th class="num">Not reached</th><th class="num">Leads</th><th style="min-width:120px">Progress</th></tr></thead>
          <tbody>
            <?php $__currentLoopData = $campaigns; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <tr>
                <td><a href="<?php echo e(route('campaigns.show', $c['id'])); ?>" class="cell-main"><?php echo e($c['name']); ?></a><span class="cell-sub"><?php echo e(ucfirst($c['status'])); ?></span></td>
                <td class="num"><?php echo e(number_format($c['target'])); ?></td>
                <td class="num"><?php echo e(number_format($c['connected'])); ?></td>
                <td class="num"><?php echo e(number_format($c['unreached'])); ?></td>
                <td class="num text-good"><?php echo e(number_format($c['leads'])); ?></td>
                <td><div class="row"><div class="progress thin" style="flex:1"><div class="progress-fill" style="width: <?php echo e($c['percent']); ?>%"></div></div><span class="text-xs muted"><?php echo e(round($c['percent'])); ?>%</span></div></td>
              </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <div class="card">
    <div class="card-h"><div><h2>By agent</h2><p>Calls in this period</p></div></div>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>Agent</th><th class="num">Calls</th><th class="num">Connected</th><th class="num">Leads</th><th class="num">Minutes</th></tr></thead>
        <tbody>
          <?php $__currentLoopData = $agents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $a): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr>
              <td class="cell-main"><?php echo e($a['name']); ?></td>
              <td class="num"><?php echo e(number_format($a['total'])); ?></td>
              <td class="num"><?php echo e(number_format($a['connected'])); ?></td>
              <td class="num text-good"><?php echo e(number_format($a['leads'])); ?></td>
              <td class="num"><?php echo e(number_format($a['minutes'])); ?></td>
            </tr>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php endif; ?>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<?php if($totals['total_calls'] > 0): ?>
<script src="<?php echo e(asset('assets/js/vendor/chart.umd.min.js')); ?>"></script>
<script>
(function () {
  AppCharts.defaults();
  var s = <?php echo json_encode($series, 15, 512) ?>;
  var other = s.total.map(function (t, i) { return Math.max(0, t - s.connected[i] - s.failed[i]); });

  new Chart(document.getElementById('seriesChart'), {
    type: 'bar',
    data: {
      labels: s.labels,
      datasets: [
        { label: 'Connected', data: s.connected, backgroundColor: '#65B82E', borderRadius: 3, maxBarThickness: 22 },
        { label: 'Not reached', data: s.failed, backgroundColor: '#f4a3a2', borderRadius: 3, maxBarThickness: 22 },
        { label: 'Other', data: other, backgroundColor: '#cfd6d2', borderRadius: 3, maxBarThickness: 22 },
      ],
    },
    options: {
      interaction: { mode: 'index', intersect: false },
      scales: {
        x: { stacked: true, grid: { display: false }, ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 10 } },
        y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } },
      },
    },
  });

  var outcomes = <?php echo json_encode(array_values($outcomes), 15, 512) ?>;
  var el = document.getElementById('outcomeChart');
  if (el && outcomes.length) {
    var colors = { 'Interested': '#65B82E', 'Follow-up': '#00A89D', 'Not Interested': '#e34948', 'Already Completed': '#7a5af8', 'Wrong Person': '#eda100', 'Do Not Contact': '#b42318', 'Needs Attention': '#f79009', 'No Outcome': '#98a2b3' };
    var bg = outcomes.map(function (o) { return colors[o.label] || '#98a2b3'; });

    new Chart(el, {
      type: 'doughnut',
      data: { labels: outcomes.map(function (o) { return o.label; }), datasets: [{ data: outcomes.map(function (o) { return o.count; }), backgroundColor: bg, borderWidth: 2, borderColor: '#fff' }] },
      options: { cutout: '68%' },
    });

    var legend = document.getElementById('outcomeLegend');
    outcomes.forEach(function (o, i) {
      var span = document.createElement('span');
      var dot = document.createElement('i');
      dot.style.background = bg[i];
      span.appendChild(dot);
      span.appendChild(document.createTextNode(o.label + ' · ' + o.count));
      legend.appendChild(span);
    });
  }
})();
</script>
<?php endif; ?>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\likhi\Downloads\sarvamaiproject\resources\views/insights/analytics.blade.php ENDPATH**/ ?>