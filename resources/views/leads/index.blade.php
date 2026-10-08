@extends('layouts.app')

@section('title', 'Leads')

@php
  use App\Support\LeadOutcome;

  /*
   | Next action is read from the agent's structured output only -- never
   | inferred from transcript text.
   */
  $nextAction = function ($lead) {
      $out = (array) ($lead->output_agent_variables ?? []);
      $truthy = fn ($v) => in_array(strtolower((string) $v), ['1', 'true', 'yes'], true);

      if ($lead->customer?->do_not_call) return ['Do not contact', 'badge-red'];
      if ($lead->callback_at) return ['Call back ' . $lead->callback_at->format('d M, g:i A'), 'badge-cyan'];
      if (isset($out['payment_link_requested']) && $truthy($out['payment_link_requested'])) return ['Send payment link', 'badge-green'];
      if ($lead->lead_generated || $lead->call_disposition === 'interested') return ['Follow up', 'badge-green'];
      return ['Review call', 'badge-gray'];
  };
@endphp

@section('content')

<div class="ph">
  <div class="ph-main">
    <div class="eyebrow">Results</div>
    <h1>Leads &amp; Results</h1>
    <p class="ph-sub">Customers worth following up, straight from the agent's own call outcomes.</p>
  </div>
</div>

<div class="card mb-4">
  <div class="card-b">
    <div class="filter-bar">
      <div class="tabs-pill" role="tablist">
        @foreach (['hot' => 'Hot Leads', 'qualified' => 'Qualified', 'follow_up' => 'Follow-ups', 'all' => 'All Results'] as $key => $label)
          <a href="{{ route('leads.index', array_merge(request()->except('page', 'filter'), ['filter' => $key])) }}"
             class="{{ $filter === $key ? 'active' : '' }}" role="tab" aria-selected="{{ $filter === $key ? 'true' : 'false' }}">
            {{ $label }} <span class="tab-count">{{ number_format($counts[$key]) }}</span>
          </a>
        @endforeach
      </div>
    </div>

    {{-- Date range, agent, call status and outcome. The tab is carried through so
         filtering does not silently drop the client back to Hot Leads. --}}
    <form method="GET" action="{{ route('leads.index') }}" class="row wrap mt-3" style="gap:10px; align-items:flex-end">
      <input type="hidden" name="filter" value="{{ $filter }}">

      <div class="field" style="margin:0; min-width:200px; flex:1">
        <label class="label" for="q">Customer or phone</label>
        <input type="search" class="input" id="q" name="q" value="{{ $filters['q'] }}" placeholder="Name, phone or policy">
      </div>

      <div class="field" style="margin:0">
        <label class="label" for="agent">Agent</label>
        <select class="input" id="agent" name="agent">
          <option value="">All</option>
          @foreach ($agents as $agent)
            <option value="{{ $agent->id }}" @selected((string) $filters['agent'] === (string) $agent->id)>{{ $agent->name }}</option>
          @endforeach
        </select>
      </div>

      <div class="field" style="margin:0">
        <label class="label" for="status">Call status</label>
        <select class="input" id="status" name="status">
          <option value="">All</option>
          @foreach ($statuses as $value => $label)
            <option value="{{ $value }}" @selected((string) $filters['status'] === (string) $value)>{{ $label }}</option>
          @endforeach
        </select>
      </div>

      <div class="field" style="margin:0">
        <label class="label" for="outcome">Outcome</label>
        <select class="input" id="outcome" name="outcome">
          <option value="">All</option>
          @foreach ($outcomes as $value => $label)
            <option value="{{ $value }}" @selected((string) $filters['outcome'] === (string) $value)>{{ $label }}</option>
          @endforeach
        </select>
      </div>

      <div class="field" style="margin:0">
        <label class="label" for="from">From</label>
        <input type="date" class="input" id="from" name="from" value="{{ $filters['from'] }}">
      </div>

      <div class="field" style="margin:0">
        <label class="label" for="to">To</label>
        <input type="date" class="input" id="to" name="to" value="{{ $filters['to'] }}">
      </div>

      <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
      <a href="{{ route('leads.index', ['filter' => $filter]) }}" class="btn btn-ghost btn-sm">Clear</a>
    </form>
  </div>
</div>

<div class="card">
  @if ($leads->isEmpty())
    <x-empty icon="icon-user-check" title="{{ $search ? 'No leads match your search' : 'No leads yet' }}">
      @if ($filter === 'follow_up')
        No follow-ups yet. They appear when a customer asks to be called back.
      @elseif ($filter === 'qualified')
        Qualified leads appear when the agent marks a call as a generated lead.
      @else
        No leads yet. Interested customers appear here after a call.
      @endif
    </x-empty>
  @else
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>Customer</th>
            <th>Agent</th>
            <th>Date / time</th>
            <th>Duration</th>
            <th>Call status</th>
            <th>Outcome</th>
            <th>Lead status</th>
            <th>Callback</th>
            <th class="end"></th>
          </tr>
        </thead>
        <tbody>
          @foreach ($leads as $lead)
            @php
              $r = \App\Services\Presenters\CallResultPresenter::for($lead);
              [$action, $actionTone] = $nextAction($lead);
            @endphp
            <tr>
              <td>
                @if ($lead->customer)
                  <a href="{{ route('customers.show', $lead->customer) }}" class="cell-main">{{ $lead->customer->name ?: 'Unnamed' }}</a>
                @else
                  <span class="cell-main">Unknown</span>
                @endif
                <span class="cell-sub mono">{{ $r->phone() }}</span>
              </td>
              <td>{{ $r->agentName() }}</td>
              <td class="nowrap">
                {{ $lead->created_at->format('d M Y') }}
                <span class="cell-sub">{{ $lead->created_at->format('g:i A') }}</span>
              </td>
              <td>{{ $r->duration() ?: '—' }}</td>
              <td><span class="badge">{{ $r->callStatusLabel() }}</span></td>
              <td><span class="badge {{ LeadOutcome::badge($lead->call_disposition) }}">{{ LeadOutcome::hotHeadline($lead) }}</span></td>
              <td>
                <span class="badge {{ $r->isInterested() ? 'badge-success' : '' }}">{{ $r->leadStatusLabel() }}</span>
                {{-- Next action, derived from the agent's structured output only. --}}
                <span class="cell-sub">{{ $action }}</span>
              </td>
              <td class="nowrap">
                {{-- The callbacks table, so this agrees with the Callbacks page. --}}
                @if ($lead->callback)
                  <span class="{{ $lead->callback->scheduled_at->isPast() ? 'text-bad' : '' }}">
                    {{ $lead->callback->scheduled_at->format('d M, g:i A') }}
                  </span>
                  <span class="cell-sub">{{ ucfirst($lead->callback->status) }}</span>
                @else
                  <span class="faint">—</span>
                @endif
              </td>
              <td class="end">
                <div class="row-actions">
                  <a href="{{ route('calls.show', $lead) }}" class="btn btn-secondary btn-xs">View Call</a>
                  @if ($lead->customer && ! $lead->customer->do_not_call)
                    <button type="button" class="btn btn-subtle btn-xs btn-icon" title="Call again" aria-label="Call again"
                            data-new-call
                            data-customer-id="{{ $lead->customer->id }}"
                            data-name="{{ $lead->customer->name }}"
                            data-phone="{{ $lead->customer->phone_number }}"
                            data-policy="{{ $lead->customer->policy_number }}">
                      <i class="icon-phone"></i>
                    </button>
                  @endif
                </div>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    @if ($leads->hasPages())
      <div class="card-f">{{ $leads->links() }}</div>
    @endif
  @endif
</div>

<p class="text-xs faint mt-3">Qualification comes from the agent's <em>lead generated</em> output. Leads are never scored from transcript text.</p>

@endsection
