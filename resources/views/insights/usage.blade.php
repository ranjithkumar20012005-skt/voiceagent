@extends('layouts.app')

@section('title', 'Usage')

@php
  $delta = function (int $now, int $before) {
      if ($before === 0) return $now > 0 ? 'New this month' : 'No activity last month';
      $pct = round(($now - $before) / $before * 100);
      return ($pct >= 0 ? '+' : '') . $pct . '% vs last month';
  };
@endphp

@section('content')

<div class="ph">
  <div class="ph-main">
    <div class="eyebrow">Settings</div>
    <h1>Usage</h1>
    <p class="ph-sub">Calls and conversation minutes for {{ $month }}, counted from recorded calls. Billing rates are agreed with your provider and are not stored here.</p>
  </div>
</div>

<div class="grid cols-4 mb-section">
  <x-stat label="Calls this month" :value="number_format($current['total_calls'])" icon="icon-phone" :hint="$delta($current['total_calls'], $previous['total_calls'])" />
  <x-stat label="Conversation minutes" :value="number_format($current['minutes'])" icon="icon-clock" tone="teal" :hint="$delta($current['minutes'], $previous['minutes'])" />
  <x-stat label="Connected calls" :value="number_format($current['connected'])" icon="icon-phone-call" tone="ink" :hint="($current['connect_rate'] ?? 0) . '% connect rate'" />
  <x-stat label="All-time minutes" :value="number_format($allTime['minutes'])" icon="icon-history" tone="ink" :hint="number_format($allTime['total_calls']) . ' calls in total'" />
</div>

<div class="split-even">
  <div class="card">
    <div class="card-h"><div><h2>Daily calls</h2><p>{{ $month }} to date</p></div></div>
    <div class="card-b">
      @if ($series['has_data'])
        <div class="chart-box"><canvas id="usageChart" role="img" aria-label="Calls per day this month"></canvas></div>
      @else
        <x-empty icon="icon-chart-column" title="No calls this month" compact>Usage appears here as calls are placed.</x-empty>
      @endif
    </div>
  </div>

  <div class="card">
    <div class="card-h"><div><h2>By agent</h2><p>{{ $month }}</p></div></div>
    @if ($agents->isEmpty())
      <x-empty icon="icon-bot" title="No usage yet" compact />
    @else
      <div class="table-wrap">
        <table class="table">
          <thead><tr><th>Agent</th><th class="num">Calls</th><th class="num">Minutes</th></tr></thead>
          <tbody>
            @foreach ($agents as $a)
              <tr><td class="cell-main">{{ $a['name'] }}</td><td class="num">{{ number_format($a['total']) }}</td><td class="num">{{ number_format($a['minutes']) }}</td></tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
    <div class="card-f"><span class="text-xs muted">Minutes are summed per call from the duration the platform reports, rounded up.</span></div>
  </div>
</div>

@endsection

@push('scripts')
@if ($series['has_data'])
<script src="{{ asset('assets/js/vendor/chart.umd.min.js') }}"></script>
<script>
(function () {
  AppCharts.defaults();
  var s = @json($series);
  new Chart(document.getElementById('usageChart'), {
    type: 'line',
    data: { labels: s.labels, datasets: [{ label: 'Calls', data: s.total, borderColor: '#00A89D', backgroundColor: 'rgba(0,168,157,.08)', fill: true, tension: .3, pointRadius: 2 }] },
    options: { scales: { x: { grid: { display: false }, ticks: { maxTicksLimit: 10, maxRotation: 0 } }, y: { beginAtZero: true, ticks: { precision: 0 } } } },
  });
})();
</script>
@endif
@endpush
