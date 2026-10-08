{{--
    One agent card on the Agents page.

    Three states:
      - building      -- automatic provisioning is in flight; shimmer + a
                          rotating line of "what it's doing" copy.
      - awaiting team  -- provisioning gave up and handed off to a person;
                          calm, no shimmer, nothing actively happening to animate.
      - ready          -- the normal card, unchanged from before this existed.

    The first two carry data-agent-id/data-poll so the page's polling script
    can fetch this same component again and swap it in once the state moves on
    -- see the script at the bottom of agents/index.blade.php. This view is
    rendered both inline in that page's grid and standalone by
    AgentController::cardFragment(), so it must stand on its own with no
    assumptions about what wraps it.
--}}
@props(['agent'])

@if ($agent->isBuilding())

  <div class="card agent-card agent-card-building" data-agent-id="{{ $agent->id }}" data-poll="1" data-interval="2500">
    <div class="card-b">
      <div class="agent-top">
        <div class="agent-avatar building"><i class="icon-bot"></i></div>
        <span class="badge badge-warning"><span class="dot live"></span>Building</span>
      </div>

      <h3>{{ $agent->name }}</h3>
      <p class="text-sm agent-building-copy" data-building-copy>Setting up your agent&hellip;</p>

      <div class="stack" style="gap:8px; margin-top:4px">
        <div class="skeleton-line" style="width:92%"></div>
        <div class="skeleton-line" style="width:78%"></div>
        <div class="skeleton-line" style="width:60%"></div>
      </div>

      <div class="agent-figures">
        <div><span class="skeleton-chip"></span><span class="figure-label">Today</span></div>
        <div><span class="skeleton-chip"></span><span class="figure-label">This month</span></div>
        <div><span class="skeleton-chip"></span><span class="figure-label">Answered</span></div>
        <div><span class="skeleton-chip"></span><span class="figure-label">Interested</span></div>
      </div>
    </div>
  </div>

@elseif ($agent->isAwaitingTeam())

  <div class="card agent-card" data-agent-id="{{ $agent->id }}" data-poll="1" data-interval="20000">
    <div class="card-b">
      <div class="agent-top">
        <div class="agent-avatar"><i class="icon-bot"></i></div>
        <span class="badge badge-warning"><span class="dot"></span>With our team</span>
      </div>

      <h3>{{ $agent->name }}</h3>
      <p class="agent-desc">{{ $agent->description ?: 'No description.' }}</p>

      <div class="callout callout-warning mt-3" style="margin:0">
        <i class="icon-circle-alert"></i>
        <div class="text-sm">Final setup is with our team. This agent will appear ready here as soon as they finish it -- no need to refresh.</div>
      </div>
    </div>
  </div>

@else

  <div class="card agent-card" data-agent-id="{{ $agent->id }}">
    <div class="card-b">
      <div class="agent-top">
        <div class="agent-avatar"><i class="icon-bot"></i></div>
        <div class="row wrap" style="justify-content:flex-end">
          <span class="badge {{ $agent->isActive() ? 'badge-success' : '' }}">
            <span class="dot"></span>{{ $agent->displayStatus() }}
          </span>
          @if ($agent->is_default)
            <span class="badge badge-brand">Primary</span>
          @endif
        </div>
      </div>

      <h3>{{ $agent->name }}</h3>
      <p class="agent-desc">{{ $agent->description ?: 'No description.' }}</p>

      <div class="agent-meta">
        <span><i class="icon-phone"></i>{{ $agent->phoneNumber ? \App\Support\PhoneNumber::display($agent->phoneNumber->phone_number) : 'No number assigned' }}</span>
        <span><i class="icon-radio"></i>{{ $agent->callingModeLabel() }}</span>
        <span><i class="icon-languages"></i>{{ $agent->default_language ?: 'Default language' }}</span>
      </div>

      {{-- Activity at a glance, so the list is useful without opening each one. --}}
      <div class="agent-figures">
        <div><span class="figure">{{ number_format($agent->calls_today_count) }}</span><span class="figure-label">Today</span></div>
        <div><span class="figure">{{ number_format($agent->calls_month_count) }}</span><span class="figure-label">This month</span></div>
        <div><span class="figure">{{ number_format($agent->answered_count) }}</span><span class="figure-label">Answered</span></div>
        <div><span class="figure text-good">{{ number_format($agent->interested_count) }}</span><span class="figure-label">Interested</span></div>
      </div>

      @if ($agent->last_activity_at)
        <div class="text-xs faint mt-3">Last call {{ $agent->last_activity_at->diffForHumans() }}</div>
      @else
        <div class="text-xs faint mt-3">No calls yet</div>
      @endif
    </div>

    <div class="card-f">
      <a href="{{ route('agents.show', $agent) }}" class="btn btn-secondary btn-sm" style="flex:1">Open</a>
      <a href="{{ route('calls.index') }}?agent={{ $agent->id }}" class="btn btn-ghost btn-sm">Calls</a>
    </div>
  </div>

@endif
