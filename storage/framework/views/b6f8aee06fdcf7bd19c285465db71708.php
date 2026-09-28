

<?php $__env->startSection('title', 'Dashboard'); ?>

<?php use App\Support\LeadOutcome; ?>

<?php $__env->startSection('content'); ?>

<div class="ph">
  <div class="ph-main">
    <div class="eyebrow">Overview</div>
    <h1>Welcome back, <?php echo e(Str::before(auth()->user()->name, ' ')); ?></h1>
    <p class="ph-sub">Monitor AI calling activity and customer outcomes.</p>
  </div>
  <div class="ph-actions">
    <span class="text-xs muted hide-sm" id="lastUpdated"><?php echo e($live ? 'Live — refreshing while calls are in progress' : ''); ?></span>
    <a href="<?php echo e(route('analytics.index')); ?>" class="btn btn-secondary btn-sm"><i class="icon-chart-column"></i> Analytics</a>
    <button type="button" class="btn btn-primary btn-sm" data-new-call><i class="icon-phone"></i> New Call</button>
  </div>
</div>

<?php if (! ($configured)): ?>
  <div class="callout callout-warning mb-5">
    <i class="icon-circle-alert"></i>
    <div>Calling is not available yet. An administrator needs to finish the server setup — see <a href="<?php echo e(route('providers.index')); ?>">Providers</a>.</div>
  </div>
<?php endif; ?>


<div class="section-label"><h2>Today</h2></div>
<div class="grid cols-5 mb-section">
  <?php if (isset($component)) { $__componentOriginal3b387acd2c997737a257e1ec014549fd = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3b387acd2c997737a257e1ec014549fd = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat','data' => ['label' => 'Calls Today','value' => $kpis['calls_today'],'icon' => 'icon-phone-outgoing','key' => 'calls_today']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Calls Today','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($kpis['calls_today']),'icon' => 'icon-phone-outgoing','key' => 'calls_today']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat','data' => ['label' => 'Connected','value' => $kpis['connected'],'icon' => 'icon-phone-call','tone' => 'teal','key' => 'connected']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Connected','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($kpis['connected']),'icon' => 'icon-phone-call','tone' => 'teal','key' => 'connected']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat','data' => ['label' => 'Hot Leads','value' => $kpis['hot_leads'],'icon' => 'icon-user-check','key' => 'hot_leads','valueTone' => 'good']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Hot Leads','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($kpis['hot_leads']),'icon' => 'icon-user-check','key' => 'hot_leads','value-tone' => 'good']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat','data' => ['label' => 'Follow-ups','value' => $kpis['follow_ups'],'icon' => 'icon-calendar-clock','tone' => 'amber','key' => 'follow_ups']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Follow-ups','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($kpis['follow_ups']),'icon' => 'icon-calendar-clock','tone' => 'amber','key' => 'follow_ups']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat','data' => ['label' => 'Total Minutes','value' => $kpis['total_minutes'],'icon' => 'icon-clock','tone' => 'ink','key' => 'total_minutes','hint' => 'Average call ' . ($kpis['avg_duration_human'] ?? 'unavailable')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Total Minutes','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($kpis['total_minutes']),'icon' => 'icon-clock','tone' => 'ink','key' => 'total_minutes','hint' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('Average call ' . ($kpis['avg_duration_human'] ?? 'unavailable'))]); ?>
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


<div class="section-label"><h2>All time</h2><a href="<?php echo e(route('calls.index')); ?>">Call logs →</a></div>
<div class="grid cols-4 mb-section">
  <?php if (isset($component)) { $__componentOriginal3b387acd2c997737a257e1ec014549fd = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3b387acd2c997737a257e1ec014549fd = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat','data' => ['label' => 'Total Calls','value' => number_format($totals['total_calls']),'icon' => 'icon-phone','hint' => $totals['connect_rate'] !== null ? $totals['connect_rate'] . '% connected' : 'No calls yet']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Total Calls','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(number_format($totals['total_calls'])),'icon' => 'icon-phone','hint' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($totals['connect_rate'] !== null ? $totals['connect_rate'] . '% connected' : 'No calls yet')]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat','data' => ['label' => 'Failed Calls','value' => number_format($totals['failed']),'icon' => 'icon-phone-missed','tone' => 'red','valueTone' => $totals['failed'] > 0 ? 'bad' : null,'hint' => number_format($totals['no_answer']) . ' no answer · ' . number_format($totals['busy']) . ' busy']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Failed Calls','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(number_format($totals['failed'])),'icon' => 'icon-phone-missed','tone' => 'red','value-tone' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($totals['failed'] > 0 ? 'bad' : null),'hint' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(number_format($totals['no_answer']) . ' no answer · ' . number_format($totals['busy']) . ' busy')]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat','data' => ['label' => 'Avg. Duration','value' => $totals['avg_duration_human'] ?? '—','icon' => 'icon-timer','tone' => 'teal','hint' => $totals['talk_time_human'] . ' total talk time']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Avg. Duration','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($totals['avg_duration_human'] ?? '—'),'icon' => 'icon-timer','tone' => 'teal','hint' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($totals['talk_time_human'] . ' total talk time')]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat','data' => ['label' => 'Customers','value' => number_format($customers['customers']),'icon' => 'icon-users','tone' => 'ink','hint' => number_format($customers['never_called']) . ' not called yet']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Customers','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(number_format($customers['customers'])),'icon' => 'icon-users','tone' => 'ink','hint' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(number_format($customers['never_called']) . ' not called yet')]); ?>
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
      <div>
        <h2>Calls over time</h2>
        <p>Last 14 days · connected vs. not reached</p>
      </div>
      <?php if($series['has_data']): ?>
        <div class="legend">
          <span><i style="background:#65B82E"></i>Connected</span>
          <span><i style="background:#f4a3a2"></i>Not reached</span>
          <span><i style="background:#cfd6d2"></i>Other</span>
        </div>
      <?php endif; ?>
    </div>
    <div class="card-b">
      <?php if($series['has_data']): ?>
        <div class="chart-box"><canvas id="callsChart" aria-label="Calls per day for the last 14 days" role="img"></canvas></div>
      <?php else: ?>
        <?php if (isset($component)) { $__componentOriginal4f22a152e0729cd34293e65bd200d933 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4f22a152e0729cd34293e65bd200d933 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.empty','data' => ['icon' => 'icon-chart-column','title' => 'No calls in the last 14 days','compact' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('empty'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'icon-chart-column','title' => 'No calls in the last 14 days','compact' => true]); ?>
          The chart fills in as calls are placed.
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

  <div class="card">
    <div class="card-h">
      <div>
        <h2>Call outcomes</h2>
        <p>What the agent reported, all time</p>
      </div>
      <span class="badge"><?php echo e(number_format($overview['total'])); ?> calls</span>
    </div>
    <div class="card-b">
      <?php $outcomeTotal = array_sum(array_column($outcomes, 'count')); ?>
      <?php if($outcomeTotal > 0): ?>
        <?php $__currentLoopData = $outcomes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <?php
            $pct = round($row['count'] / $outcomeTotal * 100, 1);
            $fill = match ($key) {
              'interested' => '', 'callback' => 'teal', 'not_interested', 'do_not_call' => 'red',
              'already_renewed' => 'violet', 'wrong_person', 'escalated' => 'amber', default => 'ink',
            };
          ?>
          <div class="bar-row">
            <div class="bar-row-head">
              <span><?php echo e($row['label']); ?></span>
              <span><?php echo e($row['count']); ?><small><?php echo e($pct); ?>%</small></span>
            </div>
            <div class="progress thin"><div class="progress-fill <?php echo e($fill); ?>" style="width: <?php echo e($pct); ?>%"></div></div>
          </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
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
<?php $component->withAttributes(['icon' => 'icon-list-checks','title' => 'No outcomes yet','compact' => true]); ?>
          Outcomes appear once the agent finishes a conversation.
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

<div class="split mb-section">
  
  <div class="card">
    <div class="card-h">
      <div>
        <h2>Hot Leads</h2>
        <p>Customers the agent identified as interested.</p>
      </div>
      <?php if(count($hotLeads)): ?>
        <a href="<?php echo e(route('leads.index')); ?>" class="btn btn-secondary btn-sm">View all</a>
      <?php endif; ?>
    </div>

    <?php $__empty_1 = true; $__currentLoopData = $hotLeads; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $lead): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
      <?php $name = $lead->customer?->name ?: 'Unknown customer'; ?>
      <div class="list-row">
        <span class="avatar lg soft"><?php echo e(Str::upper(Str::substr(trim($name), 0, 1)) ?: '?'); ?></span>
        <div class="list-main">
          <div class="list-title">
            <?php echo e($name); ?>

            <span class="badge <?php echo e(LeadOutcome::badge($lead->call_disposition)); ?>"><?php echo e(LeadOutcome::hotHeadline($lead)); ?></span>
          </div>
          <div class="list-meta"><?php echo e($lead->display_phone); ?> · <?php echo e($lead->created_at->format('d M, g:i A')); ?> · <?php echo e($lead->duration_for_humans); ?></div>
        </div>
        <div class="list-actions">
          <a href="<?php echo e(route('calls.show', $lead)); ?>" class="btn btn-secondary btn-xs">View Call</a>
          <?php if($lead->customer && ! $lead->customer->do_not_call): ?>
            <button type="button" class="btn btn-subtle btn-xs"
                    data-new-call
                    data-customer-id="<?php echo e($lead->customer->id); ?>"
                    data-name="<?php echo e($lead->customer->name); ?>"
                    data-phone="<?php echo e($lead->customer->phone_number); ?>"
                    data-policy="<?php echo e($lead->customer->policy_number); ?>">
              <i class="icon-phone"></i> Call again
            </button>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
      <?php if (isset($component)) { $__componentOriginal4f22a152e0729cd34293e65bd200d933 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4f22a152e0729cd34293e65bd200d933 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.empty','data' => ['icon' => 'icon-user-check','title' => 'No hot leads yet']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('empty'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'icon-user-check','title' => 'No hot leads yet']); ?>
        Interested customers appear here after a call.
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

  <div class="stack">
    
    <div class="card">
      <div class="card-h">
        <div>
          <h2>Campaign progress</h2>
          <p><?php echo e($campaign ? $campaign['name'] : 'Most recent bulk run'); ?></p>
        </div>
        <?php if($campaign): ?>
          <span class="badge <?php echo e((new \App\Models\Campaign(['status' => $campaign['status']]))->status_badge); ?>"><?php echo e(ucfirst($campaign['status'])); ?></span>
        <?php endif; ?>
      </div>
      <div class="card-b">
        <?php if($campaign): ?>
          <div class="bar-row-head">
            <span><?php echo e($campaign['completed']); ?> of <?php echo e($campaign['target']); ?> completed</span>
            <span><?php echo e($campaign['percent']); ?>%</span>
          </div>
          <div class="progress"><div class="progress-fill" style="width: <?php echo e($campaign['percent']); ?>%"></div></div>
          <div class="meter mt-4">
            <div class="meter-cell"><span>Connected</span><strong><?php echo e($campaign['connected']); ?></strong></div>
            <div class="meter-cell"><span>No answer</span><strong><?php echo e($campaign['no_answer']); ?></strong></div>
            <div class="meter-cell"><span>Failed</span><strong><?php echo e($campaign['failed']); ?></strong></div>
            <div class="meter-cell"><span>Remaining</span><strong><?php echo e($campaign['remaining']); ?></strong></div>
          </div>
          <a href="<?php echo e(route('campaigns.show', $campaign['id'])); ?>" class="btn btn-secondary btn-sm btn-block mt-4">Open campaign</a>
        <?php else: ?>
          <?php if (isset($component)) { $__componentOriginal4f22a152e0729cd34293e65bd200d933 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4f22a152e0729cd34293e65bd200d933 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.empty','data' => ['icon' => 'icon-megaphone','title' => 'No campaigns yet','compact' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('empty'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'icon-megaphone','title' => 'No campaigns yet','compact' => true]); ?>
            Start a bulk run from an imported customer list.
             <?php $__env->slot('action', null, []); ?> 
              <a href="<?php echo e(route('campaigns.index')); ?>" class="btn btn-secondary btn-sm">Go to Campaigns</a>
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
        <?php endif; ?>
      </div>
    </div>

    
    <div class="card">
      <div class="card-h">
        <div>
          <h2>Upcoming callbacks</h2>
          <p>
            <?php echo e($customers['pending_callbacks']); ?> scheduled
            <?php if($customers['overdue_callbacks'] > 0): ?>
              · <span class="text-bad"><?php echo e($customers['overdue_callbacks']); ?> overdue</span>
            <?php endif; ?>
          </p>
        </div>
        <a href="<?php echo e(route('callbacks.index', $customers['overdue_callbacks'] > 0 ? ['range' => 'overdue'] : [])); ?>" class="btn btn-ghost btn-xs">All →</a>
      </div>
      <?php $__empty_1 = true; $__currentLoopData = $callbacks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $customer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <div class="list-row">
          <div class="list-main">
            <div class="list-title"><a href="<?php echo e(route('customers.show', $customer)); ?>" class="cell-main"><?php echo e($customer->name ?: 'Unnamed'); ?></a></div>
            <div class="list-meta"><?php echo e($customer->next_callback_at->format('d M, g:i A')); ?> · <?php echo e($customer->next_callback_at->diffForHumans()); ?></div>
          </div>
          <button type="button" class="btn btn-subtle btn-xs btn-icon" title="Call now" aria-label="Call <?php echo e($customer->name); ?>"
                  <?php if($customer->do_not_call): echo 'disabled'; endif; ?>
                  data-new-call data-customer-id="<?php echo e($customer->id); ?>" data-name="<?php echo e($customer->name); ?>"
                  data-phone="<?php echo e($customer->phone_number); ?>" data-policy="<?php echo e($customer->policy_number); ?>"
                  data-language="<?php echo e($customer->preferred_language); ?>">
            <i class="icon-phone"></i>
          </button>
        </div>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <?php if (isset($component)) { $__componentOriginal4f22a152e0729cd34293e65bd200d933 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4f22a152e0729cd34293e65bd200d933 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.empty','data' => ['icon' => 'icon-calendar-clock','title' => 'Nothing scheduled','compact' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('empty'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'icon-calendar-clock','title' => 'Nothing scheduled','compact' => true]); ?>
          Callbacks appear when a customer asks to be called later.
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


<div class="card">
  <div class="card-h">
    <div>
      <h2>Recent Calls</h2>
      <p>The latest calls placed by your AI agent.</p>
    </div>
    <a href="<?php echo e(route('calls.index')); ?>" class="btn btn-secondary btn-sm">View all</a>
  </div>

  <?php if($recent->isEmpty()): ?>
    <?php if (isset($component)) { $__componentOriginal4f22a152e0729cd34293e65bd200d933 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4f22a152e0729cd34293e65bd200d933 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.empty','data' => ['icon' => 'icon-phone','title' => 'No calls yet']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('empty'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'icon-phone','title' => 'No calls yet']); ?>
      Use New Call to place the first one.
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
  <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr><th>Customer</th><th>Status</th><th>Outcome</th><th class="num">Duration</th><th>Time</th><th class="end"></th></tr>
        </thead>
        <tbody>
          <?php $__currentLoopData = $recent; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $attempt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
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
  <?php endif; ?>
</div>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<?php if($series['has_data']): ?>
<script src="<?php echo e(asset('assets/js/vendor/chart.umd.min.js')); ?>"></script>
<script>
(function () {
  AppCharts.defaults();
  var s = <?php echo json_encode($series, 15, 512) ?>;
  var other = s.total.map(function (t, i) { return Math.max(0, t - s.connected[i] - s.failed[i]); });

  new Chart(document.getElementById('callsChart'), {
    type: 'bar',
    data: {
      labels: s.labels,
      datasets: [
        { label: 'Connected', data: s.connected, backgroundColor: '#65B82E', borderRadius: 4, maxBarThickness: 28 },
        { label: 'Not reached', data: s.failed, backgroundColor: '#f4a3a2', borderRadius: 4, maxBarThickness: 28 },
        { label: 'Other', data: other, backgroundColor: '#cfd6d2', borderRadius: 4, maxBarThickness: 28 },
      ],
    },
    options: {
      interaction: { mode: 'index', intersect: false },
      scales: {
        x: { stacked: true, grid: { display: false }, ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 7 } },
        y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } },
      },
    },
  });
})();
</script>
<?php endif; ?>
<script>
(function () {
  // Poll today's tiles only while there is work in flight; otherwise idle.
  var statsUrl = <?php echo json_encode(route('dashboard.stats'), 15, 512) ?>;
  var live = <?php echo json_encode($live, 15, 512) ?>;
  var timer = null;

  async function refresh() {
    try {
      var res = await fetch(statsUrl, { headers: { 'Accept': 'application/json' } });
      if (!res.ok) return;
      var data = await res.json();

      Object.keys(data.kpis || {}).forEach(function (key) {
        document.querySelectorAll('[data-kpi="' + key + '"]').forEach(function (el) { el.textContent = data.kpis[key]; });
      });

      var stamp = document.getElementById('lastUpdated');
      if (stamp) stamp.textContent = 'Updated ' + new Date().toLocaleTimeString();

      if (live && !data.live) { live = false; clearInterval(timer); }
    } catch (e) { /* transient -- the next tick retries */ }
  }

  function start() { if (live && !timer) { timer = setInterval(refresh, 7000); } }
  function stop() { clearInterval(timer); timer = null; }

  start();
  document.addEventListener('visibilitychange', function () { document.hidden ? stop() : start(); });
})();
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\likhi\Downloads\sarvamaiproject\resources\views/dashboard/index.blade.php ENDPATH**/ ?>