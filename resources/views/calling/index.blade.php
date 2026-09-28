@extends('layouts.app')

@section('title', 'New Call')

@php use App\Support\LeadOutcome; @endphp

@section('content')

<div class="ph">
  <div class="ph-main">
    <div class="eyebrow">Calling</div>
    <h1>New Call</h1>
    <p class="ph-sub">Start an AI call and review what happened.</p>
  </div>
</div>

<div class="tabs-line" data-tabs="calling" role="tablist">
  <button type="button" class="active" data-tab="instant" role="tab" aria-selected="true"><i class="icon-phone-outgoing"></i> Instant Call</button>
  <button type="button" data-tab="bulk" role="tab" aria-selected="false"><i class="icon-users"></i> Bulk Calling</button>
  <button type="button" data-tab="inbound" role="tab" aria-selected="false"><i class="icon-phone-incoming"></i> Inbound <span class="nav-soon">Soon</span></button>
</div>

{{-- ======================================================== Instant Call --}}
<div data-tab-panel="instant" data-tab-scope="calling">
  <div class="split-left">
    <div class="card">
      <div class="card-h">
        <div class="card-h-title">
          <div class="card-icon"><i class="icon-phone-outgoing"></i></div>
          <div><h2>Instant AI Call</h2><p>The agent calls this customer straight away.</p></div>
        </div>
      </div>

      <form class="card-b" data-call-form="{{ route('calls.store') }}" autocomplete="off" novalidate>
        @unless ($configured)
          <div class="callout callout-warning mb-4">
            <i class="icon-circle-alert"></i><div>Calling is unavailable right now. Please contact your administrator.</div>
          </div>
        @endunless

        <div data-call-alert hidden></div>

        <div class="field">
          <label for="icName" class="label">Customer name</label>
          <input type="text" class="input" id="icName" name="name" maxlength="120" placeholder="Anita Sharma">
        </div>

        <div class="field">
          <label for="icPhone" class="label">Phone number <span class="req">*</span></label>
          <input type="tel" class="input" id="icPhone" name="phone_number" required maxlength="24" placeholder="+91 98765 43210">
          <div class="hint">10-digit Indian numbers are accepted. An existing customer with this number is reused.</div>
        </div>

        <div class="form-grid mb-4">
          <div class="field">
            <label for="icPolicy" class="label">Policy number</label>
            <input type="text" class="input" id="icPolicy" name="policy_number" maxlength="64" placeholder="POL-2291">
          </div>
          <div class="field">
            <label for="icRegMobile" class="label">Registered mobile <span class="opt">(optional)</span></label>
            <input type="tel" class="input" id="icRegMobile" name="registered_mobile" maxlength="24" placeholder="+91 98765 43210">
          </div>
        </div>

        <div class="form-grid">
          @if ($callAgents->isNotEmpty())
            <div class="field">
              <label for="icAgent" class="label">Agent</label>
              <select class="select" id="icAgent" name="agent_id">
                @foreach ($callAgents as $callAgent)
                  <option value="{{ $callAgent->id }}" @selected($callAgent->is_default)>{{ $callAgent->name }}{{ $callAgent->is_default ? ' (default)' : '' }}</option>
                @endforeach
              </select>
            </div>
          @endif
          <div class="field {{ $callAgents->isEmpty() ? 'full' : '' }}">
            <label for="icLanguage" class="label">Language</label>
            <select class="select" id="icLanguage" name="language">
              <option value="">Agent default</option>
              @foreach ($languages as $language)
                <option value="{{ $language }}">{{ $language }}</option>
              @endforeach
            </select>
          </div>
        </div>

        <button type="submit" class="btn btn-primary btn-lg btn-block mt-5" @disabled(! $configured)>
          <i class="icon-phone"></i> Start Call
        </button>
      </form>
    </div>

    {{-- ------------------------------------------------ Recent instant calls --}}
    <div class="card">
      <div class="card-h">
        <div><h2>Recent instant calls</h2><p>Calls placed one at a time. Results arrive when each call ends.</p></div>
        <a href="{{ route('calls.index') }}" class="btn btn-secondary btn-sm">All call logs</a>
      </div>

      @if ($recent->isEmpty())
        <x-empty icon="icon-phone" title="No calls yet">
          Fill in the form to place your first AI call.
        </x-empty>
      @else
        <div class="table-wrap">
          <table class="table">
            <thead>
              <tr><th>Customer</th><th>Agent</th><th>Status</th><th>Result</th><th class="num">Duration</th><th class="end"></th></tr>
            </thead>
            <tbody>
              @foreach ($recent as $attempt)
                <tr>
                  <td>
                    <span class="cell-main">{{ $attempt->customer?->name ?: 'Unknown' }}</span>
                    <span class="cell-sub">{{ $attempt->display_phone }} · {{ $attempt->created_at->diffForHumans() }}</span>
                  </td>
                  <td class="muted nowrap">{{ $attempt->agent?->name ?? 'Workspace agent' }}</td>
                  <td><span class="badge {{ LeadOutcome::connectivityBadge($attempt) }}">{{ LeadOutcome::connectivityLabel($attempt) }}</span></td>
                  <td><span class="badge {{ LeadOutcome::badge($attempt->call_disposition) }}">{{ LeadOutcome::label($attempt->call_disposition) }}</span></td>
                  <td class="num">{{ $attempt->duration_for_humans }}</td>
                  <td class="end"><a href="{{ route('calls.show', $attempt) }}" class="btn btn-secondary btn-xs">View</a></td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @endif
    </div>
  </div>
</div>

{{-- ======================================================== Bulk Calling --}}
<div data-tab-panel="bulk" data-tab-scope="calling" hidden>
  <div class="grid cols-3">
    @foreach ([
      ['1', 'icon-upload', 'Import customers', 'Upload a .csv or .xlsx file and map your columns to ours.', route('imports.index'), 'Go to Imports'],
      ['2', 'icon-megaphone', 'Create a campaign', 'Pick an import batch or policies expiring soon, then dispatch.', route('campaigns.index'), 'Go to Campaigns'],
      ['3', 'icon-chart-column', 'Track results', 'Follow progress, connections and leads as the run completes.', route('analytics.index'), 'Open Analytics'],
    ] as [$n, $icon, $title, $body, $href, $cta])
      <div class="card">
        <div class="card-b">
          <div class="row between mb-3">
            <div class="card-icon"><i class="{{ $icon }}"></i></div>
            <span class="mono faint">0{{ $n }}</span>
          </div>
          <h3 style="font-size:15px">{{ $title }}</h3>
          <p class="text-sm muted mt-1">{{ $body }}</p>
        </div>
        <div class="card-f"><a href="{{ $href }}" class="btn btn-secondary btn-sm btn-block">{{ $cta }}</a></div>
      </div>
    @endforeach
  </div>
</div>

{{-- ============================================================= Inbound --}}
<div data-tab-panel="inbound" data-tab-scope="calling" hidden>
  <div class="card">
    <x-empty icon="icon-phone-incoming" title="Inbound AI Calls — Coming Soon">
      Let customers call your number and have the AI agent answer, qualify and route the conversation. This is not connected yet.
    </x-empty>
  </div>
</div>

@endsection
