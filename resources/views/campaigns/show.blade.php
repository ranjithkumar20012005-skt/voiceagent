@extends('layouts.app')

@section('title', $campaign->name)

@php use App\Support\LeadOutcome; @endphp

@section('content')

<div class="ph">
  <div class="ph-main">
    <a href="{{ route('campaigns.index') }}" class="ph-back"><i class="icon-arrow-left"></i> Campaigns</a>
    <h1>{{ $campaign->name }}</h1>
    <div class="ph-meta">
      <span class="badge {{ $campaign->status_badge }}" id="campaignStatus"><span class="dot"></span>{{ ucfirst($campaign->status) }}</span>
      <span>{{ number_format($campaign->total_contacts) }} contacts</span>
      <span>· Created {{ $campaign->created_at->format('d M Y, g:i A') }}</span>
      @if ($campaign->description)<span>· {{ $campaign->description }}</span>@endif
    </div>
  </div>

  @if ($campaign->isRemote())
    <div class="ph-actions">
      <button type="button" class="btn btn-secondary btn-sm" data-campaign-action="pause"><i class="icon-pause"></i> Pause</button>
      <button type="button" class="btn btn-secondary btn-sm" data-campaign-action="resume"><i class="icon-play"></i> Resume</button>
      <button type="button" class="btn btn-danger btn-sm" data-campaign-action="cancel"><i class="icon-circle-x"></i> Cancel</button>
    </div>
  @endif
</div>

@if ($campaign->error_message)
  <div class="callout callout-danger mb-5"><i class="icon-circle-alert"></i><div>{{ $campaign->error_message }}</div></div>
@endif

<div id="campaignAlert" hidden></div>

@if ($stats)
  @php
    $target    = max((int) $campaign->total_contacts, (int) $stats->total);
    $completed = (int) $stats->completed;
    $failed    = (int) $stats->failed + (int) $stats->no_answer + (int) $stats->busy;
    $percent   = $target > 0 ? round($completed / $target * 100, 1) : 0;
  @endphp

  <div class="grid cols-4 mb-section">
    <x-stat label="Total contacts" :value="number_format($target)" icon="icon-users" tone="ink" />
    <x-stat label="Completed" :value="number_format($completed)" icon="icon-circle-check" value-tone="good" :hint="$percent . '% of the list'" />
    <x-stat label="Not reached" :value="number_format($failed)" icon="icon-phone-missed" tone="red" :value-tone="$failed ? 'bad' : null"
            :hint="(int) $stats->no_answer . ' no answer · ' . (int) $stats->busy . ' busy · ' . (int) $stats->failed . ' failed'" />
    <x-stat label="Remaining" :value="number_format(max(0, $target - $completed))" icon="icon-clock" tone="amber" />
  </div>

  <div class="card mb-section">
    <div class="card-h">
      <div><h2>Progress</h2><p>{{ $completed }} of {{ $target }} calls completed</p></div>
      <span class="badge badge-teal">{{ $percent }}%</span>
    </div>
    <div class="card-b">
      <div class="progress thick"><div class="progress-fill" style="width: {{ $percent }}%"></div></div>
      <div class="meter mt-4">
        <div class="meter-cell"><span>Connected</span><strong class="text-good">{{ (int) $stats->connected }}</strong></div>
        <div class="meter-cell"><span>No answer</span><strong>{{ (int) $stats->no_answer }}</strong></div>
        <div class="meter-cell"><span>Busy</span><strong>{{ (int) $stats->busy }}</strong></div>
        <div class="meter-cell"><span>Failed</span><strong class="{{ $stats->failed ? 'text-bad' : '' }}">{{ (int) $stats->failed }}</strong></div>
      </div>
    </div>
  </div>
@else
  <div class="card mb-section">
    <x-empty icon="icon-loader-circle" title="Not dispatched yet">
      Progress appears once the contacts have been queued with the agent. Make sure the queue worker is running.
    </x-empty>
  </div>
@endif

@if ($attempts)
  <div class="card">
    <div class="card-h"><div><h2>Results</h2><p>Every call attempt in this campaign</p></div></div>
    @if ($attempts->isEmpty())
      <x-empty icon="icon-phone" title="No attempts recorded yet" compact>Results appear here as the agent places calls.</x-empty>
    @else
      <div class="table-wrap">
        <table class="table">
          <thead>
            <tr><th>Customer</th><th>Status</th><th>Outcome</th><th class="num">Duration</th><th>When</th><th class="end"></th></tr>
          </thead>
          <tbody>
            @foreach ($attempts as $attempt)
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
      @if ($attempts->hasPages())
        <div class="card-f">{{ $attempts->links() }}</div>
      @endif
    @endif
  </div>
@endif

@endsection

@push('scripts')
@if ($campaign->isRemote())
<script>
(function () {
  var csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
  var url = @json(route('campaigns.status', $campaign));
  var box = document.getElementById('campaignAlert');

  function say(ok, message) {
    box.className = 'callout mb-5 callout-' + (ok ? 'success' : 'danger');
    box.textContent = message;
    box.hidden = false;
  }

  document.querySelectorAll('[data-campaign-action]').forEach(function (btn) {
    btn.addEventListener('click', async function () {
      var action = btn.dataset.campaignAction;
      if (action === 'cancel' && !confirm('Cancel this campaign? Remaining calls will not be placed.')) return;

      btn.disabled = true;
      try {
        var res = await fetch(url, {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' },
          body: JSON.stringify({ action: action }),
        });
        var data = await res.json().catch(function () { return {}; });
        say(!!data.ok, data.message || 'Request failed.');

        if (data.ok && data.status) {
          var badge = document.getElementById('campaignStatus');
          if (badge) badge.lastChild.textContent = data.status.charAt(0).toUpperCase() + data.status.slice(1);
        }
      } catch (e) {
        say(false, 'Network error. Please try again.');
      } finally {
        btn.disabled = false;
      }
    });
  });
})();
</script>
@endif
@endpush
