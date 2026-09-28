@extends('layouts.app')

@section('title', $customer->name ?: 'Customer')

@php
  use App\Support\LeadOutcome;
  $attempts = $customer->callAttempts;
  $connected = $attempts->where('connectivity_status', 'connected')->count();
@endphp

@section('content')

<div class="ph">
  <div class="ph-main">
    <a href="{{ route('customers.index') }}" class="ph-back"><i class="icon-arrow-left"></i> Customers</a>
    <h1>{{ $customer->name ?: 'Unnamed customer' }}</h1>
    <div class="ph-meta">
      <span>{{ $customer->display_phone }}</span>
      <span class="badge">{{ ucfirst(str_replace('_', ' ', $customer->customer_status)) }}</span>
      @if ($customer->last_outcome)
        <span class="badge {{ LeadOutcome::badge($customer->last_outcome) }}">{{ LeadOutcome::label($customer->last_outcome) }}</span>
      @endif
      @if ($customer->do_not_call)
        <span class="badge badge-danger">Do not call</span>
      @endif
    </div>
  </div>
  <div class="ph-actions">
    <button type="button" class="btn btn-primary btn-sm"
            @disabled($customer->do_not_call)
            data-new-call
            data-customer-id="{{ $customer->id }}"
            data-name="{{ $customer->name }}"
            data-phone="{{ $customer->phone_number }}"
            data-policy="{{ $customer->policy_number }}"
            data-registered-mobile="{{ $customer->registered_mobile }}"
            data-language="{{ $customer->preferred_language }}">
      <i class="icon-phone"></i> Call Now
    </button>
  </div>
</div>

<div class="grid cols-4 mb-section">
  <x-stat label="Total calls" :value="$customer->call_count" icon="icon-phone" tone="ink" />
  <x-stat label="Connected" :value="$connected" icon="icon-phone-call" tone="teal" :hint="'of the last ' . $attempts->count() . ' attempts'" />
  <x-stat label="Last call" :value="$customer->last_call_at?->format('d M') ?? 'Never'" icon="icon-clock" tone="ink"
          :hint="$customer->last_call_at?->diffForHumans()" />
  <x-stat label="Next callback" :value="$customer->next_callback_at?->format('d M, H:i') ?? 'None'" icon="icon-calendar-clock"
          :tone="$customer->next_callback_at?->isPast() ? 'red' : 'amber'"
          :value-tone="$customer->next_callback_at?->isPast() ? 'bad' : null"
          :hint="$customer->next_callback_at ? ($customer->next_callback_at->isPast() ? 'Overdue' : $customer->next_callback_at->diffForHumans()) : null" />
</div>

<div class="split-left">
  <div class="stack">
    {{-- ------------------------------------------------------------ Details --}}
    <form method="POST" action="{{ route('customers.update', $customer) }}" class="card">
      @csrf
      @method('PUT')
      <div class="card-h"><div><h2>Customer information</h2><p>Changes apply to future calls.</p></div></div>
      <div class="card-b">
        <div class="form-grid">
          <div class="field full">
            <label class="label" for="name">Name</label>
            <input type="text" id="name" name="name" value="{{ old('name', $customer->name) }}" class="input" maxlength="120">
          </div>
          <div class="field">
            <label class="label" for="policy_number">Policy number</label>
            <input type="text" id="policy_number" name="policy_number" value="{{ old('policy_number', $customer->policy_number) }}" class="input" maxlength="64">
          </div>
          <div class="field">
            <label class="label" for="preferred_language">Language</label>
            <select id="preferred_language" name="preferred_language" class="select">
              <option value="">Agent default</option>
              @foreach (config('sarvam.languages', []) as $language)
                <option value="{{ $language }}" @selected(old('preferred_language', $customer->preferred_language) === $language)>{{ $language }}</option>
              @endforeach
            </select>
          </div>
          <div class="field">
            <label class="label" for="customer_status">Status</label>
            <select id="customer_status" name="customer_status" class="select">
              @foreach (['pending', 'queued', 'in_progress', 'contacted', 'closed'] as $status)
                <option value="{{ $status }}" @selected(old('customer_status', $customer->customer_status) === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
              @endforeach
            </select>
          </div>
          <div class="field">
            <label class="label" for="next_callback_at">Next callback</label>
            <input type="datetime-local" id="next_callback_at" name="next_callback_at" class="input"
                   value="{{ old('next_callback_at', $customer->next_callback_at?->format('Y-m-d\TH:i')) }}">
          </div>
          <div class="field full">
            <label class="label" for="notes">Notes</label>
            <textarea id="notes" name="notes" rows="3" class="textarea" maxlength="5000">{{ old('notes', $customer->notes) }}</textarea>
          </div>
          <div class="field full">
            <label class="check">
              <input type="checkbox" name="do_not_call" value="1" @checked(old('do_not_call', $customer->do_not_call))>
              <span>Do not call this customer <span class="muted">— excluded from calls, campaigns and automations</span></span>
            </label>
          </div>
        </div>
      </div>
      <div class="card-f"><button type="submit" class="btn btn-primary btn-sm">Save changes</button></div>
    </form>

    <div class="card">
      <div class="card-h"><div><h2>Policy</h2></div></div>
      <div class="card-b">
        <dl class="kv">
          <dt>Customer ID</dt><dd>{{ $customer->customer_identifier ?: '—' }}</dd>
          <dt>Registered mobile</dt><dd>{{ $customer->registered_mobile ?: '—' }}</dd>
          <dt>Policy expiry</dt><dd>{{ $customer->policy_expiry_date?->format('d M Y') ?: '—' }}</dd>
          <dt>Renewal premium</dt><dd>{{ $customer->renewal_premium !== null ? number_format((float) $customer->renewal_premium, 2) : '—' }}</dd>
          <dt>Added</dt>
          <dd>
            {{ $customer->created_at->format('d M Y') }}
            @if ($customer->importBatch)
              · <a href="{{ route('imports.show', $customer->importBatch) }}">{{ Str::limit($customer->importBatch->original_filename, 28) }}</a>
            @endif
          </dd>
        </dl>
      </div>
    </div>
  </div>

  <div class="stack">
    {{-- -------------------------------------------------------- Call history --}}
    <div class="card">
      <div class="card-h"><div><h2>Call history</h2><p>Most recent first</p></div></div>
      @if ($attempts->isEmpty())
        <x-empty icon="icon-phone" title="No calls yet" compact>No calls recorded for this customer.</x-empty>
      @else
        <div class="table-wrap">
          <table class="table">
            <thead>
              <tr><th>Date</th><th>Agent</th><th>Status</th><th>Outcome</th><th class="num">Duration</th><th class="end"></th></tr>
            </thead>
            <tbody>
              @foreach ($attempts as $attempt)
                <tr>
                  <td class="nowrap">{{ $attempt->created_at->format('d M Y') }}<span class="cell-sub">{{ $attempt->created_at->format('g:i A') }}</span></td>
                  <td class="muted nowrap">{{ $attempt->agent?->name ?? 'Workspace agent' }}</td>
                  <td><span class="badge {{ LeadOutcome::connectivityBadge($attempt) }}">{{ LeadOutcome::connectivityLabel($attempt) }}</span></td>
                  <td><span class="badge {{ LeadOutcome::badge($attempt->call_disposition) }}">{{ LeadOutcome::label($attempt->call_disposition) }}</span></td>
                  <td class="num">{{ $attempt->duration_for_humans }}</td>
                  <td class="end"><a href="{{ route('calls.show', $attempt) }}" class="btn btn-secondary btn-xs">View</a></td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @endif
    </div>

    <div class="grid cols-2">
      {{-- ------------------------------------------------------- Campaigns --}}
      <div class="card">
        <div class="card-h"><div><h3>Campaigns</h3><p>Bulk runs that called this customer</p></div></div>
        @forelse ($campaigns as $campaign)
          <div class="list-row">
            <div class="list-main">
              <a href="{{ route('campaigns.show', $campaign) }}" class="list-title cell-main">{{ $campaign->name }}</a>
              <div class="list-meta">{{ $campaign->created_at->format('d M Y') }}</div>
            </div>
            <span class="badge {{ $campaign->status_badge }}">{{ ucfirst($campaign->status) }}</span>
          </div>
        @empty
          <x-empty icon="icon-megaphone" title="Not in any campaign" compact />
        @endforelse
      </div>

      {{-- ------------------------------------------------------- Callback --}}
      <div class="card">
        <div class="card-h"><div><h3>Callback</h3><p>Requested by the customer</p></div></div>
        @if ($customer->next_callback_at)
          <div class="card-b">
            <div class="strong">{{ $customer->next_callback_at->format('l, d M Y') }}</div>
            <div class="text-sm {{ $customer->next_callback_at->isPast() ? 'text-bad' : 'muted' }}">
              {{ $customer->next_callback_at->format('g:i A') }} · {{ $customer->next_callback_at->diffForHumans() }}
            </div>
          </div>
          <div class="card-f">
            <form method="POST" action="{{ route('callbacks.clear', $customer) }}" class="inline-form">
              @csrf
              <button type="submit" class="btn btn-secondary btn-sm"><i class="icon-check"></i> Mark handled</button>
            </form>
          </div>
        @else
          <x-empty icon="icon-calendar-clock" title="No callback scheduled" compact />
        @endif
      </div>
    </div>
  </div>
</div>

@endsection
