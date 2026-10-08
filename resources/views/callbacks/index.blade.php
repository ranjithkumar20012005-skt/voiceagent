{{--
    Callbacks.

    Rebuilt on the callbacks table, which the webhook populates. Each row links
    back to the call that asked for it.
--}}
@extends('layouts.app')

@section('title', 'Callbacks')

@section('content')

<div class="ph">
  <div class="ph-main">
    <div class="eyebrow">Results</div>
    <h1>Callbacks</h1>
    <p class="ph-sub">Customers who asked to be called back, and when.</p>
  </div>
</div>

@php
  $tabs = [
      'upcoming'  => 'Upcoming',
      'due'       => 'Due now',
      'completed' => 'Completed',
      'cancelled' => 'Cancelled',
      'all'       => 'All',
  ];
@endphp

<div class="row wrap mb-4" style="gap:8px">
  @foreach ($tabs as $key => $label)
    <a href="{{ route('callbacks.index', ['range' => $key]) }}"
       class="btn btn-sm {{ $range === $key ? 'btn-primary' : 'btn-secondary' }}">
      {{ $label }}
      @if (($counts[$key] ?? 0) > 0)
        <span class="badge {{ $key === 'due' ? 'badge-warning' : '' }}">{{ number_format($counts[$key]) }}</span>
      @endif
    </a>
  @endforeach
</div>

<div class="card">
  <div class="card-b p-0">
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>Customer</th><th>Phone</th><th>Agent</th>
            <th>Requested time</th><th>Reason</th><th>Original call</th><th>Status</th><th class="end"></th>
          </tr>
        </thead>
        <tbody>
          @forelse ($callbacks as $callback)
            <tr>
              <td class="cell-main">{{ $callback->customer?->name ?: 'Unknown customer' }}</td>
              <td class="mono">
                {{ $callback->customer?->phone_number ? \App\Support\PhoneNumber::display($callback->customer->phone_number) : '—' }}
              </td>
              <td>{{ $callback->agent?->name ?: '—' }}</td>
              <td class="nowrap">
                {{ $callback->scheduled_at->format('j M Y, g:i a') }}
                @if ($callback->scheduled_at->isPast() && in_array($callback->status, ['scheduled', 'due'], true))
                  <div class="text-xs text-bad">Overdue</div>
                @endif
              </td>
              <td>{{ $callback->reason ?: '—' }}</td>
              <td>
                @if ($callback->call)
                  <a href="{{ route('calls.show', $callback->call) }}">View call</a>
                @else
                  <span class="muted">—</span>
                @endif
              </td>
              <td>
                <span class="badge {{ $callback->status === 'due' ? 'badge-warning' : ($callback->status === 'completed' ? 'badge-success' : '') }}">
                  <span class="dot"></span>{{ ucfirst($callback->status) }}
                </span>
              </td>
              <td class="end">
                @if (in_array($callback->status, ['scheduled', 'due'], true))
                  <form method="POST" action="{{ route('callbacks.complete', $callback) }}" class="inline-form">
                    @csrf
                    <button type="submit" class="btn btn-ghost btn-sm" title="Mark as done"><i class="icon-check"></i></button>
                  </form>
                  <form method="POST" action="{{ route('callbacks.cancel', $callback) }}" class="inline-form"
                        data-confirm="Cancel this callback?">
                    @csrf
                    <button type="submit" class="btn btn-ghost btn-sm text-bad" title="Cancel"><i class="icon-x"></i></button>
                  </form>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="8">
                <x-empty icon="icon-calendar-clock" title="No callbacks currently scheduled">
                  When a customer asks your agent to call back, the request will appear here with the time they asked for.
                </x-empty>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

@if ($callbacks->hasPages())
  <div class="mt-3">{{ $callbacks->links() }}</div>
@endif

@endsection
