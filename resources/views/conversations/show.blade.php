{{--
    One conversation, in full.

    Everything shown here comes from the presenters, so nothing in this file
    parses a payload or reaches for a provider field.
--}}
@extends('layouts.app')

@section('title', 'Conversation')

@section('content')

<div class="ph">
  <div class="ph-main">
    <div class="eyebrow"><a href="{{ route('conversations.index') }}">Conversations</a></div>
    <h1>{{ $result->customerName() }}</h1>
    <p class="ph-sub">
      {{ $result->agentName() }} &middot;
      {{ $result->startedAt()?->format('j M Y, g:i a') ?: 'Time not recorded' }} &middot;
      {{ $result->duration() ?: 'No duration' }}
    </p>
  </div>
  <div class="ph-actions">
    <a href="{{ route('calls.show', $call) }}" class="btn btn-secondary btn-sm">Call record</a>
  </div>
</div>

<div class="grid cols-3">

  {{-- ---------------------------------------------- Conversation --}}
  <div style="grid-column: span 2">
    <div class="card">
      <div class="card-h"><div><h2>Conversation</h2><p>What was said, in order</p></div></div>
      <div class="card-b">
        <x-transcript :transcript="$transcript" />
      </div>
    </div>

    <div class="card mt-4">
      <div class="card-h"><div><h2>Summary</h2></div></div>
      <div class="card-b">
        @if ($result->summary())
          <p>{{ $result->summary() }}</p>
        @else
          <p class="text-sm muted">{{ $result->summaryFallback() }}</p>
        @endif
      </div>
    </div>
  </div>

  {{-- ---------------------------------------------- Side panel --}}
  <div>
    <div class="card">
      <div class="card-h"><div><h2>Result</h2></div></div>
      <div class="card-b">
        <dl class="kv">
          <dt>Call status</dt><dd><span class="badge">{{ $result->callStatusLabel() }}</span></dd>
          <dt>Outcome</dt><dd><span class="badge">{{ $result->outcomeLabel() }}</span></dd>
          <dt>Lead status</dt>
          <dd>
            <span class="badge {{ $result->isInterested() ? 'badge-success' : '' }}">
              {{ $result->leadStatusLabel() }}
            </span>
          </dd>
          <dt>Direction</dt><dd>{{ $result->directionLabel() }}</dd>
          @if ($result->languageLabel())
            <dt>Language</dt><dd>{{ $result->languageLabel() }}</dd>
          @endif
          <dt>Phone</dt><dd class="mono">{{ $result->phone() }}</dd>
        </dl>
      </div>
    </div>

    <div class="card mt-4">
      <div class="card-h"><div><h2>Callback</h2></div></div>
      <div class="card-b">
        @if ($result->callbackRequired())
          <dl class="kv">
            <dt>Requested</dt><dd>Yes</dd>
            <dt>When</dt>
            <dd>{{ $result->callbackAt()?->format('j M Y, g:i a') ?: 'Time not given' }}</dd>
            @if ($callback)
              <dt>Status</dt><dd><span class="badge">{{ ucfirst($callback->status) }}</span></dd>
              @if ($callback->reason)
                <dt>Reason</dt><dd>{{ $callback->reason }}</dd>
              @endif
            @endif
          </dl>
          <a href="{{ route('callbacks.index') }}" class="btn btn-secondary btn-sm btn-block mt-3">All callbacks</a>
        @else
          <p class="text-sm muted">No callback was requested on this call.</p>
        @endif
      </div>
    </div>

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
