@extends('layouts.app')

@section('title', 'My Agents')

@section('content')

<div class="ph">
  <div class="ph-main">
    <div class="eyebrow">Agents</div>
    <h1>My Agents</h1>
    <p class="ph-sub">The voice agents your team can call with. Each one points at an agent built in Sarvam; calls use the workspace phone line.</p>
  </div>
  <div class="ph-actions">
    <a href="{{ route('agents.create') }}" class="btn btn-primary btn-sm"><i class="icon-plus"></i> Create Agent</a>
  </div>
</div>

<div class="grid cols-3">

  {{-- The workspace agent from server configuration. Always present, never editable here. --}}
  <div class="card agent-card">
    <div class="card-b">
      <div class="agent-top">
        <div class="agent-avatar env"><i class="icon-bot"></i></div>
        <div class="row wrap" style="justify-content:flex-end">
          <span class="badge {{ $workspace['configured'] ? 'badge-success' : 'badge-warning' }}">
            <span class="dot"></span>{{ $workspace['configured'] ? 'Configured' : 'Not configured' }}
          </span>
          @if ($agents->where('is_default', true)->isEmpty())
            <span class="badge badge-brand">Default</span>
          @endif
        </div>
      </div>
      <h3>Workspace agent</h3>
      <p class="agent-desc">Set in the server environment. Used whenever no other agent is selected, and by campaigns and automations.</p>
      <div class="agent-meta">
        <span><i class="icon-hash"></i>{{ $workspace['agent_name'] ?: 'No agent ID' }}</span>
        <span><i class="icon-layers"></i>v{{ $workspace['agent_version'] ?: '—' }}</span>
        <span><i class="icon-languages"></i>{{ $workspace['default_language'] ?: config('sarvam.default_language') }}</span>
        <span><i class="icon-phone-call"></i>{{ number_format($unassigned) }} calls</span>
      </div>
    </div>
    <div class="card-f">
      <a href="{{ route('providers.index') }}" class="btn btn-secondary btn-sm btn-block">View configuration</a>
    </div>
  </div>

  @foreach ($agents as $agent)
    <div class="card agent-card">
      <div class="card-b">
        <div class="agent-top">
          <div class="agent-avatar"><i class="icon-bot"></i></div>
          <div class="row wrap" style="justify-content:flex-end">
            <span class="badge {{ $agent->isActive() ? 'badge-success' : '' }}">
              <span class="dot"></span>{{ $agent->isActive() ? 'Active' : 'Inactive' }}
            </span>
            @if ($agent->is_default)
              <span class="badge badge-brand">Default</span>
            @endif
          </div>
        </div>
        <h3>{{ $agent->name }}</h3>
        <p class="agent-desc">{{ $agent->description ?: 'No description.' }}</p>
        <div class="agent-meta">
          <span title="Sarvam agent ID"><i class="icon-hash"></i>{{ $agent->platform_app_id ?: 'Uses workspace agent' }}</span>
          @if ($agent->platform_app_version)
            <span><i class="icon-layers"></i>v{{ $agent->platform_app_version }}</span>
          @endif
          <span><i class="icon-languages"></i>{{ $agent->default_language ?: 'Default language' }}</span>
          <span><i class="icon-phone-call"></i>{{ number_format($agent->call_attempts_count) }} calls</span>
        </div>
        <div class="text-xs faint mt-3">Updated {{ $agent->updated_at->diffForHumans() }}</div>
      </div>
      <div class="card-f">
        <a href="{{ route('agents.edit', $agent) }}" class="btn btn-secondary btn-sm" style="flex:1">Open</a>
        @if ($agent->isActive() && ! $agent->is_default)
          <form method="POST" action="{{ route('agents.default', $agent) }}" class="inline-form">
            @csrf
            <button type="submit" class="btn btn-ghost btn-sm" title="Make default"><i class="icon-star"></i></button>
          </form>
        @endif
        <form method="POST" action="{{ route('agents.destroy', $agent) }}" class="inline-form"
              data-confirm="{{ $agent->call_attempts_count ? 'This agent has call history, so it will be deactivated rather than deleted. Continue?' : 'Delete this agent?' }}">
          @csrf
          @method('DELETE')
          <button type="submit" class="btn btn-ghost btn-sm text-bad" title="Delete" aria-label="Delete {{ $agent->name }}"><i class="icon-trash-2"></i></button>
        </form>
      </div>
    </div>
  @endforeach

  <a href="{{ route('agents.create') }}" class="card card-link" style="border-style:dashed; box-shadow:none">
    <x-empty icon="icon-plus" tone="green" title="Add another agent">
      Register another agent from your Sarvam workspace — for a different script, product or language.
    </x-empty>
  </a>
</div>

@endsection
