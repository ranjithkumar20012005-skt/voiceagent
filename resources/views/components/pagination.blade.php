@if ($paginator->hasPages())
  <nav class="pager" aria-label="Pagination">
    <span>Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ number_format($paginator->total()) }}</span>

    <div class="pager-links">
      @if ($paginator->onFirstPage())
        <span class="disabled" aria-hidden="true"><i class="icon-arrow-left"></i></span>
      @else
        <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous page"><i class="icon-arrow-left"></i></a>
      @endif

      @foreach ($elements as $element)
        @if (is_string($element))
          <span class="disabled">{{ $element }}</span>
        @endif

        @if (is_array($element))
          @foreach ($element as $page => $url)
            @if ($page == $paginator->currentPage())
              <span class="current" aria-current="page">{{ $page }}</span>
            @else
              <a href="{{ $url }}">{{ $page }}</a>
            @endif
          @endforeach
        @endif
      @endforeach

      @if ($paginator->hasMorePages())
        <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next page"><i class="icon-arrow-right"></i></a>
      @else
        <span class="disabled" aria-hidden="true"><i class="icon-arrow-right"></i></span>
      @endif
    </div>
  </nav>
@endif
