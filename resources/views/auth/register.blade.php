{{--
    Create an account.

    Deliberately the same shell, classes and copy register as the sign-in page --
    same aside, same field markup, same password toggle -- so signup is part of
    the existing design system rather than a second look.
--}}
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create Account · {{ config('app.name') }}</title>
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
      <h2>Your own calling workspace.</h2>
      <p>Create an account and you get a workspace of your own: your agents, your customers, your campaigns and your results, visible only to you.</p>

      <ul class="auth-points">
        <li>Build a calling agent in minutes</li>
        <li>Your data stays private to your workspace</li>
        <li>Invite your team when you are ready</li>
        <li>Transcripts, leads and usage in one place</li>
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
      <h1>Create your account</h1>
      <p class="intro">Set up your workspace. You can change any of this later.</p>

      @if ($errors->any())
        <div class="auth-error" role="alert">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
          <span>{{ $errors->first() }}</span>
        </div>
      @endif

      <form method="POST" action="{{ route('register.submit') }}" novalidate>
        @csrf

        <div class="field">
          <label class="label" for="name">Your name</label>
          <input type="text" name="name" id="name" class="input {{ $errors->has('name') ? 'invalid' : '' }}" required autofocus
                 autocomplete="name" value="{{ old('name') }}" placeholder="Likhitha Samala" maxlength="120"
                 @if ($errors->has('name')) aria-invalid="true" @endif>
        </div>

        <div class="field">
          <label class="label" for="business_name">Business name <span class="opt">(optional)</span></label>
          <input type="text" name="business_name" id="business_name" class="input {{ $errors->has('business_name') ? 'invalid' : '' }}"
                 autocomplete="organization" value="{{ old('business_name') }}" placeholder="Your company" maxlength="160">
          <span class="text-xs muted">Used to name your workspace. Defaults to your own name.</span>
        </div>

        <div class="field">
          <label class="label" for="email">Email address</label>
          <input type="email" name="email" id="email" class="input {{ $errors->has('email') ? 'invalid' : '' }}" required
                 autocomplete="username" value="{{ old('email') }}" placeholder="you@company.com" maxlength="191"
                 @if ($errors->has('email')) aria-invalid="true" @endif>
        </div>

        <div class="field">
          <label class="label" for="password">Password</label>
          <div class="input-wrap">
            <input type="password" name="password" id="password" class="input {{ $errors->has('password') ? 'invalid' : '' }}" required
                   autocomplete="new-password" placeholder="••••••••" style="padding-right:64px">
            <button type="button" class="toggle" id="pwToggle" aria-controls="password" aria-label="Show password">Show</button>
          </div>
          <span class="text-xs muted">At least 8 characters, with letters and numbers.</span>
        </div>

        <div class="field">
          <label class="label" for="password_confirmation">Confirm password</label>
          <input type="password" name="password_confirmation" id="password_confirmation" class="input" required
                 autocomplete="new-password" placeholder="••••••••">
        </div>

        <button type="submit" class="btn btn-primary btn-lg btn-block" id="createBtn">Create Account &rarr;</button>
      </form>

      <p class="auth-help">Already have an account? <a href="{{ route('login') }}">Sign in</a>.</p>
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
    var b = document.getElementById('createBtn');
    b.disabled = true;
    b.textContent = 'Creating account…';
  });
})();
</script>

</body>

</html>
