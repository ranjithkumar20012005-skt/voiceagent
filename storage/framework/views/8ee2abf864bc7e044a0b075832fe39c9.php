<header class="site-header" id="siteHeader">
  <div class="container site-header-inner">
    <?php if (isset($component)) { $__componentOriginal6328f0deb07a8bef5ad2cd5691beb925 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6328f0deb07a8bef5ad2cd5691beb925 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.brand','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('brand'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
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

    <nav class="site-nav" aria-label="Main">
      <a href="<?php echo e(route('home')); ?>#how-it-works">How It Works</a>
      <a href="<?php echo e(route('home')); ?>#why-us">Why Choose Us</a>
      <a href="<?php echo e(route('home')); ?>#pricing">Pricing</a>
      <a href="<?php echo e(route('home')); ?>#faq">FAQs</a>
    </nav>

    <div class="site-actions">
      <?php if(auth()->guard()->check()): ?>
        <a href="<?php echo e(route('dashboard')); ?>" class="btn btn-primary">Go to Dashboard</a>
      <?php else: ?>
        <a href="<?php echo e(route('login')); ?>" class="btn btn-plain">Sign In</a>
        <a href="<?php echo e(route('start')); ?>" class="btn btn-primary">Start Free</a>
      <?php endif; ?>

      <button type="button" class="burger" id="siteBurger" aria-label="Menu" aria-expanded="false" aria-controls="mobilePanel">
        <svg class="open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
        <svg class="close" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18"/></svg>
      </button>
    </div>
  </div>

  <div class="mobile-panel" id="mobilePanel">
    <a class="link" href="<?php echo e(route('home')); ?>#how-it-works">How It Works</a>
    <a class="link" href="<?php echo e(route('home')); ?>#why-us">Why Choose Us</a>
    <a class="link" href="<?php echo e(route('home')); ?>#pricing">Pricing</a>
    <a class="link" href="<?php echo e(route('home')); ?>#faq">FAQs</a>
    <div class="row">
      <?php if(auth()->guard()->check()): ?>
        <a href="<?php echo e(route('dashboard')); ?>" class="btn btn-primary" style="grid-column:1/-1">Go to Dashboard</a>
      <?php else: ?>
        <a href="<?php echo e(route('login')); ?>" class="btn btn-secondary">Sign In</a>
        <a href="<?php echo e(route('start')); ?>" class="btn btn-primary">Start Free</a>
      <?php endif; ?>
    </div>
  </div>
</header>
<?php /**PATH C:\Users\likhi\Downloads\sarvamaiproject\resources\views/public/partials/header.blade.php ENDPATH**/ ?>