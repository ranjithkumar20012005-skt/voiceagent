{{--
    Sign in.

    The authentication itself is unchanged -- same route, same POST, same
    validation and throttling. Only the presentation was rebuilt.
--}}
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign In · {{ config('app.name') }}</title>
  <link rel="icon" type="image/svg+xml" href="{{ asset('assets/img/logo.svg') }}">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Inter+Tight:wght@500;600;700&display=swap">
  <link rel="stylesheet" href="{{ asset('assets/css/site.css') }}?v={{ filemtime(public_path('assets/css/site.css')) }}">
</head>

<body>

<div class="auth">

  <aside class="auth-aside on-dark">
    <x-brand />

    <div>
      <h2>Conversations that turn into action.</h2>
      <p>Place calls, let the agent hold the conversation, and come back to a clean list of who is interested.</p>

      <ul class="auth-points">
        <li>Start an AI call in a few seconds</li>
        <li>Interested customers surfaced as hot leads</li>
        <li>Full transcript for every completed call</li>
        <li>Callbacks, campaigns and automations in one place</li>
      </ul>
    </div>

    <div class="auth-legal">&copy; {{ date('Y') }} {{ config('app.name') }}</div>
  </aside>

  <main class="auth-main">
    <div class="auth-top">
      <span class="mobile-brand"><x-brand /></span>
      <a href="{{ route('home') }}" class="back">&larr; Back to site</a>
    </div>

    <div class="auth-form">
      <h1>Sign in</h1>
      <p class="intro">Welcome back. Sign in to review your calling activity and start new AI calls.</p>

      @if ($errors->any())
        <div class="auth-error" role="alert">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
          <span>{{ $errors->first() }}</span>
        </div>
      @endif

      <form method="POST" action="{{ route('login.submit') }}" novalidate>
        @csrf

        <div class="field">
          <label class="label" for="email">Email address</label>
          <input type="email" name="email" id="email" class="input {{ $errors->has('email') ? 'invalid' : '' }}" required autofocus
                 autocomplete="username" value="{{ old('email') }}" placeholder="you@company.com"
                 @if ($errors->has('email')) aria-invalid="true" @endif>
        </div>

        <div class="field">
          <label class="label" for="password">Password</label>
          <div class="input-wrap">
            <input type="password" name="password" id="password" class="input {{ $errors->has('password') ? 'invalid' : '' }}" required
                   autocomplete="current-password" placeholder="••••••••" style="padding-right:64px">
            <button type="button" class="toggle" id="pwToggle" aria-controls="password" aria-label="Show password">Show</button>
          </div>
        </div>

        <button type="submit" class="btn btn-primary btn-lg btn-block" id="signInBtn">Sign In &rarr;</button>
      </form>

      <p class="auth-help">No account yet? <a href="{{ route('home') }}#pricing">Talk to us about access</a>.</p>
    </div>
  </main>

</div>

<style>
  .mobile-brand { display: none; }
  @media (max-width: 900px) { .mobile-brand { display: inline-flex; } }
</style>
<script>
(function () {
  var t = document.getElementById('pwToggle');
  var p = document.getElementById('password');
  t.addEventListener('click', function () {
    var show = p.type === 'password';
    p.type = show ? 'text' : 'password';
    t.textContent = show ? 'Hide' : 'Show';
    t.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
  });
  document.querySelector('form').addEventListener('submit', function () {
    var b = document.getElementById('signInBtn');
    b.disabled = true;
    b.textContent = 'Signing in…';
  });
})();
</script>

</body>

</html>
