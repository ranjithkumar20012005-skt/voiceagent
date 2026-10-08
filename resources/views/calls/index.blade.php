@extends('layouts.app')

@section('title', 'Call Logs')

@php
  use App\Support\LeadOutcome;

  // Client-facing wording throughout; the values posted stay canonical.
  $tabs = ['all' => 'All']
      + LeadOutcome::connectivityLabels()
      + array_intersect_key(LeadOutcome::labels(), array_flip(LeadOutcome::headlineOutcomes()));
@endphp

@section('content')

<div class="ph">
  <div class="ph-main">
    <div class="eyebrow">Results</div>
    <h1>Call Logs</h1>
    <p class="ph-sub">{{ number_format($attempts->total()) }} calls{{ $filter !== 'all' || $search || request('from') || request('to') || request('agent') ? ' match these filters' : ' in total' }}. Open any call for its details and transcript.</p>
  </div>
</div>

<div class="card mb-4">
  <div class="card-b">
    <div class="tabs-pill mb-4" role="tablist">
      @foreach ($tabs as $key => $label)
        <a href="{{ route('calls.index', array_merge(request()->except('page', 'filter'), ['filter' => $key])) }}"
           class="{{ $filter === $key ? 'active' : '' }}" role="tab" aria-selected="{{ $filter === $key ? 'true' : 'false' }}">{{ $label }}</a>
      @endforeach
    </div>

    <form method="GET" action="{{ route('calls.index') }}" class="filter-bar">
      <input type="hidden" name="filter" value="{{ $filter }}">
      <div class="field grow">
        <label class="label" for="q">Search</label>
        <div class="search">
          <i class="icon-search"></i>
          <input type="search" id="q" name="q" value="{{ $search }}" class="input" placeholder="Customer, phone or policy">
        </div>
      </div>
      @if ($agents->isNotEmpty())
        <div class="field fixed">
          <label class="label" for="agent">Agent</label>
          <select id="agent" name="agent" class="select">
            <option value="">All agents</option>
            <option value="workspace" @selected(request('agent') === 'workspace')>Workspace agent</option>
            @foreach ($agents as $agent)
              <option value="{{ $agent->id }}" @selected(request('agent') == $agent->id)>{{ $agent->name }}</option>
            @endforeach
          </select>
        </div>
      @endif
      <div class="field fixed">
        <label class="label" for="direction">Direction</label>
        <select id="direction" name="direction" class="select">
          <option value="">Both</option>
          <option value="outbound" @selected(request('direction') === 'outbound')>Outbound</option>
          <option value="inbound" @selected(request('direction') === 'inbound')>Inbound</option>
        </select>
      </div>
      <div class="field fixed">
        <label class="label" for="from">From</label>
        <input type="date" id="from" name="from" value="{{ request('from') }}" class="input">
      </div>
      <div class="field fixed">
        <label class="label" for="to">To</label>
        <input type="date" id="to" name="to" value="{{ request('to') }}" class="input">
      </div>
      <div class="row">
        <button type="submit" class="btn btn-primary"><i class="icon-filter"></i> Apply</button>
        @if ($search || request('from') || request('to') || request('agent') || request('direction'))
          <a href="{{ route('calls.index', ['filter' => $filter]) }}" class="btn btn-ghost">Reset</a>
        @endif
      </div>
    </form>
  </div>
</div>

<div class="card">
  @if ($attempts->isEmpty())
    <x-empty icon="icon-phone-call" title="No calls match this filter">
      Try another filter, or place a call with New Call.
    </x-empty>
  @else
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>Customer</th>
            <th>Agent</th>
            <th>Status</th>
            <th>Outcome</th>
            <th class="num">Duration</th>
            <th>Date &amp; time</th>
            <th class="end"></th>
          </tr>
        </thead>
        <tbody>
          @foreach ($attempts as $attempt)
            <tr>
              <td>
                <span class="cell-main">{{ $attempt->customer?->name ?: 'Unknown' }}</span>
                <span class="cell-sub">{{ $attempt->display_phone }}</span>
              </td>
              <td class="nowrap muted">
                {{ $attempt->agent?->name ?? 'Workspace agent' }}
                @if ($attempt->campaign_id)<span class="cell-sub">Campaign</span>@endif
              </td>
              <td><span class="badge {{ LeadOutcome::connectivityBadge($attempt) }}">{{ LeadOutcome::connectivityLabel($attempt) }}</span></td>
              <td><span class="badge {{ LeadOutcome::badge($attempt->call_disposition) }}">{{ LeadOutcome::label($attempt->call_disposition) }}</span></td>
              <td class="num">{{ $attempt->duration_for_humans }}</td>
              <td class="nowrap">
                {{ $attempt->created_at->format('d M Y') }}
                <span class="cell-sub">{{ $attempt->created_at->format('g:i A') }}</span>
              </td>
              <td class="end">
                <div class="row-actions">
                  <a href="{{ route('calls.show', $attempt) }}" class="btn btn-secondary btn-xs">View</a>
                  @if ($attempt->customer && ! $attempt->customer->do_not_call)
                    <button type="button" class="btn btn-subtle btn-xs btn-icon" title="Call again" aria-label="Call again"
                            data-new-call
                            data-customer-id="{{ $attempt->customer->id }}"
                            data-name="{{ $attempt->customer->name }}"
                            data-phone="{{ $attempt->customer->phone_number }}"
                            data-policy="{{ $attempt->customer->policy_number }}">
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
    @if ($attempts->hasPages())
      <div class="card-f">{{ $attempts->links() }}</div>
    @endif
  @endif
</div>

@endsection
