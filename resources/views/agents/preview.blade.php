{{--
    The prompt this agent's answers produce.

    A review step before going live: the platform's own guidance is that an
    accepted config says nothing about whether the agent is any good, so the
    assembled prompt is readable here rather than only inside the provider.
--}}
@extends('layouts.app')

@section('title', 'Prompt preview')

@section('content')

<div class="ph">
  <div class="ph-main">
    <div class="eyebrow"><a href="{{ route('agents.show', $agent) }}">{{ $agent->name }}</a></div>
    <h1>Prompt preview</h1>
    <p class="ph-sub">What {{ $agent->name }} has been told to do, built from your answers.</p>
  </div>
  <div class="ph-actions">
    <a href="{{ route('agents.edit', $agent) }}" class="btn btn-secondary btn-sm">Edit agent</a>
  </div>
</div>

<div class="card mb-4">
  <div class="card-h"><div><h2>Opening line</h2></div></div>
  <div class="card-b"><p>{{ $opening }}</p></div>
</div>

<div class="card mb-4">
  <div class="card-h"><div><h2>Instructions</h2><p>{{ number_format(strlen($prompt)) }} characters</p></div></div>
  <div class="card-b">
    <pre style="white-space:pre-wrap; font-size:13px; line-height:1.6; margin:0">{{ $prompt }}</pre>
  </div>
</div>

<div class="card">
  <div class="card-h"><div><h2>Values the agent is given</h2></div></div>
  <div class="card-b">
    <div class="chips">
      @foreach ($variables as $variable)
        <span class="chip mono">{{ $variable }}</span>
      @endforeach
    </div>
    <p class="text-xs muted mt-3">
      call_outcome and call_summary are filled in after each call and appear on the call result page.
    </p>
  </div>
</div>

@endsection
