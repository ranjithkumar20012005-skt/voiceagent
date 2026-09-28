<?php $__env->startSection('title', 'New Call'); ?>

<?php use App\Support\LeadOutcome; ?>

<?php $__env->startSection('content'); ?>

<div class="ph">
  <div class="ph-main">
    <div class="eyebrow">Calling</div>
    <h1>New Call</h1>
    <p class="ph-sub">Start an AI call and review what happened.</p>
  </div>
</div>

<div class="tabs-line" data-tabs="calling" role="tablist">
  <button type="button" class="active" data-tab="instant" role="tab" aria-selected="true"><i class="icon-phone-outgoing"></i> Instant Call</button>
  <button type="button" data-tab="bulk" role="tab" aria-selected="false"><i class="icon-users"></i> Bulk Calling</button>
  <button type="button" data-tab="inbound" role="tab" aria-selected="false"><i class="icon-phone-incoming"></i> Inbound <span class="nav-soon">Soon</span></button>
</div>


<div data-tab-panel="instant" data-tab-scope="calling">
  <div class="split-left">
    <div class="card">
      <div class="card-h">
        <div class="card-h-title">
          <div class="card-icon"><i class="icon-phone-outgoing"></i></div>
          <div><h2>Instant AI Call</h2><p>The agent calls this customer straight away.</p></div>
        </div>
      </div>

      <form class="card-b" data-call-form="<?php echo e(route('calls.store')); ?>" autocomplete="off" novalidate>
        <?php if (! ($configured)): ?>
          <div class="callout callout-warning mb-4">
            <i class="icon-circle-alert"></i><div>Calling is unavailable right now. Please contact your administrator.</div>
          </div>
        <?php endif; ?>

        <div data-call-alert hidden></div>

        <div class="field">
          <label for="icName" class="label">Customer name</label>
          <input type="text" class="input" id="icName" name="name" maxlength="120" placeholder="Anita Sharma">
        </div>

        <div class="field">
          <label for="icPhone" class="label">Phone number <span class="req">*</span></label>
          <input type="tel" class="input" id="icPhone" name="phone_number" required maxlength="24" placeholder="+91 98765 43210">
          <div class="hint">10-digit Indian numbers are accepted. An existing customer with this number is reused.</div>
        </div>

        <div class="form-grid mb-4">
          <div class="field">
            <label for="icPolicy" class="label">Policy number</label>
            <input type="text" class="input" id="icPolicy" name="policy_number" maxlength="64" placeholder="POL-2291">
          </div>
          <div class="field">
            <label for="icRegMobile" class="label">Registered mobile <span class="opt">(optional)</span></label>
            <input type="tel" class="input" id="icRegMobile" name="registered_mobile" maxlength="24" placeholder="+91 98765 43210">
          </div>
        </div>

        <div class="form-grid">
          <?php if($callAgents->isNotEmpty()): ?>
            <div class="field">
              <label for="icAgent" class="label">Agent</label>
              <select class="select" id="icAgent" name="agent_id">
                <?php $__currentLoopData = $callAgents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $callAgent): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                  <option value="<?php echo e($callAgent->id); ?>" <?php if($callAgent->is_default): echo 'selected'; endif; ?>><?php echo e($callAgent->name); ?><?php echo e($callAgent->is_default ? ' (default)' : ''); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
              </select>
            </div>
          <?php endif; ?>
          <div class="field <?php echo e($callAgents->isEmpty() ? 'full' : ''); ?>">
            <label for="icLanguage" class="label">Language</label>
            <select class="select" id="icLanguage" name="language">
              <option value="">Agent default</option>
              <?php $__currentLoopData = $languages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $language): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($language); ?>"><?php echo e($language); ?></option>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
          </div>
        </div>

        <button type="submit" class="btn btn-primary btn-lg btn-block mt-5" <?php if(! $configured): echo 'disabled'; endif; ?>>
          <i class="icon-phone"></i> Start Call
        </button>
      </form>
    </div>

    
    <div class="card">
      <div class="card-h">
        <div><h2>Recent instant calls</h2><p>Calls placed one at a time. Results arrive when each call ends.</p></div>
        <a href="<?php echo e(route('calls.index')); ?>" class="btn btn-secondary btn-sm">All call logs</a>
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
          Fill in the form to place your first AI call.
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
              <tr><th>Customer</th><th>Agent</th><th>Status</th><th>Result</th><th class="num">Duration</th><th class="end"></th></tr>
            </thead>
            <tbody>
              <?php $__currentLoopData = $recent; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $attempt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                  <td>
                    <span class="cell-main"><?php echo e($attempt->customer?->name ?: 'Unknown'); ?></span>
                    <span class="cell-sub"><?php echo e($attempt->display_phone); ?> · <?php echo e($attempt->created_at->diffForHumans()); ?></span>
                  </td>
                  <td class="muted nowrap"><?php echo e($attempt->agent?->name ?? 'Workspace agent'); ?></td>
                  <td><span class="badge <?php echo e(LeadOutcome::connectivityBadge($attempt)); ?>"><?php echo e(LeadOutcome::connectivityLabel($attempt)); ?></span></td>
                  <td><span class="badge <?php echo e(LeadOutcome::badge($attempt->call_disposition)); ?>"><?php echo e(LeadOutcome::label($attempt->call_disposition)); ?></span></td>
                  <td class="num"><?php echo e($attempt->duration_for_humans); ?></td>
                  <td class="end"><a href="<?php echo e(route('calls.show', $attempt)); ?>" class="btn btn-secondary btn-xs">View</a></td>
                </tr>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>


<div data-tab-panel="bulk" data-tab-scope="calling" hidden>
  <div class="grid cols-3">
    <?php $__currentLoopData = [
      ['1', 'icon-upload', 'Import customers', 'Upload a .csv or .xlsx file and map your columns to ours.', route('imports.index'), 'Go to Imports'],
      ['2', 'icon-megaphone', 'Create a campaign', 'Pick an import batch or policies expiring soon, then dispatch.', route('campaigns.index'), 'Go to Campaigns'],
      ['3', 'icon-chart-column', 'Track results', 'Follow progress, connections and leads as the run completes.', route('analytics.index'), 'Open Analytics'],
    ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$n, $icon, $title, $body, $href, $cta]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <div class="card">
        <div class="card-b">
          <div class="row between mb-3">
            <div class="card-icon"><i class="<?php echo e($icon); ?>"></i></div>
            <span class="mono faint">0<?php echo e($n); ?></span>
          </div>
          <h3 style="font-size:15px"><?php echo e($title); ?></h3>
          <p class="text-sm muted mt-1"><?php echo e($body); ?></p>
        </div>
        <div class="card-f"><a href="<?php echo e($href); ?>" class="btn btn-secondary btn-sm btn-block"><?php echo e($cta); ?></a></div>
      </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
  </div>
</div>


<div data-tab-panel="inbound" data-tab-scope="calling" hidden>
  <div class="card">
    <?php if (isset($component)) { $__componentOriginal4f22a152e0729cd34293e65bd200d933 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4f22a152e0729cd34293e65bd200d933 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.empty','data' => ['icon' => 'icon-phone-incoming','title' => 'Inbound AI Calls — Coming Soon']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('empty'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'icon-phone-incoming','title' => 'Inbound AI Calls — Coming Soon']); ?>
      Let customers call your number and have the AI agent answer, qualify and route the conversation. This is not connected yet.
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

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\likhi\Downloads\sarvamaiproject\resources\views/calling/index.blade.php ENDPATH**/ ?>