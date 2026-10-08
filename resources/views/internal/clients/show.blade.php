{{--
    Internal: one client.

    The only screen where provider identifiers are entered or displayed. Never
    reachable by a client user -- the `admin` middleware 404s them.
--}}
@extends('layouts.app')

@section('title', $workspace->name)

@section('content')

<div class="ph">
  <div class="ph-main">
    <div class="eyebrow"><a href="{{ route('internal.clients.index') }}">Clients</a></div>
    <h1>{{ $workspace->name }}</h1>
    <p class="ph-sub">{{ number_format($callCount) }} calls &middot; {{ $members->count() }} user(s) &middot; workspace #{{ $workspace->id }}</p>
  </div>
  <div class="ph-actions">
    <form method="POST" action="{{ route('internal.clients.status', $workspace) }}" class="inline-form">
      @csrf
      <input type="hidden" name="status" value="{{ $workspace->isActive() ? 'suspended' : 'active' }}">
      <button type="submit" class="btn btn-secondary btn-sm">{{ $workspace->isActive() ? 'Suspend' : 'Reactivate' }}</button>
    </form>
  </div>
</div>

@if ($errors->any())
  <div class="card mb-4"><div class="card-b">
    <span class="badge badge-danger"><span class="dot"></span>{{ $errors->first() }}</span>
  </div></div>
@endif

{{-- ---------------------------------------------------------------- --}}
{{-- Agent mapping                                                    --}}
{{-- ---------------------------------------------------------------- --}}
<div class="card mb-4">
  <div class="card-b">
    <h3 class="text-sm">Hosted agent mapping</h3>
    <p class="text-sm muted">
      The agent our team built for this client in our platform account. These identifiers are internal and never rendered in the client's dashboard.
    </p>

    @if ($agents->isNotEmpty())
      <table class="table mt-3">
        <thead>
          <tr><th>Agent</th><th>Mapping</th><th>Number</th><th>Mode</th><th>Status</th><th class="end"></th></tr>
        </thead>
        <tbody>
          @foreach ($agents as $agent)
            <tr>
              <td>
                <strong>{{ $agent->name }}</strong>
                <div class="text-xs faint mono">{{ $agent->agent_ref }}</div>
              </td>
              <td class="mono text-xs">
                @if ($agent->isProvisioned())
                  {{ $agent->provider_agent_id }} &middot; v{{ $agent->provider_agent_version }}
                  @if ($agent->provider_deployment_id)
                    <div class="faint">dep {{ $agent->provider_deployment_id }}</div>
                  @endif
                @else
                  <span class="text-bad">Not mapped</span>
                @endif
              </td>
              <td>{{ $agent->phoneNumber?->phone_number ?: '—' }}</td>
              <td>{{ $agent->callingModeLabel() }}</td>
              <td>
                <span class="badge {{ $agent->isActive() ? 'badge-success' : ($agent->status === 'error' ? 'badge-danger' : '') }}">
                  <span class="dot"></span>{{ $agent->displayStatus() }}
                </span>
                @if ($agent->error_message)
                  <div class="text-xs text-bad">{{ $agent->error_message }}</div>
                @endif
              </td>
              <td class="end">
                <form method="POST" action="{{ route('internal.clients.agent.status', [$workspace, $agent->id]) }}" class="inline-form">
                  @csrf
                  <input type="hidden" name="status" value="{{ $agent->status === 'active' ? 'paused' : 'active' }}">
                  <button type="submit" class="btn btn-ghost btn-sm">{{ $agent->status === 'active' ? 'Pause' : 'Activate' }}</button>
                </form>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    @endif

    <form method="POST" action="{{ route('internal.clients.agent.map', $workspace) }}" class="stack mt-4">
      @csrf
      <div class="grid cols-2">
        <div class="field">
          <label class="label" for="display_name">Agent name (client sees this)</label>
          <input class="input" id="display_name" name="display_name" required maxlength="80"
                 value="{{ old('display_name') }}" placeholder="Maya">
        </div>
        <div class="field">
          <label class="label" for="calling_mode">Calling mode</label>
          <select class="input" id="calling_mode" name="calling_mode" required>
            @foreach ($modes as $value => $label)
              <option value="{{ $value }}" @selected(old('calling_mode') === $value)>{{ $label }}</option>
            @endforeach
          </select>
        </div>
      </div>

      <div class="field">
        <label class="label" for="description">Description <span class="opt">(optional)</span></label>
        <input class="input" id="description" name="description" maxlength="255" value="{{ old('description') }}">
      </div>

      <div class="grid cols-3">
        <div class="field">
          <label class="label" for="provider_agent_id">Hosted agent ID <span class="opt">(internal)</span></label>
          <input class="input mono" id="provider_agent_id" name="provider_agent_id" required maxlength="191"
                 value="{{ old('provider_agent_id') }}" placeholder="Renewal-ins-2f5d65f0">
        </div>
        <div class="field">
          <label class="label" for="provider_agent_version">Version</label>
          <input class="input" type="number" min="1" id="provider_agent_version" name="provider_agent_version" required
                 value="{{ old('provider_agent_version', 1) }}">
        </div>
        <div class="field">
          <label class="label" for="provider_deployment_id">Deployment ID <span class="opt">(optional)</span></label>
          <input class="input mono" id="provider_deployment_id" name="provider_deployment_id" maxlength="191"
                 value="{{ old('provider_deployment_id') }}">
        </div>
      </div>

      <div class="field" style="max-width:260px">
        <label class="label" for="default_language">Primary language</label>
        <select class="input" id="default_language" name="default_language">
          <option value="">Workspace default</option>
          @foreach ($languages as $language)
            <option value="{{ $language }}" @selected(old('default_language') === $language)>{{ $language }}</option>
          @endforeach
        </select>
      </div>

      <div>
        <button type="submit" class="btn btn-primary btn-sm">Save mapping</button>
        <span class="text-xs muted">Creates a new agent for this client, or updates the one you select.</span>
      </div>
    </form>
  </div>
</div>

{{-- ---------------------------------------------------------------- --}}
{{-- Numbers                                                          --}}
{{-- ---------------------------------------------------------------- --}}
<div class="card mb-4">
  <div class="card-b">
    <h3 class="text-sm">Phone numbers</h3>

    @if ($numbers->isNotEmpty())
      <table class="table mt-3">
        <thead><tr><th>Number</th><th>Country</th><th>Status</th><th>Agent</th><th class="end"></th></tr></thead>
        <tbody>
          @foreach ($numbers as $number)
            <tr>
              <td class="mono">{{ $number->phone_number }}</td>
              <td>{{ $number->country }}</td>
              <td><span class="badge"><span class="dot"></span>{{ $number->displayStatus() }}</span></td>
              <td>{{ $number->agent?->name ?: '—' }}</td>
              <td class="end">
                <form method="POST" action="{{ route('internal.clients.numbers.release', [$workspace, $number->id]) }}"
                      class="inline-form" data-confirm="Return this number to the shared pool?">
                  @csrf @method('DELETE')
                  <button type="submit" class="btn btn-ghost btn-sm text-bad">Release</button>
                </form>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    @endif

    @if ($agents->isEmpty())
      <p class="text-sm muted mt-3">Map an agent first, then a number can be attached to it.</p>
    @elseif ($pool->isEmpty())
      <p class="text-sm muted mt-3">No numbers left in the shared pool. Add some from the Clients page.</p>
    @else
      <form method="POST" action="{{ route('internal.clients.numbers.assign', $workspace) }}" class="row wrap mt-3" style="gap:8px; align-items:flex-end">
        @csrf
        <div class="field" style="margin:0">
          <label class="label" for="phone_number_id">From pool</label>
          <select class="input" id="phone_number_id" name="phone_number_id" required>
            @foreach ($pool as $available)
              <option value="{{ $available->id }}">{{ $available->phone_number }}</option>
            @endforeach
          </select>
        </div>
        <div class="field" style="margin:0">
          <label class="label" for="agent_id">Attach to</label>
          <select class="input" id="agent_id" name="agent_id" required>
            @foreach ($agents as $agent)
              <option value="{{ $agent->id }}">{{ $agent->name }}</option>
            @endforeach
          </select>
        </div>
        <button type="submit" class="btn btn-secondary btn-sm">Assign</button>
      </form>
    @endif
  </div>
</div>

{{-- ---------------------------------------------------------------- --}}
{{-- Users                                                            --}}
{{-- ---------------------------------------------------------------- --}}
<div class="card">
  <div class="card-b">
    <h3 class="text-sm">Client logins</h3>

    <table class="table mt-3">
      <thead><tr><th>Name</th><th>Email</th><th>Role</th></tr></thead>
      <tbody>
        @foreach ($members as $member)
          <tr>
            <td>{{ $member->user->name }}</td>
            <td class="mono text-xs">{{ $member->user->email }}</td>
            <td>{{ ucfirst($member->role) }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>

    <form method="POST" action="{{ route('internal.clients.users.store', $workspace) }}" class="row wrap mt-4" style="gap:8px; align-items:flex-end">
      @csrf
      <div class="field" style="margin:0">
        <label class="label" for="user_name">Name</label>
        <input class="input" id="user_name" name="name" required maxlength="120">
      </div>
      <div class="field" style="margin:0">
        <label class="label" for="user_email">Email</label>
        <input class="input" type="email" id="user_email" name="email" required maxlength="191">
      </div>
      <div class="field" style="margin:0">
        <label class="label" for="user_role">Role</label>
        <select class="input" id="user_role" name="role" required>
          <option value="member">Member</option>
          <option value="admin">Admin</option>
          <option value="owner">Owner</option>
        </select>
      </div>
      <button type="submit" class="btn btn-secondary btn-sm">Add login</button>
    </form>
    <span class="text-xs muted">Leave the password out and one is generated, shown once.</span>
  </div>
</div>

@endsection
