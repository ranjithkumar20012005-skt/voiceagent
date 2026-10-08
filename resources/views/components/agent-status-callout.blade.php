{{--
    The "not provisioned yet" callout, shared by the agent page and its Setup
    page -- same three states and the same copy as agent-card.blade.php, so an
    agent doesn't read as "being built" on one page and "stuck" on another.

    Polls agents.card for this agent and reloads the page the moment it stops
    coming back with data-poll="1", so neither page is left showing a stale
    "still being set up" message after it no longer is.
--}}
@props(['agent'])

@unless ($agent->isProvisioned())

  @if ($agent->isBuilding())

    <div class="callout callout-warning mb-4" data-agent-status="{{ $agent->id }}">
      <i class="icon-circle-alert"></i>
      <div style="flex:1">
        <strong>Building</strong>
        <div class="text-sm">This agent is still being set up, so it cannot be started or placed on a campaign yet. This page will update on its own the moment it's ready.</div>
      </div>
    </div>

  @elseif ($agent->isAwaitingTeam())

    <div class="callout callout-warning mb-4" data-agent-status="{{ $agent->id }}">
      <i class="icon-circle-alert"></i>
      <div style="flex:1">
        <strong>With our team</strong>
        <div class="text-sm">Final setup is with our team, so this agent cannot be started or placed on a campaign yet. No need to refresh -- it will be ready here as soon as they finish it.</div>
        @if (auth()->user()?->is_internal_admin && $agent->error_message)
          {{-- Internal diagnostic. Never shown to a client. --}}
          <div class="text-xs muted mt-2">{{ $agent->error_message }}</div>
        @endif
      </div>
      @if (auth()->user()?->is_internal_admin)
        <form method="POST" action="{{ route('agents.provision', $agent) }}" class="inline-form">
          @csrf
          <button type="submit" class="btn btn-primary btn-sm">Retry setup</button>
        </form>
      @endif
    </div>

  @else

    {{-- status === ERROR: a real refusal, not a hand-off. Worth a client's own retry. --}}
    <div class="callout callout-danger mb-4" data-agent-status="{{ $agent->id }}">
      <i class="icon-circle-alert"></i>
      <div style="flex:1">
        <strong>This agent needs attention.</strong>
        <div class="text-sm">It cannot place calls yet.</div>
        @if (auth()->user()?->is_internal_admin && $agent->error_message)
          <div class="text-xs muted mt-2">{{ $agent->error_message }}</div>
        @endif
      </div>
      <form method="POST" action="{{ route('agents.provision', $agent) }}" class="inline-form">
        @csrf
        <button type="submit" class="btn btn-primary btn-sm">Retry setup</button>
      </form>
    </div>

  @endif

  @push('scripts')
    <script>
      (function () {
        var el = document.querySelector('[data-agent-status="{{ $agent->id }}"]');
        if (! el) return;

        (function poll() {
          if (! el.isConnected) return;
          fetch('{{ route('agents.card', $agent) }}', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.ok ? r.text() : Promise.reject(); })
            .then(function (html) {
              if (html.indexOf('data-poll="1"') === -1) {
                window.location.reload();
                return;
              }
              setTimeout(poll, {{ $agent->isBuilding() ? 3000 : 20000 }});
            })
            .catch(function () { setTimeout(poll, 10000); });
        })();
      })();
    </script>
  @endpush

@endunless
