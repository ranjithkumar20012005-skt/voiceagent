{{--
    Phone Numbers -- the client's own numbers.

    Rebuilt for the white-label model. It used to read the number out of the
    server environment and name the voice platform in three places; it now lists
    the numbers allocated to this workspace from our own pool, and names no
    provider anywhere.
--}}
@extends('layouts.app')

@section('title', 'Phone Numbers')

@section('content')

<div class="ph">
  <div class="ph-main">
    <div class="eyebrow">Deploy</div>
    <h1>Phone Numbers</h1>
    <p class="ph-sub">The lines your agents call from.</p>
  </div>
</div>

<div class="card mb-section">
  <div class="card-h">
    <div>
      <h2>Numbers</h2>
      <p>{{ $numbers->isEmpty() ? 'No numbers assigned yet' : trans_choice(':count number assigned|:count numbers assigned', $numbers->count(), ['count' => $numbers->count()]) }}</p>
    </div>
  </div>

  @if ($numbers->isNotEmpty())
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>Number</th>
            <th>Country</th>
            <th>Type</th>
            <th>Assigned agent</th>
            <th class="num">Calls placed</th>
            <th>Last used</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($numbers as $number)
            <tr>
              <td class="cell-main mono">{{ \App\Support\PhoneNumber::display($number->phone_number) }}</td>
              <td>{{ $number->country }}</td>
              <td><span class="badge badge-teal">Voice</span></td>
              <td>
                @if ($number->agent)
                  <span class="chip">{{ $number->agent->name }}</span>
                @else
                  <span class="muted text-sm">Unassigned</span>
                @endif
              </td>
              <td class="num">{{ number_format($callCounts[$number->phone_number] ?? 0) }}</td>
              <td class="nowrap muted">{{ $lastUsed[$number->phone_number] ?? 'Never' }}</td>
              <td>
                <span class="badge {{ $number->status === 'active' ? 'badge-success' : '' }}">
                  <span class="dot"></span>{{ $number->displayStatus() }}
                </span>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @else
    <x-empty icon="icon-hash" tone="amber" title="No number assigned yet">
      We are setting up the calling line for your business. It will appear here once it is ready.
    </x-empty>
  @endif
</div>

<div class="grid cols-2">
  <div class="card">
    <div class="card-b">
      <div class="row mb-2">
        <i class="icon-phone-incoming muted"></i><strong>Inbound calling</strong>
        <span class="badge">{{ $hasInbound ? 'On' : 'Off' }}</span>
      </div>
      <p class="text-sm muted">
        {{ $hasInbound
            ? 'Calls to your number are answered by your agent.'
            : 'Answering incoming calls is not switched on for your account yet.' }}
      </p>
    </div>
  </div>
  <div class="card">
    <div class="card-b">
      <div class="row mb-2"><i class="icon-plus muted"></i><strong>Need another number?</strong></div>
      <p class="text-sm muted">Tell us which city or country you need a line in and we will set it up and assign it to your agent.</p>
    </div>
  </div>
</div>

@endsection
