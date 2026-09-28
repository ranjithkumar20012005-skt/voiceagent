
<?php $statuses = (array) ($a->customer_statuses ?? ['pending']); ?>

<div class="form-section-title"><i class="icon-clock"></i> Trigger</div>
<div class="form-grid cols-3">
  <div class="field">
    <label class="label" for="<?php echo e($p); ?>name">Name <span class="req">*</span></label>
    <input type="text" id="<?php echo e($p); ?>name" name="name" class="input" value="<?php echo e($a->name); ?>" maxlength="120" required>
  </div>
  <div class="field">
    <label class="label" for="<?php echo e($p); ?>run_at">Runs daily at</label>
    <input type="hidden" name="frequency" value="daily">
    <input type="time" id="<?php echo e($p); ?>run_at" name="run_at" class="input" value="<?php echo e($a->run_at ?? '08:00'); ?>" required>
  </div>
  <div class="field">
    <label class="label" for="<?php echo e($p); ?>timezone">Timezone</label>
    <select id="<?php echo e($p); ?>timezone" name="timezone" class="select">
      <?php $__currentLoopData = ['Asia/Kolkata', 'UTC', 'Asia/Dubai', 'Europe/London', 'America/New_York']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tz): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <option value="<?php echo e($tz); ?>" <?php if(($a->timezone ?? 'Asia/Kolkata') === $tz): echo 'selected'; endif; ?>><?php echo e($tz); ?></option>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </select>
  </div>
</div>

<div class="form-section-title"><i class="icon-users"></i> Who gets called</div>
<div class="form-grid cols-3">
  <div class="field">
    <label class="label" for="<?php echo e($p); ?>expiry">Policy due within (days)</label>
    <input type="number" id="<?php echo e($p); ?>expiry" name="expiry_within_days" class="input" min="0" max="365" value="<?php echo e($a->expiry_within_days ?? 30); ?>" required>
    <div class="hint">0 = ignore expiry date.</div>
  </div>
  <div class="field">
    <label class="label" for="<?php echo e($p); ?>statuses">Customer status</label>
    <select id="<?php echo e($p); ?>statuses" name="customer_statuses[]" class="select" multiple size="3">
      <?php $__currentLoopData = ['pending', 'queued', 'in_progress', 'contacted', 'closed']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <option value="<?php echo e($status); ?>" <?php if(in_array($status, $statuses, true)): echo 'selected'; endif; ?>><?php echo e(ucfirst(str_replace('_', ' ', $status))); ?></option>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </select>
  </div>
  <div class="field">
    <label class="label" for="<?php echo e($p); ?>gap">Min. days between calls</label>
    <input type="number" id="<?php echo e($p); ?>gap" name="min_days_between_calls" class="input" min="0" max="90" value="<?php echo e($a->min_days_between_calls ?? 1); ?>" required>
  </div>
  <div class="field full">
    <label class="check"><input type="checkbox" name="skip_do_not_call" value="1" <?php if($a->skip_do_not_call ?? true): echo 'checked'; endif; ?>> <span>Skip Do Not Call customers <span class="muted">(always enforced)</span></span></label>
    <label class="check"><input type="checkbox" name="skip_already_renewed" value="1" <?php if($a->skip_already_renewed ?? true): echo 'checked'; endif; ?>> <span>Skip customers who already renewed</span></label>
    <label class="check"><input type="checkbox" name="skip_active_callback" value="1" <?php if($a->skip_active_callback ?? true): echo 'checked'; endif; ?>> <span>Skip customers with a callback still scheduled</span></label>
  </div>
</div>

<div class="form-section-title"><i class="icon-shield-check"></i> Calling limits</div>
<div class="form-grid cols-4">
  <div class="field">
    <label class="label" for="<?php echo e($p); ?>max">Max calls per run</label>
    <input type="number" id="<?php echo e($p); ?>max" name="max_calls_per_run" class="input" min="1" max="10000" value="<?php echo e($a->max_calls_per_run ?? 200); ?>" required>
  </div>
  <div class="field">
    <label class="label" for="<?php echo e($p); ?>retries">Max retries</label>
    <input type="number" id="<?php echo e($p); ?>retries" name="max_retries" class="input" min="0" max="20" value="<?php echo e($a->max_retries ?? 2); ?>" required>
  </div>
  <div class="field">
    <label class="label" for="<?php echo e($p); ?>ws">Window start</label>
    <input type="time" id="<?php echo e($p); ?>ws" name="window_start" class="input" value="<?php echo e($a->window_start ?? '09:00'); ?>" required>
  </div>
  <div class="field">
    <label class="label" for="<?php echo e($p); ?>we">Window end</label>
    <input type="time" id="<?php echo e($p); ?>we" name="window_end" class="input" value="<?php echo e($a->window_end ?? '19:00'); ?>" required>
  </div>
  <div class="field">
    <label class="label" for="<?php echo e($p); ?>rate">Calls per second</label>
    <input type="number" id="<?php echo e($p); ?>rate" name="attempts_per_second" class="input" step="0.1" min="0.1" max="500" value="<?php echo e($a->attempts_per_second ?? 1.0); ?>" required>
  </div>
</div>

<div class="mt-5">
  <label class="switch">
    <input type="checkbox" name="enabled" value="1" <?php if($a->enabled): echo 'checked'; endif; ?>>
    <span class="switch-track"></span>
    Enabled — runs automatically every day
  </label>
</div>
<?php /**PATH C:\Users\likhi\Downloads\sarvamaiproject\resources\views/automations/_form.blade.php ENDPATH**/ ?>