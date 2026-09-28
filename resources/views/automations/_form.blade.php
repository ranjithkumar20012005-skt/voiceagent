{{-- Shared fields for creating and editing an automation. $a is the model (may be unsaved); $p prefixes ids. --}}
@php $statuses = (array) ($a->customer_statuses ?? ['pending']); @endphp

<div class="form-section-title"><i class="icon-clock"></i> Trigger</div>
<div class="form-grid cols-3">
  <div class="field">
    <label class="label" for="{{ $p }}name">Name <span class="req">*</span></label>
    <input type="text" id="{{ $p }}name" name="name" class="input" value="{{ $a->name }}" maxlength="120" required>
  </div>
  <div class="field">
    <label class="label" for="{{ $p }}run_at">Runs daily at</label>
    <input type="hidden" name="frequency" value="daily">
    <input type="time" id="{{ $p }}run_at" name="run_at" class="input" value="{{ $a->run_at ?? '08:00' }}" required>
  </div>
  <div class="field">
    <label class="label" for="{{ $p }}timezone">Timezone</label>
    <select id="{{ $p }}timezone" name="timezone" class="select">
      @foreach (['Asia/Kolkata', 'UTC', 'Asia/Dubai', 'Europe/London', 'America/New_York'] as $tz)
        <option value="{{ $tz }}" @selected(($a->timezone ?? 'Asia/Kolkata') === $tz)>{{ $tz }}</option>
      @endforeach
    </select>
  </div>
</div>

<div class="form-section-title"><i class="icon-users"></i> Who gets called</div>
<div class="form-grid cols-3">
  <div class="field">
    <label class="label" for="{{ $p }}expiry">Policy due within (days)</label>
    <input type="number" id="{{ $p }}expiry" name="expiry_within_days" class="input" min="0" max="365" value="{{ $a->expiry_within_days ?? 30 }}" required>
    <div class="hint">0 = ignore expiry date.</div>
  </div>
  <div class="field">
    <label class="label" for="{{ $p }}statuses">Customer status</label>
    <select id="{{ $p }}statuses" name="customer_statuses[]" class="select" multiple size="3">
      @foreach (['pending', 'queued', 'in_progress', 'contacted', 'closed'] as $status)
        <option value="{{ $status }}" @selected(in_array($status, $statuses, true))>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
      @endforeach
    </select>
  </div>
  <div class="field">
    <label class="label" for="{{ $p }}gap">Min. days between calls</label>
    <input type="number" id="{{ $p }}gap" name="min_days_between_calls" class="input" min="0" max="90" value="{{ $a->min_days_between_calls ?? 1 }}" required>
  </div>
  <div class="field full">
    <label class="check"><input type="checkbox" name="skip_do_not_call" value="1" @checked($a->skip_do_not_call ?? true)> <span>Skip Do Not Call customers <span class="muted">(always enforced)</span></span></label>
    <label class="check"><input type="checkbox" name="skip_already_renewed" value="1" @checked($a->skip_already_renewed ?? true)> <span>Skip customers who already renewed</span></label>
    <label class="check"><input type="checkbox" name="skip_active_callback" value="1" @checked($a->skip_active_callback ?? true)> <span>Skip customers with a callback still scheduled</span></label>
  </div>
</div>

<div class="form-section-title"><i class="icon-shield-check"></i> Calling limits</div>
<div class="form-grid cols-4">
  <div class="field">
    <label class="label" for="{{ $p }}max">Max calls per run</label>
    <input type="number" id="{{ $p }}max" name="max_calls_per_run" class="input" min="1" max="10000" value="{{ $a->max_calls_per_run ?? 200 }}" required>
  </div>
  <div class="field">
    <label class="label" for="{{ $p }}retries">Max retries</label>
    <input type="number" id="{{ $p }}retries" name="max_retries" class="input" min="0" max="20" value="{{ $a->max_retries ?? 2 }}" required>
  </div>
  <div class="field">
    <label class="label" for="{{ $p }}ws">Window start</label>
    <input type="time" id="{{ $p }}ws" name="window_start" class="input" value="{{ $a->window_start ?? '09:00' }}" required>
  </div>
  <div class="field">
    <label class="label" for="{{ $p }}we">Window end</label>
    <input type="time" id="{{ $p }}we" name="window_end" class="input" value="{{ $a->window_end ?? '19:00' }}" required>
  </div>
  <div class="field">
    <label class="label" for="{{ $p }}rate">Calls per second</label>
    <input type="number" id="{{ $p }}rate" name="attempts_per_second" class="input" step="0.1" min="0.1" max="500" value="{{ $a->attempts_per_second ?? 1.0 }}" required>
  </div>
</div>

<div class="mt-5">
  <label class="switch">
    <input type="checkbox" name="enabled" value="1" @checked($a->enabled)>
    <span class="switch-track"></span>
    Enabled — runs automatically every day
  </label>
</div>
