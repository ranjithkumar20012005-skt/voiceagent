@extends('layouts.app')

@section('title', 'Dashboard')

@php use App\Support\LeadOutcome; @endphp

@section('content')

<div class="ph">
  <div class="ph-main">
    <div class="eyebrow">Overview</div>
    <h1>Welcome back, {{ Str::before(auth()->user()->name, ' ') }}</h1>
    <p class="ph-sub">Monitor AI calling activity and customer outcomes.</p>
  </div>
  <div class="ph-actions">
    <span class="text-xs muted hide-sm" id="lastUpdated">{{ $live ? 'Live — refreshing while calls are in progress' : '' }}</span>
    <a href="{{ route('analytics.index') }}" class="btn btn-secondary btn-sm"><i class="icon-chart-column"></i> Analytics</a>
    <button type="button" class="btn btn-primary btn-sm" data-new-call><i class="icon-phone"></i> New Call</button>
  </div>
</div>

@unless ($configured)
  <div class="callout callout-warning mb-5">
    <i class="icon-circle-alert"></i>
    <div>Calling is not available yet. An administrator needs to finish the server setup — see <a href="{{ route('providers.index') }}">Providers</a>.</div>
  </div>
@endunless

{{-- ------------------------------------------------------------ Today --}}
<div class="section-label"><h2>Today</h2></div>
<div class="grid cols-5 mb-section">
  <x-stat label="Calls Today" :value="$kpis['calls_today']" icon="icon-phone-outgoing" key="calls_today" />
  <x-stat label="Connected" :value="$kpis['connected']" icon="icon-phone-call" tone="teal" key="connected" />
  <x-stat label="Hot Leads" :value="$kpis['hot_leads']" icon="icon-user-check" key="hot_leads" value-tone="good" />
  <x-stat label="Follow-ups" :value="$kpis['follow_ups']" icon="icon-calendar-clock" tone="amber" key="follow_ups" />
  <x-stat label="Total Minutes" :value="$kpis['total_minutes']" icon="icon-clock" tone="ink" key="total_minutes"
          :hint="'Average call ' . ($kpis['avg_duration_human'] ?? 'unavailable')" />
</div>

{{-- --------------------------------------------------------- All time --}}
<div class="section-label"><h2>All time</h2><a href="{{ route('calls.index') }}">Call logs →</a></div>
<div class="grid cols-4 mb-section">
  <x-stat label="Total Calls" :value="number_format($totals['total_calls'])" icon="icon-phone"
          :hint="$totals['connect_rate'] !== null ? $totals['connect_rate'] . '% connected' : 'No calls yet'" />
  <x-stat label="Failed Calls" :value="number_format($totals['failed'])" icon="icon-phone-missed" tone="red"
          :value-tone="$totals['failed'] > 0 ? 'bad' : null"
          :hint="number_format($totals['no_answer']) . ' no answer · ' . number_format($totals['busy']) . ' busy'" />
  <x-stat label="Avg. Duration" :value="$totals['avg_duration_human'] ?? '—'" icon="icon-timer" tone="teal"
          :hint="$totals['talk_time_human'] . ' total talk time'" />
  <x-stat label="Customers" :value="number_format($customers['customers'])" icon="icon-users" tone="ink"
          :hint="number_format($customers['never_called']) . ' not called yet'" />
</div>

{{-- ------------------------------------------------------ Charts row --}}
<div class="split-even mb-section">
  <div class="card">
    <div class="card-h">
      <div>
        <h2>Calls over time</h2>
        <p>Last 14 days · connected vs. not reached</p>
      </div>
      @if ($series['has_data'])
        <div class="legend">
          <span><i style="background:#65B82E"></i>Connected</span>
          <span><i style="background:#f4a3a2"></i>Not reached</span>
          <span><i style="background:#cfd6d2"></i>Other</span>
        </div>
      @endif
    </div>
    <div class="card-b">
      @if ($series['has_data'])
        <div class="chart-box"><canvas id="callsChart" aria-label="Calls per day for the last 14 days" role="img"></canvas></div>
      @else
        <x-empty icon="icon-chart-column" title="No calls in the last 14 days" compact>
          The chart fills in as calls are placed.
        </x-empty>
      @endif
    </div>
  </div>

  <div class="card">
    <div class="card-h">
      <div>
        <h2>Call outcomes</h2>
        <p>What the agent reported, all time</p>
      </div>
      <span class="badge">{{ number_format($overview['total']) }} calls</span>
    </div>
    <div class="card-b">
      @php $outcomeTotal = array_sum(array_column($outcomes, 'count')); @endphp
      @if ($outcomeTotal > 0)
        @foreach ($outcomes as $key => $row)
          @php
            $pct = round($row['count'] / $outcomeTotal * 100, 1);
            $fill = match ($key) {
              'interested' => '', 'callback' => 'teal', 'not_interested', 'do_not_call' => 'red',
              'already_renewed' => 'violet', 'wrong_person', 'escalated' => 'amber', default => 'ink',
            };
          @endphp
          <div class="bar-row">
            <div class="bar-row-head">
              <span>{{ $row['label'] }}</span>
              <span>{{ $row['count'] }}<small>{{ $pct }}%</small></span>
            </div>
            <div class="progress thin"><div class="progress-fill {{ $fill }}" style="width: {{ $pct }}%"></div></div>
          </div>
        @endforeach
      @else
        <x-empty icon="icon-list-checks" title="No outcomes yet" compact>
          Outcomes appear once the agent finishes a conversation.
        </x-empty>
      @endif
    </div>
  </div>
</div>

<div class="split mb-section">
  {{-- ------------------------------------------------------- Hot leads --}}
  <div class="card">
    <div class="card-h">
      <div>
        <h2>Hot Leads</h2>
        <p>Customers the agent identified as interested.</p>
      </div>
      @if (count($hotLeads))
        <a href="{{ route('leads.index') }}" class="btn btn-secondary btn-sm">View all</a>
      @endif
    </div>

    @forelse ($hotLeads as $lead)
      @php $name = $lead->customer?->name ?: 'Unknown customer'; @endphp
      <div class="list-row">
        <span class="avatar lg soft">{{ Str::upper(Str::substr(trim($name), 0, 1)) ?: '?' }}</span>
        <div class="list-main">
          <div class="list-title">
            {{ $name }}
            <span class="badge {{ LeadOutcome::badge($lead->call_disposition) }}">{{ LeadOutcome::hotHeadline($lead) }}</span>
          </div>
          <div class="list-meta">{{ $lead->display_phone }} · {{ $lead->created_at->format('d M, g:i A') }} · {{ $lead->duration_for_humans }}</div>
        </div>
        <div class="list-actions">
          <a href="{{ route('calls.show', $lead) }}" class="btn btn-secondary btn-xs">View Call</a>
          @if ($lead->customer && ! $lead->customer->do_not_call)
            <button type="button" class="btn btn-subtle btn-xs"
                    data-new-call
                    data-customer-id="{{ $lead->customer->id }}"
                    data-name="{{ $lead->customer->name }}"
                    data-phone="{{ $lead->customer->phone_number }}"
                    data-policy="{{ $lead->customer->policy_number }}">
              <i class="icon-phone"></i> Call again
            </button>
          @endif
        </div>
      </div>
    @empty
      <x-empty icon="icon-user-check" title="No hot leads yet">
        Interested customers appear here after a call.
      </x-empty>
    @endforelse
  </div>

  <div class="stack">
    {{-- ----------------------------------------------- Campaign progress --}}
    <div class="card">
      <div class="card-h">
        <div>
          <h2>Campaign progress</h2>
          <p>{{ $campaign ? $campaign['name'] : 'Most recent bulk run' }}</p>
        </div>
        @if ($campaign)
          <span class="badge {{ (new \App\Models\Campaign(['status' => $campaign['status']]))->status_badge }}">{{ ucfirst($campaign['status']) }}</span>
        @endif
      </div>
      <div class="card-b">
        @if ($campaign)
          <div class="bar-row-head">
            <span>{{ $campaign['completed'] }} of {{ $campaign['target'] }} completed</span>
            <span>{{ $campaign['percent'] }}%</span>
          </div>
          <div class="progress"><div class="progress-fill" style="width: {{ $campaign['percent'] }}%"></div></div>
          <div class="meter mt-4">
            <div class="meter-cell"><span>Connected</span><strong>{{ $campaign['connected'] }}</strong></div>
            <div class="meter-cell"><span>No answer</span><strong>{{ $campaign['no_answer'] }}</strong></div>
            <div class="meter-cell"><span>Failed</span><strong>{{ $campaign['failed'] }}</strong></div>
            <div class="meter-cell"><span>Remaining</span><strong>{{ $campaign['remaining'] }}</strong></div>
          </div>
          <a href="{{ route('campaigns.show', $campaign['id']) }}" class="btn btn-secondary btn-sm btn-block mt-4">Open campaign</a>
        @else
          <x-empty icon="icon-megaphone" title="No campaigns yet" compact>
            Start a bulk run from an imported customer list.
            <x-slot:action>
              <a href="{{ route('campaigns.index') }}" class="btn btn-secondary btn-sm">Go to Campaigns</a>
            </x-slot:action>
          </x-empty>
        @endif
      </div>
    </div>

    {{-- ------------------------------------------------ Upcoming callbacks --}}
    <div class="card">
      <div class="card-h">
        <div>
          <h2>Upcoming callbacks</h2>
          <p>
            {{ $customers['pending_callbacks'] }} scheduled
            @if ($customers['overdue_callbacks'] > 0)
              · <span class="text-bad">{{ $customers['overdue_callbacks'] }} overdue</span>
            @endif
          </p>
        </div>
        <a href="{{ route('callbacks.index', $customers['overdue_callbacks'] > 0 ? ['range' => 'overdue'] : []) }}" class="btn btn-ghost btn-xs">All →</a>
      </div>
      @forelse ($callbacks as $customer)
        <div class="list-row">
          <div class="list-main">
            <div class="list-title"><a href="{{ route('customers.show', $customer) }}" class="cell-main">{{ $customer->name ?: 'Unnamed' }}</a></div>
            <div class="list-meta">{{ $customer->next_callback_at->format('d M, g:i A') }} · {{ $customer->next_callback_at->diffForHumans() }}</div>
          </div>
          <button type="button" class="btn btn-subtle btn-xs btn-icon" title="Call now" aria-label="Call {{ $customer->name }}"
                  @disabled($customer->do_not_call)
                  data-new-call data-customer-id="{{ $customer->id }}" data-name="{{ $customer->name }}"
                  data-phone="{{ $customer->phone_number }}" data-policy="{{ $customer->policy_number }}"
                  data-language="{{ $customer->preferred_language }}">
            <i class="icon-phone"></i>
          </button>
        </div>
      @empty
        <x-empty icon="icon-calendar-clock" title="Nothing scheduled" compact>
          Callbacks appear when a customer asks to be called later.
        </x-empty>
      @endforelse
    </div>
  </div>
</div>

{{-- --------------------------------------------------------- Recent calls --}}
<div class="card">
  <div class="card-h">
    <div>
      <h2>Recent Calls</h2>
      <p>The latest calls placed by your AI agent.</p>
    </div>
    <a href="{{ route('calls.index') }}" class="btn btn-secondary btn-sm">View all</a>
  </div>

  @if ($recent->isEmpty())
    <x-empty icon="icon-phone" title="No calls yet">
      Use New Call to place the first one.
      <x-slot:action>
        <button type="button" class="btn btn-primary btn-sm" data-new-call><i class="icon-phone"></i> New Call</button>
      </x-slot:action>
    </x-empty>
  @else
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr><th>Customer</th><th>Status</th><th>Outcome</th><th class="num">Duration</th><th>Time</th><th class="end"></th></tr>
        </thead>
        <tbody>
          @foreach ($recent as $attempt)
            <tr>
              <td>
                <span class="cell-main">{{ $attempt->customer?->name ?: 'Unknown' }}</span>
                <span class="cell-sub">{{ $attempt->display_phone }}</span>
              </td>
              <td><span class="badge {{ LeadOutcome::connectivityBadge($attempt) }}">{{ LeadOutcome::connectivityLabel($attempt) }}</span></td>
              <td><span class="badge {{ LeadOutcome::badge($attempt->call_disposition) }}">{{ LeadOutcome::label($attempt->call_disposition) }}</span></td>
              <td class="num">{{ $attempt->duration_for_humans }}</td>
              <td class="nowrap muted">{{ $attempt->created_at->diffForHumans() }}</td>
              <td class="end"><a href="{{ route('calls.show', $attempt) }}" class="btn btn-secondary btn-xs">View</a></td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif
</div>

@endsection

@push('scripts')
@if ($series['has_data'])
<script src="{{ asset('assets/js/vendor/chart.umd.min.js') }}"></script>
<script>
(function () {
  AppCharts.defaults();
  var s = @json($series);
  var other = s.total.map(function (t, i) { return Math.max(0, t - s.connected[i] - s.failed[i]); });

  new Chart(document.getElementById('callsChart'), {
    type: 'bar',
    data: {
      labels: s.labels,
      datasets: [
        { label: 'Connected', data: s.connected, backgroundColor: '#65B82E', borderRadius: 4, maxBarThickness: 28 },
        { label: 'Not reached', data: s.failed, backgroundColor: '#f4a3a2', borderRadius: 4, maxBarThickness: 28 },
        { label: 'Other', data: other, backgroundColor: '#cfd6d2', borderRadius: 4, maxBarThickness: 28 },
      ],
    },
    options: {
      interaction: { mode: 'index', intersect: false },
      scales: {
        x: { stacked: true, grid: { display: false }, ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 7 } },
        y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } },
      },
    },
  });
})();
</script>
@endif
<script>
(function () {
  // Poll today's tiles only while there is work in flight; otherwise idle.
  var statsUrl = @json(route('dashboard.stats'));
  var live = @json($live);
  var timer = null;

  async function refresh() {
    try {
      var res = await fetch(statsUrl, { headers: { 'Accept': 'application/json' } });
      if (!res.ok) return;
      var data = await res.json();

      Object.keys(data.kpis || {}).forEach(function (key) {
        document.querySelectorAll('[data-kpi="' + key + '"]').forEach(function (el) { el.textContent = data.kpis[key]; });
      });

      var stamp = document.getElementById('lastUpdated');
      if (stamp) stamp.textContent = 'Updated ' + new Date().toLocaleTimeString();

      if (live && !data.live) { live = false; clearInterval(timer); }
    } catch (e) { /* transient -- the next tick retries */ }
  }

  function start() { if (live && !timer) { timer = setInterval(refresh, 7000); } }
  function stop() { clearInterval(timer); timer = null; }

  start();
  document.addEventListener('visibilitychange', function () { document.hidden ? stop() : start(); });
})();
</script>
@endpush
