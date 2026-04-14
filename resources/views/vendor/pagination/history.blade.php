@php
    $currentPage = $paginator->currentPage();
    $lastPage = max($paginator->lastPage(), 1);
@endphp

<nav role="navigation" aria-label="Pagination Navigation" class="flex items-center gap-1 text-[13px] text-slate-700">

    {{-- Back --}}
    @if ($currentPage <= 1)
        <span style="color:#94a3b8;cursor:default;user-select:none;padding:0 8px;display:flex;align-items:center;font-size:13px;">‹ Back</span>
    @else
        <a href="{{ $paginator->previousPageUrl() }}" style="color:#475569;padding:0 8px;display:flex;align-items:center;font-size:13px;text-decoration:none;" onmouseover="this.style.color='#0f172a'" onmouseout="this.style.color='#475569'">‹ Back</a>
    @endif

    {{-- Pages --}}
    @if ($lastPage <= 1)
        <span style="width:28px;height:28px;border-radius:50%;background:#2f88d8;color:#fff;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:500;">1</span>
    @else
        @foreach ($elements as $element)
            @if (is_string($element))
                <span style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;font-size:13px;color:#64748b;user-select:none;">...</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $currentPage)
                        <span style="width:28px;height:28px;border-radius:50%;background:#2f88d8;color:#fff;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:500;user-select:none;">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" style="width:28px;height:28px;border-radius:50%;color:#334155;display:flex;align-items:center;justify-content:center;font-size:13px;text-decoration:none;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='transparent'">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach
    @endif

    {{-- Next --}}
    @if ($currentPage >= $lastPage)
        <span style="color:#94a3b8;cursor:default;user-select:none;padding:0 8px;display:flex;align-items:center;font-size:13px;">Next ›</span>
    @else
        <a href="{{ $paginator->nextPageUrl() }}" style="color:#475569;padding:0 8px;display:flex;align-items:center;font-size:13px;text-decoration:none;" onmouseover="this.style.color='#0f172a'" onmouseout="this.style.color='#475569'">Next ›</a>
    @endif

</nav>