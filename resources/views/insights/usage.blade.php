{{--
    Usage.

    Built from usage_records -- one row per call, written when the result lands.
    Only what the client is charged appears here; our wholesale cost is hidden on
    the model and never selected.
--}}
@extends('layouts.app')

@section('title', 'Usage')

@section('content')

<div class="ph">
  <div class="ph-main">
    <div class="eyebrow">Settings</div>
    <h1>Usage</h1>
    <p class="ph-sub">Calls and conversation minutes for {{ $month }}.</p>
  </div>
</div>

@php
  $deltaCalls = $previous['calls'] > 0
      ? round(($current['calls'] - $previous['calls']) / $previous['calls'] * 100)
      : null;
@endphp

<div class="grid cols-4 mb-section">
  <x-stat label="Calls this month" :value="number_format($current['calls'])" icon="icon-phone-call"
          :hint="$deltaCalls !== null ? ($deltaCalls >= 0 ? '+' : '') . $deltaCalls . '% vs last month' : null" />
  <x-stat label="Minutes this month" :value="number_format($current['minutes'], 2)" icon="icon-clock" />
  <x-stat label="Conversation minutes" :value="number_format($current['seconds'] / 60, 1)" icon="icon-timer"
          hint="Actual talk time" />
  <x-stat label="Calls all time" :value="number_format($allTime['calls'])" icon="icon-chart-column" />
</div>

@if ($current['calls'] === 0)

  <div class="card">
    <div class="card-b">
      <x-empty icon="icon-gauge" title="No usage yet this month">
        Your call activity will appear here after your agent begins calling.
      </x-empty>
    </div>
  </div>

@else

  <div class="grid cols-2">

    <div class="card">
      <div class="card-h"><div><h2>Daily usage</h2><p>{{ $month }}</p></div></div>
      <div class="card-b p-0">
        <div class="table-wrap">
          <table class="table">
            <thead><tr><th>Day</th><th class="num">Calls</th><th class="num">Minutes</th></tr></thead>
            <tbody>
              @foreach ($daily as $row)
                <tr>
                  <td>{{ $row['day'] }}</td>
                  <td class="num">{{ number_format($row['calls']) }}</td>
                  <td class="num">{{ number_format($row['minutes'], 2) }}</td>
                </tr>
              @endforeach
            </tbody>
            <tfoot>
              <tr>
                <th>Total</th>
                <th class="num">{{ number_format($current['calls']) }}</th>
                <th class="num">{{ number_format($current['minutes'], 2) }}</th>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-h"><div><h2>Month on month</h2></div></div>
      <div class="card-b">
        <dl class="kv">
          <dt>{{ $month }} calls</dt><dd>{{ number_format($current['calls']) }}</dd>
          <dt>{{ $month }} minutes</dt><dd>{{ number_format($current['minutes'], 2) }}</dd>
          <dt>Previous month calls</dt><dd>{{ number_format($previous['calls']) }}</dd>
          <dt>Previous month minutes</dt><dd>{{ number_format($previous['minutes'], 2) }}</dd>
          <dt>All time calls</dt><dd>{{ number_format($allTime['calls']) }}</dd>
          <dt>All time minutes</dt><dd>{{ number_format($allTime['minutes'], 2) }}</dd>
        </dl>
        <p class="text-xs muted mt-3">Minutes are billed per started minute of connected conversation.</p>
      </div>
    </div>

  </div>

  @if ($perAgent->count() > 1)
    <div class="card mt-4">
      <div class="card-h"><div><h2>By agent</h2><p>{{ $month }}</p></div></div>
      <div class="card-b p-0">
        <div class="table-wrap">
          <table class="table">
            <thead><tr><th>Agent</th><th class="num">Calls</th><th class="num">Minutes</th></tr></thead>
            <tbody>
              @foreach ($perAgent as $row)
                <tr>
                  <td class="cell-main">{{ $row['agent'] }}</td>
                  <td class="num">{{ number_format($row['calls']) }}</td>
                  <td class="num">{{ number_format($row['minutes'], 2) }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
  @endif

@endif

@endsection
