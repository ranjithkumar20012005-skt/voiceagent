{{--
    Create / edit an agent.

    Everything here is in our own vocabulary. The provider's config shape, prompt
    skeleton and identifiers are AgentBlueprint's concern, not this form's.
--}}
@extends('layouts.app')

@section('title', $editing ? 'Edit ' . $agent->name : 'Create Agent')

@section('content')

<div class="ph">
  <div class="ph-main">
    <div class="eyebrow"><a href="{{ route('agents.index') }}">Agents</a></div>
    <h1>{{ $editing ? 'Edit ' . $agent->name : 'Create an agent' }}</h1>
    <p class="ph-sub">
      {{ $editing
          ? 'Changes are pushed to the live agent as soon as you save.'
          : 'Describe the call in your own words. The agent is built and goes live automatically.' }}
    </p>
  </div>
</div>

@if ($errors->any())
  <div class="callout callout-warning mb-4">
    <i class="icon-circle-alert"></i>
    <div>
      <strong>Check the form.</strong>
      <ul class="text-sm mt-1">
        @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
      </ul>
    </div>
  </div>
@endif

<form method="POST"
      action="{{ $editing ? route('agents.update', $agent) : route('agents.store') }}"
      class="stack" style="max-width:860px">
  @csrf
  @if ($editing) @method('PUT') @endif

  {{-- ------------------------------------------------------- Basics --}}
  <div class="card">
    <div class="card-h"><div><h2>1. The basics</h2><p>What this agent is and how it will be used</p></div></div>
    <div class="card-b stack">

      <div class="grid cols-2">
        <div class="field">
          <label class="label" for="name">Agent name</label>
          <input class="input" id="name" name="name" required maxlength="80"
                 value="{{ old('name', $agent->name) }}" placeholder="Maya">
          <span class="text-xs muted">The name it gives on the call.</span>
        </div>
        <div class="field">
          <label class="label" for="role">Role <span class="opt">(optional)</span></label>
          <input class="input" id="role" name="role" maxlength="120"
                 value="{{ old('role', $agent->role) }}" placeholder="appointment assistant">
        </div>
      </div>

      <div class="field">
        <label class="label" for="description">Description <span class="opt">(optional)</span></label>
        <input class="input" id="description" name="description" maxlength="255"
               value="{{ old('description', $agent->description) }}"
               placeholder="Books appointments for the cardiology department">
      </div>

      <div class="field">
        <label class="label" for="calling_mode">Calling mode</label>
        <select class="input" id="calling_mode" name="calling_mode" required>
          @foreach ($modes as $value => $label)
            <option value="{{ $value }}" @selected(old('calling_mode', $agent->calling_mode) === $value)>{{ $label }}</option>
          @endforeach
        </select>
        <span class="text-xs muted">Instant Leads calls one person at a time, Bulk Campaigns works through a list, Inbound answers calls.</span>
      </div>

    </div>
  </div>

  {{-- ------------------------------------------------ Language & voice --}}
  <div class="card">
    <div class="card-h"><div><h2>2. Language and voice</h2><p>How it sounds</p></div></div>
    <div class="card-b stack">

      <div class="grid cols-2">
        <div class="field">
          <label class="label" for="default_language">Primary language</label>
          <select class="input" id="default_language" name="default_language" required>
            @foreach ($languages as $language)
              <option value="{{ $language }}" @selected(old('default_language', $agent->default_language) === $language)>{{ $language }}</option>
            @endforeach
          </select>
          <span class="text-xs muted">The language the call opens in.</span>
        </div>
        <div class="field">
          <label class="label" for="voice">Voice</label>
          <select class="input" id="voice" name="voice">
            <option value="">Use the default voice</option>
            @foreach ($voices as $id => $voice)
              <option value="{{ $id }}" @selected(old('voice', $agent->voice) === $id)>{{ $voice['label'] }}</option>
            @endforeach
          </select>
        </div>
      </div>

      <div class="field">
        <label class="label">Additional languages <span class="opt">(optional)</span></label>
        <div class="row wrap" style="gap:10px 18px">
          @php $secondary = old('secondary_languages', $agent->secondary_languages ?? []); @endphp
          @foreach ($languages as $language)
            <label class="row" style="gap:6px; align-items:center; font-size:13px">
              <input type="checkbox" name="secondary_languages[]" value="{{ $language }}"
                     @checked(in_array($language, (array) $secondary, true))>
              {{ $language }}
            </label>
          @endforeach
        </div>
        <span class="text-xs muted">The agent switches to a caller's language automatically when more than one is set.</span>
      </div>

    </div>
  </div>

  {{-- ------------------------------------------------- Conversation --}}
  <div class="card">
    <div class="card-h"><div><h2>3. The conversation</h2><p>What it says and what it is trying to achieve</p></div></div>
    <div class="card-b stack">

      <div class="field">
        <label class="label" for="first_message">Opening line <span class="opt">(optional)</span></label>
        <input class="input" id="first_message" name="first_message" maxlength="300"
               value="{{ old('first_message', $agent->first_message) }}"
               placeholder="Hello, this is Maya calling from ABC Hospital. Is this a good time to talk?">
        <span class="text-xs muted">Leave blank and one is written for you. Kept under 25 words and translated into every language you selected.</span>
      </div>

      <div class="field">
        <label class="label" for="goal">What does a successful call look like?</label>
        <input class="input" id="goal" name="goal" required maxlength="300"
               value="{{ old('goal', $agent->goal) }}"
               placeholder="book or confirm an appointment with the right department">
      </div>

      <div class="field">
        <label class="label" for="instructions">What should the agent do on the call?</label>
        <textarea class="input" id="instructions" name="instructions" rows="8" required maxlength="6000"
                  placeholder="Ask which department they need.&#10;Offer the next available slots and confirm one.&#10;If they are busy, ask when to call back.&#10;Answer basic questions about timings and location.">{{ old('instructions', $agent->instructions) }}</textarea>
        <span class="text-xs muted">One instruction per line. These become the flow, with a stop after each question so the agent waits for an answer.</span>
      </div>

      <div class="field">
        <label class="label" for="objection_handling">Things it must never do <span class="opt">(optional)</span></label>
        <textarea class="input" id="objection_handling" name="objection_handling" rows="4" maxlength="3000"
                  placeholder="Never give medical advice or discuss symptoms.&#10;Never quote prices or insurance cover.">{{ old('objection_handling', $agent->objection_handling) }}</textarea>
        <span class="text-xs muted">One per line. Added as guardrails on top of the standard ones every agent gets.</span>
      </div>

      <div class="field">
        <label class="label" for="closing_message">Closing line <span class="opt">(optional)</span></label>
        <input class="input" id="closing_message" name="closing_message" maxlength="300"
               value="{{ old('closing_message', $agent->closing_message) }}"
               placeholder="Thank you for your time.">
      </div>

    </div>
  </div>

  {{-- -------------------------------------------------- Business data --}}
  <div class="card">
    <div class="card-h"><div><h2>4. Business details</h2><p>Values that change from call to call, and answers to common questions</p></div></div>
    <div class="card-b stack">

      <div class="field">
        <label class="label">Call variables <span class="opt">(optional)</span></label>
        @php $vars = old('variable_names', array_keys((array) ($agent->business_variables ?? []))); @endphp
        @php $varDescs = old('variable_descriptions', array_values((array) ($agent->business_variables ?? []))); @endphp
        <div id="varRows" class="stack" style="gap:8px">
          @for ($i = 0; $i < max(2, count($vars)); $i++)
            <div class="row" style="gap:8px">
              <input class="input" name="variable_names[]" maxlength="40" style="flex:0 0 200px"
                     value="{{ $vars[$i] ?? '' }}" placeholder="policy_number">
              <input class="input" name="variable_descriptions[]" maxlength="160" style="flex:1"
                     value="{{ $varDescs[$i] ?? '' }}" placeholder="What this value is">
            </div>
          @endfor
        </div>
        <span class="text-xs muted">The agent is given these for each person it calls. Name on the left, what it means on the right.</span>
      </div>

      <div class="field">
        <label class="label">Common questions <span class="opt">(optional)</span></label>
        @php $fq = old('faq_questions', array_keys((array) ($agent->faqs ?? []))); @endphp
        @php $fa = old('faq_answers', array_values((array) ($agent->faqs ?? []))); @endphp
        <div class="stack" style="gap:8px">
          @for ($i = 0; $i < max(2, count($fq)); $i++)
            <div class="row" style="gap:8px">
              <input class="input" name="faq_questions[]" maxlength="160" style="flex:0 0 240px"
                     value="{{ $fq[$i] ?? '' }}" placeholder="opening hours">
              <input class="input" name="faq_answers[]" maxlength="400" style="flex:1"
                     value="{{ $fa[$i] ?? '' }}" placeholder="Nine in the morning to seven in the evening, Monday to Saturday">
            </div>
          @endfor
        </div>
        <span class="text-xs muted">Only answers you supply here are given. Anything else is passed to a colleague rather than guessed.</span>
      </div>

    </div>
  </div>

  {{-- ------------------------------------------------- Call behaviour --}}
  <div class="card">
    <div class="card-h"><div><h2>5. Call behaviour</h2></div></div>
    <div class="card-b">
      <div class="grid cols-2">
        <div class="field">
          <label class="label" for="max_call_seconds">Maximum call length</label>
          <select class="input" id="max_call_seconds" name="max_call_seconds" required>
            @foreach ([180 => '3 minutes', 300 => '5 minutes', 420 => '7 minutes', 600 => '10 minutes', 900 => '15 minutes'] as $sec => $label)
              <option value="{{ $sec }}" @selected((int) old('max_call_seconds', $agent->max_call_seconds) === $sec)>{{ $label }}</option>
            @endforeach
          </select>
        </div>
        <div class="field">
          <label class="label" for="temperature">Conversation style</label>
          <select class="input" id="temperature" name="temperature" required>
            @foreach (['0.20' => 'Very consistent - sticks closely to the script', '0.40' => 'Balanced (recommended)', '0.70' => 'More varied - sounds less scripted'] as $t => $label)
              <option value="{{ $t }}" @selected(number_format((float) old('temperature', $agent->temperature), 2, '.', '') === $t)>{{ $label }}</option>
            @endforeach
          </select>
        </div>
      </div>
    </div>
  </div>

  <div class="row" style="gap:8px">
    <button type="submit" class="btn btn-primary">
      {{ $editing ? 'Save and update agent' : 'Create agent' }}
    </button>
    <a href="{{ $editing ? route('agents.show', $agent) : route('agents.index') }}" class="btn btn-ghost">Cancel</a>
    @if ($editing)
      <a href="{{ route('agents.preview', $agent) }}" class="btn btn-secondary">Preview prompt</a>
    @endif
  </div>

</form>

@endsection
