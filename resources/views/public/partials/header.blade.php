<header class="site-header" id="siteHeader">
  <div class="container site-header-inner">
    <x-brand />

    <nav class="site-nav" aria-label="Main">
      <a href="{{ route('home') }}#how-it-works">How It Works</a>
      <a href="{{ route('home') }}#why-us">Why Choose Us</a>
      <a href="{{ route('home') }}#pricing">Pricing</a>
      <a href="{{ route('home') }}#faq">FAQs</a>
    </nav>

    <div class="site-actions">
      @auth
        <a href="{{ route('dashboard') }}" class="btn btn-primary">Go to Dashboard</a>
      @else
        <a href="{{ route('login') }}" class="btn btn-plain">Sign In</a>
        <a href="{{ route('start') }}" class="btn btn-primary">Start Free</a>
      @endauth

      <button type="button" class="burger" id="siteBurger" aria-label="Menu" aria-expanded="false" aria-controls="mobilePanel">
        <svg class="open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
        <svg class="close" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18"/></svg>
      </button>
    </div>
  </div>

  <div class="mobile-panel" id="mobilePanel">
    <a class="link" href="{{ route('home') }}#how-it-works">How It Works</a>
    <a class="link" href="{{ route('home') }}#why-us">Why Choose Us</a>
    <a class="link" href="{{ route('home') }}#pricing">Pricing</a>
    <a class="link" href="{{ route('home') }}#faq">FAQs</a>
    <div class="row">
      @auth
        <a href="{{ route('dashboard') }}" class="btn btn-primary" style="grid-column:1/-1">Go to Dashboard</a>
      @else
        <a href="{{ route('login') }}" class="btn btn-secondary">Sign In</a>
        <a href="{{ route('start') }}" class="btn btn-primary">Start Free</a>
      @endauth
    </div>
  </div>
</header>
