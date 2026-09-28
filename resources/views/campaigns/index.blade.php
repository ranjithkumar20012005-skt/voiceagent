@extends('layouts.app')

@section('title', 'Campaigns')

@section('content')

<div class="ph">
  <div class="ph-main">
    <div class="eyebrow">Calling</div>
    <h1>Campaigns</h1>
    <p class="ph-sub">Bulk calling runs. The agent works through a customer list at a controlled pace and records every result.</p>
  </div>
  <div class="ph-actions">
    <a href="{{ route('imports.index') }}" class="btn btn-secondary btn-sm"><i class="icon-upload"></i> Import customers</a>
    <button type="button" class="btn btn-primary btn-sm" data-modal-open="campaignModal" @disabled(! $configured)><i class="icon-plus"></i> New Campaign</button>
  </div>
</div>

@unless ($configured)
  <div class="callout callout-warning mb-5">
    <i class="icon-circle-alert"></i><div>The voice service is not configured on the server, so campaigns cannot be dispatched yet.</div>
  </div>
@endunless

<div class="card">
  @if ($campaigns->isEmpty())
    <x-empty icon="icon-megaphone" title="No campaigns yet">
      Import a customer list, then create a campaign to call everyone on it.
      <x-slot:action>
        <div class="row" style="justify-content:center">
          <a href="{{ route('imports.index') }}" class="btn btn-secondary btn-sm">Import customers</a>
          <button type="button" class="btn btn-primary btn-sm" data-modal-open="campaignModal" @disabled(! $configured)>New Campaign</button>
        </div>
      </x-slot:action>
    </x-empty>
  @else
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>Campaign</th>
            <th>Status</th>
            <th class="num">Contacts</th>
            <th class="num">Completed</th>
            <th class="num">Not reached</th>
            <th class="num">Remaining</th>
            <th style="min-width:160px">Progress</th>
            <th class="end"></th>
          </tr>
        </thead>
        <tbody>
          @foreach ($campaigns as $campaign)
            @php
              $s = $stats->get($campaign->sarvam_campaign_id);
              $completed = (int) ($s->completed ?? 0);
              $failed = (int) ($s->failed ?? 0);
              $target = (int) $campaign->total_contacts;
              $pct = $target > 0 ? min(100, round($completed / $target * 100)) : 0;
            @endphp
            <tr>
              <td>
                <a href="{{ route('campaigns.show', $campaign) }}" class="cell-main">{{ $campaign->name }}</a>
                <span class="cell-sub">
                  Created {{ $campaign->created_at->diffForHumans() }}
                  @if ($campaign->automation_id) · <i class="icon-zap"></i> Automation @endif
                </span>
                @if ($campaign->error_message)
                  <span class="cell-sub text-bad">{{ Str::limit($campaign->error_message, 80) }}</span>
                @endif
              </td>
              <td><span class="badge {{ $campaign->status_badge }}"><span class="dot"></span>{{ ucfirst($campaign->status) }}</span></td>
              <td class="num">{{ number_format($target) }}</td>
              <td class="num">{{ number_format($completed) }}</td>
              <td class="num {{ $failed ? 'text-bad' : '' }}">{{ number_format($failed) }}</td>
              <td class="num">{{ number_format(max(0, $target - $completed)) }}</td>
              <td>
                <div class="row">
                  <div class="progress thin" style="flex:1"><div class="progress-fill" style="width: {{ $pct }}%"></div></div>
                  <span class="text-xs muted" style="width:34px;text-align:right">{{ $pct }}%</span>
                </div>
              </td>
              <td class="end"><a href="{{ route('campaigns.show', $campaign) }}" class="btn btn-secondary btn-xs">Open</a></td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    @if ($campaigns->hasPages())
      <div class="card-f">{{ $campaigns->links() }}</div>
    @endif
  @endif
</div>

{{-- ------------------------------------------------------ Create modal --}}
<div class="modal" id="campaignModal" role="dialog" aria-modal="true" aria-labelledby="campaignModalTitle" aria-hidden="true">
  <div class="modal-scrim" data-modal-close></div>
  <div class="modal-panel">
    <form method="POST" action="{{ route('campaigns.store') }}">
      @csrf
      <div class="modal-h">
        <div>
          <h2 id="campaignModalTitle">New campaign</h2>
          <p>Contacts are sent to the agent as soon as you create it.</p>
        </div>
        <button type="button" class="btn btn-ghost btn-icon btn-sm" data-modal-close aria-label="Close"><i class="icon-x"></i></button>
      </div>

      <div class="modal-b">
        <div class="field">
          <label class="label" for="cName">Name <span class="req">*</span></label>
          <input type="text" name="name" id="cName" class="input" maxlength="50" required value="{{ old('name') }}" placeholder="October renewals">
        </div>

        <div class="field">
          <label class="label" for="cDesc">Description</label>
          <input type="text" name="description" id="cDesc" class="input" maxlength="150" value="{{ old('description') }}">
        </div>

        <div class="field">
          <label class="label" for="campaignSource">Who to call</label>
          <select name="source" class="select" id="campaignSource">
            <option value="import_batch" @selected(old('source', 'import_batch') === 'import_batch')>Customers from an import</option>
            <option value="filtered" @selected(old('source') === 'filtered')>Policies expiring soon</option>
          </select>
        </div>

        <div class="field" id="batchField">
          <label class="label" for="cBatch">Import</label>
          <select name="import_batch_id" id="cBatch" class="select">
            <option value="">— Select an import —</option>
            @foreach ($batches as $batch)
              <option value="{{ $batch->id }}" @selected((old('import_batch_id') ?? request('import_batch_id')) == $batch->id)>
                {{ $batch->original_filename }} ({{ $batch->valid_rows }} valid)
              </option>
            @endforeach
          </select>
          @if ($batches->isEmpty())
            <div class="hint">No completed imports yet — <a href="{{ route('imports.index') }}">import customers</a> first.</div>
          @endif
        </div>

        <div class="field" id="expiryField" hidden>
          <label class="label" for="cExpiry">Expiring within (days)</label>
          <input type="number" name="expiry_within_days" id="cExpiry" class="input" value="{{ old('expiry_within_days', 30) }}" min="0" max="365">
          <div class="hint">Only pending, callable customers are included.</div>
        </div>

        <div class="field">
          <label class="label" for="cRate">Calls per second</label>
          <input type="number" name="attempts_per_second" id="cRate" class="input"
                 value="{{ old('attempts_per_second', config('sarvam.campaign.attempts_per_second')) }}" step="0.1" min="0.1" max="500">
          <div class="hint">Kept low by default to protect answer rates. Do-not-call customers are always skipped.</div>
        </div>
      </div>

      <div class="modal-f">
        <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
        <button type="submit" class="btn btn-primary" @disabled(! $configured)><i class="icon-play"></i> Create &amp; dispatch</button>
      </div>
    </form>
  </div>
</div>

@endsection

@push('scripts')
<script>
(function () {
  var source = document.getElementById('campaignSource');
  function sync() {
    var filtered = source.value === 'filtered';
    document.getElementById('batchField').hidden = filtered;
    document.getElementById('expiryField').hidden = !filtered;
  }
  source.addEventListener('change', sync);
  sync();

  // Arriving from an import ("Start campaign") or after a validation error: open the form.
  @if (request('import_batch_id') || $errors->any())
    AppModal.open(document.getElementById('campaignModal'));
  @endif
})();
</script>
@endpush
