@extends('layouts.app')

@section('title', 'Callbacks')

@php
  use App\Support\LeadOutcome;

  // The reason shown is only what the agent recorded -- nothing is inferred.
  $reason = function ($customer) {
      $call = $customer->latestCallAttempt;
      $notes = $call ? ((array) ($call->output_agent_variables ?? []))['customer_notes'] ?? null : null;

      if (is_scalar($notes) && trim((string) $notes) !== '') return Str::limit((string) $notes, 90);
      if ($call?->call_disposition === 'callback') return 'Customer asked to be called back';
      return $customer->notes ? Str::limit($customer->notes, 90) : 'Scheduled by your team';
  };
@endphp

@section('content')

<div class="ph">
  <div class="ph-main">
    <div class="eyebrow">Results</div>
    <h1>Callbacks</h1>
    <p class="ph-sub">Customers who asked to be called back. Overdue callbacks come first — call them, then mark them handled.</p>
  </div>
</div>

<div class="grid cols-4 mb-section">
  @foreach ([
    'overdue'  => ['Overdue', 'icon-circle-alert', 'red'],
    'today'    => ['Due today', 'icon-calendar-clock', 'amber'],
    'week'     => ['Next 7 days', 'icon-calendar-clock', 'teal'],
    'upcoming' => ['All upcoming', 'icon-clock', 'ink'],
  ] as $key => [$label, $icon, $tone])
    <a href="{{ route('callbacks.index', ['range' => $key]) }}" class="card card-link stat" style="{{ $range === $key ? 'border-color:var(--green-600);box-shadow:0 0 0 3px rgba(82,151,37,.12)' : '' }}">
      <div>
        <div class="stat-label">{{ $label }}</div>
        <div class="stat-value {{ $key === 'overdue' && $counts[$key] > 0 ? 'bad' : '' }}">{{ $counts[$key] }}</div>
      </div>
      <div class="stat-icon {{ $tone }}"><i class="{{ $icon }}"></i></div>
    </a>
  @endforeach
</div>

<div class="card">
  <div class="card-h">
    <div>
      <h2>{{ ['overdue' => 'Overdue callbacks', 'today' => 'Due today', 'week' => 'Due in the next 7 days', 'upcoming' => 'All upcoming callbacks'][$range] ?? 'Callbacks' }}</h2>
      <p>{{ $customers->total() }} {{ Str::plural('customer', $customers->total()) }}</p>
    </div>
  </div>

  @if ($customers->isEmpty())
    <x-empty icon="icon-calendar-clock" title="No callbacks in this range">
      Callbacks appear when a customer asks the agent to call them later.
    </x-empty>
  @else
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr><th>Customer</th><th>Callback time</th><th>Reason</th><th>Status</th><th>Previous call</th><th class="end"></th></tr>
        </thead>
        <tbody>
          @foreach ($customers as $customer)
            @php
              $overdue = $customer->next_callback_at->isPast();
              $dueToday = $customer->next_callback_at->isToday();
              $previous = $customer->latestCallAttempt;
            @endphp
            <tr>
              <td>
                <a href="{{ route('customers.show', $customer) }}" class="cell-main">{{ $customer->name ?: 'Unnamed' }}</a>
                @if ($customer->do_not_call)<span class="badge badge-danger" style="margin-left:4px">DNC</span>@endif
                <span class="cell-sub">{{ $customer->display_phone }}</span>
              </td>
              <td class="nowrap">
                <span class="strong">{{ $customer->next_callback_at->format('d M, g:i A') }}</span>
                <span class="cell-sub {{ $overdue ? 'text-bad' : '' }}">{{ $customer->next_callback_at->diffForHumans() }}</span>
              </td>
              <td class="text-sm" style="max-width:280px">{{ $reason($customer) }}</td>
              <td>
                @if ($overdue)
                  <span class="badge badge-danger"><span class="dot"></span>Overdue</span>
                @elseif ($dueToday)
                  <span class="badge badge-warning"><span class="dot"></span>Due today</span>
                @else
                  <span class="badge badge-teal"><span class="dot"></span>Scheduled</span>
                @endif
              </td>
              <td class="nowrap">
                @if ($previous)
                  <a href="{{ route('calls.show', $previous) }}" class="text-sm">{{ $previous->created_at->format('d M') }}</a>
                  <span class="cell-sub">{{ LeadOutcome::label($previous->call_disposition) }} · {{ $previous->duration_for_humans }}</span>
                @else
                  <span class="faint">—</span>
                @endif
              </td>
              <td class="end">
                <div class="row-actions">
                  <button type="button" class="btn btn-primary btn-xs" title="Call now"
                          @disabled($customer->do_not_call)
                          data-new-call
                          data-customer-id="{{ $customer->id }}"
                          data-name="{{ $customer->name }}"
                          data-phone="{{ $customer->phone_number }}"
                          data-policy="{{ $customer->policy_number }}"
                          data-language="{{ $customer->preferred_language }}">
                    <i class="icon-phone"></i> Call
                  </button>
                  <form method="POST" action="{{ route('callbacks.clear', $customer) }}" class="inline-form">
                    @csrf
                    <button type="submit" class="btn btn-secondary btn-xs" title="Mark handled"><i class="icon-check"></i> Done</button>
                  </form>
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

@endsection
