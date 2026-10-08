{{--
    One call, in full: the result page.

    Rebuilt on CallResultPresenter and TranscriptPresenter. The previous version
    printed the provider's attempt identifier in a details list; nothing
    provider-side appears here now, and no raw JSON is dumped.
--}}
@extends('layouts.app')

@section('title', 'Call Details')

@section('content')

<div class="ph">
  <div class="ph-main">
    <div class="eyebrow"><a href="{{ route('calls.index') }}">Call Logs</a></div>
    <h1>Call Details</h1>
    <p class="ph-sub">
      {{ $result->customerName() }} &middot;
      {{ $result->startedAt()?->format('j M Y, g:i a') ?: 'Time not recorded' }}
    </p>
  </div>
  <div class="ph-actions">
    @if ($transcript->hasTranscript())
      <a href="{{ route('conversations.show', $call) }}" class="btn btn-secondary btn-sm">Conversation view</a>
    @endif
    @if ($call->customer)
      <a href="{{ route('customers.show', $call->customer) }}" class="btn btn-ghost btn-sm">Customer</a>
    @endif
  </div>
</div>

{{-- ------------------------------------------------- Call facts --}}
<div class="card mb-4">
  <div class="card-h"><div><h2>Call</h2></div></div>
  <div class="card-b">
    <div class="grid cols-4">
      <div><div class="stat-label">Customer</div><div>{{ $result->customerName() }}</div></div>
      <div><div class="stat-label">Phone</div><div class="mono">{{ $result->phone() }}</div></div>
      <div><div class="stat-label">Agent</div><div>{{ $result->agentName() }}</div></div>
      <div><div class="stat-label">Direction</div><div>{{ $result->directionLabel() }}</div></div>
      <div><div class="stat-label">Date</div><div>{{ $result->startedAt()?->format('j M Y') ?: '—' }}</div></div>
      <div><div class="stat-label">Start time</div><div>{{ $result->startedAt()?->format('g:i a') ?: '—' }}</div></div>
      <div><div class="stat-label">Duration</div><div>{{ $result->duration() ?: '—' }}</div></div>
      <div>
        <div class="stat-label">Call status</div>
        <div><span class="badge">{{ $result->callStatusLabel() }}</span></div>
      </div>
    </div>
  </div>
</div>

<div class="grid cols-3">

  <div style="grid-column: span 2">

    {{-- ------------------------------------------------- Summary --}}
    <div class="card">
      <div class="card-h"><div><h2>Summary</h2></div></div>
      <div class="card-b">
        @if ($result->summary())
          <p>{{ $result->summary() }}</p>
        @else
          <p class="text-sm muted">{{ $result->summaryFallback() }}</p>
        @endif
      </div>
    </div>

    {{-- ------------------------------------------------- Conversation --}}
    <div class="card mt-4">
      <div class="card-h"><div><h2>Conversation</h2><p>What was said, in order</p></div></div>
      <div class="card-b">
        <x-transcript :transcript="$transcript" />
      </div>
    </div>

  </div>

  <div>

    {{-- ------------------------------------------------- Result --}}
    <div class="card">
      <div class="card-h"><div><h2>Result</h2></div></div>
      <div class="card-b">
        <dl class="kv">
          <dt>Outcome</dt><dd><span class="badge">{{ $result->outcomeLabel() }}</span></dd>
          <dt>Lead status</dt>
          <dd>
            <span class="badge {{ $result->isInterested() ? 'badge-success' : '' }}">
              {{ $result->leadStatusLabel() }}
            </span>
          </dd>
          <dt>Interested</dt><dd>{{ $result->isInterested() ? 'Yes' : 'No' }}</dd>
          @if ($result->languageLabel())
            <dt>Language</dt><dd>{{ $result->languageLabel() }}</dd>
          @endif
          @if ($call->failure_reason)
            <dt>Not completed</dt><dd>{{ $call->failure_reason }}</dd>
          @endif
        </dl>
      </div>
    </div>

    {{-- ------------------------------------------------- Callback --}}
    <div class="card mt-4">
      <div class="card-h"><div><h2>Callback</h2></div></div>
      <div class="card-b">
        @if ($result->callbackRequired())
          <dl class="kv">
            <dt>Required</dt><dd>Yes</dd>
            <dt>When</dt><dd>{{ $result->callbackAt()?->format('j M Y, g:i a') ?: 'Time not given' }}</dd>
            @if ($callback)
              <dt>Status</dt><dd><span class="badge">{{ ucfirst($callback->status) }}</span></dd>
              @if ($callback->reason)
                <dt>Reason</dt><dd>{{ $callback->reason }}</dd>
              @endif
            @endif
          </dl>
        @else
          <p class="text-sm muted">No callback required.</p>
        @endif
      </div>
    </div>

    {{-- ------------------------------------------------- Captured --}}
    <div class="card mt-4">
      <div class="card-h"><div><h2>Captured information</h2></div></div>
      <div class="card-b">
        @if ($result->hasCapturedFields())
          <dl class="kv">
            @foreach ($result->capturedFields() as $field)
              <dt>{{ $field['label'] }}</dt><dd>{{ $field['value'] }}</dd>
            @endforeach
          </dl>
        @else
          <p class="text-sm muted">Nothing was captured on this call.</p>
        @endif
      </div>
    </div>

  </div>

</div>

@endsection
