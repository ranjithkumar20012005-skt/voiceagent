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
    <h1>Leads</h1>
    <p class="ph-sub">Customers worth following up, straight from the agent's own call outcomes.</p>
  </div>
</div>

<div class="card mb-4">
  <div class="card-b">
    <div class="filter-bar">
      <div class="tabs-pill" role="tablist">
        @foreach (['hot' => 'Hot Leads', 'qualified' => 'Qualified', 'follow_up' => 'Follow-ups'] as $key => $label)
          <a href="{{ route('leads.index', array_merge(request()->except('page', 'filter'), ['filter' => $key])) }}"
             class="{{ $filter === $key ? 'active' : '' }}" role="tab" aria-selected="{{ $filter === $key ? 'true' : 'false' }}">
            {{ $label }} <span class="tab-count">{{ $counts[$key] }}</span>
          </a>
        @endforeach
      </div>

      <form method="GET" action="{{ route('leads.index') }}" class="row ml-auto" style="flex:1 1 280px; max-width:420px">
        <input type="hidden" name="filter" value="{{ $filter }}">
        <div class="search" style="flex:1">
          <i class="icon-search"></i>
          <input type="search" name="q" value="{{ $search }}" class="input input-sm" placeholder="Search name, phone or policy" aria-label="Search leads">
        </div>
        @if ($search)
          <a href="{{ route('leads.index', ['filter' => $filter]) }}" class="btn btn-ghost btn-sm">Clear</a>
        @endif
      </form>
    </div>
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
            <th>Outcome</th>
            <th>Qualification</th>
            <th>Last call</th>
            <th>Next action</th>
            <th>Callback</th>
            <th class="end"></th>
          </tr>
        </thead>
        <tbody>
          @foreach ($leads as $lead)
            @php [$action, $actionTone] = $nextAction($lead); @endphp
            <tr>
              <td>
                @if ($lead->customer)
                  <a href="{{ route('customers.show', $lead->customer) }}" class="cell-main">{{ $lead->customer->name ?: 'Unnamed' }}</a>
                @else
                  <span class="cell-main">Unknown</span>
                @endif
                <span class="cell-sub">{{ $lead->display_phone }}</span>
              </td>
              <td><span class="badge {{ LeadOutcome::badge($lead->call_disposition) }}">{{ LeadOutcome::hotHeadline($lead) }}</span></td>
              <td>
                @if ($lead->lead_generated)
                  <span class="badge badge-success"><i class="icon-circle-check"></i> Qualified</span>
                @else
                  <span class="muted text-sm">Not flagged</span>
                @endif
              </td>
              <td class="nowrap">
                {{ $lead->created_at->format('d M Y') }}
                <span class="cell-sub">{{ $lead->created_at->format('g:i A') }} · {{ $lead->duration_for_humans }}</span>
              </td>
              <td><span class="badge {{ $actionTone }}">{{ $action }}</span></td>
              <td class="nowrap">
                @if ($lead->customer?->next_callback_at)
                  <span class="{{ $lead->customer->next_callback_at->isPast() ? 'text-bad' : '' }}">{{ $lead->customer->next_callback_at->format('d M, g:i A') }}</span>
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
