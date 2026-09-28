@extends('layouts.app')

@section('title', 'Customers')

@php use App\Support\LeadOutcome; @endphp

@section('content')

<div class="ph">
  <div class="ph-main">
    <div class="eyebrow">Calling</div>
    <h1>Customers</h1>
    <p class="ph-sub">{{ number_format($customers->total()) }} {{ Str::plural('record', $customers->total()) }}{{ array_filter($filters) ? ' match these filters' : '' }}. Everyone the agent can call, with their latest result.</p>
  </div>
  <div class="ph-actions">
    <a href="{{ route('customers.export', request()->query()) }}" class="btn btn-secondary btn-sm"><i class="icon-download"></i> Export CSV</a>
    <a href="{{ route('imports.index') }}" class="btn btn-secondary btn-sm"><i class="icon-upload"></i> Import</a>
    <button type="button" class="btn btn-primary btn-sm" data-new-call><i class="icon-phone"></i> New Call</button>
  </div>
</div>

<div class="card mb-4">
  <div class="card-b">
    <form method="GET" action="{{ route('customers.index') }}" class="filter-bar">
      <div class="field grow">
        <label class="label" for="q">Search</label>
        <div class="search">
          <i class="icon-search"></i>
          <input type="search" id="q" name="q" value="{{ $filters['q'] ?? '' }}" class="input" placeholder="Name, phone or policy number">
        </div>
      </div>
      <div class="field fixed">
        <label class="label" for="status">Status</label>
        <select id="status" name="status" class="select">
          <option value="">All statuses</option>
          @foreach ($statuses as $status)
            <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
          @endforeach
        </select>
      </div>
      <div class="field fixed">
        <label class="label" for="outcome">Last outcome</label>
        <select id="outcome" name="outcome" class="select">
          <option value="">All outcomes</option>
          @foreach ($outcomes as $key => $label)
            <option value="{{ $key }}" @selected(($filters['outcome'] ?? '') === $key)>{{ LeadOutcome::label($key) }}</option>
          @endforeach
        </select>
      </div>
      <div class="field fixed">
        <label class="label" for="callback_from">Callback from</label>
        <input type="date" id="callback_from" name="callback_from" value="{{ $filters['callback_from'] ?? '' }}" class="input">
      </div>
      <div class="field fixed">
        <label class="label" for="callback_to">Callback to</label>
        <input type="date" id="callback_to" name="callback_to" value="{{ $filters['callback_to'] ?? '' }}" class="input">
      </div>
      <div class="row">
        <button type="submit" class="btn btn-primary"><i class="icon-filter"></i> Apply</button>
        @if (array_filter($filters))
          <a href="{{ route('customers.index') }}" class="btn btn-ghost">Reset</a>
        @endif
      </div>
    </form>
  </div>
</div>

<div class="card">
  @if ($customers->isEmpty())
    <x-empty icon="icon-users" title="{{ array_filter($filters) ? 'No customers match these filters' : 'No customers yet' }}">
      Upload a customer list to get started, or place a call — callers with a name are added automatically.
      <x-slot:action>
        <a href="{{ route('imports.index') }}" class="btn btn-primary btn-sm"><i class="icon-upload"></i> Upload a customer list</a>
      </x-slot:action>
    </x-empty>
  @else
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>Customer</th>
            <th>Status</th>
            <th>Latest call</th>
            <th>Outcome</th>
            <th>Policy expiry</th>
            <th>Callback</th>
            <th class="end"></th>
          </tr>
        </thead>
        <tbody>
          @foreach ($customers as $customer)
            @php $latest = $customer->latestCallAttempt; @endphp
            <tr>
              <td>
                <a href="{{ route('customers.show', $customer) }}" class="cell-main">{{ $customer->name ?: 'Unnamed' }}</a>
                @if ($customer->do_not_call)
                  <span class="badge badge-danger" style="margin-left:4px">DNC</span>
                @endif
                <span class="cell-sub">{{ $customer->display_phone }}{{ $customer->policy_number ? ' · ' . $customer->policy_number : '' }}</span>
              </td>
              <td><span class="badge">{{ ucfirst(str_replace('_', ' ', $customer->customer_status)) }}</span></td>
              <td class="nowrap">
                @if ($latest)
                  <a href="{{ route('calls.show', $latest) }}" class="text-sm">{{ $latest->created_at->diffForHumans() }}</a>
                  <span class="cell-sub">{{ \App\Support\LeadOutcome::connectivityLabel($latest) }} · {{ $latest->duration_for_humans }}</span>
                @else
                  <span class="faint text-sm">Not called yet</span>
                @endif
              </td>
              <td>
                @if ($customer->last_outcome)
                  <span class="badge {{ LeadOutcome::badge($customer->last_outcome) }}">{{ LeadOutcome::label($customer->last_outcome) }}</span>
                @else
                  <span class="faint">—</span>
                @endif
              </td>
              <td class="nowrap muted">{{ $customer->policy_expiry_date?->format('d M Y') ?: '—' }}</td>
              <td class="nowrap">
                @if ($customer->next_callback_at)
                  <span class="{{ $customer->next_callback_at->isPast() ? 'text-bad' : '' }}">{{ $customer->next_callback_at->format('d M, g:i A') }}</span>
                @else
                  <span class="faint">—</span>
                @endif
              </td>
              <td class="end">
                <div class="row-actions">
                  <a href="{{ route('customers.show', $customer) }}" class="btn btn-secondary btn-xs">Open</a>
                  <button type="button" class="btn btn-subtle btn-xs btn-icon"
                          title="{{ $customer->do_not_call ? 'Marked Do Not Call' : 'Call now' }}"
                          aria-label="Call {{ $customer->name }}"
                          @disabled($customer->do_not_call)
                          data-new-call
                          data-customer-id="{{ $customer->id }}"
                          data-name="{{ $customer->name }}"
                          data-phone="{{ $customer->phone_number }}"
                          data-policy="{{ $customer->policy_number }}"
                          data-registered-mobile="{{ $customer->registered_mobile }}"
                          data-language="{{ $customer->preferred_language }}">
                    <i class="icon-phone"></i>
                  </button>
                </div>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    @if ($customers->hasPages())
      <div class="card-f">{{ $customers->links() }}</div>
    @endif
  @endif
</div>

<p class="text-xs faint mt-3">Need the column layout? <a href="{{ route('customers.template') }}">Download the sample template</a>.</p>

@endsection
