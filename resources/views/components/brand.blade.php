{{-- Product wordmark ("VoiceAgent"). No vendor name appears here. --}}
@props(['href' => route('home')])
<a href="{{ $href }}" {{ $attributes->merge(['class' => 'brand']) }}>
  <span class="brand-mark" aria-hidden="true">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
      <path d="M2 10v3"/><path d="M6 6v11"/><path d="M10 3v18"/><path d="M14 8v7"/><path d="M18 5v13"/><path d="M22 10v3"/>
    </svg>
  </span>
  <span>Voice<span class="brand-tag">Agent</span></span>
</a>
