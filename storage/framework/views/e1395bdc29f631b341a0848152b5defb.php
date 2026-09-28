
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $__env->yieldContent('title', 'AI Voice Agents'); ?> · <?php echo e(config('app.name')); ?></title>
  <meta name="description" content="<?php echo $__env->yieldContent('meta_description', 'Automate outbound customer conversations with natural AI voice agents. Launch calls, identify high-intent leads and track every outcome.'); ?>">

  <link rel="icon" type="image/svg+xml" href="<?php echo e(asset('assets/img/logo.svg')); ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Inter+Tight:wght@500;600;700&family=JetBrains+Mono:wght@400;500&display=swap">
  <link rel="stylesheet" href="<?php echo e(asset('assets/css/site.css')); ?>?v=<?php echo e(filemtime(public_path('assets/css/site.css'))); ?>">
</head>

<body>

<?php echo $__env->make('public.partials.header', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<main>
  <?php echo $__env->yieldContent('content'); ?>
</main>

<footer class="site-footer">
  <div class="container">
    <div class="footer-grid">
      <div>
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
        <p>AI voice agents that call your customers, understand the answer and hand your team a clean list of who to follow up.</p>
      </div>
      <div>
        <h4>Product</h4>
        <ul>
          <li><a href="<?php echo e(route('home')); ?>#how-it-works">How it works</a></li>
          <li><a href="<?php echo e(route('home')); ?>#why-us">Why choose us</a></li>
          <li><a href="<?php echo e(route('home')); ?>#pricing">Pricing</a></li>
        </ul>
      </div>
      <div>
        <h4>Resources</h4>
        <ul>
          <li><a href="<?php echo e(route('home')); ?>#faq">FAQs</a></li>
          <li><a href="mailto:<?php echo e(config('pricing.contact_email')); ?>">Contact sales</a></li>
        </ul>
      </div>
      <div>
        <h4>Account</h4>
        <ul>
          <li><a href="<?php echo e(route('login')); ?>">Sign in</a></li>
          <li><a href="<?php echo e(route('start')); ?>">Start free</a></li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <span>&copy; <?php echo e(date('Y')); ?> <?php echo e(config('app.name')); ?>. All rights reserved.</span>
      <?php if(! str_ends_with((string) config('pricing.contact_email'), '@example.com')): ?>
        <a href="mailto:<?php echo e(config('pricing.contact_email')); ?>"><?php echo e(config('pricing.contact_email')); ?></a>
      <?php endif; ?>
    </div>
  </div>
</footer>

<script>
(function () {
  // Mobile navigation.
  var header = document.getElementById('siteHeader');
  var burger = document.getElementById('siteBurger');
  if (header && burger) {
    burger.addEventListener('click', function () {
      var open = header.classList.toggle('open');
      burger.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    header.addEventListener('click', function (e) {
      if (e.target.closest('.mobile-panel a')) {
        header.classList.remove('open');
        burger.setAttribute('aria-expanded', 'false');
      }
    });
    window.addEventListener('resize', function () {
      if (window.innerWidth > 900) header.classList.remove('open');
    });
  }
})();
</script>
<?php echo $__env->yieldPushContent('scripts'); ?>

</body>

</html>
<?php /**PATH C:\Users\likhi\Downloads\sarvamaiproject\resources\views/public/layout.blade.php ENDPATH**/ ?>