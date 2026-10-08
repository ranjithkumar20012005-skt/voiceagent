{{--
    My Agents. Build one with agents.create and it lands here, in this card
    layout, as soon as it exists.
--}}
@extends('layouts.app')

@section('title', 'My Agents')

@section('content')

<div class="ph">
  <div class="ph-main">
    <div class="eyebrow">Agents</div>
    <h1>My Agents</h1>
    <p class="ph-sub">The voice agents set up for your business. Calls, leads and transcripts from each one appear across your dashboard.</p>
  </div>
  <div class="ph-actions">
    <a href="{{ route('agents.create') }}" class="btn btn-primary btn-sm"><i class="icon-plus"></i> Create Agent</a>
  </div>
</div>

@if ($agents->isEmpty())

  <div class="card">
    <div class="card-b">
      <x-empty icon="icon-bot" tone="green" title="No agents yet">
        Click Create Agent above to build your first one.
      </x-empty>
    </div>
  </div>

@else

  <div class="grid cols-3">
    @foreach ($agents as $agent)
      <x-agent-card :agent="$agent" />
    @endforeach
  </div>

  @if ($agents->contains(fn ($a) => $a->isBuilding() || $a->isAwaitingTeam()))
    @push('scripts')
      <script>
        (function () {
          // A building card's own copy, so it reads like something is actually
          // happening rather than the same static line sitting there the whole time.
          var COPY = [
            'Setting up your agent…',
            'Writing its opening line…',
            'Teaching it your script…',
            'Connecting its voice…',
            'Almost there…',
          ];

          function cycleCopy(card) {
            var el = card.querySelector('[data-building-copy]');
            if (! el || ! card.isConnected) return;
            var i = COPY.indexOf(el.textContent.trim());
            el.textContent = COPY[(i + 1) % COPY.length];
            setTimeout(function () { cycleCopy(card); }, 2200);
          }

          function poll(card) {
            if (! card.isConnected) return;
            var id = card.dataset.agentId;
            var interval = parseInt(card.dataset.interval, 10) || 15000;

            fetch('/agents/' + id + '/card', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
              .then(function (r) { return r.ok ? r.text() : Promise.reject(); })
              .then(function (html) {
                var tmp = document.createElement('div');
                tmp.innerHTML = html.trim();
                var next = tmp.firstElementChild;
                if (! next || ! card.isConnected) return;

                if (next.dataset.poll === '1') {
                  // Still in progress -- swap in place (status/copy may have
                  // moved from "building" to "awaiting team") and keep polling.
                  card.replaceWith(next);
                  setTimeout(function () { poll(next); }, parseInt(next.dataset.interval, 10) || interval);
                  if (next.querySelector('[data-building-copy]')) cycleCopy(next);
                } else {
                  // Finished -- the real card takes its place.
                  next.classList.add('agent-card-reveal');
                  card.replaceWith(next);
                }
              })
              .catch(function () { setTimeout(function () { poll(card); }, interval); });
          }

          document.querySelectorAll('[data-poll="1"]').forEach(function (card) {
            var interval = parseInt(card.dataset.interval, 10) || 15000;
            setTimeout(function () { poll(card); }, interval);
            if (card.querySelector('[data-building-copy]')) cycleCopy(card);
          });
        })();
      </script>
    @endpush
  @endif

@endif

<div class="card mt-4">
  <div class="card-b">
    <h3 class="text-sm">Need a change?</h3>
    <p class="text-sm muted">
      Open an agent and edit it directly -- what it says, the language it speaks, its schedule and its lead sources are
      all yours to change.
    </p>
  </div>
</div>

@endsection
