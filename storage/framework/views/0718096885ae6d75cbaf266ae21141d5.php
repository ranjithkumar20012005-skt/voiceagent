

<?php $__env->startSection('title', 'AI Voice Agents That Talk, Qualify and Follow Up'); ?>

<?php
  $check = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 5 5L20 7"/></svg>';
  $icon = fn (string $paths) => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">' . $paths . '</svg>';
?>

<?php $__env->startSection('content'); ?>


<section class="hero" id="home">
  <div class="hero-bg" aria-hidden="true">
    <div class="hero-grid"></div>
    <div class="hero-glow-green"></div>
    <div class="hero-glow-teal"></div>
    <div class="hero-fade"></div>
  </div>

  <div class="container hero-inner">
    <div>
      <span class="pill"><i></i> AI calling for renewals &amp; follow-ups</span>

      <h1>AI Voice Agents That Talk, <span class="accent">Qualify</span> and Follow Up</h1>

      <p class="hero-lead">
        Your AI agent calls every customer on your list, holds a natural conversation in their language
        and records the outcome — so your team only spends time on the people who said yes.
      </p>

      <div class="hero-cta">
        <a href="<?php echo e(route('start')); ?>" class="btn btn-primary btn-lg">
          Start Free
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        </a>
        <a href="#how-it-works" class="btn btn-secondary btn-lg">See How It Works</a>
      </div>

      <div class="hero-checks">
        <span><?php echo $check; ?> Hindi, English &amp; 11 more languages</span>
        <span><?php echo $check; ?> Outcome and transcript for every call</span>
        <span><?php echo $check; ?> No telephony to set up</span>
      </div>
    </div>

    
    <div class="preview" aria-hidden="true">
      <div class="preview-glow"></div>
      <span class="preview-tag">EXAMPLE CALL</span>

      <div class="preview-card">
        <div class="preview-head">
          <div class="preview-avatar">
            <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 10v3M6 6v11M10 3v18M14 8v7M18 5v13M22 10v3"/></svg>
          </div>
          <div class="preview-who">
            <strong>Renewal agent → Anita S.</strong>
            <span>Outbound · Hindi</span>
          </div>
          <span class="preview-timer" id="pvTimer">00:00</span>
        </div>

        <div class="preview-body">
          <ol class="preview-steps" id="pvSteps">
            <li><b>1</b><span>Customer queued<small>From your list</small></span></li>
            <li><b>2</b><span>Agent calling<small>Workspace line</small></span></li>
            <li><b>3</b><span>Connected<small>Conversation live</small></span></li>
            <li><b>4</b><span>Outcome captured<small>Interested</small></span></li>
            <li><b>5</b><span>Callback booked<small>Tomorrow, 11 AM</small></span></li>
          </ol>

          <div class="preview-talk">
            <div class="preview-label">Transcript</div>
            <div class="bubble agent" data-step="3">Namaste Anita ji, your policy renews on 15 October. Shall I share the renewal details?</div>
            <div class="bubble customer" data-step="3">Yes, but I'm busy right now.</div>
            <div class="bubble agent" data-step="4">No problem. When should I call you back?</div>
            <div class="bubble customer" data-step="5">Tomorrow at 11 is fine.</div>
          </div>
        </div>

        <div class="preview-foot">
          <span class="outcome hot" data-step="4">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="m17 11 2 2 4-4"/></svg>
            Hot lead · Interested
          </span>
          <span class="outcome cb" data-step="5">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
            Callback scheduled
          </span>
        </div>
      </div>

    </div>
  </div>
</section>


<section class="section muted" id="how-it-works">
  <div class="container">
    <div class="section-head reveal">
      <p class="kicker">How it works</p>
      <h2>From a contact list to qualified leads in four steps</h2>
      <p>No scripts to code and no telephony to wire up. Add people, start the calls, read the results.</p>
    </div>

    <div class="cards cards-4">
      <?php $__currentLoopData = [
        ['Add customers', 'Upload a spreadsheet or enter a number. Map your own column headings — no reformatting.',
         '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m17 8-5-5-5 5"/><path d="M12 3v12"/>'],
        ['The agent calls', 'Call one customer now, or run a campaign through the whole list at a safe pace.',
         '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.79 19.79 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.9.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/>'],
        ['Outcomes are captured', 'Interested, callback, not interested, wrong person — reported by the agent, not guessed.',
         '<path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>'],
        ['Your team follows up', 'Hot leads, due callbacks and full transcripts are waiting in one dashboard.',
         '<path d="M3 3v18h18"/><path d="m7 15 4-4 3 3 5-6"/>'],
      ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => [$title, $body, $paths]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="card reveal">
          <div class="card-top">
            <div class="icon-tile"><?php echo $icon($paths); ?></div>
            <span class="step-no">0<?php echo e($i + 1); ?></span>
          </div>
          <h3><?php echo e($title); ?></h3>
          <p><?php echo e($body); ?></p>
        </div>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
  </div>
</section>


<section class="section" id="why-us">
  <div class="container">
    <div class="section-head reveal">
      <p class="kicker">Why choose us</p>
      <h2>Built for teams that need conversations, not configuration</h2>
      <p>Everything a calling team actually uses — and nothing it does not.</p>
    </div>

    <div class="cards cards-3">
      <?php $__currentLoopData = [
        ['Natural conversations', 'The agent listens, answers and adapts like a trained caller — in the customer\'s own language.',
         '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>', ''],
        ['Bulk calling, safely paced', 'Run campaigns through thousands of contacts with calling windows, rate limits and retries.',
         '<path d="M3 11l18-5v12L3 14v-3z"/><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/>', 'teal'],
        ['Honest lead qualification', 'Leads come from the agent\'s structured outcome — never from guessing at transcript text.',
         '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="m17 11 2 2 4-4"/>', ''],
        ['Callbacks that don\'t slip', 'When a customer asks for a later call, it lands in a callbacks queue with the time they chose.',
         '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>', 'teal'],
        ['Automations', 'Schedule daily rules — e.g. call everyone whose policy expires this month — and let them run.',
         '<path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"/>', ''],
        ['Every call on record', 'Duration, outcome and a turn-by-turn transcript for each call, searchable and exportable.',
         '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/>', 'teal'],
      ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$title, $body, $paths, $tone]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="card reveal">
          <div class="icon-tile <?php echo e($tone); ?>"><?php echo $icon($paths); ?></div>
          <h3><?php echo e($title); ?></h3>
          <p><?php echo e($body); ?></p>
        </div>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
  </div>
</section>


<section class="section muted">
  <div class="container showcase">
    <div class="reveal">
      <p class="kicker">One dashboard</p>
      <h2 style="font-size:clamp(28px,3vw,38px);line-height:1.15">See who is interested the moment the call ends</h2>
      <p style="font-size:17px;color:var(--ink-500);margin-top:14px">Results flow in automatically. Your team opens the dashboard and knows exactly who to call next.</p>
      <ul class="check-list">
        <li><?php echo $check; ?><span><strong>Connectivity and outcome kept separate</strong> — "no answer" never gets mistaken for "not interested".</span></li>
        <li><?php echo $check; ?><span><strong>Do-not-call is honoured everywhere</strong> — single calls, campaigns and automations.</span></li>
        <li><?php echo $check; ?><span><strong>Your data stays yours</strong> — export customers and results to CSV at any time.</span></li>
      </ul>
    </div>

    <div class="reveal" aria-hidden="true">
      <div class="mock-table">
        <div class="mock-bar"><i></i><i></i><i></i><span>Leads — example</span></div>
        <div class="mock-kpis">
          <div><span>Calls today</span><strong>128</strong></div>
          <div><span>Connected</span><strong>91</strong></div>
          <div><span>Hot leads</span><strong>24</strong></div>
        </div>
        <div class="mock-row"><span class="who">Ramesh R.<small>+91 98··· ··210</small></span><span class="tag green">Interested</span><span class="meta">3m 18s</span></div>
        <div class="mock-row"><span class="who">Priya S.<small>+91 97··· ··044</small></span><span class="tag teal">Callback · 11:00</span><span class="meta">2m 04s</span></div>
        <div class="mock-row"><span class="who">Arun M.<small>+91 99··· ··781</small></span><span class="tag gray">No answer</span><span class="meta">—</span></div>
        <div class="mock-row"><span class="who">Fatima K.<small>+91 90··· ··352</small></span><span class="tag red">Not interested</span><span class="meta">1m 12s</span></div>
      </div>
      <p class="mock-note">Illustrative data</p>
    </div>
  </div>
</section>


<section class="section" id="pricing">
  <div class="container">
    <div class="section-head center reveal">
      <p class="kicker">Pricing</p>
      <h2>Plans that scale with your call volume</h2>
      <p>Pick the plan that fits your team. Talk to us for volume and custom requirements.</p>
    </div>

    <div class="cards cards-3">
      <?php $__currentLoopData = $plans; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $plan): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="card price-card reveal <?php echo e(($plan['featured'] ?? false) ? 'featured' : ''); ?>">
          <?php if($plan['featured'] ?? false): ?>
            <span class="price-badge">Most popular</span>
          <?php endif; ?>

          <div class="price-name"><?php echo e($plan['name']); ?></div>
          <div class="price-tag"><?php echo e($plan['tagline']); ?></div>

          
          <?php if(filled($plan['price'] ?? null)): ?>
            <div class="price-amount"><?php echo e($plan['price']); ?></div>
            <div class="price-period"><?php echo e($plan['period']); ?></div>
          <?php else: ?>
            <div class="price-amount quote">Contact Sales</div>
            <div class="price-period">Custom quote for your volume</div>
          <?php endif; ?>

          <ul class="price-features">
            <?php $__currentLoopData = $plan['features']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $feature): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <li><?php echo $check; ?><span><?php echo e($feature); ?></span></li>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </ul>

          <?php if(filled($plan['price'] ?? null)): ?>
            <a href="<?php echo e(route('start')); ?>" class="btn btn-block <?php echo e(($plan['featured'] ?? false) ? 'btn-primary' : 'btn-secondary'); ?>">Start Free</a>
          <?php else: ?>
            <a href="mailto:<?php echo e($contactEmail); ?>?subject=<?php echo e(rawurlencode($plan['name'] . ' plan enquiry')); ?>"
               class="btn btn-block <?php echo e(($plan['featured'] ?? false) ? 'btn-primary' : 'btn-secondary'); ?>">Contact Sales</a>
          <?php endif; ?>
        </div>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    <?php if(filled($pricingNote)): ?>
      <p class="price-note"><?php echo e($pricingNote); ?></p>
    <?php endif; ?>
  </div>
</section>


<section class="section muted" id="faq">
  <div class="container">
    <div class="section-head center reveal">
      <p class="kicker">FAQs</p>
      <h2>Questions, answered simply</h2>
    </div>

    <div class="faq">
      <?php $__currentLoopData = [
        ['What is an AI voice agent?',
         'An automated caller that speaks with your customers over the phone, understands their replies and records what they said and what should happen next.'],
        ['Can it call a whole list of customers?',
         'Yes. Upload a CSV or Excel file, match your columns to ours, and start a campaign. The agent works through the list at a controlled pace.'],
        ['Can it speak multiple languages?',
         'Yes. You can pick the language per call or per customer, including English, Hindi, Tamil, Telugu, Bengali, Marathi and other major Indian languages.'],
        ['Can I see call transcripts?',
         'Yes. Every completed call has a turn-by-turn transcript along with its duration and outcome.'],
        ['How are interested leads identified?',
         'The agent reports a structured outcome at the end of each call. Interested customers are grouped as hot leads, and callback requests go to a callbacks queue.'],
        ['Can customers call the AI agent?',
         'Inbound answering is on our roadmap. Outbound calling — single calls, campaigns and scheduled automations — is available today.'],
        ['Will it call people who opted out?',
         'No. Customers marked Do Not Call are skipped by single calls, campaigns and automations alike.'],
        ['Can I integrate this with my CRM?',
         'You can export customers and results to CSV at any time. Direct CRM integrations are available on request — contact us about your setup.'],
      ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => [$q, $a]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <details <?php if($i === 0): ?> open <?php endif; ?>>
          <summary><?php echo e($q); ?></summary>
          <p><?php echo e($a); ?></p>
        </details>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
  </div>
</section>


<section class="section">
  <div class="container">
    <div class="cta-band reveal">
      <h2>Ready to let your AI agent start calling?</h2>
      <p>Sign in, add a customer and place your first AI call in under a minute.</p>
      <div class="actions">
        <a href="<?php echo e(route('start')); ?>" class="btn btn-white btn-lg">Start Free</a>
        <a href="<?php echo e(route('login')); ?>" class="btn btn-outline-light btn-lg">Sign In</a>
      </div>
    </div>
  </div>
</section>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
(function () {
  // Walk the example call through its steps. Purely presentational.
  var steps = document.querySelectorAll('#pvSteps li');
  var timed = document.querySelectorAll('.preview [data-step]');
  var timer = document.getElementById('pvTimer');
  if (!steps.length) return;

  var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var tick = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 5 5L20 7"/></svg>';
  var current = 0, seconds = 0;

  function render() {
    steps.forEach(function (li, i) {
      var n = i + 1;
      li.classList.toggle('done', n < current);
      li.classList.toggle('active', n === current);
      li.querySelector('b').innerHTML = n < current ? tick : String(n);
    });
    timed.forEach(function (el) { el.classList.toggle('show', Number(el.getAttribute('data-step')) <= current); });
  }

  function advance() {
    current = current >= steps.length + 1 ? 1 : current + 1;
    if (current === 1) seconds = 0;
    render();
  }

  if (reduce) { current = steps.length + 1; render(); timer.textContent = '01:42'; return; }

  current = 3; seconds = 4; render();
  setInterval(advance, 1800);
  setInterval(function () {
    if (current >= 3 && current <= steps.length) seconds++;
    timer.textContent = String(Math.floor(seconds / 60)).padStart(2, '0') + ':' + String(seconds % 60).padStart(2, '0');
  }, 1000);
})();
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('public.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\sarvamaiprojectfinal\resources\views/public/home.blade.php ENDPATH**/ ?>