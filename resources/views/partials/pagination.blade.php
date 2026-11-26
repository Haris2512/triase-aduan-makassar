@if ($paginator->hasPages())
    <div class="pager">
        <div>
            Menampilkan <strong>{{ $paginator->firstItem() }}</strong>&ndash;<strong>{{ $paginator->lastItem() }}</strong>
            dari <strong>{{ number_format($paginator->total(), 0, ',', '.') }}</strong> aduan
        </div>
        <div class="pager-links">
            @if ($paginator->onFirstPage())
                <span class="btn btn-secondary btn-sm" style="opacity: 0.45; cursor: default;">&larr; Sebelumnya</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="btn btn-secondary btn-sm">&larr; Sebelumnya</a>
            @endif

            <span class="mono" style="padding: 0 8px; font-size: 12.5px;">
                Hal {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}
            </span>

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="btn btn-secondary btn-sm">Berikutnya &rarr;</a>
            @else
                <span class="btn btn-secondary btn-sm" style="opacity: 0.45; cursor: default;">Berikutnya &rarr;</span>
            @endif
        </div>
    </div>
@endif
