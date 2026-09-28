<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['icon' => 'icon-inbox', 'title', 'tone' => null, 'compact' => false]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter((['icon' => 'icon-inbox', 'title', 'tone' => null, 'compact' => false]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>
<div <?php echo e($attributes->merge(['class' => 'empty' . ($compact ? ' compact' : '')])); ?>>
  <div class="empty-icon <?php echo e($tone); ?>"><i class="<?php echo e($icon); ?>"></i></div>
  <h3><?php echo e($title); ?></h3>
  <?php if(trim($slot) !== ''): ?>
    <p><?php echo e($slot); ?></p>
  <?php endif; ?>
  <?php echo e($action ?? ''); ?>

</div>
<?php /**PATH C:\Users\likhi\Downloads\sarvamaiproject\resources\views/components/empty.blade.php ENDPATH**/ ?>