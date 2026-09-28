<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['label', 'value', 'icon' => null, 'tone' => null, 'hint' => null, 'valueTone' => null, 'key' => null]));

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

foreach (array_filter((['label', 'value', 'icon' => null, 'tone' => null, 'hint' => null, 'valueTone' => null, 'key' => null]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>
<div <?php echo e($attributes->merge(['class' => 'card stat'])); ?>>
  <div>
    <div class="stat-label"><?php echo e($label); ?></div>
    <div class="stat-value <?php echo e($valueTone); ?>" <?php if($key): ?> data-kpi="<?php echo e($key); ?>" <?php endif; ?>><?php echo e($value); ?></div>
    <?php if($hint): ?>
      <div class="stat-hint"><?php echo e($hint); ?></div>
    <?php endif; ?>
  </div>
  <?php if($icon): ?>
    <div class="stat-icon <?php echo e($tone); ?>"><i class="<?php echo e($icon); ?>"></i></div>
  <?php endif; ?>
</div>
<?php /**PATH C:\Users\likhi\Downloads\sarvamaiproject\resources\views/components/stat.blade.php ENDPATH**/ ?>