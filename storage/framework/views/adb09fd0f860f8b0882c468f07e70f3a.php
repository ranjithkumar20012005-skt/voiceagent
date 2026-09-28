

<?php $__env->startSection('title', 'Providers'); ?>

<?php $__env->startSection('body_class', 'theme-warm'); ?>

<?php
  [$stateLabel, $stateTone, $stateText] = match ($state) {
      'connected'      => ['Connected', 'badge-success', 'The platform accepted the last call request' . ($lastAccepted ? ' ' . $lastAccepted->diffForHumans() : '') . '.'],
      'error'          => ['Error', 'badge-danger', 'The most recent call request failed' . ($lastFailedAt ? ' ' . $lastFailedAt->diffForHumans() : '') . '. Check the server log for the upstream response.'],
      'configured'     => ['Configured', 'badge-info', 'Credentials are present, but no call has been placed yet to confirm the connection.'],
      default          => ['Not configured', 'badge-warning', 'Required server settings are missing, so calls cannot be placed.'],
  };

  /*
   | What the voice platform provides for the configured agent. These are the
   | capabilities of the one integration this app actually has -- nothing here
   | is a separately connected provider.
   */
  $categories = [
      ['Speech-to-Text', 'icon-mic', 'Transcribes what the customer says, in the call language.', ['Indian languages', 'Real-time']],
      ['Text-to-Speech', 'icon-audio-lines', 'Speaks the agent\'s replies with the voice set on the agent.', ['Natural voices', 'Multilingual']],
      ['Language Model', 'icon-brain', 'Runs the conversation and fills the outcome variables.', ['Agent prompt', 'Structured outputs']],
      ['Telephony', 'icon-phone-call', 'Places the outbound call from the workspace number.', [$status['caller_number'] ? 'Line configured' : 'No line', 'Outbound']],
  ];
?>

<?php $__env->startSection('content'); ?>

<div class="ph">
  <div class="ph-main">
    <div class="eyebrow">Deploy</div>
    <h1>Providers</h1>
    <p class="ph-sub">What powers your voice agent. Speech, voice, language model and telephony are all delivered through one integration — Sarvam Voice Agents.</p>
  </div>
</div>

<div class="card mb-section">
  <div class="card-h">
    <div class="card-h-title">
      <div class="card-icon"><i class="icon-audio-waveform"></i></div>
      <div><h2>Sarvam Voice Agents</h2><p>Speech-to-text, text-to-speech, language model and telephony</p></div>
    </div>
    <span class="badge badge-lg <?php echo e($stateTone); ?>"><span class="dot <?php echo e($state === 'connected' ? 'live' : ''); ?>"></span><?php echo e($stateLabel); ?></span>
  </div>
  <div class="card-b">
    <p class="text-sm muted mb-4"><?php echo e($stateText); ?></p>
    <div class="grid cols-4">
      <div class="meter-cell"><span>API credentials</span><strong class="text-sm" style="font-family:var(--font)"><?php echo e(in_array('SARVAM_API_KEY', $missing, true) ? 'Missing' : 'Configured'); ?></strong></div>
      <div class="meter-cell"><span>Agent</span><strong class="text-sm" style="font-family:var(--font)"><?php echo e($status['agent_name'] ? 'Configured' : 'Missing'); ?></strong></div>
      <div class="meter-cell"><span>Result webhook</span><strong class="text-sm" style="font-family:var(--font)"><?php echo e($status['webhook_configured'] ? 'Configured' : 'Missing'); ?></strong></div>
      <div class="meter-cell"><span>Last result received</span><strong class="text-sm" style="font-family:var(--font)"><?php echo e($lastWebhook?->diffForHumans() ?? 'Never'); ?></strong></div>
    </div>

    <?php if($missing): ?>
      <div class="callout callout-warning mt-4">
        <i class="icon-key-round"></i>
        <div><strong>Missing server settings:</strong> <?php echo e(implode(', ', $missing)); ?>. An administrator sets these as environment variables — they are never entered or shown in this app.</div>
      </div>
    <?php endif; ?>
  </div>
</div>

<div class="section-label"><h2>Capabilities</h2></div>
<div class="grid cols-4 mb-section">
  <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$name, $icon, $body, $chips]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <div class="card">
      <div class="card-b">
        <div class="row between mb-3">
          <div class="card-icon teal"><i class="<?php echo e($icon); ?>"></i></div>
          <span class="badge <?php echo e($status['configured'] ? 'badge-success' : 'badge-warning'); ?>"><?php echo e($status['configured'] ? 'Active' : 'Not configured'); ?></span>
        </div>
        <h3 style="font-size:15px"><?php echo e($name); ?></h3>
        <p class="text-sm muted mt-1"><?php echo e($body); ?></p>
        <div class="chips mt-3"><?php $__currentLoopData = $chips; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $chip): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><span class="chip"><?php echo e($chip); ?></span><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></div>
      </div>
      <div class="card-f"><span class="text-xs muted">Provided by Sarvam</span></div>
    </div>
  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>

<div class="section-label"><h2>Not available</h2></div>
<div class="card">
  <?php if (isset($component)) { $__componentOriginal4f22a152e0729cd34293e65bd200d933 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4f22a152e0729cd34293e65bd200d933 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.empty','data' => ['icon' => 'icon-plug','title' => 'Bring-your-own providers are not supported','compact' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('empty'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'icon-plug','title' => 'Bring-your-own providers are not supported','compact' => true]); ?>
    This application calls through a single Sarvam workspace. Swapping in another speech, voice, model or telephony provider is not wired up, so none are listed as connected.
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

<p class="text-xs faint mt-3"><i class="icon-shield-check"></i> API keys, tokens and the webhook secret are held in the server environment only. They are never displayed, sent to the browser or stored in the database.</p>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\likhi\Downloads\sarvamaiproject\resources\views/insights/providers.blade.php ENDPATH**/ ?>