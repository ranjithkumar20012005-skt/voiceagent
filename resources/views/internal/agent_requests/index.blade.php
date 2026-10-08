{{--
    Internal: "build it for us" requests, across every client.

    Our own team only. A client's self-serve option is agents.create; this is
    the queue for the ones who asked us to build it instead.
--}}
@extends('layouts.app')

@section('title', 'Agent Requests')

@section('content')

<div class="ph">
  <div class="ph-main">
    <div class="eyebrow">Internal</div>
    <h1>Agent Requests</h1>
    <p class="ph-sub">What clients have asked us to build for them, newest first.</p>
  </div>
</div>

<div class="card">
  <div class="card-b p-0">
    <table class="table">
      <thead>
        <tr>
          <th>Client</th>
          <th>Request</th>
          <th>Requested by</th>
          <th>Status</th>
          <th class="end">Update</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($requests as $req)
          <tr>
            <td>
              <a href="{{ route('internal.clients.show', $req->workspace) }}">{{ $req->workspace->name }}</a>
            </td>
            <td style="max-width:360px">
              <strong>{{ $req->title }}</strong>
              <p class="text-sm muted mt-1">{{ \Illuminate\Support\Str::limit($req->details, 160) }}</p>
              @if ($req->fulfilledAgent)
                <a href="{{ route('internal.clients.show', $req->workspace) }}" class="text-xs">Fulfilled by {{ $req->fulfilledAgent->name }}</a>
              @endif
            </td>
            <td>
              <div>{{ $req->requestedBy->name }}</div>
              <div class="text-xs faint">{{ $req->created_at->diffForHumans() }}</div>
            </td>
            <td>
              <span class="badge {{ $req->status === 'fulfilled' ? 'badge-success' : ($req->status === 'declined' ? '' : 'badge-warning') }}">
                <span class="dot"></span>{{ $req->displayStatus() }}
              </span>
            </td>
            <td class="end">
              <form method="POST" action="{{ route('internal.agent-requests.update', $req) }}" class="row" style="justify-content:flex-end; gap:6px; flex-wrap:nowrap">
                @csrf
                @method('PUT')
                <select name="status" class="input" style="width:auto">
                  @foreach (\App\Models\AgentBuildRequest::STATUSES as $value => $label)
                    <option value="{{ $value }}" @selected($req->status === $value)>{{ $label }}</option>
                  @endforeach
                </select>
                @if ($req->workspace->agents->isNotEmpty())
                  <select name="fulfilled_agent_id" class="input" style="width:auto">
                    <option value="">No agent linked</option>
                    @foreach ($req->workspace->agents as $agent)
                      <option value="{{ $agent->id }}" @selected($req->fulfilled_agent_id === $agent->id)>{{ $agent->name }}</option>
                    @endforeach
                  </select>
                @endif
                <button type="submit" class="btn btn-secondary btn-sm">Save</button>
              </form>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="5">
              <x-empty icon="icon-inbox" title="No requests yet">
                Requests clients send from their Agents tab will show up here.
              </x-empty>
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

@endsection
