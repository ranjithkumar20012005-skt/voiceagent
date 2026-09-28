@extends('layouts.app')

@section('title', 'Settings')

@section('content')

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

<form method="POST" action="{{ route('settings.update') }}">
  @csrf
  @method('PUT')

  {{-- ------------------------------------------------------- Workspace --}}
  <div data-tab-panel="workspace" data-tab-scope="settings">
    <div class="grid cols-2">
      <div class="card">
        <div class="card-h"><div class="card-h-title"><div class="card-icon"><i class="icon-building-2"></i></div><div><h2>Workspace</h2><p>Set by your administrator</p></div></div></div>
        <div class="card-b">
          <dl class="kv">
            <dt>Workspace name</dt><dd>{{ config('app.name') }}</dd>
            <dt>Timezone</dt><dd>{{ $status['timezone'] }}</dd>
            <dt>Default language</dt><dd>{{ $settings['default_language'] }}</dd>
            <dt>Calling</dt>
            <dd><span class="badge {{ $status['configured'] ? 'badge-success' : 'badge-warning' }}"><span class="dot"></span>{{ $status['configured'] ? 'Available' : 'Not configured' }}</span></dd>
          </dl>
        </div>
      </div>
      <div class="card">
        <div class="card-h"><div class="card-h-title"><div class="card-icon ink"><i class="icon-circle-user"></i></div><div><h2>Your account</h2><p>Signed in as</p></div></div></div>
        <div class="card-b">
          <dl class="kv">
            <dt>Name</dt><dd>{{ auth()->user()->name }}</dd>
            <dt>Email</dt><dd>{{ auth()->user()->email }}</dd>
            <dt>Member since</dt><dd>{{ auth()->user()->created_at?->format('d M Y') ?? '—' }}</dd>
          </dl>
        </div>
      </div>
    </div>
  </div>

  {{-- ----------------------------------------------------------- Agent --}}
  <div data-tab-panel="agent" data-tab-scope="settings" hidden>
    <div class="card">
      <div class="card-h">
        <div class="card-h-title"><div class="card-icon"><i class="icon-bot"></i></div><div><h2>Workspace agent</h2><p>The agent used when no other agent is selected</p></div></div>
        <a href="{{ route('agents.index') }}" class="btn btn-secondary btn-sm">Manage agents</a>
      </div>
      <div class="card-b">
        <dl class="kv">
          <dt>Status</dt>
          <dd><span class="badge {{ $status['configured'] ? 'badge-success' : 'badge-warning' }}"><span class="dot"></span>{{ $status['configured'] ? 'Active' : 'Not configured' }}</span></dd>
          <dt>Agent ID</dt><dd class="mono">{{ $status['agent_name'] ?: 'Not configured' }}</dd>
          <dt>Agent version</dt><dd>{{ $status['agent_version'] ?: 'Not configured' }}</dd>
          <dt>Caller number</dt><dd>{{ $status['caller_number'] ?: 'Not configured' }}</dd>
          <dt>Variables sent</dt><dd>{{ implode(', ', array_keys(config('sarvam.agent_variables', []))) }}</dd>
        </dl>
      </div>
    </div>
  </div>

  {{-- --------------------------------------------------------- Calling --}}
  <div data-tab-panel="calling" data-tab-scope="settings" hidden>
    <div class="card">
      <div class="card-h"><div class="card-h-title"><div class="card-icon amber"><i class="icon-phone-outgoing"></i></div><div><h2>Calling defaults</h2><p>Applied to new calls, campaigns and automations</p></div></div></div>
      <div class="card-b">
        <div class="form-grid">
          <div class="field">
            <label class="label" for="default_language">Default language</label>
            <select id="default_language" name="default_language" class="select">
              @foreach ($languages as $language)
                <option value="{{ $language }}" @selected(old('default_language', $settings['default_language']) === $language)>{{ $language }}</option>
              @endforeach
            </select>
          </div>
          <div class="field">
            <label class="label" for="retry_limit">Retry limit</label>
            <input type="number" id="retry_limit" name="retry_limit" class="input" min="0" max="20" value="{{ old('retry_limit', $settings['retry_limit']) }}" required>
            <div class="hint">How often an unanswered call is retried.</div>
          </div>
          <div class="field">
            <label class="label" for="window_start">Calling window start</label>
            <input type="time" id="window_start" name="window_start" class="input" value="{{ old('window_start', $settings['window_start']) }}" required>
          </div>
          <div class="field">
            <label class="label" for="window_end">Calling window end</label>
            <input type="time" id="window_end" name="window_end" class="input" value="{{ old('window_end', $settings['window_end']) }}" required>
          </div>
          <div class="field">
            <label class="label" for="max_calls_per_run">Max calls per run</label>
            <input type="number" id="max_calls_per_run" name="max_calls_per_run" class="input" min="1" max="10000" value="{{ old('max_calls_per_run', $settings['max_calls_per_run']) }}" required>
          </div>
        </div>
      </div>
      <div class="card-f"><button type="submit" class="btn btn-primary btn-sm">Save settings</button></div>
    </div>
  </div>

  {{-- ------------------------------------------------------- Providers --}}
  <div data-tab-panel="providers" data-tab-scope="settings" hidden>
    <div class="card">
      <div class="card-h">
        <div class="card-h-title"><div class="card-icon teal"><i class="icon-boxes"></i></div><div><h2>Provider settings</h2><p>Connection status only — never credentials</p></div></div>
        <a href="{{ route('providers.index') }}" class="btn btn-secondary btn-sm">Open Providers</a>
      </div>
      <div class="card-b">
        <dl class="kv">
          <dt>Voice platform</dt>
          <dd><span class="badge {{ $status['configured'] ? 'badge-success' : 'badge-warning' }}">{{ $status['configured'] ? 'Configured' : 'Not configured' }}</span></dd>
          <dt>Result webhook</dt>
          <dd><span class="badge {{ $status['webhook_configured'] ? 'badge-success' : 'badge-warning' }}">{{ $status['webhook_configured'] ? 'Configured' : 'Not configured' }}</span></dd>
        </dl>

        {{-- Names of missing settings only -- never their values. --}}
        @if ($missing)
          <div class="callout callout-warning mt-4">
            <i class="icon-key-round"></i>
            <div><strong>Missing server configuration:</strong> {{ implode(', ', $missing) }}<br>These are environment variables set on the server by an administrator.</div>
          </div>
        @endif
      </div>
    </div>
  </div>

  {{-- ------------------------------------------------------ Automation --}}
  <div data-tab-panel="automation" data-tab-scope="settings" hidden>
    <div class="card">
      <div class="card-h">
        <div class="card-h-title"><div class="card-icon"><i class="icon-zap"></i></div><div><h2>Automation settings</h2><p>Defaults for scheduled calling</p></div></div>
        <a href="{{ route('automations.index') }}" class="btn btn-secondary btn-sm">Manage automations</a>
      </div>
      <div class="card-b">
        <div class="form-grid">
          <div class="field">
            <label class="label" for="automation_time">Default automation time</label>
            <input type="time" id="automation_time" name="automation_time" class="input" value="{{ old('automation_time', $settings['automation_time']) }}" required>
            <div class="hint">Each automation can override this with its own run time.</div>
          </div>
        </div>
      </div>
      <div class="card-f"><button type="submit" class="btn btn-primary btn-sm">Save settings</button></div>
    </div>
  </div>

  {{-- ----------------------------------------------------------- Usage --}}
  <div data-tab-panel="usage" data-tab-scope="settings" hidden>
    <div class="card">
      <x-empty icon="icon-gauge" tone="green" title="Usage has its own page">
        Calls and conversation minutes for this month, by agent and by day.
        <x-slot:action><a href="{{ route('usage.index') }}" class="btn btn-primary btn-sm">Open Usage</a></x-slot:action>
      </x-empty>
    </div>
  </div>
</form>

@endsection

@push('scripts')
@if ($errors->any())
<script>
  // Land on the tab holding the fields that failed validation.
  (function () { var b = document.querySelector('[data-tab="calling"]'); if (b && !location.hash) b.click(); })();
</script>
@endif
@endpush
