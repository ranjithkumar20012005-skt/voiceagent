{{--
    One agent and everything it has done.

    Edit and setup are open to whoever owns this workspace. No provider name,
    agent id, version or deployment appears anywhere on this page -- those live
    only in the internal area, and the raw error_message below stays admin-only
    for the same reason.
--}}
@extends('layouts.app')

@section('title', $agent->name)

@section('content')

<div class="ph">
  <div class="ph-main">
    <div class="eyebrow"><a href="{{ route('agents.index') }}">Agents</a></div>
    <h1>{{ $agent->name }}</h1>
    <p class="ph-sub">{{ $agent->description ?: $agent->role ?: 'Your voice agent.' }}</p>
  </div>
  <div class="ph-actions">
    <span class="badge {{ $agent->isActive() ? 'badge-success' : '' }}">
      <span class="dot"></span>{{ $agent->displayStatus() }}
    </span>
    <a href="{{ route('calls.index', ['agent' => $agent->id]) }}" class="btn btn-secondary btn-sm">All calls</a>
    <a href="{{ route('agents.setup', $agent) }}" class="btn btn-primary btn-sm">Set up calling</a>
    <a href="{{ route('agents.edit', $agent) }}" class="btn btn-secondary btn-sm">Edit</a>
  </div>
</div>

{{--
    Setup state. An agent that is still being provisioned says so plainly
    rather than looking ready, because an agent that looks ready and cannot
    call is worse than one that admits it is not finished yet. Same copy and
    the same three states as the Agents list card -- see agent-status-callout.
--}}
<x-agent-status-callout :agent="$agent" />

{{-- ------------------------------------------------------ Activity --}}
<div class="section-label"><h2>Activity</h2></div>
<div class="grid cols-5 mb-4">
  <x-stat label="Calls Today" :value="number_format($stats['today'])" icon="icon-phone-outgoing" />
  <x-stat label="Calls This Month" :value="number_format($stats['month'])" icon="icon-calendar" />
  <x-stat label="Calls All Time" :value="number_format($stats['total'])" icon="icon-phone" />
  <x-stat label="Minutes Used" :value="number_format($stats['minutes'])" icon="icon-clock" tone="ink" />
  <x-stat label="Avg Call Duration" :value="$stats['avg_duration'] ?: '—'" icon="icon-timer" tone="ink" />
</div>

{{-- ------------------------------------------------------- Outcomes --}}
<div class="section-label"><h2>Outcomes</h2></div>
<div class="grid cols-5 mb-section">
  <x-stat label="Answered" :value="number_format($stats['answered'])" icon="icon-phone-call" tone="teal" value-tone="good" />
  <x-stat label="No Answer" :value="number_format($stats['no_answer'])" icon="icon-phone-missed" tone="amber" />
  <x-stat label="Failed" :value="number_format($stats['failed'])" icon="icon-circle-alert" tone="red" />
  <x-stat label="Interested" :value="number_format($stats['interested'])" icon="icon-user-check" value-tone="good"
          :hint="number_format($stats['not_interested']) . ' not interested'" />
  <x-stat label="Conversion Rate"
          :value="$stats['conversion'] !== null ? $stats['conversion'] . '%' : '—'"
          icon="icon-trending-up"
          :hint="$stats['conversion'] !== null ? 'Interested out of answered' : 'No answered calls yet'" />
</div>

<div class="grid cols-3">

  {{-- ------------------------------------------------- Recent calls --}}
  <div style="grid-column: span 2">
    <div class="card">
      <div class="card-h">
        <div><h2>Recent calls</h2><p>The latest calls this agent placed</p></div>
        <a href="{{ route('calls.index', ['agent' => $agent->id]) }}" class="btn btn-ghost btn-xs">All →</a>
      </div>
      <div class="table-wrap">
        <table class="table">
          <thead>
            <tr><th>Customer</th><th>When</th><th>Duration</th><th>Call status</th><th>Outcome</th><th class="end"></th></tr>
          </thead>
          <tbody>
            @forelse ($recentCalls as $call)
              @php $r = \App\Services\Presenters\CallResultPresenter::for($call); @endphp
              <tr>
                <td class="cell-main">
                  {{ $r->customerName() }}
                  <span class="cell-sub mono">{{ $r->phone() }}</span>
                </td>
                <td class="nowrap">
                  {{ $call->created_at->format('d M') }}
                  <span class="cell-sub">{{ $call->created_at->format('g:i A') }}</span>
                </td>
                <td>{{ $r->duration() ?: '—' }}</td>
                <td><span class="badge">{{ $r->callStatusLabel() }}</span></td>
                <td><span class="badge">{{ $r->outcomeLabel() }}</span></td>
                <td class="end">
                  <a href="{{ route('calls.show', $call) }}" class="btn btn-secondary btn-xs">Open</a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6">
                  <x-empty icon="icon-phone-call" title="No calls yet">
                    This agent's call activity will appear here once it begins calling.
                  </x-empty>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    {{-- ------------------------------------------------- Campaigns --}}
    <div class="card mt-4">
      <div class="card-h"><div><h2>Campaigns</h2><p>Campaigns this agent has called for</p></div></div>
      @if ($campaigns->isNotEmpty())
        <div class="table-wrap">
          <table class="table">
            <thead>
              <tr><th>Campaign</th><th>Status</th><th class="num">Calls</th><th class="num">Answered</th><th class="num">Interested</th></tr>
            </thead>
            <tbody>
              @foreach ($campaigns as $campaign)
                <tr>
                  <td class="cell-main">
                    @if ($campaign['model'])
                      <a href="{{ route('campaigns.show', $campaign['model']) }}">{{ $campaign['name'] }}</a>
                    @else
                      {{ $campaign['name'] }}
                    @endif
                  </td>
                  <td>{{ $campaign['status'] ? ucfirst($campaign['status']) : '—' }}</td>
                  <td class="num">{{ number_format($campaign['calls']) }}</td>
                  <td class="num">{{ number_format($campaign['answered']) }}</td>
                  <td class="num text-good">{{ number_format($campaign['interested']) }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @else
        <div class="card-b">
          <x-empty icon="icon-megaphone" title="No campaigns yet" compact>
            Campaigns this agent calls for will be listed here.
          </x-empty>
        </div>
      @endif
    </div>
  </div>

  {{-- ------------------------------------------------- Side panel --}}
  <div>
    <div class="card">
      <div class="card-h"><div><h2>Agent</h2><p>Set up and maintained by our team</p></div></div>
      <div class="card-b">
        <dl class="kv">
          <dt>Name</dt><dd>{{ $agent->name }}</dd>
          @if ($agent->role)
            <dt>Role</dt><dd>{{ $agent->role }}</dd>
          @endif
          <dt>Status</dt>
          <dd><span class="badge {{ $agent->isActive() ? 'badge-success' : '' }}"><span class="dot"></span>{{ $agent->displayStatus() }}</span></dd>
          <dt>Call type</dt><dd>{{ $agent->callingModeLabel() }}</dd>
          <dt>Phone number</dt>
          <dd class="mono">
            {{ $agent->phoneNumber ? \App\Support\PhoneNumber::display($agent->phoneNumber->phone_number) : 'Not assigned yet' }}
          </dd>
          <dt>Languages</dt>
          <dd>{{ $languages->isNotEmpty() ? $languages->implode(', ') : 'Not recorded yet' }}</dd>
          <dt>Last call</dt>
          <dd>{{ $stats['last_call']?->created_at?->diffForHumans() ?: 'No calls yet' }}</dd>
          <dt>Last activity</dt><dd>{{ $agent->updated_at->diffForHumans() }}</dd>
        </dl>
      </div>
    </div>

    <div class="card mt-4">
      <div class="card-h"><div><h2>History</h2></div></div>
      <div class="card-b">
        <dl class="kv">
          <dt>Conversations</dt>
          <dd>
            @if ($stats['conversations'] > 0)
              <a href="{{ route('conversations.index', ['agent' => $agent->id]) }}">{{ number_format($stats['conversations']) }} recorded</a>
            @else
              None yet
            @endif
          </dd>
          <dt>Leads &amp; results</dt>
          <dd><a href="{{ route('leads.index', ['filter' => 'all', 'agent' => $agent->id]) }}">View results</a></dd>
          <dt>Pending callbacks</dt>
          <dd>
            @if ($stats['callbacks'] > 0)
              <a href="{{ route('callbacks.index') }}">{{ number_format($stats['callbacks']) }} scheduled</a>
            @else
              None
            @endif
          </dd>
        </dl>
      </div>
    </div>

    @if ($callbackList->isNotEmpty())
      <div class="card mt-4">
        <div class="card-h"><div><h2>Upcoming callbacks</h2></div></div>
        @foreach ($callbackList as $callback)
          <div class="list-row">
            <div class="list-main">
              <div class="list-title">{{ $callback->customer?->name ?: 'Unnamed' }}</div>
              <div class="list-meta">{{ $callback->scheduled_at->format('d M, g:i A') }} · {{ $callback->scheduled_at->diffForHumans() }}</div>
            </div>
          </div>
        @endforeach
      </div>
    @endif

    <div class="card mt-4">
      <div class="card-b">
        <h3 class="text-sm">Need a change?</h3>
        <p class="text-sm muted">
          What this agent says, the language it speaks and the number it calls from are handled by our team.
          Tell us what you would like changed and we will update it.
        </p>
      </div>
    </div>
  </div>

</div>

@endsection
