<?php
    /*
     | Product navigation, grouped by job. Every item points at a real route
     | backed by real data. Knowledge Base and Tools are listed as "not
     | connected" because the voice platform owns them today -- their pages
     | say so plainly rather than pretending to work.
     */
    $groups = [
        'Overview' => [
            ['route' => 'dashboard', 'match' => 'dashboard', 'icon' => 'icon-layout-dashboard', 'label' => 'Dashboard'],
        ],
        'Agents' => [
            ['route' => 'agents.index',  'match' => ['agents.index', 'agents.edit'], 'icon' => 'icon-bot', 'label' => 'My Agents'],
            ['route' => 'agents.create', 'match' => 'agents.create', 'icon' => 'icon-plus', 'label' => 'Create Agent'],
        ],
        'Calling' => [
            ['route' => 'calling.index',   'match' => 'calling.*',   'icon' => 'icon-phone-outgoing', 'label' => 'New Call'],
            ['route' => 'campaigns.index', 'match' => 'campaigns.*', 'icon' => 'icon-megaphone',      'label' => 'Campaigns'],
            ['route' => 'customers.index', 'match' => 'customers.*', 'icon' => 'icon-users',          'label' => 'Customers'],
            ['route' => 'imports.index',   'match' => 'imports.*',   'icon' => 'icon-upload',         'label' => 'Imports'],
        ],
        'Results' => [
            ['route' => 'leads.index',     'match' => 'leads.*',     'icon' => 'icon-user-check',     'label' => 'Leads'],
            ['route' => 'calls.index',     'match' => 'calls.*',     'icon' => 'icon-phone-call',     'label' => 'Call Logs'],
            ['route' => 'callbacks.index', 'match' => 'callbacks.*', 'icon' => 'icon-calendar-clock', 'label' => 'Callbacks',
             'count' => $pendingCallbacks + $overdueCallbacks, 'alert' => $overdueCallbacks > 0],
        ],
        'Automation' => [
            ['route' => 'automations.index', 'match' => 'automations.*', 'icon' => 'icon-zap', 'label' => 'Automations'],
        ],
        'Build' => [
            ['route' => 'knowledge.index', 'match' => 'knowledge.*', 'icon' => 'icon-book-open', 'label' => 'Knowledge Base', 'soon' => 'Off'],
            ['route' => 'tools.index',     'match' => 'tools.*',     'icon' => 'icon-wrench',    'label' => 'Tools',          'soon' => 'Off'],
        ],
        'Deploy' => [
            ['route' => 'phone-numbers.index', 'match' => 'phone-numbers.*', 'icon' => 'icon-hash',  'label' => 'Phone Numbers'],
            ['route' => 'providers.index',     'match' => 'providers.*',     'icon' => 'icon-boxes', 'label' => 'Providers'],
        ],
        'Insights' => [
            ['route' => 'analytics.index', 'match' => 'analytics.*', 'icon' => 'icon-chart-column', 'label' => 'Analytics'],
        ],
        'Settings' => [
            ['route' => 'usage.index',    'match' => 'usage.*',    'icon' => 'icon-gauge',    'label' => 'Usage'],
            ['route' => 'settings.index', 'match' => 'settings.*', 'icon' => 'icon-settings', 'label' => 'Settings'],
        ],
    ];
?>

<aside class="shell-sidebar" id="appSidebar" aria-label="Main navigation">
  <div class="shell-brand">
    <?php if (isset($component)) { $__componentOriginal6328f0deb07a8bef5ad2cd5691beb925 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6328f0deb07a8bef5ad2cd5691beb925 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.brand','data' => ['href' => route('dashboard')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('brand'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('dashboard'))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal6328f0deb07a8bef5ad2cd5691beb925)): ?>
<?php $attributes = $__attributesOriginal6328f0deb07a8bef5ad2cd5691beb925; ?>
<?php unset($__attributesOriginal6328f0deb07a8bef5ad2cd5691beb925); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal6328f0deb07a8bef5ad2cd5691beb925)): ?>
<?php $component = $__componentOriginal6328f0deb07a8bef5ad2cd5691beb925; ?>
<?php unset($__componentOriginal6328f0deb07a8bef5ad2cd5691beb925); ?>
<?php endif; ?>
    <button type="button" class="btn btn-ghost btn-icon btn-sm close-nav" data-nav-close aria-label="Close menu">
      <i class="icon-x"></i>
    </button>
  </div>

  <nav class="shell-nav">
    <?php $__currentLoopData = $groups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $label => $items): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <div class="nav-group">
        <div class="nav-group-label"><?php echo e($label); ?></div>
        <?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <?php if(! Route::has($item['route'])) continue; ?>
          <?php $active = request()->routeIs(...(array) $item['match']); ?>
          <a href="<?php echo e(route($item['route'])); ?>" class="nav-link <?php echo e($active ? 'active' : ''); ?>" <?php if($active): ?> aria-current="page" <?php endif; ?>>
            <i class="<?php echo e($item['icon']); ?>"></i>
            <span><?php echo e($item['label']); ?></span>
            <?php if(! empty($item['count'])): ?>
              <span class="nav-count <?php echo e(! empty($item['alert']) ? 'alert' : ''); ?>"><?php echo e($item['count']); ?></span>
            <?php elseif(! empty($item['soon'])): ?>
              <span class="nav-soon" title="Not connected to the voice agent yet"><?php echo e($item['soon']); ?></span>
            <?php endif; ?>
          </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
  </nav>

  <div class="shell-foot">
    <span class="dot <?php echo e($agentConfigured ? 'live' : ''); ?>" style="color: <?php echo e($agentConfigured ? 'var(--green-600)' : 'var(--amber-700)'); ?>"></span>
    <?php echo e($agentConfigured ? 'Calling is available' : 'Calling is not configured'); ?>

  </div>
</aside>
<?php /**PATH C:\Users\likhi\Downloads\sarvamaiproject\resources\views/components/sidebar.blade.php ENDPATH**/ ?>