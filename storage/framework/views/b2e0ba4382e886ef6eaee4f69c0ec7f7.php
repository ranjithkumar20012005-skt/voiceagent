<?php $__env->startSection('title', 'Settings'); ?>

<?php $__env->startSection('content'); ?>

<div class="ph">
  <div class="ph-main">
    <div class="eyebrow">Settings</div>
    <h1>Settings</h1>
    <p class="ph-sub">Workspace details and the safe calling defaults operators can change. Credentials live only in the server environment and are never shown here.</p>
  </div>
</div>

<div class="tabs-line" data-tabs="settings" role="tablist">
  <button type="button" class="active" data-tab="workspace" role="tab"><i class="icon-building-2"></i> Workspace</button>
  <button type="button" data-tab="agent" role="tab"><i class="icon-bot"></i> Agent</button>
  <button type="button" data-tab="calling" role="tab"><i class="icon-phone-outgoing"></i> Calling</button>
  <button type="button" data-tab="providers" role="tab"><i class="icon-boxes"></i> Providers</button>
  <button type="button" data-tab="automation" role="tab"><i class="icon-zap"></i> Automation</button>
  <button type="button" data-tab="usage" role="tab"><i class="icon-gauge"></i> Usage</button>
</div>

<form method="POST" action="<?php echo e(route('settings.update')); ?>">
  <?php echo csrf_field(); ?>
  <?php echo method_field('PUT'); ?>

  
  <div data-tab-panel="workspace" data-tab-scope="settings">
    <div class="grid cols-2">
      <div class="card">
        <div class="card-h"><div class="card-h-title"><div class="card-icon"><i class="icon-building-2"></i></div><div><h2>Workspace</h2><p>Set by your administrator</p></div></div></div>
        <div class="card-b">
          <dl class="kv">
            <dt>Workspace name</dt><dd><?php echo e(config('app.name')); ?></dd>
            <dt>Timezone</dt><dd><?php echo e($status['timezone']); ?></dd>
            <dt>Default language</dt><dd><?php echo e($settings['default_language']); ?></dd>
            <dt>Calling</dt>
            <dd><span class="badge <?php echo e($status['configured'] ? 'badge-success' : 'badge-warning'); ?>"><span class="dot"></span><?php echo e($status['configured'] ? 'Available' : 'Not configured'); ?></span></dd>
          </dl>
        </div>
      </div>
      <div class="card">
        <div class="card-h"><div class="card-h-title"><div class="card-icon ink"><i class="icon-circle-user"></i></div><div><h2>Your account</h2><p>Signed in as</p></div></div></div>
        <div class="card-b">
          <dl class="kv">
            <dt>Name</dt><dd><?php echo e(auth()->user()->name); ?></dd>
            <dt>Email</dt><dd><?php echo e(auth()->user()->email); ?></dd>
            <dt>Member since</dt><dd><?php echo e(auth()->user()->created_at?->format('d M Y') ?? '—'); ?></dd>
          </dl>
        </div>
      </div>
    </div>
  </div>

  
  <div data-tab-panel="agent" data-tab-scope="settings" hidden>
    <div class="card">
      <div class="card-h">
        <div class="card-h-title"><div class="card-icon"><i class="icon-bot"></i></div><div><h2>Workspace agent</h2><p>The agent used when no other agent is selected</p></div></div>
        <a href="<?php echo e(route('agents.index')); ?>" class="btn btn-secondary btn-sm">Manage agents</a>
      </div>
      <div class="card-b">
        <dl class="kv">
          <dt>Status</dt>
          <dd><span class="badge <?php echo e($status['configured'] ? 'badge-success' : 'badge-warning'); ?>"><span class="dot"></span><?php echo e($status['configured'] ? 'Active' : 'Not configured'); ?></span></dd>
          <dt>Agent ID</dt><dd class="mono"><?php echo e($status['agent_name'] ?: 'Not configured'); ?></dd>
          <dt>Agent version</dt><dd><?php echo e($status['agent_version'] ?: 'Not configured'); ?></dd>
          <dt>Caller number</dt><dd><?php echo e($status['caller_number'] ?: 'Not configured'); ?></dd>
          <dt>Variables sent</dt><dd><?php echo e(implode(', ', array_keys(config('sarvam.agent_variables', [])))); ?></dd>
        </dl>
      </div>
    </div>
  </div>

  
  <div data-tab-panel="calling" data-tab-scope="settings" hidden>
    <div class="card">
      <div class="card-h"><div class="card-h-title"><div class="card-icon amber"><i class="icon-phone-outgoing"></i></div><div><h2>Calling defaults</h2><p>Applied to new calls, campaigns and automations</p></div></div></div>
      <div class="card-b">
        <div class="form-grid">
          <div class="field">
            <label class="label" for="default_language">Default language</label>
            <select id="default_language" name="default_language" class="select">
              <?php $__currentLoopData = $languages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $language): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($language); ?>" <?php if(old('default_language', $settings['default_language']) === $language): echo 'selected'; endif; ?>><?php echo e($language); ?></option>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
          </div>
          <div class="field">
            <label class="label" for="retry_limit">Retry limit</label>
            <input type="number" id="retry_limit" name="retry_limit" class="input" min="0" max="20" value="<?php echo e(old('retry_limit', $settings['retry_limit'])); ?>" required>
            <div class="hint">How often an unanswered call is retried.</div>
          </div>
          <div class="field">
            <label class="label" for="window_start">Calling window start</label>
            <input type="time" id="window_start" name="window_start" class="input" value="<?php echo e(old('window_start', $settings['window_start'])); ?>" required>
          </div>
          <div class="field">
            <label class="label" for="window_end">Calling window end</label>
            <input type="time" id="window_end" name="window_end" class="input" value="<?php echo e(old('window_end', $settings['window_end'])); ?>" required>
          </div>
          <div class="field">
            <label class="label" for="max_calls_per_run">Max calls per run</label>
            <input type="number" id="max_calls_per_run" name="max_calls_per_run" class="input" min="1" max="10000" value="<?php echo e(old('max_calls_per_run', $settings['max_calls_per_run'])); ?>" required>
          </div>
        </div>
      </div>
      <div class="card-f"><button type="submit" class="btn btn-primary btn-sm">Save settings</button></div>
    </div>
  </div>

  
  <div data-tab-panel="providers" data-tab-scope="settings" hidden>
    <div class="card">
      <div class="card-h">
        <div class="card-h-title"><div class="card-icon teal"><i class="icon-boxes"></i></div><div><h2>Provider settings</h2><p>Connection status only — never credentials</p></div></div>
        <a href="<?php echo e(route('providers.index')); ?>" class="btn btn-secondary btn-sm">Open Providers</a>
      </div>
      <div class="card-b">
        <dl class="kv">
          <dt>Voice platform</dt>
          <dd><span class="badge <?php echo e($status['configured'] ? 'badge-success' : 'badge-warning'); ?>"><?php echo e($status['configured'] ? 'Configured' : 'Not configured'); ?></span></dd>
          <dt>Result webhook</dt>
          <dd><span class="badge <?php echo e($status['webhook_configured'] ? 'badge-success' : 'badge-warning'); ?>"><?php echo e($status['webhook_configured'] ? 'Configured' : 'Not configured'); ?></span></dd>
        </dl>

        
        <?php if($missing): ?>
          <div class="callout callout-warning mt-4">
            <i class="icon-key-round"></i>
            <div><strong>Missing server configuration:</strong> <?php echo e(implode(', ', $missing)); ?><br>These are environment variables set on the server by an administrator.</div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  
  <div data-tab-panel="automation" data-tab-scope="settings" hidden>
    <div class="card">
      <div class="card-h">
        <div class="card-h-title"><div class="card-icon"><i class="icon-zap"></i></div><div><h2>Automation settings</h2><p>Defaults for scheduled calling</p></div></div>
        <a href="<?php echo e(route('automations.index')); ?>" class="btn btn-secondary btn-sm">Manage automations</a>
      </div>
      <div class="card-b">
        <div class="form-grid">
          <div class="field">
            <label class="label" for="automation_time">Default automation time</label>
            <input type="time" id="automation_time" name="automation_time" class="input" value="<?php echo e(old('automation_time', $settings['automation_time'])); ?>" required>
            <div class="hint">Each automation can override this with its own run time.</div>
          </div>
        </div>
      </div>
      <div class="card-f"><button type="submit" class="btn btn-primary btn-sm">Save settings</button></div>
    </div>
  </div>

  
  <div data-tab-panel="usage" data-tab-scope="settings" hidden>
    <div class="card">
      <?php if (isset($component)) { $__componentOriginal4f22a152e0729cd34293e65bd200d933 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4f22a152e0729cd34293e65bd200d933 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.empty','data' => ['icon' => 'icon-gauge','tone' => 'green','title' => 'Usage has its own page']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('empty'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'icon-gauge','tone' => 'green','title' => 'Usage has its own page']); ?>
        Calls and conversation minutes for this month, by agent and by day.
         <?php $__env->slot('action', null, []); ?> <a href="<?php echo e(route('usage.index')); ?>" class="btn btn-primary btn-sm">Open Usage</a> <?php $__env->endSlot(); ?>
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
  </div>
</form>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<?php if($errors->any()): ?>
<script>
  // Land on the tab holding the fields that failed validation.
  (function () { var b = document.querySelector('[data-tab="calling"]'); if (b && !location.hash) b.click(); })();
</script>
<?php endif; ?>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\likhi\Downloads\sarvamaiproject\resources\views/settings/index.blade.php ENDPATH**/ ?>