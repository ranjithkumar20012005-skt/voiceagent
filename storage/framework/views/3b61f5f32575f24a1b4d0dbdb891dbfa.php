<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
  <title><?php echo $__env->yieldContent('title', 'Dashboard'); ?> · <?php echo e(config('app.name')); ?></title>

  <link rel="icon" type="image/svg+xml" href="<?php echo e(asset('assets/img/logo.svg')); ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Inter+Tight:wght@500;600;700&family=JetBrains+Mono:wght@400;500&display=swap">
  <link rel="stylesheet" href="<?php echo e(asset('assets/css/zentic-icons.css')); ?>">
  <link rel="stylesheet" href="<?php echo e(asset('assets/css/app.css')); ?>?v=<?php echo e(filemtime(public_path('assets/css/app.css'))); ?>">
  <?php echo $__env->yieldPushContent('head'); ?>
</head>

<body class="<?php echo $__env->yieldContent('body_class'); ?>">

  <div class="shell-scrim" data-nav-close></div>

  <?php echo $__env->make('components.sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

  <div class="shell-main">
    <?php echo $__env->make('components.header', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <main class="shell-content" id="main">
      <?php if(session('status')): ?>
        <div class="callout callout-success flash" role="status">
          <i class="icon-circle-check"></i><div><?php echo e(session('status')); ?></div>
        </div>
      <?php endif; ?>

      <?php if($errors->any()): ?>
        <div class="callout callout-danger flash" role="alert">
          <i class="icon-circle-alert"></i>
          <div>
            <strong>Please fix the following:</strong>
            <ul>
              <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <li><?php echo e($error); ?></li>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
          </div>
        </div>
      <?php endif; ?>

      <?php echo $__env->yieldContent('content'); ?>
    </main>

    <?php echo $__env->make('components.footer', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
  </div>

  <?php echo $__env->make('components.new-call-modal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

  <script src="<?php echo e(asset('assets/js/app.js')); ?>?v=<?php echo e(filemtime(public_path('assets/js/app.js'))); ?>"></script>
  <?php echo $__env->yieldPushContent('scripts'); ?>
</body>

</html>
<?php /**PATH C:\Users\likhi\Downloads\sarvamaiproject\resources\views/layouts/app.blade.php ENDPATH**/ ?>