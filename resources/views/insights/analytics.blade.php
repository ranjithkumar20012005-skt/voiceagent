@extends('layouts.app')

@section('title', 'Analytics')

@section('content')

<div class="ph">
  <div class="ph-main">
    <div class="eyebrow">Insights</div>
    <h1>Analytics</h1>
    <p class="ph-sub">How your calling is performing. Every figure is counted from recorded calls — nothing is estimated.</p>
  </div>
  <div class="tabs-pill" role="tablist" aria-label="Date range">
    @foreach ($ranges as $key => [$label])
      <a href="{{ route('analytics.index', ['range' => $key]) }}" class="{{ $range === $key ? 'active' : '' }}" role="tab" aria-selected="{{ $range === $key ? 'true' : 'false' }}">{{ $label }}</a>
    @endforeach
  </div>
</div>

@if ($totals['total_calls'] === 0)
  <div class="card">
    <x-empty icon="icon-chart-column" title="No calls in this period">
      Analytics fill in as calls are placed. Try a longer range, or place a call.
      <x-slot:action>
        <button type="button" class="btn btn-primary btn-sm" data-new-call><i class="icon-phone"></i> New Call</button>
      </x-slot:action>
    </x-empty>
  </div>
@else

{{-- Call outcome of the attempt itself: did it reach anyone. --}}
<div class="grid cols-5 mb-4">
  <x-stat label="Total calls" :value="number_format($totals['total_calls'])" icon="icon-phone" :hint="$totals['in_flight'] ? $totals['in_flight'] . ' in progress' : null" />
  <x-stat label="Answered" :value="number_format($totals['connected'])" icon="icon-phone-call" tone="teal" value-tone="good"
          :hint="($totals['connect_rate'] ?? 0) . '% answered'" />
  <x-stat label="No answer" :value="number_format($totals['no_answer'] + $totals['busy'])" icon="icon-phone-missed" tone="amber"
          :hint="$totals['busy'] ? $totals['busy'] . ' busy' : null" />
  <x-stat label="Failed" :value="number_format($totals['failed'])" icon="icon-circle-alert" tone="red" />
  <x-stat label="Avg. duration" :value="$totals['avg_duration_human'] ?? '—'" icon="icon-timer" tone="ink" :hint="$totals['talk_time_human'] . ' total'" />
</div>

{{-- What the conversation produced. --}}
<div class="grid cols-5 mb-section">
  <x-stat label="Interested" :value="number_format($totals['leads'])" icon="icon-user-check" value-tone="good" />
  <x-stat label="Not interested" :value="number_format($totals['not_interested'])" icon="icon-user-x" />
  <x-stat label="Conversion rate" :value="$totals['lead_rate'] !== null ? $totals['lead_rate'] . '%' : '—'"
          icon="icon-trending-up"
          :hint="$totals['lead_rate'] !== null ? 'Interested out of answered' : 'No answered calls yet'" />
  <x-stat label="Callbacks" :value="number_format($callbacks)" icon="icon-calendar-clock" tone="amber"
          :hint="$totals['callbacks_requested'] . ' requested on calls'" />
  <x-stat label="Talk time" :value="number_format($totals['minutes'])" icon="icon-clock" tone="ink" hint="minutes, rounded up" />
</div>

<div class="split-even mb-section">
  <div class="card">
    <div class="card-h">
      <div><h2>Calls over time</h2><p>Last {{ count($series['labels']) }} days</p></div>
      <div class="legend">
        <span><i style="background:#65B82E"></i>Connected</span>
        <span><i style="background:#f4a3a2"></i>Not reached</span>
        <span><i style="background:#cfd6d2"></i>Other</span>
      </div>
    </div>
    <div class="card-b"><div class="chart-box"><canvas id="seriesChart" role="img" aria-label="Calls per day"></canvas></div></div>
  </div>

  <div class="card">
    <div class="card-h"><div><h2>Call outcomes</h2><p>As reported by the agent</p></div></div>
    <div class="card-b">
      @if ($outcomes)
        <div class="chart-box sm"><canvas id="outcomeChart" role="img" aria-label="Call outcomes"></canvas></div>
        <div class="legend mt-4" id="outcomeLegend"></div>
      @else
        <x-empty icon="icon-list-checks" title="No outcomes yet" compact>Outcomes appear once conversations finish.</x-empty>
      @endif
    </div>
  </div>
</div>

<div class="split-even">
  <div class="card">
    <div class="card-h"><div><h2>Campaign performance</h2><p>Most recent campaigns</p></div><a href="{{ route('campaigns.index') }}" class="btn btn-ghost btn-xs">All →</a></div>
    @if ($campaigns->isEmpty())
      <x-empty icon="icon-megaphone" title="No campaigns yet" compact>Campaign results appear here once a bulk run is dispatched.</x-empty>
    @else
      <div class="table-wrap">
        <table class="table">
          <thead><tr><th>Campaign</th><th class="num">Contacts</th><th class="num">Connected</th><th class="num">Not reached</th><th class="num">Leads</th><th style="min-width:120px">Progress</th></tr></thead>
          <tbody>
            @foreach ($campaigns as $c)
              <tr>
                <td><a href="{{ route('campaigns.show', $c['id']) }}" class="cell-main">{{ $c['name'] }}</a><span class="cell-sub">{{ ucfirst($c['status']) }}</span></td>
                <td class="num">{{ number_format($c['target']) }}</td>
                <td class="num">{{ number_format($c['connected']) }}</td>
                <td class="num">{{ number_format($c['unreached']) }}</td>
                <td class="num text-good">{{ number_format($c['leads']) }}</td>
                <td><div class="row"><div class="progress thin" style="flex:1"><div class="progress-fill" style="width: {{ $c['percent'] }}%"></div></div><span class="text-xs muted">{{ round($c['percent']) }}%</span></div></td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>

  <div class="card">
    <div class="card-h"><div><h2>By agent</h2><p>Calls in this period</p></div></div>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>Agent</th><th class="num">Calls</th><th class="num">Answered</th><th class="num">Interested</th><th class="num">Minutes</th></tr></thead>
        <tbody>
          @foreach ($agents as $a)
            <tr>
              <td class="cell-main">{{ $a['name'] }}</td>
              <td class="num">{{ number_format($a['total']) }}</td>
              <td class="num">{{ number_format($a['connected']) }}</td>
              <td class="num text-good">{{ number_format($a['leads']) }}</td>
              <td class="num">{{ number_format($a['minutes']) }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>

{{-- ------------------------------------------- Answered vs not, by day --}}
@if ($answerTrend->isNotEmpty())
  <div class="card mb-section">
    <div class="card-h"><div><h2>Answered vs not answered</h2><p>By day, in this period</p></div></div>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>Day</th><th class="num">Answered</th><th class="num">Not answered</th><th class="num">Answer rate</th></tr></thead>
        <tbody>
          @foreach ($answerTrend as $row)
            @php $dayTotal = $row['answered'] + $row['unanswered']; @endphp
            <tr>
              <td>{{ $row['day'] }}</td>
              <td class="num text-good">{{ number_format($row['answered']) }}</td>
              <td class="num">{{ number_format($row['unanswered']) }}</td>
              {{-- A day with no classified calls has no rate to show. --}}
              <td class="num">{{ $dayTotal > 0 ? round($row['answered'] / $dayTotal * 100) . '%' : '—' }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
@endif

{{-- ------------------------------------------------------- By language --}}
{{--
    Shown only when calls have actually recorded a language. With none, the
    section is omitted rather than rendering a table of unknowns.
--}}
@if ($byLanguage->isNotEmpty())
  <div class="card">
    <div class="card-h"><div><h2>By language</h2><p>Where the conversation language was recorded</p></div></div>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>Language</th><th class="num">Calls</th><th class="num">Answered</th><th class="num">Interested</th></tr></thead>
        <tbody>
          @foreach ($byLanguage as $row)
            <tr>
              <td class="cell-main">{{ $row['language'] }}</td>
              <td class="num">{{ number_format($row['calls']) }}</td>
              <td class="num">{{ number_format($row['answered']) }}</td>
              <td class="num text-good">{{ number_format($row['interested']) }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
@endif
@endif

@endsection

@push('scripts')
@if ($totals['total_calls'] > 0)
<script src="{{ asset('assets/js/vendor/chart.umd.min.js') }}"></script>
<script>
(function () {
  AppCharts.defaults();
  var s = @json($series);
  var other = s.total.map(function (t, i) { return Math.max(0, t - s.connected[i] - s.failed[i]); });

  new Chart(document.getElementById('seriesChart'), {
    type: 'bar',
    data: {
      labels: s.labels,
      datasets: [
        { label: 'Connected', data: s.connected, backgroundColor: '#65B82E', borderRadius: 3, maxBarThickness: 22 },
        { label: 'Not reached', data: s.failed, backgroundColor: '#f4a3a2', borderRadius: 3, maxBarThickness: 22 },
        { label: 'Other', data: other, backgroundColor: '#cfd6d2', borderRadius: 3, maxBarThickness: 22 },
      ],
    },
    options: {
      interaction: { mode: 'index', intersect: false },
      scales: {
        x: { stacked: true, grid: { display: false }, ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 10 } },
        y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } },
      },
    },
  });

  var outcomes = @json(array_values($outcomes));
  var el = document.getElementById('outcomeChart');
  if (el && outcomes.length) {
    var colors = { 'Interested': '#65B82E', 'Follow-up': '#00A89D', 'Not Interested': '#e34948', 'Already Completed': '#7a5af8', 'Wrong Person': '#eda100', 'Do Not Contact': '#b42318', 'Needs Attention': '#f79009', 'No Outcome': '#98a2b3' };
    var bg = outcomes.map(function (o) { return colors[o.label] || '#98a2b3'; });

    new Chart(el, {
      type: 'doughnut',
      data: { labels: outcomes.map(function (o) { return o.label; }), datasets: [{ data: outcomes.map(function (o) { return o.count; }), backgroundColor: bg, borderWidth: 2, borderColor: '#fff' }] },
      options: { cutout: '68%' },
    });

    var legend = document.getElementById('outcomeLegend');
    outcomes.forEach(function (o, i) {
      var span = document.createElement('span');
      var dot = document.createElement('i');
      dot.style.background = bg[i];
      span.appendChild(dot);
      span.appendChild(document.createTextNode(o.label + ' · ' + o.count));
      legend.appendChild(span);
    });
  }
})();
</script>
@endif
@endpush
