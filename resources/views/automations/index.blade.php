@extends('layouts.app')

@section('title', 'Automations')

@section('content')

<div class="ph">
  <div class="ph-main">
    <div class="eyebrow">Automation</div>
    <h1>Automations</h1>
    <p class="ph-sub">Recurring calling rules. Each day at its set time, an automation finds matching customers and queues calls for them.</p>
  </div>
  <div class="ph-actions">
    <button type="button" class="btn btn-primary btn-sm" data-modal-open="automationNew"><i class="icon-plus"></i> New automation</button>
  </div>
</div>

<div class="callout callout-info mb-5">
  <i class="icon-info"></i>
  <div>Automations run through the server's task scheduler. Production needs the cron entry <code>* * * * * php artisan schedule:run</code> and a running queue worker, otherwise nothing fires automatically.</div>
</div>

<div class="card">
  @if ($automations->isEmpty())
    <x-empty icon="icon-zap" title="No automations yet">
      Create a rule such as “call customers whose policy expires within 30 days, every morning at 8”.
      <x-slot:action>
        <button type="button" class="btn btn-primary btn-sm" data-modal-open="automationNew">New automation</button>
      </x-slot:action>
    </x-empty>
  @else
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr><th>Automation</th><th>Trigger</th><th>Action</th><th class="num">Matching now</th><th>Status</th><th>Last run</th><th class="end"></th></tr>
        </thead>
        <tbody>
          @foreach ($automations as $automation)
            <tr>
              <td><span class="cell-main">{{ $automation->name }}</span></td>
              <td class="nowrap">
                Daily at {{ $automation->run_at }}
                <span class="cell-sub">{{ $automation->timezone }} · calls {{ $automation->window_start }}–{{ $automation->window_end }}</span>
              </td>
              <td class="text-sm" style="min-width:220px">
                Call customers
                @if ($automation->expiry_within_days > 0) with a policy due within {{ $automation->expiry_within_days }} days @endif
                @if (array_filter((array) $automation->customer_statuses)) · status {{ implode(', ', array_map(fn ($s) => str_replace('_', ' ', $s), (array) $automation->customer_statuses)) }} @endif
                <span class="cell-sub">Up to {{ number_format($automation->max_calls_per_run) }} per run · {{ $automation->max_retries }} retries</span>
              </td>
              <td class="num strong">{{ number_format($eligible[$automation->id] ?? 0) }}</td>
              <td>
                @if ($automation->enabled)
                  <span class="badge badge-success"><span class="dot"></span>Enabled</span>
                @else
                  <span class="badge"><span class="dot"></span>Disabled</span>
                @endif
              </td>
              <td class="nowrap">
                @if ($automation->last_run_at)
                  {{ $automation->last_run_at->diffForHumans() }}
                  <span class="cell-sub">{{ $automation->last_run_count }} queued{{ $automation->last_run_status ? ' · ' . str_replace('_', ' ', $automation->last_run_status) : '' }}</span>
                @else
                  <span class="faint">Never run</span>
                @endif
              </td>
              <td class="end">
                <div class="row-actions">
                  <form method="POST" action="{{ route('automations.run', $automation) }}" class="inline-form">
                    @csrf
                    <input type="hidden" name="dry_run" value="1">
                    <button type="submit" class="btn btn-secondary btn-xs" title="Count who would be called — places no calls">Preview</button>
                  </form>
                  <button type="button" class="btn btn-secondary btn-xs" data-modal-open="automation{{ $automation->id }}"><i class="icon-pencil"></i> Edit</button>
                </div>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif
</div>

<p class="text-xs faint mt-3">Do-not-call customers are never included, and a customer with a callback still scheduled is left alone until it is due.</p>

{{-- ------------------------------------------------------------ Modals --}}
@foreach ($automations as $automation)
  <div class="modal" id="automation{{ $automation->id }}" role="dialog" aria-modal="true" aria-labelledby="automation{{ $automation->id }}Title" aria-hidden="true">
    <div class="modal-scrim" data-modal-close></div>
    <div class="modal-panel" style="max-width:760px">
      <form method="POST" action="{{ route('automations.update', $automation) }}">
        @csrf
        @method('PUT')
        <div class="modal-h">
          <div><h2 id="automation{{ $automation->id }}Title">Edit automation</h2><p>{{ number_format($eligible[$automation->id] ?? 0) }} customers currently match this rule.</p></div>
          <button type="button" class="btn btn-ghost btn-icon btn-sm" data-modal-close aria-label="Close"><i class="icon-x"></i></button>
        </div>
        <div class="modal-b">
          @include('automations._form', ['a' => $automation, 'p' => 'a' . $automation->id . '_'])
        </div>
        <div class="modal-f" style="justify-content:space-between">
          <button type="submit" form="runNow{{ $automation->id }}" class="btn btn-danger btn-sm"><i class="icon-play"></i> Run now</button>
          <div class="row">
            <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
            <button type="submit" class="btn btn-primary">Save</button>
          </div>
        </div>
      </form>
      <form method="POST" action="{{ route('automations.run', $automation) }}" id="runNow{{ $automation->id }}"
            data-confirm="Place real calls now to all {{ $eligible[$automation->id] ?? 0 }} matching customers?">
        @csrf
        <input type="hidden" name="dry_run" value="0">
        <input type="hidden" name="force" value="1">
      </form>
    </div>
  </div>
@endforeach

<div class="modal" id="automationNew" role="dialog" aria-modal="true" aria-labelledby="automationNewTitle" aria-hidden="true">
  <div class="modal-scrim" data-modal-close></div>
  <div class="modal-panel" style="max-width:760px">
    <form method="POST" action="{{ route('automations.store') }}">
      @csrf
      <div class="modal-h">
        <div><h2 id="automationNewTitle">New automation</h2><p>Created disabled unless you switch it on. Use Preview to check who matches first.</p></div>
        <button type="button" class="btn btn-ghost btn-icon btn-sm" data-modal-close aria-label="Close"><i class="icon-x"></i></button>
      </div>
      <div class="modal-b">
        @include('automations._form', ['a' => new \App\Models\Automation(['name' => 'Daily renewal calls', 'enabled' => false]), 'p' => 'new_'])
      </div>
      <div class="modal-f">
        <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
        <button type="submit" class="btn btn-primary">Create automation</button>
      </div>
    </form>
  </div>
</div>

@endsection
