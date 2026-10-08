@extends('layouts.app')

@php
  $copy = [
      // Copy de-branded: these pages named the voice platform and told the
      // client to go and configure it. Both are our team's work, not theirs.
      'knowledge' => [
          'title' => 'Knowledge Base',
          'sub'   => 'Documents your agent can answer questions from.',
          'icon'  => 'icon-book-open',
          'what'  => 'Uploading documents here is not available yet. Your agent\'s knowledge is set up and maintained by our team.',
          'next'  => ['Send us the documents your agent should answer from.', 'We add them to your agent and publish the change.', 'Answers drawn from them then appear in your call transcripts.'],
      ],
      'tools' => [
          'title' => 'Tools',
          'sub'   => 'Actions your agent can take during a call — webhooks, CRM updates, bookings.',
          'icon'  => 'icon-wrench',
          'what'  => 'No actions are switched on for your agent yet. Actions such as booking an appointment or posting a lead to your CRM are set up by our team.',
          'next'  => ['Tell us which action you need during a call.', 'We configure it on your agent.', 'What it captured then appears on the call detail page here.'],
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
