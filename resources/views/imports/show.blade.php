@extends('layouts.app')

@section('title', 'Import Result')

@php
  $running = in_array($batch->status, ['queued', 'processing'], true);
  $tone = match ($batch->status) {
      'completed' => 'badge-success', 'failed' => 'badge-danger',
      'pending_mapping' => 'badge-warning', 'queued', 'processing' => 'badge-teal', default => '',
  };
  $done = $batch->valid_rows + $batch->rejected_rows + $batch->duplicate_rows;
  $pct = $batch->total_rows > 0 ? min(100, round($done / $batch->total_rows * 100)) : ($batch->isFinished() ? 100 : 0);
@endphp

@section('content')

<div class="ph">
  <div class="ph-main">
    <a href="{{ route('imports.index') }}" class="ph-back"><i class="icon-arrow-left"></i> Imports</a>
    <h1 class="break">{{ $batch->original_filename }}</h1>
    <div class="ph-meta">
      <span class="badge {{ $tone }}" id="batchStatus">{{ $batch->status_label }}</span>
      <span>Uploaded {{ $batch->created_at->format('d M Y, g:i A') }}</span>
    </div>
  </div>
  <div class="ph-actions">
    @if ($batch->valid_rows > 0)
      <a href="{{ route('campaigns.index', ['import_batch_id' => $batch->id]) }}" class="btn btn-primary btn-sm"><i class="icon-megaphone"></i> Start campaign</a>
    @endif
    <form method="POST" action="{{ route('imports.destroy', $batch) }}" class="inline-form" data-confirm="Remove this import record? Imported customers are kept.">
      @csrf
      @method('DELETE')
      <button type="submit" class="btn btn-secondary btn-sm" @disabled($batch->status === 'processing')><i class="icon-trash-2"></i> Remove</button>
    </form>
  </div>
</div>

<div class="steps" aria-label="Import steps">
  <span class="step-chip done"><b><i class="icon-check"></i></b>Upload</span>
  <span class="step-chip done"><b><i class="icon-check"></i></b>Map columns</span>
  <span class="step-chip {{ $running ? 'current' : 'done' }}"><b>@if ($running) 3 @else <i class="icon-check"></i> @endif</b>Process</span>
  <span class="step-chip {{ $running ? '' : 'current' }}"><b>4</b>Review results</span>
</div>

@if ($batch->error_message)
  <div class="callout callout-danger mb-5"><i class="icon-circle-alert"></i><div><strong>Import failed.</strong> {{ $batch->error_message }}</div></div>
@endif

@if ($running)
  <div class="card mb-section">
    <div class="card-b">
      <div class="bar-row-head"><span><i class="icon-loader-circle spin"></i> Processing rows…</span><span id="importPct">{{ $pct }}%</span></div>
      <div class="progress"><div class="progress-fill" id="importBar" style="width: {{ $pct }}%"></div></div>
      <p class="hint">This page updates automatically. Imports run in the background queue worker.</p>
    </div>
  </div>
@endif

<div class="grid cols-4 mb-section">
  <x-stat label="Total rows" :value="$batch->total_rows" icon="icon-file-spreadsheet" tone="ink" />
  <x-stat label="Imported" :value="$batch->valid_rows" icon="icon-circle-check" value-tone="good" />
  <x-stat label="Rejected" :value="$batch->rejected_rows" icon="icon-circle-x" tone="red" :value-tone="$batch->rejected_rows ? 'bad' : null" />
  <x-stat label="Duplicates" :value="$batch->duplicate_rows" icon="icon-copy" tone="amber" />
</div>

<div class="{{ empty($batch->rejection_samples) ? '' : 'split-even' }}">
  @if (! empty($batch->rejection_samples))
    <div class="card">
      <div class="card-h">
        <div><h2>Validation errors</h2><p>Rejected and duplicate rows, with the reason</p></div>
        <span class="badge">showing {{ count($batch->rejection_samples) }}</span>
      </div>
      <div class="table-wrap">
        <table class="table">
          <thead><tr><th class="num">Line</th><th>Name</th><th>Phone</th><th>Reason</th></tr></thead>
          <tbody>
            @foreach ($batch->rejection_samples as $sample)
              <tr>
                <td class="num muted">{{ $sample['line'] ?? '—' }}</td>
                <td>{{ ($sample['name'] ?? null) ?: '—' }}</td>
                <td class="muted nowrap">{{ ($sample['phone'] ?? null) ?: '—' }}</td>
                <td class="text-bad">{{ $sample['reason'] ?? '' }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  @endif

  <div class="card">
    <div class="card-h">
      <div><h2>Imported customers</h2><p>The first 25 from this file</p></div>
      <a href="{{ route('customers.index') }}" class="btn btn-secondary btn-sm">All customers</a>
    </div>
    @if ($customers->isEmpty())
      <x-empty icon="icon-users" title="{{ $running ? 'Import in progress…' : 'No customers imported' }}" compact>
        {{ $running ? 'Customers appear here as rows are processed.' : 'No customers were imported from this file.' }}
      </x-empty>
    @else
      <div class="table-wrap">
        <table class="table">
          <thead><tr><th>Name</th><th>Phone</th><th>Policy</th><th>Expiry</th></tr></thead>
          <tbody>
            @foreach ($customers as $customer)
              <tr>
                <td><a href="{{ route('customers.show', $customer) }}" class="cell-main">{{ $customer->name ?: 'Unnamed' }}</a></td>
                <td class="muted nowrap">{{ $customer->display_phone }}</td>
                <td class="muted">{{ $customer->policy_number ?: '—' }}</td>
                <td class="muted nowrap">{{ $customer->policy_expiry_date?->format('d M Y') ?: '—' }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>
</div>

@endsection

@push('scripts')
@if ($running)
<script>
(function () {
  var url = @json(route('imports.status', $batch));
  var timer = setInterval(async function () {
    try {
      var res = await fetch(url, { headers: { 'Accept': 'application/json' } });
      if (!res.ok) return;
      var data = await res.json();

      var done = (data.valid || 0) + (data.rejected || 0) + (data.duplicates || 0);
      var pct = data.total > 0 ? Math.min(100, Math.round(done / data.total * 100)) : 0;
      document.getElementById('importBar').style.width = pct + '%';
      document.getElementById('importPct').textContent = pct + '%';
      document.getElementById('batchStatus').textContent = data.label;

      if (data.finished) { clearInterval(timer); window.location.reload(); }
    } catch (e) { /* retry on the next tick */ }
  }, 3000);
})();
</script>
@endif
@endpush
