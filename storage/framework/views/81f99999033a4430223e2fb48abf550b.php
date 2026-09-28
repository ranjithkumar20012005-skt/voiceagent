
<div class="modal" id="newCallModal" role="dialog" aria-modal="true" aria-labelledby="newCallTitle" aria-hidden="true">
  <div class="modal-scrim" data-modal-close></div>

  <div class="modal-panel">
    <form data-call-form="<?php echo e(route('calls.store')); ?>" autocomplete="off" novalidate>
      <div class="modal-h">
        <div>
          <h2 id="newCallTitle">New call</h2>
          <p>The AI agent calls this number straight away.</p>
        </div>
        <button type="button" class="btn btn-ghost btn-icon btn-sm" data-modal-close aria-label="Close"><i class="icon-x"></i></button>
      </div>

      <div class="modal-b">
        <?php if (! ($agentConfigured ?? false)): ?>
          <div class="callout callout-warning mb-4">
            <i class="icon-circle-alert"></i><div>Calling is unavailable right now. Please contact your administrator.</div>
          </div>
        <?php endif; ?>

        <div data-call-alert hidden></div>
        <div class="callout callout-info mb-4" data-call-who hidden></div>

        <input type="hidden" name="customer_id">

        <div class="form-grid">
          <div class="field">
            <label class="label" for="ncName">Customer name</label>
            <input type="text" class="input" id="ncName" name="name" maxlength="120" placeholder="Anita Sharma">
          </div>

          <div class="field">
            <label class="label" for="ncPhone">Phone number <span class="req">*</span></label>
            <input type="tel" class="input" id="ncPhone" name="phone_number" required maxlength="24" placeholder="+91 98765 43210">
            <div class="hint">10-digit Indian numbers are accepted.</div>
          </div>

          <div class="field">
            <label class="label" for="ncPolicy">Policy number</label>
            <input type="text" class="input" id="ncPolicy" name="policy_number" maxlength="64" placeholder="POL-2291">
          </div>

          <div class="field">
            <label class="label" for="ncRegMobile">Registered mobile <span class="opt">(optional)</span></label>
            <input type="tel" class="input" id="ncRegMobile" name="registered_mobile" maxlength="24" placeholder="+91 98765 43210">
          </div>

          <?php if(($callAgents ?? collect())->isNotEmpty()): ?>
            <div class="field">
              <label class="label" for="ncAgent">Agent</label>
              <select class="select" id="ncAgent" name="agent_id">
                <?php $__currentLoopData = $callAgents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $callAgent): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                  <option value="<?php echo e($callAgent->id); ?>" <?php if($callAgent->is_default): echo 'selected'; endif; ?>>
                    <?php echo e($callAgent->name); ?><?php echo e($callAgent->is_default ? ' (default)' : ''); ?>

                  </option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
              </select>
            </div>
          <?php endif; ?>

          <div class="field <?php echo e(($callAgents ?? collect())->isEmpty() ? 'full' : ''); ?>">
            <label class="label" for="ncLanguage">Language</label>
            <select class="select" id="ncLanguage" name="language">
              <option value="">Agent default</option>
              <?php $__currentLoopData = $agentLanguages ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $language): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($language); ?>"><?php echo e($language); ?></option>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
          </div>
        </div>
      </div>

      <div class="modal-f">
        <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
        <button type="submit" class="btn btn-primary" <?php if(! ($agentConfigured ?? false)): echo 'disabled'; endif; ?>>
          <i class="icon-phone"></i> Start Call
        </button>
      </div>
    </form>
  </div>
</div>
<?php /**PATH C:\Users\likhi\Downloads\sarvamaiproject\resources\views/components/new-call-modal.blade.php ENDPATH**/ ?>