{{--
    Topbar: menu toggle (mobile), workspace name, agent status, the single
    New Call button and the account menu. What powers the agent is not shown
    here -- only whether calling is available.
--}}
@php $initials = collect(explode(' ', trim(auth()->user()->name)))->filter()->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode(''); @endphp

<header class="shell-topbar">
  <button type="button" class="btn btn-secondary btn-icon btn-sm menu-btn" data-nav-open aria-label="Open menu" aria-controls="appSidebar" aria-expanded="false">
    <i class="icon-menu"></i>
  </button>

  <div class="topbar-title">
    <strong>@yield('title', 'Dashboard')</strong>
    <span>Voice agent operations</span>
  </div>

  <div class="topbar-actions">
    <span class="status-pill {{ $agentConfigured ? 'ok' : 'off' }}"
          title="{{ $agentConfigured ? 'The voice agent is ready to place calls.' : 'The voice agent is not available.' }}">
      <span class="dot {{ $agentConfigured ? 'live' : '' }}"></span>
      <span class="hide-sm">{{ $agentConfigured ? 'Agent active' : 'Agent unavailable' }}</span>
    </span>

    <button type="button" class="btn btn-primary btn-sm" data-new-call>
      <i class="icon-phone"></i><span class="hide-sm">New Call</span>
    </button>

    <div class="user-menu">
      <button type="button" class="user-btn" data-dropdown="userMenu" aria-haspopup="true" aria-expanded="false">
        <span class="avatar">{{ strtoupper($initials ?: '?') }}</span>
        <span class="user-name">{{ auth()->user()->name }}</span>
        <i class="icon-chevron-down text-xs muted"></i>
      </button>

      <div class="dropdown" id="userMenu" role="menu">
        <div class="dropdown-head">
          <strong>{{ auth()->user()->name }}</strong>
          <span>{{ auth()->user()->email }}</span>
        </div>
        <a href="{{ route('settings.index') }}" class="dropdown-item" role="menuitem"><i class="icon-settings"></i> Settings</a>
        <a href="{{ route('usage.index') }}" class="dropdown-item" role="menuitem"><i class="icon-gauge"></i> Usage</a>
        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button type="submit" class="dropdown-item danger" role="menuitem"><i class="icon-log-out"></i> Sign out</button>
        </form>
      </div>
    </div>
  </div>
</header>
