@if ($paginator->hasPages())
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 py-2">
        <div class="text-muted small">
            Showing <strong class="text-dark">{{ $paginator->firstItem() }}</strong> to <strong class="text-dark">{{ $paginator->lastItem() }}</strong> of <strong class="text-dark">{{ $paginator->total() }}</strong> results
        </div>

        <nav aria-label="Page navigation">
            <ul class="pagination pagination-sm mb-0 align-items-center">
                {{-- Previous Page Link --}}
                @if ($paginator->onFirstPage())
                    <li class="page-item disabled" aria-disabled="true">
                        <span class="page-link px-2">‹ Previous</span>
                    </li>
                @else
                    <li class="page-item">
                        <a class="page-link px-2" href="{{ $paginator->previousPageUrl() }}" rel="prev">‹ Previous</a>
                    </li>
                @endif

                {{-- Pagination Elements --}}
                @foreach ($elements as $element)
                    {{-- "Three Dots" Separator --}}
                    @if (is_string($element))
                        <li class="page-item disabled" aria-disabled="true"><span class="page-link px-2">{{ $element }}</span></li>
                    @endif

                    {{-- Array Of Links --}}
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <li class="page-item active" aria-current="page"><span class="page-link px-3 fw-bold">{{ $page }}</span></li>
                            @else
                                <li class="page-item"><a class="page-link px-3" href="{{ $url }}">{{ $page }}</a></li>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                {{-- Next Page Link --}}
                @if ($paginator->hasMorePages())
                    <li class="page-item">
                        <a class="page-link px-2" href="{{ $paginator->nextPageUrl() }}" rel="next">Next ›</a>
                    </li>
                @else
                    <li class="page-item disabled" aria-disabled="true">
                        <span class="page-link px-2">Next ›</span>
                    </li>
                @endif
            </ul>
        </nav>
    </div>
@endif
