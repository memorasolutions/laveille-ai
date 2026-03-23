{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
@if ($paginator->hasPages())
    @push('head')
        @if (!$paginator->onFirstPage())
            <link rel="prev" href="{{ $paginator->previousPageUrl() }}">
        @endif

        @if ($paginator->hasMorePages())
            <link rel="next" href="{{ $paginator->nextPageUrl() }}">
        @endif
    @endpush
@endif
