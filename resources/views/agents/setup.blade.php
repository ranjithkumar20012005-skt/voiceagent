{{--
    How this agent gets people to call.

    Two paths, matching how it will be used: connect a lead source for instant
    calling, or upload a list and start a campaign. The bulk path hands over to
    the existing import pipeline rather than offering a second uploader.
--}}
@extends('layouts.app')

@section('title', 'Set up ' . $agent->name)

@section('content')

@php $isInstant = $agent->calling_mode !== \App\Models\Agent::MODE_BULK; @endphp

<div class="ph">
  <div class="ph-main">
    <div class="eyebrow"><a href="{{ route('agents.show', $agent) }}">{{ $agent->name }}</a></div>
    <h1>Set up calling</h1>
    <p class="ph-sub">Choose where {{ $agent->name }} gets people to call, and when it is allowed to call them.</p>
  </div>
  <div class="ph-actions">
    @if ($agent->status === \App\Models\Agent::ACTIVE)
      <form method="POST" action="{{ route('agents.pause', $agent) }}" class="inline-form">
        @csrf
        <button type="submit" class="btn btn-secondary btn-sm"><i class="icon-pause"></i> Pause agent</button>
      </form>
    @else
      <form method="POST" action="{{ route('agents.start', $agent) }}" class="inline-form">
        @csrf
        <button type="submit" class="btn btn-primary btn-sm" @disabled(! $agent->isProvisioned())>
          <i class="icon-play"></i> Start agent
        </button>
      </form>
    @endif
  </div>
</div>

@if ($errors->any())
  <div class="callout callout-warning mb-4"><i class="icon-circle-alert"></i><div>{{ $errors->first() }}</div></div>
@endif

{{-- Same copy and the same three states as the Agents list card and the agent page. --}}
<x-agent-status-callout :agent="$agent" />

{{-- ------------------------------------------------ Mode and hours --}}
<form method="POST" action="{{ route('agents.setup.update', $agent) }}" class="card mb-4">
  @csrf @method('PUT')
  <div class="card-h"><div><h2>1. How it gets people to call</h2></div></div>
  <div class="card-b stack">

    <div class="grid cols-2">
      <label class="card card-link" style="padding:14px; cursor:pointer">
        <div class="row" style="gap:10px; align-items:flex-start">
          <input type="radio" name="calling_mode" value="{{ \App\Models\Agent::MODE_INSTANT_LEADS }}"
                 @checked($agent->calling_mode === \App\Models\Agent::MODE_INSTANT_LEADS)>
          <div>
            <strong>Instant leads</strong>
            <p class="text-sm muted" style="margin:4px 0 0">
              Connect a website form, lead ads or an automation tool. Every new lead is called within seconds of arriving.
            </p>
          </div>
        </div>
      </label>

      <label class="card card-link" style="padding:14px; cursor:pointer">
        <div class="row" style="gap:10px; align-items:flex-start">
          <input type="radio" name="calling_mode" value="{{ \App\Models\Agent::MODE_BULK }}"
                 @checked($agent->calling_mode === \App\Models\Agent::MODE_BULK)>
          <div>
            <strong>Bulk campaign</strong>
            <p class="text-sm muted" style="margin:4px 0 0">
              Upload a list of leads, review it, then start the agent working through it.
            </p>
          </div>
        </div>
      </label>
    </div>

    <label class="card card-link" style="padding:14px; cursor:pointer">
      <div class="row" style="gap:10px; align-items:flex-start">
        <input type="radio" name="calling_mode" value="{{ \App\Models\Agent::MODE_INBOUND }}"
               @checked($agent->calling_mode === \App\Models\Agent::MODE_INBOUND)>
        <div>
          <strong>Inbound</strong>
          <p class="text-sm muted" style="margin:4px 0 0">The agent answers calls to your number rather than placing them.</p>
        </div>
      </div>
    </label>

    {{-- ------------------------------------------------ Hours --}}
    <div class="field" style="border-top:1px solid var(--line,#e6e8ec); padding-top:16px">
      <label class="label">Calling hours</label>

      <label class="row" style="gap:8px; align-items:center; margin-bottom:10px">
        <input type="checkbox" name="is_always_on" value="1" id="alwaysOn" @checked($agent->is_always_on)>
        <span>Always on, 24 hours a day</span>
      </label>
      <span class="text-xs muted">Recommended for inbound and instant leads, so a lead is never left waiting until morning.</span>

      <div id="windowFields" class="mt-3 {{ $agent->is_always_on ? 'hidden' : '' }}">
        <div class="grid cols-3">
          <div class="field">
            <label class="label" for="calling_window_start">From</label>
            <input class="input" type="time" id="calling_window_start" name="calling_window_start"
                   value="{{ $agent->calling_window_start ?: '09:00' }}">
          </div>
          <div class="field">
            <label class="label" for="calling_window_end">To</label>
            <input class="input" type="time" id="calling_window_end" name="calling_window_end"
                   value="{{ $agent->calling_window_end ?: '20:00' }}">
          </div>
          <div class="field">
            <label class="label" for="timezone">Timezone</label>
            <input class="input" id="timezone" name="timezone"
                   value="{{ $agent->timezone ?: ($agent->workspace?->timezone ?: config('app.timezone')) }}">
          </div>
        </div>

        <div class="field">
          <label class="label">Days</label>
          <div class="row wrap" style="gap:10px 18px">
            @foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day)
              <label class="row" style="gap:6px; align-items:center; font-size:13px">
                <input type="checkbox" name="calling_days[]" value="{{ $day }}"
                       @checked(in_array($day, $schedule->days(), true))>
                {{ Str::substr($day, 0, 3) }}
              </label>
            @endforeach
          </div>
        </div>
      </div>

      <div class="text-sm muted mt-2">Currently: <strong>{{ $schedule->describe() }}</strong></div>
    </div>

    <div><button type="submit" class="btn btn-primary btn-sm">Save calling setup</button></div>
  </div>
</form>

{{-- ------------------------------------------------ Instant leads --}}
@if ($isInstant)
  <div class="card mb-4">
    <div class="card-h">
      <div><h2>2. Connect your leads</h2><p>Anything that can send a web request can feed this agent</p></div>
    </div>
    <div class="card-b stack">

      @if ($sources->isNotEmpty())
        <div class="table-wrap">
          <table class="table">
            <thead><tr><th>Source</th><th>Type</th><th>Where to send leads</th><th class="num">Leads</th><th>Status</th><th class="end"></th></tr></thead>
            <tbody>
              @foreach ($sources as $source)
                <tr>
                  <td class="cell-main">
                    {{ $source->name }}
                    <span class="cell-sub">{{ $source->last_lead_at ? 'Last lead ' . $source->last_lead_at->diffForHumans() : 'No leads yet' }}</span>
                  </td>
                  <td>{{ $source->kindLabel() }}</td>
                  <td>
                    <input class="input mono" readonly onclick="this.select()" value="{{ $source->endpoint() }}"
                           style="font-size:11px; min-width:280px">
                    @if (in_array($source->kind, ['meta_lead', 'instagram'], true))
                      <div class="text-xs muted mt-1">
                        Verify token: <code>{{ $source->metadata['verify_token'] ?? '—' }}</code>
                      </div>
                    @endif
                  </td>
                  <td class="num">
                    {{ number_format($source->lead_count) }}
                    @if ($source->rejected_count)
                      <div class="text-xs text-bad">{{ number_format($source->rejected_count) }} rejected</div>
                    @endif
                  </td>
                  <td>
                    <span class="badge {{ $source->enabled ? 'badge-success' : '' }}">
                      <span class="dot"></span>{{ $source->enabled ? 'Live' : 'Paused' }}
                    </span>
                  </td>
                  <td class="end">
                    <form method="POST" action="{{ route('agents.sources.toggle', [$agent, $source]) }}" class="inline-form">
                      @csrf
                      <button type="submit" class="btn btn-ghost btn-xs">{{ $source->enabled ? 'Pause' : 'Resume' }}</button>
                    </form>
                    <form method="POST" action="{{ route('agents.sources.destroy', [$agent, $source]) }}" class="inline-form"
                          data-confirm="Remove this source? Its URL stops working immediately.">
                      @csrf @method('DELETE')
                      <button type="submit" class="btn btn-ghost btn-xs text-bad">Remove</button>
                    </form>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @else
        <x-empty icon="icon-zap" title="No lead sources connected yet" compact>
          Connect one below and the agent starts calling new leads as they arrive.
        </x-empty>
      @endif

      <form method="POST" action="{{ route('agents.sources.store', $agent) }}" class="row wrap" style="gap:8px; align-items:flex-end; border-top:1px solid var(--line,#e6e8ec); padding-top:16px">
        @csrf
        <div class="field" style="margin:0; min-width:180px">
          <label class="label" for="src_name">Name it</label>
          <input class="input" id="src_name" name="name" required maxlength="80" placeholder="Website enquiry form">
        </div>
        <div class="field" style="margin:0">
          <label class="label" for="src_kind">Type</label>
          <select class="input" id="src_kind" name="kind" required>
            @foreach ($kinds as $value => $label)
              <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
          </select>
        </div>
        <div class="field" style="margin:0">
          <label class="label" for="map_phone">Phone field <span class="opt">(optional)</span></label>
          <input class="input" id="map_phone" name="map_phone" maxlength="60" placeholder="phone_number">
        </div>
        <div class="field" style="margin:0">
          <label class="label" for="map_name">Name field <span class="opt">(optional)</span></label>
          <input class="input" id="map_name" name="map_name" maxlength="60" placeholder="full_name">
        </div>
        <button type="submit" class="btn btn-secondary btn-sm">Connect source</button>
      </form>
      <span class="text-xs muted">
        Leave the field names blank and common ones are detected automatically. Each source gets its own URL, so removing
        one never affects the others.
      </span>

    </div>
  </div>
@endif

{{-- ------------------------------------------------ Bulk campaign --}}
@unless ($isInstant)
  <div class="card mb-4">
    <div class="card-h">
      <div><h2>2. Upload your list and start</h2><p>CSV, XLSX or XLS</p></div>
      <a href="{{ route('imports.index') }}" class="btn btn-secondary btn-sm">Upload a list</a>
    </div>
    <div class="card-b stack">

      @if ($batches->isEmpty())
        <x-empty icon="icon-upload" title="No lists uploaded yet" compact>
          Upload a file of leads and map its columns. Phone numbers are normalised, duplicates removed, and any rejected
          rows are shown with the reason before anything is called.
        </x-empty>
      @else
        <form method="POST" action="{{ route('agents.campaign.start', $agent) }}" class="stack">
          @csrf
          <div class="grid cols-2">
            <div class="field">
              <label class="label" for="name">Campaign name</label>
              <input class="input" id="name" name="name" required maxlength="80"
                     value="{{ old('name', $agent->name . ' campaign') }}">
            </div>
            <div class="field">
              <label class="label" for="import_batch_id">Which list</label>
              <select class="input" id="import_batch_id" name="import_batch_id">
                <option value="">Everyone in my customers ({{ number_format($callableCustomers) }})</option>
                @foreach ($batches as $batch)
                  <option value="{{ $batch->id }}">
                    {{ $batch->original_filename }} — {{ number_format($batch->valid_rows) }} usable
                  </option>
                @endforeach
              </select>
            </div>
          </div>

          <div class="field" style="max-width:220px">
            <label class="label" for="max_calls">Maximum calls in this run</label>
            <input class="input" type="number" id="max_calls" name="max_calls" required min="1" max="5000"
                   value="{{ old('max_calls', 100) }}">
          </div>

          <div>
            <button type="submit" class="btn btn-primary" @disabled(! $agent->isProvisioned())>
              <i class="icon-play"></i> Start the agent on this list
            </button>
            <span class="text-xs muted">
              Nobody is called until you press this. People who asked not to be contacted are always skipped, and the run
              waits for your calling window.
            </span>
          </div>
        </form>
      @endif

      @if ($campaigns->isNotEmpty())
        <div class="table-wrap" style="border-top:1px solid var(--line,#e6e8ec); padding-top:12px">
          <table class="table">
            <thead><tr><th>Campaign</th><th>Status</th><th class="num">Contacts</th><th>Started</th></tr></thead>
            <tbody>
              @foreach ($campaigns as $campaign)
                <tr>
                  <td class="cell-main"><a href="{{ route('campaigns.show', $campaign) }}">{{ $campaign->name }}</a></td>
                  <td><span class="badge">{{ ucfirst($campaign->status) }}</span></td>
                  <td class="num">{{ number_format($campaign->total_contacts) }}</td>
                  <td>{{ $campaign->created_at->diffForHumans() }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @endif

    </div>
  </div>
@endunless

{{-- ------------------------------------------------ Scheduled calling --}}
{{--
    A recurring run. Saved as an automation, which the `calls:dispatch-due`
    scheduler already executes -- so this is a form over machinery that is
    already in production rather than a second dispatcher.
--}}
<div class="card mb-4">
  <div class="card-h">
    <div><h2>3. Scheduled calling <span class="opt">(optional)</span></h2>
      <p>Wake at a set time, work through the list, and follow up on anyone not reached</p></div>
  </div>
  <div class="card-b stack">

    @if ($schedules->isNotEmpty())
      <div class="table-wrap">
        <table class="table">
          <thead><tr><th>Schedule</th><th>Runs</th><th>List</th><th class="num">Per run</th><th>Last run</th><th>Status</th><th class="end"></th></tr></thead>
          <tbody>
            @foreach ($schedules as $schedule)
              <tr>
                <td class="cell-main">
                  {{ $schedule->name }}
                  @if ($schedule->only_unreached)
                    <span class="cell-sub">Follow-ups only · up to {{ $schedule->max_retries }} attempts</span>
                  @endif
                </td>
                <td class="nowrap">
                  {{ $schedule->run_at }}
                  <div class="text-xs muted">
                    {{ $schedule->run_days ? implode(', ', array_map(fn ($d) => Str::substr($d, 0, 3), $schedule->run_days)) : 'every day' }}
                    · {{ $schedule->timezone }}
                  </div>
                </td>
                <td>{{ $schedule->importBatch?->original_filename ?: 'All customers' }}</td>
                <td class="num">{{ number_format($schedule->max_calls_per_run) }}</td>
                <td class="nowrap">
                  {{ $schedule->last_run_at ? $schedule->last_run_at->diffForHumans() : 'Never' }}
                  @if ($schedule->last_run_message)
                    <div class="text-xs muted">{{ Str::limit($schedule->last_run_message, 44) }}</div>
                  @endif
                </td>
                <td>
                  <span class="badge {{ $schedule->enabled ? 'badge-success' : '' }}">
                    <span class="dot"></span>{{ $schedule->enabled ? 'On' : 'Off' }}
                  </span>
                </td>
                <td class="end">
                  <form method="POST" action="{{ route('agents.schedule.run', [$agent, $schedule]) }}" class="inline-form">
                    @csrf
                    <button type="submit" class="btn btn-ghost btn-xs" @disabled(! $agent->isProvisioned())>Run now</button>
                  </form>
                  <form method="POST" action="{{ route('agents.schedule.destroy', [$agent, $schedule]) }}" class="inline-form"
                        data-confirm="Remove this schedule?">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-ghost btn-xs text-bad">Remove</button>
                  </form>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif

    <form method="POST" action="{{ route('agents.schedule.save', $agent) }}" class="stack"
          style="border-top:1px solid var(--line,#e6e8ec); padding-top:16px">
      @csrf

      <div class="grid cols-3">
        <div class="field">
          <label class="label" for="sch_name">Name</label>
          <input class="input" id="sch_name" name="name" required maxlength="80"
                 value="{{ old('name', 'Daily run') }}">
        </div>
        <div class="field">
          <label class="label" for="run_at">Start at</label>
          <input class="input" type="time" id="run_at" name="run_at" required value="{{ old('run_at', '09:00') }}">
          <span class="text-xs muted">In {{ $agent->timezone ?: ($agent->workspace?->timezone ?: config('app.timezone')) }}.</span>
        </div>
        <div class="field">
          <label class="label" for="sch_batch">Which list</label>
          <select class="input" id="sch_batch" name="import_batch_id">
            <option value="">All customers</option>
            @foreach ($batches as $batch)
              <option value="{{ $batch->id }}">{{ $batch->original_filename }} ({{ number_format($batch->valid_rows) }})</option>
            @endforeach
          </select>
        </div>
      </div>

      <div class="field">
        <label class="label">Days it runs</label>
        <div class="row wrap" style="gap:10px 18px">
          @foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day)
            <label class="row" style="gap:6px; align-items:center; font-size:13px">
              <input type="checkbox" name="run_days[]" value="{{ $day }}">
              {{ Str::substr($day, 0, 3) }}
            </label>
          @endforeach
        </div>
        <span class="text-xs muted">Leave all unticked to run every day.</span>
      </div>

      <div class="grid cols-3">
        <div class="field">
          <label class="label" for="max_calls_per_run">Calls per run</label>
          <input class="input" type="number" id="max_calls_per_run" name="max_calls_per_run" required
                 min="1" max="5000" value="{{ old('max_calls_per_run', 100) }}">
        </div>
        <div class="field">
          <label class="label" for="max_retries">Follow-up attempts</label>
          <input class="input" type="number" id="max_retries" name="max_retries" required
                 min="0" max="10" value="{{ old('max_retries', 2) }}">
          <span class="text-xs muted">How many times to retry someone who did not answer.</span>
        </div>
        <div class="field">
          <label class="label" for="min_days_between_calls">Days between calls</label>
          <input class="input" type="number" id="min_days_between_calls" name="min_days_between_calls" required
                 min="0" max="60" value="{{ old('min_days_between_calls', 1) }}">
          <span class="text-xs muted">Nobody is rung again inside this gap.</span>
        </div>
      </div>

      <div class="stack" style="gap:8px">
        <label class="row" style="gap:8px; align-items:center">
          <input type="checkbox" name="only_unreached" value="1">
          <span>Follow-ups only — call back the people the last run could not reach</span>
        </label>
        <label class="row" style="gap:8px; align-items:center">
          <input type="checkbox" name="enabled" value="1" checked>
          <span>Switch this schedule on</span>
        </label>
      </div>

      <div>
        <button type="submit" class="btn btn-primary btn-sm">Save schedule</button>
        <span class="text-xs muted">
          Runs inside this agent's calling hours and never dials anyone who asked not to be contacted.
        </span>
      </div>
    </form>

  </div>
</div>

<script>
(function () {
  var toggle = document.getElementById('alwaysOn');
  var fields = document.getElementById('windowFields');
  if (!toggle || !fields) return;
  toggle.addEventListener('change', function () {
    fields.classList.toggle('hidden', toggle.checked);
  });
})();
</script>

@endsection
