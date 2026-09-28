@extends('layouts.app')

@php
  use App\Support\LeadOutcome;
  $editing = $agent->exists;
@endphp

@section('title', $editing ? $agent->name : 'Create Agent')

@section('content')

<div class="ph">
  <div class="ph-main">
    <a href="{{ route('agents.index') }}" class="ph-back"><i class="icon-arrow-left"></i> My Agents</a>
    <h1>{{ $editing ? $agent->name : 'Create Agent' }}</h1>
    <div class="ph-meta">
      @if ($editing)
        <span class="badge {{ $agent->isActive() ? 'badge-success' : '' }}"><span class="dot"></span>{{ $agent->isActive() ? 'Active' : 'Inactive' }}</span>
        @if ($agent->is_default)<span class="badge badge-brand">Default</span>@endif
        <span>{{ number_format($agent->call_attempts_count) }} calls placed</span>
      @else
        <span>Connect an agent you have already built in Sarvam so your team can call with it.</span>
      @endif
    </div>
  </div>
  @if ($editing && $agent->isActive())
    <div class="ph-actions">
      <button type="button" class="btn btn-secondary btn-sm" data-new-call data-agent-id="{{ $agent->id }}"><i class="icon-phone"></i> Test call</button>
    </div>
  @endif
</div>

@unless ($editing)
  <div class="steps" aria-hidden="true">
    <span class="step-chip current"><b>1</b>Identity</span>
    <span class="step-chip"><b>2</b>Conversation</span>
    <span class="step-chip"><b>3</b>Calling</span>
  </div>
@endunless

<div class="split">
  <form method="POST" action="{{ $editing ? route('agents.update', $agent) : route('agents.store') }}" class="stack">
    @csrf
    @if ($editing) @method('PUT') @endif

    {{-- ----------------------------------------------------- Identity --}}
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
            <input class="input" id="name" name="name" maxlength="80" required value="{{ old('name', $agent->name) }}" placeholder="Renewal reminder — Hindi">
          </div>
          <div class="field full">
            <label class="label" for="description">Description</label>
            <input class="input" id="description" name="description" maxlength="255" value="{{ old('description', $agent->description) }}" placeholder="Calls customers whose policy expires in the next 30 days.">
          </div>
          <div class="field">
            <label class="label" for="status">Status</label>
            <select class="select" id="status" name="status">
              <option value="active" @selected(old('status', $agent->status) === 'active')>Active — can place calls</option>
              <option value="inactive" @selected(old('status', $agent->status) === 'inactive')>Inactive — hidden from New Call</option>
            </select>
          </div>
          <div class="field" style="align-self:end">
            <label class="switch">
              <input type="checkbox" name="is_default" value="1" @checked(old('is_default', $agent->is_default))>
              <span class="switch-track"></span>
              Default agent for new calls
            </label>
          </div>
        </div>
      </div>
    </div>

    {{-- ------------------------------------------------- Conversation --}}
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
          <textarea class="textarea" id="first_message" name="first_message" maxlength="2000" rows="3" placeholder="Namaste, am I speaking with {user_name}?">{{ old('first_message', $agent->first_message) }}</textarea>
        </div>
        <div class="field">
          <label class="label" for="instructions">Instructions</label>
          <textarea class="textarea" id="instructions" name="instructions" maxlength="20000" rows="8" placeholder="Role, tone, what to confirm, when to end the call…">{{ old('instructions', $agent->instructions) }}</textarea>
        </div>
      </div>
    </div>

    {{-- ------------------------------------------------------ Calling --}}
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
            <input class="input mono" id="platform_app_id" name="platform_app_id" maxlength="191" value="{{ old('platform_app_id', $agent->platform_app_id) }}" placeholder="{{ $workspace['agent_name'] ?: 'e.g. Renewal-ins-2f5d65f0-ab95' }}">
            <div class="hint">Leave blank to use the workspace agent.</div>
          </div>
          <div class="field">
            <label class="label" for="platform_app_version">Agent version</label>
            <input class="input" type="number" min="1" id="platform_app_version" name="platform_app_version" value="{{ old('platform_app_version', $agent->platform_app_version) }}" placeholder="{{ $workspace['agent_version'] ?: '1' }}">
            <div class="hint">Required together with the agent ID.</div>
          </div>
          <div class="field">
            <label class="label" for="default_language">Default language</label>
            <select class="select" id="default_language" name="default_language">
              <option value="">Workspace default ({{ config('sarvam.default_language') }})</option>
              @foreach ($languages as $language)
                <option value="{{ $language }}" @selected(old('default_language', $agent->default_language) === $language)>{{ $language }}</option>
              @endforeach
            </select>
          </div>
          <div class="field">
            <label class="label">Phone line</label>
            <input class="input" value="{{ $workspace['caller_number'] ?: 'Not configured' }}" disabled>
            <div class="hint">Shared workspace line — see <a href="{{ route('phone-numbers.index') }}">Phone Numbers</a>.</div>
          </div>
        </div>
      </div>
      <div class="card-f between">
        <span class="text-xs muted">The agent must declare the variables this app sends: {{ implode(', ', array_keys(config('sarvam.agent_variables', []))) }}.</span>
        <div class="row">
          <a href="{{ route('agents.index') }}" class="btn btn-secondary btn-sm">Cancel</a>
          <button type="submit" class="btn btn-primary btn-sm">{{ $editing ? 'Save changes' : 'Create agent' }}</button>
        </div>
      </div>
    </div>
  </form>

  {{-- --------------------------------------------------------- Side rail --}}
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

    @if ($editing)
      <div class="card">
        <div class="card-h"><div><h3>Recent calls</h3><p>Placed with this agent</p></div></div>
        @forelse ($recent as $call)
          <div class="list-row">
            <div class="list-main">
              <div class="list-title">{{ $call->customer?->name ?: $call->display_phone }}</div>
              <div class="list-meta">{{ $call->created_at->diffForHumans() }} · {{ $call->duration_for_humans }}</div>
            </div>
            <a href="{{ route('calls.show', $call) }}" class="badge {{ LeadOutcome::badge($call->call_disposition) }}">{{ LeadOutcome::label($call->call_disposition) }}</a>
          </div>
        @empty
          <x-empty icon="icon-phone" title="No calls yet" compact>Use Test call to place the first one.</x-empty>
        @endforelse
      </div>

      <form method="POST" action="{{ route('agents.destroy', $agent) }}"
            data-confirm="{{ $agent->call_attempts_count ? 'This agent has call history, so it will be deactivated rather than deleted. Continue?' : 'Delete this agent?' }}">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-danger btn-sm btn-block"><i class="icon-trash-2"></i> {{ $agent->call_attempts_count ? 'Deactivate agent' : 'Delete agent' }}</button>
      </form>
    @endif
  </div>
</div>

@endsection
