@extends('layouts.app')

@section('title', 'Phone Numbers')

@section('content')

<div class="ph">
  <div class="ph-main">
    <div class="eyebrow">Deploy</div>
    <h1>Phone Numbers</h1>
    <p class="ph-sub">The line your agents call from. Numbers are provisioned in your Sarvam workspace; this app uses the one configured on the server.</p>
  </div>
</div>

<div class="card mb-section">
  <div class="card-h"><div><h2>Numbers</h2><p>{{ $status['caller_number'] ? '1 number configured' : 'No number configured' }}</p></div></div>
  @if ($status['caller_number'])
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>Number</th><th>Provider</th><th>Assigned agents</th><th>Direction</th><th class="num">Calls placed</th><th>Last used</th><th>Status</th></tr></thead>
        <tbody>
          <tr>
            <td class="cell-main mono">{{ \App\Support\PhoneNumber::display($status['caller_number']) }}</td>
            <td>Sarvam</td>
            <td>
              <div class="chips">
                <span class="chip">Workspace agent</span>
                @foreach ($agents as $agent)<span class="chip">{{ $agent->name }}</span>@endforeach
              </div>
            </td>
            <td><span class="badge badge-teal">Outbound</span></td>
            <td class="num">{{ number_format($calls) }}</td>
            <td class="nowrap muted">{{ $lastCall ? \Illuminate\Support\Carbon::parse($lastCall)->diffForHumans() : 'Never' }}</td>
            <td><span class="badge {{ $status['configured'] ? 'badge-success' : 'badge-warning' }}"><span class="dot"></span>{{ $status['configured'] ? 'Active' : 'Incomplete setup' }}</span></td>
          </tr>
        </tbody>
      </table>
    </div>
  @else
    <x-empty icon="icon-hash" tone="amber" title="No phone number configured">
      An administrator needs to set the agent's phone number and connection on the server before calls can be placed.
    </x-empty>
  @endif
</div>

<div class="grid cols-2">
  <div class="card">
    <div class="card-b">
      <div class="row mb-2"><i class="icon-phone-incoming muted"></i><strong>Inbound calling</strong><span class="badge">Not connected</span></div>
      <p class="text-sm muted">Answering calls to this number with the AI agent is not set up in this application yet.</p>
    </div>
  </div>
  <div class="card">
    <div class="card-b">
      <div class="row mb-2"><i class="icon-plus muted"></i><strong>Adding numbers</strong></div>
      <p class="text-sm muted">Numbers are bought and connected in your Sarvam workspace. This app does not purchase or provision numbers.</p>
    </div>
  </div>
</div>

@endsection
