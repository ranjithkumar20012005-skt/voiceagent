@extends('layouts.app')

@php
  $copy = [
      'knowledge' => [
          'title' => 'Knowledge Base',
          'sub'   => 'Documents your agent can answer questions from.',
          'icon'  => 'icon-book-open',
          'what'  => 'Documents uploaded here are not connected to the voice agent, so this app does not accept uploads yet. Your agent\'s knowledge is managed in the Sarvam agent builder.',
          'next'  => ['Add or update documents on the agent in Sarvam.', 'Publish a new agent version.', 'Update the version on the agent in My Agents so calls use it.'],
      ],
      'tools' => [
          'title' => 'Tools',
          'sub'   => 'Actions your agent can take during a call — webhooks, CRM updates, bookings.',
          'icon'  => 'icon-wrench',
          'what'  => 'No tools are connected to the voice agent from this application, so none are listed as available. Tools such as webhooks or booking actions are configured on the agent in Sarvam.',
          'next'  => ['Configure the tool on the agent in Sarvam.', 'Have the agent return its result as an output variable.', 'Results then appear on the call detail page here.'],
      ],
  ][$feature];
@endphp

@section('title', $copy['title'])

@section('content')

<div class="ph">
  <div class="ph-main">
    <div class="eyebrow">Build</div>
    <h1>{{ $copy['title'] }}</h1>
    <p class="ph-sub">{{ $copy['sub'] }}</p>
  </div>
  <span class="badge badge-lg"><span class="dot"></span>Not connected</span>
</div>

<div class="split-even">
  <div class="card">
    <x-empty :icon="$copy['icon']" tone="amber" title="Not connected in this application">
      {{ $copy['what'] }}
    </x-empty>
  </div>

  <div class="card">
    <div class="card-h"><div><h3>How to do this today</h3></div></div>
    <div class="card-b">
      <ul class="feature-list">
        @foreach ($copy['next'] as $step)
          <li><i class="icon-check"></i><span>{{ $step }}</span></li>
        @endforeach
      </ul>
    </div>
    <div class="card-f"><a href="{{ route('agents.index') }}" class="btn btn-secondary btn-sm">Go to My Agents</a></div>
  </div>
</div>

@endsection
