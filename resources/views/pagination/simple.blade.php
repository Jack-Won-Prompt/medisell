@if ($paginator->hasPages())
    @php
        $current = $paginator->currentPage();
        $last = $paginator->lastPage();
        $window = 2;                     // 현재 페이지 양옆 표시 개수
        $start = max(1, $current - $window);
        $end = min($last, $current + $window);
    @endphp
    <nav class="pagination" role="navigation" aria-label="페이지 네비게이션">
        {{-- 이전 --}}
        @if ($paginator->onFirstPage())
            <span aria-disabled="true">&lsaquo;</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev">&lsaquo;</a>
        @endif

        {{-- 첫 페이지 + 생략부호 --}}
        @if ($start > 1)
            <a href="{{ $paginator->url(1) }}">1</a>
            @if ($start > 2)<span class="dots" aria-disabled="true">&hellip;</span>@endif
        @endif

        {{-- 현재 주변 윈도우 --}}
        @for ($page = $start; $page <= $end; $page++)
            @if ($page == $current)
                <span class="active"><span>{{ $page }}</span></span>
            @else
                <a href="{{ $paginator->url($page) }}">{{ $page }}</a>
            @endif
        @endfor

        {{-- 생략부호 + 마지막 페이지 --}}
        @if ($end < $last)
            @if ($end < $last - 1)<span class="dots" aria-disabled="true">&hellip;</span>@endif
            <a href="{{ $paginator->url($last) }}">{{ $last }}</a>
        @endif

        {{-- 다음 --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next">&rsaquo;</a>
        @else
            <span aria-disabled="true">&rsaquo;</span>
        @endif
    </nav>
@endif
