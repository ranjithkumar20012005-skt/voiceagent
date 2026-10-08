{{-- Internal: create a client workspace and its first login. --}}
@extends('layouts.app')

@section('title', 'New client')

@section('content')

<div class="ph">
  <div class="ph-main">
    <div class="eyebrow"><a href="{{ route('internal.clients.index') }}">Clients</a></div>
    <h1>New client</h1>
    <p class="ph-sub">Creates the workspace and the first login. Map their hosted agent on the next screen.</p>
  </div>
</div>

@if ($errors->any())
  <div class="card mb-4">
    <div class="card-b">
      <span class="badge badge-danger"><span class="dot"></span>Check the form</span>
      <ul class="text-sm mt-2">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  </div>
@endif

<form method="POST" action="{{ route('internal.clients.store') }}" class="card" style="max-width:640px">
  @csrf
  <div class="card-b stack">

    <div class="field">
      <label class="label" for="business_name">Business name</label>
      <input class="input" id="business_name" name="business_name" required maxlength="160"
             value="{{ old('business_name') }}" placeholder="ABC Hospital">
      <span class="text-xs muted">Names the workspace. The client sees this as their own business name.</span>
    </div>

    <div class="field">
      <label class="label" for="contact_name">Contact name</label>
      <input class="input" id="contact_name" name="contact_name" required maxlength="120"
             value="{{ old('contact_name') }}" placeholder="Priya Menon">
    </div>

    <div class="field">
      <label class="label" for="email">Sign-in email</label>
      <input class="input" type="email" id="email" name="email" required maxlength="191"
             value="{{ old('email') }}" placeholder="priya@abchospital.com">
    </div>

    <div class="field">
      <label class="label" for="password">Password <span class="opt">(optional)</span></label>
      <input class="input" type="password" id="password" name="password" autocomplete="new-password" placeholder="Leave blank to generate one">
      <span class="text-xs muted">If left blank a strong password is generated and shown once, for you to pass on.</span>
    </div>

    <div class="field">
      <label class="label" for="password_confirmation">Confirm password</label>
      <input class="input" type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password">
    </div>

    <div class="field">
      <label class="label" for="default_language">Default language <span class="opt">(optional)</span></label>
      <select class="input" id="default_language" name="default_language">
        <option value="">Not set</option>
        @foreach ($languages as $language)
          <option value="{{ $language }}" @selected(old('default_language') === $language)>{{ $language }}</option>
        @endforeach
      </select>
    </div>

  </div>
  <div class="card-f">
    <button type="submit" class="btn btn-primary btn-sm">Create client</button>
    <a href="{{ route('internal.clients.index') }}" class="btn btn-ghost btn-sm">Cancel</a>
  </div>
</form>

@endsection
