<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'Dashboard') · {{ config('app.name') }}</title>

  <link rel="icon" type="image/svg+xml" href="{{ asset('assets/img/logo.svg') }}">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Inter+Tight:wght@500;600;700&family=JetBrains+Mono:wght@400;500&display=swap">
  <link rel="stylesheet" href="{{ asset('assets/css/zentic-icons.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}?v={{ filemtime(public_path('assets/css/app.css')) }}">
  @stack('head')
</head>

<body class="@yield('body_class')">

  <div class="shell-scrim" data-nav-close></div>

  @include('components.sidebar')

  <div class="shell-main">
    @include('components.header')

    <main class="shell-content" id="main">
      @if (session('status'))
        <div class="callout callout-success flash" role="status">
          <i class="icon-circle-check"></i><div>{{ session('status') }}</div>
        </div>
      @endif

      @if ($errors->any())
        <div class="callout callout-danger flash" role="alert">
          <i class="icon-circle-alert"></i>
          <div>
            <strong>Please fix the following:</strong>
            <ul>
              @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
          </div>
        </div>
      @endif

      @yield('content')
    </main>

    @include('components.footer')
  </div>

  @include('components.new-call-modal')

  <script src="{{ asset('assets/js/app.js') }}?v={{ filemtime(public_path('assets/js/app.js')) }}"></script>
  @stack('scripts')
</body>

</html>
