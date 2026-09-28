{{--
    Public marketing shell.

    Kept entirely separate from layouts/app.blade.php: nothing here touches the
    authenticated application, its sidebar or its data. No vendor name and no
    configuration value is rendered on this page.
--}}
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'AI Voice Agents') · {{ config('app.name') }}</title>
  <meta name="description" content="@yield('meta_description', 'Automate outbound customer conversations with natural AI voice agents. Launch calls, identify high-intent leads and track every outcome.')">

  <link rel="icon" type="image/svg+xml" href="{{ asset('assets/img/logo.svg') }}">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Inter+Tight:wght@500;600;700&family=JetBrains+Mono:wght@400;500&display=swap">
  <link rel="stylesheet" href="{{ asset('assets/css/site.css') }}?v={{ filemtime(public_path('assets/css/site.css')) }}">
</head>

<body>

@include('public.partials.header')

<main>
  @yield('content')
</main>

<footer class="site-footer">
  <div class="container">
    <div class="footer-grid">
      <div>
        <x-brand />
        <p>AI voice agents that call your customers, understand the answer and hand your team a clean list of who to follow up.</p>
      </div>
      <div>
        <h4>Product</h4>
        <ul>
          <li><a href="{{ route('home') }}#how-it-works">How it works</a></li>
          <li><a href="{{ route('home') }}#why-us">Why choose us</a></li>
          <li><a href="{{ route('home') }}#pricing">Pricing</a></li>
        </ul>
      </div>
      <div>
        <h4>Resources</h4>
        <ul>
          <li><a href="{{ route('home') }}#faq">FAQs</a></li>
          <li><a href="mailto:{{ config('pricing.contact_email') }}">Contact sales</a></li>
        </ul>
      </div>
      <div>
        <h4>Account</h4>
        <ul>
          <li><a href="{{ route('login') }}">Sign in</a></li>
          <li><a href="{{ route('start') }}">Start free</a></li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <span>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</span>
      @if (! str_ends_with((string) config('pricing.contact_email'), '@example.com'))
        <a href="mailto:{{ config('pricing.contact_email') }}">{{ config('pricing.contact_email') }}</a>
      @endif
    </div>
  </div>
</footer>

<script>
(function () {
  // Mobile navigation.
  var header = document.getElementById('siteHeader');
  var burger = document.getElementById('siteBurger');
  if (header && burger) {
    burger.addEventListener('click', function () {
      var open = header.classList.toggle('open');
      burger.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    header.addEventListener('click', function (e) {
      if (e.target.closest('.mobile-panel a')) {
        header.classList.remove('open');
        burger.setAttribute('aria-expanded', 'false');
      }
    });
    window.addEventListener('resize', function () {
      if (window.innerWidth > 900) header.classList.remove('open');
    });
  }
})();
</script>
@stack('scripts')

</body>

</html>
