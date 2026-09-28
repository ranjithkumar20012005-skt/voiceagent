@props(['icon' => 'icon-inbox', 'title', 'tone' => null, 'compact' => false])
<div {{ $attributes->merge(['class' => 'empty' . ($compact ? ' compact' : '')]) }}>
  <div class="empty-icon {{ $tone }}"><i class="{{ $icon }}"></i></div>
  <h3>{{ $title }}</h3>
  @if (trim($slot) !== '')
    <p>{{ $slot }}</p>
  @endif
  {{ $action ?? '' }}
</div>
