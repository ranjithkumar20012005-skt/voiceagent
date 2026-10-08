@php
    /*
     | Product navigation, grouped by job. Every item points at a real route
     | backed by real data. Knowledge Base and Tools are listed as "not
     | connected" because the voice platform owns them today -- their pages
     | say so plainly rather than pretending to work.
     */
    $groups = [
        'Overview' => [
            ['route' => 'dashboard', 'match' => 'dashboard', 'icon' => 'icon-layout-dashboard', 'label' => 'Dashboard'],
        ],
        // A client builds and runs their own agent here.
        'Agents' => [
            ['route' => 'agents.index', 'match' => ['agents.index', 'agents.show'], 'icon' => 'icon-bot', 'label' => 'My Agents'],
            ['route' => 'agents.create', 'match' => [
                'agents.create', 'agents.store', 'agents.edit', 'agents.update', 'agents.preview',
                'agents.provision', 'agents.setup', 'agents.setup.update', 'agents.sources.*',
                'agents.campaign.start', 'agents.schedule.*', 'agents.start', 'agents.pause',
            ], 'icon' => 'icon-plus', 'label' => 'Create Agent'],
        ],
        'Calling' => [
            ['route' => 'calling.index',   'match' => 'calling.*',   'icon' => 'icon-phone-outgoing', 'label' => 'New Call'],
            ['route' => 'campaigns.index', 'match' => 'campaigns.*', 'icon' => 'icon-megaphone',      'label' => 'Campaigns'],
            ['route' => 'customers.index', 'match' => 'customers.*', 'icon' => 'icon-users',          'label' => 'Customers'],
            ['route' => 'imports.index',   'match' => 'imports.*',   'icon' => 'icon-upload',         'label' => 'Imports'],
        ],
        'Results' => [
            ['route' => 'leads.index',     'match' => 'leads.*',     'icon' => 'icon-user-check',     'label' => 'Leads & Results'],
            ['route' => 'conversations.index', 'match' => 'conversations.*', 'icon' => 'icon-message-square', 'label' => 'Conversations'],
            ['route' => 'calls.index',     'match' => 'calls.*',     'icon' => 'icon-phone-call',     'label' => 'Call Logs'],
            ['route' => 'callbacks.index', 'match' => 'callbacks.*', 'icon' => 'icon-calendar-clock', 'label' => 'Callbacks',
             'count' => $pendingCallbacks + $overdueCallbacks, 'alert' => $overdueCallbacks > 0],
        ],
        'Automation' => [
            ['route' => 'automations.index', 'match' => 'automations.*', 'icon' => 'icon-zap', 'label' => 'Automations'],
        ],
        'Build' => [
            ['route' => 'knowledge.index', 'match' => 'knowledge.*', 'icon' => 'icon-book-open', 'label' => 'Knowledge Base', 'soon' => 'Off'],
            ['route' => 'tools.index',     'match' => 'tools.*',     'icon' => 'icon-wrench',    'label' => 'Tools',          'soon' => 'Off'],
        ],
        // "Providers" is gone from the client's navigation: it exposed the voice
        // platform's configuration state, which is ours and not theirs to see.
        'Deploy' => [
            ['route' => 'phone-numbers.index', 'match' => 'phone-numbers.*', 'icon' => 'icon-hash', 'label' => 'Phone Numbers'],
        ],
        'Insights' => [
            ['route' => 'analytics.index', 'match' => 'analytics.*', 'icon' => 'icon-chart-column', 'label' => 'Analytics'],
        ],
        'Settings' => [
            ['route' => 'usage.index',    'match' => 'usage.*',    'icon' => 'icon-gauge',    'label' => 'Usage'],
            ['route' => 'settings.index', 'match' => 'settings.*', 'icon' => 'icon-settings', 'label' => 'Settings'],
        ],
    ];

    // Our own team only. Client users never have this flag, so the group is not
    // rendered for them at all -- and the routes 404 regardless.
    if (auth()->user()?->is_internal_admin) {
        $groups['Internal'] = [
            ['route' => 'internal.clients.index', 'match' => 'internal.clients.*', 'icon' => 'icon-boxes', 'label' => 'Clients'],
            ['route' => 'internal.agent-requests.index', 'match' => 'internal.agent-requests.*', 'icon' => 'icon-inbox', 'label' => 'Agent Requests',
             'count' => $pendingAgentRequests, 'alert' => $pendingAgentRequests > 0],
        ];
    }
@endphp

<aside class="shell-sidebar" id="appSidebar" aria-label="Main navigation">
  <div class="shell-brand">
    <x-brand :href="route('dashboard')" />
    <button type="button" class="btn btn-ghost btn-icon btn-sm close-nav" data-nav-close aria-label="Close menu">
      <i class="icon-x"></i>
    </button>
  </div>

  <nav class="shell-nav">
    @foreach ($groups as $label => $items)
      <div class="nav-group">
        <div class="nav-group-label">{{ $label }}</div>
        @foreach ($items as $item)
          @continue(! Route::has($item['route']))
          @php $active = request()->routeIs(...(array) $item['match']); @endphp
          <a href="{{ route($item['route']) }}" class="nav-link {{ $active ? 'active' : '' }}" @if ($active) aria-current="page" @endif>
            <i class="{{ $item['icon'] }}"></i>
            <span>{{ $item['label'] }}</span>
            @if (! empty($item['count']))
              <span class="nav-count {{ ! empty($item['alert']) ? 'alert' : '' }}">{{ $item['count'] }}</span>
            @elseif (! empty($item['soon']))
              <span class="nav-soon" title="Not connected to the voice agent yet">{{ $item['soon'] }}</span>
            @endif
          </a>
        @endforeach
      </div>
    @endforeach
  </nav>

  <div class="shell-foot">
    <span class="dot {{ $agentConfigured ? 'live' : '' }}" style="color: {{ $agentConfigured ? 'var(--green-600)' : 'var(--amber-700)' }}"></span>
    {{ $agentConfigured ? 'Calling is available' : 'Calling is not configured' }}
  </div>
</aside>
