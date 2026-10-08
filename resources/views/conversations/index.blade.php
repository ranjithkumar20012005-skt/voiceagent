@extends('layouts.app')

@section('title', 'Conversations')

@section('content')

<div class="ph">
  <div class="ph-main">
    <div class="eyebrow">Results</div>
    <h1>Conversations</h1>
    <p class="ph-sub">Every call your agent held, with the full conversation.</p>
  </div>
</div>

{{-- Filters. Language only appears when calls have actually recorded one. --}}
<form method="GET" class="card mb-4">
  <div class="card-b">
    <div class="row wrap" style="gap:10px; align-items:flex-end">
      <div class="field" style="margin:0; min-width:220px; flex:1">
        <label class="label" for="search">Customer or phone</label>
        <input class="input" id="search" name="search" value="{{ $filters['search'] }}" placeholder="Name or number">
      </div>
      <div class="field" style="margin:0">
        <label class="label" for="agent">Agent</label>
        <select class="input" id="agent" name="agent">
          <option value="">All</option>
          @foreach ($agents as $agent)
            <option value="{{ $agent->id }}" @selected((string) $filters['agent'] === (string) $agent->id)>{{ $agent->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="field" style="margin:0">
        <label class="label" for="outcome">Outcome</label>
        <select class="input" id="outcome" name="outcome">
          <option value="">All</option>
          @foreach ($outcomes as $value => $label)
            <option value="{{ $value }}" @selected((string) $filters['outcome'] === (string) $value)>{{ $label }}</option>
          @endforeach
        </select>
      </div>
      @if ($languages->isNotEmpty())
        <div class="field" style="margin:0">
          <label class="label" for="language">Language</label>
          <select class="input" id="language" name="language">
            <option value="">All</option>
            @foreach ($languages as $language)
              <option value="{{ $language }}" @selected((string) $filters['language'] === (string) $language)>{{ $language }}</option>
            @endforeach
          </select>
        </div>
      @endif
      <div class="field" style="margin:0">
        <label class="label" for="from">From</label>
        <input class="input" type="date" id="from" name="from" value="{{ $filters['from'] }}">
      </div>
      <div class="field" style="margin:0">
        <label class="label" for="to">To</label>
        <input class="input" type="date" id="to" name="to" value="{{ $filters['to'] }}">
      </div>
      <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
      <a href="{{ route('conversations.index') }}" class="btn btn-ghost btn-sm">Clear</a>
    </div>
  </div>
</form>

<div class="card">
  <div class="card-b p-0">
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>Customer</th><th>Agent</th><th>Date</th><th>Duration</th>
            <th>Direction</th><th>Language</th><th>Outcome</th><th class="end"></th>
          </tr>
        </thead>
        <tbody>
          @forelse ($conversations as $conversation)
            @php $r = \App\Services\Presenters\CallResultPresenter::for($conversation); @endphp
            <tr>
              <td class="cell-main">
                {{ $r->customerName() }}
                <div class="text-xs faint mono">{{ $r->phone() }}</div>
              </td>
              <td>{{ $r->agentName() }}</td>
              <td class="nowrap">{{ $r->startedAt()?->format('j M Y, g:i a') ?: '—' }}</td>
              <td>{{ $r->duration() ?: '—' }}</td>
              <td>{{ $r->directionLabel() }}</td>
              <td>{{ $r->languageLabel() ?: '—' }}</td>
              <td><span class="badge">{{ $r->outcomeLabel() }}</span></td>
              <td class="end">
                <a href="{{ route('conversations.show', $conversation) }}" class="btn btn-secondary btn-sm">Open</a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="8">
                <x-empty icon="icon-message-square" title="No conversations recorded yet">
                  Once your agent starts speaking to customers, every conversation will be listed here with its full transcript.
                </x-empty>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

@if ($conversations->hasPages())
  <div class="mt-3">{{ $conversations->links() }}</div>
@endif

@endsection
