{{--
    The conversation, as chronological messages.

    One component used by both the call result page and the conversation page, so
    the rendering stays identical. All parsing happens in TranscriptPresenter --
    this file only lays out what it returns, and never touches a provider payload.

    @param \App\Services\Presenters\TranscriptPresenter $transcript
--}}
@props(['transcript'])

@if ($transcript->hasTranscript())

  @if ($transcript->isPartial())
    <div class="callout callout-warning mb-3">
      <i class="icon-circle-alert"></i>
      <div>This call is still in progress, so the conversation below may be incomplete.</div>
    </div>
  @endif

  <div class="transcript">
    @foreach ($transcript->turns() as $turn)
      <div class="turn {{ $turn['is_agent'] ? 'turn-agent' : 'turn-customer' }}">
        <div class="turn-who">
          <span class="turn-avatar">
            <i class="{{ $turn['is_agent'] ? 'icon-bot' : 'icon-user' }}"></i>
          </span>
          <strong>{{ $turn['label'] }}</strong>
        </div>
        <div class="turn-body">
          <p>{{ $turn['text'] }}</p>
          @if ($turn['original'])
            <p class="turn-original">{{ $turn['original'] }}</p>
          @endif
        </div>
      </div>
    @endforeach
  </div>

  <style>
    .transcript { display: flex; flex-direction: column; gap: 14px; }
    .turn { display: grid; grid-template-columns: 150px 1fr; gap: 14px; align-items: start; }
    .turn-who { display: flex; align-items: center; gap: 8px; font-size: 13px; padding-top: 8px; }
    .turn-avatar { display: inline-flex; align-items: center; justify-content: center;
                   width: 26px; height: 26px; border-radius: 50%; flex: none;
                   background: var(--surface-2, #eef1f5); font-size: 13px; }
    .turn-agent .turn-avatar { background: var(--brand-soft, #e6f0ff); }
    .turn-body { background: var(--surface-2, #f6f7f9); border-radius: 10px; padding: 10px 14px; }
    .turn-agent .turn-body { background: var(--brand-soft, #eff5ff); }
    .turn-body p { margin: 0; line-height: 1.55; }
    .turn-original { margin-top: 6px !important; font-size: 13px; opacity: .65; }
    @media (max-width: 720px) {
      .turn { grid-template-columns: 1fr; gap: 6px; }
      .turn-who { padding-top: 0; }
    }
  </style>

@else

  <x-empty icon="icon-message-square" title="No conversation recorded">
    This call has no transcript. That is normal when the call was not answered, or
    ended before anybody spoke.
  </x-empty>

@endif
