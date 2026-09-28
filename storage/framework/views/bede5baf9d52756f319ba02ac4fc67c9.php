

<?php
  use App\Support\LeadOutcome;
  $editing = $agent->exists;
?>

<?php $__env->startSection('title', $editing ? $agent->name : 'Create Agent'); ?>

<?php $__env->startSection('content'); ?>

<div class="ph">
  <div class="ph-main">
    <a href="<?php echo e(route('agents.index')); ?>" class="ph-back"><i class="icon-arrow-left"></i> My Agents</a>
    <h1><?php echo e($editing ? $agent->name : 'Create Agent'); ?></h1>
    <div class="ph-meta">
      <?php if($editing): ?>
        <span class="badge <?php echo e($agent->isActive() ? 'badge-success' : ''); ?>"><span class="dot"></span><?php echo e($agent->isActive() ? 'Active' : 'Inactive'); ?></span>
        <?php if($agent->is_default): ?><span class="badge badge-brand">Default</span><?php endif; ?>
        <span><?php echo e(number_format($agent->call_attempts_count)); ?> calls placed</span>
      <?php else: ?>
        <span>Connect an agent you have already built in Sarvam so your team can call with it.</span>
      <?php endif; ?>
    </div>
  </div>
  <?php if($editing && $agent->isActive()): ?>
    <div class="ph-actions">
      <button type="button" class="btn btn-secondary btn-sm" data-new-call data-agent-id="<?php echo e($agent->id); ?>"><i class="icon-phone"></i> Test call</button>
    </div>
  <?php endif; ?>
</div>

<?php if (! ($editing)): ?>
  <div class="steps" aria-hidden="true">
    <span class="step-chip current"><b>1</b>Identity</span>
    <span class="step-chip"><b>2</b>Conversation</span>
    <span class="step-chip"><b>3</b>Calling</span>
  </div>
<?php endif; ?>

<div class="split">
  <form method="POST" action="<?php echo e($editing ? route('agents.update', $agent) : route('agents.store')); ?>" class="stack">
    <?php echo csrf_field(); ?>
    <?php if($editing): ?> <?php echo method_field('PUT'); ?> <?php endif; ?>

    
    <div class="card">
      <div class="card-h">
        <div class="card-h-title">
          <div class="card-icon"><i class="icon-bot"></i></div>
          <div><h2>1. Identity</h2><p>How your team recognises this agent.</p></div>
        </div>
      </div>
      <div class="card-b">
        <div class="form-grid">
          <div class="field full">
            <label class="label" for="name">Agent name <span class="req">*</span></label>
            <input class="input" id="name" name="name" maxlength="80" required value="<?php echo e(old('name', $agent->name)); ?>" placeholder="Renewal reminder — Hindi">
          </div>
          <div class="field full">
            <label class="label" for="description">Description</label>
            <input class="input" id="description" name="description" maxlength="255" value="<?php echo e(old('description', $agent->description)); ?>" placeholder="Calls customers whose policy expires in the next 30 days.">
          </div>
          <div class="field">
            <label class="label" for="status">Status</label>
            <select class="select" id="status" name="status">
              <option value="active" <?php if(old('status', $agent->status) === 'active'): echo 'selected'; endif; ?>>Active — can place calls</option>
              <option value="inactive" <?php if(old('status', $agent->status) === 'inactive'): echo 'selected'; endif; ?>>Inactive — hidden from New Call</option>
            </select>
          </div>
          <div class="field" style="align-self:end">
            <label class="switch">
              <input type="checkbox" name="is_default" value="1" <?php if(old('is_default', $agent->is_default)): echo 'checked'; endif; ?>>
              <span class="switch-track"></span>
              Default agent for new calls
            </label>
          </div>
        </div>
      </div>
    </div>

    
    <div class="card">
      <div class="card-h">
        <div class="card-h-title">
          <div class="card-icon teal"><i class="icon-message-square"></i></div>
          <div><h2>2. Conversation</h2><p>Reference copy of the agent's script for your team.</p></div>
        </div>
      </div>
      <div class="card-b">
        <div class="callout callout-info mb-4">
          <i class="icon-info"></i>
          <div>The live prompt, first message and voice are edited in the Sarvam agent builder. What you enter here is stored in this application only, so your team can see what the agent says — it is not sent to the agent.</div>
        </div>
        <div class="field">
          <label class="label" for="first_message">First message</label>
          <textarea class="textarea" id="first_message" name="first_message" maxlength="2000" rows="3" placeholder="Namaste, am I speaking with {user_name}?"><?php echo e(old('first_message', $agent->first_message)); ?></textarea>
        </div>
        <div class="field">
          <label class="label" for="instructions">Instructions</label>
          <textarea class="textarea" id="instructions" name="instructions" maxlength="20000" rows="8" placeholder="Role, tone, what to confirm, when to end the call…"><?php echo e(old('instructions', $agent->instructions)); ?></textarea>
        </div>
      </div>
    </div>

    
    <div class="card">
      <div class="card-h">
        <div class="card-h-title">
          <div class="card-icon amber"><i class="icon-phone-outgoing"></i></div>
          <div><h2>3. Calling</h2><p>Which Sarvam agent answers, and in which language.</p></div>
        </div>
      </div>
      <div class="card-b">
        <div class="form-grid">
          <div class="field">
            <label class="label" for="platform_app_id">Sarvam agent ID <span class="opt">(optional)</span></label>
            <input class="input mono" id="platform_app_id" name="platform_app_id" maxlength="191" value="<?php echo e(old('platform_app_id', $agent->platform_app_id)); ?>" placeholder="<?php echo e($workspace['agent_name'] ?: 'e.g. Renewal-ins-2f5d65f0-ab95'); ?>">
            <div class="hint">Leave blank to use the workspace agent.</div>
          </div>
          <div class="field">
            <label class="label" for="platform_app_version">Agent version</label>
            <input class="input" type="number" min="1" id="platform_app_version" name="platform_app_version" value="<?php echo e(old('platform_app_version', $agent->platform_app_version)); ?>" placeholder="<?php echo e($workspace['agent_version'] ?: '1'); ?>">
            <div class="hint">Required together with the agent ID.</div>
          </div>
          <div class="field">
            <label class="label" for="default_language">Default language</label>
            <select class="select" id="default_language" name="default_language">
              <option value="">Workspace default (<?php echo e(config('sarvam.default_language')); ?>)</option>
              <?php $__currentLoopData = $languages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $language): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($language); ?>" <?php if(old('default_language', $agent->default_language) === $language): echo 'selected'; endif; ?>><?php echo e($language); ?></option>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
          </div>
          <div class="field">
            <label class="label">Phone line</label>
            <input class="input" value="<?php echo e($workspace['caller_number'] ?: 'Not configured'); ?>" disabled>
            <div class="hint">Shared workspace line — see <a href="<?php echo e(route('phone-numbers.index')); ?>">Phone Numbers</a>.</div>
          </div>
        </div>
      </div>
      <div class="card-f between">
        <span class="text-xs muted">The agent must declare the variables this app sends: <?php echo e(implode(', ', array_keys(config('sarvam.agent_variables', [])))); ?>.</span>
        <div class="row">
          <a href="<?php echo e(route('agents.index')); ?>" class="btn btn-secondary btn-sm">Cancel</a>
          <button type="submit" class="btn btn-primary btn-sm"><?php echo e($editing ? 'Save changes' : 'Create agent'); ?></button>
        </div>
      </div>
    </div>
  </form>

  
  <div class="stack">
    <div class="card">
      <div class="card-h"><div><h3>How agents work here</h3></div></div>
      <div class="card-b">
        <ul class="feature-list">
          <li><i class="icon-check"></i><span>Build and publish the agent in Sarvam first.</span></li>
          <li><i class="icon-check"></i><span>Register it here with its agent ID and version.</span></li>
          <li><i class="icon-check"></i><span>Pick it in <strong>New Call</strong> — the call and its results are recorded against this agent.</span></li>
          <li><i class="icon-check"></i><span>Campaigns and automations use the workspace agent.</span></li>
        </ul>
      </div>
    </div>

    <?php if($editing): ?>
      <div class="card">
        <div class="card-h"><div><h3>Recent calls</h3><p>Placed with this agent</p></div></div>
        <?php $__empty_1 = true; $__currentLoopData = $recent; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $call): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
          <div class="list-row">
            <div class="list-main">
              <div class="list-title"><?php echo e($call->customer?->name ?: $call->display_phone); ?></div>
              <div class="list-meta"><?php echo e($call->created_at->diffForHumans()); ?> · <?php echo e($call->duration_for_humans); ?></div>
            </div>
            <a href="<?php echo e(route('calls.show', $call)); ?>" class="badge <?php echo e(LeadOutcome::badge($call->call_disposition)); ?>"><?php echo e(LeadOutcome::label($call->call_disposition)); ?></a>
          </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
          <?php if (isset($component)) { $__componentOriginal4f22a152e0729cd34293e65bd200d933 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4f22a152e0729cd34293e65bd200d933 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.empty','data' => ['icon' => 'icon-phone','title' => 'No calls yet','compact' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('empty'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'icon-phone','title' => 'No calls yet','compact' => true]); ?>Use Test call to place the first one. <?php echo $__env->renderComponent(); ?>
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

      <form method="POST" action="<?php echo e(route('agents.destroy', $agent)); ?>"
            data-confirm="<?php echo e($agent->call_attempts_count ? 'This agent has call history, so it will be deactivated rather than deleted. Continue?' : 'Delete this agent?'); ?>">
        <?php echo csrf_field(); ?>
        <?php echo method_field('DELETE'); ?>
        <button type="submit" class="btn btn-danger btn-sm btn-block"><i class="icon-trash-2"></i> <?php echo e($agent->call_attempts_count ? 'Deactivate agent' : 'Delete agent'); ?></button>
      </form>
    <?php endif; ?>
  </div>
</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\likhi\Downloads\sarvamaiproject\resources\views/agents/form.blade.php ENDPATH**/ ?>