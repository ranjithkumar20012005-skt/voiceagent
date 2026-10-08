{{--
    Internal: our clients.

    Our own team only. This is one of the two places provider identifiers are
    ever shown, and the `admin` middleware is what keeps it closed -- a client
    user gets a 404.
--}}
@extends('layouts.app')

@section('title', 'Clients')

@section('content')

<div class="ph">
  <div class="ph-main">
    <div class="eyebrow">Internal</div>
    <h1>Clients</h1>
    <p class="ph-sub">Every business running on the platform, and the hosted agent mapped to each one.</p>
  </div>
  <div class="ph-actions">
    <a href="{{ route('internal.clients.create') }}" class="btn btn-primary btn-sm"><i class="icon-plus"></i> New client</a>
  </div>
</div>

@unless ($configured)
  <div class="card mb-4">
    <div class="card-b">
      <span class="badge badge-warning"><span class="dot"></span>Platform not configured</span>
      <p class="text-sm muted mt-2">
        The voice platform credentials are missing from this environment, so mappings can be recorded but calls cannot be placed.
      </p>
    </div>
  </div>
@endunless

<div class="card">
  <div class="card-b p-0">
    <table class="table">
      <thead>
        <tr>
          <th>Client</th>
          <th>Agent</th>
          <th>Mapping</th>
          <th>Users</th>
          <th>Calls</th>
          <th>Status</th>
          <th class="end"></th>
        </tr>
      </thead>
      <tbody>
        @forelse ($workspaces as $workspace)
          @php
            $wsAgents = $agents->get($workspace->id) ?? collect();
            $primary  = $wsAgents->first();
          @endphp
          <tr>
            <td>
              <strong>{{ $workspace->name }}</strong>
              <div class="text-xs faint">{{ $workspace->slug }}</div>
            </td>
            <td>
              @if ($primary)
                {{ $primary->name }}
                @if ($wsAgents->count() > 1)
                  <span class="text-xs faint">+{{ $wsAgents->count() - 1 }} more</span>
                @endif
              @else
                <span class="text-xs faint">Not mapped</span>
              @endif
            </td>
            <td>
              @if ($primary && $primary->isProvisioned())
                <span class="badge badge-success"><span class="dot"></span>Mapped</span>
              @elseif ($primary)
                <span class="badge badge-warning"><span class="dot"></span>Incomplete</span>
              @else
                <span class="badge"><span class="dot"></span>None</span>
              @endif
            </td>
            <td>{{ number_format($workspace->users_count) }}</td>
            <td>{{ number_format((int) ($callCounts[$workspace->id] ?? 0)) }}</td>
            <td>
              <span class="badge {{ $workspace->isActive() ? 'badge-success' : '' }}">
                <span class="dot"></span>{{ ucfirst($workspace->status) }}
              </span>
            </td>
            <td class="end">
              <a href="{{ route('internal.clients.show', $workspace) }}" class="btn btn-secondary btn-sm">Open</a>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="7">
              <x-empty icon="icon-boxes" title="No clients yet">
                Create the first client workspace, then map it to the hosted agent built for them.
              </x-empty>
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<div class="card mt-4">
  <div class="card-b">
    <h3 class="text-sm">Add numbers to the pool</h3>
    <p class="text-sm muted">
      The voice platform has no API for listing the numbers our account owns, so numbers are rented or imported in the
      platform dashboard and then recorded here. This pool is what clients are allocated from.
    </p>
    <form method="POST" action="{{ route('internal.numbers.import') }}" class="stack mt-3">
      @csrf
      <div class="field">
        <label class="label" for="numbers">Numbers</label>
        <textarea class="input mono" id="numbers" name="numbers" rows="3"
                  placeholder="+914012345678, +918098765432">{{ old('numbers') }}</textarea>
        <span class="text-xs muted">One per line or comma separated. Normalised to E.164; duplicates are skipped.</span>
      </div>
      <div class="field" style="max-width:140px">
        <label class="label" for="country">Country</label>
        <input class="input" id="country" name="country" maxlength="2" value="{{ old('country', 'IN') }}">
      </div>
      <button type="submit" class="btn btn-secondary btn-sm">Add to pool</button>
    </form>
  </div>
</div>

@endsection
