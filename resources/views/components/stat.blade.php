@props(['label', 'value', 'icon' => null, 'tone' => null, 'hint' => null, 'valueTone' => null, 'key' => null])
<div {{ $attributes->merge(['class' => 'card stat']) }}>
  <div>
    <div class="stat-label">{{ $label }}</div>
    <div class="stat-value {{ $valueTone }}" @if ($key) data-kpi="{{ $key }}" @endif>{{ $value }}</div>
    @if ($hint)
      <div class="stat-hint">{{ $hint }}</div>
    @endif
  </div>
  @if ($icon)
    <div class="stat-icon {{ $tone }}"><i class="{{ $icon }}"></i></div>
  @endif
</div>
