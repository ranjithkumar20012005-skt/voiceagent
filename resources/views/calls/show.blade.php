@extends('layouts.app')

@section('title', 'Call Details')

@php
  use App\Support\CallStatus;
  use App\Support\LeadOutcome;

  $turns = $call->transcriptTurns();

  /*
   | Technical detail -- platform identifiers and the raw agent variables --
   | is for internal operators only. In a normal client build (APP_DEBUG off)
   | none of it is rendered at all.
   */
  $internal = (bool) config('app.debug');

  $output = (array) ($call->output_agent_variables ?? []);
  $summary = $output['customer_notes'] ?? $output['summary'] ?? null;
  $back = url()->previous() === url()->current() ? route('calls.index') : url()->previous();
@endphp

@section('content')

<div class="ph">
  <div class="ph-main">
    <a href="{{ $back }}" class="ph-back"><i class="icon-arrow-left"></i> Back</a>
    <h1>{{ $call->customer?->name ?: 'Unknown customer' }}</h1>
    <div class="ph-meta">
      <span class="badge {{ LeadOutcome::connectivityBadge($call) }}">{{ LeadOutcome::connectivityLabel($call) }}</span>
      <span class="badge {{ LeadOutcome::badge($call->call_disposition) }}">{{ LeadOutcome::label($call->call_disposition) }}</span>
      <span>{{ $call->display_phone }} · {{ $call->created_at->format('d M Y, g:i A') }}</span>
    </div>
  </div>

  <div class="ph-actions">
    @if ($call->customer)
      <a href="{{ route('customers.show', $call->customer) }}" class="btn btn-secondary btn-sm"><i class="icon-circle-user"></i> Customer</a>
    @endif
    @if ($call->customer && ! $call->customer->do_not_call)
      <button type="button" class="btn btn-primary btn-sm"
              data-new-call
              data-customer-id="{{ $call->customer->id }}"
              data-name="{{ $call->customer->name }}"
              data-phone="{{ $call->customer->phone_number }}"
              data-policy="{{ $call->customer->policy_number }}"
              @if ($call->agent_id) data-agent-id="{{ $call->agent_id }}" @endif>
        <i class="icon-phone"></i> Call Again
      </button>
    @endif
  </div>
</div>

<div class="split">

  {{-- -------------------------------------------------------- Transcript --}}
  <div class="card">
    <div class="card-h">
      <div><h2>Conversation Transcript</h2><p>As returned by the agent when the call ended.</p></div>
      @if ($turns)
        <span class="badge">{{ count($turns) }} turns</span>
      @endif
    </div>

    <div class="card-b">
      @forelse ($turns as $turn)
        {{-- Always escaped: customer speech is untrusted input. --}}
        <div class="turn {{ $turn['role'] }}">
          <div class="turn-who">
            @if ($turn['role'] === 'agent')
              <i class="icon-bot"></i> Agent
            @else
              <i class="icon-circle-user"></i> Customer
            @endif
          </div>
          <div class="turn-text">{{ $turn['text'] }}</div>
        </div>
      @empty
        <x-empty icon="icon-message-square-text" title="{{ $call->status === CallStatus::COMPLETED ? 'No transcript' : 'Transcript pending' }}" compact>
          @if ($call->status === CallStatus::COMPLETED)
            No transcript was returned for this call.
          @elseif ($call->status === CallStatus::FAILED)
            The call did not go through, so there is no conversation to show.
          @else
            Transcript pending — it appears once the call completes.
          @endif
        </x-empty>
      @endforelse
    </div>
  </div>

  {{-- ------------------------------------------------------- Side rail --}}
  <div class="stack">
    <div class="card">
      <div class="card-h"><div><h3>Call Details</h3></div></div>
      <div class="card-b">
        <dl class="kv">
          <dt>Customer</dt><dd>{{ $call->customer?->name ?: 'Unknown' }}</dd>
          <dt>Phone</dt><dd>{{ $call->display_phone }}</dd>
          @if ($call->customer?->policy_number)
            <dt>Policy</dt><dd>{{ $call->customer->policy_number }}</dd>
          @endif
          <dt>Agent</dt><dd>{{ $call->agent?->name ?? 'Workspace agent' }}</dd>
          <dt>Source</dt><dd>{{ $call->campaign_id ? 'Campaign' : 'Instant call' }}</dd>
          <dt>Call time</dt><dd>{{ $call->started_at?->format('d M Y, g:i A') ?: $call->created_at->format('d M Y, g:i A') }}</dd>
          <dt>Duration</dt><dd>{{ $call->duration_for_humans }}</dd>
          <dt>Connectivity</dt><dd>{{ LeadOutcome::connectivityLabel($call) }}</dd>
          <dt>Outcome</dt><dd>{{ LeadOutcome::label($call->call_disposition) }}</dd>
        </dl>
      </div>
    </div>

    {{-- Next action: only when the agent actually reported one. --}}
    @if ($call->callback_at || $call->lead_generated)
      <div class="card">
        <div class="card-h"><div><h3>Next Action</h3></div></div>
        <div class="card-b stack" style="gap:10px">
          @if ($call->lead_generated)
            <div class="row"><span class="badge badge-success">Hot Lead</span><span class="text-sm muted">Worth a follow-up from your team.</span></div>
          @endif
          @if ($call->callback_at)
            <div class="row"><span class="badge badge-teal">Callback</span><span class="text-sm muted">{{ $call->callback_at->format('d M Y, g:i A') }}</span></div>
          @endif
        </div>
      </div>
    @endif

    @if (filled($summary))
      <div class="card">
        <div class="card-h"><div><h3>Summary</h3><p>Notes recorded by the agent</p></div></div>
        <div class="card-b"><p class="text-sm">{{ is_scalar($summary) ? $summary : json_encode($summary) }}</p></div>
      </div>
    @endif

    {{-- Internal operators only. Never rendered in a client build. --}}
    @if ($internal)
      <div class="card">
        <div class="card-h"><div><h3>Internal Detail</h3></div><span class="badge badge-warning">Debug build</span></div>
        <div class="card-b">
          <dl class="kv">
            <dt>Local status</dt><dd>{{ ucfirst($call->status) }}</dd>
            <dt>Attempt ID</dt><dd><code>{{ $call->attempt_id ?: 'Pending' }}</code></dd>
            @if ($call->failure_reason)
              <dt>Failure reason</dt><dd class="text-bad">{{ $call->failure_reason }}</dd>
            @endif
            @foreach ($output as $key => $value)
              <dt class="break">{{ $key }}</dt>
              <dd>
                @if (is_scalar($value) || $value === null)
                  {{ $value === null ? '--' : (is_bool($value) ? ($value ? 'true' : 'false') : $value) }}
                @else
                  <code>{{ json_encode($value, JSON_UNESCAPED_UNICODE) }}</code>
                @endif
              </dd>
            @endforeach
          </dl>
        </div>
      </div>
    @endif
  </div>
</div>

@endsection
